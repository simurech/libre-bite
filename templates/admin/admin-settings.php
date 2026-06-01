<?php
/**
 * Template: Admin-Einstellungen
 *
 * Erweiterte Einstellungen nur für Administratoren
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Nur Administratoren haben Zugriff
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'libre-bite' ) );
}

// Aktuelle Einstellungen abrufen
$lbite_custom_plugin_name = get_option( 'lbite_custom_plugin_name', '' );
$lbite_menu_visibility    = get_option( 'lbite_menu_visibility', array() );

// Alle Menüeinträge und Rollen abrufen
$lbite_all_menu_items       = LBite_Admin_Settings::get_all_menu_items();
$lbite_all_roles            = LBite_Admin_Settings::get_all_roles();
$lbite_standard_roles = LBite_Admin_Settings::get_standard_roles();

// Debug: Menüs anzeigen (nur für Admins)
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lese-Parameter für Debug-Ausgabe.
if ( isset( $_GET['debug_menus'] ) && current_user_can( 'manage_options' ) ) {
	echo '<div class="notice notice-info"><pre style="max-height: 300px; overflow: auto;">';
	echo 'Gefundene Menüeinträge (' . count( $lbite_all_menu_items ) . '):<br><br>';
	foreach ( $lbite_all_menu_items as $lbite_slug => $lbite_data ) {
		echo 'Slug: ' . esc_html( $lbite_slug ) . ' | Titel: ' . esc_html( $lbite_data['title'] ) . ' | Parent: ' . esc_html( $lbite_data['parent'] ) . '<br>';
	}
	echo '</pre></div>';
}

// Plugin-Name für Titel
$lbite_plugin_display_name = ! empty( $lbite_custom_plugin_name ) ? $lbite_custom_plugin_name : 'Libre Bite';
?>

<?php if ( empty( $lbite_is_tab ) ) : ?>
<div class="wrap">
	<h1><?php echo esc_html( $lbite_plugin_display_name ); ?> - <?php esc_html_e( 'Admin Settings', 'libre-bite' ); ?></h1>
<?php endif; ?>

	<p class="description">
		<?php esc_html_e( 'These settings are only visible to administrators and allow advanced customizations.', 'libre-bite' ); ?>
	</p>

	<?php settings_errors( 'lbite_admin_settings' ); ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'lbite_save_admin_settings', 'lbite_admin_settings_nonce' ); ?>

		<!-- Zugriff für andere Rollen -->
		<h2><?php esc_html_e( 'Access for User Roles', 'libre-bite' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Overview of access levels. Standard roles can be granted Order Overview and POS access.', 'libre-bite' ); ?>
		</p>
		<table class="form-table">
			<tbody>
			<!-- shop_manager: always has full access (J) -->
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
				$lbite_allowed_standard_roles = get_option( 'lbite_allowed_standard_roles', array() );
				foreach ( $lbite_standard_roles as $lbite_std_role_key => $lbite_std_role_name ) :
					if ( 'shop_manager' === $lbite_std_role_key ) continue; // already shown above
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

		<!-- Rollennamen anpassen (nur lbite_staff + lbite_manager) -->
		<h2><?php esc_html_e( 'Manage User Roles', 'libre-bite' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Customize the displayed names of Libre Bite roles or disable unused ones.', 'libre-bite' ); ?>
		</p>
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
			$lbite_own_roles         = array( 'lbite_staff', 'lbite_manager' );
			$lbite_custom_role_names = get_option( 'lbite_custom_role_names', array() );
			$lbite_disabled_roles    = get_option( 'lbite_disabled_roles', array() );
			$lbite_all_wp_roles      = wp_roles()->roles;

			foreach ( $lbite_own_roles as $lbite_role_key ) :
				if ( ! isset( $lbite_all_wp_roles[ $lbite_role_key ] ) ) continue;
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

		<!-- Menü-Sichtbarkeit nach Nutzerrollen -->
		<h2><?php esc_html_e( 'Menu Visibility by User Role', 'libre-bite' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Choose which menu items should be hidden for each user role. Administrators always have full access.', 'libre-bite' ); ?>
		</p>

		<?php if ( empty( $lbite_all_roles ) ) : ?>
			<p><?php esc_html_e( 'No additional user roles found (other than Administrator).', 'libre-bite' ); ?></p>
		<?php else : ?>
			<div class="lbite-menu-visibility-settings">
				<?php
				$lbite_custom_role_names_display = get_option( 'lbite_custom_role_names', array() );
				foreach ( $lbite_all_roles as $lbite_role_key => $lbite_role_name ) :
					if ( 'shop_manager' === $lbite_role_key ) continue;
					// Angepassten Rollennamen verwenden, falls vorhanden
					$lbite_display_name = isset( $lbite_custom_role_names_display[ $lbite_role_key ] ) && ! empty( $lbite_custom_role_names_display[ $lbite_role_key ] )
						? $lbite_custom_role_names_display[ $lbite_role_key ]
						: $lbite_role_name;
					?>
					<div class="lbite-role-section" style="margin-bottom: 30px; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;" data-role="<?php echo esc_attr( $lbite_role_key ); ?>">
						<h3 style="margin-top: 0;">
							<?php echo esc_html( $lbite_display_name ); ?>
							<span style="font-weight: normal; color: #666; font-size: 13px;">
								(<?php echo esc_html( $lbite_role_key ); ?>)
							</span>
						</h3>

						<?php if ( 'lbite_staff' === $lbite_role_key ) : ?>
							<p class="description">
								<?php esc_html_e( 'Staff users always have access to: Order Overview, POS System, Help & Support. Menu visibility cannot be customized for this role.', 'libre-bite' ); ?>
							</p>
							<ul style="margin: 10px 0 0 10px; color: #2271b1;">
								<li>&#x2713; <?php esc_html_e( 'Order Overview', 'libre-bite' ); ?></li>
								<li>&#x2713; <?php esc_html_e( 'POS System', 'libre-bite' ); ?></li>
								<li>&#x2713; <?php esc_html_e( 'Help & Support', 'libre-bite' ); ?></li>
							</ul>
							<p class="description" style="margin-top: 10px; padding: 8px 12px; background: #f0f6fc; border-left: 3px solid #2271b1; border-radius: 0 3px 3px 0;">
								<strong><?php esc_html_e( 'Tip:', 'libre-bite' ); ?></strong>
								<?php
								printf(
									/* translators: %s: link to users list */
									esc_html__( 'Assign a location to each Staff user on their %s so they only see orders and options for their location.', 'libre-bite' ),
									'<a href="' . esc_url( admin_url( 'users.php' ) ) . '">' . esc_html__( 'user profile', 'libre-bite' ) . '</a>'
								);
								?>
							</p>
						<?php else : ?>
							<p class="description" style="margin-bottom: 15px;">
								<?php
								printf(
									/* translators: %s: Role name */
									esc_html__( 'Choose the menu items that should be hidden for users with the role "%s".', 'libre-bite' ),
									esc_html( $lbite_display_name )
								);
								?>
							</p>

							<p style="margin-bottom: 15px;">
								<label style="font-weight: 600; cursor: pointer;">
									<input
										type="checkbox"
										class="lbite-toggle-all-menus"
										data-role="<?php echo esc_attr( $lbite_role_key ); ?>"
									>
									<?php esc_html_e( 'Select All / Deselect All', 'libre-bite' ); ?>
								</label>
							</p>

							<?php
							$lbite_main_menus = array();
							$lbite_submenus   = array();
							foreach ( $lbite_all_menu_items as $lbite_menu_slug => $lbite_menu_data ) {
								if ( empty( $lbite_menu_data['parent'] ) ) {
									$lbite_main_menus[ $lbite_menu_slug ] = $lbite_menu_data;
								} else {
									if ( ! isset( $lbite_submenus[ $lbite_menu_data['parent'] ] ) ) {
										$lbite_submenus[ $lbite_menu_data['parent'] ] = array();
									}
									$lbite_submenus[ $lbite_menu_data['parent'] ][ $lbite_menu_slug ] = $lbite_menu_data;
								}
							}
							?>

							<div class="lbite-menu-items">
								<?php foreach ( $lbite_main_menus as $lbite_parent_slug => $lbite_parent_data ) : ?>
									<?php
									$lbite_has_submenus     = isset( $lbite_submenus[ $lbite_parent_slug ] ) && ! empty( $lbite_submenus[ $lbite_parent_slug ] );
									$lbite_is_parent_checked = isset( $lbite_menu_visibility[ $lbite_role_key ] ) && in_array( $lbite_parent_slug, $lbite_menu_visibility[ $lbite_role_key ], true );
									?>

									<div class="lbite-menu-group">
										<div class="lbite-main-menu-item">
											<label>
												<input
													type="checkbox"
													name="lbite_menu_visibility[<?php echo esc_attr( $lbite_role_key ); ?>][]"
													value="<?php echo esc_attr( $lbite_parent_slug ); ?>"
													<?php checked( $lbite_is_parent_checked ); ?>
												/>
												<strong><?php echo esc_html( $lbite_parent_data['title'] ); ?></strong>
												<span class="lbite-menu-badge"><?php esc_html_e( 'Main Menu', 'libre-bite' ); ?></span>
											</label>
										</div>

										<?php if ( $lbite_has_submenus ) : ?>
											<div class="lbite-submenu-items">
												<?php foreach ( $lbite_submenus[ $lbite_parent_slug ] as $lbite_submenu_slug => $lbite_submenu_data ) : ?>
													<?php
													$lbite_is_submenu_checked = isset( $lbite_menu_visibility[ $lbite_role_key ] ) && in_array( $lbite_submenu_slug, $lbite_menu_visibility[ $lbite_role_key ], true );
													?>
													<label>
														<input
															type="checkbox"
															name="lbite_menu_visibility[<?php echo esc_attr( $lbite_role_key ); ?>][]"
															value="<?php echo esc_attr( $lbite_submenu_slug ); ?>"
															<?php checked( $lbite_is_submenu_checked ); ?>
														/>
														<?php echo esc_html( $lbite_submenu_data['title'] ); ?>
													</label>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php submit_button( __( 'Save Settings', 'libre-bite' ), 'primary' ); ?>
	</form>

<?php
// Manager-Zuweisungen (Pro)
$lbite_mgr_premium = function_exists( 'lbite_freemius' ) && lbite_freemius()->can_use_premium_code__premium_only();

echo '<hr style="margin: 32px 0;">';
echo '<h2>' . esc_html__( 'Manager Assignments', 'libre-bite' ) . ' <span class="lbite-pro-badge">Pro</span></h2>';

if ( ! $lbite_mgr_premium ) :
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
	<p class="description" style="margin-bottom: 8px;">
		<?php esc_html_e( 'Assign one or more locations to each manager. Managers can only see and manage orders for their assigned locations.', 'libre-bite' ); ?>
	</p>
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

<?php if ( empty( $lbite_is_tab ) ) : ?>
</div>
<?php endif; ?>
