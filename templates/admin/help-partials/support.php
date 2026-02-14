<?php
/**
 * Hilfe-Partial: Support
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
<div class="lbite-help-section">
	<h2><?php esc_html_e( 'Support kontaktieren', 'libre-bite' ); ?></h2>

	<div class="lbite-support-card">
		<div class="lbite-support-info">
			<?php if ( $support_email ) : ?>
				<div class="lbite-support-item">
					<span class="dashicons dashicons-email"></span>
					<div>
						<strong><?php esc_html_e( 'E-Mail', 'libre-bite' ); ?></strong>
						<a href="mailto:<?php echo esc_attr( $support_email ); ?>"><?php echo esc_html( $support_email ); ?></a>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $support_phone ) : ?>
				<div class="lbite-support-item">
					<span class="dashicons dashicons-phone"></span>
					<div>
						<strong><?php esc_html_e( 'Telefon', 'libre-bite' ); ?></strong>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $support_phone ) ); ?>"><?php echo esc_html( $support_phone ); ?></a>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $support_hours ) : ?>
				<div class="lbite-support-item">
					<span class="dashicons dashicons-clock"></span>
					<div>
						<strong><?php esc_html_e( 'Erreichbarkeit', 'libre-bite' ); ?></strong>
						<span><?php echo esc_html( $support_hours ); ?></span>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $billing_note ) : ?>
			<div class="lbite-billing-notice">
				<span class="dashicons dashicons-info"></span>
				<p><?php echo esc_html( $billing_note ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( $custom_text ) : ?>
			<div class="lbite-custom-text">
				<?php echo wp_kses_post( wpautop( $custom_text ) ); ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Bevor Sie uns kontaktieren', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Um Ihnen schnell helfen zu können, bereiten Sie bitte folgende Informationen vor:', 'libre-bite' ); ?></p>

		<ul>
			<li><?php esc_html_e( 'Beschreibung des Problems', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Schritte zum Reproduzieren des Fehlers', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Screenshots (falls relevant)', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Fehlermeldungen (falls vorhanden)', 'libre-bite' ); ?></li>
		</ul>
	</div>

	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Häufige Probleme', 'libre-bite' ); ?></h3>

		<div class="lbite-faq-item">
			<h4><?php esc_html_e( 'Keine Bestellbenachrichtigungen?', 'libre-bite' ); ?></h4>
			<p><?php esc_html_e( 'Prüfen Sie in den Einstellungen, ob Sound-Benachrichtigungen aktiviert sind. Stellen Sie sicher, dass der Browser Sound-Wiedergabe erlaubt.', 'libre-bite' ); ?></p>
		</div>

		<div class="lbite-faq-item">
			<h4><?php esc_html_e( 'Standort wird nicht angezeigt?', 'libre-bite' ); ?></h4>
			<p><?php esc_html_e( 'Stellen Sie sicher, dass der Standort veröffentlicht ist und Öffnungszeiten eingetragen sind.', 'libre-bite' ); ?></p>
		</div>

		<div class="lbite-faq-item">
			<h4><?php esc_html_e( 'Produkt-Optionen erscheinen nicht?', 'libre-bite' ); ?></h4>
			<p><?php esc_html_e( 'Überprüfen Sie, ob die Option veröffentlicht und dem Produkt zugewiesen ist.', 'libre-bite' ); ?></p>
		</div>
	</div>
</div>

