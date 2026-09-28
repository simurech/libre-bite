<?php
/**
 * Stempelkarte
 *
 * Ein Stempel je abgeschlossener Bestellung über einem Mindestbetrag, nach
 * einer festgelegten Anzahl ein Rabattgutschein mit Gültigkeitsfrist.
 *
 * Bewusst Stempel statt Punkte: «jede zehnte Bestellung günstiger» versteht
 * ein Gast sofort, ein Punktestand mit Umrechnungskurs nicht. Ausserdem gibt
 * es dadurch deutlich weniger Zustand, den man falsch berechnen kann — es
 * wird gezählt, nicht gerechnet.
 *
 * Der Gutschein ist ein echter WooCommerce-Coupon. Damit greifen
 * Einlösung, Gültigkeit und Verrechnung über Bordmittel, statt in einer
 * eigenen Rabattlogik nachgebaut zu werden.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse LBite_Stampcard
 */
class LBite_Stampcard {

	/**
	 * Benutzer-Meta: aktueller Stempelstand.
	 */
	const META_COUNT = '_lbite_stamps';

	/**
	 * Benutzer-Meta: aktuell offener Gutscheincode.
	 */
	const META_COUPON = '_lbite_stamp_coupon';

	/**
	 * Bestell-Meta: für diese Bestellung wurde bereits gestempelt.
	 */
	const META_ORDER = '_lbite_stamp_awarded';

	/**
	 * Bereits gewährter Rabatt je Gutscheincode innerhalb der aktuellen
	 * Warenkorb-Berechnung - für cap_percent_discount().
	 *
	 * @var array
	 */
	private static $discount_given = array();

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

		// Bewusst der WooCommerce-Status und nicht der Kanban-Status: so
		// zählt jede abgeschlossene Bestellung, egal über welchen Weg sie
		// entstanden ist.
		$this->loader->add_action( 'woocommerce_order_status_completed', $this, 'award_stamp' );

		// Storno/Erstattung nach bereits vergebenem Stempel entzog diesen
		// bisher nicht (Audit 26.09.2026, AP-11).
		$this->loader->add_action( 'woocommerce_order_status_cancelled', $this, 'revoke_stamp' );
		$this->loader->add_action( 'woocommerce_order_status_refunded', $this, 'revoke_stamp' );

		$this->loader->add_action( 'init', $this, 'register_shortcode' );
		$this->loader->add_action( 'woocommerce_account_dashboard', $this, 'render_account_card', 20 );

		// Rabattdeckel für prozentuale Stempelkarten-Gutscheine (Audit
		// 26.09.2026, AP-11): set_maximum_amount() begrenzte bisher den
		// zulässigen Bestellwert statt den Rabatt selbst zu deckeln.
		$this->loader->add_action( 'woocommerce_before_calculate_totals', $this, 'reset_discount_tracking', 5 );
		$this->loader->add_filter( 'woocommerce_coupon_get_discount_amount', $this, 'cap_percent_discount', 10, 5 );

