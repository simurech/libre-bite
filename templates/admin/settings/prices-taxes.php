<?php
/**
 * Einstellungen: Preise & Steuern
 *
 * Ausgelagert aus settings-tabbed.php (v3.2.0) im Zuge der Umstellung auf
 * vertikale Navigation. Speicherlogik bleibt unverändert im zentralen
 * Save-Switch in settings-tabbed.php.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Control how totals are rounded and, with Pro, which tax class applies to takeaway versus dine-in orders.', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="prices_taxes">

	<?php
	$lbite_toggle_key         = 'enable_rounding';
	$lbite_toggle_label       = __( 'Price Rounding', 'libre-bite' );
	$lbite_toggle_description = __( 'Round total to 5 cents (0.05 CHF). Prevents rounding errors when combining vouchers and tips. Recommended for Swiss businesses.', 'libre-bite' );
	$lbite_toggle_is_pro      = false;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';

	$lbite_toggle_key             = 'enable_swiss_vat';
	$lbite_toggle_label           = __( 'Multiple Tax Rates', 'libre-bite' );
	$lbite_toggle_description     = __( 'Apply a different tax class per order type: configure which tax class applies to Takeaway orders and which applies to Dine-in orders.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<?php if ( $lbite_premium_allowed && lbite_feature_enabled( 'enable_swiss_vat' ) ) :
		$lbite_tax_class_takeaway = get_option( 'lbite_tax_class_takeaway', '' );
		$lbite_tax_class_dine_in  = get_option( 'lbite_tax_class_dine_in', '' );
		$lbite_wc_tax_classes     = array_merge( array( '' => __( 'Standard', 'libre-bite' ) ), WC_Tax::get_tax_classes() );
	?>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Takeaway Tax Class', 'libre-bite' ); ?></th>
			<td>
				<select name="lbite_tax_class_takeaway">
					<?php foreach ( $lbite_wc_tax_classes as $lbite_slug => $lbite_name ) :
						$lbite_value = ( '' === $lbite_slug ) ? '' : sanitize_title( $lbite_name );
					?>
						<option value="<?php echo esc_attr( $lbite_value ); ?>" <?php selected( $lbite_tax_class_takeaway, $lbite_value ); ?>>
							<?php echo esc_html( '' === $lbite_slug ? __( 'Standard', 'libre-bite' ) : $lbite_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Tax class applied to takeaway and pickup orders.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Dine-in Tax Class', 'libre-bite' ); ?></th>
			<td>
				<select name="lbite_tax_class_dine_in">
					<?php foreach ( $lbite_wc_tax_classes as $lbite_slug => $lbite_name ) :
						$lbite_value = ( '' === $lbite_slug ) ? '' : sanitize_title( $lbite_name );
					?>
						<option value="<?php echo esc_attr( $lbite_value ); ?>" <?php selected( $lbite_tax_class_dine_in, $lbite_value ); ?>>
							<?php echo esc_html( '' === $lbite_slug ? __( 'Standard', 'libre-bite' ) : $lbite_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Tax class applied to dine-in and table orders.', 'libre-bite' ); ?></p>
			</td>
		</tr>
	</table>
	<?php endif; ?>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>

<hr style="margin: 24px 0;">
<p>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=tax' ) ); ?>" class="button">
		<?php esc_html_e( 'WooCommerce Tax Settings →', 'libre-bite' ); ?>
	</a>
</p>
