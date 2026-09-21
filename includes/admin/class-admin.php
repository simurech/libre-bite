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
		$this->loader->add_filter( 'admin_body_class', $this, 'add_admin_body_classes' );
		$this->loader->add_action( 'personal_options', $this, 'render_theme_user_option' );
		$this->loader->add_action( 'personal_options_update', $this, 'save_theme_user_option' );
		$this->loader->add_action( 'edit_user_profile_update', $this, 'save_theme_user_option' );
		$this->loader->add_action( 'admin_init', $this, 'maybe_upgrade' );

		// Während der Entwicklung den Änderungszeitpunkt als Versionsangabe der
		// Asset-URLs verwenden. Sonst bleibt die Plugin-Version der Cache-Schlüssel,
		// und jede Korrektur an einer CSS-Datei innerhalb derselben Version
		// erreicht den Browser erst nach einem harten Neuladen.
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$this->loader->add_filter( 'style_loader_src', $this, 'bust_asset_cache_during_development' );
			$this->loader->add_filter( 'script_loader_src', $this, 'bust_asset_cache_during_development' );
		}

		// WooCommerce leitet Benutzer ohne edit_posts/manage_woocommerce aus dem Backend um.
		// lbite_staff hat keine dieser Capabilities, braucht aber Zugriff auf POS und Kanban.
		$this->loader->add_filter( 'woocommerce_prevent_admin_access', $this, 'allow_staff_admin_access' );

		// Capabilities der lbite_staff-Rolle dynamisch sicherstellen (funktioniert auch bei
		// veralteten Rollen-Einträgen in der Datenbank, ohne auf die Migration angewiesen zu sein).
		$this->loader->add_filter( 'user_has_cap', $this, 'ensure_staff_capabilities', 10, 4 );

		// Menü-Highlighting für CPT-Seiten
		$this->loader->add_filter( 'parent_file', $this, 'fix_menu_parent_file' );
		$this->loader->add_filter( 'submenu_file', $this, 'fix_menu_submenu_file' );

		// AJAX-Handler.
		$this->loader->add_action( 'wp_ajax_lbite_save_pos_location', $this, 'ajax_save_pos_location' );
		$this->loader->add_action( 'wp_ajax_lbite_pos_get_product_details', $this, 'ajax_pos_get_product_details' );
		$this->loader->add_action( 'wp_ajax_lbite_pos_create_order', $this, 'ajax_pos_create_order' );
		$this->loader->add_action( 'wp_ajax_lbite_pos_get_coupons', $this, 'ajax_pos_get_coupons' );
		$this->loader->add_action( 'wp_ajax_lbite_pos_toggle_stock', $this, 'ajax_pos_toggle_stock' );
		$this->loader->add_action( 'wp_ajax_lbite_get_theme_colors', $this, 'ajax_get_theme_colors' );
		$this->loader->add_action( 'wp_ajax_lbite_save_features', $this, 'ajax_save_features' );
		$this->loader->add_action( 'wp_ajax_lbite_save_support_settings', $this, 'ajax_save_support_settings' );
		$this->loader->add_action( 'wp_ajax_lbite_get_location_tables', $this, 'ajax_get_location_tables' );
		$this->loader->add_action( 'wp_ajax_lbite_dismiss_welcome_notice', $this, 'ajax_dismiss_welcome_notice' );
		$this->loader->add_action( 'wp_ajax_lbite_save_pos_product_order', $this, 'ajax_save_pos_product_order' );
		$this->loader->add_action( 'wp_ajax_lbite_admin_search_products', $this, 'ajax_admin_search_products' );

		// Offene Tabs (F_TAB).
		$this->loader->add_action( 'wp_ajax_lbite_pos_get_open_tabs', $this, 'ajax_pos_get_open_tabs' );
		$this->loader->add_action( 'wp_ajax_lbite_pos_open_tab', $this, 'ajax_pos_open_tab' );
		$this->loader->add_action( 'wp_ajax_lbite_pos_add_to_tab', $this, 'ajax_pos_add_to_tab' );
		$this->loader->add_action( 'wp_ajax_lbite_pos_close_tab', $this, 'ajax_pos_close_tab' );
		$this->loader->add_action( 'wp_ajax_lbite_pos_cancel_tab', $this, 'ajax_pos_cancel_tab' );

		// Bestellungs-Counter im Menü-Badge (nach Menü-Aufbau)
		$this->loader->add_action( 'admin_menu', $this, 'inject_order_count_badge', 999 );

		// Standort-Zuweisung pro Benutzer (Benutzerprofil)
		$this->loader->add_action( 'show_user_profile', $this, 'render_user_location_field' );
		$this->loader->add_action( 'edit_user_profile', $this, 'render_user_location_field' );
		$this->loader->add_action( 'personal_options_update', $this, 'save_user_location_field' );
		$this->loader->add_action( 'edit_user_profile_update', $this, 'save_user_location_field' );

		// Support-Box im Admin-Footer
		$this->loader->add_action( 'admin_footer', $this, 'render_support_footer' );

		// Beleg-Versand (Premium): AJAX immer registrieren – wird von Kanban-Board und
		// Bestellansicht benötigt, unabhängig vom aktiven Checkout-Modus.
		if ( function_exists( 'lbite_freemius' ) && lbite_freemius()->is__premium_only() ) {
			$this->loader->add_action( 'wp_ajax_lbite_admin_send_receipt', $this, 'ajax_admin_send_receipt__premium_only' );
			// Beleg-Metabox nur wenn Optimierter Checkout aktiv.
			if ( lbite_feature_enabled( 'enable_optimized_checkout' ) ) {
				$this->loader->add_action( 'add_meta_boxes', $this, 'add_receipt_metabox__premium_only' );
			}
		}
	}

	/**
	 * WooCommerce-Redirect aus wp-admin für Libre-Bite-Staff aufheben.
	 * WooCommerce sperrt Benutzer ohne edit_posts/manage_woocommerce aus dem Backend.
	 *
	 * @param bool $prevent
	 * @return bool
	 */
	public function allow_staff_admin_access( $prevent ) {
		if ( current_user_can( 'lbite_view_dashboard' ) || current_user_can( 'lbite_use_pos' ) ) {
			return false;
		}
		return $prevent;
	}

	/**
	 * lbite_staff-Rolle: alle nötigen Capabilities dynamisch sicherstellen.
	 *
	 * WordPress cached User-Capabilities zu Beginn des Requests. Falls die Rollen-Definition
	 * in der DB veraltet ist (fehlende Caps), stellt dieser Filter sicher, dass lbite_staff-
	 * Benutzer trotzdem korrekt berechtigt werden – ohne auf einen Migrations-Run angewiesen zu sein.
	 *
	 * @param bool[]  $allcaps Array aller dem Benutzer gewährten Capabilities.
	 * @param string[] $caps    Angeforderte Capabilities.
	 * @param array   $args    Weitere Argumente.
	 * @param WP_User $user    Benutzer-Objekt.
	 * @return bool[]
	 */
	public function ensure_staff_capabilities( $allcaps, $caps, $args, $user ) {
		if ( ! $user instanceof WP_User || ! in_array( 'lbite_staff', (array) $user->roles, true ) ) {
			return $allcaps;
		}
		$allcaps['lbite_view_dashboard'] = true;
		$allcaps['lbite_view_orders']    = true;
		$allcaps['lbite_manage_orders']  = true;
		$allcaps['lbite_use_pos']        = true;
		return $allcaps;
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

		// Dashboard – Capability muss lbite_view_dashboard sein, sonst schlägt der
		// WordPress-Capability-Check beim Navigieren zu page=libre-bite für Staff fehl.
		// Die Weiterleitung auf die Bestellübersicht erfolgt in render_dashboard_page().
		add_submenu_page(
			'libre-bite',
			__( 'Dashboard', 'libre-bite' ),
			__( 'Dashboard', 'libre-bite' ),
			'lbite_view_dashboard',
			'libre-bite',
			array( $this, 'render_dashboard_page' )
		);

		// Dashboard-Eintrag für Staff aus der Menü-Anzeige entfernen
		if ( ! current_user_can( 'lbite_manage_settings' ) ) {
			remove_submenu_page( 'libre-bite', 'libre-bite' );
		}

		// Bestellübersicht (Kanban) – nur wenn Feature aktiv
		// Sichtbarkeit via lbite_view_dashboard (= alle Staff-Rollen); AJAX-Sicherheit
		// über spezifische Capabilities (lbite_manage_orders) in den Handlern.
		if ( lbite_feature_enabled( 'enable_kanban_board' ) ) {
			add_submenu_page(
				'libre-bite',
				__( 'Order Overview', 'libre-bite' ),
				__( 'Order Overview', 'libre-bite' ),
				'lbite_view_dashboard',
				'lbite-order-board',
				array( $this, 'render_order_board_page' )
			);
		}

		// POS/Kassensystem – nur wenn Feature aktiv
		if ( lbite_feature_enabled( 'enable_pos' ) ) {
			add_submenu_page(
				'libre-bite',
				__( 'POS System', 'libre-bite' ),
				__( 'POS System', 'libre-bite' ),
				'lbite_view_dashboard',
				'lbite-pos',
				array( $this, 'render_pos_page' )
			);
		}

		// Reservierungen – gehört inhaltlich zu den täglich genutzten Boards (Order Overview,
		// POS), deshalb hier statt bei der Stammdaten-Verwaltung einsortiert.
		if ( lbite_feature_enabled( 'enable_reservations' ) ) {
			add_submenu_page(
				'libre-bite',
				__( 'Reservations Overview', 'libre-bite' ),
				__( 'Reservations', 'libre-bite' ),
				'lbite_manage_options',
				'lbite-reservation-board',
				array( $this, 'render_reservation_board_page' )
			);
		}

		// ============================================
		// ADMIN-BEREICH (administrator)
		// ============================================

		// Standorte (CPT) – immer anzeigen, da mindestens 1 Standort für POS und Kanban benötigt wird
		add_submenu_page(
			'libre-bite',
			__( 'Locations', 'libre-bite' ),
			__( 'Locations', 'libre-bite' ),
			'lbite_manage_locations',
			'edit.php?post_type=lbite_location'
		);

		// Tische (CPT) + Tischplan – nur wenn Tischbestellung aktiv
		if ( lbite_feature_enabled( 'enable_table_ordering' ) ) {
			add_submenu_page(
				'libre-bite',
				__( 'Tables', 'libre-bite' ),
				__( 'Tables', 'libre-bite' ),
				'lbite_manage_locations',
				'edit.php?post_type=lbite_table'
			);
			add_submenu_page(
				'libre-bite',
				__( 'Table Plan', 'libre-bite' ),
				__( 'Table Plan', 'libre-bite' ),
				'lbite_manage_locations',
				'lbite-floor-plan',
				array( $this, 'render_floor_plan_page' )
			);
		}

		// Produkt-Optionen (CPT) – unter WooCommerce/Produkte
		if ( lbite_feature_enabled( 'enable_product_options' ) ) {
			add_submenu_page(
				'edit.php?post_type=product',
				__( 'Product Options', 'libre-bite' ),
				__( 'Product Options', 'libre-bite' ),
				'lbite_manage_options',
				'edit.php?post_type=lbite_product_option'
			);
		}

		// Allergen-Matrix: druckbare Übersicht aller Produkte (nur bei aktivem Allergen-Modul)
		if ( lbite_feature_enabled( 'enable_allergens' ) ) {
			add_submenu_page(
				'edit.php?post_type=product',
				__( 'Allergen Matrix', 'libre-bite' ),
				__( 'Allergen Matrix', 'libre-bite' ),
				'lbite_manage_products',
				'lbite-allergen-matrix',
				array( $this, 'render_allergen_matrix_page' )
			);
		}

		// ============================================
		// SUPPORT
		// ============================================

		// Support - für alle sichtbar, zeigt die in den Einstellungen hinterlegten Kontaktdaten
		add_submenu_page(
			'libre-bite',
			__( 'Support', 'libre-bite' ),
			__( 'Support', 'libre-bite' ),
			'lbite_view_dashboard',
			'lbite-support',
			array( $this, 'render_support_page' )
		);

		// Statistik – nur für Admin und Manager (lbite_view_statistics)
		add_submenu_page(
			'libre-bite',
			__( 'Statistics', 'libre-bite' ),
			__( 'Statistics', 'libre-bite' ),
			'lbite_view_statistics',
			'lbite-statistics',
			array( $this, 'render_statistics_page' )
		);

		// Pricing / Upgrade (Nur wenn nicht Premium)
		if ( function_exists( 'lbite_freemius' ) && ! lbite_freemius()->is_premium() ) {
			add_submenu_page(
				'libre-bite',
				__( 'Upgrade to Pro', 'libre-bite' ),
				'<span style="color: #f18500; font-weight: bold;">' . __( 'Pricing', 'libre-bite' ) . '</span>',
				'manage_options',
				'lbite-pricing',
				array( $this, 'render_pricing_page' )
			);
		}

		// Einstellungen ans Ende (Improvement R)
		add_submenu_page(
			'libre-bite',
			__( 'Settings', 'libre-bite' ),
			__( 'Settings', 'libre-bite' ),
			'lbite_manage_settings',
			'lbite-settings',
			array( $this, 'render_settings_page' )
		);

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
			wp_die( esc_html__( 'You do not have permission to access this page.', 'libre-bite' ) );
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
	 * Support-Seite rendern (Kontaktdaten aus den Einstellungen, für alle Rollen identisch)
	 */
	public function render_support_page() {
		include LBITE_PLUGIN_DIR . 'templates/admin/support-page.php';
	}

	/**
	 * Statistik-Seite rendern
	 */
	/**
	 * Allergen-Matrix rendern
	 */
	public function render_allergen_matrix_page() {
		if ( ! current_user_can( 'lbite_manage_products' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'libre-bite' ) );
		}
		include LBITE_PLUGIN_DIR . 'templates/admin/allergen-matrix.php';
	}

	public function render_statistics_page() {
		if ( ! current_user_can( 'lbite_view_statistics' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'libre-bite' ) );
		}
		include LBITE_PLUGIN_DIR . 'templates/admin/statistics.php';
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
			wp_die( esc_html__( 'You do not have permission to access this page.', 'libre-bite' ) );
		}
		include LBITE_PLUGIN_DIR . 'templates/admin/table-plan.php';
	}

	/**
	 * Debug-Seite rendern
	 */
	public function render_debug_page() {
		if ( ! current_user_can( 'lbite_view_debug' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'libre-bite' ) );
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
			echo '<div class="wrap"><h1>' . esc_html__( 'Upgrade to Libre Bite Pro', 'libre-bite' ) . '</h1>';
			echo '<p>' . esc_html__( 'Unlock all premium features to realize the full potential of Libre Bite.', 'libre-bite' ) . '</p>';
			echo '<a href="' . esc_url( lbite_freemius()->get_upgrade_url() ) . '" class="button button-primary">' . esc_html__( 'View Pricing', 'libre-bite' ) . '</a>';
			echo '</div>';
		}
	}

	/**
	 * Gewähltes Farbschema des angemeldeten Benutzers
	 *
	 * @return string auto, light oder dark.
	 */
	/**
	 * Asset-URLs während der Entwicklung mit dem Änderungszeitpunkt versehen
	 *
	 * Greift nur bei aktivem SCRIPT_DEBUG und nur für Dateien dieses Plugins.
	 * Im Normalbetrieb bleibt die Plugin-Version die Versionsangabe, damit
	 * Browser und CDN wie gewohnt zwischenspeichern.
	 *
	 * @param string $src Vollständige Asset-URL.
	 * @return string
	 */
	public function bust_asset_cache_during_development( $src ) {
		if ( ! is_string( $src ) || false === strpos( $src, LBITE_PLUGIN_URL ) ) {
			return $src;
		}

		$relativ = ltrim( str_replace( LBITE_PLUGIN_URL, '', strtok( $src, '?' ) ), '/' );
		$pfad    = LBITE_PLUGIN_DIR . $relativ;

		if ( ! file_exists( $pfad ) ) {
			return $src;
		}

		return add_query_arg( 'ver', LBITE_VERSION . '.' . filemtime( $pfad ), $src );
	}

	public static function get_admin_theme() {
		$lbite_theme = get_user_meta( get_current_user_id(), 'lbite_admin_theme', true );

		if ( ! in_array( $lbite_theme, array( 'auto', 'light', 'dark' ), true ) ) {
			return 'auto';
		}

		return $lbite_theme;
	}

	/**
	 * Farbschema-Auswahl im Benutzerprofil rendern
	 *
	 * Bewusst eine persönliche Einstellung und keine globale Option: das
	 * Küchen-Tablet und der Büro-Rechner sollen unabhängig voneinander
	 * eingestellt werden können.
	 *
	 * @param WP_User $user Benutzerobjekt.
	 */
	public function render_theme_user_option( $user ) {
		if ( ! isset( $user->ID ) ) {
			return;
		}

		$lbite_current = get_user_meta( $user->ID, 'lbite_admin_theme', true );

		if ( ! in_array( $lbite_current, array( 'auto', 'light', 'dark' ), true ) ) {
			$lbite_current = 'auto';
		}

		$lbite_choices = array(
			'auto'  => __( 'Follow system setting', 'libre-bite' ),
			'light' => __( 'Always light', 'libre-bite' ),
			'dark'  => __( 'Always dark', 'libre-bite' ),
		);
		?>
		<tr class="lbite-admin-theme-row">
			<th scope="row"><?php esc_html_e( 'Libre Bite colour scheme', 'libre-bite' ); ?></th>
			<td>
				<select name="lbite_admin_theme" id="lbite_admin_theme">
					<?php foreach ( $lbite_choices as $lbite_value => $lbite_label ) : ?>
						<option value="<?php echo esc_attr( $lbite_value ); ?>" <?php selected( $lbite_current, $lbite_value ); ?>>
							<?php echo esc_html( $lbite_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?php esc_html_e( 'Applies to the Libre Bite screens only. A dark scheme is easier on the eyes on kitchen and counter displays that run all day.', 'libre-bite' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Farbschema-Auswahl speichern
	 *
	 * @param int $user_id Benutzer-ID.
	 */
	public function save_theme_user_option( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		check_admin_referer( 'update-user_' . $user_id );

		$lbite_theme = isset( $_POST['lbite_admin_theme'] )
			? sanitize_key( wp_unslash( $_POST['lbite_admin_theme'] ) )
			: 'auto';

		if ( ! in_array( $lbite_theme, array( 'auto', 'light', 'dark' ), true ) ) {
			$lbite_theme = 'auto';
		}

		update_user_meta( $user_id, 'lbite_admin_theme', $lbite_theme );
	}

	/**
	 * Gehoert der aktuelle Bildschirm zu Libre Bite?
	 *
	 * Einzige Quelle fuer diese Frage. Der Enqueue-Waechter und die Body-Klasse
	 * beantworteten sie frueher getrennt und auf unterschiedlicher Grundlage: der
	 * eine ueber den Seiten-Hook, die andere ueber die Bildschirm-Kennung. Bei den
	 * eigenen Inhaltstypen heisst der Hook aber schlicht post.php beziehungsweise
	 * edit.php – das Design-System wurde dort also nie geladen, waehrend die
	 * Theme-Klasse trotzdem am Body stand. Ergebnis: gesetztes Farbschema ohne die
	 * Tokens, auf denen es beruht, plus eine WordPress-Meldung ueber eine nicht
	 * registrierte Abhaengigkeit.
	 *
	 * @param string $hook Aktuelle Admin-Seite (optional, ergaenzt die Kennung).
	 * @return bool
	 */
	public static function is_lbite_screen( $hook = '' ) {
		$kennungen = array();

		if ( ! empty( $hook ) ) {
			$kennungen[] = (string) $hook;
		}

		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen ) {
				if ( ! empty( $screen->id ) ) {
					$kennungen[] = (string) $screen->id;
				}
				if ( ! empty( $screen->post_type ) ) {
					$kennungen[] = (string) $screen->post_type;
				}
			}
		}

		foreach ( $kennungen as $kennung ) {
			if ( false !== strpos( $kennung, 'libre-bite' )
				|| false !== strpos( $kennung, 'lbite-' )
				|| false !== strpos( $kennung, 'lbite_' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Screen-Modus als Body-Klasse setzen
	 *
	 * Arbeits-Screens (Kanban, POS, Tischplan, Reservierungsboard) laufen im
	 * App-Modus: sie füllen den Viewport und scrollen intern. Normale
	 * Einstellungsseiten bleiben im Dokument-Modus und damit WP-Standard.
	 * `lbite-app` aktiviert zusätzlich die Basisregeln des Design-Systems
	 * (Typografie, Fokusringe, prefers-reduced-motion, Touch-Zielgrössen).
	 *
	 * @param string $classes Bestehende Body-Klassen.
	 * @return string
	 */
	public function add_admin_body_classes( $classes ) {
		$lbite_screen = get_current_screen();

		if ( ! $lbite_screen || ! isset( $lbite_screen->id ) ) {
			return $classes;
		}

		$lbite_id = (string) $lbite_screen->id;

		if ( ! self::is_lbite_screen() ) {
			return $classes;
		}

		// Der Einrichtungsassistent blendet das WordPress-Chrome komplett aus.
		if ( false !== strpos( $lbite_id, 'lbite-setup' ) ) {
			return $classes . ' lbite-app lbite-setup-screen ';
		}

		$lbite_app_screens = array(
			'lbite-order-board',
			'lbite-pos',
			'lbite-floor-plan',
			'lbite-reservation-board',
		);

		$lbite_is_app = false;
		foreach ( $lbite_app_screens as $lbite_needle ) {
			if ( false !== strpos( $lbite_id, $lbite_needle ) ) {
				$lbite_is_app = true;
				break;
			}
		}

		$classes .= ' lbite-app '
			. ( $lbite_is_app ? 'lbite-screen-app' : 'lbite-screen-doc' )
			. ' lbite-theme-' . self::get_admin_theme() . ' ';

		return $classes;
	}

	/**
	 * Branding-Farben als Design-Token-Overrides ausgeben
	 *
	 * Die im Branding-Tab gesetzte Primär-/Sekundär-/Akzentfarbe gilt damit
	 * nicht nur im Frontend-Checkout, sondern auch im Backend. Fällt eine
	 * Option auf den Standardwert zurück, bleibt der Token aus dem
	 * Design-System unverändert.
	 *
	 * @return string CSS-Regel für wp_add_inline_style()
	 */
	private static function get_branding_tokens() {
		$lbite_primary   = sanitize_hex_color( (string) get_option( 'lbite_color_primary', '#0073aa' ) );
		$lbite_secondary = sanitize_hex_color( (string) get_option( 'lbite_color_secondary', '#23282d' ) );
		$lbite_accent    = sanitize_hex_color( (string) get_option( 'lbite_color_accent', '#00a32a' ) );

		$lbite_vars = array();

		if ( $lbite_primary ) {
			$lbite_vars[] = '--lbite-color-primary:' . $lbite_primary;
			$lbite_vars[] = '--lbite-color-primary-hover:' . self::shade_hex_color( $lbite_primary, -0.18 );
			$lbite_vars[] = '--lbite-color-primary-soft:' . self::shade_hex_color( $lbite_primary, 0.92 );
			$lbite_vars[] = '--lbite-focus-ring:0 0 0 2px var(--lbite-surface),0 0 0 4px ' . self::hex_to_rgba( $lbite_primary, 0.45 );
		}

		if ( $lbite_secondary ) {
			$lbite_vars[] = '--lbite-color-secondary:' . $lbite_secondary;
		}

		if ( $lbite_accent ) {
			$lbite_vars[] = '--lbite-color-accent:' . $lbite_accent;
		}

		if ( empty( $lbite_vars ) ) {
			return '';
		}

		return ':root{' . implode( ';', $lbite_vars ) . ';}';
	}

	/**
	 * Hex-Farbe abdunkeln oder aufhellen
	 *
	 * @param string $hex    Hex-Farbe inkl. führendem #.
	 * @param float  $amount Negativ = abdunkeln, positiv = Richtung Weiss mischen (0..1).
	 * @return string Hex-Farbe inkl. führendem #.
	 */
	private static function shade_hex_color( $hex, $amount ) {
		$lbite_rgb = self::hex_to_rgb( $hex );

		if ( null === $lbite_rgb ) {
			return $hex;
		}

		foreach ( $lbite_rgb as $lbite_i => $lbite_channel ) {
			if ( $amount < 0 ) {
				$lbite_value = $lbite_channel * ( 1 + $amount );
			} else {
				$lbite_value = $lbite_channel + ( ( 255 - $lbite_channel ) * $amount );
			}

			$lbite_rgb[ $lbite_i ] = max( 0, min( 255, (int) round( $lbite_value ) ) );
		}

		return sprintf( '#%02x%02x%02x', $lbite_rgb[0], $lbite_rgb[1], $lbite_rgb[2] );
	}

	/**
	 * Hex-Farbe als rgba()-String
	 *
	 * @param string $hex   Hex-Farbe inkl. führendem #.
	 * @param float  $alpha Deckkraft 0..1.
	 * @return string
	 */
	private static function hex_to_rgba( $hex, $alpha ) {
		$lbite_rgb = self::hex_to_rgb( $hex );

		if ( null === $lbite_rgb ) {
			return 'rgba(0,115,170,' . $alpha . ')';
		}

		return sprintf( 'rgba(%d,%d,%d,%s)', $lbite_rgb[0], $lbite_rgb[1], $lbite_rgb[2], $alpha );
	}

	/**
	 * Hex-Farbe in RGB-Kanäle zerlegen
	 *
	 * @param string $hex Hex-Farbe inkl. führendem #.
	 * @return array|null Array mit drei Kanälen oder null bei ungültiger Eingabe.
	 */
	private static function hex_to_rgb( $hex ) {
		$lbite_hex = ltrim( (string) $hex, '#' );

		if ( 3 === strlen( $lbite_hex ) ) {
			$lbite_hex = $lbite_hex[0] . $lbite_hex[0] . $lbite_hex[1] . $lbite_hex[1] . $lbite_hex[2] . $lbite_hex[2];
		}

		if ( 6 !== strlen( $lbite_hex ) || ! ctype_xdigit( $lbite_hex ) ) {
			return null;
		}

		return array(
			hexdec( substr( $lbite_hex, 0, 2 ) ),
			hexdec( substr( $lbite_hex, 2, 2 ) ),
			hexdec( substr( $lbite_hex, 4, 2 ) ),
		);
	}

	/**
	 * Admin-Assets laden
	 *
	 * @param string $hook Aktuelle Admin-Seite
	 */
	public function enqueue_admin_assets( $hook ) {
		// Nur auf Plugin-Seiten laden. Die Entscheidung faellt in
		// is_lbite_screen(), damit sie mit der Body-Klasse nicht auseinanderlaeuft.
		if ( ! self::is_lbite_screen( $hook ) ) {
			return;
		}

		// Design-System zuerst: definiert alle Tokens, auf denen das
		// übrige Admin-CSS aufbaut.
		wp_enqueue_style(
			'lbite-admin-design-system',
			LBITE_PLUGIN_URL . 'assets/css/admin-design-system.css',
			array(),
			LBITE_VERSION
		);
		wp_add_inline_style( 'lbite-admin-design-system', self::get_branding_tokens() );

		// CSS
		wp_enqueue_style(
			'lbite-admin',
			LBITE_PLUGIN_URL . 'assets/css/admin.css',
			array( 'lbite-admin-design-system' ),
			LBITE_VERSION
		);

		// Seitenspezifisches CSS
		wp_enqueue_style(
			'lbite-admin-pages',
			LBITE_PLUGIN_URL . 'assets/css/admin-pages.css',
			array( 'lbite-admin' ),
			LBITE_VERSION
		);

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
					'confirmDelete' => __( 'Really delete?', 'libre-bite' ),
					'saveSuccess'   => __( 'Successfully saved', 'libre-bite' ),
					'saveError'     => __( 'Error saving', 'libre-bite' ),
					'noTable'       => __( 'No table', 'libre-bite' ),
				),
			)
		);

		// Einstellungen-Seite JS (nur auf Settings- und Haupt-Plugin-Seite laden).
		if ( strpos( $hook, 'lbite-settings' ) !== false || strpos( $hook, 'lbite-statistics' ) !== false || 'toplevel_page_libre-bite' === $hook ) {
			if ( ! wp_script_is( 'sortablejs', 'registered' ) ) {
				wp_register_script(
					'sortablejs',
					LBITE_PLUGIN_URL . 'assets/js/vendor/sortable.min.js',
					array(),
					LBITE_VERSION,
					true
				);
			}
			wp_enqueue_script( 'sortablejs' );

			wp_enqueue_script(
				'lbite-admin-settings',
				LBITE_PLUGIN_URL . 'assets/js/admin-settings-page.js',
				array( 'jquery', 'sortablejs' ),
				LBITE_VERSION,
				true
			);
			wp_localize_script(
				'lbite-admin-settings',
				'lbiteAdminSettings',
				array(
					'nonce'   => wp_create_nonce( 'lbite_admin_nonce' ),
					'strings' => array(
						'countsAsCompleted' => __( 'Counts as completed', 'libre-bite' ),
						'scheduleFrom'      => __( 'From', 'libre-bite' ),
						'scheduleTo'        => __( 'To', 'libre-bite' ),
					),
				)
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

			$lbite_kanban_dragdrop = lbite_feature_enabled( 'enable_kanban_customization' )
				&& '1' === (string) get_option( 'lbite_kanban_drag_drop_enabled', 0 );
			$lbite_dashboard_deps = array( 'jquery' );
			if ( $lbite_kanban_dragdrop ) {
				if ( ! wp_script_is( 'sortablejs', 'registered' ) ) {
					wp_register_script(
						'sortablejs',
						LBITE_PLUGIN_URL . 'assets/js/vendor/sortable.min.js',
						array(),
						LBITE_VERSION,
						true
					);
				}
				$lbite_dashboard_deps[] = 'sortablejs';
			}

			wp_enqueue_script(
				'lbite-dashboard',
				LBITE_PLUGIN_URL . 'assets/js/dashboard.js',
				$lbite_dashboard_deps,
				LBITE_VERSION,
				true
			);

			// Standort-Farben für Dashboard-Hervorhebung (gecacht via LBite_Locations).
			$lbite_dashboard_colors = LBite_Locations::get_all_location_colors();

			// Zahlungsarten-Labels für Dashboard-Anzeige aufbereiten (key => label).
			$lbite_pm_raw    = get_option( 'lbite_pos_payment_methods', array() );
			$lbite_pm_labels = array();
			$lbite_pm_defaults = array(
				'cash'  => __( 'Cash', 'libre-bite' ),
				'card'  => __( 'Card', 'libre-bite' ),
				'twint' => __( 'Twint', 'libre-bite' ),
				'other' => __( 'Other', 'libre-bite' ),
			);
			foreach ( $lbite_pm_defaults as $lbite_pm_key => $lbite_pm_label ) {
				$lbite_pm_labels[ $lbite_pm_key ] = $lbite_pm_label;
			}
			foreach ( $lbite_pm_raw as $lbite_pm ) {
				if ( ! empty( $lbite_pm['key'] ) && ! empty( $lbite_pm['label'] ) ) {
					$lbite_pm_labels[ $lbite_pm['key'] ] = $lbite_pm['label'];
				}
			}

			$lbite_is_premium   = function_exists( 'lbite_freemius' ) && lbite_freemius()->can_use_premium_code__premium_only();
			$lbite_custom_sound = get_option( 'lbite_notification_sound', '' );
			$lbite_sound_url    = ( $lbite_is_premium && ! empty( $lbite_custom_sound ) )
				? $lbite_custom_sound
				: LBITE_PLUGIN_URL . 'assets/sounds/notification.mp3';

			wp_localize_script(
				'lbite-dashboard',
				'lbiteDashboard',
				array(
					'ajaxUrl'               => admin_url( 'admin-ajax.php' ),
					'orderEditUrl'          => admin_url( 'post.php' ),
					'nonce'                 => wp_create_nonce( 'lbite_dashboard_nonce' ),
					'receiptNonce'          => wp_create_nonce( 'lbite_admin_nonce' ),
					'soundUrl'              => $lbite_sound_url,
					'refreshInterval'       => (int) get_option( 'lbite_dashboard_refresh_interval', 30 ) * 1000,
					'locationColors'        => $lbite_dashboard_colors,
					'paymentMethods'        => $lbite_pm_labels,
					'currency'              => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
					'futureDimmingEnabled'  => lbite_feature_enabled( 'enable_future_orders_dimmed' ) && '0' !== get_option( 'lbite_dim_future_orders', 1 ),
					'kanbanColumns'             => LBite_Order_Dashboard::get_columns(),
					'kanbanCustomizationActive' => lbite_feature_enabled( 'enable_kanban_customization' ),
					'kanbanDragDropEnabled'     => $lbite_kanban_dragdrop,
					'kdsTimerEnabled'           => '0' !== (string) get_option( 'lbite_kds_timer_enabled', 1 ),
					'kdsWarnMinutes'            => (int) get_option( 'lbite_kds_warn_minutes', 0 ),
					'kdsLateMinutes'            => (int) get_option( 'lbite_kds_late_minutes', 0 ),
					'soundRepeatInterval'       => max( 0, (int) get_option( 'lbite_sound_repeat_interval', 0 ) ) * 1000,
					'serverTime'                => time(),
					'receiptTypes'              => class_exists( 'LBite_Receipts' ) ? LBite_Receipts::get_types() : array(),
					'strings'               => array(
						'orderUpdated'    => __( 'Status updated', 'libre-bite' ),
						'updateError'     => __( 'Error updating', 'libre-bite' ),
						'soundActive'     => __( 'Sound active', 'libre-bite' ),
						'soundInactive'   => __( 'Sound off', 'libre-bite' ),
						'loadingOrders'   => __( 'Loading orders...', 'libre-bite' ),
						'loadOrdersError' => __( 'Error loading orders', 'libre-bite' ),
						'loadMoreError'   => __( 'Error loading more orders', 'libre-bite' ),
						'confirmCancel'   => __( "Do you really want to cancel this order?\n\nThe payment will be automatically refunded.", 'libre-bite' ),
						'cancellingOrder' => __( 'Cancelling order...', 'libre-bite' ),
						'orderCancelled'        => __( 'Order cancelled and payment refunded', 'libre-bite' ),
						'orderCancelledNoRefund' => __( 'Order cancelled', 'libre-bite' ),
						'cancelError'     => __( 'Error cancelling', 'libre-bite' ),
						'cancelOrderError'   => __( 'Error cancelling order', 'libre-bite' ),
						'unknownError'       => __( 'Unknown error', 'libre-bite' ),
						'moreOrders'         => __( 'more order(s)', 'libre-bite' ),
						'startPreparation'   => __( 'Prepare Now →', 'libre-bite' ),
						'completed'          => __( '✓ Complete', 'libre-bite' ),
						'cancelOrder'        => __( 'Cancel order', 'libre-bite' ),
						'fullscreen'         => __( 'Fullscreen', 'libre-bite' ),
						'exitFullscreen'     => __( 'Exit fullscreen', 'libre-bite' ),
						'sendReceipt'        => __( 'Send receipt', 'libre-bite' ),
						'enterEmail'         => __( 'Enter customer email address:', 'libre-bite' ),
						'takeaway'           => __( 'Takeaway', 'libre-bite' ),
						'dineIn'             => __( 'Dine-in', 'libre-bite' ),
						'tab'                => __( 'Tab', 'libre-bite' ),
						'openTab'            => __( 'Open tab', 'libre-bite' ),
						'openTabs'           => __( 'Open Tabs', 'libre-bite' ),
						'closeTab'           => __( 'Close tab', 'libre-bite' ),
						'confirmCancelTab'   => __( 'Cancel this tab?', 'libre-bite' ),
						'tabOpened'          => __( 'Tab opened', 'libre-bite' ),
						'tabClosed'          => __( 'Tab closed', 'libre-bite' ),
						'tabCancelled'       => __( 'Tab cancelled', 'libre-bite' ),
						'round'              => __( 'Round', 'libre-bite' ),
						'table'              => __( 'Table', 'libre-bite' ),
						'asap'               => __( 'ASAP', 'libre-bite' ),
						'waiting'            => __( 'Waiting time', 'libre-bite' ),
						'overdue'            => __( 'Overdue', 'libre-bite' ),
						'minutesShort'       => __( 'min', 'libre-bite' ),
						'printReceipt'       => __( 'Print (right-click to choose a template)', 'libre-bite' ),
						'printError'         => __( 'Could not create the receipt', 'libre-bite' ),
						'popupBlocked'       => __( 'Please allow pop-ups for this site to print', 'libre-bite' ),
						'wakeLockUnsupported' => __( 'This browser does not support keeping the screen awake.', 'libre-bite' ),
						'back'               => __( 'Back', 'libre-bite' ),
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
						'reservation'  => __( 'Reservation', 'libre-bite' ),
						'reservations' => __( 'Reservations', 'libre-bite' ),
						'table'        => __( 'Table', 'libre-bite' ),
						'noTable'      => __( 'No table', 'libre-bite' ),
						'knownGuest'   => __( 'Returning guest', 'libre-bite' ),
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

		if ( ! current_user_can( 'lbite_use_pos' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		// Standort-Fixierung: Benutzer mit zugewiesenem Standort können ihn nicht ändern
		$assigned = (int) get_user_meta( get_current_user_id(), 'lbite_assigned_location', true );
		if ( $assigned > 0 && ! current_user_can( 'lbite_manage_locations' ) ) {
			wp_send_json_error( array( 'message' => __( 'Location is fixed for your account', 'libre-bite' ) ) );
		}

		$location_id = isset( $_POST['location_id'] ) ? intval( $_POST['location_id'] ) : 0;

		// Standort für aktuellen Benutzer speichern
		update_user_meta( get_current_user_id(), 'lbite_pos_location', $location_id );

		wp_send_json_success( array( 'location_id' => $location_id ) );
	}

	/**
	 * AJAX: POS-Produkte laden (mit Caching)
	 */
	/**
	 * AJAX: POS-Produkt-Details laden (Varianten & Optionen)
	 */
	public function ajax_pos_get_product_details() {
		check_ajax_referer( 'lbite_pos_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_use_pos' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'No Product ID specified', 'libre-bite' ) ) );
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Product not found', 'libre-bite' ) ) );
		}

		try {

		$response = array(
			'id'                 => $product->get_id(),
			'name'               => $product->get_name(),
			'type'               => $product->get_type(),
			'price'              => $product->get_price(),
			'variations'         => array(),
			'options'            => array(),
			'default_attributes' => $product->is_type( 'variable' ) ? $product->get_default_attributes() : array(),
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
					'name'       => ! empty( $attr_labels ) ? implode( ', ', $attr_labels ) : __( 'Variant', 'libre-bite' ),
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
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		$cart_items = $this->parse_cart_items_from_post();

		$location_id    = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;
		$table_id       = isset( $_POST['table_id'] ) ? intval( wp_unslash( $_POST['table_id'] ) ) : 0;
		$order_type     = 'now'; // POS-Bestellungen sind immer sofort.
		$customer_name  = isset( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : '';
		$payment_method = isset( $_POST['payment_method'] ) ? sanitize_key( wp_unslash( $_POST['payment_method'] ) ) : 'cash';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce bereits geprüft (lbite_pos_nonce); Einträge werden unten per array_map('sanitize_text_field', ...) sanitisiert.
		$raw_coupons    = isset( $_POST['coupon_codes'] ) ? wp_unslash( $_POST['coupon_codes'] ) : '[]';
		$coupon_codes   = json_decode( $raw_coupons, true );
		if ( ! is_array( $coupon_codes ) ) {
			$coupon_codes = array();
		}
		$coupon_codes = array_map( 'sanitize_text_field', $coupon_codes );

		// Erlaubte Zahlungsarten aus Einstellungen lesen (Fallback: alle vier Standardarten).
		$allowed_payment_methods = $this->get_allowed_payment_methods();
		if ( ! in_array( $payment_method, $allowed_payment_methods, true ) ) {
			$payment_method = $allowed_payment_methods[0] ?? 'other';
		}

		// Split-Payment: Beträge parsen und gegen die Zahlungsart-Whitelist validieren.
		$split_payments = $this->parse_split_payments( $allowed_payment_methods );
		if ( ! empty( $split_payments ) ) {
			$payment_method = 'split';
		}

		if ( ! $location_id ) {
			wp_send_json_error( array( 'message' => __( 'No location selected', 'libre-bite' ) ) );
		}

		// Schweizer MWST: Kontext setzen damit der Tax-Filter die richtige Steuerklasse anwendet.
		// Expliziter vat_order_type aus POS-Selektor hat Vorrang, sonst Fallback via Tisch-ID.
		if ( class_exists( 'LBite_Checkout' ) && lbite_feature_enabled( 'enable_swiss_vat' ) ) {
			$vat_order_type = isset( $_POST['vat_order_type'] ) ? sanitize_key( wp_unslash( $_POST['vat_order_type'] ) ) : '';
			if ( 'dine_in' === $vat_order_type ) {
				LBite_Checkout::set_pos_vat_context( 'dine_in' );
			} elseif ( 'takeaway' === $vat_order_type ) {
				LBite_Checkout::set_pos_vat_context( 'takeaway' );
			} else {
				LBite_Checkout::set_pos_vat_context( $table_id ? 'dine_in' : 'takeaway' );
			}
		}

		try {
			// WooCommerce-Bestellung erstellen.
			$order = wc_create_order();

			// Produkte hinzufügen.
			$this->add_cart_items_to_order( $order, $cart_items );

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

			$order->update_meta_data( '_lbite_order_type', 'now' );
			$order->delete_meta_data( '_lbite_pickup_time' ); // POS-Bestellungen sind immer sofort.
			$order->update_meta_data( '_lbite_order_status', LBite_Order_Dashboard::KEY_ACTIVE );
			$order->update_meta_data( '_lbite_order_source', 'pos' );
			$order->update_meta_data( '_lbite_payment_method', $payment_method );

			// Kundenname speichern (falls angegeben).
			if ( ! empty( $customer_name ) ) {
				$order->set_billing_first_name( $customer_name );
				$order->update_meta_data( '_lbite_customer_name', $customer_name );
			}

			// Gutscheine anwenden (vor calculate_totals).
			foreach ( $coupon_codes as $coupon_code ) {
				if ( ! empty( $coupon_code ) ) {
					$order->apply_coupon( $coupon_code );
				}
			}

			// Berechnen.
			$order->calculate_totals();

			// Split-Payment: Summe gegen das Bestelltotal abgleichen (Toleranz wegen Rappenrundung/Coupons).
			if ( ! empty( $split_payments ) ) {
				$reconciled_split = $this->reconcile_split_payments( $split_payments, (float) $order->get_total() );
				if ( null === $reconciled_split ) {
					if ( class_exists( 'LBite_Checkout' ) ) {
						LBite_Checkout::clear_pos_vat_context();
					}
					$order->delete( true );
					delete_transient( 'lbite_incoming_orders_count' );
					wp_send_json_error( array( 'message' => __( 'Split amounts do not match the order total', 'libre-bite' ) ) );
				}
				$split_payments = $reconciled_split;
				$order->update_meta_data( '_lbite_split_payments', $split_payments );
			}

			// Schweizer MWST: Kontext zurücksetzen.
			if ( class_exists( 'LBite_Checkout' ) ) {
				LBite_Checkout::clear_pos_vat_context();
			}

			// Status setzen.
			$order->update_status( 'processing', __( 'Order created via POS system.', 'libre-bite' ) );

			// Transient-Cache löschen, damit der Kanban-Badge-Counter sofort aktualisiert wird.
			delete_transient( 'lbite_incoming_orders_count' );

			/**
			 * Nach dem Anlegen einer Bestellung an der Kasse.
			 *
			 * @param int      $order_id Bestell-ID.
			 * @param WC_Order $order    Bestellobjekt.
			 */
			do_action( 'lbite_pos_order_created', $order->get_id(), $order );

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
			if ( class_exists( 'LBite_Checkout' ) ) {
				LBite_Checkout::clear_pos_vat_context();
			}
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Warenkorb-Positionen (Produkte, Add-ons, Notizen) einer Bestellung hinzufügen.
	 *
	 * Gemeinsam genutzt von ajax_pos_create_order() (regulärer POS-Bestellfluss) und den
	 * Open-Tabs-Endpoints (F_TAB): $round > 0 markiert nachgebuchte Positionen mit der
	 * Rundennummer, damit die Küche im Kanban erkennt, was neu ist.
	 *
	 * @param WC_Order $order      Ziel-Bestellung.
	 * @param array    $cart_items Validierte Warenkorb-Items [{id, quantity, meta, option_ids, note}].
	 * @param int      $round      0 = regulärer Bestellfluss, >0 = Tab-Rundennummer.
	 */
	private function add_cart_items_to_order( WC_Order $order, array $cart_items, int $round = 0 ) {
		foreach ( $cart_items as $item ) {
			$product = wc_get_product( $item['id'] );
			if ( ! $product || ! $product->is_purchasable() ) {
				continue;
			}

			// Nettopreis berechnen: bei aktiver Schweizer MWST-Umschaltung Zielstufe nutzen,
			// da wc_get_price_excluding_tax() intern get_tax_class('unfiltered') aufruft.
			$unit_price_excl = ( class_exists( 'LBite_Checkout' ) && lbite_feature_enabled( 'enable_swiss_vat' ) )
				? LBite_Checkout::gross_to_net_at_filtered_class( $product, (float) $product->get_price() )
				: wc_get_price_excluding_tax( $product );
			$price_excl_tax  = $unit_price_excl * $item['quantity'];

			$order_item_id = $order->add_product(
				$product,
				$item['quantity'],
				array(
					'subtotal' => $price_excl_tax,
					'total'    => $price_excl_tax,
				)
			);

			// Add-ons als separate Gebührenpositionen erfassen (je Add-on eine eigene Zeile).
			$addon_names_for_meta = array();
			$fee_items_for_round  = array();
			if ( ! empty( $item['option_ids'] ) ) {
				// _lbite_product_options hängt am Eltern-Produkt; bei Varianten Parent-ID nutzen.
				$options_lookup_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $item['id'];
				$allowed_options   = get_post_meta( $options_lookup_id, '_lbite_product_options', true );
				if ( ! is_array( $allowed_options ) ) {
					$allowed_options = array();
				}
				foreach ( $item['option_ids'] as $opt_id ) {
					// Lose Prüfung: WP serialisiert Integers; JSON-Decode liefert ebenfalls Integers,
					// aber beim Mischen von alten/neuen Datenbankwerten können Typen abweichen.
					if ( ! in_array( $opt_id, $allowed_options ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						continue;
					}
					$opt_price = get_post_meta( $opt_id, '_lbite_price', true );
					$opt_name  = get_the_title( $opt_id );
					if ( ! $opt_name ) {
						$opt_name = __( 'Add-on', 'libre-bite' );
					}
					$addon_names_for_meta[] = $opt_name;
					if ( $opt_price ) {
						$fee_net = ( class_exists( 'LBite_Checkout' ) && lbite_feature_enabled( 'enable_swiss_vat' ) )
							? LBite_Checkout::gross_to_net_at_filtered_class( $product, floatval( $opt_price ) ) * $item['quantity']
							: wc_get_price_excluding_tax( $product, array( 'price' => floatval( $opt_price ) ) ) * $item['quantity'];
						$fee_item = new WC_Order_Item_Fee();
						$fee_item->set_name( $opt_name );
						$fee_item->set_amount( $fee_net );
						$fee_item->set_total( $fee_net );
						$fee_item->set_tax_class( $product->get_tax_class() );
						$fee_item->set_tax_status( $product->get_tax_status() );
						$order->add_item( $fee_item );
						$fee_items_for_round[] = $fee_item;
					}
				}
			}

			// Meta-Daten (Varianten & Optionen) hinzufügen.
			if ( $order_item_id ) {
				$order_item = $order->get_item( $order_item_id );
				if ( $order_item ) {
					if ( ! empty( $addon_names_for_meta ) ) {
						$order_item->add_meta_data( 'Add-on', implode( ', ', $addon_names_for_meta ), true );
					} elseif ( ! empty( $item['meta'] ) ) {
						$order_item->add_meta_data( 'Add-on', $item['meta'], true );
					}
					if ( ! empty( $item['note'] ) && lbite_feature_enabled( 'enable_item_notes_pos' ) ) {
						$order_item->add_meta_data( 'Note', sanitize_text_field( $item['note'] ), true );
					}
					if ( $round > 0 ) {
						$order_item->add_meta_data( '_lbite_tab_round', $round, true );
					}
					$order_item->save();
				}
			}

			// Rundennummer auch auf zugehörigen Add-on-Fee-Positionen speichern (Küchen-Sichtbarkeit).
			if ( $round > 0 ) {
				foreach ( $fee_items_for_round as $fee_item ) {
					$fee_item->add_meta_data( '_lbite_tab_round', $round, true );
					$fee_item->save();
				}
			}
		}
	}

	/**
	 * Warenkorb-Items aus $_POST['cart_items'] parsen und validieren.
	 *
	 * Gemeinsam genutzt von ajax_pos_create_order(), ajax_pos_open_tab() und ajax_pos_add_to_tab().
	 * Bricht bei leerem/ungültigem Warenkorb den Request direkt via wp_send_json_error() ab.
	 *
	 * @return array Validierte Items [{id, quantity, meta, option_ids, note}].
	 */
	private function parse_cart_items_from_post() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON wird nach dem Decode Feld für Feld validiert.
		$cart_items_raw = isset( $_POST['cart_items'] ) ? wp_unslash( $_POST['cart_items'] ) : '';

		if ( empty( $cart_items_raw ) ) {
			wp_send_json_error( array( 'message' => __( 'Cart is empty', 'libre-bite' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON wird nach dem Decode Feld für Feld validiert.
		$cart_items_decoded = json_decode( $cart_items_raw, true );

		if ( ! is_array( $cart_items_decoded ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid cart data', 'libre-bite' ) ) );
		}

		$cart_items = array();

		foreach ( $cart_items_decoded as $raw_item ) {
			if ( ! is_array( $raw_item ) ) {
				continue;
			}

			$product_id = isset( $raw_item['id'] ) ? absint( $raw_item['id'] ) : 0;
			$quantity   = isset( $raw_item['quantity'] ) ? (int) $raw_item['quantity'] : 0;
			$meta       = isset( $raw_item['meta'] ) ? sanitize_text_field( $raw_item['meta'] ) : '';
			$note       = isset( $raw_item['note'] ) ? sanitize_text_field( $raw_item['note'] ) : '';
			$option_ids = array();
			if ( isset( $raw_item['option_ids'] ) && is_array( $raw_item['option_ids'] ) ) {
				$option_ids = array_map( 'absint', $raw_item['option_ids'] );
			}

			if ( ! $product_id || $quantity <= 0 ) {
				continue;
			}

			$cart_items[] = array(
				'id'         => $product_id,
				'quantity'   => $quantity,
				'meta'       => $meta,
				'option_ids' => $option_ids,
				'note'       => $note,
			);
		}

		if ( empty( $cart_items ) ) {
			wp_send_json_error( array( 'message' => __( 'Cart is empty', 'libre-bite' ) ) );
		}

		return $cart_items;
	}

	/**
	 * Erlaubte POS-Zahlungsart-Keys aus den Einstellungen lesen (Fallback: vier Standardarten).
	 *
	 * @return array Liste gültiger Zahlungsart-Keys.
	 */
	private function get_allowed_payment_methods() {
		$configured_methods = get_option( 'lbite_pos_payment_methods', array() );
		return ! empty( $configured_methods )
			? array_column( $configured_methods, 'key' )
			: array( 'cash', 'card', 'twint', 'other' );
	}

	/**
	 * AJAX: Offene Tabs (F_TAB) für einen Standort auflisten.
	 */
	public function ajax_pos_get_open_tabs() {
		check_ajax_referer( 'lbite_pos_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_use_pos' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		if ( ! lbite_feature_enabled( 'enable_open_tabs' ) ) {
			wp_send_json_error( array( 'message' => __( 'Open Tabs is not enabled', 'libre-bite' ) ) );
		}

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;
		if ( ! $location_id ) {
			wp_send_json_success( array( 'tabs' => array() ) );
		}

		$orders = wc_get_orders( array(
			'limit'      => 100,
			'status'     => array( 'wc-on-hold' ),
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
					'key'   => '_lbite_tab_open',
					'value' => '1',
				),
				array(
					'key'   => '_lbite_location_id',
					'value' => $location_id,
				),
			),
		) );

		$tabs = array();
		foreach ( $orders as $tab_order ) {
			$items = array();
			foreach ( $tab_order->get_items() as $tab_item ) {
				$items[] = array(
					'name'     => $tab_item->get_name(),
					'quantity' => $tab_item->get_quantity(),
					'round'    => (int) $tab_item->get_meta( '_lbite_tab_round', true ),
				);
			}
			$tab_date_created = $tab_order->get_date_created();
			$tabs[]            = array(
				'order_id'      => $tab_order->get_id(),
				'table_id'      => (int) $tab_order->get_meta( '_lbite_table_id', true ),
				'table_name'    => $tab_order->get_meta( '_lbite_table_name', true ),
				'customer_name' => $tab_order->get_meta( '_lbite_customer_name', true ),
				'total'         => html_entity_decode( wp_strip_all_tags( $tab_order->get_formatted_order_total() ), ENT_QUOTES, 'UTF-8' ),
				'total_raw'     => (float) $tab_order->get_total(),
				'item_count'    => count( $items ),
				'round_count'   => (int) $tab_order->get_meta( '_lbite_tab_round_count', true ),
				'created'       => $tab_date_created ? $tab_date_created->date( 'H:i' ) : '',
				'items'         => $items,
			);
		}

		wp_send_json_success( array( 'tabs' => $tabs ) );
	}

	/**
	 * AJAX: Neuen offenen Tab an einem Tisch eröffnen (F_TAB).
	 */
	public function ajax_pos_open_tab() {
		check_ajax_referer( 'lbite_pos_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_use_pos' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		if ( ! lbite_feature_enabled( 'enable_open_tabs' ) ) {
			wp_send_json_error( array( 'message' => __( 'Open Tabs is not enabled', 'libre-bite' ) ) );
		}

		$cart_items = $this->parse_cart_items_from_post();

		$location_id   = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;
		$table_id      = isset( $_POST['table_id'] ) ? intval( wp_unslash( $_POST['table_id'] ) ) : 0;
		$customer_name = isset( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : '';

		if ( ! $location_id ) {
			wp_send_json_error( array( 'message' => __( 'No location selected', 'libre-bite' ) ) );
		}
		if ( ! $table_id ) {
			wp_send_json_error( array( 'message' => __( 'Please select a table', 'libre-bite' ) ) );
		}

		if ( class_exists( 'LBite_Checkout' ) && lbite_feature_enabled( 'enable_swiss_vat' ) ) {
			LBite_Checkout::set_pos_vat_context( 'dine_in' );
		}

		try {
			$order = wc_create_order();

			$this->add_cart_items_to_order( $order, $cart_items, 1 );

			$order->update_meta_data( '_lbite_location_id', $location_id );

			$location = get_post( $location_id );
			if ( $location ) {
				$order->update_meta_data( '_lbite_location_name', $location->post_title );
			}

			$order->update_meta_data( '_lbite_table_id', $table_id );
			$table = get_post( $table_id );
			if ( $table ) {
				$order->update_meta_data( '_lbite_table_name', $table->post_title );
			}

			$order->update_meta_data( '_lbite_order_type', 'now' );
			$order->update_meta_data( '_lbite_order_status', LBite_Order_Dashboard::KEY_ACTIVE );
			$order->update_meta_data( '_lbite_order_source', 'pos' );
			$order->update_meta_data( '_lbite_service_type', 'dine_in' );
			$order->update_meta_data( '_lbite_tab_open', '1' );
			$order->update_meta_data( '_lbite_tab_round_count', 1 );

			if ( ! empty( $customer_name ) ) {
				$order->set_billing_first_name( $customer_name );
				$order->update_meta_data( '_lbite_customer_name', $customer_name );
			}

			$order->calculate_totals();

			if ( class_exists( 'LBite_Checkout' ) ) {
				LBite_Checkout::clear_pos_vat_context();
			}

			$order->update_status( 'on-hold', __( 'Tab opened via POS.', 'libre-bite' ) );

			delete_transient( 'lbite_incoming_orders_count' );

			/**
			 * Nach dem Eröffnen eines offenen Tabs an einem Tisch.
			 *
			 * @param int      $order_id Bestell-ID.
			 * @param WC_Order $order    Bestellobjekt.
			 */
			do_action( 'lbite_tab_opened', $order->get_id(), $order );

			wp_send_json_success( array( 'order_id' => $order->get_id() ) );
		} catch ( Exception $e ) {
			if ( class_exists( 'LBite_Checkout' ) ) {
				LBite_Checkout::clear_pos_vat_context();
			}
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Offenen Tab anhand order_id + location_id laden und validieren. Bricht den Request bei
	 * fehlender/fremder/geschlossener Bestellung via wp_send_json_error() ab.
	 *
	 * @param int $expected_location_id Erwartete Standort-ID (0 = nicht prüfen).
	 * @return WC_Order
	 */
	private function get_validated_open_tab_order( $expected_location_id = 0 ) {
		$order_id = isset( $_POST['order_id'] ) ? intval( wp_unslash( $_POST['order_id'] ) ) : 0;
		$order    = $order_id ? wc_get_order( $order_id ) : false;

		if ( ! $order instanceof WC_Order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'libre-bite' ) ) );
		}
		if ( 'on-hold' !== $order->get_status() || '1' !== (string) $order->get_meta( '_lbite_tab_open', true ) ) {
			wp_send_json_error( array( 'message' => __( 'This tab is no longer open', 'libre-bite' ) ) );
		}
		if ( $expected_location_id && (int) $order->get_meta( '_lbite_location_id', true ) !== $expected_location_id ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'libre-bite' ) ) );
		}

		return $order;
	}

	/**
	 * AJAX: Weitere Positionen zu einem offenen Tab hinzubuchen (F_TAB).
	 */
	public function ajax_pos_add_to_tab() {
		check_ajax_referer( 'lbite_pos_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_use_pos' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		if ( ! lbite_feature_enabled( 'enable_open_tabs' ) ) {
			wp_send_json_error( array( 'message' => __( 'Open Tabs is not enabled', 'libre-bite' ) ) );
		}

		$cart_items  = $this->parse_cart_items_from_post();
		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;
		$order       = $this->get_validated_open_tab_order( $location_id );

		// VAT-Kontext aus dem beim Öffnen persistierten Bestelltyp ableiten, nicht aus dem Client-POST
		// (sonst würden Nachbuchungen bei aktivierter Schweizer MWST die falsche Steuerklasse erhalten).
		if ( class_exists( 'LBite_Checkout' ) && lbite_feature_enabled( 'enable_swiss_vat' ) ) {
			$service_type = $order->get_meta( '_lbite_service_type', true );
			LBite_Checkout::set_pos_vat_context( 'dine_in' === $service_type ? 'dine_in' : 'takeaway' );
		}

		try {
			$round = (int) $order->get_meta( '_lbite_tab_round_count', true ) + 1;

			$this->add_cart_items_to_order( $order, $cart_items, $round );

			$order->update_meta_data( '_lbite_tab_round_count', $round );
			$order->calculate_totals();

			if ( class_exists( 'LBite_Checkout' ) ) {
				LBite_Checkout::clear_pos_vat_context();
			}

			// translators: %d: Rundennummer.
			$order->add_order_note( sprintf( __( 'Round %d added via POS', 'libre-bite' ), $round ) );
			$order->save();

			/**
			 * Nach dem Nachbuchen einer weiteren Runde auf einen offenen Tab.
			 *
			 * @param int      $order_id Bestell-ID.
			 * @param int      $round    Rundennummer.
			 * @param WC_Order $order    Bestellobjekt.
			 */
			do_action( 'lbite_tab_round_added', $order->get_id(), $round, $order );

			wp_send_json_success( array( 'order_id' => $order->get_id(), 'round' => $round ) );
		} catch ( Exception $e ) {
			if ( class_exists( 'LBite_Checkout' ) ) {
				LBite_Checkout::clear_pos_vat_context();
			}
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX: Offenen Tab abschliessen und bezahlen (F_TAB).
	 */
	public function ajax_pos_close_tab() {
		check_ajax_referer( 'lbite_pos_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_use_pos' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		if ( ! lbite_feature_enabled( 'enable_open_tabs' ) ) {
			wp_send_json_error( array( 'message' => __( 'Open Tabs is not enabled', 'libre-bite' ) ) );
		}

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;
		$order       = $this->get_validated_open_tab_order( $location_id );

		$allowed_payment_methods = $this->get_allowed_payment_methods();
		$payment_method          = isset( $_POST['payment_method'] ) ? sanitize_key( wp_unslash( $_POST['payment_method'] ) ) : 'cash';
		if ( ! in_array( $payment_method, $allowed_payment_methods, true ) ) {
			$payment_method = $allowed_payment_methods[0] ?? 'other';
		}

		$split_payments = $this->parse_split_payments( $allowed_payment_methods );
		if ( ! empty( $split_payments ) ) {
			$payment_method = 'split';
			$reconciled     = $this->reconcile_split_payments( $split_payments, (float) $order->get_total() );
			if ( null === $reconciled ) {
				wp_send_json_error( array( 'message' => __( 'Split amounts do not match the order total', 'libre-bite' ) ) );
			}
			$split_payments = $reconciled;
		}

		$order->update_meta_data( '_lbite_payment_method', $payment_method );
		if ( ! empty( $split_payments ) ) {
			$order->update_meta_data( '_lbite_split_payments', $split_payments );
		}
		$order->delete_meta_data( '_lbite_tab_open' );
		$order->update_status( 'processing', __( 'Tab closed via POS.', 'libre-bite' ) );

		delete_transient( 'lbite_incoming_orders_count' );

		/**
		 * Nach dem Abschluss eines offenen Tabs.
		 *
		 * @param int      $order_id Bestell-ID.
		 * @param WC_Order $order    Bestellobjekt.
		 */
		do_action( 'lbite_tab_closed', $order->get_id(), $order );

		wp_send_json_success( array( 'order_id' => $order->get_id() ) );
	}

	/**
	 * AJAX: Offenen Tab stornieren (F_TAB) – eigener Endpoint statt Nonce-Streuung, da
	 * ajax_cancel_order() im Kanban-Modul einen anderen Nonce (lbite_dashboard_nonce) erwartet.
	 */
	public function ajax_pos_cancel_tab() {
		check_ajax_referer( 'lbite_pos_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_use_pos' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		if ( ! lbite_feature_enabled( 'enable_open_tabs' ) ) {
			wp_send_json_error( array( 'message' => __( 'Open Tabs is not enabled', 'libre-bite' ) ) );
		}

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;
		$order       = $this->get_validated_open_tab_order( $location_id );

		$order->update_status( 'cancelled', __( 'Tab cancelled via POS.', 'libre-bite' ) );
		delete_transient( 'lbite_incoming_orders_count' );

		wp_send_json_success( array( 'order_id' => $order->get_id() ) );
	}

	/**
	 * Split-Payment-Beträge aus $_POST['split_payments'] parsen und gegen die Zahlungsart-
	 * Whitelist validieren. Gemeinsam genutzt von ajax_pos_create_order() und ajax_pos_close_tab().
	 *
	 * @param array $allowed_payment_methods Whitelist gültiger Zahlungsart-Keys.
	 * @return array Liste [{method, amount}], leer wenn Feature aus oder kein Split gesendet.
	 */
	private function parse_split_payments( array $allowed_payment_methods ) {
		$split_payments = array();
		if ( ! lbite_feature_enabled( 'enable_split_payment' ) || empty( $_POST['split_payments'] ) ) {
			return $split_payments;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON wird nach dem Decode Feld für Feld validiert.
		$split_payments_raw     = wp_unslash( $_POST['split_payments'] );
		$split_payments_decoded = json_decode( $split_payments_raw, true );
		if ( ! is_array( $split_payments_decoded ) ) {
			return $split_payments;
		}
		foreach ( $split_payments_decoded as $split_row ) {
			if ( ! is_array( $split_row ) || empty( $split_row['method'] ) ) {
				continue;
			}
			$split_method = sanitize_key( $split_row['method'] );
			$split_amount = round( (float) ( $split_row['amount'] ?? 0 ), 2 );
			if ( ! in_array( $split_method, $allowed_payment_methods, true ) || $split_amount <= 0 ) {
				continue;
			}
			$split_payments[] = array(
				'method' => $split_method,
				'amount' => $split_amount,
			);
		}
		return $split_payments;
	}

	/**
	 * Split-Payment-Summe gegen das Bestelltotal abgleichen und kleine Abweichungen normalisieren.
	 *
	 * Toleranzstufen: Abweichung > 0.10 gilt als echte Fehlbedienung (Aufrufer lehnt ab),
	 * 0.001–0.10 wird als Rappenrundung/Coupon-Drift dem grössten Split-Eintrag zugeschlagen,
	 * damit array_sum( $split_payments ) === $order->get_total() als Invariante gilt.
	 * Gemeinsam genutzt von ajax_pos_create_order() und ajax_pos_close_tab().
	 *
	 * @param array $split_payments Liste [{method, amount}].
	 * @param float $order_total    Tatsächliches Bestelltotal.
	 * @return array|null Normalisierte Liste, oder null bei Abweichung > 0.10.
	 */
	private function reconcile_split_payments( array $split_payments, float $order_total ) {
		$split_sum = 0.0;
		foreach ( $split_payments as $split_row ) {
			$split_sum += $split_row['amount'];
		}
		$diff = round( $order_total - $split_sum, 2 );

		if ( abs( $diff ) > 0.10 ) {
			return null;
		}

		if ( 0.0 !== $diff ) {
			$largest_index = 0;
			foreach ( $split_payments as $split_index => $split_row ) {
				if ( $split_row['amount'] > $split_payments[ $largest_index ]['amount'] ) {
					$largest_index = $split_index;
				}
			}
			$split_payments[ $largest_index ]['amount'] = round( $split_payments[ $largest_index ]['amount'] + $diff, 2 );
		}

		return $split_payments;
	}

	/**
	 * AJAX: Aktive WooCommerce-Gutscheine für POS-Auswahl liefern
	 */
	public function ajax_pos_get_coupons() {
		check_ajax_referer( 'lbite_pos_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_use_pos' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		$today = current_time( 'Y-m-d' );

		$coupon_posts = get_posts(
			array(
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$coupons = array();
		foreach ( $coupon_posts as $coupon_post ) {
			$coupon      = new WC_Coupon( $coupon_post->post_title );
			$expiry_date = $coupon->get_date_expires();
			if ( $expiry_date && $expiry_date->date( 'Y-m-d' ) < $today ) {
				continue; // Abgelaufene Gutscheine überspringen.
			}

			$coupons[] = array(
				'code'          => $coupon->get_code(),
				'description'   => $coupon_post->post_excerpt,
				'discount_type' => $coupon->get_discount_type(),
				'amount'        => (float) $coupon->get_amount(),
			);
		}

		wp_send_json_success( array( 'coupons' => $coupons ) );
	}

	/**
	 * AJAX: Lagerbestand eines Produkts umschalten (vorr��tig / nicht vorrätig)
	 */
	public function ajax_pos_toggle_stock() {
		check_ajax_referer( 'lbite_pos_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_use_pos' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		$product_id        = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$stock_status      = isset( $_POST['stock_status'] ) ? sanitize_text_field( wp_unslash( $_POST['stock_status'] ) ) : '';
		$unavailable_until = isset( $_POST['unavailable_until'] ) ? sanitize_text_field( wp_unslash( $_POST['unavailable_until'] ) ) : '';

		if ( ! $product_id || ! in_array( $stock_status, array( 'instock', 'outofstock' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters', 'libre-bite' ) ) );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Product not found', 'libre-bite' ) ) );
		}

		$product->set_stock_status( $stock_status );
		$product->save();

		if ( 'outofstock' === $stock_status && '' !== $unavailable_until ) {
			update_post_meta( $product_id, '_lbite_unavailable_until', $unavailable_until );
		} else {
			delete_post_meta( $product_id, '_lbite_unavailable_until' );
		}

		// Bei variablen Produkten: Lagerbestand-Status aller Varianten setzen,
		// damit WooCommerces Sync-Mechanismus den Parent-Status nicht überschreibt.
		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $child_id ) {
				$variation = wc_get_product( $child_id );
				if ( $variation ) {
					$variation->set_stock_status( $stock_status );
					$variation->save();
				}
			}
			WC_Product_Variable::sync( $product_id );
		}

		// POS-Transient-Cache für alle Standorte leeren.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk-Löschung von Transients via LIKE, WP-API unterstützt kein Wildcard-Delete.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_lbite_pos_products_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_lbite_pos_products_' ) . '%'
			)
		);

		wp_send_json_success( array( 'stock_status' => $stock_status ) );
	}

	/**
	 * AJAX: Theme-Farben abrufen
	 */
	public function ajax_get_theme_colors() {
		check_ajax_referer( 'lbite_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
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
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$features_json = isset( $_POST['features'] ) ? wp_unslash( $_POST['features'] ) : '';
		$features      = json_decode( $features_json, true );

		if ( ! is_array( $features ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data', 'libre-bite' ) ) );
		}

		$known_keys = array_keys( LBite_Features::get_definitions() );

		// Alle Feature-Werte als boolean sanitieren; nur bekannte Keys übernehmen.
		// Die Lizenzprüfung erfolgt ausschliesslich zur Laufzeit via lbite_feature_enabled() –
		// der gespeicherte Wert hat keinen Effekt wenn der Premium-Code nicht ausführbar ist.
		$sanitized_features = array();
		foreach ( $features as $key => $value ) {
			$clean_key = sanitize_key( $key );
			if ( ! in_array( $clean_key, $known_keys, true ) ) {
				continue;
			}
			$sanitized_features[ $clean_key ] = (bool) $value;
		}

		update_option( 'lbite_features', $sanitized_features );

		wp_send_json_success( array( 'message' => __( 'Settings saved', 'libre-bite' ) ) );
	}

	/**
	 * AJAX: Support-Einstellungen speichern
	 */
	public function ajax_save_support_settings() {
		check_ajax_referer( 'lbite_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_manage_support' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		$settings = array(
			'support_email'        => isset( $_POST['support_email'] ) ? sanitize_email( wp_unslash( $_POST['support_email'] ) ) : '',
			'support_phone'        => isset( $_POST['support_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['support_phone'] ) ) : '',
			'support_hours'        => isset( $_POST['support_hours'] ) ? sanitize_text_field( wp_unslash( $_POST['support_hours'] ) ) : '',
			'support_billing_note' => isset( $_POST['support_billing_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['support_billing_note'] ) ) : '',
			'support_custom_text'  => isset( $_POST['support_custom_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['support_custom_text'] ) ) : '',
		);

		update_option( 'lbite_support_settings', $settings );

		wp_send_json_success( array( 'message' => __( 'Support settings saved', 'libre-bite' ) ) );
	}

	/**
	 * AJAX: Welcome-Notice schliessen
	 */
	public function ajax_dismiss_welcome_notice() {
		check_ajax_referer( 'lbite_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		update_option( 'lbite_show_welcome_notice', false );
		wp_send_json_success();
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

		// Auf der Support-Seite nicht anzeigen (Kontaktdaten sind dort bereits sichtbar)
		if ( strpos( $screen->id, 'lbite-support' ) !== false ) {
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
			<span><?php esc_html_e( 'Help', 'libre-bite' ); ?></span>

			<div id="lbite-help-panel" role="dialog" aria-label="<?php esc_attr_e( 'Support', 'libre-bite' ); ?>">
				<div class="lbite-help-panel-head">
					<strong><?php esc_html_e( 'Support', 'libre-bite' ); ?></strong>
					<button type="button" class="lbite-help-panel-close" aria-label="<?php esc_attr_e( 'Close', 'libre-bite' ); ?>">&#x2715;</button>
				</div>

				<!-- Support-Kontakt: primäre Aktion -->
				<div class="lbite-help-panel-primary">
					<p class="lbite-help-panel-primary-text">
						<?php esc_html_e( 'Need help? Reach out using the contact details below.', 'libre-bite' ); ?>
					</p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-support' ) ); ?>" class="button button-primary button-large lbite-help-panel-cta">
						<span class="dashicons dashicons-sos"></span>
						<?php esc_html_e( 'Open Support Page', 'libre-bite' ); ?>
					</a>
				</div>

				<div class="lbite-help-panel-support">
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
		<?php
	}

	/**
	 * AJAX: Tische für einen Standort abrufen
	 */
	public function ajax_get_location_tables() {
		check_ajax_referer( 'lbite_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_use_pos' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
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

	/**
	 * Metabox «Beleg» in WooCommerce-Bestellansicht registrieren (nur Premium).
	 */
	public function add_receipt_metabox__premium_only() {
		// HPOS-kompatibel: beide Screen-IDs registrieren.
		$screens = array( 'shop_order', 'woocommerce_page_wc-orders' );
		foreach ( $screens as $screen ) {
			add_meta_box(
				'lbite-receipt-metabox',
				__( 'Receipt', 'libre-bite' ),
				array( $this, 'render_receipt_metabox__premium_only' ),
				$screen,
				'side',
				'default'
			);
		}
	}

	/**
	 * Inhalt der Beleg-Metabox rendern (nur Premium).
	 *
	 * @param WP_Post|WC_Order $post_or_order Post- oder Order-Objekt.
	 */
	public function render_receipt_metabox__premium_only( $post_or_order ) {
		$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
		if ( ! $order ) {
			return;
		}

		$order_id      = $order->get_id();
		$sent_at       = $order->get_meta( '_lbite_receipt_sent' );
		$billing_email = $order->get_billing_email();
		$is_dummy      = empty( $billing_email ) || strpos( $billing_email, '@nomail.local' ) !== false;
		$nonce         = wp_create_nonce( 'lbite_admin_nonce' );
		?>
		<?php if ( $is_dummy ) : ?>
		<p>
			<input type="email" id="lbite-receipt-email-input" class="widefat"
				placeholder="customer@example.com">
		</p>
		<?php else : ?>
		<p style="margin-bottom: 6px;">
			<strong><?php esc_html_e( 'To:', 'libre-bite' ); ?></strong> <?php echo esc_html( $billing_email ); ?>
		</p>
		<?php endif; ?>
		<?php if ( $sent_at ) : ?>
			<p style="margin-bottom: 10px; color: #46b450;">
				<?php
				/* translators: %s: date/time */
				printf( esc_html__( 'Last sent: %s', 'libre-bite' ), esc_html( $sent_at ) );
				?>
			</p>
		<?php endif; ?>
		<button type="button" id="lbite-admin-send-receipt-btn" class="button"
			style="width: 100%;"
			data-order-id="<?php echo esc_attr( $order_id ); ?>"
			data-nonce="<?php echo esc_attr( $nonce ); ?>"
			data-has-email="<?php echo $is_dummy ? '0' : '1'; ?>">
			<?php esc_html_e( 'Send Receipt by Email', 'libre-bite' ); ?>
		</button>
		<p id="lbite-receipt-msg" style="margin-top: 6px; display: none;"></p>
		<script>
		jQuery(document).ready(function($) {
			$('#lbite-admin-send-receipt-btn').on('click', function() {
				var $btn = $(this);
				var hasEmail = $btn.data('has-email') === 1 || $btn.data('has-email') === '1';
				var email = '';

				if (!hasEmail) {
					email = $('#lbite-receipt-email-input').val().trim();
					if (!email) {
						alert('<?php echo esc_js( __( 'Please enter a valid email address.', 'libre-bite' ) ); ?>');
						return;
					}
				}

				$btn.prop('disabled', true);
				var postData = {
					action: 'lbite_admin_send_receipt',
					order_id: $btn.data('order-id'),
					nonce: $btn.data('nonce')
				};
				if (email) {
					postData.email = email;
				}
				$.post(ajaxurl, postData, function(response) {
					var $msg = $('#lbite-receipt-msg');
					$msg.show();
					if (response.success) {
						$btn.prop('disabled', false);
						$msg.css('color', '#46b450').text(response.data);
						if (email) {
							// Gespeicherte E-Mail anzeigen, Eingabefeld ausblenden
							var $input = $('#lbite-receipt-email-input');
							$input.closest('p').replaceWith('<p style="margin-bottom: 6px;"><strong><?php echo esc_js( __( 'To:', 'libre-bite' ) ); ?></strong> ' + $('<span>').text(email).html() + '</p>');
							$btn.data('has-email', '1');
						}
					} else {
						$btn.prop('disabled', false);
						$msg.css('color', '#dc3232').text(response.data || '<?php echo esc_js( __( 'Error', 'libre-bite' ) ); ?>');
					}
				}).fail(function() {
					$btn.prop('disabled', false);
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * AJAX: Beleg per E-Mail senden (Admin, nur Premium).
	 */
	public function ajax_admin_send_receipt__premium_only() {
		check_ajax_referer( 'lbite_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_manage_orders' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'libre-bite' ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
		if ( ! $order_id ) {
			wp_send_json_error( __( 'Invalid order.', 'libre-bite' ) );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( __( 'Order not found.', 'libre-bite' ) );
		}

		$billing_email = $order->get_billing_email();
		$is_dummy      = empty( $billing_email ) || strpos( $billing_email, '@nomail.local' ) !== false;

		if ( $is_dummy ) {
			$override_email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
			if ( ! is_email( $override_email ) ) {
				wp_send_json_error( __( 'Please enter a valid email address.', 'libre-bite' ) );
			}
			// E-Mail dauerhaft speichern – für spätere Belege verfügbar.
			$order->set_billing_email( $override_email );
			$order->save();
		}

		$emails = WC()->mailer()->get_emails();
		if ( isset( $emails['WC_Email_Customer_Invoice'] ) ) {
			$emails['WC_Email_Customer_Invoice']->trigger( $order_id );
		}

		$sent_at = current_time( 'mysql' );
		$order->update_meta_data( '_lbite_receipt_sent', $sent_at );
		$order->save();

		/* translators: %s: date/time */
		wp_send_json_success( sprintf( __( 'Receipt sent (%s)', 'libre-bite' ), $sent_at ) );
	}

	/**
	 * Standort-Zuweisung im Benutzerprofil anzeigen
	 *
	 * @param WP_User $user Benutzer-Objekt
	 */
	public function render_user_location_field( $user ) {
		if ( ! current_user_can( 'lbite_manage_roles' ) ) {
			return;
		}

		$assigned  = (int) get_user_meta( $user->ID, 'lbite_assigned_location', true );
		$locations = LBite_Locations::get_all_locations();

		if ( empty( $locations ) ) {
			return;
		}

		wp_nonce_field( 'lbite_save_user_location_' . $user->ID, 'lbite_user_location_nonce' );
		?>
		<h2><?php esc_html_e( 'Libre Bite', 'libre-bite' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="lbite_assigned_location"><?php esc_html_e( 'Assigned Location', 'libre-bite' ); ?></label></th>
				<td>
					<select name="lbite_assigned_location" id="lbite_assigned_location">
						<option value="0"><?php esc_html_e( 'No fixed location (user can select freely)', 'libre-bite' ); ?></option>
						<?php foreach ( $locations as $location ) : ?>
							<option value="<?php echo esc_attr( $location->ID ); ?>" <?php selected( $assigned, $location->ID ); ?>>
								<?php echo esc_html( $location->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'When set, this location is pre-selected in POS and Order Overview and cannot be changed by the user.', 'libre-bite' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * AJAX: POS-Produktreihenfolge speichern (via menu_order)
	 */
	public function ajax_save_pos_product_order() {
		check_ajax_referer( 'lbite_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		$order = isset( $_POST['order'] ) && is_array( $_POST['order'] )
			? array_map( 'absint', wp_unslash( $_POST['order'] ) )
			: array();

		if ( empty( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'No order provided', 'libre-bite' ) ) );
		}

		foreach ( $order as $position => $product_id ) {
			wp_update_post( array(
				'ID'         => $product_id,
				'menu_order' => $position,
			) );
		}

		// POS-Produkt-Cache leeren.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_lbite_pos_products_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_lbite_pos_products_' ) . '%'
			)
		);

		wp_send_json_success( array( 'message' => __( 'Product order saved.', 'libre-bite' ) ) );
	}

	/**
	 * AJAX: Produkte für den Picker in den Einstellungen suchen (Order Bumps, Promotions)
	 *
	 * Nimmt entweder `s` (Suchbegriff) oder `ids` (Komma-Liste, für die
	 * Anzeige bereits gespeicherter Auswahl) entgegen.
	 */
	public function ajax_admin_search_products() {
		check_ajax_referer( 'lbite_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		$results = array();

		if ( ! empty( $_POST['ids'] ) ) {
			$ids = array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['ids'] ) ) ) ) );

			foreach ( $ids as $id ) {
				$product = wc_get_product( $id );
				if ( $product ) {
					$results[] = array(
						'id'   => $product->get_id(),
						'name' => $product->get_name(),
						'sku'  => $product->get_sku(),
					);
				}
			}
		} else {
			$search = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';

			if ( '' !== $search && class_exists( 'WC_Product_Query' ) ) {
				$query = new WC_Product_Query(
					array(
						's'       => $search,
						'limit'   => 20,
						'status'  => 'publish',
						'type'    => array_diff( array_keys( wc_get_product_types() ), array( 'variation' ) ),
						'orderby' => 'title',
						'order'   => 'ASC',
					)
				);

				foreach ( $query->get_products() as $product ) {
					$results[] = array(
						'id'   => $product->get_id(),
						'name' => $product->get_name(),
						'sku'  => $product->get_sku(),
					);
				}
			}
		}

		wp_send_json_success( array( 'products' => $results ) );
	}

	/**
	 * Standort-Zuweisung aus dem Benutzerprofil speichern
	 *
	 * @param int $user_id Benutzer-ID
	 */
	public function save_user_location_field( $user_id ) {
		if ( ! current_user_can( 'lbite_manage_roles' ) || ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if ( ! isset( $_POST['lbite_user_location_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['lbite_user_location_nonce'] ) ), 'lbite_save_user_location_' . $user_id ) ) {
			return;
		}

		$location_id = isset( $_POST['lbite_assigned_location'] ) ? intval( wp_unslash( $_POST['lbite_assigned_location'] ) ) : 0;
		update_user_meta( $user_id, 'lbite_assigned_location', $location_id );
	}
}
