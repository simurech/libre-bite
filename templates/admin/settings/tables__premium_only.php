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
	?>

	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Table Order Page', 'libre-bite' ); ?></th>
			<td>
				<select name="lbite_table_order_page_id">
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
	</table>

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
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-table-plan' ) ); ?>" class="button" style="margin-left: 8px;">
		<?php esc_html_e( 'Table Plan', 'libre-bite' ); ?>
	</a>
</p>
