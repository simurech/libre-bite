<?php
/**
 * Einstellungen: Menü-Sichtbarkeit nach Rolle
 *
 * Ausgelagert aus admin-settings.php (weitere Aufteilung nach v3.2.0).
 * Speichert über LBite_Admin_Settings::save_menu_visibility() (eigene Nonce).
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'libre-bite' ) );
}

$lbite_menu_visibility = get_option( 'lbite_menu_visibility', array() );
$lbite_all_menu_items  = LBite_Admin_Settings::get_all_menu_items();
$lbite_all_roles       = LBite_Admin_Settings::get_all_roles();

settings_errors( 'lbite_menu_visibility' );
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Choose which menu items should be hidden for each user role. Administrators always have full access.', 'libre-bite' ); ?>
</p>

<?php if ( empty( $lbite_all_roles ) ) : ?>
	<p><?php esc_html_e( 'No additional user roles found (other than Administrator).', 'libre-bite' ); ?></p>
<?php else : ?>
	<form method="post" action="">
		<?php wp_nonce_field( 'lbite_save_menu_visibility', 'lbite_menu_visibility_nonce' ); ?>

		<h2><?php esc_html_e( 'Menu Visibility by User Role', 'libre-bite' ); ?></h2>

		<div class="lbite-menu-visibility-settings">
			<?php
			$lbite_custom_role_names_display = get_option( 'lbite_custom_role_names', array() );
			foreach ( $lbite_all_roles as $lbite_role_key => $lbite_role_name ) :
				if ( 'shop_manager' === $lbite_role_key ) {
					continue;
				}
				$lbite_display_name = isset( $lbite_custom_role_names_display[ $lbite_role_key ] ) && ! empty( $lbite_custom_role_names_display[ $lbite_role_key ] )
					? $lbite_custom_role_names_display[ $lbite_role_key ]
					: $lbite_role_name;
				?>
				<div class="lbite-role-section" style="margin-bottom: 30px; padding: 15px; background: var(--lbite-surface, #fff); border: 1px solid #ccd0d4; border-radius: 4px;" data-role="<?php echo esc_attr( $lbite_role_key ); ?>">
					<h3 style="margin-top: 0;">
						<?php echo esc_html( $lbite_display_name ); ?>
						<span style="font-weight: normal; color: #666; font-size: 13px;">
							(<?php echo esc_html( $lbite_role_key ); ?>)
						</span>
					</h3>

					<?php if ( 'lbite_staff' === $lbite_role_key ) : ?>
						<p class="description">
							<?php esc_html_e( 'Staff users always have access to: Order Overview, POS System, Support. Menu visibility cannot be customized for this role.', 'libre-bite' ); ?>
						</p>
						<ul style="margin: 10px 0 0 10px; color: #2271b1;">
							<li>&#x2713; <?php esc_html_e( 'Order Overview', 'libre-bite' ); ?></li>
							<li>&#x2713; <?php esc_html_e( 'POS System', 'libre-bite' ); ?></li>
							<li>&#x2713; <?php esc_html_e( 'Support', 'libre-bite' ); ?></li>
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
								$lbite_has_submenus      = isset( $lbite_submenus[ $lbite_parent_slug ] ) && ! empty( $lbite_submenus[ $lbite_parent_slug ] );
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

		<?php submit_button( __( 'Save Settings', 'libre-bite' ), 'primary' ); ?>
	</form>
<?php endif; ?>
