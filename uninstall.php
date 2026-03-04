<?php
/**
 * Deinstallation des Plugins
 *
 * Diese Datei wird aufgerufen, wenn das Plugin vollständig aus WordPress gelöscht wird.
 * Sie bereinigt alle Optionen, Custom Post Types und Metadaten, falls der Benutzer dies in den Einstellungen aktiviert hat.
 *
 * @package LibreBite
 */

// Wenn die Deinstallation nicht von WordPress aufgerufen wird, abbrechen.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Vollständige Daten-Bereinigung bei Deinstallation
 */
function lbite_uninstall_plugin() {
	// Prüfen ob Datenlöschung aktiviert ist.
	$delete_data = get_option( 'lbite_delete_data_on_uninstall', false ) || get_option( 'oos_delete_data_on_uninstall', false );

	if ( ! $delete_data ) {
		return;
	}

	global $wpdb;

	// 1. Custom Post Types löschen (Standorte, Produkt-Optionen).
	$post_types = array( 'lbite_location', 'oos_location', 'lbite_product_option', 'oos_product_option', 'lbite_table' );
	foreach ( $post_types as $post_type ) {
		// Begrenzt auf 500 zur Sicherheit, in der Regel gibt es nicht so viele Standorte/Optionen.
		$posts = get_posts(
			array(
				'post_type'   => $post_type,
				'numberposts' => 500,
				'fields'      => 'ids',
				'post_status' => 'any',
			)
		);
		foreach ( $posts as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	// 2. Optionen löschen (lbite_ und altes oos_ Präfix).
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( 'lbite_' ) . '%', $wpdb->esc_like( 'oos_' ) . '%' ) );

	// 3. Metadaten löschen.
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s OR meta_key LIKE %s", $wpdb->esc_like( '_lbite_' ) . '%', $wpdb->esc_like( '_oos_' ) . '%' ) );
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s OR meta_key LIKE %s", $wpdb->esc_like( 'lbite_' ) . '%', $wpdb->esc_like( 'oos_' ) . '%' ) );

	// 4. Rollen & Capabilities entfernen.
	remove_role( 'lbite_staff' );
	remove_role( 'lbite_admin' );
	remove_role( 'oos_staff' );
	remove_role( 'oos_admin' );

	$roles = array( 'administrator', 'shop_manager', 'editor' );
	$caps  = array(
		'lbite_view_dashboard',
		'lbite_view_orders',
		'lbite_manage_orders',
		'lbite_use_pos',
		'lbite_manage_locations',
		'lbite_manage_products',
		'lbite_manage_options',
		'lbite_manage_checkout',
		'lbite_manage_settings',
		'lbite_manage_features',
		'lbite_manage_roles',
		'lbite_manage_support',
		'lbite_view_debug',
	);

	foreach ( $roles as $role_name ) {
		$role = get_role( $role_name );
		if ( $role ) {
			foreach ( $caps as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}

	// 5. Cron Jobs entfernen.
	$cron_hooks = array( 'lbite_check_scheduled_orders', 'lbite_send_pickup_reminders' );
	foreach ( $cron_hooks as $hook ) {
		wp_clear_scheduled_hook( $hook );
	}

	// 6. Transients löschen.
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_lbite_' ) . '%', $wpdb->esc_like( '_transient_timeout_lbite_' ) . '%' ) );
}

// Bereinigung ausführen.
lbite_uninstall_plugin();
