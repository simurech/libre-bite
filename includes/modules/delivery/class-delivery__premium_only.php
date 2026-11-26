<?php
/**
 * Lieferung Stufe 1 (F44, Pro)
 *
 * Pro Standort: Liefergebiet als Liste von Postleitzahlen (exakt, mit
 * Platzhalter «80*» oder als Bereich «8000-8099»), Liefergebühr, Gratis-
 * Lieferung ab einem Betrag, Mindestbestellwert und zusätzliche Vorlaufzeit.
 *
 * Im Checkout erscheint eine eigene Auswahl «Abholung / Lieferung» mit eigenen
 * Adressfeldern. Bewusst nicht über die WooCommerce-Rechnungsfelder: Die sind
 * je nach Einstellung ausgeblendet und im optimierten Checkout ganz entfernt.
 * Die Lieferadresse landet beim Absenden in der WooCommerce-Lieferadresse der
 * Bestellung, damit Bestellansicht, Mails und Bons sie wie gewohnt zeigen.
 *
 * Polygon- oder Radiuszonen (F50) sind ausdrücklich nicht Teil dieser Stufe.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Liefer-Klasse
 */
class LBite_Delivery {

	/**
	 * Meta-Präfix auf lbite_location.
	 */
	const META = '_lbite_delivery_';

	/**
	 * Loader-Instanz.
	 *
	 * @var LBite_Loader
	 */
	private $loader;

	/**
	 * Konstruktor
	 *
	 * @param LBite_Loader $loader Loader.
	 */
	public function __construct( $loader ) {
		$this->loader = $loader;

		// Standort-Einstellungen.
		$this->loader->add_action( 'add_meta_boxes', $this, 'add_meta_box' );
		$this->loader->add_action( 'save_post_lbite_location', $this, 'save_location_settings' );

		// Checkout.
		$this->loader->add_action( 'woocommerce_checkout_before_customer_details', $this, 'render_checkout_block', 20 );
		$this->loader->add_action( 'woocommerce_checkout_update_order_review', $this, 'save_to_session' );
		// Priorität 8: nach den Aktionen (5), vor Trinkgeld (10) und Rundung (999).
		$this->loader->add_action( 'woocommerce_cart_calculate_fees', $this, 'add_delivery_fee', 8 );
		$this->loader->add_action( 'woocommerce_checkout_process', $this, 'validate_checkout' );
		$this->loader->add_action( 'woocommerce_checkout_create_order', $this, 'save_to_order', 10, 2 );
		$this->loader->add_action( 'woocommerce_checkout_create_order_fee_item', $this, 'mark_fee_item', 10, 4 );
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_assets' );

		// Personal.
		$this->loader->add_filter( 'lbite_dashboard_order_data', $this, 'add_to_dashboard_data', 20, 2 );
	}

	/*
	 * ------------------------------------------------------------------
	 * Reine Logik (ohne WordPress-Zustand, per PHPUnit getestet)
	 * ------------------------------------------------------------------
	 */

	/**
	 * Postleitzahl vereinheitlichen (Leerzeichen raus, Grossbuchstaben).
	 *
	 * @param string $postcode Postleitzahl.
	 * @return string
	 */
	public static function normalize_postcode( $postcode ) {
		return strtoupper( (string) preg_replace( '/\s+/', '', (string) $postcode ) );
	}

