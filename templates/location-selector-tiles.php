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
$location_class     = $is_single_location ? 'lbite-location-selector-tiles lbite-single-location' : 'lbite-location-selector-tiles';
?>

<div class="<?php echo esc_attr( $location_class ); ?>">
	<!-- Schritt 1: Standort-Auswahl -->
	<div class="lbite-step lbite-step-location active" id="lbite-step-location">
		<?php if ( ! $is_single_location ) : ?>
			<h2 class="lbite-step-title"><?php esc_html_e( 'Wählen Sie Ihren Standort', 'libre-bite' ); ?></h2>
		<?php endif; ?>

		<div class="lbite-location-grid<?php echo $is_single_location ? ' lbite-single' : ''; ?>">
			<?php foreach ( $locations as $location ) : ?>
				<?php
				$image_id = get_post_meta( $location->ID, '_lbite_location_image', true );
				$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
				$address = array();
				$street = get_post_meta( $location->ID, '_lbite_street', true );
				$zip = get_post_meta( $location->ID, '_lbite_zip', true );
				$city = get_post_meta( $location->ID, '_lbite_city', true );
				$maps_url = LBite_Locations::get_maps_url( $location->ID );

				if ( $street ) {
					$address[] = $street;
				}
				if ( $zip || $city ) {
					$address[] = trim( $zip . ' ' . $city );
				}

				// Status-Badge berechnen
				$opening_hours = LBite_Locations::get_opening_hours( $location->ID );
				$status_data = LBite_Locations::get_location_status( $opening_hours );
				?>
				<div class="lbite-location-card" data-location-id="<?php echo esc_attr( $location->ID ); ?>">
					<?php if ( $image_url ) : ?>
						<div class="lbite-location-image" style="background-image: url('<?php echo esc_url( $image_url ); ?>');"></div>
					<?php else : ?>
						<div class="lbite-location-image lbite-location-placeholder">
							<span class="dashicons dashicons-store"></span>
						</div>
					<?php endif; ?>

					<?php if ( $status_data ) : ?>
						<div class="lbite-location-status lbite-status-<?php echo esc_attr( $status_data['type'] ); ?>">
							<?php echo esc_html( $status_data['text'] ); ?>
						</div>
					<?php endif; ?>

					<div class="lbite-location-content">
						<h3 class="lbite-location-name"><?php echo esc_html( $location->post_title ); ?></h3>
						<?php if ( ! empty( $address ) ) : ?>
							<?php if ( $maps_url ) : ?>
								<a href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener noreferrer" class="lbite-location-address lbite-maps-link" onclick="event.stopPropagation();">
									<?php echo esc_html( implode( ', ', $address ) ); ?>
									<svg class="lbite-external-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
										<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
										<polyline points="15 3 21 3 21 9"></polyline>
										<line x1="10" y1="14" x2="21" y2="3"></line>
									</svg>
								</a>
							<?php else : ?>
								<p class="lbite-location-address"><?php echo esc_html( implode( ', ', $address ) ); ?></p>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Schritt 2: Zeit-Auswahl -->
	<div class="lbite-step lbite-step-time" id="lbite-step-time">
		<button type="button" class="lbite-back-button">
			<span class="dashicons dashicons-arrow-left-alt2"></span>
			<?php esc_html_e( 'Zurück', 'libre-bite' ); ?>
		</button>

		<div class="lbite-selected-location-info">
			<div class="lbite-selected-location-image"></div>
			<div class="lbite-selected-location-details">
				<h3 class="lbite-selected-location-name"></h3>
				<p class="lbite-selected-location-address"></p>
			</div>
		</div>

		<!-- Loading Overlay -->
		<div class="lbite-loading-overlay" style="display: none;">
			<div class="lbite-spinner"></div>
			<p class="lbite-loading-text"><?php esc_html_e( 'Einen Moment bitte...', 'libre-bite' ); ?></p>
		</div>

		<?php if ( 'yes' === $atts['show_time'] ) : ?>
			<h2 class="lbite-step-title"><?php esc_html_e( 'Wann möchten Sie bestellen?', 'libre-bite' ); ?></h2>

			<div class="lbite-time-selection">
				<div class="lbite-time-option" data-time-type="now">
					<div class="lbite-time-icon">
						<span class="dashicons dashicons-clock"></span>
					</div>
					<div class="lbite-time-content">
						<h4><?php esc_html_e( 'Sofort', 'libre-bite' ); ?></h4>
						<p><?php esc_html_e( 'Abholung so schnell wie möglich', 'libre-bite' ); ?></p>
					</div>
				</div>

				<div class="lbite-time-option" data-time-type="later">
					<div class="lbite-time-icon">
						<span class="dashicons dashicons-calendar-alt"></span>
					</div>
					<div class="lbite-time-content">
						<h4><?php esc_html_e( 'Für später vorbestellen', 'libre-bite' ); ?></h4>
						<p><?php esc_html_e( 'Wunschzeit auswählen', 'libre-bite' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Zeitslot-Auswahl (nur bei "später") -->
			<div class="lbite-timeslot-selection" style="display: none;">
				<div class="lbite-form-group">
					<label for="lbite-pickup-date">
						<?php esc_html_e( 'Datum', 'libre-bite' ); ?>
						<span class="required">*</span>
					</label>
					<input type="date" id="lbite-pickup-date" class="lbite-input" min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
					<div class="lbite-date-error" id="lbite-date-error" style="display: none;">
						<span class="dashicons dashicons-warning"></span>
						<span class="lbite-error-message">
							<?php esc_html_e( 'Der Standort ist an diesem Tag geschlossen.', 'libre-bite' ); ?>
							<span id="lbite-next-opening"></span>
						</span>
					</div>
				</div>

				<div class="lbite-form-group">
					<label for="lbite-pickup-time">
						<?php esc_html_e( 'Uhrzeit', 'libre-bite' ); ?>
						<span class="required">*</span>
					</label>
					<select id="lbite-pickup-time" class="lbite-select">
						<option value=""><?php esc_html_e( 'Bitte Datum wählen', 'libre-bite' ); ?></option>
					</select>
				</div>

				<button type="button" class="lbite-button lbite-button-primary lbite-confirm-time">
					<?php esc_html_e( 'Weiter zum Menü', 'libre-bite' ); ?>
				</button>
			</div>
		<?php else : ?>
			<!-- Wenn keine Zeit-Auswahl, direkt weiter -->
			<button type="button" class="lbite-button lbite-button-primary lbite-confirm-no-time" style="margin-top: 20px;">
				<?php esc_html_e( 'Weiter zum Menü', 'libre-bite' ); ?>
			</button>
		<?php endif; ?>
	</div>
</div>


<?php ob_start(); ?>
jQuery(document).ready(function($) {
	let selectedLocationId = null;
	let selectedLocationData = {};
	let selectedTimeType = null;

	// Standort-Karte auswählen
	$('.lbite-location-card').on('click', function() {
		selectedLocationId = $(this).data('location-id');

		// Standort-Daten speichern
		selectedLocationData = {
			id: selectedLocationId,
			name: $(this).find('.lbite-location-name').text(),
			address: $(this).find('.lbite-location-address').text(),
			image: $(this).find('.lbite-location-image').css('background-image')
		};

		// Zur Zeit-Auswahl wechseln
		showStep('time');

		// Standort-Info anzeigen
		$('.lbite-selected-location-name').text(selectedLocationData.name);
		$('.lbite-selected-location-address').text(selectedLocationData.address);
		$('.lbite-selected-location-image').css('background-image', selectedLocationData.image);

		// Geschlossene Tage laden
		updateDisabledDates();
	});

	// Zurück-Button
	$('.lbite-back-button').on('click', function() {
		showStep('location');
		resetTimeSelection();
	});

	// Zeit-Option auswählen
	$('.lbite-time-option').on('click', function() {
		const $option = $(this);

		$('.lbite-time-option').removeClass('selected');
		$option.addClass('selected');

		selectedTimeType = $option.data('time-type');

		if (selectedTimeType === 'later') {
			$('.lbite-timeslot-selection').slideDown();
			// Zeitslots laden
			if ($('#lbite-pickup-date').val()) {
				loadTimeslots();
			}
		} else {
			// Loading-State anzeigen
			$option.addClass('loading');
			$('.lbite-timeslot-selection').slideUp();

			// Direkt weiterleiten bei "sofort"
			confirmSelection('now', null);
		}
	});

	// Datum-Änderung
	$('#lbite-pickup-date').on('change', function() {
		validateSelectedDate();
		loadTimeslots();
	});

	// Zeit bestätigen (später)
	$('.lbite-confirm-time').on('click', function() {
		const $btn = $(this);
		const pickupTime = $('#lbite-pickup-time').val();

		if (!pickupTime) {
			alert(lbiteData.strings.selectTime);
			return;
		}

		// Button auf Loading setzen
		$btn.addClass('loading');

		confirmSelection('later', pickupTime);
	});

	// Ohne Zeit-Auswahl fortfahren
	$('.lbite-confirm-no-time').on('click', function() {
		const $btn = $(this);
		$btn.addClass('loading');
		confirmSelection('now', null);
	});

	// Zeitslots laden
	function loadTimeslots() {
		const date = $('#lbite-pickup-date').val();

		if (!selectedLocationId || !date) {
			return;
		}

		const $select = $('#lbite-pickup-time');
		$select.html('<option value=""><?php echo esc_js( __( 'Laden...', 'libre-bite' ) ); ?></option>').prop('disabled', true);

		// Visuelles Feedback
		$select.css('opacity', '0.6');

		$.ajax({
			url: lbiteData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lbite_get_timeslots',
				nonce: lbiteData.nonce,
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
		$('.lbite-loading-overlay').fadeIn(200);

		$.ajax({
			url: lbiteData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lbite_set_location',
				nonce: lbiteData.nonce,
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
					$('.lbite-loading-overlay').fadeOut(200);
					alert(response.data.message || 'Fehler beim Speichern');
					// Loading States zurücksetzen
					$('.lbite-button, .lbite-time-option').removeClass('loading');
				}
			},
			error: function() {
				$('.lbite-loading-overlay').fadeOut(200);
				alert('Ein Fehler ist aufgetreten');
				// Loading States zurücksetzen
				$('.lbite-button, .lbite-time-option').removeClass('loading');
			}
		});
	}

	// Schritt wechseln
	function showStep(step) {
		$('.lbite-step').removeClass('active');
		$('#lbite-step-' + step).addClass('active');
	}

	// Zeit-Auswahl zurücksetzen
	function resetTimeSelection() {
		$('.lbite-time-option').removeClass('selected');
		$('.lbite-timeslot-selection').hide();
		selectedTimeType = null;
	}

	// Geschlossene Tage vom Server abrufen
	let closedDaysCache = [];

	function updateDisabledDates() {
		if (!selectedLocationId) {
			return;
		}

		$.ajax({
			url: lbiteData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lbite_get_opening_days',
				nonce: lbiteData.nonce,
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
		const selectedDate = $('#lbite-pickup-date').val();
		if (!selectedDate || closedDaysCache.length === 0) {
			$('#lbite-date-error').slideUp(200);
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
				$('#lbite-next-opening').html('<br><?php echo esc_js( __( 'Nächste Öffnung:', 'libre-bite' ) ); ?> <strong>' + formattedDate + '</strong>');
			} else {
				$('#lbite-next-opening').html('');
			}

			// Inline Fehlermeldung anzeigen (bleibt sichtbar!)
			$('#lbite-date-error').slideDown(300);
			$('#lbite-pickup-date').addClass('lbite-error-input');
		} else {
			// Fehlermeldung ausblenden wenn gültiges Datum
			$('#lbite-date-error').slideUp(200);
			$('#lbite-pickup-date').removeClass('lbite-error-input');
			$('#lbite-next-opening').html('');
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
		$('.lbite-location-card[data-location-id="' + locationParam + '"]').trigger('click');
	}
});
<?php wp_add_inline_script( 'lbite-frontend', ob_get_clean() ); ?>
