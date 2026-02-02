<?php
/**
 * Hilfe-Partial: Support
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
?>
<div class="lb-help-section">
	<h2><?php esc_html_e( 'Support kontaktieren', 'libre-bite' ); ?></h2>

	<div class="lb-support-card">
		<div class="lb-support-info">
			<?php if ( $support_email ) : ?>
				<div class="lb-support-item">
					<span class="dashicons dashicons-email"></span>
					<div>
						<strong><?php esc_html_e( 'E-Mail', 'libre-bite' ); ?></strong>
						<a href="mailto:<?php echo esc_attr( $support_email ); ?>"><?php echo esc_html( $support_email ); ?></a>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $support_phone ) : ?>
				<div class="lb-support-item">
					<span class="dashicons dashicons-phone"></span>
					<div>
						<strong><?php esc_html_e( 'Telefon', 'libre-bite' ); ?></strong>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $support_phone ) ); ?>"><?php echo esc_html( $support_phone ); ?></a>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $support_hours ) : ?>
				<div class="lb-support-item">
					<span class="dashicons dashicons-clock"></span>
					<div>
						<strong><?php esc_html_e( 'Erreichbarkeit', 'libre-bite' ); ?></strong>
						<span><?php echo esc_html( $support_hours ); ?></span>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $billing_note ) : ?>
			<div class="lb-billing-notice">
				<span class="dashicons dashicons-info"></span>
				<p><?php echo esc_html( $billing_note ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( $custom_text ) : ?>
			<div class="lb-custom-text">
				<?php echo wp_kses_post( wpautop( $custom_text ) ); ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="lb-help-article">
		<h3><?php esc_html_e( 'Bevor Sie uns kontaktieren', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Um Ihnen schnell helfen zu können, bereiten Sie bitte folgende Informationen vor:', 'libre-bite' ); ?></p>

		<ul>
			<li><?php esc_html_e( 'Beschreibung des Problems', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Schritte zum Reproduzieren des Fehlers', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Screenshots (falls relevant)', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Fehlermeldungen (falls vorhanden)', 'libre-bite' ); ?></li>
		</ul>
	</div>

	<div class="lb-help-article">
		<h3><?php esc_html_e( 'Häufige Probleme', 'libre-bite' ); ?></h3>

		<div class="lb-faq-item">
			<h4><?php esc_html_e( 'Keine Bestellbenachrichtigungen?', 'libre-bite' ); ?></h4>
			<p><?php esc_html_e( 'Prüfen Sie in den Einstellungen, ob Sound-Benachrichtigungen aktiviert sind. Stellen Sie sicher, dass der Browser Sound-Wiedergabe erlaubt.', 'libre-bite' ); ?></p>
		</div>

		<div class="lb-faq-item">
			<h4><?php esc_html_e( 'Standort wird nicht angezeigt?', 'libre-bite' ); ?></h4>
			<p><?php esc_html_e( 'Stellen Sie sicher, dass der Standort veröffentlicht ist und Öffnungszeiten eingetragen sind.', 'libre-bite' ); ?></p>
		</div>

		<div class="lb-faq-item">
			<h4><?php esc_html_e( 'Produkt-Optionen erscheinen nicht?', 'libre-bite' ); ?></h4>
			<p><?php esc_html_e( 'Überprüfen Sie, ob die Option veröffentlicht und dem Produkt zugewiesen ist.', 'libre-bite' ); ?></p>
		</div>
	</div>
</div>

<style>
.lb-help-section {
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 4px;
	padding: 20px;
}

.lb-help-article {
	margin-top: 30px;
	padding-top: 30px;
	border-top: 1px solid #eee;
}

.lb-help-article h3 {
	margin-top: 0;
}

.lb-support-card {
	background: #f6f7f7;
	border-radius: 4px;
	padding: 20px;
}

.lb-support-info {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
	gap: 20px;
}

.lb-support-item {
	display: flex;
	align-items: flex-start;
	gap: 12px;
}

.lb-support-item .dashicons {
	color: #2271b1;
	font-size: 24px;
	width: 24px;
	height: 24px;
}

.lb-support-item strong {
	display: block;
	margin-bottom: 4px;
}

.lb-support-item a {
	text-decoration: none;
}

.lb-support-item a:hover {
	text-decoration: underline;
}

.lb-billing-notice {
	display: flex;
	align-items: flex-start;
	gap: 10px;
	background: #fff8e5;
	padding: 12px;
	border-radius: 4px;
	border-left: 3px solid #dba617;
	margin-top: 20px;
}

.lb-billing-notice .dashicons {
	color: #dba617;
	flex-shrink: 0;
}

.lb-billing-notice p {
	margin: 0;
}

.lb-custom-text {
	margin-top: 20px;
	padding-top: 20px;
	border-top: 1px solid #ddd;
}

.lb-faq-item {
	margin-bottom: 20px;
	padding-bottom: 20px;
	border-bottom: 1px solid #eee;
}

.lb-faq-item:last-child {
	margin-bottom: 0;
	padding-bottom: 0;
	border-bottom: none;
}

.lb-faq-item h4 {
	margin-top: 0;
	margin-bottom: 8px;
	color: #1d2327;
}

.lb-faq-item p {
	margin: 0;
	color: #50575e;
}
</style>
