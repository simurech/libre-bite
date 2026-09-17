<?php
/**
 * Bestell-Dashboard (Kanban-Board)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order-Dashboard-Modul
 */
class LBite_Order_Dashboard {

	/**
	 * Spalten-Schlüssel der Vorbestellungen-Spalte (fix, nur sichtbar wenn
	 * enable_scheduled_orders aktiv ist).
	 */
	const KEY_PREORDER = 'incoming';

	/**
	 * Spalten-Schlüssel der Sofort/Eingang-Spalte (fix, immer vorhanden).
	 */
	const KEY_ACTIVE = 'preparing';

	/**
	 * Loader-Instanz
	 *
	 * @var LBite_Loader
	 */
	private $loader;

	/**
	 * Standard-Spaltenkonfiguration (Fallback wenn Feature aus oder keine Custom-Config)
	 *
	 * @return array
	 */
	private static function get_default_columns() {
		return array(
			array(
				'key'                 => self::KEY_PREORDER,
				'label'               => __( 'Pre-orders', 'libre-bite' ),
				'counts_as_completed' => false,
			),
			array(
				'key'                 => self::KEY_ACTIVE,
				'label'               => __( 'Prepare Now', 'libre-bite' ),
				'counts_as_completed' => false,
			),
			array(
				'key'                 => 'completed',
				'label'               => __( 'Completed', 'libre-bite' ),
				'counts_as_completed' => true,
			),
		);
	}

	/**
	 * Aktuell gültige Kanban-Spaltenkonfiguration abrufen
	 *
	 * @return array
	 */
	public static function get_columns() {
		if ( ! lbite_feature_enabled( 'enable_kanban_customization' ) ) {
			$columns = self::get_default_columns();
		} else {
			$stored  = get_option( 'lbite_kanban_columns', array() );
			$columns = self::sanitize_columns_input( is_array( $stored ) ? $stored : array() );

			if ( empty( $columns ) ) {
				$columns = self::get_default_columns();
			}
		}

		return self::enforce_fixed_columns( $columns );
	}

	/**
	 * Garantiert die beiden fixen Spalten unabhängig von Kanban-Anpassung oder
	 * gespeicherter Konfiguration: «Sofort» (KEY_ACTIVE) ist immer vorhanden,
	 * «Vorbestellungen» (KEY_PREORDER) nur wenn enable_scheduled_orders aktiv ist.
	 * Beide werden - falls vorhanden - an den Anfang gestellt (Vorbestellungen zuerst),
	 * mit ihrem zuletzt gespeicherten Label (falls vorhanden), aber nie als
	 * «abgeschlossen» markiert. Fehlt am Ende eine «abgeschlossen»-Spalte
	 * (z. B. weil die eigenen Spalten noch keine hatten), wird eine Standard-Spalte
	 * angehängt statt eine der fixen Spalten umzuflaggen.
	 *
	 * @param array $columns Spaltenliste (Standard oder aus sanitize_columns_input()).
	 * @return array
	 */
	private static function enforce_fixed_columns( $columns ) {
		$by_key = array();
		foreach ( $columns as $col ) {
			$by_key[ $col['key'] ] = $col;
		}

		$fixed = array();

		if ( lbite_feature_enabled( 'enable_scheduled_orders' ) ) {
			$fixed[] = isset( $by_key[ self::KEY_PREORDER ] )
				? array_merge( $by_key[ self::KEY_PREORDER ], array( 'counts_as_completed' => false ) )
				: array(
					'key'                 => self::KEY_PREORDER,
					'label'               => __( 'Pre-orders', 'libre-bite' ),
					'counts_as_completed' => false,
				);
		}

		$fixed[] = isset( $by_key[ self::KEY_ACTIVE ] )
			? array_merge( $by_key[ self::KEY_ACTIVE ], array( 'counts_as_completed' => false ) )
			: array(
				'key'                 => self::KEY_ACTIVE,
				'label'               => __( 'Prepare Now', 'libre-bite' ),
				'counts_as_completed' => false,
			);

		$rest = array();
		foreach ( $columns as $col ) {
			if ( self::KEY_PREORDER === $col['key'] || self::KEY_ACTIVE === $col['key'] ) {
				continue;
			}
			$rest[] = $col;
		}

		$result = array_merge( $fixed, $rest );

		$has_completed = false;
		foreach ( $result as $col ) {
			if ( ! empty( $col['counts_as_completed'] ) ) {
				$has_completed = true;
				break;
			}
		}
		if ( ! $has_completed ) {
			$result[] = array(
				'key'                 => 'completed',
				'label'               => __( 'Completed', 'libre-bite' ),
				'counts_as_completed' => true,
			);
		}

		return $result;
	}

	/**
	 * Spaltenkonfiguration als Key-indizierte Map abrufen
	 *
	 * @return array
	 */
	public static function get_columns_by_key() {
		$map = array();
		foreach ( self::get_columns() as $col ) {
			$map[ $col['key'] ] = $col;
		}
		return $map;
	}

