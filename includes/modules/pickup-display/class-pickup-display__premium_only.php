<?php
/**
 * Abholanzeige (Pro)
 *
 * Vollbildseite für einen Fernseher oder Monitor im Lokal: zeigt, welche
 * Bestellnummern gerade zubereitet werden und welche abholbereit sind.
 *
 * - Die Spalten kommen aus der Bestellübersicht (LBite_Order_Dashboard::get_columns()),
 *   eigene Spalten funktionieren deshalb automatisch mit.
 * - Zugang ohne Anmeldung über einen geheimen Schlüssel in der Adresse
 *   (Option lbite_pickup_display_key), damit der Fernseher kein Benutzerkonto
 *   braucht. Die Seite zeigt nur Bestellnummern, keine Namen oder Beträge.
 * - Daten über die REST-Route lbite/v1/pickup-display, alle 5 Sekunden.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abholanzeige
 */
class LBite_Pickup_Display {

	/**
	 * Query-Parameter der Anzeigeseite.
	 */
	const QUERY_VAR = 'lbite_pickup_display';

	/**
	 * Konstruktor
	 *
	 * @param LBite_Loader $loader Loader.
	 */
	public function __construct( $loader ) {
		$loader->add_action( 'template_redirect', $this, 'maybe_render_display', 1 );
		$loader->add_action( 'rest_api_init', $this, 'register_routes' );
	}

	/* ─────────────────────────────────────────────────────────────────
	 * Einstellungen
	 * ───────────────────────────────────────────────────────────────── */

	/**
	 * Geheimer Schlüssel der Anzeige (wird beim ersten Zugriff erzeugt).
	 *
	 * @return string
	 */
	public static function get_key() {
		$key = (string) get_option( 'lbite_pickup_display_key', '' );
		if ( strlen( $key ) < 20 ) {
			$key = self::regenerate_key();
		}

		return $key;
	}

	/**
	 * Neuen Schlüssel erzeugen. Bisherige Anzeige-Links funktionieren danach nicht mehr.
	 *
	 * @return string
	 */
	public static function regenerate_key() {
		$key = wp_generate_password( 32, false, false );
		update_option( 'lbite_pickup_display_key', $key, false );

		return $key;
	}

	/**
	 * Adresse der Anzeige für einen Standort.
	 *
	 * @param int $location_id Standort-ID.
	 * @return string
	 */
	public static function get_url( $location_id ) {
		return add_query_arg(
			array(
				self::QUERY_VAR => self::get_key(),
				'location'      => (int) $location_id,
			),
			home_url( '/' )
		);
	}

	/**
	 * Angezeigte Spalten mit Titel für die Gäste.
	 *
	 * Standard: alle Spalten ausser den Vorbestellungen.
	 *
	 * @return array[] Liste aus [ key, label, done ].
	 */
	public static function get_display_columns() {
		$selected = get_option( 'lbite_pickup_display_columns', null );
		$labels   = (array) get_option( 'lbite_pickup_display_labels', array() );
		$columns  = array();

		foreach ( LBite_Order_Dashboard::get_columns() as $column ) {
			$is_selected = is_array( $selected )
				? in_array( $column['key'], $selected, true )
				: LBite_Order_Dashboard::KEY_PREORDER !== $column['key'];
			if ( ! $is_selected ) {
				continue;
			}

			$columns[] = array(
				'key'   => $column['key'],
				'label' => ! empty( $labels[ $column['key'] ] ) ? $labels[ $column['key'] ] : $column['label'],
				'done'  => ! empty( $column['counts_as_completed'] ),
			);
		}

		return $columns;
	}

	/* ─────────────────────────────────────────────────────────────────
	 * Daten
	 * ───────────────────────────────────────────────────────────────── */

	/**
	 * Bestellnummern je angezeigter Spalte.
	 *
	 * Abgeschlossene Spalten zeigen eine Nummer nur für die eingestellte Zeit
	 * (lbite_pickup_display_minutes), sonst füllte sich «Abholbereit» endlos.
	 *
	 * @param int $location_id Standort-ID.
	 * @return array
	 */
	public static function get_data( $location_id ) {
		$columns = self::get_display_columns();
		$minutes = max( 1, (int) get_option( 'lbite_pickup_display_minutes', 15 ) );
		$cutoff  = time() - $minutes * MINUTE_IN_SECONDS;
		$by_key  = array();
		foreach ( $columns as $i => $column ) {
			$by_key[ $column['key'] ] = $i;
			$columns[ $i ]['numbers'] = array();
		}

		$orders = wc_get_orders(
			array(
				'limit'        => 200,
				'status'       => array( 'wc-processing', 'wc-on-hold', 'wc-completed' ),
				'date_created' => '>' . ( time() - DAY_IN_SECONDS ),
				'orderby'      => 'date',
				'order'        => 'ASC',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bestellungen eines Standorts der letzten 24 Stunden, höchstens 200.
				'meta_query'   => array(
					array(
						'key'   => '_lbite_location_id',
						'value' => (int) $location_id,
					),
				),
			)
		);

		foreach ( $orders as $order ) {
			// Offene Tabs am Tisch holt niemand an der Theke ab.
			if ( '1' === (string) $order->get_meta( '_lbite_tab_open' ) ) {
				continue;
			}

			$key = (string) $order->get_meta( '_lbite_order_status' );
			if ( ! isset( $by_key[ $key ] ) ) {
				continue;
			}
			$i = $by_key[ $key ];

			if ( $columns[ $i ]['done'] ) {
				$changed = (string) $order->get_meta( '_lbite_status_changed' );
				$stamp   = '' !== $changed ? strtotime( get_gmt_from_date( $changed ) . ' UTC' ) : ( $order->get_date_modified() ? $order->get_date_modified()->getTimestamp() : 0 );
				if ( $stamp < $cutoff ) {
					continue;
				}
			}

			$columns[ $i ]['numbers'][] = (string) $order->get_order_number();
		}

		$location = get_post( $location_id );

		return array(
			'location' => $location ? $location->post_title : '',
			'columns'  => $columns,
			'time'     => time(),
		);
	}

