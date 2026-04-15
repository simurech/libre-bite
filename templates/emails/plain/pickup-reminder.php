<?php
/**
 * E-Mail-Template: Pickup-Reminder (Plain Text)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_pickup_time      = get_post_meta( $order->get_id(), '_lbite_pickup_time', true );
$lbite_location_name    = get_post_meta( $order->get_id(), '_lbite_location_name', true );
$lbite_location_id      = get_post_meta( $order->get_id(), '_lbite_location_id', true );
$lbite_location_address = $lbite_location_id ? LBite_Locations::get_formatted_address( $lbite_location_id ) : '';
$lbite_location_maps    = $lbite_location_id ? LBite_Locations::get_maps_url( $lbite_location_id ) : '';

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: customer first name */
printf( esc_html__( 'Hello %s,', 'libre-bite' ), esc_html( $order->get_billing_first_name() ) );

echo "\n\n";

echo esc_html__( 'this is a reminder about your upcoming order.', 'libre-bite' );

echo "\n\n";

if ( $lbite_pickup_time ) {
	echo esc_html__( 'Pickup Time:', 'libre-bite' ) . ' ';
	echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), lbite_local_time_to_timestamp( $lbite_pickup_time ) ) );
	echo "\n";
}

if ( $lbite_location_name ) {
	echo esc_html__( 'Location:', 'libre-bite' ) . ' ';
	echo esc_html( $lbite_location_name );
	echo "\n";
	if ( $lbite_location_address ) {
		echo esc_html( $lbite_location_address );
		echo "\n";
		if ( $lbite_location_maps ) {
			echo esc_html__( 'Map:', 'libre-bite' ) . ' ' . esc_url( $lbite_location_maps );
			echo "\n";
		}
	}
}

echo "\n";

/* translators: %s: order number */
echo sprintf( esc_html__( 'Order #%s', 'libre-bite' ), esc_html( $order->get_order_number() ) );
echo "\n";
echo esc_html__( 'Ordered on:', 'libre-bite' ) . ' ' . esc_html( wc_format_datetime( $order->get_date_created() ) );

echo "\n\n";

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n";

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n";
}

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
echo esc_html( wp_strip_all_tags( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) );
