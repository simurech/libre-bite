<?php
/**
 * Benachrichtigungen (E-Mail, Sound, Druck)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notifications-Modul
 */
class LB_Notifications {

	/**
	 * Loader-Instanz
	 *
	 * @var LB_Loader
	 */
	private $loader;

	/**
	 * Konstruktor
	 *
	 * @param LB_Loader $loader Loader-Instanz
	 */
	public function __construct( $loader ) {
		$this->loader = $loader;
		$this->init_hooks();
	}

	/**
	 * Hooks initialisieren
	 */
	private function init_hooks() {
		// E-Mail-Templates
		$this->loader->add_filter( 'woocommerce_email_classes', $this, 'add_custom_emails' );

		// Pickup-Reminder Cron
		$this->loader->add_action( 'lb_send_pickup_reminders', $this, 'send_pickup_reminders' );

		// Cron-Job aktivieren
		if ( ! wp_next_scheduled( 'lb_send_pickup_reminders' ) ) {
			wp_schedule_event( time(), 'every_minute', 'lb_send_pickup_reminders' );
		}
	}

	/**
	 * Custom E-Mail-Klassen hinzufügen
	 *
	 * @param array $emails E-Mail-Klassen
	 * @return array
	 */
	public function add_custom_emails( $emails ) {
		require_once LB_PLUGIN_DIR . 'includes/modules/notifications/class-email-pickup-reminder.php';
		$emails['LB_Email_Pickup_Reminder'] = new LB_Email_Pickup_Reminder();

		return $emails;
	}

	/**
	 * Pickup-Reminder versenden
	 */
	public function send_pickup_reminders() {
		if ( ! get_option( 'lb_email_pickup_reminder', true ) ) {
			return;
		}

		$reminder_time = get_option( 'lb_pickup_reminder_time', 15 );

		// Bestellungen mit Pickup-Zeit in den nächsten X Minuten
		$orders = wc_get_orders(
			array(
				'limit'      => -1,
				'status'     => array( 'processing', 'on-hold' ),
				'meta_query' => array(
					array(
						'key'     => '_lb_order_type',
						'value'   => 'later',
						'compare' => '=',
					),
					array(
						'key'     => '_lb_reminder_sent',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		$current_time = current_time( 'timestamp' );

		foreach ( $orders as $order ) {
			$pickup_time = get_post_meta( $order->get_id(), '_lb_pickup_time', true );
			if ( ! $pickup_time ) {
				continue;
			}

			$pickup_timestamp = strtotime( $pickup_time );
			$reminder_timestamp = $pickup_timestamp - ( $reminder_time * 60 );

			// Wenn Reminder-Zeit erreicht ist
			if ( $current_time >= $reminder_timestamp && $current_time < $pickup_timestamp ) {
				$this->send_pickup_reminder_email( $order );
				update_post_meta( $order->get_id(), '_lb_reminder_sent', true );
			}
		}
	}

	/**
	 * Pickup-Reminder E-Mail versenden
	 *
	 * @param WC_Order $order Bestellung
	 */
	private function send_pickup_reminder_email( $order ) {
		$mailer = WC()->mailer();
		$emails = $mailer->get_emails();

		if ( isset( $emails['LB_Email_Pickup_Reminder'] ) ) {
			$emails['LB_Email_Pickup_Reminder']->trigger( $order->get_id() );
		}
	}
}
