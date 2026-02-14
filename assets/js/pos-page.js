jQuery(document).ready(function($) {
	// Standort-Auswahl speichern
	$('#lbite-pos-location').on('change', function() {
		const locationId = $(this).val();

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
					// Seite neu laden um Produkte für diesen Standort anzuzeigen
					location.reload();
				}
			}
		});
	});
});
