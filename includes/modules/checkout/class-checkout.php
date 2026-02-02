<?php
/**
 * Checkout-Modul
 * Verwaltet: Felder-Verwaltung, Standort-/Zeitwahl, Trinkgeld
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checkout-Klasse
 */
class LB_Checkout {

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
		// Checkout-Felder anpassen
		$this->loader->add_filter( 'woocommerce_checkout_fields', $this, 'customize_checkout_fields' );
		$this->loader->add_filter( 'woocommerce_cart_needs_shipping_address', $this, 'maybe_disable_shipping_address' );
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'maybe_hide_additional_info_section' );
		$this->loader->add_filter( 'gettext', $this, 'customize_billing_details_title', 10, 3 );

		// Versand-Informationen ausblenden
		$this->loader->add_filter( 'woocommerce_cart_needs_shipping', $this, 'maybe_hide_shipping' );
		$this->loader->add_filter( 'woocommerce_order_needs_shipping_address', $this, 'maybe_hide_shipping' );
		$this->loader->add_filter( 'woocommerce_cart_ready_to_calc_shipping', $this, 'maybe_hide_shipping_calculator' );
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'maybe_add_hide_shipping_css' );

		// Rundung auf 5 Rappen (Feature-abhängig)
		if ( lb_feature_enabled( 'enable_rounding' ) ) {
			$this->loader->add_action( 'woocommerce_cart_calculate_fees', $this, 'apply_rounding_fee', 999 );
		}

		// Standort- & Zeitwahl (Feature-abhängig)
		if ( lb_feature_enabled( 'enable_location_selector' ) ) {
			$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_frontend_assets' );
			// Modal nur anzeigen wenn explizit aktiviert via Filter
			if ( apply_filters( 'lb_enable_location_modal', false ) ) {
				$this->loader->add_action( 'wp_footer', $this, 'render_location_modal' );
			}
			$this->loader->add_action( 'woocommerce_checkout_before_customer_details', $this, 'render_location_time_selection' );
			$this->loader->add_action( 'woocommerce_checkout_process', $this, 'validate_location_time' );
			$this->loader->add_action( 'woocommerce_checkout_update_order_meta', $this, 'save_location_time_meta' );

			// AJAX-Endpoints
			$this->loader->add_action( 'wp_ajax_lb_set_location', $this, 'ajax_set_location' );
			$this->loader->add_action( 'wp_ajax_nopriv_lb_set_location', $this, 'ajax_set_location' );
			$this->loader->add_action( 'wp_ajax_lb_get_timeslots', $this, 'ajax_get_timeslots' );
			$this->loader->add_action( 'wp_ajax_nopriv_lb_get_timeslots', $this, 'ajax_get_timeslots' );
			$this->loader->add_action( 'wp_ajax_lb_get_opening_days', $this, 'ajax_get_opening_days' );
			$this->loader->add_action( 'wp_ajax_nopriv_lb_get_opening_days', $this, 'ajax_get_opening_days' );
		}

		// Trinkgeld (Feature-abhängig)
		if ( lb_feature_enabled( 'enable_tips' ) ) {
			$this->loader->add_action( 'woocommerce_review_order_before_payment', $this, 'render_tip_selection' );
			$this->loader->add_action( 'woocommerce_cart_calculate_fees', $this, 'add_tip_fee' );
			$this->loader->add_action( 'woocommerce_checkout_update_order_meta', $this, 'save_tip_meta' );
		}

		// Shortcode
		$this->loader->add_action( 'init', $this, 'register_shortcodes' );

		// URL-Parameter verarbeiten
		$this->loader->add_action( 'template_redirect', $this, 'process_url_parameters' );

		// Optimierter Checkout-Modus (Feature-abhängig)
		if ( lb_feature_enabled( 'enable_optimized_checkout' ) ) {
			$this->loader->add_filter( 'wc_get_template', $this, 'maybe_use_optimized_checkout', 10, 2 );
			$this->loader->add_action( 'woocommerce_thankyou', $this, 'render_optimized_thankyou', 1 );
			$this->loader->add_action( 'wp', $this, 'maybe_remove_thankyou_actions' );
			$this->loader->add_filter( 'woocommerce_checkout_fields', $this, 'maybe_make_email_optional', 999 );
			$this->loader->add_action( 'woocommerce_checkout_process', $this, 'maybe_set_placeholder_email', 5 );
		}
	}

	/**
	 * Checkout-Felder anpassen
	 *
	 * @param array $fields Checkout-Felder
	 * @return array
	 */
	public function customize_checkout_fields( $fields ) {
		$custom_fields = get_option( 'lb_checkout_fields', array() );

		if ( empty( $custom_fields ) ) {
			return $fields;
		}

		// Alle Felder durchgehen
		foreach ( array( 'billing', 'shipping' ) as $fieldset ) {
			if ( ! isset( $fields[ $fieldset ] ) ) {
				continue;
			}

			foreach ( $fields[ $fieldset ] as $key => $field ) {
				$field_id = str_replace( $fieldset . '_', '', $key );

				// Prüfen ob Feld deaktiviert ist
				if ( isset( $custom_fields[ $fieldset ][ $field_id ]['enabled'] ) && ! $custom_fields[ $fieldset ][ $field_id ]['enabled'] ) {
					unset( $fields[ $fieldset ][ $key ] );
					continue;
				}

				// Label überschreiben wenn gesetzt
				if ( ! empty( $custom_fields[ $fieldset ][ $field_id ]['label'] ) ) {
					$fields[ $fieldset ][ $key ]['label'] = $custom_fields[ $fieldset ][ $field_id ]['label'];
				}
			}
		}

		// Anmerkungen zur Bestellung anpassen
		$comments_enabled = isset( $custom_fields['_enable_order_comments'] ) ? $custom_fields['_enable_order_comments'] : true;

		if ( ! $comments_enabled ) {
			// Anmerkungen-Feld komplett entfernen
			if ( isset( $fields['order']['order_comments'] ) ) {
				unset( $fields['order']['order_comments'] );
			}
		} else {
			// Label und Platzhalter anpassen wenn gesetzt
			if ( isset( $fields['order']['order_comments'] ) ) {
				if ( ! empty( $custom_fields['_order_comments_label'] ) ) {
					$fields['order']['order_comments']['label'] = $custom_fields['_order_comments_label'];
				}
				if ( ! empty( $custom_fields['_order_comments_placeholder'] ) ) {
					$fields['order']['order_comments']['placeholder'] = $custom_fields['_order_comments_placeholder'];
				}
			}
		}

		return $fields;
	}

	/**
	 * Lieferadresse deaktivieren wenn in Einstellungen ausgeschaltet
	 *
	 * @param bool $needs_shipping Ob Lieferadresse benötigt wird
	 * @return bool
	 */
	public function maybe_disable_shipping_address( $needs_shipping ) {
		$custom_fields = get_option( 'lb_checkout_fields', array() );

		// Wenn die Einstellung nicht gesetzt ist oder false ist, Lieferadresse deaktivieren
		if ( ! isset( $custom_fields['_enable_shipping_address'] ) || ! $custom_fields['_enable_shipping_address'] ) {
			return false;
		}

		return $needs_shipping;
	}

	/**
	 * "Zusätzliche Informationen" Abschnitt ausblenden wenn keine Felder aktiv
	 */
	public function maybe_hide_additional_info_section() {
		if ( ! is_checkout() ) {
			return;
		}

		$custom_fields = get_option( 'lb_checkout_fields', array() );

		// Prüfen ob "Anmerkungen zur Bestellung" deaktiviert ist
		$comments_enabled = isset( $custom_fields['_enable_order_comments'] ) ? $custom_fields['_enable_order_comments'] : true;

		// Wenn keine Felder im "Zusätzliche Informationen" Abschnitt aktiv sind, verstecken
		if ( ! $comments_enabled ) {
			wp_add_inline_style( 'woocommerce-general', '
				/* "Zusätzliche Informationen" Abschnitt ausblenden wenn leer */
				.woocommerce-additional-fields {
					display: none !important;
				}
			' );
		}
	}

	/**
	 * Titel "Rechnungsdetails" überschreiben
	 *
	 * @param string $translated Übersetzter Text
	 * @param string $text Original-Text
	 * @param string $domain Text-Domain
	 * @return string
	 */
	public function customize_billing_details_title( $translated, $text, $domain ) {
		// Nur im Checkout-Kontext und für WooCommerce-Texte
		if ( ! is_checkout() || 'woocommerce' !== $domain ) {
			return $translated;
		}

		// Nur den spezifischen String "Rechnungsdetails" überschreiben
		if ( 'Rechnungsdetails' === $text || 'Billing details' === $text ) {
			$custom_fields = get_option( 'lb_checkout_fields', array() );
			$custom_title = isset( $custom_fields['_billing_details_title'] ) ? $custom_fields['_billing_details_title'] : '';

			if ( ! empty( $custom_title ) ) {
				return $custom_title;
			}
		}

		return $translated;
	}

	/**
	 * Versand komplett ausblenden wenn in Einstellungen deaktiviert
	 *
	 * @param bool $needs_shipping Ob Versand benötigt wird
	 * @return bool
	 */
	public function maybe_hide_shipping( $needs_shipping ) {
		$custom_fields = get_option( 'lb_checkout_fields', array() );

		// Wenn Versand-Anzeigen NICHT aktiviert ist, Versand ausblenden
		if ( ! isset( $custom_fields['_show_shipping_info'] ) || ! $custom_fields['_show_shipping_info'] ) {
			return false;
		}

		return $needs_shipping;
	}

	/**
	 * Versandrechner ausblenden wenn in Einstellungen deaktiviert
	 *
	 * @param bool $show_calculator Ob Versandrechner angezeigt wird
	 * @return bool
	 */
	public function maybe_hide_shipping_calculator( $show_calculator ) {
		$custom_fields = get_option( 'lb_checkout_fields', array() );

		// Wenn Versand-Anzeigen NICHT aktiviert ist, Versandrechner ausblenden
		if ( ! isset( $custom_fields['_show_shipping_info'] ) || ! $custom_fields['_show_shipping_info'] ) {
			return false;
		}

		return $show_calculator;
	}

	/**
	 * CSS hinzufügen um Versand-Elemente auszublenden
	 */
	public function maybe_add_hide_shipping_css() {
		$custom_fields = get_option( 'lb_checkout_fields', array() );

		// Wenn Versand-Anzeigen NICHT aktiviert ist, CSS zum Ausblenden hinzufügen
		if ( ! isset( $custom_fields['_show_shipping_info'] ) || ! $custom_fields['_show_shipping_info'] ) {
			// CSS inline hinzufügen um alle Versand-Elemente zu verstecken
			wp_add_inline_style( 'woocommerce-general', '
				/* Versand-Informationen ausblenden */
				.woocommerce-shipping-totals,
				.shipping,
				tr.woocommerce-shipping-totals,
				.woocommerce-shipping-calculator,
				.shipping-calculator-button,
				.shipping-calculator-form,
				#shipping_method,
				.woocommerce-shipping-methods,
				tr.shipping,
				td.shipping,
				th.shipping {
					display: none !important;
				}
			' );
		}
	}

	/**
	 * Frontend-Assets laden
	 */
	public function enqueue_frontend_assets() {
		if ( ! is_shop() && ! is_product() && ! is_cart() && ! is_checkout() ) {
			return;
		}

		wp_enqueue_style(
			'lb-frontend',
			LB_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			LB_VERSION
		);

		// Optimiertes Checkout CSS laden wenn Feature aktiviert
		if ( is_checkout() && lb_feature_enabled( 'enable_optimized_checkout' ) ) {
			wp_enqueue_style(
				'lb-checkout-optimized',
				LB_PLUGIN_URL . 'assets/css/checkout-optimized.css',
				array( 'lb-frontend' ),
				LB_VERSION
			);

			wp_enqueue_style(
				'lb-thankyou-optimized',
				LB_PLUGIN_URL . 'assets/css/thankyou-optimized.css',
				array( 'lb-frontend' ),
				LB_VERSION
			);
		}

		// Branding CSS Custom Properties hinzufügen.
		$color_primary   = get_option( 'lb_color_primary', '#0073aa' );
		$color_secondary = get_option( 'lb_color_secondary', '#23282d' );
		$color_accent    = get_option( 'lb_color_accent', '#00a32a' );

		$custom_css = sprintf(
			':root {
				--lb-color-primary: %s;
				--lb-color-secondary: %s;
				--lb-color-accent: %s;
				--lb-color-primary-hover: %s;
				--lb-color-accent-hover: %s;
			}',
			esc_attr( $color_primary ),
			esc_attr( $color_secondary ),
			esc_attr( $color_accent ),
			esc_attr( $this->adjust_brightness( $color_primary, -20 ) ),
			esc_attr( $this->adjust_brightness( $color_accent, -20 ) )
		);

		wp_add_inline_style( 'lb-frontend', $custom_css );

		wp_enqueue_script(
			'lb-frontend',
			LB_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			LB_VERSION,
			true
		);

		wp_localize_script(
			'lb-frontend',
			'lbData',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'lb_frontend_nonce' ),
				'strings'       => array(
					'selectLocation' => __( 'Bitte wählen Sie einen Standort', 'libre-bite' ),
					'selectTime'     => __( 'Bitte wählen Sie eine Abholzeit', 'libre-bite' ),
				),
				'hasLocation'   => ( WC()->session ? ! empty( WC()->session->get( 'lb_location_id' ) ) : false ),
				'locationId'    => ( WC()->session ? WC()->session->get( 'lb_location_id' ) : null ),
				'orderType'     => ( WC()->session ? WC()->session->get( 'lb_order_type', 'now' ) : 'now' ),
			)
		);
	}

	/**
	 * Shortcodes registrieren
	 */
	public function register_shortcodes() {
		add_shortcode( 'lb_location_selector', array( $this, 'shortcode_location_selector' ) );
	}

	/**
	 * URL-Parameter verarbeiten
	 */
	public function process_url_parameters() {
		if ( ! WC()->session ) {
			return;
		}

		// Standort via URL-Parameter setzen (?location=123)
		if ( isset( $_GET['location'] ) ) {
			$location_id = intval( $_GET['location'] );
			$location = get_post( $location_id );

			if ( $location && $location->post_type === 'lb_location' && $location->post_status === 'publish' ) {
				WC()->session->set( 'lb_location_id', $location_id );
			}
		}

		// Bestelltyp via URL-Parameter setzen (?order_type=now oder ?order_type=later).
		// phpcs:ignore WordPress.Security.NonceVerification -- No nonce needed for GET parameter reading.
		if ( isset( $_GET['order_type'] ) && in_array( sanitize_text_field( wp_unslash( $_GET['order_type'] ) ), array( 'now', 'later' ), true ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification -- No nonce needed for GET parameter reading.
			WC()->session->set( 'lb_order_type', sanitize_text_field( wp_unslash( $_GET['order_type'] ) ) );
		}
	}

	/**
	 * Shortcode: Standort-Auswahl
	 */
	public function shortcode_location_selector( $atts ) {
		// CSS laden
		if ( ! wp_style_is( 'lb-frontend', 'enqueued' ) ) {
			wp_enqueue_style(
				'lb-frontend',
				LB_PLUGIN_URL . 'assets/css/frontend.css',
				array(),
				LB_VERSION
			);
		}

		// JavaScript laden
		if ( ! wp_script_is( 'lb-frontend', 'enqueued' ) ) {
			wp_enqueue_script(
				'lb-frontend',
				LB_PLUGIN_URL . 'assets/js/frontend.js',
				array( 'jquery' ),
				LB_VERSION,
				true
			);

			// Lokalisierte Daten hinzufügen (wichtig für AJAX!)
			wp_localize_script(
				'lb-frontend',
				'lbData',
				array(
					'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
					'nonce'         => wp_create_nonce( 'lb_frontend_nonce' ),
					'strings'       => array(
						'selectLocation' => __( 'Bitte wählen Sie einen Standort', 'libre-bite' ),
						'selectTime'     => __( 'Bitte wählen Sie eine Abholzeit', 'libre-bite' ),
					),
					'hasLocation'   => ( WC()->session ? ! empty( WC()->session->get( 'lb_location_id' ) ) : false ),
					'locationId'    => ( WC()->session ? WC()->session->get( 'lb_location_id' ) : null ),
					'orderType'     => ( WC()->session ? WC()->session->get( 'lb_order_type', 'now' ) : 'now' ),
				)
			);
		}

		$atts = shortcode_atts(
			array(
				'show_time' => 'yes',
				'style'     => 'tiles', // 'tiles' oder 'inline'
			),
			$atts,
			'lb_location_selector'
		);

		$locations = LB_Locations::get_all_locations();

		if ( empty( $locations ) ) {
			return '<p>' . esc_html__( 'Keine Standorte verfügbar.', 'libre-bite' ) . '</p>';
		}

		$location_id = WC()->session ? WC()->session->get( 'lb_location_id' ) : null;
		$order_type = WC()->session ? WC()->session->get( 'lb_order_type', 'now' ) : 'now';
		$pickup_time = WC()->session ? WC()->session->get( 'lb_pickup_time' ) : null;

		ob_start();

		// Neues Kachel-Layout verwenden (Standard)
		if ( 'inline' === $atts['style'] ) {
			include LB_PLUGIN_DIR . 'templates/location-selector-inline.php';
		} else {
			include LB_PLUGIN_DIR . 'templates/location-selector-tiles.php';
		}

		return ob_get_clean();
	}

	/**
	 * Standort-Modal rendern
	 *
	 * Hinweis: Diese Funktion wird nur aufgerufen, wenn das Modal explizit
	 * via Filter aktiviert wurde: add_filter('lb_enable_location_modal', '__return_true')
	 */
	public function render_location_modal() {
		// Nur auf bestimmten Seiten anzeigen
		if ( ! is_shop() && ! is_product() && ! is_front_page() ) {
			return;
		}

		// Nur anzeigen wenn noch kein Standort gewählt wurde
		if ( WC()->session && WC()->session->get( 'lb_location_id' ) ) {
			return;
		}

		$locations = LB_Locations::get_all_locations();

		if ( empty( $locations ) ) {
			return;
		}

		include LB_PLUGIN_DIR . 'templates/location-modal.php';
	}

	/**
	 * Standort- & Zeitwahl im Checkout anzeigen
	 */
	public function render_location_time_selection() {
		$location_id = WC()->session ? WC()->session->get( 'lb_location_id' ) : null;
		$order_type  = WC()->session ? WC()->session->get( 'lb_order_type', 'now' ) : 'now';
		$pickup_time = WC()->session ? WC()->session->get( 'lb_pickup_time' ) : null;

		$locations = LB_Locations::get_all_locations();

		include LB_PLUGIN_DIR . 'templates/checkout-location-time.php';
	}

	/**
	 * Standort & Zeit validieren
	 */
	public function validate_location_time() {
		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification for checkout.
		$location_id = isset( $_POST['lb_location_id'] ) ? intval( wp_unslash( $_POST['lb_location_id'] ) ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification for checkout.
		$order_type  = isset( $_POST['lb_order_type'] ) ? sanitize_text_field( wp_unslash( $_POST['lb_order_type'] ) ) : '';

		if ( ! $location_id ) {
			wc_add_notice( __( 'Bitte wählen Sie einen Standort.', 'libre-bite' ), 'error' );
		}

		if ( ! in_array( $order_type, array( 'now', 'later' ), true ) ) {
			wc_add_notice( __( 'Bitte wählen Sie eine Bestellart.', 'libre-bite' ), 'error' );
		}

		if ( 'later' === $order_type ) {
			// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification for checkout.
			$pickup_time = isset( $_POST['lb_pickup_time'] ) ? sanitize_text_field( wp_unslash( $_POST['lb_pickup_time'] ) ) : '';
			if ( ! $pickup_time ) {
				wc_add_notice( __( 'Bitte wählen Sie eine Abholzeit.', 'libre-bite' ), 'error' );
			}
		}
	}

	/**
	 * Standort & Zeit in Bestellung speichern
	 *
	 * @param int $order_id Bestellungs-ID
	 */
	public function save_location_time_meta( $order_id ) {
		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification for checkout.
		$location_id = isset( $_POST['lb_location_id'] ) ? intval( wp_unslash( $_POST['lb_location_id'] ) ) : 0;

		// Fallback: Session verwenden wenn POST leer.
		if ( ! $location_id && WC()->session ) {
			$location_id = WC()->session->get( 'lb_location_id' );
		}

		if ( $location_id ) {
			update_post_meta( $order_id, '_lb_location_id', $location_id );

			// Standort-Name speichern.
			$location = get_post( $location_id );
			if ( $location ) {
				update_post_meta( $order_id, '_lb_location_name', $location->post_title );
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification for checkout.
		$order_type = isset( $_POST['lb_order_type'] ) ? sanitize_text_field( wp_unslash( $_POST['lb_order_type'] ) ) : '';

		// Fallback: Session verwenden wenn POST leer.
		if ( ! $order_type && WC()->session ) {
			$order_type = WC()->session->get( 'lb_order_type', 'now' );
		}

		if ( $order_type ) {
			update_post_meta( $order_id, '_lb_order_type', $order_type );

			// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification for checkout.
			$pickup_time = isset( $_POST['lb_pickup_time'] ) ? sanitize_text_field( wp_unslash( $_POST['lb_pickup_time'] ) ) : '';

			// Fallback: Session verwenden.
			if ( ! $pickup_time && WC()->session ) {
				$pickup_time = WC()->session->get( 'lb_pickup_time' );
			}

			if ( 'later' === $order_type && $pickup_time ) {
				update_post_meta( $order_id, '_lb_pickup_time', $pickup_time );
			}
		}
	}

	/**
	 * Trinkgeld-Auswahl rendern
	 */
	public function render_tip_selection() {
		// Prüfen ob Trinkgeld-Feature aktiviert ist (standardmäßig aktiviert)
		$custom_fields = get_option( 'lb_checkout_fields', array() );
		$tip_enabled = isset( $custom_fields['_enable_tip_selection'] ) ? $custom_fields['_enable_tip_selection'] : true;

		if ( ! $tip_enabled ) {
			return;
		}

		$percentage_1 = get_option( 'lb_tip_percentage_1', 5 );
		$percentage_2 = get_option( 'lb_tip_percentage_2', 10 );
		$percentage_3 = get_option( 'lb_tip_percentage_3', 15 );
		$default_selection = get_option( 'lb_tip_default_selection', 'none' );

		include LB_PLUGIN_DIR . 'templates/checkout-tip.php';
	}

	/**
	 * Trinkgeld als Fee hinzufügen
	 */
	public function add_tip_fee() {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification for checkout.
		if ( ! isset( $_POST['post_data'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification for checkout.
		$post_data_string = sanitize_text_field( wp_unslash( $_POST['post_data'] ) );

		// Sicherere Alternative zu parse_str(): wp_parse_args() verwenden
		$post_data = array();
		$pairs     = explode( '&', $post_data_string );
		foreach ( $pairs as $pair ) {
			$parts = explode( '=', $pair, 2 );
			if ( count( $parts ) === 2 ) {
				$key   = sanitize_key( urldecode( $parts[0] ) );
				$value = sanitize_text_field( urldecode( $parts[1] ) );
				$post_data[ $key ] = $value;
			}
		}

		if ( ! isset( $post_data['lb_tip_type'] ) || 'none' === $post_data['lb_tip_type'] ) {
			return;
		}

		$tip_type   = sanitize_text_field( $post_data['lb_tip_type'] );
		$tip_amount = 0;

		// Use gross subtotal (including tax) for tip calculation.
		$cart       = WC()->cart;
		$cart_total = $cart->get_subtotal() + $cart->get_subtotal_tax();

		if ( 'percentage' === $tip_type && isset( $post_data['lb_tip_percentage'] ) ) {
			$percentage = floatval( $post_data['lb_tip_percentage'] );
			$tip_amount = ( $cart_total * $percentage ) / 100;
		} elseif ( 'custom' === $tip_type && isset( $post_data['lb_tip_custom'] ) ) {
			$percentage = floatval( $post_data['lb_tip_custom'] );
			$tip_amount = ( $cart_total * $percentage ) / 100;
		}

		if ( $tip_amount > 0 ) {
			// Prüfen ob Rundung aktiviert ist.
			$enable_rounding = get_option( 'lb_enable_rounding', false );

			if ( $enable_rounding ) {
				// Gesamtbetrag MIT Trinkgeld berechnen (brutto).
				$total_with_tip = $cart_total + $tip_amount;

				// Auf 5 Rappen runden.
				$rounded_total = round( $total_with_tip / 0.05 ) * 0.05;

				// Trinkgeld anpassen, sodass Gesamtbetrag gerundet ist.
				$tip_amount = $rounded_total - $cart_total;
			}

			WC()->cart->add_fee( __( 'Trinkgeld', 'libre-bite' ), $tip_amount );
		}
	}

	/**
	 * Rundung auf 5 Rappen als Fee hinzufügen (nur wenn kein Trinkgeld vorhanden)
	 */
	public function apply_rounding_fee() {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		// Prüfen ob Rundung aktiviert ist.
		$enable_rounding = get_option( 'lb_enable_rounding', false );
		if ( ! $enable_rounding ) {
			return;
		}

		$cart = WC()->cart;

		// Prüfen ob Trinkgeld vorhanden ist.
		$has_tip = false;
		foreach ( $cart->get_fees() as $fee ) {
			if ( $fee->name === __( 'Trinkgeld', 'libre-bite' ) ) {
				$has_tip = true;
				break;
			}
		}

		// Wenn Trinkgeld vorhanden ist, wird Rundung dort eingerechnet.
		if ( $has_tip ) {
			return;
		}

		// Aktuellen Brutto-Gesamtbetrag berechnen (ohne Rundung).
		// Use gross subtotal (subtotal + tax) instead of net subtotal.
		$subtotal   = $cart->get_subtotal() + $cart->get_subtotal_tax();
		$fees_total = 0;

		// Alle Fees außer Rundung addieren (inkl. deren Steuern).
		foreach ( $cart->get_fees() as $fee ) {
			if ( $fee->name !== __( 'Rundung', 'libre-bite' ) ) {
				$fees_total += $fee->amount + $fee->tax;
			}
		}

		$current_total = $subtotal + $fees_total;

		// Auf 5 Rappen runden.
		$rounded_total = round( $current_total / 0.05 ) * 0.05;

		// Rundungsdifferenz berechnen.
		$rounding_amount = $rounded_total - $current_total;

		// Nur hinzufügen wenn Differenz nicht 0 ist (mit Toleranz für Floating-Point-Fehler).
		// Third parameter false = tax-exempt fee.
		if ( abs( $rounding_amount ) > 0.001 ) {
			$cart->add_fee( __( 'Rundung', 'libre-bite' ), $rounding_amount, false );
		}
	}

	/**
	 * Trinkgeld in Bestellung speichern
	 *
	 * @param int $order_id Bestellungs-ID
	 */
	public function save_tip_meta( $order_id ) {
		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification for checkout.
		if ( isset( $_POST['lb_tip_type'] ) && 'none' !== sanitize_text_field( wp_unslash( $_POST['lb_tip_type'] ) ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification for checkout.
			$tip_type = sanitize_text_field( wp_unslash( $_POST['lb_tip_type'] ) );
			update_post_meta( $order_id, '_lb_tip_type', $tip_type );

			// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification for checkout.
			if ( 'percentage' === $tip_type && isset( $_POST['lb_tip_percentage'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification for checkout.
				$percentage = floatval( wp_unslash( $_POST['lb_tip_percentage'] ) );
				update_post_meta( $order_id, '_lb_tip_percentage', $percentage );
			// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification for checkout.
			} elseif ( 'custom' === $tip_type && isset( $_POST['lb_tip_custom'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification for checkout.
				$percentage = floatval( wp_unslash( $_POST['lb_tip_custom'] ) );
				update_post_meta( $order_id, '_lb_tip_percentage', $percentage );
			}
		}
	}

	/**
	 * AJAX: Standort setzen
	 */
	public function ajax_set_location() {
		check_ajax_referer( 'lb_frontend_nonce', 'nonce' );

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;
		$order_type  = isset( $_POST['order_type'] ) ? sanitize_text_field( wp_unslash( $_POST['order_type'] ) ) : 'now';
		$pickup_time = isset( $_POST['pickup_time'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_time'] ) ) : '';

		if ( ! $location_id ) {
			wp_send_json_error( array( 'message' => __( 'Ungültiger Standort', 'libre-bite' ) ) );
		}

		// Session initialisieren falls nötig.
		if ( ! WC()->session ) {
			WC()->session = new WC_Session_Handler();
			WC()->session->init();
		}

		// Session-Cookie setzen falls noch nicht vorhanden.
		if ( ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		WC()->session->set( 'lb_location_id', $location_id );
		WC()->session->set( 'lb_order_type', $order_type );

		if ( 'later' === $order_type && $pickup_time ) {
			WC()->session->set( 'lb_pickup_time', $pickup_time );
		} else {
			WC()->session->__unset( 'lb_pickup_time' );
		}

		$location = get_post( $location_id );

		wp_send_json_success(
			array(
				'message'       => __( 'Standort gesetzt', 'libre-bite' ),
				'location_name' => $location ? $location->post_title : '',
			)
		);
	}

	/**
	 * AJAX: Zeitslots abrufen
	 */
	public function ajax_get_timeslots() {
		check_ajax_referer( 'lb_frontend_nonce', 'nonce' );

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;
		$date        = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : current_time( 'Y-m-d' );

		if ( ! $location_id ) {
			wp_send_json_error();
		}

		$timeslots = $this->get_available_timeslots( $location_id, $date );

		wp_send_json_success( array( 'timeslots' => $timeslots ) );
	}

	/**
	 * AJAX: Geschlossene Tage abrufen
	 */
	public function ajax_get_opening_days() {
		check_ajax_referer( 'lb_frontend_nonce', 'nonce' );

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;

		if ( ! $location_id ) {
			wp_send_json_error( array( 'message' => __( 'Ungültiger Standort', 'libre-bite' ) ) );
		}

		$opening_hours = LB_Locations::get_opening_hours( $location_id );
		$closed_days   = array();

		if ( $opening_hours && is_array( $opening_hours ) ) {
			// Alle Wochentage durchgehen
			$weekdays = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );

			foreach ( $weekdays as $day ) {
				// Wenn Tag nicht existiert oder als geschlossen markiert ist
				if ( ! isset( $opening_hours[ $day ] ) || $opening_hours[ $day ]['closed'] ) {
					$closed_days[] = $day;
				}
			}
		}

		wp_send_json_success( array( 'closed_days' => $closed_days ) );
	}

	/**
	 * Verfügbare Zeitslots berechnen
	 *
	 * @param int    $location_id Standort-ID
	 * @param string $date        Datum (Y-m-d)
	 * @return array
	 */
	private function get_available_timeslots( $location_id, $date ) {
		$opening_hours = LB_Locations::get_opening_hours( $location_id );
		$interval      = get_option( 'lb_timeslot_interval', 15 );

		if ( ! $opening_hours || ! is_array( $opening_hours ) ) {
			return array();
		}

		// Wochentag ermitteln (englische Namen).
		$timestamp = strtotime( $date );
		$day_name  = strtolower( gmdate( 'l', $timestamp ) );

		if ( ! isset( $opening_hours[ $day_name ] ) || ! empty( $opening_hours[ $day_name ]['closed'] ) ) {
			return array();
		}

		$open  = isset( $opening_hours[ $day_name ]['open'] ) ? $opening_hours[ $day_name ]['open'] : '09:00';
		$close = isset( $opening_hours[ $day_name ]['close'] ) ? $opening_hours[ $day_name ]['close'] : '18:00';

		$timeslots = array();

		// Zeitstempel für den gewählten Tag mit Öffnungs- und Schließzeit.
		$open_timestamp  = strtotime( $date . ' ' . $open );
		$close_timestamp = strtotime( $date . ' ' . $close );

		// Vorbereitungszeit und aktueller Zeitstempel.
		$prep_time      = get_option( 'lb_preparation_time', 30 );
		$now            = current_time( 'timestamp' );
		$earliest_slot  = $now + ( $prep_time * 60 );

		// Nur zukünftige Slots für heute.
		$is_today = ( $date === current_time( 'Y-m-d' ) );

		$current_slot = $open_timestamp;

		while ( $current_slot < $close_timestamp ) {
			// Für heute: Nur zukünftige Slots nach Vorbereitungszeit.
			if ( $is_today && $current_slot < $earliest_slot ) {
				$current_slot += $interval * 60;
				continue;
			}

			$timeslots[] = array(
				'value' => wp_date( 'Y-m-d H:i', $current_slot ),
				'label' => wp_date( 'H:i', $current_slot ),
			);

			$current_slot += $interval * 60;
		}

		return $timeslots;
	}

	/**
	 * Hex-Farbe in Helligkeit anpassen
	 *
	 * @param string $hex    Hex-Farbwert.
	 * @param int    $amount Anpassungswert (-255 bis 255).
	 * @return string
	 */
	private function adjust_brightness( $hex, $amount ) {
		$hex = ltrim( $hex, '#' );

		// Kurze Hex-Notation erweitern.
		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		$r = max( 0, min( 255, hexdec( substr( $hex, 0, 2 ) ) + $amount ) );
		$g = max( 0, min( 255, hexdec( substr( $hex, 2, 2 ) ) + $amount ) );
		$b = max( 0, min( 255, hexdec( substr( $hex, 4, 2 ) ) + $amount ) );

		return sprintf( '#%02x%02x%02x', $r, $g, $b );
	}

	/**
	 * Optimiertes Checkout-Template verwenden wenn aktiviert
	 *
	 * @param string $template      Template-Pfad.
	 * @param string $template_name Template-Name.
	 * @return string
	 */
	public function maybe_use_optimized_checkout( $template, $template_name ) {
		if ( 'checkout/form-checkout.php' !== $template_name ) {
			return $template;
		}

		$checkout_mode = get_option( 'lb_checkout_mode', 'standard' );

		if ( 'optimized' !== $checkout_mode ) {
			return $template;
		}

		$custom_template = LB_PLUGIN_DIR . 'templates/checkout-optimized.php';

		if ( file_exists( $custom_template ) ) {
			return $custom_template;
		}

		return $template;
	}

	/**
	 * WooCommerce Thank-You Actions entfernen wenn optimierter Modus aktiv
	 */
	public function maybe_remove_thankyou_actions() {
		if ( ! is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}

		$checkout_mode = get_option( 'lb_checkout_mode', 'standard' );

		if ( 'optimized' !== $checkout_mode ) {
			return;
		}

		// Standard WooCommerce Thank-You Actions entfernen.
		remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
		remove_action( 'woocommerce_order_details_after_order_table', 'woocommerce_order_again_button' );

		// Bestelldetails-Hook komplett entfernen.
		remove_all_actions( 'woocommerce_order_details_before_order_table' );
		remove_all_actions( 'woocommerce_order_details_after_order_table' );
		remove_all_actions( 'woocommerce_order_details_after_order_table_items' );
	}

	/**
	 * Optimierte Thank-You-Seite rendern
	 *
	 * @param int $order_id Bestellungs-ID.
	 */
	public function render_optimized_thankyou( $order_id ) {
		$checkout_mode = get_option( 'lb_checkout_mode', 'standard' );

		if ( 'optimized' !== $checkout_mode ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// Optimiertes Template laden.
		include LB_PLUGIN_DIR . 'templates/thankyou-optimized.php';
	}

	/**
	 * E-Mail-Feld im optimierten Modus als optional markieren
	 *
	 * @param array $fields Checkout-Felder.
	 * @return array
	 */
	public function maybe_make_email_optional( $fields ) {
		$checkout_mode = get_option( 'lb_checkout_mode', 'standard' );

		if ( 'optimized' !== $checkout_mode ) {
			return $fields;
		}

		// E-Mail als nicht erforderlich markieren.
		if ( isset( $fields['billing']['billing_email'] ) ) {
			$fields['billing']['billing_email']['required'] = false;
		}

		return $fields;
	}

	/**
	 * Platzhalter-E-Mail setzen wenn im optimierten Modus keine E-Mail angegeben
	 */
	public function maybe_set_placeholder_email() {
		$checkout_mode = get_option( 'lb_checkout_mode', 'standard' );

		if ( 'optimized' !== $checkout_mode ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification.
		$receipt_option = isset( $_POST['lb_receipt_option'] ) ? sanitize_text_field( wp_unslash( $_POST['lb_receipt_option'] ) ) : 'none';
		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification.
		$billing_email = isset( $_POST['billing_email'] ) ? sanitize_email( wp_unslash( $_POST['billing_email'] ) ) : '';

		// Wenn kein Beleg gewünscht oder keine E-Mail angegeben, Platzhalter setzen.
		if ( 'none' === $receipt_option || empty( $billing_email ) || strpos( $billing_email, '@nomail.local' ) !== false ) {
			$placeholder_email = 'guest-' . time() . '-' . wp_rand( 1000, 9999 ) . '@nomail.local';
			$_POST['billing_email'] = $placeholder_email;
		}
	}
}
