/**
 * Libre Bite – Bestelloptionen im WooCommerce-Checkout-Block
 *
 * Zeigt Standort, Bestellart und Abholzeit oberhalb der Kontaktangaben an.
 * Jede Auswahl geht über die Store API (extensionCartUpdate) sofort in die
 * WC-Session; WooCommerce rechnet den Warenkorb neu und liefert den Stand in
 * cart.extensions['libre-bite'] zurück. Der Server ist die einzige Quelle der
 * Wahrheit, dieses Script hält nur Zwischenstände (Datum vor Zeitwahl).
 *
 * Kein Build-Schritt: React über wp.element, ohne JSX.
 */
( function () {
	'use strict';

	if ( ! window.wp || ! window.wc || ! window.wc.blocksCheckout || ! window.lbiteCheckoutBlocks ) {
		return;
	}

	var el = wp.element;
	var h = el.createElement;
	var useState = el.useState;
	var useEffect = el.useEffect;
	var useSelect = wp.data.useSelect;
	var cfg = window.lbiteCheckoutBlocks;
	var s = cfg.strings;
	var NS = 'libre-bite';

	// Erweiterungspunkt für den Pro-Teil: weitere Abschnitte als Komponenten.
	var api = window.LBiteCheckoutBlocks = window.LBiteCheckoutBlocks || { sections: [] };
	api.h = h;

	/**
	 * Auswahl an den Server schicken.
	 *
	 * @param {Object} data Aktion und Werte.
	 * @return {Promise}
	 */
	function update( data ) {
		return wc.blocksCheckout.extensionCartUpdate( { namespace: NS, data: data } );
	}
	api.update = update;

	/**
	 * Fehlermeldung aus einer Store-API-Antwort.
	 *
	 * @param {*} err Fehler.
	 * @return {string}
	 */
	function errorMessage( err ) {
		return ( err && err.message ) ? String( err.message ).replace( /<[^>]*>/g, '' ) : s.error;
	}
	api.errorMessage = errorMessage;

	/**
	 * Validierungsfehler im Checkout setzen oder entfernen. Solange einer
	 * gesetzt ist, sendet WooCommerce die Bestellung nicht ab.
	 *
	 * @param {string}      key     Schlüssel.
	 * @param {string|null} message Meldung oder null zum Entfernen.
	 */
	function setValidation( key, message ) {
		var store = wp.data.dispatch( 'wc/store/validation' );
		if ( ! store ) {
			return;
		}
		if ( message ) {
			var errors = {};
			errors[ key ] = { message: message, hidden: true };
			store.setValidationErrors( errors );
		} else {
			store.clearValidationError( key );
		}
	}
	api.setValidation = setValidation;

	/**
	 * Validierungsmeldung anzeigen, sobald WooCommerce sie sichtbar schaltet.
	 *
	 * @param {Object} props id.
	 * @return {Object|null}
	 */
	function ValidationMessage( props ) {
		var error = useSelect( function ( select ) {
			var store = select( 'wc/store/validation' );
			return store ? store.getValidationError( props.id ) : null;
		}, [ props.id ] );

		if ( ! error || error.hidden ) {
			return null;
		}
		return h( 'p', { className: 'lbite-cb__error', role: 'alert' }, error.message );
	}
	api.ValidationMessage = ValidationMessage;

	/**
	 * Zeitfenster eines Tages laden (bestehender, öffentlicher Endpunkt).
	 *
	 * @param {number} locationId Standort.
	 * @param {string} date       Y-m-d.
	 * @return {Promise<Array>}
	 */
	function fetchSlots( locationId, date ) {
		var body = new window.FormData();
		body.append( 'action', 'lbite_get_timeslots' );
		body.append( 'location_id', locationId );
		body.append( 'date', date );

		return window.fetch( cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' } )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( json ) {
				return json && json.success && json.data && Array.isArray( json.data.timeslots ) ? json.data.timeslots : [];
			} )
			.catch( function () {
				return [];
			} );
	}

	/**
	 * Optionsgruppe mit Radio-Kacheln.
	 *
	 * @param {Object} props name, legend, value, options, onChange, busy.
	 * @return {Object}
	 */
	function RadioGroup( props ) {
		return h(
			'fieldset',
			{ className: 'lbite-cb__group' },
			h( 'legend', { className: 'lbite-cb__label' }, props.legend ),
			h(
				'div',
				{ className: 'lbite-cb__options' },
				props.options.map( function ( opt ) {
					return h(
						'label',
						{
							key: opt.value,
							className: 'lbite-cb__option' + ( props.value === opt.value ? ' is-selected' : '' ) + ( opt.disabled ? ' is-disabled' : '' ),
						},
						h( 'input', {
							type: 'radio',
							name: props.name,
							value: opt.value,
							checked: props.value === opt.value,
							disabled: !! opt.disabled || !! props.busy,
							onChange: function () {
								props.onChange( opt.value );
							},
						} ),
						h( 'span', null, opt.label )
					);
				} )
			)
		);
	}
	api.RadioGroup = RadioGroup;

	/**
	 * Standort, Bestellart und Abholzeit.
	 *
	 * @param {Object} props data (Erweiterungsdaten).
	 * @return {Object|null}
	 */
	function OrderOptions( props ) {
		var data = props.data;
		var isTable = !! data.table_name;
		var busyState = useState( false );
		var busy = busyState[ 0 ];
		var errState = useState( '' );
		var dateState = useState( data.pickup_time ? data.pickup_time.slice( 0, 10 ) : cfg.today );
		var slotsState = useState( null );
		var date = dateState[ 0 ];
		var slots = slotsState[ 0 ];

		function send( payload ) {
			busyState[ 1 ]( true );
			errState[ 1 ]( '' );
			return update( Object.assign( { action: 'set_location' }, payload ) )
				.catch( function ( err ) {
					errState[ 1 ]( errorMessage( err ) );
				} )
				.then( function () {
					busyState[ 1 ]( false );
				} );
		}

		// Zeitfenster laden, sobald «Später» mit Standort und Datum feststeht.
		useEffect( function () {
			if ( 'later' !== data.order_type || ! data.location_id || ! date ) {
				slotsState[ 1 ]( null );
				return undefined;
			}
			var cancelled = false;
			slotsState[ 1 ]( null );
			fetchSlots( data.location_id, date ).then( function ( list ) {
				if ( ! cancelled ) {
					slotsState[ 1 ]( list );
				}
			} );
			return function () {
				cancelled = true;
			};
		}, [ data.order_type, data.location_id, date ] );

		// Pflichtangaben als Validierungsfehler melden.
		useEffect( function () {
			setValidation( 'lbite-location', data.selector_enabled && ! data.location_id ? s.needLocation : null );
			setValidation( 'lbite-pickup-time', data.selector_enabled && 'later' === data.order_type && ! data.pickup_time ? s.needTime : null );
		}, [ data.selector_enabled, data.location_id, data.order_type, data.pickup_time ] );

		if ( ! data.selector_enabled ) {
			return null;
		}

		var locationField = null;
		if ( isTable ) {
			var loc = cfg.locations.filter( function ( l ) {
				return l.id === data.location_id;
			} )[ 0 ];
			locationField = h( 'p', { key: 'loc', className: 'lbite-cb__fixed' }, ( loc ? loc.name + ' · ' : '' ) + s.table + ' ' + data.table_name );
		} else if ( cfg.locations.length > 1 ) {
			locationField = h(
				'div',
				{ key: 'loc', className: 'lbite-cb__group' },
				h( 'label', { className: 'lbite-cb__label', htmlFor: 'lbite-cb-location' }, s.location ),
				h(
					'select',
					{
						id: 'lbite-cb-location',
						className: 'lbite-cb__select',
						value: data.location_id || '',
						disabled: busy,
						onChange: function ( e ) {
							send( { location_id: parseInt( e.target.value, 10 ) || 0, order_type: data.order_type, pickup_time: '' } );
						},
					},
					h( 'option', { value: '' }, s.selectLocation ),
					cfg.locations.map( function ( l ) {
						return h( 'option', { key: l.id, value: l.id }, l.label );
					} )
				),
				h( ValidationMessage, { id: 'lbite-location' } )
			);
		} else if ( 1 === cfg.locations.length ) {
			locationField = h( 'p', { key: 'loc', className: 'lbite-cb__fixed' }, cfg.locations[ 0 ].name );
		}

		var nowClosed = !! data.now_closed_text;
		var children = [ locationField ];

		if ( data.location_id ) {
			children.push( h( RadioGroup, {
				key: 'type',
				name: 'lbite-cb-order-type',
				legend: s.orderType,
				value: data.order_type,
				busy: busy,
				options: [
					{ value: 'now', label: isTable ? s.tableService : s.orderNow, disabled: nowClosed && 'now' !== data.order_type },
					{ value: 'later', label: s.later },
				],
				onChange: function ( value ) {
					send( { location_id: data.location_id, order_type: value, pickup_time: '' } );
				},
			} ) );
			if ( nowClosed ) {
				children.push( h( 'p', { key: 'closed', className: 'lbite-cb__notice' }, s.notNow + ' ' + data.now_closed_text ) );
			}
		}

		if ( data.location_id && 'later' === data.order_type ) {
			children.push(
				h(
					'div',
					{ key: 'when', className: 'lbite-cb__row' },
					h(
						'div',
						{ className: 'lbite-cb__group' },
						h( 'label', { className: 'lbite-cb__label', htmlFor: 'lbite-cb-date' }, s.pickupDate ),
						h( 'input', {
							id: 'lbite-cb-date',
							type: 'date',
							className: 'lbite-cb__select',
							min: cfg.today,
							value: date,
							disabled: busy,
							onChange: function ( e ) {
								dateState[ 1 ]( e.target.value );
							},
						} )
					),
					h(
						'div',
						{ className: 'lbite-cb__group' },
						h( 'label', { className: 'lbite-cb__label', htmlFor: 'lbite-cb-time' }, s.pickupTime ),
						h(
							'select',
							{
								id: 'lbite-cb-time',
								className: 'lbite-cb__select',
								value: data.pickup_time && data.pickup_time.slice( 0, 10 ) === date ? data.pickup_time : '',
								disabled: busy || ! slots || ! slots.length,
								onChange: function ( e ) {
									send( { location_id: data.location_id, order_type: 'later', pickup_time: e.target.value } );
								},
							},
							h( 'option', { value: '' }, null === slots ? '…' : s.selectTime ),
							( slots || [] ).map( function ( slot ) {
								return h( 'option', { key: slot.value, value: slot.value }, slot.label );
							} )
						),
						h( ValidationMessage, { id: 'lbite-pickup-time' } )
					)
				)
			);
			if ( slots && ! slots.length ) {
				children.push( h( 'p', { key: 'noslots', className: 'lbite-cb__notice' }, s.noSlots ) );
			}
		}

		if ( errState[ 0 ] ) {
			children.push( h( 'p', { key: 'err', className: 'lbite-cb__error', role: 'alert' }, errState[ 0 ] ) );
		}

		return h( 'div', { className: 'lbite-cb__section' }, children );
	}

	/**
	 * Ziel im Hauptbereich des Checkouts (vor den Kontaktangaben).
	 *
	 * @return {Element|null}
	 */
	function findTarget() {
		var existing = document.getElementById( 'lbite-checkout-blocks-root' );
		if ( existing ) {
			return existing;
		}
		var fields = document.querySelector( '.wc-block-checkout__main .wc-block-components-form, .wp-block-woocommerce-checkout-fields-block' );
		if ( ! fields ) {
			return null;
		}
		var root = document.createElement( 'div' );
		root.id = 'lbite-checkout-blocks-root';
		root.className = 'lbite-cb';
		fields.insertBefore( root, fields.firstChild );
		return root;
	}

	/**
	 * Gesamter Libre-Bite-Bereich.
	 *
	 * @return {Object|null}
	 */
	function Panel() {
		var data = useSelect( function ( select ) {
			var cart = select( 'wc/store/cart' ).getCartData();
			return cart && cart.extensions ? cart.extensions[ NS ] : null;
		}, [] );
		var targetState = useState( null );

		useEffect( function () {
			var target = findTarget();
			if ( target ) {
				targetState[ 1 ]( target );
				return undefined;
			}
			// Der Checkout-Block baut sich asynchron auf: auf das Formular warten.
			var observer = new window.MutationObserver( function () {
				var found = findTarget();
				if ( found ) {
					observer.disconnect();
					targetState[ 1 ]( found );
				}
			} );
			observer.observe( document.body, { childList: true, subtree: true } );
			return function () {
				observer.disconnect();
			};
		}, [] );

		if ( ! data ) {
			return null;
		}

		var content = h(
			'div',
			{ className: 'lbite-cb__inner' },
			h( 'h2', { className: 'lbite-cb__title wc-block-components-title' }, s.title ),
			h( OrderOptions, { data: data } ),
			api.sections.map( function ( Section, i ) {
				return h( Section, { key: i, data: data } );
			} )
		);

		// Ohne gefundenes Ziel im Bestellüberblick anzeigen statt gar nicht.
		return targetState[ 0 ] ? el.createPortal( content, targetState[ 0 ] ) : content;
	}

	wp.domReady( function () {
		wp.plugins.registerPlugin( 'libre-bite-checkout', {
			scope: 'woocommerce-checkout',
			render: function () {
				return h( wc.blocksCheckout.ExperimentalOrderMeta, null, h( Panel ) );
			},
		} );
	} );
}() );
