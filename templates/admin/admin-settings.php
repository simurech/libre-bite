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
	wp_die( __( 'Sie haben keine Berechtigung, auf diese Seite zuzugreifen.', 'libre-bite' ) );
}

// Aktuelle Einstellungen abrufen
$custom_plugin_name = get_option( 'lb_custom_plugin_name', '' );
$menu_visibility    = get_option( 'lb_menu_visibility', array() );

// Alle Menüeinträge und Rollen abrufen
$all_menu_items = LB_Admin_Settings::get_all_menu_items();
$all_roles      = LB_Admin_Settings::get_all_roles();

// Debug: Menüs anzeigen (nur für Admins)
if ( isset( $_GET['debug_menus'] ) && current_user_can( 'manage_options' ) ) {
	echo '<div class="notice notice-info"><pre style="max-height: 300px; overflow: auto;">';
	echo 'Gefundene Menüeinträge (' . count( $all_menu_items ) . '):<br><br>';
	foreach ( $all_menu_items as $slug => $data ) {
		echo 'Slug: ' . esc_html( $slug ) . ' | Titel: ' . esc_html( $data['title'] ) . ' | Parent: ' . esc_html( $data['parent'] ) . '<br>';
	}
	echo '</pre></div>';
}

// Plugin-Name für Titel
$plugin_display_name = ! empty( $custom_plugin_name ) ? $custom_plugin_name : 'Libre Bite';
?>

