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
$location_image_id = $location_id ? get_post_meta( $location_id, '_lb_location_image', true ) : '';
$location_image_url = $location_image_id ? wp_get_attachment_image_url( $location_image_id, 'thumbnail' ) : '';
?>

<div class="lb-checkout-selection">
	<h3><?php esc_html_e( 'Standort & Abholzeit', 'libre-bite' ); ?></h3>

	<!-- Versteckte Felder für das Formular -->
	<input type="hidden" name="lb_location_id" id="lb_location_id" value="<?php echo esc_attr( $location_id ); ?>" required>
	<input type="hidden" name="lb_order_type" id="lb_order_type" value="<?php echo esc_attr( $order_type ); ?>" required>
	<input type="hidden" name="lb_pickup_time" id="lb_pickup_time" value="<?php echo esc_attr( $pickup_time ); ?>">

	<?php if ( $location ) : ?>
		<!-- Anzeige der aktuellen Auswahl -->
		<div class="lb-current-selection">
			<div class="lb-selection-display">
				<?php if ( $location_image_url ) : ?>
					<div class="lb-location-image">
						<img src="<?php echo esc_url( $location_image_url ); ?>" alt="<?php echo esc_attr( $location->post_title ); ?>">
					</div>
				<?php else : ?>
					<div class="lb-location-image lb-location-placeholder">
						<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
							<circle cx="12" cy="10" r="3"></circle>
						</svg>
					</div>
				<?php endif; ?>

				<div class="lb-selection-details">
					<div class="lb-location-name">
						<strong><?php echo esc_html( $location->post_title ); ?></strong>
					</div>
					<div class="lb-order-info">
						<?php if ( 'now' === $order_type ) : ?>
							<span class="lb-badge lb-badge-now">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<circle cx="12" cy="12" r="10"></circle>
									<polyline points="12 6 12 12 16 14"></polyline>
								</svg>
								<?php esc_html_e( 'Sofort bestellen', 'libre-bite' ); ?>
							</span>
						<?php else : ?>
							<span class="lb-badge lb-badge-later">
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

				<button type="button" class="lb-change-btn" id="lb-change-selection">
					<?php esc_html_e( 'Ändern', 'libre-bite' ); ?>
				</button>
			</div>
		</div>
	<?php else : ?>
		<div class="lb-no-selection">
			<p><?php esc_html_e( 'Bitte wählen Sie einen Standort und eine Abholzeit.', 'libre-bite' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Bearbeitungsformular (zunächst versteckt) -->
	<div class="lb-edit-form" style="<?php echo $location ? 'display: none;' : ''; ?>">
		<div class="lb-form-group">
			<label>
				<?php esc_html_e( 'Standort', 'libre-bite' ); ?> <span class="required">*</span>
			</label>
			<select id="lb_location_select" class="lb-select">
				<option value=""><?php esc_html_e( 'Standort wählen...', 'libre-bite' ); ?></option>
				<?php foreach ( $locations as $loc ) : ?>
					<option value="<?php echo esc_attr( $loc->ID ); ?>" <?php selected( $location_id, $loc->ID ); ?>>
						<?php echo esc_html( $loc->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="lb-form-group">
			<label>
				<?php esc_html_e( 'Bestellart', 'libre-bite' ); ?> <span class="required">*</span>
			</label>
			<div class="lb-radio-group">
				<label class="lb-radio-option">
					<input type="radio" name="lb_order_type_select" value="now" <?php checked( $order_type, 'now' ); ?>>
					<span><?php esc_html_e( 'Sofort bestellen', 'libre-bite' ); ?></span>
				</label>
				<label class="lb-radio-option">
					<input type="radio" name="lb_order_type_select" value="later" <?php checked( $order_type, 'later' ); ?>>
					<span><?php esc_html_e( 'Für später vorbestellen', 'libre-bite' ); ?></span>
				</label>
			</div>
		</div>

		<div class="lb-form-group lb-pickup-time-group" style="display: none;">
			<label>
				<?php esc_html_e( 'Abholdatum', 'libre-bite' ); ?> <span class="required">*</span>
			</label>
			<input type="date" id="lb_pickup_date_select" class="lb-select" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
			<div class="lb-date-error" id="lb_date_error" style="display: none;">
				<span class="dashicons dashicons-info"></span>
				<span class="lb-error-message">
					<span id="lb_next_opening"></span>
				</span>
			</div>
		</div>

		<div class="lb-form-group lb-pickup-time-group" style="display: none;">
			<label>
				<?php esc_html_e( 'Abholzeit', 'libre-bite' ); ?> <span class="required">*</span>
			</label>
			<select id="lb_pickup_time_select" class="lb-select">
				<option value=""><?php esc_html_e( 'Zeit wählen...', 'libre-bite' ); ?></option>
			</select>
		</div>

		<div class="lb-form-actions">
			<button type="button" class="lb-save-btn" id="lb-save-selection">
				<?php esc_html_e( 'Übernehmen', 'libre-bite' ); ?>
			</button>
			<?php if ( $location ) : ?>
				<button type="button" class="lb-cancel-btn" id="lb-cancel-selection">
					<?php esc_html_e( 'Abbrechen', 'libre-bite' ); ?>
				</button>
			<?php endif; ?>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Ändern-Button
	$('#lb-change-selection').on('click', function() {
		$('.lb-current-selection').slideUp(300);
		$('.lb-edit-form').slideDown(300);
	});

	// Abbrechen-Button
	$('#lb-cancel-selection').on('click', function() {
		$('.lb-edit-form').slideUp(300);
		$('.lb-current-selection').slideDown(300);
	});

	// Bestellart ändern
	$('input[name="lb_order_type_select"]').on('change', function() {
		if ($(this).val() === 'later') {
			$('.lb-pickup-time-group').slideDown(300);
			loadTimeslots();
		} else {
			$('.lb-pickup-time-group').slideUp(300);
		}
	});

	// Initial anzeigen wenn "later" gewählt
	if ($('input[name="lb_order_type_select"]:checked').val() === 'later') {
		$('.lb-pickup-time-group').show();
		if ($('#lb_location_select').val()) {
			updateDisabledDates();
			loadTimeslots();
		}
	}

	// Zeitslots laden wenn Standort geändert wird
	$('#lb_location_select').on('change', function() {
		if ($('input[name="lb_order_type_select"]:checked').val() === 'later') {
			loadTimeslots();
			updateDisabledDates();
		}
	});

	// Zeitslots laden wenn Datum geändert wird
	$('#lb_pickup_date_select').on('change', function() {
		validateSelectedDate();
		loadTimeslots();
	});

	// Übernehmen-Button
	$('#lb-save-selection').on('click', function() {
		var $btn = $(this);
		var locationId = $('#lb_location_select').val();
		var orderType = $('input[name="lb_order_type_select"]:checked').val();
		var pickupDate = $('#lb_pickup_date_select').val();
		var pickupTimeSlot = $('#lb_pickup_time_select').val();

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
			url: lbData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lb_set_location',
				nonce: lbData.nonce,
				location_id: locationId,
				order_type: orderType,
				pickup_time: pickupTimeSlot
			},
			success: function(response) {
				if (response.success) {
					// Versteckte Felder aktualisieren
					$('#lb_location_id').val(locationId);
					$('#lb_order_type').val(orderType);
					$('#lb_pickup_time').val(pickupTimeSlot);

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
		var locationId = $('#lb_location_select').val();
		var selectedDate = $('#lb_pickup_date_select').val() || new Date().toISOString().split('T')[0];

		if (!locationId) {
			return;
		}

		$.ajax({
			url: lbData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lb_get_timeslots',
				nonce: lbData.nonce,
				location_id: locationId,
				date: selectedDate
			},
			success: function(response) {
				if (response.success && response.data.timeslots) {
					var $select = $('#lb_pickup_time_select');
					var currentValue = $('#lb_pickup_time').val();
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
		var locationId = $('#lb_location_select').val();
		if (!locationId) {
			return;
		}

		$.ajax({
			url: lbData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lb_get_opening_days',
				nonce: lbData.nonce,
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

				$('#lb_pickup_date_select').val(isoDate);
				loadTimeslots();
				return;
			}

			// Nächsten Tag prüfen
			currentDate.setDate(currentDate.getDate() + 1);
		}
	}

	// Ausgewähltes Datum validieren und ggf. korrigieren
	function validateSelectedDate() {
		var selectedDate = $('#lb_pickup_date_select').val();
		if (!selectedDate || closedDaysCache.length === 0) {
			$('#lb_date_error').slideUp(200);
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
				$('#lb_pickup_date_select').val(isoDate);

				// Hinweis anzeigen dass Datum geändert wurde
				var formattedDate = formatDate(nextOpenDate);
				$('#lb_next_opening').html('<?php echo esc_js( __( 'Automatisch geändert zu:', 'libre-bite' ) ); ?> <strong>' + formattedDate + '</strong>');
				$('#lb_date_error').slideDown(300);
				$('#lb_pickup_date_select').addClass('lb-error-input');

				// Nach kurzer Zeit Hinweis ausblenden und Timeslots laden
				setTimeout(function() {
					$('#lb_date_error').slideUp(200);
					$('#lb_pickup_date_select').removeClass('lb-error-input');
					loadTimeslots();
				}, 2500);
			} else {
				// Kein offener Tag in den nächsten 14 Tagen
				$('#lb_next_opening').html('<?php echo esc_js( __( 'Kein Öffnungstag in den nächsten 14 Tagen gefunden.', 'libre-bite' ) ); ?>');
				$('#lb_date_error').slideDown(300);
				$('#lb_pickup_date_select').addClass('lb-error-input');
			}
		} else {
			// Fehlermeldung ausblenden wenn gültiges Datum
			$('#lb_date_error').slideUp(200);
			$('#lb_pickup_date_select').removeClass('lb-error-input');
			$('#lb_next_opening').html('');
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
</script>

<style>
.lb-checkout-selection {
	background: #fff;
	border: 1px solid #e0e0e0;
	border-radius: 8px;
	padding: 20px;
	margin-bottom: 20px;
}

.lb-checkout-selection h3 {
	margin-top: 0;
	margin-bottom: 15px;
	font-size: 18px;
	color: #333;
}

/* Aktuelle Auswahl Anzeige */
.lb-selection-display {
	display: flex;
	align-items: center;
	gap: 15px;
	padding: 15px;
	background: #f8f9fa;
	border-radius: 8px;
	border: 2px solid #e8e8e8;
}

.lb-location-image {
	width: 60px;
	height: 60px;
	border-radius: 8px;
	overflow: hidden;
	flex-shrink: 0;
}

.lb-location-image img {
	width: 100%;
	height: 100%;
	object-fit: cover;
}

.lb-location-placeholder {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	display: flex;
	align-items: center;
	justify-content: center;
	color: #fff;
}

.lb-selection-details {
	flex: 1;
}

.lb-location-name {
	font-size: 16px;
	margin-bottom: 5px;
	color: #333;
}

.lb-order-info {
	display: flex;
	gap: 10px;
	flex-wrap: wrap;
}

.lb-badge {
	display: inline-flex;
	align-items: center;
	gap: 5px;
	padding: 5px 12px;
	border-radius: 20px;
	font-size: 13px;
	font-weight: 500;
}

.lb-badge svg {
	width: 16px;
	height: 16px;
}

.lb-badge-now {
	background: #d4edda;
	color: #155724;
}

.lb-badge-later {
	background: #fff3cd;
	color: #856404;
}

.lb-change-btn {
	padding: 8px 20px;
	background: #0073aa;
	color: #fff;
	border: none;
	border-radius: 6px;
	cursor: pointer;
	font-size: 14px;
	font-weight: 500;
	transition: background 0.3s;
	flex-shrink: 0;
}

.lb-change-btn:hover {
	background: #005a87;
}

/* Keine Auswahl */
.lb-no-selection {
	text-align: center;
	padding: 30px;
	color: #666;
}

/* Bearbeitungsformular */
.lb-edit-form {
	padding-top: 15px;
}

.lb-form-group {
	margin-bottom: 20px;
}

.lb-form-group label {
	display: block;
	font-weight: 600;
	margin-bottom: 8px;
	color: #333;
}

.lb-form-group label .required {
	color: #dc3232;
}

.lb-select {
	width: 100%;
	padding: 10px 12px;
	border: 1px solid #ddd;
	border-radius: 6px;
	font-size: 14px;
	background: #fff;
	cursor: pointer;
	transition: border-color 0.3s;
}

.lb-select:focus {
	outline: none;
	border-color: #0073aa;
	box-shadow: 0 0 0 1px #0073aa;
}

.lb-radio-group {
	display: flex;
	gap: 15px;
	flex-wrap: wrap;
}

.lb-radio-option {
	flex: 1;
	min-width: 150px;
	padding: 12px 15px;
	border: 2px solid #ddd;
	border-radius: 8px;
	cursor: pointer;
	transition: all 0.3s;
	display: flex;
	align-items: center;
	gap: 10px;
}

.lb-radio-option:hover {
	border-color: #999;
	background: #f8f9fa;
}

.lb-radio-option input[type="radio"] {
	margin: 0;
	cursor: pointer;
}

.lb-radio-option:has(input[type="radio"]:checked) {
	border-color: #0073aa;
	background: #f0f8ff;
}

.lb-radio-option span {
	font-weight: 500;
	color: #333;
}

.lb-form-actions {
	display: flex;
	gap: 10px;
	margin-top: 20px;
}

.lb-save-btn,
.lb-cancel-btn {
	padding: 10px 24px;
	border: none;
	border-radius: 6px;
	cursor: pointer;
	font-size: 14px;
	font-weight: 500;
	transition: all 0.3s;
}

.lb-save-btn {
	background: #0073aa;
	color: #fff;
}

.lb-save-btn:hover {
	background: #005a87;
}

.lb-cancel-btn {
	background: #f3f3f3;
	color: #333;
}

.lb-cancel-btn:hover {
	background: #e0e0e0;
}

/* Responsive */
@media (max-width: 600px) {
	.lb-selection-display {
		flex-direction: column;
		text-align: center;
	}

	.lb-radio-group {
		flex-direction: column;
	}

	.lb-radio-option {
		min-width: 100%;
	}

	.lb-form-actions {
		flex-direction: column;
	}

	.lb-save-btn,
	.lb-cancel-btn {
		width: 100%;
	}
}

/* Inline Hinweis für Datum */
.lb-date-error {
	display: flex;
	align-items: flex-start;
	gap: 8px;
	margin-top: 8px;
	padding: 10px 12px;
	background: #d4edda;
	border: 1px solid #28a745;
	border-radius: 6px;
	color: #155724;
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
	color: #28a745;
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
	color: #155724;
	font-weight: 600;
}

.lb-error-input {
	border-color: #28a745 !important;
	background-color: #f0fff4 !important;
}

/* Fix für abgeschnittenes Dropdown */
.lb-checkout-selection {
	overflow: visible !important;
}

.lb-edit-form {
	overflow: visible !important;
}

.lb-form-group {
	overflow: visible !important;
	position: relative;
}

.lb-select {
	position: relative;
	z-index: 1;
}

/* Select Dropdown besser sichtbar */
select.lb-select {
	-webkit-appearance: none;
	-moz-appearance: none;
	appearance: none;
	background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
	background-repeat: no-repeat;
	background-position: right 12px center;
	padding-right: 36px;
}

/* Sicherstellen dass Optionen sichtbar sind */
select.lb-select option {
	padding: 8px 12px;
}
</style>
