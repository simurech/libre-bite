jQuery(document).ready(function($) {
	// Standort-Auswahl speichern (ohne Seitenneulad)
	$('#lbite-pos-location').on('change', function() {
		const locationId = $(this).val();
		const $tableContainer = $('#lbite-pos-table-selector-container');
		const $tableSelect = $('#lbite-pos-table');

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
						// Tische für diesen Standort laden
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
									let options = '<option value="">' + (lbiteAdmin.strings.noTable || 'Kein Tisch') + '</option>';
									tableResponse.data.tables.forEach(function(table) {
										options += '<option value="' + table.id + '">' + table.title + '</option>';
									});
									$tableSelect.html(options);
								}
							}
						});
					} else if ($tableContainer.length) {
						$tableContainer.hide();
						$tableSelect.html('<option value="">' + (lbiteAdmin.strings.noTable || 'Kein Tisch') + '</option>');
					}

					// No-Location-Overlay aktualisieren
					if (!locationId) {
						$('.lbite-pos-products').addClass('lbite-pos-no-location');
					} else {
						$('.lbite-pos-products').removeClass('lbite-pos-no-location');
					}

					// Standort-Farb-Highlighting aktualisieren
					const $select = $('#lbite-pos-location');
					const colors = (typeof lbitePos !== 'undefined' && lbitePos.locationColors) ? lbitePos.locationColors : {};
					const color = locationId ? colors[locationId] : null;
					if (color) {
						$select.css({ 'border-color': color, 'border-width': '2px', 'box-shadow': '0 0 0 1px ' + color });
					} else {
						$select.css({ 'border-color': '', 'border-width': '', 'box-shadow': '' });
					}
				}
			}
		});
	});
});
