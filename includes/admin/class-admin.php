<?php
/**
 * Admin-Bereich
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin-Klasse
 */
class LBite_Admin {

	/**
	 * Loader-Instanz
	 *
	 * @var LBite_Loader
	 */
	private $loader;

	/**
	 * Admin-Einstellungen-Instanz
	 *
	 * @var LBite_Admin_Settings
	 */
	private $admin_settings;

	/**
	 * Konstruktor
	 *
	 * @param LBite_Loader $loader Loader-Instanz
	 */
	public function __construct( $loader ) {
		$this->loader = $loader;
		$this->init_roles();
		$this->init_admin_settings();
		$this->init_hooks();
	}

	/**
	 * Rollen-Klasse laden
	 */
	private function init_roles() {
		require_once LBITE_PLUGIN_DIR . 'includes/admin/class-roles.php';
	}

	/**
	 * Admin-Einstellungen initialisieren
	 */
	private function init_admin_settings() {
		require_once LBITE_PLUGIN_DIR . 'includes/admin/class-admin-settings.php';
		$this->admin_settings = new LBite_Admin_Settings( $this->loader );
	}

	/**
	 * Hooks initialisieren
	 */
	private function init_hooks() {
		$this->loader->add_action( 'admin_menu', $this, 'add_admin_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_assets' );
		$this->loader->add_action( 'admin_init', $this, 'maybe_upgrade' );

		// Menü-Highlighting für CPT-Seiten
		$this->loader->add_filter( 'parent_file', $this, 'fix_menu_parent_file' );
		$this->loader->add_filter( 'submenu_file', $this, 'fix_menu_submenu_file' );

		// AJAX-Handler.
		$this->loader->add_action( 'wp_ajax_lbite_save_pos_location', $this, 'ajax_save_pos_location' );
		$this->loader->add_action( 'wp_ajax_lbite_pos_get_products', $this, 'ajax_pos_get_products' );
		$this->loader->add_action( 'wp_ajax_lbite_pos_get_product_details', $this, 'ajax_pos_get_product_details' );
		$this->loader->add_action( 'wp_ajax_lbite_pos_create_order', $this, 'ajax_pos_create_order' );
		$this->loader->add_action( 'wp_ajax_lbite_get_theme_colors', $this, 'ajax_get_theme_colors' );
		$this->loader->add_action( 'wp_ajax_lbite_save_features', $this, 'ajax_save_features' );
		$this->loader->add_action( 'wp_ajax_lbite_save_support_settings', $this, 'ajax_save_support_settings' );
		$this->loader->add_action( 'wp_ajax_lbite_get_location_tables', $this, 'ajax_get_location_tables' );
		$this->loader->add_action( 'wp_ajax_lbite_restart_onboarding', $this, 'ajax_restart_onboarding' );

		// Bestellungs-Counter im Menü-Badge (nach Menü-Aufbau)
		$this->loader->add_action( 'admin_menu', $this, 'inject_order_count_badge', 999 );

		// Support-Box im Admin-Footer
		$this->loader->add_action( 'admin_footer', $this, 'render_support_footer' );
	}

	/**
	 * Plugin-Upgrade bei Bedarf durchführen
	 */
	public function maybe_upgrade() {
		LBite_Installer::maybe_upgrade();
	}

