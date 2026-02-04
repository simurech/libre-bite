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

		/**
		 * Initialisierung
		 */
		init: function() {
			this.initAudio();
			this.initDragDrop();
			this.initControls();
			this.bindFullscreenEvents();

			// Initial laden wenn Standort bereits gewählt ist
			const locationId = $('#lb-board-location').val();
			if (locationId) {
				this.loadOrders();
			}

			this.startAutoRefresh();
		},

		/**
		 * Audio initialisieren
		 */
		initAudio: function() {
			if (lbDashboard.soundUrl) {
				this.audio = new Audio(lbDashboard.soundUrl);
			}
		},

		/**
		 * Drag & Drop initialisieren
		 */
		initDragDrop: function() {
			const columns = document.querySelectorAll('.lb-kanban-cards');

			columns.forEach(column => {
				new Sortable(column, {
					group: 'kanban',
					animation: 150,
					ghostClass: 'lb-ghost',
					dragClass: 'lb-dragging',
					onEnd: (evt) => {
						const orderId = evt.item.dataset.orderId;
						const newStatus = evt.to.closest('.lb-kanban-column').dataset.status;

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

			// Wake Lock initial aktivieren wenn Checkbox angehakt
			if ($('#lb-wake-lock').is(':checked')) {
				this.requestWakeLock();
			}

			// Standort-Filter
			$('#lb-board-location').on('change', () => {
				const locationId = $('#lb-board-location').val();

				// Standort speichern
				if (locationId) {
					$.ajax({
						url: lbDashboard.ajaxUrl,
						type: 'POST',
						data: {
							action: 'lb_save_board_location',
							nonce: lbDashboard.nonce,
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

			// Wake Lock
			$('#lb-wake-lock').on('change', (e) => {
				if (e.target.checked) {
					this.requestWakeLock();
				} else {
					this.releaseWakeLock();
				}
			});

			// Sound Toggle
			$('#lb-sound-toggle').on('click', () => {
				this.soundEnabled = !this.soundEnabled;

				const $btn = $('#lb-sound-toggle');
				const $icon = $btn.find('.dashicons');

				if (this.soundEnabled) {
					$icon.removeClass('dashicons-controls-volumeoff').addClass('dashicons-controls-volumeon');
					$btn.find('span:not(.dashicons)').text(lbDashboard.strings.soundActive || 'Sound aktiv');
				} else {
					$icon.removeClass('dashicons-controls-volumeon').addClass('dashicons-controls-volumeoff');
					$btn.find('span:not(.dashicons)').text(lbDashboard.strings.soundInactive || 'Sound aus');
				}
			});
		},

		/**
		 * Wake Lock anfordern
		 */
		async requestWakeLock() {
			if (!('wakeLock' in navigator)) {
				alert('Wake Lock wird von diesem Browser nicht unterstützt.');
				$('#lb-wake-lock').prop('checked', false);
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
				$('#lb-wake-lock').prop('checked', false);
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
			}, lbDashboard.refreshInterval);
		},

		/**
		 * Board-Sichtbarkeit umschalten
		 */
		toggleBoardVisibility: function() {
			const locationId = $('#lb-board-location').val();

			if (locationId) {
				$('#lb-no-location-message').css('display', 'none');
				$('#lb-kanban-board').css('display', 'grid');
			} else {
				$('#lb-no-location-message').css('display', 'block');
				$('#lb-kanban-board').css('display', 'none');
			}
		},

		/**
		 * Lade-Overlay anzeigen
		 */
		showLoading: function(message = 'Laden...') {
			if ($('#lb-loading-overlay').length === 0) {
				$('body').append(`
					<div id="lb-loading-overlay" style="
						position: fixed;
						top: 0;
						left: 0;
						right: 0;
						bottom: 0;
						background: rgba(255,255,255,0.8);
						display: flex;
						flex-direction: column;
						align-items: center;
						justify-content: center;
						z-index: 99999;
					">
						<div class="lb-spinner" style="
							width: 40px;
							height: 40px;
							border: 4px solid #e0e0e0;
							border-top: 4px solid #0073aa;
							border-radius: 50%;
							animation: lb-spin 0.8s linear infinite;
						"></div>
						<p style="margin-top: 15px; font-size: 14px; color: #666;">${message}</p>
					</div>
					<style>
						@keyframes lb-spin {
							0% { transform: rotate(0deg); }
							100% { transform: rotate(360deg); }
						}
					</style>
				`);
			}
			$('#lb-loading-overlay').fadeIn(150);
		},

		/**
		 * Lade-Overlay ausblenden
		 */
		hideLoading: function() {
			$('#lb-loading-overlay').fadeOut(150);
		},

		/**
		 * Button-Lade-Status setzen
		 */
		setButtonLoading: function($btn, loading) {
			if (loading) {
				$btn.data('original-text', $btn.html());
				$btn.html('<span class="lb-btn-spinner">⏳</span>').prop('disabled', true);
			} else {
				$btn.html($btn.data('original-text')).prop('disabled', false);
			}
		},

		/**
		 * Bestellungen laden
		 */
		loadOrders: function(silent = false) {
			const locationId = $('#lb-board-location').val();

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
					this.showLoading('Bestellungen werden geladen...');
				}
			}

			$.ajax({
				url: lbDashboard.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lb_get_orders',
					nonce: lbDashboard.nonce,
					location_id: locationId
				},
				success: (response) => {
					if (response.success && response.data.orders) {
						this.completedCount = response.data.completed_count || 0;
						this.completedOffset = 3; // Zurücksetzen beim Neuladen
						this.renderOrders(response.data.orders, silent);
					}
				},
				error: () => {
					if (!silent) {
						window.lbNotify && window.lbNotify.error('Fehler beim Laden der Bestellungen');
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
		let totalOrders = 0;
		let activeOrders = 0; // Nur nicht-abgeschlossene Bestellungen

		Object.keys(ordersByStatus).forEach(status => {
			const orders = ordersByStatus[status];
			const $column = $('#lb-column-' + status);

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
				$column.append(`
					<button class="lb-load-more-completed" onclick="Dashboard.loadMoreCompleted()">
						📋 ${remainingCount} weitere Bestellung(en) anzeigen
					</button>
				`);
			}
		});

		// Neue Bestellung erkannt (nur aktive Bestellungen zählen)
		if (!silent && activeOrders > this.lastOrderCount) {
			this.playNotificationSound();
		}

		this.lastOrderCount = activeOrders;
	},

		/**
		 * Bestellungs-Karte erstellen
		 */
		createOrderCard: function(order, currentStatus) {
			const typeLabel = order.type === 'later'
				? '<span style="color: #f39c12;">⏰ ' + escapeHtml(order.pickup_time || '') + '</span>'
				: '<span style="color: #27ae60;">🔥 Sofort</span>';

			// Kundenname anzeigen
			const customerNameRaw = order.customer && order.customer.trim() ? order.customer.trim() : '';
			const customerName = escapeHtml(customerNameRaw);

			let itemsHtml = '';
			order.items.forEach(item => {
				const safeName = escapeHtml(item.name);
				const meta = item.meta ? '<div style="margin-left: 20px; font-size: 0.9em; color: #666;">' + item.meta + '</div>' : '';
				itemsHtml += `<div class="lb-kanban-card-item" style="margin-bottom: 8px;">
					<strong>${item.quantity}x ${safeName}</strong>
					${meta}
				</div>`;
			});

			const notesHtml = order.notes
				? `<div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee; font-style: italic; color: #666;">
					📝 ${escapeHtml(order.notes)}
				</div>`
				: '';

			// Status-Button bestimmen
			const statusButtons = {
				'incoming': { next: 'preparing', label: 'Zubereitung starten', icon: '🔪', color: '#f39c12' },
				'preparing': { next: 'ready', label: 'Abholbereit', icon: '✅', color: '#27ae60' },
				'ready': { next: 'completed', label: 'Abgeschlossen', icon: '🎉', color: '#3498db' },
				'completed': null
			};

			const statusButton = statusButtons[currentStatus];
			let statusButtonHtml = '';

			if (statusButton) {
				statusButtonHtml = `<button onclick="Dashboard.moveToNextStatus(${order.id}, '${statusButton.next}')"
					class="lb-status-button"
					style="flex: 1; padding: 14px; border: none; background: ${statusButton.color}; color: white; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: 600;">
					${statusButton.icon} ${statusButton.label}
				</button>`;
			}

			// Stornieren-Button (außer bei completed)
			const cancelButton = currentStatus !== 'completed'
				? `<button onclick="Dashboard.cancelOrder(${order.id})"
					class="lb-cancel-button"
					title="Bestellung stornieren"
					style="padding: 8px 12px; border: 1px solid #e74c3c; background: white; color: #e74c3c; border-radius: 4px; cursor: pointer; font-size: 20px;">
					🗑️
				</button>`
				: '';

			return `
				<div class="lb-kanban-card" data-order-id="${order.id}">
					<h3 style="margin: 0 0 10px 0; font-size: 16px;">
						#${order.number}${customerName ? ' - ' + customerName : ''}
					</h3>
					<div class="lb-kanban-card-meta" style="margin-bottom: 12px; font-size: 13px; line-height: 1.6;">
						${typeLabel}
						<br>🕐 ${escapeHtml(order.date || '')}
						${order.location ? '<br>📍 ' + escapeHtml(order.location) : ''}
					</div>
					<div class="lb-kanban-card-items" style="margin: 12px 0; padding: 10px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee;">
						${itemsHtml}
					</div>
					${notesHtml}
					<div class="lb-kanban-card-actions" style="display: flex; gap: 8px; margin-top: 12px; align-items: center;">
						${statusButtonHtml}
						${cancelButton}
					</div>
				</div>
			`;
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
			const $card = $(`.lb-kanban-card[data-order-id="${orderId}"]`);
			$card.css('opacity', '0.5').find('button').prop('disabled', true);

			$.ajax({
				url: lbDashboard.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lb_update_order_status',
					nonce: lbDashboard.nonce,
					order_id: orderId,
					status: newStatus
				},
				success: (response) => {
					if (response.success) {
						window.lbNotify && window.lbNotify.success(lbDashboard.strings.orderUpdated || 'Status aktualisiert');
						this.loadOrders();
					} else {
						window.lbNotify && window.lbNotify.error(lbDashboard.strings.updateError || 'Fehler beim Aktualisieren');
						$card.css('opacity', '1').find('button').prop('disabled', false);
					}
				},
				error: () => {
					window.lbNotify && window.lbNotify.error(lbDashboard.strings.updateError || 'Fehler beim Aktualisieren');
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

			if (!confirm('Möchten Sie diese Bestellung wirklich stornieren?\n\nDie Zahlung wird automatisch zurückerstattet.')) {
				return;
			}

			this.pendingActions.add(actionKey);

			// Karte visuell als "in Bearbeitung" markieren
			const $card = $(`.lb-kanban-card[data-order-id="${orderId}"]`);
			$card.css('opacity', '0.5').find('button').prop('disabled', true);

			this.showLoading('Bestellung wird storniert...');

			$.ajax({
				url: lbDashboard.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lb_cancel_order',
					nonce: lbDashboard.nonce,
					order_id: orderId
				},
				success: (response) => {
					if (response.success) {
						window.lbNotify && window.lbNotify.success('Bestellung storniert und Zahlung zurückerstattet');
						this.loadOrders();
					} else {
						window.lbNotify && window.lbNotify.error('Fehler beim Stornieren: ' + (response.data && response.data.message ? response.data.message : 'Unbekannter Fehler'));
						$card.css('opacity', '1').find('button').prop('disabled', false);
					}
				},
				error: () => {
					window.lbNotify && window.lbNotify.error('Fehler beim Stornieren der Bestellung');
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
		const locationId = $('#lb-board-location').val();

		if (!locationId) {
			return;
		}

		$.ajax({
			url: lbDashboard.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lb_load_more_completed',
				nonce: lbDashboard.nonce,
				location_id: locationId,
				offset: this.completedOffset
			},
			success: (response) => {
				if (response.success && response.data.orders) {
					const $column = $('#lb-column-completed');

					// Button entfernen
					$column.find('.lb-load-more-completed').remove();

					// Neue Bestellungen hinzufügen
					response.data.orders.forEach(order => {
						$column.append(this.createOrderCard(order, 'completed'));
					});

					// Offset aktualisieren
					this.completedOffset += response.data.orders.length;

					// Button wieder hinzufügen wenn noch mehr vorhanden
					if (this.completedOffset < response.data.total_count) {
						const remainingCount = response.data.total_count - this.completedOffset;
						$column.append(`
							<button class="lb-load-more-completed" onclick="Dashboard.loadMoreCompleted()">
								📋 ${remainingCount} weitere Bestellung(en) anzeigen
							</button>
						`);
					}
				}
			},
			error: () => {
				window.lbNotify.error('Fehler beim Laden weiterer Bestellungen');
			}
		});
	},

	/**
	 * Bestellung ansehen
		 */
		viewOrder: function(orderId) {
			window.open(
				lbDashboard.ajaxUrl.replace('admin-ajax.php', 'post.php?post=' + orderId + '&action=edit'),
				'_blank'
			);
		},

		/**
		 * Bestellung drucken
		 */
		printOrder: function(orderId) {
			const $card = $(`.lb-kanban-card[data-order-id="${orderId}"]`);
			if ($card.length === 0) return;

			const printWindow = window.open('', '', 'width=300,height=600');
			printWindow.document.write(`
				<html>
				<head>
					<title>Bestellung #${orderId}</title>
					<style>
						body { font-family: monospace; font-size: 12px; margin: 10px; }
						h2 { text-align: center; margin: 10px 0; }
						.divider { border-top: 1px dashed #000; margin: 10px 0; }
						.item { margin: 5px 0; }
					</style>
				</head>
				<body>
					${$card.html()}
				</body>
				</html>
			`);
			printWindow.document.close();
			printWindow.print();
		},

		/**
		 * Sound abspielen
		 */
		playNotificationSound: function() {
			if (this.soundEnabled && this.audio) {
				this.audio.play().catch(err => {
					console.log('Sound konnte nicht abgespielt werden:', err);
				});
			}
		},

		/**
		 * Vollbild-Events binden
		 */
		bindFullscreenEvents: function() {
			$('#lb-board-fullscreen').on('click', () => {
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
			const $btn = $('#lb-board-fullscreen');
			const $icon = $btn.find('.dashicons');

			if (document.fullscreenElement) {
				$icon.removeClass('dashicons-editor-expand').addClass('dashicons-editor-contract');
				$btn.attr('title', 'Vollbild beenden');
				$('body').addClass('lb-board-fullscreen-active');
			} else {
				$icon.removeClass('dashicons-editor-contract').addClass('dashicons-editor-expand');
				$btn.attr('title', 'Vollbild');
				$('body').removeClass('lb-board-fullscreen-active');
			}
		}
	};

	// Global verfügbar machen
	window.Dashboard = Dashboard;

	// Initialisieren wenn Seite geladen ist
	$(document).ready(() => {
		if ($('.lb-order-board').length > 0) {
			Dashboard.init();
		}
	});

})(jQuery);
