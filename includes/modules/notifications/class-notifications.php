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
class LBite_Notifications {

	/**
	 * Loader-Instanz
	 *
	 * @var LBite_Loader
	 */
	private $loader;

	/**
	 * Konstruktor
	 *
	 * @param LBite_Loader $loader Loader-Instanz
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

		// Pickup-Reminder Cron: nur in Premium-Version (dieser Block wird in Gratis-Version entfernt).
		if ( lbite_freemius()->is__premium_only() ) {
			$this->loader->add_action( 'lbite_send_pickup_reminders', $this, 'send_pickup_reminders__premium_only' );
		}
	}

	/**
	 * Custom E-Mail-Klassen hinzufügen
	 *
	 * @param array $emails E-Mail-Klassen
	 * @return array
	 */
	public function add_custom_emails( $emails ) {
		require_once LBITE_PLUGIN_DIR . 'includes/modules/notifications/class-email-pickup-reminder.php';
		$emails['LBite_Email_Pickup_Reminder'] = new LBite_Email_Pickup_Reminder();

		return $emails;
	}

	/**
	 * Pickup-Reminder versenden (nur Premium)
	 */
	public function send_pickup_reminders__premium_only() {
		if ( ! get_option( 'lbite_email_pickup_reminder', true ) ) {
			return;
		}

		$reminder_time = get_option( 'lbite_pickup_reminder_time', 15 );

		// Auf "heute oder morgen" eingrenzen (per _lbite_pickup_date, immer
		// zuverlässig "Y-m-d" formatiert - anders als _lbite_pickup_time,
		// das historisch mit und ohne "T"-Trenner vorkommt). Ohne dieses
		// Fenster konnten alte, längst abgelaufene Vorbestellungen das
		// limit von 100 komplett füllen und neue, tatsächlich fällige
		// Bestellungen verdrängen - und zwar in beliebiger, nicht nach
		// Dringlichkeit sortierter Reihenfolge (Audit 26.09.2026, AP-21).
		$lbite_today    = current_time( 'Y-m-d' );
		$lbite_tomorrow = gmdate( 'Y-m-d', strtotime( '+1 day', current_time( 'timestamp' ) ) );

		$orders = wc_get_orders(
			array(
				'limit'      => 100,
				'status'     => array( 'processing', 'on-hold' ),
				'orderby'    => 'meta_value',
				'meta_key'   => '_lbite_pickup_time', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Sortierung nach Dringlichkeit (frühester Abholtermin zuerst).
				'order'      => 'ASC',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Cron-Abfrage für Abholbenachrichtigungen nach Metadaten; auf 100 Bestellungen begrenzt.
				'meta_query' => array(
					array(
						'key'     => '_lbite_order_type',
						'value'   => 'later',
						'compare' => '=',
					),
					array(
						'key'     => '_lbite_reminder_sent',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_lbite_pickup_date',
						'value'   => array( $lbite_today, $lbite_tomorrow ),
						'compare' => 'IN',
					),
				),
			)
		);

		$current_time = time();

		foreach ( $orders as $order ) {
			$pickup_time = $order->get_meta( '_lbite_pickup_time', true );
			if ( ! $pickup_time ) {
				continue;
			}

			$pickup_timestamp = lbite_local_time_to_timestamp( $pickup_time );
			$reminder_timestamp = $pickup_timestamp - ( $reminder_time * 60 );

			// Wenn Reminder-Zeit erreicht ist
			if ( $current_time >= $reminder_timestamp && $current_time < $pickup_timestamp ) {
				$this->send_pickup_reminder_email( $order );
				$order->update_meta_data( '_lbite_reminder_sent', true );
				$order->save();
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

		if ( isset( $emails['LBite_Email_Pickup_Reminder'] ) ) {
			$emails['LBite_Email_Pickup_Reminder']->trigger( $order->get_id() );
		}
	}
}
