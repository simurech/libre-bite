<?php
/**
 * Hilfe-Seite für Administratoren (lbite_admin)
 *
 * Mittlere Dokumentation:
 * - Alle Personal-Funktionen
 * - Produktverwaltung
 * - Standorte
 * - Einstellungen
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-Datei, wird innerhalb einer Klassen-Methode via include geladen; Variablen befinden sich im Methoden-Scope, nicht im globalen Namespace.

$support_settings = get_option( 'lbite_support_settings', array() );
$support_email    = isset( $support_settings['support_email'] ) ? $support_settings['support_email'] : get_option( 'admin_email' );
$support_phone    = isset( $support_settings['support_phone'] ) ? $support_settings['support_phone'] : '';
$support_hours    = isset( $support_settings['support_hours'] ) ? $support_settings['support_hours'] : '';
$billing_note     = isset( $support_settings['support_billing_note'] ) ? $support_settings['support_billing_note'] : '';
$custom_text      = isset( $support_settings['support_custom_text'] ) ? $support_settings['support_custom_text'] : '';

// Aktiver Tab
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lese-Parameter für Tab-Navigation.
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview';
?>
<div class="wrap lbite-help-wrap">
	<h1><?php esc_html_e( 'Hilfe & Support', 'libre-bite' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=overview' ) ); ?>"
		   class="nav-tab <?php echo 'overview' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Übersicht', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=orders' ) ); ?>"
		   class="nav-tab <?php echo 'orders' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Bestellungen', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=products' ) ); ?>"
		   class="nav-tab <?php echo 'products' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Produkte', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=locations' ) ); ?>"
		   class="nav-tab <?php echo 'locations' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Standorte', 'libre-bite' ); ?>
		</a>
		<?php if ( lbite_feature_enabled( 'enable_table_ordering' ) ) : ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=tables' ) ); ?>"
		   class="nav-tab <?php echo 'tables' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Tische', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=reservations' ) ); ?>"
		   class="nav-tab <?php echo 'reservations' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Reservierungen', 'libre-bite' ); ?>
		</a>
		<?php endif; ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=settings' ) ); ?>"
		   class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Einstellungen', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=support' ) ); ?>"
		   class="nav-tab <?php echo 'support' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Support', 'libre-bite' ); ?>
		</a>
		<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=debug' ) ); ?>"
		   class="nav-tab <?php echo 'debug' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Debug-Info', 'libre-bite' ); ?>
		</a>
		<?php endif; ?>
	</nav>

	<div class="lbite-help-content">
		<?php
		switch ( $active_tab ) {
			case 'orders':
				include __DIR__ . '/help-partials/orders.php';
				break;
			case 'products':
				include __DIR__ . '/help-partials/products.php';
				break;
			case 'locations':
				include __DIR__ . '/help-partials/locations.php';
				break;
			case 'tables':
				if ( lbite_feature_enabled( 'enable_table_ordering' ) ) {
					include __DIR__ . '/help-partials/tables.php';
				}
				break;
			case 'reservations':
				if ( lbite_feature_enabled( 'enable_table_ordering' ) ) {
					include __DIR__ . '/help-partials/reservations.php';
				}
				break;
			case 'settings':
				include __DIR__ . '/help-partials/settings.php';
				break;
			case 'support':
				include __DIR__ . '/help-partials/support.php';
				break;
			case 'debug':
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && current_user_can( 'manage_options' ) ) {
					$lbite_is_tab = true;
					include LBITE_PLUGIN_DIR . 'templates/admin/debug-info.php';
				}
				break;
			default:
				// Übersicht
				?>
				<div class="lbite-help-grid">
					<!-- Schnellstart -->
					<div class="lbite-help-card lbite-help-quickstart">
						<h2><span class="dashicons dashicons-flag"></span> <?php esc_html_e( 'Administrator-Übersicht', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Als Administrator können Sie Bestellungen verwalten, Produkte konfigurieren, Standorte einrichten und Einstellungen anpassen.', 'libre-bite' ); ?></p>

						<div class="lbite-quick-links">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-order-board' ) ); ?>" class="button button-primary">
								<span class="dashicons dashicons-clipboard"></span>
								<?php esc_html_e( 'Bestellübersicht', 'libre-bite' ); ?>
							</a>
							<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lbite_location' ) ); ?>" class="button">
								<span class="dashicons dashicons-location"></span>
								<?php esc_html_e( 'Standorte', 'libre-bite' ); ?>
							</a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-settings' ) ); ?>" class="button">
								<span class="dashicons dashicons-admin-settings"></span>
								<?php esc_html_e( 'Einstellungen', 'libre-bite' ); ?>
							</a>
						</div>

						<hr style="margin: 16px 0;">
						<p style="margin: 0;">
							<span class="dashicons dashicons-welcome-learn-more" style="vertical-align: middle;"></span>
							<button type="button" id="lbite-restart-onboarding" class="button button-secondary" style="margin-left: 4px;">
								<?php esc_html_e( 'Ersteinrichtung erneut öffnen', 'libre-bite' ); ?>
							</button>
							<span id="lbite-onboarding-status" style="margin-left: 8px; color: #646970;"></span>
						</p>
					</div>

					<!-- Bestellungen -->
					<div class="lbite-help-card">
						<h2><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Bestellungen', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Verwalten Sie eingehende Bestellungen mit dem Kanban-Board oder dem Kassensystem.', 'libre-bite' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Bestellstatus per Drag & Drop ändern', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Sound-Benachrichtigung bei neuen Bestellungen', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Bestelldetails und Kundeninformationen einsehen', 'libre-bite' ); ?></li>
						</ul>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=orders' ) ); ?>" class="button">
							<?php esc_html_e( 'Mehr erfahren', 'libre-bite' ); ?>
						</a>
					</div>

					<!-- Produkte -->
					<div class="lbite-help-card">
						<h2><span class="dashicons dashicons-products"></span> <?php esc_html_e( 'Produkte', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Konfigurieren Sie Produkt-Optionen und Zusätze für Ihre Bestellungen.', 'libre-bite' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Produkt-Optionen (Add-ons) erstellen', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Optionen mit Aufpreisen versehen', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Optionen Produkten zuweisen', 'libre-bite' ); ?></li>
						</ul>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=products' ) ); ?>" class="button">
							<?php esc_html_e( 'Mehr erfahren', 'libre-bite' ); ?>
						</a>
					</div>

					<!-- Standorte -->
					<div class="lbite-help-card">
						<h2><span class="dashicons dashicons-location"></span> <?php esc_html_e( 'Standorte', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Verwalten Sie Ihre Standorte mit Öffnungszeiten und Kontaktdaten.', 'libre-bite' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Standorte anlegen und bearbeiten', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Öffnungszeiten pro Tag festlegen', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Adresse und Kontaktdaten hinterlegen', 'libre-bite' ); ?></li>
						</ul>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=locations' ) ); ?>" class="button">
							<?php esc_html_e( 'Mehr erfahren', 'libre-bite' ); ?>
						</a>
					</div>

					<!-- Tische -->
					<?php if ( lbite_feature_enabled( 'enable_table_ordering' ) ) : ?>
					<div class="lbite-help-card">
						<h2><span class="dashicons dashicons-grid-view"></span> <?php esc_html_e( 'Tische', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'QR-Code-basierte Tischbestellung – Gäste scannen und bestellen direkt am Tisch.', 'libre-bite' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Tische anlegen und QR-Codes generieren', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Sitzplätze pro Tisch konfigurieren', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Mehrere Tische auf einmal erstellen', 'libre-bite' ); ?></li>
						</ul>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=tables' ) ); ?>" class="button">
							<?php esc_html_e( 'Mehr erfahren', 'libre-bite' ); ?>
						</a>
					</div>
					

					<!-- Reservierungen -->
					<div class="lbite-help-card">
						<h2><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e( 'Reservierungen', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Tischanfragen über ein Frontend-Formular entgegennehmen, verwalten und per E-Mail bestätigen.', 'libre-bite' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Shortcode [lbite_reservation_form] einbinden', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Anfragen im Backend verwalten und Status setzen', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Automatische E-Mail an Gast und Admin', 'libre-bite' ); ?></li>
						</ul>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=reservations' ) ); ?>" class="button">
							<?php esc_html_e( 'Mehr erfahren', 'libre-bite' ); ?>
						</a>
					</div>
					<?php endif; ?>

				<!-- Einstellungen -->
					<div class="lbite-help-card">
						<h2><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Einstellungen', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Passen Sie das Libre Bite an Ihre Bedürfnisse an.', 'libre-bite' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Checkout-Felder konfigurieren', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Trinkgeld-Optionen anpassen', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Vorbereitungszeit und Zeitslots einstellen', 'libre-bite' ); ?></li>
						</ul>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=settings' ) ); ?>" class="button">
							<?php esc_html_e( 'Mehr erfahren', 'libre-bite' ); ?>
						</a>
					</div>

					<!-- Support -->
					<div class="lbite-help-card lbite-help-support">
						<h2><span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Support', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Nicht fündig geworden? Ich helfe dir gerne weiter.', 'libre-bite' ); ?></p>

						<div class="lbite-support-info">
							<?php if ( $support_hours ) : ?>
								<p>
									<span class="dashicons dashicons-clock"></span>
									<?php echo esc_html( $support_hours ); ?>
								</p>
							<?php endif; ?>

							<?php if ( $billing_note ) : ?>
								<p class="lbite-billing-note">
									<span class="dashicons dashicons-info"></span>
									<?php echo esc_html( $billing_note ); ?>
								</p>
							<?php endif; ?>

							<?php if ( $support_email ) : ?>
								<p>
									<span class="dashicons dashicons-email"></span>
									<a href="mailto:<?php echo esc_attr( $support_email ); ?>"><?php echo esc_html( $support_email ); ?></a>
								</p>
							<?php endif; ?>

							<?php if ( $support_phone ) : ?>
								<p>
									<span class="dashicons dashicons-phone"></span>
									<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $support_phone ) ); ?>"><?php echo esc_html( $support_phone ); ?></a>
								</p>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<?php
				break;
		}
		?>
	</div>
</div>

<?php ob_start(); ?>
jQuery(function($) {
	$('#lbite-restart-onboarding').on('click', function() {
		var $btn = $(this);
		var $status = $('#lbite-onboarding-status');
		$btn.prop('disabled', true);
		$status.text('<?php echo esc_js( __( 'Wird geöffnet…', 'libre-bite' ) ); ?>');
		$.post(ajaxurl, {
			action: 'lbite_restart_onboarding',
			nonce: lbiteAdmin.nonce
		}, function(response) {
			if (response.success && response.data.redirect) {
				window.location.href = response.data.redirect;
			} else {
				$btn.prop('disabled', false);
				$status.text('<?php echo esc_js( __( 'Fehler – bitte Seite neu laden.', 'libre-bite' ) ); ?>');
			}
		}).fail(function() {
			$btn.prop('disabled', false);
			$status.text('<?php echo esc_js( __( 'Fehler – bitte Seite neu laden.', 'libre-bite' ) ); ?>');
		});
	});
});
<?php wp_add_inline_script( 'lbite-admin', ob_get_clean() ); ?>
