<?php
/**
 * E-Mail: Pickup-Reminder
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * E-Mail-Klasse für Pickup-Reminder
 */
class LBite_Email_Pickup_Reminder extends WC_Email {

	/**
	 * Konstruktor
	 */
	public function __construct() {
		$this->id             = 'lbite_pickup_reminder';
		$this->title          = __( 'Libre Bite - Pickup Reminder', 'libre-bite' );
		$this->description    = __( 'Erinnerung an bevorstehende Abholzeit', 'libre-bite' );
		$this->template_html  = 'emails/pickup-reminder.php';
		$this->template_plain = 'emails/plain/pickup-reminder.php';
		$this->template_base  = LBITE_PLUGIN_DIR . 'templates/';
		$this->placeholders   = array(
			'{order_date}'   => '',
			'{order_number}' => '',
		);

		// Trigger
		add_action( 'lbite_pickup_reminder_notification', array( $this, 'trigger' ), 10, 1 );

		// Parent-Konstruktor aufrufen
		parent::__construct();

		// Recipient: Kunde
		$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
	}

	/**
	 * E-Mail auslösen
	 *
	 * @param int $order_id Bestellungs-ID
	 */
	public function trigger( $order_id ) {
		$this->setup_locale();

		if ( $order_id && ! is_a( $order_id, 'WC_Order' ) ) {
			$this->object = wc_get_order( $order_id );
		} else {
			$this->object = $order_id;
		}

		if ( ! is_a( $this->object, 'WC_Order' ) ) {
			$this->restore_locale();
			return;
		}

		$this->recipient = $this->object->get_billing_email();

		$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
		$this->placeholders['{order_number}'] = $this->object->get_order_number();

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send(
				$this->get_recipient(),
				$this->get_subject(),
				$this->get_content(),
				$this->get_headers(),
				$this->get_attachments()
			);
		}

		$this->restore_locale();
	}

	/**
	 * HTML-Inhalt abrufen
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Plain-Text-Inhalt abrufen
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Standard-Betreff
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'Ihre Bestellung {order_number} ist bald abholbereit', 'libre-bite' );
	}

	/**
	 * Standard-Überschrift
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Abholung in Kürze', 'libre-bite' );
	}

	/**
	 * Admin-Formular-Felder
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'            => array(
				'title'   => __( 'Aktivieren/Deaktivieren', 'libre-bite' ),
				'type'    => 'checkbox',
				'label'   => __( 'Diese E-Mail-Benachrichtigung aktivieren', 'libre-bite' ),
				'default' => 'yes',
			),
			'subject'            => array(
				'title'       => __( 'Betreff', 'libre-bite' ),
				'type'        => 'text',
				'desc_tip'    => true,
				/* translators: %s: default email subject */
				'description' => sprintf( __( 'Standard: %s', 'libre-bite' ), $this->get_default_subject() ),
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
			),
			'heading'            => array(
				'title'       => __( 'Überschrift', 'libre-bite' ),
				'type'        => 'text',
				'desc_tip'    => true,
				/* translators: %s: default email heading */
				'description' => sprintf( __( 'Standard: %s', 'libre-bite' ), $this->get_default_heading() ),
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
			),
			'additional_content' => array(
				'title'       => __( 'Zusätzlicher Inhalt', 'libre-bite' ),
				'description' => __( 'Text unterhalb der E-Mail.', 'libre-bite' ),
				'css'         => 'width:400px; height: 75px;',
				'placeholder' => __( 'N/A', 'libre-bite' ),
				'type'        => 'textarea',
				'default'     => '',
				'desc_tip'    => true,
			),
			'email_type'         => array(
				'title'       => __( 'E-Mail-Typ', 'libre-bite' ),
				'type'        => 'select',
				'description' => __( 'Wählen Sie das Format für diese E-Mail.', 'libre-bite' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			),
		);
	}
}
