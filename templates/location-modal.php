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
		<h2><?php esc_html_e( 'Standort wählen', 'libre-bite' ); ?></h2>
		<p><?php esc_html_e( 'Bitte wählen Sie Ihren gewünschten Standort:', 'libre-bite' ); ?></p>

		<div class="lbite-location-list">
			<?php foreach ( $locations as $location ) : ?>
				<?php
				$street = get_post_meta( $location->ID, '_lbite_street', true );
				$zip    = get_post_meta( $location->ID, '_lbite_zip', true );
				$city   = get_post_meta( $location->ID, '_lbite_city', true );
				?>
				<div class="lbite-location-item" data-location-id="<?php echo esc_attr( $location->ID ); ?>">
					<h3><?php echo esc_html( $location->post_title ); ?></h3>
					<?php if ( $street || $city ) : ?>
						<p class="lbite-location-address">
							<?php echo esc_html( $street ); ?><br>
							<?php echo esc_html( $zip . ' ' . $city ); ?>
						</p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="lbite-order-type">
			<h3><?php esc_html_e( 'Wann möchten Sie bestellen?', 'libre-bite' ); ?></h3>
			<label class="lbite-order-type-option">
				<input type="radio" name="lbite_order_type" value="now" checked>
				<?php esc_html_e( 'Sofort bestellen', 'libre-bite' ); ?>
			</label>
			<label class="lbite-order-type-option">
				<input type="radio" name="lbite_order_type" value="later">
				<?php esc_html_e( 'Für später vorbestellen', 'libre-bite' ); ?>
			</label>
		</div>

		<button type="button" id="lbite-confirm-location" class="button">
			<?php esc_html_e( 'Bestätigen', 'libre-bite' ); ?>
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
			alert('<?php esc_html_e( 'Bitte wählen Sie einen Standort', 'libre-bite' ); ?>');
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
