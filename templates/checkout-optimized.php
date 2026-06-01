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

// Tischbestellung via QR-Code.
$lbite_table_id   = WC()->session ? WC()->session->get( 'lbite_table_id', 0 ) : 0;
$lbite_table_name = '';
if ( $lbite_table_id ) {
	$lbite_table_post = get_post( $lbite_table_id );
	if ( $lbite_table_post ) {
		$lbite_table_name = $lbite_table_post->post_title;
	}
}

// Bestelltyp-Auswahl (Pro).
$lbite_show_order_type   = lbite_feature_enabled( 'enable_order_type_selection' );
$lbite_service_type      = WC()->session ? WC()->session->get( 'lbite_service_type', '' ) : '';
if ( $lbite_show_order_type && ! $lbite_service_type && $lbite_table_id ) {
	$lbite_service_type = 'dine_in';
}
$lbite_show_table_field  = $lbite_show_order_type && lbite_feature_enabled( 'enable_table_ordering' );
$lbite_checkout_table_nr = WC()->session ? WC()->session->get( 'lbite_checkout_table_number', '' ) : '';

// Tische für Standort laden (für Dropdown, falls konfiguriert).
$lbite_checkout_tables = array();
if ( $lbite_show_table_field && $lbite_location_id ) {
	$lbite_checkout_tables = get_posts( array(
		'post_type'      => 'lbite_table',
		'posts_per_page' => 50,
		'orderby'        => 'title',
		'order'          => 'ASC',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Tischabfrage auf max. 50 Einträge begrenzt.
		'meta_query'     => array(
			array(
				'key'   => '_lbite_location_id',
				'value' => $lbite_location_id,
			),
		),
	) );
}
// Vorauswahl: Session-Wert oder per QR-Code gescannter Tisch.
if ( ! $lbite_checkout_table_nr && $lbite_table_name ) {
	$lbite_checkout_table_nr = $lbite_table_name;
}

