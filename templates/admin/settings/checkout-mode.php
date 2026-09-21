<?php
/**
 * Einstellungen: Checkout-Modus & Bestelltyp
 *
 * Ausgelagert aus checkout.php (weitere Aufteilung nach v3.2.0).
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_checkout_mode      = get_option( 'lbite_checkout_mode', 'standard' );
$lbite_email_req_gateways = get_option( 'lbite_email_required_gateways', array() );
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Replace the standard WooCommerce checkout with a minimal one, and let customers pick Takeaway or Dine-in up front.', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="checkout_mode">

	<?php
	$lbite_toggle_key             = 'enable_optimized_checkout';
	$lbite_toggle_label           = __( 'Optimized Checkout', 'libre-bite' );
	$lbite_toggle_description     = __( 'Replace WooCommerce fields with a minimal checkout: name and receipt option only.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';

	$lbite_toggle_key             = 'enable_order_type_selection';
	$lbite_toggle_label           = __( 'Order Type Selection', 'libre-bite' );
	$lbite_toggle_description     = __( 'Show a Takeaway / Dine-in selector in the checkout. When Multiple Tax Rates is enabled, the selection also controls the applicable tax rate. With the Table module active, Dine-in reveals an optional table number field.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<?php if ( $lbite_premium_allowed && lbite_feature_enabled( 'enable_optimized_checkout' ) ) : ?>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Checkout Mode', 'libre-bite' ); ?></th>
			<td>
				<select name="lbite_checkout_mode">
					<option value="standard" <?php selected( $lbite_checkout_mode, 'standard' ); ?>><?php esc_html_e( 'Standard (all WooCommerce fields)', 'libre-bite' ); ?></option>
					<option value="optimized" <?php selected( $lbite_checkout_mode, 'optimized' ); ?>><?php esc_html_e( 'Optimized (name + receipt option only)', 'libre-bite' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'In optimized mode, only the name is requested and whether a receipt by email is desired.', 'libre-bite' ); ?></p>
				<div class="notice notice-warning inline" style="margin: 8px 0 0; padding: 8px 12px;">
					<p><strong><?php esc_html_e( 'Important:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'The optimized checkout only works with the classic WooCommerce shortcode. Your checkout page must contain the shortcode', 'libre-bite' ); ?> <code>[woocommerce_checkout]</code><?php esc_html_e( ', not the WooCommerce Checkout Block.', 'libre-bite' ); ?></p>
				</div>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Email Required For', 'libre-bite' ); ?></th>
			<td>
				<?php
				$lbite_all_gateways = WC()->payment_gateways->payment_gateways();
				if ( $lbite_all_gateways ) :
					foreach ( $lbite_all_gateways as $lbite_gw_id => $lbite_gw ) :
				?>
				<label style="display:block; margin-bottom:5px;">
					<input type="checkbox" name="lbite_email_required_gateways[]" value="<?php echo esc_attr( $lbite_gw_id ); ?>" <?php checked( in_array( $lbite_gw_id, $lbite_email_req_gateways, true ) ); ?>>
					<?php echo esc_html( $lbite_gw->get_title() ); ?> <code style="font-size:11px;"><?php echo esc_html( $lbite_gw_id ); ?></code>
				</label>
				<?php
					endforeach;
				else :
					echo '<p class="description">' . esc_html__( 'No payment methods found. Please configure WooCommerce payment methods first.', 'libre-bite' ) . '</p>';
				endif;
				?>
				<p class="description" style="margin-top:8px;"><?php esc_html_e( 'The email field is shown in the optimized checkout only for the selected payment methods.', 'libre-bite' ); ?></p>
			</td>
		</tr>
	</table>
	<?php endif; ?>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
