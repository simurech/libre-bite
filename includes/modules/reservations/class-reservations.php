<?php
/**
 * Tischreservierungen
 *
 * CPT lbite_reservation + Frontend-Formular + E-Mail-Flow.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reservations-Modul
 */
class LBite_Reservations {

	/**
	 * Post-Type Name
	 *
	 * @var string
	 */
	const POST_TYPE = 'lbite_reservation';

	/**
	 * Mögliche Reservierungs-Status
	 *
	 * @var array
	 */
	const STATUSES = array(
		'pending'   => 'Pending',
		'confirmed' => 'Confirmed',
		'cancelled' => 'Cancelled',
		'completed' => 'Completed',
	);

	/**
	 * Anzahl ausstehender Reservierungen für Menü-Badge zurückgeben (gecacht)
	 *
	 * @return int
	 */
	public static function get_pending_reservations_count() {
		$cached = get_transient( 'lbite_pending_reservations_count' );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		$count = (int) wp_count_posts( self::POST_TYPE )->publish ?? 0;

		// Nur ausstehende zählen
		$args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'fields'         => 'ids',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Ausstehende Reservierungen für Menü-Badge; Abfrage auf 500 begrenzt und gecacht.
			'meta_query'     => array(
				array(
					'key'     => '_lbite_reservation_status',
					'value'   => 'pending',
					'compare' => '=',
				),
			),
		);
		$query = new WP_Query( $args );
		$count = $query->found_posts;

		set_transient( 'lbite_pending_reservations_count', $count, 2 * MINUTE_IN_SECONDS );

