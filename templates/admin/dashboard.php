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

// Leerzustände für neue Installationen: ohne Standort und ohne aktive
// Zahlungsart kann noch keine einzige Bestellung durchlaufen, das war
// bisher nirgends auf dem Dashboard erkennbar (Audit 26.09.2026, AP-20).
$lbite_has_location = $lbite_can_locations && (bool) get_posts(
	array(
		'post_type'      => 'lbite_location',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
	)
);

$lbite_has_active_payment_method = (bool) array_filter(
	get_option( 'lbite_pos_payment_methods', array() ),
	function( $lbite_pm ) {
		return ! empty( $lbite_pm['enabled'] );
	}
);

$lbite_tiles          = array();
$lbite_inactive_tiles = array();

$lbite_disabled_desc          = __( 'This module is currently disabled. A manager can enable it under Settings.', 'libre-bite' );
$lbite_disabled_desc_no_license = __( 'This is a Pro feature. A manager can activate it once a Pro license is active.', 'libre-bite' );
$lbite_premium_features       = class_exists( 'LBite_Features' ) ? LBite_Features::get_premium_features() : array();

/**
 * Hinweistext für eine inaktive Kachel: unterscheidet, ob ein blosser
 * Schalter fehlt oder ob die Funktion Pro ist und keine Lizenz vorliegt –
 * «Ein Manager kann es aktivieren» war für lizenzpflichtige Funktionen
 * schlicht falsch (Audit 26.09.2026, AP-17).
 *
 * @param string $feature_key Feature-Schlüssel.
 * @return string
 */
$lbite_disabled_tile_desc = function( $feature_key ) use ( $lbite_premium_features, $lbite_premium_allowed, $lbite_disabled_desc, $lbite_disabled_desc_no_license ) {
	if ( in_array( $feature_key, $lbite_premium_features, true ) && ! $lbite_premium_allowed ) {
		return $lbite_disabled_desc_no_license;
	}
	return $lbite_disabled_desc;
};

// Bestellübersicht
if ( lbite_feature_enabled( 'enable_kanban_board' ) ) {
	$lbite_tiles[] = array(
		'icon'  => 'dashicons-list-view',
		'title' => __( 'Order Overview', 'libre-bite' ),
		'desc'  => __( 'View and manage incoming orders in the Kanban board.', 'libre-bite' ),
		'url'   => admin_url( 'admin.php?page=lbite-order-board' ),
		'color' => 'var(--lbite-accent-blue, #2271b1)',
	);
} else {
	$lbite_inactive_tiles[] = array(
		'icon'  => 'dashicons-list-view',
		'title' => __( 'Order Overview', 'libre-bite' ),
		'desc'  => $lbite_disabled_tile_desc( 'enable_kanban_board' ),
	);
}

// Kassensystem
if ( lbite_feature_enabled( 'enable_pos' ) ) {
	$lbite_tiles[] = array(
		'icon'  => 'dashicons-cart',
		'title' => __( 'POS System', 'libre-bite' ),
		'desc'  => __( 'Process in-person orders with the Point of Sale interface.', 'libre-bite' ),
		'url'   => admin_url( 'admin.php?page=lbite-pos' ),
		'color' => 'var(--lbite-accent-green, #007f26)',
	);
} else {
	$lbite_inactive_tiles[] = array(
		'icon'  => 'dashicons-cart',
		'title' => __( 'POS System', 'libre-bite' ),
		'desc'  => $lbite_disabled_tile_desc( 'enable_pos' ),
	);
}

