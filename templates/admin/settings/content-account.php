<?php
/**
 * Einstellungen: Beiträge & Mein Konto
 *
 * Zwei unabhängige, standardmässig deaktivierte Schalter (Audit 26.09.2026,
 * AP-01). Vorher blendete das Customizations-Modul WordPress-Beiträge und
 * Teile von "Mein Konto" immer aus, ohne dass der Shopbetreiber das
 * beeinflussen konnte.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_disable_blog_posts  = get_option( 'lbite_disable_blog_posts', false );
$lbite_simplify_my_account = get_option( 'lbite_simplify_my_account', false );
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Optional adjustments for shops that do not use the WordPress blog or certain WooCommerce account features. Both are off by default and do not delete any existing content.', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="content_account">

	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Hide Blog Posts', 'libre-bite' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="lbite_disable_blog_posts" value="1" <?php checked( $lbite_disable_blog_posts ); ?>>
					<?php esc_html_e( 'Hide WordPress posts in the frontend and in the admin menu.', 'libre-bite' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'For websites without a blog: hides posts in the frontend and admin. Existing posts are not deleted.', 'libre-bite' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Simplify My Account', 'libre-bite' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="lbite_simplify_my_account" value="1" <?php checked( $lbite_simplify_my_account ); ?>>
					<?php esc_html_e( 'Remove "Downloads" and "Addresses" from My Account.', 'libre-bite' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Removes "Downloads" and "Addresses" from My Account if you sell no digital products and offer no shipping.', 'libre-bite' ); ?>
				</p>
			</td>
		</tr>
	</table>
	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
