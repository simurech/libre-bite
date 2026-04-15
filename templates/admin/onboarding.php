<?php
/**
 * Onboarding-Seite – Ersteinrichtung nach Aktivierung
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Aktuelle Feature-Einstellungen laden (Standard: alle false bei Erstinstallation)
$lbite_current_features = get_option( 'lbite_features', array() );

// Feature-Gruppen mit Metadaten für die Darstellung
$lbite_feature_groups = array(
	array(
		'label'    => __( 'Order System', 'libre-bite' ),
		'icon'     => '🍽️',
		'features' => array(
			array(
				'key'         => 'enable_kanban_board',
				'label'       => __( 'Order Overview', 'libre-bite' ),
				'description' => __( 'Kanban board for incoming orders with status tracking and fullscreen mode.', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_pos',
				'label'       => __( 'POS System', 'libre-bite' ),
				'description' => __( 'Internal POS system for orders placed on-site.', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_scheduled_orders',
				'label'       => __( 'Pre-Orders', 'libre-bite' ),
				'description' => __( 'Customers can choose pickup times in advance.', 'libre-bite' ),
				'pro'         => false,
			),
		),
	),
	array(
		'label'    => __( 'Checkout', 'libre-bite' ),
		'icon'     => '🛒',
		'features' => array(
			array(
				'key'         => 'enable_optimized_checkout',
				'label'       => __( 'Optimized Checkout', 'libre-bite' ),
				'description' => __( 'Simplified checkout process tailored for the food service industry.', 'libre-bite' ),
				'pro'         => true,
			),
			array(
				'key'         => 'enable_tips',
				'label'       => __( 'Tips', 'libre-bite' ),
				'description' => __( 'Customers can select a tip at checkout.', 'libre-bite' ),
				'pro'         => true,
			),
			array(
				'key'         => 'enable_rounding',
				'label'       => __( '5-Centime Rounding', 'libre-bite' ),
				'description' => __( 'Round amounts to the nearest 5 centimes (CH).', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_guest_checkout',
				'label'       => __( 'Guest Checkout', 'libre-bite' ),
				'description' => __( 'Allow orders without a customer account.', 'libre-bite' ),
				'pro'         => false,
			),
		),
	),
	array(
		'label'    => __( 'Locations', 'libre-bite' ),
		'icon'     => '📍',
		'features' => array(
			array(
				'key'         => 'enable_location_selector',
				'label'       => __( 'Location Selection', 'libre-bite' ),
				'description' => __( 'Customers choose their pickup location when ordering.', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_opening_hours',
				'label'       => __( 'Opening Hours', 'libre-bite' ),
				'description' => __( 'Only allow orders during configured opening hours.', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_multi_location',
				'label'       => __( 'Multi-Location', 'libre-bite' ),
				'description' => __( 'Manage multiple locations with individual settings.', 'libre-bite' ),
				'pro'         => true,
			),
			array(
				'key'         => 'enable_table_ordering',
				'label'       => __( 'Table Management & Table Ordering', 'libre-bite' ),
				'description' => __( 'Create tables, define seats, generate QR codes, and enable ordering directly at the table.', 'libre-bite' ),
				'pro'         => true,
			),
		),
	),
	array(
		'label'    => __( 'Notifications', 'libre-bite' ),
		'icon'     => '🔔',
		'features' => array(
			array(
				'key'         => 'enable_admin_email',
				'label'       => __( 'Admin Email', 'libre-bite' ),
				'description' => __( 'Email notification for new orders.', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_sound_notifications',
				'label'       => __( 'Sound Notifications', 'libre-bite' ),
				'description' => __( 'Audible alert for new orders in the dashboard.', 'libre-bite' ),
				'pro'         => true,
			),
			array(
				'key'         => 'enable_pickup_reminders',
				'label'       => __( 'Pickup Reminders', 'libre-bite' ),
				'description' => __( 'Automatic email reminder to customers shortly before their pickup time.', 'libre-bite' ),
				'pro'         => true,
			),
		),
	),
	array(
		'label'    => __( 'Products', 'libre-bite' ),
		'icon'     => '🧩',
		'features' => array(
			array(
				'key'         => 'enable_product_options',
				'label'       => __( 'Product Options', 'libre-bite' ),
				'description' => __( 'Configure add-ons and extras per product (e.g. sauces, side dishes).', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_nutritional_info',
				'label'       => __( 'Nutritional Information', 'libre-bite' ),
				'description' => __( 'Display calories and nutritional values per product.', 'libre-bite' ),
				'pro'         => true,
			),
			array(
				'key'         => 'enable_allergens',
				'label'       => __( 'Allergens', 'libre-bite' ),
				'description' => __( 'Allergen labelling in accordance with EU food information regulation.', 'libre-bite' ),
				'pro'         => true,
			),
		),
	),
);

// Prüfen ob Premium aktiv
$lbite_is_premium = function_exists( 'lbite_freemius' ) && lbite_freemius()->is_premium();
?>
<div class="lbite-onboarding-page">

	<div class="lbite-onboarding-header">
		<div class="lbite-onboarding-header-inner">
			<h1><?php esc_html_e( 'Welcome to Libre Bite!', 'libre-bite' ); ?></h1>
			<p><?php esc_html_e( 'Activate the features you need for your restaurant or café. You can adjust this selection at any time under Settings → Features.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<div class="lbite-onboarding-body">

		<?php foreach ( $lbite_feature_groups as $lbite_group ) : ?>
		<div class="lbite-onboarding-group">
			<h2 class="lbite-onboarding-group-title">
				<span class="lbite-onboarding-group-icon"><?php echo esc_html( $lbite_group['icon'] ); ?></span>
				<?php echo esc_html( $lbite_group['label'] ); ?>
			</h2>
			<div class="lbite-onboarding-cards">
				<?php foreach ( $lbite_group['features'] as $lbite_feature ) :
					$lbite_is_enabled = isset( $lbite_current_features[ $lbite_feature['key'] ] ) ? (bool) $lbite_current_features[ $lbite_feature['key'] ] : false;
					$lbite_is_pro_locked = $lbite_feature['pro'] && ! $lbite_is_premium;
				?>
				<div class="lbite-onboarding-card <?php echo $lbite_is_pro_locked ? 'lbite-onboarding-card--pro' : ''; ?>" data-feature="<?php echo esc_attr( $lbite_feature['key'] ); ?>">
					<?php if ( $lbite_feature['pro'] ) : ?>
					<span class="lbite-onboarding-pro-badge"><?php esc_html_e( 'Pro', 'libre-bite' ); ?></span>
					<?php endif; ?>

					<div class="lbite-onboarding-card-header">
						<span class="lbite-onboarding-card-label"><?php echo esc_html( $lbite_feature['label'] ); ?></span>
						<button
							type="button"
							class="lbite-onboarding-toggle <?php echo $lbite_is_enabled ? 'is-active' : ''; ?> <?php echo $lbite_is_pro_locked ? 'is-locked' : ''; ?>"
							data-feature="<?php echo esc_attr( $lbite_feature['key'] ); ?>"
							aria-pressed="<?php echo $lbite_is_enabled ? 'true' : 'false'; ?>"
							<?php echo $lbite_is_pro_locked ? 'disabled' : ''; ?>
						>
							<span class="lbite-onboarding-toggle-track">
								<span class="lbite-onboarding-toggle-thumb"></span>
							</span>
							<span class="lbite-onboarding-toggle-label">
								<?php echo $lbite_is_enabled ? esc_html__( 'ON', 'libre-bite' ) : esc_html__( 'OFF', 'libre-bite' ); ?>
							</span>
						</button>
					</div>
					<p class="lbite-onboarding-card-description"><?php echo esc_html( $lbite_feature['description'] ); ?></p>

					<?php if ( $is_pro_locked ) : ?>
					<div class="lbite-onboarding-pro-notice">
						<?php
						printf(
							/* translators: %s: URL zu Upgrade-Seite */
							'<a href="%s" target="_blank">%s</a>',
							esc_url( admin_url( 'admin.php?page=lbite-pricing' ) ),
							esc_html__( 'Unlock Pro Version →', 'libre-bite' )
						);
						?>
					</div>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endforeach; ?>

		<div class="lbite-onboarding-next-steps">
			<h2><?php esc_html_e( 'Next Steps', 'libre-bite' ); ?></h2>
			<ul class="lbite-onboarding-checklist">
				<li>
					<span class="lbite-onboarding-check-icon">☐</span>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=lbite_location' ) ); ?>">
						<?php esc_html_e( 'Create your first location', 'libre-bite' ); ?>
					</a>
					<span class="lbite-onboarding-check-hint"><?php esc_html_e( 'Orders cannot be accepted without a location.', 'libre-bite' ); ?></span>
				</li>
				<li>
					<span class="lbite-onboarding-check-icon">☐</span>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-settings' ) ); ?>">
						<?php esc_html_e( 'Configure opening hours and time slots', 'libre-bite' ); ?>
					</a>
					<span class="lbite-onboarding-check-hint"><?php esc_html_e( 'Define when orders are possible.', 'libre-bite' ); ?></span>
				</li>
				<li>
					<span class="lbite-onboarding-check-icon">☐</span>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-order-board' ) ); ?>">
						<?php esc_html_e( 'Try the order overview', 'libre-bite' ); ?>
					</a>
					<span class="lbite-onboarding-check-hint"><?php esc_html_e( 'Here you can see all incoming orders.', 'libre-bite' ); ?></span>
				</li>
			</ul>
		</div>

		<div class="lbite-onboarding-footer">
			<button type="button" id="lbite-onboarding-complete" class="button button-primary button-hero">
				<?php esc_html_e( 'Complete Setup', 'libre-bite' ); ?>
				<span class="dashicons dashicons-arrow-right-alt" style="margin-left: 5px; margin-top: 2px;"></span>
			</button>
			<p class="lbite-onboarding-footer-hint">
				<?php esc_html_e( 'You can adjust all features at any time under Settings → Features.', 'libre-bite' ); ?>
			</p>
		</div>

	</div>
</div>
