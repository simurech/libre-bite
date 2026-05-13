jQuery(document).ready(function($) {
	// Platzhalter-E-Mail einfügen damit WooCommerce das Pflichtfeld nicht blockiert
	function lbiteInjectDummyEmail() {
		var $emailInput = $('#billing_email');
		if (!$emailInput.val()) {
			$emailInput.val('guest-' + Date.now() + '@nomail.local');
		}
	}

	$('form.checkout').on('checkout_place_order', function() {
		lbiteInjectDummyEmail();
		return true;
	});

	$('form.checkout').on('submit', function() {
		lbiteInjectDummyEmail();
	});
});
