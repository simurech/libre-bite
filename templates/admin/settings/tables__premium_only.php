<?php
/**
 * Tab: Tischverwaltung & Tischbestellung (Pro)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_premium_allowed        = true; // Nur erreichbar wenn Premium aktiv
$lbite_table_order_page       = get_option( 'lbite_table_order_page_id', 0 );
$lbite_table_dropdown_sort    = get_option( 'lbite_table_dropdown_sort', 'natural' );
$lbite_pages                  = get_pages();
?>
<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="tables">

	<?php
	$lbite_toggle_key             = 'enable_table_ordering';
	$lbite_toggle_label           = __( 'Table Ordering', 'libre-bite' );
	$lbite_toggle_description     = __( 'Allow guests to scan a QR code at their table and place orders directly.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = true;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';

	$lbite_toggle_key             = 'enable_service_calls';
	$lbite_toggle_label           = __( 'Waiter Call', 'libre-bite' );
	$lbite_toggle_description     = __( 'After scanning the table QR code, guests see a small bar at the bottom of every shop page with a button to call the staff – also after ordering, for three hours. The call appears on the order board (with sound) and in the table plan until someone marks it as done.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = true;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<table class="form-table">
		<tr>
			<th><label for="lbite-table-order-page-id"><?php esc_html_e( 'Table Order Page', 'libre-bite' ); ?></label></th>
			<td>
				<select id="lbite-table-order-page-id" name="lbite_table_order_page_id">
					<option value="0"><?php esc_html_e( '— Select page —', 'libre-bite' ); ?></option>
					<?php foreach ( $lbite_pages as $lbite_p ) : ?>
						<option value="<?php echo esc_attr( $lbite_p->ID ); ?>" <?php selected( $lbite_table_order_page, $lbite_p->ID ); ?>>
							<?php echo esc_html( $lbite_p->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'The WooCommerce shop page customers are redirected to after scanning the table QR code.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="lbite-table-dropdown-sort"><?php esc_html_e( 'Table Sort Order', 'libre-bite' ); ?></label></th>
			<td>
				<select id="lbite-table-dropdown-sort" name="lbite_table_dropdown_sort">
					<option value="natural" <?php selected( $lbite_table_dropdown_sort, 'natural' ); ?>><?php esc_html_e( 'Alphabetical with natural number sort (1, 2, 10, 11)', 'libre-bite' ); ?></option>
					<option value="menu_order" <?php selected( $lbite_table_dropdown_sort, 'menu_order' ); ?>><?php esc_html_e( 'Menu order (drag to reorder in table list)', 'libre-bite' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Controls how tables are sorted in the checkout dropdown.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="lbite-service-call-hint"><?php esc_html_e( 'Waiter call: text', 'libre-bite' ); ?></label></th>
			<td>
				<input id="lbite-service-call-hint" type="text" class="regular-text" name="lbite_service_call_hint" value="<?php echo esc_attr( get_option( 'lbite_service_call_hint', '' ) ); ?>" placeholder="<?php esc_attr_e( 'Need help?', 'libre-bite' ); ?>" maxlength="60">
				<p class="description"><?php esc_html_e( 'Short text next to the button. Leave empty for the standard text in the language of the website.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="lbite-service-call-button"><?php esc_html_e( 'Waiter call: button', 'libre-bite' ); ?></label></th>
			<td>
				<input id="lbite-service-call-button" type="text" class="regular-text" name="lbite_service_call_button" value="<?php echo esc_attr( get_option( 'lbite_service_call_button', '' ) ); ?>" placeholder="<?php esc_attr_e( 'Call staff', 'libre-bite' ); ?>" maxlength="40">
				<p class="description"><?php esc_html_e( 'Label of the button. Leave empty for the standard text.', 'libre-bite' ); ?></p>
			</td>
		</tr>
	</table>

	<?php
	// Ohne Zahlungsart mit Sofortzahlung können QR-Gäste nicht abschliessen.
	$lbite_instant_gateways = array();
	if ( function_exists( 'WC' ) && WC()->payment_gateways() ) {
		$lbite_deferred = (array) apply_filters( 'lbite_table_deferred_gateways', array( 'cod', 'cheque', 'bacs' ) );
		foreach ( WC()->payment_gateways()->payment_gateways() as $lbite_gw_id => $lbite_gw ) {
			if ( 'yes' === $lbite_gw->enabled && ! in_array( $lbite_gw_id, $lbite_deferred, true ) ) {
				$lbite_instant_gateways[] = $lbite_gw_id;
			}
		}
	}
	if ( lbite_feature_enabled( 'enable_table_ordering' ) && empty( $lbite_instant_gateways ) ) :
		?>
		<div class="notice notice-warning inline">
			<p><?php esc_html_e( 'No online payment method is active. Guests ordering by table QR code cannot complete their order until you activate one (for example card, TWINT or PayPal) under WooCommerce → Settings → Payments.', 'libre-bite' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="lbite-settings-card">
		<h3><?php esc_html_e( 'How table ordering works', 'libre-bite' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Each table has its own QR code. After scanning, the order is linked to the table for three hours – guests can order another round from the same phone.', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Guests ordering by QR code always pay online right away. Payment methods without immediate payment (cash on delivery, bank transfer, cheque) are hidden for table orders, so nobody can order to someone else\'s table without paying.', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Paying at the end is done by the staff: open a tab for the table in the POS, add rounds and close the table bill there with any payment method.', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'The table plan shows each table at a glance: green = free, purple = reserved, red = ordered and not yet served (with waiting time), orange = served but not yet paid. The kitchen marks orders as served on the Order Board.', 'libre-bite' ); ?></li>
		</ul>
	</div>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>

<hr style="margin: 24px 0;">
<h3><?php esc_html_e( 'Manage Tables', 'libre-bite' ); ?></h3>
<p class="description" style="margin-bottom: 12px;">
	<?php esc_html_e( 'Create tables and generate QR codes for each. Guests scan the QR code to start ordering.', 'libre-bite' ); ?>
</p>
<p>
	<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lbite_table' ) ); ?>" class="button">
		<?php esc_html_e( 'Manage Tables', 'libre-bite' ); ?>
	</a>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-floor-plan' ) ); ?>" class="button" style="margin-left: 8px;">
		<?php esc_html_e( 'Table Plan', 'libre-bite' ); ?>
	</a>
</p>
