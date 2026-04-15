<?php
/**
 * Hilfe-Partial: Support
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
?>
<div class="lbite-help-section">
	<h2><?php esc_html_e( 'Contact Support', 'libre-bite' ); ?></h2>

	<div class="lbite-support-card">
		<div class="lbite-support-info">
			<?php if ( $support_email ) : ?>
				<div class="lbite-support-item">
					<span class="dashicons dashicons-email"></span>
					<div>
						<strong><?php esc_html_e( 'Email', 'libre-bite' ); ?></strong>
						<a href="mailto:<?php echo esc_attr( $support_email ); ?>"><?php echo esc_html( $support_email ); ?></a>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $support_phone ) : ?>
				<div class="lbite-support-item">
					<span class="dashicons dashicons-phone"></span>
					<div>
						<strong><?php esc_html_e( 'Phone', 'libre-bite' ); ?></strong>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $support_phone ) ); ?>"><?php echo esc_html( $support_phone ); ?></a>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $support_hours ) : ?>
				<div class="lbite-support-item">
					<span class="dashicons dashicons-clock"></span>
					<div>
						<strong><?php esc_html_e( 'Availability', 'libre-bite' ); ?></strong>
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
		<h3><?php esc_html_e( 'Before Contacting Us', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'To help you quickly, please prepare the following information:', 'libre-bite' ); ?></p>

		<ul>
			<li><?php esc_html_e( 'Description of the issue', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Steps to reproduce the error', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Screenshots (if relevant)', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Error messages (if any)', 'libre-bite' ); ?></li>
		</ul>
	</div>

	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Common Issues', 'libre-bite' ); ?></h3>

		<div class="lbite-faq-item">
			<h4><?php esc_html_e( 'No order notifications?', 'libre-bite' ); ?></h4>
			<p><?php esc_html_e( 'Check in the settings whether sound notifications are enabled. Make sure the browser allows sound playback.', 'libre-bite' ); ?></p>
		</div>

		<div class="lbite-faq-item">
			<h4><?php esc_html_e( 'Location not showing?', 'libre-bite' ); ?></h4>
			<p><?php esc_html_e( 'Make sure the location is published and opening hours are configured.', 'libre-bite' ); ?></p>
		</div>

		<div class="lbite-faq-item">
			<h4><?php esc_html_e( 'Product options not appearing?', 'libre-bite' ); ?></h4>
			<p><?php esc_html_e( 'Check whether the option is published and assigned to the product.', 'libre-bite' ); ?></p>
		</div>
	</div>
</div>

