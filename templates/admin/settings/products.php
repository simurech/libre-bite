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
$lbite_availability_hint_style = get_option( 'lbite_availability_hint_style', 'popup' );
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
	<h3><?php esc_html_e( 'Item Notes', 'libre-bite' ); ?></h3>

	<?php
	$lbite_toggle_key         = 'enable_item_notes_pos';
	$lbite_toggle_label       = __( 'Item Notes in POS', 'libre-bite' );
	$lbite_toggle_description = __( 'Allow staff to add a short note to individual cart items in the POS system.', 'libre-bite' );
	$lbite_toggle_is_pro      = false;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';

	$lbite_toggle_key         = 'enable_item_notes_checkout';
	$lbite_toggle_label       = __( 'Item Notes in Online Checkout', 'libre-bite' );
	$lbite_toggle_description = __( 'Allow customers to add a note to individual cart items at checkout.', 'libre-bite' );
	$lbite_toggle_is_pro      = false;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

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

	<hr style="margin: 24px 0;">
	<h3>
		<?php esc_html_e( 'Availability Hint & Filter', 'libre-bite' ); ?>
		<span class="lbite-pro-badge">Pro</span>
	</h3>
	<p class="description" style="margin-bottom: 12px;">
		<?php esc_html_e( 'Controls the "Available at X of Y locations" hint on category and product pages, and the "Show only available products" filter on the shop page. Both appear for products that are disabled at at least one location.', 'libre-bite' ); ?>
	</p>

	<?php
	$lbite_toggle_key             = 'enable_availability_hint_category';
	$lbite_toggle_label           = __( 'Availability Hint on Category Pages', 'libre-bite' );
	$lbite_toggle_description     = __( 'Show the availability hint on shop and category (archive) pages.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';

	$lbite_toggle_key             = 'enable_availability_hint_product';
	$lbite_toggle_label           = __( 'Availability Hint on Product Page', 'libre-bite' );
	$lbite_toggle_description     = __( 'Show the availability hint on the single product page.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';

	$lbite_toggle_key             = 'enable_availability_filter';
	$lbite_toggle_label           = __( 'Availability Filter on Shop Page', 'libre-bite' );
	$lbite_toggle_description     = __( 'Show a "Show only available products" filter bar on the shop and category pages.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<table class="form-table">
		<tr>
			<th>
				<?php esc_html_e( 'Availability Hint Style', 'libre-bite' ); ?>
				<?php if ( ! $lbite_premium_allowed ) : ?>
					<span class="lbite-pro-badge">Pro</span>
				<?php endif; ?>
			</th>
			<td>
				<select name="lbite_availability_hint_style" <?php echo $lbite_premium_allowed ? '' : 'disabled'; ?>>
					<option value="popup" <?php selected( $lbite_availability_hint_style, 'popup' ); ?>><?php esc_html_e( 'Popup (icon + hover/click popup)', 'libre-bite' ); ?></option>
					<option value="list" <?php selected( $lbite_availability_hint_style, 'list' ); ?>><?php esc_html_e( 'List (location list always visible)', 'libre-bite' ); ?></option>
					<option value="text" <?php selected( $lbite_availability_hint_style, 'text' ); ?>><?php esc_html_e( 'Text only (single line, no popup)', 'libre-bite' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Choose how the availability hint is displayed. If the hint looks out of place with your theme, try "Text only" for the simplest, most compatible layout.', 'libre-bite' ); ?></p>
			</td>
		</tr>
	</table>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
