<?php
/**
 * Plugin Name:       Libre Bite
 * Plugin URI:        https://github.com/simurech/libre-bite
 * Description:       Complete order and location management system for WooCommerce restaurants and food businesses.
 * Version:           1.2.5
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Simon Urech
 * Author URI:        https://urech.dev/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       libre-bite
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 * @fs_premium_only /includes/modules/nutritional-info/, /includes/modules/reservations/, /includes/modules/tables/, /templates/checkout-optimized.php, /templates/admin/reservation-board.php, /templates/admin/table-plan.php, /templates/frontend/reservation-form.php, /templates/admin/help-partials/reservations.php, /templates/admin/help-partials/tables.php, /assets/js/reservation-form.js, /assets/js/admin-tables.js, /assets/js/admin-table-plan.js, /assets/js/reservation-board.js, /assets/css/reservation-form.css, /assets/css/admin-tables.css, /assets/css/admin-table-plan.css, /assets/css/admin-reservation-board.css, /assets/css/checkout-optimized.css
 */

// Direkten Dateizugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'lbite_freemius' ) ) {
	lbite_freemius()->set_basename( true, __FILE__ );
} else {
	// Create a helper function for easy SDK access.
	function lbite_freemius() {
		global $lbite_freemius;

		if ( ! isset( $lbite_freemius ) ) {
			// Include Freemius SDK.
			require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';

			$lbite_freemius = fs_dynamic_init( array(
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
				'is_org_compliant'    => true,
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

		return $lbite_freemius;
	}

	// Init Freemius.
	lbite_freemius();

	// Add uninstall hook for Freemius tracking compliance.
	lbite_freemius()->add_action( 'after_uninstall', array( 'LBite_Installer', 'uninstall' ) );

	// Signal that SDK was initiated.
	do_action( 'lbite_freemius_loaded' );
}

// Plugin-Konstanten definieren
define( 'LBITE_VERSION', '1.2.5' );
define( 'LBITE_PLUGIN_FILE', __FILE__ );
define( 'LBITE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LBITE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LBITE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader für Plugin-Klassen
 */
spl_autoload_register( function ( $class ) {
	// Nur Klassen des Plugins laden
	if ( strpos( $class, 'LBite_' ) !== 0 ) {
		return;
	}

	// Klassenname in Dateiname konvertieren
	$class_file = strtolower( str_replace( '_', '-', $class ) );
	$class_file = str_replace( 'lbite-', '', $class_file );

	// Mögliche Pfade durchsuchen
	$paths = array(
		LBITE_PLUGIN_DIR . 'includes/core/class-' . $class_file . '.php',
		LBITE_PLUGIN_DIR . 'includes/admin/class-' . $class_file . '.php',
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
		'tables',
	);

	foreach ( $modules as $module ) {
		$paths[] = LBITE_PLUGIN_DIR . 'includes/modules/' . $module . '/class-' . $class_file . '.php';
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
 * Übersetzungen laden
 */
function lbite_load_textdomain() {
	load_plugin_textdomain( 'libre-bite', false, dirname( plugin_basename( LBITE_PLUGIN_FILE ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'lbite_load_textdomain', 10 );

/**
 * Hauptklasse des Plugins laden und initialisieren
 */
function lbite_init_plugin() {
	// WooCommerce Abhängigkeit prüfen
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'lbite_woocommerce_missing_notice' );
		return;
	}

	// Minimale WooCommerce-Version prüfen
	if ( version_compare( WC_VERSION, '8.0', '<' ) ) {
		add_action( 'admin_notices', 'lbite_woocommerce_version_notice' );
		return;
	}

	// Feature-Manager laden
	require_once LBITE_PLUGIN_DIR . 'includes/core/class-features.php';
	LBite_Features::instance();

	// Hauptklasse laden
	require_once LBITE_PLUGIN_DIR . 'includes/core/class-plugin.php';

	// Plugin initialisieren
	LBite_Plugin::instance();
}
add_action( 'plugins_loaded', 'lbite_init_plugin', 11 );

/**
 * Admin-Hinweis: WooCommerce fehlt
 */
function lbite_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<strong><?php echo esc_html__( 'Libre Bite', 'libre-bite' ); ?></strong>
			<?php echo esc_html__( 'requires WooCommerce. Please install and activate WooCommerce.', 'libre-bite' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Admin-Hinweis: WooCommerce-Version zu alt
 */
function lbite_woocommerce_version_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<strong><?php echo esc_html__( 'Libre Bite', 'libre-bite' ); ?></strong>
			<?php echo esc_html__( 'requires at least WooCommerce version 8.0. Please update WooCommerce.', 'libre-bite' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Plugin-Aktivierung
 */
function lbite_activate_plugin() {
	// WooCommerce-Check bei Aktivierung
	if ( ! class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			'Libre Bite requires WooCommerce. Please install and activate WooCommerce first.',
			'Plugin Dependency',
			array( 'back_link' => true )
		);
	}

	require_once LBITE_PLUGIN_DIR . 'includes/core/class-installer.php';
	LBite_Installer::activate();
}
register_activation_hook( __FILE__, 'lbite_activate_plugin' );

/**
 * Plugin-Deaktivierung
 */
function lbite_deactivate_plugin() {
	require_once LBITE_PLUGIN_DIR . 'includes/core/class-installer.php';
	LBite_Installer::deactivate();
}
register_deactivation_hook( __FILE__, 'lbite_deactivate_plugin' );

/**
 * Lokalen Zeit-String timezone-korrekt in Unix-Timestamp umwandeln.
 * Verhindert Doppelkonvertierung bei strtotime() + wp_date()/date_i18n():
 * wp_date() speichert Zeiten als lokale Strings; strtotime() würde diese als UTC
 * interpretieren, wodurch wp_date() bei der Anzeige den Offset nochmals addiert.
 *
 * @param string $time_str Lokaler Zeit-String (z.B. «2024-01-15 14:30»).
 * @return int Unix-Timestamp (UTC), 0 bei ungültigem Input.
 */
function lbite_local_time_to_timestamp( $time_str ) {
	if ( empty( $time_str ) ) {
		return 0;
	}
	try {
		$dt = new DateTimeImmutable( $time_str, wp_timezone() );
		return $dt->getTimestamp();
	} catch ( Exception $e ) {
		return 0;
	}
}

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
		'display'  => 'Every Minute', // No translation here to avoid early-loading warnings
	);
	return $schedules;
} );
