<?php
/**
 * Einstellungen: Zugriff für Standard-Rollen
 *
 * Ausgelagert aus admin-settings.php (weitere Aufteilung nach v3.2.0).
 * Speichert über LBite_Admin_Settings::save_role_access() (eigene Nonce,
 * unabhängig von den Geschwister-Unterseiten "Manage User Roles" und
 * "Menu Visibility by Role").
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'libre-bite' ) );
}

$lbite_standard_roles         = LBite_Admin_Settings::get_standard_roles();
$lbite_allowed_standard_roles = get_option( 'lbite_allowed_standard_roles', array() );

settings_errors( 'lbite_role_access' );
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Overview of access levels. Standard WordPress roles can be granted access to the Order Overview and POS, without needing a Libre Bite-specific role.', 'libre-bite' ); ?>
</p>

<form method="post" action="">
	<?php wp_nonce_field( 'lbite_save_role_access', 'lbite_role_access_nonce' ); ?>

	<h2><?php esc_html_e( 'Access for User Roles', 'libre-bite' ); ?></h2>

	<table class="form-table">
		<tbody>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Shop Manager', 'libre-bite' ); ?>
				<span style="color: #646970; font-weight: normal; font-size: 12px;">(shop_manager)</span>
			</th>
			<td>
				<span style="color: #00a32a; font-weight: 600;">&#x2713; <?php esc_html_e( 'Full access (same as Administrator)', 'libre-bite' ); ?></span>
			</td>
		</tr>
		<?php if ( ! empty( $lbite_standard_roles ) ) :
			foreach ( $lbite_standard_roles as $lbite_std_role_key => $lbite_std_role_name ) :
				if ( 'shop_manager' === $lbite_std_role_key ) {
					continue;
				}
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $lbite_std_role_name ); ?> <span style="color: #646970; font-weight: normal; font-size: 12px;">(<?php echo esc_html( $lbite_std_role_key ); ?>)</span></th>
			<td>
				<label>
					<input
						type="checkbox"
						name="lbite_allowed_standard_roles[]"
						value="<?php echo esc_attr( $lbite_std_role_key ); ?>"
						<?php checked( in_array( $lbite_std_role_key, $lbite_allowed_standard_roles, true ) ); ?>
					>
					<?php esc_html_e( 'Grant access (Order Overview + POS)', 'libre-bite' ); ?>
				</label>
			</td>
		</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>

	<?php submit_button( __( 'Save Settings', 'libre-bite' ), 'primary' ); ?>
</form>
