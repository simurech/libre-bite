<?php
/**
 * E-Mail-Template: Pickup-Reminder (HTML)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_pickup_time      = $order->get_meta( '_lbite_pickup_time' );
$lbite_location_name    = $order->get_meta( '_lbite_location_name' );
$lbite_location_id      = $order->get_meta( '_lbite_location_id' );
$lbite_location_address = $lbite_location_id ? LBite_Locations::get_formatted_address( $lbite_location_id ) : '';
$lbite_location_maps    = $lbite_location_id ? LBite_Locations::get_maps_url( $lbite_location_id ) : '';

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<?php /* translators: %s: customer first name */ ?>
<p><?php printf( esc_html__( 'Hello %s,', 'libre-bite' ), esc_html( $order->get_billing_first_name() ) ); ?></p>

<p><?php esc_html_e( 'this is a reminder about your upcoming order.', 'libre-bite' ); ?></p>

<?php if ( $lbite_pickup_time ) : ?>
	<p>
		<strong><?php esc_html_e( 'Pickup Time:', 'libre-bite' ); ?></strong>
		<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), lbite_local_time_to_timestamp( $lbite_pickup_time ) ) ); ?>
	</p>
<?php endif; ?>

<?php if ( $lbite_location_name ) : ?>
	<p>
		<strong><?php esc_html_e( 'Location:', 'libre-bite' ); ?></strong>
		<?php echo esc_html( $lbite_location_name ); ?>
		<?php if ( $lbite_location_address ) : ?>
			<br>
			<?php if ( $lbite_location_maps ) : ?>
				<a href="<?php echo esc_url( $lbite_location_maps ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $lbite_location_address ); ?></a>
			<?php else : ?>
				<?php echo esc_html( $lbite_location_address ); ?>
			<?php endif; ?>
		<?php endif; ?>
	</p>
<?php endif; ?>

<?php /* translators: %s: order number */ ?>
<h2><?php printf( esc_html__( 'Order #%s', 'libre-bite' ), esc_html( $order->get_order_number() ) ); ?></h2>

<?php
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce-Standard-Hook; darf nicht umbenannt werden.
do_action( 'woocommerce_email_footer', $email );
