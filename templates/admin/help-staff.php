<?php
/**
 * Hilfe-Seite für Personal (lbite_staff)
 *
 * Einfache Dokumentation für Grundfunktionen:
 * - Bestellungen bearbeiten
 * - POS nutzen
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$support_settings = get_option( 'lbite_support_settings', array() );
$support_email    = isset( $support_settings['support_email'] ) ? $support_settings['support_email'] : get_option( 'admin_email' );
$support_phone    = isset( $support_settings['support_phone'] ) ? $support_settings['support_phone'] : '';
$support_hours    = isset( $support_settings['support_hours'] ) ? $support_settings['support_hours'] : '';
$billing_note     = isset( $support_settings['support_billing_note'] ) ? $support_settings['support_billing_note'] : '';
$custom_text      = isset( $support_settings['support_custom_text'] ) ? $support_settings['support_custom_text'] : '';
?>
<div class="wrap lbite-help-wrap">
	<h1><?php esc_html_e( 'Hilfe & Support', 'libre-bite' ); ?></h1>

	<div class="lbite-help-grid">
		<!-- Schnellstart -->
		<div class="lbite-help-card lbite-help-quickstart">
			<h2><span class="dashicons dashicons-flag"></span> <?php esc_html_e( 'Schnellstart', 'libre-bite' ); ?></h2>
			<p><?php esc_html_e( 'Als Personal haben Sie Zugriff auf die Bestellübersicht und das Kassensystem.', 'libre-bite' ); ?></p>

			<div class="lbite-quick-links">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-order-board' ) ); ?>" class="button button-primary">
					<span class="dashicons dashicons-clipboard"></span>
					<?php esc_html_e( 'Bestellübersicht öffnen', 'libre-bite' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-pos' ) ); ?>" class="button">
					<span class="dashicons dashicons-cart"></span>
					<?php esc_html_e( 'Kassensystem öffnen', 'libre-bite' ); ?>
				</a>
			</div>
		</div>

		<!-- Bestellungen bearbeiten -->
		<div class="lbite-help-card">
			<h2><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Bestellungen bearbeiten', 'libre-bite' ); ?></h2>

			<h3><?php esc_html_e( 'Bestellstatus ändern', 'libre-bite' ); ?></h3>
			<ol>
				<li><?php esc_html_e( 'Öffnen Sie die Bestellübersicht', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'Ziehen Sie eine Bestellung in die nächste Spalte oder klicken Sie auf den Status-Button', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'Status-Ablauf: Eingehend → In Bearbeitung → Bereit → Abgeholt', 'libre-bite' ); ?></li>
			</ol>

			<h3><?php esc_html_e( 'Bestelldetails anzeigen', 'libre-bite' ); ?></h3>
			<p><?php esc_html_e( 'Klicken Sie auf eine Bestellung, um alle Details wie Produkte, Kundenname und Abholzeit zu sehen.', 'libre-bite' ); ?></p>

			<div class="lbite-help-tip">
				<span class="dashicons dashicons-lightbulb"></span>
				<p><?php esc_html_e( 'Tipp: Bei neuen Bestellungen ertönt ein Signalton. Stellen Sie sicher, dass der Ton aktiviert ist.', 'libre-bite' ); ?></p>
			</div>
		</div>

		<!-- Kassensystem (POS) -->
		<div class="lbite-help-card">
			<h2><span class="dashicons dashicons-cart"></span> <?php esc_html_e( 'Kassensystem (POS)', 'libre-bite' ); ?></h2>

			<h3><?php esc_html_e( 'Neue Bestellung erstellen', 'libre-bite' ); ?></h3>
			<ol>
				<li><?php esc_html_e( 'Wählen Sie den Standort aus (falls mehrere vorhanden)', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'Klicken Sie auf Produkte, um sie zum Warenkorb hinzuzufügen', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'Wählen Sie ggf. Varianten und Optionen aus', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'Geben Sie optional einen Kundennamen ein', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'Klicken Sie auf "Bestellung abschliessen"', 'libre-bite' ); ?></li>
			</ol>

			<h3><?php esc_html_e( 'Warenkorb bearbeiten', 'libre-bite' ); ?></h3>
			<ul>
				<li><?php esc_html_e( 'Menge ändern: +/- Buttons beim Produkt', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'Produkt entfernen: X-Button klicken', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'Warenkorb leeren: "Warenkorb leeren" Button', 'libre-bite' ); ?></li>
			</ul>
		</div>

		<!-- Support-Kontakt -->
		<div class="lbite-help-card lbite-help-support">
			<h2><span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Support kontaktieren', 'libre-bite' ); ?></h2>

			<p><?php esc_html_e( 'Bei Problemen oder Fragen wenden Sie sich an:', 'libre-bite' ); ?></p>

			<div class="lbite-support-info">
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
				<div class="lbite-billing-notice">
					<span class="dashicons dashicons-info"></span>
					<?php echo esc_html( $billing_note ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $custom_text ) : ?>
				<div class="lbite-custom-text">
					<?php echo wp_kses_post( wpautop( $custom_text ) ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

