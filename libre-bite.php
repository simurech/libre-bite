<?php
/**
 * Plugin Name:       Libre Bite
 * Plugin URI:        https://github.com/simurech/libre-bite
 * Description:       Komplettes Bestell- und Standortverwaltungssystem für WooCommerce
 * Version:           1.0.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Simon Urech
 * Author URI:        https://github.com/simurech
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       libre-bite
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 */

if ( function_exists( 'lb_freemius' ) ) {
	lb_freemius()->set_basename( true, __FILE__ );
} else {
	if ( ! function_exists( 'lb_freemius' ) ) {
		// Create a helper function for easy SDK access.
		function lb_freemius() {
			global $lb_freemius;

			if ( ! isset( $lb_freemius ) ) {
				// Include Freemius SDK.
				require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';

				$lb_freemius = fs_dynamic_init( array(
					'id'                  => '23812',
					'slug'                => 'librebite',
					'premium_slug'        => 'librebite-pro',
					'type'                => 'plugin',
					'public_key'          => 'pk_ce29dda57055eecde2de784d17506',
					'is_premium'          => true,
					'premium_suffix'      => 'Pro',
					// If your plugin is a serviceware, set this option to false.
					'has_premium_version' => true,
					'has_addons'          => false,
					'has_paid_plans'      => true,
					// Automatically removed in the free version. If you're not using the
					// auto-generated free version, delete this line before uploading to wp.org.
					'wp_org_gatekeeper'   => 'OA7#BoRiBNqdf52FvzEf!!074aRLPs8fspif$7K1#4u4Csys1fQlCecVcUTOs2mcpeVHi#C2j9d09fOTvbC0HloPT7fFee5WdS3G',
					'trial'               => array(
						'days'               => 7,
						'is_require_payment' => false,
					),
					'menu'                => array(
						'slug'    => 'libre-bite',
						'contact' => false,
						'support' => false,
					),
				) );
			}

			return $lb_freemius;
		}

			// Init Freemius.
			lb_freemius();
			// Signal that SDK was initiated.
			do_action( 'lb_freemius_loaded' );
		
			// Handle uninstall cleanup via Freemius.
			lb_freemius()->add_action( 'after_uninstall', function() {
				require_once plugin_dir_path( __FILE__ ) . 'includes/core/class-installer.php';
				LB_Installer::uninstall_cleanup();
			} );
		}
	// Direkten Zugriff verhindern
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	// Plugin-Konstanten definieren
	define( 'LB_VERSION', '1.0.1' );
	define( 'LB_PLUGIN_FILE', __FILE__ );
	define( 'LB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	define( 'LB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	define( 'LB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

	/**
	 * Autoloader für Plugin-Klassen
	 */
	spl_autoload_register( function ( $class ) {
		// Nur Klassen des Plugins laden
		if ( strpos( $class, 'LB_' ) !== 0 ) {
			return;
		}

		// Klassenname in Dateiname konvertieren
		$class_file = strtolower( str_replace( '_', '-', $class ) );
		$class_file = str_replace( 'lb-', '', $class_file );

		// Mögliche Pfade durchsuchen
		$paths = array(
			LB_PLUGIN_DIR . 'includes/core/class-' . $class_file . '.php',
			LB_PLUGIN_DIR . 'includes/admin/class-' . $class_file . '.php',
		);

		// Module-Pfade hinzufügen
		$modules = array(
			'locations',
			'checkout',
			'product-options',
			'order-dashboard',
			'pos',
			'notifications',
			'nutritional-info',
			'customizations',
		);

		foreach ( $modules as $module ) {
			$paths[] = LB_PLUGIN_DIR . 'includes/modules/' . $module . '/class-' . $class_file . '.php';
		}

		// Datei laden wenn gefunden
		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}
	} );

	/**
	 * Hauptklasse des Plugins laden und initialisieren
	 */
	function lb_init_plugin() {
		// WooCommerce Abhängigkeit prüfen
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', 'lb_woocommerce_missing_notice' );
			return;
		}

		// Minimale WooCommerce-Version prüfen
		if ( version_compare( WC_VERSION, '8.0', '<' ) ) {
			add_action( 'admin_notices', 'lb_woocommerce_version_notice' );
			return;
		}

		// Feature-Manager laden
		require_once LB_PLUGIN_DIR . 'includes/core/class-features.php';
		LB_Features::instance();

		// Hauptklasse laden
		require_once LB_PLUGIN_DIR . 'includes/core/class-plugin.php';

		// Plugin initialisieren
		LB_Plugin::instance();
	}
	add_action( 'plugins_loaded', 'lb_init_plugin', 11 );

	/**
	 * Admin-Hinweis: WooCommerce fehlt
	 */
	function lb_woocommerce_missing_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<strong>Libre Bite</strong> benötigt WooCommerce.
				Bitte installieren und aktivieren Sie WooCommerce.
			</p>
		</div>
		<?php
	}

	/**
	 * Admin-Hinweis: WooCommerce-Version zu alt
	 */
	function lb_woocommerce_version_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<strong>Libre Bite</strong> benötigt mindestens WooCommerce Version 8.0.
				Bitte aktualisieren Sie WooCommerce.
			</p>
		</div>
		<?php
	}

	/**
	 * Plugin-Aktivierung
	 */
	function lb_activate_plugin() {
		// WooCommerce-Check bei Aktivierung
		if ( ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				'Libre Bite benötigt WooCommerce. Bitte installieren und aktivieren Sie WooCommerce zuerst.',
				'Plugin-Abhängigkeit',
				array( 'back_link' => true )
			);
		}

		require_once LB_PLUGIN_DIR . 'includes/core/class-installer.php';
		LB_Installer::activate();
	}
	register_activation_hook( __FILE__, 'lb_activate_plugin' );

	/**
	 * Plugin-Deaktivierung
	 */
	function lb_deactivate_plugin() {
		require_once LB_PLUGIN_DIR . 'includes/core/class-installer.php';
		LB_Installer::deactivate();
	}
	register_deactivation_hook( __FILE__, 'lb_deactivate_plugin' );

	/**
	 * HPOS-Kompatibilität deklarieren
	 */
	add_action( 'before_woocommerce_init', function() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
		}
	} );

	/**
	 * Custom Cron-Intervalle registrieren
	 */
	add_filter( 'cron_schedules', function( $schedules ) {
		$schedules['every_minute'] = array(
			'interval' => 60,
			'display'  => 'Jede Minute', // Keine Übersetzung hier um Early-Loading-Warnung zu vermeiden
		);
		return $schedules;
	} );

	/**
	 * Migrations-Script laden (kann nach Migration gelöscht werden)
	 */
	if ( file_exists( LB_PLUGIN_DIR . 'migrate.php' ) ) {
		require_once LB_PLUGIN_DIR . 'migrate.php';
	}
}
