<?php
/**
 * Einstellungen: Abholanzeige (Pro)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_premium_allowed = lbite_has_pro_license();
$lbite_display_active  = lbite_feature_enabled( 'enable_pickup_display' ) && class_exists( 'LBite_Pickup_Display' ) && class_exists( 'LBite_Order_Dashboard' );
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'A full-screen page for a TV or monitor that shows guests which order numbers are being prepared and which are ready for pickup.', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="orders_display">

	<?php
	$lbite_toggle_key             = 'enable_pickup_display';
	$lbite_toggle_label           = __( 'Pickup Display', 'libre-bite' );
	$lbite_toggle_description     = __( 'Shows order numbers per Order Board column on a separate screen. Only numbers are shown – no names or amounts.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<?php if ( $lbite_display_active ) : ?>
		<?php
		$lbite_display_selected = array_column( LBite_Pickup_Display::get_display_columns(), 'key' );
		$lbite_display_labels   = (array) get_option( 'lbite_pickup_display_labels', array() );
		?>
		<input type="hidden" name="lbite_pickup_display_form" value="1">
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Columns', 'libre-bite' ); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e( 'Columns', 'libre-bite' ); ?></legend>
						<?php foreach ( LBite_Order_Dashboard::get_columns() as $lbite_col ) : ?>
							<?php $lbite_col_id = 'lbite-pd-col-' . sanitize_html_class( $lbite_col['key'] ); ?>
							<p class="lbite-pd-setting-row">
								<label>
									<input type="checkbox" name="lbite_pickup_display_columns[]" value="<?php echo esc_attr( $lbite_col['key'] ); ?>" <?php checked( in_array( $lbite_col['key'], $lbite_display_selected, true ) ); ?>>
									<?php echo esc_html( $lbite_col['label'] ); ?>
								</label>
								<label class="screen-reader-text" for="<?php echo esc_attr( $lbite_col_id ); ?>">
									<?php
									/* translators: %s: name of the Order Board column */
									echo esc_html( sprintf( __( 'Title on the display for %s', 'libre-bite' ), $lbite_col['label'] ) );
									?>
								</label>
								<input type="text" id="<?php echo esc_attr( $lbite_col_id ); ?>" class="regular-text" name="lbite_pickup_display_labels[<?php echo esc_attr( $lbite_col['key'] ); ?>]" value="<?php echo esc_attr( isset( $lbite_display_labels[ $lbite_col['key'] ] ) ? $lbite_display_labels[ $lbite_col['key'] ] : '' ); ?>" placeholder="<?php echo esc_attr( $lbite_col['label'] ); ?>">
							</p>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'Choose the columns to show and, if you like, a title for guests, e.g. "Being prepared" and "Ready for pickup". Columns that count as completed are highlighted.', 'libre-bite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="lbite-pd-minutes"><?php esc_html_e( 'Show completed orders for', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" id="lbite-pd-minutes" name="lbite_pickup_display_minutes" min="1" max="240" step="1" class="small-text" value="<?php echo esc_attr( (int) get_option( 'lbite_pickup_display_minutes', 15 ) ); ?>">
					<?php esc_html_e( 'minutes', 'libre-bite' ); ?>
					<p class="description"><?php esc_html_e( 'After that, a number disappears from columns that count as completed.', 'libre-bite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="lbite-pd-title"><?php esc_html_e( 'Title', 'libre-bite' ); ?></label></th>
				<td>
					<input type="text" id="lbite-pd-title" name="lbite_pickup_display_title" class="regular-text" value="<?php echo esc_attr( (string) get_option( 'lbite_pickup_display_title', '' ) ); ?>">
					<p class="description"><?php esc_html_e( 'Empty = name of the location.', 'libre-bite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Sound', 'libre-bite' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="lbite_pickup_display_sound" value="1" <?php checked( '0' !== (string) get_option( 'lbite_pickup_display_sound', 1 ) ); ?>>
						<?php esc_html_e( 'Play a sound when an order becomes ready.', 'libre-bite' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Display links', 'libre-bite' ); ?></th>
				<td>
					<?php foreach ( LBite_Locations::get_all_locations() as $lbite_loc ) : ?>
						<?php $lbite_pd_url = LBite_Pickup_Display::get_url( $lbite_loc->ID ); ?>
						<p>
							<strong><?php echo esc_html( $lbite_loc->post_title ); ?></strong><br>
							<input type="text" class="large-text code" readonly value="<?php echo esc_attr( $lbite_pd_url ); ?>" aria-label="<?php echo esc_attr( $lbite_loc->post_title ); ?>" onclick="this.select();">
							<a class="button" href="<?php echo esc_url( $lbite_pd_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open display', 'libre-bite' ); ?></a>
						</p>
					<?php endforeach; ?>
					<p class="description"><?php esc_html_e( 'Open the link on the TV browser and switch it to full screen. The link contains a secret key – anyone with the link can see the order numbers.', 'libre-bite' ); ?></p>
					<label>
						<input type="checkbox" name="lbite_pickup_display_regenerate" value="1">
						<?php esc_html_e( 'Create new links (the current links stop working)', 'libre-bite' ); ?>
					</label>
				</td>
			</tr>
		</table>
	<?php endif; ?>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
