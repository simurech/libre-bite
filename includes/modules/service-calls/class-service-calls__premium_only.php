<?php
/**
 * Digitaler Kellnerruf (F48, Pro)
 *
 * Gäste an einem per QR-Code erkannten Tisch können «Bedienung rufen» oder
 * «Rechnung bitte» antippen. Der Ruf erscheint im Kanban-Board (mit Ton) und
 * im Tischplan, bis jemand ihn als erledigt markiert.
 *
 * Sicherheit: Der Tisch stammt ausschliesslich aus der WooCommerce-Session,
 * die beim Aufruf des Tisch-QR-Codes geprüft gesetzt wurde
 * (LBite_Tables::process_table_url_parameters()). Eine Tisch-ID aus dem
 * Request wird nie übernommen – sonst könnte jeder beliebige Tische rufen.
 * Pro Tisch und Art gibt es höchstens einen offenen Ruf, neue Rufe eines
 * Tisches sind zusätzlich zeitlich gedrosselt.
 *
 * Speicher: eine nicht automatisch geladene Option statt eines eigenen
 * Inhaltstyps. Rufe sind kurzlebig (sie verfallen nach drei Stunden) und
 * sollen keine Spuren in Beiträgen oder Bestellungen hinterlassen.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kellnerruf-Klasse
 */
class LBite_Service_Calls {

	/**
	 * Option mit allen offenen Rufen.
	 */
	const OPTION = 'lbite_service_calls';

	/**
	 * Lebensdauer eines unerledigten Rufs in Sekunden (3 Stunden).
	 */
	const MAX_AGE = 10800;

	/**
	 * Mindestabstand zwischen zwei neuen Rufen desselben Tisches in Sekunden.
	 */
	const THROTTLE = 30;

	/**
	 * Loader-Instanz.
	 *
	 * @var LBite_Loader
	 */
	private $loader;

	/**
	 * Konstruktor
	 *
	 * @param LBite_Loader $loader Loader.
	 */
	public function __construct( $loader ) {
		$this->loader = $loader;

		// Gäste (angemeldet oder nicht).
		$this->loader->add_action( 'wp_ajax_lbite_service_call', $this, 'ajax_create_call' );
		$this->loader->add_action( 'wp_ajax_nopriv_lbite_service_call', $this, 'ajax_create_call' );
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_assets' );
		$this->loader->add_action( 'wp_footer', $this, 'render_guest_bar' );

		// Personal.
		$this->loader->add_action( 'wp_ajax_lbite_resolve_service_call', $this, 'ajax_resolve_call' );
		$this->loader->add_filter( 'lbite_board_data', $this, 'add_calls_to_board', 10, 2 );
		$this->loader->add_filter( 'lbite_table_status_response', $this, 'add_calls_to_table_plan', 10, 2 );
		$this->loader->add_action( 'rest_api_init', $this, 'register_rest_routes' );
	}

	/**
	 * Gültige Rufarten mit Beschriftung für das Personal.
	 *
	 * @return array<string,string>
	 */
	public static function get_types() {
		return array(
			'waiter' => __( 'Staff requested', 'libre-bite' ),
			'bill'   => __( 'Bill requested', 'libre-bite' ),
		);
	}

	/**
	 * Alle noch gültigen Rufe laden (abgelaufene werden verworfen).
	 *
	 * @return array[]
	 */
	public static function get_all_calls() {
		$calls = get_option( self::OPTION, array() );
		$calls = is_array( $calls ) ? $calls : array();
		$limit = time() - self::MAX_AGE;

		return array_values(
			array_filter(
				$calls,
				function ( $call ) use ( $limit ) {
					return is_array( $call ) && isset( $call['id'], $call['created'] ) && (int) $call['created'] >= $limit;
				}
			)
		);
	}

	/**
	 * Rufe speichern.
	 *
	 * @param array[] $calls Rufe.
	 */
	private static function save_calls( array $calls ) {
		update_option( self::OPTION, array_values( $calls ), false );
	}

	/**
	 * Offene Rufe eines Standorts, ältester zuerst, für Board und REST.
	 *
	 * @param int $location_id Standort-ID.
	 * @return array[]
	 */
	public static function get_calls_for_location( $location_id ) {
		$types  = self::get_types();
		$result = array();

		foreach ( self::get_all_calls() as $call ) {
			if ( (int) $call['location_id'] !== (int) $location_id ) {
				continue;
			}

			$result[] = array(
				'id'          => $call['id'],
				'table_id'    => (int) $call['table_id'],
				'table'       => get_the_title( (int) $call['table_id'] ),
				'type'        => $call['type'],
				'type_label'  => isset( $types[ $call['type'] ] ) ? $types[ $call['type'] ] : $call['type'],
				'created'     => (int) $call['created'],
				'age_minutes' => (int) floor( ( time() - (int) $call['created'] ) / MINUTE_IN_SECONDS ),
			);
		}

		usort(
			$result,
			function ( $a, $b ) {
				return $a['created'] - $b['created'];
			}
		);

		return $result;
	}

