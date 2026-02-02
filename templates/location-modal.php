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

<div id="lb-location-modal" class="lb-modal" style="display: none;">
	<div class="lb-modal-overlay"></div>
	<div class="lb-modal-content">
		<h2><?php esc_html_e( 'Standort wählen', 'libre-bite' ); ?></h2>
		<p><?php esc_html_e( 'Bitte wählen Sie Ihren gewünschten Standort:', 'libre-bite' ); ?></p>

		<div class="lb-location-list">
			<?php foreach ( $locations as $location ) : ?>
				<?php
				$street = get_post_meta( $location->ID, '_lb_street', true );
				$zip    = get_post_meta( $location->ID, '_lb_zip', true );
				$city   = get_post_meta( $location->ID, '_lb_city', true );
				?>
				<div class="lb-location-item" data-location-id="<?php echo esc_attr( $location->ID ); ?>">
					<h3><?php echo esc_html( $location->post_title ); ?></h3>
					<?php if ( $street || $city ) : ?>
						<p class="lb-location-address">
							<?php echo esc_html( $street ); ?><br>
							<?php echo esc_html( $zip . ' ' . $city ); ?>
						</p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="lb-order-type">
			<h3><?php esc_html_e( 'Wann möchten Sie bestellen?', 'libre-bite' ); ?></h3>
			<label class="lb-order-type-option">
				<input type="radio" name="lb_order_type" value="now" checked>
				<?php esc_html_e( 'Sofort bestellen', 'libre-bite' ); ?>
			</label>
			<label class="lb-order-type-option">
				<input type="radio" name="lb_order_type" value="later">
				<?php esc_html_e( 'Für später vorbestellen', 'libre-bite' ); ?>
			</label>
		</div>

		<button type="button" id="lb-confirm-location" class="button">
			<?php esc_html_e( 'Bestätigen', 'libre-bite' ); ?>
		</button>
	</div>
</div>

<style>
.lb-modal {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	z-index: 999999;
}

.lb-modal-overlay {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background: rgba(0, 0, 0, 0.7);
}

.lb-modal-content {
	position: relative;
	max-width: 600px;
	margin: 50px auto;
	background: #fff;
	padding: 30px;
	border-radius: 8px;
	max-height: 80vh;
	overflow-y: auto;
}

.lb-location-list {
	margin: 20px 0;
}

.lb-location-item {
	padding: 15px;
	border: 2px solid #ddd;
	border-radius: 4px;
	margin-bottom: 10px;
	cursor: pointer;
	transition: all 0.3s;
}

.lb-location-item:hover,
.lb-location-item.selected {
	border-color: #0073aa;
	background: #f0f8ff;
}

.lb-location-item h3 {
	margin: 0 0 5px 0;
	font-size: 18px;
}

.lb-location-address {
	margin: 0;
	color: #666;
	font-size: 14px;
}

.lb-order-type {
	margin: 20px 0;
}

.lb-order-type-option {
	display: block;
	padding: 10px;
	margin-bottom: 10px;
	cursor: pointer;
}

#lb-confirm-location {
	width: 100%;
	padding: 12px;
	font-size: 16px;
}
</style>

<script>
jQuery(document).ready(function($) {
	var selectedLocation = null;

	// Modal anzeigen
	<?php if ( ! WC()->session || ! WC()->session->get( 'lb_location_id' ) ) : ?>
	$('#lb-location-modal').fadeIn();
	<?php endif; ?>

	// Standort auswählen
	$('.lb-location-item').on('click', function() {
		$('.lb-location-item').removeClass('selected');
		$(this).addClass('selected');
		selectedLocation = $(this).data('location-id');
	});

	// Bestätigen
	$('#lb-confirm-location').on('click', function() {
		if (!selectedLocation) {
			alert('<?php esc_html_e( 'Bitte wählen Sie einen Standort', 'libre-bite' ); ?>');
			return;
		}

		var orderType = $('input[name="lb_order_type"]:checked').val();

		$.ajax({
			url: lbData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lb_set_location',
				nonce: lbData.nonce,
				location_id: selectedLocation,
				order_type: orderType
			},
			success: function(response) {
				if (response.success) {
					$('#lb-location-modal').fadeOut();
					location.reload();
				}
			}
		});
	});
});
</script>
