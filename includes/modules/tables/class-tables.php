<?php
/**
 * Tischverwaltung (Kellner-Modus)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tables-Modul
 */
class LBite_Tables {

	/**
	 * Loader-Instanz
	 *
	 * @var LBite_Loader
	 */
	private $loader;

	/**
	 * Post-Type Name
	 *
	 * @var string
	 */
	const POST_TYPE = 'lbite_table';

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
		$this->loader->add_action( 'init', $this, 'register_post_type' );
		$this->loader->add_action( 'add_meta_boxes', $this, 'add_meta_boxes' );
		$this->loader->add_action( 'save_post_' . self::POST_TYPE, $this, 'save_table_meta' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_table_assets' );

		// Admin-Spalten
		$this->loader->add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', $this, 'add_admin_columns' );
		$this->loader->add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', $this, 'render_admin_columns', 10, 2 );

		// Frontend: URL Parameter verarbeiten
		$this->loader->add_action( 'template_redirect', $this, 'process_table_url_parameters' );

		// Bestellung: Tisch-ID speichern
		$this->loader->add_action( 'woocommerce_checkout_create_order', $this, 'add_table_meta_to_order', 10, 2 );

		// Dashboard: Tisch-Name anzeigen
		$this->loader->add_filter( 'lbite_dashboard_order_data', $this, 'add_table_name_to_dashboard_data', 10, 2 );

		// Checkout: Tisch-Info anzeigen
		$this->loader->add_action( 'woocommerce_before_checkout_form', $this, 'render_table_checkout_info' );
	}

	/**
	 * Assets für Tischverwaltungs-Seite laden
	 *
	 * @param string $hook Aktuelle Admin-Seite
	 */
	public function enqueue_table_assets( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'lbite-admin-tables',
			LBITE_PLUGIN_URL . 'assets/css/admin-tables.css',
			array(),
			LBITE_VERSION
		);

		wp_enqueue_script(
			'lbite-admin-tables',
			LBITE_PLUGIN_URL . 'assets/js/admin-tables.js',
			array(),
			LBITE_VERSION,
			true
		);

