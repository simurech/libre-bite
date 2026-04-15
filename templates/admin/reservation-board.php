<?php
/**
 * Template: Reservierungsübersicht (Tagesansicht)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_locations      = get_posts(
	array(
		'post_type'      => 'lbite_location',
		'posts_per_page' => 100,
		'post_status'    => 'publish',
	)
);
$lbite_saved_location = get_user_meta( get_current_user_id(), 'lbite_reservation_board_location', true );
?>

<div class="wrap lbite-reservation-board">
	<div class="lbite-board-header">
		<h1><?php esc_html_e( 'Reservations', 'libre-bite' ); ?></h1>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lbite_reservation' ) ); ?>" class="button">
			<?php esc_html_e( 'All Reservations', 'libre-bite' ); ?>
		</a>
	</div>

	<div class="lbite-board-controls lbite-res-board-controls">
		<label>
			<?php esc_html_e( 'Location:', 'libre-bite' ); ?>
			<select id="lbite-res-board-location">
				<option value=""><?php esc_html_e( 'Please select a location', 'libre-bite' ); ?></option>
				<?php foreach ( $lbite_locations as $lbite_location ) : ?>
					<option value="<?php echo esc_attr( $lbite_location->ID ); ?>" <?php selected( $lbite_saved_location, $lbite_location->ID ); ?>>
						<?php echo esc_html( $lbite_location->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>

		<div class="lbite-res-date-nav">
			<button type="button" id="lbite-res-date-prev" class="button" title="<?php esc_attr_e( 'Previous Day', 'libre-bite' ); ?>">&#8249;</button>
			<input type="date" id="lbite-res-date" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
			<button type="button" id="lbite-res-date-next" class="button" title="<?php esc_attr_e( 'Next Day', 'libre-bite' ); ?>">&#8250;</button>
			<button type="button" id="lbite-res-date-today" class="button"><?php esc_html_e( 'Today', 'libre-bite' ); ?></button>
		</div>

		<span id="lbite-res-count-badge" class="lbite-res-count-badge" style="display:none;"></span>
	</div>

	<!-- Platzhalter wenn kein Standort gewählt -->
	<div id="lbite-res-no-location-message" class="lbite-no-location-selected" style="<?php echo $lbite_saved_location ? 'display: none;' : ''; ?>">
		<div style="background: #fff; padding: 40px; margin: 40px 0; border: 2px dashed #ccc; border-radius: 8px; text-align: center;">
			<span class="dashicons dashicons-calendar-alt" style="font-size: 48px; color: #999; margin-bottom: 20px;"></span>
			<h2 style="color: #666; margin: 10px 0;"><?php esc_html_e( 'Please select a location', 'libre-bite' ); ?></h2>
			<p style="color: #999;"><?php esc_html_e( 'The reservation overview will be displayed for the selected location.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<div id="lbite-reservation-board-wrap" style="<?php echo $lbite_saved_location ? '' : 'display: none;'; ?>">
		<div id="lbite-res-loading" class="lbite-res-loading" style="display:none;">
			<span class="spinner is-active"></span>
			<?php esc_html_e( 'Loading…', 'libre-bite' ); ?>
		</div>

		<div id="lbite-reservation-list"></div>

		<div id="lbite-res-empty-state" class="lbite-res-empty-state" style="display:none;">
			<span class="dashicons dashicons-calendar-alt" style="font-size: 36px; color: #ccc;"></span>
			<p><?php esc_html_e( 'No reservations for this day.', 'libre-bite' ); ?></p>
		</div>
	</div>
</div>
