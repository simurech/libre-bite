/**
 * Tischplan – Admin-Seite
 */
(function($) {
	'use strict';

	const FloorPlan = {
		sortable: null,
		isDirty: false,
		currentLocationId: null,

		/**
		 * Initialisierung
		 */
		init: function() {
			this.bindEvents();

			// Vorgewählten Standort laden
			const savedLocation = $('#lbite-floor-plan-location').val();
			if (savedLocation) {
				this.currentLocationId = savedLocation;
				this.loadTables(savedLocation);
			}
		},

		/**
		 * Event-Listener binden
		 */
		bindEvents: function() {
			// Standort-Wechsel
			$('#lbite-floor-plan-location').on('change', () => {
				const locationId = $('#lbite-floor-plan-location').val();
				this.currentLocationId = locationId;
				this.isDirty = false;
				$('#lbite-floor-plan-save').prop('disabled', true);
				$('#lbite-floor-plan-status').text('');

				if (locationId) {
					this.loadTables(locationId);
				} else {
					$('#lbite-floor-plan-grid').hide().empty();
					$('#lbite-floor-plan-empty').hide();
					$('#lbite-floor-plan-hint').hide();
				}
			});

			// Reihenfolge speichern
			$('#lbite-floor-plan-save').on('click', () => {
				this.saveOrder();
			});
		},

		/**
		 * Tische für Standort laden
		 *
		 * @param {string} locationId Standort-ID
		 */
		loadTables: function(locationId) {
			const $grid  = $('#lbite-floor-plan-grid');
			const $empty = $('#lbite-floor-plan-empty');
			const $hint  = $('#lbite-floor-plan-hint');

			$grid.html('<div class="lbite-floor-plan-loading">&#9202;</div>').show();
			$empty.hide();
			$hint.hide();

			$.ajax({
				url: lbiteFloorPlan.ajaxUrl,
				type: 'POST',
				data: {
					action:      'lbite_get_floor_plan_tables',
					nonce:       lbiteFloorPlan.nonce,
					location_id: locationId
				},
				success: (response) => {
					$grid.empty();

					if (!response.success || !response.data.tables.length) {
						$grid.hide();
						$empty.show();
						return;
					}

					response.data.tables.forEach(table => {
						$grid.append(this.createTableCard(table));
					});

					$grid.show();
					$hint.show();
					this.initSortable();
				},
				error: () => {
					$grid.html(
						$('<p class="lbite-floor-plan-error"></p>')
							.text(lbiteFloorPlan.strings.loadError)
					);
				}
			});
		},

		/**
		 * Tisch-Kachel erstellen
		 *
		 * @param {Object} table Tisch-Daten
		 * @return {jQuery} Kachel-Element
		 */
		createTableCard: function(table) {
			const $card = $('<div class="lbite-floor-plan-table"></div>')
				.attr('data-table-id', table.id);

			$card.append($('<div class="lbite-floor-plan-table-handle" aria-hidden="true">\u2807</div>'));

			$card.append(
				$('<div class="lbite-floor-plan-table-name"></div>').text(table.title)
			);

			if (table.seats) {
				$card.append(
					$('<div class="lbite-floor-plan-table-seats"></div>')
						.text('\uD83D\uDC65 ' + table.seats)
				);
			}

			const $editLink = $('<a class="lbite-floor-plan-table-edit" target="_blank">\u270F\uFE0F</a>')
				.attr('href', lbiteFloorPlan.editUrl.replace('%d', table.id))
				.attr('title', lbiteFloorPlan.strings.editTable);

			$card.append($editLink);

			return $card;
		},

		/**
		 * SortableJS initialisieren
		 */
		initSortable: function() {
			if (this.sortable) {
				this.sortable.destroy();
			}

			const gridEl = document.getElementById('lbite-floor-plan-grid');
			this.sortable = new Sortable(gridEl, {
				animation:  150,
				handle:     '.lbite-floor-plan-table-handle',
				ghostClass: 'lbite-floor-plan-ghost',
				dragClass:  'lbite-floor-plan-dragging',
				onEnd: () => {
					this.isDirty = true;
					$('#lbite-floor-plan-save').prop('disabled', false);
					$('#lbite-floor-plan-status').text('');
				}
			});
		},

		/**
		 * Reihenfolge per AJAX speichern
		 */
		saveOrder: function() {
			if (!this.currentLocationId) {
				return;
			}

			const order = [];
			$('#lbite-floor-plan-grid .lbite-floor-plan-table').each(function() {
				order.push($(this).data('table-id'));
			});

			const $btn    = $('#lbite-floor-plan-save');
			const $status = $('#lbite-floor-plan-status');

			$btn.prop('disabled', true);
			$status.text(lbiteFloorPlan.strings.saving);

			$.ajax({
				url: lbiteFloorPlan.ajaxUrl,
				type: 'POST',
				data: {
					action:      'lbite_save_floor_plan_order',
					nonce:       lbiteFloorPlan.nonce,
					location_id: this.currentLocationId,
					order:       order
				},
				success: (response) => {
					if (response.success) {
						this.isDirty = false;
						$status.text(lbiteFloorPlan.strings.saved);
						setTimeout(() => $status.text(''), 2500);
					} else {
						$status.text(lbiteFloorPlan.strings.saveError);
						$btn.prop('disabled', false);
					}
				},
				error: () => {
					$status.text(lbiteFloorPlan.strings.saveError);
					$btn.prop('disabled', false);
				}
			});
		}
	};

	$(document).ready(() => {
		if ($('#lbite-floor-plan-grid').length) {
			FloorPlan.init();
		}
	});

})(jQuery);
