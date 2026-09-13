<?php
/**
 * SMS-Benachrichtigungen (Twilio)
 *
 * Schickt Gästen eine kurze Nachricht, wenn ihre Bestellung bereit ist oder
 * die Abholzeit näher rückt. E-Mail erreicht viele Gäste nicht rechtzeitig;
 * eine SMS wird gelesen.
 *
 * Drei Grundsätze:
 *
 * 1. **Bring your own account.** Es gibt keinen Dienst von uns dazwischen,
 *    der Betrieb hinterlegt sein eigenes Twilio-Konto. Damit fliessen keine
 *    Kundendaten über fremde Server, und die Kosten bleiben transparent.
 * 2. **Das Auth-Token wird verschlüsselt abgelegt**, nicht im Klartext in
 *    der Optionstabelle.
 * 3. **Ein Fehlschlag darf niemals eine Bestellung blockieren.** Jeder
 *    Versand ist gekapselt; scheitert er, wird das protokolliert und der
 *    Vorgang läuft normal weiter.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse LBite_SMS
 */
class LBite_SMS {

	/**
	 * Twilio-API-Endpunkt.
	 */
	const API_BASE = 'https://api.twilio.com/2010-04-01/Accounts/';

	/**
	 * Loader-Instanz.
	 *
	 * @var LBite_Loader
	 */
	private $loader;

