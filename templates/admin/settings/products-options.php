<?php
/**
 * Einstellungen: Produkt-Optionen (Add-ons)
 *
 * Ausgelagert aus products.php (weitere Aufteilung nach v3.2.0).
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Let customers customize products with add-ons, variants, or extras — sizes, toppings, sauces, anything configured per product.', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="products_options">

	<?php
	$lbite_toggle_key         = 'enable_product_options';
	$lbite_toggle_label       = __( 'Product Options (Add-ons)', 'libre-bite' );
	$lbite_toggle_description = __( 'Allow customers to customize products with add-ons, variants, or extras.', 'libre-bite' );
	$lbite_toggle_is_pro      = false;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';

	$lbite_toggle_key             = 'enable_modifier_groups';
	$lbite_toggle_label           = __( 'Option Groups', 'libre-bite' );
	$lbite_toggle_description     = __( 'Group options per product with rules: one or several choices, minimum, maximum and a number of included choices (e.g. "Sauce: choose 1", "Toppings: up to 4, 2 included"). Set up in the product editor under Product Options.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = lbite_has_pro_license();
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<?php if ( lbite_feature_enabled( 'enable_product_options' ) ) : ?>
	<p style="margin-bottom: 24px;">
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lbite_product_option' ) ); ?>" class="button">
			<?php esc_html_e( 'Manage Product Options', 'libre-bite' ); ?>
		</a>
	</p>
	<?php endif; ?>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
