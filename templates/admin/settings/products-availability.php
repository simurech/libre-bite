<?php
/**
 * Einstellungen: Verfügbarkeits-Hinweis & Filter
 *
 * Ausgelagert aus products.php (weitere Aufteilung nach v3.2.0).
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_premium_allowed         = function_exists( 'lbite_freemius' ) && lbite_freemius()->can_use_premium_code__premium_only();
$lbite_availability_hint_style = get_option( 'lbite_availability_hint_style', 'popup' );
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Controls the "Available at X of Y locations" hint on category and product pages, and the "Show only available products" filter on the shop page. Both appear for products that are disabled at at least one location.', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="products_availability">

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

	<div id="lbite-hint-style-preview" class="lbite-preview-box" aria-hidden="true">
		<p class="lbite-preview-box__label"><?php esc_html_e( 'Preview: hint shown on category and product pages', 'libre-bite' ); ?></p>
		<div class="lbite-hint-style-preview__example" data-style="popup">
			<span class="lbite-hint-style-preview__icon" title="<?php esc_attr_e( 'Available at 2 of 3 locations', 'libre-bite' ); ?>">ⓘ</span>
			<span class="lbite-hint-style-preview__caption"><?php esc_html_e( 'Icon — hover or tap opens a small popup', 'libre-bite' ); ?></span>
		</div>
		<div class="lbite-hint-style-preview__example" data-style="list" hidden>
			<span class="lbite-hint-style-preview__list"><?php esc_html_e( 'Available at: Downtown, Airport', 'libre-bite' ); ?></span>
		</div>
		<div class="lbite-hint-style-preview__example" data-style="text" hidden>
			<span class="lbite-hint-style-preview__text"><?php esc_html_e( 'Available at 2 of 3 locations', 'libre-bite' ); ?></span>
		</div>
	</div>

	<table class="form-table">
		<tr>
			<th>
				<?php esc_html_e( 'Availability Hint Style', 'libre-bite' ); ?>
				<?php if ( ! $lbite_premium_allowed ) : ?>
					<span class="lbite-pro-badge">Pro</span>
				<?php endif; ?>
			</th>
			<td>
				<select id="lbite_availability_hint_style" name="lbite_availability_hint_style" <?php echo $lbite_premium_allowed ? '' : 'disabled'; ?>>
					<option value="popup" <?php selected( $lbite_availability_hint_style, 'popup' ); ?>><?php esc_html_e( 'Popup (icon + hover/click popup)', 'libre-bite' ); ?></option>
					<option value="list" <?php selected( $lbite_availability_hint_style, 'list' ); ?>><?php esc_html_e( 'List (location list always visible)', 'libre-bite' ); ?></option>
					<option value="text" <?php selected( $lbite_availability_hint_style, 'text' ); ?>><?php esc_html_e( 'Text only (single line, no popup)', 'libre-bite' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Choose how the availability hint is displayed. If the hint looks out of place with your theme, try "Text only" for the simplest, most compatible layout.', 'libre-bite' ); ?></p>
			</td>
		</tr>
	</table>
	<script>
	(function() {
		var select = document.getElementById('lbite_availability_hint_style');
		if ( ! select ) { return; }
		var examples = document.querySelectorAll('#lbite-hint-style-preview .lbite-hint-style-preview__example');
		function update() {
			examples.forEach(function(ex) { ex.hidden = ex.dataset.style !== select.value; });
		}
		select.addEventListener('change', update);
		update();
	})();
	</script>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
