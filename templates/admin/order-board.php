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

	<div class="lbite-board-controls">
		<label>
			<?php esc_html_e( 'Location:', 'libre-bite' ); ?>
			<select id="lbite-board-location">
				<option value=""><?php esc_html_e( 'Please select a location', 'libre-bite' ); ?></option>
				<?php
				$lbite_locations = get_posts(
					array(
						'post_type'      => 'lbite_location',
						'posts_per_page' => 100, // Begrenzt für Performance.
						'post_status'    => 'publish',
					)
				);
				$lbite_saved_location = get_user_meta( get_current_user_id(), 'lbite_board_location', true );

				foreach ( $lbite_locations as $lbite_location ) :
					?>
					<option value="<?php echo esc_attr( $lbite_location->ID ); ?>" <?php selected( $lbite_saved_location, $lbite_location->ID ); ?>>
						<?php echo esc_html( $lbite_location->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
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

		<button type="button" id="lbite-sound-toggle" class="button">
			<span class="dashicons dashicons-controls-volumeon"></span>
			<?php esc_html_e( 'Sound active', 'libre-bite' ); ?>
		</button>

		<button type="button" id="lbite-activate-audio" class="button button-primary" style="display: none;">
			<span class="dashicons dashicons-megaphone"></span>
			<?php esc_html_e( 'Enable Sound Notifications', 'libre-bite' ); ?>
		</button>
	</div>

	<!-- Platzhalter wenn kein Standort gewählt -->
	<div class="lbite-no-location-selected" id="lbite-no-location-message" style="<?php echo $lbite_saved_location ? 'display: none;' : ''; ?>">
		<div style="background: #fff; padding: 40px; margin: 40px 0; border: 2px dashed #ccc; border-radius: 8px; text-align: center;">
			<span class="dashicons dashicons-location" style="font-size: 48px; color: #999; margin-bottom: 20px;"></span>
			<h2 style="color: #666; margin: 10px 0;"><?php esc_html_e( 'Please select a location', 'libre-bite' ); ?></h2>
			<p style="color: #999;"><?php esc_html_e( 'The order overview is displayed for the selected location.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<div class="lbite-kanban-board" id="lbite-kanban-board" style="<?php echo $lbite_saved_location ? '' : 'display: none;'; ?>">
		<div class="lbite-kanban-column" data-status="incoming">
			<h2><?php esc_html_e( 'Incoming', 'libre-bite' ); ?></h2>
			<div class="lbite-kanban-cards" id="lbite-column-incoming"></div>
		</div>

		<div class="lbite-kanban-column" data-status="preparing">
			<h2><?php esc_html_e( 'Preparing', 'libre-bite' ); ?></h2>
			<div class="lbite-kanban-cards" id="lbite-column-preparing"></div>
		</div>

		<div class="lbite-kanban-column" data-status="ready">
			<h2><?php esc_html_e( 'Ready for Pickup', 'libre-bite' ); ?></h2>
			<div class="lbite-kanban-cards" id="lbite-column-ready"></div>
		</div>

		<div class="lbite-kanban-column" data-status="completed">
			<h2><?php esc_html_e( 'Completed', 'libre-bite' ); ?></h2>
			<div class="lbite-kanban-cards" id="lbite-column-completed"></div>
		</div>
	</div>
</div>
