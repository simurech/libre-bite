<?php
/**
 * Template: Standort-Auswahl mit Kacheln & Zwei-Schritt-Prozess
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if single location mode.
$is_single_location = ( count( $locations ) === 1 );
$location_class     = $is_single_location ? 'lb-location-selector-tiles lb-single-location' : 'lb-location-selector-tiles';
?>

<div class="<?php echo esc_attr( $location_class ); ?>">
	<!-- Schritt 1: Standort-Auswahl -->
	<div class="lb-step lb-step-location active" id="lb-step-location">
		<?php if ( ! $is_single_location ) : ?>
			<h2 class="lb-step-title"><?php esc_html_e( 'Wählen Sie Ihren Standort', 'libre-bite' ); ?></h2>
		<?php endif; ?>

		<div class="lb-location-grid<?php echo $is_single_location ? ' lb-single' : ''; ?>">
			<?php foreach ( $locations as $location ) : ?>
				<?php
				$image_id = get_post_meta( $location->ID, '_lb_location_image', true );
				$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
				$address = array();
				$street = get_post_meta( $location->ID, '_lb_street', true );
				$zip = get_post_meta( $location->ID, '_lb_zip', true );
				$city = get_post_meta( $location->ID, '_lb_city', true );
				$maps_url = LB_Locations::get_maps_url( $location->ID );

				if ( $street ) {
					$address[] = $street;
				}
				if ( $zip || $city ) {
					$address[] = trim( $zip . ' ' . $city );
				}

				// Status-Badge berechnen
				$opening_hours = LB_Locations::get_opening_hours( $location->ID );
				$status_data = LB_Locations::get_location_status( $opening_hours );
				?>
				<div class="lb-location-card" data-location-id="<?php echo esc_attr( $location->ID ); ?>">
					<?php if ( $image_url ) : ?>
						<div class="lb-location-image" style="background-image: url('<?php echo esc_url( $image_url ); ?>');"></div>
					<?php else : ?>
						<div class="lb-location-image lb-location-placeholder">
							<span class="dashicons dashicons-store"></span>
						</div>
					<?php endif; ?>

					<?php if ( $status_data ) : ?>
						<div class="lb-location-status lb-status-<?php echo esc_attr( $status_data['type'] ); ?>">
							<?php echo esc_html( $status_data['text'] ); ?>
						</div>
					<?php endif; ?>

					<div class="lb-location-content">
						<h3 class="lb-location-name"><?php echo esc_html( $location->post_title ); ?></h3>
						<?php if ( ! empty( $address ) ) : ?>
							<?php if ( $maps_url ) : ?>
								<a href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener noreferrer" class="lb-location-address lb-maps-link" onclick="event.stopPropagation();">
									<?php echo esc_html( implode( ', ', $address ) ); ?>
									<svg class="lb-external-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
										<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
										<polyline points="15 3 21 3 21 9"></polyline>
										<line x1="10" y1="14" x2="21" y2="3"></line>
									</svg>
								</a>
							<?php else : ?>
								<p class="lb-location-address"><?php echo esc_html( implode( ', ', $address ) ); ?></p>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Schritt 2: Zeit-Auswahl -->
	<div class="lb-step lb-step-time" id="lb-step-time">
		<button type="button" class="lb-back-button">
			<span class="dashicons dashicons-arrow-left-alt2"></span>
			<?php esc_html_e( 'Zurück', 'libre-bite' ); ?>
		</button>

		<div class="lb-selected-location-info">
			<div class="lb-selected-location-image"></div>
			<div class="lb-selected-location-details">
				<h3 class="lb-selected-location-name"></h3>
				<p class="lb-selected-location-address"></p>
			</div>
		</div>

		<!-- Loading Overlay -->
		<div class="lb-loading-overlay" style="display: none;">
			<div class="lb-spinner"></div>
			<p class="lb-loading-text"><?php esc_html_e( 'Einen Moment bitte...', 'libre-bite' ); ?></p>
		</div>

		<?php if ( 'yes' === $atts['show_time'] ) : ?>
			<h2 class="lb-step-title"><?php esc_html_e( 'Wann möchten Sie bestellen?', 'libre-bite' ); ?></h2>

			<div class="lb-time-selection">
				<div class="lb-time-option" data-time-type="now">
					<div class="lb-time-icon">
						<span class="dashicons dashicons-clock"></span>
					</div>
					<div class="lb-time-content">
						<h4><?php esc_html_e( 'Sofort', 'libre-bite' ); ?></h4>
						<p><?php esc_html_e( 'Abholung so schnell wie möglich', 'libre-bite' ); ?></p>
					</div>
				</div>

				<div class="lb-time-option" data-time-type="later">
					<div class="lb-time-icon">
						<span class="dashicons dashicons-calendar-alt"></span>
					</div>
					<div class="lb-time-content">
						<h4><?php esc_html_e( 'Für später vorbestellen', 'libre-bite' ); ?></h4>
						<p><?php esc_html_e( 'Wunschzeit auswählen', 'libre-bite' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Zeitslot-Auswahl (nur bei "später") -->
			<div class="lb-timeslot-selection" style="display: none;">
				<div class="lb-form-group">
					<label for="lb-pickup-date">
						<?php esc_html_e( 'Datum', 'libre-bite' ); ?>
						<span class="required">*</span>
					</label>
					<input type="date" id="lb-pickup-date" class="lb-input" min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
					<div class="lb-date-error" id="lb-date-error" style="display: none;">
						<span class="dashicons dashicons-warning"></span>
						<span class="lb-error-message">
							<?php esc_html_e( 'Der Standort ist an diesem Tag geschlossen.', 'libre-bite' ); ?>
							<span id="lb-next-opening"></span>
						</span>
					</div>
				</div>

				<div class="lb-form-group">
					<label for="lb-pickup-time">
						<?php esc_html_e( 'Uhrzeit', 'libre-bite' ); ?>
						<span class="required">*</span>
					</label>
					<select id="lb-pickup-time" class="lb-select">
						<option value=""><?php esc_html_e( 'Bitte Datum wählen', 'libre-bite' ); ?></option>
					</select>
				</div>

				<button type="button" class="lb-button lb-button-primary lb-confirm-time">
					<?php esc_html_e( 'Weiter zum Menü', 'libre-bite' ); ?>
				</button>
			</div>
		<?php else : ?>
			<!-- Wenn keine Zeit-Auswahl, direkt weiter -->
			<button type="button" class="lb-button lb-button-primary lb-confirm-no-time" style="margin-top: 20px;">
				<?php esc_html_e( 'Weiter zum Menü', 'libre-bite' ); ?>
			</button>
		<?php endif; ?>
	</div>
</div>

<style>
.lb-location-selector-tiles {
	max-width: 1200px;
	margin: 40px auto;
	padding: 0 20px;
}

.lb-step {
	display: none;
	animation: fadeIn 0.3s ease-in;
}

.lb-step.active {
	display: block;
}

@keyframes fadeIn {
	from { opacity: 0; transform: translateY(10px); }
	to { opacity: 1; transform: translateY(0); }
}

.lb-step-title {
	text-align: center;
	font-size: 32px;
	margin-bottom: 40px;
	color: #333;
}

/* Standort-Kacheln */
.lb-location-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
	gap: 24px;
	margin-bottom: 40px;
}