	/**
	 * Konstruktor
	 *
	 * @param LBite_Loader $loader Hook-Loader.
	 */
	public function __construct( $loader ) {
		$this->loader = $loader;

		$this->loader->add_action( 'lbite_order_status_changed', $this, 'on_status_changed', 10, 4 );
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Geheimnisse
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Zeichenkette verschlüsseln
	 *
	 * Der Schlüssel wird aus den WordPress-Salts abgeleitet; es braucht
	 * dadurch kein zusätzliches Geheimnis, das selbst wieder irgendwo
	 * liegen müsste.
	 *
	 * @param string $plaintext Klartext.
	 * @return string Base64-kodiert, oder Klartext falls OpenSSL fehlt.
	 */
	public static function encrypt( $plaintext ) {
		if ( '' === $plaintext || ! function_exists( 'openssl_encrypt' ) ) {
			return $plaintext;
		}

		$key    = hash( 'sha256', wp_salt( 'auth' ), true );
		$iv     = random_bytes( 16 );
		$cipher = openssl_encrypt( $plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );

		if ( false === $cipher ) {
			return '';
		}

		return base64_encode( $iv . $cipher );
	}

	/**
	 * Zeichenkette entschlüsseln
	 *
	 * Schlägt fehl, wenn die WordPress-Salts gewechselt wurden. In dem Fall
	 * wird ein leerer Wert zurückgegeben, damit die Einstellungsseite zur
	 * Neueingabe auffordern kann, statt mit einem unlesbaren Wert zu
	 * arbeiten.
	 *
	 * @param string $encoded Base64-kodierter Wert.
	 * @return string Klartext oder leer.
	 */
	public static function decrypt( $encoded ) {
		if ( '' === $encoded || ! function_exists( 'openssl_decrypt' ) ) {
			return $encoded;
		}

		$data = base64_decode( $encoded, true );

		if ( false === $data || strlen( $data ) <= 16 ) {
			return '';
		}

		$key   = hash( 'sha256', wp_salt( 'auth' ), true );
		$iv    = substr( $data, 0, 16 );
		$plain = openssl_decrypt( substr( $data, 16 ), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );

		return false === $plain ? '' : $plain;
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Konfiguration
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Zugangsdaten lesen
	 *
	 * @return array{sid:string,token:string,from:string}
	 */
	public static function get_credentials() {
		return array(
			'sid'   => (string) get_option( 'lbite_sms_account_sid', '' ),
			'token' => self::decrypt( (string) get_option( 'lbite_sms_auth_token', '' ) ),
			'from'  => (string) get_option( 'lbite_sms_from', '' ),
		);
	}

	/**
	 * Ist der Versand einsatzbereit?
	 *
	 * @return bool
	 */
	public static function is_configured() {
		$creds = self::get_credentials();

		return '' !== $creds['sid'] && '' !== $creds['token'] && '' !== $creds['from'];
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Auslöser
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Auf Kanban-Statuswechsel reagieren
	 *
	 * @param int      $order_id   Bestell-ID.
	 * @param string   $new_status Neuer Spaltenschlüssel.
	 * @param string   $old_status Vorheriger Spaltenschlüssel.
	 * @param WC_Order $order      Bestellung.
	 */
	public function on_status_changed( $order_id, $new_status, $old_status, $order ) {
		if ( ! lbite_feature_enabled( 'enable_sms_notifications' ) ) {
			return;
		}

		$trigger = (string) get_option( 'lbite_sms_trigger_status', '' );

		if ( '' === $trigger || $trigger !== $new_status ) {
			return;
		}

		// Nicht zweimal für dieselbe Bestellung.
		if ( '1' === (string) $order->get_meta( '_lbite_sms_sent', true ) ) {
			return;
		}

		$phone = $order->get_billing_phone();

		if ( '' === $phone ) {
			return;
		}

		$sent = $this->send( $phone, $this->build_message( $order ) );

		if ( $sent ) {
			$order->update_meta_data( '_lbite_sms_sent', '1' );
			$order->save();
		}
	}

	/**
	 * Nachrichtentext aus der Vorlage erzeugen
	 *
	 * @param WC_Order $order Bestellung.
	 * @return string
	 */
	private function build_message( $order ) {
		$template = (string) get_option(
			'lbite_sms_template',
			__( 'Your order {order_number} is ready for pickup. Thank you! {site}', 'libre-bite' )
		);

		$location_id = (int) $order->get_meta( '_lbite_location_id', true );

		$replacements = array(
			'{order_number}' => $order->get_order_number(),
			'{first_name}'   => $order->get_billing_first_name(),
			'{site}'         => get_bloginfo( 'name' ),
			'{location}'     => $location_id ? get_the_title( $location_id ) : '',
			'{total}'        => wp_strip_all_tags( $order->get_formatted_order_total() ),
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Versand
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * SMS senden
	 *
	 * Fehler werden protokolliert und geschluckt. Ein nicht erreichbarer
	 * SMS-Dienst darf keinen Betriebsablauf anhalten.
	 *
	 * @param string $to      Empfängernummer.
	 * @param string $message Nachrichtentext.
	 * @return bool Erfolg.
	 */
	public function send( $to, $message ) {
		if ( ! self::is_configured() ) {
			return false;
		}

		$creds = self::get_credentials();
		$to    = self::format_number( $to );

		if ( '' === $to || '' === trim( $message ) ) {
			return false;
		}

		$response = wp_remote_post(
			self::API_BASE . rawurlencode( $creds['sid'] ) . '/Messages.json',
			array(
				'timeout' => 10,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $creds['sid'] . ':' . $creds['token'] ),
				),
				'body'    => array(
					'From' => $creds['from'],
					'To'   => $to,
					'Body' => $message,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			self::log( 'Request failed: ' . $response->get_error_message() );

			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( $code < 200 || $code >= 300 ) {
			self::log( 'Unexpected response ' . $code . ': ' . wp_remote_retrieve_body( $response ) );

			return false;
		}

		return true;
	}

	/**
	 * Nummer in das von Twilio erwartete E.164-Format bringen
	 *
	 * Nummern ohne Ländervorwahl werden mit der eingestellten Vorwahl
	 * ergänzt — sonst weist Twilio sie ab.
	 *
	 * @param string $number Rohe Nummer.
	 * @return string
	 */
	public static function format_number( $number ) {
		$raw = trim( (string) $number );

		if ( '' === $raw ) {
			return '';
		}

		$digits = preg_replace( '/\D+/', '', $raw );

		if ( '' === $digits ) {
			return '';
		}

		// Bereits international angegeben.
		if ( 0 === strpos( $raw, '+' ) ) {
			return '+' . $digits;
		}

		if ( 0 === strpos( $digits, '00' ) ) {
			return '+' . substr( $digits, 2 );
		}

		$prefix = (string) get_option( 'lbite_sms_country_code', '+41' );
		$prefix = '+' . preg_replace( '/\D+/', '', $prefix );

		return $prefix . ltrim( $digits, '0' );
	}

	/**
	 * Fehler protokollieren
	 *
	 * @param string $message Meldung.
	 */
	private static function log( $message ) {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->error( $message, array( 'source' => 'libre-bite-sms' ) );
		}
	}
}
