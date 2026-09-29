/**
 * Menü-Ansicht – Interaktion
 *
 * Produkt-Modal und Slide-In-Warenkorb. Artikel werden über den nativen
 * WooCommerce-AJAX-Endpunkt hinzugefügt und nicht über einen eigenen
 * Bestellweg: nur so greifen die bestehende Add-on-Preisverrechnung, die
 * Zeitplan-Prüfung und die Steuerlogik unverändert weiter.
 *
 * Die Feldnamen im Modal entsprechen deshalb exakt denen der normalen
 * Produktseite — `lbite_options[]` und `attribute_*`.
 */
( function ( $ ) {
	'use strict';

	const MenuView = {
		$root: null,
		$modal: null,
		busy: false,
		activeDiets: [],
		onlyAvailable: false,
		$availabilityPopup: null,
		$openAvailabilityToggle: null,

		init: function () {
			this.$root = $( '[data-lbite-menu]' );
			if ( this.$root.length === 0 ) {
				return;
			}

			this.$modal = $( '#lbite-menu-modal' );
			this.bindEvents();
			this.initAvailabilityPopup();
			this.refreshCart();
		},

		bindEvents: function () {
			const self = this;

			// Artikel hinzufügen oder Modal öffnen
			this.$root.on( 'click', '.lbite-menu-item__add', function () {
				const $item = $( this ).closest( '.lbite-menu-item' );
				const productId = $item.data( 'product-id' );

				if ( String( $item.data( 'needs-options' ) ) === '1' ) {
					self.openModal( productId );
				} else {
					self.addToCart( productId, 1, {}, $( this ) );
				}
			} );

			// Modal schliessen
			this.$root.on( 'click', '[data-lbite-close]', function () {
				self.closeModal();
			} );

			$( document ).on( 'keydown', function ( e ) {
				if ( e.key === 'Escape' ) {
					self.closeModal();
					self.toggleCart( false );
				}
			} );

			// Abschnitts-Navigation: reines Scrollen, keine dauerhafte Auswahl -
			// der Button zeigt bewusst nur echtes :hover (siehe menu-view.css),
			// kein anhaltender "aktiv"-Zustand nach dem Klick.
			this.$root.on( 'click', '.lbite-menu-nav__item', function ( e ) {
				e.preventDefault();
				const target = $( $( this ).attr( 'href' ) );
				if ( target.length ) {
					$( 'html, body' ).animate( { scrollTop: target.offset().top - 20 }, 300 );
				}
				$( this ).trigger( 'blur' );
			} );

			// Warenkorb auf/zu
			this.$root.on( 'click', '#lbite-menu-cart-toggle', function () {
				self.toggleCart( true );
			} );
			this.$root.on( 'click', '[data-lbite-cart-close]', function () {
				self.toggleCart( false );
			} );

			// Hinzufügen aus dem Modal
			this.$root.on( 'click', '.lbite-menu-modal__add', function () {
				self.submitModal( $( this ) );
			} );

			// Variantenauswahl im Modal: Buttons statt Dropdown (Nutzer-Fund
			// 2026-09-29) - Einfachauswahl innerhalb der eigenen Attribut-Gruppe.
			this.$root.on( 'click', '.lbite-menu-modal__variation-btn', function () {
				const $btn = $( this );
				const $group = $btn.closest( '.lbite-menu-modal__variation-group' );
				$group.removeClass( 'is-invalid' ).find( '.lbite-menu-modal__variation-btn' ).removeClass( 'is-active' );
				$btn.addClass( 'is-active' );
				self.updateVariationPrice();
			} );

			// WooCommerce meldet Warenkorb-Änderungen
			$( document.body ).on( 'added_to_cart wc_fragments_refreshed wc_fragments_loaded', function () {
				self.refreshCart();
			} );

			// Standort-Umschalter: derselbe AJAX-Endpoint wie der reguläre Standort-
			// Selector (order_type bewusst leer - reine Durchstöber-Auswahl, keine
			// Bestellabsicht). Danach ein einfacher Reload statt eines eigenen
			// Client-seitigen Re-Renderns: get_menu() filtert serverseitig bereits
			// korrekt nach Standort, ein Reload ist robuster als das nachzubauen.
			this.$root.on( 'change', '.lbite-menu-location-banner__picker', function () {
				const $picker = $( this );
				const locationId = $picker.val();
				if ( ! locationId ) {
					return;
				}

				$picker.prop( 'disabled', true );

				$.ajax( {
					url: lbiteMenu.ajaxUrl,
					type: 'POST',
					data: {
						action: 'lbite_set_location',
						nonce: lbiteMenu.nonce,
						location_id: locationId,
						order_type: ''
					},
					success: function ( response ) {
						if ( response.success ) {
							window.location.reload();
						} else {
							$picker.prop( 'disabled', false );
						}
					},
					error: function () {
						$picker.prop( 'disabled', false );
					}
				} );
			} );

			// Ernährungsform-Filter (eigenständig statt frontend.js: dessen DietaryFilter
			// zielt auf die WooCommerce-Loop-Markup .products .product, nicht auf
			// .lbite-menu-item, und frontend.js wird auf dieser Seite gar nicht geladen).
			this.$root.on( 'click', '.lbite-menu-dietary-filter__btn', function () {
				const diet = $( this ).data( 'diet' );
				const idx = self.activeDiets.indexOf( diet );

				if ( idx === -1 ) {
					self.activeDiets.push( diet );
					$( this ).addClass( 'is-active' );
				} else {
					self.activeDiets.splice( idx, 1 );
					$( this ).removeClass( 'is-active' );
				}

				self.applyItemVisibility();
			} );

			this.$root.on( 'click', '.lbite-menu-dietary-filter__reset', function () {
				self.activeDiets = [];
				self.$root.find( '.lbite-menu-dietary-filter__btn' ).removeClass( 'is-active' );
				self.applyItemVisibility();
			} );

			// "Nur verfügbare Produkte anzeigen" - analog zum gleichnamigen Filter
			// auf den Standard-Shop-Seiten (LocationFilter.toggle() in frontend.js),
			// hier aber eigenständig, weil frontend.js auf dieser Seite bewusst
			// nicht geladen wird (siehe enqueue_assets() in class-menu-view.php).
			this.$root.on( 'click', '[data-lbite-availability-toggle]', function () {
				const $btn = $( this );
				self.onlyAvailable = ! self.onlyAvailable;
				$btn.text( self.onlyAvailable ? lbiteMenu.strings.showAll : lbiteMenu.strings.showOnly );
				self.applyItemVisibility();
			} );

			// "Standort ändern": zurück zum Auswahl-Dropdown, keine eigene AJAX-
			// Aktion nötig - die bestehende change-Bindung auf .lbite-menu-
			// location-banner__picker übernimmt das Setzen des neuen Standorts.
			// Wert auf "Bitte wählen" zurücksetzen (Nutzer-Fund 2026-09-29):
			// blieb der bisherige Standort vorausgewählt, feuerte ein erneuter
			// Klick auf dieselbe Option kein change-Event - wirkte wie "passiert
			// nichts", analog zum frischen Picker auf den Standard-Shop-Seiten.
			this.$root.on( 'click', '[data-lbite-location-change]', function () {
				self.$root.find( '[data-lbite-location-current]' ).prop( 'hidden', true );
				self.$root.find( '[data-lbite-location-picker]' ).prop( 'hidden', false );
				self.$root.find( '.lbite-menu-location-banner__picker' ).val( '' );
			} );
		},

		/* ── Ernährungsform-/Verfügbarkeits-Filter ───────────────────── */

		applyItemVisibility: function () {
			const self = this;
			const hasDietFilter = this.activeDiets.length > 0;

			this.$root.find( '.lbite-menu-dietary-filter__reset' ).prop( 'hidden', ! hasDietFilter );

			this.$root.find( '.lbite-menu-item' ).each( function () {
				const $item = $( this );
				let hidden = false;

				if ( hasDietFilter ) {
					const raw = $item.data( 'diet' );
					const diets = String( raw === undefined ? '' : raw ).split( ' ' ).filter( Boolean );
					const matchesAll = self.activeDiets.every( function ( d ) {
						return diets.indexOf( d ) !== -1;
					} );
					if ( ! matchesAll ) {
						hidden = true;
					}
				}

				if ( self.onlyAvailable && $item.hasClass( 'lbite-unavailable' ) ) {
					hidden = true;
				}

				$item.prop( 'hidden', hidden );
			} );

			// Ein Abschnitt kann durch die Filterung komplett leer werden - ohne
			// Hinweis bliebe dort nur der Titel über einer leeren Fläche stehen
			// (Nutzer-Fund 2026-09-29).
			const activeLabels = this.$root.find( '.lbite-menu-dietary-filter__btn.is-active' )
				.map( function () {
					return $( this ).text().trim();
				} )
				.get();

			this.$root.find( '.lbite-menu-section' ).each( function () {
				const $section = $( this );
				const $grid    = $section.find( '.lbite-menu-grid' );
				const anyVisible = $grid.find( '.lbite-menu-item' ).filter( function () {
					return ! $( this ).prop( 'hidden' );
				} ).length > 0;

				let $empty = $section.find( '.lbite-menu-section__empty' );

				if ( hasDietFilter && ! anyVisible ) {
					if ( ! $empty.length ) {
						$empty = $( '<p class="lbite-menu-section__empty"></p>' );
						$grid.after( $empty );
					}
					$empty.text( lbiteMenu.strings.noMatch.replace( '%s', activeLabels.join( ', ' ) ) ).prop( 'hidden', false );
				} else if ( $empty.length ) {
					$empty.prop( 'hidden', true );
				}
			} );
		},

		/* ── Verfügbarkeits-Hinweis ───────────────────────────────────
		 * Nachgebaut aus ProductAvailability in frontend.js (dasselbe Markup,
		 * über LBite_Locations::render_availability_hint() wiederverwendet) -
		 * hier eigenständig statt frontend.js zu laden, das mehrere reine
		 * Checkout-/Shop-Loop-Objekte enthält, die auf dieser Seite ohnehin
		 * ins Leere liefen und zusätzliche lbiteData-Lokalisierung bräuchten
		 * (Nutzer-Fund 2026-09-29). */

		initAvailabilityPopup: function () {
			const self = this;

			if ( ! this.$root.find( '.lbite-availability-toggle' ).length ) {
				return;
			}

			this.$availabilityPopup = $( '<div class="lbite-availability-floating-popup"></div>' ).appendTo( 'body' );

			const isHoverCapable = window.matchMedia && window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches;

			this.$root.on( 'click', '.lbite-availability-toggle', function ( e ) {
				e.stopPropagation();
				const $toggle = $( this );
				const wasOpen = self.$openAvailabilityToggle && self.$openAvailabilityToggle.is( $toggle );
				self.closeAvailabilityPopup();
				if ( ! wasOpen ) {
					self.openAvailabilityPopup( $toggle );
				}
			} );

			if ( isHoverCapable ) {
				this.$root.on( 'mouseenter', '.lbite-availability', function () {
					self.openAvailabilityPopup( $( this ).find( '.lbite-availability-toggle' ) );
				} );
				this.$root.on( 'mouseleave', '.lbite-availability', function () {
					self.closeAvailabilityPopup();
				} );
			}

			$( document ).on( 'click', function () {
				self.closeAvailabilityPopup();
			} );

			$( window ).on( 'scroll resize', function () {
				if ( self.$openAvailabilityToggle ) {
					self.repositionAvailabilityPopup();
				}
			} );
		},

		openAvailabilityPopup: function ( $toggle ) {
			const $source = $toggle.siblings( '.lbite-availability-popup' ).find( '.lbite-availability-table' );
			if ( ! $source.length ) {
				return;
			}
			this.$availabilityPopup.html( $source.prop( 'outerHTML' ) ).addClass( 'lbite-visible' );
			this.$openAvailabilityToggle = $toggle;
			this.repositionAvailabilityPopup();
			$toggle.attr( 'aria-expanded', 'true' );
		},

		closeAvailabilityPopup: function () {
			if ( ! this.$availabilityPopup ) {
				return;
			}
			this.$availabilityPopup.removeClass( 'lbite-visible' );
			if ( this.$openAvailabilityToggle ) {
				this.$openAvailabilityToggle.attr( 'aria-expanded', 'false' );
				this.$openAvailabilityToggle = null;
			}
		},

		repositionAvailabilityPopup: function () {
			const rect = this.$openAvailabilityToggle[ 0 ].getBoundingClientRect();
			const popupWidth = this.$availabilityPopup.outerWidth();
			let left = rect.left;
			const maxLeft = window.innerWidth - popupWidth - 8;

			if ( left > maxLeft ) {
				left = Math.max( 8, maxLeft );
			}

			this.$availabilityPopup.css( {
				top: ( rect.bottom + 4 ) + 'px',
				left: left + 'px'
			} );
		},

		/* ── Modal ────────────────────────────────────────────────── */

		openModal: function ( productId ) {
			const self = this;

			this.$modal.removeAttr( 'hidden' ).addClass( 'is-open' );
			$( '#lbite-menu-modal-body' ).html( '<div class="lbite-menu-modal__loading"></div>' );

			$.post( lbiteMenu.ajaxUrl, {
				action: 'lbite_menu_product',
				nonce: lbiteMenu.nonce,
				product_id: productId
			} ).done( function ( response ) {
				if ( ! response || ! response.success ) {
					const msg = ( response && response.data && response.data.message ) || lbiteMenu.strings.error;
					$( '#lbite-menu-modal-body' ).html( $( '<p class="lbite-menu-modal__error"></p>' ).text( msg ) );
					return;
				}
				self.renderModal( response.data );
			} ).fail( function () {
				$( '#lbite-menu-modal-body' ).html(
					$( '<p class="lbite-menu-modal__error"></p>' ).text( lbiteMenu.strings.error )
				);
			} );
		},

		renderModal: function ( data ) {
			const self = this;
			const $body = $( '#lbite-menu-modal-body' ).empty();

			this.currentModalData = data;

			if ( data.image ) {
				$body.append( $( '<img class="lbite-menu-modal__img">' ).attr( { src: data.image, alt: '' } ) );
			}

			$body.append( $( '<h2 class="lbite-menu-modal__title" id="lbite-menu-modal-title"></h2>' ).text( data.name ) );

			if ( data.description ) {
				$body.append( $( '<div class="lbite-menu-modal__desc"></div>' ).html( data.description ) );
			}

			const $form = $( '<div class="lbite-menu-modal__form"></div>' ).attr( 'data-product-id', data.id );

			// Varianten: Buttons statt Dropdown (Nutzer-Fund 2026-09-29), damit der
			// Preis pro Option sichtbar ist. Feldname (attribute_<slug>) NICHT selbst
			// aus dem Anzeigenamen nachbauen (Nutzer-Fund 2026-09-29 - führte bei
			// Umlauten/Sonderzeichen im Attributnamen zu "Bitte eine Auswahl treffen",
			// weil WordPress' eigenes sanitize_title() Akzente entfernt, ein simples
			// JS-Lowercase aber nicht) - stattdessen den von WooCommerce selbst
			// gelieferten Feldnamen aus den Variationsdaten übernehmen, der ist
			// garantiert korrekt.
			const attrNames = data.attributes ? Object.keys( data.attributes ) : [];
			const fieldNames = ( data.variations && data.variations.length )
				? Object.keys( data.variations[ 0 ].attributes )
				: [];

			if ( data.type === 'variable' && attrNames.length === 1 && fieldNames.length === 1 ) {
				// Genau ein variationsbildendes Attribut (Regelfall: eine Grösse) -
				// Preis direkt am Button, aus den Variationsdaten selbst gebaut statt
				// aus der Attribut-Werteliste, damit Button-Wert und Variation
				// garantiert zusammenpassen.
				const attrName = attrNames[ 0 ];
				const fieldName = fieldNames[ 0 ];
				const $group = $( '<div class="lbite-menu-modal__group"></div>' );
				$group.append( $( '<span class="lbite-menu-modal__label"></span>' ).text( attrName ) );

				const $btnGroup = $( '<div class="lbite-menu-modal__variation-group"></div>' ).attr( 'data-attribute', fieldName );

				data.variations.forEach( function ( variation ) {
					const value = variation.attributes[ fieldName ] || '';
					const $vbtn = $( '<button type="button" class="lbite-menu-modal__variation-btn"></button>' )
						.attr( 'data-value', value )
						.attr( 'data-variation-id', variation.id );
					$vbtn.append( $( '<span class="lbite-menu-modal__variation-btn-label"></span>' ).text( variation.label || value ) );
					$vbtn.append( $( '<span class="lbite-menu-modal__variation-btn-price"></span>' ).text( variation.price ) );
					$btnGroup.append( $vbtn );
				} );

				$group.append( $btnGroup );
				$form.append( $group );
			} else if ( data.type === 'variable' && attrNames.length > 1 ) {
				// Mehrere variationsbildende Attribute (Randfall, z. B. Grösse +
				// Sorte): Preis erst nach vollständiger Auswahl bekannt, deshalb
				// Button-Gruppen ohne Preis am Button plus einer Live-Preiszeile.
				// Reihenfolge von data.attributes und data.variations[0].attributes
				// stammt aus demselben WooCommerce-Attribut-Durchlauf, daher per
				// Index zuordenbar.
				attrNames.forEach( function ( attrName, idx ) {
					const values = data.attributes[ attrName ];
					const fieldName = fieldNames[ idx ] || ( 'attribute_' + self.slugify( attrName ) );

					const $group = $( '<div class="lbite-menu-modal__group"></div>' );
					$group.append( $( '<span class="lbite-menu-modal__label"></span>' ).text( attrName ) );

					const $btnGroup = $( '<div class="lbite-menu-modal__variation-group"></div>' ).attr( 'data-attribute', fieldName );

					values.forEach( function ( value ) {
						$btnGroup.append(
							$( '<button type="button" class="lbite-menu-modal__variation-btn"></button>' )
								.attr( 'data-value', value )
								.text( value )
						);
					} );

					$group.append( $btnGroup );
					$form.append( $group );
				} );

				$form.append(
					$( '<p class="lbite-menu-modal__variation-price" data-lbite-variation-price></p>' ).text( lbiteMenu.strings.chooseOption )
				);
			}

			// Zusatzoptionen
			if ( data.options && data.options.length ) {
				const $group = $( '<div class="lbite-menu-modal__group"></div>' );
				$group.append( $( '<span class="lbite-menu-modal__label"></span>' ).text( lbiteMenu.strings.options ) );
				data.options.forEach( function ( option ) {
					const $label = $( '<label class="lbite-menu-modal__option"></label>' );
					$label.append(
						$( '<input type="checkbox" class="lbite-menu-modal__opt">' ).val( option.id )
					);
					$label.append( $( '<span></span>' ).text( option.name ) );
					if ( option.price > 0 ) {
						$label.append( $( '<em></em>' ).text( '+' + option.price.toFixed( 2 ) ) );
					}
					$group.append( $label );
				} );
				$form.append( $group );
			}

			$body.append( $form );

			$body.append(
				$( '<button type="button" class="lbite-menu-modal__add"></button>' ).text( lbiteMenu.strings.add )
			);
		},

		/**
		 * Grobe clientseitige Entsprechung zu sanitize_title(): dient nur dazu,
		 * Attributnamen konsistent mit dem serverseitigen Feldnamen-Schema
		 * (attribute_<slug>) zu bilden bzw. Button-Werte mit den Variationsdaten
		 * abzugleichen - kein Ersatz für die serverseitige Validierung.
		 */
		slugify: function ( value ) {
			return String( value === undefined || value === null ? '' : value )
				.toLowerCase()
				.trim()
				.replace( /[^a-z0-9]+/g, '-' )
				.replace( /^-+|-+$/g, '' );
		},

		/**
		 * Zu einer Attribut-Auswahl passende Variation suchen (nur für den
		 * Mehrattribut-Fall relevant, siehe renderModal()).
		 */
		findMatchingVariation: function ( data, selections ) {
			const self = this;
			const variations = ( data && data.variations ) || [];

			for ( let i = 0; i < variations.length; i++ ) {
				const variation = variations[ i ];
				const matches = Object.keys( selections ).every( function ( field ) {
					const raw = variation.attributes[ field ];
					// Ein leerer Wert bedeutet in WooCommerce "beliebig" (Any).
					return raw === '' || raw === undefined || self.slugify( raw ) === selections[ field ];
				} );

				if ( matches ) {
					return variation;
				}
			}

			return null;
		},

		/**
		 * Live-Preisanzeige für den Mehrattribut-Fall aktualisieren, sobald alle
		 * Attribut-Gruppen eine aktive Auswahl haben.
		 */
		updateVariationPrice: function () {
			const self = this;
			const $priceLine = $( '#lbite-menu-modal-body [data-lbite-variation-price]' );

			if ( ! $priceLine.length || ! this.currentModalData ) {
				return;
			}

			const $form = $( '#lbite-menu-modal-body .lbite-menu-modal__form' );
			const selections = {};
			let complete = true;

			$form.find( '.lbite-menu-modal__variation-group' ).each( function () {
				const field = $( this ).data( 'attribute' );
				const $active = $( this ).find( '.lbite-menu-modal__variation-btn.is-active' );

				if ( ! $active.length ) {
					complete = false;
					return;
				}

				selections[ field ] = self.slugify( $active.data( 'value' ) );
			} );

			if ( ! complete ) {
				$priceLine.text( lbiteMenu.strings.chooseOption );
				return;
			}

			const match = self.findMatchingVariation( self.currentModalData, selections );
			$priceLine.text( match ? match.price : lbiteMenu.strings.chooseOption );
		},

		submitModal: function ( $button ) {
			const self = this;
			const $form = $( '#lbite-menu-modal-body .lbite-menu-modal__form' );
			let productId = $form.data( 'product-id' );
			const extra = {};
			const selections = {};
			let missing = false;

			$form.find( '.lbite-menu-modal__variation-group' ).each( function () {
				const $group = $( this );
				const $active = $group.find( '.lbite-menu-modal__variation-btn.is-active' );

				if ( ! $active.length ) {
					missing = true;
					$group.addClass( 'is-invalid' );
				} else {
					$group.removeClass( 'is-invalid' );
					const field = $group.data( 'attribute' );
					const value = $active.data( 'value' );
					selections[ field ] = self.slugify( value );
				}
			} );

			if ( missing ) {
				return;
			}

			// Kern des Bugs (Nutzer-Fund 2026-09-29, im WooCommerce-Kern verifiziert):
			// WC_AJAX::add_to_cart() liest variation_id/attribute_* NIE aus $_POST.
			// Es prüft stattdessen nur, ob die gesendete product_id selbst ein
			// Variations-Post ist, und leitet Eltern-ID + Attribute serverseitig
			// selbst daraus ab. Weder das ursprüngliche Senden der Attribute noch
			// das spätere zusätzliche Senden einer separaten variation_id wurden
			// je gelesen - die product_id muss die Variations-ID selbst sein.
			if ( self.currentModalData && self.currentModalData.type === 'variable' && Object.keys( selections ).length ) {
				const match = self.findMatchingVariation( self.currentModalData, selections );
				if ( match ) {
					productId = match.id;
				}
			}

			const options = [];
			$form.find( '.lbite-menu-modal__opt:checked' ).each( function () {
				options.push( $( this ).val() );
			} );
			if ( options.length ) {
				extra[ 'lbite_options' ] = options;
			}

			// Kein Mengenfeld mehr im Popup (Nutzer-Fund 2026-09-29): ein Klick
			// fügt immer 1 Stück hinzu, konsistent zum Verhalten einfacher
			// Artikel ohne Popup - für mehr erneut klicken.
			this.addToCart( productId, 1, extra, $button, true );
		},

		/* ── Warenkorb ────────────────────────────────────────────── */

		addToCart: function ( productId, quantity, extra, $button, closeAfter ) {
			const self = this;

			if ( this.busy ) {
				return;
			}
			this.busy = true;

			const original = $button.text();
			$button.prop( 'disabled', true ).text( lbiteMenu.strings.adding );

			const payload = {
				product_id: productId,
				quantity: quantity
			};

			// Variationen brauchen zusätzlich die Variations-ID nicht: WooCommerce
			// löst sie aus den attribute_*-Feldern auf.
			Object.keys( extra || {} ).forEach( function ( key ) {
				payload[ key ] = extra[ key ];
			} );

			$.ajax( {
				url: self.getAddToCartUrl(),
				method: 'POST',
				data: payload,
				traditional: false
			} ).done( function ( response ) {
				self.busy = false;
				$button.prop( 'disabled', false );

				if ( response && response.error ) {
					$button.text( original );
					self.showError( response.product_url ? lbiteMenu.strings.chooseOption : lbiteMenu.strings.error );
					return;
				}

				$button.text( lbiteMenu.strings.added );
				setTimeout( function () {
					$button.text( original );
				}, 1500 );

				if ( closeAfter ) {
					self.closeModal();
				}

				$( document.body ).trigger( 'added_to_cart', [
					response && response.fragments,
					response && response.cart_hash,
					$button
				] );

				self.refreshCart();
				// Slide-in statt dem vom Theme eingeblendeten "Warenkorb
				// ansehen"-Link öffnen, damit sich nichts im Produktraster
				// verschiebt (Nutzer-Fund 2026-09-28).
				self.toggleCart( true );
			} ).fail( function () {
				self.busy = false;
				$button.prop( 'disabled', false ).text( original );
				self.showError( lbiteMenu.strings.error );
			} );
		},

		/**
		 * WooCommerce-Endpunkt fürs Hinzufügen
		 *
		 * Nutzt den offiziellen wc-ajax-Parameter. Der Pfad wird aus der
		 * Warenkorb-URL abgeleitet, damit er auch bei abweichenden
		 * Permalink-Strukturen stimmt.
		 */
		getAddToCartUrl: function () {
			if ( typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.wc_ajax_url ) {
				return wc_add_to_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'add_to_cart' );
			}

			const base = lbiteMenu.cartUrl || window.location.origin + '/';

			return base + ( base.indexOf( '?' ) === -1 ? '?' : '&' ) + 'wc-ajax=add_to_cart';
		},

		refreshCart: function () {
			const $toggle = $( '#lbite-menu-cart-toggle' );

			if ( $toggle.length === 0 ) {
				return;
			}

			$.post( lbiteMenu.ajaxUrl, {
				action: 'lbite_menu_cart',
				nonce: lbiteMenu.nonce
			} ).done( function ( response ) {
				if ( ! response || ! response.success ) {
					return;
				}

				const data = response.data;

				if ( ! data.count ) {
					$toggle.attr( 'hidden', true );
					$( '#lbite-menu-cart-body' ).html(
						$( '<p class="lbite-menu-cart__empty"></p>' ).text( lbiteMenu.strings.emptyCart )
					);
					return;
				}

				$toggle.removeAttr( 'hidden' );
				$toggle.find( '.lbite-menu-cart-toggle__count' ).text( data.count );
				$( '#lbite-menu-cart-body' ).html( data.html );
			} );
		},

		toggleCart: function ( open ) {
			$( '#lbite-menu-cart' ).attr( 'hidden', ! open ).toggleClass( 'is-open', !! open );
			$( '#lbite-menu-cart-scrim' ).attr( 'hidden', ! open );
			$( 'body' ).toggleClass( 'lbite-menu-cart-open', !! open );
		},

		closeModal: function () {
			if ( this.$modal ) {
				this.$modal.attr( 'hidden', true ).removeClass( 'is-open' );
			}
		},

		showError: function ( message ) {
			const $error = $( '<div class="lbite-menu-toast"></div>' ).text( message );
			$( 'body' ).append( $error );
			setTimeout( function () {
				$error.addClass( 'is-leaving' );
				setTimeout( function () {
					$error.remove();
				}, 300 );
			}, 3000 );
		}
	};

	$( document ).ready( function () {
		MenuView.init();
	} );
} )( jQuery );
