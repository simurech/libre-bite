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

<div class="lbite-location-selector-inline">
	<form method="post" class="lbite-location-form">
		<?php wp_nonce_field( 'lbite_location_selector', 'lbite_location_nonce' ); ?>

		<!-- Standort-Auswahl -->
		<div class="lbite-form-group">
			<label for="lbite-location-select">
				<?php esc_html_e( 'Standort wählen', 'libre-bite' ); ?>
				<span class="required">*</span>
			</label>
			<select id="lbite-location-select" name="lbite_location_id" class="lbite-select" required>
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
			<div class="lbite-form-group">
				<label><?php esc_html_e( 'Wann möchten Sie bestellen?', 'libre-bite' ); ?></label>
				<div class="lbite-radio-group">
					<label class="lbite-radio-label">
						<input type="radio" name="lbite_order_type" value="now" <?php checked( $order_type, 'now' ); ?>>
						<span><?php esc_html_e( 'Sofort', 'libre-bite' ); ?></span>
					</label>
					<label class="lbite-radio-label">
						<input type="radio" name="lbite_order_type" value="later" <?php checked( $order_type, 'later' ); ?>>
						<span><?php esc_html_e( 'Für später vorbestellen', 'libre-bite' ); ?></span>
					</label>
				</div>
			</div>

			<!-- Zeitslot-Auswahl (nur bei "später") -->
			<div class="lbite-form-group lbite-timeslot-group" style="display: none;">
				<label for="lbite-pickup-date">
					<?php esc_html_e( 'Datum', 'libre-bite' ); ?>
					<span class="required">*</span>
				</label>
				<input type="date" id="lbite-pickup-date" class="lbite-input" min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">

				<label for="lbite-pickup-time" style="margin-top: 10px;">
					<?php esc_html_e( 'Uhrzeit', 'libre-bite' ); ?>
					<span class="required">*</span>
				</label>
				<select id="lbite-pickup-time" name="lbite_pickup_time" class="lbite-select">
					<option value=""><?php esc_html_e( 'Bitte Datum wählen', 'libre-bite' ); ?></option>
				</select>
			</div>
		<?php endif; ?>

		<!-- Submit Button -->
		<div class="lbite-form-group">
			<button type="submit" class="lbite-button lbite-button-primary">
				<?php esc_html_e( 'Auswahl bestätigen', 'libre-bite' ); ?>
			</button>
		</div>

		<!-- Aktuelle Auswahl anzeigen -->
		<?php if ( $location_id ) : ?>
			<div class="lbite-current-selection">
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


<?php ob_start(); ?>
jQuery(document).ready(function($) {
	const $form = $('.lbite-location-form');
	const $locationSelect = $('#lbite-location-select');
	const $orderTypeRadios = $('input[name="lbite_order_type"]');
	const $timeslotGroup = $('.lbite-timeslot-group');
	const $pickupDate = $('#lbite-pickup-date');
	const $pickupTime = $('#lbite-pickup-time');

	// Zeitslot-Gruppe anzeigen/verstecken
	function toggleTimeslotGroup() {
		const orderType = $('input[name="lbite_order_type"]:checked').val();
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
			url: lbiteData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lbite_get_timeslots',
				nonce: lbiteData.nonce,
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
		const orderType = $('input[name="lbite_order_type"]:checked').val() || 'now';
		const pickupTime = orderType === 'later' ? $pickupTime.val() : '';

		if (!locationId) {
			alert(lbiteData.strings.selectLocation);
			return;
		}

		if (orderType === 'later' && !pickupTime) {
			alert(lbiteData.strings.selectTime);
			return;
		}

		// Per AJAX speichern
		$.ajax({
			url: lbiteData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lbite_set_location',
				nonce: lbiteData.nonce,
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
<?php wp_add_inline_script( 'lbite-frontend', ob_get_clean() ); ?>
