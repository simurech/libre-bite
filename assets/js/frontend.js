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
			$(document).on('change', '.lbite-location-picker', function() {
				LocationFilter.chooseLocation($(this));
			});
			$(document).on('click', '.lbite-notice-change-location', function() {
				LocationFilter.changeLocation();
			});
		},

		/**
		 * Standort-Auswahl direkt in der Hinweisleiste (kein Standort in localStorage,
		 * aber mindestens ein Produkt auf der Seite hat eine Standort-Einschränkung).
		 */
		renderPicker: function($notice) {
			if (typeof window.lbiteLocations === 'undefined' || window.lbiteLocations.length < 2) {
				$notice.hide();
				return;
			}

			var hasRestriction = Object.keys(window.lbiteProductLocations).some(function(id) {
				var excluded = window.lbiteProductLocations[id];
				return excluded && excluded.length > 0;
			});
			if (!hasRestriction) {
				$notice.hide();
				return;
			}

			var label       = $notice.data('choose-location') || '';
			var placeholder = $notice.data('choose-placeholder') || '';
			var $select     = $('<select class="lbite-location-picker"></select>');
			$select.append($('<option value=""></option>').text(placeholder));
			window.lbiteLocations.forEach(function(loc) {
				$select.append($('<option></option>').attr('value', loc.id).text(loc.name));
			});

			$notice.empty()
				.append($('<span class="lbite-notice-text">📍 </span>').append($('<span></span>').text(label)))
				.append($select)
				.show();
		},

		/**
		 * Standort aus dem Picker übernehmen — setzt den echten Bestell-Standort
		 * (gleicher AJAX-Endpoint wie der normale Standort-Selector), damit es kein
		 * zweites, paralleles Konzept von "aktueller Standort" gibt.
		 */
		chooseLocation: function($select) {
			var locationId = $select.val();
			if (!locationId) {
				return;
			}
			var locationName = $select.find('option:selected').text();

			$select.prop('disabled', true);

			$.ajax({
				url: lbiteData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lbite_set_location',
					nonce: lbiteData.nonce,
					location_id: locationId,
					// Leer statt "now": Hier wird nur zum Durchstöbern des Menüs ausgewählt,
					// keine Bestellabsicht. Bestelltyp/-zeit werden erst im Checkout-Flow
					// festgelegt (auch für Standorte, die erst künftig öffnen).
					order_type: ''
				},
				success: function(response) {
					if (response.success) {
						localStorage.setItem('lbite_location_id', locationId);
						localStorage.setItem('lbite_location_name', locationName);
						LocationFilter.apply();
					} else {
						$select.prop('disabled', false);
						alert((response.data && response.data.message) || 'Error');
					}
				},
				error: function() {
					$select.prop('disabled', false);
				}
			});
		},

		apply: function() {
			var locationId   = parseInt(localStorage.getItem('lbite_location_id') || '0', 10);
			var locationName = localStorage.getItem('lbite_location_name') || '';
			var $notice      = $('#lbite-location-notice');

			if (!$notice.length) {
				return;
			}

			if (!locationId) {
				this.renderPicker($notice);
				return;
			}

			var unavailableCount = 0;

			$('.products .product').each(function() {
				var match = this.className.match(/\bpost-(\d+)\b/);
				if (!match) { return; }
				var productId         = parseInt(match[1], 10);
				var excludedLocations = window.lbiteProductLocations[productId];

				// Leeres Array oder kein Eintrag = überall verfügbar (nichts ausgeschlossen)
				if (!excludedLocations || excludedLocations.length === 0) {
					$(this).removeClass('lbite-unavailable');
					return;
				}

				if (excludedLocations.indexOf(locationId) !== -1) {
					$(this).addClass('lbite-unavailable');
					unavailableCount++;
				} else {
					$(this).removeClass('lbite-unavailable');
				}
			});

			var changeLocationLabel = $notice.data('change-location') || '';
			var $changeBtn = $('<button type="button" class="lbite-notice-change-location"></button>').text(changeLocationLabel);

			if (unavailableCount > 0) {
				var labelSingular = $notice.data('unavailable-singular') || '';
				var labelPlural   = $notice.data('unavailable-plural') || '';
				var label         = unavailableCount === 1 ? labelSingular : labelPlural;
				var filterShow    = $notice.data('filter-show') || '';

				$notice.empty()
					.append(
						$('<span class="lbite-notice-text"></span>').html(
							(locationName ? '📍 ' + $('<span>').text(locationName).html() + ' &mdash; ' : '') +
							unavailableCount + ' ' + label
						)
					)
					.append('<button class="lbite-filter-btn button">' + filterShow + '</button>')
					.append($changeBtn)
					.show();

				// Gefilterten Zustand wiederherstellen falls aktiv
				if (this.filtering) {
					$('.products .product.lbite-unavailable').hide();
				}
			} else if (locationName) {
				// Standort gewählt, aber nichts eingeschränkt: Leiste bleibt sichtbar,
				// damit der Standort trotzdem jederzeit gewechselt werden kann.
				$notice.empty()
					.append($('<span class="lbite-notice-text">📍 </span>').append($('<span></span>').text(locationName)))
					.append($changeBtn)
					.show();
			} else {
				$notice.hide();
			}
		},

		/**
		 * Standort-Auswahl zurücksetzen und wieder den Picker anzeigen.
		 */
		changeLocation: function() {
			localStorage.removeItem('lbite_location_id');
			localStorage.removeItem('lbite_location_name');
			this.filtering = false;
			$('.products .product.lbite-unavailable').show();
			this.apply();
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
	 * Verfügbarkeits-Popup am Grid-Item/Produktseite (Hover am Desktop, Klick-Toggle auf Touch).
	 *
	 * Das sichtbare Popup ist ein einzelnes, wiederverwendetes Element als Kind von <body>
	 * (position: fixed, per JS anhand der Toggle-Button-Position platziert) statt eines
	 * position:absolute-Elements innerhalb der Produktkarte. So bleibt die gewohnte, kompakte
	 * Darstellung nahe am Button erhalten, aber ohne dass Theme-Produktkarten mit
	 * overflow:hidden (z. B. für Hover-Zoom-Effekte) das Popup abschneiden können – das PHP
	 * rendert weiterhin ein verstecktes .lbite-availability-popup pro Produkt, das hier nur als
	 * Inhaltsquelle dient (siehe render_product_availability_badge()).
	 */
	const ProductAvailability = {
		$floatingPopup: null,
		$openToggle: null,

		init: function() {
			// Auf der Einzelprodukt-Seite direkt nach dem Titel positionieren – per JS statt Hook-
			// Priorität, da manche Themes (z. B. Astra) die Summary-Reihenfolge selbst umbauen.
			var $singleBadge = $('.single-product .entry-summary > .lbite-availability');
			var $title       = $('.single-product .entry-summary > .product_title');
			if ($singleBadge.length && $title.length) {
				$singleBadge.insertAfter($title);
			}

			// Shop-Loop: Themes, die li.product als Flex-Container ohne Umbruch aufbauen (Bild
			// links, Inhalt rechts, z. B. Astra "List Style"), reihen unser Badge sonst als
			// drittes Flex-Geschwisterkind rechts daneben statt darunter ein. In diesen Fällen
			// das Badge in den vorherigen Geschwister-Container (den Inhalts-Wrapper) verschieben.
			$('ul.products li.product > .lbite-availability').each(function() {
				var $badge  = $(this);
				var $parent = $badge.parent();
				var display = $parent.css('display');
				var wrap    = $parent.css('flex-wrap');
				if (display === 'flex' && wrap !== 'wrap') {
					var $prev = $badge.prev();
					if ($prev.length) {
						$prev.append($badge);
					}
				}
			});

			if (!$('.lbite-availability-toggle').length) {
				return;
			}

			var self = this;
			self.$floatingPopup = $('<div class="lbite-availability-floating-popup"></div>').appendTo('body');

			var isHoverCapable = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;

			$(document).on('click', '.lbite-availability-toggle', function(e) {
				e.stopPropagation();
				var $toggle = $(this);
				var wasOpen = self.$openToggle && self.$openToggle.is($toggle);
				self.close();
				if (!wasOpen) {
					self.open($toggle);
				}
			});

			if (isHoverCapable) {
				$(document).on('mouseenter', '.lbite-availability', function() {
					self.open($(this).find('.lbite-availability-toggle'));
				});
				$(document).on('mouseleave', '.lbite-availability', function() {
					self.close();
				});
			}

			$(document).on('click', function() {
				self.close();
			});

			$(window).on('scroll resize', function() {
				if (self.$openToggle) {
					self.reposition();
				}
			});
		},

		open: function($toggle) {
			var $source = $toggle.siblings('.lbite-availability-popup').find('.lbite-availability-table');
			if (!$source.length) {
				return;
			}
			this.$floatingPopup.html($source.prop('outerHTML')).addClass('lbite-visible');
			this.$openToggle = $toggle;
			this.reposition();
			$toggle.attr('aria-expanded', 'true');
		},

		close: function() {
			this.$floatingPopup.removeClass('lbite-visible');
			if (this.$openToggle) {
				this.$openToggle.attr('aria-expanded', 'false');
				this.$openToggle = null;
			}
		},

		reposition: function() {
			var rect       = this.$openToggle[0].getBoundingClientRect();
			var popupWidth = this.$floatingPopup.outerWidth();
			var left       = rect.left;
			var maxLeft    = window.innerWidth - popupWidth - 8;

			if (left > maxLeft) {
				left = Math.max(8, maxLeft);
			}

			this.$floatingPopup.css({
				top:  ( rect.bottom + 4 ) + 'px',
				left: left + 'px'
			});
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
		ProductAvailability.init();
	});

})(jQuery);
