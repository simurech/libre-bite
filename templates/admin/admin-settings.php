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

		<!-- Plugin-Name anpassen -->
		<h2><?php esc_html_e( 'Customize Plugin Name', 'libre-bite' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="lbite_custom_plugin_name">
						<?php esc_html_e( 'Displayed Plugin Name', 'libre-bite' ); ?>
					</label>
				</th>
				<td>
					<input
						type="text"
						id="lbite_custom_plugin_name"
						name="lbite_custom_plugin_name"
						value="<?php echo esc_attr( $lbite_custom_plugin_name ); ?>"
						class="regular-text"
						placeholder="Libre Bite"
					>
					<p class="description">
						<?php esc_html_e( 'Override the displayed name of the plugin in the backend menu and on pages. Leave empty for the default "Libre Bite".', 'libre-bite' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<!-- Zugriff für andere Rollen -->
		<?php if ( ! empty( $lbite_standard_roles ) ) : ?>
		<h2><?php esc_html_e( 'Access for Other User Roles', 'libre-bite' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Choose which standard roles are allowed to access the plugin. Enabled roles receive the same access as Libre Bite Personal (Order Overview, POS).', 'libre-bite' ); ?>
		</p>
		<?php
		$lbite_allowed_standard_roles = get_option( 'lbite_allowed_standard_roles', array() );
		?>
		<table class="form-table">
			<tbody>
			<?php foreach ( $lbite_standard_roles as $lbite_std_role_key => $lbite_std_role_name ) : ?>
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
							<?php esc_html_e( 'Can use plugin', 'libre-bite' ); ?>
						</label>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>

		<!-- Rollennamen anpassen -->
		<h2><?php esc_html_e( 'Manage User Roles', 'libre-bite' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Customize the displayed names of user roles in the backend or completely disable unused roles.', 'libre-bite' ); ?>
		</p>
		<table class="form-table">
			<thead>
				<tr>
					<th style="padding-left: 0; font-weight: 600;">
						<?php esc_html_e( 'Role', 'libre-bite' ); ?>
					</th>
					<th style="font-weight: 600;">
						<?php esc_html_e( 'Displayed Name', 'libre-bite' ); ?>
					</th>
					<th style="font-weight: 600;">
						<?php esc_html_e( 'Disable Role', 'libre-bite' ); ?>
					</th>
				</tr>
			</thead>
			<tbody>
			<?php
			$lbite_all_roles_with_admin = LBite_Admin_Settings::get_all_roles( true );
			$lbite_custom_role_names = get_option( 'lbite_custom_role_names', array() );
			$lbite_disabled_roles = get_option( 'lbite_disabled_roles', array() );

			foreach ( $lbite_all_roles_with_admin as $lbite_role_key => $lbite_role_name ) :
				$lbite_custom_name = isset( $lbite_custom_role_names[ $lbite_role_key ] ) ? $lbite_custom_role_names[ $lbite_role_key ] : '';
				$lbite_is_disabled = in_array( $lbite_role_key, $lbite_disabled_roles, true );
				$lbite_is_admin = $lbite_role_key === 'administrator';
				?>
				<tr>
					<th scope="row">
						<label for="lbite_role_name_<?php echo esc_attr( $lbite_role_key ); ?>">
							<?php echo esc_html( $lbite_role_name ); ?>
							<span style="color: #646970; font-weight: normal; font-size: 12px;">
								(<?php echo esc_html( $lbite_role_key ); ?>)
							</span>
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
							<?php disabled( $lbite_is_disabled && ! $lbite_is_admin ); ?>
						>
					</td>
					<td>
						<?php if ( $lbite_is_admin ) : ?>
							<span style="color: #646970; font-style: italic;">
								<?php esc_html_e( 'Cannot be disabled', 'libre-bite' ); ?>
							</span>
						<?php else : ?>
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
							<p class="description" style="margin-top: 5px;">
								<?php esc_html_e( 'The role will no longer be available in the backend.', 'libre-bite' ); ?>
							</p>
						<?php endif; ?>
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
						// Menüs nach Parent gruppieren - besser strukturiert
						$lbite_main_menus = array();
						$lbite_submenus = array();

						// Erst alle Menüs sortieren
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
								$lbite_has_submenus = isset( $lbite_submenus[ $lbite_parent_slug ] ) && ! empty( $lbite_submenus[ $lbite_parent_slug ] );
								$lbite_is_parent_checked = isset( $lbite_menu_visibility[ $lbite_role_key ] ) && in_array( $lbite_parent_slug, $lbite_menu_visibility[ $lbite_role_key ], true );
								?>

								<div class="lbite-menu-group">
									<!-- Hauptmenü -->
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

									<!-- Untermenüs -->
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
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php submit_button( __( 'Save Settings', 'libre-bite' ), 'primary' ); ?>
	</form>
<?php if ( empty( $lbite_is_tab ) ) : ?>
</div>
<?php endif; ?>
