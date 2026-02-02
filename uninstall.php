<?php
/**
 * Uninstall Libre Bite
 *
 * Runs when the plugin is uninstalled via the WordPress admin.
 *
 * @package LibreBite
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if data deletion is enabled (check both old and new option names for compatibility).
$delete_data = get_option( 'lb_delete_data_on_uninstall', false ) || get_option( 'oos_delete_data_on_uninstall', false );

if ( ! $delete_data ) {
	// Do not delete data, just exit.
	return;
}

global $wpdb;

// 1. Delete CPTs: lb_location (and legacy oos_location).
$locations = get_posts(
	array(
		'post_type'      => array( 'lb_location', 'oos_location' ),
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	)
);

foreach ( $locations as $location_id ) {
	wp_delete_post( $location_id, true );
}

// 2. Delete CPTs: lb_product_option (and legacy oos_product_option).
$options = get_posts(
	array(
		'post_type'      => array( 'lb_product_option', 'oos_product_option' ),
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	)
);

foreach ( $options as $option_id ) {
	wp_delete_post( $option_id, true );
}

// 3. Delete all lb_* and oos_* options.
$plugin_options = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( 'lb_' ) . '%',
		$wpdb->esc_like( 'oos_' ) . '%'
	)
);

foreach ( $plugin_options as $option_name ) {
	delete_option( $option_name );
}

// 4. Delete order/post meta with _lb_ and _oos_ prefix.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
		$wpdb->esc_like( '_lb_' ) . '%',
		$wpdb->esc_like( '_oos_' ) . '%'
	)
);

// 5. Delete user meta with lb_ and oos_ prefix.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
		$wpdb->esc_like( 'lb_' ) . '%',
		$wpdb->esc_like( 'oos_' ) . '%'
	)
);

// 6. Remove custom roles (both new and legacy).
remove_role( 'lb_staff' );
remove_role( 'lb_admin' );
remove_role( 'oos_staff' );
remove_role( 'oos_admin' );

// 7. Remove custom capabilities from existing roles.
$roles = array( 'administrator', 'shop_manager', 'editor' );
$caps  = array(
	// New lb_ capabilities
	'lb_view_dashboard',
	'lb_view_orders',
	'lb_manage_orders',
	'lb_use_pos',
	'lb_manage_locations',
	'lb_manage_products',
	'lb_manage_options',
	'lb_manage_checkout',
	'lb_manage_settings',
	'lb_manage_features',
	'lb_manage_roles',
	'lb_manage_support',
	'lb_view_debug',
	// Legacy oos_ capabilities
	'oos_view_dashboard',
	'oos_view_orders',
	'oos_manage_orders',
	'oos_use_pos',
	'oos_manage_locations',
	'oos_manage_products',
	'oos_manage_options',
	'oos_manage_checkout',
	'oos_manage_settings',
	'oos_manage_features',
	'oos_manage_roles',
	'oos_manage_support',
	'oos_view_debug',
	// Other legacy capabilities
	'manage_lb_locations',
	'manage_lb_options',
	'view_lb_dashboard',
	'view_lb_pos',
	'use_lb_pos',
);

foreach ( $roles as $role_name ) {
	$role = get_role( $role_name );
	if ( $role ) {
		foreach ( $caps as $cap ) {
			$role->remove_cap( $cap );
		}
	}
}

// 8. Clear scheduled cron jobs (both new and legacy).
$cron_hooks = array(
	'lb_check_pickup_reminders',
	'lb_check_preorders',
	'lb_check_scheduled_orders',
	'lb_send_pickup_reminders',
	'oos_check_pickup_reminders',
	'oos_check_preorders',
	'oos_check_scheduled_orders',
	'oos_send_pickup_reminders',
	'oos_cleanup_expired_sessions',
);

foreach ( $cron_hooks as $hook ) {
	$timestamp = wp_next_scheduled( $hook );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, $hook );
	}
	wp_clear_scheduled_hook( $hook );
}

// 9. Delete transients (both new and legacy).
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_lb_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_lb_' ) . '%',
		$wpdb->esc_like( '_transient_oos_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_oos_' ) . '%'
	)
);
