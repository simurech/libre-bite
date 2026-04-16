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

		/**
		 * Initialisierung
		 */
		init: function() {
			this.initAudio();
			this.initDragDrop();
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
				
				// Browser-Restriktionen prüfen: Versuche Audio "stumm" anzuspielen
				this.audio.play().then(() => {
					// Autoplay funktioniert oder ist bereits erlaubt
					this.audio.pause();
					this.audio.currentTime = 0;
				}).catch(() => {
					// Autoplay blockiert -> Button anzeigen
					$('#lbite-activate-audio').show();
					$('#lbite-sound-toggle').hide();
				});
			}
		},

		/**
		 * Drag & Drop initialisieren
		 */
		initDragDrop: function() {
			const columns = document.querySelectorAll('.lbite-kanban-cards');

			columns.forEach(column => {
				new Sortable(column, {
					group: 'kanban',
					animation: 150,
					ghostClass: 'lbite-ghost',
					dragClass: 'lbite-dragging',
					onEnd: (evt) => {
						const orderId = evt.item.dataset.orderId;
						const newStatus = evt.to.closest('.lbite-kanban-column').dataset.status;

						if (orderId && newStatus) {
							this.updateOrderStatus(orderId, newStatus);
						}
					}
				});
			});
		},

		/**
		 * Kontrollen initialisieren
		 */
		initControls: function() {
			// Initial-Check: Board nur anzeigen wenn Standort gewählt
			this.toggleBoardVisibility();

			// Audio Aktivierung (Browser Workaround)
			$('#lbite-activate-audio').on('click', () => {
				if (this.audio) {
					this.audio.play().then(() => {
						this.audio.pause();
						this.audio.currentTime = 0;
						$('#lbite-activate-audio').hide();
						$('#lbite-sound-toggle').show();
						window.lbiteNotify && window.lbiteNotify.success('Sound-Benachrichtigungen aktiviert');
					});
				}
			});

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
			});

			// Sound Toggle
			$('#lbite-sound-toggle').on('click', () => {
				this.soundEnabled = !this.soundEnabled;

				const $btn = $('#lbite-sound-toggle');
				const $icon = $btn.find('.dashicons');

				if (this.soundEnabled) {
					$icon.removeClass('dashicons-controls-volumeoff').addClass('dashicons-controls-volumeon');
					$btn.find('span:not(.dashicons)').text(lbiteDashboard.strings.soundActive || 'Sound aktiv');
				} else {
					$icon.removeClass('dashicons-controls-volumeon').addClass('dashicons-controls-volumeoff');
					$btn.find('span:not(.dashicons)').text(lbiteDashboard.strings.soundInactive || 'Sound aus');
				}
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
				console.log('Wake Lock aktiviert');

				this.wakeLock.addEventListener('release', () => {
					console.log('Wake Lock deaktiviert');
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
		 * Auto-Refresh starten
		 */
		startAutoRefresh: function() {
			this.refreshTimer = setInterval(() => {
				this.loadOrders(true);
			}, lbiteDashboard.refreshInterval);
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
						this.completedOffset = 3; // Zurücksetzen beim Neuladen
						this.renderOrders(response.data.orders, silent);
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
		 * Bestellungs-Karte erstellen
		 */
		createOrderCard: function(order, currentStatus) {
			const $card = $('<div class="lbite-kanban-card"></div>').attr('data-order-id', order.id);
			
			// Header (Nummer & Name)
			const customerNameRaw = order.customer && order.customer.trim() ? order.customer.trim() : '';
			const $h3 = $('<h3></h3>').text(`#${order.number}${customerNameRaw ? ' - ' + customerNameRaw : ''}`);
			$card.append($h3);
			
			// Meta-Info
			const $meta = $('<div class="lbite-kanban-card-meta"></div>');
			if (order.type === 'later') {
				$meta.append($('<span class="lbite-order-type-later"></span>').text(`⏰ ${order.pickup_time || ''}`));
			} else {
				$meta.append($('<span class="lbite-order-type-now"></span>').text('🔥 Sofort'));
			}
			$meta.append('<br>🕐 ').append(document.createTextNode(order.date || ''));
			if (order.location) {
				$meta.append('<br>📍 ').append(document.createTextNode(order.location));
			}
			$card.append($meta);
			
			// Items
			const $items = $('<div class="lbite-kanban-card-items"></div>');
			order.items.forEach(item => {
				const $itemDiv = $('<div class="lbite-kanban-card-item"></div>');
				$itemDiv.append($('<strong></strong>').text(`${item.quantity}x ${item.name}`));
				if (item.meta) {
					$itemDiv.append($('<div class="lbite-item-meta"></div>').text(item.meta));
				}
				$items.append($itemDiv);
			});
			$card.append($items);
			
			// Notizen
			if (order.notes) {
				$card.append($('<div class="lbite-kanban-card-notes"></div>').text(`📝 ${order.notes}`));
			}
			
			// Actions
			const $actions = $('<div class="lbite-kanban-card-actions"></div>');
			
			// Status-Button
			const statusButtons = {
				'incoming': { next: 'preparing', label: 'Zubereitung starten', icon: '🔪', color: '#f39c12' },
				'preparing': { next: 'ready', label: 'Abholbereit', icon: '✅', color: '#27ae60' },
				'ready': { next: 'completed', label: 'Abgeschlossen', icon: '🎉', color: '#3498db' },
				'completed': null
			};
			
			const statusButton = statusButtons[currentStatus];
			if (statusButton) {
				const $sBtn = $('<button class="lbite-status-button"></button>')
					.addClass(`lbite-status-button-${currentStatus}`)
					.text(`${statusButton.icon} ${statusButton.label}`)
					.on('click', () => this.moveToNextStatus(order.id, statusButton.next));
				$actions.append($sBtn);
			}
			
			// Stornieren-Button
			if (currentStatus !== 'completed') {
				const $cBtn = $('<button class="lbite-cancel-button" title="Bestellung stornieren">🗑️</button>')
					.on('click', () => this.cancelOrder(order.id));
				$actions.append($cBtn);
			}
			
			$card.append($actions);
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
						window.lbiteNotify && window.lbiteNotify.success(lbiteDashboard.strings.orderCancelled || 'Order cancelled and payment refunded');
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
				$btn.attr('title', 'Vollbild beenden');
				$('body').addClass('lbite-board-fullscreen-active');
			} else {
				$icon.removeClass('dashicons-editor-contract').addClass('dashicons-editor-expand');
				$btn.attr('title', 'Vollbild');
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
