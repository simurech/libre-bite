<?php
/**
 * Hilfe-Seite für Super-Administratoren (administrator)
 *
 * Detaillierte Dokumentation:
 * - Alle Admin-Funktionen
 * - Feature-Toggles
 * - Technische Details
 * - Troubleshooting
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_support_settings = get_option( 'lbite_support_settings', array() );
$lbite_features         = get_option( 'lbite_features', array() );

// Aktiver Tab
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lese-Parameter für Tab-Navigation.
$lbite_active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview';
?>
<div class="wrap lbite-help-wrap">
	<h1><?php esc_html_e( 'Documentation', 'libre-bite' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=overview' ) ); ?>"
		   class="nav-tab <?php echo 'overview' === $lbite_active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Overview', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=features' ) ); ?>"
		   class="nav-tab <?php echo 'features' === $lbite_active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Feature Toggles', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=roles' ) ); ?>"
		   class="nav-tab <?php echo 'roles' === $lbite_active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Roles', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=technical' ) ); ?>"
		   class="nav-tab <?php echo 'technical' === $lbite_active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Technical', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=troubleshooting' ) ); ?>"
		   class="nav-tab <?php echo 'troubleshooting' === $lbite_active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Troubleshooting', 'libre-bite' ); ?>
		</a>
	</nav>

	<div class="lbite-help-content">
		<?php
		switch ( $lbite_active_tab ) {
			case 'features':
				?>
				<div class="lbite-help-section">
					<h2><?php esc_html_e( 'Feature Toggles', 'libre-bite' ); ?></h2>
					<p><?php esc_html_e( 'With feature toggles, you can enable or disable individual Libre Bite features.', 'libre-bite' ); ?></p>

					<div class="lbite-help-article">
						<h3><?php esc_html_e( 'Current Configuration', 'libre-bite' ); ?></h3>
						<table class="widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Feature', 'libre-bite' ); ?></th>
									<th><?php esc_html_e( 'Status', 'libre-bite' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $lbite_features as $lbite_key => $lbite_enabled ) : ?>
									<tr>
										<td><code><?php echo esc_html( $lbite_key ); ?></code></td>
										<td>
											<?php if ( $lbite_enabled ) : ?>
												<span class="lbite-status-enabled"><?php esc_html_e( 'Enabled', 'libre-bite' ); ?></span>
											<?php else : ?>
												<span class="lbite-status-disabled"><?php esc_html_e( 'Disabled', 'libre-bite' ); ?></span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<p>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-features' ) ); ?>" class="button button-primary">
								<?php esc_html_e( 'Edit Feature Toggles', 'libre-bite' ); ?>
							</a>
						</p>
					</div>

					<div class="lbite-help-article">
						<h3><?php esc_html_e( 'Feature Checks in Code', 'libre-bite' ); ?></h3>
						<p><?php esc_html_e( 'Features can be checked in code as follows:', 'libre-bite' ); ?></p>

						<pre><code>// Feature-Manager laden
$features = get_option( 'lbite_features', array() );

// Feature prüfen
if ( ! empty( $features['enable_tips'] ) ) {
    // Trinkgeld-Funktion aktiviert
}</code></pre>
					</div>
				</div>
				<?php
				break;

			case 'roles':
				?>
				<div class="lbite-help-section">
					<h2><?php esc_html_e( 'User Roles', 'libre-bite' ); ?></h2>

					<div class="lbite-help-article">
						<h3><?php esc_html_e( 'Role Overview', 'libre-bite' ); ?></h3>
						<table class="widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Role', 'libre-bite' ); ?></th>
									<th><?php esc_html_e( 'Target Group', 'libre-bite' ); ?></th>
									<th><?php esc_html_e( 'Access', 'libre-bite' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><strong>lbite_staff</strong></td>
									<td><?php esc_html_e( 'Store Staff', 'libre-bite' ); ?></td>
									<td><?php esc_html_e( 'Dashboard, Order Overview, POS', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><strong>lbite_admin</strong></td>
									<td><?php esc_html_e( 'Branch Manager', 'libre-bite' ); ?></td>
									<td><?php esc_html_e( '+ Locations, Product Options, Settings', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><strong>administrator</strong></td>
									<td><?php esc_html_e( 'Super Admin', 'libre-bite' ); ?></td>
									<td><?php esc_html_e( '+ Feature Toggles, Admin Settings, Debug', 'libre-bite' ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>

					<div class="lbite-help-article">
						<h3><?php esc_html_e( 'Capabilities', 'libre-bite' ); ?></h3>
						<table class="widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Capability', 'libre-bite' ); ?></th>
									<th><?php esc_html_e( 'Description', 'libre-bite' ); ?></th>
									<th>Staff</th>
									<th>Admin</th>
									<th>Super</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><code>lbite_view_dashboard</code></td>
									<td><?php esc_html_e( 'View Dashboard', 'libre-bite' ); ?></td>
									<td>&#10003;</td>
									<td>&#10003;</td>
									<td>&#10003;</td>
								</tr>
								<tr>
									<td><code>lbite_view_orders</code></td>
									<td><?php esc_html_e( 'View Orders', 'libre-bite' ); ?></td>
									<td>&#10003;</td>
									<td>&#10003;</td>
									<td>&#10003;</td>
								</tr>
								<tr>
									<td><code>lbite_use_pos</code></td>
									<td><?php esc_html_e( 'Use POS', 'libre-bite' ); ?></td>
									<td>&#10003;</td>
									<td>&#10003;</td>
									<td>&#10003;</td>
								</tr>
								<tr>
									<td><code>lbite_manage_locations</code></td>
									<td><?php esc_html_e( 'Manage Locations', 'libre-bite' ); ?></td>
									<td>-</td>
									<td>&#10003;</td>
									<td>&#10003;</td>
								</tr>
								<tr>
									<td><code>lbite_manage_settings</code></td>
									<td><?php esc_html_e( 'Edit Settings', 'libre-bite' ); ?></td>
									<td>-</td>
									<td>&#10003;</td>
									<td>&#10003;</td>
								</tr>
								<tr>
									<td><code>lbite_manage_features</code></td>
									<td><?php esc_html_e( 'Manage Feature Toggles', 'libre-bite' ); ?></td>
									<td>-</td>
									<td>-</td>
									<td>&#10003;</td>
								</tr>
							</tbody>
						</table>
					</div>

					<div class="lbite-help-article">
						<h3><?php esc_html_e( 'Assign User to Role', 'libre-bite' ); ?></h3>
						<ol>
							<li><?php esc_html_e( 'Go to "Users" → "All Users"', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Edit the desired user', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Select the desired POS role under "Role"', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Save changes', 'libre-bite' ); ?></li>
						</ol>
					</div>
				</div>
				<?php
				break;

			case 'technical':
				?>
				<div class="lbite-help-section">
					<h2><?php esc_html_e( 'Technical Details', 'libre-bite' ); ?></h2>

					<div class="lbite-help-article">
						<h3><?php esc_html_e( 'Plugin Information', 'libre-bite' ); ?></h3>
						<table class="widefat">
							<tbody>
								<tr>
									<th><?php esc_html_e( 'Version', 'libre-bite' ); ?></th>
									<td><?php echo esc_html( LBITE_VERSION ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Installation Date', 'libre-bite' ); ?></th>
									<td><?php echo esc_html( get_option( 'lbite_installed_date', '-' ) ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Plugin Path', 'libre-bite' ); ?></th>
									<td><code><?php echo esc_html( LBITE_PLUGIN_DIR ); ?></code></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'WordPress', 'libre-bite' ); ?></th>
									<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'WooCommerce', 'libre-bite' ); ?></th>
									<td><?php echo defined( 'WC_VERSION' ) ? esc_html( WC_VERSION ) : '-'; ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'PHP', 'libre-bite' ); ?></th>
									<td><?php echo esc_html( phpversion() ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>

					<div class="lbite-help-article">
						<h3><?php esc_html_e( 'Options (Database)', 'libre-bite' ); ?></h3>
						<table class="widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Option', 'libre-bite' ); ?></th>
									<th><?php esc_html_e( 'Description', 'libre-bite' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><code>lbite_version</code></td>
									<td><?php esc_html_e( 'Installed Plugin Version', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><code>lbite_features</code></td>
									<td><?php esc_html_e( 'Feature Toggle Settings', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><code>lbite_support_settings</code></td>
									<td><?php esc_html_e( 'Support Contact Information', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><code>lbite_checkout_fields</code></td>
									<td><?php esc_html_e( 'Checkout Field Configuration', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><code>lbite_preparation_time</code></td>
									<td><?php esc_html_e( 'Preparation Time in Minutes', 'libre-bite' ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>

					<div class="lbite-help-article">
						<h3><?php esc_html_e( 'Custom Post Types', 'libre-bite' ); ?></h3>
						<ul>
							<li><code>lbite_location</code> - <?php esc_html_e( 'Locations', 'libre-bite' ); ?></li>
							<li><code>lbite_product_option</code> - <?php esc_html_e( 'Product Options', 'libre-bite' ); ?></li>
						</ul>
					</div>

					<div class="lbite-help-article">
						<h3><?php esc_html_e( 'Order Meta', 'libre-bite' ); ?></h3>
						<table class="widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Meta Key', 'libre-bite' ); ?></th>
									<th><?php esc_html_e( 'Description', 'libre-bite' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><code>_lbite_location_id</code></td>
									<td><?php esc_html_e( 'Location ID', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><code>_lbite_location_name</code></td>
									<td><?php esc_html_e( 'Location Name', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><code>_lbite_order_type</code></td>
									<td><?php esc_html_e( 'Order Type (now/scheduled)', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><code>_lbite_pickup_time</code></td>
									<td><?php esc_html_e( 'Scheduled Pickup Time', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><code>_lbite_order_source</code></td>
									<td><?php esc_html_e( 'Source (pos/website)', 'libre-bite' ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				<?php
				break;

			case 'troubleshooting':
				?>
				<div class="lbite-help-section">
					<h2><?php esc_html_e( 'Troubleshooting', 'libre-bite' ); ?></h2>

					<div class="lbite-help-article">
						<h3><?php esc_html_e( 'Common Issues', 'libre-bite' ); ?></h3>

						<div class="lbite-faq-item">
							<h4><?php esc_html_e( 'Menus are not displaying correctly', 'libre-bite' ); ?></h4>
							<p><strong><?php esc_html_e( 'Cause:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Capabilities were not assigned correctly.', 'libre-bite' ); ?></p>
							<p><strong><?php esc_html_e( 'Solution:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Disable and re-enable the plugin to recreate roles.', 'libre-bite' ); ?></p>
						</div>

						<div class="lbite-faq-item">
							<h4><?php esc_html_e( 'AJAX Error in Dashboard', 'libre-bite' ); ?></h4>
							<p><strong><?php esc_html_e( 'Cause:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Nonce error or permission issue.', 'libre-bite' ); ?></p>
							<p><strong><?php esc_html_e( 'Solution:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Reload page, clear browser cache, check user permissions.', 'libre-bite' ); ?></p>
						</div>

						<div class="lbite-faq-item">
							<h4><?php esc_html_e( 'Opening Hours Not Being Applied', 'libre-bite' ); ?></h4>
							<p><strong><?php esc_html_e( 'Cause:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Timezone or server time misconfigured.', 'libre-bite' ); ?></p>
							<p><strong><?php esc_html_e( 'Solution:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Check WordPress timezone in Settings → General.', 'libre-bite' ); ?></p>
						</div>

						<div class="lbite-faq-item">
							<h4><?php esc_html_e( 'Email Reminders Not Being Sent', 'libre-bite' ); ?></h4>
							<p><strong><?php esc_html_e( 'Cause:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'WP-Cron not running or email delivery faulty.', 'libre-bite' ); ?></p>
							<p><strong><?php esc_html_e( 'Solution:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Check WP-Cron, use SMTP plugin, check email logs.', 'libre-bite' ); ?></p>
						</div>
					</div>

					<div class="lbite-help-article">
						<h3><?php esc_html_e( 'Debug Mode', 'libre-bite' ); ?></h3>
						<p><?php esc_html_e( 'Enable WP_DEBUG in wp-config.php for detailed error messages:', 'libre-bite' ); ?></p>

						<pre><code>define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );</code></pre>

						<p><?php esc_html_e( 'Errors are stored in /wp-content/debug.log.', 'libre-bite' ); ?></p>

						<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
							<p>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-debug-info' ) ); ?>" class="button">
									<?php esc_html_e( 'Show Debug Info', 'libre-bite' ); ?>
								</a>
							</p>
						<?php endif; ?>
					</div>

					<div class="lbite-help-article">
						<h3><?php esc_html_e( 'Reset Plugin', 'libre-bite' ); ?></h3>
						<p><?php esc_html_e( 'If serious problems occur:', 'libre-bite' ); ?></p>
						<ol>
							<li><?php esc_html_e( 'Disable Plugin', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Delete Plugin (Data Preserved)', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Reinstall Plugin', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Activate Plugin', 'libre-bite' ); ?></li>
						</ol>

						<div class="lbite-help-notice lbite-help-warning">
							<span class="dashicons dashicons-warning"></span>
							<p><?php esc_html_e( 'Warning: To delete all data, the plugin must be deleted via the WordPress interface (not just deactivated).', 'libre-bite' ); ?></p>
						</div>
					</div>
				</div>
				<?php
				break;

			default:
				// Übersicht
				?>
				<div class="lbite-help-grid">
					<div class="lbite-help-card lbite-help-quickstart">
						<h2><span class="dashicons dashicons-superhero"></span> <?php esc_html_e( 'Super Admin Overview', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'As a Super Admin, you have full access to all Libre Bite features and settings.', 'libre-bite' ); ?></p>

						<div class="lbite-quick-links">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-features' ) ); ?>" class="button button-primary">
								<span class="dashicons dashicons-admin-plugins"></span>
								<?php esc_html_e( 'Feature Toggles', 'libre-bite' ); ?>
							</a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-admin-settings' ) ); ?>" class="button">
								<span class="dashicons dashicons-admin-settings"></span>
								<?php esc_html_e( 'Admin Settings', 'libre-bite' ); ?>
							</a>
							<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-debug-info' ) ); ?>" class="button">
									<span class="dashicons dashicons-info"></span>
									<?php esc_html_e( 'Debug Info', 'libre-bite' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>

					<div class="lbite-help-card">
						<h2><span class="dashicons dashicons-admin-plugins"></span> <?php esc_html_e( 'Feature Toggles', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Enable or disable individual plugin features.', 'libre-bite' ); ?></p>
						<p><strong><?php esc_html_e( 'Active Features:', 'libre-bite' ); ?></strong> <?php echo esc_html( count( array_filter( $features ) ) ); ?> / <?php echo esc_html( count( $features ) ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=features' ) ); ?>" class="button">
							<?php esc_html_e( 'Details', 'libre-bite' ); ?>
						</a>
					</div>

					<div class="lbite-help-card">
						<h2><span class="dashicons dashicons-groups"></span> <?php esc_html_e( 'Roles & Permissions', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Manage the three user levels: Staff, Admin, Super Admin.', 'libre-bite' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=roles' ) ); ?>" class="button">
							<?php esc_html_e( 'Details', 'libre-bite' ); ?>
						</a>
					</div>

					<div class="lbite-help-card">
						<h2><span class="dashicons dashicons-editor-code"></span> <?php esc_html_e( 'Technical Details', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Plugin information, database options, meta keys.', 'libre-bite' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=technical' ) ); ?>" class="button">
							<?php esc_html_e( 'Details', 'libre-bite' ); ?>
						</a>
					</div>

					<div class="lbite-help-card">
						<h2><span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Troubleshooting', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Solutions for common issues and debugging tips.', 'libre-bite' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=troubleshooting' ) ); ?>" class="button">
							<?php esc_html_e( 'Details', 'libre-bite' ); ?>
						</a>
					</div>

					<div class="lbite-help-card lbite-help-support">
						<h2><span class="dashicons dashicons-phone"></span> <?php esc_html_e( 'Support', 'libre-bite' ); ?></h2>
						<p>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-support-settings' ) ); ?>" class="button">
								<?php esc_html_e( 'Support Settings', 'libre-bite' ); ?>
							</a>
						</p>
					</div>
				</div>
				<?php
				break;
		}
		?>
	</div>
</div>

