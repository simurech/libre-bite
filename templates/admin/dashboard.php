<?php
/**
 * Template: Admin Dashboard
 *
 * Dynamische Kachel-Übersicht aller verfügbaren Menüpunkte.
 * Kacheln passen sich an aktive Features und Benutzerrolle an.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_plugin_name     = apply_filters( 'lbite_plugin_display_name', __( 'Libre Bite', 'libre-bite' ) );
$lbite_can_manage      = current_user_can( 'lbite_manage_settings' );
$lbite_can_locations   = current_user_can( 'lbite_manage_locations' );
$lbite_premium_allowed = function_exists( 'lbite_freemius' ) && lbite_freemius()->can_use_premium_code__premium_only();

$lbite_tiles = array();

// Bestellübersicht — für alle sichtbar wenn Feature aktiv
if ( lbite_feature_enabled( 'enable_kanban_board' ) ) {
	$lbite_tiles[] = array(
		'icon'  => 'dashicons-list-view',
		'title' => __( 'Order Overview', 'libre-bite' ),
		'desc'  => __( 'View and manage incoming orders in the Kanban board.', 'libre-bite' ),
		'url'   => admin_url( 'admin.php?page=lbite-order-board' ),
		'color' => '#2271b1',
	);
}

// Kassensystem — für alle sichtbar wenn Feature aktiv
if ( lbite_feature_enabled( 'enable_pos' ) ) {
	$lbite_tiles[] = array(
		'icon'  => 'dashicons-cart',
		'title' => __( 'POS System', 'libre-bite' ),
		'desc'  => __( 'Process in-person orders with the Point of Sale interface.', 'libre-bite' ),
		'url'   => admin_url( 'admin.php?page=lbite-pos' ),
		'color' => '#00a32a',
	);
}

// Admin-Bereich
if ( $lbite_can_locations ) {
	$lbite_tiles[] = array(
		'icon'  => 'dashicons-location',
		'title' => __( 'Locations', 'libre-bite' ),
		'desc'  => __( 'Manage pickup locations, opening hours, and timeslots.', 'libre-bite' ),
		'url'   => admin_url( 'edit.php?post_type=lbite_location' ),
		'color' => '#8c5aa9',
	);

	if ( lbite_feature_enabled( 'enable_table_ordering' ) ) {
		$lbite_tiles[] = array(
			'icon'  => 'dashicons-grid-view',
			'title' => __( 'Tables', 'libre-bite' ),
			'desc'  => __( 'Manage tables and generate QR codes for table ordering.', 'libre-bite' ),
			'url'   => admin_url( 'edit.php?post_type=lbite_table' ),
			'color' => '#c3522e',
		);

		if ( lbite_feature_enabled( 'enable_reservations' ) ) {
			$lbite_tiles[] = array(
				'icon'  => 'dashicons-calendar-alt',
				'title' => __( 'Reservations', 'libre-bite' ),
				'desc'  => __( 'View and manage table reservations.', 'libre-bite' ),
				'url'   => admin_url( 'admin.php?page=lbite-reservation-board' ),
				'color' => '#c3522e',
			);
		}
	}
}

if ( $lbite_can_manage ) {
	$lbite_tiles[] = array(
		'icon'  => 'dashicons-chart-bar',
		'title' => __( 'Statistics', 'libre-bite' ),
		'desc'  => __( 'Revenue and order statistics per location and time period.', 'libre-bite' ),
		'url'   => admin_url( 'admin.php?page=lbite-settings&tab=statistics' ),
		'color' => '#b32d2e',
	);
}

// Hilfe — für alle sichtbar
$lbite_tiles[] = array(
	'icon'  => 'dashicons-editor-help',
	'title' => __( 'Help & Support', 'libre-bite' ),
	'desc'  => __( 'Documentation, guides, and support contact.', 'libre-bite' ),
	'url'   => admin_url( 'admin.php?page=lbite-help' ),
	'color' => '#50575e',
);

if ( $lbite_can_manage ) {
	$lbite_tiles[] = array(
		'icon'  => 'dashicons-admin-settings',
		'title' => __( 'Settings', 'libre-bite' ),
		'desc'  => __( 'Configure features, locations, checkout, branding, and more.', 'libre-bite' ),
		'url'   => admin_url( 'admin.php?page=lbite-settings' ),
		'color' => '#1d2327',
	);
}
?>

<div class="wrap lbite-admin-dashboard">
	<h1><?php echo esc_html( $lbite_plugin_name ); ?></h1>

	<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-top: 20px; align-items: stretch;">
		<?php foreach ( $lbite_tiles as $lbite_tile ) : ?>
		<a href="<?php echo esc_url( $lbite_tile['url'] ); ?>" style="text-decoration: none; color: inherit; display: flex;">
			<div style="background: #fff; border: 1px solid #dcdcde; border-radius: 8px; padding: 24px 20px; transition: box-shadow 0.15s; border-top: 4px solid <?php echo esc_attr( $lbite_tile['color'] ); ?>; display: flex; flex-direction: column; width: 100%;" onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.12)'" onmouseout="this.style.boxShadow='none'">
				<span class="dashicons <?php echo esc_attr( $lbite_tile['icon'] ); ?>" style="font-size: 28px; width: 28px; height: 28px; color: <?php echo esc_attr( $lbite_tile['color'] ); ?>; margin-bottom: 10px; display: block;"></span>
				<strong style="font-size: 15px; display: block; margin-bottom: 6px; color: #1d2327;"><?php echo esc_html( $lbite_tile['title'] ); ?></strong>
				<span style="font-size: 13px; color: #50575e; line-height: 1.5; flex: 1;"><?php echo esc_html( $lbite_tile['desc'] ); ?></span>
				<span style="display: block; margin-top: 16px; font-size: 13px; color: <?php echo esc_attr( $lbite_tile['color'] ); ?>;">
					<?php esc_html_e( 'Go to page', 'libre-bite' ); ?> <span class="dashicons dashicons-arrow-right-alt" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span>
				</span>
			</div>
		</a>
		<?php endforeach; ?>
	</div>
</div>
