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

		// Standort-Filter in Tischliste
		$this->loader->add_action( 'restrict_manage_posts', $this, 'filter_tables_by_location' );
		$this->loader->add_action( 'parse_query', $this, 'apply_table_location_filter' );

		// Link "Mehrere Tische erstellen" in Tischliste
		$this->loader->add_filter( 'views_edit-' . self::POST_TYPE, $this, 'add_bulk_create_link' );

		// Admin-Menü: Schnell-Erstellungsseite
		$this->loader->add_action( 'admin_menu', $this, 'register_admin_pages' );

		// Formular-Handler: Schnell-Erstellung
		$this->loader->add_action( 'admin_post_lbite_bulk_create_tables', $this, 'handle_bulk_create' );

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

		// Öffentlicher QR-Code-Deeplink: keine Nonce möglich, da der Link extern geteilt wird (QR-Codes, Schilder).
		// Werte werden ausschliesslich in der Session gespeichert (kein direkter DB-Write), daher kein Sicherheitsrisiko.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$location_id = isset( $_GET['lbite_location'] ) ? intval( wp_unslash( $_GET['lbite_location'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$table_id    = isset( $_GET['lbite_table'] ) ? intval( wp_unslash( $_GET['lbite_table'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

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
		$seats       = get_post_meta( $post->ID, '_lbite_table_seats', true );
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
				<th><label for="lbite_table_seats"><?php esc_html_e( 'Sitzplätze', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" name="lbite_table_seats" id="lbite_table_seats" value="<?php echo esc_attr( $seats ); ?>" min="1" class="small-text">
					<p class="description"><?php esc_html_e( 'Anzahl Sitzplätze an diesem Tisch (optional, für Reservationen).', 'libre-bite' ); ?></p>
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

		if ( isset( $_POST['lbite_table_seats'] ) ) {
			$seats = absint( wp_unslash( $_POST['lbite_table_seats'] ) );
			if ( $seats > 0 ) {
				update_post_meta( $post_id, '_lbite_table_seats', $seats );
			} else {
				delete_post_meta( $post_id, '_lbite_table_seats' );
			}
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
		$new_columns['seats']    = __( 'Sitzplätze', 'libre-bite' );
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

		if ( 'seats' === $column ) {
			$seats = get_post_meta( $post_id, '_lbite_table_seats', true );
			echo $seats ? esc_html( $seats ) : '—';
		}
	}

	/**
	 * Standort-Filter in Tischliste anzeigen
	 */
	public function filter_tables_by_location() {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-' . self::POST_TYPE !== $screen->id ) {
			return;
		}

		$locations = get_posts( array(
			'post_type'      => 'lbite_location',
			'posts_per_page' => 100,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		if ( empty( $locations ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lese-Parameter für Filter.
		$selected = isset( $_GET['lbite_location_filter'] ) ? intval( $_GET['lbite_location_filter'] ) : 0;
		?>
		<select name="lbite_location_filter">
			<option value=""><?php esc_html_e( 'Alle Standorte', 'libre-bite' ); ?></option>
			<?php foreach ( $locations as $loc ) : ?>
				<option value="<?php echo esc_attr( $loc->ID ); ?>" <?php selected( $selected, $loc->ID ); ?>>
					<?php echo esc_html( $loc->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Standort-Filter auf Query anwenden
	 *
	 * @param WP_Query $query Query-Objekt.
	 */
	public function apply_table_location_filter( $query ) {
		global $pagenow;

		if ( ! is_admin() || 'edit.php' !== $pagenow ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lese-Parameter für Filter.
		if ( ! isset( $_GET['post_type'] ) || self::POST_TYPE !== sanitize_key( $_GET['post_type'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lese-Parameter für Filter.
		$location_id = isset( $_GET['lbite_location_filter'] ) ? intval( $_GET['lbite_location_filter'] ) : 0;

		if ( ! $location_id ) {
			return;
		}

		$meta_query   = $query->get( 'meta_query' ) ?: array();
		$meta_query[] = array(
			'key'   => '_lbite_location_id',
			'value' => $location_id,
		);
		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Link "Mehrere Tische erstellen" in Tischliste
	 *
	 * @param array $views Views.
	 * @return array
	 */
	public function add_bulk_create_link( $views ) {
		$url = admin_url( 'admin.php?page=lbite-table-bulk-create' );
		$views['bulk_create'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Mehrere Tische erstellen', 'libre-bite' ) . '</a>';
		return $views;
	}

	/**
	 * Admin-Seite für Schnell-Erstellung registrieren
	 */
	public function register_admin_pages() {
		if ( ! lbite_feature_enabled( 'enable_table_ordering' ) ) {
			return;
		}

		// Seite registrieren, aber aus dem sichtbaren Menü entfernen.
		// Zugänglich via Tischliste-Ansicht (add_bulk_create_link).
		add_submenu_page(
			'libre-bite',
			__( 'Mehrere Tische erstellen', 'libre-bite' ),
			__( 'Mehrere Tische erstellen', 'libre-bite' ),
			'lbite_manage_locations',
			'lbite-table-bulk-create',
			array( $this, 'render_bulk_create_page' )
		);
		remove_submenu_page( 'libre-bite', 'lbite-table-bulk-create' );
	}

	/**
	 * Schnell-Erstellungsseite rendern
	 */
	public function render_bulk_create_page() {
		if ( ! current_user_can( 'lbite_manage_locations' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'libre-bite' ) );
		}

		$locations = get_posts( array(
			'post_type'      => 'lbite_location',
			'posts_per_page' => 100,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		// Erfolgs-/Fehlermeldung anzeigen.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lese-Parameter für Meldung.
		$created = isset( $_GET['lbite_created'] ) ? intval( $_GET['lbite_created'] ) : 0;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Mehrere Tische erstellen', 'libre-bite' ); ?></h1>

			<?php if ( $created > 0 ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						/* translators: %d: Anzahl erstellter Tische */
						printf( esc_html__( '%d Tische wurden erfolgreich erstellt.', 'libre-bite' ), $created );
						?>
					</p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lbite_bulk_create_tables">
				<?php wp_nonce_field( 'lbite_bulk_create_tables', 'lbite_bulk_create_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th><label for="lbite_bulk_location"><?php esc_html_e( 'Standort', 'libre-bite' ); ?></label></th>
						<td>
							<select name="lbite_bulk_location" id="lbite_bulk_location" required>
								<option value=""><?php esc_html_e( 'Bitte wählen...', 'libre-bite' ); ?></option>
								<?php foreach ( $locations as $loc ) : ?>
									<option value="<?php echo esc_attr( $loc->ID ); ?>">
										<?php echo esc_html( $loc->post_title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="lbite_bulk_prefix"><?php esc_html_e( 'Präfix', 'libre-bite' ); ?></label></th>
						<td>
							<input type="text" name="lbite_bulk_prefix" id="lbite_bulk_prefix" value="<?php esc_attr_e( 'Tisch', 'libre-bite' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Präfix für den Tischnamen, z.B. "Tisch" → "Tisch 1", "Tisch 2" …', 'libre-bite' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Nummerierung', 'libre-bite' ); ?></th>
						<td>
							<label for="lbite_bulk_from"><?php esc_html_e( 'Von', 'libre-bite' ); ?></label>
							<input type="number" name="lbite_bulk_from" id="lbite_bulk_from" value="1" min="1" class="small-text" required>
							&nbsp;
							<label for="lbite_bulk_to"><?php esc_html_e( 'Bis', 'libre-bite' ); ?></label>
							<input type="number" name="lbite_bulk_to" id="lbite_bulk_to" value="10" min="1" max="50" class="small-text" required>
							<p class="description"><?php esc_html_e( 'Maximal 50 Tische auf einmal erstellen.', 'libre-bite' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="lbite_bulk_seats"><?php esc_html_e( 'Sitzplätze', 'libre-bite' ); ?></label></th>
						<td>
							<input type="number" name="lbite_bulk_seats" id="lbite_bulk_seats" value="" min="1" class="small-text" placeholder="—">
							<p class="description"><?php esc_html_e( 'Optional: Gleiche Sitzplatzanzahl für alle Tische.', 'libre-bite' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Tische erstellen', 'libre-bite' ) ); ?>
			</form>

			<p>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lbite_table' ) ); ?>">
					&larr; <?php esc_html_e( 'Zurück zur Tischliste', 'libre-bite' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Schnell-Erstellung verarbeiten
	 */
	public function handle_bulk_create() {
		if ( ! isset( $_POST['lbite_bulk_create_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lbite_bulk_create_nonce'] ) ), 'lbite_bulk_create_tables' ) ) {
			wp_die( esc_html__( 'Sicherheitsprüfung fehlgeschlagen.', 'libre-bite' ) );
		}

		if ( ! current_user_can( 'lbite_manage_locations' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Aktion.', 'libre-bite' ) );
		}

		$location_id = isset( $_POST['lbite_bulk_location'] ) ? intval( wp_unslash( $_POST['lbite_bulk_location'] ) ) : 0;
		$prefix      = isset( $_POST['lbite_bulk_prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_bulk_prefix'] ) ) : 'Tisch';
		$from        = isset( $_POST['lbite_bulk_from'] ) ? intval( wp_unslash( $_POST['lbite_bulk_from'] ) ) : 1;
		$to          = isset( $_POST['lbite_bulk_to'] ) ? intval( wp_unslash( $_POST['lbite_bulk_to'] ) ) : 10;
		$seats       = isset( $_POST['lbite_bulk_seats'] ) ? absint( wp_unslash( $_POST['lbite_bulk_seats'] ) ) : 0;

		if ( ! $location_id ) {
			wp_safe_redirect( admin_url( 'admin.php?page=lbite-table-bulk-create' ) );
			exit;
		}

		// Sicherheits-Limit: max. 50 Tische.
		$to   = min( $to, $from + 49 );
		$from = max( 1, $from );

		$created = 0;
		for ( $i = $from; $i <= $to; $i++ ) {
			$post_id = wp_insert_post( array(
				'post_type'   => self::POST_TYPE,
				'post_title'  => $prefix . ' ' . $i,
				'post_status' => 'publish',
			) );

			if ( $post_id && ! is_wp_error( $post_id ) ) {
				update_post_meta( $post_id, '_lbite_location_id', $location_id );
				if ( $seats > 0 ) {
					update_post_meta( $post_id, '_lbite_table_seats', $seats );
				}
				$created++;
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=lbite-table-bulk-create&lbite_created=' . $created ) );
		exit;
	}
}