	/**
	 * Schlüssel und Standort einer Anfrage prüfen.
	 *
	 * @param string $key         Schlüssel aus der Adresse.
	 * @param int    $location_id Standort-ID.
	 * @return bool
	 */
	public static function is_valid_request( $key, $location_id ) {
		return '' !== (string) $key
			&& hash_equals( self::get_key(), (string) $key )
			&& class_exists( 'LBite_Locations' )
			&& LBite_Locations::is_valid_location( (int) $location_id );
	}

	/* ─────────────────────────────────────────────────────────────────
	 * REST
	 * ───────────────────────────────────────────────────────────────── */

	/**
	 * Route lbite/v1/pickup-display registrieren.
	 */
	public function register_routes() {
		register_rest_route(
			'lbite/v1',
			'/pickup-display',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_rest' ),
				'permission_callback' => array( $this, 'rest_permission' ),
				'args'                => array(
					'key'         => array(
						'type'     => 'string',
						'required' => true,
					),
					'location_id' => array(
						'type'     => 'integer',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Zugriff nur mit gültigem Schlüssel.
	 *
	 * @param WP_REST_Request $request Anfrage.
	 * @return bool|WP_Error
	 */
	public function rest_permission( $request ) {
		if ( self::is_valid_request( $request->get_param( 'key' ), (int) $request->get_param( 'location_id' ) ) ) {
			return true;
		}

		return new WP_Error( 'lbite_forbidden', __( 'No permission', 'libre-bite' ), array( 'status' => 403 ) );
	}

	/**
	 * Daten der Anzeige.
	 *
	 * @param WP_REST_Request $request Anfrage.
	 * @return WP_REST_Response
	 */
	public function handle_rest( $request ) {
		$response = rest_ensure_response( self::get_data( (int) $request->get_param( 'location_id' ) ) );
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/* ─────────────────────────────────────────────────────────────────
	 * Seite
	 * ───────────────────────────────────────────────────────────────── */

	/**
	 * Anzeigeseite ausgeben, wenn die Adresse dazu passt.
	 */
	public function maybe_render_display() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Öffentliche Leseseite, Zugang über den geheimen Schlüssel.
		if ( ! isset( $_GET[ self::QUERY_VAR ] ) ) {
			return;
		}

		$key         = sanitize_text_field( wp_unslash( $_GET[ self::QUERY_VAR ] ) );
		$location_id = isset( $_GET['location'] ) ? absint( $_GET['location'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! self::is_valid_request( $key, $location_id ) ) {
			status_header( 403 );
			nocache_headers();
			wp_die( esc_html__( 'This pickup display link is not valid. Copy the current link from the settings.', 'libre-bite' ), '', array( 'response' => 403 ) );
		}

		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow', true );

		wp_enqueue_style( 'lbite-pickup-display', LBITE_PLUGIN_URL . 'assets/css/pickup-display__premium_only.css', array(), LBITE_VERSION );
		if ( class_exists( 'LBite_Branding' ) ) {
			wp_add_inline_style( 'lbite-pickup-display', LBite_Branding::get_inline_css() );
		}
		wp_enqueue_script( 'lbite-pickup-display', LBITE_PLUGIN_URL . 'assets/js/pickup-display__premium_only.js', array(), LBITE_VERSION, true );
		wp_localize_script(
			'lbite-pickup-display',
			'lbitePickupDisplay',
			array(
				'endpoint' => esc_url_raw(
					add_query_arg(
						array(
							'key'         => $key,
							'location_id' => $location_id,
						),
						rest_url( 'lbite/v1/pickup-display' )
					)
				),
				'interval' => 5000,
				'sound'    => '0' !== (string) get_option( 'lbite_pickup_display_sound', 1 ) ? LBITE_PLUGIN_URL . 'assets/sounds/notification.mp3' : '',
				'strings'  => array(
					'offline'     => __( 'Connection lost – retrying …', 'libre-bite' ),
					'enableSound' => __( 'Tap to enable sound', 'libre-bite' ),
				),
			)
		);

		$lbite_display_title = (string) get_option( 'lbite_pickup_display_title', '' );
		$lbite_display_data  = self::get_data( $location_id );

		include LBITE_PLUGIN_DIR . 'templates/frontend/pickup-display__premium_only.php';
		exit;
	}
}
