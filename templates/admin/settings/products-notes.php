<?php
/**
 * Einstellungen: Positions-Notizen
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
	<?php esc_html_e( 'Let staff or customers attach a short note to a single cart item — "no onions", "extra spicy".', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="products_notes">

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

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
