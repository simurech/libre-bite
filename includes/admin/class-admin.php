<?php
/**
 * Admin-Bereich
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin-Klasse
 */
class LB_Admin {

	/**
	 * Loader-Instanz
	 *
	 * @var LB_Loader
	 */
	private $loader;

	/**
	 * Admin-Einstellungen-Instanz
	 *
	 * @var LB_Admin_Settings
	 */
	private $admin_settings;

	/**
	 * Konstruktor
	 *
	 * @param LB_Loader $loader Loader-Instanz
	 */
	public function __construct( $loader ) {
		$this->loader = $loader;
		$this->init_roles();
		$this->init_admin_settings();
		$this->init_hooks();
	}

	/**
	 * Rollen-Klasse laden
	 */
	private function init_roles() {
		require_once LB_PLUGIN_DIR . 'includes/admin/class-roles.php';
	}

	/**
	 * Admin-Einstellungen initialisieren
	 */
	private function init_admin_settings() {
		require_once LB_PLUGIN_DIR . 'includes/admin/class-admin-settings.php';
		$this->admin_settings = new LB_Admin_Settings( $this->loader );
	}

	/**
	 * Hooks initialisieren
	 */
	private function init_hooks() {
		$this->loader->add_action( 'admin_menu', $this, 'add_admin_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_assets' );
		$this->loader->add_action( 'admin_init', $this, 'maybe_upgrade' );

		// AJAX-Handler.
		$this->loader->add_action( 'wp_ajax_lb_save_pos_location', $this, 'ajax_save_pos_location' );
		$this->loader->add_action( 'wp_ajax_lb_pos_get_products', $this, 'ajax_pos_get_products' );
		$this->loader->add_action( 'wp_ajax_lb_pos_get_product_details', $this, 'ajax_pos_get_product_details' );
		$this->loader->add_action( 'wp_ajax_lb_pos_create_order', $this, 'ajax_pos_create_order' );
		$this->loader->add_action( 'wp_ajax_lb_get_theme_colors', $this, 'ajax_get_theme_colors' );
		$this->loader->add_action( 'wp_ajax_lb_save_features', $this, 'ajax_save_features' );
		$this->loader->add_action( 'wp_ajax_lb_save_support_settings', $this, 'ajax_save_support_settings' );
	}

	/**
	 * Plugin-Upgrade bei Bedarf durchführen
	 */
	public function maybe_upgrade() {
		LB_Installer::maybe_upgrade();
	}

