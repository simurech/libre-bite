<?php
/**
 * Feature-Manager für Libre Bite
 *
 * Zentrale Klasse für Feature-Toggle-Verwaltung.
 * Ermöglicht das Aktivieren/Deaktivieren einzelner Funktionen.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Feature-Manager Klasse
 */
class LB_Features {

	/**
	 * Singleton-Instanz
	 *
	 * @var LB_Features
	 */
	private static $instance = null;

	/**
	 * Geladene Features
	 *
	 * @var array
	 */
	private $features = array();

	/**
	 * Feature-Definitionen mit Metadaten
	 *
	 * @var array
	 */
	private static $feature_definitions = array();

	/**
	 * Konstruktor
	 */
	private function __construct() {
		$this->init_definitions();
		$this->load_features();
	}

	/**
	 * Definitionen initialisieren (damit Übersetzungen geladen werden können)
	 */
	private function init_definitions() {
		self::$feature_definitions = array(
			// Bestellsystem
			'enable_pos'                => array(
				'group'       => 'order_system',
				'default'     => true,
				'premium'     => false,
				'label'       => __( 'Kassensystem (POS)', 'libre-bite' ),
				'description' => __( 'Kassensystem für Vor-Ort-Bestellungen aktivieren', 'libre-bite' ),
			),
			'enable_scheduled_orders'   => array(
				'group'       => 'order_system',
				'default'     => true,
				'premium'     => false,
				'label'       => __( 'Vorbestellungen', 'libre-bite' ),
				'description' => __( 'Kunden können Bestellungen für später aufgeben', 'libre-bite' ),
			),
			'enable_order_notes'        => array(
				'group'       => 'order_system',
				'default'     => true,
				'premium'     => false,
				'label'       => __( 'Kundennotizen', 'libre-bite' ),
				'description' => __( 'Kunden können Notizen zur Bestellung hinzufügen', 'libre-bite' ),
			),
			'enable_order_cancellation' => array(
				'group'       => 'order_system',
				'default'     => true,
				'premium'     => false,
				'label'       => __( 'Stornierung', 'libre-bite' ),
				'description' => __( 'Kunden können Bestellungen selbst stornieren', 'libre-bite' ),
			),

			// Checkout
			'enable_optimized_checkout' => array(
				'group'       => 'checkout',
				'default'     => true,
				'premium'     => true,
				'label'       => __( 'Optimierter Checkout', 'libre-bite' ),
				'description' => __( 'Vereinfachter Checkout-Flow', 'libre-bite' ),
			),
			'enable_tips'               => array(
				'group'       => 'checkout',
				'default'     => true,
				'premium'     => true,
				'label'       => __( 'Trinkgeld-System', 'libre-bite' ),
				'description' => __( 'Trinkgeld-Optionen im Checkout anzeigen', 'libre-bite' ),
			),
			'enable_rounding'           => array(
				'group'       => 'checkout',
				'default'     => true,
				'premium'     => false,
				'label'       => __( '5-Rappen-Rundung', 'libre-bite' ),
				'description' => __( 'Beträge auf 5 Rappen runden (Schweiz)', 'libre-bite' ),
			),
			'enable_guest_checkout'     => array(
				'group'       => 'checkout',
				'default'     => true,
				'premium'     => false,
				'label'       => __( 'Gast-Checkout', 'libre-bite' ),
				'description' => __( 'Checkout ohne Kundenkonto ermöglichen', 'libre-bite' ),
			),
			'enable_email_field'        => array(
				'group'       => 'checkout',
				'default'     => true,
				'premium'     => false,
				'label'       => __( 'E-Mail-Feld', 'libre-bite' ),
				'description' => __( 'E-Mail-Adresse im Checkout anzeigen', 'libre-bite' ),
			),
			'enable_phone_field'        => array(
				'group'       => 'checkout',
				'default'     => true,
				'premium'     => false,
				'label'       => __( 'Telefon-Feld', 'libre-bite' ),
				'description' => __( 'Telefonnummer im Checkout anzeigen', 'libre-bite' ),
			),

			// Standorte
			'enable_multi_location'     => array(
				'group'       => 'locations',
				'default'     => false,
				'premium'     => true,
				'label'       => __( 'Multi-Standort', 'libre-bite' ),
				'description' => __( 'Mehrere Standorte verwalten', 'libre-bite' ),
			),
			'enable_location_selector'  => array(
				'group'       => 'locations',
				'default'     => true,
				'premium'     => false,
				'label'       => __( 'Standort-Auswahl', 'libre-bite' ),
				'description' => __( 'Standort-Auswahl im Frontend anzeigen', 'libre-bite' ),
			),
			'enable_opening_hours'      => array(
				'group'       => 'locations',
				'default'     => true,
				'premium'     => false,
				'label'       => __( 'Öffnungszeiten', 'libre-bite' ),
				'description' => __( 'Öffnungszeiten pro Standort verwalten', 'libre-bite' ),
			),

			// Benachrichtigungen
			'enable_pickup_reminders'   => array(
				'group'       => 'notifications',
				'default'     => true,
				'premium'     => true,
				'label'       => __( 'Abhol-Erinnerungen', 'libre-bite' ),
				'description' => __( 'E-Mail-Erinnerung vor Abholzeit senden', 'libre-bite' ),
			),
			'enable_sound_notifications' => array(
				'group'       => 'notifications',
				'default'     => true,
				'premium'     => false,
				'label'       => __( 'Sound-Benachrichtigung', 'libre-bite' ),
				'description' => __( 'Ton bei neuen Bestellungen abspielen', 'libre-bite' ),
			),
			'enable_admin_email'        => array(
				'group'       => 'notifications',
				'default'     => true,
				'premium'     => false,
				'label'       => __( 'Admin-Benachrichtigung', 'libre-bite' ),
				'description' => __( 'E-Mail an Admin bei neuen Bestellungen', 'libre-bite' ),
			),

			// Produkte
			'enable_product_options'    => array(
				'group'       => 'products',
				'default'     => true,
				'premium'     => false,
				'label'       => __( 'Produkt-Optionen', 'libre-bite' ),
				'description' => __( 'Zusatzoptionen für Produkte (Add-ons)', 'libre-bite' ),
			),
			'enable_nutritional_info'   => array(
				'group'       => 'products',
				'default'     => false,
				'premium'     => false,
				'label'       => __( 'Nährwertangaben', 'libre-bite' ),
				'description' => __( 'Nährwertinformationen bei Produkten anzeigen', 'libre-bite' ),
			),
			'enable_allergens'          => array(
				'group'       => 'products',
				'default'     => false,
				'premium'     => false,
				'label'       => __( 'Allergene', 'libre-bite' ),
				'description' => __( 'Allergen-Warnungen bei Produkten anzeigen', 'libre-bite' ),
			),

			// Dashboard
			'enable_kanban_board'       => array(
				'group'       => 'dashboard',
				'default'     => true,
				'premium'     => false,
				'label'       => __( 'Kanban-Board', 'libre-bite' ),
				'description' => __( 'Bestellungen als Kanban-Board anzeigen', 'libre-bite' ),
			),
			'enable_auto_status_change' => array(
				'group'       => 'dashboard',
				'default'     => true,
				'premium'     => false,
				'label'       => __( 'Auto-Status', 'libre-bite' ),
				'description' => __( 'Automatischer Status-Wechsel bei Zeitüberschreitung', 'libre-bite' ),
			),
			'enable_fullscreen_mode'    => array(
				'group'       => 'dashboard',
				'default'     => true,
				'premium'     => false,
				'label'       => __( 'Fullscreen-Modus', 'libre-bite' ),
				'description' => __( 'Vollbild-Ansicht für Tablets aktivieren', 'libre-bite' ),
			),
		);
	}