	/**
	 * Tisch und Standort der aktuellen Gast-Session, sofern gültig.
	 *
	 * @return array{table_id:int,location_id:int}|null
	 */
	private static function get_session_table() {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return null;
		}

		$table_id    = (int) WC()->session->get( 'lbite_table_id' );
		$location_id = (int) WC()->session->get( 'lbite_location_id' );

		if ( ! $table_id || ! $location_id || ! class_exists( 'LBite_Tables' ) || ! LBite_Tables::is_valid_table( $table_id, $location_id ) ) {
			return null;
		}

		return array(
			'table_id'    => $table_id,
			'location_id' => $location_id,
		);
	}

	/**
	 * Gast-Script und -Stil nur laden, wenn die Session einen Tisch kennt.
	 */
	public function enqueue_assets() {
		if ( ! self::get_session_table() ) {
			return;
		}

		wp_enqueue_style( 'lbite-service-calls', LBITE_PLUGIN_URL . 'assets/css/service-calls__premium_only.css', array(), LBITE_VERSION );
		wp_enqueue_script(
			'lbite-service-calls',
			LBITE_PLUGIN_URL . 'assets/js/service-calls__premium_only.js',
			array(),
			LBITE_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_localize_script(
			'lbite-service-calls',
			'lbiteServiceCalls',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'lbite_service_call' ),
				'strings' => array(
					'sent'    => __( 'Thank you – the staff has been notified.', 'libre-bite' ),
					'already' => __( 'The staff has already been notified and is on the way.', 'libre-bite' ),
					'error'   => __( 'That did not work. Please try again or wave to the staff.', 'libre-bite' ),
				),
			)
		);
	}

	/**
	 * Schwebende Rufleiste für Gäste am Tisch ausgeben.
	 */
	public function render_guest_bar() {
		$session = self::get_session_table();
		if ( ! $session ) {
			return;
		}
		?>
		<div class="lbite-service-call" role="region" aria-label="<?php esc_attr_e( 'Table service', 'libre-bite' ); ?>">
			<span class="lbite-service-call__table"><?php echo esc_html( get_the_title( $session['table_id'] ) ); ?></span>
			<button type="button" class="lbite-service-call__button" data-lbite-call="waiter"><?php esc_html_e( 'Call staff', 'libre-bite' ); ?></button>
			<button type="button" class="lbite-service-call__button" data-lbite-call="bill"><?php esc_html_e( 'Request bill', 'libre-bite' ); ?></button>
			<span class="lbite-service-call__status" role="status" aria-live="polite"></span>
		</div>
		<?php
	}

	/**
	 * AJAX (Gast): Ruf auslösen.
	 */
	public function ajax_create_call() {
		check_ajax_referer( 'lbite_service_call', 'nonce' );

		$session = self::get_session_table();
		if ( ! $session ) {
			wp_send_json_error( array( 'message' => 'no_table' ), 403 );
		}

		$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		if ( ! array_key_exists( $type, self::get_types() ) ) {
			wp_send_json_error( array( 'message' => 'invalid_type' ), 400 );
		}

		$calls = self::get_all_calls();

		// Bereits offener Ruf derselben Art: nichts doppeln, Gast trotzdem bestätigen.
		foreach ( $calls as $call ) {
			if ( (int) $call['table_id'] === $session['table_id'] && $call['type'] === $type ) {
				wp_send_json_success( array( 'status' => 'already' ) );
			}
		}

		// Drosselung pro Tisch über alle Arten hinweg.
		$throttle_key = 'lbite_sc_throttle_' . $session['table_id'];
		if ( get_transient( $throttle_key ) ) {
			wp_send_json_success( array( 'status' => 'already' ) );
		}
		set_transient( $throttle_key, 1, self::THROTTLE );

		$call = array(
			'id'          => wp_generate_uuid4(),
			'location_id' => $session['location_id'],
			'table_id'    => $session['table_id'],
			'type'        => $type,
			'created'     => time(),
		);

		$calls[] = $call;
		self::save_calls( $calls );

		/**
		 * Ein Gast hat am Tisch die Bedienung oder die Rechnung angefordert.
		 *
		 * @param array $call        Ruf (id, location_id, table_id, type, created).
		 * @param int   $location_id Standort-ID.
		 */
		do_action( 'lbite_service_call_created', $call, $session['location_id'] );

		wp_send_json_success( array( 'status' => 'sent' ) );
	}

	/**
	 * Ruf als erledigt entfernen.
	 *
	 * @param string $call_id Ruf-ID.
	 * @return bool|WP_Error true bei Erfolg.
	 */
	public static function resolve_call( $call_id ) {
		$calls = self::get_all_calls();

		foreach ( $calls as $index => $call ) {
			if ( $call['id'] !== $call_id ) {
				continue;
			}

			if ( ! LBite_Access::can_access_location( (int) $call['location_id'] ) ) {
				return new WP_Error( 'lbite_forbidden', __( 'No permission for this location', 'libre-bite' ), array( 'status' => 403 ) );
			}

			unset( $calls[ $index ] );
			self::save_calls( $calls );

			/**
			 * Ein Kellnerruf wurde als erledigt markiert.
			 *
			 * @param array $call Ruf.
			 */
			do_action( 'lbite_service_call_resolved', $call );

			return true;
		}

		// Schon von jemand anderem erledigt – für die Oberfläche kein Fehler.
		return true;
	}

	/**
	 * AJAX (Personal): Ruf als erledigt markieren.
	 */
	public function ajax_resolve_call() {
		// Board (lbite_dashboard_nonce) und Tischplan (lbite_admin_nonce) nutzen
		// unterschiedliche Nonces; beide sind für diese Aktion gültig.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'lbite_dashboard_nonce' ) && ! wp_verify_nonce( $nonce, 'lbite_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'libre-bite' ) ), 403 );
		}

		if ( ! current_user_can( 'lbite_manage_orders' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ), 403 );
		}

		$call_id = isset( $_POST['call_id'] ) ? sanitize_text_field( wp_unslash( $_POST['call_id'] ) ) : '';
		$result  = self::resolve_call( $call_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 403 );
		}

		wp_send_json_success();
	}

	/**
	 * Filter lbite_board_data: offene Rufe ins Kanban-Board (AJAX und REST).
	 *
	 * @param array $data        Board-Daten.
	 * @param int   $location_id Standort-ID.
	 * @return array
	 */
	public function add_calls_to_board( $data, $location_id ) {
		$data['service_calls']        = $location_id ? self::get_calls_for_location( $location_id ) : array();
		$data['service_call_strings'] = array(
			'done'    => __( 'Done', 'libre-bite' ),
			/* translators: %d: minutes since the guest called. */
			'minutes' => __( '%d min ago', 'libre-bite' ),
			'now'     => __( 'just now', 'libre-bite' ),
		);

		return $data;
	}

	/**
	 * Filter lbite_table_status_response: Rufe im Tischplan markieren.
	 *
	 * @param array $response    Antwort mit 'statuses'.
	 * @param int   $location_id Standort-ID.
	 * @return array
	 */
	public function add_calls_to_table_plan( $response, $location_id ) {
		$by_table = array();
		foreach ( self::get_calls_for_location( $location_id ) as $call ) {
			$by_table[ $call['table_id'] ][] = $call['type_label'];
		}

		$response['service_calls'] = $by_table;

		return $response;
	}

	/**
	 * REST: Rufe lesen und erledigen (lbite/v1).
	 */
	public function register_rest_routes() {
		register_rest_route(
			'lbite/v1',
			'/service-calls',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_calls' ),
				'permission_callback' => array( $this, 'rest_can_view' ),
				'args'                => array(
					'location_id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			'lbite/v1',
			'/service-calls/(?P<id>[a-f0-9-]{36})/resolve',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_resolve_call' ),
				'permission_callback' => array( $this, 'rest_can_manage' ),
			)
		);
	}

	/**
	 * REST: offene Rufe eines Standorts.
	 *
	 * @param WP_REST_Request $request Anfrage.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_get_calls( $request ) {
		$location_id = (int) $request->get_param( 'location_id' );
		if ( ! LBite_Access::can_access_location( $location_id ) ) {
			return new WP_Error( 'lbite_forbidden', __( 'No permission for this location', 'libre-bite' ), array( 'status' => 403 ) );
		}

		return rest_ensure_response( self::get_calls_for_location( $location_id ) );
	}

	/**
	 * REST: Ruf erledigen.
	 *
	 * @param WP_REST_Request $request Anfrage.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_resolve_call( $request ) {
		$result = self::resolve_call( (string) $request->get_param( 'id' ) );

		return is_wp_error( $result ) ? $result : rest_ensure_response( array( 'resolved' => true ) );
	}

	/**
	 * REST-Berechtigung: lesen.
	 *
	 * @return bool
	 */
	public function rest_can_view() {
		return current_user_can( 'lbite_view_orders' ) || current_user_can( 'manage_options' );
	}

	/**
	 * REST-Berechtigung: erledigen.
	 *
	 * @return bool
	 */
	public function rest_can_manage() {
		return current_user_can( 'lbite_manage_orders' ) || current_user_can( 'manage_options' );
	}
}