// Admin-Bereich
if ( $lbite_can_locations ) {
	$lbite_tiles[] = array(
		'icon'  => 'dashicons-location',
		'title' => __( 'Locations', 'libre-bite' ),
		'desc'  => __( 'Manage pickup locations, opening hours, and timeslots.', 'libre-bite' ),
		'url'   => admin_url( 'edit.php?post_type=lbite_location' ),
		'color' => 'var(--lbite-accent-purple, #8c5aa9)',
	);

	if ( lbite_feature_enabled( 'enable_table_ordering' ) ) {
		$lbite_tiles[] = array(
			'icon'  => 'dashicons-grid-view',
			'title' => __( 'Tables', 'libre-bite' ),
			'desc'  => __( 'Manage tables and generate QR codes for table ordering.', 'libre-bite' ),
			'url'   => admin_url( 'edit.php?post_type=lbite_table' ),
			'color' => 'var(--lbite-accent-orange, #c3522e)',
		);
	} else {
		$lbite_inactive_tiles[] = array(
			'icon'  => 'dashicons-grid-view',
			'title' => __( 'Tables', 'libre-bite' ),
			'desc'  => $lbite_disabled_tile_desc( 'enable_table_ordering' ),
		);
	}

	if ( lbite_feature_enabled( 'enable_reservations' ) ) {
		$lbite_tiles[] = array(
			'icon'  => 'dashicons-calendar-alt',
			'title' => __( 'Reservations', 'libre-bite' ),
			'desc'  => __( 'View and manage table reservations.', 'libre-bite' ),
			'url'   => admin_url( 'admin.php?page=lbite-reservation-board' ),
			'color' => 'var(--lbite-accent-orange, #c3522e)',
		);
	} else {
		$lbite_inactive_tiles[] = array(
			'icon'  => 'dashicons-calendar-alt',
			'title' => __( 'Reservations', 'libre-bite' ),
			'desc'  => $lbite_disabled_tile_desc( 'enable_reservations' ),
		);
	}
}

if ( $lbite_can_manage ) {
	$lbite_tiles[] = array(
		'icon'  => 'dashicons-chart-bar',
		'title' => __( 'Statistics', 'libre-bite' ),
		'desc'  => __( 'Revenue and order statistics per location and time period.', 'libre-bite' ),
		'url'   => admin_url( 'admin.php?page=lbite-statistics' ),
		'color' => 'var(--lbite-accent-red, #b32d2e)',
	);
}

// Support — für alle sichtbar
$lbite_tiles[] = array(
	'icon'  => 'dashicons-sos',
	'title' => __( 'Support', 'libre-bite' ),
	'desc'  => __( 'Contact details and conditions for support requests.', 'libre-bite' ),
	'url'   => admin_url( 'admin.php?page=lbite-support' ),
	'color' => 'var(--lbite-accent-neutral, #50575e)',
);

if ( $lbite_can_manage ) {
	$lbite_tiles[] = array(
		'icon'  => 'dashicons-admin-settings',
		'title' => __( 'Settings', 'libre-bite' ),
		'desc'  => __( 'Configure features, locations, checkout, branding, and more.', 'libre-bite' ),
		'url'   => admin_url( 'admin.php?page=lbite-settings' ),
		'color' => 'var(--lbite-accent-strong, #1d2327)',
	);
}

// Inaktive Module ans Ende der Übersicht, ausgegraut und ohne Link.
$lbite_tiles = array_merge( $lbite_tiles, $lbite_inactive_tiles );
?>

