<?php
/**
 * Einstellungen: Nährwerte & Ernährungsformen
 *
 * Ausgelagert aus products.php (weitere Aufteilung nach v3.2.0).
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_premium_allowed = function_exists( 'lbite_freemius' ) && lbite_freemius()->can_use_premium_code__premium_only();
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Show calories and nutritional values, allergen warnings, and dietary labels (vegan, gluten-free, etc.) on product pages.', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="products_nutrition">

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

	$lbite_toggle_key             = 'enable_dietary_labels';
	$lbite_toggle_label           = __( 'Dietary Labels', 'libre-bite' );
	$lbite_toggle_description     = __( 'Label dishes as vegan, vegetarian, gluten-free, lactose-free, spicy or alcohol-free on the product page and menu view. Works independently of the filter below.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';

	$lbite_toggle_key             = 'enable_dietary_filter';
	$lbite_toggle_label           = __( 'Dietary Filter', 'libre-bite' );
	$lbite_toggle_description     = __( 'Add a filter bar above the shop grid and menu view so guests can combine several dietary labels at once, without reloading the page.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