	/**
	 * Rohe Spaltendaten (aus Option oder $_POST) validieren und normalisieren
	 *
	 * @param array $raw Rohe Spaltendaten
	 * @return array Bereinigte Spaltenliste, leeres Array wenn ungültig
	 */
	public static function sanitize_columns_input( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$used_keys = array();
		$columns   = array();

		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$label = isset( $row['label'] ) ? sanitize_text_field( wp_unslash( $row['label'] ) ) : '';
			if ( '' === $label ) {
				continue;
			}

			$key = isset( $row['key'] ) ? sanitize_key( wp_unslash( $row['key'] ) ) : '';
			if ( '' === $key || isset( $used_keys[ $key ] ) ) {
				do {
					$key = 'col_' . substr( md5( $label . wp_generate_password( 8, false ) ), 0, 8 );
				} while ( isset( $used_keys[ $key ] ) );
			}

			$used_keys[ $key ] = true;
			$columns[]         = array(
				'key'                 => $key,
				'label'               => $label,
				'counts_as_completed' => ! empty( $row['counts_as_completed'] ),
			);

			if ( count( $columns ) >= 5 ) {
				break;
			}
		}

		if ( count( $columns ) < 2 ) {
			return array();
		}

		// Die beiden fixen Spalten dürfen nie als «abgeschlossen» zählen, auch nicht
		// über einen manipulierten POST - enforce_fixed_columns() erzwingt das beim
		// Lesen zwar ohnehin, aber der automatische Fallback unten darf sich nicht
		// versehentlich an einer fixen Spalte «bedienen».
		foreach ( $columns as &$c ) {
			if ( self::KEY_PREORDER === $c['key'] || self::KEY_ACTIVE === $c['key'] ) {
				$c['counts_as_completed'] = false;
			}
		}
		unset( $c );

		$has_completed = false;
		foreach ( $columns as $c ) {
			if ( $c['counts_as_completed'] ) {
				$has_completed = true;
				break;
			}
		}
		if ( ! $has_completed ) {
			for ( $i = count( $columns ) - 1; $i >= 0; $i-- ) {
				if ( self::KEY_PREORDER !== $columns[ $i ]['key'] && self::KEY_ACTIVE !== $columns[ $i ]['key'] ) {
					$columns[ $i ]['counts_as_completed'] = true;
					$has_completed                        = true;
					break;
				}
			}
			if ( ! $has_completed ) {
				$columns[] = array(
					'key'                 => 'completed',
					'label'               => __( 'Completed', 'libre-bite' ),
					'counts_as_completed' => true,
				);
			}
		}

