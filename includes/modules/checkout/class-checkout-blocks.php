<?php
/**
 * Anbindung an den WooCommerce-Checkout-Block
 *
 * Der Block-Checkout spricht ausschliesslich über die Store API mit dem Server.
 * Die klassischen Hooks (woocommerce_checkout_process, _update_order_meta …)
 * feuern dort nicht. Diese Klasse überträgt die Libre-Bite-Logik auf die
 * Store API, ohne sie zu duplizieren:
 *
 * - Jede Auswahl im Block (Standort, Bestellart, Abholzeit) landet über einen
 *   Store-API-Update-Callback sofort in derselben WC-Session, die auch der
 *   klassische Checkout nutzt. WooCommerce rechnet danach den Warenkorb neu
 *   (Rundung, Trinkgeld, Liefergebühr, Schweizer MWST).
 * - Der aktuelle Stand geht als Erweiterungsdaten (`extensions.libre-bite`)
 *   mit jeder Warenkorb-Antwort an das Script zurück.
 * - Beim Absenden prüft LBite_Checkout::get_location_errors() die Session –
 *   dieselbe Prüfung wie im klassischen Checkout.
 *
 * Pro-Funktionen (Trinkgeld, Bestelltyp, Lieferung, Impulsangebote) hängen
 * sich über die Filter `lbite_blocks_*` an und wohnen in
 * class-checkout-blocks-pro__premium_only.php.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block-Checkout-Anbindung
 */
class LBite_Checkout_Blocks {

	/**
	 * Namensraum in der Store API.
	 */
	const NAMESPACE_ID = 'libre-bite';

	/**
	 * Loader-Instanz.
	 *
	 * @var LBite_Loader
	 */
	private $loader;

