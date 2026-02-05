<?php
/**
 * POS / Kassensystem
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * POS-Modul
 */
class LB_POS {

	/**
	 * Loader-Instanz
	 *
	 * @var LB_Loader
	 */
	private $loader;

	/**
	 * Konstruktor
	 *
	 * @param LB_Loader $loader Loader-Instanz
	 */
	public function __construct( $loader ) {
		$this->loader = $loader;
		$this->init_hooks();
	}

	/**
	 * Hooks initialisieren
	 */
	private function init_hooks() {
		// Admin-Assets
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_pos_assets' );

		// AJAX-Endpoints (Haupthandler sind in class-admin.php definiert)
		// Diese bleiben als Fallback erhalten, werden aber normalerweise nicht verwendet.
		$this->loader->add_action( 'wp_ajax_lb_pos_get_products', $this, 'ajax_get_products' );
	}

	/**
	 * POS-Assets laden
	 *
	 * @param string $hook Aktuelle Admin-Seite
	 */
	public function enqueue_pos_assets( $hook ) {
		// Null-Check für $hook.
		if ( empty( $hook ) ) {
			return;
		}

		// POS-Seite erkennen (verschiedene Hook-Formate möglich).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only checking page parameter for asset loading.
		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$is_pos_page  = ( 'libre-bite_page_lb-pos' === $hook )
			|| ( strpos( (string) $hook, '_page_oos-pos' ) !== false )
			|| ( strpos( (string) $hook, '_page_lb-pos' ) !== false )
			|| ( 'lb-pos' === $current_page );

		if ( ! $is_pos_page ) {
			return;
		}

		// POS-spezifisches JS.
		wp_enqueue_script(
			'lb-pos',
			LB_PLUGIN_URL . 'assets/js/pos.js',
			array( 'jquery' ),
			LB_VERSION,
			true
		);

		// Produktdaten direkt einbetten (kein zusätzlicher HTTP-Request nötig).
		$product_data = $this->get_pos_product_data();

		// Lokalisierte Daten.
		wp_localize_script(
			'lb-pos',
			'lbPos',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'lb_pos_nonce' ),
				'currency'     => get_woocommerce_currency_symbol(),
				'preloadData'  => $product_data,
				'strings'      => array(
					'addToCart'      => __( 'Zum Warenkorb', 'libre-bite' ),
					'removeFromCart' => __( 'Entfernen', 'libre-bite' ),
					'orderCreated'   => __( 'Bestellung erstellt', 'libre-bite' ),
					'orderError'     => __( 'Fehler beim Erstellen der Bestellung', 'libre-bite' ),
					'cartEmpty'      => __( 'Warenkorb ist leer', 'libre-bite' ),
					'selectLocation' => __( 'Bitte Standort wählen', 'libre-bite' ),
				),
			)
		);
	}

	/**
	 * Produktdaten für POS abrufen
	 *
	 * @return array Produktdaten mit Kategorien, Produkten und Details.
	 */
	private function get_pos_product_data() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array(
				'categories' => array(),
				'products'   => array(),
				'details'    => array(),
			);
		}

		$products = get_posts(
			array(
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$products_data    = array();
		$products_details = array();
		$categories_data  = array();

		// Kategorien sammeln.
		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
			)
		);

		if ( ! is_wp_error( $categories ) ) {
			foreach ( $categories as $cat ) {
				$categories_data[] = array(
					'id'   => $cat->term_id,
					'name' => $cat->name,
					'slug' => $cat->slug,
				);
			}
		}

		foreach ( $products as $product_post ) {
			$product = wc_get_product( $product_post->ID );

			if ( ! $product ) {
				continue;
			}

			$product_id      = $product->get_id();
			$has_variations  = $product->is_type( 'variable' );
			$product_options = get_post_meta( $product_id, '_lb_product_options', true );
			$has_options     = ! empty( $product_options );

			// Kategorien des Produkts.
			$product_cats = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
			if ( is_wp_error( $product_cats ) ) {
				$product_cats = array();
			}

			// Basis-Produktdaten.
			$products_data[] = array(
				'id'             => $product_id,
				'name'           => $product->get_name(),
				'price'          => $product->get_price(),
				'image'          => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
				'has_variations' => $has_variations,
				'has_options'    => $has_options,
				'type'           => $product->get_type(),
				'categories'     => $product_cats,
			);

			// Details nur für Produkte mit Konfiguration.
			if ( $has_variations || $has_options ) {
				$detail = array(
					'id'         => $product_id,
					'name'       => $product->get_name(),
					'type'       => $product->get_type(),
					'price'      => $product->get_price(),
					'variations' => array(),
					'options'    => array(),
				);

				// Varianten.
				if ( $has_variations ) {
					$variations = $product->get_available_variations();
					foreach ( $variations as $variation ) {
						$variation_obj = wc_get_product( $variation['variation_id'] );
						if ( ! $variation_obj ) {
							continue;
						}

						$attr_labels = array();
						if ( ! empty( $variation['attributes'] ) && is_array( $variation['attributes'] ) ) {
							foreach ( $variation['attributes'] as $attr_key => $attr_value ) {
								$attr_name     = str_replace( 'attribute_', '', $attr_key );
								$attr_labels[] = ucfirst( $attr_name ) . ': ' . $attr_value;
							}
						}

						$detail['variations'][] = array(
							'id'         => $variation['variation_id'],
							'attributes' => $variation['attributes'],
							'price'      => $variation_obj->get_price(),
							'name'       => ! empty( $attr_labels ) ? implode( ', ', $attr_labels ) : __( 'Variante', 'libre-bite' ),
						);
					}
				}

				// Optionen.
				if ( $has_options && is_array( $product_options ) ) {
					foreach ( $product_options as $option_id ) {
						$option_post = get_post( $option_id );
						if ( ! $option_post ) {
							continue;
						}

						$option_price        = get_post_meta( $option_id, '_lb_price', true );
						$detail['options'][] = array(
							'id'       => $option_id,
							'name'     => $option_post->post_title,
							'type'     => 'checkbox',
							'required' => false,
							'choices'  => array(
								array(
									'label' => $option_post->post_title,
									'price' => floatval( $option_price ),
								),
							),
						);
					}
				}

				$products_details[ $product_id ] = $detail;
			}
		}

		return array(
			'categories' => $categories_data,
			'products'   => $products_data,
			'details'    => $products_details,
		);
	}

	/**
	 * Produkte laden (AJAX) - Fallback falls preloadData nicht verfügbar
	 */
	public function ajax_get_products() {
		check_ajax_referer( 'lb_pos_nonce', 'nonce' );

		if ( ! current_user_can( 'lb_use_pos' ) && ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$category_id = isset( $_POST['category_id'] ) ? intval( wp_unslash( $_POST['category_id'] ) ) : 0;

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		// Nach Kategorie filtern
		if ( $category_id ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $category_id,
				),
			);
		}

		$products = get_posts( $args );

		$formatted_products = array();

		foreach ( $products as $product_post ) {
			$product = wc_get_product( $product_post->ID );

			if ( ! $product ) {
				continue;
			}

			$formatted_products[] = array(
				'id'    => $product->get_id(),
				'name'  => $product->get_name(),
				'price' => $product->get_price(),
				'image' => get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' ),
			);
		}

		wp_send_json_success( array( 'products' => $formatted_products ) );
	}

	/**
	 * Bestellung erstellen (AJAX)
	 */
	public function ajax_create_order() {
		check_ajax_referer( 'lb_pos_nonce', 'nonce' );

		if ( ! current_user_can( 'lb_use_pos' ) && ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		// Rohes JSON laden und validieren.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON wird nach dem Decode Feld für Feld validiert.
		$cart_items_raw = isset( $_POST['cart_items'] ) ? wp_unslash( $_POST['cart_items'] ) : '';

		if ( empty( $cart_items_raw ) ) {
			wp_send_json_error( array( 'message' => __( 'Warenkorb ist leer', 'libre-bite' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON wird nach dem Decode Feld für Feld validiert.
		$cart_items_decoded = json_decode( $cart_items_raw, true );

		if ( ! is_array( $cart_items_decoded ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungültige Warenkorbdaten', 'libre-bite' ) ) );
		}

		$cart_items = array();

		foreach ( $cart_items_decoded as $raw_item ) {
			if ( ! is_array( $raw_item ) ) {
				continue;
			}

			$product_id = isset( $raw_item['id'] ) ? absint( $raw_item['id'] ) : 0;
			$quantity   = isset( $raw_item['quantity'] ) ? (int) $raw_item['quantity'] : 0;

			if ( ! $product_id || $quantity <= 0 ) {
				continue;
			}

			$cart_items[] = array(
				'id'       => $product_id,
				'quantity' => $quantity,
			);
		}

		if ( empty( $cart_items ) ) {
			wp_send_json_error( array( 'message' => __( 'Warenkorb ist leer', 'libre-bite' ) ) );
		}

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;
		$order_type  = isset( $_POST['order_type'] ) ? sanitize_text_field( wp_unslash( $_POST['order_type'] ) ) : 'now';
		$pickup_time = isset( $_POST['pickup_time'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_time'] ) ) : '';

		if ( ! $location_id ) {
			wp_send_json_error( array( 'message' => __( 'Kein Standort gewählt', 'libre-bite' ) ) );
		}

		try {
			// Bestellung erstellen.
			$order = wc_create_order();

			// Produkte hinzufügen.
			foreach ( $cart_items as $item ) {
				$product = wc_get_product( $item['id'] );
				if ( ! $product ) {
					continue;
				}

				$order->add_product( $product, $item['quantity'] );
			}

			// Meta-Daten speichern.
			$order->update_meta_data( '_lb_location_id', $location_id );
			$order->update_meta_data( '_lb_order_type', $order_type );
			$order->update_meta_data( '_lb_order_status', 'incoming' );
			$order->update_meta_data( '_lb_pos_order', true );

			if ( 'later' === $order_type && $pickup_time ) {
				$order->update_meta_data( '_lb_pickup_time', $pickup_time );
			}

			// Standort-Name speichern.
			$location = get_post( $location_id );
			if ( $location ) {
				$order->update_meta_data( '_lb_location_name', $location->post_title );
			}

			// Gesamt berechnen.
			$order->calculate_totals();

			// Status setzen.
			$order->set_status( 'processing', __( 'POS-Bestellung', 'libre-bite' ) );

			// Speichern.
			$order->save();

			wp_send_json_success(
				array(
					'message'      => __( 'Bestellung erfolgreich erstellt', 'libre-bite' ),
					'order_id'     => $order->get_id(),
					'order_number' => $order->get_order_number(),
					'total'        => $order->get_formatted_order_total(),
				)
			);

		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => __( 'Fehler beim Erstellen der Bestellung: ', 'libre-bite' ) . $e->getMessage(),
				)
			);
		}
	}
}
