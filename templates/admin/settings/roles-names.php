<?php
/**
 * Einstellungen: Rollennamen & deaktivierte Rollen
 *
 * Ausgelagert aus admin-settings.php (weitere Aufteilung nach v3.2.0).
 * Speichert über LBite_Admin_Settings::save_role_names() (eigene Nonce).
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'libre-bite' ) );
}

$lbite_own_roles         = array( 'lbite_staff', 'lbite_manager' );
$lbite_custom_role_names = get_option( 'lbite_custom_role_names', array() );
$lbite_disabled_roles    = get_option( 'lbite_disabled_roles', array() );
$lbite_all_wp_roles      = wp_roles()->roles;

settings_errors( 'lbite_role_names' );
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Customize the displayed names of Libre Bite roles or disable unused ones.', 'libre-bite' ); ?>
</p>

<form method="post" action="">
	<?php wp_nonce_field( 'lbite_save_role_names', 'lbite_role_names_nonce' ); ?>

	<h2><?php esc_html_e( 'Manage User Roles', 'libre-bite' ); ?></h2>

	<table class="form-table">
		<thead>
			<tr>
				<th style="padding-left: 0; font-weight: 600;"><?php esc_html_e( 'Role', 'libre-bite' ); ?></th>
				<th style="font-weight: 600;"><?php esc_html_e( 'Displayed Name', 'libre-bite' ); ?></th>
				<th style="font-weight: 600;"><?php esc_html_e( 'Disable Role', 'libre-bite' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $lbite_own_roles as $lbite_role_key ) :
			if ( ! isset( $lbite_all_wp_roles[ $lbite_role_key ] ) ) {
				continue;
			}
			$lbite_role_name   = $lbite_all_wp_roles[ $lbite_role_key ]['name'];
			$lbite_custom_name = isset( $lbite_custom_role_names[ $lbite_role_key ] ) ? $lbite_custom_role_names[ $lbite_role_key ] : '';
			$lbite_is_disabled = in_array( $lbite_role_key, $lbite_disabled_roles, true );
		?>
		<tr>
			<th scope="row">
				<label for="lbite_role_name_<?php echo esc_attr( $lbite_role_key ); ?>">
					<?php echo esc_html( $lbite_role_name ); ?>
					<span style="color: #646970; font-weight: normal; font-size: 12px;">(<?php echo esc_html( $lbite_role_key ); ?>)</span>
				</label>
			</th>
			<td>
				<input
					type="text"
					id="lbite_role_name_<?php echo esc_attr( $lbite_role_key ); ?>"
					name="lbite_custom_role_names[<?php echo esc_attr( $lbite_role_key ); ?>]"
					value="<?php echo esc_attr( $lbite_custom_name ); ?>"
					class="regular-text"
					placeholder="<?php echo esc_attr( $lbite_role_name ); ?>"
					<?php disabled( $lbite_is_disabled ); ?>
				>
			</td>
			<td>
				<label>
					<input
						type="checkbox"
						name="lbite_disabled_roles[]"
						value="<?php echo esc_attr( $lbite_role_key ); ?>"
						<?php checked( $lbite_is_disabled ); ?>
						class="lbite-disable-role-checkbox"
						data-role="<?php echo esc_attr( $lbite_role_key ); ?>"
					>
					<?php esc_html_e( 'Disable', 'libre-bite' ); ?>
				</label>
			</td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<?php submit_button( __( 'Save Settings', 'libre-bite' ), 'primary' ); ?>
</form>
