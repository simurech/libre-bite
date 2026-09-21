<?php
/**
 * Einstellungen: Bestellübersicht (Kanban-Grundeinstellungen)
 *
 * Ausgelagert aus orders.php (weitere Aufteilung nach v3.2.0).
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_refresh      = get_option( 'lbite_dashboard_refresh_interval', 30 );
$lbite_kds_timer    = '0' !== (string) get_option( 'lbite_kds_timer_enabled', 1 );
$lbite_kds_warn     = (int) get_option( 'lbite_kds_warn_minutes', 0 );
$lbite_kds_late     = (int) get_option( 'lbite_kds_late_minutes', 0 );
$lbite_sound_repeat = (int) get_option( 'lbite_sound_repeat_interval', 0 );
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Turn the kitchen order overview on and configure how it refreshes and flags waiting orders.', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="orders_board">

	<?php
	$lbite_toggle_key         = 'enable_kanban_board';
	$lbite_toggle_label       = __( 'Order Overview (Kanban)', 'libre-bite' );
	$lbite_toggle_description = __( 'Display incoming orders as a kanban board for quick status management.', 'libre-bite' );
	$lbite_toggle_is_pro      = false;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Order Overview Refresh Interval', 'libre-bite' ); ?></th>
			<td>
				<input type="number" min="10" name="lbite_dashboard_refresh_interval" value="<?php echo esc_attr( $lbite_refresh ); ?>" class="small-text"> <?php esc_html_e( 'Seconds', 'libre-bite' ); ?>
				<p class="description"><?php esc_html_e( 'How often the order overview checks for new orders.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Waiting Time Timer', 'libre-bite' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="lbite_kds_timer_enabled" value="1" <?php checked( $lbite_kds_timer ); ?>>
					<?php esc_html_e( 'Show how long each order has been waiting', 'libre-bite' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Each order card shows the minutes since the order came in, turning amber and then red as it ages. Future pre-orders are excluded.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Amber After', 'libre-bite' ); ?></th>
			<td>
				<input type="number" min="0" name="lbite_kds_warn_minutes" value="<?php echo esc_attr( $lbite_kds_warn ); ?>" class="small-text"> <?php esc_html_e( 'Minutes', 'libre-bite' ); ?>
				<p class="description"><?php esc_html_e( 'Leave at 0 to use the preparation time configured for each location.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Red After', 'libre-bite' ); ?></th>
			<td>
				<input type="number" min="0" name="lbite_kds_late_minutes" value="<?php echo esc_attr( $lbite_kds_late ); ?>" class="small-text"> <?php esc_html_e( 'Minutes', 'libre-bite' ); ?>
				<p class="description"><?php esc_html_e( 'Leave at 0 to use one and a half times the amber threshold.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Repeat Alert Sound', 'libre-bite' ); ?></th>
			<td>
				<input type="number" min="0" name="lbite_sound_repeat_interval" value="<?php echo esc_attr( $lbite_sound_repeat ); ?>" class="small-text"> <?php esc_html_e( 'Seconds', 'libre-bite' ); ?>
				<p class="description"><?php esc_html_e( 'Repeat the alert sound while orders are still waiting in the first column. Moving an order onwards acknowledges it and stops the sound. Set to 0 to play the sound only once per new order.', 'libre-bite' ); ?></p>
			</td>
		</tr>
	</table>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
