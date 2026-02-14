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
class LBite_Plugin {

	/**
	 * Plugin-Instanz
	 *
	 * @var LBite_Plugin
	 */
	private static $instance = null;

	/**
	 * Loader-Instanz
	 *
	 * @var LBite_Loader
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
	 * @return LBite_Plugin
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
		$this->init_modules();
		$this->loader->run();
	}

	/**
	 * Abhängigkeiten laden
	 */
	private function load_dependencies() {
		require_once LBITE_PLUGIN_DIR . 'includes/core/class-loader.php';
		$this->loader = new LBite_Loader();
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
		$this->load_module( 'customizations', 'LBite_Customizations' );
		$this->load_module( 'locations', 'LBite_Locations' );
		$this->load_module( 'checkout', 'LBite_Checkout' );

		// Feature-abhängige Module
		if ( lbite_feature_enabled( 'enable_product_options' ) ) {
			$this->load_module( 'product-options', 'LBite_Product_Options' );
		}

		if ( lbite_feature_enabled( 'enable_nutritional_info' ) ) {
			$this->load_module( 'nutritional-info', 'LBite_Nutritional_Info' );
		}

		if ( lbite_feature_enabled( 'enable_kanban_board' ) ) {
			$this->load_module( 'order-dashboard', 'LBite_Order_Dashboard' );
		}

		if ( lbite_feature_enabled( 'enable_pos' ) ) {
			$this->load_module( 'pos', 'LBite_POS' );
		}

		if ( lbite_feature_enabled( 'enable_pickup_reminders' ) || lbite_feature_enabled( 'enable_admin_email' ) ) {
			$this->load_module( 'notifications', 'LBite_Notifications' );
		}
	}

	/**
	 * Admin-Bereich initialisieren
	 */
	private function init_admin() {
		require_once LBITE_PLUGIN_DIR . 'includes/admin/class-admin.php';
		$this->modules['admin'] = new LBite_Admin( $this->loader );
	}

	/**
	 * Einzelnes Modul laden
	 *
	 * @param string $module_dir Modul-Verzeichnis
	 * @param string $class_name Klassenname
	 */
	private function load_module( $module_dir, $class_name ) {
		// Erst "LBite_" entfernen, DANN Underscores mit Bindestrichen ersetzen
		$class_file = str_replace( 'LBite_', '', $class_name );
		$class_file = str_replace( '_', '-', $class_file );
		$class_file = strtolower( $class_file );

		$file_path = LBITE_PLUGIN_DIR . 'includes/modules/' . $module_dir . '/class-' . $class_file . '.php';

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
