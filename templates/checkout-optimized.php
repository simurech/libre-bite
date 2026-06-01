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

$lbite_brand_name = get_option( 'lbite_brand_name', get_bloginfo( 'name' ) );
$lbite_brand_logo = get_option( 'lbite_brand_logo', 0 );
$lbite_logo_url   = $lbite_brand_logo ? wp_get_attachment_image_url( $lbite_brand_logo, 'medium' ) : '';
?>

<div class="lbite-checkout-optimized">
	<?php if ( $lbite_logo_url ) : ?>
		<div class="lbite-checkout-logo">
			<img src="<?php echo esc_url( $lbite_logo_url ); ?>" alt="<?php echo esc_attr( $lbite_brand_name ); ?>">
		</div>
	<?php endif; ?>

	<h2 class="lbite-checkout-title"><?php esc_html_e( 'Complete Order', 'libre-bite' ); ?></h2>

	<?php
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook.
	do_action( 'woocommerce_before_checkout_form', WC()->checkout() );
	?>

	<form name="checkout" method="post" class="checkout woocommerce-checkout lbite-checkout-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

		<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
		do_action( 'woocommerce_checkout_before_customer_details' ); ?>


		<div class="lbite-checkout-step" id="lbite-step-name">
			<h3><?php esc_html_e( 'What\'s your name?', 'libre-bite' ); ?></h3>
			<p class="form-row form-row-wide">
				<label for="billing_first_name"><?php esc_html_e( 'Name', 'libre-bite' ); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="billing_first_name" id="billing_first_name" placeholder="<?php esc_attr_e( 'Your Name', 'libre-bite' ); ?>" value="<?php echo esc_attr( WC()->checkout->get_value( 'billing_first_name' ) ); ?>" required>
			</p>
		</div>

		<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
		do_action( 'woocommerce_checkout_after_customer_details' ); ?>

		<div class="lbite-checkout-step" id="lbite-step-review">
			<h3><?php esc_html_e( 'Your Order', 'libre-bite' ); ?></h3>

			<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
			do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
			<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
			do_action( 'woocommerce_checkout_before_order_review' ); ?>

			<div id="order_review" class="woocommerce-checkout-review-order">
				<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
				do_action( 'woocommerce_checkout_order_review' ); ?>
			</div>

			<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
			do_action( 'woocommerce_checkout_after_order_review' ); ?>
		</div>

		<div class="lbite-checkout-step" id="lbite-step-email" style="display:none">
			<h3><?php esc_html_e( 'Your Email', 'libre-bite' ); ?></h3>
			<p class="form-row form-row-wide">
				<label for="billing_email"><?php esc_html_e( 'Email address', 'libre-bite' ); ?> <span class="required">*</span></label>
				<input type="email" class="input-text" name="billing_email" id="billing_email" value="<?php echo esc_attr( WC()->checkout->get_value( 'billing_email' ) ); ?>">
			</p>
		</div>

		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>

	</form>

	<?php
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
	do_action( 'woocommerce_after_checkout_form', WC()->checkout() );
	?>
</div>

<script>
(function($) {
	var emailRequiredGateways = <?php echo wp_json_encode( get_option( 'lbite_email_required_gateways', array() ) ); ?>;

	function repositionEmailStep() {
		var $placeOrder = $('#payment .place-order');
		if ($placeOrder.length) {
			$placeOrder.before($('#lbite-step-email'));
		}
	}

	function updateEmailStep() {
		var selected = $('input[name="payment_method"]:checked').val();
		var needsEmail = selected && emailRequiredGateways.indexOf(selected) !== -1;
		$('#lbite-step-email').toggle(needsEmail);
		$('#billing_email').prop('required', needsEmail);
	}

	$(function() {
		$('body')
			.on('change', 'input[name="payment_method"]', updateEmailStep)
			.on('updated_checkout', function() {
				repositionEmailStep();
				updateEmailStep();
			});
		repositionEmailStep();
		updateEmailStep();
	});
}(jQuery));
</script>
