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

		$this->loader->add_action( 'init', $this, 'register_shortcode' );
		$this->loader->add_action( 'woocommerce_account_dashboard', $this, 'render_account_card', 20 );
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
			'discount'          => max( 1, (int) get_option( 'lbite_stampcard_discount', 50 ) ),
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
					$coupon->set_maximum_amount( $settings['max_amount'] );
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

			<?php if ( '' !== $coupon ) : ?>
				<p class="lbite-stampcard__reward">
					<?php
					printf(
						/* translators: 1: discount percentage, 2: coupon code */
						esc_html__( 'Your reward is ready: %1$d%% off with the code %2$s', 'libre-bite' ),
						(int) $settings['discount'],
						'<strong>' . esc_html( $coupon ) . '</strong>'
					);
					?>
				</p>
			<?php else : ?>
				<p class="lbite-stampcard__progress">
					<?php
					$lbite_left = max( 0, $settings['target'] - $count );
					printf(
						/* translators: 1: remaining stamps, 2: discount percentage */
						esc_html( _n( '%1$d more order and you get %2$d%% off.', '%1$d more orders and you get %2$d%% off.', $lbite_left, 'libre-bite' ) ),
						(int) $lbite_left,
						(int) $settings['discount']
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
