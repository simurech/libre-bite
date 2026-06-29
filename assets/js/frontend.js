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

			// Bestelltyp-Auswahl: Tischnummer-Feld ein-/ausblenden + Steuer neu berechnen
			$(document).on('change', 'input[name="lbite_service_type"]', function() {
				var isDineIn = $(this).val() === 'dine_in';
				$('#lbite-table-number-wrap').toggle(isDineIn);
				$(document.body).trigger('update_checkout');
			});
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
	 * Standort-Filterung im Shop
	 *
	 * Liest den gewählten Standort aus localStorage (gesetzt beim Standort-Wechsel via AJAX)
	 * und markiert nicht verfügbare Produkte visuell. Ein Toggle-Button blendet sie aus/ein.
	 */
	const LocationFilter = {
		filtering: false,

		init: function() {
			if (typeof window.lbiteProductLocations === 'undefined') {
				return;
			}
			this.apply();
			$(document).on('click', '.lbite-filter-btn', function() {
				LocationFilter.toggle();
			});
		},

		apply: function() {
			var locationId   = parseInt(localStorage.getItem('lbite_location_id') || '0', 10);
			var locationName = localStorage.getItem('lbite_location_name') || '';
			var $notice      = $('#lbite-location-notice');

			if (!locationId || !$notice.length) {
				return;
			}

			var unavailableCount = 0;

			$('.products .product').each(function() {
				var match = this.className.match(/\bpost-(\d+)\b/);
				if (!match) { return; }
				var productId        = parseInt(match[1], 10);
				var productLocations = window.lbiteProductLocations[productId];

				// Leeres Array oder kein Eintrag = überall verfügbar
				if (!productLocations || productLocations.length === 0) {
					$(this).removeClass('lbite-unavailable');
					return;
				}

				if (productLocations.indexOf(locationId) === -1) {
					$(this).addClass('lbite-unavailable');
					unavailableCount++;
				} else {
					$(this).removeClass('lbite-unavailable');
				}
			});

			if (unavailableCount > 0) {
				var labelSingular = $notice.data('unavailable-singular') || '';
				var labelPlural   = $notice.data('unavailable-plural') || '';
				var label         = unavailableCount === 1 ? labelSingular : labelPlural;
				var filterShow    = $notice.data('filter-show') || '';

				$notice.html(
					'<span class="lbite-notice-text">' +
						(locationName ? '📍 ' + $('<span>').text(locationName).html() + ' &mdash; ' : '') +
						unavailableCount + ' ' + label +
					'</span>' +
					'<button class="lbite-filter-btn button">' + filterShow + '</button>'
				).show();

				// Gefilterten Zustand wiederherstellen falls aktiv
				if (this.filtering) {
					$('.products .product.lbite-unavailable').hide();
				}
			} else {
				$notice.hide();
			}
		},

		toggle: function() {
			this.filtering = !this.filtering;
			var $notice      = $('#lbite-location-notice');
			var filterShow    = $notice.data('filter-show') || '';
			var filterShowAll = $notice.data('filter-show-all') || '';

			if (this.filtering) {
				$('.products .product.lbite-unavailable').hide();
				$notice.find('.lbite-filter-btn').text(filterShowAll);
			} else {
				$('.products .product.lbite-unavailable').show();
				$notice.find('.lbite-filter-btn').text(filterShow);
			}
		}
	};

	/**
	 * Gewählten Standort in localStorage speichern wenn lbite_set_location erfolgreich war.
	 * Zentrale Abfangstelle für alle Templates (Banner, Modal, Tiles, Inline).
	 */
	$(document).ajaxSuccess(function(event, xhr, settings, response) {
		if (typeof settings.data !== 'string') { return; }
		if (settings.data.indexOf('action=lbite_set_location') === -1) { return; }
		if (!response || !response.success) { return; }

		var locationId   = response.data && response.data.location_id ? response.data.location_id : 0;
		var locationName = response.data && response.data.location_name ? response.data.location_name : '';
		localStorage.setItem('lbite_location_id', locationId || '');
		localStorage.setItem('lbite_location_name', locationName);

		// Sofort neu markieren falls Produktdaten auf der Seite vorhanden
		LocationFilter.filtering = false;
		LocationFilter.apply();
	});

	/**
	 * Initialisierung
	 */
	$(document).ready(function() {
		LocationModal.init();
		ProductOptions.init();
		Checkout.init();
		OpeningHours.init();
		LocationFilter.init();
	});

})(jQuery);
