<?php
/**
 * Theme-unabhängige Menü-Ansicht
 *
 * Gibt die Speisekarte in einem eigenen Layout aus, statt sich auf das
 * WooCommerce-Produktraster des jeweiligen Themes zu verlassen. Damit muss
 * niemand mehr am Theme oder an WooCommerce-Vorlagen schrauben, nur damit
 * die Karte brauchbar aussieht.
 *
 * Zwei bewusste Grundsatzentscheidungen:
 *
 * 1. **Eigene Datenbeschaffung statt der des POS.** `LBite_POS` liefert
 *    bewusst auch POS-only-Artikel und behandelt den Zeitplan nur als
 *    Hinweis — im Gästebereich wäre beides falsch. Hier werden stattdessen
 *    alle fünf Sichtbarkeitsregeln serverseitig durchgesetzt.
 * 2. **Nativer WooCommerce-Warenkorb statt eines eigenen Bestellwegs.**
 *    Ein eigener Pfad (wie ihn der POS geht) müsste Add-on-Verrechnung,
 *    Zeitplan-Prüfung und Steuerlogik nachbauen. Stattdessen sendet das
 *    Modal exakt dieselben Feldnamen wie die normale Produktseite.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse LBite_Menu_View
 */
class LBite_Menu_View {

	/**
	 * Shortcode-Tag.
	 */
	const SHORTCODE = 'lbite_menu';

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