	/**
	 * Singleton-Instanz abrufen
	 *
	 * @return LB_Features
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Features aus Datenbank laden
	 */
	private function load_features() {
		$saved_features = get_option( 'lb_features', array() );

		// Defaults mit gespeicherten Werten mergen
		foreach ( self::$feature_definitions as $key => $definition ) {
			$this->features[ $key ] = isset( $saved_features[ $key ] )
				? (bool) $saved_features[ $key ]
				: $definition['default'];
		}
	}

	/**
	 * Prüfen ob ein Feature aktiviert ist
	 *
	 * @param string $feature Feature-Key
	 * @return bool
	 */
	public function is_enabled( $feature ) {
		// Filter für externe Steuerung
		$enabled = isset( $this->features[ $feature ] ) ? $this->features[ $feature ] : false;

		/**
		 * Filter zum Überschreiben des Feature-Status
		 *
		 * @param bool   $enabled Aktueller Status
		 * @param string $feature Feature-Key
		 */
		return apply_filters( 'lb_feature_enabled', $enabled, $feature );
	}

	/**
	 * Alle Features abrufen
	 *
	 * @return array
	 */
	public function get_all() {
		return $this->features;
	}

	/**
	 * Feature-Definitionen abrufen
	 *
	 * @return array
	 */
	public static function get_definitions() {
		return self::$feature_definitions;
	}

	/**
	 * Features nach Gruppe abrufen
	 *
	 * @param string $group Gruppen-Name
	 * @return array
	 */
	public function get_by_group( $group ) {
		$filtered = array();
		foreach ( self::$feature_definitions as $key => $definition ) {
			if ( $definition['group'] === $group ) {
				$filtered[ $key ] = $this->features[ $key ];
			}
		}
		return $filtered;
	}

	/**
	 * Premium-Features abrufen
	 *
	 * @return array
	 */
	public static function get_premium_features() {
		$premium = array();
		foreach ( self::$feature_definitions as $key => $definition ) {
			if ( $definition['premium'] ) {
				$premium[] = $key;
			}
		}
		return $premium;
	}

	/**
	 * Feature-Wert setzen (temporär, wird nicht gespeichert)
	 *
	 * @param string $feature Feature-Key
	 * @param bool   $enabled Status
	 */
	public function set( $feature, $enabled ) {
		if ( isset( $this->features[ $feature ] ) ) {
			$this->features[ $feature ] = (bool) $enabled;
		}
	}

	/**
	 * Features speichern
	 *
	 * @param array $features Zu speichernde Features
	 * @return bool
	 */
	public function save( $features ) {
		$sanitized = array();
		foreach ( $features as $key => $value ) {
			if ( isset( self::$feature_definitions[ $key ] ) ) {
				$sanitized[ $key ] = (bool) $value;
			}
		}

		$result = update_option( 'lb_features', $sanitized );

		if ( $result ) {
			$this->features = $sanitized;
		}

		return $result;
	}

	/**
	 * Alle Features auf Defaults zurücksetzen
	 *
	 * @return bool
	 */
	public function reset_to_defaults() {
		$defaults = array();
		foreach ( self::$feature_definitions as $key => $definition ) {
			$defaults[ $key ] = $definition['default'];
		}

		return $this->save( $defaults );
	}
}

/**
 * Globale Hilfsfunktion zum Prüfen von Features
 *
 * @param string $feature Feature-Key
 * @return bool
 */
function lb_feature_enabled( $feature ) {
	return LB_Features::instance()->is_enabled( $feature );
}
