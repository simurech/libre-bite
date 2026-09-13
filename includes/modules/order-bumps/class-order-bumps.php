<?php
/**
 * Order Bumps – Impulsangebote im Kassenbereich
 *
 * Zeigt kurz vor dem Bezahlen passende Zusatzartikel an: «Pommes dazu?».
 * Der Gast hat bereits entschieden zu bestellen, die Hürde für eine Beilage
 * ist an dieser Stelle minimal.
 *
 * Bewusst als echte Warenkorb-Position und nicht als Gebühr umgesetzt.
 * Der naheliegende Weg über `woocommerce_cart_calculate_fees` hätte drei
 * Nachteile: der Artikel erschiene nicht auf dem Küchenbon, die Statistik
 * zählte ihn nicht als verkauftes Produkt, und er läge im selben Pfad wie
 * Trinkgeld und Rappenrundung. Als Warenkorb-Position entfällt all das.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse LBite_Order_Bumps
 */
class LBite_Order_Bumps {

	/**
	 * Option mit den konfigurierten Angeboten.
	 */
	const OPTION = 'lbite_order_bumps';

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

		$this->loader->add_action( 'woocommerce_review_order_before_payment', $this, 'render_bumps' );
		$this->loader->add_action( 'wp_ajax_lbite_add_order_bump', $this, 'ajax_add_bump' );
		$this->loader->add_action( 'wp_ajax_nopriv_lbite_add_order_bump', $this, 'ajax_add_bump' );
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Konfiguration
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Konfigurierte Angebote lesen
	 *
	 * @return array Liste aus [ 'product_id' => int, 'text' => string ].
	 */
	public static function get_bumps() {
		$raw = get_option( self::OPTION, array() );

		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * Eingaben aus den Einstellungen bereinigen
	 *
	 * @param mixed $raw Rohwert.
	 * @return array
	 */
	public static function sanitize_bumps( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$clean = array();
		$seen  = array();

		foreach ( $raw as $entry ) {
			$product_id = isset( $entry['product_id'] ) ? absint( $entry['product_id'] ) : 0;

			// Leere Zeilen und Doppelnennungen überspringen.
			if ( ! $product_id || isset( $seen[ $product_id ] ) ) {
				continue;
			}

			$seen[ $product_id ] = true;

			$clean[] = array(
				'product_id' => $product_id,
				'text'       => isset( $entry['text'] ) ? sanitize_text_field( $entry['text'] ) : '',
			);

			// Mehr als drei Angebote wirken im Checkout aufdringlich statt hilfreich.
			if ( count( $clean ) >= 3 ) {
				break;
			}
		}

		return $clean;
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Darstellung
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Angebote im Kassenbereich ausgeben
	 */
	public function render_bumps() {
		if ( ! lbite_feature_enabled( 'enable_order_bumps' ) ) {
			return;
		}

		$offers = $this->get_available_bumps();

		if ( empty( $offers ) ) {
			return;
		}
		?>
		<div class="lbite-bumps" data-lbite-bumps>
			<?php foreach ( $offers as $offer ) : ?>
				<?php
				$product = $offer['product'];
				$text    = '' !== $offer['text']
					? $offer['text']
					: sprintf(
						/* translators: %s: product name */
						__( 'Add %s to your order?', 'libre-bite' ),
						$product->get_name()
					);
				?>
				<label class="lbite-bump" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
					<input type="checkbox" class="lbite-bump__check">
					<?php if ( $product->get_image_id() ) : ?>
						<img class="lbite-bump__img" src="<?php echo esc_url( (string) wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) ); ?>" alt="">
					<?php endif; ?>
					<span class="lbite-bump__body">
						<span class="lbite-bump__text"><?php echo esc_html( $text ); ?></span>
						<span class="lbite-bump__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
					</span>
				</label>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Angebote ermitteln, die gerade sinnvoll sind
	 *
	 * Ausgeschlossen werden Artikel, die bereits im Warenkorb liegen, nicht
	 * kaufbar sind oder ausserhalb ihres Verfügbarkeits-Zeitplans liegen.
	 *
	 * @return array Liste aus [ 'product' => WC_Product, 'text' => string ].
	 */
	private function get_available_bumps() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return array();
		}

		$in_cart = array();
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$in_cart[ (int) $cart_item['product_id'] ] = true;
		}

		$offers = array();

		foreach ( self::get_bumps() as $entry ) {
			$product_id = (int) $entry['product_id'];

			if ( isset( $in_cart[ $product_id ] ) ) {
				continue;
			}

			$product = wc_get_product( $product_id );

			if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				continue;
			}

			// Variable Produkte brauchen eine Auswahl und eignen sich daher nicht
			// für einen Ein-Klick-Zusatz.
			if ( $product->is_type( 'variable' ) ) {
				continue;
			}

			if ( class_exists( 'LBite_Menu_Schedule' ) && ! LBite_Menu_Schedule::is_product_available( $product_id ) ) {
				continue;
			}

			$offers[] = array(
				'product' => $product,
				'text'    => $entry['text'],
			);
		}

		return $offers;
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Endpunkt
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * AJAX: Angebot in den Warenkorb legen
	 */
	public function ajax_add_bump() {
		check_ajax_referer( 'lbite_frontend_nonce', 'nonce' );

		if ( ! lbite_feature_enabled( 'enable_order_bumps' ) ) {
			wp_send_json_error( array( 'message' => __( 'Not available', 'libre-bite' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;

		// Nur ausdrücklich konfigurierte Artikel zulassen – sonst liesse sich
		// über diesen Endpunkt jedes beliebige Produkt einschleusen.
		$allowed = wp_list_pluck( self::get_bumps(), 'product_id' );

		if ( ! $product_id || ! in_array( $product_id, array_map( 'absint', $allowed ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown offer', 'libre-bite' ) ) );
		}

		// WooCommerce wertet diesen Filter an seinen eigenen Eintrittspunkten aus,
		// WC_Cart::add_to_cart() selbst tut es nicht. Ohne den Aufruf liesse sich
		// hierüber ein Artikel bestellen, der am gewählten Standort gesperrt,
		// ausserhalb seines Zeitfensters oder nur für die Kasse gedacht ist.
		if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, 1 ) ) {
			wp_send_json_error( array( 'message' => __( 'This item is not available right now.', 'libre-bite' ) ) );
		}

		$added = WC()->cart->add_to_cart( $product_id, 1 );

		if ( ! $added ) {
			wp_send_json_error( array( 'message' => __( 'Could not add the item', 'libre-bite' ) ) );
		}

		wp_send_json_success( array( 'product_id' => $product_id ) );
	}
}
