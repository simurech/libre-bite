<?php
/**
 * Einstellungen: Manager-Zuweisungen (Pro)
 *
 * Ausgelagert aus admin-settings.php (weitere Aufteilung nach v3.2.0).
 * War bereits vollständig eigenständig (eigene Nonce/eigener Save-Handler
 * LBite_Admin_Settings::save_manager_assignments__premium_only()) - reine
 * Verschiebung, keine Save-Logik-Änderung.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'libre-bite' ) );
}

$lbite_mgr_premium = function_exists( 'lbite_freemius' ) && lbite_freemius()->can_use_premium_code__premium_only();
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Assign one or more locations to each manager. Managers can only see and manage orders for their assigned locations.', 'libre-bite' ); ?>
</p>

<?php if ( ! $lbite_mgr_premium ) :
	$lbite_locked_title       = __( 'Manager Assignments', 'libre-bite' );
	$lbite_locked_description = __( 'Assign managers to specific locations. Managers can view and manage orders only for their assigned locations.', 'libre-bite' );
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_pro-locked.php';
else :
	// Manager-Rolle erstellen, falls noch nicht vorhanden
	LBite_Roles::create_manager_role__premium_only();

	$lbite_locations = get_posts( array(
		'post_type'      => 'lbite_location',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );

	$lbite_managers = get_users( array( 'role' => 'lbite_manager' ) );
	?>
	<p class="description" style="margin-bottom: 16px;">
		<?php esc_html_e( 'For Staff users, location assignment is done via the individual user profile page.', 'libre-bite' ); ?>
		<a href="<?php echo esc_url( admin_url( 'users.php?role=lbite_staff' ) ); ?>"><?php esc_html_e( 'Manage Staff users', 'libre-bite' ); ?></a>
	</p>
	<?php if ( empty( $lbite_managers ) ) : ?>
		<p class="description">
			<?php esc_html_e( 'No manager users found. Create a user and assign the "Libre Bite Manager" role to get started.', 'libre-bite' ); ?>
			&nbsp;<a href="<?php echo esc_url( admin_url( 'user-new.php' ) ); ?>"><?php esc_html_e( 'Add New User', 'libre-bite' ); ?></a>
		</p>
	<?php else : ?>
		<form method="post">
			<?php wp_nonce_field( 'lbite_save_manager_assignments' ); ?>
			<input type="hidden" name="lbite_save_manager_assignments" value="1">
			<table class="form-table">
				<?php foreach ( $lbite_managers as $lbite_mgr ) : ?>
					<?php $lbite_assigned = (array) get_user_meta( $lbite_mgr->ID, 'lbite_assigned_locations', true ); ?>
					<tr>
						<th>
							<?php echo esc_html( $lbite_mgr->display_name ); ?>
							<br>
							<span style="font-weight: normal; color: #646970; font-size: 12px;"><?php echo esc_html( $lbite_mgr->user_email ); ?></span>
						</th>
						<td>
							<?php if ( empty( $lbite_locations ) ) : ?>
								<p class="description"><?php esc_html_e( 'No locations found.', 'libre-bite' ); ?></p>
							<?php else : ?>
								<select name="lbite_manager_locations[<?php echo esc_attr( $lbite_mgr->ID ); ?>][]" multiple style="min-width: 280px; min-height: 100px;">
									<?php foreach ( $lbite_locations as $lbite_loc ) : ?>
										<option value="<?php echo esc_attr( $lbite_loc->ID ); ?>" <?php selected( in_array( (string) $lbite_loc->ID, $lbite_assigned, true ) ); ?>>
											<?php echo esc_html( $lbite_loc->post_title ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Hold Ctrl/Cmd to select multiple locations.', 'libre-bite' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button( __( 'Save Assignments', 'libre-bite' ), 'primary', 'lbite_save_manager_assignments_btn' ); ?>
		</form>
	<?php endif; ?>
<?php endif; ?>
