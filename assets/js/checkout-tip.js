jQuery(document).ready(function($) {
	// Trinkgeld-Auswahl
	$('input[name="lbite_tip_type"]').on('change', function() {
		// Alle versteckten Felder zurücksetzen
		$('.lbite-tip-percentage-value').prop('disabled', true).val('');

		// Wenn percentage gewählt, entsprechendes verstecktes Feld aktivieren
		if ($(this).val() === 'percentage') {
			var percentage = $(this).data('percentage');
			var $hiddenField = $(this).siblings('.lbite-tip-percentage-value');
			$hiddenField.val(percentage).prop('disabled', false);
		}

		$('body').trigger('update_checkout');
	});

	$('input[name="lbite_tip_custom"]').on('input', function() {
		$(this).closest('.lbite-tip-option').find('input[name="lbite_tip_type"]').prop('checked', true);

		// Debounce
		clearTimeout(window.tipTimeout);
		window.tipTimeout = setTimeout(function() {
			$('body').trigger('update_checkout');
		}, 500);
	});
});
