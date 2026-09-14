/**
 * Bestell-Dashboard JavaScript
 */

(function($) {
	'use strict';

	function escapeHtml(str) {
		if (str === null || str === undefined) {
			return '';
		}
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	const Dashboard = {
		refreshTimer: null,
		wakeLock: null,
		soundEnabled: true,
		lastOrderCount: 0,
		audio: null,
		completedCounts: {},
		completedOffsets: {},
		sortableInstances: [],
		isLoading: false,
		pendingActions: new Set(),
		currentFilter: 'all',
		allOrders: {},
		lastActivity: Date.now(),
		kdsTimerInterval: null,
		soundRepeatTimer: null,
		// Versatz zwischen Server- und Clientuhr. Ohne diese Korrektur würde
		// eine falsch gestellte Kassen-Uhr die Wartezeiten verfälschen.
		serverOffsetMs: (function() {
			const srv = parseInt(lbiteDashboard.serverTime, 10);
			return isNaN(srv) ? 0 : (srv * 1000) - Date.now();
		})(),

		/**
		 * Initialisierung
		 */
		init: function() {
			this.initAudio();
			this.initControls();
			this.bindFullscreenEvents();

			// Initial laden wenn Standort bereits gewählt ist
			const locationId = $('#lbite-board-location').val();
			if (locationId) {
				this.loadOrders();
			}

			// Initiale Farbe anwenden
			this.applyLocationColor(locationId);

			this.startAutoRefresh();
			this.startAutoReload();
			this.startWaitTimers();
		},

		/**
		 * Standort-Farbe auf Dropdown anwenden
		 */
		applyLocationColor: function(locationId) {
			const $select = $('#lbite-board-location');
			const colors = (lbiteDashboard.locationColors) || {};
			const color = locationId ? colors[locationId] : null;
			if (color) {
				$select.css({
					'border-color': color,
					'border-width': '2px',
					'box-shadow': '0 0 0 1px ' + color
				});
			} else {
				$select.css({
					'border-color': '',
					'border-width': '',
					'box-shadow': ''
				});
			}
		},

		/**
		 * Audio initialisieren
		 */
		initAudio: function() {
			if (lbiteDashboard.soundUrl) {
				this.audio = new Audio(lbiteDashboard.soundUrl);
			}
		},

		/**
		 * Kontrollen initialisieren
		 */
		initControls: function() {
			// Initial-Check: Board nur anzeigen wenn Standort gewählt
			this.toggleBoardVisibility();

			// Gespeicherte Einstellungen laden (vor dem initialen Wake-Lock-Check)
			this.loadSavedSettings();

			// Wake Lock initial aktivieren wenn Checkbox angehakt
			if ($('#lbite-wake-lock').is(':checked')) {
				this.requestWakeLock();
			}

			// Standort-Filter
			$('#lbite-board-location').on('change', () => {
				const locationId = $('#lbite-board-location').val();

				// Farbe anwenden
				this.applyLocationColor(locationId);

				// Standort speichern
				if (locationId) {
					$.ajax({
						url: lbiteDashboard.ajaxUrl,
						type: 'POST',
						data: {
							action: 'lbite_save_board_location',
							nonce: lbiteDashboard.nonce,
							location_id: locationId
						},
						error: () => {
							console.error('Error saving location');
						}
					});
				}

				// Board-Sichtbarkeit umschalten
				this.toggleBoardVisibility();

				// Bestellungen laden
				if (locationId) {
					this.loadOrders();
				}
			});

			// Bestell-Filter
			$('#lbite-board-filter').on('change', (e) => {
				this.currentFilter = $(e.target).val();
				if (this.allOrders) {
					this.renderOrders(this.allOrders, true);
				}
			});

			// Wake Lock
			$('#lbite-wake-lock').on('change', (e) => {
				if (e.target.checked) {
					this.requestWakeLock();
				} else {
					this.releaseWakeLock();
				}
				localStorage.setItem('lbite_dashboard_wake_lock', e.target.checked ? '1' : '0');
			});

			// Wake Lock nach Tab-Wechsel / App-Wechsel neu anfordern (Android)
			document.addEventListener('visibilitychange', () => {
				if (document.visibilityState === 'visible' && $('#lbite-wake-lock').is(':checked')) {
					this.requestWakeLock();
				}
			});

			// Sound
			$('#lbite-sound-enabled').on('change', (e) => {
				this.soundEnabled = e.target.checked;
				localStorage.setItem('lbite_dashboard_sound', this.soundEnabled ? '1' : '0');
				// Beim Ausschalten sofort verstummen, nicht erst beim nächsten Intervall.
				if (!this.soundEnabled) {
					this.updateSoundRepeat(0);
				}
			});
		},

		/**
		 * Wake Lock anfordern
		 */
		async requestWakeLock() {
			if (!('wakeLock' in navigator)) {
				this.showToast(lbiteDashboard.strings.wakeLockUnsupported || 'This browser does not support keeping the screen awake.', 'warning');
				$('#lbite-wake-lock').prop('checked', false);
				return;
			}

			try {
				this.wakeLock = await navigator.wakeLock.request('screen');

				this.wakeLock.addEventListener('release', () => {
					this.wakeLock = null;
					// Sofort neu anfordern falls Checkbox noch aktiv und Seite sichtbar
					if ($('#lbite-wake-lock').is(':checked') && document.visibilityState === 'visible') {
						this.requestWakeLock();
					}
				});
			} catch (err) {
				console.error('Wake Lock Fehler:', err);
				$('#lbite-wake-lock').prop('checked', false);
			}
		},

		/**
		 * Wake Lock freigeben
		 */
		releaseWakeLock() {
			if (this.wakeLock) {
				this.wakeLock.release();
				this.wakeLock = null;
			}
		},

		/**
		 * Einstellungen aus localStorage laden und anwenden
		 */
		loadSavedSettings: function() {
			const wakeLockSaved = localStorage.getItem('lbite_dashboard_wake_lock');
			if (wakeLockSaved !== null) {
				$('#lbite-wake-lock').prop('checked', wakeLockSaved === '1');
			}
			const soundSaved = localStorage.getItem('lbite_dashboard_sound');
			if (soundSaved !== null) {
				this.soundEnabled = soundSaved === '1';
				$('#lbite-sound-enabled').prop('checked', this.soundEnabled);
			}
		},

		/**
		 * Auto-Refresh starten
		 */
		startAutoRefresh: function() {
			this.refreshTimer = setInterval(() => {
				this.loadOrders(true);
			}, lbiteDashboard.refreshInterval);
		},

		/**
		 * Auto-Reload starten: lädt die Seite nach 15 Minuten Inaktivität neu.
		 * Schützt vor ungewolltem Reload bei laufenden Aktionen.
		 */
		startAutoReload: function() {
			const INACTIVITY_LIMIT = 15 * 60 * 1000;

			$(document).on('click touchstart keydown', () => {
				this.lastActivity = Date.now();
			});

			setInterval(() => {
				if ( Date.now() - this.lastActivity < INACTIVITY_LIMIT ) return;
				if ( $('#lbite-loading-overlay').is(':visible') ) return;
				if ( this.pendingActions.size > 0 ) return;
				location.reload();
			}, 60 * 1000);
		},

		/**
		 * Board-Sichtbarkeit umschalten
		 */
		toggleBoardVisibility: function() {
			const locationId = $('#lbite-board-location').val();

			if (locationId) {
				$('#lbite-no-location-message').css('display', 'none');
				$('#lbite-kanban-board').css('display', 'grid');
			} else {
				$('#lbite-no-location-message').css('display', 'block');
				$('#lbite-kanban-board').css('display', 'none');
			}
		},

		/**
		 * Lade-Overlay anzeigen
		 */
		showLoading: function(message = 'Laden...') {
			let $overlay = $('#lbite-loading-overlay');
			
			if ($overlay.length === 0) {
				$overlay = $('<div id="lbite-loading-overlay"></div>');
				$overlay.append('<div class="lbite-spinner"></div>');
				$overlay.append($('<p></p>').text(message));
				$('body').append($overlay);
			} else {
				$overlay.find('p').text(message);
			}
			
			$overlay.fadeIn(150);
		},

		/**
		 * Lade-Overlay ausblenden
		 */
		hideLoading: function() {
			$('#lbite-loading-overlay').fadeOut(150);
		},

		/**
		 * Button-Lade-Status setzen
		 */
		setButtonLoading: function($btn, loading) {
			if (loading) {
				$btn.data('original-text', $btn.html());
				$btn.html('<span class="lbite-btn-spinner">⏳</span>').prop('disabled', true);
			} else {
				$btn.html($btn.data('original-text')).prop('disabled', false);
			}
		},

		/**
		 * Bestellungen laden
		 */
		loadOrders: function(silent = false) {
			const locationId = $('#lbite-board-location').val();

			// Nur laden wenn Standort gewählt
			if (!locationId) {
				return;
			}

			// Verhindern von doppelten Anfragen
			if (this.isLoading && !silent) {
				return;
			}

			if (!silent) {
				this.isLoading = true;
				// Nur beim ersten Laden Overlay zeigen
				if (this.lastOrderCount === 0) {
					this.showLoading(lbiteDashboard.strings.loadingOrders || 'Loading orders...');
				}
			}

			$.ajax({
				url: lbiteDashboard.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lbite_get_orders',
					nonce: lbiteDashboard.nonce,
					location_id: locationId
				},
				success: (response) => {
					if (response.success && response.data.orders) {
						this.allOrders = response.data.orders;
						this.completedCounts = response.data.completed_counts || {};
						const prevOffsets = this.completedOffsets;
						this.completedOffsets = {};
						Object.keys(this.completedCounts).forEach(key => {
							this.completedOffsets[key] = 3;
						});
						this.renderOrders(response.data.orders, silent);
						// Bei stillem Refresh: extra geladene abgeschlossene Bestellungen neu abrufen.
						if (silent) {
							Object.keys(prevOffsets).forEach(key => {
								if (prevOffsets[key] > 3 && this.completedOffsets.hasOwnProperty(key)) {
									this.loadMoreCompleted(key);
								}
							});
						}
					}
				},
				error: () => {
					if (!silent) {
						window.lbiteNotify && window.lbiteNotify.error(lbiteDashboard.strings.loadOrdersError || 'Error loading orders');
					}
				},
				complete: () => {
					this.isLoading = false;
					this.hideLoading();
				}
			});
		},

	/**
	 * Bestellungen rendern
	 */
	renderOrders: function(ordersByStatus, silent) {
		this.allOrders = ordersByStatus;

		let totalOrders = 0;
		let activeOrders = 0; // Nur nicht-abgeschlossene Bestellungen

		if (lbiteDashboard.kanbanCustomizationActive && Array.isArray(lbiteDashboard.kanbanColumns)) {
			// Feature aktiv: generisch über die konfigurierten Spalten iterieren.
			lbiteDashboard.kanbanColumns.forEach(col => {
				const status = col.key;
				let orders = ordersByStatus[status] || [];
				const $column = $('#lbite-column-' + status);

				if (this.currentFilter === 'table') {
					orders = orders.filter(o => !!o.table_id);
				} else if (this.currentFilter === 'takeaway') {
					orders = orders.filter(o => !o.table_id);
				}

				totalOrders += orders.length;

				if (!col.counts_as_completed) {
					activeOrders += orders.length;
				}

				$column.empty();

				orders.forEach(order => {
					$column.append(this.createOrderCard(order, status));
				});

				if (col.counts_as_completed) {
					const total = this.completedCounts[status] || 0;
					const offset = this.completedOffsets[status] || 3;
					if (total > offset) {
						const remainingCount = total - offset;
						const $loadMoreBtn = $('<button class="lbite-load-more-completed"></button>')
							.attr('data-column', status)
							.text(`📋 ${remainingCount} ` + (lbiteDashboard.strings.moreOrders || 'more order(s)'))
							.on('click', () => this.loadMoreCompleted(status));
						$column.append($loadMoreBtn);
					}
				}
			});
		} else {
			// Feature aus: unverändertes Verhalten wie vor F30.
			Object.keys(ordersByStatus).forEach(status => {
				let orders = ordersByStatus[status];
				const $column = $('#lbite-column-' + status);

				// Filter anwenden
				if (this.currentFilter === 'table') {
					orders = orders.filter(o => !!o.table_id);
				} else if (this.currentFilter === 'takeaway') {
					orders = orders.filter(o => !o.table_id);
				}

				totalOrders += orders.length;

				// Zähle nur aktive Bestellungen (nicht abgeschlossen) für Sound-Trigger
				if (status !== 'completed') {
					activeOrders += orders.length;
				}

				// Leer machen
				$column.empty();

				// Bestellungen rendern
				orders.forEach(order => {
					$column.append(this.createOrderCard(order, status));
				});

				// "Mehr laden" Button bei abgeschlossenen Bestellungen
				const completedTotal  = this.completedCounts['completed'] || 0;
				const completedOffset = this.completedOffsets['completed'] || 3;
				if (status === 'completed' && completedTotal > completedOffset) {
					const remainingCount = completedTotal - completedOffset;
					const $loadMoreBtn = $('<button class="lbite-load-more-completed"></button>')
						.text(`📋 ${remainingCount} ${lbiteDashboard.strings.moreOrders || 'more order(s)'}`)
						.on('click', () => this.loadMoreCompleted());
					$column.append($loadMoreBtn);
				}
			});
		}

		// Neue Bestellung erkannt (auch bei Auto-Refresh prüfen)
		if (activeOrders > this.lastOrderCount) {
			this.playNotificationSound();
		}

		this.lastOrderCount = activeOrders;

		// Wiederholter Alarmton, solange in der ersten Spalte etwas liegt.
		const firstColumnKey = (Array.isArray(lbiteDashboard.kanbanColumns) && lbiteDashboard.kanbanColumns.length)
			? lbiteDashboard.kanbanColumns[0].key
			: 'incoming';
		const pendingOrders = (ordersByStatus[firstColumnKey] || []).length;
		this.updateSoundRepeat(pendingOrders);

		// Drag & Drop nach jedem Render neu initialisieren (Spalten wurden geleert/neu befüllt).
		if (lbiteDashboard.kanbanDragDropEnabled) {
			this.initDragDrop();
		}
	},

		/**
		 * Bestellungs-Karte erstellen (kompaktes Layout: Artikel prominent, Fusszeile sekundär)
		 */
		createOrderCard: function(order, currentStatus) {
			const $card = $('<div class="lbite-kanban-card"></div>').attr('data-order-id', order.id);
			if (order.is_future && lbiteDashboard.futureDimmingEnabled) {
				$card.addClass('lbite-kanban-card--future');
			}

			// Badge-Zeile: Bestelltyp + Zeit
			const $badge = $('<div class="lbite-kanban-card-badge"></div>');

			const $timer = this.createWaitTimer(order, currentStatus);
			if ($timer) {
				$badge.append($timer);
			}
			if (order.type === 'later') {
				$badge.append($('<span class="lbite-order-type-later lbite-badge-chip"></span>').text(`⏰ ${order.pickup_time || ''}`));
			} else {
				$badge.append($('<span class="lbite-order-type-now lbite-badge-chip"></span>').text(`🔥 ${lbiteDashboard.strings.asap || 'ASAP'}`));
			}
			if (order.table_id) {
				const tableLabel = `🪑 ${order.table_name || lbiteDashboard.strings.table || 'Table'}`;
				$badge.append($('<span class="lbite-badge-chip lbite-badge-table"></span>').text(tableLabel));
			} else if (order.service_type === 'dine_in') {
				$badge.append($('<span class="lbite-badge-chip lbite-badge-dine-in"></span>').text(lbiteDashboard.strings.dineIn || 'Dine-in'));
			} else {
				$badge.append($('<span class="lbite-badge-chip lbite-badge-takeaway"></span>').text(lbiteDashboard.strings.takeaway || 'Take-away'));
			}
			if (order.is_open_tab) {
				$badge.append($('<span class="lbite-badge-chip lbite-badge-tab"></span>').text(lbiteDashboard.strings.tab || 'Tab'));
			}
			if (order.payment_method === 'split' && Array.isArray(order.split_payments) && order.split_payments.length > 0) {
				order.split_payments.forEach(split => {
					const pmLabel = (lbiteDashboard.paymentMethods && lbiteDashboard.paymentMethods[split.method])
						? lbiteDashboard.paymentMethods[split.method]
						: split.method;
					const amount = (lbiteDashboard.currency || '') + ' ' + parseFloat(split.amount).toFixed(2);
					$badge.append($('<span class="lbite-badge-chip lbite-badge-payment"></span>').text(`${pmLabel} ${amount}`));
				});
			} else if (order.payment_method) {
				const pmLabel = (lbiteDashboard.paymentMethods && lbiteDashboard.paymentMethods[order.payment_method])
					? lbiteDashboard.paymentMethods[order.payment_method]
					: order.payment_method;
				$badge.append($('<span class="lbite-badge-chip lbite-badge-payment"></span>').text(pmLabel));
			}
			$card.append($badge);

			// Artikel-Liste (Hauptinhalt)
			const $items = $('<div class="lbite-kanban-card-items"></div>');
			let lastRound = null;
			order.items.forEach(item => {
				if (order.is_open_tab && item.round > 0 && item.round !== lastRound) {
					const roundLabel = (lbiteDashboard.strings.round || 'Round') + ' ' + item.round;
					$items.append($('<div class="lbite-kanban-round-divider"></div>').text(`— ${roundLabel} —`));
					lastRound = item.round;
				}
				const $itemDiv = $('<div class="lbite-kanban-card-item"></div>');
				$itemDiv.append($('<span class="lbite-item-qty"></span>').text(`${item.quantity}×`));
				$itemDiv.append($('<span class="lbite-item-name"></span>').text(` ${item.name}`));
				if (item.meta) {
					$itemDiv.append($('<div class="lbite-item-meta lbite-item-config"></div>').html(item.meta));
				}
				if (item.note) {
					$itemDiv.append($('<div class="lbite-item-meta lbite-item-note-badge"></div>').text(`✎ ${item.note}`));
				}
				$items.append($itemDiv);
			});
			$card.append($items);

			// Notizen (kompakt, nur wenn vorhanden)
			if (order.notes) {
				$card.append($('<div class="lbite-kanban-card-notes"></div>').text(`📝 ${order.notes}`));
			}

			// Fusszeile: Nr + Name + Buttons
			const $footer = $('<div class="lbite-kanban-card-footer"></div>');
			const customerNameRaw = order.customer && order.customer.trim() ? order.customer.trim() : '';
			const footerParts = [`#${order.number}`];
			if (order.date) footerParts.push(order.date);
			if (customerNameRaw) footerParts.push(customerNameRaw);
			$footer.append($('<span class="lbite-card-footer-info"></span>').text(footerParts.join(' · ')));

			const $btnGroup = $('<span class="lbite-card-footer-btns"></span>');

			if (lbiteDashboard.kanbanCustomizationActive && Array.isArray(lbiteDashboard.kanbanColumns)) {
				// Feature aktiv: Vorwärts-/Zurück-Button aus den Spalten-Nachbarn ableiten.
				const columns  = lbiteDashboard.kanbanColumns;
				const colIndex = columns.findIndex(c => c.key === currentStatus);
				const col      = colIndex >= 0 ? columns[colIndex] : null;
				const prevCol  = colIndex > 0 ? columns[colIndex - 1] : null;
				const nextCol  = (colIndex >= 0 && colIndex < columns.length - 1) ? columns[colIndex + 1] : null;

				if (prevCol) {
					const $bBtn = $('<button class="lbite-status-button lbite-status-button--back"></button>')
						.text('← ' + (lbiteDashboard.strings.back || 'Back'))
						.on('click', (e) => { e.stopPropagation(); this.moveToNextStatus(order.id, prevCol.key); });
					$btnGroup.append($bBtn);
				}

				if (nextCol) {
					const nextLabel = nextCol.counts_as_completed ? ('✓ ' + nextCol.label) : (nextCol.label + ' →');
					const $sBtn = $('<button class="lbite-status-button"></button>')
						.addClass(`lbite-status-button-${currentStatus}`)
						.text(nextLabel)
						.on('click', (e) => { e.stopPropagation(); this.moveToNextStatus(order.id, nextCol.key); });
					$btnGroup.append($sBtn);
				}

				if (!col || !col.counts_as_completed) {
					const $cBtn = $('<button class="lbite-cancel-button"></button>')
						.attr('title', lbiteDashboard.strings.cancelOrder)
						.text('✕')
						.on('click', (e) => { e.stopPropagation(); this.cancelOrder(order.id); });
					$btnGroup.append($cBtn);
				}
			} else {
				// Feature aus: unverändertes Verhalten wie vor F30.
				const statusButtons = {
					'incoming':  { next: 'preparing', label: lbiteDashboard.strings.startPreparation },
					'preparing': { next: 'completed',  label: lbiteDashboard.strings.completed },
					'completed': null
				};
				const statusButton = statusButtons[currentStatus];
				if (statusButton) {
					const $sBtn = $('<button class="lbite-status-button"></button>')
						.addClass(`lbite-status-button-${currentStatus}`)
						.text(statusButton.label)
						.on('click', (e) => { e.stopPropagation(); this.moveToNextStatus(order.id, statusButton.next); });
					$btnGroup.append($sBtn);
				}

				// Stornieren-Button
				if (currentStatus !== 'completed') {
					const $cBtn = $('<button class="lbite-cancel-button"></button>')
						.attr('title', lbiteDashboard.strings.cancelOrder)
						.text('✕')
						.on('click', (e) => { e.stopPropagation(); this.cancelOrder(order.id); });
					$btnGroup.append($cBtn);
				}
			}

			// Beleg-Button
			const $rBtn = $('<button class="lbite-receipt-button"></button>')
				.attr('title', lbiteDashboard.strings.sendReceipt || 'Send receipt')
				.on('click', (e) => { e.stopPropagation(); this.sendReceipt(order.id, order.has_email); });
			$rBtn.append($('<span class="dashicons dashicons-email-alt"></span>'));
			$btnGroup.append($rBtn);

			// Druck-Button: Klick druckt das Küchenticket, langer Druck bzw.
			// Rechtsklick öffnet die Auswahl der drei Bonvorlagen.
			const $pBtn = $('<button class="lbite-print-button"></button>')
				.attr('title', lbiteDashboard.strings.printReceipt || 'Print')
				.on('click', (e) => { e.stopPropagation(); this.printOrder(order.id, 'kitchen'); })
				.on('contextmenu', (e) => {
					e.preventDefault();
					e.stopPropagation();
					this.showPrintMenu(order.id, $pBtn);
				});
			$pBtn.append($('<span class="dashicons dashicons-printer"></span>'));
			$btnGroup.append($pBtn);

			$footer.append($btnGroup);
			$card.append($footer);
			return $card;
		},

		/**
		 * Bestellungs-Status aktualisieren
		 */
		updateOrderStatus: function(orderId, newStatus) {
			// Doppelklick-Schutz
			const actionKey = `status_${orderId}`;
			if (this.pendingActions.has(actionKey)) {
				return;
			}
			this.pendingActions.add(actionKey);

			// Karte visuell als "in Bearbeitung" markieren
			const $card = $(`.lbite-kanban-card[data-order-id="${orderId}"]`);
			$card.css('opacity', '0.5').find('button').prop('disabled', true);

			$.ajax({
				url: lbiteDashboard.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lbite_update_order_status',
					nonce: lbiteDashboard.nonce,
					order_id: orderId,
					status: newStatus
				},
				success: (response) => {
					if (response.success) {
						window.lbiteNotify && window.lbiteNotify.success(lbiteDashboard.strings.orderUpdated || 'Status aktualisiert');
						this.loadOrders();
					} else {
						window.lbiteNotify && window.lbiteNotify.error(lbiteDashboard.strings.updateError || 'Fehler beim Aktualisieren');
						$card.css('opacity', '1').find('button').prop('disabled', false);
					}
				},
				error: () => {
					window.lbiteNotify && window.lbiteNotify.error(lbiteDashboard.strings.updateError || 'Fehler beim Aktualisieren');
					$card.css('opacity', '1').find('button').prop('disabled', false);
				},
				complete: () => {
					this.pendingActions.delete(actionKey);
				}
			});
		},

		/**
		 * Bestellung zum nächsten Status verschieben
		 */
		moveToNextStatus: function(orderId, newStatus) {
			this.updateOrderStatus(orderId, newStatus);
		},

		/**
		 * Drag & Drop zwischen Kanban-Spalten initialisieren (F30, nur wenn aktiviert)
		 * Muss nach jedem renderOrders()-Aufruf erneut laufen, da die Spalten dabei
		 * geleert/neu befüllt werden und alte SortableJS-Instanzen an entfernte DOM-Knoten
		 * gebunden sind.
		 */
		initDragDrop: function() {
			if (typeof Sortable === 'undefined') {
				return;
			}

			this.sortableInstances.forEach(instance => instance.destroy());
			this.sortableInstances = [];

			$('.lbite-kanban-cards').each((i, el) => {
				const instance = Sortable.create(el, {
					group: 'lbite-kanban',
					animation: 150,
					ghostClass: 'lbite-kanban-ghost',
					chosenClass: 'lbite-kanban-chosen',
					dragClass: 'lbite-kanban-dragging',
					onEnd: (evt) => {
						const orderId    = $(evt.item).data('order-id');
						const newColumn  = $(evt.to).closest('.lbite-kanban-column').data('drop-zone');
						const oldColumn  = $(evt.from).closest('.lbite-kanban-column').data('drop-zone');
						if (orderId && newColumn && newColumn !== oldColumn) {
							this.updateOrderStatus(orderId, newColumn);
						}
					}
				});
				this.sortableInstances.push(instance);
			});
		},

		/**
		 * Bestellung stornieren
		 */
		cancelOrder: function(orderId) {
			// Doppelklick-Schutz
			const actionKey = `cancel_${orderId}`;
			if (this.pendingActions.has(actionKey)) {
				return;
			}

			if (!confirm(lbiteDashboard.strings.confirmCancel || 'Do you really want to cancel this order?\n\nThe payment will be automatically refunded.')) {
				return;
			}

			this.pendingActions.add(actionKey);

			// Karte visuell als "in Bearbeitung" markieren
			const $card = $(`.lbite-kanban-card[data-order-id="${orderId}"]`);
			$card.css('opacity', '0.5').find('button').prop('disabled', true);

			this.showLoading(lbiteDashboard.strings.cancellingOrder || 'Cancelling order...');

			$.ajax({
				url: lbiteDashboard.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lbite_cancel_order',
					nonce: lbiteDashboard.nonce,
					order_id: orderId
				},
				success: (response) => {
					if (response.success) {
						const msg = (response.data && response.data.refunded)
							? (lbiteDashboard.strings.orderCancelled || 'Order cancelled and payment refunded')
							: (lbiteDashboard.strings.orderCancelledNoRefund || 'Order cancelled');
						window.lbiteNotify && window.lbiteNotify.success(msg);
						this.loadOrders();
					} else {
						window.lbiteNotify && window.lbiteNotify.error((lbiteDashboard.strings.cancelError || 'Error cancelling') + ': ' + escapeHtml(response.data && response.data.message ? response.data.message : (lbiteDashboard.strings.unknownError || 'Unknown error')));
						$card.css('opacity', '1').find('button').prop('disabled', false);
					}
				},
				error: () => {
					window.lbiteNotify && window.lbiteNotify.error(lbiteDashboard.strings.cancelOrderError || 'Error cancelling order');
					$card.css('opacity', '1').find('button').prop('disabled', false);
				},
				complete: () => {
					this.pendingActions.delete(actionKey);
					this.hideLoading();
				}
			});
		},

		/**
		 * Beleg per E-Mail senden
		 */
		sendReceipt: function(orderId, hasEmail) {
			var email = '';
			if (!hasEmail) {
				email = prompt(lbiteDashboard.strings.enterEmail || 'Enter customer email address:');
				if (!email) {
					return;
				}
			}
			var postData = {
				action: 'lbite_admin_send_receipt',
				nonce: lbiteDashboard.receiptNonce || lbiteDashboard.nonce,
				order_id: orderId
			};
			if (email) {
				postData.email = email;
			}
			$.post(lbiteDashboard.ajaxUrl, postData, function(response) {
				if (response.success) {
					window.lbiteNotify && window.lbiteNotify.success(response.data || '');
				} else {
					window.lbiteNotify && window.lbiteNotify.error(response.data || '');
				}
			});
		},

		/**
		 * Weitere abgeschlossene Bestellungen laden
	 */
	loadMoreCompleted: function(columnKey) {
		const column     = columnKey || 'completed';
		const locationId = $('#lbite-board-location').val();

		if (!locationId) {
			return;
		}

		$.ajax({
			url: lbiteDashboard.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lbite_load_more_completed',
				nonce: lbiteDashboard.nonce,
				location_id: locationId,
				offset: this.completedOffsets[column] || 3,
				column: column
			},
			success: (response) => {
				if (response.success && response.data.orders) {
					const $column = $('#lbite-column-' + column);

					// Button entfernen
					$column.find('.lbite-load-more-completed').remove();

					// Neue Bestellungen hinzufügen
					response.data.orders.forEach(order => {
						$column.append(this.createOrderCard(order, column));
					});

					// Offset aktualisieren
					this.completedOffsets[column] = (this.completedOffsets[column] || 3) + response.data.orders.length;

					// Button wieder hinzufügen wenn noch mehr vorhanden
					if (this.completedOffsets[column] < response.data.total_count) {
						const remainingCount = response.data.total_count - this.completedOffsets[column];
						const $loadMoreBtn = $('<button class="lbite-load-more-completed"></button>')
							.attr('data-column', column)
							.text(`📋 ${remainingCount} ` + (lbiteDashboard.strings.moreOrders || 'more order(s)'))
							.on('click', () => this.loadMoreCompleted(column));
						$column.append($loadMoreBtn);
					}
				}
			},
			error: () => {
				window.lbiteNotify.error(lbiteDashboard.strings.loadMoreError || 'Error loading more orders');
			}
		});
	},

	/**
	 * Bestellung ansehen
		 */
		viewOrder: function(orderId) {
			window.open(
				lbiteDashboard.orderEditUrl + '?post=' + orderId + '&action=edit',
				'_blank'
			);
		},

		/**
		 * Bestellung drucken
		 */
		/**
		 * Auswahl der Bonvorlagen einblenden
		 *
		 * @param {number} orderId Bestell-ID.
		 * @param {jQuery} $anchor Auslösender Knopf.
		 */
		showPrintMenu: function(orderId, $anchor) {
			$('.lbite-print-menu').remove();

			const types = lbiteDashboard.receiptTypes || {};
			const $menu = $('<div class="lbite-print-menu"></div>');

			Object.keys(types).forEach((key) => {
				$('<button type="button"></button>')
					.text(types[key])
					.on('click', (e) => {
						e.stopPropagation();
						$menu.remove();
						this.printOrder(orderId, key);
					})
					.appendTo($menu);
			});

			$anchor.closest('.lbite-kanban-card').append($menu);

			// Beim nächsten Klick irgendwo schliessen.
			setTimeout(() => {
				$(document).one('click', () => $menu.remove());
			}, 0);
		},

		printOrder: function(orderId, type) {
			// Holt eine echte 80-mm-Bonvorlage vom Server. Früher wurde das
			// HTML der Kanban-Karte samt Bedienknöpfen gedruckt.
			$.post(lbiteDashboard.ajaxUrl, {
				action: 'lbite_get_receipt',
				nonce: lbiteDashboard.nonce,
				order_id: orderId,
				type: type || 'kitchen'
			}).done((response) => {
				if (!response || !response.success || !response.data || !response.data.html) {
					this.showToast(lbiteDashboard.strings.printError || 'Could not create receipt', 'error');
					return;
				}

				const printWindow = window.open('', '', 'width=380,height=700');
				if (!printWindow) {
					this.showToast(lbiteDashboard.strings.popupBlocked || 'Please allow pop-ups to print', 'error');
					return;
				}

				printWindow.document.write(response.data.html);
				printWindow.document.title = response.data.title || '';
				printWindow.document.close();

				// Der Beleg bringt sein CSS eingebettet mit, es ist also nichts
				// nachzuladen – ein kurzer Aufschub reicht fürs Layout.
				printWindow.onload = () => printWindow.print();
				setTimeout(() => {
					try { printWindow.print(); } catch (e) {}
				}, 250);
			}).fail(() => {
				this.showToast(lbiteDashboard.strings.printError || 'Could not create receipt', 'error');
			});
		},

		/**
		 * Wartezeit-Timer für eine Bestellkarte erzeugen
		 *
		 * Reiner Client-Zusatz: der Bestellzeitstempel liegt bereits in den
		 * Kartendaten, es ist kein zusätzlicher Server-Roundtrip nötig.
		 * Vorbestellungen in der Zukunft bekommen keinen Timer – dort wäre
		 * die Zeit seit Bestelleingang ohne Aussage.
		 *
		 * @param {Object} order         Bestelldaten.
		 * @param {string} currentStatus Schlüssel der Spalte.
		 * @return {jQuery|null} Timer-Element oder null.
		 */
		createWaitTimer: function(order, currentStatus) {
			if (!lbiteDashboard.kdsTimerEnabled) {
				return null;
			}
			if (!order.created_ts || order.is_future) {
				return null;
			}
			if (this.columnCountsAsCompleted(currentStatus)) {
				return null;
			}

			const $timer = $('<span class="lbite-order-timer"></span>')
				.attr('data-created-ts', order.created_ts)
				.attr('data-prep-minutes', order.prep_minutes || 0)
				.attr('title', lbiteDashboard.strings.waiting || 'Waiting time');

			$timer.append('<span class="lbite-order-timer__ring" aria-hidden="true"></span>');
			$timer.append('<span class="lbite-order-timer__value"></span>');

			this.updateWaitTimer($timer);
			return $timer;
		},

        /**
		 * Prüfen, ob eine Spalte als abgeschlossen zählt
		 *
		 * @param {string} key Spalten-Schlüssel.
		 * @return {boolean}
		 */
		columnCountsAsCompleted: function(key) {
			const columns = lbiteDashboard.kanbanColumns || [];
			for (let i = 0; i < columns.length; i++) {
				if (columns[i].key === key) {
					return !!columns[i].counts_as_completed;
				}
			}
			// Ohne Spaltenkonfiguration gilt der Standardschlüssel.
			return key === 'completed';
		},

		/**
		 * Einen einzelnen Timer neu berechnen und einfärben
		 *
		 * @param {jQuery} $timer Timer-Element.
		 */
		updateWaitTimer: function($timer) {
			const createdTs = parseInt($timer.attr('data-created-ts'), 10);
			if (!createdTs) {
				return;
			}

			const nowMs   = Date.now() + this.serverOffsetMs;
			const minutes = Math.max(0, Math.floor((nowMs - (createdTs * 1000)) / 60000));

			const prepMinutes = parseInt($timer.attr('data-prep-minutes'), 10) || 0;
			// 0 bedeutet in den Einstellungen "Zubereitungszeit des Standorts
			// verwenden" – so passen die Schwellen bei mehreren Filialen
			// automatisch zur jeweiligen Küche.
			let warnAt = parseInt(lbiteDashboard.kdsWarnMinutes, 10) || 0;
			if (!warnAt) {
				warnAt = prepMinutes || 10;
			}
			let lateAt = parseInt(lbiteDashboard.kdsLateMinutes, 10) || 0;
			if (!lateAt) {
				lateAt = Math.round(warnAt * 1.5);
			}
			if (lateAt <= warnAt) {
				lateAt = warnAt + 1;
			}

			$timer.removeClass('is-warn is-late');
			if (minutes >= lateAt) {
				$timer.addClass('is-late');
				$timer.attr('title', lbiteDashboard.strings.overdue || 'Overdue');
			} else if (minutes >= warnAt) {
				$timer.addClass('is-warn');
			}

			// Ringfüllung: bis zur Spätschwelle proportional, danach voll.
			const ratio = Math.max(0, Math.min(1, minutes / lateAt));
			$timer.css('--lbite-timer-progress', (ratio * 360) + 'deg');

			$timer.find('.lbite-order-timer__value')
				.text(minutes + ' ' + (lbiteDashboard.strings.minutesShort || 'min'));
		},

		/**
		 * Alle sichtbaren Timer aktualisieren
		 *
		 * Läuft unabhängig vom Board-Refresh, damit die Wartezeit auch
		 * zwischen zwei Abfragen weiterzählt.
		 */
		startWaitTimers: function() {
			if (!lbiteDashboard.kdsTimerEnabled || this.kdsTimerInterval) {
				return;
			}
			this.kdsTimerInterval = setInterval(() => {
				$('.lbite-order-timer').each((i, el) => {
					this.updateWaitTimer($(el));
				});
			}, 30000);
		},

		/**
		 * Wiederholten Alarmton starten oder stoppen
		 *
		 * Wiederholt den Ton, solange mindestens eine Bestellung in der
		 * ersten Spalte liegt. Das Weiterschieben einer Bestellung ist damit
		 * die Quittierung – es braucht keine zusätzliche Schaltfläche.
		 *
		 * @param {number} pendingCount Anzahl Bestellungen in der ersten Spalte.
		 */
		updateSoundRepeat: function(pendingCount) {
			const interval = parseInt(lbiteDashboard.soundRepeatInterval, 10) || 0;

			if (!interval || !pendingCount || !this.soundEnabled) {
				if (this.soundRepeatTimer) {
					clearInterval(this.soundRepeatTimer);
					this.soundRepeatTimer = null;
				}
				return;
			}

			if (this.soundRepeatTimer) {
				return;
			}

			this.soundRepeatTimer = setInterval(() => {
				if (!this.soundEnabled) {
					clearInterval(this.soundRepeatTimer);
					this.soundRepeatTimer = null;
					return;
				}
				this.playNotificationSound();
			}, interval);
		},

		/**
		 * Kurze Rückmeldung einblenden
		 *
		 * Nutzt die Toast-Komponente des Design-Systems. Vorher gab es im
		 * Board gar keinen Melde-Mechanismus ausser einem blockierenden
		 * alert().
		 *
		 * @param {string} message Text.
		 * @param {string} type    success, warning, danger oder info.
		 */
		showToast: function(message, type) {
			let $stack = $('.lbite-toast-stack');
			if ($stack.length === 0) {
				$stack = $('<div class="lbite-toast-stack"></div>').appendTo('body');
			}

			const variant = ({ error: 'danger', warning: 'warning', success: 'success' })[type] || 'info';
			const $toast = $('<div class="lbite-toast"></div>')
				.addClass('lbite-toast--' + variant)
				.attr('role', 'status')
				.text(message)
				.appendTo($stack);

			setTimeout(() => {
				$toast.addClass('is-leaving');
				setTimeout(() => $toast.remove(), 300);
			}, 4000);
		},

		/**
		 * Sound abspielen
		 */
		playNotificationSound: function() {
			if (this.soundEnabled && this.audio) {
				this.audio.currentTime = 0;
				this.audio.play().catch(() => {
					// Autoplay vom Browser blockiert -> Aktivierungs-Button erneut anzeigen
					$('#lbite-activate-audio').show();
					$('#lbite-sound-toggle').hide();
				});
			}
		},

		/**
		 * Vollbild-Events binden
		 */
		bindFullscreenEvents: function() {
			$('#lbite-board-fullscreen').on('click', () => {
				this.toggleFullscreen();
			});

			// Vollbild-Status überwachen
			document.addEventListener('fullscreenchange', () => {
				this.updateFullscreenButton();
			});
		},

		/**
		 * Vollbild umschalten
		 */
		toggleFullscreen: function() {
			if (!document.fullscreenElement) {
				document.documentElement.requestFullscreen().catch(err => {
					console.error('Vollbild-Fehler:', err);
				});
			} else {
				if (document.exitFullscreen) {
					document.exitFullscreen();
				}
			}
		},

		/**
		 * Vollbild-Button aktualisieren
		 */
		updateFullscreenButton: function() {
			const $btn = $('#lbite-board-fullscreen');
			const $icon = $btn.find('.dashicons');

			if (document.fullscreenElement) {
				$icon.removeClass('dashicons-editor-expand').addClass('dashicons-editor-contract');
				$btn.attr('title', lbiteDashboard.strings.exitFullscreen);
				$('body').addClass('lbite-board-fullscreen-active');
			} else {
				$icon.removeClass('dashicons-editor-contract').addClass('dashicons-editor-expand');
				$btn.attr('title', lbiteDashboard.strings.fullscreen);
				$('body').removeClass('lbite-board-fullscreen-active');
			}
		}
	};

	// Global verfügbar machen
	window.Dashboard = Dashboard;

	// Initialisieren wenn Seite geladen ist
	$(document).ready(() => {
		if ($('.lbite-order-board').length > 0) {
			Dashboard.init();
		}
	});

})(jQuery);