	/**
	 * Gehört die Postleitzahl zum Liefergebiet?
	 *
	 * Regeln (eine pro Zeile oder durch Komma getrennt):
	 * «8000» exakt, «80*» Präfix, «8000-8099» numerischer Bereich.
	 *
	 * @param string $postcode Postleitzahl des Gastes.
	 * @param string $rules    Regeln aus den Standort-Einstellungen.
	 * @return bool
	 */
	public static function postcode_matches( $postcode, $rules ) {
		$postcode = self::normalize_postcode( $postcode );
		if ( '' === $postcode ) {
			return false;
		}

		foreach ( preg_split( '/[\r\n,;]+/', (string) $rules ) as $rule ) {
			$rule = self::normalize_postcode( $rule );
			if ( '' === $rule ) {
				continue;
			}

			if ( preg_match( '/^(\d+)-(\d+)$/', $rule, $m ) ) {
				if ( ctype_digit( $postcode ) && strlen( $postcode ) === strlen( $m[1] )
					&& (int) $postcode >= (int) $m[1] && (int) $postcode <= (int) $m[2] ) {
					return true;
				}
				continue;
			}

			if ( '*' === substr( $rule, -1 ) ) {
				if ( 0 === strpos( $postcode, substr( $rule, 0, -1 ) ) ) {
					return true;
				}
				continue;
			}

			if ( $rule === $postcode ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Liefergebühr für einen Bestellwert.
	 *
	 * @param float $subtotal  Warenwert.
	 * @param float $fee       Gebühr.
	 * @param float $free_from Gratis ab diesem Warenwert, 0 = nie gratis.
	 * @return float
	 */
	public static function calculate_fee( $subtotal, $fee, $free_from ) {
		$fee = max( 0.0, (float) $fee );

		if ( (float) $free_from > 0 && (float) $subtotal >= (float) $free_from ) {
			return 0.0;
		}

		return $fee;
	}

	/*
	 * ------------------------------------------------------------------
	 * Standort-Einstellungen
	 * ------------------------------------------------------------------
	 */

	/**
	 * Liefer-Einstellungen eines Standorts.
	 *
	 * @param int $location_id Standort-ID.
	 * @return array{enabled:bool,postcodes:string,fee:float,free_from:float,min_total:float,lead_time:int}
	 */
	public static function get_settings( $location_id ) {
		$location_id = (int) $location_id;

		return array(
			'enabled'   => $location_id && '1' === get_post_meta( $location_id, self::META . 'enabled', true ),
			'postcodes' => (string) get_post_meta( $location_id, self::META . 'postcodes', true ),
			'fee'       => (float) get_post_meta( $location_id, self::META . 'fee', true ),
			'free_from' => (float) get_post_meta( $location_id, self::META . 'free_from', true ),
			'min_total' => (float) get_post_meta( $location_id, self::META . 'min_total', true ),
			'lead_time' => (int) get_post_meta( $location_id, self::META . 'lead_time', true ),
		);
	}

	/**
	 * Metabox am Standort registrieren.
	 */
	public function add_meta_box() {
		add_meta_box(
			'lbite_location_delivery',
			__( 'Delivery', 'libre-bite' ),
			array( $this, 'render_meta_box' ),
			'lbite_location',
			'normal',
			'default'
		);
	}

	/**
	 * Metabox ausgeben.
	 *
	 * @param WP_Post $post Standort.
	 */
	public function render_meta_box( $post ) {
		$s = self::get_settings( $post->ID );
		wp_nonce_field( 'lbite_location_delivery', 'lbite_location_delivery_nonce' );
		?>
		<p>
			<label>
				<input type="checkbox" name="lbite_delivery_enabled" value="1" <?php checked( $s['enabled'] ); ?>>
				<?php esc_html_e( 'Offer delivery from this location', 'libre-bite' ); ?>
			</label>
		</p>
		<table class="form-table">
			<tr>
				<th><label for="lbite_delivery_postcodes"><?php esc_html_e( 'Delivery area (postcodes)', 'libre-bite' ); ?></label></th>
				<td>
					<textarea id="lbite_delivery_postcodes" name="lbite_delivery_postcodes" rows="4" class="large-text code"><?php echo esc_textarea( $s['postcodes'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One per line: 8000 (exact), 80* (all starting with 80) or 8000-8099 (range).', 'libre-bite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="lbite_delivery_fee"><?php esc_html_e( 'Delivery fee', 'libre-bite' ); ?></label></th>
				<td><input type="number" step="0.05" min="0" id="lbite_delivery_fee" name="lbite_delivery_fee" value="<?php echo esc_attr( $s['fee'] ); ?>" class="small-text"> <?php echo esc_html( get_woocommerce_currency() ); ?></td>
			</tr>
			<tr>
				<th><label for="lbite_delivery_free_from"><?php esc_html_e( 'Free delivery from', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" step="0.05" min="0" id="lbite_delivery_free_from" name="lbite_delivery_free_from" value="<?php echo esc_attr( $s['free_from'] ); ?>" class="small-text"> <?php echo esc_html( get_woocommerce_currency() ); ?>
					<p class="description"><?php esc_html_e( '0 = delivery is never free.', 'libre-bite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="lbite_delivery_min_total"><?php esc_html_e( 'Minimum order value', 'libre-bite' ); ?></label></th>
				<td><input type="number" step="0.05" min="0" id="lbite_delivery_min_total" name="lbite_delivery_min_total" value="<?php echo esc_attr( $s['min_total'] ); ?>" class="small-text"> <?php echo esc_html( get_woocommerce_currency() ); ?></td>
			</tr>
			<tr>
				<th><label for="lbite_delivery_lead_time"><?php esc_html_e( 'Additional lead time', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" step="5" min="0" id="lbite_delivery_lead_time" name="lbite_delivery_lead_time" value="<?php echo esc_attr( $s['lead_time'] ); ?>" class="small-text"> <?php esc_html_e( 'minutes', 'libre-bite' ); ?>
					<p class="description"><?php esc_html_e( 'Added to the preparation time for the earliest delivery time.', 'libre-bite' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Metabox speichern.
	 *
	 * @param int $post_id Standort-ID.
	 */
	public function save_location_settings( $post_id ) {
		if ( ! isset( $_POST['lbite_location_delivery_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lbite_location_delivery_nonce'] ) ), 'lbite_location_delivery' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, self::META . 'enabled', isset( $_POST['lbite_delivery_enabled'] ) ? '1' : '0' );

		$rules = isset( $_POST['lbite_delivery_postcodes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['lbite_delivery_postcodes'] ) ) : '';
		update_post_meta( $post_id, self::META . 'postcodes', $rules );

		foreach ( array( 'fee', 'free_from', 'min_total' ) as $key ) {
			$value = isset( $_POST[ 'lbite_delivery_' . $key ] ) ? (float) sanitize_text_field( wp_unslash( $_POST[ 'lbite_delivery_' . $key ] ) ) : 0;
			update_post_meta( $post_id, self::META . $key, max( 0, round( $value, 2 ) ) );
		}

		$lead = isset( $_POST['lbite_delivery_lead_time'] ) ? absint( wp_unslash( $_POST['lbite_delivery_lead_time'] ) ) : 0;
		update_post_meta( $post_id, self::META . 'lead_time', min( 600, $lead ) );
	}

	/*
	 * ------------------------------------------------------------------
	 * Checkout
	 * ------------------------------------------------------------------
	 */

	/**
	 * Standort der aktuellen Session mit aktiver Lieferung, sonst 0.
	 *
	 * Tischbestellungen (QR-Scan) sind nie Lieferungen.
	 *
	 * @return int
	 */
	private static function get_delivery_location_id() {
		if ( ! function_exists( 'WC' ) || ! WC()->session || WC()->session->get( 'lbite_table_id' ) ) {
			return 0;
		}

		$location_id = (int) WC()->session->get( 'lbite_location_id' );

		return ( $location_id && self::get_settings( $location_id )['enabled'] ) ? $location_id : 0;
	}

	/**
	 * Hat der Gast in dieser Session «Lieferung» gewählt?
	 *
	 * @return bool
	 */
	public static function is_delivery_selected() {
		return self::get_delivery_location_id() && 'delivery' === WC()->session->get( 'lbite_fulfilment' );
	}

	/**
	 * Gespeicherte Lieferangaben aus der Session.
	 *
	 * @return array{address:string,postcode:string,city:string,phone:string,notes:string}
	 */
	private static function get_session_address() {
		$data = WC()->session ? WC()->session->get( 'lbite_delivery', array() ) : array();
		$data = is_array( $data ) ? $data : array();

		return wp_parse_args(
			$data,
			array(
				'address'  => '',
				'postcode' => '',
				'city'     => '',
				'phone'    => '',
				'notes'    => '',
			)
		);
	}

	/**
	 * Script und Stil im Checkout laden.
	 */
	public function enqueue_assets() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || ! self::get_delivery_location_id() ) {
			return;
		}

		wp_enqueue_style( 'lbite-delivery', LBITE_PLUGIN_URL . 'assets/css/delivery__premium_only.css', array(), LBITE_VERSION );
		wp_enqueue_script(
			'lbite-delivery',
			LBITE_PLUGIN_URL . 'assets/js/delivery__premium_only.js',
			array( 'jquery' ),
			LBITE_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Auswahl «Abholung / Lieferung» mit Adressfeldern ausgeben.
	 */
	public function render_checkout_block() {
		$location_id = self::get_delivery_location_id();
		if ( ! $location_id ) {
			return;
		}

		$s        = self::get_settings( $location_id );
		$selected = 'delivery' === WC()->session->get( 'lbite_fulfilment' ) ? 'delivery' : 'pickup';
		$a        = self::get_session_address();
		?>
		<div id="lbite-fulfilment" class="lbite-fulfilment">
			<p class="lbite-fulfilment__label"><?php esc_html_e( 'Pickup or delivery?', 'libre-bite' ); ?> <span class="required">*</span></p>
			<div class="lbite-fulfilment__options">
				<label class="lbite-fulfilment__option">
					<input type="radio" name="lbite_fulfilment" value="pickup" <?php checked( 'pickup', $selected ); ?>>
					<span><?php esc_html_e( 'Pickup', 'libre-bite' ); ?></span>
				</label>
				<label class="lbite-fulfilment__option">
					<input type="radio" name="lbite_fulfilment" value="delivery" <?php checked( 'delivery', $selected ); ?>>
					<span><?php esc_html_e( 'Delivery', 'libre-bite' ); ?></span>
				</label>
			</div>

			<div class="lbite-fulfilment__delivery" <?php echo 'delivery' === $selected ? '' : 'hidden'; ?>>
				<?php if ( $s['min_total'] > 0 || $s['fee'] > 0 || $s['lead_time'] > 0 ) : ?>
					<p class="lbite-fulfilment__info">
						<?php
						$lbite_parts = array();
						if ( $s['fee'] > 0 ) {
							/* translators: %s: delivery fee incl. currency. */
							$lbite_parts[] = sprintf( __( 'Delivery fee %s', 'libre-bite' ), wp_strip_all_tags( wc_price( $s['fee'] ) ) );
						}
						if ( $s['free_from'] > 0 ) {
							/* translators: %s: amount incl. currency. */
							$lbite_parts[] = sprintf( __( 'free from %s', 'libre-bite' ), wp_strip_all_tags( wc_price( $s['free_from'] ) ) );
						}
						if ( $s['min_total'] > 0 ) {
							/* translators: %s: amount incl. currency. */
							$lbite_parts[] = sprintf( __( 'minimum order %s', 'libre-bite' ), wp_strip_all_tags( wc_price( $s['min_total'] ) ) );
						}
						if ( $s['lead_time'] > 0 ) {
							/* translators: %d: minutes. */
							$lbite_parts[] = sprintf( __( 'about %d minutes longer than pickup', 'libre-bite' ), $s['lead_time'] );
						}
						echo esc_html( implode( ' · ', $lbite_parts ) );
						?>
					</p>
				<?php endif; ?>

				<p class="form-row form-row-wide">
					<label for="lbite_delivery_address"><?php esc_html_e( 'Street and number', 'libre-bite' ); ?> <span class="required">*</span></label>
					<input type="text" class="input-text" id="lbite_delivery_address" name="lbite_delivery_address" autocomplete="street-address" value="<?php echo esc_attr( $a['address'] ); ?>">
				</p>
				<p class="form-row form-row-first">
					<label for="lbite_delivery_postcode"><?php esc_html_e( 'Postcode', 'libre-bite' ); ?> <span class="required">*</span></label>
					<input type="text" class="input-text" id="lbite_delivery_postcode" name="lbite_delivery_postcode" autocomplete="postal-code" inputmode="numeric" value="<?php echo esc_attr( $a['postcode'] ); ?>">
				</p>
				<p class="form-row form-row-last">
					<label for="lbite_delivery_city"><?php esc_html_e( 'City', 'libre-bite' ); ?> <span class="required">*</span></label>
					<input type="text" class="input-text" id="lbite_delivery_city" name="lbite_delivery_city" autocomplete="address-level2" value="<?php echo esc_attr( $a['city'] ); ?>">
				</p>
				<p class="form-row form-row-wide">
					<label for="lbite_delivery_phone"><?php esc_html_e( 'Phone for the driver', 'libre-bite' ); ?> <span class="required">*</span></label>
					<input type="tel" class="input-text" id="lbite_delivery_phone" name="lbite_delivery_phone" autocomplete="tel" value="<?php echo esc_attr( $a['phone'] ); ?>">
				</p>
				<p class="form-row form-row-wide">
					<label for="lbite_delivery_notes"><?php esc_html_e( 'Delivery notes (floor, doorbell …)', 'libre-bite' ); ?></label>
					<input type="text" class="input-text" id="lbite_delivery_notes" name="lbite_delivery_notes" value="<?php echo esc_attr( $a['notes'] ); ?>">
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Auswahl bei jedem update_order_review in die Session übernehmen,
	 * damit die Gebühr sofort im Bestellüberblick erscheint.
	 *
	 * @param string $post_data URL-kodierte Formulardaten.
	 */
	public function save_to_session( $post_data ) {
		if ( ! WC()->session ) {
			return;
		}

		$parsed = array();
		parse_str( (string) $post_data, $parsed );

		$fulfilment = isset( $parsed['lbite_fulfilment'] ) && 'delivery' === $parsed['lbite_fulfilment'] ? 'delivery' : 'pickup';
		WC()->session->set( 'lbite_fulfilment', $fulfilment );

		// Lieferung gilt als Mitnahme: Steuersatz der Artikel und der Gebühr
		// (Schweizer MWST) richten sich nach diesem Session-Wert.
		if ( 'delivery' === $fulfilment ) {
			WC()->session->set( 'lbite_service_type', 'takeaway' );
		}

		$address = array();
		foreach ( array( 'address', 'postcode', 'city', 'phone', 'notes' ) as $key ) {
			$address[ $key ] = isset( $parsed[ 'lbite_delivery_' . $key ] ) ? sanitize_text_field( wp_unslash( $parsed[ 'lbite_delivery_' . $key ] ) ) : '';
		}
		WC()->session->set( 'lbite_delivery', $address );
	}

	/**
	 * Liefergebühr als Gebühr in den Warenkorb.
	 *
	 * @param WC_Cart $cart Warenkorb.
	 */
	public function add_delivery_fee( $cart ) {
		if ( ! self::is_delivery_selected() ) {
			return;
		}

		$s   = self::get_settings( self::get_delivery_location_id() );
		$fee = self::calculate_fee( (float) $cart->get_subtotal() + (float) $cart->get_subtotal_tax(), $s['fee'], $s['free_from'] );

		if ( $fee <= 0 ) {
			return;
		}

		// Lieferung gilt steuerlich als Mitnahme (Schweiz: reduzierter Satz).
		$tax_class = lbite_feature_enabled( 'enable_swiss_vat' ) ? (string) get_option( 'lbite_tax_class_takeaway', '' ) : '';
		$taxable   = wc_tax_enabled();

		// WooCommerce-Gebühren sind immer netto. Bei Bruttopreisen (Schweiz
		// üblich) soll der Gast aber genau den angekündigten Betrag zahlen –
		// sonst würden aus «CHF 5.00» z. B. CHF 5.13. Darum die enthaltene
		// Steuer vorher herausrechnen.
		if ( $taxable && wc_prices_include_tax() ) {
			$taxes = WC_Tax::calc_tax( $fee, WC_Tax::get_rates( $tax_class ), true );
			$fee   = $fee - array_sum( $taxes );
		}

		$cart->add_fee( __( 'Delivery fee', 'libre-bite' ), $fee, $taxable, $tax_class );
	}

	/**
	 * Liefergebühr in der Bestellung kennzeichnen.
	 *
	 * Die Statistik erkennt sie so ohne Vergleich mit dem übersetzten
	 * Gebührennamen und zählt sie nicht als Add-on.
	 *
	 * @param WC_Order_Item_Fee $item    Gebührenposition.
	 * @param string            $fee_key Schlüssel der Gebühr im Warenkorb.
	 * @param object            $fee     Gebühr aus dem Warenkorb.
	 * @param WC_Order          $order   Bestellung.
	 */
	public function mark_fee_item( $item, $fee_key, $fee, $order ) {
		unset( $fee_key, $order );
		if ( isset( $fee->name ) && __( 'Delivery fee', 'libre-bite' ) === $fee->name ) {
			$item->add_meta_data( '_lbite_delivery_fee', '1', true );
		}
	}

	/**
	 * Lieferangaben beim Absenden prüfen.
	 */
	public function validate_checkout() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce prüft WooCommerce beim Absenden des Checkouts.
		$fulfilment = isset( $_POST['lbite_fulfilment'] ) ? sanitize_key( wp_unslash( $_POST['lbite_fulfilment'] ) ) : 'pickup';
		if ( 'delivery' !== $fulfilment ) {
			return;
		}

		$location_id = self::get_delivery_location_id();
		if ( ! $location_id ) {
			wc_add_notice( __( 'Delivery is not available from the selected location.', 'libre-bite' ), 'error' );
			return;
		}

		$s = self::get_settings( $location_id );
		$v = array();
		foreach ( array( 'address', 'postcode', 'city', 'phone' ) as $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce prüft WooCommerce beim Absenden des Checkouts.
			$v[ $key ] = isset( $_POST[ 'lbite_delivery_' . $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'lbite_delivery_' . $key ] ) ) : '';
		}

		if ( in_array( '', $v, true ) ) {
			wc_add_notice( __( 'Please enter the complete delivery address and a phone number.', 'libre-bite' ), 'error' );
			return;
		}

		if ( ! self::postcode_matches( $v['postcode'], $s['postcodes'] ) ) {
			/* translators: %s: postcode. */
			wc_add_notice( sprintf( __( 'Unfortunately we do not deliver to %s. You are welcome to pick up your order.', 'libre-bite' ), $v['postcode'] ), 'error' );
			return;
		}

		$goods = (float) WC()->cart->get_subtotal() + (float) WC()->cart->get_subtotal_tax();
		if ( $s['min_total'] > 0 && $goods < $s['min_total'] ) {
			/* translators: %s: minimum order value incl. currency. */
			wc_add_notice( sprintf( __( 'The minimum order value for delivery is %s.', 'libre-bite' ), wp_strip_all_tags( wc_price( $s['min_total'] ) ) ), 'error' );
			return;
		}

		// Vorbestellung: gewählte Zeit muss die zusätzliche Vorlaufzeit erlauben.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce prüft WooCommerce beim Absenden des Checkouts.
		$order_type = isset( $_POST['lbite_order_type'] ) ? sanitize_key( wp_unslash( $_POST['lbite_order_type'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce prüft WooCommerce beim Absenden des Checkouts.
		$pickup_time = isset( $_POST['lbite_pickup_time'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_pickup_time'] ) ) : '';
		if ( 'later' === $order_type && '' !== $pickup_time && $s['lead_time'] > 0 && function_exists( 'lbite_local_time_to_timestamp' ) ) {
			$prep     = LBite_Locations::get_time_setting( $location_id, 'preparation_time', 30 );
			$earliest = time() + ( $prep + $s['lead_time'] ) * MINUTE_IN_SECONDS;
			$chosen   = lbite_local_time_to_timestamp( $pickup_time );
			if ( $chosen > 0 && $chosen < $earliest ) {
				/* translators: %s: earliest delivery time. */
				wc_add_notice( sprintf( __( 'The earliest possible delivery time is %s. Please choose a later time.', 'libre-bite' ), wp_date( get_option( 'time_format' ), $earliest ) ), 'error' );
			}
		}
	}

	/**
	 * Lieferung in der Bestellung festhalten.
	 *
	 * @param WC_Order $order Bestellung.
	 * @param array    $data  Checkout-Daten.
	 */
	public function save_to_order( $order, $data ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce prüft WooCommerce beim Absenden des Checkouts.
		$fulfilment = isset( $_POST['lbite_fulfilment'] ) ? sanitize_key( wp_unslash( $_POST['lbite_fulfilment'] ) ) : 'pickup';
		if ( 'delivery' !== $fulfilment || ! self::get_delivery_location_id() ) {
			return;
		}

		$v = array();
		foreach ( array( 'address', 'postcode', 'city', 'phone', 'notes' ) as $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce prüft WooCommerce beim Absenden des Checkouts.
			$v[ $key ] = isset( $_POST[ 'lbite_delivery_' . $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'lbite_delivery_' . $key ] ) ) : '';
		}

		$order->set_shipping_first_name( $order->get_billing_first_name() );
		$order->set_shipping_last_name( $order->get_billing_last_name() );
		$order->set_shipping_address_1( $v['address'] );
		$order->set_shipping_postcode( $v['postcode'] );
		$order->set_shipping_city( $v['city'] );
		$order->set_shipping_country( WC()->countries->get_base_country() );
		if ( method_exists( $order, 'set_shipping_phone' ) ) {
			$order->set_shipping_phone( $v['phone'] );
		}
		if ( ! $order->get_billing_phone() ) {
			$order->set_billing_phone( $v['phone'] );
		}

		$order->update_meta_data( '_lbite_fulfilment', 'delivery' );
		if ( '' !== $v['notes'] ) {
			$order->update_meta_data( '_lbite_delivery_notes', $v['notes'] );
		}
		// Lieferung gilt als Mitnahme (Steuer, Bon, Statistik).
		$order->update_meta_data( '_lbite_service_type', 'takeaway' );
	}

	/**
	 * Lieferangaben für die Kanban-Karte.
	 *
	 * @param array    $data  Kartendaten.
	 * @param WC_Order $order Bestellung.
	 * @return array
	 */
	public function add_to_dashboard_data( $data, $order ) {
		if ( ! $order instanceof WC_Order || 'delivery' !== $order->get_meta( '_lbite_fulfilment' ) ) {
			return $data;
		}

		$data['delivery'] = array(
			'label'   => __( 'Delivery', 'libre-bite' ),
			'address' => trim( $order->get_shipping_address_1() . ', ' . $order->get_shipping_postcode() . ' ' . $order->get_shipping_city(), ', ' ),
			'phone'   => method_exists( $order, 'get_shipping_phone' ) && $order->get_shipping_phone() ? $order->get_shipping_phone() : $order->get_billing_phone(),
			'notes'   => (string) $order->get_meta( '_lbite_delivery_notes' ),
		);

		return $data;
	}
}