		return $columns;
	}

	/**
	 * Anzahl eingehender Bestellungen für Menü-Badge zurückgeben (gecacht)
	 *
	 * @return int
	 */
	public static function get_incoming_orders_count() {
		$cached = get_transient( 'lbite_incoming_orders_count' );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		// Zählt Bestellungen, die gerade Aufmerksamkeit brauchen (Sofort/Eingang) - nicht
		// die Vorbestellungen-Spalte, deren Inhalt bewusst noch nicht dringend ist.
		$default_status_key = self::KEY_ACTIVE;

		$order_ids = wc_get_orders( array(
			'limit'  => 500,
			'status' => array( 'processing', 'on-hold' ),
			'return' => 'ids',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Eingehende Bestellungen für Menü-Badge; Abfrage auf 500 begrenzt und gecacht.
			'meta_query' => array(
				array(
					'key'     => '_lbite_order_status',
					'value'   => $default_status_key,
					'compare' => '=',
				),
			),
		) );

		$count = count( $order_ids );
		set_transient( 'lbite_incoming_orders_count', $count, 2 * MINUTE_IN_SECONDS );

		return $count;
	}

	/**
	 * Übersetzte Status-Labels zurückgeben
	 *
	 * @return array
	 */
	public static function get_status_labels() {
		$labels = array();
		foreach ( self::get_columns() as $col ) {
			$labels[ $col['key'] ] = $col['label'];
		}
		return $labels;
	}

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
		// AJAX-Endpoints
		$this->loader->add_action( 'wp_ajax_lbite_get_orders', $this, 'ajax_get_orders' );
		$this->loader->add_action( 'wp_ajax_lbite_update_order_status', $this, 'ajax_update_order_status' );
		$this->loader->add_action( 'wp_ajax_lbite_save_board_location', $this, 'ajax_save_board_location' );
		$this->loader->add_action( 'wp_ajax_lbite_cancel_order', $this, 'ajax_cancel_order' );
		$this->loader->add_action( 'wp_ajax_lbite_load_more_completed', $this, 'ajax_load_more_completed' );

		// Cron für automatische Statusänderungen (Hook wird bei Aktivierung geplant)
		$this->loader->add_action( 'lbite_check_scheduled_orders', $this, 'check_scheduled_orders' );

		// Bestellungs-Meta-Box
		$this->loader->add_action( 'add_meta_boxes', $this, 'add_order_meta_box' );

		// Neue Bestellung: Initial Status setzen
		$this->loader->add_action( 'woocommerce_new_order', $this, 'set_initial_order_status' );
	}

	/**
	 * Bestellungen abrufen (AJAX)
	 */
	public function ajax_get_orders() {
		check_ajax_referer( 'lbite_dashboard_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_view_orders' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;

		wp_send_json_success( $this->get_board_data( $location_id ) );
	}

	/**
	 * Board-Daten für einen Standort zusammenstellen
	 *
	 * Gemeinsame Grundlage für den AJAX-Endpunkt des Kanban-Boards und die
	 * REST-Route lbite/v1/orders. Bewusst als eigene Methode, damit beide
	 * Zugänge nicht auseinanderlaufen können.
	 *
	 * @param int $location_id Standort-ID. 0 liefert leere Spalten.
	 * @return array {
	 *     @type array $orders           Bestellungen je Spalten-Schlüssel.
	 *     @type array $completed_counts Gesamtzahl je «abgeschlossen»-Spalte.
	 * }
	 */
	public function get_board_data( $location_id ) {
		$location_id = (int) $location_id;

		$columns         = self::get_columns();
		$column_keys     = wp_list_pluck( $columns, 'key' );
		$columns_by_key  = self::get_columns_by_key();

		// Standort ist Pflicht.
		if ( ! $location_id ) {
			return array(
				'orders'           => array_fill_keys( $column_keys, array() ),
				'completed_counts' => array(),
			);
		}

		// Get today's date at midnight for filtering completed orders.
		$today_midnight = wp_date( 'Y-m-d 00:00:00' );

		// Query for non-completed orders (no date restriction).
		$args_active = array(
			'limit'      => 200,
			'status'     => array( 'processing', 'on-hold' ),
			'orderby'    => 'ID',
			'order'      => 'ASC',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- WooCommerce-Bestellfilterung nach Plugin-Metadaten; Abfragen auf max. 200 Einträge begrenzt.
			'meta_query' => array(
				array(
					'key'   => '_lbite_location_id',
					'value' => $location_id,
				),
			),
		);

		// Query for completed orders (only from today).
		$args_completed = array(
			'limit'      => 200,
			'status'     => array( 'completed' ),
			'orderby'    => 'ID',
			'order'      => 'ASC',
			'date_after' => $today_midnight,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- WooCommerce-Bestellfilterung nach Plugin-Metadaten; Abfragen auf max. 200 Einträge begrenzt.
			'meta_query' => array(
				array(
					'key'   => '_lbite_location_id',
					'value' => $location_id,
				),
			),
		);

		$orders_active    = wc_get_orders( $args_active );
		$orders_completed = wc_get_orders( $args_completed );

		$orders_by_status = array_fill_keys( $column_keys, array() );

		// Legacy-Routing («ready»-Mapping, order_type-Heuristik) nur, wenn die Standard-Keys
		// «incoming»/«preparing» noch in der aktuellen Spaltenkonfiguration existieren.
		$legacy_active_routing = isset( $columns_by_key['incoming'], $columns_by_key['preparing'] );

		// Generischer Fallback für ungültige/fehlende Status bei aktiven Bestellungen:
		// erste Spalte, die nicht als «abgeschlossen» zählt.
		$fallback_active_key = $column_keys[0];
		foreach ( $columns as $col ) {
			if ( ! $col['counts_as_completed'] ) {
				$fallback_active_key = $col['key'];
				break;
			}
		}

		// Aktive Bestellungen nach Dringlichkeit sortieren:
		// «now»-Bestellungen nach Erstellungszeit, «later»-Bestellungen nach Abholzeit.
		usort(
			$orders_active,
			function ( $a, $b ) {
				$a_type   = $a->get_meta( '_lbite_order_type', true );
				$b_type   = $b->get_meta( '_lbite_order_type', true );
				$a_pickup = $a->get_meta( '_lbite_pickup_time', true );
				$b_pickup = $b->get_meta( '_lbite_pickup_time', true );

				$a_ts = ( 'later' === $a_type && $a_pickup ) ? lbite_local_time_to_timestamp( $a_pickup ) : $a->get_date_created()->getTimestamp();
				$b_ts = ( 'later' === $b_type && $b_pickup ) ? lbite_local_time_to_timestamp( $b_pickup ) : $b->get_date_created()->getTimestamp();

				return $a_ts - $b_ts;
			}
		);

		// Process active orders.
		foreach ( $orders_active as $order ) {
			$lbite_status = $order->get_meta( '_lbite_order_status', true );

			// Rückwärtskompatibilität: «ready» existiert nicht mehr → Spalte B.
			if ( $legacy_active_routing && 'ready' === $lbite_status ) {
				$lbite_status = 'preparing';
			}

			// Ungültige oder fehlende Status: Routing nach Bestelltyp (nur mit Standard-Keys)
			// bzw. genereller Fallback auf die erste Nicht-«abgeschlossen»-Spalte.
			if ( ! $lbite_status || ! isset( $columns_by_key[ $lbite_status ] ) ) {
				if ( $legacy_active_routing ) {
					$order_type   = $order->get_meta( '_lbite_order_type', true );
					$lbite_status = ( 'later' === $order_type ) ? 'incoming' : 'preparing';
				} else {
					$lbite_status = $fallback_active_key;
				}
			}

			// «incoming»-Bestellungen mit Typ «now» gehören in Spalte B (nur mit Standard-Keys).
			if ( $legacy_active_routing && 'incoming' === $lbite_status ) {
				$order_type = $order->get_meta( '_lbite_order_type', true );
				if ( 'now' === $order_type || '' === $order_type ) {
					$lbite_status = 'preparing';
				}
			}

			// Sicherheitsnetz: Zielspalte muss existieren und darf nicht als «abgeschlossen» zählen
			// (eine aktive WC-Bestellung gehört nie in eine completed-Spalte).
			if ( ! isset( $columns_by_key[ $lbite_status ] ) || $columns_by_key[ $lbite_status ]['counts_as_completed'] ) {
				$lbite_status = $fallback_active_key;
			}

			$orders_by_status[ $lbite_status ][] = $this->format_order_for_dashboard( $order );
		}

		// Process completed orders (already filtered to today only).
		foreach ( $orders_completed as $order ) {
			$lbite_status = $order->get_meta( '_lbite_order_status', true );
			// Nur einsortieren, wenn die gespeicherte Spalte noch existiert und als
			// «abgeschlossen» zählt (es kann mehrere solcher Spalten geben).
			if ( isset( $columns_by_key[ $lbite_status ] ) && $columns_by_key[ $lbite_status ]['counts_as_completed'] ) {
				$orders_by_status[ $lbite_status ][] = $this->format_order_for_dashboard( $order );
			}
		}

		// Vorbestellungen ausblenden wenn Option deaktiviert (Pro).
		$show_future = '0' !== (string) get_option( 'lbite_show_future_orders', 1 );
		if ( ! $show_future ) {
			foreach ( $orders_by_status as &$status_orders ) {
				$status_orders = array_values(
					array_filter(
						$status_orders,
						function( $o ) {
							return ! $o['is_future'];
						}
					)
				);
			}
			unset( $status_orders );
		}

		// Bei allen «abgeschlossen»-Spalten: Neueste zuerst, nur die letzten 3 initial anzeigen.
		$completed_counts = array();
		foreach ( $columns as $col ) {
			if ( ! $col['counts_as_completed'] ) {
				continue;
			}
			$key = $col['key'];
			if ( ! empty( $orders_by_status[ $key ] ) ) {
				$orders_by_status[ $key ] = array_reverse( $orders_by_status[ $key ] );
				$completed_counts[ $key ] = count( $orders_by_status[ $key ] );
				$orders_by_status[ $key ] = array_slice( $orders_by_status[ $key ], 0, 3 );
			} else {
				$completed_counts[ $key ] = 0;
			}
		}

		return array(
			'orders'           => $orders_by_status,
			'completed_counts' => $completed_counts,
		);
	}

	/**
	 * Bestellung für Dashboard formatieren
	 *
	 * @param WC_Order $order Bestellung
	 * @return array
	 */
	private function format_order_for_dashboard( $order ) {
		$order_type     = $order->get_meta( '_lbite_order_type', true );
		$pickup_time    = $order->get_meta( '_lbite_pickup_time', true );
		$location       = $order->get_meta( '_lbite_location_name', true );
		$table_id       = $order->get_meta( '_lbite_table_id', true );
		$payment_method = $order->get_meta( '_lbite_payment_method', true );
		$service_type   = $order->get_meta( '_lbite_service_type', true );

		$items = array();
		foreach ( $order->get_items() as $item ) {
			$product   = $item->get_product();
			$meta_data = $this->get_item_meta_split( $item );
			$items[]   = array(
				'name'     => $item->get_name(),
				'quantity' => $item->get_quantity(),
				'meta'     => $meta_data['config'],
				'note'     => $meta_data['note'],
				'round'    => (int) $item->get_meta( '_lbite_tab_round', true ),
			);
		}

		// Vorbestellung in der Zukunft? Immer berechnen (für Filterung und Dimming).
		// Das Dimming im JS wird nur angewendet wenn futureDimmingEnabled aktiv ist.
		$is_future = false;
		if ( 'later' === $order_type && $pickup_time ) {
			$location_id_for_prep = (int) $order->get_meta( '_lbite_location_id', true );
			$prep_time            = LBite_Locations::get_time_setting( $location_id_for_prep, 'preparation_time', 30 );
			$is_future            = lbite_local_time_to_timestamp( $pickup_time ) > ( current_time( 'timestamp' ) + $prep_time * 60 );
		}

		$billing_email = $order->get_billing_email();

		$table_name = '';
		if ( $table_id ) {
			$table_post = get_post( (int) $table_id );
			$table_name = $table_post ? $table_post->post_title : '';
		}
		if ( ! $table_name ) {
			$table_name = $order->get_meta( '_lbite_checkout_table_number', true );
		}

		// Rohzeitstempel für den Wartezeit-Timer im Board. Die Berechnung
		// läuft clientseitig, daher wird hier nur die Basis mitgegeben.
		$date_created = $order->get_date_created();
		$created_ts   = $date_created ? $date_created->getTimestamp() : 0;

		$prep_minutes = (int) LBite_Locations::get_time_setting(
			(int) $order->get_meta( '_lbite_location_id', true ),
			'preparation_time',
			30
		);

		$data = array(
			'id'             => $order->get_id(),
			'number'         => $order->get_order_number(),
			'date'           => $date_created ? $date_created->format( 'H:i' ) : '',
			'created_ts'     => $created_ts,
			'prep_minutes'   => $prep_minutes,
			'type'           => $order_type,
			'pickup_time'    => $pickup_time ? $this->format_pickup_time_for_display( $pickup_time ) : '',
			'location'       => $location,
			'table_id'       => $table_id,
			'table_name'     => $table_name,
			'service_type'   => $service_type,
			'customer'       => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'total'          => $order->get_formatted_order_total(),
			'items'          => $items,
			'notes'          => $order->get_customer_note(),
			'is_future'      => $is_future,
			'has_email'      => ! empty( $billing_email ) && strpos( $billing_email, '@nomail.local' ) === false,
			'payment_method' => $payment_method ?: '',
			'split_payments' => $order->get_meta( '_lbite_split_payments', true ) ?: array(),
			'is_open_tab'    => '1' === (string) $order->get_meta( '_lbite_tab_open', true ),
			'round_count'    => (int) $order->get_meta( '_lbite_tab_round_count', true ),
		);

		return apply_filters( 'lbite_dashboard_order_data', $data, $order );
	}

	/**
	 * Abholzeit für Anzeige formatieren: Datum nur wenn nicht heute
	 *
	 * @param string $pickup_time Abholzeitstempel (Y-m-d H:i)
	 * @return string
	 */
	private function format_pickup_time_for_display( $pickup_time ) {
		$ts    = lbite_local_time_to_timestamp( $pickup_time );
		$today = wp_date( 'Y-m-d' );
		$day   = wp_date( 'Y-m-d', $ts );

		if ( $day !== $today ) {
			return wp_date( 'd.m. H:i', $ts );
		}

		return wp_date( 'H:i', $ts );
	}

	/**
	 * Item-Meta für Anzeige aufbereiten
	 *
	 * @param WC_Order_Item_Product $item Order-Item
	 * @return string
	 */
	/**
	 * Positions-Meta in Konfiguration und Notiz aufteilen
	 *
	 * @param \WC_Order_Item $item Bestellposition
	 * @return array{config: string, note: string}
	 */
	private function get_item_meta_split( $item ) {
		$meta_data = $item->get_formatted_meta_data();
		if ( empty( $meta_data ) ) {
			return array( 'config' => '', 'note' => '' );
		}

		$wc_internal = array( '_reduced_stock', '_line_subtotal', '_line_total', '_line_tax', '_line_subtotal_tax' );
		$config      = array();
		$note        = '';

		foreach ( $meta_data as $meta ) {
			if ( in_array( $meta->key, $wc_internal, true ) ) {
				continue;
			}

			$value = html_entity_decode( wp_strip_all_tags( $meta->display_value ), ENT_QUOTES, 'UTF-8' );

			if ( 'Note' === $meta->key ) {
				$note = $value;
			} else {
				$config[] = esc_html( $meta->display_key ) . ': ' . esc_html( $value );
			}
		}

		return array(
			'config' => implode( '<br>', $config ),
			'note'   => $note,
		);
	}

	/**
	 * Bestellungs-Status aktualisieren (AJAX)
	 */
	public function ajax_update_order_status() {
		check_ajax_referer( 'lbite_dashboard_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_manage_orders' ) && ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		$order_id   = isset( $_POST['order_id'] ) ? intval( wp_unslash( $_POST['order_id'] ) ) : 0;
		$new_status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

		$result = $this->apply_order_status( $order_id, $new_status );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Status updated', 'libre-bite' ),
			)
		);
	}

	/**
	 * Kanban-Status einer Bestellung setzen
	 *
	 * Gemeinsame Grundlage für den AJAX-Endpunkt und die REST-Route
	 * lbite/v1/orders/<id>/status, damit Tab-Schutz, Legacy-Mapping und die
	 * Rückführung des WooCommerce-Status nicht doppelt gepflegt werden.
	 *
	 * @param int    $order_id   Bestell-ID.
	 * @param string $new_status Ziel-Spaltenschlüssel.
	 * @return array|WP_Error
	 */
	public function apply_order_status( $order_id, $new_status ) {
		$order_id   = (int) $order_id;
		$new_status = sanitize_text_field( (string) $new_status );


		// Rückwärtskompatibilität: «ready» auf «preparing» mappen.
		if ( 'ready' === $new_status ) {
			$new_status = 'preparing';
		}

		$columns_by_key = self::get_columns_by_key();

		if ( ! $order_id || ! isset( $columns_by_key[ $new_status ] ) ) {
			return new WP_Error(
				'lbite_invalid_status',
				__( 'Unknown column', 'libre-bite' ),
				array( 'status' => 400 )
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_Error(
				'lbite_order_not_found',
				__( 'Order not found', 'libre-bite' ),
				array( 'status' => 404 )
			);
		}

		$target_counts_as_completed = $columns_by_key[ $new_status ]['counts_as_completed'];

		// Offene Tabs (F_TAB) dürfen erst nach dem Abschluss in der POS-Oberfläche in eine
		// «abgeschlossen»-Spalte verschoben werden – sonst bleibt _lbite_tab_open gesetzt, obwohl
		// die Bestellung im Kanban bereits als fertig gilt.
		if ( $target_counts_as_completed && '1' === (string) $order->get_meta( '_lbite_tab_open', true ) ) {
			return new WP_Error(
				'lbite_tab_open',
				__( 'Close the tab in the POS first', 'libre-bite' ),
				array( 'status' => 409 )
			);
		}

		$old_status              = $order->get_meta( '_lbite_order_status', true );
		$old_counts_as_completed = isset( $columns_by_key[ $old_status ] ) && $columns_by_key[ $old_status ]['counts_as_completed'];

		$order->update_meta_data( '_lbite_order_status', $new_status );
		$order->update_meta_data( '_lbite_status_changed', current_time( 'mysql' ) );
		$order->save();

		// Menü-Badge-Cache invalidieren.
		delete_transient( 'lbite_incoming_orders_count' );

		if ( $target_counts_as_completed ) {
			// In eine «abgeschlossen»-Spalte verschoben - WooCommerce-Status entsprechend setzen.
			$order->update_status( 'completed', __( 'Order completed via Dashboard', 'libre-bite' ) );
		} elseif ( $old_counts_as_completed && 'completed' === $order->get_status() ) {
			// Zurück-Button: aus einer «abgeschlossen»-Spalte heraus verschoben - WC-Status
			// zurücksetzen, damit die Statistik-Seite konsistent bleibt.
			$order->update_status( 'processing', __( 'Order moved back from completed via Dashboard', 'libre-bite' ) );
		}

		/**
		 * Nach einem Kanban-Statuswechsel.
		 *
		 * Zentraler Erweiterungspunkt für Drucker-Brücken, Küchenanzeigen auf
		 * Zweitgeräten und externe Benachrichtigungen.
		 *
		 * @param int      $order_id   Bestell-ID.
		 * @param string   $new_status Neuer Spalten-Schlüssel.
		 * @param string   $old_status Vorheriger Spalten-Schlüssel.
		 * @param WC_Order $order      Bestellobjekt.
		 */
		do_action( 'lbite_order_status_changed', $order_id, $new_status, $old_status, $order );

		return array(
			'order_id' => $order_id,
			'status'   => $new_status,
		);
	}

	/**
	 * AJAX: Board-Standort speichern
	 */
	public function ajax_save_board_location() {
		check_ajax_referer( 'lbite_dashboard_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_view_dashboard' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		// Standort-Fixierung: Benutzer mit zugewiesenem Standort können ihn nicht ändern
		$assigned = (int) get_user_meta( get_current_user_id(), 'lbite_assigned_location', true );
		if ( $assigned > 0 && ! current_user_can( 'lbite_manage_locations' ) ) {
			wp_send_json_error( array( 'message' => __( 'Location is fixed for your account', 'libre-bite' ) ) );
		}

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;

		// Standort für aktuellen Benutzer speichern
		update_user_meta( get_current_user_id(), 'lbite_board_location', $location_id );

		wp_send_json_success( array( 'location_id' => $location_id ) );
	}

	/**
	 * AJAX: Bestellung stornieren
	 */
	public function ajax_cancel_order() {
		check_ajax_referer( 'lbite_dashboard_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_manage_orders' ) && ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( wp_unslash( $_POST['order_id'] ) ) : 0;
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'libre-bite' ) ) );
		}

		// Rückerstattung VOR der Stornierung – nach update_status('cancelled') gibt is_paid() false zurück.
		// refund_payment: true versucht die Gateway-Rückerstattung. Offline-Gateways (BACS, COD, Cheque)
		// geben einen WP_Error zurück und erstellen KEINEN Rückerstattungs-Eintrag in WC – das ist korrekt,
		// da die Rückerstattung manuell erfolgen muss. Online-Gateways (Stripe, TWINT usw.) verarbeiten
		// die Rückerstattung automatisch und erstellen einen WC-Eintrag.
		$refund_triggered = false;
		if ( $order->get_total() > 0 && $order->is_paid() ) {
			$refund = wc_create_refund(
				array(
					'order_id'       => $order_id,
					'amount'         => $order->get_total(),
					'reason'         => __( 'Order cancelled', 'libre-bite' ),
					'refund_payment' => true,
				)
			);
			$refund_triggered = ! is_wp_error( $refund );
		}

		// Bestellung stornieren
		$order->update_status( 'cancelled', __( 'Cancelled via Dashboard', 'libre-bite' ) );

		// Dashboard-Status entfernen
		$order->update_meta_data( '_lbite_order_status', 'cancelled' );
		$order->save();

		wp_send_json_success(
			array(
				'message'  => __( 'Order successfully cancelled', 'libre-bite' ),
				'refunded' => $refund_triggered,
			)
		);
	}

	/**
	 * AJAX: Weitere abgeschlossene Bestellungen laden
	 */
	public function ajax_load_more_completed() {
		check_ajax_referer( 'lbite_dashboard_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_view_orders' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;
		$offset      = isset( $_POST['offset'] ) ? intval( wp_unslash( $_POST['offset'] ) ) : 0;
		$column      = isset( $_POST['column'] ) ? sanitize_key( wp_unslash( $_POST['column'] ) ) : 'completed';

		if ( ! $location_id ) {
			wp_send_json_success( array( 'orders' => array() ) );
		}

		// Angeforderte Spalte muss existieren und als «abgeschlossen» zählen.
		$columns_by_key = self::get_columns_by_key();
		if ( ! isset( $columns_by_key[ $column ] ) || ! $columns_by_key[ $column ]['counts_as_completed'] ) {
			wp_send_json_success( array( 'orders' => array() ) );
		}

		// Get today's date at midnight for filtering completed orders.
		$today_midnight = wp_date( 'Y-m-d 00:00:00' );

		$args = array(
			'status'     => array( 'completed' ),
			'orderby'    => 'ID',
			'order'      => 'DESC',
			'date_after' => $today_midnight, // Only orders from today.
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- WooCommerce-Bestellfilterung nach Plugin-Metadaten; Abfragen auf max. 200 Einträge begrenzt.
			'meta_query' => array(
				array(
					'key'     => '_lbite_location_id',
					'value'   => $location_id,
					'compare' => '=',
				),
				array(
					'key'     => '_lbite_order_status',
					'value'   => $column,
					'compare' => '=',
				),
			),
		);

		// Nur die angeforderten Bestellungen laden (paginiert).
		$args['limit']    = 10;
		$args['offset']   = $offset;
		$args['paginate'] = true;
		
		$results         = wc_get_orders( $args );
		$orders          = $results->orders;
		$total_completed = $results->total;
		
		$formatted = array();

		foreach ( $orders as $order ) {
			$formatted[] = $this->format_order_for_dashboard( $order );
		}

		wp_send_json_success(
			array(
				'orders'      => $formatted,
				'total_count' => $total_completed,
			)
		);
	}

	/**
	 * Geplante Bestellungen prüfen und automatisch verschieben
	 */
	public function check_scheduled_orders() {
		// Die Vorbestellungen-Spalte existiert nur, wenn das Feature aktiv ist -
		// enforce_fixed_columns() garantiert in diesem Fall beide Schlüssel zuverlässig,
		// eine Existenzprüfung der Spalten selbst ist darum nicht mehr nötig.
		if ( ! lbite_feature_enabled( 'enable_scheduled_orders' ) ) {
			return;
		}

		// Bestellungen mit Pickup-Zeit in der Zukunft
		$orders = wc_get_orders(
			array(
				'limit'      => 100,
				'status'     => array( 'processing', 'on-hold' ),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- WooCommerce-Bestellfilterung nach Plugin-Metadaten; Abfragen auf max. 200 Einträge begrenzt.
				'meta_query' => array(
					array(
						'key'     => '_lbite_order_type',
						'value'   => 'later',
						'compare' => '=',
					),
					array(
						'key'     => '_lbite_order_status',
						'value'   => self::KEY_PREORDER,
						'compare' => '=',
					),
				),
			)
		);

		$current_time = current_time( 'timestamp' );

		foreach ( $orders as $order ) {
			$pickup_time = $order->get_meta( '_lbite_pickup_time', true );
			if ( ! $pickup_time ) {
				continue;
			}

			$location_id      = (int) $order->get_meta( '_lbite_location_id', true );
			$prep_time        = LBite_Locations::get_time_setting( $location_id, 'preparation_time', 30 );
			$pickup_timestamp = lbite_local_time_to_timestamp( $pickup_time );
			$prep_start_time  = $pickup_timestamp - ( $prep_time * 60 );

			// Wenn Vorbereitungszeit erreicht ist
			if ( $current_time >= $prep_start_time ) {
				$order->update_meta_data( '_lbite_order_status', self::KEY_ACTIVE );
				$order->update_meta_data( '_lbite_status_changed', current_time( 'mysql' ) );
				$order->save();

				do_action( 'lbite_order_auto_moved_to_preparing', $order->get_id() );
			}
		}
	}

	/**
	 * Initial Order Status setzen
	 *
	 * @param int $order_id Bestellungs-ID
	 */
	public function set_initial_order_status( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$lbite_status = $order->get_meta( '_lbite_order_status', true );
		if ( ! $lbite_status ) {
			// Der Bestelltyp aus der Checkout-Session lesen, nicht aus der Bestellung selbst:
			// dieser Hook (woocommerce_new_order) feuert regelmässig, bevor der Checkout-Modul-
			// Handler _lbite_order_type auf die Bestellung schreibt (woocommerce_checkout_update_
			// order_meta läuft später). Für POS/Tab-Bestellungen ist _lbite_order_type bereits
			// vor diesem Hook gesetzt, daher zuerst die Bestellung selbst prüfen.
			$order_type = $order->get_meta( '_lbite_order_type', true );
			if ( ! $order_type && function_exists( 'WC' ) && WC()->session ) {
				$order_type = WC()->session->get( 'lbite_order_type', 'now' );
			}

			$is_preorder    = ( 'later' === $order_type ) && lbite_feature_enabled( 'enable_scheduled_orders' );
			$initial_status = $is_preorder ? self::KEY_PREORDER : self::KEY_ACTIVE;

			$order->update_meta_data( '_lbite_order_status', $initial_status );
			$order->save();

			// Menü-Badge-Cache invalidieren.
			delete_transient( 'lbite_incoming_orders_count' );
		}

		// Fallback: Location-Meta aus Session setzen falls nicht vorhanden.
		// Nur bei echtem WC-Frontend-Checkout anwenden – POS-Bestellungen sollen
		// keine veralteten Session-Werte (z.B. frühere Vorbestellungs-Tests) erben.
		$location_id = $order->get_meta( '_lbite_location_id', true );
		if ( ! $location_id && function_exists( 'WC' ) && WC()->session ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nur Lesezugriff zur Unterscheidung von Checkout vs. AJAX.
			$nonce_value = isset( $_POST['woocommerce-process-checkout-nonce'] )
				? sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) )
				: '';
			$is_checkout = $nonce_value && wp_verify_nonce( $nonce_value, 'woocommerce-process_checkout' );
			if ( ! $is_checkout ) {
				return;
			}

			$session_location_id = WC()->session->get( 'lbite_location_id' );
			if ( $session_location_id ) {
				$order->update_meta_data( '_lbite_location_id', $session_location_id );

				// Standort-Name speichern.
				$location = get_post( $session_location_id );
				if ( $location ) {
					$order->update_meta_data( '_lbite_location_name', $location->post_title );
				}
			}

			// Order type und pickup time aus Session.
			$order_type = WC()->session->get( 'lbite_order_type', 'now' );
			if ( $order_type ) {
				$order->update_meta_data( '_lbite_order_type', $order_type );
			}

			$pickup_time = WC()->session->get( 'lbite_pickup_time' );
			if ( $pickup_time && 'later' === $order_type ) {
				$order->update_meta_data( '_lbite_pickup_time', $pickup_time );
			}

			$order->save();
		}
	}

	/**
	 * Order Meta-Box hinzufügen
	 */
	public function add_order_meta_box() {
		add_meta_box(
			'lbite_order_dashboard_status',
			__( 'Dashboard Status', 'libre-bite' ),
			array( $this, 'render_order_meta_box' ),
			'shop_order',
			'side',
			'high'
		);

		// HPOS Support
		$screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_meta_box(
			'lbite_order_dashboard_status',
			__( 'Dashboard Status', 'libre-bite' ),
			array( $this, 'render_order_meta_box' ),
			$screen,
			'side',
			'high'
		);
	}

	/**
	 * Order Meta-Box rendern
	 *
	 * @param WP_Post|WC_Order $post_or_order Post oder Order Objekt
	 */
	public function render_order_meta_box( $post_or_order ) {
		$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );

		if ( ! $order ) {
			return;
		}

		$current_status = $order->get_meta( '_lbite_order_status', true );
		$status_labels  = self::get_status_labels();
		if ( ! $current_status || ! isset( $status_labels[ $current_status ] ) ) {
			$current_status = self::KEY_ACTIVE;
		}

		$status_changed = $order->get_meta( '_lbite_status_changed', true );
		?>
		<div class="lbite-order-status-meta">
			<p>
				<strong><?php esc_html_e( 'Current Status:', 'libre-bite' ); ?></strong><br>
				<span class="lbite-status-badge lbite-status-<?php echo esc_attr( $current_status ); ?>">
					<?php echo esc_html( $status_labels[ $current_status ] ); ?>
				</span>
			</p>

			<?php if ( $status_changed ) : ?>
				<p>
					<small><?php esc_html_e( 'Last Changed:', 'libre-bite' ); ?><br>
					<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), lbite_local_time_to_timestamp( $status_changed ) ) ); ?></small>
				</p>
			<?php endif; ?>

			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-order-board' ) ); ?>" class="button">
					<?php esc_html_e( 'Go to Dashboard', 'libre-bite' ); ?>
				</a>
			</p>
		</div>

		<?php
		$meta_box_css = '.lbite-status-badge { display: inline-block; padding: 5px 10px; border-radius: 3px; font-weight: 600; font-size: 13px; }
		.lbite-status-incoming { background: #f0f0f0; color: #333; }
		.lbite-status-preparing { background: #fff3cd; color: #856404; }
		.lbite-status-ready { background: #d1ecf1; color: #0c5460; }
		.lbite-status-completed { background: #d4edda; color: #155724; }';
		wp_add_inline_style( 'wp-admin', $meta_box_css );
		?>
		<?php
	}
}
