<?php
/**
 * Feature-Toggles (Super-Admin Einstellungen)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-Datei, wird innerhalb einer Klassen-Methode via include geladen; Variablen befinden sich im Methoden-Scope, nicht im globalen Namespace.

$features = get_option( 'lbite_features', array() );

// Feature-Gruppen definieren
$feature_groups = array(
	'order_system' => array(
		'title'       => __( 'Order System', 'libre-bite' ),
		'description' => __( 'Basic order functions', 'libre-bite' ),
		'features'    => array(
			'enable_kanban_board'     => array(
				'label'       => __( 'Enable Order Overview', 'libre-bite' ),
				'description' => __( 'Kanban board for incoming orders with status tracking and fullscreen mode', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
			'enable_pos'              => array(
				'label'       => __( 'POS System', 'libre-bite' ),
				'description' => __( 'Enable POS system for on-site orders', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
			'enable_scheduled_orders' => array(
				'label'       => __( 'Pre-orders', 'libre-bite' ),
				'description' => __( 'Customers can place orders for later', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
		),
	),
	'checkout'     => array(
		'title'       => __( 'Checkout', 'libre-bite' ),
		'description' => __( 'Checkout process settings', 'libre-bite' ),
		'features'    => array(
			'enable_optimized_checkout' => array(
				'label'       => __( 'Optimized Checkout', 'libre-bite' ),
				'description' => __( 'Simplified checkout flow', 'libre-bite' ),
				'default'     => true,
				'premium'     => true,
			),
			'enable_tips'               => array(
				'label'       => __( 'Tip System', 'libre-bite' ),
				'description' => __( 'Show tip options in checkout', 'libre-bite' ),
				'default'     => true,
				'premium'     => true,
			),
			'enable_rounding'           => array(
				'label'       => __( '5-cent rounding', 'libre-bite' ),
				'description' => __( 'Round amounts to 5 cents (Switzerland)', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
			'enable_guest_checkout'     => array(
				'label'       => __( 'Guest Checkout', 'libre-bite' ),
				'description' => __( 'Allow orders without a customer account', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
			),
	),
	'locations'    => array(
		'title'       => __( 'Locations', 'libre-bite' ),
		'description' => __( 'Location management', 'libre-bite' ),
		'features'    => array(
			'enable_multi_location'    => array(
				'label'       => __( 'Multi-Location', 'libre-bite' ),
				'description' => __( 'Manage multiple locations', 'libre-bite' ),
				'default'     => false,
				'premium'     => true,
			),
			'enable_location_selector' => array(
				'label'       => __( 'Location Selection', 'libre-bite' ),
				'description' => __( 'Show location selection in frontend', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
			'enable_opening_hours'     => array(
				'label'       => __( 'Opening Hours', 'libre-bite' ),
				'description' => __( 'Manage opening hours per location', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
			'enable_table_ordering'    => array(
				'label'       => __( 'Table Management & Table Ordering', 'libre-bite' ),
				'description' => __( 'Create tables, define seats, generate QR codes and enable orders directly at the table', 'libre-bite' ),
				'default'     => false,
				'premium'     => true,
			),
			'enable_reservations'      => array(
				'label'       => __( 'Table Reservations', 'libre-bite' ),
				'description' => __( 'Customers can reserve tables online – frontend form via shortcode [lbite_reservation_form]', 'libre-bite' ),
				'default'     => false,
				'premium'     => true,
			),
		),
	),
	'notifications' => array(
		'title'       => __( 'Notifications', 'libre-bite' ),
		'description' => __( 'Email and sound notifications', 'libre-bite' ),
		'features'    => array(
			'enable_pickup_reminders'    => array(
				'label'       => __( 'Pickup Reminders', 'libre-bite' ),
				'description' => __( 'Send email reminder before pickup time', 'libre-bite' ),
				'default'     => true,
				'premium'     => true,
			),
			'enable_sound_notifications' => array(
				'label'       => __( 'Sound Notifications', 'libre-bite' ),
				'description' => __( 'Play sound alert for new orders', 'libre-bite' ),
				'default'     => true,
				'premium'     => true,
			),
			'enable_admin_email'         => array(
				'label'       => __( 'Admin Email', 'libre-bite' ),
				'description' => __( 'Email notification for new orders', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
		),
	),
	'products'     => array(
		'title'       => __( 'Products', 'libre-bite' ),
		'description' => __( 'Product extensions', 'libre-bite' ),
		'features'    => array(
			'enable_product_options'  => array(
				'label'       => __( 'Product Options', 'libre-bite' ),
				'description' => __( 'Additional options for products (add-ons)', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
			'enable_nutritional_info' => array(
				'label'       => __( 'Nutritional Information', 'libre-bite' ),
				'description' => __( 'Show nutritional information for products', 'libre-bite' ),
				'default'     => false,
				'premium'     => true,
			),
			'enable_allergens'        => array(
				'label'       => __( 'Allergens', 'libre-bite' ),
				'description' => __( 'Show allergen warnings for products', 'libre-bite' ),
				'default'     => false,
				'premium'     => true,
			),
		),
	),
);
?>
<?php if ( empty( $lbite_is_tab ) ) : ?>
<div class="wrap lbite-admin-wrap">
	<h1><?php esc_html_e( 'Feature Toggles', 'libre-bite' ); ?></h1>
<?php endif; ?>
	<p class="description"><?php esc_html_e( 'Enable or disable individual features of Libre Bite.', 'libre-bite' ); ?></p>

	<?php if ( function_exists( 'lbite_freemius' ) && ! lbite_freemius()->is_premium() ) : ?>
		<div class="lbite-upgrade-notice" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 25px; border-radius: 8px; margin: 20px 0; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
			<div style="flex: 1;">
				<h2 style="color: #fff; margin-top: 0; font-size: 22px;"><?php esc_html_e( 'Get Libre Bite Pro!', 'libre-bite' ); ?></h2>
				<p style="font-size: 16px; opacity: 0.9; margin-bottom: 0;">
					<?php esc_html_e( 'Unlock multi-location management, tip options, pickup reminders and much more.', 'libre-bite' ); ?>
				</p>
			</div>
			<div style="margin-left: 20px;">
				<a href="<?php echo esc_url( lbite_freemius()->get_upgrade_url() ); ?>" class="button button-primary button-hero" style="background: #fff; color: #764ba2; border: none; font-weight: bold;">
					<?php esc_html_e( 'Upgrade Now', 'libre-bite' ); ?>
				</a>
			</div>
		</div>
	<?php endif; ?>

	<form id="lbite-features-form" method="post">
		<?php wp_nonce_field( 'lbite_admin_nonce', 'lbite_nonce' ); ?>

		<div class="lbite-features-grid">
			<?php foreach ( $feature_groups as $group_key => $group ) : ?>
				<div class="lbite-feature-group">
					<h2><?php echo esc_html( $group['title'] ); ?></h2>
					<p class="description"><?php echo esc_html( $group['description'] ); ?></p>

					<table class="form-table lbite-features-table">
						<tbody>
							<?php foreach ( $group['features'] as $feature_key => $feature ) : ?>
								<?php
								$is_enabled = isset( $features[ $feature_key ] ) ? $features[ $feature_key ] : $feature['default'];
								$is_premium = $feature['premium'];
								?>
								<tr class="<?php echo $is_premium ? 'lbite-premium-feature' : ''; ?>">
									<th scope="row">
										<label for="<?php echo esc_attr( $feature_key ); ?>">
											<?php echo esc_html( $feature['label'] ); ?>
											<?php if ( $is_premium ) : ?>
												<span class="lbite-premium-badge" title="<?php esc_attr_e( 'Premium Feature', 'libre-bite' ); ?>">Premium</span>
											<?php endif; ?>
										</label>
									</th>
									<td>
										<label class="lbite-toggle">
											<input type="checkbox"
												   id="<?php echo esc_attr( $feature_key ); ?>"
												   name="features[<?php echo esc_attr( $feature_key ); ?>]"
												   value="1"
												   <?php checked( $is_enabled ); ?>>
											<span class="lbite-toggle-slider"></span>
										</label>
										<p class="description"><?php echo esc_html( $feature['description'] ); ?></p>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endforeach; ?>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'libre-bite' ); ?></button>
			<span class="lbite-save-status"></span>
		</p>
	</form>
<?php if ( empty( $lbite_is_tab ) ) : ?>
</div>
<?php endif; ?>


<?php ob_start(); ?>
jQuery(document).ready(function($) {
	$('#lbite-features-form').on('submit', function(e) {
		e.preventDefault();

		var $form = $(this);
		var $status = $form.find('.lbite-save-status');
		var $button = $form.find('button[type="submit"]');

		// Collect all feature states
		var features = {};
		$form.find('input[type="checkbox"]').each(function() {
			var key = $(this).attr('id');
			features[key] = $(this).is(':checked');
		});

		$button.prop('disabled', true);
		$status.text('<?php echo esc_js( __( 'Saving...', 'libre-bite' ) ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'lbite_save_features',
				nonce: $('#lbite_nonce').val(),
				features: JSON.stringify(features)
			},
			success: function(response) {
				if (response.success) {
					$status.text('<?php echo esc_js( __( 'Saved!', 'libre-bite' ) ); ?>');
					setTimeout(function() {
						$status.text('');
					}, 2000);
				} else {
					$status.text(response.data.message || '<?php echo esc_js( __( 'Error saving', 'libre-bite' ) ); ?>');
				}
			},
			error: function() {
				$status.text('<?php echo esc_js( __( 'Fehler beim Speichern', 'libre-bite' ) ); ?>');
			},
			complete: function() {
				$button.prop('disabled', false);
			}
		});
	});
});
<?php wp_add_inline_script( 'lbite-admin', ob_get_clean() ); ?>
