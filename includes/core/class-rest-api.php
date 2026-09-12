<?php
/**
 * REST-API-Fundament
 *
 * Stellt den Namensraum `lbite/v1` bereit – parallel zu admin-ajax.php,
 * nicht als Ersatz. Bestehende AJAX-Endpunkte bleiben unverändert; beide
 * Zugänge teilen sich dieselben Kernmethoden, damit sie nicht auseinanderlaufen.
 *
 * Authentifizierung bewusst über WordPress-Bordmittel (Cookie-Session mit
 * Nonce oder Application Passwords) statt über ein eigenes Schlüsselschema.
 * Das vermeidet ein weiteres Geheimnis in der Datenbank und nutzt die
 * Rechteverwaltung, die ohnehin schon existiert.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse LBite_REST_API
 */
class LBite_REST_API {

	/**
	 * Namensraum der API.
	 */
	const NAMESPACE_V1 = 'lbite/v1';

	/**
	 * Loader-Instanz.
	 *
	 * @var LBite_Loader
	 */
	private $loader;

	/**
	 * Konstruktor
	 *
	 * @param LBite_Loader $loader Hook-Loader.
	 */
	public function __construct( $loader ) {
		$this->loader = $loader;
		$this->loader->add_action( 'rest_api_init', $this, 'register_routes' );
	}

	/**
	 * Routen registrieren
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE_V1,
			'/ping',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_ping' ),
				'permission_callback' => array( $this, 'can_view_orders' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/locations',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_get_locations' ),
				'permission_callback' => array( $this, 'can_view_orders' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/columns',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_get_columns' ),
				'permission_callback' => array( $this, 'can_view_orders' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/orders',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_get_orders' ),
				'permission_callback' => array( $this, 'can_view_orders' ),
				'args'                => array(
					'location_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => 'ID of the location whose board should be returned.',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/orders/(?P<id>\d+)/status',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'handle_update_order_status' ),
				'permission_callback' => array( $this, 'can_manage_orders' ),
				'args'                => array(
					'id'     => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'status' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'description'       => 'Target column key.',
					),
				),
			)
		);
	}

	/* ─────────────────────────────────────────────────────────────────
	 * Berechtigungen
	 * ───────────────────────────────────────────────────────────────── */

	/**
	 * Darf der aktuelle Benutzer Bestellungen einsehen?
	 *
	 * @return true|WP_Error
	 */
	public function can_view_orders() {
		if ( current_user_can( 'lbite_view_orders' ) || current_user_can( 'manage_options' ) ) {
			return true;
		}

		return $this->forbidden();
	}

	/**
	 * Darf der aktuelle Benutzer Bestellungen verändern?
	 *
	 * @return true|WP_Error
	 */
	public function can_manage_orders() {
		if ( current_user_can( 'lbite_manage_orders' )
			|| current_user_can( 'edit_shop_orders' )
			|| current_user_can( 'manage_options' ) ) {
			return true;
		}

		return $this->forbidden();
	}

	/**
	 * Einheitliche Ablehnung
	 *
	 * @return WP_Error
	 */
	private function forbidden() {
		return new WP_Error(
			'lbite_forbidden',
			__( 'No permission', 'libre-bite' ),
			array( 'status' => is_user_logged_in() ? 403 : 401 )
		);
	}

	/**
	 * Standorte, auf die der aktuelle Benutzer zugreifen darf
	 *
	 * Manager können auf einzelne Standorte eingeschränkt sein
	 * (`lbite_assigned_locations`). Wer die Einschränkung nicht hat oder
	 * `manage_options` besitzt, sieht alle Standorte.
	 *
	 * @return int[]|null Liste erlaubter IDs oder null für «alle».
	 */
	private function get_allowed_location_ids() {
		if ( current_user_can( 'manage_options' ) ) {
			return null;
		}

		$assigned = get_user_meta( get_current_user_id(), 'lbite_assigned_locations', true );

		if ( empty( $assigned ) || ! is_array( $assigned ) ) {
			return null;
		}

		return array_values( array_filter( array_map( 'absint', $assigned ) ) );
	}