	/**
	 * Admin-Menü hinzufügen
	 *
	 * Menüstruktur nach Benutzerrollen:
	 *
	 * PERSONAL (lb_staff):
	 * - Dashboard, Bestellübersicht, Kassensystem, Hilfe
	 *
	 * ADMIN (lb_admin):
	 * - Alle Personal-Menüs + Standorte, Produkt-Optionen, Checkout-Felder, Einstellungen
	 *
	 * SUPER-ADMIN (administrator):
	 * - Alle Admin-Menüs + Feature-Toggles, Admin-Einstellungen, Support-Einstellungen, Debug
	 */
	public function add_admin_menu() {
		// Prüfen ob Benutzer mindestens Staff-Zugriff hat
		if ( ! LB_Roles::is_staff() ) {
			return;
		}

		// Angepasster Plugin-Name
		$plugin_name = apply_filters( 'lb_plugin_display_name', __( 'Libre Bite', 'libre-bite' ) );
		$menu_name   = apply_filters( 'lb_plugin_menu_name', __( 'Libre Bite', 'libre-bite' ) );

		// Hauptmenü - für alle OOS-Benutzer sichtbar
		add_menu_page(
			$plugin_name,
			$menu_name,
			'lb_view_dashboard',
			'libre-bite',
			array( $this, 'render_dashboard_page' ),
			'dashicons-store',
			56
		);

		// ============================================
		// PERSONAL-BEREICH (lb_staff)
		// ============================================

		// Dashboard
		add_submenu_page(
			'libre-bite',
			__( 'Dashboard', 'libre-bite' ),
			__( 'Dashboard', 'libre-bite' ),
			'lb_view_dashboard',
			'libre-bite',
			array( $this, 'render_dashboard_page' )
		);

		// Bestellübersicht (Kanban)
		add_submenu_page(
			'libre-bite',
			__( 'Bestellübersicht', 'libre-bite' ),
			__( 'Bestellübersicht', 'libre-bite' ),
			'lb_view_orders',
			'lb-order-board',
			array( $this, 'render_order_board_page' )
		);

		// POS/Kassensystem
		add_submenu_page(
			'libre-bite',
			__( 'Kassensystem', 'libre-bite' ),
			__( 'Kassensystem', 'libre-bite' ),
			'lb_use_pos',
			'lb-pos',
			array( $this, 'render_pos_page' )
		);

		// ============================================
		// ADMIN-BEREICH (lb_admin)
		// ============================================

		// Standorte (CPT)
		add_submenu_page(
			'libre-bite',
			__( 'Standorte', 'libre-bite' ),
			__( 'Standorte', 'libre-bite' ),
			'lb_manage_locations',
			'edit.php?post_type=lb_location'
		);

		// Produkt-Optionen (CPT)
		add_submenu_page(
			'libre-bite',
			__( 'Produkt-Optionen', 'libre-bite' ),
			__( 'Produkt-Optionen', 'libre-bite' ),
			'lb_manage_options',
			'edit.php?post_type=lb_product_option'
		);

		// Checkout-Felder
		add_submenu_page(
			'libre-bite',
			__( 'Checkout-Felder', 'libre-bite' ),
			__( 'Checkout-Felder', 'libre-bite' ),
			'lb_manage_checkout',
			'lb-checkout-fields',
			array( $this, 'render_checkout_fields_page' )
		);

		// Einstellungen
		add_submenu_page(
			'libre-bite',
			__( 'Einstellungen', 'libre-bite' ),
			__( 'Einstellungen', 'libre-bite' ),
			'lb_manage_settings',
			'lb-settings',
			array( $this, 'render_settings_page' )
		);

		// ============================================
		// SUPER-ADMIN-BEREICH (administrator)
		// ============================================

		// Feature-Toggles (Grundkonfiguration)
		add_submenu_page(
			'libre-bite',
			__( 'Feature-Toggles', 'libre-bite' ),
			__( 'Feature-Toggles', 'libre-bite' ),
			'lb_manage_features',
			'lb-features',
			array( $this, 'render_features_page' )
		);

		// Admin-Einstellungen (Rollen, Menüs)
		add_submenu_page(
			'libre-bite',
			__( 'Admin-Einstellungen', 'libre-bite' ),
			__( 'Admin-Einstellungen', 'libre-bite' ),
			'lb_manage_roles',
			'lb-admin-settings',
			array( $this, 'render_admin_settings_page' )
		);

		// Support-Einstellungen
		add_submenu_page(
			'libre-bite',
			__( 'Support-Einstellungen', 'libre-bite' ),
			__( 'Support-Einstellungen', 'libre-bite' ),
			'lb_manage_support',
			'lb-support-settings',
			array( $this, 'render_support_settings_page' )
		);

		// ============================================
		// DOKUMENTATION (rollenbasiert)
		// ============================================

		// Hilfe & Support - für alle sichtbar, Inhalt variiert nach Rolle
		add_submenu_page(
			'libre-bite',
			__( 'Hilfe & Support', 'libre-bite' ),
			__( 'Hilfe & Support', 'libre-bite' ),
			'lb_view_dashboard',
			'lb-help',
			array( $this, 'render_help_page' )
		);

		// Debug (nur wenn WP_DEBUG aktiv und Super-Admin)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			add_submenu_page(
				'libre-bite',
				__( 'Debug-Info', 'libre-bite' ),
				__( 'Debug-Info', 'libre-bite' ),
				'lb_view_debug',
				'lb-debug-info',
				array( $this, 'render_debug_page' )
			);
		}
	}

	/**
	 * Dashboard-Seite rendern
	 */
	public function render_dashboard_page() {
		include LB_PLUGIN_DIR . 'templates/admin/dashboard.php';
	}

	/**
	 * Bestellübersicht-Seite rendern
	 */
	public function render_order_board_page() {
		include LB_PLUGIN_DIR . 'templates/admin/order-board.php';
	}

	/**
	 * POS-Seite rendern
	 */
	public function render_pos_page() {
		include LB_PLUGIN_DIR . 'templates/admin/pos.php';
	}

	/**
	 * Checkout-Felder-Seite rendern
	 */
	public function render_checkout_fields_page() {
		include LB_PLUGIN_DIR . 'templates/admin/checkout-fields.php';
	}

	/**
	 * Einstellungen-Seite rendern
	 */
	public function render_settings_page() {
		include LB_PLUGIN_DIR . 'templates/admin/settings.php';
	}

	/**
	 * Admin-Einstellungen-Seite rendern
	 */
	public function render_admin_settings_page() {
		include LB_PLUGIN_DIR . 'templates/admin/admin-settings.php';
	}

	/**
	 * Feature-Toggles-Seite rendern
	 */
	public function render_features_page() {
		if ( ! current_user_can( 'lb_manage_features' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'libre-bite' ) );
		}
		include LB_PLUGIN_DIR . 'templates/admin/super-admin-settings.php';
	}

	/**
	 * Support-Einstellungen-Seite rendern
	 */
	public function render_support_settings_page() {
		if ( ! current_user_can( 'lb_manage_support' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'libre-bite' ) );
		}
		include LB_PLUGIN_DIR . 'templates/admin/support-settings.php';
	}

	/**
	 * Hilfe-Seite rendern (rollenbasiert)
	 */
	public function render_help_page() {
		$user_level = LB_Roles::get_user_level();

		switch ( $user_level ) {
			case 'super_admin':
				include LB_PLUGIN_DIR . 'templates/admin/help-superadmin.php';
				break;
			case 'admin':
				include LB_PLUGIN_DIR . 'templates/admin/help-admin.php';
				break;
			case 'staff':
			default:
				include LB_PLUGIN_DIR . 'templates/admin/help-staff.php';
				break;
		}
	}

	/**
	 * Debug-Seite rendern
	 */
	public function render_debug_page() {
		if ( ! current_user_can( 'lb_view_debug' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'libre-bite' ) );
		}
		include LB_PLUGIN_DIR . 'templates/admin/debug-info.php';
	}

	/**
	 * Admin-Assets laden
	 *
	 * @param string $hook Aktuelle Admin-Seite
	 */
	public function enqueue_admin_assets( $hook ) {
		// Nur auf Plugin-Seiten laden
		if ( empty( $hook ) || ( strpos( $hook, 'libre-bite' ) === false && strpos( $hook, 'lb-' ) === false ) ) {
			return;
		}

		// CSS
		wp_enqueue_style(
			'lb-admin',
			LB_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			LB_VERSION
		);

		// JS
		wp_enqueue_script(
			'lb-admin',
			LB_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			LB_VERSION,
			true
		);

		// Lokalisierte Daten
		wp_localize_script(
			'lb-admin',
			'lbAdmin',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'lb_admin_nonce' ),
				'strings'      => array(
					'confirmDelete' => __( 'Wirklich löschen?', 'libre-bite' ),
					'saveSuccess'   => __( 'Erfolgreich gespeichert', 'libre-bite' ),
					'saveError'     => __( 'Fehler beim Speichern', 'libre-bite' ),
				),
			)
		);

		// POS-Assets werden komplett in class-pos.php geladen (inkl. preloadData).
	}

	/**
	 * AJAX: POS-Standort speichern
	 */
	public function ajax_save_pos_location() {
		check_ajax_referer( 'lb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$location_id = isset( $_POST['location_id'] ) ? intval( $_POST['location_id'] ) : 0;

		// Standort für aktuellen Benutzer speichern
		update_user_meta( get_current_user_id(), 'lb_pos_location', $location_id );

		wp_send_json_success( array( 'location_id' => $location_id ) );
	}

	/**
	 * AJAX: POS-Produkte laden (mit Caching)
	 */
	public function ajax_pos_get_products() {
		check_ajax_referer( 'lb_pos_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$category_id = isset( $_POST['category_id'] ) ? intval( $_POST['category_id'] ) : 0;

		// Transient-Cache prüfen (5 Minuten).
		$cache_key    = 'lb_pos_products_' . $category_id;
		$product_data = get_transient( $cache_key );

		if ( false === $product_data ) {
			// Nicht im Cache - Daten laden.
			$args = array(
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			);

			// Nach Kategorie filtern.
			if ( $category_id > 0 ) {
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $category_id,
					),
				);
			}

			$products     = get_posts( $args );
			$product_data = array();

			foreach ( $products as $product_post ) {
				$product = wc_get_product( $product_post->ID );

				if ( ! $product ) {
					continue;
				}

				// Prüfen ob Produkt Varianten oder Optionen hat.
				$has_variations  = $product->is_type( 'variable' );
				$product_options = get_post_meta( $product->get_id(), '_lb_product_options', true );
				$has_options     = ! empty( $product_options );

				$product_data[] = array(
					'id'             => $product->get_id(),
					'name'           => $product->get_name(),
					'price'          => $product->get_price(),
					'image'          => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
					'has_variations' => $has_variations,
					'has_options'    => $has_options,
					'type'           => $product->get_type(),
				);
			}

			// Im Cache speichern (5 Minuten).
			set_transient( $cache_key, $product_data, 5 * MINUTE_IN_SECONDS );
		}

		wp_send_json_success( array( 'products' => $product_data ) );
	}

	/**
	 * AJAX: POS-Produkt-Details laden (Varianten & Optionen)
	 */
	public function ajax_pos_get_product_details() {
		check_ajax_referer( 'lb_pos_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Keine Produkt-ID angegeben', 'libre-bite' ) ) );
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Produkt nicht gefunden', 'libre-bite' ) ) );
		}

		try {

		$response = array(
			'id'         => $product->get_id(),
			'name'       => $product->get_name(),
			'type'       => $product->get_type(),
			'price'      => $product->get_price(),
			'variations' => array(),
			'options'    => array(),
		);

		// Varianten laden (für variable Produkte)
		if ( $product->is_type( 'variable' ) ) {
			$variations = $product->get_available_variations();
			foreach ( $variations as $variation ) {
				$variation_obj = wc_get_product( $variation['variation_id'] );
				if ( ! $variation_obj ) {
					continue;
				}

				// Attribute formatieren
				$attr_labels = array();
				if ( ! empty( $variation['attributes'] ) && is_array( $variation['attributes'] ) ) {
					foreach ( $variation['attributes'] as $attr_key => $attr_value ) {
						// Attribut-Label und Wert extrahieren
						$attr_name = str_replace( 'attribute_', '', $attr_key );
						$attr_labels[] = ucfirst( $attr_name ) . ': ' . $attr_value;
					}
				}

				$response['variations'][] = array(
					'id'         => $variation['variation_id'],
					'attributes' => $variation['attributes'],
					'price'      => $variation_obj->get_price(),
					'name'       => ! empty( $attr_labels ) ? implode( ', ', $attr_labels ) : __( 'Variante', 'libre-bite' ),
				);
			}
		}

		// OOS Produkt-Optionen laden
		$product_options = get_post_meta( $product->get_id(), '_lb_product_options', true );
		if ( ! empty( $product_options ) && is_array( $product_options ) ) {
			foreach ( $product_options as $option_id ) {
				$option_post = get_post( $option_id );
				if ( ! $option_post ) {
					continue;
				}

				// Einfaches Optionssystem (eine Option = eine Checkbox mit Preis)
				$option_price = get_post_meta( $option_id, '_lb_price', true );

				$response['options'][] = array(
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

		wp_send_json_success( $response );

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX: POS-Bestellung erstellen
	 */
	public function ajax_pos_create_order() {
		check_ajax_referer( 'lb_pos_nonce', 'nonce' );

		if ( ! current_user_can( 'lb_use_pos' ) && ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		// Rohes JSON laden und validieren.
		// phpcs:ignore WordPress.Security.NonceVerification -- Nonce wurde bereits oben geprüft.
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
			$price      = isset( $raw_item['price'] ) ? floatval( $raw_item['price'] ) : 0.0;
			$meta       = isset( $raw_item['meta'] ) ? sanitize_text_field( $raw_item['meta'] ) : '';

			if ( ! $product_id || $quantity <= 0 ) {
				continue;
			}

			$cart_items[] = array(
				'id'       => $product_id,
				'quantity' => $quantity,
				'price'    => $price,
				'meta'     => $meta,
			);
		}

		if ( empty( $cart_items ) ) {
			wp_send_json_error( array( 'message' => __( 'Warenkorb ist leer', 'libre-bite' ) ) );
		}

		$location_id   = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;
		$order_type    = isset( $_POST['order_type'] ) ? sanitize_text_field( wp_unslash( $_POST['order_type'] ) ) : 'now';
		$pickup_time   = isset( $_POST['pickup_time'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_time'] ) ) : '';
		$customer_name = isset( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : '';

		if ( ! $location_id ) {
			wp_send_json_error( array( 'message' => __( 'Kein Standort ausgewählt', 'libre-bite' ) ) );
		}

		try {
			// WooCommerce-Bestellung erstellen.
			$order = wc_create_order();

			// Produkte hinzufügen.
			foreach ( $cart_items as $item ) {
				$product = wc_get_product( $item['id'] );
				if ( ! $product ) {
					continue;
				}

				$order_item_id = $order->add_product(
					$product,
					$item['quantity'],
					array(
						'subtotal' => $item['price'] * $item['quantity'],
						'total'    => $item['price'] * $item['quantity'],
					)
				);

				// Meta-Daten (Varianten & Optionen) hinzufügen.
				if ( ! empty( $item['meta'] ) && $order_item_id ) {
					$order_item = $order->get_item( $order_item_id );
					if ( $order_item ) {
						$order_item->add_meta_data( 'Konfiguration', $item['meta'], true );
						$order_item->save();
					}
				}
			}

			// Bestellmeta setzen.
			$order->update_meta_data( '_lb_location_id', $location_id );

			// Standort-Name speichern.
			$location = get_post( $location_id );
			if ( $location ) {
				$order->update_meta_data( '_lb_location_name', $location->post_title );
			}

			$order->update_meta_data( '_lb_order_type', $order_type );
			if ( $pickup_time ) {
				$order->update_meta_data( '_lb_pickup_time', $pickup_time );
			}
			$order->update_meta_data( '_lb_order_source', 'pos' );

			// Kundenname speichern (falls angegeben).
			if ( ! empty( $customer_name ) ) {
				$order->set_billing_first_name( $customer_name );
				$order->update_meta_data( '_lb_customer_name', $customer_name );
			}

			// Berechnen.
			$order->calculate_totals();

			// Status setzen.
			$order->update_status( 'processing', __( 'Bestellung über Kassensystem erstellt.', 'libre-bite' ) );

			// Währungssymbol dekodieren (z.B. &#67;&#72;&#70; -> CHF).
			$currency = html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' );

			wp_send_json_success(
				array(
					'order_id'     => $order->get_id(),
					'order_number' => $order->get_order_number(),
					'total'        => $currency . ' ' . number_format( $order->get_total(), 2, '.', "'" ),
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX: Theme-Farben abrufen
	 */
	public function ajax_get_theme_colors() {
		check_ajax_referer( 'lb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$colors = array(
			'primary'   => '',
			'secondary' => '',
			'accent'    => '',
		);

		// Versuche Block-Theme Farben zu lesen (WordPress 5.9+).
		if ( function_exists( 'wp_get_global_settings' ) ) {
			$global_settings = wp_get_global_settings();

			if ( ! empty( $global_settings['color']['palette']['theme'] ) ) {
				$palette = $global_settings['color']['palette']['theme'];

				foreach ( $palette as $color ) {
					$slug = strtolower( $color['slug'] );
					$hex  = $color['color'];

					// Versuche Farben anhand von Slug-Namen zuzuordnen.
					if ( strpos( $slug, 'primary' ) !== false || strpos( $slug, 'brand' ) !== false ) {
						if ( empty( $colors['primary'] ) ) {
							$colors['primary'] = $hex;
						}
					} elseif ( strpos( $slug, 'secondary' ) !== false ) {
						if ( empty( $colors['secondary'] ) ) {
							$colors['secondary'] = $hex;
						}
					} elseif ( strpos( $slug, 'accent' ) !== false || strpos( $slug, 'tertiary' ) !== false ) {
						if ( empty( $colors['accent'] ) ) {
							$colors['accent'] = $hex;
						}
					}
				}

				// Fallback: Nimm erste 3 Farben falls keine passenden Namen gefunden.
				if ( empty( $colors['primary'] ) && isset( $palette[0] ) ) {
					$colors['primary'] = $palette[0]['color'];
				}
				if ( empty( $colors['secondary'] ) && isset( $palette[1] ) ) {
					$colors['secondary'] = $palette[1]['color'];
				}
				if ( empty( $colors['accent'] ) && isset( $palette[2] ) ) {
					$colors['accent'] = $palette[2]['color'];
				}
			}
		}

		// Fallback: Klassisches Theme mit Customizer.
		if ( empty( $colors['primary'] ) ) {
			// Versuche gängige Theme-Mods zu lesen.
			$customizer_options = array(
				'primary'   => array( 'primary_color', 'brand_color', 'link_color', 'accent_color' ),
				'secondary' => array( 'secondary_color', 'heading_color' ),
				'accent'    => array( 'accent_color', 'highlight_color', 'button_color' ),
			);

			foreach ( $customizer_options as $key => $options ) {
				foreach ( $options as $option ) {
					$value = get_theme_mod( $option, '' );
					if ( ! empty( $value ) && empty( $colors[ $key ] ) ) {
						$colors[ $key ] = $value;
						break;
					}
				}
			}
		}

		// Letzte Fallback: Standard-Farben.
		if ( empty( $colors['primary'] ) ) {
			$colors['primary'] = '#2271b1';
		}
		if ( empty( $colors['secondary'] ) ) {
			$colors['secondary'] = '#1d2327';
		}
		if ( empty( $colors['accent'] ) ) {
			$colors['accent'] = '#135e96';
		}

		wp_send_json_success( $colors );
	}

	/**
	 * AJAX: Feature-Toggles speichern
	 */
	public function ajax_save_features() {
		check_ajax_referer( 'lb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'lb_manage_features' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$features_json = isset( $_POST['features'] ) ? wp_unslash( $_POST['features'] ) : '';
		$features      = json_decode( $features_json, true );

		if ( ! is_array( $features ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungültige Daten', 'libre-bite' ) ) );
		}

		// Alle Feature-Werte als boolean konvertieren
		$sanitized_features = array();
		foreach ( $features as $key => $value ) {
			$sanitized_features[ sanitize_key( $key ) ] = (bool) $value;
		}

		update_option( 'lb_features', $sanitized_features );

		wp_send_json_success( array( 'message' => __( 'Einstellungen gespeichert', 'libre-bite' ) ) );
	}

	/**
	 * AJAX: Support-Einstellungen speichern
	 */
	public function ajax_save_support_settings() {
		check_ajax_referer( 'lb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'lb_manage_support' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$settings = array(
			'support_email'        => isset( $_POST['support_email'] ) ? sanitize_email( wp_unslash( $_POST['support_email'] ) ) : '',
			'support_phone'        => isset( $_POST['support_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['support_phone'] ) ) : '',
			'support_hours'        => isset( $_POST['support_hours'] ) ? sanitize_text_field( wp_unslash( $_POST['support_hours'] ) ) : '',
			'support_billing_note' => isset( $_POST['support_billing_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['support_billing_note'] ) ) : '',
			'support_custom_text'  => isset( $_POST['support_custom_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['support_custom_text'] ) ) : '',
		);

		update_option( 'lb_support_settings', $settings );

		wp_send_json_success( array( 'message' => __( 'Support-Einstellungen gespeichert', 'libre-bite' ) ) );
	}
}