		wp_localize_script(
			'lbite-admin-tables',
			'lbiteTableData',
			array(
				'scanText' => __( 'Hier scannen zum Bestellen', 'libre-bite' ),
			)
		);
	}

	/**
	 * Tisch-Info im Checkout anzeigen
	 */
	public function render_table_checkout_info() {
		if ( ! WC()->session ) {
			return;
		}

		$table_id = WC()->session->get( 'lbite_table_id' );
		if ( ! $table_id ) {
			return;
		}

		$table = get_post( $table_id );
		if ( ! $table ) {
			return;
		}

		?>
		<div class="lbite-table-info-box">
			<div class="lbite-table-info-flex">
				<span class="dashicons dashicons-grid-view lbite-table-icon"></span>
				<div>
					<strong><?php esc_html_e( 'Bestellung am Tisch', 'libre-bite' ); ?></strong><br>
					<?php 
					/* translators: %s: table name */
					printf( esc_html__( 'Sie bestellen aktuell für %s.', 'libre-bite' ), '<strong>' . esc_html( $table->post_title ) . '</strong>' ); 
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * URL Parameter verarbeiten (QR-Code Link)
	 */
	public function process_table_url_parameters() {
		if ( is_admin() ) {
			return;
		}

		$location_id = isset( $_GET['lbite_location'] ) ? intval( wp_unslash( $_GET['lbite_location'] ) ) : 0;
		$table_id    = isset( $_GET['lbite_table'] ) ? intval( wp_unslash( $_GET['lbite_table'] ) ) : 0;

		if ( ! $location_id ) {
			return;
		}

		// Session initialisieren falls nötig.
		if ( WC()->session && ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		if ( WC()->session ) {
			WC()->session->set( 'lbite_location_id', $location_id );
			
			if ( $table_id ) {
				WC()->session->set( 'lbite_table_id', $table_id );
				// Bei Tischbestellung erzwingen wir meist "Sofort"
				WC()->session->set( 'lbite_order_type', 'now' );
			}
		}
	}

	/**
	 * Tisch-ID zur Bestellung hinzufügen
	 */
	public function add_table_meta_to_order( $order, $data ) {
		if ( ! WC()->session ) {
			return;
		}

		$table_id = WC()->session->get( 'lbite_table_id' );
		if ( $table_id ) {
			$order->update_meta_data( '_lbite_table_id', $table_id );
			
			$table = get_post( $table_id );
			if ( $table ) {
				$order->update_meta_data( '_lbite_table_name', $table->post_title );
			}
			
			// Nach Bestellung aus Session entfernen
			WC()->session->__unset( 'lbite_table_id' );
		}
	}

	/**
	 * Tisch-Name für Dashboard-Daten hinzufügen
	 */
	public function add_table_name_to_dashboard_data( $data, $order ) {
		$table_name = $order->get_meta( '_lbite_table_name', true );
		if ( $table_name ) {
			$data['customer'] .= ' (Tisch: ' . $table_name . ')';
		}
		return $data;
	}

	/**
	 * Custom Post Type registrieren
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Tische', 'libre-bite' ),
			'singular_name'      => __( 'Tisch', 'libre-bite' ),
			'menu_name'          => __( 'Tische', 'libre-bite' ),
			'add_new'            => __( 'Neuer Tisch', 'libre-bite' ),
			'add_new_item'       => __( 'Neuen Tisch hinzufügen', 'libre-bite' ),
			'edit_item'          => __( 'Tisch bearbeiten', 'libre-bite' ),
			'new_item'           => __( 'Neuer Tisch', 'libre-bite' ),
			'view_item'          => __( 'Tisch ansehen', 'libre-bite' ),
			'search_items'       => __( 'Tische suchen', 'libre-bite' ),
			'not_found'          => __( 'Keine Tische gefunden', 'libre-bite' ),
			'not_found_in_trash' => __( 'Keine Tische im Papierkorb', 'libre-bite' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'query_var'           => true,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'supports'            => array( 'title' ),
			'menu_icon'           => 'dashicons-grid-view',
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Meta-Boxen hinzufügen
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'lbite_table_details',
			__( 'Tisch-Details', 'libre-bite' ),
			array( $this, 'render_table_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Tisch-Meta-Box rendern
	 *
	 * @param WP_Post $post Post-Objekt
	 */
	public function render_table_meta_box( $post ) {
		wp_nonce_field( 'lbite_save_table', 'lbite_table_nonce' );

		$location_id = get_post_meta( $post->ID, '_lbite_location_id', true );
		$locations   = get_posts( array(
			'post_type'      => 'lbite_location',
			'posts_per_page' => 100,
			'post_status'    => 'publish',
		) );
		?>
		<table class="form-table">
			<tr>
				<th><label for="lbite_location_id"><?php esc_html_e( 'Standort', 'libre-bite' ); ?></label></th>
				<td>
					<select name="lbite_location_id" id="lbite_location_id" class="regular-text">
						<option value=""><?php esc_html_e( 'Bitte wählen...', 'libre-bite' ); ?></option>
						<?php foreach ( $locations as $loc ) : ?>
							<option value="<?php echo esc_attr( $loc->ID ); ?>" <?php selected( $location_id, $loc->ID ); ?>>
								<?php echo esc_html( $loc->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Zu welchem Standort gehört dieser Tisch?', 'libre-bite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'QR-Code Link', 'libre-bite' ); ?></label></th>
				<td>
					<?php if ( $location_id ) : 
						$url = add_query_arg( array(
							'lbite_location' => $location_id,
							'lbite_table'    => $post->ID
						), home_url() );
					?>
						<div class="lbite-qr-meta-url">
							<input type="text" value="<?php echo esc_url( $url ); ?>" class="large-text" readonly onclick="this.select();">
						</div>
						
						<div class="lbite-qr-display">
							<?php
							$qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode( $url );
							?>
							<img src="<?php echo esc_url( $qr_url ); ?>" alt="QR Code">
						</div>
						<p class="description">
							<?php esc_html_e( 'Diesen Link oder QR-Code können Sie für den Tisch verwenden.', 'libre-bite' ); ?><br>
							<a href="<?php echo esc_url( $qr_url ); ?>&format=png" target="_blank" download="qr-table-<?php echo esc_attr( $post->ID ); ?>.png" class="button"><?php esc_html_e( 'QR-Code herunterladen', 'libre-bite' ); ?></a>
							<button type="button" class="button lbite-print-qr-btn" data-title="<?php echo esc_attr( $post->post_title ); ?>" data-qr="<?php echo esc_url( $qr_url ); ?>">
								<?php esc_html_e( 'QR-Code drucken', 'libre-bite' ); ?>
							</button>
						</p>

					<?php else : ?>
						<p class="description"><?php esc_html_e( 'Bitte wählen Sie zuerst einen Standort und speichern Sie, um den Link zu generieren.', 'libre-bite' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Tisch-Meta speichern
	 *
	 * @param int $post_id Post-ID
	 */
	public function save_table_meta( $post_id ) {
		if ( ! isset( $_POST['lbite_table_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lbite_table_nonce'] ) ), 'lbite_save_table' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['lbite_location_id'] ) ) {
			update_post_meta( $post_id, '_lbite_location_id', intval( wp_unslash( $_POST['lbite_location_id'] ) ) );
		}
	}

	/**
	 * Admin-Spalten hinzufügen
	 */
	public function add_admin_columns( $columns ) {
		$new_columns = array();
		$new_columns['cb']       = $columns['cb'];
		$new_columns['title']    = $columns['title'];
		$new_columns['location'] = __( 'Standort', 'libre-bite' );
		$new_columns['date']     = $columns['date'];

		return $new_columns;
	}

	/**
	 * Admin-Spalten rendern
	 */
	public function render_admin_columns( $column, $post_id ) {
		if ( 'location' === $column ) {
			$location_id = get_post_meta( $post_id, '_lbite_location_id', true );
			if ( $location_id ) {
				echo esc_html( get_the_title( $location_id ) );
			} else {
				echo '—';
			}
		}
	}
}
