/**
 * Reservierungs-Dashboard JavaScript
 */

(function($) {
	'use strict';

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
				self.renderPendingElsewhere(response.data.pending_elsewhere || []);
			}).fail(function() {
				self.isLoading = false;
				self.hideLoading();
			});
		},

		/**
		 * Hinweis auf offene Anfragen an anderen Tagen/Standorten.
		 *
		 * Ein Klick springt direkt zum Tag und Standort der Anfrage.
		 *
		 * @param {Array} groups  [{date, date_label, location_id, location, count}]
		 */
		renderPendingElsewhere: function(groups) {
			const $box = $('#lbite-res-pending-elsewhere');
			const s    = lbiteReservationBoard.strings;
			const self = this;

			$box.empty();
			if (!groups.length) {
				$box.hide();
				return;
			}

			const $p = $('<p></p>').append($('<strong></strong>').text(s.pendingElsewhere)).append(' ');
			groups.forEach(function(g, i) {
				const label = g.date_label + (g.location ? ' · ' + g.location : '') + ' (' + String(s.pendingCount).replace('%d', g.count) + ')';
				const $btn  = $('<button type="button" class="button-link"></button>').text(label);
				$btn.on('click', function() {
					self.jumpTo(g.date, g.location_id);
				});
				if (i > 0) {
					$p.append(', ');
				}
				$p.append($btn);
			});
			$box.append($p).show();
		},

		/**
		 * Zu Datum und Standort wechseln.
		 *
		 * @param {string} date        Y-m-d
		 * @param {number} locationId  Standort-ID
		 */
		jumpTo: function(date, locationId) {
			this.currentDate = date;
			$('#lbite-res-date').val(date);
			const $loc = $('#lbite-res-board-location');
			if (locationId && String(locationId) !== String(this.currentLocationId) && $loc.find('option[value="' + locationId + '"]').length) {
				// Der Change-Handler speichert den Standort und lädt neu.
				$loc.val(String(locationId)).trigger('change');
				return;
			}
			this.loadReservations();
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

			// .text() escaped bereits selbst - ein zusätzliches escapeHtml() davor
			// liess HTML-Entities wörtlich sichtbar werden statt sie darzustellen
			// (Audit 26.09.2026, AP-21).
			$('<span>').addClass('lbite-res-card__time').text(res.time).appendTo($header);
			$('<span>').addClass('lbite-res-card__guests').text(res.guests + ' P').appendTo($header);

			// Status nur als Anzeige; geändert wird über die Knöpfe unten.
			const $statusBadge = $('<span>')
				.addClass('lbite-res-status-badge')
				.css('background-color', statusColor)
				.text(statusLabel);

			$header.append($statusBadge);
			$card.append($header);

			// --- Rumpf: Name, Telefon, Notiz, Tisch-Dropdown ---
			const $body = $('<div>').addClass('lbite-res-card__body');

			$('<div>').addClass('lbite-res-card__name').text(res.name).appendTo($body);

			if (res.phone) {
				const $phone = $('<div>').addClass('lbite-res-card__phone');
				$('<span>').addClass('dashicons dashicons-phone').appendTo($phone);
				$phone.append(document.createTextNode(' ' + res.phone));
				$body.append($phone);
			}

			if (res.notes) {
				$('<div>').addClass('lbite-res-card__notes').text(res.notes).appendTo($body);
			}

			// Gastnotizen aus dem Kundenkonto (nur bei wiedererkanntem Gast)
			if (res.guest_known) {
				const $guest = $('<div>').addClass('lbite-res-card__guest');

				$('<span>')
					.addClass('lbite-res-card__guest-badge')
					.text(lbiteReservationBoard.strings.knownGuest || 'Returning guest')
					.appendTo($guest);

				if (res.guest_allergies) {
					$('<div>')
						.addClass('lbite-res-card__allergies')
						.text('⚠ ' + res.guest_allergies)
						.appendTo($guest);
				}

				if (res.guest_notes) {
					$('<div>')
						.addClass('lbite-res-card__guest-notes')
						.text(res.guest_notes)
						.appendTo($guest);
				}

				$body.append($guest);
			}

			// Tisch-Dropdown
			const $tableRow    = $('<div>').addClass('lbite-res-card__table-row');
			const selectId     = 'lbite-res-table-' + res.id;
			const $tableLabel  = $('<label>').attr('for', selectId).text(lbiteReservationBoard.strings.table + ': ');
			const $tableSelect = $('<select>').addClass('lbite-res-table-select').attr({ id: selectId, 'data-id': res.id });

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

			// --- Aktionen je Status ---
			const S = lbiteReservationBoard.strings;
			const actionsByStatus = {
				pending:   [ { to: 'confirmed', label: S.accept, primary: true }, { to: 'cancelled', label: S.decline, confirm: S.confirmDecline } ],
				confirmed: [ { to: 'completed', label: S.arrived, primary: true }, { to: 'cancelled', label: S.cancel, confirm: S.confirmCancel } ],
				cancelled: [ { to: 'pending', label: S.reopen } ],
				completed: [ { to: 'confirmed', label: S.reopen } ]
			};
			const $actions = $('<div>').addClass('lbite-res-card__actions');
			(actionsByStatus[res.status] || []).forEach(function(action) {
				$('<button>')
					.attr('type', 'button')
					.addClass('button lbite-res-action' + (action.primary ? ' button-primary' : '') + ('cancelled' === action.to ? ' lbite-res-action--danger' : ''))
					.text(action.label)
					.on('click', function() {
						if (action.confirm && ! window.confirm(action.confirm)) {
							return;
						}
						self.changeStatus(res.id, action.to, $actions);
					})
					.appendTo($actions);
			});
			if (lbiteReservationBoard.emailsEnabled && 'pending' === res.status) {
				$('<span>').addClass('lbite-res-card__mail-hint').text(S.mailHint).appendTo($actions);
			}
			$card.append($actions);

			// --- Events ---

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
		/**
		 * Status ändern; der Server informiert den Gast per Mail.
		 *
		 * @param {number} id       Reservierung.
		 * @param {string} status   Neuer Status.
		 * @param {jQuery} $actions Knopfleiste (wird währenddessen gesperrt).
		 */
		changeStatus: function(id, status, $actions) {
			const self = this;
			if (self.pendingActions.has('status_' + id)) {
				return;
			}
			self.pendingActions.add('status_' + id);
			$actions.find('button').prop('disabled', true);

			$.post(lbiteReservationBoard.ajaxUrl, {
				action:         'lbite_update_reservation_status',
				nonce:          lbiteReservationBoard.nonce,
				reservation_id: id,
				status:         status
			}).done(function(resp) {
				if (resp && resp.success) {
					if (window.lbiteNotify) {
						window.lbiteNotify.success(resp.data.notified ? lbiteReservationBoard.strings.savedMailed : lbiteReservationBoard.strings.saved);
					}
					self.loadReservations(true);
				} else if (window.lbiteNotify) {
					window.lbiteNotify.error((resp && resp.data && resp.data.message) || lbiteReservationBoard.strings.error);
				}
			}).fail(function() {
				if (window.lbiteNotify) {
					window.lbiteNotify.error(lbiteReservationBoard.strings.error);
				}
			}).always(function() {
				self.pendingActions.delete('status_' + id);
				$actions.find('button').prop('disabled', false);
			});
		},

		startAutoRefresh: function() {
			const self     = this;
			const interval = lbiteReservationBoard.refreshInterval || 60000;

			this.refreshTimer = setInterval(function() {
				// Kein Server-Roundtrip für einen Tab, den ohnehin niemand
				// ansieht (Audit 26.09.2026, AP-21).
				if (document.hidden) { return; }
				if (self.currentLocationId && !self.isLoading) {
					self.loadReservations(true);
				}
			}, interval);

			document.addEventListener('visibilitychange', function() {
				if (!document.hidden && self.currentLocationId && !self.isLoading) {
					self.loadReservations(true);
				}
			});
		}
	};

	$(document).ready(function() {
		ReservationBoard.init();
	});

})(jQuery);
