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
	private static $feature_definitions = array(
		// Bestellsystem
		'enable_pos'                => array(
			'group'       => 'order_system',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Kassensystem (POS)',
			'description' => 'Kassensystem für Vor-Ort-Bestellungen aktivieren',
		),
		'enable_scheduled_orders'   => array(
			'group'       => 'order_system',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Vorbestellungen',
			'description' => 'Kunden können Bestellungen für später aufgeben',
		),
		'enable_order_notes'        => array(
			'group'       => 'order_system',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Kundennotizen',
			'description' => 'Kunden können Notizen zur Bestellung hinzufügen',
		),
		'enable_order_cancellation' => array(
			'group'       => 'order_system',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Stornierung',
			'description' => 'Kunden können Bestellungen selbst stornieren',
		),

		// Checkout
		'enable_optimized_checkout' => array(
			'group'       => 'checkout',
			'default'     => true,
			'premium'     => true,
			'label'       => 'Optimierter Checkout',
			'description' => 'Vereinfachter Checkout-Flow',
		),
		'enable_tips'               => array(
			'group'       => 'checkout',
			'default'     => true,
			'premium'     => true,
			'label'       => 'Trinkgeld-System',
			'description' => 'Trinkgeld-Optionen im Checkout anzeigen',
		),
		'enable_rounding'           => array(
			'group'       => 'checkout',
			'default'     => true,
			'premium'     => false,
			'label'       => '5-Rappen-Rundung',
			'description' => 'Beträge auf 5 Rappen runden (Schweiz)',
		),
		'enable_guest_checkout'     => array(
			'group'       => 'checkout',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Gast-Checkout',
			'description' => 'Checkout ohne Kundenkonto ermöglichen',
		),
		'enable_email_field'        => array(
			'group'       => 'checkout',
			'default'     => true,
			'premium'     => false,
			'label'       => 'E-Mail-Feld',
			'description' => 'E-Mail-Adresse im Checkout anzeigen',
		),
		'enable_phone_field'        => array(
			'group'       => 'checkout',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Telefon-Feld',
			'description' => 'Telefonnummer im Checkout anzeigen',
		),

		// Standorte
		'enable_multi_location'     => array(
			'group'       => 'locations',
			'default'     => false,
			'premium'     => true,
			'label'       => 'Multi-Standort',
			'description' => 'Mehrere Standorte verwalten',
		),
		'enable_location_selector'  => array(
			'group'       => 'locations',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Standort-Auswahl',
			'description' => 'Standort-Auswahl im Frontend anzeigen',
		),
		'enable_opening_hours'      => array(
			'group'       => 'locations',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Öffnungszeiten',
			'description' => 'Öffnungszeiten pro Standort verwalten',
		),

		// Benachrichtigungen
		'enable_pickup_reminders'   => array(
			'group'       => 'notifications',
			'default'     => true,
			'premium'     => true,
			'label'       => 'Abhol-Erinnerungen',
			'description' => 'E-Mail-Erinnerung vor Abholzeit senden',
		),
		'enable_sound_notifications' => array(
			'group'       => 'notifications',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Sound-Benachrichtigung',
			'description' => 'Ton bei neuen Bestellungen abspielen',
		),
		'enable_admin_email'        => array(
			'group'       => 'notifications',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Admin-Benachrichtigung',
			'description' => 'E-Mail an Admin bei neuen Bestellungen',
		),

		// Produkte
		'enable_product_options'    => array(
			'group'       => 'products',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Produkt-Optionen',
			'description' => 'Zusatzoptionen für Produkte (Add-ons)',
		),
		'enable_nutritional_info'   => array(
			'group'       => 'products',
			'default'     => false,
			'premium'     => false,
			'label'       => 'Nährwertangaben',
			'description' => 'Nährwertinformationen bei Produkten anzeigen',
		),
		'enable_allergens'          => array(
			'group'       => 'products',
			'default'     => false,
			'premium'     => false,
			'label'       => 'Allergene',
			'description' => 'Allergen-Warnungen bei Produkten anzeigen',
		),

		// Dashboard
		'enable_kanban_board'       => array(
			'group'       => 'dashboard',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Kanban-Board',
			'description' => 'Bestellungen als Kanban-Board anzeigen',
		),
		'enable_auto_status_change' => array(
			'group'       => 'dashboard',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Auto-Status',
			'description' => 'Automatischer Status-Wechsel bei Zeitüberschreitung',
		),
		'enable_fullscreen_mode'    => array(
			'group'       => 'dashboard',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Fullscreen-Modus',
			'description' => 'Vollbild-Ansicht für Tablets aktivieren',
		),
	);

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
	 * Konstruktor
	 */
	private function __construct() {
		$this->load_features();
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

		// Wenn das Feature generell deaktiviert ist, direkt false zurückgeben
		if ( ! $enabled ) {
			return apply_filters( 'lb_feature_enabled', false, $feature );
		}

		// Premium-Check
		$is_premium_feature = isset( self::$feature_definitions[ $feature ]['premium'] ) && self::$feature_definitions[ $feature ]['premium'];

		if ( $is_premium_feature ) {
			// 1. Check auf Entwickler-Override via Konstante
			if ( defined( 'LB_PREMIUM_OVERRIDE' ) && LB_PREMIUM_OVERRIDE ) {
				return apply_filters( 'lb_feature_enabled', true, $feature );
			}

			// 2. Check via Freemius SDK
			if ( function_exists( 'lb_freemius' ) ) {
				if ( ! lb_freemius()->is_premium() ) {
					return apply_filters( 'lb_feature_enabled', false, $feature );
				}
			} else {
				// Falls Freemius nicht geladen ist (Sicherheits-Fallback)
				return apply_filters( 'lb_feature_enabled', false, $feature );
			}
		}

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
