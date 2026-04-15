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

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-Datei, wird innerhalb einer Klassen-Methode via include geladen; Variablen befinden sich im Methoden-Scope, nicht im globalen Namespace.

$support_settings = get_option( 'lbite_support_settings', array() );
$support_email    = isset( $support_settings['support_email'] ) ? $support_settings['support_email'] : get_option( 'admin_email' );
$support_phone    = isset( $support_settings['support_phone'] ) ? $support_settings['support_phone'] : '';
$support_hours    = isset( $support_settings['support_hours'] ) ? $support_settings['support_hours'] : '';
$billing_note     = isset( $support_settings['support_billing_note'] ) ? $support_settings['support_billing_note'] : '';
$custom_text      = isset( $support_settings['support_custom_text'] ) ? $support_settings['support_custom_text'] : '';
?>
<div class="wrap lbite-help-wrap">
	<h1><?php esc_html_e( 'Help & Support', 'libre-bite' ); ?></h1>

	<div class="lbite-help-grid">
		<!-- Schnellstart -->
		<div class="lbite-help-card lbite-help-quickstart">
			<h2><span class="dashicons dashicons-flag"></span> <?php esc_html_e( 'Quick Start', 'libre-bite' ); ?></h2>
			<p><?php esc_html_e( 'As staff, you have access to the order overview and the POS system.', 'libre-bite' ); ?></p>

			<div class="lbite-quick-links">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-order-board' ) ); ?>" class="button button-primary">
					<span class="dashicons dashicons-clipboard"></span>
					<?php esc_html_e( 'Open Order Overview', 'libre-bite' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-pos' ) ); ?>" class="button">
					<span class="dashicons dashicons-cart"></span>
					<?php esc_html_e( 'Open POS System', 'libre-bite' ); ?>
				</a>
			</div>
		</div>

		<!-- Bestellungen bearbeiten -->
		<div class="lbite-help-card">
			<h2><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Manage Orders', 'libre-bite' ); ?></h2>

			<h3><?php esc_html_e( 'Change Order Status', 'libre-bite' ); ?></h3>
			<ol>
				<li><?php esc_html_e( 'Open the order overview', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'Drag an order to the next column or click the status button', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'Status flow: Incoming → In Progress → Ready → Picked Up', 'libre-bite' ); ?></li>
			</ol>

			<h3><?php esc_html_e( 'View Order Details', 'libre-bite' ); ?></h3>
			<p><?php esc_html_e( 'Click on an order to view all details such as products, customer name, and pickup time.', 'libre-bite' ); ?></p>

			<div class="lbite-help-tip">
				<span class="dashicons dashicons-lightbulb"></span>
				<p><?php esc_html_e( 'Tip: A sound plays when new orders arrive. Make sure the sound is enabled.', 'libre-bite' ); ?></p>
			</div>
		</div>

		<!-- Kassensystem (POS) -->
		<div class="lbite-help-card">
			<h2><span class="dashicons dashicons-cart"></span> <?php esc_html_e( 'POS System', 'libre-bite' ); ?></h2>

			<h3><?php esc_html_e( 'Create New Order', 'libre-bite' ); ?></h3>
			<ol>
				<li><?php esc_html_e( 'Select the location (if multiple are available)', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'Click on products to add them to the cart', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'Select variants and options if applicable', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'Optionally enter a customer name', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'Click "Complete Order"', 'libre-bite' ); ?></li>
			</ol>

			<h3><?php esc_html_e( 'Edit Cart', 'libre-bite' ); ?></h3>
			<ul>
				<li><?php esc_html_e( 'Change quantity: +/- buttons next to the product', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'Remove product: click the X button', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'Clear cart: "Clear Cart" button', 'libre-bite' ); ?></li>
			</ul>
		</div>

		<!-- Support-Kontakt -->
		<div class="lbite-help-card lbite-help-support">
			<h2><span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Contact Support', 'libre-bite' ); ?></h2>

			<p><?php esc_html_e( 'For issues or questions, please contact:', 'libre-bite' ); ?></p>

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

