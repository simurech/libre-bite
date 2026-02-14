jQuery(document).ready(function($) {
	// E-Mail-Feld ein/ausblenden
	$('input[name="lbite_receipt_option"]').on('change', function() {
		var $emailField = $('#lbite-email-field');
		var $emailInput = $emailField.find('input');

		if ($(this).val() === 'email') {
			$emailField.slideDown();
			$emailInput.prop('required', true).val('');
		} else {
			$emailField.slideUp();
			$emailInput.prop('required', false);
		}
	});

	// Vor dem Absenden: Platzhalter-E-Mail einfügen wenn kein Beleg gewählt
	$('form.checkout').on('checkout_place_order', function() {
		var receiptOption = $('input[name="lbite_receipt_option"]:checked').val();

		if (receiptOption === 'none' || !receiptOption) {
			var timestamp = Date.now();
			var placeholderEmail = 'guest-' + timestamp + '@nomail.local';
			$('#billing_email').val(placeholderEmail);
		}

		return true;
	});

	// Alternativ: Beim Submit des Formulars
	$('form.checkout').on('submit', function() {
		var receiptOption = $('input[name="lbite_receipt_option"]:checked').val();

		if (receiptOption === 'none' || !receiptOption) {
			var timestamp = Date.now();
			var placeholderEmail = 'guest-' + timestamp + '@nomail.local';
			$('#billing_email').val(placeholderEmail);
		}
	});
});