/* Single location mode - full width, larger display */
.lb-location-grid.lb-single {
	grid-template-columns: 1fr;
	max-width: 600px;
	margin-left: auto;
	margin-right: auto;
}

.lb-single-location .lb-location-card {
	max-width: 600px;
	margin: 0 auto;
}

.lb-single-location .lb-location-image {
	height: 300px;
}

.lb-single-location .lb-location-content {
	padding: 30px;
	text-align: center;
}

.lb-single-location .lb-location-name {
	font-size: 28px;
	margin-bottom: 12px;
}

.lb-single-location .lb-location-address {
	font-size: 16px;
}

.lb-location-card {
	background: #fff;
	border: 2px solid #e0e0e0;
	border-radius: 12px;
	overflow: hidden;
	cursor: pointer;
	transition: all 0.3s ease;
	box-shadow: 0 2px 8px rgba(0,0,0,0.08);
	position: relative;
}

.lb-location-card:hover {
	transform: translateY(-4px);
	box-shadow: 0 8px 20px rgba(0,0,0,0.15);
	border-color: #0073aa;
}

.lb-location-image {
	width: 100%;
	height: 200px;
	background-size: cover;
	background-position: center;
	background-color: #f5f5f5;
	position: relative;
}

/* Status-Badge */
.lb-location-status {
	position: absolute;
	top: 12px;
	right: 12px;
	padding: 6px 12px;
	border-radius: 20px;
	font-size: 12px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	box-shadow: 0 2px 8px rgba(0,0,0,0.15);
	z-index: 10;
	backdrop-filter: blur(8px);
}