<div class="wrap">
	<h1><?php echo esc_html( $plugin_display_name ); ?> - <?php esc_html_e( 'Admin-Einstellungen', 'libre-bite' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'Diese Einstellungen sind nur für Administratoren sichtbar und ermöglichen erweiterte Anpassungen.', 'libre-bite' ); ?>
	</p>

	<?php settings_errors( 'lb_admin_settings' ); ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'lb_save_admin_settings', 'lb_admin_settings_nonce' ); ?>

		<!-- Plugin-Name anpassen -->
		<h2><?php esc_html_e( 'Plugin-Name anpassen', 'libre-bite' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="lb_custom_plugin_name">
						<?php esc_html_e( 'Angezeigter Plugin-Name', 'libre-bite' ); ?>
					</label>
				</th>
				<td>
					<input
						type="text"
						id="lb_custom_plugin_name"
						name="lb_custom_plugin_name"
						value="<?php echo esc_attr( $custom_plugin_name ); ?>"
						class="regular-text"
						placeholder="Libre Bite"
					>
					<p class="description">
						<?php esc_html_e( 'Überschreiben Sie den angezeigten Namen des Plugins im Backend-Menü und auf Seiten. Leer lassen für Standard "Libre Bite".', 'libre-bite' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<!-- Rollennamen anpassen -->
		<h2><?php esc_html_e( 'Nutzerrollen verwalten', 'libre-bite' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Passen Sie die angezeigten Namen der Benutzerrollen im Backend an oder deaktivieren Sie ungenutzte Rollen komplett.', 'libre-bite' ); ?>
		</p>
		<table class="form-table">
			<thead>
				<tr>
					<th style="padding-left: 0; font-weight: 600;">
						<?php esc_html_e( 'Rolle', 'libre-bite' ); ?>
					</th>
					<th style="font-weight: 600;">
						<?php esc_html_e( 'Angezeigter Name', 'libre-bite' ); ?>
					</th>
					<th style="font-weight: 600;">
						<?php esc_html_e( 'Rolle deaktivieren', 'libre-bite' ); ?>
					</th>
				</tr>
			</thead>
			<tbody>
			<?php
			$all_roles_with_admin = LB_Admin_Settings::get_all_roles( true );
			$custom_role_names = get_option( 'lb_custom_role_names', array() );
			$disabled_roles = get_option( 'lb_disabled_roles', array() );

			foreach ( $all_roles_with_admin as $role_key => $role_name ) :
				$custom_name = isset( $custom_role_names[ $role_key ] ) ? $custom_role_names[ $role_key ] : '';
				$is_disabled = in_array( $role_key, $disabled_roles, true );
				$is_admin = $role_key === 'administrator';
				?>
				<tr>
					<th scope="row">
						<label for="lb_role_name_<?php echo esc_attr( $role_key ); ?>">
							<?php echo esc_html( $role_name ); ?>
							<span style="color: #646970; font-weight: normal; font-size: 12px;">
								(<?php echo esc_html( $role_key ); ?>)
							</span>
						</label>
					</th>
					<td>
						<input
							type="text"
							id="lb_role_name_<?php echo esc_attr( $role_key ); ?>"
							name="lb_custom_role_names[<?php echo esc_attr( $role_key ); ?>]"
							value="<?php echo esc_attr( $custom_name ); ?>"
							class="regular-text"
							placeholder="<?php echo esc_attr( $role_name ); ?>"
							<?php disabled( $is_disabled && ! $is_admin ); ?>
						>
					</td>
					<td>
						<?php if ( $is_admin ) : ?>
							<span style="color: #646970; font-style: italic;">
								<?php esc_html_e( 'Kann nicht deaktiviert werden', 'libre-bite' ); ?>
							</span>
						<?php else : ?>
							<label>
								<input
									type="checkbox"
									name="lb_disabled_roles[]"
									value="<?php echo esc_attr( $role_key ); ?>"
									<?php checked( $is_disabled ); ?>
									class="lb-disable-role-checkbox"
									data-role="<?php echo esc_attr( $role_key ); ?>"
								>
								<?php esc_html_e( 'Deaktivieren', 'libre-bite' ); ?>
							</label>
							<p class="description" style="margin-top: 5px;">
								<?php esc_html_e( 'Die Rolle wird im Backend nicht mehr verfügbar sein.', 'libre-bite' ); ?>
							</p>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<!-- Menü-Sichtbarkeit nach Nutzerrollen -->
		<h2><?php esc_html_e( 'Menü-Sichtbarkeit nach Nutzerrollen', 'libre-bite' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Wählen Sie für jede Nutzerrolle, welche Menüeinträge ausgeblendet werden sollen. Administratoren haben immer vollen Zugriff.', 'libre-bite' ); ?>
		</p>

		<?php if ( empty( $all_roles ) ) : ?>
			<p><?php esc_html_e( 'Keine zusätzlichen Benutzerrollen gefunden (außer Administrator).', 'libre-bite' ); ?></p>
		<?php else : ?>
			<div class="lb-menu-visibility-settings">
				<?php
				$custom_role_names_display = get_option( 'lb_custom_role_names', array() );
				foreach ( $all_roles as $role_key => $role_name ) :
					// Angepassten Rollennamen verwenden, falls vorhanden
					$display_name = isset( $custom_role_names_display[ $role_key ] ) && ! empty( $custom_role_names_display[ $role_key ] )
						? $custom_role_names_display[ $role_key ]
						: $role_name;
					?>
					<div class="lb-role-section" style="margin-bottom: 30px; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;" data-role="<?php echo esc_attr( $role_key ); ?>">
						<h3 style="margin-top: 0;">
							<?php echo esc_html( $display_name ); ?>
							<span style="font-weight: normal; color: #666; font-size: 13px;">
								(<?php echo esc_html( $role_key ); ?>)
							</span>
						</h3>

						<p class="description" style="margin-bottom: 15px;">
							<?php
							printf(
								/* translators: %s: Rollenname */
								esc_html__( 'Wählen Sie die Menüeinträge, die für Benutzer mit der Rolle "%s" ausgeblendet werden sollen.', 'libre-bite' ),
								esc_html( $display_name )
							);
							?>
						</p>

						<p style="margin-bottom: 15px;">
							<label style="font-weight: 600; cursor: pointer;">
								<input
									type="checkbox"
									class="lb-toggle-all-menus"
									data-role="<?php echo esc_attr( $role_key ); ?>"
								>
								<?php esc_html_e( 'Alle auswählen / abwählen', 'libre-bite' ); ?>
							</label>
						</p>

						<?php
						// Menüs nach Parent gruppieren - besser strukturiert
						$main_menus = array();
						$submenus = array();

						// Erst alle Menüs sortieren
						foreach ( $all_menu_items as $menu_slug => $menu_data ) {
							if ( empty( $menu_data['parent'] ) ) {
								$main_menus[ $menu_slug ] = $menu_data;
							} else {
								if ( ! isset( $submenus[ $menu_data['parent'] ] ) ) {
									$submenus[ $menu_data['parent'] ] = array();
								}
								$submenus[ $menu_data['parent'] ][ $menu_slug ] = $menu_data;
							}
						}
						?>

						<div class="lb-menu-items">
							<?php foreach ( $main_menus as $parent_slug => $parent_data ) : ?>
								<?php
								$has_submenus = isset( $submenus[ $parent_slug ] ) && ! empty( $submenus[ $parent_slug ] );
								$is_parent_checked = isset( $menu_visibility[ $role_key ] ) && in_array( $parent_slug, $menu_visibility[ $role_key ], true );
								?>

								<div class="lb-menu-group">
									<!-- Hauptmenü -->
									<div class="lb-main-menu-item">
										<label>
											<input
												type="checkbox"
												name="lb_menu_visibility[<?php echo esc_attr( $role_key ); ?>][]"
												value="<?php echo esc_attr( $parent_slug ); ?>"
												<?php checked( $is_parent_checked ); ?>
											/>
											<strong><?php echo esc_html( $parent_data['title'] ); ?></strong>
											<span class="lb-menu-badge"><?php esc_html_e( 'Hauptmenü', 'libre-bite' ); ?></span>
										</label>
									</div>

									<!-- Untermenüs -->
									<?php if ( $has_submenus ) : ?>
										<div class="lb-submenu-items">
											<?php foreach ( $submenus[ $parent_slug ] as $submenu_slug => $submenu_data ) : ?>
												<?php
												$is_submenu_checked = isset( $menu_visibility[ $role_key ] ) && in_array( $submenu_slug, $menu_visibility[ $role_key ], true );
												?>
												<label>
													<input
														type="checkbox"
														name="lb_menu_visibility[<?php echo esc_attr( $role_key ); ?>][]"
														value="<?php echo esc_attr( $submenu_slug ); ?>"
														<?php checked( $is_submenu_checked ); ?>
													/>
													<?php echo esc_html( $submenu_data['title'] ); ?>
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

		<?php submit_button( __( 'Einstellungen speichern', 'libre-bite' ), 'primary' ); ?>
	</form>
</div>

<style>
.lb-menu-visibility-settings {
	margin-top: 20px;
}

.lb-role-section {
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.lb-menu-items {
	display: flex;
	flex-direction: column;
	gap: 20px;
}

.lb-menu-group {
	padding: 15px;
	background: #f6f7f7;
	border-radius: 3px;
	border-left: 4px solid #2271b1;
	transition: box-shadow 0.2s ease;
}

.lb-menu-group:hover {
	box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.lb-main-menu-item {
	margin-bottom: 12px;
}

.lb-main-menu-item label {
	display: block;
	font-weight: 600;
	cursor: pointer;
	line-height: 1.8;
}

.lb-main-menu-item input[type="checkbox"] {
	margin-right: 8px;
	vertical-align: middle;
}

.lb-menu-badge {
	margin-left: 8px;
	font-size: 11px;
	color: #646970;
	font-weight: normal;
}

.lb-submenu-items {
	margin-left: 24px;
	padding-top: 8px;
	border-top: 1px solid #dcdcde;
}

.lb-submenu-items label {
	display: block;
	padding: 5px 0;
	cursor: pointer;
	color: #50575e;
}

.lb-submenu-items input[type="checkbox"] {
	margin-right: 8px;
	vertical-align: middle;
}

.lb-menu-items label:hover {
	background: #fff;
	padding-left: 5px;
	margin-left: -5px;
	border-radius: 3px;
}

.lb-toggle-all-menus {
	margin-right: 8px;
}
</style>

<script>
jQuery(document).ready(function($) {
	// "Alle auswählen/abwählen" Funktionalität
	$('.lb-toggle-all-menus').on('change', function() {
		var roleKey = $(this).data('role');
		var isChecked = $(this).prop('checked');
		var roleSection = $('.lb-role-section[data-role="' + roleKey + '"]');

		// Alle Checkboxen in dieser Rolle an-/abwählen
		roleSection.find('.lb-menu-items input[type="checkbox"]').prop('checked', isChecked);
	});

	// Status der "Alle auswählen" Checkbox aktualisieren
	$('.lb-menu-items input[type="checkbox"]').on('change', function() {
		var roleSection = $(this).closest('.lb-role-section');
		var roleKey = roleSection.data('role');
		var totalCheckboxes = roleSection.find('.lb-menu-items input[type="checkbox"]').length;
		var checkedCheckboxes = roleSection.find('.lb-menu-items input[type="checkbox"]:checked').length;

		// "Alle auswählen" Checkbox aktualisieren
		var toggleAll = roleSection.find('.lb-toggle-all-menus');
		if (checkedCheckboxes === totalCheckboxes) {
			toggleAll.prop('checked', true);
			toggleAll.prop('indeterminate', false);
		} else if (checkedCheckboxes === 0) {
			toggleAll.prop('checked', false);
			toggleAll.prop('indeterminate', false);
		} else {
			toggleAll.prop('indeterminate', true);
		}
	});

	// Initialen Status der "Alle auswählen" Checkboxen setzen
	$('.lb-role-section').each(function() {
		var roleSection = $(this);
		var totalCheckboxes = roleSection.find('.lb-menu-items input[type="checkbox"]').length;
		var checkedCheckboxes = roleSection.find('.lb-menu-items input[type="checkbox"]:checked').length;

		var toggleAll = roleSection.find('.lb-toggle-all-menus');
		if (checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0) {
			toggleAll.prop('checked', true);
		} else if (checkedCheckboxes > 0) {
			toggleAll.prop('indeterminate', true);
		}
	});

	// Rolle deaktivieren - Namensfeld deaktivieren
	$('.lb-disable-role-checkbox').on('change', function() {
		var roleKey = $(this).data('role');
		var isDisabled = $(this).prop('checked');
		var nameInput = $('#lb_role_name_' + roleKey);

		if (isDisabled) {
			nameInput.prop('disabled', true).css('opacity', '0.5');
		} else {
			nameInput.prop('disabled', false).css('opacity', '1');
		}
	});

	// Initial deaktivierte Rollen
	$('.lb-disable-role-checkbox:checked').each(function() {
		var roleKey = $(this).data('role');
		var nameInput = $('#lb_role_name_' + roleKey);
		nameInput.css('opacity', '0.5');
	});
});
</script>