		$this->loader->add_action( 'init', $this, 'register_shortcode' );
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'maybe_enqueue', 20 );
		$this->loader->add_action( 'wp_ajax_lbite_menu_product', $this, 'ajax_get_product' );
		$this->loader->add_action( 'wp_ajax_nopriv_lbite_menu_product', $this, 'ajax_get_product' );
		$this->loader->add_action( 'wp_ajax_lbite_menu_cart', $this, 'ajax_get_cart' );
		$this->loader->add_action( 'wp_ajax_nopriv_lbite_menu_cart', $this, 'ajax_get_cart' );
	}

	/**
	 * Shortcode registrieren
	 */
	public function register_shortcode() {
		add_shortcode( self::SHORTCODE, array( $this, 'render_shortcode' ) );
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Assets
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Assets laden, wenn der Shortcode auf der Seite vorkommt
	 */
	public function maybe_enqueue() {
		global $post;

		if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, self::SHORTCODE ) ) {
			return;
		}

		$this->enqueue_assets();
	}

	/**
	 * Assets tatsächlich einreihen
	 *
	 * Zweiter, unabhängiger Ladeweg neben maybe_enqueue(): wird der
	 * Shortcode aus einem Template oder Block heraus gerendert, greift die
	 * Inhaltsprüfung nicht.
	 */
	private function enqueue_assets() {
		if ( wp_style_is( 'lbite-menu-view', 'enqueued' ) ) {
			return;
		}

		wp_enqueue_style(
			'lbite-menu-view',
			LBITE_PLUGIN_URL . 'assets/css/menu-view.css',
			array(),
			LBITE_VERSION
		);

		wp_enqueue_script(
			'lbite-menu-view',
			LBITE_PLUGIN_URL . 'assets/js/menu-view.js',
			array( 'jquery' ),
			LBITE_VERSION,
			true
		);

		wp_localize_script(
			'lbite-menu-view',
			'lbiteMenu',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'lbite_frontend_nonce' ),
				'cartUrl'   => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
				'checkout'  => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
				'strings'   => array(
					'add'          => __( 'Add to order', 'libre-bite' ),
					'adding'       => __( 'Adding…', 'libre-bite' ),
					'added'        => __( 'Added', 'libre-bite' ),
					'error'        => __( 'Could not add the item. Please try again.', 'libre-bite' ),
					'cart'         => __( 'Your order', 'libre-bite' ),
					'emptyCart'    => __( 'Nothing in your order yet.', 'libre-bite' ),
					'checkout'     => __( 'Go to checkout', 'libre-bite' ),
					'close'        => __( 'Close', 'libre-bite' ),
					'total'        => __( 'Total', 'libre-bite' ),
					'chooseOption' => __( 'Please choose an option.', 'libre-bite' ),
				),
			)
		);
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Sichtbarkeit
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Darf ein Produkt in der Menü-Ansicht erscheinen?
	 *
	 * Fasst alle fünf Regeln zusammen, die im Gästebereich gelten müssen.
	 * Bewusst als eigene Methode und nicht verteilt: eine vergessene Regel
	 * hiesse, dass Gäste etwas bestellen können, das gar nicht verfügbar ist.
	 *
	 * @param WC_Product $product     Produkt.
	 * @param int        $location_id Gewählter Standort, 0 für keiner.
	 * @return bool
	 */
	public static function is_visible( $product, $location_id = 0 ) {
		if ( ! $product ) {
			return false;
		}

		$product_id = $product->get_id();

		// 1. Nur für die Kasse gedachte Artikel gehören nie ins Gästemenü.
		if ( '1' === (string) get_post_meta( $product_id, '_lbite_pos_only', true ) ) {
			return false;
		}

		// 2. Am gewählten Standort ausgeschlossen?
		if ( $location_id && class_exists( 'LBite_Locations' )
			&& ! LBite_Locations::is_product_available_at_location( $product_id, $location_id ) ) {
			return false;
		}

		// 3. Ausserhalb des Verfügbarkeits-Zeitplans?
		if ( class_exists( 'LBite_Menu_Schedule' )
			&& ! LBite_Menu_Schedule::is_product_available( $product_id ) ) {
			return false;
		}

		// 4. Vorübergehend als nicht verfügbar markiert? Dafür greift im
		//    normalen Shop kein Hook, das muss hier selbst geprüft werden.
		if ( self::is_temporarily_unavailable( $product_id ) ) {
			return false;
		}

		// 5. Nativer WooCommerce-Status.
		if ( ! $product->is_purchasable() ) {
			return false;
		}

		if ( ! $product->is_in_stock() && 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Ist ein Produkt vorübergehend gesperrt?
	 *
	 * `_lbite_unavailable_until` kennt zwei Zustände: ein leerer Wert
	 * bedeutet «unbefristet gesperrt», ein Zeitstempel «gesperrt bis».
	 * Fehlt das Meta ganz, ist der Artikel verfügbar.
	 *
	 * @param int $product_id Produkt-ID.
	 * @return bool
	 */
	private static function is_temporarily_unavailable( $product_id ) {
		if ( ! metadata_exists( 'post', $product_id, '_lbite_unavailable_until' ) ) {
			return false;
		}

		$until = (string) get_post_meta( $product_id, '_lbite_unavailable_until', true );

		if ( '' === $until ) {
			return true;
		}

		return strtotime( $until ) > current_time( 'timestamp' );
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Daten
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Menü-Daten zusammenstellen
	 *
	 * @param int $location_id Gewählter Standort.
	 * @return array Liste aus [ 'term' => WP_Term|null, 'products' => WC_Product[] ].
	 */
	public static function get_menu( $location_id = 0 ) {
		$product_ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 300,
				'fields'         => 'ids',
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
			)
		);

		$grouped   = array();
		$uncategorised = array();

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! self::is_visible( $product, $location_id ) ) {
				continue;
			}

			$terms = get_the_terms( $product_id, 'product_cat' );

			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				$uncategorised[] = $product;
				continue;
			}

			// Ein Artikel erscheint unter seiner ersten Kategorie – sonst
			// stünde er mehrfach in der Karte.
			$term = $terms[0];

			if ( ! isset( $grouped[ $term->term_id ] ) ) {
				$grouped[ $term->term_id ] = array(
					'term'     => $term,
					'products' => array(),
				);
			}

			$grouped[ $term->term_id ]['products'][] = $product;
		}

		$sections = array_values( $grouped );

		usort(
			$sections,
			function ( $a, $b ) {
				return strcmp( $a['term']->name, $b['term']->name );
			}
		);

		if ( ! empty( $uncategorised ) ) {
			$sections[] = array(
				'term'     => null,
				'products' => $uncategorised,
			);
		}

		return $sections;
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Shortcode
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Shortcode rendern
	 *
	 * @param array $atts Attribute.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		if ( ! lbite_feature_enabled( 'enable_menu_view' ) ) {
			return '';
		}

		if ( ! function_exists( 'WC' ) ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'layout' => 'grid',
				'cart'   => 'yes',
			),
			$atts,
			self::SHORTCODE
		);

		$lbite_layout = in_array( $atts['layout'], array( 'grid', 'list' ), true ) ? $atts['layout'] : 'grid';
		$lbite_cart   = 'no' !== $atts['cart'];

		$this->enqueue_assets();

		$lbite_location_id = ( WC()->session && WC()->session->get( 'lbite_location_id' ) )
			? (int) WC()->session->get( 'lbite_location_id' )
			: 0;

		$lbite_sections = self::get_menu( $lbite_location_id );

		ob_start();
		include LBITE_PLUGIN_DIR . 'templates/frontend/menu-view.php';

		return ob_get_clean();
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Warenkorb
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * AJAX: Warenkorb-Inhalt für die Seitenleiste
	 *
	 * Liest den echten WooCommerce-Warenkorb aus, statt eine eigene Zählung
	 * zu führen. Damit stimmen Mengen, Add-on-Aufpreise und Summen
	 * zwangsläufig mit dem Checkout überein.
	 */
	public function ajax_get_cart() {
		check_ajax_referer( 'lbite_frontend_nonce', 'nonce' );

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_success(
				array(
					'count' => 0,
					'html'  => '',
				)
			);
		}

		$cart  = WC()->cart;
		$items = $cart->get_cart();

		if ( empty( $items ) ) {
			wp_send_json_success(
				array(
					'count' => 0,
					'html'  => '',
				)
			);
		}

		$html = '<ul class="lbite-menu-cart__items">';

		foreach ( $items as $item ) {
			$product = isset( $item['data'] ) ? $item['data'] : null;

			if ( ! $product ) {
				continue;
			}

			$html .= '<li class="lbite-menu-cart__item">';
			$html .= '<span class="lbite-menu-cart__qty">' . esc_html( $item['quantity'] ) . '&times;</span>';
			$html .= '<span class="lbite-menu-cart__name">' . esc_html( $product->get_name() );

			// Gewählte Zusatzoptionen mit anzeigen – sonst wäre im Warenkorb
			// nicht erkennbar, wofür der abweichende Preis steht.
			if ( ! empty( $item['lbite_options'] ) && is_array( $item['lbite_options'] ) ) {
				$names = array();

				foreach ( $item['lbite_options'] as $option_id ) {
					$option = get_post( (int) $option_id );

					if ( $option ) {
						$names[] = $option->post_title;
					}
				}

				if ( $names ) {
					$html .= '<em class="lbite-menu-cart__opts">' . esc_html( implode( ', ', $names ) ) . '</em>';
				}
			}

			$html .= '</span>';
			$html .= '<span class="lbite-menu-cart__price">'
				. wp_kses_post( $cart->get_product_subtotal( $product, $item['quantity'] ) )
				. '</span>';
			$html .= '</li>';
		}

		$html .= '</ul>';
		$html .= '<div class="lbite-menu-cart__total"><span>'
			. esc_html__( 'Total', 'libre-bite' )
			. '</span><strong>' . wp_kses_post( $cart->get_cart_subtotal() ) . '</strong></div>';

		wp_send_json_success(
			array(
				'count' => (int) $cart->get_cart_contents_count(),
				'html'  => $html,
			)
		);
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Produkt-Modal
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * AJAX: Detaildaten eines Produkts für das Modal
	 *
	 * Liefert Varianten und Zusatzoptionen. Die Feldnamen entsprechen exakt
	 * denen der normalen Produktseite, damit die bestehende Warenkorb- und
	 * Bestellverarbeitung unverändert greift.
	 */
	public function ajax_get_product() {
		check_ajax_referer( 'lbite_frontend_nonce', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$product    = $product_id ? wc_get_product( $product_id ) : false;

		$location_id = ( function_exists( 'WC' ) && WC()->session )
			? (int) WC()->session->get( 'lbite_location_id' )
			: 0;

		// Auch hier gegen dieselben Regeln prüfen: sonst liesse sich über
		// den Endpunkt ein gesperrter Artikel in den Warenkorb bringen.
		if ( ! $product || ! self::is_visible( $product, $location_id ) ) {
			wp_send_json_error( array( 'message' => __( 'This product is not available.', 'libre-bite' ) ) );
		}

		$variations = array();

		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_available_variations() as $variation ) {
				$variations[] = array(
					'id'         => (int) $variation['variation_id'],
					'attributes' => $variation['attributes'],
					'price'      => wp_strip_all_tags( $variation['price_html'] ? $variation['price_html'] : wc_price( $variation['display_price'] ) ),
				);
			}
		}

		$options    = array();
		$option_ids = get_post_meta( $product->get_id(), '_lbite_product_options', true );

		if ( is_array( $option_ids ) ) {
			foreach ( $option_ids as $option_id ) {
				$option = get_post( (int) $option_id );

				if ( ! $option || 'publish' !== $option->post_status ) {
					continue;
				}

				$options[] = array(
					'id'    => (int) $option_id,
					'name'  => $option->post_title,
					'price' => (float) get_post_meta( $option_id, '_lbite_price', true ),
				);
			}
		}

		wp_send_json_success(
			array(
				'id'          => $product->get_id(),
				'name'        => $product->get_name(),
				'description' => wpautop( wp_kses_post( $product->get_short_description() ) ),
				'image'       => (string) wp_get_attachment_image_url( $product->get_image_id(), 'large' ),
				'price'       => wp_strip_all_tags( $product->get_price_html() ),
				'type'        => $product->get_type(),
				'attributes'  => $product->is_type( 'variable' ) ? $product->get_variation_attributes() : array(),
				'variations'  => $variations,
				'options'     => $options,
			)
		);
	}
}
