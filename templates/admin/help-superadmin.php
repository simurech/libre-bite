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

$support_settings = get_option( 'lb_support_settings', array() );
$features         = get_option( 'lb_features', array() );

// Aktiver Tab
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview';
?>
<div class="wrap lb-help-wrap">
	<h1><?php esc_html_e( 'Dokumentation', 'libre-bite' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=overview' ) ); ?>"
		   class="nav-tab <?php echo 'overview' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Übersicht', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=features' ) ); ?>"
		   class="nav-tab <?php echo 'features' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Feature-Toggles', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=roles' ) ); ?>"
		   class="nav-tab <?php echo 'roles' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Rollen', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=technical' ) ); ?>"
		   class="nav-tab <?php echo 'technical' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Technisch', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=troubleshooting' ) ); ?>"
		   class="nav-tab <?php echo 'troubleshooting' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Troubleshooting', 'libre-bite' ); ?>
		</a>
	</nav>

	<div class="lb-help-content">
		<?php
		switch ( $active_tab ) {
			case 'features':
				?>
				<div class="lb-help-section">
					<h2><?php esc_html_e( 'Feature-Toggles', 'libre-bite' ); ?></h2>
					<p><?php esc_html_e( 'Mit Feature-Toggles können Sie einzelne Funktionen des Libre Bites aktivieren oder deaktivieren.', 'libre-bite' ); ?></p>

					<div class="lb-help-article">
						<h3><?php esc_html_e( 'Aktuelle Konfiguration', 'libre-bite' ); ?></h3>
						<table class="widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Feature', 'libre-bite' ); ?></th>
									<th><?php esc_html_e( 'Status', 'libre-bite' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $features as $key => $enabled ) : ?>
									<tr>
										<td><code><?php echo esc_html( $key ); ?></code></td>
										<td>
											<?php if ( $enabled ) : ?>
												<span class="lb-status-enabled"><?php esc_html_e( 'Aktiviert', 'libre-bite' ); ?></span>
											<?php else : ?>
												<span class="lb-status-disabled"><?php esc_html_e( 'Deaktiviert', 'libre-bite' ); ?></span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<p>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-features' ) ); ?>" class="button button-primary">
								<?php esc_html_e( 'Feature-Toggles bearbeiten', 'libre-bite' ); ?>
							</a>
						</p>
					</div>

					<div class="lb-help-article">
						<h3><?php esc_html_e( 'Feature-Checks im Code', 'libre-bite' ); ?></h3>
						<p><?php esc_html_e( 'Features können im Code wie folgt geprüft werden:', 'libre-bite' ); ?></p>

						<pre><code>// Feature-Manager laden
$features = get_option( 'lb_features', array() );

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
				<div class="lb-help-section">
					<h2><?php esc_html_e( 'Benutzerrollen', 'libre-bite' ); ?></h2>

					<div class="lb-help-article">
						<h3><?php esc_html_e( 'Rollenübersicht', 'libre-bite' ); ?></h3>
						<table class="widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Rolle', 'libre-bite' ); ?></th>
									<th><?php esc_html_e( 'Zielgruppe', 'libre-bite' ); ?></th>
									<th><?php esc_html_e( 'Zugriff', 'libre-bite' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><strong>lb_staff</strong></td>
									<td><?php esc_html_e( 'Laden-Personal', 'libre-bite' ); ?></td>
									<td><?php esc_html_e( 'Dashboard, Bestellübersicht, POS', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><strong>lb_admin</strong></td>
									<td><?php esc_html_e( 'Filialleiter', 'libre-bite' ); ?></td>
									<td><?php esc_html_e( '+ Standorte, Produkt-Optionen, Einstellungen', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><strong>administrator</strong></td>
									<td><?php esc_html_e( 'Super-Admin', 'libre-bite' ); ?></td>
									<td><?php esc_html_e( '+ Feature-Toggles, Admin-Einstellungen, Debug', 'libre-bite' ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>

					<div class="lb-help-article">
						<h3><?php esc_html_e( 'Capabilities', 'libre-bite' ); ?></h3>
						<table class="widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Capability', 'libre-bite' ); ?></th>
									<th><?php esc_html_e( 'Beschreibung', 'libre-bite' ); ?></th>
									<th>Staff</th>
									<th>Admin</th>
									<th>Super</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><code>lb_view_dashboard</code></td>
									<td><?php esc_html_e( 'Dashboard anzeigen', 'libre-bite' ); ?></td>
									<td>&#10003;</td>
									<td>&#10003;</td>
									<td>&#10003;</td>
								</tr>
								<tr>
									<td><code>lb_view_orders</code></td>
									<td><?php esc_html_e( 'Bestellungen anzeigen', 'libre-bite' ); ?></td>
									<td>&#10003;</td>
									<td>&#10003;</td>
									<td>&#10003;</td>
								</tr>
								<tr>
									<td><code>lb_use_pos</code></td>
									<td><?php esc_html_e( 'POS nutzen', 'libre-bite' ); ?></td>
									<td>&#10003;</td>
									<td>&#10003;</td>
									<td>&#10003;</td>
								</tr>
								<tr>
									<td><code>lb_manage_locations</code></td>
									<td><?php esc_html_e( 'Standorte verwalten', 'libre-bite' ); ?></td>
									<td>-</td>
									<td>&#10003;</td>
									<td>&#10003;</td>
								</tr>
								<tr>
									<td><code>lb_manage_settings</code></td>
									<td><?php esc_html_e( 'Einstellungen bearbeiten', 'libre-bite' ); ?></td>
									<td>-</td>
									<td>&#10003;</td>
									<td>&#10003;</td>
								</tr>
								<tr>
									<td><code>lb_manage_features</code></td>
									<td><?php esc_html_e( 'Feature-Toggles verwalten', 'libre-bite' ); ?></td>
									<td>-</td>
									<td>-</td>
									<td>&#10003;</td>
								</tr>
							</tbody>
						</table>
					</div>

					<div class="lb-help-article">
						<h3><?php esc_html_e( 'Benutzer einer Rolle zuweisen', 'libre-bite' ); ?></h3>
						<ol>
							<li><?php esc_html_e( 'Gehen Sie zu "Benutzer" → "Alle Benutzer"', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Bearbeiten Sie den gewünschten Benutzer', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Wählen Sie unter "Rolle" die gewünschte OOS-Rolle', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Speichern Sie die Änderungen', 'libre-bite' ); ?></li>
						</ol>
					</div>
				</div>
				<?php
				break;

			case 'technical':
				?>
				<div class="lb-help-section">
					<h2><?php esc_html_e( 'Technische Details', 'libre-bite' ); ?></h2>

					<div class="lb-help-article">
						<h3><?php esc_html_e( 'Plugin-Informationen', 'libre-bite' ); ?></h3>
						<table class="widefat">
							<tbody>
								<tr>
									<th><?php esc_html_e( 'Version', 'libre-bite' ); ?></th>
									<td><?php echo esc_html( LB_VERSION ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Installationsdatum', 'libre-bite' ); ?></th>
									<td><?php echo esc_html( get_option( 'lb_installed_date', '-' ) ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Plugin-Pfad', 'libre-bite' ); ?></th>
									<td><code><?php echo esc_html( LB_PLUGIN_DIR ); ?></code></td>
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

					<div class="lb-help-article">
						<h3><?php esc_html_e( 'Options (Datenbank)', 'libre-bite' ); ?></h3>
						<table class="widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Option', 'libre-bite' ); ?></th>
									<th><?php esc_html_e( 'Beschreibung', 'libre-bite' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><code>lb_version</code></td>
									<td><?php esc_html_e( 'Installierte Plugin-Version', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><code>lb_features</code></td>
									<td><?php esc_html_e( 'Feature-Toggle-Einstellungen', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><code>lb_support_settings</code></td>
									<td><?php esc_html_e( 'Support-Kontaktdaten', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><code>lb_checkout_fields</code></td>
									<td><?php esc_html_e( 'Checkout-Feld-Konfiguration', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><code>lb_preparation_time</code></td>
									<td><?php esc_html_e( 'Vorbereitungszeit in Minuten', 'libre-bite' ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>

					<div class="lb-help-article">
						<h3><?php esc_html_e( 'Custom Post Types', 'libre-bite' ); ?></h3>
						<ul>
							<li><code>lb_location</code> - <?php esc_html_e( 'Standorte', 'libre-bite' ); ?></li>
							<li><code>lb_product_option</code> - <?php esc_html_e( 'Produkt-Optionen', 'libre-bite' ); ?></li>
						</ul>
					</div>

					<div class="lb-help-article">
						<h3><?php esc_html_e( 'Order Meta', 'libre-bite' ); ?></h3>
						<table class="widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Meta-Key', 'libre-bite' ); ?></th>
									<th><?php esc_html_e( 'Beschreibung', 'libre-bite' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><code>_lb_location_id</code></td>
									<td><?php esc_html_e( 'Standort-ID', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><code>_lb_location_name</code></td>
									<td><?php esc_html_e( 'Standort-Name', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><code>_lb_order_type</code></td>
									<td><?php esc_html_e( 'Bestelltyp (now/scheduled)', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><code>_lb_pickup_time</code></td>
									<td><?php esc_html_e( 'Geplante Abholzeit', 'libre-bite' ); ?></td>
								</tr>
								<tr>
									<td><code>_lb_order_source</code></td>
									<td><?php esc_html_e( 'Quelle (pos/website)', 'libre-bite' ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				<?php
				break;

			case 'troubleshooting':
				?>
				<div class="lb-help-section">
					<h2><?php esc_html_e( 'Troubleshooting', 'libre-bite' ); ?></h2>

					<div class="lb-help-article">
						<h3><?php esc_html_e( 'Häufige Probleme', 'libre-bite' ); ?></h3>

						<div class="lb-faq-item">
							<h4><?php esc_html_e( 'Menüs werden nicht korrekt angezeigt', 'libre-bite' ); ?></h4>
							<p><strong><?php esc_html_e( 'Ursache:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Capabilities wurden nicht korrekt zugewiesen.', 'libre-bite' ); ?></p>
							<p><strong><?php esc_html_e( 'Lösung:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Plugin deaktivieren und wieder aktivieren, um Rollen neu zu erstellen.', 'libre-bite' ); ?></p>
						</div>

						<div class="lb-faq-item">
							<h4><?php esc_html_e( 'AJAX-Fehler im Dashboard', 'libre-bite' ); ?></h4>
							<p><strong><?php esc_html_e( 'Ursache:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Nonce-Fehler oder Berechtigungsproblem.', 'libre-bite' ); ?></p>
							<p><strong><?php esc_html_e( 'Lösung:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Seite neu laden, Browser-Cache leeren, Benutzerberechtigungen prüfen.', 'libre-bite' ); ?></p>
						</div>

						<div class="lb-faq-item">
							<h4><?php esc_html_e( 'Öffnungszeiten werden nicht berücksichtigt', 'libre-bite' ); ?></h4>
							<p><strong><?php esc_html_e( 'Ursache:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Zeitzone oder Serverzeit falsch konfiguriert.', 'libre-bite' ); ?></p>
							<p><strong><?php esc_html_e( 'Lösung:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'WordPress Zeitzone in Einstellungen → Allgemein prüfen.', 'libre-bite' ); ?></p>
						</div>

						<div class="lb-faq-item">
							<h4><?php esc_html_e( 'E-Mail-Erinnerungen werden nicht versendet', 'libre-bite' ); ?></h4>
							<p><strong><?php esc_html_e( 'Ursache:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'WP-Cron läuft nicht oder E-Mail-Versand fehlerhaft.', 'libre-bite' ); ?></p>
							<p><strong><?php esc_html_e( 'Lösung:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'WP-Cron prüfen, SMTP-Plugin verwenden, E-Mail-Logs prüfen.', 'libre-bite' ); ?></p>
						</div>
					</div>

					<div class="lb-help-article">
						<h3><?php esc_html_e( 'Debug-Modus', 'libre-bite' ); ?></h3>
						<p><?php esc_html_e( 'Aktivieren Sie WP_DEBUG in der wp-config.php für detaillierte Fehlermeldungen:', 'libre-bite' ); ?></p>

						<pre><code>define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );</code></pre>

						<p><?php esc_html_e( 'Fehler werden in /wp-content/debug.log gespeichert.', 'libre-bite' ); ?></p>

						<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
							<p>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-debug-info' ) ); ?>" class="button">
									<?php esc_html_e( 'Debug-Info anzeigen', 'libre-bite' ); ?>
								</a>
							</p>
						<?php endif; ?>
					</div>

					<div class="lb-help-article">
						<h3><?php esc_html_e( 'Plugin zurücksetzen', 'libre-bite' ); ?></h3>
						<p><?php esc_html_e( 'Falls schwerwiegende Probleme auftreten:', 'libre-bite' ); ?></p>
						<ol>
							<li><?php esc_html_e( 'Plugin deaktivieren', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Plugin löschen (Daten bleiben erhalten)', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Plugin neu installieren', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Plugin aktivieren', 'libre-bite' ); ?></li>
						</ol>

						<div class="lb-help-notice lb-help-warning">
							<span class="dashicons dashicons-warning"></span>
							<p><?php esc_html_e( 'Warnung: Um alle Daten zu löschen, muss das Plugin über die WordPress-Oberfläche gelöscht werden (nicht nur deaktiviert).', 'libre-bite' ); ?></p>
						</div>
					</div>
				</div>
				<?php
				break;

			default:
				// Übersicht
				?>
				<div class="lb-help-grid">
					<div class="lb-help-card lb-help-quickstart">
						<h2><span class="dashicons dashicons-superhero"></span> <?php esc_html_e( 'Super-Admin Übersicht', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Als Super-Admin haben Sie vollen Zugriff auf alle Funktionen und Einstellungen des Libre Bites.', 'libre-bite' ); ?></p>

						<div class="lb-quick-links">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-features' ) ); ?>" class="button button-primary">
								<span class="dashicons dashicons-admin-plugins"></span>
								<?php esc_html_e( 'Feature-Toggles', 'libre-bite' ); ?>
							</a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-admin-settings' ) ); ?>" class="button">
								<span class="dashicons dashicons-admin-settings"></span>
								<?php esc_html_e( 'Admin-Einstellungen', 'libre-bite' ); ?>
							</a>
							<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-debug-info' ) ); ?>" class="button">
									<span class="dashicons dashicons-info"></span>
									<?php esc_html_e( 'Debug-Info', 'libre-bite' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>

					<div class="lb-help-card">
						<h2><span class="dashicons dashicons-admin-plugins"></span> <?php esc_html_e( 'Feature-Toggles', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Aktivieren oder deaktivieren Sie einzelne Funktionen des Plugins.', 'libre-bite' ); ?></p>
						<p><strong><?php esc_html_e( 'Aktive Features:', 'libre-bite' ); ?></strong> <?php echo esc_html( count( array_filter( $features ) ) ); ?> / <?php echo esc_html( count( $features ) ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=features' ) ); ?>" class="button">
							<?php esc_html_e( 'Details', 'libre-bite' ); ?>
						</a>
					</div>

					<div class="lb-help-card">
						<h2><span class="dashicons dashicons-groups"></span> <?php esc_html_e( 'Rollen & Berechtigungen', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Verwalten Sie die drei Benutzerebenen: Personal, Admin, Super-Admin.', 'libre-bite' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=roles' ) ); ?>" class="button">
							<?php esc_html_e( 'Details', 'libre-bite' ); ?>
						</a>
					</div>

					<div class="lb-help-card">
						<h2><span class="dashicons dashicons-editor-code"></span> <?php esc_html_e( 'Technische Details', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Plugin-Informationen, Datenbank-Optionen, Meta-Keys.', 'libre-bite' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=technical' ) ); ?>" class="button">
							<?php esc_html_e( 'Details', 'libre-bite' ); ?>
						</a>
					</div>

					<div class="lb-help-card">
						<h2><span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Troubleshooting', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Lösungen für häufige Probleme und Debug-Tipps.', 'libre-bite' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=troubleshooting' ) ); ?>" class="button">
							<?php esc_html_e( 'Details', 'libre-bite' ); ?>
						</a>
					</div>

					<div class="lb-help-card lb-help-support">
						<h2><span class="dashicons dashicons-phone"></span> <?php esc_html_e( 'Support', 'libre-bite' ); ?></h2>
						<p>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-support-settings' ) ); ?>" class="button">
								<?php esc_html_e( 'Support-Einstellungen', 'libre-bite' ); ?>
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

<style>
.lb-help-wrap {
	max-width: 1200px;
}

.lb-help-content {
	margin-top: 20px;
}

.lb-help-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
	gap: 20px;
}

.lb-help-card {
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 4px;
	padding: 20px;
}

.lb-help-card h2 {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-top: 0;
	padding-bottom: 10px;
	border-bottom: 1px solid #eee;
}

.lb-help-card h2 .dashicons {
	color: #2271b1;
}

.lb-help-quickstart {
	grid-column: 1 / -1;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: #fff;
	border: none;
}

.lb-help-quickstart h2 {
	color: #fff;
	border-bottom-color: rgba(255,255,255,0.2);
}

.lb-help-quickstart h2 .dashicons {
	color: #fff;
}

.lb-quick-links {
	display: flex;
	gap: 10px;
	margin-top: 15px;
	flex-wrap: wrap;
}

.lb-quick-links .button {
	display: inline-flex;
	align-items: center;
	gap: 5px;
}

.lb-help-section {
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 4px;
	padding: 20px;
}

.lb-help-article {
	margin-bottom: 30px;
	padding-bottom: 30px;
	border-bottom: 1px solid #eee;
}

.lb-help-article:last-child {
	margin-bottom: 0;
	padding-bottom: 0;
	border-bottom: none;
}

.lb-help-article h3 {
	margin-top: 0;
}

.lb-help-article table {
	margin: 15px 0;
}

.lb-help-article pre {
	background: #f6f7f7;
	padding: 15px;
	border-radius: 4px;
	overflow-x: auto;
}

.lb-status-enabled {
	color: #00a32a;
	font-weight: 500;
}

.lb-status-disabled {
	color: #d63638;
}

.lb-faq-item {
	margin-bottom: 20px;
	padding: 15px;
	background: #f6f7f7;
	border-radius: 4px;
}

.lb-faq-item h4 {
	margin-top: 0;
	margin-bottom: 10px;
}

.lb-faq-item p {
	margin: 5px 0;
}

.lb-help-notice {
	display: flex;
	align-items: flex-start;
	gap: 10px;
	padding: 12px;
	border-radius: 4px;
	margin-top: 15px;
	background: #f0f6fc;
	border-left: 3px solid #2271b1;
}

.lb-help-notice .dashicons {
	color: #2271b1;
}

.lb-help-warning {
	background: #fcf0f1;
	border-left-color: #d63638;
}

.lb-help-warning .dashicons {
	color: #d63638;
}

.lb-help-notice p {
	margin: 0;
}

.lb-help-support {
	background: #f6f7f7;
}

@media screen and (max-width: 782px) {
	.lb-help-grid {
		grid-template-columns: 1fr;
	}

	.lb-help-quickstart {
		grid-column: 1;
	}
}
</style>
