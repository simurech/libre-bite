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

// Standort aus Session.
$lbite_location_id   = WC()->session ? WC()->session->get( 'lbite_location_id' ) : 0;
$lbite_location_name = '';
if ( $lbite_location_id ) {
	$lbite_location = get_post( $lbite_location_id );
	if ( $lbite_location ) {
		$lbite_location_name = $lbite_location->post_title;
	}
}

// Bestelltyp und Zeit.
$lbite_order_type  = WC()->session ? WC()->session->get( 'lbite_order_type', 'now' ) : 'now';
$lbite_pickup_time = WC()->session ? WC()->session->get( 'lbite_pickup_time', '' ) : '';
?>

<div class="lbite-checkout-optimized">
	<?php if ( $lbite_logo_url ) : ?>
		<div class="lbite-checkout-logo">
			<img src="<?php echo esc_url( $lbite_logo_url ); ?>" alt="<?php echo esc_attr( $lbite_brand_name ); ?>">
		</div>
	<?php endif; ?>

	<h2 class="lbite-checkout-title"><?php esc_html_e( 'Bestellung abschliessen', 'libre-bite' ); ?></h2>

	<?php if ( $lbite_location_name ) : ?>
		<div class="lbite-checkout-info">
			<p>
				<strong><?php esc_html_e( 'Standort:', 'libre-bite' ); ?></strong>
				<?php echo esc_html( $lbite_location_name ); ?>
			</p>
			<?php if ( 'later' === $lbite_order_type && $lbite_pickup_time ) : ?>
				<p>
					<strong><?php esc_html_e( 'Abholung:', 'libre-bite' ); ?></strong>
					<?php echo esc_html( wp_date( 'd.m.Y H:i', strtotime( $lbite_pickup_time ) ) ); ?> <?php esc_html_e( 'Uhr', 'libre-bite' ); ?>
				</p>
			<?php else : ?>
				<p>
					<strong><?php esc_html_e( 'Abholung:', 'libre-bite' ); ?></strong>
					<?php esc_html_e( 'Sobald fertig', 'libre-bite' ); ?>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<form name="checkout" method="post" class="checkout woocommerce-checkout lbite-checkout-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

		<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
		do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<div class="lbite-checkout-step" id="lbite-step-name">
			<h3><?php esc_html_e( 'Wie heisst du?', 'libre-bite' ); ?></h3>
			<p class="form-row form-row-wide">
				<label for="billing_first_name"><?php esc_html_e( 'Name', 'libre-bite' ); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="billing_first_name" id="billing_first_name" placeholder="<?php esc_attr_e( 'Dein Name', 'libre-bite' ); ?>" value="<?php echo esc_attr( WC()->checkout->get_value( 'billing_first_name' ) ); ?>" required>
			</p>
		</div>

		<div class="lbite-checkout-step" id="lbite-step-receipt">
			<h3><?php esc_html_e( 'Beleg erhalten?', 'libre-bite' ); ?></h3>
			<div class="lbite-receipt-options">
				<label class="lbite-receipt-option">
					<input type="radio" name="lbite_receipt_option" value="none" checked>
					<span class="lbite-receipt-option-label">
						<span class="lbite-receipt-option-icon dashicons dashicons-no-alt"></span>
						<?php esc_html_e( 'Kein Beleg', 'libre-bite' ); ?>
					</span>
				</label>
				<label class="lbite-receipt-option">
					<input type="radio" name="lbite_receipt_option" value="email">
					<span class="lbite-receipt-option-label">
						<span class="lbite-receipt-option-icon dashicons dashicons-email"></span>
						<?php esc_html_e( 'Per E-Mail', 'libre-bite' ); ?>
					</span>
				</label>
			</div>

			<div class="lbite-email-field" id="lbite-email-field" style="display: none;">
				<p class="form-row form-row-wide">
					<label for="billing_email"><?php esc_html_e( 'E-Mail-Adresse', 'libre-bite' ); ?> <span class="required">*</span></label>
					<input type="email" class="input-text" name="billing_email" id="billing_email" placeholder="<?php esc_attr_e( 'deine@email.ch', 'libre-bite' ); ?>" value="<?php echo esc_attr( WC()->checkout->get_value( 'billing_email' ) ); ?>">
				</p>
			</div>
		</div>

		<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
		do_action( 'woocommerce_checkout_after_customer_details' ); ?>

		<div class="lbite-checkout-step" id="lbite-step-review">
			<h3><?php esc_html_e( 'Deine Bestellung', 'libre-bite' ); ?></h3>

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

		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>

	</form>
</div>