	/**
	 * Klassisches Checkout-Modul (gemeinsame Prüf- und Speicherlogik).
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
		$this->loader   = $loader;
		$this->checkout = $checkout;

		// WooCommerce feuert woocommerce_blocks_loaded je nach Ladereihenfolge
		// schon vor diesem Modul.
		if ( did_action( 'woocommerce_blocks_loaded' ) ) {
			$this->register_store_api();
		} else {
			$this->loader->add_action( 'woocommerce_blocks_loaded', $this, 'register_store_api' );
		}
		$this->loader->add_action( 'woocommerce_store_api_checkout_update_order_from_request', $this, 'process_checkout', 10, 2 );
		$this->loader->add_action( 'woocommerce_store_api_checkout_order_processed', $this, 'after_order_processed' );
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_assets', 20 );
	}

	/**
	 * Läuft der Checkout als Block?
	 *
	 * @return bool
	 */
	public static function is_block_checkout() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return false;
		}

		if ( function_exists( 'has_block' ) && has_block( 'woocommerce/checkout' ) ) {
			return true;
		}

		return function_exists( 'lbite_checkout_uses_blocks' ) && lbite_checkout_uses_blocks();
	}

	/**
	 * Erweiterungsdaten und Update-Callback in der Store API anmelden.
	 */
	public function register_store_api() {
		if ( function_exists( 'woocommerce_store_api_register_endpoint_data' ) && class_exists( '\Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema' ) ) {
			woocommerce_store_api_register_endpoint_data(
				array(
					'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
					'namespace'       => self::NAMESPACE_ID,
					'data_callback'   => array( $this, 'get_cart_data' ),
					'schema_callback' => array( $this, 'get_cart_schema' ),
					'schema_type'     => ARRAY_A,
				)
			);
		}

		if ( function_exists( 'woocommerce_store_api_register_update_callback' ) ) {
			woocommerce_store_api_register_update_callback(
				array(
					'namespace' => self::NAMESPACE_ID,
					'callback'  => array( $this, 'handle_update' ),
				)
			);
		}
	}

	/**
	 * Schema der Erweiterungsdaten (nur lesend, Dokumentationszweck).
	 *
	 * @return array
	 */
	public function get_cart_schema() {
		$readonly = array(
			'context'  => array( 'view', 'edit' ),
			'readonly' => true,
		);

		return array(
			'selector_enabled' => array( 'type' => 'boolean' ) + $readonly,
			'location_id'      => array( 'type' => 'integer' ) + $readonly,
			'order_type'       => array( 'type' => 'string' ) + $readonly,
			'pickup_time'      => array( 'type' => 'string' ) + $readonly,
			'pickup_label'     => array( 'type' => 'string' ) + $readonly,
			'table_name'       => array( 'type' => 'string' ) + $readonly,
			'now_closed_text'  => array( 'type' => 'string' ) + $readonly,
			'pro'              => array( 'type' => 'object' ) + $readonly,
		);
	}

	/**
	 * Aktueller Stand für das Block-Script.
	 *
	 * @return array
	 */
	public function get_cart_data() {
		$input       = LBite_Checkout::get_session_input();
		$selector    = lbite_feature_enabled( 'enable_location_selector' );
		$location_id = $input['location_id'];
		$locations   = class_exists( 'LBite_Locations' ) ? LBite_Locations::get_all_locations() : array();

		// Nur ein Standort: wie im klassischen Checkout automatisch setzen.
		if ( $selector && ! $location_id && 1 === count( $locations ) && WC()->session ) {
			LBite_Checkout::set_location_session( $locations[0]->ID, 'now' );
			$input       = LBite_Checkout::get_session_input();
			$location_id = $input['location_id'];
		}

		if ( ! $selector ) {
			$location_id = $this->checkout->resolve_location_for_order( $location_id );
		}

		$table_name = '';
		$table_id   = WC()->session ? (int) WC()->session->get( 'lbite_table_id' ) : 0;
		if ( $table_id ) {
			$table      = get_post( $table_id );
			$table_name = $table ? $table->post_title : '';
		}

		$pickup_label = '';
		if ( 'later' === $input['order_type'] && $input['pickup_time'] ) {
			$dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $input['pickup_time'], wp_timezone() );
			if ( $dt ) {
				$pickup_label = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $dt->getTimestamp() );
			}
		}

		$data = array(
			'selector_enabled' => (bool) $selector,
			'location_id'      => (int) $location_id,
			'order_type'       => in_array( $input['order_type'], array( 'now', 'later' ), true ) ? $input['order_type'] : 'now',
			'pickup_time'      => $input['pickup_time'],
			'pickup_label'     => $pickup_label,
			'table_name'       => $table_name,
			'now_closed_text'  => $location_id ? $this->get_now_closed_text( $location_id ) : '',
			'pro'              => new stdClass(),
		);

		/**
		 * Erweiterungsdaten für den Block-Checkout ergänzen (Pro-Module).
		 *
		 * @param array $data Daten.
		 */
		return apply_filters( 'lbite_blocks_cart_data', $data );
	}

	/**
	 * Text, warum «Jetzt bestellen» gerade nicht geht, sonst leer.
	 *
	 * @param int $location_id Standort-ID.
	 * @return string
	 */
	private function get_now_closed_text( $location_id ) {
		$status = LBite_Locations::get_activation_status( $location_id );

		if ( ! $status ) {
			$status = LBite_Locations::get_location_status( LBite_Locations::get_opening_hours( $location_id ), $location_id );
			if ( ! $status || in_array( $status['type'], array( 'open', 'closing-soon' ), true ) ) {
				return '';
			}
		}

		return isset( $status['text'] ) ? wp_strip_all_tags( (string) $status['text'] ) : '';
	}

	/**
	 * Auswahl aus dem Block in die Session übernehmen.
	 *
	 * Aufgerufen über POST /wc/store/v1/cart/extensions. WooCommerce schickt
	 * danach den neu berechneten Warenkorb zurück.
	 *
	 * @param array $data Daten aus extensionCartUpdate().
	 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException Bei ungültiger Auswahl.
	 */
	public function handle_update( $data ) {
		$data   = is_array( $data ) ? $data : array();
		$action = isset( $data['action'] ) ? sanitize_key( $data['action'] ) : '';

		if ( 'set_location' === $action ) {
			$location_id = isset( $data['location_id'] ) ? absint( $data['location_id'] ) : 0;
			$order_type  = isset( $data['order_type'] ) && 'later' === $data['order_type'] ? 'later' : 'now';
			$pickup_time = isset( $data['pickup_time'] ) ? sanitize_text_field( (string) $data['pickup_time'] ) : '';

			// Tischbestellung per QR-Code: Standort ist fest.
			if ( WC()->session && WC()->session->get( 'lbite_table_id' ) ) {
				$location_id = (int) WC()->session->get( 'lbite_location_id' );
			}

			$result = LBite_Checkout::set_location_session( $location_id, $order_type, $pickup_time );
			if ( empty( $result['success'] ) ) {
				self::fail( $result['message'] );
			}
			return;
		}

		/**
		 * Weitere Aktionen aus dem Block-Checkout behandeln (Pro-Module).
		 *
		 * @param bool   $handled Bereits behandelt.
		 * @param string $action  Aktion.
		 * @param array  $data    Daten.
		 */
		$handled = apply_filters( 'lbite_blocks_handle_update', false, $action, $data );

		if ( ! $handled ) {
			self::fail( __( 'Unknown action.', 'libre-bite' ) );
		}
	}

	/**
	 * Store-API-Fehler auslösen.
	 *
	 * @param string $message Meldung.
	 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException Immer.
	 */
	public static function fail( $message ) {
		throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'lbite_checkout_invalid', esc_html( wp_strip_all_tags( (string) $message ) ), 400 );
	}

	/**
	 * Ist die Anfrage das eigentliche Absenden des Block-Checkouts?
	 *
	 * update_order_from_request() läuft auch bei PUT (Formularänderungen nach
	 * fehlgeschlagener Zahlung) und beim Bezahlen einer bestehenden Bestellung
	 * (/checkout/<id>). Geprüft und gespeichert wird nur beim POST auf /checkout.
	 *
	 * @param WP_REST_Request $request Anfrage.
	 * @return bool
	 */
	private static function is_checkout_submit( $request ) {
		if ( ! $request instanceof WP_REST_Request || 'POST' !== $request->get_method() ) {
			return false;
		}

		return (bool) preg_match( '#/checkout/?$#', (string) $request->get_route() );
	}

	/**
	 * Beim Absenden prüfen und Libre-Bite-Angaben in die Bestellung schreiben.
	 *
	 * @param WC_Order        $order   Bestellung.
	 * @param WP_REST_Request $request Anfrage.
	 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException Bei ungültiger Auswahl.
	 */
	public function process_checkout( $order, $request ) {
		if ( ! self::is_checkout_submit( $request ) ) {
			return;
		}

		$input  = LBite_Checkout::get_session_input();
		$errors = $this->checkout->get_location_errors( $input );

		/**
		 * Weitere Prüfungen beim Absenden des Block-Checkouts (Pro-Module).
		 *
		 * @param string[] $errors Fehlermeldungen.
		 * @param array    $input  Standort, Bestellart, Abholzeit, Bestelltyp.
		 */
		$errors = apply_filters( 'lbite_blocks_checkout_errors', $errors, $input );

		if ( ! empty( $errors ) ) {
			self::fail( implode( ' ', array_unique( $errors ) ) );
		}

		$selector = lbite_feature_enabled( 'enable_location_selector' );

		LBite_Checkout::write_location_meta(
			$order,
			array(
				'location_id' => $selector ? $input['location_id'] : $this->checkout->resolve_location_for_order( $input['location_id'] ),
				'order_type'  => $selector ? $input['order_type'] : 'now',
				'pickup_time' => $input['pickup_time'],
			)
		);

		/**
		 * Weitere Angaben in die Bestellung schreiben (Pro-Module). Die
		 * Bestellung wird danach von WooCommerce gespeichert.
		 *
		 * @param WC_Order $order Bestellung.
		 * @param array    $input Standort, Bestellart, Abholzeit, Bestelltyp.
		 */
		do_action( 'lbite_blocks_checkout_order', $order, $input );
	}

	/**
	 * Nach dem Absenden: einmalige Angaben aus der Session entfernen.
	 *
	 * @param WC_Order $order Bestellung.
	 */
	public function after_order_processed( $order ) {
		unset( $order );
		if ( WC()->session ) {
			WC()->session->__unset( 'lbite_tip' );
		}
	}

	/**
	 * Script und Stil laden, wenn die Kasse als Block läuft.
	 */
	public function enqueue_assets() {
		if ( ! self::is_block_checkout() ) {
			return;
		}

		wp_enqueue_style( 'lbite-checkout-blocks', LBITE_PLUGIN_URL . 'assets/css/checkout-blocks.css', array(), LBITE_VERSION );

		wp_enqueue_script(
			'lbite-checkout-blocks',
			LBITE_PLUGIN_URL . 'assets/js/checkout-blocks.js',
			array( 'wp-element', 'wp-plugins', 'wp-data', 'wp-dom-ready', 'wc-blocks-checkout' ),
			LBITE_VERSION,
			true
		);

		$locations = array();
		foreach ( LBite_Locations::get_all_locations() as $location ) {
			$address     = LBite_Locations::get_formatted_address( $location->ID );
			$locations[] = array(
				'id'    => (int) $location->ID,
				'label' => $address ? $location->post_title . ' (' . $address . ')' : $location->post_title,
				'name'  => $location->post_title,
			);
		}

		wp_localize_script(
			'lbite-checkout-blocks',
			'lbiteCheckoutBlocks',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'today'     => current_time( 'Y-m-d' ),
				'locations' => $locations,
				'strings'   => array(
					'title'          => __( 'Order Options', 'libre-bite' ),
					'location'       => __( 'Location', 'libre-bite' ),
					'selectLocation' => __( 'Select location...', 'libre-bite' ),
					'orderType'      => __( 'Order Type', 'libre-bite' ),
					'orderNow'       => __( 'Order Now', 'libre-bite' ),
					'tableService'   => __( 'Table Service', 'libre-bite' ),
					'later'          => __( 'Pre-order for Later', 'libre-bite' ),
					'notNow'         => __( 'Immediate order not available –', 'libre-bite' ),
					'pickupDate'     => __( 'Pickup Date', 'libre-bite' ),
					'pickupTime'     => __( 'Pickup Time', 'libre-bite' ),
					'selectTime'     => __( 'Select time...', 'libre-bite' ),
					'noSlots'        => __( 'No pickup times available on this day.', 'libre-bite' ),
					'table'          => __( 'Table', 'libre-bite' ),
					'needLocation'   => __( 'Please select a location.', 'libre-bite' ),
					'needTime'       => __( 'Please select a pickup time.', 'libre-bite' ),
					'error'          => __( 'Could not save your selection. Please try again.', 'libre-bite' ),
				),
			)
		);

		/**
		 * Weitere Block-Checkout-Assets laden (Pro-Module).
		 */
		do_action( 'lbite_blocks_enqueue_assets' );
	}
}
