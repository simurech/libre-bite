jQuery(document).ready(function($) {
	// Event-Delegation: Listener auf document binden damit WooCommerce Fragment-Updates
	// (DOM-Replacement von .woocommerce-checkout-review-order) die Handler nicht entfernen.

	// Trinkgeld-Auswahl
	$(document).on('change', 'input[name="lbite_tip_type"]', function() {
		// Alle versteckten Felder zurücksetzen
		$('.lbite-tip-percentage-value').prop('disabled', true).val('');

		// Wenn percentage gewählt, entsprechendes verstecktes Feld aktivieren
		if ($(this).val() === 'percentage') {
			var percentage = $(this).data('percentage');
			var $hiddenField = $(this).siblings('.lbite-tip-percentage-value');
			$hiddenField.val(percentage).prop('disabled', false);
		}

		$('.lbite-tip-selection').addClass('lbite-tip--updating');
		$('body').trigger('update_checkout');
	});

	$(document).on('input', 'input[name="lbite_tip_custom"]', function() {
		$(this).closest('.lbite-tip-option').find('input[name="lbite_tip_type"]').prop('checked', true);

		// Debounce
		clearTimeout(window.tipTimeout);
		window.tipTimeout = setTimeout(function() {
			$('.lbite-tip-selection').addClass('lbite-tip--updating');
			$('body').trigger('update_checkout');
		}, 500);
	});

	// Lade-Status entfernen sobald WooCommerce den Checkout aktualisiert hat
	$(document.body).on('updated_checkout', function() {
		$('.lbite-tip-selection').removeClass('lbite-tip--updating');
	});
});
