jQuery(document).ready(function($) {
	// Platzhalter-E-Mail nur für Offline-Gateways einfügen (Barzahlung, Überweisung).
	// Online-Gateways (TWINT etc.) erfordern eine echte E-Mail-Adresse.
	function lbiteInjectDummyEmail() {
		var noEmailGateways = ['cod', 'bacs', 'cheque'];
		var selectedGateway = $('input[name="payment_method"]:checked').val();
		if ( ! selectedGateway || noEmailGateways.indexOf( selectedGateway ) === -1 ) {
			return;
		}
		var $emailInput = $('#billing_email');
		if ( ! $emailInput.val() ) {
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
