<?php
/**
 * Template: Standort-Auswahl Modal
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="lbite-location-modal" class="lbite-modal" style="display: none;">
	<div class="lbite-modal-overlay"></div>
	<div class="lbite-modal-content">
		<h2><?php esc_html_e( 'Select Location', 'libre-bite' ); ?></h2>
		<p><?php esc_html_e( 'Please select your desired location:', 'libre-bite' ); ?></p>

		<div class="lbite-location-list">
			<?php foreach ( $lbite_locations as $lbite_location ) : ?>
				<?php
				$lbite_street = get_post_meta( $lbite_location->ID, '_lbite_street', true );
				$lbite_zip    = get_post_meta( $lbite_location->ID, '_lbite_zip', true );
				$lbite_city   = get_post_meta( $lbite_location->ID, '_lbite_city', true );
				?>
				<div class="lbite-location-item" data-location-id="<?php echo esc_attr( $lbite_location->ID ); ?>">
					<h3><?php echo esc_html( $lbite_location->post_title ); ?></h3>
					<?php if ( $lbite_street || $lbite_city ) : ?>
						<p class="lbite-location-address">
							<?php echo esc_html( $lbite_street ); ?><br>
							<?php echo esc_html( $lbite_zip . ' ' . $lbite_city ); ?>
						</p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="lbite-order-type">
			<h3><?php esc_html_e( 'When would you like to order?', 'libre-bite' ); ?></h3>
			<label class="lbite-order-type-option">
				<input type="radio" name="lbite_order_type" value="now" checked>
				<?php esc_html_e( 'Order Now', 'libre-bite' ); ?>
			</label>
			<label class="lbite-order-type-option">
				<input type="radio" name="lbite_order_type" value="later">
				<?php esc_html_e( 'Pre-order for later', 'libre-bite' ); ?>
			</label>
		</div>

		<button type="button" id="lbite-confirm-location" class="button">
			<?php esc_html_e( 'Confirm', 'libre-bite' ); ?>
		</button>
	</div>
</div>


<?php ob_start(); ?>
jQuery(document).ready(function($) {
	var selectedLocation = null;

	// Modal anzeigen
	<?php if ( ! WC()->session || ! WC()->session->get( 'lbite_location_id' ) ) : ?>
	$('#lbite-location-modal').fadeIn();
	<?php endif; ?>

	// Standort auswählen
	$('.lbite-location-item').on('click', function() {
		$('.lbite-location-item').removeClass('selected');
		$(this).addClass('selected');
		selectedLocation = $(this).data('location-id');
	});

	// Bestätigen
	$('#lbite-confirm-location').on('click', function() {
		if (!selectedLocation) {
			alert('<?php esc_html_e( 'Please select a location', 'libre-bite' ); ?>');
			return;
		}

		var orderType = $('input[name="lbite_order_type"]:checked').val();

		$.ajax({
			url: lbiteData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lbite_set_location',
				nonce: lbiteData.nonce,
				location_id: selectedLocation,
				order_type: orderType
			},
			success: function(response) {
				if (response.success) {
					$('#lbite-location-modal').fadeOut();
					location.reload();
				}
			}
		});
	});
});
<?php wp_add_inline_script( 'lbite-frontend', ob_get_clean() ); ?>