.lb-status-open {
	background: rgba(16, 185, 129, 0.95);
	color: #fff;
}

.lb-status-closing-soon {
	background: rgba(245, 158, 11, 0.95);
	color: #fff;
}

.lb-status-opening-soon {
	background: rgba(59, 130, 246, 0.95);
	color: #fff;
}

.lb-status-closed {
	background: rgba(107, 114, 128, 0.95);
	color: #fff;
}

.lb-location-placeholder {
	display: flex;
	align-items: center;
	justify-content: center;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.lb-location-placeholder .dashicons {
	font-size: 64px;
	width: 64px;
	height: 64px;
	color: rgba(255,255,255,0.9);
}

.lb-location-content {
	padding: 20px;
}

.lb-location-name {
	font-size: 20px;
	font-weight: 600;
	margin: 0 0 8px 0;
	color: #333;
}

.lb-location-address {
	font-size: 14px;
	color: #666;
	margin: 0;
}

/* Maps Link */
a.lb-location-address.lb-maps-link {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	color: #0073aa;
	text-decoration: none;
	transition: color 0.2s;
}

a.lb-location-address.lb-maps-link:hover {
	color: #005a87;
	text-decoration: underline;
}

.lb-external-icon {
	flex-shrink: 0;
	opacity: 0.7;
}

/* Zeit-Auswahl */
.lb-back-button {
	background: none;
	border: none;
	color: #0073aa;
	font-size: 16px;
	cursor: pointer;
	padding: 8px 12px;
	margin-bottom: 20px;
	display: inline-flex;
	align-items: center;
	gap: 4px;
	transition: all 0.2s;
}

.lb-back-button:hover {
	color: #005a87;
	transform: translateX(-4px);
}

.lb-selected-location-info {
	display: flex;
	gap: 20px;
	padding: 20px;
	background: #f8f9fa;
	border-radius: 8px;
	margin-bottom: 40px;
	align-items: center;
}

.lb-selected-location-image {
	width: 80px;
	height: 80px;
	border-radius: 8px;
	background-size: cover;
	background-position: center;
	background-color: #e0e0e0;
	flex-shrink: 0;
}

.lb-selected-location-details h3 {
	margin: 0 0 4px 0;
	font-size: 20px;
}

.lb-selected-location-details p {
	margin: 0;
	color: #666;
	font-size: 14px;
}

.lb-time-selection {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	gap: 20px;
	margin-bottom: 30px;
}

.lb-time-option {
	background: #fff;
	border: 2px solid #e0e0e0;
	border-radius: 12px;
	padding: 24px;
	cursor: pointer;
	transition: all 0.3s ease;
	display: flex;
	gap: 16px;
	align-items: flex-start;
}

.lb-time-option:hover {
	border-color: #0073aa;
	box-shadow: 0 4px 12px rgba(0,115,170,0.1);
}

.lb-time-option.selected {
	border-color: #0073aa;
	background: #f0f8ff;
}

.lb-time-icon {
	width: 48px;
	height: 48px;
	border-radius: 50%;
	background: #0073aa;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
}

.lb-time-icon .dashicons {
	font-size: 24px;
	width: 24px;
	height: 24px;
	color: #fff;
}

.lb-time-content h4 {
	margin: 0 0 4px 0;
	font-size: 18px;
	color: #333;
}

.lb-time-content p {
	margin: 0;
	font-size: 14px;
	color: #666;
}

/* Zeitslot-Auswahl */
.lb-timeslot-selection {
	max-width: 500px;
	margin: 0 auto;
}

.lb-form-group {
	margin-bottom: 20px;
}

.lb-form-group label {
	display: block;
	margin-bottom: 8px;
	font-weight: 600;
	color: #333;
}

.lb-form-group .required {
	color: #dc3232;
}

.lb-select,
.lb-input {
	width: 100%;
	padding: 12px;
	border: 1px solid #ddd;
	border-radius: 8px;
	font-size: 16px;
	transition: border-color 0.3s;
}

.lb-select:focus,
.lb-input:focus {
	outline: none;
	border-color: #0073aa;
}

.lb-button {
	padding: 14px 30px;
	border: none;
	border-radius: 8px;
	font-size: 16px;
	font-weight: 600;
	cursor: pointer;
	transition: all 0.3s;
	width: 100%;
}

.lb-button-primary {
	background: #0073aa;
	color: #fff;
}

.lb-button-primary:hover {
	background: #005a87;
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(0,115,170,0.3);
}

/* Responsive */
/* Loading Overlay */
.lb-loading-overlay {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(255, 255, 255, 0.95);
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	z-index: 9999;
	animation: fadeIn 0.2s ease-in;
}

.lb-spinner {
	width: 50px;
	height: 50px;
	border: 4px solid #e0e0e0;
	border-top: 4px solid #0073aa;
	border-radius: 50%;
	animation: spin 0.8s linear infinite;
}

@keyframes spin {
	0% { transform: rotate(0deg); }
	100% { transform: rotate(360deg); }
}

.lb-loading-text {
	margin-top: 20px;
	font-size: 16px;
	color: #666;
	font-weight: 500;
}

/* Loading State für Buttons */
.lb-button.loading {
	position: relative;
	color: transparent;
	pointer-events: none;
}

.lb-button.loading::after {
	content: "";
	position: absolute;
	width: 20px;
	height: 20px;
	top: 50%;
	left: 50%;
	margin-left: -10px;
	margin-top: -10px;
	border: 3px solid rgba(255, 255, 255, 0.3);
	border-top: 3px solid #fff;
	border-radius: 50%;
	animation: spin 0.6s linear infinite;
}

/* Loading State für Zeit-Optionen */
.lb-time-option.loading {
	opacity: 0.6;
	pointer-events: none;
}

.lb-time-option.loading .lb-time-icon::after {
	content: "";
	position: absolute;
	width: 24px;
	height: 24px;
	border: 3px solid rgba(255, 255, 255, 0.3);
	border-top: 3px solid #fff;
	border-radius: 50%;
	animation: spin 0.6s linear infinite;
}

@media (max-width: 768px) {
	.lb-location-grid {
		grid-template-columns: 1fr;
	}

	.lb-step-title {
		font-size: 24px;
		margin-bottom: 24px;
	}

	.lb-time-selection {
		grid-template-columns: 1fr;
	}
}

/* Inline Fehlermeldung für Datum */
.lb-date-error {
	display: flex;
	align-items: flex-start;
	gap: 8px;
	margin-top: 8px;
	padding: 10px 12px;
	background: #fff3cd;
	border: 1px solid #ffc107;
	border-radius: 6px;
	color: #856404;
	font-size: 14px;
	animation: slideInError 0.3s ease-out;
	line-height: 1.5;
}

@keyframes slideInError {
	from {
		opacity: 0;
		transform: translateY(-10px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

.lb-date-error .dashicons {
	color: #ffc107;
	font-size: 18px;
	width: 18px;
	height: 18px;
	flex-shrink: 0;
	margin-top: 2px;
}

.lb-error-message {
	flex: 1;
}

.lb-error-message strong {
	color: #664d03;
	font-weight: 600;
}

.lb-error-input {
	border-color: #ffc107 !important;
	background-color: #fffbf0 !important;
}
</style>

<script>
jQuery(document).ready(function($) {
	let selectedLocationId = null;
	let selectedLocationData = {};
	let selectedTimeType = null;

	// Standort-Karte auswählen
	$('.lb-location-card').on('click', function() {
		selectedLocationId = $(this).data('location-id');

		// Standort-Daten speichern
		selectedLocationData = {
			id: selectedLocationId,
			name: $(this).find('.lb-location-name').text(),
			address: $(this).find('.lb-location-address').text(),
			image: $(this).find('.lb-location-image').css('background-image')
		};

		// Zur Zeit-Auswahl wechseln
		showStep('time');

		// Standort-Info anzeigen
		$('.lb-selected-location-name').text(selectedLocationData.name);
		$('.lb-selected-location-address').text(selectedLocationData.address);
		$('.lb-selected-location-image').css('background-image', selectedLocationData.image);

		// Geschlossene Tage laden
		updateDisabledDates();
	});

	// Zurück-Button
	$('.lb-back-button').on('click', function() {
		showStep('location');
		resetTimeSelection();
	});

	// Zeit-Option auswählen
	$('.lb-time-option').on('click', function() {
		const $option = $(this);

		$('.lb-time-option').removeClass('selected');
		$option.addClass('selected');

		selectedTimeType = $option.data('time-type');

		if (selectedTimeType === 'later') {
			$('.lb-timeslot-selection').slideDown();
			// Zeitslots laden
			if ($('#lb-pickup-date').val()) {
				loadTimeslots();
			}
		} else {
			// Loading-State anzeigen
			$option.addClass('loading');
			$('.lb-timeslot-selection').slideUp();

			// Direkt weiterleiten bei "sofort"
			confirmSelection('now', null);
		}
	});

	// Datum-Änderung
	$('#lb-pickup-date').on('change', function() {
		validateSelectedDate();
		loadTimeslots();
	});

	// Zeit bestätigen (später)
	$('.lb-confirm-time').on('click', function() {
		const $btn = $(this);
		const pickupTime = $('#lb-pickup-time').val();

		if (!pickupTime) {
			alert(lbData.strings.selectTime);
			return;
		}

		// Button auf Loading setzen
		$btn.addClass('loading');

		confirmSelection('later', pickupTime);
	});

	// Ohne Zeit-Auswahl fortfahren
	$('.lb-confirm-no-time').on('click', function() {
		const $btn = $(this);
		$btn.addClass('loading');
		confirmSelection('now', null);
	});

	// Zeitslots laden
	function loadTimeslots() {
		const date = $('#lb-pickup-date').val();

		if (!selectedLocationId || !date) {
			return;
		}

		const $select = $('#lb-pickup-time');
		$select.html('<option value=""><?php echo esc_js( __( 'Laden...', 'libre-bite' ) ); ?></option>').prop('disabled', true);

		// Visuelles Feedback
		$select.css('opacity', '0.6');

		$.ajax({
			url: lbData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lb_get_timeslots',
				nonce: lbData.nonce,
				location_id: selectedLocationId,
				date: date
			},
			success: function(response) {
				if (response.success && response.data.timeslots) {
					let options = '<option value=""><?php echo esc_js( __( 'Bitte wählen...', 'libre-bite' ) ); ?></option>';
					response.data.timeslots.forEach(function(slot) {
						options += '<option value="' + slot.value + '">' + slot.label + '</option>';
					});
					$select.html(options).prop('disabled', false);
				} else {
					$select.html('<option value=""><?php echo esc_js( __( 'Keine Zeitslots verfügbar', 'libre-bite' ) ); ?></option>');
				}
				$select.css('opacity', '1');
			},
			error: function() {
				$select.html('<option value=""><?php echo esc_js( __( 'Fehler beim Laden', 'libre-bite' ) ); ?></option>');
				$select.css('opacity', '1');
			}
		});
	}

	// Auswahl bestätigen und weiterleiten
	function confirmSelection(orderType, pickupTime) {
		// Loading Overlay anzeigen
		$('.lb-loading-overlay').fadeIn(200);

		$.ajax({
			url: lbData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lb_set_location',
				nonce: lbData.nonce,
				location_id: selectedLocationId,
				order_type: orderType,
				pickup_time: pickupTime
			},
			success: function(response) {
				if (response.success) {
					// Kurze Verzögerung für besseres UX-Feedback
					setTimeout(function() {
						// Zur Shop-Seite weiterleiten
						window.location.href = '<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>';
					}, 300);
				} else {
					$('.lb-loading-overlay').fadeOut(200);
					alert(response.data.message || 'Fehler beim Speichern');
					// Loading States zurücksetzen
					$('.lb-button, .lb-time-option').removeClass('loading');
				}
			},
			error: function() {
				$('.lb-loading-overlay').fadeOut(200);
				alert('Ein Fehler ist aufgetreten');
				// Loading States zurücksetzen
				$('.lb-button, .lb-time-option').removeClass('loading');
			}
		});
	}

	// Schritt wechseln
	function showStep(step) {
		$('.lb-step').removeClass('active');
		$('#lb-step-' + step).addClass('active');
	}

	// Zeit-Auswahl zurücksetzen
	function resetTimeSelection() {
		$('.lb-time-option').removeClass('selected');
		$('.lb-timeslot-selection').hide();
		selectedTimeType = null;
	}

	// Geschlossene Tage vom Server abrufen
	let closedDaysCache = [];

	function updateDisabledDates() {
		if (!selectedLocationId) {
			return;
		}

		$.ajax({
			url: lbData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lb_get_opening_days',
				nonce: lbData.nonce,
				location_id: selectedLocationId
			},
			success: function(response) {
				if (response.success && response.data.closed_days) {
					closedDaysCache = response.data.closed_days;
					// Aktuelles Datum validieren
					validateSelectedDate();
				}
			}
		});
	}

	// Ausgewähltes Datum validieren
	function validateSelectedDate() {
		const selectedDate = $('#lb-pickup-date').val();
		if (!selectedDate || closedDaysCache.length === 0) {
			$('#lb-date-error').slideUp(200);
			return;
		}

		const date = new Date(selectedDate + 'T00:00:00');
		const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
		const dayName = dayNames[date.getDay()];

		if (closedDaysCache.includes(dayName)) {
			// Nächsten Öffnungstag finden
			const nextOpenDate = findNextOpenDate(selectedDate);

			// Fehlermeldung mit nächstem Öffnungsdatum anzeigen
			if (nextOpenDate) {
				const formattedDate = formatDate(nextOpenDate);
				$('#lb-next-opening').html('<br><?php echo esc_js( __( 'Nächste Öffnung:', 'libre-bite' ) ); ?> <strong>' + formattedDate + '</strong>');
			} else {
				$('#lb-next-opening').html('');
			}

			// Inline Fehlermeldung anzeigen (bleibt sichtbar!)
			$('#lb-date-error').slideDown(300);
			$('#lb-pickup-date').addClass('lb-error-input');
		} else {
			// Fehlermeldung ausblenden wenn gültiges Datum
			$('#lb-date-error').slideUp(200);
			$('#lb-pickup-date').removeClass('lb-error-input');
			$('#lb-next-opening').html('');
		}
	}

	// Nächsten Öffnungstag finden
	function findNextOpenDate(fromDate) {
		const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
		let currentDate = new Date(fromDate + 'T00:00:00');

		// Maximal 14 Tage in die Zukunft suchen
		for (let i = 1; i <= 14; i++) {
			currentDate.setDate(currentDate.getDate() + 1);
			const dayName = dayNames[currentDate.getDay()];

			if (!closedDaysCache.includes(dayName)) {
				return currentDate;
			}
		}

		return null;
	}

	// Datum formatieren (dd.mm.YYYY)
	function formatDate(date) {
		const day = ('0' + date.getDate()).slice(-2);
		const month = ('0' + (date.getMonth() + 1)).slice(-2);
		const year = date.getFullYear();

		return day + '.' + month + '.' + year;
	}

	// URL-Parameter verarbeiten (für Direktlinks)
	const urlParams = new URLSearchParams(window.location.search);
	const locationParam = urlParams.get('location');

	if (locationParam) {
		$('.lb-location-card[data-location-id="' + locationParam + '"]').trigger('click');
	}
});
</script>
