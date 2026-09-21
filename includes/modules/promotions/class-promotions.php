<?php
/**
 * Aktionen und Ankündigungen
 *
 * Regelbasierte Rabatte, die WooCommerce von sich aus nicht kennt: zwei zum
 * Preis von einem, Prozente auf eine Kategorie, Nachlass ab einem
 * Warenkorbwert — jeweils nur an bestimmten Tagen oder zu bestimmten Zeiten.
 * Dazu eine Ankündigungsleiste, damit die Aktion auch gesehen wird.
 *
 * Zwei Entscheidungen, die den Ausschlag geben:
 *
 * 1. **Reihenfolge der Gebühren.** Warenkorb-Rabatte greifen bei Priorität 5,
 *    also **vor** dem Trinkgeld (10) und der Rappenrundung (999). Andernfalls
 *    würde das Trinkgeld auf einen Betrag berechnet, den niemand bezahlt, und
 *    die Rundung liefe auf ein Zwischenergebnis.
 * 2. **Zeitsteuerung wiederverwendet.** Die Wochentag- und Uhrzeitlogik ist
 *    dieselbe wie bei der zeitgesteuerten Verfügbarkeit
 *    (`LBite_Menu_Schedule::is_active()`) — bereits geprüft, inklusive
 *    Fenstern über Mitternacht. Eine zweite Zeitlogik wäre eine zweite
 *    Fehlerquelle.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse LBite_Promotions
 */
class LBite_Promotions {

	/**
	 * Option mit den Aktionsregeln.
	 */
	const OPTION = 'lbite_promotions';

	/**
	 * Option mit der Ankündigungsleiste.
	 */
	const OPTION_BANNER = 'lbite_promo_banner';

	/**
	 * Loader-Instanz.
	 *
	 * @var LBite_Loader
	 */
	private $loader;

	/**
	 * Unrabattierter Preis je Warenkorbposition, innerhalb einer Anfrage gemerkt.
	 *
	 * WooCommerce ruft `woocommerce_before_calculate_totals` mehrfach je Anfrage auf
	 * und behält dabei dasselbe Produktobjekt. Ohne diesen Basiswert würde jeder
	 * weitere Durchlauf den Rabatt erneut auf den bereits rabattierten Preis rechnen.
	 *
	 * @var array<string,float>
	 */
	private $base_prices = array();

