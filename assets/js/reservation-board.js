/**
 * Reservierungs-Dashboard JavaScript
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

	const ReservationBoard = {
		refreshTimer: null,
		isLoading: false,
		pendingActions: new Set(),
		currentDate: '',
		currentLocationId: '',
		tables: [],
		statuses: {},

		/**
		 * Initialisierung
		 */
		init: function() {
			this.currentDate       = $('#lbite-res-date').val() || new Date().toISOString().slice(0, 10);
			this.currentLocationId = $('#lbite-res-board-location').val() || '';

			this.bindEvents();

			if (this.currentLocationId) {
				this.loadReservations();
			}

			this.startAutoRefresh();
		},

		/**
		 * Event-Listener registrieren
		 */
		bindEvents: function() {
			const self = this;

			$('#lbite-res-board-location').on('change', function() {
				self.currentLocationId = $(this).val();
				self.toggleBoardVisibility();

				if (self.currentLocationId) {
					// Standort-Wahl persistent speichern
					$.post(lbiteReservationBoard.ajaxUrl, {
						action:      'lbite_save_reservation_board_location',
						nonce:       lbiteReservationBoard.nonce,
						location_id: self.currentLocationId
					});
					self.loadReservations();
				}
			});

			$('#lbite-res-date').on('change', function() {
				self.currentDate = $(this).val();
				if (self.currentLocationId) {
					self.loadReservations();
				}
			});

			$('#lbite-res-date-prev').on('click', function() {
				self.navigateDate(-1);
			});

			$('#lbite-res-date-next').on('click', function() {
				self.navigateDate(1);
			});

			$('#lbite-res-date-today').on('click', function() {
				self.currentDate = new Date().toISOString().slice(0, 10);
				$('#lbite-res-date').val(self.currentDate);
				if (self.currentLocationId) {
					self.loadReservations();
				}
			});
		},

		/**
		 * Datum um delta Tage verschieben und neu laden
		 *
		 * @param {number} delta  +1 oder -1
		 */
		navigateDate: function(delta) {
			const date = new Date(this.currentDate + 'T00:00:00');
			date.setDate(date.getDate() + delta);
			this.currentDate = date.toISOString().slice(0, 10);
			$('#lbite-res-date').val(this.currentDate);

			if (this.currentLocationId) {
				this.loadReservations();
			}
		},

		/**
		 * Board-Sichtbarkeit abhängig von Standort-Auswahl steuern
		 */
		toggleBoardVisibility: function() {
			if (this.currentLocationId) {
				$('#lbite-res-no-location-message').hide();
				$('#lbite-reservation-board-wrap').show();
			} else {
				$('#lbite-res-no-location-message').show();
				$('#lbite-reservation-board-wrap').hide();
			}
		},

		showLoading: function() {
			$('#lbite-res-loading').show();
		},

		hideLoading: function() {
			$('#lbite-res-loading').hide();
		},

		/**
		 * Reservierungen per AJAX laden
		 *
		 * @param {boolean} silent  Kein Lade-Indikator (für Auto-Refresh)
		 */
		loadReservations: function(silent) {
			if (!this.currentLocationId || this.isLoading) {
				return;
			}

			this.isLoading = true;

			if (!silent) {
				this.showLoading();
			}

			const self = this;

			$.post(lbiteReservationBoard.ajaxUrl, {
				action:      'lbite_get_reservations',
				nonce:       lbiteReservationBoard.nonce,
				location_id: this.currentLocationId,
				date:        this.currentDate
			}, function(response) {
				self.isLoading = false;
				self.hideLoading();

				if (!response.success) {
					return;
				}

				self.tables   = response.data.tables   || [];
				self.statuses = response.data.statuses || {};
				self.renderReservations(response.data.reservations || []);
			}).fail(function() {
				self.isLoading = false;
				self.hideLoading();
			});
		},

		/**
		 * Reservierungsliste rendern
		 *
		 * @param {Array} reservations  Reservierungsobjekte aus AJAX-Response
		 */
		renderReservations: function(reservations) {
			const $list  = $('#lbite-reservation-list');
			const $empty = $('#lbite-res-empty-state');
			const $badge = $('#lbite-res-count-badge');

			$list.empty();

			if (reservations.length === 0) {
				$empty.show();
				$badge.hide();
				return;
			}

			$empty.hide();

			const label = reservations.length === 1
				? lbiteReservationBoard.strings.reservation
				: lbiteReservationBoard.strings.reservations;
			$badge.text(reservations.length + ' ' + label).show();

			const self = this;
			reservations.forEach(function(res) {
				$list.append(self.createReservationCard(res));
			});
		},

		/**
		 * Einzelne Reservierungskarte als jQuery-Objekt erzeugen
		 *
		 * @param {Object} res  Reservierungsdatenobjekt
		 * @return {jQuery}
		 */
		createReservationCard: function(res) {
			const self = this;

			const statusColors = {
				pending:   '#f39c12',
				confirmed: '#27ae60',
				cancelled: '#e74c3c',
				completed: '#3498db'
			};

			const statusColor = statusColors[res.status] || '#999';
			const statusLabel = this.statuses[res.status] || res.status;

			// Karte
			const $card = $('<div>').addClass('lbite-res-card').attr('data-id', res.id);

			// --- Kopfzeile: Uhrzeit, Personen, Status-Badge ---
			const $header = $('<div>').addClass('lbite-res-card__header');

			$('<span>').addClass('lbite-res-card__time').text(escapeHtml(res.time)).appendTo($header);
			$('<span>').addClass('lbite-res-card__guests').text(escapeHtml(res.guests) + ' P').appendTo($header);

			const $statusBtn = $('<button>')
				.addClass('lbite-res-status-badge button')
				.attr('type', 'button')
				.attr('data-id', res.id)
				.attr('data-current', res.status)
				.css('background-color', statusColor)
				.text(statusLabel);

			$header.append($statusBtn);
			$card.append($header);

			// --- Rumpf: Name, Telefon, Notiz, Tisch-Dropdown ---
			const $body = $('<div>').addClass('lbite-res-card__body');

			$('<div>').addClass('lbite-res-card__name').text(escapeHtml(res.name)).appendTo($body);

			if (res.phone) {
				const $phone = $('<div>').addClass('lbite-res-card__phone');
				$('<span>').addClass('dashicons dashicons-phone').appendTo($phone);
				$phone.append(document.createTextNode(' ' + escapeHtml(res.phone)));
				$body.append($phone);
			}

			if (res.notes) {
				$('<div>').addClass('lbite-res-card__notes').text(escapeHtml(res.notes)).appendTo($body);
			}

			// Tisch-Dropdown
			const $tableRow    = $('<div>').addClass('lbite-res-card__table-row');
			const $tableLabel  = $('<label>').text(lbiteReservationBoard.strings.table + ': ');
			const $tableSelect = $('<select>').addClass('lbite-res-table-select').attr('data-id', res.id);

			$('<option>').val('0').text('— ' + lbiteReservationBoard.strings.noTable + ' —').appendTo($tableSelect);

			self.tables.forEach(function(table) {
				const label = table.seats
					? table.title + ' (' + table.seats + ' P)'
					: table.title;
				const $opt = $('<option>').val(table.id).text(label);
				if (parseInt(res.table_id, 10) === parseInt(table.id, 10)) {
					$opt.prop('selected', true);
				}
				$tableSelect.append($opt);
			});

			$tableRow.append($tableLabel).append($tableSelect);
			$body.append($tableRow);
			$card.append($body);

			// --- Events ---

			// Status-Badge: zyklisch weiterschalten (Ausstehend → Bestätigt → Abgeschlossen → Storniert)
			$statusBtn.on('click', function() {
				const id = parseInt($(this).data('id'), 10);
				if (self.pendingActions.has('status_' + id)) {
					return;
				}

				const statusOrder = ['pending', 'confirmed', 'completed', 'cancelled'];
				const current     = $(this).data('current');
				const nextIdx     = (statusOrder.indexOf(current) + 1) % statusOrder.length;
				const next        = statusOrder[nextIdx];

				self.pendingActions.add('status_' + id);
				$statusBtn.prop('disabled', true);

				$.post(lbiteReservationBoard.ajaxUrl, {
					action:          'lbite_update_reservation_status',
					nonce:           lbiteReservationBoard.nonce,
					reservation_id:  id,
					status:          next
				}, function(resp) {
					self.pendingActions.delete('status_' + id);

					if (resp.success) {
						$statusBtn
							.text(self.statuses[next] || next)
							.css('background-color', statusColors[next] || '#999')
							.attr('data-current', next)
							.prop('disabled', false);
					} else {
						$statusBtn.prop('disabled', false);
					}
				}).fail(function() {
					self.pendingActions.delete('status_' + id);
					$statusBtn.prop('disabled', false);
				});
			});

			// Tisch-Zuweisung per Dropdown
			$tableSelect.on('change', function() {
				const id      = parseInt($(this).data('id'), 10);
				const tableId = $(this).val();

				if (self.pendingActions.has('table_' + id)) {
					return;
				}

				self.pendingActions.add('table_' + id);

				$.post(lbiteReservationBoard.ajaxUrl, {
					action:         'lbite_assign_reservation_table',
					nonce:          lbiteReservationBoard.nonce,
					reservation_id: id,
					table_id:       tableId
				}, function() {
					self.pendingActions.delete('table_' + id);
				}).fail(function() {
					self.pendingActions.delete('table_' + id);
				});
			});

			return $card;
		},

		/**
		 * Auto-Refresh starten
		 */
		startAutoRefresh: function() {
			const self     = this;
			const interval = lbiteReservationBoard.refreshInterval || 60000;

			this.refreshTimer = setInterval(function() {
				if (self.currentLocationId && !self.isLoading) {
					self.loadReservations(true);
				}
			}, interval);
		}
	};

	$(document).ready(function() {
		ReservationBoard.init();
	});

})(jQuery);
