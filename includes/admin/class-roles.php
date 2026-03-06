<?php
/**
 * Rollenverwaltung für Libre Bite
 *
 * Definiert zwei Benutzerebenen:
 * - lbite_staff: Personal (Bestellübersicht, POS)
 * - administrator: Admin (alle weiteren Funktionen)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rollen-Klasse
 */
class LBite_Roles {

	/**
	 * Alle Plugin-spezifischen Capabilities
	 *
	 * @var array
	 */
	private static $capabilities = array(
		// Personal-Capabilities
		'lbite_view_dashboard'   => array( 'lbite_staff', 'administrator' ),
		'lbite_view_orders'      => array( 'lbite_staff', 'administrator' ),
		'lbite_manage_orders'    => array( 'lbite_staff', 'administrator' ),
		'lbite_use_pos'          => array( 'lbite_staff', 'administrator' ),

		// Admin-Capabilities (nur administrator)
		'lbite_manage_locations' => array( 'administrator' ),
		'lbite_manage_products'  => array( 'administrator' ),
		'lbite_manage_options'   => array( 'administrator' ),
		'lbite_manage_checkout'  => array( 'administrator' ),
		'lbite_manage_settings'  => array( 'administrator' ),
		'lbite_manage_features'  => array( 'administrator' ),
		'lbite_manage_roles'     => array( 'administrator' ),
		'lbite_manage_support'   => array( 'administrator' ),
		'lbite_view_debug'       => array( 'administrator' ),
	);

	/**
	 * Rollen bei Plugin-Aktivierung erstellen
	 */
	public static function create_roles() {
		// Staff-Rolle erstellen
		add_role(
			'lbite_staff',
			'Libre Bite Personal', // Keine Übersetzung bei Aktivierung um Early-Loading zu vermeiden
			array(
				'read'                 => true,
				'edit_posts'           => false,
				'delete_posts'         => false,
				'publish_posts'        => false,
				'upload_files'         => false,
				'lbite_view_dashboard' => true,
				'lbite_view_orders'    => true,
				'lbite_manage_orders'  => true,
				'lbite_use_pos'        => true,
			)
		);

		// Administrator-Rolle: alle Capabilities hinzufügen
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			foreach ( self::$capabilities as $cap => $roles ) {
				$admin_role->add_cap( $cap );
			}
		}

