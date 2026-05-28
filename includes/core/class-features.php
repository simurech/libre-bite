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
			'default'     => false,
			'premium'     => true,
			'label'       => 'Optimized Checkout',
			'description' => 'Simplified checkout flow',
		),
		'enable_tips'               => array(
			'group'       => 'checkout',
			'default'     => false,
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
		'enable_swiss_vat'          => array(
			'group'       => 'checkout',
			'default'     => false,
			'premium'     => true,
			'label'       => 'Swiss VAT Switching',
			'description' => 'Apply different VAT rates for dine-in (8.1%) vs. takeaway (2.6%)',
		),

		// Locations
		'enable_location_selector'  => array(
			'group'       => 'locations',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Location Selection',
			'description' => 'Show location selector in the frontend',
		),

		// Notifications
		'enable_pickup_reminders'   => array(
			'group'       => 'notifications',
			'default'     => false,
			'premium'     => true,
			'label'       => 'Pickup Reminders',
			'description' => 'Send email reminder before pickup time',
		),
		'enable_sound_notifications' => array(
			'group'       => 'notifications',
			'default'     => false,
			'premium'     => true,
			'label'       => 'Sound Notifications',
			'description' => 'Play a sound for new orders',
		),

		// Products
		'enable_product_options'    => array(
			'group'       => 'products',
			'default'     => true,
			'premium'     => false,
			'label'       => 'Product Options',
			'description' => 'Additional options for products (add-ons)',
		),
		'enable_item_notes_pos'     => array(
			'group'       => 'products',
			'default'     => false,
			'premium'     => false,
			'label'       => 'Item Notes (POS)',
			'description' => 'Allow staff to add notes to individual cart items in POS',
		),
		'enable_item_notes_checkout' => array(
			'group'       => 'products',
			'default'     => false,
			'premium'     => false,
			'label'       => 'Item Notes (Checkout)',
			'description' => 'Allow customers to add notes to individual cart items',
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
		'enable_future_orders_dimmed' => array(
			'group'       => 'dashboard',
			'default'     => false,
			'premium'     => true,
			'label'       => 'Dim Future Pre-orders',
			'description' => 'Grey out pre-orders with pickup time beyond preparation time in the Kanban board',
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
		$saved_features  = get_option( 'lbite_features', array() );
		$premium_allowed = function_exists( 'lbite_freemius' )
			&& lbite_freemius()->can_use_premium_code__premium_only();

		foreach ( self::$feature_definitions as $key => $definition ) {
			$value = isset( $saved_features[ $key ] )
				? (bool) $saved_features[ $key ]
				: $definition['default'];

			// Pro-Features ohne gültige Lizenz immer erzwingen – verhindert Phantom-Defaults.
			if ( $definition['premium'] && ! $premium_allowed ) {
				$value = false;
			}

			$this->features[ $key ] = $value;
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
	 * Standard-Werte aller Features aus den Definitionen ableiten
	 *
	 * @return array
	 */
	public static function get_default_values() {
		$defaults = array();
		foreach ( self::$feature_definitions as $key => $definition ) {
			$defaults[ $key ] = $definition['default'];
		}
		return $defaults;
	}

	/**
	 * WP-Options-Keys, die Premium-Funktionen steuern (nicht Feature-Toggles)
	 *
	 * Wird vom Pro-Schutz-Helper verwendet, um Premium-Settings auf Free-Installationen
	 * beim Speichern auf ihre Default-Werte zurückzusetzen.
	 *
	 * @return array Associative: option_key => default_value
	 */
	public static function get_premium_only_options() {
		return array(
			'lbite_slot_buffer_start'     => 0,
			'lbite_slot_buffer_end'       => 0,
			'lbite_table_order_page_id'   => 0,
			'lbite_checkout_mode'         => 'standard',
			'lbite_show_future_orders'    => 1,
			'lbite_dim_future_orders'     => 1,
			'lbite_pickup_reminder_time'  => 15,
			'lbite_tip_percentage_1'      => 5,
			'lbite_tip_percentage_2'      => 10,
			'lbite_tip_percentage_3'      => 15,
			'lbite_tip_mode'              => 'percentage',
			'lbite_tip_title'             => '',
			'lbite_tip_default_selection' => 'none',
		);
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

/**
 * Premium-Options in einem Settings-Array auf Default zwingen (für Free-Installationen)
 *
 * Alle Keys aus LBite_Features::get_premium_only_options() werden auf ihren Default-Wert
 * gesetzt, wenn keine gültige Premium-Lizenz vorhanden ist. Muss vor jedem update_option()-
 * Aufruf auf den Input-Array angewendet werden.
 *
 * @param array $values Zu prüfendes Wertearray (option_key => value)
 * @return array Bereinigtes Wertearray
 */
function lbite_enforce_pro_options( array $values ) {
	$premium_allowed = function_exists( 'lbite_freemius' )
		&& lbite_freemius()->can_use_premium_code__premium_only();

	if ( $premium_allowed ) {
		return $values;
	}

	$pro_defaults = LBite_Features::get_premium_only_options();

	foreach ( $pro_defaults as $key => $default ) {
		if ( array_key_exists( $key, $values ) ) {
			$values[ $key ] = $default;
		}
	}

	return $values;
}
