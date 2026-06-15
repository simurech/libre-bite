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
class LBite_Checkout {

	/**
	 * Loader-Instanz
	 *
	 * @var LBite_Loader
	 */
	private $loader;

	/**
	 * POS-Bestellart-Kontext für MWST-Filter (null = kein POS-Kontext)
	 *
	 * @var string|null
	 */
	private static $pos_vat_context = null;

	/**
	 * Konstruktor
	 *
	 * @param LBite_Loader $loader Loader-Instanz
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
		if ( lbite_feature_enabled( 'enable_rounding' ) ) {
			$this->loader->add_action( 'woocommerce_cart_calculate_fees', $this, 'apply_rounding_fee', 999 );
		}

		// Standort- & Zeitwahl (Feature-abhängig)
		if ( lbite_feature_enabled( 'enable_location_selector' ) ) {
			$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_frontend_assets' );
			// Modal nur anzeigen wenn explizit aktiviert via Filter
			if ( apply_filters( 'lbite_enable_location_modal', false ) ) {
				$this->loader->add_action( 'wp_footer', $this, 'render_location_modal' );
			}
			$this->loader->add_action( 'woocommerce_checkout_before_customer_details', $this, 'render_location_time_selection' );
			$this->loader->add_action( 'woocommerce_checkout_process', $this, 'validate_location_time' );
			$this->loader->add_action( 'woocommerce_checkout_update_order_meta', $this, 'save_location_time_meta' );

			// AJAX-Endpoints
			$this->loader->add_action( 'wp_ajax_lbite_set_location', $this, 'ajax_set_location' );
			$this->loader->add_action( 'wp_ajax_nopriv_lbite_set_location', $this, 'ajax_set_location' );
			$this->loader->add_action( 'wp_ajax_lbite_get_timeslots', $this, 'ajax_get_timeslots' );
			$this->loader->add_action( 'wp_ajax_nopriv_lbite_get_timeslots', $this, 'ajax_get_timeslots' );
			$this->loader->add_action( 'wp_ajax_lbite_get_opening_days', $this, 'ajax_get_opening_days' );
			$this->loader->add_action( 'wp_ajax_nopriv_lbite_get_opening_days', $this, 'ajax_get_opening_days' );
			$this->loader->add_action( 'wp_ajax_lbite_get_location_status', $this, 'ajax_get_location_status' );
			$this->loader->add_action( 'wp_ajax_nopriv_lbite_get_location_status', $this, 'ajax_get_location_status' );

			// Produkt-Standortverfügbarkeit beim Hinzufügen zum Warenkorb prüfen.
			$this->loader->add_filter( 'woocommerce_add_to_cart_validation', $this, 'validate_product_location_availability', 10, 3 );
		}

		// Trinkgeld & optimierter Checkout: nur in Premium-Version (dieser Block wird in Gratis-Version entfernt).
		if ( lbite_freemius()->is__premium_only() ) {
			if ( lbite_feature_enabled( 'enable_tips' ) ) {
				// Priorität 15: zwischen Bestelltabelle (Prio 10) und Zahlungsbereich (Prio 20).
				// So landet das Trinkgeld ausserhalb von #payment und wird nicht vom Theme transformiert.
				$this->loader->add_action( 'woocommerce_checkout_order_review', $this, 'render_tip_selection__premium_only', 15 );
				$this->loader->add_action( 'woocommerce_cart_calculate_fees', $this, 'add_tip_fee__premium_only' );
				$this->loader->add_action( 'woocommerce_checkout_update_order_meta', $this, 'save_tip_meta__premium_only' );
			}

			if ( lbite_feature_enabled( 'enable_optimized_checkout' ) ) {
				$this->loader->add_filter( 'wc_get_template', $this, 'maybe_use_optimized_checkout__premium_only', 10, 2 );
				$this->loader->add_action( 'woocommerce_thankyou', $this, 'render_optimized_thankyou__premium_only', 1 );
				$this->loader->add_action( 'wp', $this, 'maybe_remove_thankyou_actions__premium_only' );
				$this->loader->add_filter( 'woocommerce_checkout_fields', $this, 'maybe_make_email_optional__premium_only', 999 );
				$this->loader->add_action( 'woocommerce_checkout_process', $this, 'maybe_set_placeholder_email__premium_only', 5 );
				$this->loader->add_filter( 'woocommerce_payment_successful_result', $this, 'wrap_plain_text_payment_messages__premium_only', 10, 2 );
				$this->loader->add_action( 'wp_ajax_lbite_send_receipt_email', $this, 'ajax_send_receipt_email__premium_only' );
				$this->loader->add_action( 'wp_ajax_nopriv_lbite_send_receipt_email', $this, 'ajax_send_receipt_email__premium_only' );
			}
		}

		$this->loader->add_action( 'wp_ajax_nopriv_lbite_check_order_status', $this, 'ajax_check_order_status' );
		$this->loader->add_action( 'wp_ajax_lbite_check_order_status', $this, 'ajax_check_order_status' );
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_order_poll_script' );

		// Shortcode
		$this->loader->add_action( 'init', $this, 'register_shortcodes' );

		// URL-Parameter verarbeiten
		$this->loader->add_action( 'template_redirect', $this, 'process_url_parameters' );

		// Zeitslot-Cache invalidieren wenn Standort oder Feiertage geändert werden.
		$this->loader->add_action( 'save_post_lbite_location', $this, 'invalidate_timeslot_cache' );
		$this->loader->add_action( 'update_option_lbite_holidays', $this, 'invalidate_timeslot_cache' );

		// Notizen pro Bestellposition (Online-Checkout)
		if ( lbite_feature_enabled( 'enable_item_notes_checkout' ) ) {
			$this->loader->add_action( 'woocommerce_after_cart_item_name', $this, 'render_item_note_field', 10, 2 );
			$this->loader->add_action( 'woocommerce_checkout_create_order_line_item', $this, 'save_item_note_to_order', 10, 3 );
		}

		// Schweizer MWST-Umschaltung (Premium)
		if ( lbite_freemius()->is__premium_only() && lbite_feature_enabled( 'enable_swiss_vat' ) ) {
			$this->loader->add_filter( 'woocommerce_product_get_tax_class', $this, 'filter_swiss_vat_tax_class__premium_only', 10, 2 );
			// Varianten nutzen ggf. einen eigenen Filter-Hook.
			$this->loader->add_filter( 'woocommerce_product_variation_get_tax_class', $this, 'filter_swiss_vat_tax_class__premium_only', 10, 2 );
			// Priorität 20: Steuerklasse auf Cart-Items setzen bevor WC die Totale berechnet.
			$this->loader->add_action( 'woocommerce_before_calculate_totals', $this, 'apply_vat_tax_class_to_cart__premium_only', 20 );
			// Priorität 5: vor dem Add-on-Hook (Prio 10 in class-product-options.php).
			$this->loader->add_action( 'woocommerce_checkout_create_order_line_item', $this, 'correct_line_item_subtotal_for_vat__premium_only', 5, 4 );
		}

		// Bestelltyp-Auswahl im Checkout (Premium)
		if ( lbite_freemius()->is__premium_only() && lbite_feature_enabled( 'enable_order_type_selection' ) ) {
			$this->loader->add_action( 'woocommerce_checkout_before_order_review', $this, 'render_order_type_selector__premium_only' );
			$this->loader->add_action( 'woocommerce_checkout_update_order_review', $this, 'save_order_type_to_session__premium_only' );
			$this->loader->add_action( 'woocommerce_checkout_create_order', $this, 'save_order_type_to_order__premium_only', 10, 2 );
		}
	}

	/**
	 * Zeitslot-Cache-Version hochzählen, damit veraltete Transients ignoriert werden.
	 */
	public function invalidate_timeslot_cache() {
		update_option( 'lbite_slots_cache_ver', (int) get_option( 'lbite_slots_cache_ver', 0 ) + 1, false );
	}