	/**
	 * Konstruktor
	 *
	 * @param LBite_Loader $loader Hook-Loader.
	 */
	public function __construct( $loader ) {
		$this->loader = $loader;

		// Produktbezogene Rabatte über den Positionspreis – nicht über eine
		// Gebühr. So erscheint der Nachlass dort, wo er hingehört, und die
		// Position bleibt auf Bon und in der Statistik korrekt zugeordnet.
		$this->loader->add_action( 'woocommerce_before_calculate_totals', $this, 'apply_item_rules', 15 );

		// Warenkorbweite Rabatte als negative Gebühr, bewusst vor dem
		// Trinkgeld.
		$this->loader->add_action( 'woocommerce_cart_calculate_fees', $this, 'apply_cart_rules', 5 );

		// Produktrabatte hinterlassen sonst keine Spur für die Statistik –
		// der Positionspreis ist einfach niedriger, ohne erkennbaren Grund.
		$this->loader->add_action( 'woocommerce_checkout_create_order_line_item', $this, 'add_order_item_promotion_meta', 20, 4 );

		// Ankündigungsleiste
		$this->loader->add_action( 'wp_body_open', $this, 'render_banner' );
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_banner_style', 25 );
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Regeln
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Verfügbare Regeltypen
	 *
	 * @return array Schlüssel => Beschriftung.
	 */
	public static function get_types() {
		return array(
			'product' => __( 'Discount on products or categories', 'libre-bite' ),
			'bogo'    => __( 'Buy several, pay for fewer', 'libre-bite' ),
			'cart'    => __( 'Discount on the whole order', 'libre-bite' ),
		);
	}

	/**
	 * Gespeicherte Regeln lesen
	 *
	 * @return array
	 */
	public static function get_rules() {
		$raw = get_option( self::OPTION, array() );

		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * Regeln aus dem Formular bereinigen
	 *
	 * @param mixed $raw Rohwert.
	 * @return array
	 */
	public static function sanitize_rules( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$types = array_keys( self::get_types() );
		$clean = array();

		foreach ( $raw as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$label = isset( $entry['label'] ) ? sanitize_text_field( $entry['label'] ) : '';
			$type  = isset( $entry['type'] ) ? sanitize_key( $entry['type'] ) : '';

			// Eine Regel ohne Namen oder mit unbekanntem Typ ist unbrauchbar.
			if ( '' === $label || ! in_array( $type, $types, true ) ) {
				continue;
			}

			$rule = array(
				'enabled'      => ! empty( $entry['enabled'] ),
				'label'        => $label,
				'type'         => $type,
				'amount'       => isset( $entry['amount'] ) ? max( 0, (float) $entry['amount'] ) : 0.0,
				'is_percent'   => ! empty( $entry['is_percent'] ),
				'product_ids'  => self::sanitize_id_list( isset( $entry['product_ids'] ) ? $entry['product_ids'] : '' ),
				'category_ids' => self::sanitize_id_list( isset( $entry['category_ids'] ) ? $entry['category_ids'] : '' ),
				'min_total'    => isset( $entry['min_total'] ) ? max( 0, (float) $entry['min_total'] ) : 0.0,
				'buy_qty'      => isset( $entry['buy_qty'] ) ? max( 2, (int) $entry['buy_qty'] ) : 2,
				'free_qty'     => isset( $entry['free_qty'] ) ? max( 1, (int) $entry['free_qty'] ) : 1,
				'schedule'     => class_exists( 'LBite_Menu_Schedule' )
					? LBite_Menu_Schedule::sanitize_schedule( isset( $entry['schedule'] ) ? $entry['schedule'] : array() )
					: array(),
			);

			// Bei „mehrere kaufen, weniger zahlen" muss die Gratismenge
			// kleiner sein als die Kaufmenge – sonst wäre alles gratis.
			if ( 'bogo' === $rule['type'] && $rule['free_qty'] >= $rule['buy_qty'] ) {
				$rule['free_qty'] = $rule['buy_qty'] - 1;
			}

			$clean[] = $rule;

			if ( count( $clean ) >= 10 ) {
				break;
			}
		}

		return $clean;
	}

	/**
	 * Kommaliste von IDs bereinigen
	 *
	 * @param mixed $value Rohwert.
	 * @return int[]
	 */
	private static function sanitize_id_list( $value ) {
		if ( is_array( $value ) ) {
			$parts = $value;
		} else {
			$parts = explode( ',', (string) $value );
		}

		$ids = array();

		foreach ( $parts as $part ) {
			$id = absint( trim( (string) $part ) );

			if ( $id ) {
				$ids[ $id ] = $id;
			}
		}

		return array_values( $ids );
	}

	/**
	 * Regeln, die gerade gelten
	 *
	 * @return array
	 */
	public static function get_active_rules() {
		if ( ! lbite_feature_enabled( 'enable_promotions' ) ) {
			return array();
		}

		$active = array();

		foreach ( self::get_rules() as $rule ) {
			if ( empty( $rule['enabled'] ) ) {
				continue;
			}

			if ( ! self::is_within_schedule( $rule ) ) {
				continue;
			}

			$active[] = $rule;
		}

		return $active;
	}

	/**
	 * Gilt die Regel zum jetzigen Zeitpunkt?
	 *
	 * @param array $rule Regel.
	 * @return bool
	 */
	private static function is_within_schedule( $rule ) {
		if ( empty( $rule['schedule'] ) || ! class_exists( 'LBite_Menu_Schedule' ) ) {
			return true;
		}

		return LBite_Menu_Schedule::is_active( $rule['schedule'], (int) current_time( 'timestamp' ) );
	}

	/**
	 * Trifft eine Regel auf ein Produkt zu?
	 *
	 * Ohne Produkt- und Kategorieangabe gilt sie für alles.
	 *
	 * @param array $rule       Regel.
	 * @param int   $product_id Produkt-ID.
	 * @return bool
	 */
	public static function matches_product( $rule, $product_id ) {
		$product_ids  = isset( $rule['product_ids'] ) ? $rule['product_ids'] : array();
		$category_ids = isset( $rule['category_ids'] ) ? $rule['category_ids'] : array();

		if ( empty( $product_ids ) && empty( $category_ids ) ) {
			return true;
		}

		if ( in_array( (int) $product_id, array_map( 'absint', $product_ids ), true ) ) {
			return true;
		}

		if ( empty( $category_ids ) ) {
			return false;
		}

		$terms = get_the_terms( $product_id, 'product_cat' );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return false;
		}

		foreach ( $terms as $term ) {
			if ( in_array( (int) $term->term_id, array_map( 'absint', $category_ids ), true ) ) {
				return true;
			}
		}

		return false;
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Anwendung auf Positionen
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Produktbezogene Regeln auf den Warenkorb anwenden
	 *
	 * @param WC_Cart $cart Warenkorb.
	 */
	public function apply_item_rules( $cart ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		$rules = self::get_active_rules();

		if ( empty( $rules ) ) {
			return;
		}

		// Jeden Durchlauf beim Basispreis beginnen. Früher brach ein static-Schalter
		// alle Folgedurchläufe ab; dadurch bekam eine Position, die erst nach der
		// ersten Berechnung in den Warenkorb kam – etwa über einen Order Bump –
		// niemals ihren Rabatt.
		foreach ( $cart->get_cart() as $cart_key => $cart_item ) {
			if ( ! isset( $cart_item['data'] ) ) {
				continue;
			}

			if ( isset( $this->base_prices[ $cart_key ] ) ) {
				$cart_item['data']->set_price( $this->base_prices[ $cart_key ] );
			} else {
				$this->base_prices[ $cart_key ] = (float) $cart_item['data']->get_price();
			}
		}

		foreach ( $rules as $rule ) {
			if ( 'product' === $rule['type'] ) {
				$this->apply_product_discount( $cart, $rule );
			} elseif ( 'bogo' === $rule['type'] ) {
				$this->apply_bogo( $cart, $rule );
			}
		}

		// Für die Statistik festhalten, um wie viel eine Position gegenüber
		// ihrem Basispreis rabattiert wurde – reine Laufzeit-Meta auf dem
		// Warenkorb-Produktobjekt, landet nie in der Datenbank.
		foreach ( $cart->get_cart() as $cart_key => $cart_item ) {
			if ( ! isset( $cart_item['data'], $this->base_prices[ $cart_key ] ) ) {
				continue;
			}

			$discount_per_unit = round( $this->base_prices[ $cart_key ] - (float) $cart_item['data']->get_price(), wc_get_price_decimals() );

			if ( $discount_per_unit > 0 ) {
				$cart_item['data']->add_meta_data( '_lbite_promotion_discount_per_unit', $discount_per_unit, true );
			} else {
				$cart_item['data']->delete_meta_data( '_lbite_promotion_discount_per_unit' );
				$cart_item['data']->delete_meta_data( '_lbite_promotion_label' );
			}
		}
	}

	/**
	 * Prozentualen oder festen Nachlass auf passende Positionen
	 *
	 * @param WC_Cart $cart Warenkorb.
	 * @param array   $rule Regel.
	 */
	private function apply_product_discount( $cart, $rule ) {
		foreach ( $cart->get_cart() as $cart_item ) {
			if ( ! isset( $cart_item['data'] ) ) {
				continue;
			}

			$product_id = (int) $cart_item['product_id'];

			if ( ! self::matches_product( $rule, $product_id ) ) {
				continue;
			}

			$price = (float) $cart_item['data']->get_price();

			if ( $price <= 0 ) {
				continue;
			}

			$new_price = ! empty( $rule['is_percent'] )
				? $price * ( 1 - ( min( 100, $rule['amount'] ) / 100 ) )
				: max( 0, $price - $rule['amount'] );

			$cart_item['data']->set_price( round( $new_price, wc_get_price_decimals() ) );
			$cart_item['data']->add_meta_data( '_lbite_promotion_label', $rule['label'], true );
		}
	}

	/**
	 * Mehrere kaufen, weniger zahlen
	 *
	 * Gratis ist immer die günstigste passende Position — das entspricht dem,
	 * was Gäste erwarten und verhindert, dass eine Aktion teurer wird als
	 * beabsichtigt.
	 *
	 * @param WC_Cart $cart Warenkorb.
	 * @param array   $rule Regel.
	 */
	private function apply_bogo( $cart, $rule ) {
		$units = array();

		foreach ( $cart->get_cart() as $key => $cart_item ) {
			if ( ! isset( $cart_item['data'] ) ) {
				continue;
			}

			if ( ! self::matches_product( $rule, (int) $cart_item['product_id'] ) ) {
				continue;
			}

			$price = (float) $cart_item['data']->get_price();

			// Jede Einheit einzeln betrachten, sonst liesse sich die Aktion
			// mit einer Position der Menge 10 aushebeln.
			for ( $i = 0; $i < (int) $cart_item['quantity']; $i++ ) {
				$units[] = array(
					'key'   => $key,
					'price' => $price,
				);
			}
		}

		$buy = max( 2, (int) $rule['buy_qty'] );

		if ( count( $units ) < $buy ) {
			return;
		}

		usort(
			$units,
			function ( $a, $b ) {
				return $a['price'] <=> $b['price'];
			}
		);

		$sets      = (int) floor( count( $units ) / $buy );
		$free_each = max( 1, (int) $rule['free_qty'] );
		$free      = min( count( $units ), $sets * $free_each );

		$discount = 0.0;

		for ( $i = 0; $i < $free; $i++ ) {
			$discount += $units[ $i ]['price'];
		}

		if ( $discount <= 0 ) {
			return;
		}

		// Der Nachlass wird als eigene Warenkorb-Gebühr geführt, damit die
		// Einzelpreise unverändert bleiben und der Gast sieht, wofür der
		// Abzug steht.
		$cart->add_fee(
			sprintf(
				/* translators: %s: promotion name */
				__( 'Promotion: %s', 'libre-bite' ),
				$rule['label']
			),
			-1 * round( $discount, wc_get_price_decimals() ),
			false
		);
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Anwendung auf den Warenkorb
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Warenkorbweite Regeln anwenden
	 *
	 * Läuft bei Priorität 5 und damit vor Trinkgeld und Rundung.
	 *
	 * @param WC_Cart $cart Warenkorb.
	 */
	public function apply_cart_rules( $cart ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		$subtotal = (float) $cart->get_subtotal();

		foreach ( self::get_active_rules() as $rule ) {
			if ( 'cart' !== $rule['type'] ) {
				continue;
			}

			if ( $subtotal < (float) $rule['min_total'] ) {
				continue;
			}

			$discount = ! empty( $rule['is_percent'] )
				? $subtotal * ( min( 100, $rule['amount'] ) / 100 )
				: min( $subtotal, $rule['amount'] );

			if ( $discount <= 0 ) {
				continue;
			}

			$cart->add_fee(
				sprintf(
					/* translators: %s: promotion name */
					__( 'Promotion: %s', 'libre-bite' ),
					$rule['label']
				),
				-1 * round( $discount, wc_get_price_decimals() ),
				false
			);
		}
	}

	/**
	 * Rabatt-Herkunft einer Position als Order-Item-Meta übernehmen
	 *
	 * Rein additiv für die Statistik – ändert nichts an Preis oder
	 * Checkout-Verhalten, das ist bereits über `apply_product_discount()`
	 * (Positionspreis) bzw. `apply_bogo()`/`apply_cart_rules()` (Gebühr)
	 * erledigt.
	 *
	 * @param WC_Order_Item_Product $item          Neu erstelltes Bestell-Item.
	 * @param string                $cart_item_key Warenkorb-Schlüssel.
	 * @param array                 $values        Warenkorbposition.
	 * @param WC_Order              $order         Bestellung.
	 */
	public function add_order_item_promotion_meta( $item, $cart_item_key, $values, $order ) {
		if ( ! isset( $values['data'] ) || ! is_a( $values['data'], 'WC_Product' ) ) {
			return;
		}

		$label = $values['data']->get_meta( '_lbite_promotion_label', true );

		if ( '' === $label ) {
			return;
		}

		$per_unit = (float) $values['data']->get_meta( '_lbite_promotion_discount_per_unit', true );

		if ( $per_unit <= 0 ) {
			return;
		}

		$item->add_meta_data( '_lbite_promotion_label', $label, true );
		$item->add_meta_data( '_lbite_promotion_discount', round( $per_unit * $item->get_quantity(), wc_get_price_decimals() ), true );
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Ankündigungsleiste
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Konfiguration der Leiste
	 *
	 * @return array
	 */
	public static function get_banner() {
		$raw = get_option( self::OPTION_BANNER, array() );

		return wp_parse_args(
			is_array( $raw ) ? $raw : array(),
			array(
				'enabled'        => false,
				'text'           => '',
				'link'           => '',
				'open_in_new_tab' => false,
				'schedule'       => array(),
			)
		);
	}

	/**
	 * Leiste bereinigen
	 *
	 * @param mixed $raw Rohwert.
	 * @return array
	 */
	public static function sanitize_banner( $raw ) {
		$raw = is_array( $raw ) ? $raw : array();

		return array(
			'enabled'         => ! empty( $raw['enabled'] ),
			'text'            => isset( $raw['text'] ) ? sanitize_text_field( $raw['text'] ) : '',
			'link'            => isset( $raw['link'] ) ? esc_url_raw( $raw['link'] ) : '',
			'open_in_new_tab' => ! empty( $raw['open_in_new_tab'] ),
			'schedule'        => self::sanitize_banner_schedule( isset( $raw['schedule'] ) ? $raw['schedule'] : array() ),
		);
	}

	/**
	 * Zeitplan der Ankündigungsleiste bereinigen
	 *
	 * Bewusst eigene, kleine Implementierung statt `LBite_Menu_Schedule`
	 * wiederzuverwenden: nur hier sind – auf ausdrücklichen Wunsch –
	 * mehrere Zeitfenster pro Tag erlaubt (z. B. 8–12 und 14–18 Uhr), das
	 * gemeinsame Zeitplan-Schema für Produkte/Kategorien/Aktionen bleibt
	 * bewusst bei einem Fenster (siehe Doc-Kommentar in class-menu-schedule.php).
	 *
	 * @param mixed $raw Rohwert.
	 * @return array
	 */
	public static function sanitize_banner_schedule( $raw ) {
		$raw      = is_array( $raw ) ? $raw : array();
		$day_keys = class_exists( 'LBite_Menu_Schedule' )
			? LBite_Menu_Schedule::DAYS
			: array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );

		$days = array();
		foreach ( $day_keys as $day ) {
			$days[ $day ] = isset( $raw['days'][ $day ] ) ? ! empty( $raw['days'][ $day ] ) : true;
		}

		$windows = array();
		if ( isset( $raw['windows'] ) && is_array( $raw['windows'] ) ) {
			foreach ( $raw['windows'] as $window ) {
				$from = isset( $window['from'] ) ? trim( (string) $window['from'] ) : '';
				$to   = isset( $window['to'] ) ? trim( (string) $window['to'] ) : '';
				$from = preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $from ) ? $from : '';
				$to   = preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $to ) ? $to : '';

				if ( '' !== $from && '' !== $to ) {
					$windows[] = array(
						'from' => $from,
						'to'   => $to,
					);
				}
			}
		}

		$dates = array();
		foreach ( array( 'from_date', 'to_date' ) as $key ) {
			$value        = isset( $raw[ $key ] ) ? trim( (string) $raw[ $key ] ) : '';
			$dates[ $key ] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
		}

		return array(
			'enabled'   => ! empty( $raw['enabled'] ),
			'days'      => $days,
			'from_date' => $dates['from_date'],
			'to_date'   => $dates['to_date'],
			'windows'   => $windows,
		);
	}

	/**
	 * "HH:MM" in Minuten seit Mitternacht
	 *
	 * @param string $value Zeit als "HH:MM".
	 * @return int
	 */
	private static function banner_to_minutes( $value ) {
		$parts = explode( ':', $value );

		return ( (int) $parts[0] * 60 ) + (int) $parts[1];
	}

	/**
	 * Ist der Zeitplan der Ankündigungsleiste zum gegebenen Zeitpunkt aktiv?
	 *
	 * Wie `LBite_Menu_Schedule::is_active()`, aber über mehrere Zeitfenster
	 * statt einem – ein Treffer in irgendeinem Fenster genügt.
	 *
	 * @param mixed $schedule  Zeitplan (roh oder bereinigt).
	 * @param int   $timestamp Zu prüfender Zeitpunkt (lokale Zeit).
	 * @return bool
	 */
	public static function banner_schedule_is_active( $schedule, $timestamp ) {
		$schedule = self::sanitize_banner_schedule( $schedule );

		if ( empty( $schedule['enabled'] ) ) {
			return true;
		}

		$date = gmdate( 'Y-m-d', $timestamp );

		if ( '' !== $schedule['from_date'] && $date < $schedule['from_date'] ) {
			return false;
		}
		if ( '' !== $schedule['to_date'] && $date > $schedule['to_date'] ) {
			return false;
		}

		$day_index = (int) gmdate( 'N', $timestamp ) - 1;
		$day_keys  = class_exists( 'LBite_Menu_Schedule' )
			? LBite_Menu_Schedule::DAYS
			: array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
		$today     = $day_keys[ $day_index ];

		// Ohne Zeitfenster zählt nur der Wochentag.
		if ( empty( $schedule['windows'] ) ) {
			return ! empty( $schedule['days'][ $today ] );
		}

		$minutes_now = ( (int) gmdate( 'H', $timestamp ) * 60 ) + (int) gmdate( 'i', $timestamp );
		$yesterday   = $day_keys[ ( $day_index + 6 ) % 7 ];

		foreach ( $schedule['windows'] as $window ) {
			$from = self::banner_to_minutes( $window['from'] );
			$to   = self::banner_to_minutes( $window['to'] );

			if ( $from === $to ) {
				if ( ! empty( $schedule['days'][ $today ] ) ) {
					return true;
				}
				continue;
			}

			if ( $from < $to ) {
				if ( ! empty( $schedule['days'][ $today ] ) && $minutes_now >= $from && $minutes_now < $to ) {
					return true;
				}
				continue;
			}

			// Fenster über Mitternacht.
			if ( ! empty( $schedule['days'][ $today ] ) && $minutes_now >= $from ) {
				return true;
			}
			if ( ! empty( $schedule['days'][ $yesterday ] ) && $minutes_now < $to ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Stylesheet der Leiste laden
	 */
	public function enqueue_banner_style() {
		if ( ! $this->banner_is_visible() ) {
			return;
		}

		wp_enqueue_style(
			'lbite-promo-banner',
			LBITE_PLUGIN_URL . 'assets/css/promo-banner.css',
			array(),
			LBITE_VERSION
		);
	}

	/**
	 * Soll die Leiste erscheinen?
	 *
	 * @return bool
	 */
	private function banner_is_visible() {
		if ( ! lbite_feature_enabled( 'enable_promotions' ) || is_admin() ) {
			return false;
		}

		$banner = self::get_banner();

		if ( empty( $banner['enabled'] ) || '' === trim( $banner['text'] ) ) {
			return false;
		}

		if ( ! empty( $banner['schedule'] ) ) {
			return self::banner_schedule_is_active( $banner['schedule'], (int) current_time( 'timestamp' ) );
		}

		return true;
	}

	/**
	 * Leiste ausgeben
	 */
	public function render_banner() {
		if ( ! $this->banner_is_visible() ) {
			return;
		}

		$banner = self::get_banner();
		?>
		<div class="lbite-promo-banner" role="status">
			<?php if ( '' !== $banner['link'] ) : ?>
				<a href="<?php echo esc_url( $banner['link'] ); ?>"
					<?php echo ! empty( $banner['open_in_new_tab'] ) ? ' target="_blank" rel="noopener"' : ''; ?>>
					<?php echo esc_html( $banner['text'] ); ?>
				</a>
			<?php else : ?>
				<span><?php echo esc_html( $banner['text'] ); ?></span>
			<?php endif; ?>
		</div>
		<?php
	}
}
