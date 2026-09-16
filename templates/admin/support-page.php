<?php
/**
 * Support-Seite
 *
 * Zeigt ausschliesslich die unter Einstellungen → Support hinterlegten Kontaktdaten
 * und Konditionen an — für alle Rollen identisch, auch für Personal ohne Zugriff
 * auf die Einstellungen.
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
<div class="wrap lbite-admin-wrap">
	<h1><?php esc_html_e( 'Support', 'libre-bite' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Questions or a problem with an order? Get in touch using the details below.', 'libre-bite' ); ?></p>

	<div class="lbite-settings-card">
		<div class="lbite-support-preview">
			<div class="lbite-support-preview-content">
				<h3><?php esc_html_e( 'Contact Support', 'libre-bite' ); ?></h3>
				<p>
					<span class="dashicons dashicons-email"></span>
					<a href="mailto:<?php echo esc_attr( $support_email ); ?>"><?php echo esc_html( $support_email ); ?></a>
				</p>
				<?php if ( $support_phone ) : ?>
					<p>
						<span class="dashicons dashicons-phone"></span>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $support_phone ) ); ?>"><?php echo esc_html( $support_phone ); ?></a>
					</p>
				<?php endif; ?>
				<?php if ( $support_hours ) : ?>
					<p>
						<span class="dashicons dashicons-clock"></span>
						<span><?php echo esc_html( $support_hours ); ?></span>
					</p>
				<?php endif; ?>
				<?php if ( $billing_note ) : ?>
					<p class="lbite-billing-note">
						<span class="dashicons dashicons-info"></span>
						<span><?php echo esc_html( $billing_note ); ?></span>
					</p>
				<?php endif; ?>
				<?php if ( $custom_text ) : ?>
					<div><?php echo wp_kses_post( wpautop( $custom_text ) ); ?></div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<?php if ( current_user_can( 'lbite_manage_settings' ) ) : ?>
		<p class="description">
			<?php
			printf(
				/* translators: %s: link to the Support settings tab */
				esc_html__( 'You can edit these details under %s.', 'libre-bite' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=lbite-settings&tab=support' ) ) . '">' . esc_html__( 'Settings → Support', 'libre-bite' ) . '</a>'
			);
			?>
		</p>
	<?php endif; ?>
</div>
