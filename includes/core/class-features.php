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
class LBite_Features {

	/**
	 * Singleton-Instanz
	 *
	 * @var LBite_Features
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
		// Order system
		'enable_pos'                => array(
			'group'       => 'order_system',
			'default'     => true,
			'premium'     => false,
			'label'       => 'POS System',
			'description' => 'Enable point-of-sale system for on-site orders',
		),
		'enable_scheduled_orders'   => array(
			'group'       => 'order_system',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Pre-orders',
			'description' => 'Customers can place orders for a later time',
		),
		'enable_order_notes'        => array(
			'group'       => 'order_system',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Customer Notes',
			'description' => 'Customers can add notes to their order',
		),
		'enable_order_cancellation' => array(
			'group'       => 'order_system',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Cancellation',
			'description' => 'Customers can cancel their own orders',
		),
		'enable_table_ordering'     => array(
			'group'       => 'order_system',
			'default'     => false,
			'premium'     => true,
			'label'       => 'Table Management & Table Ordering',
			'description' => 'Create tables, define seats, generate QR codes and allow orders directly at the table',
		),
		'enable_reservations'       => array(
			'group'       => 'order_system',
			'default'     => false,
			'premium'     => true,
			'label'       => 'Table Reservations',
			'description' => 'Customers can reserve tables online – frontend form via shortcode [lbite_reservation_form]',
		),

		// Checkout
		'enable_optimized_checkout' => array(
			'group'       => 'checkout',
			'default'     => true,
			'premium'     => true,
			'label'       => 'Optimized Checkout',
			'description' => 'Simplified checkout flow',
		),
		'enable_tips'               => array(
			'group'       => 'checkout',
			'default'     => true,
			'premium'     => true,
			'label'       => 'Tip System',
			'description' => 'Show tip options at checkout',
		),
		'enable_rounding'           => array(
			'group'       => 'checkout',
			'default'     => true,
			'premium'     => false,
			'label'       => '5-Cent Rounding',
			'description' => 'Round amounts to 5 cents (Switzerland)',
		),
		'enable_guest_checkout'     => array(
			'group'       => 'checkout',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Guest Checkout',
			'description' => 'Allow checkout without a customer account',
		),
		'enable_email_field'        => array(
			'group'       => 'checkout',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Email Field',
			'description' => 'Show email address field at checkout',
		),
		'enable_phone_field'        => array(
			'group'       => 'checkout',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Phone Field',
			'description' => 'Show phone number field at checkout',
		),

		// Locations
		'enable_multi_location'     => array(
			'group'       => 'locations',
			'default'     => false,
			'premium'     => true,
			'label'       => 'Multi-Location',
			'description' => 'Manage multiple locations',
		),
		'enable_location_selector'  => array(
			'group'       => 'locations',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Location Selection',
			'description' => 'Show location selector in the frontend',
		),
		'enable_opening_hours'      => array(
			'group'       => 'locations',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Opening Hours',
			'description' => 'Manage opening hours per location',
		),

		// Notifications
		'enable_pickup_reminders'   => array(
			'group'       => 'notifications',
			'default'     => true,
			'premium'     => true,
			'label'       => 'Pickup Reminders',
			'description' => 'Send email reminder before pickup time',
		),
		'enable_sound_notifications' => array(
			'group'       => 'notifications',
			'default'     => true,
			'premium'     => true,
			'label'       => 'Sound Notifications',
			'description' => 'Play a sound for new orders',
		),
		'enable_admin_email'        => array(
			'group'       => 'notifications',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Admin Email',
			'description' => 'Send email to admin for new orders',
		),

		// Products
		'enable_product_options'    => array(
			'group'       => 'products',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Product Options',
			'description' => 'Additional options for products (add-ons)',
		),
		'enable_nutritional_info'   => array(
			'group'       => 'products',
			'default'     => false,
			'premium'     => true,
			'label'       => 'Nutritional Information',
			'description' => 'Show nutritional information for products',
		),
		'enable_allergens'          => array(
			'group'       => 'products',
			'default'     => false,
			'premium'     => true,
			'label'       => 'Allergens',
			'description' => 'Show allergen warnings for products',
		),

		// Dashboard
		'enable_kanban_board'       => array(
			'group'       => 'dashboard',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Kanban Board',
			'description' => 'Display orders as a kanban board',
		),
		'enable_auto_status_change' => array(
			'group'       => 'dashboard',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Automatic Status Change',
			'description' => 'Automatic status change on timeout',
		),
		'enable_fullscreen_mode'    => array(
			'group'       => 'dashboard',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Fullscreen Mode',
			'description' => 'Enable fullscreen view for tablets',
		),
	);

	/**
	 * Singleton-Instanz abrufen
	 *
	 * @return LBite_Features
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
		$saved_features = get_option( 'lbite_features', array() );

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
			return apply_filters( 'lbite_feature_enabled', false, $feature );
		}

		// Premium-Check
		$is_premium_feature = isset( self::$feature_definitions[ $feature ]['premium'] ) && self::$feature_definitions[ $feature ]['premium'];

		if ( $is_premium_feature ) {
			// Dieser Block wird vom Freemius-Generator in der Gratis-Version automatisch entfernt.
			if ( lbite_freemius()->is__premium_only() ) {
				// Entwickler-Override via Konstante (nur in Premium-Version verfügbar)
				if ( defined( 'LBITE_PREMIUM_OVERRIDE' ) && LBITE_PREMIUM_OVERRIDE ) {
					return apply_filters( 'lbite_feature_enabled', true, $feature );
				}

				if ( ! lbite_freemius()->can_use_premium_code__premium_only() ) {
					return apply_filters( 'lbite_feature_enabled', false, $feature );
				}
			} else {
				// Gratis-Version: Premium-Features immer deaktiviert.
				return apply_filters( 'lbite_feature_enabled', false, $feature );
			}
		}

		/**
		 * Filter zum Überschreiben des Feature-Status
		 *
		 * @param bool   $enabled Aktueller Status
		 * @param string $feature Feature-Key
		 */
		return apply_filters( 'lbite_feature_enabled', $enabled, $feature );
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

		$result = update_option( 'lbite_features', $sanitized );

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
function lbite_feature_enabled( $feature ) {
	return LBite_Features::instance()->is_enabled( $feature );
}
