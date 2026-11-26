/**
 * Libre Bite – Pro-Abschnitte im WooCommerce-Checkout-Block
 *
 * Bestelltyp mit Tischnummer, Lieferung, Trinkgeld und Impulsangebote.
 * Hängt sich an window.LBiteCheckoutBlocks (checkout-blocks.js) und schickt
 * jede Auswahl über dieselbe Store-API-Schnittstelle an den Server.
 */
( function () {
	'use strict';

	var api = window.LBiteCheckoutBlocks;
	var cfg = window.lbiteCheckoutBlocksPro;
	if ( ! api || ! cfg || ! window.wp ) {
		return;
	}

	var el = wp.element;
	var h = el.createElement;
	var useState = el.useState;
	var useEffect = el.useEffect;
	var s = cfg.strings;

	/**
	 * Aufruf mit Fehleranzeige und Sperre während der Anfrage.
	 *
	 * @return {Array} [busy, error, run]
	 */
	function useSender() {
		var busy = useState( false );
		var err = useState( '' );
		function run( data ) {
			busy[ 1 ]( true );
			err[ 1 ]( '' );
			return api.update( data )
				.catch( function ( e ) {
					err[ 1 ]( api.errorMessage( e ) );
				} )
				.then( function () {
					busy[ 1 ]( false );
				} );
		}
		return [ busy[ 0 ], err[ 0 ], run ];
	}

	function errorLine( message ) {
		return message ? h( 'p', { className: 'lbite-cb__error', role: 'alert' }, message ) : null;
	}

	/**
	 * Mitnehmen / Hier essen, optional mit Tisch.
	 *
	 * @param {Object} props data.
	 * @return {Object|null}
	 */
	function ServiceSection( props ) {
		var service = props.data.pro && props.data.pro.service;
		var delivery = props.data.pro && props.data.pro.delivery;
		var sender = useSender();
		var tableState = useState( service ? service.table_number : '' );
		var isDelivery = delivery && 'delivery' === delivery.selected;

		useEffect( function () {
			api.setValidation( 'lbite-service', service && ! isDelivery && ! service.value ? s.needService : null );
			return function () {
				api.setValidation( 'lbite-service', null );
			};
		}, [ service && service.value, isDelivery, !! service ] );

		useEffect( function () {
			if ( service ) {
				tableState[ 1 ]( service.table_number );
			}
		}, [ service && service.table_number ] );

		// Lieferung gilt als Mitnahme, die Frage entfällt.
		if ( ! service || isDelivery ) {
			return null;
		}

		function save( value, table ) {
			sender[ 2 ]( { action: 'set_service', service_type: value, table_number: table } );
		}

		var children = [
			h( api.RadioGroup, {
				key: 'service',
				name: 'lbite-cb-service',
				legend: s.howOrder,
				value: service.value,
				busy: sender[ 0 ],
				options: [
					{ value: 'takeaway', label: s.takeaway },
					{ value: 'dine_in', label: s.dineIn },
				],
				onChange: function ( value ) {
					save( value, 'dine_in' === value ? tableState[ 0 ] : '' );
				},
			} ),
			h( api.ValidationMessage, { key: 'v', id: 'lbite-service' } ),
		];

		if ( 'dine_in' === service.value && service.tables_enabled ) {
			var field = service.tables.length
				? h(
					'select',
					{
						id: 'lbite-cb-table',
						className: 'lbite-cb__select',
						value: tableState[ 0 ],
						disabled: sender[ 0 ],
						onChange: function ( e ) {
							tableState[ 1 ]( e.target.value );
							save( 'dine_in', e.target.value );
						},
					},
					h( 'option', { value: '' }, s.selectTable ),
					service.tables.map( function ( name ) {
						return h( 'option', { key: name, value: name }, name );
					} )
				)
				: h( 'input', {
					id: 'lbite-cb-table',
					type: 'text',
					className: 'lbite-cb__select',
					placeholder: s.tablePlaceholder,
					value: tableState[ 0 ],
					onChange: function ( e ) {
						tableState[ 1 ]( e.target.value );
					},
					onBlur: function () {
						if ( tableState[ 0 ] !== service.table_number ) {
							save( 'dine_in', tableState[ 0 ] );
						}
					},
				} );
			children.push(
				h( 'div', { key: 'table', className: 'lbite-cb__group' },
					h( 'label', { className: 'lbite-cb__label', htmlFor: 'lbite-cb-table' }, s.tableOptional ),
					field
				)
			);
		}

		children.push( h( 'div', { key: 'e' }, errorLine( sender[ 1 ] ) ) );

		return h( 'div', { className: 'lbite-cb__section' }, children );
	}

	var ADDRESS_KEYS = [ 'address', 'postcode', 'city', 'phone', 'notes' ];

	/**
	 * Abholung oder Lieferung mit Adresse.
	 *
	 * @param {Object} props data.
	 * @return {Object|null}
	 */
	function DeliverySection( props ) {
		var delivery = props.data.pro && props.data.pro.delivery;
		var sender = useSender();
		var addrState = useState( delivery ? Object.assign( {}, delivery.address ) : {} );
		var addr = addrState[ 0 ];
		var selected = delivery ? delivery.selected : 'pickup';
		var complete = [ 'address', 'postcode', 'city', 'phone' ].every( function ( k ) {
			return addr[ k ] && String( addr[ k ] ).trim();
		} );

		useEffect( function () {
			api.setValidation( 'lbite-delivery', delivery && 'delivery' === selected && ! complete ? s.needAddress : null );
			return function () {
				api.setValidation( 'lbite-delivery', null );
			};
		}, [ !! delivery, selected, complete ] );

		if ( ! delivery ) {
			return null;
		}

		function save( fulfilment, address ) {
			sender[ 2 ]( { action: 'set_delivery', fulfilment: fulfilment, address: address } );
		}

		function changed() {
			return ADDRESS_KEYS.some( function ( k ) {
				return ( addr[ k ] || '' ) !== ( delivery.address[ k ] || '' );
			} );
		}

		var children = [
			h( api.RadioGroup, {
				key: 'f',
				name: 'lbite-cb-fulfilment',
				legend: s.pickupOrDelivery,
				value: selected,
				busy: sender[ 0 ],
				options: [
					{ value: 'pickup', label: s.pickup },
					{ value: 'delivery', label: s.delivery },
				],
				onChange: function ( value ) {
					save( value, addr );
				},
			} ),
		];

		if ( 'delivery' === selected ) {
			if ( delivery.info ) {
				children.push( h( 'p', { key: 'info', className: 'lbite-cb__notice' }, delivery.info ) );
			}
			var fields = [
				{ key: 'address', label: s.street, auto: 'street-address', wide: true },
				{ key: 'postcode', label: s.postcode, auto: 'postal-code', mode: 'numeric' },
				{ key: 'city', label: s.city, auto: 'address-level2' },
				{ key: 'phone', label: s.phone, auto: 'tel', type: 'tel', wide: true },
				{ key: 'notes', label: s.notes, wide: true, optional: true },
			];
			children.push(
				h( 'div', { key: 'addr', className: 'lbite-cb__grid' },
					fields.map( function ( f ) {
						return h( 'div', { key: f.key, className: 'lbite-cb__group' + ( f.wide ? ' is-wide' : '' ) },
							h( 'label', { className: 'lbite-cb__label', htmlFor: 'lbite-cb-' + f.key }, f.label + ( f.optional ? '' : ' *' ) ),
							h( 'input', {
								id: 'lbite-cb-' + f.key,
								type: f.type || 'text',
								className: 'lbite-cb__select',
								autoComplete: f.auto,
								inputMode: f.mode,
								value: addr[ f.key ] || '',
								onChange: function ( e ) {
									var next = Object.assign( {}, addr );
									next[ f.key ] = e.target.value;
									addrState[ 1 ]( next );
								},
								onBlur: function () {
									if ( changed() ) {
										save( 'delivery', addr );
									}
								},
							} )
						);
					} )
				)
			);
			children.push( h( api.ValidationMessage, { key: 'v', id: 'lbite-delivery' } ) );
		}

		children.push( h( 'div', { key: 'e' }, errorLine( sender[ 1 ] ) ) );

		return h( 'div', { className: 'lbite-cb__section' }, children );
	}

	/**
	 * Trinkgeld.
	 *
	 * @param {Object} props data.
	 * @return {Object|null}
	 */
	function TipSection( props ) {
		var tip = props.data.pro && props.data.pro.tip;
		var sender = useSender();
		var customState = useState( tip && 'custom' === tip.type ? String( tip.value ) : '' );

		if ( ! tip ) {
			return null;
		}

		var current = 'none';
		if ( 'custom' === tip.type ) {
			current = 'custom';
		} else if ( 'percentage' === tip.type ) {
			tip.options.forEach( function ( o, i ) {
				if ( Number( o.value ) === Number( tip.value ) ) {
					current = 'p' + i;
				}
			} );
		}

		var options = [ { value: 'none', label: tip.label_none || s.noTip } ];
		tip.options.forEach( function ( o, i ) {
			options.push( { value: 'p' + i, label: o.label } );
		} );
		options.push( { value: 'custom', label: s.custom.replace( /:$/, '' ) } );

		function choose( value ) {
			if ( 'none' === value ) {
				sender[ 2 ]( { action: 'set_tip', type: 'none' } );
			} else if ( 'custom' === value ) {
				sender[ 2 ]( { action: 'set_tip', type: 'custom', value: parseFloat( customState[ 0 ] ) || 0 } );
			} else {
				sender[ 2 ]( { action: 'set_tip', type: 'percentage', value: tip.options[ parseInt( value.slice( 1 ), 10 ) ].value } );
			}
		}

		var children = [
			h( api.RadioGroup, {
				key: 'tip',
				name: 'lbite-cb-tip',
				legend: tip.title || s.tipTitle,
				value: current,
				busy: sender[ 0 ],
				options: options,
				onChange: choose,
			} ),
		];

		if ( 'custom' === current ) {
			children.push(
				h( 'div', { key: 'c', className: 'lbite-cb__group lbite-cb__inline' },
					h( 'label', { className: 'screen-reader-text', htmlFor: 'lbite-cb-tip-custom' }, s.custom ),
					h( 'input', {
						id: 'lbite-cb-tip-custom',
						type: 'number',
						className: 'lbite-cb__select',
						min: 0,
						max: tip.fixed ? undefined : 100,
						step: tip.fixed ? '0.05' : '0.1',
						value: customState[ 0 ],
						onChange: function ( e ) {
							customState[ 1 ]( e.target.value );
						},
						onBlur: function () {
							sender[ 2 ]( { action: 'set_tip', type: 'custom', value: parseFloat( customState[ 0 ] ) || 0 } );
						},
					} ),
					h( 'span', { className: 'lbite-cb__unit' }, tip.fixed ? cfg.currency : '%' )
				)
			);
		}

		children.push( h( 'div', { key: 'e' }, errorLine( sender[ 1 ] ) ) );

		return h( 'div', { className: 'lbite-cb__section' }, children );
	}

	/**
	 * Impulsangebote.
	 *
	 * @param {Object} props data.
	 * @return {Object|null}
	 */
	function BumpsSection( props ) {
		var bumps = props.data.pro && props.data.pro.bumps;
		var sender = useSender();

		if ( ! bumps || ! bumps.length ) {
			return null;
		}

		return h( 'div', { className: 'lbite-cb__section lbite-cb__bumps' },
			bumps.map( function ( b ) {
				return h( 'div', { key: b.product_id, className: 'lbite-cb__bump' },
					b.image ? h( 'img', { className: 'lbite-cb__bump-img', src: b.image, alt: '' } ) : null,
					h( 'span', { className: 'lbite-cb__bump-body' },
						h( 'span', { className: 'lbite-cb__bump-text' }, b.text ),
						h( 'span', { className: 'lbite-cb__bump-price' }, b.price )
					),
					h( 'button', {
						type: 'button',
						className: 'lbite-cb__bump-add',
						disabled: sender[ 0 ],
						onClick: function () {
							sender[ 2 ]( { action: 'add_bump', product_id: b.product_id } );
						},
					}, s.add )
				);
			} ),
			errorLine( sender[ 1 ] )
		);
	}

	api.sections.push( DeliverySection, ServiceSection, TipSection, BumpsSection );
}() );
