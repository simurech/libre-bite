<?php
/**
 * Support-Seite
 *
 * Für alle Rollen mit Zugang zu Libre Bite (lbite_view_dashboard): zeigt die
 * unter Einstellungen → Support hinterlegten Kontaktdaten – also die Stelle,
 * die diesen Betrieb betreut (Inhaber, IT, Agentur), nicht automatisch den
 * Plugin-Hersteller.
 *
 * Administratoren sehen zusätzlich einen klar gekennzeichneten Bereich: wer
 * die Seite sieht, woher die Angaben stammen, Systemangaben für Anfragen und
 * die Kanäle zum Libre-Bite-Support selbst.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-Datei, wird innerhalb einer Klassen-Methode via include geladen; Variablen befinden sich im Methoden-Scope, nicht im globalen Namespace.

$support_settings  = get_option( 'lbite_support_settings', array() );
$support_email_set = ! empty( $support_settings['support_email'] );
$support_email     = $support_email_set ? $support_settings['support_email'] : get_option( 'admin_email' );
$support_phone     = isset( $support_settings['support_phone'] ) ? $support_settings['support_phone'] : '';
$support_hours     = isset( $support_settings['support_hours'] ) ? $support_settings['support_hours'] : '';
$billing_note      = isset( $support_settings['support_billing_note'] ) ? $support_settings['support_billing_note'] : '';
$custom_text       = isset( $support_settings['support_custom_text'] ) ? $support_settings['support_custom_text'] : '';
$is_admin_user     = current_user_can( 'manage_options' );
?>
<div class="wrap lbite-admin-wrap lbite-support-page">
	<h1><?php esc_html_e( 'Support', 'libre-bite' ); ?></h1>
	<p class="description lbite-settings-intro">
		<?php esc_html_e( 'Something does not work as expected, or you have a question about orders, the POS or reservations? Contact the person who looks after Libre Bite in your business.', 'libre-bite' ); ?>
	</p>

	<div class="lbite-support-grid">
		<div class="lbite-settings-card">
			<div class="lbite-support-preview">
				<div class="lbite-support-preview-content">
					<h2><?php esc_html_e( 'Your contact', 'libre-bite' ); ?></h2>
					<p>
						<span class="dashicons dashicons-email" aria-hidden="true"></span>
						<a href="mailto:<?php echo esc_attr( $support_email ); ?>"><?php echo esc_html( $support_email ); ?></a>
					</p>
					<?php if ( $support_phone ) : ?>
						<p>
							<span class="dashicons dashicons-phone" aria-hidden="true"></span>
							<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $support_phone ) ); ?>"><?php echo esc_html( $support_phone ); ?></a>
						</p>
					<?php endif; ?>
					<?php if ( $support_hours ) : ?>
						<p>
							<span class="dashicons dashicons-clock" aria-hidden="true"></span>
							<span><?php echo esc_html( $support_hours ); ?></span>
						</p>
					<?php endif; ?>
					<?php if ( $billing_note ) : ?>
						<p class="lbite-billing-note">
							<span class="dashicons dashicons-info" aria-hidden="true"></span>
							<span><?php echo esc_html( $billing_note ); ?></span>
						</p>
					<?php endif; ?>
					<?php if ( $custom_text ) : ?>
						<div><?php echo wp_kses_post( wpautop( $custom_text ) ); ?></div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="lbite-settings-card">
			<h2><?php esc_html_e( 'So we can help quickly', 'libre-bite' ); ?></h2>
			<p><?php esc_html_e( 'Please include in your message:', 'libre-bite' ); ?></p>
			<ul class="lbite-support-list">
				<li><?php esc_html_e( 'What you wanted to do and what happened instead', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'The page where it happened (for example POS, Order Overview, checkout)', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'The order number and location, if an order is affected', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'A screenshot, if possible', 'libre-bite' ); ?></li>
			</ul>
			<p class="description"><?php esc_html_e( 'Tip: many display problems are solved by reloading the page. If several devices are affected, check the internet connection first.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<?php if ( $is_admin_user ) : ?>
		<?php
		global $wp_version;
		$lbite_wc_version = defined( 'WC_VERSION' ) ? WC_VERSION : '–';
		?>
		<div class="lbite-settings-card lbite-support-admin">
			<h2>
				<?php esc_html_e( 'For administrators', 'libre-bite' ); ?>
				<span class="lbite-support-admin-badge"><?php esc_html_e( 'Only visible to administrators', 'libre-bite' ); ?></span>
			</h2>

			<?php if ( ! $support_email_set ) : ?>
				<div class="notice notice-warning inline">
					<p><?php esc_html_e( 'No support contact is set yet. Your staff currently sees the WordPress administrator email address.', 'libre-bite' ); ?></p>
				</div>
			<?php endif; ?>

			<h3><?php esc_html_e( 'Who sees this page?', 'libre-bite' ); ?></h3>
			<p><?php esc_html_e( 'Everyone who works with Libre Bite: staff, location managers, shop managers and administrators. They all see the same contact details above. Only this box is shown to administrators alone.', 'libre-bite' ); ?></p>
			<p>
				<?php
				printf(
					/* translators: %s: link to the Support settings tab */
					esc_html__( 'You set the contact details, support hours and conditions under %s – for example your own phone number, your IT partner or the agency that set up your shop.', 'libre-bite' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=lbite-settings&tab=support' ) ) . '">' . esc_html__( 'Settings → Support', 'libre-bite' ) . '</a>'
				);
				?>
			</p>

			<h3><?php esc_html_e( 'Help with Libre Bite itself', 'libre-bite' ); ?></h3>
			<ul class="lbite-support-list">
				<li>
					<?php
					printf(
						/* translators: %s: link to the WordPress.org support forum */
						esc_html__( 'Questions about the free version: %s', 'libre-bite' ),
						'<a href="https://wordpress.org/support/plugin/libre-bite/" target="_blank" rel="noopener">' . esc_html__( 'WordPress.org support forum', 'libre-bite' ) . '</a>'
					);
					?>
				</li>
				<li>
					<?php
					printf(
						/* translators: %s: link to the developer website */
						esc_html__( 'Pro licence, setup or managed hosting: %s', 'libre-bite' ),
						'<a href="https://pulacha-labs.ch/" target="_blank" rel="noopener">Pulacha Labs</a>'
					);
					?>
				</li>
			</ul>

			<h3><?php esc_html_e( 'System details for support requests', 'libre-bite' ); ?></h3>
			<p class="lbite-support-versions">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: Libre Bite version, 2: WordPress version, 3: WooCommerce version, 4: PHP version */
						__( 'Libre Bite %1$s · WordPress %2$s · WooCommerce %3$s · PHP %4$s', 'libre-bite' ),
						LBITE_VERSION,
						$wp_version,
						$lbite_wc_version,
						PHP_VERSION
					)
				);
				?>
			</p>
			<p class="description">
				<?php
				printf(
					/* translators: %s: link to the Developer settings tab */
					esc_html__( 'The full debug information (with error log) is under %s.', 'libre-bite' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=lbite-settings&tab=developer' ) ) . '">' . esc_html__( 'Settings → Developer', 'libre-bite' ) . '</a>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>
</div>
