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
	 * Loader-Instanz
	 *
	 * @var LBite_Loader
	 */
	private $loader;

	/**
	 * Status-Definitionen
	 *
	 * @var array
	 */
	const STATUSES = array(
		'incoming'  => 'Eingang',
		'preparing' => 'Zubereiten',
		'ready'     => 'Abholbereit',
		'completed' => 'Abgeschlossen',
	);

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
		// Admin-Assets
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_dashboard_assets' );

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

		// Cron-Job aktivieren wenn nicht vorhanden
		if ( ! wp_next_scheduled( 'lbite_check_scheduled_orders' ) ) {
			wp_schedule_event( time(), 'every_minute', 'lbite_check_scheduled_orders' );
		}
	}

	/**
	 * Dashboard-Assets laden
	 *
	 * @param string $hook Aktuelle Admin-Seite
	 */
	public function enqueue_dashboard_assets( $hook ) {
		// Null-Check für $hook.
		if ( empty( $hook ) || strpos( (string) $hook, 'lbite-order-board' ) === false ) {
			return;
		}

		// Order Board CSS
		wp_enqueue_style(
			'lbite-order-board',
			LBITE_PLUGIN_URL . 'assets/css/admin-order-board.css',
			array(),
			LBITE_VERSION
		);

		// SortableJS für Drag & Drop (lokal gebündelt).
		wp_enqueue_script(
			'sortablejs',
			LBITE_PLUGIN_URL . 'assets/js/vendor/sortable.min.js',
			array(),
			'1.15.7',
			true
		);

		// Dashboard-spezifisches JS
		wp_enqueue_script(
			'lbite-dashboard',
			LBITE_PLUGIN_URL . 'assets/js/dashboard.js',
			array( 'jquery', 'sortablejs' ),
			LBITE_VERSION,
			true
		);

		// Sound-Datei-Pfad (aus Einstellungen oder Default)
		$sound_url = get_option( 'lbite_notification_sound', '' );

		// Falls kein Sound eingestellt, prüfe auf Default-Sound im Plugin
		if ( empty( $sound_url ) ) {
			$sound_file = LBITE_PLUGIN_DIR . 'assets/sounds/notification.mp3';
			$sound_url  = file_exists( $sound_file ) ? LBITE_PLUGIN_URL . 'assets/sounds/notification.mp3' : '';
		}

		// Lokalisierte Daten
		wp_localize_script(
			'lbite-dashboard',
			'lbiteDashboard',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'lbite_dashboard_nonce' ),
				'refreshInterval' => get_option( 'lbite_dashboard_refresh_interval', 30 ) * 1000,
				'soundUrl'        => $sound_url,
				'statuses'        => self::STATUSES,
				'strings'         => array(
					'orderUpdated' => __( 'Bestellung aktualisiert', 'libre-bite' ),
					'updateError'  => __( 'Fehler beim Aktualisieren', 'libre-bite' ),
					'newOrder'     => __( 'Neue Bestellung', 'libre-bite' ),
				),
			)
		);
	}

	/**
	 * Bestellungen abrufen (AJAX)
	 */
	public function ajax_get_orders() {
		check_ajax_referer( 'lbite_dashboard_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_view_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;

		// Standort ist Pflicht.
		if ( ! $location_id ) {
			wp_send_json_success(
				array(
					'orders' => array(
						'incoming'  => array(),
						'preparing' => array(),
						'ready'     => array(),
						'completed' => array(),
					),
				)
			);
		}

		// Get today's date at midnight for filtering completed orders.
		$today_midnight = wp_date( 'Y-m-d 00:00:00' );

		// Query for non-completed orders (no date restriction).
		$args_active = array(
			'limit'      => -1,
			'status'     => array( 'processing', 'pending', 'on-hold' ),
			'orderby'    => 'ID',
			'order'      => 'ASC',
			'meta_query' => array(
				array(
					'key'   => '_lbite_location_id',
					'value' => $location_id,
				),
			),
		);

		// Query for completed orders (only from today).
		$args_completed = array(
			'limit'      => -1,
			'status'     => array( 'completed' ),
			'orderby'    => 'ID',
			'order'      => 'ASC',
			'date_after' => $today_midnight,
			'meta_query' => array(
				array(
					'key'   => '_lbite_location_id',
					'value' => $location_id,
				),
			),
		);

		$orders_active    = wc_get_orders( $args_active );
		$orders_completed = wc_get_orders( $args_completed );

		$orders_by_status = array(
			'incoming'  => array(),
			'preparing' => array(),
			'ready'     => array(),
			'completed' => array(),
		);

		// Process active orders.
		foreach ( $orders_active as $order ) {
			$lbite_status = get_post_meta( $order->get_id(), '_lbite_order_status', true );
			if ( ! $lbite_status || ! isset( $orders_by_status[ $lbite_status ] ) ) {
				$lbite_status = 'incoming';
			}

			$orders_by_status[ $lbite_status ][] = $this->format_order_for_dashboard( $order );
		}

		// Process completed orders (already filtered to today only).
		foreach ( $orders_completed as $order ) {
			$lbite_status = get_post_meta( $order->get_id(), '_lbite_order_status', true );
			// Only add to completed if status is 'completed'.
			if ( 'completed' === $lbite_status ) {
				$orders_by_status['completed'][] = $this->format_order_for_dashboard( $order );
			}
		}

		// Bei abgeschlossenen Bestellungen: Neueste zuerst, nur die letzten 3 initial anzeigen.
		if ( ! empty( $orders_by_status['completed'] ) ) {
			$orders_by_status['completed'] = array_reverse( $orders_by_status['completed'] );
			$completed_count               = count( $orders_by_status['completed'] );
			$orders_by_status['completed'] = array_slice( $orders_by_status['completed'], 0, 3 );
		} else {
			$completed_count = 0;
		}

		wp_send_json_success(
			array(
				'orders'          => $orders_by_status,
				'completed_count' => $completed_count,
			)
		);
	}

	/**
	 * Bestellung für Dashboard formatieren
	 *
	 * @param WC_Order $order Bestellung
	 * @return array
	 */
	private function format_order_for_dashboard( $order ) {
		$order_type  = get_post_meta( $order->get_id(), '_lbite_order_type', true );
		$pickup_time = get_post_meta( $order->get_id(), '_lbite_pickup_time', true );
		$location    = get_post_meta( $order->get_id(), '_lbite_location_name', true );

		$items = array();
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$items[] = array(
				'name'     => $item->get_name(),
				'quantity' => $item->get_quantity(),
				'meta'     => $this->get_item_meta_display( $item ),
			);
		}

		return array(
			'id'          => $order->get_id(),
			'number'      => $order->get_order_number(),
			'date'        => $order->get_date_created()->format( 'H:i' ),
			'type'        => $order_type,
			'pickup_time' => $pickup_time ? wp_date( 'H:i', strtotime( $pickup_time ) ) : '',
			'location'    => $location,
			'customer'    => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'total'       => $order->get_formatted_order_total(),
			'items'       => $items,
			'notes'       => $order->get_customer_note(),
		);
	}

	/**
	 * Item-Meta für Anzeige aufbereiten
	 *
	 * @param WC_Order_Item_Product $item Order-Item
	 * @return string
	 */
	private function get_item_meta_display( $item ) {
		$meta_data = $item->get_formatted_meta_data();
		if ( empty( $meta_data ) ) {
			return '';
		}

		$meta_strings = array();
		foreach ( $meta_data as $meta ) {
			// Interne WooCommerce-Meta überspringen
			if ( in_array( $meta->key, array( '_reduced_stock', '_line_subtotal', '_line_total', '_line_tax', '_line_subtotal_tax' ) ) ) {
				continue;
			}

			$label = $meta->display_key;
			$value = wp_strip_all_tags( $meta->display_value );

			// HTML Entities dekodieren
			$value = html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );

			$meta_strings[] = $label . ': ' . $value;
		}

		return implode( '<br>', $meta_strings );
	}

	/**
	 * Bestellungs-Status aktualisieren (AJAX)
	 */
	public function ajax_update_order_status() {
		check_ajax_referer( 'lbite_dashboard_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_manage_orders' ) && ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$order_id   = isset( $_POST['order_id'] ) ? intval( wp_unslash( $_POST['order_id'] ) ) : 0;
		$new_status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

		if ( ! $order_id || ! isset( self::STATUSES[ $new_status ] ) ) {
			wp_send_json_error();
		}

		update_post_meta( $order_id, '_lbite_order_status', $new_status );
		update_post_meta( $order_id, '_lbite_status_changed', current_time( 'mysql' ) );

		// Bei "Abholbereit" - Kunde benachrichtigen (optional)
		if ( 'ready' === $new_status ) {
			do_action( 'lbite_order_ready', $order_id );
		}

		// Bei "Abgeschlossen" - WooCommerce Status ändern
		if ( 'completed' === $new_status ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$order->update_status( 'completed', __( 'Bestellung abgeschlossen via Dashboard', 'libre-bite' ) );
			}
		}

		wp_send_json_success(
			array(
				'message' => __( 'Status aktualisiert', 'libre-bite' ),
			)
		);
	}

	/**
	 * AJAX: Board-Standort speichern
	 */
	public function ajax_save_board_location() {
		check_ajax_referer( 'lbite_dashboard_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
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

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( wp_unslash( $_POST['order_id'] ) ) : 0;
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Bestellung nicht gefunden', 'libre-bite' ) ) );
		}

		// Bestellung stornieren
		$order->update_status( 'cancelled', __( 'Storniert über Dashboard', 'libre-bite' ) );

		// Rückerstattung erstellen wenn Bestellung bezahlt wurde
		if ( $order->get_total() > 0 && $order->is_paid() ) {
			$refund = wc_create_refund(
				array(
					'order_id' => $order_id,
					'amount'   => $order->get_total(),
					'reason'   => __( 'Bestellung storniert', 'libre-bite' ),
				)
			);

			if ( is_wp_error( $refund ) ) {
				wp_send_json_error( array( 'message' => __( 'Bestellung storniert, aber Rückerstattung fehlgeschlagen: ', 'libre-bite' ) . $refund->get_error_message() ) );
			}
		}

		// Dashboard-Status entfernen
		update_post_meta( $order_id, '_lbite_order_status', 'cancelled' );

		wp_send_json_success(
			array(
				'message' => __( 'Bestellung erfolgreich storniert', 'libre-bite' ),
			)
		);
	}

	/**
	 * AJAX: Weitere abgeschlossene Bestellungen laden
	 */
	public function ajax_load_more_completed() {
		check_ajax_referer( 'lbite_dashboard_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_view_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'libre-bite' ) ) );
		}

		$location_id = isset( $_POST['location_id'] ) ? intval( wp_unslash( $_POST['location_id'] ) ) : 0;
		$offset      = isset( $_POST['offset'] ) ? intval( wp_unslash( $_POST['offset'] ) ) : 0;

		if ( ! $location_id ) {
			wp_send_json_success( array( 'orders' => array() ) );
		}

		// Get today's date at midnight for filtering completed orders.
		$today_midnight = wp_date( 'Y-m-d 00:00:00' );

		$args = array(
			'limit'      => -1,
			'status'     => array( 'completed' ),
			'orderby'    => 'ID',
			'order'      => 'DESC',
			'date_after' => $today_midnight, // Only orders from today.
			'meta_query' => array(
				array(
					'key'     => '_lbite_location_id',
					'value'   => $location_id,
					'compare' => '=',
				),
				array(
					'key'     => '_lbite_order_status',
					'value'   => 'completed',
					'compare' => '=',
				),
			),
		);

		$orders          = wc_get_orders( $args );
		$formatted       = array();
		$total_completed = count( $orders );

		// Nur die angeforderten Bestellungen zurückgeben (ab offset, 10 Stück).
		$orders_to_show = array_slice( $orders, $offset, 10 );

		foreach ( $orders_to_show as $order ) {
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
		$prep_time = get_option( 'lbite_preparation_time', 30 );

		// Bestellungen mit Pickup-Zeit in der Zukunft
		$orders = wc_get_orders(
			array(
				'limit'      => -1,
				'status'     => array( 'processing', 'pending' ),
				'meta_query' => array(
					array(
						'key'     => '_lbite_order_type',
						'value'   => 'later',
						'compare' => '=',
					),
					array(
						'key'     => '_lbite_order_status',
						'value'   => 'incoming',
						'compare' => '=',
					),
				),
			)
		);

		$current_time = current_time( 'timestamp' );

		foreach ( $orders as $order ) {
			$pickup_time = get_post_meta( $order->get_id(), '_lbite_pickup_time', true );
			if ( ! $pickup_time ) {
				continue;
			}

			$pickup_timestamp = strtotime( $pickup_time );
			$prep_start_time  = $pickup_timestamp - ( $prep_time * 60 );

			// Wenn Vorbereitungszeit erreicht ist
			if ( $current_time >= $prep_start_time ) {
				update_post_meta( $order->get_id(), '_lbite_order_status', 'preparing' );
				update_post_meta( $order->get_id(), '_lbite_status_changed', current_time( 'mysql' ) );

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
		$lbite_status = get_post_meta( $order_id, '_lbite_order_status', true );
		if ( ! $lbite_status ) {
			update_post_meta( $order_id, '_lbite_order_status', 'incoming' );
		}

		// Fallback: Location-Meta aus Session setzen falls nicht vorhanden.
		$location_id = get_post_meta( $order_id, '_lbite_location_id', true );
		if ( ! $location_id && function_exists( 'WC' ) && WC()->session ) {
			$session_location_id = WC()->session->get( 'lbite_location_id' );
			if ( $session_location_id ) {
				update_post_meta( $order_id, '_lbite_location_id', $session_location_id );

				// Standort-Name speichern.
				$location = get_post( $session_location_id );
				if ( $location ) {
					update_post_meta( $order_id, '_lbite_location_name', $location->post_title );
				}
			}

			// Order type und pickup time auch speichern.
			$order_type = WC()->session->get( 'lbite_order_type', 'now' );
			if ( $order_type ) {
				update_post_meta( $order_id, '_lbite_order_type', $order_type );
			}

			$pickup_time = WC()->session->get( 'lbite_pickup_time' );
			if ( $pickup_time && 'later' === $order_type ) {
				update_post_meta( $order_id, '_lbite_pickup_time', $pickup_time );
			}
		}
	}

	/**
	 * Order Meta-Box hinzufügen
	 */
	public function add_order_meta_box() {
		add_meta_box(
			'lbite_order_dashboard_status',
			__( 'Dashboard-Status', 'libre-bite' ),
			array( $this, 'render_order_meta_box' ),
			'shop_order',
			'side',
			'high'
		);

		// HPOS Support
		$screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_meta_box(
			'lbite_order_dashboard_status',
			__( 'Dashboard-Status', 'libre-bite' ),
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

		$current_status = get_post_meta( $order->get_id(), '_lbite_order_status', true );
		if ( ! $current_status ) {
			$current_status = 'incoming';
		}

		$status_changed = get_post_meta( $order->get_id(), '_lbite_status_changed', true );
		?>
		<div class="lbite-order-status-meta">
			<p>
				<strong><?php esc_html_e( 'Aktueller Status:', 'libre-bite' ); ?></strong><br>
				<span class="lbite-status-badge lbite-status-<?php echo esc_attr( $current_status ); ?>">
					<?php echo esc_html( self::STATUSES[ $current_status ] ); ?>
				</span>
			</p>

			<?php if ( $status_changed ) : ?>
				<p>
					<small><?php esc_html_e( 'Zuletzt geändert:', 'libre-bite' ); ?><br>
					<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $status_changed ) ) ); ?></small>
				</p>
			<?php endif; ?>

			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-order-board' ) ); ?>" class="button">
					<?php esc_html_e( 'Zum Dashboard', 'libre-bite' ); ?>
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
