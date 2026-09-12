<?php
/**
 * Bon-Vorlagen (Küchenticket, Kundenbon, Lieferschein)
 *
 * Ersetzt die bisherige Notlösung, die schlicht das HTML der Kanban-Karte
 * samt Bedienknöpfen in ein 300×600-Popup schob. Stattdessen echte
 * 80-mm-Vorlagen mit pro Typ konfigurierbarem Inhalt.
 *
 * Bewusst drei getrennte Typen: die Küche braucht Positionen, Notizen und
 * Allergene, aber keine Preise; der Gast braucht Preise und Steuern, aber
 * keine Zubereitungsnotizen; der Fahrer braucht Adresse und Telefonnummer.
 *
 * Der erzeugte Beleg ist bewusst eigenständiges HTML mit eingebettetem CSS
 * und ohne externe Abhängigkeiten – er wird in ein Druckfenster geschrieben,
 * in dem keine Stylesheets der Seite zur Verfügung stehen.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse LBite_Receipts
 */
class LBite_Receipts {

	/**
	 * Option mit der Feldkonfiguration je Bontyp.
	 */
	const OPTION_FIELDS = 'lbite_receipt_fields';

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
		$this->loader->add_action( 'wp_ajax_lbite_get_receipt', $this, 'ajax_get_receipt' );
	}

	/**
	 * Verfügbare Bontypen
	 *
	 * @return array Schlüssel => Beschriftung.
	 */
	public static function get_types() {
		return array(
			'kitchen'  => __( 'Kitchen ticket', 'libre-bite' ),
			'customer' => __( 'Customer receipt', 'libre-bite' ),
			'delivery' => __( 'Delivery note', 'libre-bite' ),
		);
	}

	/**
	 * Konfigurierbare Felder mit ihren Standardwerten je Bontyp
	 *
	 * @return array Feld => array( Beschriftung, Standard je Typ ).
	 */
	public static function get_field_definitions() {
		return array(
			'order_number' => array(
				'label'    => __( 'Order number', 'libre-bite' ),
				'defaults' => array( 'kitchen' => true, 'customer' => true, 'delivery' => true ),
			),
			'order_time'   => array(
				'label'    => __( 'Order time', 'libre-bite' ),
				'defaults' => array( 'kitchen' => true, 'customer' => true, 'delivery' => true ),
			),
			'pickup_time'  => array(
				'label'    => __( 'Pickup / delivery time', 'libre-bite' ),
				'defaults' => array( 'kitchen' => true, 'customer' => true, 'delivery' => true ),
			),
			'location'     => array(
				'label'    => __( 'Location', 'libre-bite' ),
				'defaults' => array( 'kitchen' => false, 'customer' => true, 'delivery' => true ),
			),
			'table'        => array(
				'label'    => __( 'Table', 'libre-bite' ),
				'defaults' => array( 'kitchen' => true, 'customer' => true, 'delivery' => false ),
			),
			'customer'     => array(
				'label'    => __( 'Customer name', 'libre-bite' ),
				'defaults' => array( 'kitchen' => false, 'customer' => true, 'delivery' => true ),
			),
			'phone'        => array(
				'label'    => __( 'Phone number', 'libre-bite' ),
				'defaults' => array( 'kitchen' => false, 'customer' => false, 'delivery' => true ),
			),
			'address'      => array(
				'label'    => __( 'Address', 'libre-bite' ),
				'defaults' => array( 'kitchen' => false, 'customer' => false, 'delivery' => true ),
			),
			'items'        => array(
				'label'    => __( 'Line items', 'libre-bite' ),
				'defaults' => array( 'kitchen' => true, 'customer' => true, 'delivery' => true ),
			),
			'prices'       => array(
				'label'    => __( 'Prices and total', 'libre-bite' ),
				'defaults' => array( 'kitchen' => false, 'customer' => true, 'delivery' => true ),
			),
			'allergens'    => array(
				'label'    => __( 'Allergens', 'libre-bite' ),
				'defaults' => array( 'kitchen' => true, 'customer' => false, 'delivery' => false ),
			),
			'notes'        => array(
				'label'    => __( 'Customer note', 'libre-bite' ),
				'defaults' => array( 'kitchen' => true, 'customer' => true, 'delivery' => true ),
			),
			'payment'      => array(
				'label'    => __( 'Payment method', 'libre-bite' ),
				'defaults' => array( 'kitchen' => false, 'customer' => true, 'delivery' => true ),
			),
		);
	}

	/**
	 * Gespeicherte Feldkonfiguration lesen
	 *
	 * @param string $type Bontyp.
	 * @return array Feld => bool.
	 */
	public static function get_fields( $type ) {
		$definitions = self::get_field_definitions();
		$stored      = get_option( self::OPTION_FIELDS, array() );
		$stored      = is_array( $stored ) && isset( $stored[ $type ] ) && is_array( $stored[ $type ] )
			? $stored[ $type ]
			: null;

		$fields = array();

		foreach ( $definitions as $key => $definition ) {
			if ( null === $stored ) {
				$fields[ $key ] = ! empty( $definition['defaults'][ $type ] );
			} else {
				$fields[ $key ] = ! empty( $stored[ $key ] );
			}
		}

		return $fields;
	}

	/**
	 * Feldkonfiguration bereinigen
	 *
	 * @param mixed $raw Rohwert aus dem Formular.
	 * @return array
	 */
	public static function sanitize_fields( $raw ) {
		$types  = array_keys( self::get_types() );
		$keys   = array_keys( self::get_field_definitions() );
		$result = array();

		foreach ( $types as $type ) {
			foreach ( $keys as $key ) {
				$result[ $type ][ $key ] = isset( $raw[ $type ][ $key ] ) && $raw[ $type ][ $key ];
			}
		}

		return $result;
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Endpunkt
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * AJAX: Beleg-HTML für eine Bestellung liefern
	 */
	public function ajax_get_receipt() {
		check_ajax_referer( 'lbite_dashboard_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_view_orders' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
		$type     = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'kitchen';

		if ( ! array_key_exists( $type, self::get_types() ) ) {
			$type = 'kitchen';
		}

		$order = $order_id ? wc_get_order( $order_id ) : false;

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'libre-bite' ) ) );
		}

		wp_send_json_success(
			array(
				'html'  => self::render( $order, $type ),
				'title' => sprintf(
					/* translators: 1: receipt type, 2: order number */
					__( '%1$s – Order %2$s', 'libre-bite' ),
					self::get_types()[ $type ],
					$order->get_order_number()
				),
			)
		);
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Darstellung
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Vollständiges Beleg-Dokument erzeugen
	 *
	 * @param WC_Order $order Bestellung.
	 * @param string   $type  Bontyp.
	 * @return string Eigenständiges HTML-Dokument.
	 */
	public static function render( $order, $type ) {
		$fields = self::get_fields( $type );

		return '<!DOCTYPE html><html><head><meta charset="utf-8">'
			. '<title>' . esc_html( self::get_types()[ $type ] ) . '</title>'
			. '<style>' . self::get_styles() . '</style>'
			. '</head><body class="lbite-receipt lbite-receipt--' . esc_attr( $type ) . '">'
			. self::render_body( $order, $type, $fields )
			. '</body></html>';
	}

	/**
	 * Beleginhalt erzeugen
	 *
	 * @param WC_Order $order  Bestellung.
	 * @param string   $type   Bontyp.
	 * @param array    $fields Aktive Felder.
	 * @return string
	 */
	private static function render_body( $order, $type, $fields ) {
		$out = '';

		// Kopf
		$brand = get_option( 'lbite_brand_name', '' );
		if ( '' === $brand ) {
			$brand = get_bloginfo( 'name' );
		}

		$out .= '<header class="r-head">';
		$out .= '<div class="r-brand">' . esc_html( $brand ) . '</div>';
		$out .= '<div class="r-type">' . esc_html( self::get_types()[ $type ] ) . '</div>';
		$out .= '</header>';

		// Kopfdaten
		$rows = array();

		if ( $fields['order_number'] ) {
			$rows[] = array( __( 'Order', 'libre-bite' ), '#' . $order->get_order_number() );
		}

		if ( $fields['order_time'] ) {
			$created = $order->get_date_created();
			$rows[]  = array( __( 'Received', 'libre-bite' ), $created ? $created->date_i18n( 'd.m.Y H:i' ) : '' );
		}

		if ( $fields['pickup_time'] ) {
			$pickup = (string) $order->get_meta( '_lbite_pickup_time', true );
			if ( '' !== $pickup ) {
				$rows[] = array( __( 'For', 'libre-bite' ), str_replace( 'T', ' ', $pickup ) );
			}
		}

		if ( $fields['location'] ) {
			$location_id = (int) $order->get_meta( '_lbite_location_id', true );
			if ( $location_id ) {
				$rows[] = array( __( 'Location', 'libre-bite' ), get_the_title( $location_id ) );
			}
		}

		if ( $fields['table'] ) {
			$table = (string) $order->get_meta( '_lbite_table_name', true );
			if ( '' === $table ) {
				$table = (string) $order->get_meta( '_lbite_checkout_table_number', true );
			}
			if ( '' !== $table ) {
				$rows[] = array( __( 'Table', 'libre-bite' ), $table );
			}
		}

		if ( $fields['customer'] ) {
			$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
			if ( '' !== $name ) {
				$rows[] = array( __( 'Customer', 'libre-bite' ), $name );
			}
		}

		if ( $fields['phone'] && $order->get_billing_phone() ) {
			$rows[] = array( __( 'Phone', 'libre-bite' ), $order->get_billing_phone() );
		}

		if ( $fields['address'] ) {
			// get_formatted_billing_address() trennt die Zeilen mit <br/>.
			// wp_strip_all_tags() würde daraus eine einzige Zeile machen –
			// die Zeilenumbrüche müssen vorher gerettet werden.
			$address = str_replace( array( '<br/>', '<br />', '<br>' ), "\n", $order->get_formatted_billing_address() );
			$address = trim( wp_strip_all_tags( $address ) );
			if ( '' !== $address ) {
				$rows[] = array( __( 'Address', 'libre-bite' ), $address );
			}
		}

		if ( $fields['payment'] ) {
			$payment = self::get_payment_label( $order );
			if ( '' !== $payment ) {
				$rows[] = array( __( 'Payment', 'libre-bite' ), $payment );
			}
		}

		if ( $rows ) {
			$out .= '<table class="r-meta">';
			foreach ( $rows as $row ) {
				$out .= '<tr><th>' . esc_html( $row[0] ) . '</th><td>' . nl2br( esc_html( $row[1] ) ) . '</td></tr>';
			}
			$out .= '</table>';
		}

		// Positionen
		if ( $fields['items'] ) {
			$out .= self::render_items( $order, $fields );
		}

		// Summen
		if ( $fields['prices'] ) {
			$out .= '<table class="r-totals">';
			foreach ( $order->get_order_item_totals() as $total ) {
				$out .= '<tr><th>' . esc_html( wp_strip_all_tags( $total['label'] ) ) . '</th>'
					. '<td>' . esc_html( wp_strip_all_tags( $total['value'] ) ) . '</td></tr>';
			}
			$out .= '</table>';
		}

		// Allergene (nur sinnvoll, wenn das Modul aktiv ist)
		if ( $fields['allergens'] ) {
			$allergens = self::collect_allergens( $order );
			if ( $allergens ) {
				$out .= '<div class="r-block"><strong>' . esc_html__( 'Allergens', 'libre-bite' ) . '</strong><br>'
					. esc_html( implode( ', ', $allergens ) ) . '</div>';
			}
		}

		// Kundennotiz
		if ( $fields['notes'] && $order->get_customer_note() ) {
			$out .= '<div class="r-block r-note"><strong>' . esc_html__( 'Note', 'libre-bite' ) . '</strong><br>'
				. nl2br( esc_html( $order->get_customer_note() ) ) . '</div>';
		}

		$out .= '<footer class="r-foot">' . esc_html( wp_date( 'd.m.Y H:i' ) ) . '</footer>';

		return $out;
	}

	/**
	 * Positionsliste erzeugen
	 *
	 * Runden aus offenen Tabs werden mit einer Trennlinie abgesetzt, damit in
	 * der Küche erkennbar bleibt, was neu dazugekommen ist.
	 *
	 * @param WC_Order $order  Bestellung.
	 * @param array    $fields Aktive Felder.
	 * @return string
	 */
	private static function render_items( $order, $fields ) {
		$out        = '<table class="r-items">';
		$last_round = null;

		foreach ( $order->get_items() as $item ) {
			$round = (int) $item->get_meta( '_lbite_tab_round', true );

			if ( null !== $last_round && $round !== $last_round ) {
				$out .= '<tr class="r-round"><td colspan="3">'
					. esc_html(
						sprintf(
							/* translators: %d: round number */
							__( 'Round %d', 'libre-bite' ),
							$round
						)
					)
					. '</td></tr>';
			}
			$last_round = $round;

			$out .= '<tr>';
			$out .= '<td class="r-qty">' . esc_html( $item->get_quantity() ) . '&times;</td>';
			$out .= '<td class="r-name">' . esc_html( $item->get_name() );

			// Gewählte Optionen als Unterzeile – für die Küche der wichtigste Teil.
			foreach ( $item->get_meta_data() as $meta ) {
				$key = (string) $meta->key;

				if ( '' === $key || '_' === $key[0] ) {
					continue;
				}

				$value = is_scalar( $meta->value ) ? (string) $meta->value : '';

				if ( '' === $value ) {
					continue;
				}

				$out .= '<div class="r-opt">' . esc_html( $key ) . ': ' . esc_html( $value ) . '</div>';
			}

			$out .= '</td>';

			if ( $fields['prices'] ) {
				$out .= '<td class="r-price">' . esc_html( wp_strip_all_tags( wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) ) ) ) . '</td>';
			} else {
				$out .= '<td class="r-price"></td>';
			}

			$out .= '</tr>';
		}

		$out .= '</table>';

		return $out;
	}

	/**
	 * Allergene aller Positionen sammeln
	 *
	 * @param WC_Order $order Bestellung.
	 * @return string[]
	 */
	private static function collect_allergens( $order ) {
		$found = array();

		foreach ( $order->get_items() as $item ) {
			$product_id = (int) $item->get_product_id();

			if ( ! $product_id ) {
				continue;
			}

			$allergens = get_post_meta( $product_id, '_lbite_allergens', true );

			if ( ! is_array( $allergens ) ) {
				continue;
			}

			foreach ( $allergens as $allergen ) {
				$found[ (string) $allergen ] = true;
			}
		}

		return array_keys( $found );
	}

	/**
	 * Zahlungsart als Text
	 *
	 * @param WC_Order $order Bestellung.
	 * @return string
	 */
	private static function get_payment_label( $order ) {
		$method = (string) $order->get_meta( '_lbite_payment_method', true );

		if ( 'split' === $method ) {
			$splits = $order->get_meta( '_lbite_split_payments', true );
			$parts  = array();

			if ( is_array( $splits ) ) {
				foreach ( $splits as $split ) {
					if ( ! isset( $split['method'], $split['amount'] ) ) {
						continue;
					}
					$parts[] = $split['method'] . ' ' . number_format_i18n( (float) $split['amount'], 2 );
				}
			}

			return implode( ', ', $parts );
		}

		if ( '' !== $method ) {
			return $method;
		}

		return $order->get_payment_method_title();
	}

	/**
	 * Druck-Stylesheet für 80-mm-Bonrollen
	 *
	 * Eingebettet statt verlinkt: im Druckfenster steht kein Stylesheet der
	 * Seite zur Verfügung, und ein externer Ladevorgang würde den
	 * Druckdialog verzögern oder leer drucken.
	 *
	 * @return string
	 */
	private static function get_styles() {
		return '
@page { size: 80mm auto; margin: 0; }
* { box-sizing: border-box; }
body.lbite-receipt {
	width: 72mm; margin: 0 auto; padding: 4mm 0;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
	font-size: 12px; line-height: 1.35; color: #000; background: #fff;
}
.r-head { text-align: center; margin-bottom: 3mm; }
.r-brand { font-size: 15px; font-weight: 700; }
.r-type { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; margin-top: 1mm; }
.r-meta { width: 100%; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 2mm 0; margin-bottom: 2mm; }
.r-meta th { text-align: left; font-weight: 400; width: 34%; vertical-align: top; padding: .4mm 0; }
.r-meta td { text-align: right; font-weight: 700; padding: .4mm 0; }
.r-items { width: 100%; margin-bottom: 2mm; }
.r-items td { vertical-align: top; padding: 1mm 0; border-bottom: 1px solid #eee; }
.r-qty { width: 9mm; font-weight: 700; }
.r-name { font-weight: 700; }
.r-price { width: 18mm; text-align: right; white-space: nowrap; }
.r-opt { font-weight: 400; font-size: 11px; padding-left: 2mm; }
.r-round td { border-bottom: none; border-top: 1px dashed #000; padding-top: 2mm; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; }
.r-totals { width: 100%; border-top: 1px dashed #000; padding-top: 2mm; }
.r-totals th { text-align: left; font-weight: 400; padding: .4mm 0; }
.r-totals td { text-align: right; font-weight: 700; padding: .4mm 0; }
.r-totals tr:last-child th, .r-totals tr:last-child td { font-size: 14px; font-weight: 700; padding-top: 1mm; }
.r-block { border-top: 1px dashed #000; padding-top: 2mm; margin-top: 2mm; }
.r-note { font-size: 13px; }
.r-foot { text-align: center; font-size: 10px; margin-top: 4mm; }
/* Küchenticket bewusst grösser: es wird im Vorbeigehen gelesen. */
.lbite-receipt--kitchen { font-size: 14px; }
.lbite-receipt--kitchen .r-name { font-size: 16px; }
.lbite-receipt--kitchen .r-qty { font-size: 16px; }
@media print { body.lbite-receipt { padding: 0; } }
';
	}
}
