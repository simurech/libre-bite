<?php
/**
 * Pro-Teil der Block-Checkout-Anbindung
 *
 * Bestelltyp (Mitnehmen/Hier essen, Tischnummer), Lieferung, Trinkgeld und
 * Impulsangebote im WooCommerce-Checkout-Block. Hängt sich an die Filter von
 * LBite_Checkout_Blocks und nutzt die Logik der jeweiligen Module, statt sie
 * zu wiederholen.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block-Checkout: Pro-Funktionen
 */
class LBite_Checkout_Blocks_Pro {

	/**
	 * Checkout-Modul.
	 *
	 * @var LBite_Checkout
	 */
	private $checkout;

	/**
	 * Konstruktor
	 *
	 * @param LBite_Loader   $loader   Loader.
	 * @param LBite_Checkout $checkout Checkout-Modul.
	 */
	public function __construct( $loader, $checkout ) {
		$this->checkout = $checkout;

		$loader->add_filter( 'lbite_blocks_cart_data', $this, 'add_cart_data' );
		$loader->add_filter( 'lbite_blocks_handle_update', $this, 'handle_update', 10, 3 );
		$loader->add_filter( 'lbite_blocks_checkout_errors', $this, 'add_errors', 10, 2 );
		$loader->add_action( 'lbite_blocks_checkout_order', $this, 'write_order', 10, 2 );
		$loader->add_action( 'lbite_blocks_enqueue_assets', $this, 'enqueue_assets' );
	}

	/**
	 * Ist das Trinkgeld im Checkout eingeschaltet?
	 *
	 * @return bool
	 */
	private static function tips_enabled() {
		if ( ! lbite_feature_enabled( 'enable_tips' ) ) {
			return false;
		}

		$custom_fields = get_option( 'lbite_checkout_fields', array() );

		return ! isset( $custom_fields['_enable_tip_selection'] ) || $custom_fields['_enable_tip_selection'];
	}

	/**
	 * Tische des Standorts für die Auswahl.
	 *
	 * @param int $location_id Standort-ID.
	 * @return string[] Tischnamen.
	 */
	private static function get_table_names( $location_id ) {
		if ( ! $location_id || ! lbite_feature_enabled( 'enable_table_ordering' ) ) {
			return array();
		}

		$sort   = get_option( 'lbite_table_dropdown_sort', 'natural' );
		$tables = get_posts(
			array(
				'post_type'      => 'lbite_table',
				'posts_per_page' => 50,
				'orderby'        => 'menu_order' === $sort ? 'menu_order' : 'title',
				'order'          => 'ASC',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Tischabfrage auf max. 50 Einträge begrenzt.
				'meta_query'     => array(
					array(
						'key'   => '_lbite_location_id',
						'value' => $location_id,
					),
				),
			)
		);

		$names = wp_list_pluck( $tables, 'post_title' );
		if ( 'natural' === $sort ) {
			usort( $names, 'strnatcasecmp' );
		}

		return array_values( $names );
	}

	/**
	 * Pro-Daten an die Erweiterungsdaten des Warenkorbs hängen.
	 *
	 * @param array $data Daten.
	 * @return array
	 */
	public function add_cart_data( $data ) {
		$session = WC()->session;
		$pro     = array();

		if ( lbite_feature_enabled( 'enable_order_type_selection' ) && $data['location_id'] ) {
			$service = $session ? (string) $session->get( 'lbite_service_type', '' ) : '';
			$table   = $session ? (string) $session->get( 'lbite_checkout_table_number', '' ) : '';

			// QR-Code-Scan: «Hier essen» und den gescannten Tisch vorauswählen.
			if ( ! $service && $data['table_name'] ) {
				$service = 'dine_in';
				if ( $session ) {
					$session->set( 'lbite_service_type', 'dine_in' );
				}
			}
			if ( ! $table && $data['table_name'] ) {
				$table = $data['table_name'];
			}

			$pro['service'] = array(
				'value'          => $service,
				'tables_enabled' => lbite_feature_enabled( 'enable_table_ordering' ),
				'tables'         => self::get_table_names( $data['location_id'] ),
				'table_number'   => $table,
			);
		}

		if ( class_exists( 'LBite_Delivery' ) ) {
			$delivery = LBite_Delivery::get_block_state();
			if ( $delivery ) {
				$pro['delivery'] = $delivery;
			}
		}

		if ( self::tips_enabled() ) {
			$is_fixed = 'fixed' === get_option( 'lbite_tip_mode', 'percentage' );
			$defaults = array(
				1 => 5,
				2 => 10,
				3 => 15,
			);
			$options  = array();
			foreach ( $defaults as $i => $default ) {
				$value = (float) get_option( 'lbite_tip_percentage_' . $i, $default );
				$label = (string) get_option( 'lbite_tip_label_' . $i, '' );
				if ( '' === $label ) {
					$label = $is_fixed
						? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) . ' ' . number_format_i18n( $value, 2 )
						: $value . '%';
				}
				$options[] = array(
					'value' => $value,
					'label' => wp_strip_all_tags( $label ),
				);
			}

			$selected   = LBite_Checkout::get_session_tip();
			$pro['tip'] = array(
				'fixed'      => $is_fixed,
				'title'      => (string) get_option( 'lbite_tip_title', '' ),
				'label_none' => (string) get_option( 'lbite_tip_label_none', '' ),
				'options'    => $options,
				'type'       => $selected ? $selected['type'] : 'none',
				'value'      => $selected ? $selected['value'] : 0,
			);
		}