		return $count;
	}

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
		// CPT + Shortcode
		$this->loader->add_action( 'init', $this, 'register_cpt' );
		$this->loader->add_action( 'init', $this, 'register_shortcode' );

		// Admin
		$this->loader->add_action( 'add_meta_boxes', $this, 'add_meta_boxes' );
		$this->loader->add_action( 'save_post_' . self::POST_TYPE, $this, 'save_reservation_meta' );
		$this->loader->add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', $this, 'add_admin_columns' );
		$this->loader->add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', $this, 'render_admin_columns', 10, 2 );
		$this->loader->add_action( 'restrict_manage_posts', $this, 'add_admin_filters' );
		$this->loader->add_action( 'parse_query', $this, 'apply_admin_filters' );

		// Frontend-Assets
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_frontend_assets' );

		// AJAX (öffentlich)
		$this->loader->add_action( 'wp_ajax_lbite_submit_reservation', $this, 'ajax_submit_reservation' );
		$this->loader->add_action( 'wp_ajax_nopriv_lbite_submit_reservation', $this, 'ajax_submit_reservation' );
		$this->loader->add_action( 'wp_ajax_lbite_get_reservation_tables', $this, 'ajax_get_reservation_tables' );
		$this->loader->add_action( 'wp_ajax_nopriv_lbite_get_reservation_tables', $this, 'ajax_get_reservation_tables' );
	}

	/**
	 * CPT registrieren
	 */
	public function register_cpt() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'               => __( 'Reservations', 'libre-bite' ),
					'singular_name'      => __( 'Reservation', 'libre-bite' ),
					'add_new'            => __( 'New Reservation', 'libre-bite' ),
					'add_new_item'       => __( 'Add New Reservation', 'libre-bite' ),
					'edit_item'          => __( 'Edit Reservation', 'libre-bite' ),
					'search_items'       => __( 'Search Reservations', 'libre-bite' ),
					'not_found'          => __( 'No Reservations Found', 'libre-bite' ),
					'not_found_in_trash' => __( 'No Reservations in Trash', 'libre-bite' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => false,
				'supports'     => array( 'title' ),
				'capability_type' => 'post',
				'capabilities' => array(
					'edit_post'          => 'lbite_manage_options',
					'edit_posts'         => 'lbite_manage_options',
					'edit_others_posts'  => 'lbite_manage_options',
					'publish_posts'      => 'lbite_manage_options',
					'read_post'          => 'lbite_manage_options',
					'read_private_posts' => 'lbite_manage_options',
					'delete_post'        => 'lbite_manage_options',
				),
				'map_meta_cap' => false,
			)
		);
	}

	/**
	 * Shortcode registrieren
	 */
	public function register_shortcode() {
		add_shortcode( 'lbite_reservation_form', array( $this, 'shortcode_reservation_form' ) );
	}

	/**
	 * Meta-Boxen für Admin-Bereich hinzufügen
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'lbite_reservation_details',
			__( 'Reservation Details', 'libre-bite' ),
			array( $this, 'render_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Meta-Box rendern
	 *
	 * @param WP_Post $post Post-Objekt
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'lbite_save_reservation', 'lbite_reservation_nonce' );

		$lbite_status   = get_post_meta( $post->ID, '_lbite_reservation_status', true ) ?: 'pending';
		$lbite_date     = get_post_meta( $post->ID, '_lbite_reservation_date', true );
		$lbite_time     = get_post_meta( $post->ID, '_lbite_reservation_time', true );
		$lbite_guests   = get_post_meta( $post->ID, '_lbite_reservation_guests', true );
		$lbite_table_id = get_post_meta( $post->ID, '_lbite_table_id', true );
		$lbite_table    = $lbite_table_id ? get_post( $lbite_table_id ) : null;
		$lbite_location_id = get_post_meta( $post->ID, '_lbite_location_id', true );
		$lbite_location    = $lbite_location_id ? get_post( $lbite_location_id ) : null;
		$lbite_name     = get_post_meta( $post->ID, '_lbite_reservation_name', true );
		$lbite_email    = get_post_meta( $post->ID, '_lbite_reservation_email', true );
		$lbite_phone    = get_post_meta( $post->ID, '_lbite_reservation_phone', true );
		$lbite_notes    = get_post_meta( $post->ID, '_lbite_reservation_notes', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="lbite_reservation_status"><?php esc_html_e( 'Status', 'libre-bite' ); ?></label></th>
				<td>
					<select id="lbite_reservation_status" name="lbite_reservation_status">
						<?php foreach ( self::STATUSES as $lbite_val => $lbite_label ) : ?>
							<option value="<?php echo esc_attr( $lbite_val ); ?>" <?php selected( $lbite_status, $lbite_val ); ?>>
								<?php echo esc_html( $lbite_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Date', 'libre-bite' ); ?></th>
				<td><?php echo $lbite_date ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $lbite_date ) ) ) : '—'; ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Time', 'libre-bite' ); ?></th>
				<td><?php echo $lbite_time ? esc_html( $lbite_time ) : '—'; ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Guests', 'libre-bite' ); ?></th>
				<td><?php echo $lbite_guests ? esc_html( $lbite_guests ) : '—'; ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Table', 'libre-bite' ); ?></th>
				<td><?php echo $lbite_table ? esc_html( $lbite_table->post_title ) : '—'; ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Location', 'libre-bite' ); ?></th>
				<td><?php echo $lbite_location ? esc_html( $lbite_location->post_title ) : '—'; ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Name', 'libre-bite' ); ?></th>
				<td><?php echo $lbite_name ? esc_html( $lbite_name ) : '—'; ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Email', 'libre-bite' ); ?></th>
				<td><?php echo $lbite_email ? esc_html( $lbite_email ) : '—'; ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Phone', 'libre-bite' ); ?></th>
				<td><?php echo $lbite_phone ? esc_html( $lbite_phone ) : '—'; ?></td>
			</tr>
			<?php if ( $lbite_notes ) : ?>
			<tr>
				<th><?php esc_html_e( 'Note', 'libre-bite' ); ?></th>
				<td><?php echo esc_html( $lbite_notes ); ?></td>
			</tr>
			<?php endif; ?>
		</table>
		<?php
	}

	/**
	 * Meta-Box-Daten speichern (nur Status ist editierbar)
	 *
	 * @param int $post_id Post-ID
	 */
	public function save_reservation_meta( $post_id ) {
		if ( ! isset( $_POST['lbite_reservation_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['lbite_reservation_nonce'] ) ), 'lbite_save_reservation' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'lbite_manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['lbite_reservation_status'] ) ) {
			$lbite_status = sanitize_key( wp_unslash( $_POST['lbite_reservation_status'] ) );
			if ( array_key_exists( $lbite_status, self::STATUSES ) ) {
				update_post_meta( $post_id, '_lbite_reservation_status', $lbite_status );
				delete_transient( 'lbite_pending_reservations_count' );
			}
		}
	}

	/**
	 * Admin-Listenspalten anpassen
	 *
	 * @param array $columns Bestehende Spalten
	 * @return array
	 */
	public function add_admin_columns( $columns ) {
		unset( $columns['date'] );
		return array_merge(
			array_slice( $columns, 0, 2 ),
			array(
				'lbite_res_date'     => __( 'Date', 'libre-bite' ),
				'lbite_res_time'     => __( 'Time', 'libre-bite' ),
				'lbite_res_guests'   => __( 'Guests', 'libre-bite' ),
				'lbite_res_table'    => __( 'Table', 'libre-bite' ),
				'lbite_res_location' => __( 'Location', 'libre-bite' ),
				'lbite_res_status'   => __( 'Status', 'libre-bite' ),
			),
			array_slice( $columns, 2 )
		);
	}

	/**
	 * Admin-Listenspalten rendern
	 *
	 * @param string $column  Spalten-Name
	 * @param int    $post_id Post-ID
	 */
	public function render_admin_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'lbite_res_date':
				$lbite_date = get_post_meta( $post_id, '_lbite_reservation_date', true );
				echo $lbite_date ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $lbite_date ) ) ) : '—';
				break;
			case 'lbite_res_time':
				echo esc_html( get_post_meta( $post_id, '_lbite_reservation_time', true ) ?: '—' );
				break;
			case 'lbite_res_guests':
				echo esc_html( get_post_meta( $post_id, '_lbite_reservation_guests', true ) ?: '—' );
				break;
			case 'lbite_res_table':
				$lbite_table = get_post( get_post_meta( $post_id, '_lbite_table_id', true ) );
				echo $lbite_table ? esc_html( $lbite_table->post_title ) : '—';
				break;
			case 'lbite_res_location':
				$lbite_loc = get_post( get_post_meta( $post_id, '_lbite_location_id', true ) );
				echo $lbite_loc ? esc_html( $lbite_loc->post_title ) : '—';
				break;
			case 'lbite_res_status':
				$lbite_status = get_post_meta( $post_id, '_lbite_reservation_status', true ) ?: 'pending';
				$lbite_label  = self::STATUSES[ $lbite_status ] ?? $lbite_status;
				$lbite_colors = array(
					'pending'   => '#f39c12',
					'confirmed' => '#27ae60',
					'cancelled' => '#e74c3c',
					'completed' => '#3498db',
				);
				$lbite_color = $lbite_colors[ $lbite_status ] ?? '#999';
				printf(
					'<span style="background:%s;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;">%s</span>',
					esc_attr( $lbite_color ),
					esc_html( $lbite_label )
				);
				break;
		}
	}

	/**
	 * Admin-Filter (Standort + Status) hinzufügen
	 */
	public function add_admin_filters() {
		global $typenow;
		if ( self::POST_TYPE !== $typenow ) {
			return;
		}

		// Standort-Filter
		$lbite_locations = get_posts(
			array(
				'post_type'      => 'lbite_location',
				'posts_per_page' => 100,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lese-Parameter zur Anzeige des vorselektierten Filterwerts; kein DB-Schreibzugriff.
		$lbite_sel_loc = isset( $_GET['lbite_res_location'] ) ? intval( $_GET['lbite_res_location'] ) : 0;
		echo '<select name="lbite_res_location">';
		echo '<option value="">' . esc_html__( 'All Locations', 'libre-bite' ) . '</option>';
		foreach ( $lbite_locations as $lbite_loc ) {
			printf(
				'<option value="%d"%s>%s</option>',
				esc_attr( $lbite_loc->ID ),
				selected( $lbite_sel_loc, $lbite_loc->ID, false ),
				esc_html( $lbite_loc->post_title )
			);
		}
		echo '</select>';

		// Status-Filter
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lese-Parameter zur Anzeige des vorselektierten Filterwerts; kein DB-Schreibzugriff.
		$lbite_sel_status = isset( $_GET['lbite_res_status'] ) ? sanitize_key( $_GET['lbite_res_status'] ) : '';
		echo '<select name="lbite_res_status">';
		echo '<option value="">' . esc_html__( 'All Status', 'libre-bite' ) . '</option>';
		foreach ( self::STATUSES as $lbite_val => $lbite_label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $lbite_val ),
				selected( $lbite_sel_status, $lbite_val, false ),
				esc_html( $lbite_label )
			);
		}
		echo '</select>';
	}

	/**
	 * Admin-Filter auf Query anwenden
	 *
	 * @param WP_Query $query Aktuelle Query
	 */
	public function apply_admin_filters( $query ) {
		global $pagenow;
		if ( ! is_admin() || 'edit.php' !== $pagenow ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lese-Parameter für WP-Admin-Filter (pre_get_posts); kein schreibender DB-Zugriff.
		if ( ! isset( $_GET['post_type'] ) || self::POST_TYPE !== sanitize_key( $_GET['post_type'] ) ) {
			return;
		}

		$lbite_meta_query = array();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lese-Parameter für WP-Admin-Filter (pre_get_posts); kein schreibender DB-Zugriff.
		if ( ! empty( $_GET['lbite_res_location'] ) ) {
			$lbite_meta_query[] = array(
				'key'   => '_lbite_location_id',
				'value' => intval( $_GET['lbite_res_location'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lese-Parameter für WP-Admin-Filter (pre_get_posts); kein schreibender DB-Zugriff.
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lese-Parameter für WP-Admin-Filter (pre_get_posts); kein schreibender DB-Zugriff.
		if ( ! empty( $_GET['lbite_res_status'] ) ) {
			$lbite_meta_query[] = array(
				'key'   => '_lbite_reservation_status',
				'value' => sanitize_key( $_GET['lbite_res_status'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lese-Parameter für WP-Admin-Filter (pre_get_posts); kein schreibender DB-Zugriff.
			);
		}

		if ( ! empty( $lbite_meta_query ) ) {
			$query->set( 'meta_query', $lbite_meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}
	}

	/**
	 * Frontend-Assets laden
	 */
	public function enqueue_frontend_assets() {
		// Assets nur auf Einzelseiten laden, die den Shortcode enthalten.
		if ( ! is_singular() || ! has_shortcode( get_post()->post_content ?? '', 'lbite_reservation_form' ) ) {
			return;
		}

		wp_enqueue_style(
			'lbite-reservation-form',
			LBITE_PLUGIN_URL . 'assets/css/reservation-form.css',
			array(),
			LBITE_VERSION
		);

		wp_enqueue_script(
			'lbite-reservation-form',
			LBITE_PLUGIN_URL . 'assets/js/reservation-form.js',
			array( 'jquery' ),
			LBITE_VERSION,
			true
		);

		wp_localize_script(
			'lbite-reservation-form',
			'lbiteReservation',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'lbite_reservation_form' ),
				'strings' => array(
					'sending'     => __( 'Sending…', 'libre-bite' ),
					'success'     => __( 'Your reservation request has been submitted successfully. We will contact you soon.', 'libre-bite' ),
					'error'       => __( 'Error sending. Please try again.', 'libre-bite' ),
					'loadTables'  => __( 'Loading tables…', 'libre-bite' ),
					'noTables'    => __( 'No tables available for this location.', 'libre-bite' ),
					'selectTable' => __( '— Select Table —', 'libre-bite' ),
				),
			)
		);
	}

	/**
	 * Shortcode: Reservierungsformular
	 *
	 * @param array $atts Shortcode-Attribute
	 * @return string HTML
	 */
	public function shortcode_reservation_form( $atts ) {
		$lbite_atts = shortcode_atts(
			array(
				'location_id' => 0,
			),
			$atts,
			'lbite_reservation_form'
		);

		$lbite_locations = get_posts(
			array(
				'post_type'      => 'lbite_location',
				'posts_per_page' => 100,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$lbite_preselected_location = intval( $lbite_atts['location_id'] );

		ob_start();
		include LBITE_PLUGIN_DIR . 'templates/frontend/reservation-form.php';
		return ob_get_clean();
	}

	/**
	 * AJAX: Tische für Standort laden
	 */
	public function ajax_get_reservation_tables() {
		check_ajax_referer( 'lbite_reservation_form', 'nonce' );

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;

		if ( ! $location_id ) {
			wp_send_json_error();
		}

		$lbite_tables = get_posts(
			array(
				'post_type'      => 'lbite_table',
				'posts_per_page' => 100,
				'post_status'    => 'publish',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_lbite_location_id',
						'value' => $location_id,
					),
				),
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		// Nach Reihenfolge sortieren (meta_key im Query würde Tische ohne Order-Meta ausschliessen).
		usort(
			$lbite_tables,
			function ( $a, $b ) {
				$order_a = intval( get_post_meta( $a->ID, '_lbite_table_order', true ) );
				$order_b = intval( get_post_meta( $b->ID, '_lbite_table_order', true ) );
				$order_a = $order_a > 0 ? $order_a : PHP_INT_MAX;
				$order_b = $order_b > 0 ? $order_b : PHP_INT_MAX;
				return $order_a - $order_b;
			}
		);

		$lbite_result = array();
		foreach ( $lbite_tables as $lbite_table ) {
			$lbite_seats      = intval( get_post_meta( $lbite_table->ID, '_lbite_table_seats', true ) );
			$lbite_result[] = array(
				'id'    => $lbite_table->ID,
				'title' => $lbite_table->post_title,
				'seats' => $lbite_seats,
			);
		}

		wp_send_json_success( array( 'tables' => $lbite_result ) );
	}

	/**
	 * AJAX: Reservierungsformular einreichen
	 */
	public function ajax_submit_reservation() {
		check_ajax_referer( 'lbite_reservation_form', 'nonce' );

		// Eingaben sanitisieren
		$lbite_location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;
		$lbite_table_id    = isset( $_POST['table_id'] ) ? intval( wp_unslash( $_POST['table_id'] ) ) : 0;
		$lbite_date        = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
		$lbite_time        = isset( $_POST['time'] ) ? sanitize_text_field( wp_unslash( $_POST['time'] ) ) : '';
		$lbite_guests      = isset( $_POST['guests'] ) ? intval( wp_unslash( $_POST['guests'] ) ) : 0;
		$lbite_name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$lbite_email       = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$lbite_phone       = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$lbite_notes       = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

		// Pflichtfelder prüfen
		if ( ! $lbite_location_id || ! $lbite_date || ! $lbite_time || ! $lbite_guests || ! $lbite_name || ! $lbite_email ) {
			wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'libre-bite' ) ) );
		}

		// Datum nicht in der Vergangenheit
		if ( strtotime( $lbite_date ) < strtotime( 'today' ) ) {
			wp_send_json_error( array( 'message' => __( 'The date cannot be in the past.', 'libre-bite' ) ) );
		}

		// E-Mail prüfen
		if ( ! is_email( $lbite_email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'libre-bite' ) ) );
		}

		// Tisch-Kapazität prüfen
		if ( $lbite_table_id ) {
			$lbite_seats = intval( get_post_meta( $lbite_table_id, '_lbite_table_seats', true ) );
			if ( $lbite_seats > 0 && $lbite_guests > $lbite_seats ) {
				wp_send_json_error(
					array(
						/* translators: %d: number of seats */
						'message' => sprintf( __( 'This table has a maximum of %d seats.', 'libre-bite' ), $lbite_seats ),
					)
				);
			}
		}

		// Reservierung erstellen
		$lbite_location  = get_post( $lbite_location_id );
		$lbite_post_title = sprintf(
			'%s – %s %s',
			$lbite_name,
			$lbite_date,
			$lbite_time
		);

		$lbite_post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_title'  => $lbite_post_title,
				'post_status' => 'publish',
			)
		);

		if ( is_wp_error( $lbite_post_id ) || ! $lbite_post_id ) {
			wp_send_json_error( array( 'message' => __( 'Error creating reservation.', 'libre-bite' ) ) );
		}

		// Meta speichern + Badge-Cache invalidieren
		delete_transient( 'lbite_pending_reservations_count' );
		update_post_meta( $lbite_post_id, '_lbite_reservation_status', 'pending' );
		update_post_meta( $lbite_post_id, '_lbite_location_id', $lbite_location_id );
		update_post_meta( $lbite_post_id, '_lbite_table_id', $lbite_table_id );
		update_post_meta( $lbite_post_id, '_lbite_reservation_date', $lbite_date );
		update_post_meta( $lbite_post_id, '_lbite_reservation_time', $lbite_time );
		update_post_meta( $lbite_post_id, '_lbite_reservation_guests', $lbite_guests );
		update_post_meta( $lbite_post_id, '_lbite_reservation_name', $lbite_name );
		update_post_meta( $lbite_post_id, '_lbite_reservation_email', $lbite_email );
		update_post_meta( $lbite_post_id, '_lbite_reservation_phone', $lbite_phone );
		update_post_meta( $lbite_post_id, '_lbite_reservation_notes', $lbite_notes );

		$lbite_data = compact(
			'lbite_location_id',
			'lbite_table_id',
			'lbite_date',
			'lbite_time',
			'lbite_guests',
			'lbite_name',
			'lbite_email',
			'lbite_phone',
			'lbite_notes'
		);
		$lbite_data['lbite_location_name'] = $lbite_location ? $lbite_location->post_title : '';
		$lbite_data['lbite_table_name']    = $lbite_table_id ? get_post( $lbite_table_id )->post_title ?? '' : '';

		// E-Mails senden
		$this->send_confirmation_email( $lbite_post_id, $lbite_data );
		$this->send_admin_notification( $lbite_post_id, $lbite_data );

		wp_send_json_success(
			array( 'message' => __( 'Reservation request successfully submitted!', 'libre-bite' ) )
		);
	}

	/**
	 * Bestätigungsmail an Kunden senden
	 *
	 * @param int   $reservation_id Reservierungs-Post-ID
	 * @param array $data           Reservierungsdaten
	 */
	private function send_confirmation_email( $reservation_id, $data ) {
		$lbite_site_name = get_bloginfo( 'name' );
		$lbite_to        = $data['lbite_email'];

		/* translators: %s: Website-Name */
		$lbite_subject = sprintf( __( 'Your reservation request at %s', 'libre-bite' ), $lbite_site_name );

		/* translators: %s: Name des Gastes */
		$lbite_message  = sprintf( __( 'Hello %s,', 'libre-bite' ), $data['lbite_name'] ) . "\n\n";
		$lbite_message .= __( 'Your reservation request has been received. We will contact you shortly to confirm the reservation.', 'libre-bite' ) . "\n\n";
		$lbite_message .= "---\n";
		/* translators: %s: Standortname */
		$lbite_message .= sprintf( __( 'Location: %s', 'libre-bite' ), $data['lbite_location_name'] ) . "\n";
		if ( $data['lbite_table_name'] ) {
			/* translators: %s: Tischname */
			$lbite_message .= sprintf( __( 'Table: %s', 'libre-bite' ), $data['lbite_table_name'] ) . "\n";
		}
		/* translators: %s: Datum */
		$lbite_message .= sprintf( __( 'Date: %s', 'libre-bite' ), date_i18n( get_option( 'date_format' ), strtotime( $data['lbite_date'] ) ) ) . "\n";
		/* translators: %s: Uhrzeit */
		$lbite_message .= sprintf( __( 'Time: %s', 'libre-bite' ), $data['lbite_time'] ) . "\n";
		/* translators: %d: Anzahl Personen */
		$lbite_message .= sprintf( __( 'Guests: %d', 'libre-bite' ), $data['lbite_guests'] ) . "\n";
		if ( $data['lbite_notes'] ) {
			/* translators: %s: Notiz */
			$lbite_message .= sprintf( __( 'Note: %s', 'libre-bite' ), $data['lbite_notes'] ) . "\n";
		}
		$lbite_message .= "---\n\n";
		$lbite_message .= $lbite_site_name;

		$lbite_headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		wp_mail( $lbite_to, $lbite_subject, $lbite_message, $lbite_headers );
	}

	/**
	 * Admin-Benachrichtigung senden
	 *
	 * @param int   $reservation_id Reservierungs-Post-ID
	 * @param array $data           Reservierungsdaten
	 */
	private function send_admin_notification( $reservation_id, $data ) {
		$lbite_site_name = get_bloginfo( 'name' );
		$lbite_to        = get_option( 'admin_email' );

		/* translators: %s: Website-Name */
		$lbite_subject = sprintf( __( 'New Reservation Request – %s', 'libre-bite' ), $lbite_site_name );

		$lbite_admin_url = admin_url( 'post.php?post=' . $reservation_id . '&action=edit' );

		$lbite_message  = __( 'A new reservation request has been received:', 'libre-bite' ) . "\n\n";
		/* translators: %s: Kundenname */
		$lbite_message .= sprintf( __( 'Customer: %s', 'libre-bite' ), $data['lbite_name'] ) . "\n";
		/* translators: %s: E-Mail */
		$lbite_message .= sprintf( __( 'Email: %s', 'libre-bite' ), $data['lbite_email'] ) . "\n";
		if ( $data['lbite_phone'] ) {
			/* translators: %s: Telefon */
			$lbite_message .= sprintf( __( 'Phone: %s', 'libre-bite' ), $data['lbite_phone'] ) . "\n";
		}
		$lbite_message .= "---\n";
		/* translators: %s: Standortname */
		$lbite_message .= sprintf( __( 'Location: %s', 'libre-bite' ), $data['lbite_location_name'] ) . "\n";
		if ( $data['lbite_table_name'] ) {
			/* translators: %s: Tischname */
			$lbite_message .= sprintf( __( 'Table: %s', 'libre-bite' ), $data['lbite_table_name'] ) . "\n";
		}
		/* translators: %s: Datum */
		$lbite_message .= sprintf( __( 'Date: %s', 'libre-bite' ), date_i18n( get_option( 'date_format' ), strtotime( $data['lbite_date'] ) ) ) . "\n";
		/* translators: %s: Uhrzeit */
		$lbite_message .= sprintf( __( 'Time: %s', 'libre-bite' ), $data['lbite_time'] ) . "\n";
		/* translators: %d: Anzahl Personen */
		$lbite_message .= sprintf( __( 'Guests: %d', 'libre-bite' ), $data['lbite_guests'] ) . "\n";
		if ( $data['lbite_notes'] ) {
			/* translators: %s: Notiz */
			$lbite_message .= sprintf( __( 'Note: %s', 'libre-bite' ), $data['lbite_notes'] ) . "\n";
		}
		$lbite_message .= "---\n\n";
		/* translators: %s: Admin-URL */
		$lbite_message .= sprintf( __( 'Edit Reservation: %s', 'libre-bite' ), $lbite_admin_url );

		$lbite_headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		wp_mail( $lbite_to, $lbite_subject, $lbite_message, $lbite_headers );
	}
}
