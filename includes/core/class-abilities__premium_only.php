<?php
/**
 * WordPress Abilities API (ab WordPress 6.9, Kern von WordPress 7.0)
 *
 * Meldet Libre-Bite-Funktionen als «Abilities» an. KI-Assistenten,
 * MCP-Server und andere Werkzeuge finden sie darüber selbständig, inklusive
 * Eingabe-/Ausgabeschema und Rechteprüfung.
 *
 * Keine zweite Logik: Die Abilities rufen dieselben Methoden auf wie die
 * REST-API `lbite/v1` (LBite_REST_API) – also dieselbe Rechte- und
 * Standortprüfung, derselbe Statuswechsel samt Hooks.
 *
 * Auf älteren WordPress-Versionen fehlt wp_register_ability(); die Klasse
 * tut dann nichts.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abilities von Libre Bite
 */
class LBite_Abilities {

	/**
	 * Kategorie-Slug.
	 */
	const CATEGORY = 'libre-bite';

	/**
	 * Konstruktor
	 *
	 * @param LBite_Loader $loader Loader.
	 */
	public function __construct( $loader ) {
		$loader->add_action( 'wp_abilities_api_categories_init', $this, 'register_category' );
		$loader->add_action( 'wp_abilities_api_init', $this, 'register_abilities' );
	}

