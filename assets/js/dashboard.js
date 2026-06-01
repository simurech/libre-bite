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
		completedCount: 0,
		completedOffset: 3,
		isLoading: false,
		pendingActions: new Set(),
		currentFilter: 'all',
		allOrders: {},
		lastActivity: Date.now(),

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
							console.error('Fehler beim Speichern des Standorts');
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
			});
		},

		/**
		 * Wake Lock anfordern
		 */
		async requestWakeLock() {
			if (!('wakeLock' in navigator)) {
				alert('Wake Lock wird von diesem Browser nicht unterstützt.');
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
						this.completedCount = response.data.completed_count || 0;
						const prevOffset = this.completedOffset;
						this.completedOffset = 3;
						this.renderOrders(response.data.orders, silent);
						// Bei stillem Refresh: extra geladene abgeschlossene Bestellungen neu abrufen.
						if (silent && prevOffset > 3) {
							this.loadMoreCompleted();
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
			if (status === 'completed' && this.completedCount > this.completedOffset) {
				const remainingCount = this.completedCount - this.completedOffset;
				const $loadMoreBtn = $('<button class="lbite-load-more-completed"></button>')
					.text(`📋 ${remainingCount} weitere Bestellung(en) anzeigen`)
					.on('click', () => this.loadMoreCompleted());
				$column.append($loadMoreBtn);
			}
		});

		// Neue Bestellung erkannt (auch bei Auto-Refresh prüfen)
		if (activeOrders > this.lastOrderCount) {
			this.playNotificationSound();
		}

		this.lastOrderCount = activeOrders;
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
			if (order.type === 'later') {
				$badge.append($('<span class="lbite-order-type-later lbite-badge-chip"></span>').text(`⏰ ${order.pickup_time || ''}`));
			} else {
				$badge.append($('<span class="lbite-order-type-now lbite-badge-chip"></span>').text('🔥 Sofort'));
			}
			if (order.table_id) {
				const tableLabel = order.table_name ? `🪑 ${order.table_name}` : '🪑 Tisch';
				$badge.append($('<span class="lbite-badge-chip lbite-badge-table"></span>').text(tableLabel));
			} else if (order.service_type === 'dine_in') {
				$badge.append($('<span class="lbite-badge-chip lbite-badge-dine-in"></span>').text(lbiteDashboard.strings.dineIn || 'Dine-in'));
			} else {
				$badge.append($('<span class="lbite-badge-chip lbite-badge-takeaway"></span>').text(lbiteDashboard.strings.takeaway || 'Take-away'));
			}
			if (order.payment_method) {
				const pmLabel = (lbiteDashboard.paymentMethods && lbiteDashboard.paymentMethods[order.payment_method])
					? lbiteDashboard.paymentMethods[order.payment_method]
					: order.payment_method;
				$badge.append($('<span class="lbite-badge-chip lbite-badge-payment"></span>').text(pmLabel));
			}
			$card.append($badge);

			// Artikel-Liste (Hauptinhalt)
			const $items = $('<div class="lbite-kanban-card-items"></div>');
			order.items.forEach(item => {
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

			// Status-Button
			const statusButtons = {
				'incoming':  { next: 'preparing', label: lbiteDashboard.strings.startPreparation },
				'preparing': { next: 'completed',  label: lbiteDashboard.strings.completed },
				'completed': null
			};
			const statusButton = statusButtons[currentStatus];
			const $btnGroup = $('<span class="lbite-card-footer-btns"></span>');
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

			// Beleg-Button
			const $rBtn = $('<button class="lbite-receipt-button"></button>')
				.attr('title', lbiteDashboard.strings.sendReceipt || 'Send receipt')
				.on('click', (e) => { e.stopPropagation(); this.sendReceipt(order.id, order.has_email); });
			$rBtn.append($('<span class="dashicons dashicons-email-alt"></span>'));
			$btnGroup.append($rBtn);

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
	loadMoreCompleted: function() {
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
				offset: this.completedOffset
			},
			success: (response) => {
				if (response.success && response.data.orders) {
					const $column = $('#lbite-column-completed');

					// Button entfernen
					$column.find('.lbite-load-more-completed').remove();

					// Neue Bestellungen hinzufügen
					response.data.orders.forEach(order => {
						$column.append(this.createOrderCard(order, 'completed'));
					});

					// Offset aktualisieren
					this.completedOffset += response.data.orders.length;

					// Button wieder hinzufügen wenn noch mehr vorhanden
					if (this.completedOffset < response.data.total_count) {
						const remainingCount = response.data.total_count - this.completedOffset;
						const $loadMoreBtn = $('<button class="lbite-load-more-completed"></button>')
							.text(`📋 ${remainingCount} ` + (lbiteDashboard.strings.moreOrders || 'more order(s)'))
							.on('click', () => this.loadMoreCompleted());
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
		printOrder: function(orderId) {
			const $card = $(`.lbite-kanban-card[data-order-id="${orderId}"]`);
			if ($card.length === 0) return;

			const printWindow = window.open('', '', 'width=300,height=600');
			
			// CSS Handles holen (einfachere Lösung für ein Popup ohne komplettes Head-Management)
			const cssUrl = $('link[id="lbite-order-board-css"]').attr('href') || '';
			
			printWindow.document.write(`
				<html>
				<head>
					<title>Bestellung #${orderId}</title>
					${cssUrl ? `<link rel="stylesheet" href="${cssUrl}">` : ''}
				</head>
				<body class="lbite-print-body">
					${$card.html()}
				</body>
				</html>
			`);
			printWindow.document.close();
			
			// Warten bis Styles geladen sind
			printWindow.onload = function() {
				printWindow.print();
			};
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
