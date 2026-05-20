<?php
/**
 * Template: Bestellübersicht (Kanban Board)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap lbite-order-board">
	<div class="lbite-board-header">
		<h1><?php esc_html_e( 'Order Overview', 'libre-bite' ); ?></h1>
		<button type="button" id="lbite-board-fullscreen" class="button button-large" title="Vollbild">
			<span class="dashicons dashicons-editor-expand"></span>
		</button>
	</div>

	<?php
	$lbite_assigned_location  = (int) get_user_meta( get_current_user_id(), 'lbite_assigned_location', true );
	$lbite_is_location_locked = $lbite_assigned_location > 0 && ! current_user_can( 'lbite_manage_locations' );
	if ( $lbite_is_location_locked ) {
		$lbite_saved_location = $lbite_assigned_location;
	} else {
		$lbite_saved_location = get_user_meta( get_current_user_id(), 'lbite_board_location', true );
	}
	?>
	<div class="lbite-board-controls">
		<label>
			<?php esc_html_e( 'Location:', 'libre-bite' ); ?>
			<?php if ( $lbite_is_location_locked ) : ?>
				<?php $lbite_locked_post = get_post( $lbite_assigned_location ); ?>
				<span class="lbite-location-locked"><?php echo esc_html( $lbite_locked_post ? $lbite_locked_post->post_title : '' ); ?></span>
				<input type="hidden" id="lbite-board-location" value="<?php echo esc_attr( $lbite_assigned_location ); ?>">
			<?php else : ?>
				<select id="lbite-board-location">
					<option value=""><?php esc_html_e( 'Please select a location', 'libre-bite' ); ?></option>
					<?php
					$lbite_locations = get_posts(
						array(
							'post_type'      => 'lbite_location',
							'posts_per_page' => 100,
							'post_status'    => 'publish',
						)
					);
					foreach ( $lbite_locations as $lbite_location ) :
						?>
						<option value="<?php echo esc_attr( $lbite_location->ID ); ?>" <?php selected( $lbite_saved_location, $lbite_location->ID ); ?>>
							<?php echo esc_html( $lbite_location->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>
		</label>

		<?php if ( lbite_feature_enabled( 'enable_table_ordering' ) ) : ?>
		<label style="margin-left: 10px;">
			<?php esc_html_e( 'Filter:', 'libre-bite' ); ?>
			<select id="lbite-board-filter">
				<option value="all"><?php esc_html_e( 'All Orders', 'libre-bite' ); ?></option>
				<option value="table"><?php esc_html_e( 'Table Orders Only', 'libre-bite' ); ?></option>
				<option value="takeaway"><?php esc_html_e( 'Takeaway Only', 'libre-bite' ); ?></option>
			</select>
		</label>
		<?php endif; ?>

		<label class="lbite-wake-lock-toggle">
			<input type="checkbox" id="lbite-wake-lock" checked>
			<?php esc_html_e( 'Prevent Standby', 'libre-bite' ); ?>
		</label>

		<label class="lbite-wake-lock-toggle">
			<input type="checkbox" id="lbite-sound-enabled" checked>
			<?php esc_html_e( 'Sound Notifications', 'libre-bite' ); ?>
		</label>
	</div>

	<!-- Platzhalter wenn kein Standort gewählt -->
	<div class="lbite-no-location-selected" id="lbite-no-location-message" style="<?php echo esc_attr( $lbite_saved_location ? 'display: none;' : '' ); ?>">
		<div style="background: #fff; padding: 40px; margin: 40px 0; border: 2px dashed #ccc; border-radius: 8px; text-align: center;">
			<span class="dashicons dashicons-location" style="font-size: 48px; color: #999; margin-bottom: 20px;"></span>
			<h2 style="color: #666; margin: 10px 0;"><?php esc_html_e( 'Please select a location', 'libre-bite' ); ?></h2>
			<p style="color: #999;"><?php esc_html_e( 'The order overview is displayed for the selected location.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<div class="lbite-kanban-board" id="lbite-kanban-board" style="<?php echo esc_attr( $lbite_saved_location ? '' : 'display: none;' ); ?>">
		<div class="lbite-kanban-column" data-status="incoming">
			<h2><?php esc_html_e( 'Pre-orders', 'libre-bite' ); ?></h2>
			<div class="lbite-kanban-cards" id="lbite-column-incoming"></div>
		</div>

		<div class="lbite-kanban-column" data-status="preparing">
			<h2><?php esc_html_e( 'Prepare Now', 'libre-bite' ); ?></h2>
			<div class="lbite-kanban-cards" id="lbite-column-preparing"></div>
		</div>

		<div class="lbite-kanban-column" data-status="completed">
			<h2><?php esc_html_e( 'Completed', 'libre-bite' ); ?></h2>
			<div class="lbite-kanban-cards" id="lbite-column-completed"></div>
		</div>
	</div>
</div>
