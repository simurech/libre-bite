/**
 * Frontend-JavaScript für Open Order System
 */

(function($) {
	'use strict';

	/**
	 * Standort-Modal
	 */
	const LocationModal = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			// Modal bereits im Template implementiert
		}
	};

	/**
	 * Produkt-Optionen
	 */
	const ProductOptions = {
		init: function() {
			this.updatePrice();
			this.bindEvents();
		},

		bindEvents: function() {
			$(document).on('change', '.lbite-product-options input[type="checkbox"]', function() {
				ProductOptions.updatePrice();
			});
		},

		updatePrice: function() {
			// Preis-Update wird via WooCommerce Hooks gemacht
			// Hier könnte zusätzliches Frontend-Feedback erfolgen
		}
	};

	/**
	 * Checkout-Funktionen
	 */
	const Checkout = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			// Zeitfeld anzeigen/verstecken
			// Wird in checkout-location-time.php Template gehandhabt
		}
	};

	/**
	 * Loading Overlay
	 */
	const Loading = {
		show: function() {
			if ($('.lbite-loading-overlay').length === 0) {
				$('body').append('<div class="lbite-loading-overlay"><div class="lbite-spinner"></div></div>');
			}
		},

		hide: function() {
			$('.lbite-loading-overlay').fadeOut(function() {
				$(this).remove();
			});
		}
	};

	window.lbiteLoading = Loading;

	/**
	 * Öffnungszeiten-Toggle
	 */
	const OpeningHours = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			$(document).on('click', '.lbite-hours-toggle', function(e) {
				e.stopPropagation();
				var $popup = $(this).next('.lbite-hours-popup');
				var isOpen = $popup.hasClass('lbite-popup-open');

				$('.lbite-hours-popup').removeClass('lbite-popup-open');
				$('.lbite-hours-toggle').attr('aria-expanded', 'false');

				if ( ! isOpen ) {
					$popup.addClass('lbite-popup-open');
					$(this).attr('aria-expanded', 'true');
				}
			});

			$(document).on('click', function(e) {
				if ( ! $(e.target).closest('.lbite-hours-popup, .lbite-hours-toggle').length ) {
					$('.lbite-hours-popup').removeClass('lbite-popup-open');
					$('.lbite-hours-toggle').attr('aria-expanded', 'false');
				}
			});
		}
	};

	/**
	 * Initialisierung
	 */
	$(document).ready(function() {
		LocationModal.init();
		ProductOptions.init();
		Checkout.init();
		OpeningHours.init();
	});

})(jQuery);
