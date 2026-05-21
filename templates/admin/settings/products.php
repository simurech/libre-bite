<?php
/**
 * Tab: Produkte
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_premium_allowed = function_exists( 'lbite_freemius' ) && lbite_freemius()->can_use_premium_code__premium_only();
?>
<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="products">

	<?php
	$lbite_toggle_key         = 'enable_product_options';
	$lbite_toggle_label       = __( 'Product Options (Add-ons)', 'libre-bite' );
	$lbite_toggle_description = __( 'Allow customers to customize products with add-ons, variants, or extras.', 'libre-bite' );
	$lbite_toggle_is_pro      = false;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<?php if ( lbite_feature_enabled( 'enable_product_options' ) ) : ?>
	<p style="margin-bottom: 24px;">
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lbite_product_option' ) ); ?>" class="button">
			<?php esc_html_e( 'Manage Product Options', 'libre-bite' ); ?>
		</a>
	</p>
	<?php endif; ?>

	<hr style="margin: 24px 0;">
	<h3>
		<?php esc_html_e( 'Nutritional Information', 'libre-bite' ); ?>
		<span class="lbite-pro-badge">Pro</span>
	</h3>

	<?php
	$lbite_toggle_key             = 'enable_nutritional_info';
	$lbite_toggle_label           = __( 'Show Nutritional Information', 'libre-bite' );
	$lbite_toggle_description     = __( 'Display calorie counts and nutritional values on product pages.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';

	$lbite_toggle_key             = 'enable_allergens';
	$lbite_toggle_label           = __( 'Show Allergen Warnings', 'libre-bite' );
	$lbite_toggle_description     = __( 'Display allergen information on product pages.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