	/**
	 * Zugriff auf einen bestimmten Standort prüfen
	 *
	 * @param int $location_id Standort-ID.
	 * @return true|WP_Error
	 */
	private function check_location_access( $location_id ) {
		$allowed = $this->get_allowed_location_ids();

		if ( null === $allowed || in_array( (int) $location_id, $allowed, true ) ) {
			return true;
		}

		return new WP_Error(
			'lbite_location_forbidden',
			__( 'No permission for this location', 'libre-bite' ),
			array( 'status' => 403 )
		);
	}

	/* ─────────────────────────────────────────────────────────────────
	 * Route-Callbacks
	 * ───────────────────────────────────────────────────────────────── */

	/**
	 * Verbindungstest
	 *
	 * @return WP_REST_Response
	 */
	public function handle_ping() {
		return rest_ensure_response(
			array(
				'ok'          => true,
				'plugin'      => 'libre-bite',
				'version'     => LBITE_VERSION,
				'server_time' => time(),
				'user_id'     => get_current_user_id(),
			)
		);
	}

	/**
	 * Standorte auflisten
	 *
	 * @return WP_REST_Response
	 */
	public function handle_get_locations() {
		$allowed   = $this->get_allowed_location_ids();
		$locations = array();

		foreach ( LBite_Locations::get_all_locations() as $location ) {
			$id = (int) $location->ID;

			if ( null !== $allowed && ! in_array( $id, $allowed, true ) ) {
				continue;
			}

			$locations[] = array(
				'id'    => $id,
				'name'  => get_the_title( $id ),
				'slug'  => $location->post_name,
			);
		}

		return rest_ensure_response( $locations );
	}

	/**
	 * Spaltenkonfiguration des Kanban-Boards
	 *
	 * @return WP_REST_Response
	 */
	public function handle_get_columns() {
		// Die Klasse existiert nur, wenn das Kanban-Modul geladen ist.
		if ( ! class_exists( 'LBite_Order_Dashboard' ) ) {
			return new WP_Error(
				'lbite_unavailable',
				__( 'Order board is not available', 'libre-bite' ),
				array( 'status' => 503 )
			);
		}

		return rest_ensure_response( LBite_Order_Dashboard::get_columns() );
	}

	/**
	 * Bestellungen eines Standorts als Board-Daten
	 *
	 * @param WP_REST_Request $request Anfrage.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_get_orders( $request ) {
		$location_id = (int) $request->get_param( 'location_id' );

		$access = $this->check_location_access( $location_id );
		if ( is_wp_error( $access ) ) {
			return $access;
		}

		$dashboard = LBite_Plugin::instance()->get_module( 'order-dashboard' );

		if ( ! $dashboard ) {
			return new WP_Error(
				'lbite_unavailable',
				__( 'Order board is not available', 'libre-bite' ),
				array( 'status' => 503 )
			);
		}

		return rest_ensure_response( $dashboard->get_board_data( $location_id ) );
	}

	/**
	 * Kanban-Status einer Bestellung setzen
	 *
	 * @param WP_REST_Request $request Anfrage.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_update_order_status( $request ) {
		$order_id = (int) $request->get_param( 'id' );
		$status   = (string) $request->get_param( 'status' );

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_Error(
				'lbite_order_not_found',
				__( 'Order not found', 'libre-bite' ),
				array( 'status' => 404 )
			);
		}

		// Standort-Einschränkung greift auch beim Schreiben: ein Manager darf
		// fremde Filialen weder lesen noch verändern.
		$access = $this->check_location_access( (int) $order->get_meta( '_lbite_location_id', true ) );
		if ( is_wp_error( $access ) ) {
			return $access;
		}

		$dashboard = LBite_Plugin::instance()->get_module( 'order-dashboard' );

		if ( ! $dashboard ) {
			return new WP_Error(
				'lbite_unavailable',
				__( 'Order board is not available', 'libre-bite' ),
				array( 'status' => 503 )
			);
		}

		$result = $dashboard->apply_order_status( $order_id, $status );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}
}