	/**
	 * Admin-Menü hinzufügen
	 *
	 * Menüstruktur nach Benutzerrollen:
	 *
	 * PERSONAL (lbite_staff):
	 * - Dashboard, Bestellübersicht, Kassensystem, Hilfe
	 *
	 * ADMIN (lbite_admin):
	 * - Alle Personal-Menüs + Standorte, Produkt-Optionen, Checkout-Felder, Einstellungen
	 *
	 * SUPER-ADMIN (administrator):
	 * - Alle Admin-Menüs + Feature-Toggles, Admin-Einstellungen, Support-Einstellungen, Debug
	 */
	public function add_admin_menu() {
		// Prüfen ob Benutzer mindestens Staff-Zugriff hat
		if ( ! LBite_Roles::is_staff() ) {
			return;
		}

		// Angepasster Plugin-Name
		$plugin_name = apply_filters( 'lbite_plugin_display_name', __( 'Libre Bite', 'libre-bite' ) );
		$menu_name   = apply_filters( 'lbite_plugin_menu_name', __( 'Libre Bite', 'libre-bite' ) );

		// Hauptmenü - für alle OOS-Benutzer sichtbar
		add_menu_page(
			$plugin_name,
			$menu_name,
			'lbite_view_dashboard',
			'libre-bite',
			array( $this, 'render_dashboard_page' ),
			'dashicons-store',
			56
		);

		// ============================================
		// PERSONAL-BEREICH (lbite_staff)
		// ============================================

		// Dashboard
		add_submenu_page(
			'libre-bite',
			__( 'Dashboard', 'libre-bite' ),
			__( 'Dashboard', 'libre-bite' ),
			'lbite_view_dashboard',
			'libre-bite',
			array( $this, 'render_dashboard_page' )
		);

		// Bestellübersicht (Kanban) – nur wenn Feature aktiv
		if ( lbite_feature_enabled( 'enable_kanban_board' ) ) {
			add_submenu_page(
				'libre-bite',
				__( 'Bestellübersicht', 'libre-bite' ),
				__( 'Bestellübersicht', 'libre-bite' ),
				'lbite_view_orders',
				'lbite-order-board',
				array( $this, 'render_order_board_page' )
			);
		}

		// POS/Kassensystem – nur wenn Feature aktiv
		if ( lbite_feature_enabled( 'enable_pos' ) ) {
			add_submenu_page(
				'libre-bite',
				__( 'Kassensystem', 'libre-bite' ),
				__( 'Kassensystem', 'libre-bite' ),
				'lbite_use_pos',
				'lbite-pos',
				array( $this, 'render_pos_page' )
			);
		}

		// ============================================
		// ADMIN-BEREICH (administrator)
		// ============================================

		// Standorte (CPT) – nur wenn Standort-Feature aktiv
		if ( lbite_feature_enabled( 'enable_location_selector' ) || lbite_feature_enabled( 'enable_multi_location' ) ) {
			add_submenu_page(
				'libre-bite',
				__( 'Standorte', 'libre-bite' ),
				__( 'Standorte', 'libre-bite' ),
				'lbite_manage_locations',
				'edit.php?post_type=lbite_location'
			);
		}

		// Tische (CPT) + Tischplan + Reservierungen – nur wenn Tischbestellung aktiv
		if ( lbite_feature_enabled( 'enable_table_ordering' ) ) {
			add_submenu_page(
				'libre-bite',
				__( 'Tische', 'libre-bite' ),
				__( 'Tische', 'libre-bite' ),
				'lbite_manage_locations',
				'edit.php?post_type=lbite_table'
			);
			add_submenu_page(
				'libre-bite',
				__( 'Tischplan', 'libre-bite' ),
				__( 'Tischplan', 'libre-bite' ),
				'lbite_manage_locations',
				'lbite-floor-plan',
				array( $this, 'render_floor_plan_page' )
			);
			add_submenu_page(
				'libre-bite',
				__( 'Reservierungsübersicht', 'libre-bite' ),
				__( 'Reservierungen', 'libre-bite' ),
				'lbite_manage_options',
				'lbite-reservation-board',
				array( $this, 'render_reservation_board_page' )
			); // CPT-Liste via Link im Template erreichbar
		}

		// Produkt-Optionen (CPT) – unter WooCommerce/Produkte
		if ( lbite_feature_enabled( 'enable_product_options' ) ) {
			add_submenu_page(
				'edit.php?post_type=product',
				__( 'Produkt-Optionen', 'libre-bite' ),
				__( 'Produkt-Optionen', 'libre-bite' ),
				'lbite_manage_options',
				'edit.php?post_type=lbite_product_option'
			);
		}

		// Einstellungen (konsolidiert mit Tabs)
		add_submenu_page(
			'libre-bite',
			__( 'Einstellungen', 'libre-bite' ),
			__( 'Einstellungen', 'libre-bite' ),
			'lbite_manage_settings',
			'lbite-settings',
			array( $this, 'render_settings_page' )
		);

		// ============================================
		// DOKUMENTATION (rollenbasiert)
		// ============================================

		// Hilfe & Support - für alle sichtbar, Inhalt variiert nach Rolle
		add_submenu_page(
			'libre-bite',
			__( 'Hilfe & Support', 'libre-bite' ),
			__( 'Hilfe & Support', 'libre-bite' ),
			'lbite_view_dashboard',
			'lbite-help',
			array( $this, 'render_help_page' )
		);

		// Pricing / Upgrade (Nur wenn nicht Premium)
		if ( function_exists( 'lbite_freemius' ) && ! lbite_freemius()->is_premium() ) {
			add_submenu_page(
				'libre-bite',
				__( 'Upgrade auf Pro', 'libre-bite' ),
				'<span style="color: #f18500; font-weight: bold;">' . __( 'Pricing', 'libre-bite' ) . '</span>',
				'manage_options',
				'lbite-pricing',
				array( $this, 'render_pricing_page' )
			);
		}

	}

	/**
	 * Dashboard-Seite rendern
	 */
	public function render_dashboard_page() {
		include LBITE_PLUGIN_DIR . 'templates/admin/dashboard.php';
	}

	/**
	 * Bestellübersicht-Seite rendern
	 */
	public function render_order_board_page() {
		include LBITE_PLUGIN_DIR . 'templates/admin/order-board.php';
	}

	/**
	 * Reservierungsübersicht-Seite rendern
	 */
	public function render_reservation_board_page() {
		if ( ! current_user_can( 'lbite_manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'libre-bite' ) );
		}
		include LBITE_PLUGIN_DIR . 'templates/admin/reservation-board.php';
	}

	/**
	 * POS-Seite rendern
	 */
	public function render_pos_page() {
		include LBITE_PLUGIN_DIR . 'templates/admin/pos.php';
	}

	/**
	 * Einstellungen-Seite rendern (konsolidiert mit Tabs)
	 */
	public function render_settings_page() {
		include LBITE_PLUGIN_DIR . 'templates/admin/settings-tabbed.php';
	}

	/**
	 * Hilfe-Seite rendern (rollenbasiert)
	 */
	public function render_help_page() {
		if ( current_user_can( 'manage_options' ) ) {
			include LBITE_PLUGIN_DIR . 'templates/admin/help-admin.php';
		} else {
			include LBITE_PLUGIN_DIR . 'templates/admin/help-staff.php';
		}
	}

	/**
	 * Menü-Highlighting: Hauptmenü für LibreBite-CPTs aktiv halten.
	 *
	 * @param string $parent_file Aktueller Parent-File.
	 * @return string
	 */
	public function fix_menu_parent_file( $parent_file ) {
		global $post_type;
		// Reservierungen und Tisch-CPTs bleiben unter libre-bite.
		$lbite_libre_types = array( 'lbite_location', 'lbite_table', 'lbite_reservation' );
		if ( in_array( $post_type, $lbite_libre_types, true ) ) {
			return 'libre-bite';
		}
		// Produkt-Optionen bleibt unter WooCommerce/Produkte.
		if ( 'lbite_product_option' === $post_type ) {
			return 'edit.php?post_type=product';
		}
		return $parent_file;
	}

	/**
	 * Menü-Highlighting: Korrekten Untermenü-Eintrag hervorheben.
	 *
	 * @param string $submenu_file Aktueller Submenu-File.
	 * @return string
	 */
	public function fix_menu_submenu_file( $submenu_file ) {
		global $post_type;
		// Standorte und Tische: CPT-Liste im Menü vorhanden → direkt darauf zeigen
		$lbite_cpt_types = array( 'lbite_location', 'lbite_table' );
		if ( in_array( $post_type, $lbite_cpt_types, true ) ) {
			return 'edit.php?post_type=' . $post_type;
		}
		// Reservierungen: kein eigener CPT-Menüeintrag mehr → Dashboard-Seite hervorheben
		if ( 'lbite_reservation' === $post_type ) {
			return 'lbite-reservation-board';
		}
		if ( 'lbite_product_option' === $post_type ) {
			return 'edit.php?post_type=lbite_product_option';
		}
		return $submenu_file;
	}

	/**
	 * Tischplan-Seite rendern
	 */
	public function render_floor_plan_page() {
		if ( ! current_user_can( 'lbite_manage_locations' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'libre-bite' ) );
		}
		include LBITE_PLUGIN_DIR . 'templates/admin/table-plan.php';
	}

