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

$lbite_premium_allowed = lbite_has_pro_license();
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

	<?php
	// Eigene Ernährungsformen (Pro). Die Klasse liegt im Pro-Modul und ist nur
	// geladen, wenn eines der Nährwert-Features aktiv ist.
	$lbite_nutri_file = LBITE_PLUGIN_DIR . 'includes/modules/nutritional-info/class-nutritional-info.php';
	if ( $lbite_premium_allowed && ( class_exists( 'LBite_Nutritional_Info' ) || file_exists( $lbite_nutri_file ) ) ) :
		if ( ! class_exists( 'LBite_Nutritional_Info' ) ) {
			require_once $lbite_nutri_file;
		}
		$lbite_diet_list     = LBite_Nutritional_Info::get_dietary_list();
		$lbite_diet_defaults = LBite_Nutritional_Info::get_default_dietary_list();
		$lbite_diet_custom   = (array) get_option( 'lbite_dietary_labels', array() );
		$lbite_diet_row      = 0;
		?>
		<input type="hidden" name="lbite_dietary_rows_present" value="1">
		<h2><?php esc_html_e( 'Dietary labels', 'libre-bite' ); ?></h2>
		<p class="description" style="max-width: 640px;"><?php esc_html_e( 'Rename, remove or add labels. Existing labels keep their internal key so products stay assigned. If you remove all labels, the default list is used again.', 'libre-bite' ); ?></p>
		<table class="widefat striped lbite-dietary-table" style="max-width: 640px; margin: 12px 0 24px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Label', 'libre-bite' ); ?></th>
					<th style="width: 160px;"><?php esc_html_e( 'Key', 'libre-bite' ); ?></th>
					<th style="width: 90px;"><?php esc_html_e( 'Remove', 'libre-bite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $lbite_diet_list as $lbite_diet_key => $lbite_diet_label ) : ?>
					<tr>
						<td>
							<input type="text" class="regular-text" maxlength="40" name="lbite_dietary_rows[<?php echo esc_attr( $lbite_diet_row ); ?>][label]"
								value="<?php echo esc_attr( isset( $lbite_diet_custom[ $lbite_diet_key ] ) ? $lbite_diet_custom[ $lbite_diet_key ] : ( isset( $lbite_diet_defaults[ $lbite_diet_key ] ) ? '' : $lbite_diet_label ) ); ?>"
								placeholder="<?php echo esc_attr( $lbite_diet_label ); ?>">
							<input type="hidden" name="lbite_dietary_rows[<?php echo esc_attr( $lbite_diet_row ); ?>][key]" value="<?php echo esc_attr( $lbite_diet_key ); ?>">
						</td>
						<td><code><?php echo esc_html( $lbite_diet_key ); ?></code></td>
						<td><label><input type="checkbox" name="lbite_dietary_rows[<?php echo esc_attr( $lbite_diet_row ); ?>][remove]" value="1"> <span class="screen-reader-text"><?php esc_html_e( 'Remove', 'libre-bite' ); ?></span></label></td>
					</tr>
					<?php $lbite_diet_row++; ?>
				<?php endforeach; ?>
				<?php for ( $lbite_i = 0; $lbite_i < 3; $lbite_i++, $lbite_diet_row++ ) : ?>
					<tr>
						<td><input type="text" class="regular-text" maxlength="40" name="lbite_dietary_rows[<?php echo esc_attr( $lbite_diet_row ); ?>][label]" value="" placeholder="<?php esc_attr_e( 'New label, e.g. Halal', 'libre-bite' ); ?>"></td>
						<td><em><?php esc_html_e( 'automatic', 'libre-bite' ); ?></em></td>
						<td></td>
					</tr>
				<?php endfor; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
