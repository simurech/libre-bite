<?php
/**
 * Tab: Tischreservierungen (Pro)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_res_refresh = get_option( 'lbite_reservation_refresh_interval', 60 );
?>
<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="reservations">

	<?php
	$lbite_toggle_key             = 'enable_reservations';
	$lbite_toggle_label           = __( 'Table Reservations', 'libre-bite' );
	$lbite_toggle_description     = __( 'Let customers reserve tables online via a frontend form.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = true;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Reservations Overview Refresh Interval', 'libre-bite' ); ?></th>
			<td>
				<input type="number" min="10" name="lbite_reservation_refresh_interval" value="<?php echo esc_attr( $lbite_res_refresh ); ?>" class="small-text">
				<?php esc_html_e( 'Seconds', 'libre-bite' ); ?>
				<p class="description"><?php esc_html_e( 'How often the reservations overview is updated. Default: 60 seconds.', 'libre-bite' ); ?></p>
			</td>
		</tr>
	</table>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>

<hr style="margin: 24px 0;">
<h3><?php esc_html_e( 'Reservation Form', 'libre-bite' ); ?></h3>
<p class="description" style="margin-bottom: 8px;">
	<?php esc_html_e( 'Add the reservation form to any page using the shortcode:', 'libre-bite' ); ?>
</p>
<code>[lbite_reservation_form]</code>

<hr style="margin: 24px 0;">
<h3><?php esc_html_e( 'Manage Reservations', 'libre-bite' ); ?></h3>
<p>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-reservation-board' ) ); ?>" class="button">
		<?php esc_html_e( 'Reservation Overview', 'libre-bite' ); ?>
	</a>
</p>
