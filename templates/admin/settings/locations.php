<?php
/**
 * Tab: Standorte
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_location_page_id  = get_option( 'lbite_location_page_id', 0 );
$lbite_preparation_time  = get_option( 'lbite_preparation_time', 30 );
$lbite_timeslot_int      = get_option( 'lbite_timeslot_interval', 15 );
$lbite_slot_buffer_start = get_option( 'lbite_slot_buffer_start', 0 );
$lbite_slot_buffer_end   = get_option( 'lbite_slot_buffer_end', 0 );
$lbite_is_premium_loc    = function_exists( 'lbite_freemius' ) && lbite_freemius()->can_use_premium_code__premium_only();
$lbite_all_pages         = get_pages( array( 'post_status' => 'publish' ) );
?>
<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="locations">

	<?php
	$lbite_toggle_key         = 'enable_location_selector';
	$lbite_toggle_label       = __( 'Location Selection', 'libre-bite' );
	$lbite_toggle_description = __( 'Show a location selector in the frontend so customers can choose their pickup location.', 'libre-bite' );
	$lbite_toggle_is_pro      = false;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<p class="description" style="margin-bottom: 16px;">
		<?php esc_html_e( 'Locations are managed as individual entries. Each location has its own opening hours, timeslots, and holidays.', 'libre-bite' ); ?>
	</p>
	<p style="margin-bottom: 24px;">
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lbite_location' ) ); ?>" class="button">
			<?php esc_html_e( 'Manage Locations', 'libre-bite' ); ?>
		</a>
	</p>

	<h2><?php esc_html_e( 'Location Page', 'libre-bite' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Location Page', 'libre-bite' ); ?></th>
			<td>
				<select name="lbite_location_page_id">
					<option value="0"><?php esc_html_e( '-- Please select --', 'libre-bite' ); ?></option>
					<option value="create_new"><?php esc_html_e( '+ Create New Page', 'libre-bite' ); ?></option>
					<?php foreach ( $lbite_all_pages as $lbite_page ) : ?>
						<option value="<?php echo esc_attr( $lbite_page->ID ); ?>" <?php selected( $lbite_location_page_id, $lbite_page->ID ); ?>>
							<?php echo esc_html( $lbite_page->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?php esc_html_e( 'Select the page where the shortcode [lbite_location_selector] is included, or create a new page.', 'libre-bite' ); ?>
					<?php if ( $lbite_location_page_id ) : ?>
						<br><a href="<?php echo esc_url( get_edit_post_link( $lbite_location_page_id ) ); ?>" target="_blank"><?php esc_html_e( 'Edit Page', 'libre-bite' ); ?></a>
						|
						<a href="<?php echo esc_url( get_permalink( $lbite_location_page_id ) ); ?>" target="_blank"><?php esc_html_e( 'View Page', 'libre-bite' ); ?></a>
					<?php endif; ?>
				</p>
			</td>
		</tr>
	</table>

	<h2><?php esc_html_e( 'Time Settings', 'libre-bite' ); ?></h2>
	<p class="description" style="margin-bottom: 12px;">
		<?php esc_html_e( 'These are global defaults. Individual locations can override preparation time and slot buffers via their location settings.', 'libre-bite' ); ?>
	</p>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Preparation Time', 'libre-bite' ); ?></th>
			<td>
				<input type="number" min="0" name="lbite_preparation_time" value="<?php echo esc_attr( $lbite_preparation_time ); ?>" class="small-text"> <?php esc_html_e( 'Minutes', 'libre-bite' ); ?>
				<p class="description"><?php esc_html_e( 'Pre-orders are automatically moved from "Pre-orders" to "Prepare Now" X minutes before pickup time.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Time Slot Interval', 'libre-bite' ); ?></th>
			<td>
				<input type="number" min="5" step="5" name="lbite_timeslot_interval" value="<?php echo esc_attr( $lbite_timeslot_int ); ?>" class="small-text"> <?php esc_html_e( 'Minutes', 'libre-bite' ); ?>
				<p class="description"><?php esc_html_e( 'Distance between time slots for pre-orders.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th>
				<?php esc_html_e( 'Slot Buffer Start', 'libre-bite' ); ?>
				<?php if ( ! $lbite_is_premium_loc ) : ?>
					<span class="lbite-pro-badge">Pro</span>
				<?php endif; ?>
			</th>
			<td>
				<input type="number" min="0" step="5" name="lbite_slot_buffer_start" value="<?php echo esc_attr( $lbite_slot_buffer_start ); ?>" class="small-text" <?php echo $lbite_is_premium_loc ? '' : 'disabled'; ?>> <?php esc_html_e( 'Minutes', 'libre-bite' ); ?>
				<p class="description"><?php esc_html_e( 'Cut off the first N minutes from each time window. Example: with 30 minutes, a window from 11:00 only shows slots from 11:30 onwards.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th>
				<?php esc_html_e( 'Slot Buffer End', 'libre-bite' ); ?>
				<?php if ( ! $lbite_is_premium_loc ) : ?>
					<span class="lbite-pro-badge">Pro</span>
				<?php endif; ?>
			</th>
			<td>
				<input type="number" min="0" step="5" name="lbite_slot_buffer_end" value="<?php echo esc_attr( $lbite_slot_buffer_end ); ?>" class="small-text" <?php echo $lbite_is_premium_loc ? '' : 'disabled'; ?>> <?php esc_html_e( 'Minutes', 'libre-bite' ); ?>
				<p class="description"><?php esc_html_e( 'Cut off the last N minutes from each time window. Example: with 30 minutes, a window until 22:00 only shows slots up to 21:30.', 'libre-bite' ); ?></p>
			</td>
		</tr>
	</table>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
