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
	<?php esc_html_e( 'Assign one or more locations to each manager or shop manager — they can only see and manage orders for checked locations, no checks means unrestricted. Administrators and staff are listed for a full overview but managed differently (see below).', 'libre-bite' ); ?>
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

	$lbite_managers = get_users(
		array(
			'role__in' => array( 'administrator', 'lbite_manager', 'shop_manager', 'lbite_staff' ),
			'orderby'  => 'display_name',
			'order'    => 'ASC',
		)
	);
	?>
	<?php if ( empty( $lbite_managers ) ) : ?>
		<p class="description">
			<?php esc_html_e( 'No users with a Libre Bite role found.', 'libre-bite' ); ?>
			&nbsp;<a href="<?php echo esc_url( admin_url( 'user-new.php' ) ); ?>"><?php esc_html_e( 'Add New User', 'libre-bite' ); ?></a>
		</p>
	<?php elseif ( empty( $lbite_locations ) ) : ?>
		<p class="description"><?php esc_html_e( 'No locations found.', 'libre-bite' ); ?></p>
	<?php else : ?>
		<form method="post">
			<?php wp_nonce_field( 'lbite_save_manager_assignments' ); ?>
			<input type="hidden" name="lbite_save_manager_assignments" value="1">
			<div class="lbite-table-scroll">
				<table class="lbite-table widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'User', 'libre-bite' ); ?></th>
							<?php foreach ( $lbite_locations as $lbite_loc ) : ?>
								<th style="text-align:center;"><?php echo esc_html( $lbite_loc->post_title ); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $lbite_managers as $lbite_mgr ) : ?>
							<?php
							$lbite_row_is_admin = in_array( 'administrator', (array) $lbite_mgr->roles, true );
							$lbite_row_is_staff = ! $lbite_row_is_admin && in_array( 'lbite_staff', (array) $lbite_mgr->roles, true );
							$lbite_assigned     = array_map( 'strval', (array) get_user_meta( $lbite_mgr->ID, 'lbite_assigned_locations', true ) );
							$lbite_fixed_loc_id = (int) get_user_meta( $lbite_mgr->ID, 'lbite_assigned_location', true );
							?>
							<tr>
								<td>
									<?php echo esc_html( $lbite_mgr->display_name ); ?>
									<br>
									<span style="font-weight: normal; color: #646970; font-size: 12px;"><?php echo esc_html( $lbite_mgr->user_email ); ?></span>
									<?php if ( $lbite_row_is_admin ) : ?>
										<br><span class="description"><?php esc_html_e( 'Unrestricted access', 'libre-bite' ); ?></span>
									<?php endif; ?>
								</td>
								<?php if ( $lbite_row_is_staff ) : ?>
									<td colspan="<?php echo (int) count( $lbite_locations ); ?>">
										<?php if ( $lbite_fixed_loc_id ) : ?>
											<?php
											printf(
												/* translators: %s: location name */
												esc_html__( 'Fixed to %s (set on the user profile page)', 'libre-bite' ),
												esc_html( get_the_title( $lbite_fixed_loc_id ) )
											);
											?>
										<?php else : ?>
											<?php esc_html_e( 'No fixed location — can work at every location (set on the user profile page)', 'libre-bite' ); ?>
										<?php endif; ?>
										<a href="<?php echo esc_url( get_edit_user_link( $lbite_mgr->ID ) ); ?>"><?php esc_html_e( 'Edit', 'libre-bite' ); ?></a>
									</td>
								<?php else : ?>
									<?php foreach ( $lbite_locations as $lbite_loc ) : ?>
										<td style="text-align:center;">
											<input type="checkbox"
												name="lbite_manager_locations[<?php echo esc_attr( $lbite_mgr->ID ); ?>][]"
												value="<?php echo esc_attr( $lbite_loc->ID ); ?>"
												<?php checked( $lbite_row_is_admin || in_array( (string) $lbite_loc->ID, $lbite_assigned, true ) ); ?>
												<?php disabled( $lbite_row_is_admin ); ?>>
										</td>
									<?php endforeach; ?>
								<?php endif; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<p class="description">
				<?php esc_html_e( 'Staff location is a single fixed location for POS use and is edited on the user profile page, not here — different from the multi-location access below.', 'libre-bite' ); ?>
			</p>
			<?php submit_button( __( 'Save Assignments', 'libre-bite' ), 'primary', 'lbite_save_manager_assignments_btn' ); ?>
		</form>
	<?php endif; ?>
<?php endif; ?>