	/**
	 * Checkout-Felder anpassen
	 *
	 * @param array $fields Checkout-Felder
	 * @return array
	 */
	public function customize_checkout_fields( $fields ) {
		$custom_fields = get_option( 'lbite_checkout_fields', array() );

		// Tischbestellung Prüfung.
		$table_id = WC()->session ? WC()->session->get( 'lbite_table_id' ) : 0;

		if ( $table_id ) {
			// Bei Tischbestellung brauchen wir keine Adressdaten.
			// billing_last_name bewusst NICHT in der Liste: wird im Template nicht angezeigt,
			// würde aber von WooCommerce als Pflichtfeld validiert.
			$keep_fields = array( 'billing_first_name', 'billing_email' );

			foreach ( $fields['billing'] as $key => $field ) {
				if ( ! in_array( $key, $keep_fields, true ) ) {
					unset( $fields['billing'][ $key ] );
				}
			}

			// Shipping Felder komplett entfernen.
			unset( $fields['shipping'] );

			return $fields;
		}

		// Optimierter Checkout: Nur Name und E-Mail behalten.
		// Die restlichen Felder werden im Template per CSS ausgeblendet, müssen aber auch
		// server-seitig entfernt werden, damit WooCommerce keine Pflichtfeld-Validierung auslöst.
		$checkout_mode = get_option( 'lbite_checkout_mode', 'standard' );
		if ( 'optimized' === $checkout_mode ) {
			$keep_fields = array( 'billing_first_name', 'billing_email' );
			foreach ( $fields['billing'] as $key => $field ) {
				if ( ! in_array( $key, $keep_fields, true ) ) {
					unset( $fields['billing'][ $key ] );
				}
			}
			if ( isset( $fields['shipping'] ) ) {
				unset( $fields['shipping'] );
			}
			return $fields;
		}

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
		$custom_fields = get_option( 'lbite_checkout_fields', array() );

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

		$custom_fields = get_option( 'lbite_checkout_fields', array() );

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
			$custom_fields = get_option( 'lbite_checkout_fields', array() );
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
		$custom_fields = get_option( 'lbite_checkout_fields', array() );

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
		$custom_fields = get_option( 'lbite_checkout_fields', array() );

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
		// Nur auf Warenkorb und Checkout Seiten.
		if ( ! is_cart() && ! is_checkout() ) {
			return;
		}

		$custom_fields = get_option( 'lbite_checkout_fields', array() );

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
		global $post;
		
		// Prüfen ob Shortcode auf der Seite vorhanden ist.
		$has_shortcode = is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'lbite_location_selector' );

		// Nur auf relevanten WooCommerce-Seiten oder wenn Shortcode vorhanden ist.
		if ( ! is_shop() && ! is_product() && ! is_cart() && ! is_checkout() && ! $has_shortcode ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'lbite-frontend',
			LBITE_PLUGIN_URL . 'assets/css/frontend.css',
			array( 'dashicons' ),
			LBITE_VERSION
		);

		// Location Selector CSS
		wp_enqueue_style(
			'lbite-location-selector',
			LBITE_PLUGIN_URL . 'assets/css/location-selector.css',
			array( 'lbite-frontend' ),
			LBITE_VERSION
		);

		// Checkout Templates CSS
		if ( is_checkout() ) {
			wp_enqueue_style(
				'lbite-checkout-templates',
				LBITE_PLUGIN_URL . 'assets/css/checkout-templates.css',
				array( 'lbite-frontend' ),
				LBITE_VERSION
			);
		}

		// Checkout JS-Dateien
		// Premium-Assets nur in Premium-Version laden (dieser Block wird in Gratis-Version entfernt).
		if ( lbite_freemius()->is__premium_only() ) {
			if ( is_checkout() ) {
				wp_enqueue_script(
					'lbite-checkout-receipt',
					LBITE_PLUGIN_URL . 'assets/js/checkout-optimized-receipt.js',
					array( 'jquery' ),
					LBITE_VERSION,
					true
				);

				if ( lbite_feature_enabled( 'enable_tips' ) ) {
					wp_enqueue_script(
						'lbite-checkout-tip',
						LBITE_PLUGIN_URL . 'assets/js/checkout-tip.js',
						array( 'jquery' ),
						LBITE_VERSION,
						true
					);
				}
			}

			// Optimiertes Checkout CSS laden wenn Feature aktiviert.
			if ( is_checkout() && lbite_feature_enabled( 'enable_optimized_checkout' ) ) {
				wp_enqueue_style(
					'lbite-checkout-optimized',
					LBITE_PLUGIN_URL . 'assets/css/checkout-optimized.css',
					array( 'lbite-frontend' ),
					LBITE_VERSION
				);

				wp_enqueue_style(
					'lbite-thankyou-optimized',
					LBITE_PLUGIN_URL . 'assets/css/thankyou-optimized.css',
					array( 'lbite-frontend' ),
					LBITE_VERSION
				);
			}
		}

		// Branding CSS Custom Properties hinzufügen.
		$color_primary   = get_option( 'lbite_color_primary', '#0073aa' );
		$color_secondary = get_option( 'lbite_color_secondary', '#23282d' );
		$color_accent    = get_option( 'lbite_color_accent', '#00a32a' );

		// Hellen Hintergrund aus Primary berechnen (8 % Primary + 92 % Weiss).
		$hex              = ltrim( $color_primary, '#' );
		$r                = (int) round( hexdec( substr( $hex, 0, 2 ) ) * 0.08 + 255 * 0.92 );
		$g                = (int) round( hexdec( substr( $hex, 2, 2 ) ) * 0.08 + 255 * 0.92 );
		$b                = (int) round( hexdec( substr( $hex, 4, 2 ) ) * 0.08 + 255 * 0.92 );
		$color_primary_bg = sprintf( '#%02x%02x%02x', $r, $g, $b );

