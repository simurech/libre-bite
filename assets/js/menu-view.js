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

		init: function () {
			this.$root = $( '[data-lbite-menu]' );
			if ( this.$root.length === 0 ) {
				return;
			}

			this.$modal = $( '#lbite-menu-modal' );
			this.bindEvents();
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

			// Abschnitts-Navigation
			this.$root.on( 'click', '.lbite-menu-nav__item', function ( e ) {
				e.preventDefault();
				const target = $( $( this ).attr( 'href' ) );
				if ( target.length ) {
					$( 'html, body' ).animate( { scrollTop: target.offset().top - 20 }, 300 );
				}
				$( '.lbite-menu-nav__item' ).removeClass( 'is-active' );
				$( this ).addClass( 'is-active' );
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

			// WooCommerce meldet Warenkorb-Änderungen
			$( document.body ).on( 'added_to_cart wc_fragments_refreshed wc_fragments_loaded', function () {
				self.refreshCart();
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
			const $body = $( '#lbite-menu-modal-body' ).empty();

			if ( data.image ) {
				$body.append( $( '<img class="lbite-menu-modal__img">' ).attr( { src: data.image, alt: '' } ) );
			}

			$body.append( $( '<h2 class="lbite-menu-modal__title" id="lbite-menu-modal-title"></h2>' ).text( data.name ) );

			if ( data.description ) {
				$body.append( $( '<div class="lbite-menu-modal__desc"></div>' ).html( data.description ) );
			}

			const $form = $( '<div class="lbite-menu-modal__form"></div>' ).attr( 'data-product-id', data.id );

			// Varianten: je Attribut eine Auswahl. Feldnamen wie auf der
			// normalen Produktseite, damit WooCommerce sie versteht.
			if ( data.type === 'variable' && data.attributes ) {
				Object.keys( data.attributes ).forEach( function ( attrName ) {
					const values = data.attributes[ attrName ];
					const fieldName = 'attribute_' + attrName.toLowerCase().replace( /\s+/g, '-' );

					const $group = $( '<div class="lbite-menu-modal__group"></div>' );
					$group.append( $( '<span class="lbite-menu-modal__label"></span>' ).text( attrName ) );

					const $select = $( '<select class="lbite-menu-modal__select"></select>' )
						.attr( 'data-attribute', fieldName );
					$select.append( $( '<option value=""></option>' ).text( lbiteMenu.strings.chooseOption ) );

					values.forEach( function ( value ) {
						$select.append( $( '<option></option>' ).attr( 'value', value ).text( value ) );
					} );

					$group.append( $select );
					$form.append( $group );
				} );
			}

			// Zusatzoptionen
			if ( data.options && data.options.length ) {
				const $group = $( '<div class="lbite-menu-modal__group"></div>' );
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

			// Menge
			const $qty = $( '<div class="lbite-menu-modal__group lbite-menu-modal__qty"></div>' );
			$qty.append( $( '<input type="number" min="1" step="1" value="1" class="lbite-menu-modal__qty-input">' ) );
			$form.append( $qty );

			$body.append( $form );

			$body.append(
				$( '<button type="button" class="lbite-menu-modal__add"></button>' ).text( lbiteMenu.strings.add )
			);
		},

		submitModal: function ( $button ) {
			const $form = $( '#lbite-menu-modal-body .lbite-menu-modal__form' );
			const productId = $form.data( 'product-id' );
			const extra = {};
			let missing = false;

			$form.find( '.lbite-menu-modal__select' ).each( function () {
				const $select = $( this );
				if ( ! $select.val() ) {
					missing = true;
					$select.addClass( 'is-invalid' );
				} else {
					$select.removeClass( 'is-invalid' );
					extra[ $select.data( 'attribute' ) ] = $select.val();
				}
			} );

			if ( missing ) {
				return;
			}

			const options = [];
			$form.find( '.lbite-menu-modal__opt:checked' ).each( function () {
				options.push( $( this ).val() );
			} );
			if ( options.length ) {
				extra[ 'lbite_options' ] = options;
			}

			const qty = parseInt( $form.find( '.lbite-menu-modal__qty-input' ).val(), 10 ) || 1;

			this.addToCart( productId, qty, extra, $button, true );
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
