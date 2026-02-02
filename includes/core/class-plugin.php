<?php
/**
 * Hauptklasse des Libre Bite Plugins
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hauptklasse - Singleton Pattern
 */
class LB_Plugin {

	/**
	 * Plugin-Instanz
	 *
	 * @var LB_Plugin
	 */
	private static $instance = null;

	/**
	 * Loader-Instanz
	 *
	 * @var LB_Loader
	 */
	public $loader;

	/**
	 * Module-Container
	 *
	 * @var array
	 */
	private $modules = array();

	/**
	 * Singleton-Instanz abrufen
	 *
	 * @return LB_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Konstruktor - Privat für Singleton
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->set_locale();
		$this->init_modules();
		$this->loader->run();
	}

	/**
	 * Abhängigkeiten laden
	 */
	private function load_dependencies() {
		require_once LB_PLUGIN_DIR . 'includes/core/class-loader.php';
		$this->loader = new LB_Loader();
	}

	/**
	 * Textdomain für Übersetzungen laden
	 */
	private function set_locale() {
		$this->loader->add_action( 'plugins_loaded', $this, 'load_plugin_textdomain' );
	}

	/**
	 * Textdomain laden
	 */
	public function load_plugin_textdomain() {
		// phpcs:ignore WordPress.WP.DeprecatedParameters.Load_plugin_textdomainParam2Found -- false is required for relative path.
		load_plugin_textdomain(
			'libre-bite',
			false,
			dirname( LB_PLUGIN_BASENAME ) . '/languages/'
		);
	}

	/**
	 * Module initialisieren
	 */
	private function init_modules() {
		// WooCommerce dependency check - do not load modules if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Admin-Bereich
		if ( is_admin() ) {
			$this->init_admin();
		}

		// Basis-Module (immer laden)
		$this->load_module( 'customizations', 'LB_Customizations' );
		$this->load_module( 'locations', 'LB_Locations' );
		$this->load_module( 'checkout', 'LB_Checkout' );

		// Feature-abhängige Module
		if ( lb_feature_enabled( 'enable_product_options' ) ) {
			$this->load_module( 'product-options', 'LB_Product_Options' );
		}

		if ( lb_feature_enabled( 'enable_nutritional_info' ) ) {
			$this->load_module( 'nutritional-info', 'LB_Nutritional_Info' );
		}

		if ( lb_feature_enabled( 'enable_kanban_board' ) ) {
			$this->load_module( 'order-dashboard', 'LB_Order_Dashboard' );
		}

		if ( lb_feature_enabled( 'enable_pos' ) ) {
			$this->load_module( 'pos', 'LB_POS' );
		}

		if ( lb_feature_enabled( 'enable_pickup_reminders' ) || lb_feature_enabled( 'enable_admin_email' ) ) {
			$this->load_module( 'notifications', 'LB_Notifications' );
		}
	}

	/**
	 * Admin-Bereich initialisieren
	 */
	private function init_admin() {
		require_once LB_PLUGIN_DIR . 'includes/admin/class-admin.php';
		$this->modules['admin'] = new LB_Admin( $this->loader );
	}

	/**
	 * Einzelnes Modul laden
	 *
	 * @param string $module_dir Modul-Verzeichnis
	 * @param string $class_name Klassenname
	 */
	private function load_module( $module_dir, $class_name ) {
		// Erst "LB_" entfernen, DANN Underscores mit Bindestrichen ersetzen
		$class_file = str_replace( 'LB_', '', $class_name );
		$class_file = str_replace( '_', '-', $class_file );
		$class_file = strtolower( $class_file );

		$file_path = LB_PLUGIN_DIR . 'includes/modules/' . $module_dir . '/class-' . $class_file . '.php';

		if ( file_exists( $file_path ) ) {
			require_once $file_path;
			if ( class_exists( $class_name ) ) {
				$this->modules[ $module_dir ] = new $class_name( $this->loader );
			}
		}
	}

	/**
	 * Modul abrufen
	 *
	 * @param string $module_name Modul-Name
	 * @return object|null
	 */
	public function get_module( $module_name ) {
		return isset( $this->modules[ $module_name ] ) ? $this->modules[ $module_name ] : null;
	}
}
