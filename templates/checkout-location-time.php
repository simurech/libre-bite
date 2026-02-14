<?php
/**
 * Template: Standort & Zeitwahl im Checkout
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$location = $location_id ? get_post( $location_id ) : null;
$location_image_id = $location_id ? get_post_meta( $location_id, '_lbite_location_image', true ) : '';
$location_image_url = $location_image_id ? wp_get_attachment_image_url( $location_image_id, 'thumbnail' ) : '';
?>

<div class="lbite-checkout-selection">
	<h3><?php esc_html_e( 'Standort & Abholzeit', 'libre-bite' ); ?></h3>

	<!-- Versteckte Felder für das Formular -->
	<input type="hidden" name="lbite_location_id" id="lbite_location_id" value="<?php echo esc_attr( $location_id ); ?>" required>
	<input type="hidden" name="lbite_order_type" id="lbite_order_type" value="<?php echo esc_attr( $order_type ); ?>" required>
	<input type="hidden" name="lbite_pickup_time" id="lbite_pickup_time" value="<?php echo esc_attr( $pickup_time ); ?>">

	<?php if ( $location ) : ?>
		<!-- Anzeige der aktuellen Auswahl -->
		<div class="lbite-current-selection">
			<div class="lbite-selection-display">
				<?php if ( $location_image_url ) : ?>
					<div class="lbite-location-image">
						<img src="<?php echo esc_url( $location_image_url ); ?>" alt="<?php echo esc_attr( $location->post_title ); ?>">
					</div>
				<?php else : ?>
					<div class="lbite-location-image lbite-location-placeholder">
						<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
							<circle cx="12" cy="10" r="3"></circle>
						</svg>
					</div>
				<?php endif; ?>

				<div class="lbite-selection-details">
					<div class="lbite-location-name">
						<strong><?php echo esc_html( $location->post_title ); ?></strong>
					</div>
					<div class="lbite-order-info">
						<?php if ( 'now' === $order_type ) : ?>
							<span class="lbite-badge lbite-badge-now">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<circle cx="12" cy="12" r="10"></circle>
									<polyline points="12 6 12 12 16 14"></polyline>
								</svg>
								<?php esc_html_e( 'Sofort bestellen', 'libre-bite' ); ?>
							</span>
						<?php else : ?>
							<span class="lbite-badge lbite-badge-later">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
									<line x1="16" y1="2" x2="16" y2="6"></line>
									<line x1="8" y1="2" x2="8" y2="6"></line>
									<line x1="3" y1="10" x2="21" y2="10"></line>
								</svg>
								<?php
								// Datum und Zeit formatieren
								$datetime = DateTime::createFromFormat( 'Y-m-d H:i', $pickup_time );
								$formatted_date = $datetime ? $datetime->format( 'd.m.Y' ) : '';
								$formatted_time = $datetime ? $datetime->format( 'H:i' ) : $pickup_time;
								?>
								<?php esc_html_e( 'Vorbestellen:', 'libre-bite' ); ?> <?php echo esc_html( $formatted_date . ' ' . $formatted_time ); ?> <?php esc_html_e( 'Uhr', 'libre-bite' ); ?>
							</span>
						<?php endif; ?>
					</div>
				</div>

				<button type="button" class="lbite-change-btn" id="lbite-change-selection">
					<?php esc_html_e( 'Ändern', 'libre-bite' ); ?>
				</button>
			</div>
		</div>
	<?php else : ?>
		<div class="lbite-no-selection">
			<p><?php esc_html_e( 'Bitte wählen Sie einen Standort und eine Abholzeit.', 'libre-bite' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Bearbeitungsformular (zunächst versteckt) -->
	<div class="lbite-edit-form" style="<?php echo $location ? 'display: none;' : ''; ?>">
		<div class="lbite-form-group">
			<label>
				<?php esc_html_e( 'Standort', 'libre-bite' ); ?> <span class="required">*</span>
			</label>
			<select id="lbite_location_select" class="lbite-select">
				<option value=""><?php esc_html_e( 'Standort wählen...', 'libre-bite' ); ?></option>
				<?php foreach ( $locations as $loc ) : ?>
					<option value="<?php echo esc_attr( $loc->ID ); ?>" <?php selected( $location_id, $loc->ID ); ?>>
						<?php echo esc_html( $loc->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="lbite-form-group">
			<label>
				<?php esc_html_e( 'Bestellart', 'libre-bite' ); ?> <span class="required">*</span>
			</label>
			<div class="lbite-radio-group">
				<label class="lbite-radio-option">
					<input type="radio" name="lbite_order_type_select" value="now" <?php checked( $order_type, 'now' ); ?>>
					<span><?php esc_html_e( 'Sofort bestellen', 'libre-bite' ); ?></span>
				</label>
				<label class="lbite-radio-option">
					<input type="radio" name="lbite_order_type_select" value="later" <?php checked( $order_type, 'later' ); ?>>
					<span><?php esc_html_e( 'Für später vorbestellen', 'libre-bite' ); ?></span>
				</label>
			</div>
		</div>

		<div class="lbite-form-group lbite-pickup-time-group" style="display: none;">
			<label>
				<?php esc_html_e( 'Abholdatum', 'libre-bite' ); ?> <span class="required">*</span>
			</label>
			<input type="date" id="lbite_pickup_date_select" class="lbite-select" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
			<div class="lbite-date-error" id="lbite_date_error" style="display: none;">
				<span class="dashicons dashicons-info"></span>
				<span class="lbite-error-message">
					<span id="lbite_next_opening"></span>
				</span>
			</div>
		</div>

		<div class="lbite-form-group lbite-pickup-time-group" style="display: none;">
			<label>
				<?php esc_html_e( 'Abholzeit', 'libre-bite' ); ?> <span class="required">*</span>
			</label>
			<select id="lbite_pickup_time_select" class="lbite-select">
				<option value=""><?php esc_html_e( 'Zeit wählen...', 'libre-bite' ); ?></option>
			</select>
		</div>

		<div class="lbite-form-actions">
			<button type="button" class="lbite-save-btn" id="lbite-save-selection">
				<?php esc_html_e( 'Übernehmen', 'libre-bite' ); ?>
			</button>
			<?php if ( $location ) : ?>
				<button type="button" class="lbite-cancel-btn" id="lbite-cancel-selection">
					<?php esc_html_e( 'Abbrechen', 'libre-bite' ); ?>
				</button>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php ob_start(); ?>
jQuery(document).ready(function($) {
	// Ändern-Button
	$('#lbite-change-selection').on('click', function() {
		$('.lbite-current-selection').slideUp(300);
		$('.lbite-edit-form').slideDown(300);
	});

	// Abbrechen-Button
	$('#lbite-cancel-selection').on('click', function() {
		$('.lbite-edit-form').slideUp(300);
		$('.lbite-current-selection').slideDown(300);
	});

	// Bestellart ändern
	$('input[name="lbite_order_type_select"]').on('change', function() {
		if ($(this).val() === 'later') {
			$('.lbite-pickup-time-group').slideDown(300);
			loadTimeslots();
		} else {
			$('.lbite-pickup-time-group').slideUp(300);
		}
	});

	// Initial anzeigen wenn "later" gewählt
	if ($('input[name="lbite_order_type_select"]:checked').val() === 'later') {
		$('.lbite-pickup-time-group').show();
		if ($('#lbite_location_select').val()) {
			updateDisabledDates();
			loadTimeslots();
		}
	}

	// Zeitslots laden wenn Standort geändert wird
	$('#lbite_location_select').on('change', function() {
		if ($('input[name="lbite_order_type_select"]:checked').val() === 'later') {
			loadTimeslots();
			updateDisabledDates();
		}
	});

	// Zeitslots laden wenn Datum geändert wird
	$('#lbite_pickup_date_select').on('change', function() {
		validateSelectedDate();
		loadTimeslots();
	});

	// Übernehmen-Button
	$('#lbite-save-selection').on('click', function() {
		var $btn = $(this);
		var locationId = $('#lbite_location_select').val();
		var orderType = $('input[name="lbite_order_type_select"]:checked').val();
		var pickupDate = $('#lbite_pickup_date_select').val();
		var pickupTimeSlot = $('#lbite_pickup_time_select').val();

		// Validierung
		if (!locationId) {
			alert('<?php esc_html_e( 'Bitte wählen Sie einen Standort.', 'libre-bite' ); ?>');
			return;
		}

		if (!orderType) {
			alert('<?php esc_html_e( 'Bitte wählen Sie eine Bestellart.', 'libre-bite' ); ?>');
			return;
		}

		if (orderType === 'later' && !pickupTimeSlot) {
			alert('<?php esc_html_e( 'Bitte wählen Sie eine Abholzeit.', 'libre-bite' ); ?>');
			return;
		}

		// Button deaktivieren
		$btn.prop('disabled', true).text('<?php esc_html_e( 'Speichere...', 'libre-bite' ); ?>');

		// AJAX-Anfrage um Session zu aktualisieren
		$.ajax({
			url: lbiteData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lbite_set_location',
				nonce: lbiteData.nonce,
				location_id: locationId,
				order_type: orderType,
				pickup_time: pickupTimeSlot
			},
			success: function(response) {
				if (response.success) {
					// Versteckte Felder aktualisieren
					$('#lbite_location_id').val(locationId);
					$('#lbite_order_type').val(orderType);
					$('#lbite_pickup_time').val(pickupTimeSlot);

					// Checkout aktualisieren
					$('body').trigger('update_checkout');

					// Seite neu laden
					location.reload();
				} else {
					alert(response.data.message || '<?php esc_html_e( 'Fehler beim Speichern.', 'libre-bite' ); ?>');
					$btn.prop('disabled', false).text('<?php esc_html_e( 'Übernehmen', 'libre-bite' ); ?>');
				}
			},
			error: function() {
				alert('<?php esc_html_e( 'Fehler beim Speichern. Bitte versuchen Sie es erneut.', 'libre-bite' ); ?>');
				$btn.prop('disabled', false).text('<?php esc_html_e( 'Übernehmen', 'libre-bite' ); ?>');
			}
		});
	});

	function loadTimeslots() {
		var locationId = $('#lbite_location_select').val();
		var selectedDate = $('#lbite_pickup_date_select').val() || new Date().toISOString().split('T')[0];

		if (!locationId) {
			return;
		}

		$.ajax({
			url: lbiteData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lbite_get_timeslots',
				nonce: lbiteData.nonce,
				location_id: locationId,
				date: selectedDate
			},
			success: function(response) {
				if (response.success && response.data.timeslots) {
					var $select = $('#lbite_pickup_time_select');
					var currentValue = $('#lbite_pickup_time').val();
					$select.empty();
					$select.append('<option value=""><?php esc_html_e( 'Zeit wählen...', 'libre-bite' ); ?></option>');

					response.data.timeslots.forEach(function(slot) {
						var selected = currentValue === slot.value ? ' selected' : '';
						$select.append('<option value="' + slot.value + '"' + selected + '>' + slot.label + '</option>');
					});
				}
			}
		});
	}

	// Geschlossene Tage vom Server abrufen
	var closedDaysCache = [];

	function updateDisabledDates() {
		var locationId = $('#lbite_location_select').val();
		if (!locationId) {
			return;
		}

		$.ajax({
			url: lbiteData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lbite_get_opening_days',
				nonce: lbiteData.nonce,
				location_id: locationId
			},
			success: function(response) {
				if (response.success && response.data.closed_days) {
					closedDaysCache = response.data.closed_days;

					// Erstes offenes Datum als Standardwert setzen
					setInitialOpenDate();
				}
			}
		});
	}

	// Initiales Datum auf ersten offenen Tag setzen
	function setInitialOpenDate() {
		var today = new Date();
		var dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
		var currentDate = new Date(today);

		// Maximal 14 Tage durchsuchen
		for (var i = 0; i <= 14; i++) {
			var dayName = dayNames[currentDate.getDay()];

			if (!closedDaysCache.includes(dayName)) {
				// Ersten offenen Tag gefunden
				var year = currentDate.getFullYear();
				var month = ('0' + (currentDate.getMonth() + 1)).slice(-2);
				var day = ('0' + currentDate.getDate()).slice(-2);
				var isoDate = year + '-' + month + '-' + day;

				$('#lbite_pickup_date_select').val(isoDate);
				loadTimeslots();
				return;
			}

			// Nächsten Tag prüfen
			currentDate.setDate(currentDate.getDate() + 1);
		}
	}

	// Ausgewähltes Datum validieren und ggf. korrigieren
	function validateSelectedDate() {
		var selectedDate = $('#lbite_pickup_date_select').val();
		if (!selectedDate || closedDaysCache.length === 0) {
			$('#lbite_date_error').slideUp(200);
			return;
		}

		var date = new Date(selectedDate + 'T00:00:00');
		var dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
		var dayName = dayNames[date.getDay()];

		if (closedDaysCache.includes(dayName)) {
			// Nächsten Öffnungstag finden
			var nextOpenDate = findNextOpenDate(selectedDate);

			if (nextOpenDate) {
				// Automatisch zum nächsten offenen Tag wechseln
				var year = nextOpenDate.getFullYear();
				var month = ('0' + (nextOpenDate.getMonth() + 1)).slice(-2);
				var day = ('0' + nextOpenDate.getDate()).slice(-2);
				var isoDate = year + '-' + month + '-' + day;

				// Datum setzen
				$('#lbite_pickup_date_select').val(isoDate);

				// Hinweis anzeigen dass Datum geändert wurde
				var formattedDate = formatDate(nextOpenDate);
				$('#lbite_next_opening').html('<?php echo esc_js( __( 'Automatisch geändert zu:', 'libre-bite' ) ); ?> <strong>' + formattedDate + '</strong>');
				$('#lbite_date_error').slideDown(300);
				$('#lbite_pickup_date_select').addClass('lbite-error-input');

				// Nach kurzer Zeit Hinweis ausblenden und Timeslots laden
				setTimeout(function() {
					$('#lbite_date_error').slideUp(200);
					$('#lbite_pickup_date_select').removeClass('lbite-error-input');
					loadTimeslots();
				}, 2500);
			} else {
				// Kein offener Tag in den nächsten 14 Tagen
				$('#lbite_next_opening').html('<?php echo esc_js( __( 'Kein Öffnungstag in den nächsten 14 Tagen gefunden.', 'libre-bite' ) ); ?>');
				$('#lbite_date_error').slideDown(300);
				$('#lbite_pickup_date_select').addClass('lbite-error-input');
			}
		} else {
			// Fehlermeldung ausblenden wenn gültiges Datum
			$('#lbite_date_error').slideUp(200);
			$('#lbite_pickup_date_select').removeClass('lbite-error-input');
			$('#lbite_next_opening').html('');
		}
	}

	// Nächsten Öffnungstag finden
	function findNextOpenDate(fromDate) {
		var dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
		var currentDate = new Date(fromDate + 'T00:00:00');

		// Maximal 14 Tage in die Zukunft suchen
		for (var i = 1; i <= 14; i++) {
			currentDate.setDate(currentDate.getDate() + 1);
			var dayName = dayNames[currentDate.getDay()];

			if (!closedDaysCache.includes(dayName)) {
				return currentDate;
			}
		}

		return null;
	}

	// Datum formatieren (dd.mm.YYYY)
	function formatDate(date) {
		var day = ('0' + date.getDate()).slice(-2);
		var month = ('0' + (date.getMonth() + 1)).slice(-2);
		var year = date.getFullYear();

		return day + '.' + month + '.' + year;
	}
});
<?php wp_add_inline_script( 'lbite-frontend', ob_get_clean() ); ?>