		// Shop Manager auch berechtigen (falls WooCommerce aktiv)
		$shop_manager = get_role( 'shop_manager' );
		if ( $shop_manager ) {
			$shop_manager->add_cap( 'lbite_view_dashboard' );
			$shop_manager->add_cap( 'lbite_view_orders' );
			$shop_manager->add_cap( 'lbite_manage_orders' );
			$shop_manager->add_cap( 'lbite_use_pos' );
			$shop_manager->add_cap( 'lbite_manage_locations' );
			$shop_manager->add_cap( 'lbite_manage_products' );
			$shop_manager->add_cap( 'lbite_manage_options' );
			$shop_manager->add_cap( 'lbite_manage_checkout' );
			$shop_manager->add_cap( 'lbite_manage_settings' );
		}
	}

	/**
	 * Rollen bei Plugin-Deaktivierung entfernen
	 */
	public static function remove_roles() {
		// Custom Roles entfernen (inkl. aller bekannten alten Benennungen)
		remove_role( 'lbite_staff' );
		remove_role( 'lbite_admin' );
		remove_role( 'lb_admin' );
		remove_role( 'lb_staff' );

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
	 * Prüfen ob aktueller Benutzer Personal ist
	 *
	 * @return bool
	 */
	public static function is_staff() {
		return current_user_can( 'lbite_view_dashboard' );
	}

	/**
	 * Prüfen ob aktueller Benutzer Administrator ist
	 *
	 * @return bool
	 */
	public static function is_admin() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Alias für is_admin() (Rückwärtskompatibilität)
	 *
	 * @return bool
	 */
	public static function is_super_admin() {
		return self::is_admin();
	}

	/**
	 * Alle Plugin-Capabilities abrufen
	 *
	 * @return array
	 */
	public static function get_all_capabilities() {
		return self::$capabilities;
	}

	/**
	 * Benutzerrolle für das Plugin ermitteln
	 *
	 * @param int|null $user_id Benutzer-ID (optional, Standard: aktueller Benutzer)
	 * @return string 'admin', 'staff' oder 'none'
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

		if ( user_can( $user_id, 'manage_options' ) ) {
			return 'admin';
		}

		if ( user_can( $user_id, 'lbite_view_dashboard' ) ) {
			return 'staff';
		}

		return 'none';
	}

	/**
	 * Migrations-Script für bestehende Benutzer
	 *
	 * Weist bestehenden Administratoren und Shop-Managern die neuen Capabilities zu
	 * und entfernt die obsolete lbite_admin-Rolle.
	 */
	public static function migrate_existing_users() {
		// Explizit bekannte veraltete Rollen entfernen
		$lbite_legacy_slugs = array( 'lbite_admin', 'lb_admin', 'lb_staff' );
		foreach ( $lbite_legacy_slugs as $lbite_legacy_slug ) {
			remove_role( $lbite_legacy_slug );
		}

		// Alle Rollen prüfen: nicht-erlaubte Rollen mit lbite_-Caps entfernen (z.B. OOS-Altlasten)
		global $wp_roles;
		$lbite_allowed_roles = array( 'lbite_staff', 'administrator', 'editor', 'author', 'contributor', 'subscriber', 'shop_manager', 'customer' );
		foreach ( array_keys( $wp_roles->roles ) as $lbite_role_slug ) {
			if ( in_array( $lbite_role_slug, $lbite_allowed_roles, true ) ) {
				continue;
			}
			$lbite_role_obj = get_role( $lbite_role_slug );
			if ( $lbite_role_obj ) {
				foreach ( array_keys( $lbite_role_obj->capabilities ) as $lbite_cap ) {
					if ( strpos( $lbite_cap, 'lbite_' ) === 0 ) {
						remove_role( $lbite_role_slug );
						break;
					}
				}
			}
		}

		// Veraltete Einträge aus lbite_custom_role_names bereinigen
		$lbite_custom_names = get_option( 'lbite_custom_role_names', array() );
		if ( is_array( $lbite_custom_names ) ) {
			foreach ( array_keys( $lbite_custom_names ) as $lbite_name_key ) {
				if ( ! get_role( $lbite_name_key ) ) {
					unset( $lbite_custom_names[ $lbite_name_key ] );
				}
			}
			update_option( 'lbite_custom_role_names', $lbite_custom_names );
		}

		// Veraltete Einträge aus lbite_disabled_roles bereinigen
		$lbite_disabled = get_option( 'lbite_disabled_roles', array() );
		if ( is_array( $lbite_disabled ) ) {
			$lbite_disabled = array_values( array_filter( $lbite_disabled, fn( $lbite_slug ) => null !== get_role( $lbite_slug ) ) );
			update_option( 'lbite_disabled_roles', $lbite_disabled );
		}

		// Administrator: alle Capabilities sicherstellen
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			foreach ( array_keys( self::$capabilities ) as $cap ) {
				if ( ! $admin_role->has_cap( $cap ) ) {
					$admin_role->add_cap( $cap );
				}
			}
		}

		// Shop Manager: Staff- und Admin-Capabilities sicherstellen
		$shop_manager = get_role( 'shop_manager' );
		if ( $shop_manager ) {
			$sm_caps = array(
				'lbite_view_dashboard',
				'lbite_view_orders',
				'lbite_manage_orders',
				'lbite_use_pos',
				'lbite_manage_locations',
				'lbite_manage_products',
				'lbite_manage_options',
				'lbite_manage_checkout',
				'lbite_manage_settings',
			);
			foreach ( $sm_caps as $cap ) {
				if ( ! $shop_manager->has_cap( $cap ) ) {
					$shop_manager->add_cap( $cap );
				}
			}
		}

		update_option( 'lbite_roles_version', '1.1.2' );
	}

	/**
	 * Prüfen ob Migration nötig ist
	 *
	 * @return bool
	 */
	public static function needs_migration() {
		$current_version = get_option( 'lbite_roles_version', '0' );
		return version_compare( $current_version, '1.1.2', '<' );
	}
}
