<?php
/**
 * Template: Inline Standort- & Zeitauswahl (Shortcode)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="lb-location-selector-inline">
	<form method="post" class="lb-location-form">
		<?php wp_nonce_field( 'lb_location_selector', 'lb_location_nonce' ); ?>

		<!-- Standort-Auswahl -->
		<div class="lb-form-group">
			<label for="lb-location-select">
				<?php esc_html_e( 'Standort wählen', 'libre-bite' ); ?>
				<span class="required">*</span>
			</label>
			<select id="lb-location-select" name="lb_location_id" class="lb-select" required>
				<option value=""><?php esc_html_e( 'Bitte wählen...', 'libre-bite' ); ?></option>
				<?php foreach ( $locations as $location ) : ?>
					<option value="<?php echo esc_attr( $location->ID ); ?>" <?php selected( $location_id, $location->ID ); ?>>
						<?php echo esc_html( $location->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<?php if ( 'yes' === $atts['show_time'] ) : ?>
			<!-- Bestelltyp-Auswahl -->
			<div class="lb-form-group">
				<label><?php esc_html_e( 'Wann möchten Sie bestellen?', 'libre-bite' ); ?></label>
				<div class="lb-radio-group">
					<label class="lb-radio-label">
						<input type="radio" name="lb_order_type" value="now" <?php checked( $order_type, 'now' ); ?>>
						<span><?php esc_html_e( 'Sofort', 'libre-bite' ); ?></span>
					</label>
					<label class="lb-radio-label">
						<input type="radio" name="lb_order_type" value="later" <?php checked( $order_type, 'later' ); ?>>
						<span><?php esc_html_e( 'Für später vorbestellen', 'libre-bite' ); ?></span>
					</label>
				</div>
			</div>

			<!-- Zeitslot-Auswahl (nur bei "später") -->
			<div class="lb-form-group lb-timeslot-group" style="display: none;">
				<label for="lb-pickup-date">
					<?php esc_html_e( 'Datum', 'libre-bite' ); ?>
					<span class="required">*</span>
				</label>
				<input type="date" id="lb-pickup-date" class="lb-input" min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">

				<label for="lb-pickup-time" style="margin-top: 10px;">
					<?php esc_html_e( 'Uhrzeit', 'libre-bite' ); ?>
					<span class="required">*</span>
				</label>
				<select id="lb-pickup-time" name="lb_pickup_time" class="lb-select">
					<option value=""><?php esc_html_e( 'Bitte Datum wählen', 'libre-bite' ); ?></option>
				</select>
			</div>
		<?php endif; ?>

		<!-- Submit Button -->
		<div class="lb-form-group">
			<button type="submit" class="lb-button lb-button-primary">
				<?php esc_html_e( 'Auswahl bestätigen', 'libre-bite' ); ?>
			</button>
		</div>

		<!-- Aktuelle Auswahl anzeigen -->
		<?php if ( $location_id ) : ?>
			<div class="lb-current-selection">
				<strong><?php esc_html_e( 'Aktuelle Auswahl:', 'libre-bite' ); ?></strong><br>
				<?php
				$location = get_post( $location_id );
				if ( $location ) {
					echo esc_html( $location->post_title );
					if ( 'later' === $order_type && $pickup_time ) {
						echo ' - ' . esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $pickup_time ) ) );
					} elseif ( 'now' === $order_type ) {
						echo ' - ' . esc_html__( 'Sofort', 'libre-bite' );
					}
				}
				?>
			</div>
		<?php endif; ?>
	</form>
</div>

<style>
.lb-location-selector-inline {
	max-width: 600px;
	margin: 20px auto;
	padding: 30px;
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
	border-radius: 4px;
	font-size: 16px;
	transition: border-color 0.3s;
}

.lb-select:focus,
.lb-input:focus {
	outline: none;
	border-color: #0073aa;
}

.lb-radio-group {
	display: flex;
	gap: 20px;
	flex-wrap: wrap;
}

.lb-radio-label {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 12px 20px;
	border: 2px solid #ddd;
	border-radius: 4px;
	cursor: pointer;
	transition: all 0.3s;
}

.lb-radio-label:hover {
	border-color: #0073aa;
	background: #f0f8ff;
}

.lb-radio-label input[type="radio"] {
	margin: 0;
}

.lb-radio-label input[type="radio"]:checked + span {
	font-weight: 600;
	color: #0073aa;
}

.lb-button {
	padding: 14px 30px;
	border: none;
	border-radius: 4px;
	font-size: 16px;
	font-weight: 600;
	cursor: pointer;
	transition: all 0.3s;
}

.lb-button-primary {
	background: #0073aa;
	color: #fff;
	width: 100%;
}

.lb-button-primary:hover {
	background: #005a87;
}

.lb-current-selection {
	margin-top: 20px;
	padding: 15px;
	background: #e7f7ff;
	border-left: 4px solid #0073aa;
	border-radius: 4px;
}

@media (max-width: 600px) {
	.lb-location-selector-inline {
		padding: 20px;
		margin: 10px;
	}

	.lb-radio-group {
		flex-direction: column;
		gap: 10px;
	}
}
</style>

<script>
jQuery(document).ready(function($) {
	const $form = $('.lb-location-form');
	const $locationSelect = $('#lb-location-select');
	const $orderTypeRadios = $('input[name="lb_order_type"]');
	const $timeslotGroup = $('.lb-timeslot-group');
	const $pickupDate = $('#lb-pickup-date');
	const $pickupTime = $('#lb-pickup-time');

	// Zeitslot-Gruppe anzeigen/verstecken
	function toggleTimeslotGroup() {
		const orderType = $('input[name="lb_order_type"]:checked').val();
		if (orderType === 'later') {
			$timeslotGroup.slideDown();
			// Zeitslots automatisch laden wenn Datum bereits gesetzt ist
			if ($pickupDate.val() && $locationSelect.val()) {
				$pickupDate.trigger('change');
			}
		} else {
			$timeslotGroup.slideUp();
		}
	}

	$orderTypeRadios.on('change', toggleTimeslotGroup);
	toggleTimeslotGroup();

	// Zeitslots laden wenn Datum gewählt wird
	$pickupDate.on('change', function() {
		const locationId = $locationSelect.val();
		const date = $(this).val();

		if (!locationId || !date) {
			return;
		}

		$pickupTime.html('<option value="">Laden...</option>').prop('disabled', true);

		$.ajax({
			url: lbData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lb_get_timeslots',
				nonce: lbData.nonce,
				location_id: locationId,
				date: date
			},
			success: function(response) {
				if (response.success && response.data.timeslots) {
					let options = '<option value=""><?php echo esc_js( __( 'Bitte wählen...', 'libre-bite' ) ); ?></option>';
					response.data.timeslots.forEach(function(slot) {
						options += '<option value="' + slot.value + '">' + slot.label + '</option>';
					});
					$pickupTime.html(options).prop('disabled', false);
				} else {
					$pickupTime.html('<option value=""><?php echo esc_js( __( 'Keine Zeitslots verfügbar', 'libre-bite' ) ); ?></option>');
				}
			},
			error: function() {
				$pickupTime.html('<option value=""><?php echo esc_js( __( 'Fehler beim Laden', 'libre-bite' ) ); ?></option>');
			}
		});
	});

	// Form-Submit
	$form.on('submit', function(e) {
		e.preventDefault();

		const locationId = $locationSelect.val();
		const orderType = $('input[name="lb_order_type"]:checked').val() || 'now';
		const pickupTime = orderType === 'later' ? $pickupTime.val() : '';

		if (!locationId) {
			alert(lbData.strings.selectLocation);
			return;
		}

		if (orderType === 'later' && !pickupTime) {
			alert(lbData.strings.selectTime);
			return;
		}

		// Per AJAX speichern
		$.ajax({
			url: lbData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lb_set_location',
				nonce: lbData.nonce,
				location_id: locationId,
				order_type: orderType,
				pickup_time: pickupTime
			},
			success: function(response) {
				if (response.success) {
					// Seite neu laden um Auswahl anzuzeigen
					location.reload();
				} else {
					alert(response.data.message || 'Fehler beim Speichern');
				}
			},
			error: function() {
				alert('Ein Fehler ist aufgetreten');
			}
		});
	});
});
</script>
