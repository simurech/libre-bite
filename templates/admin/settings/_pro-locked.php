<?php
/**
 * Partial: Pro-Feature gesperrt (Fallback für Free-Build)
 *
 * Wird vom Tab-Router geladen, wenn ein Pro-Tab ohne gültige Lizenz aufgerufen wird.
 * Diese Datei wird NICHT als __premium_only markiert und ist in beiden Builds vorhanden.
 *
 * Erwartet: $lbite_locked_title (string), $lbite_locked_description (string, optional)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_upgrade_url = function_exists( 'lbite_freemius' ) ? lbite_freemius()->get_upgrade_url() : 'https://wordpress.org/plugins/libre-bite/';
?>
<div class="lbite-pro-locked-screen" style="text-align: center; padding: 60px 20px; max-width: 520px; margin: 0 auto;">
	<div style="font-size: 48px; margin-bottom: 16px;">🔒</div>
	<h2 style="margin-bottom: 8px;"><?php echo esc_html( $lbite_locked_title ?? __( 'Pro Feature', 'libre-bite' ) ); ?></h2>
	<p style="color: #646970; margin-bottom: 24px;">
		<?php
		echo esc_html(
			$lbite_locked_description ?? __( 'This feature is available in Libre Bite Pro. Start your free 7-day trial to unlock it – no credit card required.', 'libre-bite' )
		);
		?>
	</p>
	<a href="<?php echo esc_url( $lbite_upgrade_url ); ?>" class="button button-primary" style="font-size: 14px; padding: 8px 20px; height: auto; line-height: 1.5;">
		<?php esc_html_e( 'Upgrade to Pro', 'libre-bite' ); ?> →
	</a>
	<p style="margin-top: 14px; font-size: 12px; color: #646970;">
		<?php esc_html_e( '7-day free trial · No credit card required', 'libre-bite' ); ?>
	</p>
</div>
