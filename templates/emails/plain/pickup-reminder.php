<?php
/**
 * E-Mail-Template: Pickup-Reminder (Plain Text)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pickup_time      = get_post_meta( $order->get_id(), '_lbite_pickup_time', true );
$location_name    = get_post_meta( $order->get_id(), '_lbite_location_name', true );
$location_id      = get_post_meta( $order->get_id(), '_lbite_location_id', true );
$location_address = $location_id ? LBite_Locations::get_formatted_address( $location_id ) : '';
$location_maps    = $location_id ? LBite_Locations::get_maps_url( $location_id ) : '';

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: customer first name */
printf( esc_html__( 'Hallo %s,', 'libre-bite' ), esc_html( $order->get_billing_first_name() ) );

echo "\n\n";

echo esc_html__( 'dies ist eine Erinnerung an Ihre bevorstehende Bestellung.', 'libre-bite' );

echo "\n\n";

if ( $pickup_time ) {
	echo esc_html__( 'Abholzeit:', 'libre-bite' ) . ' ';
	echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $pickup_time ) ) );
	echo "\n";
}

if ( $location_name ) {
	echo esc_html__( 'Standort:', 'libre-bite' ) . ' ';
	echo esc_html( $location_name );
	echo "\n";
	if ( $location_address ) {
		echo esc_html( $location_address );
		echo "\n";
		if ( $location_maps ) {
			echo esc_html__( 'Karte:', 'libre-bite' ) . ' ' . esc_url( $location_maps );
			echo "\n";
		}
	}
}

echo "\n";

/* translators: %s: order number */
echo sprintf( esc_html__( 'Bestellung #%s', 'libre-bite' ), esc_html( $order->get_order_number() ) );
echo "\n";
echo esc_html__( 'Bestellt am:', 'libre-bite' ) . ' ' . esc_html( wc_format_datetime( $order->get_date_created() ) );

echo "\n\n";

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n";

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n";
}

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html( wp_strip_all_tags( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) );
