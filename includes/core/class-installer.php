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
class LBite_Installer {

	/**
	 * Plugin-Aktivierung
	 */
	public static function activate() {
		// Rollen und Capabilities erstellen
		require_once LBITE_PLUGIN_DIR . 'includes/admin/class-roles.php';
		LBite_Roles::create_roles();

		// Datenbank-Tabellen erstellen
		self::create_tables();

		// Standard-Optionen setzen
		self::set_default_options();

		// Feature-Toggles initialisieren
		self::set_default_features();

		// Support-Einstellungen initialisieren
		self::set_default_support_settings();

		// Cron-Jobs registrieren, falls noch nicht vorhanden.
		if ( ! wp_next_scheduled( 'lbite_check_scheduled_orders' ) ) {
			wp_schedule_event( time(), 'every_minute', 'lbite_check_scheduled_orders' );
		}

		if ( ! wp_next_scheduled( 'lbite_send_pickup_reminders' ) ) {
			wp_schedule_event( time(), 'every_minute', 'lbite_send_pickup_reminders' );
		}

		// Flush rewrite rules
		flush_rewrite_rules();

		// Version speichern
		update_option( 'lbite_version', LBITE_VERSION );
		update_option( 'lbite_installed_date', current_time( 'mysql' ) );
	}

	/**
	 * Plugin-Deaktivierung
	 */
	public static function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();

		// Geplante Cron-Jobs entfernen
		wp_clear_scheduled_hook( 'lbite_check_scheduled_orders' );
		wp_clear_scheduled_hook( 'lbite_send_pickup_reminders' );

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

		// upgrade.php wird fuer dbDelta() benoetigt (offizielle WordPress-Methode fuer DB-Schema-Updates).
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	}

	/**
	 * Standard-Optionen setzen
	 */
	private static function set_default_options() {
		$defaults = array(
			// Checkout-Einstellungen
			'lbite_checkout_fields'           => array(),

			// Trinkgeld-Einstellungen
			'lbite_tip_percentage_1'          => 5,
			'lbite_tip_percentage_2'          => 10,
			'lbite_tip_percentage_3'          => 15,

			// Vorbestellungs-Einstellungen
			'lbite_preparation_time'          => 30, // Minuten
			'lbite_pickup_reminder_time'      => 15, // Minuten vor Abholung

			// Zeitslot-Einstellungen
			'lbite_timeslot_interval'         => 15, // Minuten

			// Dashboard-Einstellungen
			'lbite_dashboard_refresh_interval' => 30, // Sekunden
			'lbite_sound_enabled'             => true,

			// E-Mail-Einstellungen
			'lbite_email_pickup_reminder'     => true,
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

		if ( false === get_option( 'lbite_features' ) ) {
			add_option( 'lbite_features', $default_features );
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

		if ( false === get_option( 'lbite_support_settings' ) ) {
			add_option( 'lbite_support_settings', $default_support );
		}
	}

	/**
	 * Migration bei Plugin-Update durchführen
	 */
	public static function maybe_upgrade() {
		$current_version = get_option( 'lbite_version', '0' );

		// Migration für Rollen-System
		require_once LBITE_PLUGIN_DIR . 'includes/admin/class-roles.php';
		if ( LBite_Roles::needs_migration() ) {
			LBite_Roles::migrate_existing_users();
		}

		// Feature-Toggles hinzufügen falls nicht vorhanden
		if ( false === get_option( 'lbite_features' ) ) {
			self::set_default_features();
		}

		// Support-Einstellungen hinzufügen falls nicht vorhanden
		if ( false === get_option( 'lbite_support_settings' ) ) {
			self::set_default_support_settings();
		}

		// Version aktualisieren
		if ( version_compare( $current_version, LBITE_VERSION, '<' ) ) {
			update_option( 'lbite_version', LBITE_VERSION );
		}
	}

	/**
	 * Vollständige Daten-Bereinigung bei Deinstallation
	 */
	public static function uninstall_cleanup() {
		// Check if data deletion is enabled.
		$delete_data = get_option( 'lbite_delete_data_on_uninstall', false ) || get_option( 'oos_delete_data_on_uninstall', false );

		if ( ! $delete_data ) {
			return;
		}

		global $wpdb;

		// 1. Delete CPTs.
		$post_types = array( 'lbite_location', 'oos_location', 'lbite_product_option', 'oos_product_option' );
		foreach ( $post_types as $post_type ) {
			$posts = get_posts( array( 'post_type' => $post_type, 'posts_per_page' => -1, 'fields' => 'ids', 'post_status' => 'any' ) );
			foreach ( $posts as $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}

		// 2. Delete Options.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall: Wildcard-Löschung benötigt direkten Query.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( 'lbite_' ) . '%', $wpdb->esc_like( 'oos_' ) . '%' ) );

		// 3. Delete Meta.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall: Wildcard-Löschung benötigt direkten Query.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s OR meta_key LIKE %s", $wpdb->esc_like( '_lbite_' ) . '%', $wpdb->esc_like( '_oos_' ) . '%' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall: Wildcard-Löschung benötigt direkten Query.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s OR meta_key LIKE %s", $wpdb->esc_like( 'lbite_' ) . '%', $wpdb->esc_like( 'oos_' ) . '%' ) );

		// 4. Roles & Caps.
		remove_role( 'lbite_staff' );
		remove_role( 'lbite_admin' );
		remove_role( 'oos_staff' );
		remove_role( 'oos_admin' );

		$roles = array( 'administrator', 'shop_manager', 'editor' );
		$caps  = array( 'lbite_view_dashboard', 'lbite_view_orders', 'lbite_manage_orders', 'lbite_use_pos', 'lbite_manage_locations', 'lbite_manage_products', 'lbite_manage_options', 'lbite_manage_checkout', 'lbite_manage_settings', 'lbite_manage_features', 'lbite_manage_roles', 'lbite_manage_support', 'lbite_view_debug' );

		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				foreach ( $caps as $cap ) { $role->remove_cap( $cap ); }
			}
		}

		// 5. Cron Jobs.
		$cron_hooks = array( 'lbite_check_scheduled_orders', 'lbite_send_pickup_reminders' );
		foreach ( $cron_hooks as $hook ) {
			wp_clear_scheduled_hook( $hook );
		}

		// 6. Transients.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall: Wildcard-Löschung benötigt direkten Query.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_lbite_' ) . '%', $wpdb->esc_like( '_transient_timeout_lbite_' ) . '%' ) );
	}
}