<div class="wrap lbite-admin-dashboard">
	<h1><?php echo esc_html( $lbite_plugin_name ); ?></h1>

	<?php if ( class_exists( 'LBite_Setup_Wizard' ) && LBite_Setup_Wizard::is_pending() && LBite_Setup_Wizard::current_user_can_run() ) : ?>
		<div class="notice notice-info">
			<p>
				<?php esc_html_e( 'The setup assistant has not been completed yet. It walks through the essentials for each module you turn on.', 'libre-bite' ); ?>
				<a href="<?php echo esc_url( LBite_Setup_Wizard::get_url() ); ?>"><?php esc_html_e( 'Start setup', 'libre-bite' ); ?></a>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $lbite_can_locations && ! $lbite_has_location ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( 'No location has been created yet — orders and the POS need at least one to work.', 'libre-bite' ); ?>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=lbite_location' ) ); ?>"><?php esc_html_e( 'Create location', 'libre-bite' ); ?></a>
			</p>
		</div>
	<?php elseif ( $lbite_can_manage && ! $lbite_has_active_payment_method ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( 'No payment method is currently enabled for the POS — staff cannot complete a sale until at least one is turned on.', 'libre-bite' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-settings&tab=pos' ) ); ?>"><?php esc_html_e( 'Enable a payment method', 'libre-bite' ); ?></a>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $lbite_can_manage && function_exists( 'lbite_checkout_uses_blocks' ) && lbite_checkout_uses_blocks() ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( 'Your checkout page uses the WooCommerce Checkout block. Libre Bite currently only works with the classic checkout shortcode.', 'libre-bite' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-settings&tab=checkout_mode' ) ); ?>"><?php esc_html_e( 'Learn more', 'libre-bite' ); ?></a>
			</p>
		</div>
	<?php endif; ?>

	<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-top: 20px; align-items: stretch;">
		<?php foreach ( $lbite_tiles as $lbite_tile ) : ?>
			<?php if ( ! empty( $lbite_tile['url'] ) ) : ?>
			<a href="<?php echo esc_url( $lbite_tile['url'] ); ?>" style="text-decoration: none; color: inherit; display: flex;">
				<div style="background: var(--lbite-surface, #fff); border: 1px solid var(--lbite-border, #dcdcde); border-radius: 8px; padding: 24px 20px; transition: box-shadow 0.15s; border-top: 4px solid <?php echo esc_attr( $lbite_tile['color'] ); ?>; display: flex; flex-direction: column; width: 100%;" onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.12)'" onmouseout="this.style.boxShadow='none'">
					<span class="dashicons <?php echo esc_attr( $lbite_tile['icon'] ); ?>" style="font-size: 28px; width: 28px; height: 28px; color: <?php echo esc_attr( $lbite_tile['color'] ); ?>; margin-bottom: 10px; display: block;"></span>
					<strong style="font-size: 15px; display: block; margin-bottom: 6px; color: var(--lbite-text, #1d2327);"><?php echo esc_html( $lbite_tile['title'] ); ?></strong>
					<span style="font-size: 13px; color: var(--lbite-text-muted, #50575e); line-height: 1.5; flex: 1;"><?php echo esc_html( $lbite_tile['desc'] ); ?></span>
					<span style="display: block; margin-top: 16px; font-size: 13px; color: <?php echo esc_attr( $lbite_tile['color'] ); ?>;">
						<?php esc_html_e( 'Go to page', 'libre-bite' ); ?> <span class="dashicons dashicons-arrow-right-alt" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span>
					</span>
				</div>
			</a>
			<?php else : ?>
			<div style="display: flex;" aria-disabled="true">
				<div style="background: var(--lbite-surface-muted, #f0f0f1); border: 1px dashed var(--lbite-border, #dcdcde); border-radius: 8px; padding: 24px 20px; display: flex; flex-direction: column; width: 100%; opacity: 0.6;">
					<span class="dashicons <?php echo esc_attr( $lbite_tile['icon'] ); ?>" style="font-size: 28px; width: 28px; height: 28px; color: var(--lbite-text-subtle, #8c8f94); margin-bottom: 10px; display: block;"></span>
					<strong style="font-size: 15px; display: block; margin-bottom: 6px; color: var(--lbite-text-muted, #50575e);"><?php echo esc_html( $lbite_tile['title'] ); ?></strong>
					<span style="font-size: 13px; color: var(--lbite-text-subtle, #8c8f94); line-height: 1.5; flex: 1;"><?php echo esc_html( $lbite_tile['desc'] ); ?></span>
					<span style="display: block; margin-top: 16px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; color: var(--lbite-text-subtle, #8c8f94);">
						<?php esc_html_e( 'Inactive', 'libre-bite' ); ?>
					</span>
				</div>
			</div>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
</div>