		// Stempelstand und Gutscheincode sind personenbezogene Daten - ohne
		// diese beiden Filter tauchten sie in Werkzeuge → Persönliche Daten
		// weder im Export noch bei der Löschung auf (Audit 26.09.2026, AP-16).
		$this->loader->add_filter( 'wp_privacy_personal_data_exporters', $this, 'register_privacy_exporter' );
		$this->loader->add_filter( 'wp_privacy_personal_data_erasers', $this, 'register_privacy_eraser' );
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Konfiguration
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Einstellungen lesen
	 *
	 * @return array
	 */
	public static function get_settings() {
		return array(
			'min_total'         => (float) get_option( 'lbite_stampcard_min_total', 0 ),
			'target'            => max( 2, (int) get_option( 'lbite_stampcard_target', 10 ) ),
			'discount'          => max( 0.01, (float) get_option( 'lbite_stampcard_discount', 50 ) ),
			'valid_days'        => max( 1, (int) get_option( 'lbite_stampcard_validity_days', 90 ) ),
			'discount_type'     => 'fixed' === get_option( 'lbite_stampcard_discount_type', 'percent' ) ? 'fixed' : 'percent',
			'max_amount'        => max( 0, (float) get_option( 'lbite_stampcard_max_amount', 0 ) ),
			'limit_categories'  => array_filter( array_map( 'absint', (array) get_option( 'lbite_stampcard_limit_categories', array() ) ) ),
			'limit_to_one_item' => (bool) get_option( 'lbite_stampcard_limit_to_one_item', 0 ),
		);
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Stempeln
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Stempel für eine abgeschlossene Bestellung vergeben
	 *
	 * @param int $order_id Bestell-ID.
	 */
	public function award_stamp( $order_id ) {
		if ( ! lbite_feature_enabled( 'enable_stampcard' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$user_id = (int) $order->get_customer_id();

		// Ohne Kundenkonto gibt es niemanden, dem der Stempel gehören
		// könnte. Gastbestellungen bleiben aussen vor.
		if ( ! $user_id ) {
			return;
		}

		// Eine Bestellung stempelt genau einmal, auch wenn ihr Status
		// mehrfach auf «abgeschlossen» wechselt.
		if ( '1' === (string) $order->get_meta( self::META_ORDER, true ) ) {
			return;
		}

		$settings = self::get_settings();

		if ( (float) $order->get_total() < $settings['min_total'] ) {
			return;
		}

		$count = (int) get_user_meta( $user_id, self::META_COUNT, true ) + 1;

		$order->update_meta_data( self::META_ORDER, '1' );
		$order->save();

		if ( $count >= $settings['target'] ) {
			$code = $this->create_reward_coupon( $user_id, $settings );

			if ( '' !== $code ) {
				// Zähler zurücksetzen, Überschuss mitnehmen: wer bei elf
				// Stempeln einlöst, startet nicht bei null.
				update_user_meta( $user_id, self::META_COUNT, $count - $settings['target'] );
				update_user_meta( $user_id, self::META_COUPON, $code );

				/**
				 * Nach Ausstellung eines Stempelkarten-Gutscheins.
				 *
				 * @param int    $user_id Benutzer-ID.
				 * @param string $code    Gutscheincode.
				 */
				do_action( 'lbite_stampcard_reward_issued', $user_id, $code );

				return;
			}
		}

		update_user_meta( $user_id, self::META_COUNT, $count );
	}

	/**
	 * Stempel einer stornierten/erstatteten Bestellung entziehen.
	 *
	 * @param int $order_id Bestell-ID.
	 */
	public function revoke_stamp( $order_id ) {
		if ( ! lbite_feature_enabled( 'enable_stampcard' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || '1' !== (string) $order->get_meta( self::META_ORDER, true ) ) {
			return;
		}

		$user_id = (int) $order->get_customer_id();
		if ( ! $user_id ) {
			return;
		}

		$count = (int) get_user_meta( $user_id, self::META_COUNT, true );
		update_user_meta( $user_id, self::META_COUNT, max( 0, $count - 1 ) );

		$order->delete_meta_data( self::META_ORDER );
		$order->save();
	}

	/**
	 * Gutschein anlegen
	 *
	 * @param int   $user_id  Benutzer-ID.
	 * @param array $settings Einstellungen.
	 * @return string Gutscheincode oder leer bei Fehlschlag.
	 */
	private function create_reward_coupon( $user_id, $settings ) {
		if ( ! class_exists( 'WC_Coupon' ) ) {
			return '';
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return '';
		}

		$code = 'LB' . strtoupper( wp_generate_password( 8, false, false ) );

		try {
			$coupon = new WC_Coupon();
			$coupon->set_code( $code );

			if ( 'fixed' === $settings['discount_type'] ) {
				$coupon->set_discount_type( 'fixed_cart' );
				$coupon->set_amount( $settings['discount'] );
			} else {
				$coupon->set_discount_type( 'percent' );
				$coupon->set_amount( $settings['discount'] );
				if ( $settings['max_amount'] > 0 ) {
					// set_maximum_amount() begrenzt den zulässigen Bestellwert,
					// nicht den Rabatt: der Gutschein wurde bei grösseren
					// Warenkörben ungültig statt den Rabatt zu deckeln (Audit
					// 26.09.2026, AP-11). Eigene Meta + Filter
					// (siehe cap_percent_discount()) deckeln stattdessen den
					// tatsächlichen Rabattbetrag, anteilig über die Positionen.
					$coupon->update_meta_data( '_lbite_max_discount', $settings['max_amount'] );
				}
			}

			// Beschränkung auf bestimmte Kategorien und/oder auf 1 Artikel
			// der Bestellung – beides nativ von WooCommerce unterstützt,
			// keine eigene Rabattlogik nötig.
			if ( ! empty( $settings['limit_categories'] ) ) {
				$coupon->set_product_categories( $settings['limit_categories'] );
			}
			if ( ! empty( $settings['limit_to_one_item'] ) ) {
				$coupon->set_limit_usage_to_x_items( 1 );
			}

			$coupon->set_individual_use( true );
			$coupon->set_usage_limit( 1 );

			// An die E-Mail gebunden, damit der Code nicht weitergegeben
			// werden kann.
			$coupon->set_email_restrictions( array( $user->user_email ) );
			$coupon->set_date_expires( time() + ( $settings['valid_days'] * DAY_IN_SECONDS ) );
			$coupon->set_description(
				sprintf(
					/* translators: %d: number of stamps */
					__( 'Stamp card reward for %d orders', 'libre-bite' ),
					$settings['target']
				)
			);
			$coupon->save();
		} catch ( Exception $e ) {
			return '';
		}

		return $code;
	}

	/**
	 * Prüft, ob ein Stempelkarten-Gutschein noch einlösbar ist.
	 *
	 * @param string $code Gutscheincode.
	 * @return bool
	 */
	private static function is_coupon_still_redeemable( $code ) {
		$coupon_id = wc_get_coupon_id_by_code( $code );

		if ( ! $coupon_id ) {
			return false;
		}

		$coupon = new WC_Coupon( $coupon_id );

		if ( $coupon->get_usage_count() >= $coupon->get_usage_limit() ) {
			return false;
		}

		$expires = $coupon->get_date_expires();

		return ! $expires || $expires->getTimestamp() > time();
	}

	/**
	 * Rabattverfolgung vor jeder Neuberechnung zurücksetzen.
	 */
	public function reset_discount_tracking() {
		self::$discount_given = array();
	}

	/**
	 * Prozentualen Rabatt eines Stempelkarten-Gutscheins auf den konfigurierten
	 * Maximalbetrag deckeln, anteilig über die Warenkorb-Positionen verteilt.
	 *
	 * @param float     $discount           Berechneter Rabatt für diese Position.
	 * @param float     $discounting_amount Betrag, auf den sich der Rabatt bezieht.
	 * @param array     $cart_item          Warenkorb-Position.
	 * @param bool      $single             Einzelpreis-Berechnung.
	 * @param WC_Coupon $coupon             Gutschein.
	 * @return float
	 */
	public function cap_percent_discount( $discount, $discounting_amount, $cart_item, $single, $coupon ) {
		$max_discount = (float) $coupon->get_meta( '_lbite_max_discount' );

		if ( $max_discount <= 0 ) {
			return $discount;
		}

		$code           = $coupon->get_code();
		$given_so_far   = isset( self::$discount_given[ $code ] ) ? self::$discount_given[ $code ] : 0.0;
		$remaining      = max( 0, $max_discount - $given_so_far );
		$capped_discount = min( $discount, $remaining );

		self::$discount_given[ $code ] = $given_so_far + $capped_discount;

		return $capped_discount;
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Anzeige
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Shortcode registrieren
	 */
	public function register_shortcode() {
		add_shortcode( 'lbite_stampcard', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Shortcode-Ausgabe
	 *
	 * @return string
	 */
	public function render_shortcode() {
		ob_start();
		$this->render_card();

		return ob_get_clean();
	}

	/**
	 * Karte im Kundenkonto
	 */
	public function render_account_card() {
		$this->render_card();
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Datenschutz (Persönliche Daten exportieren/löschen)
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Exporteur registrieren
	 *
	 * @param array $exporters Bestehende Exporteure.
	 * @return array
	 */
	public function register_privacy_exporter( $exporters ) {
		$exporters['lbite-stampcard'] = array(
			'exporter_friendly_name' => __( 'Libre Bite Stamp Card', 'libre-bite' ),
			'callback'               => array( $this, 'export_data' ),
		);
		return $exporters;
	}

	/**
	 * Daten für den Export zusammenstellen
	 *
	 * @param string $email_address E-Mail-Adresse der betroffenen Person.
	 * @return array
	 */
	public function export_data( $email_address ) {
		$user        = get_user_by( 'email', $email_address );
		$export_data = array();

		if ( $user ) {
			$count  = (int) get_user_meta( $user->ID, self::META_COUNT, true );
			$coupon = (string) get_user_meta( $user->ID, self::META_COUPON, true );

			if ( $count > 0 || '' !== $coupon ) {
				$items = array(
					array( 'name' => __( 'Stamps', 'libre-bite' ), 'value' => $count ),
				);
				if ( '' !== $coupon ) {
					$items[] = array( 'name' => __( 'Reward Coupon', 'libre-bite' ), 'value' => $coupon );
				}

				$export_data[] = array(
					'group_id'    => 'lbite-stampcard',
					'group_label' => __( 'Stamp Card', 'libre-bite' ),
					'item_id'     => 'lbite-stampcard',
					'data'        => $items,
				);
			}
		}

		return array(
			'data' => $export_data,
			'done' => true,
		);
	}

	/**
	 * Eraser registrieren
	 *
	 * @param array $erasers Bestehende Eraser.
	 * @return array
	 */
	public function register_privacy_eraser( $erasers ) {
		$erasers['lbite-stampcard'] = array(
			'eraser_friendly_name' => __( 'Libre Bite Stamp Card', 'libre-bite' ),
			'callback'             => array( $this, 'erase_data' ),
		);
		return $erasers;
	}

	/**
	 * Stempelstand und Gutscheincode löschen
	 *
	 * @param string $email_address E-Mail-Adresse der betroffenen Person.
	 * @return array
	 */
	public function erase_data( $email_address ) {
		$user          = get_user_by( 'email', $email_address );
		$items_removed = false;

		if ( $user ) {
			if ( '' !== (string) get_user_meta( $user->ID, self::META_COUNT, true ) ) {
				delete_user_meta( $user->ID, self::META_COUNT );
				$items_removed = true;
			}
			if ( '' !== (string) get_user_meta( $user->ID, self::META_COUPON, true ) ) {
				delete_user_meta( $user->ID, self::META_COUPON );
				$items_removed = true;
			}
		}

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	/**
	 * Stempelkarte ausgeben
	 */
	private function render_card() {
		if ( ! lbite_feature_enabled( 'enable_stampcard' ) || ! is_user_logged_in() ) {
			return;
		}

		$user_id  = get_current_user_id();
		$settings = self::get_settings();
		$count    = min( $settings['target'], (int) get_user_meta( $user_id, self::META_COUNT, true ) );
		$coupon   = (string) get_user_meta( $user_id, self::META_COUPON, true );

		// Ein bereits eingelöster oder abgelaufener Gutschein wurde bisher
		// unbegrenzt weiter angezeigt (Audit 26.09.2026, AP-11).
		if ( '' !== $coupon && ! self::is_coupon_still_redeemable( $coupon ) ) {
			delete_user_meta( $user_id, self::META_COUPON );
			$coupon = '';
		}

		wp_enqueue_style(
			'lbite-stampcard',
			LBITE_PLUGIN_URL . 'assets/css/stampcard.css',
			array(),
			LBITE_VERSION
		);
		?>
		<div class="lbite-stampcard">
			<h3 class="lbite-stampcard__title"><?php esc_html_e( 'Your stamp card', 'libre-bite' ); ?></h3>

			<div class="lbite-stampcard__stamps" role="img"
				aria-label="<?php echo esc_attr( sprintf( /* translators: 1: collected stamps, 2: stamps needed */ __( '%1$d of %2$d stamps collected', 'libre-bite' ), $count, $settings['target'] ) ); ?>">
				<?php for ( $lbite_i = 0; $lbite_i < $settings['target']; $lbite_i++ ) : ?>
					<span class="lbite-stampcard__stamp<?php echo $lbite_i < $count ? ' is-filled' : ''; ?>" aria-hidden="true"></span>
				<?php endfor; ?>
			</div>

			<?php
			// Fixbetrag zeigt bisher immer "% Rabatt", auch wenn discount_type
			// auf "fixed" stand (Audit 26.09.2026, AP-11).
			$lbite_discount_display = 'fixed' === $settings['discount_type']
				? wp_strip_all_tags( wc_price( $settings['discount'] ) )
				: (int) $settings['discount'] . '%';
			?>
			<?php if ( '' !== $coupon ) : ?>
				<p class="lbite-stampcard__reward">
					<?php
					printf(
						/* translators: 1: discount amount or percentage, 2: coupon code */
						esc_html__( 'Your reward is ready: %1$s off with the code %2$s', 'libre-bite' ),
						esc_html( $lbite_discount_display ),
						'<strong>' . esc_html( $coupon ) . '</strong>'
					);
					?>
				</p>
			<?php else : ?>
				<p class="lbite-stampcard__progress">
					<?php
					$lbite_left = max( 0, $settings['target'] - $count );
					printf(
						/* translators: 1: remaining stamps, 2: discount amount or percentage */
						esc_html( _n( '%1$d more order and you get %2$s off.', '%1$d more orders and you get %2$s off.', $lbite_left, 'libre-bite' ) ),
						(int) $lbite_left,
						esc_html( $lbite_discount_display )
					);
					?>
				</p>
			<?php endif; ?>

			<?php if ( $settings['min_total'] > 0 ) : ?>
				<p class="lbite-stampcard__note">
					<?php
					printf(
						/* translators: %s: minimum order value */
						esc_html__( 'Orders from %s count towards a stamp.', 'libre-bite' ),
						esc_html( wp_strip_all_tags( wc_price( $settings['min_total'] ) ) )
					);
					?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
}
