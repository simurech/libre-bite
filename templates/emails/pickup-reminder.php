<?php
/**
 * E-Mail-Template: Pickup-Reminder (HTML)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pickup_time      = get_post_meta( $order->get_id(), '_lb_pickup_time', true );
$location_name    = get_post_meta( $order->get_id(), '_lb_location_name', true );
$location_id      = get_post_meta( $order->get_id(), '_lb_location_id', true );
$location_address = $location_id ? LB_Locations::get_formatted_address( $location_id ) : '';
$location_maps    = $location_id ? LB_Locations::get_maps_url( $location_id ) : '';

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<?php /* translators: %s: customer first name */ ?>
<p><?php printf( esc_html__( 'Hallo %s,', 'libre-bite' ), esc_html( $order->get_billing_first_name() ) ); ?></p>

<p><?php esc_html_e( 'dies ist eine Erinnerung an Ihre bevorstehende Bestellung.', 'libre-bite' ); ?></p>

<?php if ( $pickup_time ) : ?>
	<p>
		<strong><?php esc_html_e( 'Abholzeit:', 'libre-bite' ); ?></strong>
		<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $pickup_time ) ) ); ?>
	</p>
<?php endif; ?>

<?php if ( $location_name ) : ?>
	<p>
		<strong><?php esc_html_e( 'Standort:', 'libre-bite' ); ?></strong>
		<?php echo esc_html( $location_name ); ?>
		<?php if ( $location_address ) : ?>
			<br>
			<?php if ( $location_maps ) : ?>
				<a href="<?php echo esc_url( $location_maps ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $location_address ); ?></a>
			<?php else : ?>
				<?php echo esc_html( $location_address ); ?>
			<?php endif; ?>
		<?php endif; ?>
	</p>
<?php endif; ?>

<?php /* translators: %s: order number */ ?>
<h2><?php printf( esc_html__( 'Bestellung #%s', 'libre-bite' ), esc_html( $order->get_order_number() ) ); ?></h2>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
