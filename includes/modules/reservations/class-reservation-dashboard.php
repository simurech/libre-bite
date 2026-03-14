<?php
/**
 * Reservierungs-Dashboard (Tagesansicht)
 *
 * AJAX-Endpoints für die operative Tagesübersicht nach Standort und Datum.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reservation-Dashboard-Modul
 */
class LBite_Reservation_Dashboard {

	/**
	 * Loader-Instanz
	 *
	 * @var LBite_Loader
	 */
	private $loader;

	/**
	 * Konstruktor
	 *
	 * @param LBite_Loader $loader Loader-Instanz
	 */
	public function __construct( $loader ) {
		$this->loader = $loader;
		$this->init_hooks();
	}

	/**
	 * Hooks initialisieren
	 */
	private function init_hooks() {
		$this->loader->add_action( 'wp_ajax_lbite_get_reservations', $this, 'ajax_get_reservations' );
		$this->loader->add_action( 'wp_ajax_lbite_update_reservation_status', $this, 'ajax_update_reservation_status' );
		$this->loader->add_action( 'wp_ajax_lbite_assign_reservation_table', $this, 'ajax_assign_reservation_table' );
		$this->loader->add_action( 'wp_ajax_lbite_save_reservation_board_location', $this, 'ajax_save_reservation_board_location' );
	}

	/**
	 * AJAX: Reservierungen für Datum und Standort laden (inkl. Tischliste)
	 */
	public function ajax_get_reservations() {
		check_ajax_referer( 'lbite_reservation_board_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;
		$date        = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';

		if ( ! $location_id || ! $date ) {
			wp_send_json_error( array( 'message' => __( 'Standort und Datum erforderlich', 'libre-bite' ) ) );
		}

		// Datum validieren und normalisieren
		$timestamp = strtotime( $date );
		if ( ! $timestamp ) {
			wp_send_json_error( array( 'message' => __( 'Ungültiges Datum', 'libre-bite' ) ) );
		}
		$date = gmdate( 'Y-m-d', $timestamp );

		// Reservierungen nach Tag und Standort laden, chronologisch nach Uhrzeit sortiert
		$args = array(
			'post_type'      => LBite_Reservations::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'meta_value',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Chronologische Sortierung der Tagesansicht; auf 200 begrenzt.
			'meta_key'       => '_lbite_reservation_time',
			'order'          => 'ASC',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Tagesansicht nach Standort und Datum; auf 200 begrenzt.
			'meta_query'     => array(
				array(
					'key'   => '_lbite_reservation_date',
					'value' => $date,
				),
				array(
					'key'   => '_lbite_location_id',
					'value' => $location_id,
				),
			),
		);

		$query        = new WP_Query( $args );
		$reservations = array();

		foreach ( $query->posts as $post ) {
			$table_id = intval( get_post_meta( $post->ID, '_lbite_table_id', true ) );
			$table    = $table_id ? get_post( $table_id ) : null;

			$reservations[] = array(
				'id'       => $post->ID,
				'time'     => get_post_meta( $post->ID, '_lbite_reservation_time', true ),
				'guests'   => intval( get_post_meta( $post->ID, '_lbite_reservation_guests', true ) ),
				'name'     => get_post_meta( $post->ID, '_lbite_reservation_name', true ),
				'phone'    => get_post_meta( $post->ID, '_lbite_reservation_phone', true ),
				'notes'    => get_post_meta( $post->ID, '_lbite_reservation_notes', true ),
				'status'   => get_post_meta( $post->ID, '_lbite_reservation_status', true ) ?: 'pending',
				'table_id' => $table_id,
				'table'    => $table ? $table->post_title : '',
			);
		}

		// Tischliste für Standort einmalig mitliefern (kein separater Request pro Karte nötig)
		$table_posts = get_posts(
			array(
				'post_type'      => 'lbite_table',
				'posts_per_page' => 100,
				'post_status'    => 'publish',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Tische nach Standort; auf 100 begrenzt.
				'meta_query'     => array(
					array(
						'key'   => '_lbite_location_id',
						'value' => $location_id,
					),
				),
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$tables = array();
		foreach ( $table_posts as $table_post ) {
			$tables[] = array(
				'id'    => $table_post->ID,
				'title' => $table_post->post_title,
				'seats' => intval( get_post_meta( $table_post->ID, '_lbite_table_seats', true ) ),
			);
		}

		wp_send_json_success(
			array(
				'reservations' => $reservations,
				'tables'       => $tables,
				'statuses'     => LBite_Reservations::STATUSES,
			)
		);
	}

	/**
	 * AJAX: Reservierungsstatus ändern
	 */
	public function ajax_update_reservation_status() {
		check_ajax_referer( 'lbite_reservation_board_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$reservation_id = isset( $_POST['reservation_id'] ) ? intval( wp_unslash( $_POST['reservation_id'] ) ) : 0;
		$status         = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';

		if ( ! $reservation_id || ! array_key_exists( $status, LBite_Reservations::STATUSES ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungültige Anfrage', 'libre-bite' ) ) );
		}

		$post = get_post( $reservation_id );
		if ( ! $post || LBite_Reservations::POST_TYPE !== $post->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Reservierung nicht gefunden', 'libre-bite' ) ) );
		}

		update_post_meta( $reservation_id, '_lbite_reservation_status', $status );
		delete_transient( 'lbite_pending_reservations_count' );

		wp_send_json_success( array( 'status' => $status ) );
	}

	/**
	 * AJAX: Tisch einer Reservierung zuweisen
	 *
	 * Prüft, dass der Tisch zum Standort der Reservierung gehört.
	 */
	public function ajax_assign_reservation_table() {
		check_ajax_referer( 'lbite_reservation_board_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$reservation_id = isset( $_POST['reservation_id'] ) ? intval( wp_unslash( $_POST['reservation_id'] ) ) : 0;
		$table_id       = isset( $_POST['table_id'] ) ? intval( wp_unslash( $_POST['table_id'] ) ) : 0;

		if ( ! $reservation_id ) {
			wp_send_json_error( array( 'message' => __( 'Ungültige Reservierung', 'libre-bite' ) ) );
		}

		$post = get_post( $reservation_id );
		if ( ! $post || LBite_Reservations::POST_TYPE !== $post->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Reservierung nicht gefunden', 'libre-bite' ) ) );
		}

		// Cross-Location-Check: Tisch muss zum Standort der Reservierung gehören
		if ( $table_id ) {
			$reservation_location = intval( get_post_meta( $reservation_id, '_lbite_location_id', true ) );
			$table_location       = intval( get_post_meta( $table_id, '_lbite_location_id', true ) );

			if ( $reservation_location && $table_location && $reservation_location !== $table_location ) {
				wp_send_json_error( array( 'message' => __( 'Tisch gehört zu einem anderen Standort', 'libre-bite' ) ) );
			}
		}

		update_post_meta( $reservation_id, '_lbite_table_id', $table_id );
		$table_title = $table_id ? get_the_title( $table_id ) : '';

		wp_send_json_success( array( 'table_id' => $table_id, 'table' => $table_title ) );
	}

	/**
	 * AJAX: Standort-Wahl für Reservierungsboard in User-Meta speichern
	 */
	public function ajax_save_reservation_board_location() {
		check_ajax_referer( 'lbite_reservation_board_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;
		update_user_meta( get_current_user_id(), 'lbite_reservation_board_location', $location_id );

		wp_send_json_success( array( 'location_id' => $location_id ) );
	}
}