	/**
	 * Debug-Seite rendern
	 */
	public function render_debug_page() {
		if ( ! current_user_can( 'lbite_view_debug' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'libre-bite' ) );
		}
		include LBITE_PLUGIN_DIR . 'templates/admin/debug-info.php';
	}

	/**
	 * Pricing-Seite rendern
	 */
	public function render_pricing_page() {
		if ( function_exists( 'lbite_freemius' ) ) {
			lbite_freemius()->get_upgrade_url(); // Dies triggert den Freemius Checkout/Pricing Frame
			
			// Fallback falls der Frame nicht sofort lädt
			echo '<div class="wrap"><h1>' . esc_html__( 'Upgrade auf Libre Bite Pro', 'libre-bite' ) . '</h1>';
			echo '<p>' . esc_html__( 'Schalten Sie alle Premium-Funktionen frei, um das volle Potenzial von Libre Bite zu nutzen.', 'libre-bite' ) . '</p>';
			echo '<a href="' . esc_url( lbite_freemius()->get_upgrade_url() ) . '" class="button button-primary">' . esc_html__( 'Preise anzeigen', 'libre-bite' ) . '</a>';
			echo '</div>';
		}
	}

	/**
	 * Admin-Assets laden
	 *
	 * @param string $hook Aktuelle Admin-Seite
	 */
	public function enqueue_admin_assets( $hook ) {
		// Nur auf Plugin-Seiten laden
		if ( empty( $hook ) || ( strpos( $hook, 'libre-bite' ) === false && strpos( $hook, 'lbite-' ) === false ) ) {
			return;
		}

		// CSS
		wp_enqueue_style(
			'lbite-admin',
			LBITE_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			LBITE_VERSION
		);

		// Seitenspezifisches CSS
		wp_enqueue_style(
			'lbite-admin-pages',
			LBITE_PLUGIN_URL . 'assets/css/admin-pages.css',
			array( 'lbite-admin' ),
			LBITE_VERSION
		);

		// Hilfe-Seiten CSS
		if ( strpos( $hook, 'lbite-help' ) !== false || strpos( $hook, 'lbite-documentation' ) !== false ) {
			wp_enqueue_style(
				'lbite-admin-help',
				LBITE_PLUGIN_URL . 'assets/css/admin-help.css',
				array( 'lbite-admin' ),
				LBITE_VERSION
			);
		}

		// JS
		wp_enqueue_script(
			'lbite-admin',
			LBITE_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			LBITE_VERSION,
			true
		);

		// Lokalisierte Daten
		wp_localize_script(
			'lbite-admin',
			'lbiteAdmin',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'lbite_admin_nonce' ),
				'strings'      => array(
					'confirmDelete' => __( 'Wirklich löschen?', 'libre-bite' ),
					'saveSuccess'   => __( 'Erfolgreich gespeichert', 'libre-bite' ),
					'saveError'     => __( 'Fehler beim Speichern', 'libre-bite' ),
					'noTable'       => __( 'Kein Tisch', 'libre-bite' ),
				),
			)
		);

		// Einstellungen-Seite JS (nur auf Settings- und Haupt-Plugin-Seite laden).
		if ( strpos( $hook, 'lbite-settings' ) !== false || 'toplevel_page_libre-bite' === $hook ) {
			wp_enqueue_script(
				'lbite-admin-settings',
				LBITE_PLUGIN_URL . 'assets/js/admin-settings-page.js',
				array( 'jquery' ),
				LBITE_VERSION,
				true
			);
		}

		// Dashboard / Order Board Assets
		if ( strpos( $hook, 'lbite-order-board' ) !== false ) {
			wp_enqueue_style(
				'lbite-order-board',
				LBITE_PLUGIN_URL . 'assets/css/admin-order-board.css',
				array( 'lbite-admin' ),
				LBITE_VERSION
			);

			// SortableJS registrieren (wird vom Dashboard als Abhängigkeit benötigt)
			wp_register_script(
				'sortablejs',
				LBITE_PLUGIN_URL . 'assets/js/vendor/sortable.min.js',
				array(),
				LBITE_VERSION,
				true
			);

			wp_enqueue_script(
				'lbite-dashboard',
				LBITE_PLUGIN_URL . 'assets/js/dashboard.js',
				array( 'jquery', 'sortablejs' ),
				LBITE_VERSION,
				true
			);

			// Standort-Farben für Dashboard-Hervorhebung (gecacht via LBite_Locations).
			$lbite_dashboard_colors = LBite_Locations::get_all_location_colors();

			wp_localize_script(
				'lbite-dashboard',
				'lbiteDashboard',
				array(
					'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
					'nonce'           => wp_create_nonce( 'lbite_dashboard_nonce' ),
					'soundUrl'        => get_option( 'lbite_notification_sound', LBITE_PLUGIN_URL . 'assets/sounds/notification.mp3' ),
					'refreshInterval' => (int) get_option( 'lbite_dashboard_refresh_interval', 30 ) * 1000,
					'locationColors'  => $lbite_dashboard_colors,
					'strings'         => array(
						'orderUpdated'  => __( 'Status aktualisiert', 'libre-bite' ),
						'updateError'   => __( 'Fehler beim Aktualisieren', 'libre-bite' ),
						'soundActive'   => __( 'Sound aktiv', 'libre-bite' ),
						'soundInactive' => __( 'Sound aus', 'libre-bite' ),
					),
				)
			);
		}

		// Reservierungsboard-Assets
		if ( strpos( $hook, 'lbite-reservation-board' ) !== false ) {
			wp_enqueue_style(
				'lbite-reservation-board',
				LBITE_PLUGIN_URL . 'assets/css/admin-reservation-board.css',
				array( 'lbite-admin' ),
				LBITE_VERSION
			);

			wp_enqueue_script(
				'lbite-reservation-board',
				LBITE_PLUGIN_URL . 'assets/js/reservation-board.js',
				array( 'jquery' ),
				LBITE_VERSION,
				true
			);

			wp_localize_script(
				'lbite-reservation-board',
				'lbiteReservationBoard',
				array(
					'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
					'nonce'           => wp_create_nonce( 'lbite_reservation_board_nonce' ),
					'refreshInterval' => (int) get_option( 'lbite_reservation_refresh_interval', 60 ) * 1000,
					'strings'         => array(
						'reservation'  => __( 'Reservierung', 'libre-bite' ),
						'reservations' => __( 'Reservierungen', 'libre-bite' ),
						'table'        => __( 'Tisch', 'libre-bite' ),
						'noTable'      => __( 'Kein Tisch', 'libre-bite' ),
					),
				)
			);
		}

		// POS-Assets werden komplett in class-pos.php geladen (inkl. preloadData).
	}

	/**
	 * AJAX: POS-Standort speichern
	 */
	public function ajax_save_pos_location() {
		check_ajax_referer( 'lbite_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$location_id = isset( $_POST['location_id'] ) ? intval( $_POST['location_id'] ) : 0;

		// Standort für aktuellen Benutzer speichern
		update_user_meta( get_current_user_id(), 'lbite_pos_location', $location_id );

		wp_send_json_success( array( 'location_id' => $location_id ) );
	}

	/**
	 * AJAX: POS-Produkte laden (mit Caching)
	 */
	public function ajax_pos_get_products() {
		check_ajax_referer( 'lbite_pos_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$category_id = isset( $_POST['category_id'] ) ? intval( $_POST['category_id'] ) : 0;

		// Transient-Cache prüfen (5 Minuten).
		$cache_key    = 'lbite_pos_products_' . $category_id;
		$product_data = get_transient( $cache_key );

		if ( false === $product_data ) {
			// Nicht im Cache - Daten laden.
			$args = array(
				'post_type'      => 'product',
				'posts_per_page' => 500, // Begrenzt für Performance.
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			);

			// Nach Kategorie filtern.
			if ( $category_id > 0 ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Produkt-Standort-Filterung via Taxonomy ist für WooCommerce-Plugins unvermeidbar; Limit begrenzt die Abfrage.
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $category_id,
					),
				);
			}

			$products     = get_posts( $args );
			$product_data = array();

			foreach ( $products as $product_post ) {
				$product = wc_get_product( $product_post->ID );

				if ( ! $product ) {
					continue;
				}

				// Prüfen ob Produkt Varianten oder Optionen hat.
				$has_variations  = $product->is_type( 'variable' );
				$product_options = get_post_meta( $product->get_id(), '_lbite_product_options', true );
				$has_options     = ! empty( $product_options );

				$product_data[] = array(
					'id'             => $product->get_id(),
					'name'           => $product->get_name(),
					'price'          => $product->get_price(),
					'image'          => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
					'has_variations' => $has_variations,
					'has_options'    => $has_options,
					'type'           => $product->get_type(),
				);
			}

			// Im Cache speichern (5 Minuten).
			set_transient( $cache_key, $product_data, 5 * MINUTE_IN_SECONDS );
		}

		wp_send_json_success( array( 'products' => $product_data ) );
	}

	/**
	 * AJAX: POS-Produkt-Details laden (Varianten & Optionen)
	 */
	public function ajax_pos_get_product_details() {
		check_ajax_referer( 'lbite_pos_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Keine Produkt-ID angegeben', 'libre-bite' ) ) );
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Produkt nicht gefunden', 'libre-bite' ) ) );
		}

		try {

		$response = array(
			'id'         => $product->get_id(),
			'name'       => $product->get_name(),
			'type'       => $product->get_type(),
			'price'      => $product->get_price(),
			'variations' => array(),
			'options'    => array(),
		);

		// Varianten laden (für variable Produkte)
		if ( $product->is_type( 'variable' ) ) {
			$variations = $product->get_available_variations();
			foreach ( $variations as $variation ) {
				$variation_obj = wc_get_product( $variation['variation_id'] );
				if ( ! $variation_obj ) {
					continue;
				}

				// Attribute formatieren
				$attr_labels = array();
				if ( ! empty( $variation['attributes'] ) && is_array( $variation['attributes'] ) ) {
					foreach ( $variation['attributes'] as $attr_key => $attr_value ) {
						// Attribut-Label und Wert extrahieren
						$attr_name = str_replace( 'attribute_', '', $attr_key );
						$attr_labels[] = ucfirst( $attr_name ) . ': ' . $attr_value;
					}
				}

				$response['variations'][] = array(
					'id'         => $variation['variation_id'],
					'attributes' => $variation['attributes'],
					'price'      => $variation_obj->get_price(),
					'name'       => ! empty( $attr_labels ) ? implode( ', ', $attr_labels ) : __( 'Variante', 'libre-bite' ),
				);
			}
		}

		// OOS Produkt-Optionen laden
		$product_options = get_post_meta( $product->get_id(), '_lbite_product_options', true );
		if ( ! empty( $product_options ) && is_array( $product_options ) ) {
			foreach ( $product_options as $option_id ) {
				$option_post = get_post( $option_id );
				if ( ! $option_post ) {
					continue;
				}

				// Einfaches Optionssystem (eine Option = eine Checkbox mit Preis)
				$option_price = get_post_meta( $option_id, '_lbite_price', true );

				$response['options'][] = array(
					'id'       => $option_id,
					'name'     => $option_post->post_title,
					'type'     => 'checkbox',
					'required' => false,
					'choices'  => array(
						array(
							'label' => $option_post->post_title,
							'price' => floatval( $option_price ),
						),
					),
				);
			}
		}

		wp_send_json_success( $response );

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX: POS-Bestellung erstellen
	 */
	public function ajax_pos_create_order() {
		check_ajax_referer( 'lbite_pos_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_use_pos' ) && ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		// Rohes JSON laden und validieren.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON wird nach dem Decode Feld für Feld validiert.
		$cart_items_raw = isset( $_POST['cart_items'] ) ? wp_unslash( $_POST['cart_items'] ) : '';

		if ( empty( $cart_items_raw ) ) {
			wp_send_json_error( array( 'message' => __( 'Warenkorb ist leer', 'libre-bite' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON wird nach dem Decode Feld für Feld validiert.
		$cart_items_decoded = json_decode( $cart_items_raw, true );

		if ( ! is_array( $cart_items_decoded ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungültige Warenkorbdaten', 'libre-bite' ) ) );
		}

		$cart_items = array();

		foreach ( $cart_items_decoded as $raw_item ) {
			if ( ! is_array( $raw_item ) ) {
				continue;
			}

			$product_id = isset( $raw_item['id'] ) ? absint( $raw_item['id'] ) : 0;
			$quantity   = isset( $raw_item['quantity'] ) ? (int) $raw_item['quantity'] : 0;
			$meta       = isset( $raw_item['meta'] ) ? sanitize_text_field( $raw_item['meta'] ) : '';

			if ( ! $product_id || $quantity <= 0 ) {
				continue;
			}

			$cart_items[] = array(
				'id'       => $product_id,
				'quantity' => $quantity,
				'meta'     => $meta,
			);
		}

		if ( empty( $cart_items ) ) {
			wp_send_json_error( array( 'message' => __( 'Warenkorb ist leer', 'libre-bite' ) ) );
		}

		$location_id    = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;
		$table_id       = isset( $_POST['table_id'] ) ? intval( wp_unslash( $_POST['table_id'] ) ) : 0;
		$order_type     = isset( $_POST['order_type'] ) ? sanitize_text_field( wp_unslash( $_POST['order_type'] ) ) : 'now';
		$pickup_time    = isset( $_POST['pickup_time'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_time'] ) ) : '';
		$customer_name  = isset( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : '';
		$payment_method = isset( $_POST['payment_method'] ) ? sanitize_key( wp_unslash( $_POST['payment_method'] ) ) : 'cash';

		// Erlaubte Zahlungsarten aus Einstellungen lesen (Fallback: alle vier Standardarten).
		$configured_methods      = get_option( 'lbite_pos_payment_methods', array() );
		$allowed_payment_methods = ! empty( $configured_methods )
			? array_column( $configured_methods, 'key' )
			: array( 'cash', 'card', 'twint', 'other' );
		if ( ! in_array( $payment_method, $allowed_payment_methods, true ) ) {
			$payment_method = $allowed_payment_methods[0] ?? 'other';
		}

		if ( ! $location_id ) {
			wp_send_json_error( array( 'message' => __( 'Kein Standort ausgewählt', 'libre-bite' ) ) );
		}

		try {
			// WooCommerce-Bestellung erstellen.
			$order = wc_create_order();

			// Produkte hinzufügen.
			foreach ( $cart_items as $item ) {
				$product = wc_get_product( $item['id'] );
				if ( ! $product || ! $product->is_purchasable() ) {
					continue;
				}

				// Client-Preis ignorieren – echten Produktpreis aus WooCommerce verwenden.
				// WC erwartet Netto-Preise als subtotal/total; wc_get_price_excluding_tax()
				// berücksichtigt die globale Einstellung «Preise inkl. Steuern» korrekt,
				// damit calculate_totals() keine Steuer doppelt aufschlägt.
				$price_excl_tax = wc_get_price_excluding_tax( $product, array( 'qty' => $item['quantity'] ) );

				$order_item_id = $order->add_product(
					$product,
					$item['quantity'],
					array(
						'subtotal' => $price_excl_tax,
						'total'    => $price_excl_tax,
					)
				);

				// Meta-Daten (Varianten & Optionen) hinzufügen.
				if ( ! empty( $item['meta'] ) && $order_item_id ) {
					$order_item = $order->get_item( $order_item_id );
					if ( $order_item ) {
						$order_item->add_meta_data( 'Konfiguration', $item['meta'], true );
						$order_item->save();
					}
				}
			}

			// Bestellmeta setzen.
			$order->update_meta_data( '_lbite_location_id', $location_id );

			// Tisch-ID speichern.
			if ( $table_id ) {
				$order->update_meta_data( '_lbite_table_id', $table_id );
				$table = get_post( $table_id );
				if ( $table ) {
					$order->update_meta_data( '_lbite_table_name', $table->post_title );
				}
			}

			// Standort-Name speichern.
			$location = get_post( $location_id );
			if ( $location ) {
				$order->update_meta_data( '_lbite_location_name', $location->post_title );
			}

			$order->update_meta_data( '_lbite_order_type', $order_type );
			if ( $pickup_time ) {
				$order->update_meta_data( '_lbite_pickup_time', $pickup_time );
			}
			$order->update_meta_data( '_lbite_order_source', 'pos' );
			$order->update_meta_data( '_lbite_payment_method', $payment_method );

			// Kundenname speichern (falls angegeben).
			if ( ! empty( $customer_name ) ) {
				$order->set_billing_first_name( $customer_name );
				$order->update_meta_data( '_lbite_customer_name', $customer_name );
			}

			// Berechnen.
			$order->calculate_totals();

			// Status setzen.
			$order->update_status( 'processing', __( 'Bestellung über Kassensystem erstellt.', 'libre-bite' ) );

			// Währungssymbol dekodieren (z.B. &#67;&#72;&#70; -> CHF).
			$currency = html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' );

			wp_send_json_success(
				array(
					'order_id'     => $order->get_id(),
					'order_number' => $order->get_order_number(),
					'total'        => $currency . ' ' . number_format( $order->get_total(), 2, '.', "'" ),
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX: Theme-Farben abrufen
	 */
	public function ajax_get_theme_colors() {
		check_ajax_referer( 'lbite_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$colors = array(
			'primary'   => '',
			'secondary' => '',
			'accent'    => '',
		);

		// Versuche Block-Theme Farben zu lesen (WordPress 5.9+).
		if ( function_exists( 'wp_get_global_settings' ) ) {
			$global_settings = wp_get_global_settings();

			if ( ! empty( $global_settings['color']['palette']['theme'] ) ) {
				$palette = $global_settings['color']['palette']['theme'];

				foreach ( $palette as $color ) {
					$slug = strtolower( $color['slug'] );
					$hex  = $color['color'];

					// Versuche Farben anhand von Slug-Namen zuzuordnen.
					if ( strpos( $slug, 'primary' ) !== false || strpos( $slug, 'brand' ) !== false ) {
						if ( empty( $colors['primary'] ) ) {
							$colors['primary'] = $hex;
						}
					} elseif ( strpos( $slug, 'secondary' ) !== false ) {
						if ( empty( $colors['secondary'] ) ) {
							$colors['secondary'] = $hex;
						}
					} elseif ( strpos( $slug, 'accent' ) !== false || strpos( $slug, 'tertiary' ) !== false ) {
						if ( empty( $colors['accent'] ) ) {
							$colors['accent'] = $hex;
						}
					}
				}

				// Fallback: Nimm erste 3 Farben falls keine passenden Namen gefunden.
				if ( empty( $colors['primary'] ) && isset( $palette[0] ) ) {
					$colors['primary'] = $palette[0]['color'];
				}
				if ( empty( $colors['secondary'] ) && isset( $palette[1] ) ) {
					$colors['secondary'] = $palette[1]['color'];
				}
				if ( empty( $colors['accent'] ) && isset( $palette[2] ) ) {
					$colors['accent'] = $palette[2]['color'];
				}
			}
		}

		// Fallback: Klassisches Theme mit Customizer.
		if ( empty( $colors['primary'] ) ) {
			// Versuche gängige Theme-Mods zu lesen.
			$customizer_options = array(
				'primary'   => array( 'primary_color', 'brand_color', 'link_color', 'accent_color' ),
				'secondary' => array( 'secondary_color', 'heading_color' ),
				'accent'    => array( 'accent_color', 'highlight_color', 'button_color' ),
			);

			foreach ( $customizer_options as $key => $options ) {
				foreach ( $options as $option ) {
					$value = get_theme_mod( $option, '' );
					if ( ! empty( $value ) && empty( $colors[ $key ] ) ) {
						$colors[ $key ] = $value;
						break;
					}
				}
			}
		}

		// Letzte Fallback: Standard-Farben.
		if ( empty( $colors['primary'] ) ) {
			$colors['primary'] = '#2271b1';
		}
		if ( empty( $colors['secondary'] ) ) {
			$colors['secondary'] = '#1d2327';
		}
		if ( empty( $colors['accent'] ) ) {
			$colors['accent'] = '#135e96';
		}

		wp_send_json_success( $colors );
	}

	/**
	 * AJAX: Feature-Toggles speichern
	 */
	public function ajax_save_features() {
		check_ajax_referer( 'lbite_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_manage_features' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$features_json = isset( $_POST['features'] ) ? wp_unslash( $_POST['features'] ) : '';
		$features      = json_decode( $features_json, true );

		if ( ! is_array( $features ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungültige Daten', 'libre-bite' ) ) );
		}

		// Alle Feature-Werte als boolean konvertieren
		$sanitized_features = array();
		foreach ( $features as $key => $value ) {
			$sanitized_features[ sanitize_key( $key ) ] = (bool) $value;
		}

		update_option( 'lbite_features', $sanitized_features );

		wp_send_json_success( array( 'message' => __( 'Einstellungen gespeichert', 'libre-bite' ) ) );
	}

	/**
	 * AJAX: Support-Einstellungen speichern
	 */
	public function ajax_save_support_settings() {
		check_ajax_referer( 'lbite_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_manage_support' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$settings = array(
			'support_email'        => isset( $_POST['support_email'] ) ? sanitize_email( wp_unslash( $_POST['support_email'] ) ) : '',
			'support_phone'        => isset( $_POST['support_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['support_phone'] ) ) : '',
			'support_hours'        => isset( $_POST['support_hours'] ) ? sanitize_text_field( wp_unslash( $_POST['support_hours'] ) ) : '',
			'support_billing_note' => isset( $_POST['support_billing_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['support_billing_note'] ) ) : '',
			'support_custom_text'  => isset( $_POST['support_custom_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['support_custom_text'] ) ) : '',
		);

		update_option( 'lbite_support_settings', $settings );

		wp_send_json_success( array( 'message' => __( 'Support-Einstellungen gespeichert', 'libre-bite' ) ) );
	}

	/**
	 * AJAX: Onboarding neu starten
	 */
	public function ajax_restart_onboarding() {
		check_ajax_referer( 'lbite_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		delete_option( 'lbite_onboarding_completed' );
		update_option( 'lbite_do_activation_redirect', true );

		wp_send_json_success( array( 'redirect' => admin_url( 'admin.php?page=lbite-onboarding' ) ) );
	}

	/**
	 * Bestellungs- und Reservierungs-Counter als Badge im Untermenü anzeigen
	 */
	public function inject_order_count_badge() {
		global $submenu;

		if ( ! isset( $submenu['libre-bite'] ) ) {
			return;
		}

		$order_count       = LBite_Order_Dashboard::get_incoming_orders_count();
		$reservation_count = class_exists( 'LBite_Reservations' ) ? LBite_Reservations::get_pending_reservations_count() : 0;

		foreach ( $submenu['libre-bite'] as &$item ) {
			if ( ! isset( $item[2] ) ) {
				continue;
			}
			if ( 'lbite-order-board' === $item[2] && $order_count > 0 ) {
				$item[0] .= ' <span class="update-plugins count-' . absint( $order_count ) . '"><span class="plugin-count">' . absint( $order_count ) . '</span></span>';
			} elseif ( 'lbite-reservation-board' === $item[2] && $reservation_count > 0 ) {
				$item[0] .= ' <span class="update-plugins count-' . absint( $reservation_count ) . '"><span class="plugin-count">' . absint( $reservation_count ) . '</span></span>';
			}
		}
		unset( $item );
	}

	/**
	 * Support-Floating-Button (nur auf Plugin-Seiten)
	 */
	public function render_support_footer() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		// Nur auf Plugin-Seiten anzeigen
		if ( strpos( $screen->id, 'libre-bite' ) === false && strpos( $screen->id, 'lbite-' ) === false ) {
			return;
		}

		// Auf der Hilfe-Seite nicht anzeigen (Support ist dort bereits integriert)
		if ( strpos( $screen->id, 'lbite-help' ) !== false ) {
			return;
		}

		$support_settings = get_option( 'lbite_support_settings', array() );
		$support_email    = isset( $support_settings['support_email'] ) ? $support_settings['support_email'] : get_option( 'admin_email' );
		$support_phone    = isset( $support_settings['support_phone'] ) ? $support_settings['support_phone'] : '';
		$support_hours    = isset( $support_settings['support_hours'] ) ? $support_settings['support_hours'] : '';
		$billing_note     = isset( $support_settings['support_billing_note'] ) ? $support_settings['support_billing_note'] : '';
		?>
		<div id="lbite-help-btn" aria-expanded="false">
			<span class="dashicons dashicons-editor-help"></span>
			<span><?php esc_html_e( 'Hilfe', 'libre-bite' ); ?></span>

			<div id="lbite-help-panel" role="dialog" aria-label="<?php esc_attr_e( 'Hilfe & Support', 'libre-bite' ); ?>">
				<div class="lbite-help-panel-head">
					<strong><?php esc_html_e( 'Hilfe & Support', 'libre-bite' ); ?></strong>
					<button type="button" class="lbite-help-panel-close" aria-label="<?php esc_attr_e( 'Schliessen', 'libre-bite' ); ?>">&#x2715;</button>
				</div>

				<!-- Hilfe-Bereich: primäre Aktion -->
				<div class="lbite-help-panel-primary">
					<p class="lbite-help-panel-primary-text">
						<?php esc_html_e( 'Zuerst im Hilfe-Bereich nachschauen – dort findest du Anleitungen zu allen Funktionen.', 'libre-bite' ); ?>
					</p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help' ) ); ?>" class="button button-primary button-large lbite-help-panel-cta">
						<span class="dashicons dashicons-book-alt"></span>
						<?php esc_html_e( 'Hilfe-Bereich öffnen', 'libre-bite' ); ?>
					</a>
				</div>

				<!-- Support-Kontakt: sekundäre Aktion -->
				<div class="lbite-help-panel-support">
					<p class="lbite-help-panel-divider"><?php esc_html_e( 'Nicht fündig geworden?', 'libre-bite' ); ?></p>

					<?php if ( $support_hours ) : ?>
						<p>
							<span class="dashicons dashicons-clock"></span>
							<?php echo esc_html( $support_hours ); ?>
						</p>
					<?php endif; ?>
					<?php if ( $billing_note ) : ?>
						<p class="lbite-help-panel-note">
							<span class="dashicons dashicons-info"></span>
							<?php echo esc_html( $billing_note ); ?>
						</p>
					<?php endif; ?>
					<?php if ( $support_email ) : ?>
						<p>
							<span class="dashicons dashicons-email"></span>
							<a href="mailto:<?php echo esc_attr( $support_email ); ?>"><?php echo esc_html( $support_email ); ?></a>
						</p>
					<?php endif; ?>
					<?php if ( $support_phone ) : ?>
						<p>
							<span class="dashicons dashicons-phone"></span>
							<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $support_phone ) ); ?>"><?php echo esc_html( $support_phone ); ?></a>
						</p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<style>
		#lbite-help-btn {
			position: fixed;
			bottom: 40px;
			right: 20px;
			z-index: 99990;
			display: flex;
			align-items: center;
			gap: 6px;
			background: #646970;
			color: #fff;
			border-radius: 4px;
			padding: 8px 14px;
			font-size: 13px;
			font-weight: 600;
			cursor: pointer;
			box-shadow: 0 2px 6px rgba(0,0,0,0.25);
			user-select: none;
			transition: background 0.15s;
		}
		#lbite-help-btn:hover {
			background: #50575e;
		}
		#lbite-help-btn > .dashicons {
			font-size: 16px;
			width: 16px;
			height: 16px;
		}
		#lbite-help-panel {
			display: none;
			position: absolute;
			bottom: calc(100% + 8px);
			right: 0;
			width: 260px;
			background: #fff;
			border: 1px solid #ccd0d4;
			border-radius: 4px;
			box-shadow: 0 4px 16px rgba(0,0,0,0.15);
			font-size: 13px;
			color: #3c434a;
			overflow: hidden;
		}
		#lbite-help-panel.is-open {
			display: block;
		}
		.lbite-help-panel-head {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 10px 14px 9px;
			border-bottom: 1px solid #f0f0f1;
		}
		.lbite-help-panel-close {
			background: none;
			border: none;
			cursor: pointer;
			color: #787c82;
			font-size: 14px;
			line-height: 1;
			padding: 0;
		}
		.lbite-help-panel-close:hover { color: #1d2327; }
		.lbite-help-panel-primary {
			padding: 14px;
			background: #f6f7f7;
			border-bottom: 1px solid #e8e8e8;
		}
		.lbite-help-panel-primary-text {
			margin: 0 0 10px;
			font-size: 12px;
			color: #646970;
			line-height: 1.5;
		}
		.lbite-help-panel-cta {
			display: flex !important;
			align-items: center;
			gap: 5px;
			width: 100%;
			justify-content: center;
			box-sizing: border-box;
		}
		.lbite-help-panel-cta .dashicons {
			font-size: 16px;
			width: 16px;
			height: 16px;
		}
		.lbite-help-panel-support {
			padding: 4px 0 6px;
		}
		.lbite-help-panel-divider {
			margin: 0;
			padding: 8px 14px 6px;
			font-size: 11px;
			font-weight: 600;
			color: #787c82;
			text-transform: uppercase;
			letter-spacing: 0.05em;
		}
		.lbite-help-panel-support p {
			margin: 0;
			padding: 5px 14px;
			display: flex;
			align-items: flex-start;
			gap: 7px;
		}
		.lbite-help-panel-support p a {
			color: #3c434a;
			text-decoration: none;
			word-break: break-all;
		}
		.lbite-help-panel-support p a:hover { color: #0073aa; }
		.lbite-help-panel-support .dashicons {
			font-size: 15px;
			width: 15px;
			height: 15px;
			flex-shrink: 0;
			margin-top: 1px;
			color: #787c82;
		}
		.lbite-help-panel-note {
			color: #646970 !important;
			font-size: 12px !important;
		}
		</style>
		<script>
		(function() {
			var btn   = document.getElementById('lbite-help-btn');
			var panel = document.getElementById('lbite-help-panel');
			if (!btn || !panel) return;

			function open() {
				panel.classList.add('is-open');
				btn.setAttribute('aria-expanded', 'true');
			}
			function close() {
				panel.classList.remove('is-open');
				btn.setAttribute('aria-expanded', 'false');
			}

			btn.addEventListener('click', function(e) {
				if (e.target.classList.contains('lbite-help-panel-close')) {
					close();
				} else {
					panel.classList.contains('is-open') ? close() : open();
				}
			});

			document.addEventListener('click', function(e) {
				if (!btn.contains(e.target)) close();
			});
		})();
		</script>
		<?php
	}

	/**
	 * AJAX: Tische für einen Standort abrufen
	 */
	public function ajax_get_location_tables() {
		check_ajax_referer( 'lbite_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_use_pos' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;

		if ( ! $location_id ) {
			wp_send_json_success( array( 'tables' => array() ) );
		}

		$tables = get_posts( array(
			'post_type'      => 'lbite_table',
			'posts_per_page' => 100,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_lbite_location_id',
					'value' => $location_id,
				),
			),
		) );

		// Aktive Bestellungen für diesen Standort abrufen, um Tischstatus zu ermitteln.
		$active_orders = wc_get_orders( array(
			'limit'      => 200,
			'status'     => array( 'wc-processing', 'wc-on-hold', 'wc-pending' ),
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_lbite_location_id',
					'value' => $location_id,
				),
			),
		) );

		// Belegte Tisch-IDs sammeln.
		$occupied_tables = array();
		foreach ( $active_orders as $order ) {
			$table_id = $order->get_meta( '_lbite_table_id' );
			if ( $table_id ) {
				$occupied_tables[ intval( $table_id ) ] = true;
			}
		}

		$formatted_tables = array();
		foreach ( $tables as $table ) {
			$formatted_tables[] = array(
				'id'       => $table->ID,
				'title'    => $table->post_title,
				'occupied' => isset( $occupied_tables[ $table->ID ] ),
			);
		}

		wp_send_json_success( array( 'tables' => $formatted_tables ) );
	}
}
