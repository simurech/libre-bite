jQuery(document).ready(function($) {
	/**
	 * Tisch-Dropdown mit Belegungsstatus befüllen.
	 *
	 * @param {jQuery} $select    Tisch-Select-Element
	 * @param {Array}  tables     Array von {id, title, occupied}
	 * @param {string} emptyLabel Label für "kein Tisch"
	 */
	function renderTableOptions($select, tables, emptyLabel) {
		$select.empty().append($('<option>').val('').text(emptyLabel));
		tables.forEach(function(table) {
			var label = table.occupied ? '\u{1F534} ' + table.title : '\u{1F7E2} ' + table.title;
			$select.append($('<option>').val(table.id).text(label));
		});
	}

	// Standort-Auswahl speichern (ohne Seitenneulad)
	$('#lbite-pos-location').on('change', function() {
		var locationId = $(this).val();
		var $tableContainer = $('#lbite-pos-table-selector-container');
		var $tableSelect = $('#lbite-pos-table');
		var noTableLabel = (lbiteAdmin.strings && lbiteAdmin.strings.noTable) ? lbiteAdmin.strings.noTable : 'Kein Tisch';

		// Standort per AJAX speichern
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'lbite_save_pos_location',
				nonce: lbiteAdmin.nonce,
				location_id: locationId
			},
			success: function(response) {
				if (response.success) {
					// Tisch-Selector aktualisieren ohne Seitenneulad
					if (locationId && $tableContainer.length) {
						$tableContainer.show();
						// Tische mit Belegungsstatus für diesen Standort laden
						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'lbite_get_location_tables',
								nonce: lbiteAdmin.nonce,
								location_id: locationId
							},
							success: function(tableResponse) {
								if (tableResponse.success) {
									renderTableOptions($tableSelect, tableResponse.data.tables, noTableLabel);
								}
							}
						});
					} else if ($tableContainer.length) {
						$tableContainer.hide();
						$tableSelect.empty().append($('<option>').val('').text(noTableLabel));
					}

					// No-Location-Overlay aktualisieren
					if (!locationId) {
						$('.lbite-pos-products').addClass('lbite-pos-no-location');
					} else {
						$('.lbite-pos-products').removeClass('lbite-pos-no-location');
					}

					// Standort-Farb-Highlighting aktualisieren
					var $locationSelect = $('#lbite-pos-location');
					var colors = (typeof lbitePos !== 'undefined' && lbitePos.locationColors) ? lbitePos.locationColors : {};
					var color = locationId ? colors[locationId] : null;
					if (color) {
						$locationSelect.css({ 'border-color': color, 'border-width': '2px', 'box-shadow': '0 0 0 1px ' + color });
					} else {
						$locationSelect.css({ 'border-color': '', 'border-width': '', 'box-shadow': '' });
					}
				}
			}
		});
	});

	// Beim Seitenaufruf bestehende Tische mit Status versehen (vorgewählter Standort)
	var $initialTableSelect = $('#lbite-pos-table');
	var $initialLocation = $('#lbite-pos-location');
	if ($initialTableSelect.length && $initialLocation.val()) {
		var initialLocationId = $initialLocation.val();
		var initialNoTableLabel = (lbiteAdmin.strings && lbiteAdmin.strings.noTable) ? lbiteAdmin.strings.noTable : 'Kein Tisch';
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'lbite_get_location_tables',
				nonce: lbiteAdmin.nonce,
				location_id: initialLocationId
			},
			success: function(tableResponse) {
				if (tableResponse.success) {
					renderTableOptions($initialTableSelect, tableResponse.data.tables, initialNoTableLabel);
				}
			}
		});
	}
});
