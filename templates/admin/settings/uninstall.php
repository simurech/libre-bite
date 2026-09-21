<?php
/**
 * Einstellungen: Deinstallation
 *
 * Ausgelagert aus dem Inline-Block in settings-tabbed.php (weitere Aufteilung
 * nach v3.2.0). Save-Case 'data' im zentralen Switch bleibt unverändert.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_delete_data = get_option( 'lbite_delete_data_on_uninstall', false );
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Controls what happens to your data when the plugin is deleted.', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="data">

	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Delete Data on Uninstall', 'libre-bite' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="lbite_delete_data_on_uninstall" value="1" <?php checked( $lbite_delete_data ); ?>>
					<?php esc_html_e( 'Completely delete all plugin data on uninstall', 'libre-bite' ); ?>
				</label>
				<div class="notice notice-warning inline">
					<p>
						<strong><?php esc_html_e( 'Important:', 'libre-bite' ); ?></strong>
						<?php esc_html_e( 'This option will permanently delete all locations, product options, settings, and order metadata!', 'libre-bite' ); ?>
					</p>
				</div>
			</td>
		</tr>
	</table>
	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
