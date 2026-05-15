<?php
/**
 * Template: Standort-Auswahl im Banner-Layout (2-Spalten, Bild links)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_align_class = ( 'center' !== $atts['align'] ) ? ' lbite-align-' . $atts['align'] : '';
?>

<div class="lbite-location-selector-banner<?php echo esc_attr( $lbite_align_class ); ?>">

	<!-- Schritt 1: Standort-Auswahl -->
	<div class="lbite-step lbite-step-location active" id="lbite-step-location">
		<?php foreach ( $lbite_locations as $lbite_location ) :
			$lbite_image_id  = get_post_meta( $lbite_location->ID, '_lbite_location_image', true );
			$lbite_image_url = $lbite_image_id ? wp_get_attachment_image_url( $lbite_image_id, 'large' ) : '';
			$lbite_address   = array();
			$lbite_street    = get_post_meta( $lbite_location->ID, '_lbite_street', true );
			$lbite_zip       = get_post_meta( $lbite_location->ID, '_lbite_zip', true );
			$lbite_city      = get_post_meta( $lbite_location->ID, '_lbite_city', true );
			$lbite_maps_url  = LBite_Locations::get_maps_url( $lbite_location->ID );

			if ( $lbite_street ) {
				$lbite_address[] = $lbite_street;
			}
			if ( $lbite_zip || $lbite_city ) {
				$lbite_address[] = trim( $lbite_zip . ' ' . $lbite_city );
			}

			$lbite_opening_hours = LBite_Locations::get_opening_hours( $lbite_location->ID );
			$lbite_status_data   = LBite_Locations::get_location_status( $lbite_opening_hours );
		?>
		<div class="lbite-banner-card lbite-location-card"
			data-location-id="<?php echo esc_attr( $lbite_location->ID ); ?>"
			data-maps-url="<?php echo esc_attr( $lbite_maps_url ); ?>"
			data-status-text="<?php echo $lbite_status_data ? esc_attr( $lbite_status_data['text'] ) : ''; ?>"
			data-status-type="<?php echo $lbite_status_data ? esc_attr( $lbite_status_data['type'] ) : ''; ?>">

			<!-- Linke Spalte: Bild -->
			<?php if ( $lbite_image_url ) : ?>
				<div class="lbite-banner-image" style="background-image: url('<?php echo esc_url( $lbite_image_url ); ?>');">
			<?php else : ?>
				<div class="lbite-banner-image lbite-location-placeholder">
					<span class="dashicons dashicons-store"></span>
			<?php endif; ?>
				<?php if ( $lbite_status_data ) : ?>
					<div class="lbite-location-status lbite-status-<?php echo esc_attr( $lbite_status_data['type'] ); ?>">
						<?php echo esc_html( $lbite_status_data['text'] ); ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- Rechte Spalte: Infos -->
			<div class="lbite-banner-content">
				<h3 class="lbite-location-name"><?php echo esc_html( $lbite_location->post_title ); ?></h3>

				<?php if ( ! empty( $lbite_address ) ) : ?>
					<?php if ( $lbite_maps_url ) : ?>
						<a href="<?php echo esc_url( $lbite_maps_url ); ?>" target="_blank" rel="noopener noreferrer"
							class="lbite-location-address lbite-maps-link" onclick="event.stopPropagation();">
							<?php echo esc_html( implode( ', ', $lbite_address ) ); ?>
							<svg class="lbite-external-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
								fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
								<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
								<polyline points="15 3 21 3 21 9"></polyline>
								<line x1="10" y1="14" x2="21" y2="3"></line>
							</svg>
						</a>
					<?php else : ?>
						<p class="lbite-location-address"><?php echo esc_html( implode( ', ', $lbite_address ) ); ?></p>
					<?php endif; ?>
				<?php endif; ?>

				<div class="lbite-banner-select-btn">
					<span class="lbite-button lbite-button-primary">
						<?php esc_html_e( 'Order here', 'libre-bite' ); ?>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
							stroke="currentColor" stroke-width="2" width="16" height="16" style="vertical-align: middle; margin-left: 6px;">
							<polyline points="9 18 15 12 9 6"></polyline>
						</svg>
					</span>
				</div>
			</div>
		</div>
		<?php endforeach; ?>
	</div>

	<!-- Schritt 2: Zeit-Auswahl -->
	<div class="lbite-step lbite-step-time" id="lbite-step-time">
		<button type="button" class="lbite-back-button">
			<span class="dashicons dashicons-arrow-left-alt2"></span>
			<?php esc_html_e( 'Back', 'libre-bite' ); ?>
		</button>

		<div class="lbite-selected-location-info">
			<div class="lbite-selected-location-image"></div>
			<div class="lbite-selected-location-details">
				<h3 class="lbite-selected-location-name"></h3>
				<p class="lbite-selected-location-address"></p>
				<p class="lbite-selected-location-status"></p>
			</div>
		</div>

		<!-- Loading Overlay -->
		<div class="lbite-loading-overlay" style="display: none;">
			<div class="lbite-spinner"></div>
			<p class="lbite-loading-text"><?php esc_html_e( 'Einen Moment bitte...', 'libre-bite' ); ?></p>
		</div>

		<?php if ( 'yes' === $atts['show_time'] ) : ?>
			<h2 class="lbite-step-title"><?php esc_html_e( 'When would you like to order?', 'libre-bite' ); ?></h2>

			<div class="lbite-closed-now-notice" id="lbite-closed-now-notice" style="display: none;">
				<span class="dashicons dashicons-info"></span>
				<span><?php esc_html_e( 'Immediate order not available –', 'libre-bite' ); ?> <span id="lbite-closed-notice-text"></span></span>
			</div>

			<div class="lbite-time-selection">
				<div class="lbite-time-option" data-time-type="now">
					<div class="lbite-time-icon">
						<span class="dashicons dashicons-clock"></span>
					</div>
					<div class="lbite-time-content">
						<h4><?php esc_html_e( 'Immediately', 'libre-bite' ); ?></h4>
						<p><?php esc_html_e( 'Pickup as soon as possible', 'libre-bite' ); ?></p>
					</div>
				</div>

				<div class="lbite-time-option" data-time-type="later">
					<div class="lbite-time-icon">
						<span class="dashicons dashicons-calendar-alt"></span>
					</div>
					<div class="lbite-time-content">
						<h4><?php esc_html_e( 'Pre-order for later', 'libre-bite' ); ?></h4>
						<p><?php esc_html_e( 'Choose your preferred time', 'libre-bite' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Zeitslot-Auswahl (nur bei "später") -->
			<div class="lbite-timeslot-selection" style="display: none;">
				<div class="lbite-form-group">
					<label for="lbite-pickup-date">
						<?php esc_html_e( 'Date', 'libre-bite' ); ?>
						<span class="required">*</span>
					</label>
					<input type="date" id="lbite-pickup-date" class="lbite-input"
						min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>"
						value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
					<div class="lbite-date-error" id="lbite-date-error" style="display: none;">
						<span class="dashicons dashicons-warning"></span>
						<span class="lbite-error-message">
							<?php esc_html_e( 'The location is closed on this day.', 'libre-bite' ); ?>
							<span id="lbite-next-opening"></span>
						</span>
					</div>
				</div>

				<div class="lbite-form-group">
					<label for="lbite-pickup-time">
						<?php esc_html_e( 'Time', 'libre-bite' ); ?>
						<span class="required">*</span>
					</label>
					<select id="lbite-pickup-time" class="lbite-select">
						<option value=""><?php esc_html_e( 'Please select a date', 'libre-bite' ); ?></option>
					</select>
				</div>

				<button type="button" class="lbite-button lbite-button-primary lbite-confirm-time">
					<?php esc_html_e( 'Continue to Menu', 'libre-bite' ); ?>
				</button>
			</div>
		<?php else : ?>
			<button type="button" class="lbite-button lbite-button-primary lbite-confirm-no-time" style="margin-top: 20px;">
				<?php esc_html_e( 'Continue to Menu', 'libre-bite' ); ?>
			</button>
		<?php endif; ?>
	</div>

</div>