$lbite_is_dine_in      = 'dine_in' === $lbite_service_type;
$lbite_has_pickup_time = 'later' === $lbite_order_type && $lbite_pickup_time;
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

		<?php if ( $lbite_location_name || $lbite_show_order_type ) : ?>
		<div class="lbite-checkout-info">

			<?php if ( $lbite_location_name ) : ?>
			<p class="lbite-checkout-info-location">
				<strong><?php esc_html_e( 'Location:', 'libre-bite' ); ?></strong>
				<?php echo esc_html( $lbite_location_name ); ?>
			</p>
			<?php endif; ?>

			<?php if ( $lbite_show_order_type ) :
				// Markiere als bereits gerendert, damit der generische Hook nicht nochmals ausgibt.
				$GLOBALS['lbite_order_type_rendered'] = true;
			?>
			<div class="lbite-order-type-options" id="lbite-order-type-selector">
				<label class="lbite-order-type-option">
					<input type="radio" name="lbite_service_type" value="takeaway" <?php checked( ! $lbite_is_dine_in ); ?>>
					<span><?php esc_html_e( 'To take away', 'libre-bite' ); ?></span>
				</label>
				<label class="lbite-order-type-option">
					<input type="radio" name="lbite_service_type" value="dine_in" <?php checked( $lbite_is_dine_in ); ?>>
					<span><?php esc_html_e( 'Eat here', 'libre-bite' ); ?></span>
				</label>
			</div>

			<?php if ( $lbite_show_table_field ) : ?>
			<div id="lbite-table-number-wrap" class="lbite-table-number-wrap" style="<?php echo $lbite_is_dine_in ? '' : 'display:none;'; ?>">
				<label for="lbite-table-number"><?php esc_html_e( 'Table (optional):', 'libre-bite' ); ?></label>
				<?php if ( ! empty( $lbite_checkout_tables ) ) : ?>
				<select id="lbite-table-number" name="lbite_checkout_table_number" class="input-text">
					<option value=""><?php esc_html_e( 'Select table (optional)', 'libre-bite' ); ?></option>
					<?php foreach ( $lbite_checkout_tables as $lbite_ct ) : ?>
					<option value="<?php echo esc_attr( $lbite_ct->post_title ); ?>" <?php selected( $lbite_checkout_table_nr, $lbite_ct->post_title ); ?>>
						<?php echo esc_html( $lbite_ct->post_title ); ?>
					</option>
					<?php endforeach; ?>
				</select>
				<?php else : ?>
				<input type="text" id="lbite-table-number" name="lbite_checkout_table_number" class="input-text" value="<?php echo esc_attr( $lbite_checkout_table_nr ); ?>" placeholder="<?php esc_attr_e( 'e.g. 5', 'libre-bite' ); ?>">
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<?php elseif ( $lbite_table_name ) : ?>
			<p>
				<strong><?php esc_html_e( 'Table:', 'libre-bite' ); ?></strong>
				<?php echo esc_html( $lbite_table_name ); ?>
			</p>
			<?php endif; ?>

			<?php if ( $lbite_has_pickup_time && ! $lbite_is_dine_in ) : ?>
			<p id="lbite-pickup-info">
				<strong><?php esc_html_e( 'Pickup:', 'libre-bite' ); ?></strong>
				<?php echo esc_html( wp_date( 'd.m.Y H:i', lbite_local_time_to_timestamp( $lbite_pickup_time ) ) ); ?>
			</p>
			<?php elseif ( $lbite_has_pickup_time ) : ?>
			<p id="lbite-pickup-info" style="display:none;">
				<strong><?php esc_html_e( 'Pickup:', 'libre-bite' ); ?></strong>
				<?php echo esc_html( wp_date( 'd.m.Y H:i', lbite_local_time_to_timestamp( $lbite_pickup_time ) ) ); ?>
			</p>
			<?php endif; ?>

		</div>
		<?php endif; ?>

		<div class="lbite-checkout-step" id="lbite-step-name">
			<h3><?php esc_html_e( 'What\'s your name?', 'libre-bite' ); ?></h3>
			<p class="form-row form-row-wide">
				<label for="billing_first_name"><?php esc_html_e( 'Name', 'libre-bite' ); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="billing_first_name" id="billing_first_name" placeholder="<?php esc_attr_e( 'Your Name', 'libre-bite' ); ?>" value="<?php echo esc_attr( WC()->checkout->get_value( 'billing_first_name' ) ); ?>" required>
			</p>
		</div>

		<div class="lbite-checkout-step" id="lbite-step-email" style="display:none">
			<h3><?php esc_html_e( 'Your Email', 'libre-bite' ); ?></h3>
			<p class="form-row form-row-wide">
				<label for="billing_email"><?php esc_html_e( 'Email address', 'libre-bite' ); ?> <span class="required">*</span></label>
				<input type="email" class="input-text" name="billing_email" id="billing_email" value="<?php echo esc_attr( WC()->checkout->get_value( 'billing_email' ) ); ?>">
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

		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>

	</form>

	<?php
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
	do_action( 'woocommerce_after_checkout_form', WC()->checkout() );
	?>
</div>

<script>
(function($) {
	var noEmailGateways = ['cod', 'bacs', 'cheque'];

	function updateEmailStep() {
		var selected = $('input[name="payment_method"]:checked').val();
		var isExternal = selected && noEmailGateways.indexOf(selected) === -1;
		$('#lbite-step-email').toggle(isExternal);
		$('#billing_email').prop('required', isExternal);
	}

	function updatePickupInfo() {
		var $pickup = $('#lbite-pickup-info');
		if (!$pickup.length) return;
		var isDineIn = $('input[name="lbite_service_type"]:checked').val() === 'dine_in';
		$pickup.toggle(!isDineIn);
	}

	$(function() {
		$('body')
			.on('change', 'input[name="payment_method"]', updateEmailStep)
			.on('updated_checkout', updateEmailStep)
			.on('change', 'input[name="lbite_service_type"]', updatePickupInfo);
		updateEmailStep();
	});
}(jQuery));
</script>
