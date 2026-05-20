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
	 * Öffnungszeiten-Lightbox
	 */
	const OpeningHours = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			$(document).on('click', '.lbite-hours-toggle', function(e) {
				e.stopPropagation();
				var $card   = $(this).closest('.lbite-location-card');
				var title   = $card.find('.lbite-location-name').text().trim();
				var content = $(this).next('.lbite-hours-popup').html() || '';
				OpeningHours.showModal(title, content);
			});
		},

		showModal: function(title, content) {
			$('#lbite-hours-modal').remove();

			var safeTitle = $('<span>').text(title).html();
			var $modal = $(
				'<div id="lbite-hours-modal" class="lbite-hours-modal-overlay" role="dialog" aria-modal="true">' +
					'<div class="lbite-hours-modal-box">' +
						'<button class="lbite-hours-modal-close" aria-label="Close">' +
							'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">' +
								'<line x1="18" y1="6" x2="6" y2="18"></line>' +
								'<line x1="6" y1="6" x2="18" y2="18"></line>' +
							'</svg>' +
						'</button>' +
						'<h4 class="lbite-hours-modal-title">' + safeTitle + '</h4>' +
						'<div class="lbite-hours-modal-body">' + content + '</div>' +
					'</div>' +
				'</div>'
			);

			$('body').append($modal);
			setTimeout(function() { $modal.addClass('lbite-hours-modal-visible'); }, 10);

			$modal.on('click', function(e) {
				if (!$(e.target).closest('.lbite-hours-modal-box').length) {
					OpeningHours.closeModal();
				}
			});
			$modal.find('.lbite-hours-modal-close').on('click', function() {
				OpeningHours.closeModal();
			});
			$(document).on('keydown.lbite-hours', function(e) {
				if (e.key === 'Escape') { OpeningHours.closeModal(); }
			});
		},

		closeModal: function() {
			var $modal = $('#lbite-hours-modal');
			$modal.removeClass('lbite-hours-modal-visible');
			setTimeout(function() { $modal.remove(); }, 200);
			$(document).off('keydown.lbite-hours');
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