	/**
	 * Kategorie anmelden.
	 */
	public function register_category() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => 'Libre Bite',
				'description' => __( 'Orders, locations and pickup times of the restaurant.', 'libre-bite' ),
			)
		);
	}

	/**
	 * REST-Modul (gemeinsame Rechte- und Datenlogik).
	 *
	 * @return LBite_REST_API|null
	 */
	private static function rest() {
		return LBite_Plugin::instance()->get_module( 'rest-api' );
	}

	/**
	 * REST-Antwort in rohe Daten umwandeln.
	 *
	 * @param mixed $response WP_REST_Response, WP_Error oder Daten.
	 * @return mixed
	 */
	private static function unwrap( $response ) {
		if ( $response instanceof WP_REST_Response ) {
			return $response->get_data();
		}

		return $response;
	}

	/**
	 * Darf der Benutzer Bestellungen sehen?
	 *
	 * @return bool|WP_Error
	 */
	public function can_view() {
		$rest = self::rest();

		return $rest ? $rest->can_view_orders() : false;
	}

	/**
	 * Darf der Benutzer Bestellungen ändern?
	 *
	 * @return bool|WP_Error
	 */
	public function can_manage() {
		$rest = self::rest();

		return $rest ? $rest->can_manage_orders() : false;
	}

	/**
	 * Abilities anmelden.
	 */
	public function register_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		$location_input = array(
			'type'                 => 'object',
			'properties'           => array(
				'location_id' => array(
					'type'        => 'integer',
					'description' => __( 'ID of the location.', 'libre-bite' ),
					'minimum'     => 1,
				),
			),
			'required'             => array( 'location_id' ),
			'additionalProperties' => false,
		);

		$readonly = array(
			'show_in_rest' => true,
			'annotations'  => array(
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			),
		);

		wp_register_ability(
			'libre-bite/list-locations',
			array(
				'label'               => __( 'List locations', 'libre-bite' ),
				'description'         => __( 'Lists the locations the current user may access, with their current opening status.', 'libre-bite' ),
				'category'            => self::CATEGORY,
				'output_schema'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'     => array( 'type' => 'integer' ),
							'name'   => array( 'type' => 'string' ),
							'slug'   => array( 'type' => 'string' ),
							'status' => array( 'type' => 'string' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'list_locations' ),
				'permission_callback' => array( $this, 'can_view' ),
				'meta'                => $readonly,
			)
		);

		wp_register_ability(
			'libre-bite/get-orders',
			array(
				'label'               => __( 'Get open orders', 'libre-bite' ),
				'description'         => __( 'Returns the orders of a location as shown on the Order Board, grouped by column.', 'libre-bite' ),
				'category'            => self::CATEGORY,
				'input_schema'        => $location_input,
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( $this, 'get_orders' ),
				'permission_callback' => array( $this, 'can_view' ),
				'meta'                => $readonly,
			)
		);

		wp_register_ability(
			'libre-bite/update-order-status',
			array(
				'label'               => __( 'Move an order on the Order Board', 'libre-bite' ),
				'description'         => __( 'Sets the Order Board column of an order, for example from incoming to preparing.', 'libre-bite' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'order_id' => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
						'status'   => array(
							'type'        => 'string',
							'description' => __( 'Column key, for example incoming, preparing or completed.', 'libre-bite' ),
						),
					),
					'required'             => array( 'order_id', 'status' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( $this, 'update_order_status' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);

		wp_register_ability(
			'libre-bite/get-pickup-times',
			array(
				'label'               => __( 'Get pickup times', 'libre-bite' ),
				'description'         => __( 'Returns the bookable pickup times of a location for one day, taking opening hours, holidays and slot capacity into account.', 'libre-bite' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'location_id' => $location_input['properties']['location_id'],
						'date'        => array(
							'type'        => 'string',
							'description' => __( 'Day in the format YYYY-MM-DD. Default: today.', 'libre-bite' ),
							'pattern'     => '^\d{4}-\d{2}-\d{2}$',
						),
					),
					'required'             => array( 'location_id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'value' => array( 'type' => 'string' ),
							'label' => array( 'type' => 'string' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'get_pickup_times' ),
				// Dieselben Daten zeigt der Checkout jedem Gast.
				'permission_callback' => '__return_true',
				'meta'                => $readonly,
			)
		);
	}

	/**
	 * Standorte mit Öffnungsstatus.
	 *
	 * @return array|WP_Error
	 */
	public function list_locations() {
		$rest = self::rest();
		if ( ! $rest ) {
			return new WP_Error( 'lbite_unavailable', __( 'Not available', 'libre-bite' ) );
		}

		$locations = self::unwrap( $rest->handle_get_locations() );
		if ( ! is_array( $locations ) ) {
			return $locations;
		}

		foreach ( $locations as &$location ) {
			$status = LBite_Locations::get_activation_status( $location['id'] );
			if ( ! $status ) {
				$status = LBite_Locations::get_location_status( LBite_Locations::get_opening_hours( $location['id'] ), $location['id'] );
			}
			$location['status'] = $status && isset( $status['text'] ) ? wp_strip_all_tags( (string) $status['text'] ) : '';
		}
		unset( $location );

		return $locations;
	}

	/**
	 * Board-Daten eines Standorts.
	 *
	 * @param array $input location_id.
	 * @return array|WP_Error
	 */
	public function get_orders( $input ) {
		$rest = self::rest();
		if ( ! $rest ) {
			return new WP_Error( 'lbite_unavailable', __( 'Not available', 'libre-bite' ) );
		}

		$request = new WP_REST_Request( 'GET', '/lbite/v1/orders' );
		$request->set_param( 'location_id', isset( $input['location_id'] ) ? (int) $input['location_id'] : 0 );

		return self::unwrap( $rest->handle_get_orders( $request ) );
	}

	/**
	 * Kanban-Status setzen.
	 *
	 * @param array $input order_id, status.
	 * @return array|WP_Error
	 */
	public function update_order_status( $input ) {
		$rest = self::rest();
		if ( ! $rest ) {
			return new WP_Error( 'lbite_unavailable', __( 'Not available', 'libre-bite' ) );
		}

		$request = new WP_REST_Request( 'POST', '/lbite/v1/orders/status' );
		$request->set_param( 'id', isset( $input['order_id'] ) ? (int) $input['order_id'] : 0 );
		$request->set_param( 'status', isset( $input['status'] ) ? sanitize_key( $input['status'] ) : '' );

		return self::unwrap( $rest->handle_update_order_status( $request ) );
	}

	/**
	 * Buchbare Abholzeiten.
	 *
	 * @param array $input location_id, date.
	 * @return array|WP_Error
	 */
	public function get_pickup_times( $input ) {
		$location_id = isset( $input['location_id'] ) ? (int) $input['location_id'] : 0;
		$date        = isset( $input['date'] ) ? (string) $input['date'] : current_time( 'Y-m-d' );
		$checkout    = LBite_Plugin::instance()->get_module( 'checkout' );

		if ( ! $checkout || ! LBite_Locations::is_valid_location( $location_id ) ) {
			return new WP_Error( 'lbite_invalid_location', __( 'Invalid location', 'libre-bite' ) );
		}

		return $checkout->get_bookable_timeslots( $location_id, $date );
	}
}
