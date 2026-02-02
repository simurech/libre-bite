<?php
/**
 * Template: Optimierter Checkout
 *
 * Minimalistischer Checkout-Flow mit nur Name und Beleg-Option.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// WooCommerce muss aktiv sein.
if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

$brand_name = get_option( 'lb_brand_name', get_bloginfo( 'name' ) );
$brand_logo = get_option( 'lb_brand_logo', 0 );
$logo_url   = $brand_logo ? wp_get_attachment_image_url( $brand_logo, 'medium' ) : '';

// Standort aus Session.
$location_id   = WC()->session ? WC()->session->get( 'lb_location_id' ) : 0;
$location_name = '';
if ( $location_id ) {
	$location = get_post( $location_id );
	if ( $location ) {
		$location_name = $location->post_title;
	}
}

// Bestelltyp und Zeit.
$order_type  = WC()->session ? WC()->session->get( 'lb_order_type', 'now' ) : 'now';
$pickup_time = WC()->session ? WC()->session->get( 'lb_pickup_time', '' ) : '';
?>

<div class="lb-checkout-optimized">
	<?php if ( $logo_url ) : ?>
		<div class="lb-checkout-logo">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $brand_name ); ?>">
		</div>
	<?php endif; ?>

	<h2 class="lb-checkout-title"><?php esc_html_e( 'Bestellung abschliessen', 'libre-bite' ); ?></h2>

	<?php if ( $location_name ) : ?>
		<div class="lb-checkout-info">
			<p>
				<strong><?php esc_html_e( 'Standort:', 'libre-bite' ); ?></strong>
				<?php echo esc_html( $location_name ); ?>
			</p>
			<?php if ( 'later' === $order_type && $pickup_time ) : ?>
				<p>
					<strong><?php esc_html_e( 'Abholung:', 'libre-bite' ); ?></strong>
					<?php echo esc_html( wp_date( 'd.m.Y H:i', strtotime( $pickup_time ) ) ); ?> <?php esc_html_e( 'Uhr', 'libre-bite' ); ?>
				</p>
			<?php else : ?>
				<p>
					<strong><?php esc_html_e( 'Abholung:', 'libre-bite' ); ?></strong>
					<?php esc_html_e( 'Sobald fertig', 'libre-bite' ); ?>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<form name="checkout" method="post" class="checkout woocommerce-checkout lb-checkout-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<div class="lb-checkout-step" id="lb-step-name">
			<h3><?php esc_html_e( 'Wie heisst du?', 'libre-bite' ); ?></h3>
			<p class="form-row form-row-wide">
				<label for="billing_first_name"><?php esc_html_e( 'Name', 'libre-bite' ); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="billing_first_name" id="billing_first_name" placeholder="<?php esc_attr_e( 'Dein Name', 'libre-bite' ); ?>" value="<?php echo esc_attr( WC()->checkout->get_value( 'billing_first_name' ) ); ?>" required>
			</p>
		</div>

		<div class="lb-checkout-step" id="lb-step-receipt">
			<h3><?php esc_html_e( 'Beleg erhalten?', 'libre-bite' ); ?></h3>
			<div class="lb-receipt-options">
				<label class="lb-receipt-option">
					<input type="radio" name="lb_receipt_option" value="none" checked>
					<span class="lb-receipt-option-label">
						<span class="lb-receipt-option-icon dashicons dashicons-no-alt"></span>
						<?php esc_html_e( 'Kein Beleg', 'libre-bite' ); ?>
					</span>
				</label>
				<label class="lb-receipt-option">
					<input type="radio" name="lb_receipt_option" value="email">
					<span class="lb-receipt-option-label">
						<span class="lb-receipt-option-icon dashicons dashicons-email"></span>
						<?php esc_html_e( 'Per E-Mail', 'libre-bite' ); ?>
					</span>
				</label>
			</div>

			<div class="lb-email-field" id="lb-email-field" style="display: none;">
				<p class="form-row form-row-wide">
					<label for="billing_email"><?php esc_html_e( 'E-Mail-Adresse', 'libre-bite' ); ?> <span class="required">*</span></label>
					<input type="email" class="input-text" name="billing_email" id="billing_email" placeholder="<?php esc_attr_e( 'deine@email.ch', 'libre-bite' ); ?>" value="<?php echo esc_attr( WC()->checkout->get_value( 'billing_email' ) ); ?>">
				</p>
			</div>
		</div>

		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

		<div class="lb-checkout-step" id="lb-step-review">
			<h3><?php esc_html_e( 'Deine Bestellung', 'libre-bite' ); ?></h3>

			<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
			<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

			<div id="order_review" class="woocommerce-checkout-review-order">
				<?php do_action( 'woocommerce_checkout_order_review' ); ?>
			</div>

			<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
		</div>

		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>

	</form>
</div>

<script>
jQuery(document).ready(function($) {
	// E-Mail-Feld ein/ausblenden
	$('input[name="lb_receipt_option"]').on('change', function() {
		var $emailField = $('#lb-email-field');
		var $emailInput = $emailField.find('input');

		if ($(this).val() === 'email') {
			$emailField.slideDown();
			$emailInput.prop('required', true).val('');
		} else {
			$emailField.slideUp();
			$emailInput.prop('required', false);
		}
	});

	// Vor dem Absenden: Platzhalter-E-Mail einfügen wenn kein Beleg gewählt
	$('form.checkout').on('checkout_place_order', function() {
		var receiptOption = $('input[name="lb_receipt_option"]:checked').val();

		if (receiptOption === 'none' || !receiptOption) {
			// Platzhalter-E-Mail generieren (wird nicht für echte E-Mails verwendet)
			var timestamp = Date.now();
			var placeholderEmail = 'guest-' + timestamp + '@nomail.local';
			$('#billing_email').val(placeholderEmail);
		}

		return true;
	});

	// Alternativ: Beim Submit des Formulars
	$('form.checkout').on('submit', function() {
		var receiptOption = $('input[name="lb_receipt_option"]:checked').val();

		if (receiptOption === 'none' || !receiptOption) {
			var timestamp = Date.now();
			var placeholderEmail = 'guest-' + timestamp + '@nomail.local';
			$('#billing_email').val(placeholderEmail);
		}
	});
});
</script>