		if ( lbite_feature_enabled( 'enable_order_bumps' ) && class_exists( 'LBite_Order_Bumps' ) ) {
			$bumps = array();
			foreach ( LBite_Order_Bumps::get_available_bumps() as $offer ) {
				$product = $offer['product'];
				$bumps[] = array(
					'product_id' => $product->get_id(),
					/* translators: %s: product name */
					'text'       => '' !== $offer['text'] ? $offer['text'] : sprintf( __( 'Add %s to your order?', 'libre-bite' ), $product->get_name() ),
					'price'      => wp_strip_all_tags( html_entity_decode( wc_price( wc_get_price_to_display( $product ) ), ENT_QUOTES, 'UTF-8' ) ),
					'image'      => $product->get_image_id() ? (string) wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : '',
				);
			}
			if ( $bumps ) {
				$pro['bumps'] = $bumps;
			}
		}

		$data['pro'] = $pro ? $pro : new stdClass();

		return $data;
	}

	/**
	 * Pro-Aktionen aus dem Block-Checkout.
	 *
	 * @param bool   $handled Bereits behandelt.
	 * @param string $action  Aktion.
	 * @param array  $data    Daten.
	 * @return bool
	 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException Wenn ein Angebot nicht hinzugefügt werden kann.
	 */
	public function handle_update( $handled, $action, $data ) {
		if ( $handled || ! WC()->session ) {
			return $handled;
		}

		switch ( $action ) {
			case 'set_service':
				if ( ! lbite_feature_enabled( 'enable_order_type_selection' ) ) {
					return false;
				}
				$service = isset( $data['service_type'] ) ? sanitize_key( $data['service_type'] ) : '';
				if ( in_array( $service, array( 'takeaway', 'dine_in' ), true ) ) {
					WC()->session->set( 'lbite_service_type', $service );
				}
				WC()->session->set( 'lbite_checkout_table_number', isset( $data['table_number'] ) ? sanitize_text_field( (string) $data['table_number'] ) : '' );
				return true;

			case 'set_delivery':
				if ( ! class_exists( 'LBite_Delivery' ) ) {
					return false;
				}
				$address = isset( $data['address'] ) && is_array( $data['address'] ) ? $data['address'] : array();
				LBite_Delivery::set_session( isset( $data['fulfilment'] ) ? (string) $data['fulfilment'] : 'pickup', $address );
				return true;

			case 'set_tip':
				if ( ! self::tips_enabled() ) {
					return false;
				}
				$type  = isset( $data['type'] ) ? sanitize_key( $data['type'] ) : 'none';
				$value = isset( $data['value'] ) ? max( 0, (float) $data['value'] ) : 0;
				// Prozentmodus: höchstens 100 %, wie das klassische Eingabefeld.
				if ( 'fixed' !== get_option( 'lbite_tip_mode', 'percentage' ) ) {
					$value = min( $value, 100 );
				}
				WC()->session->set(
					'lbite_tip',
					in_array( $type, array( 'percentage', 'custom' ), true )
						? array(
							'type'  => $type,
							'value' => $value,
						)
						: array( 'type' => 'none' )
				);
				return true;

			case 'add_bump':
				if ( ! lbite_feature_enabled( 'enable_order_bumps' ) || ! class_exists( 'LBite_Order_Bumps' ) ) {
					return false;
				}
				$error = LBite_Order_Bumps::add_to_cart( isset( $data['product_id'] ) ? absint( $data['product_id'] ) : 0 );
				if ( '' !== $error ) {
					LBite_Checkout_Blocks::fail( $error );
				}
				return true;
		}

		return false;
	}

	/**
	 * Lieferangaben beim Absenden prüfen.
	 *
	 * @param string[] $errors Fehlermeldungen.
	 * @param array    $input  Eingaben.
	 * @return string[]
	 */
	public function add_errors( $errors, $input ) {
		if ( class_exists( 'LBite_Delivery' ) ) {
			$error = LBite_Delivery::get_session_validation_error( $input['order_type'], $input['pickup_time'] );
			if ( '' !== $error ) {
				$errors[] = $error;
			}
		}

		return $errors;
	}

	/**
	 * Pro-Angaben in die Bestellung schreiben.
	 *
	 * @param WC_Order $order Bestellung.
	 * @param array    $input Eingaben.
	 */
	public function write_order( $order, $input ) {
		unset( $input );

		if ( lbite_feature_enabled( 'enable_order_type_selection' ) ) {
			$this->checkout->save_order_type_to_order__premium_only( $order, array() );
		}

		// Tisch aus dem QR-Scan: dieselbe Methode wie im klassischen Checkout.
		$tables = LBite_Plugin::instance()->get_module( 'tables' );
		if ( $tables && method_exists( $tables, 'add_table_meta_to_order' ) ) {
			$tables->add_table_meta_to_order( $order, array() );
		}

		if ( class_exists( 'LBite_Delivery' ) ) {
			LBite_Delivery::apply_session_to_order( $order );
		}

		if ( self::tips_enabled() ) {
			$tip = LBite_Checkout::get_session_tip();
			if ( $tip ) {
				LBite_Checkout::write_tip_meta( $order, $tip['type'], $tip['value'] );
			}
		}
	}

	/**
	 * Pro-Script des Block-Checkouts laden.
	 */
	public function enqueue_assets() {
		wp_enqueue_script(
			'lbite-checkout-blocks-pro',
			LBITE_PLUGIN_URL . 'assets/js/checkout-blocks-pro__premium_only.js',
			array( 'lbite-checkout-blocks' ),
			LBITE_VERSION,
			true
		);

		wp_localize_script(
			'lbite-checkout-blocks-pro',
			'lbiteCheckoutBlocksPro',
			array(
				'currency' => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
				'strings'  => array(
					'howOrder'         => __( 'How would you like your order?', 'libre-bite' ),
					'takeaway'         => __( 'To take away', 'libre-bite' ),
					'dineIn'           => __( 'Eat here', 'libre-bite' ),
					'tableOptional'    => __( 'Table (optional):', 'libre-bite' ),
					'selectTable'      => __( 'Select table (optional)', 'libre-bite' ),
					'tablePlaceholder' => __( 'e.g. 5', 'libre-bite' ),
					'pickupOrDelivery' => __( 'Pickup or delivery?', 'libre-bite' ),
					'pickup'           => __( 'Pickup', 'libre-bite' ),
					'delivery'         => __( 'Delivery', 'libre-bite' ),
					'street'           => __( 'Street and number', 'libre-bite' ),
					'postcode'         => __( 'Postcode', 'libre-bite' ),
					'city'             => __( 'City', 'libre-bite' ),
					'phone'            => __( 'Phone for the driver', 'libre-bite' ),
					'notes'            => __( 'Delivery notes (floor, doorbell …)', 'libre-bite' ),
					'tipTitle'         => __( 'Add a tip?', 'libre-bite' ),
					'noTip'            => __( 'No tip', 'libre-bite' ),
					'custom'           => __( 'Custom:', 'libre-bite' ),
					'needService'      => __( 'Please select whether you want to take away or eat here.', 'libre-bite' ),
					'needAddress'      => __( 'Please enter the complete delivery address and a phone number.', 'libre-bite' ),
					'add'              => __( 'Add', 'libre-bite' ),
				),
			)
		);
	}
}
