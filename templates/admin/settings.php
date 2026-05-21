<?php
/**
 * Template: Einstellungen – Allgemein
 *
 * Wird als Tab-Inhalt in settings-tabbed.php geladen.
 * Enthält: Standort-Seite, Zeiteinstellungen. Branding → settings/branding.php
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Einstellungen speichern
if ( isset( $_POST['lbite_save_settings'] ) && check_admin_referer( 'lbite_settings' ) ) {
	$lbite_save_tab = isset( $_POST['lbite_save_tab'] ) ? sanitize_key( wp_unslash( $_POST['lbite_save_tab'] ) ) : '';

	// Nur wenn dieser Tab die Daten abschickt
	if ( 'general' === $lbite_save_tab ) {
		// Standort-Seite
		if ( isset( $_POST['lbite_location_page_id'] ) ) {
			$lbite_loc_page = sanitize_text_field( wp_unslash( $_POST['lbite_location_page_id'] ) );
			if ( 'create_new' === $lbite_loc_page ) {
				$lbite_new_page_id = wp_insert_post(
					array(
						'post_title'   => __( 'Locations', 'libre-bite' ),
						'post_content' => '[lbite_location_selector]',
						'post_status'  => 'publish',
						'post_type'    => 'page',
					)
				);
				if ( ! is_wp_error( $lbite_new_page_id ) ) {
					update_option( 'lbite_location_page_id', $lbite_new_page_id );
				}
			} else {
				update_option( 'lbite_location_page_id', intval( $lbite_loc_page ) );
			}
		}

		// Zeiteinstellungen + Pro-Options durch den Helper schicken
		$lbite_time_values = lbite_enforce_pro_options( array(
			'lbite_preparation_time'    => isset( $_POST['lbite_preparation_time'] ) ? intval( wp_unslash( $_POST['lbite_preparation_time'] ) ) : 30,
			'lbite_pickup_reminder_time' => isset( $_POST['lbite_pickup_reminder_time'] ) ? intval( wp_unslash( $_POST['lbite_pickup_reminder_time'] ) ) : 15,
			'lbite_slot_buffer_start'   => isset( $_POST['lbite_slot_buffer_start'] ) ? intval( wp_unslash( $_POST['lbite_slot_buffer_start'] ) ) : 0,
			'lbite_slot_buffer_end'     => isset( $_POST['lbite_slot_buffer_end'] ) ? intval( wp_unslash( $_POST['lbite_slot_buffer_end'] ) ) : 0,
			'lbite_table_order_page_id' => isset( $_POST['lbite_table_order_page_id'] ) ? intval( wp_unslash( $_POST['lbite_table_order_page_id'] ) ) : 0,
		) );
		update_option( 'lbite_preparation_time', $lbite_time_values['lbite_preparation_time'] );
		update_option( 'lbite_pickup_reminder_time', $lbite_time_values['lbite_pickup_reminder_time'] );
		update_option( 'lbite_timeslot_interval', isset( $_POST['lbite_timeslot_interval'] ) ? intval( wp_unslash( $_POST['lbite_timeslot_interval'] ) ) : 15 );
		update_option( 'lbite_slot_buffer_start', $lbite_time_values['lbite_slot_buffer_start'] );
		update_option( 'lbite_slot_buffer_end', $lbite_time_values['lbite_slot_buffer_end'] );

		// Tischbestellung
		update_option( 'lbite_table_order_page_id', $lbite_time_values['lbite_table_order_page_id'] );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'lbite-settings',
					'tab'     => 'general',
					'updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}

// Optionen laden
$lbite_location_page_id = get_option( 'lbite_location_page_id', 0 );
$lbite_preparation_time = get_option( 'lbite_preparation_time', 30 );
$lbite_pickup_reminder  = get_option( 'lbite_pickup_reminder_time', 15 );
$lbite_timeslot_int        = get_option( 'lbite_timeslot_interval', 15 );
$lbite_slot_buffer_start   = get_option( 'lbite_slot_buffer_start', 0 );
$lbite_slot_buffer_end     = get_option( 'lbite_slot_buffer_end', 0 );
$lbite_is_premium_for_f5   = function_exists( 'lbite_freemius' ) && lbite_freemius()->can_use_premium_code__premium_only();
$lbite_all_pages           = get_pages( array( 'post_status' => 'publish' ) );
$lbite_table_order_page = get_option( 'lbite_table_order_page_id', 0 );
?>

<?php if ( empty( $lbite_is_tab ) ) : ?>
<div class="wrap">
	<h1><?php echo esc_html( apply_filters( 'lbite_plugin_display_name', __( 'Libre Bite', 'libre-bite' ) ) . ' - ' . __( 'Settings', 'libre-bite' ) ); ?></h1>
<?php endif; ?>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="general">

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
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Preparation Time', 'libre-bite' ); ?></th>
			<td>
				<input type="number" min="0" name="lbite_preparation_time" value="<?php echo esc_attr( $lbite_preparation_time ); ?>" class="small-text"> <?php esc_html_e( 'Minutes', 'libre-bite' ); ?>
				<p class="description"><?php esc_html_e( 'Pre-orders are automatically moved from "Pre-orders" to "Prepare Now" X minutes before pickup time.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Reminder Before Pickup', 'libre-bite' ); ?></th>
			<td>
				<input type="number" min="0" name="lbite_pickup_reminder_time" value="<?php echo esc_attr( $lbite_pickup_reminder ); ?>" class="small-text"> <?php esc_html_e( 'Minutes', 'libre-bite' ); ?>
				<p class="description"><?php esc_html_e( 'Send reminder email X minutes before pickup time.', 'libre-bite' ); ?></p>
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
				<?php if ( ! $lbite_is_premium_for_f5 ) : ?>
					<span class="lbite-pro-badge">Pro</span>
				<?php endif; ?>
			</th>
			<td>
				<input type="number" min="0" step="5" name="lbite_slot_buffer_start" value="<?php echo esc_attr( $lbite_slot_buffer_start ); ?>" class="small-text" <?php echo $lbite_is_premium_for_f5 ? '' : 'disabled'; ?>> <?php esc_html_e( 'Minutes', 'libre-bite' ); ?>
				<p class="description"><?php esc_html_e( 'Cut off the first N minutes from each time window. Example: with 30 minutes, a window from 11:00 only shows slots from 11:30 onwards.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th>
				<?php esc_html_e( 'Slot Buffer End', 'libre-bite' ); ?>
				<?php if ( ! $lbite_is_premium_for_f5 ) : ?>
					<span class="lbite-pro-badge">Pro</span>
				<?php endif; ?>
			</th>
			<td>
				<input type="number" min="0" step="5" name="lbite_slot_buffer_end" value="<?php echo esc_attr( $lbite_slot_buffer_end ); ?>" class="small-text" <?php echo $lbite_is_premium_for_f5 ? '' : 'disabled'; ?>> <?php esc_html_e( 'Minutes', 'libre-bite' ); ?>
				<p class="description"><?php esc_html_e( 'Cut off the last N minutes from each time window. Example: with 30 minutes, a window until 22:00 only shows slots up to 21:30.', 'libre-bite' ); ?></p>
			</td>
		</tr>
	</table>

	<h2>
		<?php esc_html_e( 'Table Ordering', 'libre-bite' ); ?>
		<?php if ( ! $lbite_is_premium_for_f5 ) : ?>
			<span class="lbite-pro-badge">Pro</span>
		<?php endif; ?>
	</h2>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Menu Page', 'libre-bite' ); ?></th>
			<td>
				<select name="lbite_table_order_page_id" <?php echo $lbite_is_premium_for_f5 ? '' : 'disabled'; ?>>
					<option value="0"><?php esc_html_e( '— Default (Shop Page) —', 'libre-bite' ); ?></option>
					<?php foreach ( $lbite_all_pages as $lbite_p ) : ?>
						<option value="<?php echo esc_attr( $lbite_p->ID ); ?>" <?php selected( $lbite_table_order_page, $lbite_p->ID ); ?>>
							<?php echo esc_html( $lbite_p->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Which page are guests redirected to after scanning the QR code? Default: WooCommerce shop page.', 'libre-bite' ); ?></p>
			</td>
		</tr>
	</table>

	<?php submit_button( __( 'Save Settings', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>

<?php if ( empty( $lbite_is_tab ) ) : ?>
</div>
<?php endif; ?>

