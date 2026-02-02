<?php
/**
 * Plugin Installation & Deinstallation
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installer-Klasse
 */
class LB_Installer {

	/**
	 * Plugin-Aktivierung
	 */
	public static function activate() {
		// Rollen und Capabilities erstellen
		require_once LB_PLUGIN_DIR . 'includes/admin/class-roles.php';
		LB_Roles::create_roles();

		// Datenbank-Tabellen erstellen
		self::create_tables();

		// Standard-Optionen setzen
		self::set_default_options();

		// Feature-Toggles initialisieren
		self::set_default_features();

		// Support-Einstellungen initialisieren
		self::set_default_support_settings();

		// Flush rewrite rules
		flush_rewrite_rules();

		// Version speichern
		update_option( 'lb_version', LB_VERSION );
		update_option( 'lb_installed_date', current_time( 'mysql' ) );
	}

	/**
	 * Plugin-Deaktivierung
	 */
	public static function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();

		// Geplante Cron-Jobs entfernen
		wp_clear_scheduled_hook( 'lb_check_scheduled_orders' );
		wp_clear_scheduled_hook( 'lb_send_pickup_reminders' );

		// Hinweis: Rollen werden NICHT bei Deaktivierung entfernt,
		// nur bei vollständiger Deinstallation (uninstall.php)
	}

	/**
	 * Datenbank-Tabellen erstellen
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Tabelle für Standorte (wenn nicht Custom Post Type verwendet wird)
		// Aktuell verwenden wir Custom Post Types, daher keine separate Tabelle nötig

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	}

	/**
	 * Standard-Optionen setzen
	 */
	private static function set_default_options() {
		$defaults = array(
			// Checkout-Einstellungen
			'lb_checkout_fields'           => array(),

			// Trinkgeld-Einstellungen
			'lb_tip_percentage_1'          => 5,
			'lb_tip_percentage_2'          => 10,
			'lb_tip_percentage_3'          => 15,

			// Vorbestellungs-Einstellungen
			'lb_preparation_time'          => 30, // Minuten
			'lb_pickup_reminder_time'      => 15, // Minuten vor Abholung

			// Zeitslot-Einstellungen
			'lb_timeslot_interval'         => 15, // Minuten

			// Dashboard-Einstellungen
			'lb_dashboard_refresh_interval' => 30, // Sekunden
			'lb_sound_enabled'             => true,

			// E-Mail-Einstellungen
			'lb_email_pickup_reminder'     => true,
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}

	/**
	 * Standard Feature-Toggles setzen
	 */
	private static function set_default_features() {
		$default_features = array(
			// Bestellsystem
			'enable_pos'                => true,
			'enable_scheduled_orders'   => true,
			'enable_order_notes'        => true,
			'enable_order_cancellation' => true,

			// Checkout
			'enable_optimized_checkout' => true,
			'enable_tips'               => true,
			'enable_rounding'           => true,
			'enable_guest_checkout'     => true,
			'enable_email_field'        => true,
			'enable_phone_field'        => true,

			// Standorte
			'enable_multi_location'     => false,
			'enable_location_selector'  => true,
			'enable_opening_hours'      => true,

			// Benachrichtigungen
			'enable_pickup_reminders'   => true,
			'enable_sound_notifications' => true,
			'enable_admin_email'        => true,

			// Produkte
			'enable_product_options'    => true,
			'enable_nutritional_info'   => false,
			'enable_allergens'          => false,

			// Dashboard
			'enable_kanban_board'       => true,
			'enable_auto_status_change' => true,
			'enable_fullscreen_mode'    => true,
		);

		if ( false === get_option( 'lb_features' ) ) {
			add_option( 'lb_features', $default_features );
		}
	}

	/**
	 * Standard Support-Einstellungen setzen
	 */
	private static function set_default_support_settings() {
		$default_support = array(
			'support_email'        => get_option( 'admin_email' ),
			'support_phone'        => '',
			'support_hours'        => '',
			'support_billing_note' => '',
			'support_custom_text'  => '',
		);

		if ( false === get_option( 'lb_support_settings' ) ) {
			add_option( 'lb_support_settings', $default_support );
		}
	}

	/**
	 * Migration bei Plugin-Update durchführen
	 */
	public static function maybe_upgrade() {
		$current_version = get_option( 'lb_version', '0' );

		// Migration für Rollen-System
		require_once LB_PLUGIN_DIR . 'includes/admin/class-roles.php';
		if ( LB_Roles::needs_migration() ) {
			LB_Roles::migrate_existing_users();
		}

		// Feature-Toggles hinzufügen falls nicht vorhanden
		if ( false === get_option( 'lb_features' ) ) {
			self::set_default_features();
		}

		// Support-Einstellungen hinzufügen falls nicht vorhanden
		if ( false === get_option( 'lb_support_settings' ) ) {
			self::set_default_support_settings();
		}

		// Version aktualisieren
		if ( version_compare( $current_version, LB_VERSION, '<' ) ) {
			update_option( 'lb_version', LB_VERSION );
		}
	}
}
