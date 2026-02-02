<?php
/**
 * Rollenverwaltung für Libre Bite
 *
 * Definiert drei Benutzerebenen:
 * - lb_staff: Personal (Bestellübersicht, POS)
 * - lb_admin: Filialleiter (+ Produkte, Standorte, Einstellungen)
 * - administrator: Super-Admin (+ Feature-Toggles, Debug)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rollen-Klasse
 */
class LB_Roles {

	/**
	 * Alle OOS-spezifischen Capabilities
	 *
	 * @var array
	 */
	private static $capabilities = array(
		// Staff Capabilities
		'lb_view_dashboard'       => array( 'lb_staff', 'lb_admin', 'administrator' ),
		'lb_view_orders'          => array( 'lb_staff', 'lb_admin', 'administrator' ),
		'lb_manage_orders'        => array( 'lb_staff', 'lb_admin', 'administrator' ),
		'lb_use_pos'              => array( 'lb_staff', 'lb_admin', 'administrator' ),

		// Admin Capabilities
		'lb_manage_locations'     => array( 'lb_admin', 'administrator' ),
		'lb_manage_products'      => array( 'lb_admin', 'administrator' ),
		'lb_manage_options'       => array( 'lb_admin', 'administrator' ),
		'lb_manage_checkout'      => array( 'lb_admin', 'administrator' ),
		'lb_manage_settings'      => array( 'lb_admin', 'administrator' ),

		// Super-Admin Capabilities
		'lb_manage_features'      => array( 'administrator' ),
		'lb_manage_roles'         => array( 'administrator' ),
		'lb_manage_support'       => array( 'administrator' ),
		'lb_view_debug'           => array( 'administrator' ),
	);

	/**
	 * Rollen bei Plugin-Aktivierung erstellen
	 */
	public static function create_roles() {
		// Staff-Rolle erstellen
		add_role(
			'lb_staff',
			'Libre Bite Personal', // Keine Übersetzung bei Aktivierung um Early-Loading zu vermeiden
			array(
				'read'                   => true,
				'edit_posts'             => false,
				'delete_posts'           => false,
				'publish_posts'          => false,
				'upload_files'           => false,
				// OOS Capabilities
				'lb_view_dashboard'     => true,
				'lb_view_orders'        => true,
				'lb_manage_orders'      => true,
				'lb_use_pos'            => true,
			)
		);

		// Admin-Rolle erstellen
		add_role(
			'lb_admin',
			'Libre Bite Administrator', // Keine Übersetzung bei Aktivierung um Early-Loading zu vermeiden
			array(
				'read'                   => true,
				'edit_posts'             => true,
				'delete_posts'           => true,
				'publish_posts'          => true,
				'upload_files'           => true,
				'edit_published_posts'   => true,
				'delete_published_posts' => true,
				// OOS Capabilities
				'lb_view_dashboard'     => true,
				'lb_view_orders'        => true,
				'lb_manage_orders'      => true,
				'lb_use_pos'            => true,
				'lb_manage_locations'   => true,
				'lb_manage_products'    => true,
				'lb_manage_options'     => true,
				'lb_manage_checkout'    => true,
				'lb_manage_settings'    => true,
			)
		);

		// Administrator-Rolle aktualisieren (Super-Admin Capabilities hinzufügen)
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			foreach ( self::$capabilities as $cap => $roles ) {
				$admin_role->add_cap( $cap );
			}
		}

		// Shop Manager auch berechtigen (falls WooCommerce aktiv)
		$shop_manager = get_role( 'shop_manager' );
		if ( $shop_manager ) {
			// Shop Manager bekommt Admin-Capabilities
			$shop_manager->add_cap( 'lb_view_dashboard' );
			$shop_manager->add_cap( 'lb_view_orders' );
			$shop_manager->add_cap( 'lb_manage_orders' );
			$shop_manager->add_cap( 'lb_use_pos' );
			$shop_manager->add_cap( 'lb_manage_locations' );
			$shop_manager->add_cap( 'lb_manage_products' );
			$shop_manager->add_cap( 'lb_manage_options' );
			$shop_manager->add_cap( 'lb_manage_checkout' );
			$shop_manager->add_cap( 'lb_manage_settings' );
		}
	}

	/**
	 * Rollen bei Plugin-Deaktivierung entfernen
	 */
	public static function remove_roles() {
		// Custom Roles entfernen
		remove_role( 'lb_staff' );
		remove_role( 'lb_admin' );

		// Capabilities von bestehenden Rollen entfernen
		$roles_to_clean = array( 'administrator', 'shop_manager' );

		foreach ( $roles_to_clean as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				foreach ( array_keys( self::$capabilities ) as $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}
	}

	/**
	 * Prüfen ob aktueller Benutzer Staff ist
	 *
	 * @return bool
	 */
	public static function is_staff() {
		return current_user_can( 'lb_view_dashboard' );
	}

	/**
	 * Prüfen ob aktueller Benutzer Admin ist
	 *
	 * @return bool
	 */
	public static function is_admin() {
		return current_user_can( 'lb_manage_settings' );
	}

	/**
	 * Prüfen ob aktueller Benutzer Super-Admin ist
	 *
	 * @return bool
	 */
	public static function is_super_admin() {
		return current_user_can( 'lb_manage_features' );
	}

	/**
	 * Alle OOS-Capabilities abrufen
	 *
	 * @return array
	 */
	public static function get_all_capabilities() {
		return self::$capabilities;
	}

	/**
	 * Benutzerrolle für OOS ermitteln
	 *
	 * @param int|null $user_id Benutzer-ID (optional, Standard: aktueller Benutzer)
	 * @return string 'super_admin', 'admin', 'staff' oder 'none'
	 */
	public static function get_user_level( $user_id = null ) {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return 'none';
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return 'none';
		}

		// Prüfe Capabilities
		if ( user_can( $user_id, 'lb_manage_features' ) ) {
			return 'super_admin';
		}

		if ( user_can( $user_id, 'lb_manage_settings' ) ) {
			return 'admin';
		}

		if ( user_can( $user_id, 'lb_view_dashboard' ) ) {
			return 'staff';
		}

		return 'none';
	}

	/**
	 * Migrations-Script für bestehende Benutzer
	 *
	 * Weist bestehenden Shop-Managern und Administratoren die neuen Capabilities zu.
	 */
	public static function migrate_existing_users() {
		// Diese Funktion wird bei Plugin-Update aufgerufen
		// Bestehende Benutzer behalten ihre Rollen, bekommen nur neue Capabilities

		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			foreach ( array_keys( self::$capabilities ) as $cap ) {
				if ( ! $admin_role->has_cap( $cap ) ) {
					$admin_role->add_cap( $cap );
				}
			}
		}

		$shop_manager = get_role( 'shop_manager' );
		if ( $shop_manager ) {
			$admin_caps = array(
				'lb_view_dashboard',
				'lb_view_orders',
				'lb_manage_orders',
				'lb_use_pos',
				'lb_manage_locations',
				'lb_manage_products',
				'lb_manage_options',
				'lb_manage_checkout',
				'lb_manage_settings',
			);
			foreach ( $admin_caps as $cap ) {
				if ( ! $shop_manager->has_cap( $cap ) ) {
					$shop_manager->add_cap( $cap );
				}
			}
		}

		// Version für Migration speichern
		update_option( 'lb_roles_version', '1.0.0' );
	}

	/**
	 * Prüfen ob Migration nötig ist
	 *
	 * @return bool
	 */
	public static function needs_migration() {
		$current_version = get_option( 'lb_roles_version', '0' );
		return version_compare( $current_version, '1.0.0', '<' );
	}
}