		$custom_css = sprintf(
			':root {
				--lbite-color-primary: %s;
				--lbite-color-secondary: %s;
				--lbite-color-accent: %s;
				--lbite-color-primary-hover: %s;
				--lbite-color-accent-hover: %s;
				--lbite-color-primary-bg: %s;
			}',
			esc_attr( $color_primary ),
			esc_attr( $color_secondary ),
			esc_attr( $color_accent ),
			esc_attr( $this->adjust_brightness( $color_primary, -20 ) ),
			esc_attr( $this->adjust_brightness( $color_accent, -20 ) ),
			esc_attr( $color_primary_bg )
		);

		wp_add_inline_style( 'lbite-frontend', $custom_css );

		wp_enqueue_script(
			'lbite-frontend',
			LBITE_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			LBITE_VERSION,
			true
		);

		wp_localize_script(
			'lbite-frontend',
			'lbiteData',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'lbite_frontend_nonce' ),
				'strings'       => array(
					'selectLocation' => __( 'Please select a location', 'libre-bite' ),
					'selectTime'     => __( 'Please select a pickup time', 'libre-bite' ),
				),
				'hasLocation'   => ( WC()->session ? ! empty( WC()->session->get( 'lbite_location_id' ) ) : false ),
				'locationId'    => ( WC()->session ? WC()->session->get( 'lbite_location_id' ) : null ),
				'orderType'     => ( WC()->session ? WC()->session->get( 'lbite_order_type', 'now' ) : 'now' ),
			)
		);
	}

	/**
	 * Shortcodes registrieren
	 */
	public function register_shortcodes() {
		add_shortcode( 'lbite_location_selector', array( $this, 'shortcode_location_selector' ) );
	}

	/**
	 * URL-Parameter verarbeiten
	 */
	public function process_url_parameters() {
		if ( ! WC()->session ) {
			return;
		}

		// Session initialisieren falls nötig (z.B. erster Besuch via Deep-Link).
		if ( ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		$location_set  = false;
		$order_type_set = false;

		// Standort via URL-Parameter setzen (?lbite_location=123, Legacy: ?location=123).
		// Oeffentlicher Deep-Link, keine Nonce moeglich (z.B. QR-Code, Flyer, Marketing-Link).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Öffentlicher Deep-Link; nur Session-Schreibzugriff, kein DB-Write.
		$raw_location_id = isset( $_GET['lbite_location'] ) ? intval( $_GET['lbite_location'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Öffentlicher Deep-Link; nur Session-Schreibzugriff, kein DB-Write.
			: ( isset( $_GET['location'] ) ? intval( $_GET['location'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Öffentlicher Deep-Link (Legacy); nur Session-Schreibzugriff, kein DB-Write.
		if ( $raw_location_id ) {
			$location = get_post( $raw_location_id );
			if ( $location && 'lbite_location' === $location->post_type && 'publish' === $location->post_status ) {
				WC()->session->set( 'lbite_location_id', $raw_location_id );
				$location_set = true;
			}
		}

		// Bestelltyp via URL-Parameter setzen (?order_type=now oder ?order_type=later).
		// Oeffentlicher Deep-Link, keine Nonce moeglich.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Öffentlicher Deep-Link; nur Session-Schreibzugriff, kein DB-Write.
		if ( isset( $_GET['order_type'] ) && in_array( sanitize_text_field( wp_unslash( $_GET['order_type'] ) ) , array( 'now', 'later' ), true ) ) {
			WC()->session->set( 'lbite_order_type', sanitize_text_field( wp_unslash( $_GET['order_type'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Öffentlicher Deep-Link; nur Session-Schreibzugriff, kein DB-Write.
			$order_type_set = true;
		}

		// Beide Parameter gesetzt → direkt zum Shop weiterleiten (Standort-Auswahl-Seite überspringen).
		if ( $location_set && $order_type_set && ! is_shop() && ! is_checkout() ) {
			wp_safe_redirect( wc_get_page_permalink( 'shop' ) );
			exit;
		}
	}

	/**
	 * Shortcode: Standort-Auswahl
	 */
	public function shortcode_location_selector( $atts ) {
		// CSS laden
		if ( ! wp_style_is( 'lbite-frontend', 'enqueued' ) ) {
			wp_enqueue_style( 'dashicons' );
			wp_enqueue_style(
				'lbite-frontend',
				LBITE_PLUGIN_URL . 'assets/css/frontend.css',
				array( 'dashicons' ),
				LBITE_VERSION
			);
		}

		// JavaScript laden
		if ( ! wp_script_is( 'lbite-frontend', 'enqueued' ) ) {
			wp_enqueue_script(
				'lbite-frontend',
				LBITE_PLUGIN_URL . 'assets/js/frontend.js',
				array( 'jquery' ),
				LBITE_VERSION,
				true
			);

			// Lokalisierte Daten hinzufügen (wichtig für AJAX!)
			wp_localize_script(
				'lbite-frontend',
				'lbiteData',
				array(
					'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
					'nonce'         => wp_create_nonce( 'lbite_frontend_nonce' ),
					'strings'       => array(
						'selectLocation' => __( 'Please select a location', 'libre-bite' ),
						'selectTime'     => __( 'Please select a pickup time', 'libre-bite' ),
					),
					'hasLocation'   => ( WC()->session ? ! empty( WC()->session->get( 'lbite_location_id' ) ) : false ),
					'locationId'    => ( WC()->session ? WC()->session->get( 'lbite_location_id' ) : null ),
					'orderType'     => ( WC()->session ? WC()->session->get( 'lbite_order_type', 'now' ) : 'now' ),
				)
			);
		}

		$atts = shortcode_atts(
			array(
				'show_time' => 'yes',
				'style'     => 'tiles', // 'tiles', 'inline' oder 'banner'
				'align'     => 'center', // 'left', 'center', 'right'
			),
			$atts,
			'lbite_location_selector'
		);

		$lbite_locations = LBite_Locations::get_all_locations();

		if ( empty( $lbite_locations ) ) {
			return '<p>' . esc_html__( 'No locations available.', 'libre-bite' ) . '</p>';
		}

		$lbite_location_id = WC()->session ? WC()->session->get( 'lbite_location_id' ) : null;
		$lbite_order_type = WC()->session ? WC()->session->get( 'lbite_order_type', 'now' ) : 'now';
		$lbite_pickup_time = WC()->session ? WC()->session->get( 'lbite_pickup_time' ) : null;

		ob_start();

		if ( 'inline' === $atts['style'] ) {
			include LBITE_PLUGIN_DIR . 'templates/location-selector-inline.php';
		} elseif ( 'banner' === $atts['style'] ) {
			include LBITE_PLUGIN_DIR . 'templates/location-selector-banner.php';
		} else {
			include LBITE_PLUGIN_DIR . 'templates/location-selector-tiles.php';
		}

		return ob_get_clean();
	}

	/**
	 * Standort-Modal rendern
	 *
	 * Hinweis: Diese Funktion wird nur aufgerufen, wenn das Modal explizit
	 * via Filter aktiviert wurde: add_filter('lbite_enable_location_modal', '__return_true')
	 */
	public function render_location_modal() {
		// Nur auf bestimmten Seiten anzeigen
		if ( ! is_shop() && ! is_product() && ! is_front_page() ) {
			return;
		}

		// Nur anzeigen wenn noch kein Standort gewählt wurde
		if ( WC()->session && WC()->session->get( 'lbite_location_id' ) ) {
			return;
		}

		$lbite_locations = LBite_Locations::get_all_locations();

		if ( empty( $lbite_locations ) ) {
			return;
		}

		include LBITE_PLUGIN_DIR . 'templates/location-modal.php';
	}

	/**
	 * Standort- & Zeitwahl im Checkout anzeigen
	 */
	public function render_location_time_selection() {
		$lbite_location_id = WC()->session ? WC()->session->get( 'lbite_location_id' ) : null;
		$lbite_order_type  = WC()->session ? WC()->session->get( 'lbite_order_type', 'now' ) : 'now';
		$lbite_pickup_time = WC()->session ? WC()->session->get( 'lbite_pickup_time' ) : null;

		$lbite_locations = LBite_Locations::get_all_locations();

		include LBITE_PLUGIN_DIR . 'templates/checkout-location-time.php';
	}

	/**
	 * Standort & Zeit validieren
	 */
	public function validate_location_time() {
		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification for checkout.
		$location_id = isset( $_POST['lbite_location_id'] ) ? intval( wp_unslash( $_POST['lbite_location_id'] ) ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification for checkout.
		$order_type  = isset( $_POST['lbite_order_type'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_order_type'] ) ) : '';

		// Fallback auf Session – konsistent mit save_location_time_meta().
		if ( ! $location_id && WC()->session ) {
			$location_id = (int) WC()->session->get( 'lbite_location_id' );
		}
		if ( ! $order_type && WC()->session ) {
			$order_type = WC()->session->get( 'lbite_order_type', 'now' );
		}

		if ( ! $location_id ) {
			wc_add_notice( __( 'Please select a location.', 'libre-bite' ), 'error' );
		}

		if ( ! in_array( $order_type, array( 'now', 'later' ), true ) ) {
			wc_add_notice( __( 'Please select an order type.', 'libre-bite' ), 'error' );
		}

		if ( 'later' === $order_type ) {
			// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification for checkout.
			$pickup_time = isset( $_POST['lbite_pickup_time'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_pickup_time'] ) ) : '';
			if ( ! $pickup_time ) {
				wc_add_notice( __( 'Please select a pickup time.', 'libre-bite' ), 'error' );
			}
		}

		// Bestelltyp (Takeaway/Dine-in) prüfen wenn Feature aktiv.
		if ( lbite_feature_enabled( 'enable_order_type_selection' ) && $location_id ) {
			// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification for checkout.
			$service_type = isset( $_POST['lbite_service_type'] ) ? sanitize_key( wp_unslash( $_POST['lbite_service_type'] ) ) : '';
			// QR-Code-Scan: Session-Wert als Fallback akzeptieren.
			if ( ! $service_type && WC()->session ) {
				$service_type = WC()->session->get( 'lbite_service_type', '' );
			}
			if ( ! in_array( $service_type, array( 'takeaway', 'dine_in' ), true ) ) {
				wc_add_notice( __( 'Please select whether you want to take away or eat here.', 'libre-bite' ), 'error' );
			}
		}

		// Sofort-Bestellungen: Standort muss geöffnet sein.
		if ( 'now' === $order_type && $location_id ) {
			$lbite_opening_hours = LBite_Locations::get_opening_hours( $location_id );
			$lbite_status        = LBite_Locations::get_location_status( $lbite_opening_hours );
			if ( $lbite_status && 'open' !== $lbite_status['type'] && 'closing-soon' !== $lbite_status['type'] ) {
				wc_add_notice( __( 'The selected location is currently closed. Please select a pre-order time.', 'libre-bite' ), 'error' );
			}
		}

		// Warenkorb-Produkte auf Standortverfügbarkeit prüfen.
		if ( $location_id && WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( ! LBite_Locations::is_product_available_at_location( $cart_item['product_id'], $location_id ) ) {
					$product = wc_get_product( $cart_item['product_id'] );
					wc_add_notice(
						sprintf(
							/* translators: %s: product name */
							__( '"%s" is not available at the selected location.', 'libre-bite' ),
							$product ? $product->get_name() : $cart_item['product_id']
						),
						'error'
					);
				}
			}
		}
	}

	/**
	 * Produkt-Standortverfügbarkeit beim Hinzufügen zum Warenkorb prüfen.
	 *
	 * @param bool $passed     Bisheriges Ergebnis
	 * @param int  $product_id Produkt-ID
	 * @param int  $quantity   Menge
	 * @return bool
	 */
	public function validate_product_location_availability( $passed, $product_id, $quantity ) {
		if ( ! $passed ) {
			return $passed;
		}
		$location_id = WC()->session ? (int) WC()->session->get( 'lbite_location_id' ) : 0;
		if ( ! $location_id ) {
			return $passed;
		}
		if ( ! LBite_Locations::is_product_available_at_location( $product_id, $location_id ) ) {
			$product = wc_get_product( $product_id );
			wc_add_notice(
				sprintf(
					/* translators: %s: product name */
					__( '"%s" is not available at the selected location.', 'libre-bite' ),
					$product ? $product->get_name() : $product_id
				),
				'error'
			);
			return false;
		}
		return $passed;
	}

	/**
	 * Standort & Zeit in Bestellung speichern
	 *
	 * @param int $order_id Bestellungs-ID
	 */
	public function save_location_time_meta( $order_id ) {
		// Nonce wird von WooCommerce beim Checkout verifiziert.
		if ( ! isset( $_POST['woocommerce-process-checkout-nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' ) ) {
			return;
		}

		$location_id = isset( $_POST['lbite_location_id'] ) ? intval( wp_unslash( $_POST['lbite_location_id'] ) ) : 0;

		// Fallback: Session verwenden wenn POST leer.
		if ( ! $location_id && WC()->session ) {
			$location_id = WC()->session->get( 'lbite_location_id' );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		if ( $location_id ) {
			$order->update_meta_data( '_lbite_location_id', $location_id );

			// Standort-Name speichern.
			$location = get_post( $location_id );
			if ( $location ) {
				$order->update_meta_data( '_lbite_location_name', $location->post_title );
			}
		}

		$order_type = isset( $_POST['lbite_order_type'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_order_type'] ) ) : '';

		// Fallback: Session verwenden wenn POST leer.
		if ( ! $order_type && WC()->session ) {
			$order_type = WC()->session->get( 'lbite_order_type', 'now' );
		}

		if ( $order_type ) {
			$order->update_meta_data( '_lbite_order_type', $order_type );

			$pickup_time = isset( $_POST['lbite_pickup_time'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_pickup_time'] ) ) : '';

			// Fallback: Session verwenden.
			if ( ! $pickup_time && WC()->session ) {
				$pickup_time = WC()->session->get( 'lbite_pickup_time' );
			}

			if ( 'later' === $order_type && $pickup_time ) {
				$order->update_meta_data( '_lbite_pickup_time', $pickup_time );
			}
		}

		$order->save();
	}

	/**
	 * Trinkgeld-Auswahl rendern (nur Premium)
	 */
	public function render_tip_selection__premium_only() {
		// Prüfen ob Trinkgeld-Feature aktiviert ist (standardmäßig aktiviert)
		$custom_fields = get_option( 'lbite_checkout_fields', array() );
		$tip_enabled = isset( $custom_fields['_enable_tip_selection'] ) ? $custom_fields['_enable_tip_selection'] : true;

		if ( ! $tip_enabled ) {
			return;
		}

		$lbite_percentage_1      = get_option( 'lbite_tip_percentage_1', 5 );
		$lbite_percentage_2      = get_option( 'lbite_tip_percentage_2', 10 );
		$lbite_percentage_3      = get_option( 'lbite_tip_percentage_3', 15 );
		$lbite_default_selection = get_option( 'lbite_tip_default_selection', 'none' );
		$lbite_tip_mode          = get_option( 'lbite_tip_mode', 'percentage' );
		$lbite_tip_title         = get_option( 'lbite_tip_title', '' );
		$lbite_tip_label_none    = get_option( 'lbite_tip_label_none', '' );
		$lbite_tip_label_1       = get_option( 'lbite_tip_label_1', '' );
		$lbite_tip_label_2       = get_option( 'lbite_tip_label_2', '' );
		$lbite_tip_label_3       = get_option( 'lbite_tip_label_3', '' );

		include LBITE_PLUGIN_DIR . 'templates/checkout-tip.php';
	}

	/**
	 * Trinkgeld als Fee hinzufügen (nur Premium)
	 */
	public function add_tip_fee__premium_only() {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		// WooCommerce-Nonce prüfen: entweder process_checkout oder update_order_review.
		if ( isset( $_POST['woocommerce-process-checkout-nonce'] ) ) {
			$nonce_value = sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) );
			if ( ! wp_verify_nonce( $nonce_value, 'woocommerce-process_checkout' ) ) {
				return;
			}
		} elseif ( isset( $_POST['security'] ) ) {
			$nonce_value = sanitize_text_field( wp_unslash( $_POST['security'] ) );
			if ( ! wp_verify_nonce( $nonce_value, 'update-order-review' ) ) {
				return;
			}
		} else {
			return;
		}

		// Formulardaten ermitteln: Bei update_order_review als serialisierter String in post_data,
		// bei process_checkout direkt in $_POST.
		if ( isset( $_POST['post_data'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- post_data ist ein serialisierter WooCommerce-Formulardatenstring; Sanitierung würde parse_str() zerstören. Felder werden nach dem Parsen einzeln validiert.
			parse_str( wp_unslash( $_POST['post_data'] ), $form_data );
		} else {
			// Nur benötigte Felder extrahieren (nicht ganzen $_POST kopieren).
			$form_data = array(
				'lbite_tip_type'       => isset( $_POST['lbite_tip_type'] ) ? wp_unslash( $_POST['lbite_tip_type'] ) : '',
				'lbite_tip_percentage' => isset( $_POST['lbite_tip_percentage'] ) ? wp_unslash( $_POST['lbite_tip_percentage'] ) : '',
				'lbite_tip_custom'     => isset( $_POST['lbite_tip_custom'] ) ? wp_unslash( $_POST['lbite_tip_custom'] ) : '',
			);
		}

		if ( ! isset( $form_data['lbite_tip_type'] ) || 'none' === $form_data['lbite_tip_type'] ) {
			return;
		}

		$tip_type   = sanitize_text_field( $form_data['lbite_tip_type'] );
		$tip_amount = 0;
		$tip_mode   = get_option( 'lbite_tip_mode', 'percentage' );

		// Brutto-Zwischensumme (inkl. MwSt.) für prozentuale Trinkgeldberechnung.
		$cart       = WC()->cart;
		$cart_total = $cart->get_subtotal() + $cart->get_subtotal_tax();

		if ( 'percentage' === $tip_type && isset( $form_data['lbite_tip_percentage'] ) ) {
			$value      = floatval( $form_data['lbite_tip_percentage'] );
			$tip_amount = 'fixed' === $tip_mode ? $value : ( $cart_total * $value ) / 100;
		} elseif ( 'custom' === $tip_type && isset( $form_data['lbite_tip_custom'] ) ) {
			$value      = floatval( $form_data['lbite_tip_custom'] );
			$tip_amount = 'fixed' === $tip_mode ? $value : ( $cart_total * $value ) / 100;
		}

		if ( $tip_amount > 0 ) {
			// Prüfen ob Rundung aktiviert ist.
			$enable_rounding = get_option( 'lbite_enable_rounding', false );

			if ( $enable_rounding ) {
				// Gesamtbetrag MIT Trinkgeld berechnen (brutto).
				$total_with_tip = $cart_total + $tip_amount;

				// Auf 5 Rappen runden.
				$rounded_total = round( $total_with_tip / 0.05 ) * 0.05;

				// Trinkgeld anpassen, sodass Gesamtbetrag gerundet ist.
				$tip_amount = $rounded_total - $cart_total;
			}

			WC()->cart->add_fee( __( 'Tip', 'libre-bite' ), $tip_amount );
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
		$enable_rounding = get_option( 'lbite_enable_rounding', false );
		if ( ! $enable_rounding ) {
			return;
		}

		$cart = WC()->cart;

		// Prüfen ob Trinkgeld vorhanden ist.
		$has_tip = false;
		foreach ( $cart->get_fees() as $fee ) {
			if ( $fee->name === __( 'Tip', 'libre-bite' ) ) {
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
			if ( $fee->name !== __( 'Rounding', 'libre-bite' ) ) {
				$fees_total += $fee->amount + $fee->tax;
			}
		}

		// Gutschein-Rabatt abziehen (inkl. Steueranteil), damit auf den tatsächlichen
		// Endbetrag gerundet wird und nicht auf den Betrag vor dem Rabatt.
		$discount_total = $cart->get_discount_total() + $cart->get_discount_tax();

		$current_total = $subtotal + $fees_total - $discount_total;

		// Auf 5 Rappen runden.
		$rounded_total = round( $current_total / 0.05 ) * 0.05;

		// Rundungsdifferenz berechnen.
		$rounding_amount = $rounded_total - $current_total;

		// Nur hinzufügen wenn Differenz nicht 0 ist (mit Toleranz für Floating-Point-Fehler).
		// Third parameter false = tax-exempt fee.
		if ( abs( $rounding_amount ) > 0.001 ) {
			$cart->add_fee( __( 'Rounding', 'libre-bite' ), $rounding_amount, false );
		}
	}

	/**
	 * Trinkgeld in Bestellung speichern (nur Premium)
	 *
	 * @param int $order_id Bestellungs-ID
	 */
	public function save_tip_meta__premium_only( $order_id ) {
		// Nonce wird von WooCommerce beim Checkout verifiziert.
		if ( ! isset( $_POST['woocommerce-process-checkout-nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		if ( isset( $_POST['lbite_tip_type'] ) && 'none' !== sanitize_text_field( wp_unslash( $_POST['lbite_tip_type'] ) ) ) {
			$tip_type = sanitize_text_field( wp_unslash( $_POST['lbite_tip_type'] ) );
			$order->update_meta_data( '_lbite_tip_type', $tip_type );

			if ( 'percentage' === $tip_type && isset( $_POST['lbite_tip_percentage'] ) ) {
				$percentage = floatval( wp_unslash( $_POST['lbite_tip_percentage'] ) );
				$order->update_meta_data( '_lbite_tip_percentage', $percentage );
			} elseif ( 'custom' === $tip_type && isset( $_POST['lbite_tip_custom'] ) ) {
				$percentage = floatval( wp_unslash( $_POST['lbite_tip_custom'] ) );
				$order->update_meta_data( '_lbite_tip_percentage', $percentage );
			}

			$order->save();
		}
	}

	/**
	 * AJAX: Bestellstatus prüfen (Polling-Fallback für Zahlungs-Gateways wie TWINT).
	 * Öffentlich (nopriv), gesichert über WooCommerce Order Key.
	 */
	public function ajax_check_order_status() {
		$order_id  = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$order_key = isset( $_POST['order_key'] ) ? sanitize_text_field( wp_unslash( $_POST['order_key'] ) ) : '';

		if ( ! $order_id || ! $order_key ) {
			wp_send_json_error( 'invalid' );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || $order->get_order_key() !== $order_key ) {
			wp_send_json_error( 'not_found' );
		}

		$status = $order->get_status();
		$paid   = in_array( $status, wc_get_is_paid_statuses(), true );

		wp_send_json_success(
			array(
				'status'   => $status,
				'paid'     => $paid,
				'redirect' => $paid ? $order->get_checkout_order_received_url() : '',
			)
		);
	}

	/**
	 * Polling-Script für Zahlungsbestätigung auf der Checkout-Seite enqueuen.
	 *
	 * Für Online-Gateways (TWINT etc.) die asynchron bestätigen: pollt lbite_check_order_status
	 * bis paid:true, dann Weiterleitung zur Bestellbestätigungsseite.
	 * Für Offline-Gateways (BACS, COD, Cheque): erzwingt Weiterleitung nach 500 ms, falls
	 * WooCommerce-Redirect durch ein Gateway-Plugin blockiert wurde.
	 */
	public function enqueue_order_poll_script() {
		if ( ! is_checkout() ) {
			return;
		}

		$ajax_url = esc_js( admin_url( 'admin-ajax.php' ) );

		// Verwendet ajaxComplete statt checkout_place_order_success, weil WooCommerce
		// triggerHandler() nutzt (kein Bubbling) – document.body-Listener würden nie feuern.
		$script = "(function(\$){
			var _lbitePoller = null, _lbiteCount = 0;

			function _lbiteParseOrder(url) {
				var m = url.match(/order-received\\/([0-9]+)\\/\\?key=(wc_order_[^&]+)/);
				if (m) return {id: m[1], key: m[2], redirect: url};
				m = url.match(/[?&]order=([0-9]+)&key=(wc_order_[^&]+)/);
				return m ? {id: m[1], key: m[2], redirect: url} : null;
			}

			function _lbiteStartPoll(order) {
				if (_lbitePoller) clearInterval(_lbitePoller);
				_lbiteCount = 0;
				_lbitePoller = setInterval(function() {
					_lbiteCount++;
					if (_lbiteCount > 120) { clearInterval(_lbitePoller); return; }
					\$.post('{$ajax_url}', {
						action: 'lbite_check_order_status',
						order_id: order.id,
						order_key: order.key
					}, function(res) {
						if (res.success && res.data.paid) {
							clearInterval(_lbitePoller);
							window.location.href = res.data.redirect;
						}
					});
				}, 5000);
			}

			\$(document).ajaxComplete(function(e, xhr, settings) {
				if (!settings || !settings.url || settings.url.indexOf('wc-ajax=checkout') === -1) return;
				var response;
				try { response = JSON.parse(xhr.responseText); } catch(ex) { return; }
				if (!response || response.result !== 'success' || !response.redirect) return;
				var order = _lbiteParseOrder(response.redirect);
				if (!order) return;
				var pm = \$('input[name=\"payment_method\"]:checked').val() || '';
				if (['bacs', 'cod', 'cheque'].indexOf(pm) !== -1) {
					// Offline-Gateway: Fallback-Weiterleitung. Falls WC bereits navigiert hat,
					// wird dieses Script durch den Seitenwechsel zerstört bevor der Timer feuert.
					setTimeout(function() { window.location.href = response.redirect; }, 500);
				} else {
					_lbiteStartPoll(order);
				}
			});
		}(jQuery));";

		wp_add_inline_script( 'wc-checkout', $script );
	}

	/**
	 * AJAX: Standort setzen
	 */
	public function ajax_set_location() {
		check_ajax_referer( 'lbite_frontend_nonce', 'nonce' );

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;
		$order_type  = isset( $_POST['order_type'] ) ? sanitize_text_field( wp_unslash( $_POST['order_type'] ) ) : 'now';
		$pickup_time = isset( $_POST['pickup_time'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_time'] ) ) : '';

		if ( ! $location_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid location', 'libre-bite' ) ) );
		}

		$location_post = get_post( $location_id );
		if ( ! $location_post || 'lbite_location' !== $location_post->post_type || 'publish' !== $location_post->post_status ) {
			wp_send_json_error( array( 'message' => __( 'Invalid location', 'libre-bite' ) ) );
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

		WC()->session->set( 'lbite_location_id', $location_id );
		WC()->session->set( 'lbite_order_type', $order_type );

		if ( 'later' === $order_type && $pickup_time ) {
			WC()->session->set( 'lbite_pickup_time', $pickup_time );
		} else {
			WC()->session->__unset( 'lbite_pickup_time' );
		}

		wp_send_json_success(
			array(
				'message'       => __( 'Location set', 'libre-bite' ),
				'location_name' => $location_post->post_title,
			)
		);
	}

	/**
	 * AJAX: Zeitslots abrufen
	 */
	public function ajax_get_timeslots() {
		check_ajax_referer( 'lbite_frontend_nonce', 'nonce' );

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
		check_ajax_referer( 'lbite_frontend_nonce', 'nonce' );

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;

		if ( ! $location_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid location', 'libre-bite' ) ) );
		}

		$opening_hours = LBite_Locations::get_opening_hours( $location_id );
		$closed_days   = array();

		if ( $opening_hours && is_array( $opening_hours ) ) {
			$weekdays = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );

			foreach ( $weekdays as $day ) {
				if ( ! isset( $opening_hours[ $day ] ) || $opening_hours[ $day ]['closed'] ) {
					$closed_days[] = $day;
				}
			}
		}

		$closed_dates = LBite_Locations::get_closed_holiday_dates( $location_id );

		wp_send_json_success(
			array(
				'closed_days'  => $closed_days,
				'closed_dates' => $closed_dates,
			)
		);
	}

	/**
	 * AJAX: Aktuellen Öffnungsstatus eines Standorts abrufen
	 */
	public function ajax_get_location_status() {
		check_ajax_referer( 'lbite_frontend_nonce', 'nonce' );

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;

		if ( ! $location_id ) {
			wp_send_json_error();
		}

		$opening_hours = LBite_Locations::get_opening_hours( $location_id );
		$status        = LBite_Locations::get_location_status( $opening_hours );

		wp_send_json_success( array( 'status' => $status ) );
	}

	/**
	 * Verfügbare Zeitslots berechnen
	 *
	 * @param int    $location_id Standort-ID
	 * @param string $date        Datum (Y-m-d)
	 * @return array
	 */
	private function get_available_timeslots( $location_id, $date ) {
		// WP-Timezone frühzeitig setzen – wird für $is_today und alle Zeitberechnungen benötigt.
		$tz       = wp_timezone();
		$is_today = ( $date === ( new DateTime( 'now', $tz ) )->format( 'Y-m-d' ) );

		// Zukünftige Daten cachen (5 Min). Heutige Slots nicht cachen – sie ändern sich minütlich.
		$cache_key = null;
		if ( ! $is_today ) {
			$cache_ver = (int) get_option( 'lbite_slots_cache_ver', 0 );
			$cache_key = 'lbite_s_' . $cache_ver . '_' . absint( $location_id ) . '_' . sanitize_key( $date );
			$cached    = get_transient( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$opening_hours = LBite_Locations::get_opening_hours( $location_id );
		$interval      = LBite_Locations::get_time_setting( $location_id, 'timeslot_interval', 15 );

		if ( ! $opening_hours || ! is_array( $opening_hours ) ) {
			return array();
		}

		// WP-Timezone explizit verwenden, damit alle Zeitberechnungen konsistent sind.
		// ($tz bereits oben gesetzt)

		// Wochentag des gewählten Datums in der WP-Timezone ermitteln (englische Namen).
		$lbite_date_dt = new DateTime( $date, $tz );
		$day_name      = strtolower( $lbite_date_dt->format( 'l' ) );

		if ( ! isset( $opening_hours[ $day_name ] ) || ! empty( $opening_hours[ $day_name ]['closed'] ) ) {
			return array();
		}

		// Feiertag prüfen – überschreibt reguläre Öffnungszeiten.
		$holiday = LBite_Locations::get_holiday_for_date( $location_id, $date );
		if ( $holiday ) {
			$holiday_type = isset( $holiday['type'] ) ? $holiday['type'] : 'closed';
			if ( 'closed' === $holiday_type ) {
				return array();
			}
			if ( 'custom' === $holiday_type ) {
				// Custom-Feiertag-Zeiten als Tagesöffnungszeiten verwenden.
				$opening_hours[ $day_name ] = array(
					'closed' => false,
					'open'   => isset( $holiday['open'] ) ? $holiday['open'] : '',
					'close'  => isset( $holiday['close'] ) ? $holiday['close'] : '',
					'open2'  => isset( $holiday['open2'] ) ? $holiday['open2'] : '',
					'close2' => isset( $holiday['close2'] ) ? $holiday['close2'] : '',
				);
			}
		}

		// Aktueller Zeitpunkt und frühstmöglicher Slot (Vorbereitungszeit).
		$prep_time     = LBite_Locations::get_time_setting( $location_id, 'preparation_time', 30 );
		$now_dt        = new DateTime( 'now', $tz );
		$now_ts        = $now_dt->getTimestamp();
		$earliest_slot = $now_ts + ( $prep_time * 60 );
		// $is_today ist bereits oben für den Cache-Check gesetzt.

		// Zeitfenster für diesen Tag zusammenstellen (Fenster 1 + optional Fenster 2).
		$windows   = array();
		$day_hours = $opening_hours[ $day_name ];
		if ( ! empty( $day_hours['open'] ) && ! empty( $day_hours['close'] ) ) {
			$windows[] = array( 'open' => $day_hours['open'], 'close' => $day_hours['close'] );
		}
		if ( ! empty( $day_hours['open2'] ) && ! empty( $day_hours['close2'] ) ) {
			$windows[] = array( 'open' => $day_hours['open2'], 'close' => $day_hours['close2'] );
		}

		if ( empty( $windows ) ) {
			return array();
		}

		// Slot-Buffer (Premium).
		$buffer_start = 0;
		$buffer_end   = 0;
		if ( function_exists( 'lbite_freemius' ) && lbite_freemius()->is__premium_only() ) {
			$buffer_start = LBite_Locations::get_time_setting( $location_id, 'slot_buffer_start', 0 );
			$buffer_end   = LBite_Locations::get_time_setting( $location_id, 'slot_buffer_end', 0 );
		}

		$timeslots = array();

		foreach ( $windows as $window ) {
			$open_dt  = new DateTime( $date . ' ' . $window['open'], $tz );
			$close_dt = new DateTime( $date . ' ' . $window['close'], $tz );

			$open_timestamp  = $open_dt->getTimestamp() + $buffer_start * 60;
			$close_timestamp = $close_dt->getTimestamp() - $buffer_end * 60;

			if ( $open_timestamp >= $close_timestamp ) {
				continue;
			}

			$current_slot = $open_timestamp;

			while ( $current_slot < $close_timestamp ) {
				// Für heute: Nur Slots nach Vorbereitungszeit anzeigen.
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
		}

		// Chronologisch sortieren und Duplikate entfernen.
		usort( $timeslots, fn( $a, $b ) => strcmp( $a['value'], $b['value'] ) );
		$seen      = array();
		$timeslots = array_filter(
			$timeslots,
			function ( $slot ) use ( &$seen ) {
				if ( isset( $seen[ $slot['value'] ] ) ) {
					return false;
				}
				$seen[ $slot['value'] ] = true;
				return true;
			}
		);

		$result = array_values( $timeslots );

		if ( $cache_key ) {
			set_transient( $cache_key, $result, 5 * MINUTE_IN_SECONDS );
		}

		return $result;
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
	 * Optimiertes Checkout-Template verwenden wenn aktiviert (nur Premium)
	 *
	 * @param string $template      Template-Pfad.
	 * @param string $template_name Template-Name.
	 * @return string
	 */
	public function maybe_use_optimized_checkout__premium_only( $template, $template_name ) {
		if ( 'checkout/form-checkout.php' !== $template_name ) {
			return $template;
		}

		$checkout_mode = get_option( 'lbite_checkout_mode', 'standard' );

		if ( 'optimized' !== $checkout_mode ) {
			return $template;
		}

		$custom_template = LBITE_PLUGIN_DIR . 'templates/checkout-optimized.php';

		if ( file_exists( $custom_template ) ) {
			return $custom_template;
		}

		return $template;
	}

	/**
	 * WooCommerce Thank-You Actions entfernen wenn optimierter Modus aktiv (nur Premium)
	 */
	public function maybe_remove_thankyou_actions__premium_only() {
		if ( ! is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}

		$checkout_mode = get_option( 'lbite_checkout_mode', 'standard' );

		if ( 'optimized' !== $checkout_mode ) {
			return;
		}

		// Standard WooCommerce Thank-You Actions entfernen.
		remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
		remove_action( 'woocommerce_order_details_after_order_table', 'woocommerce_order_again_button' );

		// Zahlungsart-spezifische Hinweise ausblenden (werden im Template als Box angezeigt).
		// Muss auf woocommerce_before_thankyou erfolgen, da Payment-Gateways ihre Hooks
		// u.U. erst nach dem wp-Hook registrieren (lazy init).
		add_action(
			'woocommerce_before_thankyou',
			function ( $order_id ) {
				$lbite_order = wc_get_order( $order_id );
				if ( $lbite_order ) {
					remove_all_actions( 'woocommerce_thankyou_' . $lbite_order->get_payment_method() );
				}
			},
			1
		);

		// Bestelldetails-Hook komplett entfernen.
		remove_all_actions( 'woocommerce_order_details_before_order_table' );
		remove_all_actions( 'woocommerce_order_details_after_order_table' );
		remove_all_actions( 'woocommerce_order_details_after_order_table_items' );
	}

	/**
	 * Optimierte Thank-You-Seite rendern (nur Premium)
	 *
	 * @param int $order_id Bestellungs-ID.
	 */
	public function render_optimized_thankyou__premium_only( $order_id ) {
		$checkout_mode = get_option( 'lbite_checkout_mode', 'standard' );

		if ( 'optimized' !== $checkout_mode ) {
			return;
		}

		$lbite_order = wc_get_order( $order_id );

		if ( ! $lbite_order ) {
			return;
		}

		// Optimiertes Template laden.
		include LBITE_PLUGIN_DIR . 'templates/thankyou-optimized.php';
	}

	/**
	 * Plain-Text-Nachrichten in Payment-Response in HTML-Tags einwickeln (nur Premium)
	 *
	 * Einige Gateways (z.B. TWINT im Inline-Modus) geben «messages» als reinen Text zurück.
	 * jQuery's $() erwartet jedoch HTML oder CSS-Selector – plain Text führt zu einem
	 * «Syntax error, unrecognized expression»-Fehler und das Popup öffnet sich nicht.
	 *
	 * @param array $result   Payment-Ergebnis-Array.
	 * @param int   $order_id Bestellungs-ID.
	 * @return array
	 */
	public function wrap_plain_text_payment_messages__premium_only( $result, $order_id ) {
		if (
			isset( $result['messages'] ) &&
			is_string( $result['messages'] ) &&
			'' !== $result['messages'] &&
			$result['messages'] === strip_tags( $result['messages'] )
		) {
			$result['messages'] = '<p>' . esc_html( $result['messages'] ) . '</p>';
		}
		return $result;
	}

	/**
	 * E-Mail-Feld im optimierten Modus als optional markieren (nur Premium)
	 *
	 * @param array $fields Checkout-Felder.
	 * @return array
	 */
	public function maybe_make_email_optional__premium_only( $fields ) {
		$checkout_mode = get_option( 'lbite_checkout_mode', 'standard' );

		if ( 'optimized' !== $checkout_mode ) {
			return $fields;
		}

		// Bei AJAX-Checkout: E-Mail nur für Offline-Gateways als optional markieren.
		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification.
		$payment_method    = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '';
		$no_email_gateways = array( 'cod', 'bacs', 'cheque' );

		if ( ! empty( $payment_method ) && ! in_array( $payment_method, $no_email_gateways, true ) ) {
			// Online-Gateway (z.B. TWINT): E-Mail bleibt Pflichtfeld.
			return $fields;
		}

		// Offline-Gateway oder initiales Rendering: E-Mail als optional markieren.
		if ( isset( $fields['billing']['billing_email'] ) ) {
			$fields['billing']['billing_email']['required'] = false;
		}

		return $fields;
	}

	/**
	 * Platzhalter-E-Mail setzen wenn im optimierten Modus keine E-Mail angegeben (nur Premium)
	 */
	public function maybe_set_placeholder_email__premium_only() {
		$checkout_mode = get_option( 'lbite_checkout_mode', 'standard' );

		if ( 'optimized' !== $checkout_mode ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification.
		$payment_method    = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce handles nonce verification.
		$billing_email     = isset( $_POST['billing_email'] ) ? sanitize_email( wp_unslash( $_POST['billing_email'] ) ) : '';
		$no_email_gateways = array( 'cod', 'bacs', 'cheque' );
		$is_no_email_gateway = in_array( $payment_method, $no_email_gateways, true );

		// Platzhalter-E-Mail nur für Offline-Gateways setzen. Externe Zahlungsanbieter
		// (TWINT, Stripe etc.) benötigen eine echte E-Mail-Adresse.
		if ( $is_no_email_gateway && ( empty( $billing_email ) || strpos( $billing_email, '@nomail.local' ) !== false ) ) {
			$_POST['billing_email'] = 'guest-' . time() . '-' . wp_rand( 1000, 9999 ) . '@nomail.local';
		}
	}

	/**
	 * AJAX: Beleg per E-Mail an Kunden senden (Bestätigungsseite)
	 * Nur in Premium-Version verfügbar.
	 */
	public function ajax_send_receipt_email__premium_only() {
		// Dieser Handler ist nopriv by design: Gäste sind nach dem Checkout nicht eingeloggt
		// und brauchen dennoch die Möglichkeit, einen Beleg anzufordern.
		// Schutz erfolgt durch eine order-spezifische Nonce (lbite_send_receipt_{order_id})
		// und das einmalige Rate-Limit via _lbite_receipt_sent Order-Meta.
		// phpcs:ignore WordPress.Security.NonceVerification -- Nonce wird unten geprüft.
		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;

		if ( ! $order_id ) {
			wp_send_json_error( __( 'Invalid order.', 'libre-bite' ) );
		}

		check_ajax_referer( 'lbite_send_receipt_' . $order_id, 'nonce' );

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( __( 'Order not found.', 'libre-bite' ) );
		}

		// Rate-Limit: nur einmal versenden.
		if ( $order->get_meta( '_lbite_receipt_sent' ) ) {
			wp_send_json_error( __( 'Receipt already sent.', 'libre-bite' ) );
		}

		$billing_email = $order->get_billing_email();
		$is_dummy      = empty( $billing_email ) || strpos( $billing_email, '@nomail.local' ) !== false;

		if ( $is_dummy ) {
			// phpcs:ignore WordPress.Security.NonceVerification -- Nonce wurde oben geprüft.
			$guest_email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
			if ( ! is_email( $guest_email ) ) {
				wp_send_json_error( __( 'Please enter a valid email address.', 'libre-bite' ) );
			}
			// Nur für diesen Versand setzen – wird nicht gespeichert.
			$order->set_billing_email( $guest_email );
		}

		$emails = WC()->mailer()->get_emails();
		if ( isset( $emails['WC_Email_Customer_Invoice'] ) ) {
			$emails['WC_Email_Customer_Invoice']->trigger( $order_id );
		}

		$order->update_meta_data( '_lbite_receipt_sent', current_time( 'mysql' ) );
		$order->save();

		wp_send_json_success( __( 'Receipt sent.', 'libre-bite' ) );
	}

	/**
	 * Notiz-Eingabefeld pro Warenkorb-Position rendern
	 *
	 * @param array  $cart_item     Warenkorb-Position
	 * @param string $cart_item_key Warenkorb-Schlüssel
	 */
	public function render_item_note_field( $cart_item, $cart_item_key ) {
		$saved_note = WC()->session ? WC()->session->get( 'lbite_item_note_' . $cart_item_key, '' ) : '';
		echo '<div class="lbite-item-note-wrap">';
		echo '<input type="text" name="lbite_item_notes[' . esc_attr( $cart_item_key ) . ']"'
			. ' class="lbite-checkout-item-note"'
			. ' value="' . esc_attr( $saved_note ) . '"'
			. ' placeholder="' . esc_attr__( 'Note', 'libre-bite' ) . '">';
		echo '</div>';
	}

	/**
	 * Notiz aus dem POST-Daten als Positions-Meta speichern
	 *
	 * @param \WC_Order_Item_Product $item          Bestellposition
	 * @param string                 $cart_item_key Warenkorb-Schlüssel
	 * @param array                  $values        Warenkorb-Positionsdaten
	 */
	public function save_item_note_to_order( $item, $cart_item_key, $values ) {
		// phpcs:ignore WordPress.Security.NonceVerification -- Nonce wird von WooCommerce geprüft.
		if ( empty( $_POST['lbite_item_notes'][ $cart_item_key ] ) ) {
			return;
		}
		$note = sanitize_text_field( wp_unslash( $_POST['lbite_item_notes'][ $cart_item_key ] ) );
		if ( '' !== $note ) {
			$item->add_meta_data( 'Note', $note, true );
		}
	}

	/**
	 * Steuerklasse direkt auf Cart-Item-Produkten setzen, bevor WC die Totale berechnet (Premium)
	 *
	 * WooCommerce ruft intern get_tax_class('unfiltered') auf, welcher unseren Filter
	 * woocommerce_product_get_tax_class umgeht. Dadurch extrahiert WC den Nettopreis mit
	 * der Original-Steuerklasse und addiert anschliessend den neuen Steuersatz – was zu
	 * einem abweichenden Bruttopreis führt. Durch direktes Setzen der Steuerklasse via
	 * set_tax_class() auf dem Produkt-Objekt liefert auch der 'unfiltered'-Aufruf die
	 * Zielklasse, sodass Extraktion und Addition dieselbe Rate verwenden und der
	 * Bruttopreis konstant bleibt.
	 *
	 * @param WC_Cart $cart Warenkorb.
	 */
	public function apply_vat_tax_class_to_cart__premium_only( $cart ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		foreach ( $cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			if ( ! $product || ! $product->is_taxable() ) {
				continue;
			}
			$original_class = $product->get_tax_class( 'unfiltered' );
			$filtered_class = $product->get_tax_class(); // Geht durch unseren Filter.
			if ( $original_class !== $filtered_class ) {
				$product->set_tax_class( $filtered_class );
			}
		}
	}

	/**
	 * Bestellposition-Nettobetrag für Schweizer MWST korrigieren (Premium)
	 *
	 * WC berechnet item->subtotal mit get_tax_class('unfiltered') und umgeht damit unseren
	 * MWST-Filter. Diese Methode korrigiert den gespeicherten Nettobetrag auf die Zielstufe,
	 * bevor der Add-on-Hook (Priorität 10) seine Korrekturen vornimmt.
	 *
	 * @param WC_Order_Item_Product $item          Bestellposition.
	 * @param string                $cart_item_key Cart-Schlüssel.
	 * @param array                 $values        Cart-Item-Daten.
	 * @param WC_Order              $order         Bestellung.
	 */
	public function correct_line_item_subtotal_for_vat__premium_only( $item, $cart_item_key, $values, $order ) {
		$product = $item->get_product();
		if ( ! $product || ! $product->is_taxable() || ! wc_prices_include_tax() ) {
			return;
		}

		// WooCommerce ruft in add_product() intern get_tax_class('unfiltered') auf,
		// sodass die gespeicherte Steuerklasse des Bestellpostens die Originalklasse enthalten kann.
		// Hier wird die gefilterte Steuerklasse explizit gesetzt, damit calculate_totals()
		// den korrekten Steuersatz (z.B. 2.6%) ausweist.
		$filtered_class = $product->get_tax_class(); // Geht durch unseren Filter.
		$item->set_tax_class( $filtered_class );

		$qty               = $item->get_quantity();
		$gross_per_unit    = (float) $values['data']->get_price(); // Warenkorb-Preis (ggf. inkl. Add-ons)
		$correct_net_total = self::gross_to_net_at_filtered_class( $product, $gross_per_unit ) * $qty;
		$item->set_subtotal( $correct_net_total );
		$item->set_total( $correct_net_total );
	}

	/**
	 * Nettopreis aus Bruttopreis anhand der gefilterten Steuerklasse berechnen.
	 *
	 * wc_get_price_excluding_tax() ruft intern get_tax_class('unfiltered') auf und umgeht
	 * damit den Schweizer MWST-Filter. Diese Methode nutzt get_tax_class() (gefiltert),
	 * damit der Endpreis für den Kunden durch die MWST-Umschaltung unverändert bleibt.
	 *
	 * @param WC_Product $product    Produkt-Instanz.
	 * @param float      $gross      Bruttopreis (inkl. Steuern), pro Einheit.
	 * @return float Nettopreis (exkl. Steuern) zur gefilterten Steuerklasse.
	 */
	public static function gross_to_net_at_filtered_class( $product, $gross ) {
		if ( ! wc_prices_include_tax() || ! $product->is_taxable() ) {
			return $gross;
		}
		$rates = WC_Tax::get_base_tax_rates( $product->get_tax_class() ); // gefilterte Klasse
		if ( empty( $rates ) ) {
			return $gross;
		}
		$taxes = WC_Tax::calc_tax( $gross, $rates, true );
		return max( 0.0, $gross - array_sum( $taxes ) );
	}

	/**
	 * Schweizer MWST-Steuerklasse je nach Bestellart filtern (Premium)
	 *
	 * @param string     $tax_class Aktuelle Steuerklasse
	 * @param \WC_Product $product   Produkt
	 * @return string Gefilterte Steuerklasse
	 */
	public function filter_swiss_vat_tax_class__premium_only( $tax_class, $product ) {
		// POS-Kontext (statisch gesetzt vor Item-Erstellung in ajax_pos_create_order)
		if ( null !== self::$pos_vat_context ) {
			// false als Default: Option nicht gesetzt → kein Override.
			// '' ist gültiger WC-Wert für die Standard-Steuerklasse → muss angewendet werden.
			$new = get_option( 'lbite_tax_class_' . self::$pos_vat_context, false );
			return false !== $new ? $new : $tax_class;
		}
		// Frontend: nur im Warenkorb, Checkout oder AJAX anwenden.
		// Auf Produktseiten darf der Filter nicht greifen, da WooCommerce sonst den
		// Anzeigepreis mit der gefilterten Steuerklasse umrechnet (z.B. 10.00 → 9.49).
		if ( ! WC()->session || ( ! is_cart() && ! is_checkout() && ! wp_doing_ajax() ) ) {
			return $tax_class;
		}
		// Explizite Bestelltyp-Auswahl des Kunden hat Vorrang vor automatischer Tisch-Erkennung.
		$service_type = WC()->session->get( 'lbite_service_type' );
		if ( $service_type ) {
			$key = 'lbite_tax_class_' . $service_type;
			$new = get_option( $key, false );
			return false !== $new ? $new : $tax_class;
		}
		// Fallback: Tisch-ID aus Session (QR-Code-Scan).
		$table_id = WC()->session->get( 'lbite_table_id' );
		if ( $table_id ) {
			$new = get_option( 'lbite_tax_class_dine_in', false );
			return false !== $new ? $new : $tax_class;
		}
		return $tax_class;
	}

	/**
	 * POS-Bestellart-Kontext für MWST-Filter setzen
	 *
	 * @param string $type 'takeaway' oder 'dine_in'
	 */
	public static function set_pos_vat_context( $type ) {
		self::$pos_vat_context = $type;
	}

	/**
	 * POS-Bestellart-Kontext zurücksetzen
	 */
	public static function clear_pos_vat_context() {
		self::$pos_vat_context = null;
	}

	/**
	 * Bestelltyp-Auswahl (Takeaway / Dine-in) im Checkout-Formular ausgeben.
	 */
	public function render_order_type_selector__premium_only() {
		// Im optimierten Checkout wird der Selektor direkt im Template gerendert.
		if ( ! empty( $GLOBALS['lbite_order_type_rendered'] ) ) {
			return;
		}
		$current          = WC()->session ? WC()->session->get( 'lbite_service_type', '' ) : '';
		$table_in_session = WC()->session ? WC()->session->get( 'lbite_table_id' ) : '';
		// QR-Code-Scan → Dine-in vorauswählen.
		if ( ! $current && $table_in_session ) {
			$current = 'dine_in';
		}
		$is_dine_in    = 'dine_in' === $current;
		$show_table    = lbite_feature_enabled( 'enable_table_ordering' );
		$location_id   = WC()->session ? (int) WC()->session->get( 'lbite_location_id' ) : 0;
		$table_nr      = WC()->session ? WC()->session->get( 'lbite_checkout_table_number', '' ) : '';
		$table_sort    = get_option( 'lbite_table_dropdown_sort', 'natural' );
		$tables        = array();
		if ( $show_table && $location_id ) {
			$tables = get_posts( array(
				'post_type'      => 'lbite_table',
				'posts_per_page' => 50,
				'orderby'        => 'menu_order' === $table_sort ? 'menu_order' : 'title',
				'order'          => 'ASC',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Tischabfrage auf max. 50 Einträge begrenzt.
				'meta_query'     => array(
					array(
						'key'   => '_lbite_location_id',
						'value' => $location_id,
					),
				),
			) );
			if ( 'natural' === $table_sort ) {
				usort( $tables, function( $a, $b ) {
					return strnatcasecmp( $a->post_title, $b->post_title );
				} );
			}
		}
		?>
		<div id="lbite-order-type-selector" class="lbite-order-type-selector">
			<p class="lbite-service-type-label"><?php esc_html_e( 'How would you like your order?', 'libre-bite' ); ?> <span class="required">*</span></p>
			<div class="lbite-order-type-options">
				<label class="lbite-order-type-option">
					<input type="radio" name="lbite_service_type" value="takeaway" <?php echo ( 'takeaway' === $current ) ? 'checked' : ''; ?>>
					<span><?php esc_html_e( 'To take away', 'libre-bite' ); ?></span>
				</label>
				<label class="lbite-order-type-option">
					<input type="radio" name="lbite_service_type" value="dine_in" <?php echo ( 'dine_in' === $current ) ? 'checked' : ''; ?>>
					<span><?php esc_html_e( 'Eat here', 'libre-bite' ); ?></span>
				</label>
			</div>
			<?php if ( $show_table ) : ?>
			<div id="lbite-table-number-wrap" class="lbite-table-number-wrap" style="<?php echo $is_dine_in ? '' : 'display:none;'; ?>">
				<label for="lbite-table-number"><?php esc_html_e( 'Table (optional):', 'libre-bite' ); ?></label>
				<?php if ( ! empty( $tables ) ) : ?>
				<select id="lbite-table-number" name="lbite_checkout_table_number" class="input-text lbite-select">
					<option value=""><?php esc_html_e( 'Select table (optional)', 'libre-bite' ); ?></option>
					<?php foreach ( $tables as $lbite_ct ) : ?>
					<option value="<?php echo esc_attr( $lbite_ct->post_title ); ?>" <?php selected( $table_nr, $lbite_ct->post_title ); ?>>
						<?php echo esc_html( $lbite_ct->post_title ); ?>
					</option>
					<?php endforeach; ?>
				</select>
				<?php else : ?>
				<input type="text" id="lbite-table-number" name="lbite_checkout_table_number" class="input-text" value="<?php echo esc_attr( $table_nr ); ?>" placeholder="<?php esc_attr_e( 'e.g. 5', 'libre-bite' ); ?>">
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Bestelltyp-Auswahl in die WC-Session speichern (wird bei update_order_review aufgerufen).
	 *
	 * @param string $post_data URL-enkodierte POST-Daten des Checkout-AJAX.
	 */
	public function save_order_type_to_session__premium_only( $post_data ) {
		if ( ! WC()->session ) {
			return;
		}
		$parsed = array();
		parse_str( $post_data, $parsed );

		$service_type = isset( $parsed['lbite_service_type'] ) ? sanitize_key( $parsed['lbite_service_type'] ) : '';
		if ( in_array( $service_type, array( 'takeaway', 'dine_in' ), true ) ) {
			WC()->session->set( 'lbite_service_type', $service_type );
		}

		$table_number = isset( $parsed['lbite_checkout_table_number'] ) ? sanitize_text_field( wp_unslash( $parsed['lbite_checkout_table_number'] ) ) : '';
		WC()->session->set( 'lbite_checkout_table_number', $table_number );
	}

	/**
	 * Bestelltyp und Tischnummer als Order-Meta speichern.
	 *
	 * @param WC_Order $order Bestellung.
	 * @param array    $data  Checkout-Formulardaten.
	 */
	public function save_order_type_to_order__premium_only( $order, $data ) {
		$service_type = WC()->session ? WC()->session->get( 'lbite_service_type', '' ) : '';
		if ( in_array( $service_type, array( 'takeaway', 'dine_in' ), true ) ) {
			$order->update_meta_data( '_lbite_service_type', $service_type );
		}

		if ( 'dine_in' === $service_type && lbite_feature_enabled( 'enable_table_ordering' ) ) {
			$table_number = WC()->session ? WC()->session->get( 'lbite_checkout_table_number', '' ) : '';
			if ( ! empty( $table_number ) ) {
				$order->update_meta_data( '_lbite_checkout_table_number', sanitize_text_field( $table_number ) );
			}
		}
	}
}
