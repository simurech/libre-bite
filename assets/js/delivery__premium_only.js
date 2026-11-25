/**
 * Auswahl «Abholung / Lieferung» im Checkout (F44, Pro)
 *
 * Blendet die Adressfelder ein und aus und lässt WooCommerce den
 * Bestellüberblick neu berechnen, damit die Liefergebühr sofort erscheint.
 * Die Postleitzahl löst die Neuberechnung erst nach einer kurzen Pause aus,
 * nicht bei jedem Tastendruck.
 */
jQuery(function ($) {
	'use strict';

	var $block = $('#lbite-fulfilment');
	if (!$block.length) {
		return;
	}

	var $delivery = $block.find('.lbite-fulfilment__delivery');
	var timer = null;

	function refresh() {
		$(document.body).trigger('update_checkout');
	}

	// Lieferung ist immer «zum Mitnehmen» (Steuersatz): Die Auswahl
	// Mitnehmen/Hier essen wird dann vorbelegt und ausgeblendet.
	// Zwei Varianten: im Block der Standort-/Zeitwahl umschliesst
	// .lbite-service-type-selector die Optionen, im Standard-Checkout ist es
	// das eigenständige #lbite-order-type-selector.
	var $serviceType = $('.lbite-service-type-selector');
	if (!$serviceType.length) {
		$serviceType = $('#lbite-order-type-selector');
	}

	$block.on('change', 'input[name="lbite_fulfilment"]', function () {
		var isDelivery = this.value === 'delivery';
		$delivery.prop('hidden', !isDelivery);
		$delivery.find('input').not('#lbite_delivery_notes').prop('required', isDelivery);
		if ($serviceType.length) {
			if (isDelivery) {
				$serviceType.find('input[name="lbite_service_type"][value="takeaway"]').prop('checked', true).trigger('change');
			}
			$serviceType.toggle(!isDelivery);
		}
		refresh();
	});

	$block.on('input', '#lbite_delivery_postcode', function () {
		clearTimeout(timer);
		timer = setTimeout(refresh, 600);
	});

	// Startzustand: Pflichtfelder nur, wenn Lieferung bereits gewählt ist.
	$block.find('input[name="lbite_fulfilment"]:checked').trigger('change');
});
