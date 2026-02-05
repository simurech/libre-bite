<?php
/**
 * Hilfe-Seite für Administratoren (lb_admin)
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

$support_settings = get_option( 'lb_support_settings', array() );
$support_email    = isset( $support_settings['support_email'] ) ? $support_settings['support_email'] : get_option( 'admin_email' );
$support_phone    = isset( $support_settings['support_phone'] ) ? $support_settings['support_phone'] : '';
$support_hours    = isset( $support_settings['support_hours'] ) ? $support_settings['support_hours'] : '';
$billing_note     = isset( $support_settings['support_billing_note'] ) ? $support_settings['support_billing_note'] : '';
$custom_text      = isset( $support_settings['support_custom_text'] ) ? $support_settings['support_custom_text'] : '';

// Aktiver Tab
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lese-Parameter für Tab-Navigation.
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview';
?>
<div class="wrap lb-help-wrap">
	<h1><?php esc_html_e( 'Hilfe & Support', 'libre-bite' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=overview' ) ); ?>"
		   class="nav-tab <?php echo 'overview' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Übersicht', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=orders' ) ); ?>"
		   class="nav-tab <?php echo 'orders' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Bestellungen', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=products' ) ); ?>"
		   class="nav-tab <?php echo 'products' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Produkte', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=locations' ) ); ?>"
		   class="nav-tab <?php echo 'locations' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Standorte', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=settings' ) ); ?>"
		   class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Einstellungen', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=support' ) ); ?>"
		   class="nav-tab <?php echo 'support' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Support', 'libre-bite' ); ?>
		</a>
	</nav>

	<div class="lb-help-content">
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
			case 'settings':
				include __DIR__ . '/help-partials/settings.php';
				break;
			case 'support':
				include __DIR__ . '/help-partials/support.php';
				break;
			default:
				// Übersicht
				?>
				<div class="lb-help-grid">
					<!-- Schnellstart -->
					<div class="lb-help-card lb-help-quickstart">
						<h2><span class="dashicons dashicons-flag"></span> <?php esc_html_e( 'Administrator-Übersicht', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Als Administrator können Sie Bestellungen verwalten, Produkte konfigurieren, Standorte einrichten und Einstellungen anpassen.', 'libre-bite' ); ?></p>

						<div class="lb-quick-links">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-order-board' ) ); ?>" class="button button-primary">
								<span class="dashicons dashicons-clipboard"></span>
								<?php esc_html_e( 'Bestellübersicht', 'libre-bite' ); ?>
							</a>
							<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lb_location' ) ); ?>" class="button">
								<span class="dashicons dashicons-location"></span>
								<?php esc_html_e( 'Standorte', 'libre-bite' ); ?>
							</a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-settings' ) ); ?>" class="button">
								<span class="dashicons dashicons-admin-settings"></span>
								<?php esc_html_e( 'Einstellungen', 'libre-bite' ); ?>
							</a>
						</div>
					</div>

					<!-- Bestellungen -->
					<div class="lb-help-card">
						<h2><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Bestellungen', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Verwalten Sie eingehende Bestellungen mit dem Kanban-Board oder dem Kassensystem.', 'libre-bite' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Bestellstatus per Drag & Drop ändern', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Sound-Benachrichtigung bei neuen Bestellungen', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Bestelldetails und Kundeninformationen einsehen', 'libre-bite' ); ?></li>
						</ul>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=orders' ) ); ?>" class="button">
							<?php esc_html_e( 'Mehr erfahren', 'libre-bite' ); ?>
						</a>
					</div>

					<!-- Produkte -->
					<div class="lb-help-card">
						<h2><span class="dashicons dashicons-products"></span> <?php esc_html_e( 'Produkte', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Konfigurieren Sie Produkt-Optionen und Zusätze für Ihre Bestellungen.', 'libre-bite' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Produkt-Optionen (Add-ons) erstellen', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Optionen mit Aufpreisen versehen', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Optionen Produkten zuweisen', 'libre-bite' ); ?></li>
						</ul>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=products' ) ); ?>" class="button">
							<?php esc_html_e( 'Mehr erfahren', 'libre-bite' ); ?>
						</a>
					</div>

					<!-- Standorte -->
					<div class="lb-help-card">
						<h2><span class="dashicons dashicons-location"></span> <?php esc_html_e( 'Standorte', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Verwalten Sie Ihre Standorte mit Öffnungszeiten und Kontaktdaten.', 'libre-bite' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Standorte anlegen und bearbeiten', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Öffnungszeiten pro Tag festlegen', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Adresse und Kontaktdaten hinterlegen', 'libre-bite' ); ?></li>
						</ul>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=locations' ) ); ?>" class="button">
							<?php esc_html_e( 'Mehr erfahren', 'libre-bite' ); ?>
						</a>
					</div>

					<!-- Einstellungen -->
					<div class="lb-help-card">
						<h2><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Einstellungen', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Passen Sie das Libre Bite an Ihre Bedürfnisse an.', 'libre-bite' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Checkout-Felder konfigurieren', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Trinkgeld-Optionen anpassen', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Vorbereitungszeit und Zeitslots einstellen', 'libre-bite' ); ?></li>
						</ul>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-help&tab=settings' ) ); ?>" class="button">
							<?php esc_html_e( 'Mehr erfahren', 'libre-bite' ); ?>
						</a>
					</div>

					<!-- Support -->
					<div class="lb-help-card lb-help-support">
						<h2><span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Support', 'libre-bite' ); ?></h2>

						<div class="lb-support-info">
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

							<?php if ( $support_hours ) : ?>
								<p>
									<span class="dashicons dashicons-clock"></span>
									<?php echo esc_html( $support_hours ); ?>
								</p>
							<?php endif; ?>
						</div>

						<?php if ( $billing_note ) : ?>
							<div class="lb-billing-notice">
								<span class="dashicons dashicons-info"></span>
								<?php echo esc_html( $billing_note ); ?>
							</div>
						<?php endif; ?>
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

.lb-help-card ul {
	margin-left: 20px;
}

.lb-help-card li {
	margin-bottom: 8px;
}

.lb-help-quickstart {
	grid-column: 1 / -1;
	background: linear-gradient(135deg, #f0f6fc 0%, #fff 100%);
	border-color: #2271b1;
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

.lb-help-support {
	background: #f6f7f7;
}

.lb-support-info p {
	display: flex;
	align-items: center;
	gap: 8px;
	margin: 10px 0;
}

.lb-support-info .dashicons {
	color: #2271b1;
}

.lb-billing-notice {
	display: flex;
	align-items: flex-start;
	gap: 8px;
	background: #fff8e5;
	padding: 12px;
	border-radius: 4px;
	border-left: 3px solid #dba617;
	margin-top: 15px;
}

.lb-billing-notice .dashicons {
	color: #dba617;
	flex-shrink: 0;
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
