<?php
/**
 * Kassenabschluss: X- und Z-Bericht (F25, Pro)
 *
 * X-Bericht: Zwischenstand seit dem letzten Z-Bericht eines Standorts,
 * beliebig oft, ohne etwas zu speichern.
 * Z-Bericht: Tagesabschluss. Friert den Stand ein, nummeriert fortlaufend je
 * Standort und speichert ihn als eigenen, nicht bearbeitbaren Datensatz. Der
 * nächste Bericht beginnt dort, wo der letzte aufgehört hat.
 *
 * Bewusst weich gekoppelt: Die Kasse funktioniert auch ohne Abschluss.
 *
 * WICHTIG: Kein zertifiziertes Kassensystem. In Deutschland verlangt die
 * KassenSichV eine TSE, in Österreich die RKSV eine Signaturkarte. Das wird in
 * der Oberfläche ausdrücklich gesagt.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kassenbericht-Klasse
 */
class LBite_Cash_Reports {

	/**
	 * Inhaltstyp der gespeicherten Z-Berichte.
	 */
	const POST_TYPE = 'lbite_z_report';

	/**
	 * Admin-Seite.
	 */
	const PAGE = 'lbite-cash-report';

	/**
	 * Erforderliche Berechtigung.
	 */
	const CAPABILITY = 'lbite_use_pos';

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

		$this->loader->add_action( 'init', $this, 'register_post_type' );
		$this->loader->add_action( 'admin_menu', $this, 'register_page', 30 );
		$this->loader->add_action( 'admin_post_lbite_close_cash_day', $this, 'handle_close_day' );
		$this->loader->add_action( 'admin_init', $this, 'maybe_export_csv' );
	}

	/*
	 * ------------------------------------------------------------------
	 * Reine Logik (ohne WordPress-Zustand, per PHPUnit getestet)
	 * ------------------------------------------------------------------
	 */

	/**
	 * Bestellzeilen zu einem Bericht zusammenfassen.
	 *
	 * Jede Zeile beschreibt eine Bestellung neutral, damit die Summierung
	 * unabhängig von WooCommerce prüfbar ist:
	 * - status:   'paid' oder 'cancelled'
	 * - total:    Bruttobetrag
	 * - payments: [ Schlüssel => Betrag ] (Split-Zahlungen mehrere Einträge)
	 * - tip, discount, rounding: Beträge (Rabatt positiv)
	 * - taxes:    [ Bezeichnung => Betrag ]
	 *
	 * @param array[] $rows          Bestellzeilen.
	 * @param float   $opening_float Wechselgeld zu Beginn.
	 * @param array   $cash_keys     Zahlungsschlüssel, die als Bargeld zählen.
	 * @return array
	 */
	public static function aggregate( array $rows, $opening_float, array $cash_keys ) {
		$report = array(
			'orders'          => 0,
			'gross'           => 0.0,
			'payments'        => array(),
			'taxes'           => array(),
			'tips'            => 0.0,
			'discounts'       => 0.0,
			'rounding'        => 0.0,
			'cancelled_count' => 0,
			'cancelled_total' => 0.0,
			'cash_sales'      => 0.0,
			'opening_float'   => round( (float) $opening_float, 2 ),
			'expected_cash'   => 0.0,
		);

		foreach ( $rows as $row ) {
			if ( 'cancelled' === $row['status'] ) {
				$report['cancelled_count']++;
				$report['cancelled_total'] += (float) $row['total'];
				continue;
			}

			$report['orders']++;
			$report['gross']     += (float) $row['total'];
			$report['tips']      += (float) $row['tip'];
			$report['discounts'] += (float) $row['discount'];
			$report['rounding']  += (float) $row['rounding'];

			foreach ( $row['payments'] as $key => $amount ) {
				if ( ! isset( $report['payments'][ $key ] ) ) {
					$report['payments'][ $key ] = 0.0;
				}
				$report['payments'][ $key ] += (float) $amount;

				if ( in_array( $key, $cash_keys, true ) ) {
					$report['cash_sales'] += (float) $amount;
				}
			}

			foreach ( $row['taxes'] as $label => $amount ) {
				if ( ! isset( $report['taxes'][ $label ] ) ) {
					$report['taxes'][ $label ] = 0.0;
				}
				$report['taxes'][ $label ] += (float) $amount;
			}
		}

		// Auf Rappen runden – Fliesskomma-Reste sollen im Bericht nicht auftauchen.
		foreach ( array( 'gross', 'tips', 'discounts', 'rounding', 'cancelled_total', 'cash_sales' ) as $key ) {
			$report[ $key ] = round( $report[ $key ], 2 );
		}
		$report['payments'] = array_map(
			function ( $v ) {
				return round( $v, 2 );
			},
			$report['payments']
		);
		$report['taxes']    = array_map(
			function ( $v ) {
				return round( $v, 2 );
			},
			$report['taxes']
		);
		arsort( $report['payments'] );
		ksort( $report['taxes'] );

		$report['expected_cash'] = round( $report['opening_float'] + $report['cash_sales'], 2 );

		return $report;
	}

	/**
	 * Kassendifferenz (gezählt minus Soll), null wenn nicht gezählt.
	 *
	 * @param float|null $counted  Gezählter Bargeldbestand.
	 * @param float      $expected Soll-Bestand.
	 * @return float|null
	 */
	public static function difference( $counted, $expected ) {
		if ( null === $counted || '' === $counted ) {
			return null;
		}

		return round( (float) $counted - (float) $expected, 2 );
	}

	/*
	 * ------------------------------------------------------------------
	 * Daten
	 * ------------------------------------------------------------------
	 */

	/**
	 * Inhaltstyp für Z-Berichte (nicht öffentlich, nicht bearbeitbar).
	 */
	public function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'rewrite'             => false,
				'supports'            => array( 'title' ),
				'label'               => __( 'Z reports', 'libre-bite' ),
			)
		);
	}

	/**
	 * Letzter Z-Bericht eines Standorts.
	 *
	 * @param int $location_id Standort-ID.
	 * @return WP_Post|null
	 */
	public static function get_last_z( $location_id ) {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'private',
				'posts_per_page' => 1,
				'orderby'        => 'ID',
				'order'          => 'DESC',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Ein Eintrag je Abschluss, Abfrage auf 1 Ergebnis begrenzt.
				'meta_query'     => array(
					array(
						'key'   => '_lbite_location_id',
						'value' => (int) $location_id,
					),
				),
			)
		);

		return $posts ? $posts[0] : null;
	}

	/**
	 * Beginn des laufenden Zeitraums (Unix-Zeit).
	 *
	 * Ende des letzten Z-Berichts oder – beim allerersten Abschluss – heute 00:00.
	 *
	 * @param int $location_id Standort-ID.
	 * @return int
	 */
	public static function get_period_start( $location_id ) {
		$last = self::get_last_z( $location_id );
		if ( $last ) {
			return (int) get_post_meta( $last->ID, '_lbite_period_end', true );
		}

		return (int) ( new DateTime( 'today', wp_timezone() ) )->getTimestamp();
	}

	/**
	 * Schlüssel, die als Bargeld gelten: Kasse «Bar» und WooCommerce «Nachnahme/Barzahlung».
	 *
	 * @return string[]
	 */
	public static function get_cash_keys() {
		return array( 'cash', 'gateway:cod' );
	}

	/**
	 * Bestellung in eine neutrale Berichtszeile übersetzen.
	 *
	 * @param WC_Order $order Bestellung.
	 * @return array
	 */
	private static function order_to_row( $order ) {
		$status = in_array( $order->get_status(), array( 'cancelled', 'refunded', 'failed' ), true ) ? 'cancelled' : 'paid';
		$total  = (float) $order->get_total();

		// Zahlungsart: Kasse über _lbite_payment_method (inkl. Split), sonst das Gateway.
		$payments = array();
		$pos_pm   = (string) $order->get_meta( '_lbite_payment_method' );
		$splits   = $order->get_meta( '_lbite_split_payments' );
		if ( 'split' === $pos_pm && is_array( $splits ) ) {
			foreach ( $splits as $split ) {
				$key              = isset( $split['method'] ) ? sanitize_key( $split['method'] ) : 'other';
				$payments[ $key ] = ( isset( $payments[ $key ] ) ? $payments[ $key ] : 0 ) + (float) $split['amount'];
			}
		} elseif ( '' !== $pos_pm ) {
			$payments[ sanitize_key( $pos_pm ) ] = $total;
		} else {
			$payments[ 'gateway:' . ( $order->get_payment_method() ? $order->get_payment_method() : 'none' ) ] = $total;
		}

		// Rundung und Rabatt-Gebühren (Aktionen) aus den Gebührenzeilen.
		$rounding        = 0.0;
		$discount        = (float) $order->get_discount_total() + (float) $order->get_discount_tax();
		$rounding_labels = array( 'Rounding', __( 'Rounding', 'libre-bite' ) );
		foreach ( $order->get_fees() as $fee ) {
			$amount = (float) $fee->get_total() + (float) $fee->get_total_tax();
			if ( in_array( $fee->get_name(), $rounding_labels, true ) ) {
				$rounding += $amount;
			} elseif ( $amount < 0 ) {
				$discount += abs( $amount );
			}
		}

		$taxes = array();
		foreach ( $order->get_tax_totals() as $tax ) {
			$taxes[ $tax->label ] = ( isset( $taxes[ $tax->label ] ) ? $taxes[ $tax->label ] : 0 ) + (float) $tax->amount;
		}

		return array(
			'status'   => $status,
			'total'    => $total,
			'payments' => $payments,
			'tip'      => (float) $order->get_meta( '_lbite_tip_amount' ),
			'discount' => $discount,
			'rounding' => $rounding,
			'taxes'    => $taxes,
		);
	}

	/**
	 * Laufenden Bericht eines Standorts berechnen.
	 *
	 * @param int   $location_id   Standort-ID.
	 * @param float $opening_float Wechselgeld zu Beginn.
	 * @param int   $until         Ende des Zeitraums (Unix-Zeit), Standard jetzt.
	 * @return array
	 */
	public static function build_report( $location_id, $opening_float = 0.0, $until = 0 ) {
		$start = self::get_period_start( $location_id );
		$until = $until ? (int) $until : time();
		$rows  = array();
		$page  = 1;

		do {
			$orders = wc_get_orders(
				array(
					'type'         => 'shop_order',
					'status'       => array( 'wc-processing', 'wc-completed', 'wc-on-hold', 'wc-cancelled', 'wc-refunded' ),
					'date_created' => $start . '...' . $until,
					'limit'        => 200,
					'paged'        => $page,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bestellungen eines Standorts, seitenweise zu je 200.
					'meta_query'   => array(
						array(
							'key'   => '_lbite_location_id',
							'value' => (int) $location_id,
						),
					),
				)
			);

			foreach ( $orders as $order ) {
				// Offene Tabs (F_TAB) sind noch nicht bezahlt und gehören erst nach dem Abschluss in den Bericht.
				if ( '1' === $order->get_meta( '_lbite_tab_open' ) ) {
					continue;
				}
				$rows[] = self::order_to_row( $order );
			}

			$page++;
		} while ( count( $orders ) === 200 );

		$report                = self::aggregate( $rows, $opening_float, self::get_cash_keys() );
		$report['location_id'] = (int) $location_id;
		$report['start']       = $start;
		$report['end']         = $until;

		return $report;
	}

	/**
	 * Lesbare Bezeichnung einer Zahlungsart.
	 *
	 * @param string $key Schlüssel aus dem Bericht.
	 * @return string
	 */
	public static function payment_label( $key ) {
		if ( 0 === strpos( $key, 'gateway:' ) ) {
			$id       = substr( $key, 8 );
			$gateways = function_exists( 'WC' ) && WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
			if ( isset( $gateways[ $id ] ) ) {
				return wp_strip_all_tags( $gateways[ $id ]->get_title() );
			}
			return 'none' === $id ? __( 'Other', 'libre-bite' ) : $id;
		}

		foreach ( (array) get_option( 'lbite_pos_payment_methods', array() ) as $method ) {
			if ( isset( $method['key'], $method['label'] ) && $method['key'] === $key ) {
				return $method['label'];
			}
		}

		$defaults = array(
			'cash'  => __( 'Cash', 'libre-bite' ),
			'card'  => __( 'Card', 'libre-bite' ),
			'twint' => 'TWINT',
			'other' => __( 'Other', 'libre-bite' ),
		);

		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : $key;
	}

	/*
	 * ------------------------------------------------------------------
	 * Oberfläche
	 * ------------------------------------------------------------------
	 */

	/**
	 * Admin-Seite registrieren.
	 */
	public function register_page() {
		add_submenu_page(
			'libre-bite',
			__( 'Cash Report', 'libre-bite' ),
			__( 'Cash Report', 'libre-bite' ),
			self::CAPABILITY,
			self::PAGE,
			array( $this, 'render_page' )
		);
		self::move_menu_entry_after_pos();
	}

	/**
	 * Menüeintrag direkt unter die Kasse stellen.
	 *
	 * Die Seite wird erst nach dem Hauptmenü registriert und landete sonst
	 * hinter den Einstellungen. Fehlt die Kasse, bleibt die Reihenfolge.
	 */
	private static function move_menu_entry_after_pos() {
		global $submenu;
		if ( empty( $submenu['libre-bite'] ) || ! is_array( $submenu['libre-bite'] ) ) {
			return;
		}
		$items = array_values( $submenu['libre-bite'] );
		$own   = null;
		$pos   = null;
		foreach ( $items as $index => $item ) {
			if ( isset( $item[2] ) && self::PAGE === $item[2] ) {
				$own = $index;
			} elseif ( isset( $item[2] ) && 'lbite-pos' === $item[2] ) {
				$pos = $index;
			}
		}
		if ( null === $own || null === $pos ) {
			return;
		}
		$entry = $items[ $own ];
		unset( $items[ $own ] );
		$items = array_values( $items );
		$pos   = $own < $pos ? $pos - 1 : $pos;
		array_splice( $items, $pos + 1, 0, array( $entry ) );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Reihenfolge des eigenen Untermenüs.
		$submenu['libre-bite'] = $items;
	}

	/**
	 * Standorte, auf die der aktuelle Benutzer zugreifen darf.
	 *
	 * @return WP_Post[]
	 */
	private static function get_accessible_locations() {
		return array_values(
			array_filter(
				LBite_Locations::get_all_locations(),
				function ( $loc ) {
					return LBite_Access::can_access_location( $loc->ID );
				}
			)
		);
	}

	/**
	 * Betrag formatieren (ohne HTML).
	 *
	 * @param float $amount Betrag.
	 * @return string
	 */
	private static function money( $amount ) {
		return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Berichtstabelle ausgeben (X und gespeicherte Z gleich dargestellt).
	 *
	 * @param array $report Bericht.
	 */
	private static function render_report_table( array $report ) {
		$fmt = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		?>
		<table class="widefat striped lbite-cash-report-table" style="max-width: 640px;">
			<tbody>
				<tr><th><?php esc_html_e( 'Period', 'libre-bite' ); ?></th><td><?php echo esc_html( wp_date( $fmt, $report['start'] ) . ' – ' . wp_date( $fmt, $report['end'] ) ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Orders', 'libre-bite' ); ?></th><td><?php echo esc_html( $report['orders'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Gross revenue', 'libre-bite' ); ?></th><td><strong><?php echo esc_html( self::money( $report['gross'] ) ); ?></strong></td></tr>
				<?php foreach ( $report['payments'] as $key => $amount ) : ?>
					<tr><th style="padding-left: 24px;"><?php echo esc_html( self::payment_label( $key ) ); ?></th><td><?php echo esc_html( self::money( $amount ) ); ?></td></tr>
				<?php endforeach; ?>
				<?php foreach ( $report['taxes'] as $label => $amount ) : ?>
					<tr><th><?php echo esc_html( $label ); ?></th><td><?php echo esc_html( self::money( $amount ) ); ?></td></tr>
				<?php endforeach; ?>
				<tr><th><?php esc_html_e( 'Tips', 'libre-bite' ); ?></th><td><?php echo esc_html( self::money( $report['tips'] ) ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Discounts', 'libre-bite' ); ?></th><td><?php echo esc_html( self::money( $report['discounts'] ) ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Rounding', 'libre-bite' ); ?></th><td><?php echo esc_html( self::money( $report['rounding'] ) ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Cancelled orders', 'libre-bite' ); ?></th><td><?php echo esc_html( $report['cancelled_count'] . ' / ' . self::money( $report['cancelled_total'] ) ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Opening float', 'libre-bite' ); ?></th><td><?php echo esc_html( self::money( $report['opening_float'] ) ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Cash sales', 'libre-bite' ); ?></th><td><?php echo esc_html( self::money( $report['cash_sales'] ) ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Expected cash in drawer', 'libre-bite' ); ?></th><td><strong><?php echo esc_html( self::money( $report['expected_cash'] ) ); ?></strong></td></tr>
				<?php if ( isset( $report['counted_cash'] ) && null !== $report['counted_cash'] ) : ?>
					<tr><th><?php esc_html_e( 'Counted cash', 'libre-bite' ); ?></th><td><?php echo esc_html( self::money( $report['counted_cash'] ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Difference', 'libre-bite' ); ?></th><td><strong style="color: <?php echo esc_attr( 0.0 === (float) $report['difference'] ? 'inherit' : '#b32d2e' ); ?>;"><?php echo esc_html( self::money( $report['difference'] ) ); ?></strong></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Admin-Seite ausgeben.
	 */
	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'No permission', 'libre-bite' ) );
		}

		$locations = self::get_accessible_locations();
		if ( empty( $locations ) ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Cash Report', 'libre-bite' ) . '</h1><p>' . esc_html__( 'No locations available.', 'libre-bite' ) . '</p></div>';
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reine Anzeigeauswahl.
		$location_id = isset( $_GET['location'] ) ? absint( $_GET['location'] ) : (int) $locations[0]->ID;
		if ( ! LBite_Access::can_access_location( $location_id ) ) {
			$location_id = (int) $locations[0]->ID;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reine Anzeigeauswahl.
		$view_id = isset( $_GET['z'] ) ? absint( $_GET['z'] ) : 0;
		$last    = self::get_last_z( $location_id );
		$float   = $last ? (float) get_post_meta( $last->ID, '_lbite_opening_float', true ) : 0.0;
		$page    = admin_url( 'admin.php?page=' . self::PAGE );
		?>
		<div class="wrap lbite-cash-report">
			<h1><?php esc_html_e( 'Cash Report', 'libre-bite' ); ?></h1>

			<div class="notice notice-info inline" style="max-width: 640px;">
				<p><?php esc_html_e( 'This report supports your daily cash count. It is not a certified cash register system: in Germany (KassenSichV/TSE) and Austria (RKSV) additional certified hardware or services are required by law.', 'libre-bite' ); ?></p>
			</div>

			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Anzeige einer Erfolgsmeldung. ?>
			<?php if ( isset( $_GET['closed'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Z report saved. A new period has started.', 'libre-bite' ); ?></p></div>
			<?php endif; ?>

			<form method="get" style="margin: 16px 0;">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE ); ?>">
				<label for="lbite-cash-location"><strong><?php esc_html_e( 'Location', 'libre-bite' ); ?></strong></label>
				<select id="lbite-cash-location" name="location" onchange="this.form.submit()">
					<?php foreach ( $locations as $loc ) : ?>
						<option value="<?php echo esc_attr( $loc->ID ); ?>" <?php selected( $location_id, $loc->ID ); ?>><?php echo esc_html( $loc->post_title ); ?></option>
					<?php endforeach; ?>
				</select>
			</form>

			<?php
			if ( $view_id ) :
				$z = get_post( $view_id );
				if ( $z && self::POST_TYPE === $z->post_type && LBite_Access::can_access_location( (int) get_post_meta( $z->ID, '_lbite_location_id', true ) ) ) :
					$data = get_post_meta( $z->ID, '_lbite_z_data', true );
					?>
					<h2><?php echo esc_html( $z->post_title ); ?></h2>
					<?php self::render_report_table( (array) $data ); ?>
					<p>
						<button type="button" class="button" onclick="window.print()"><?php esc_html_e( 'Print', 'libre-bite' ); ?></button>
						<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'lbite_z_csv' => $z->ID ), $page ), 'lbite_z_csv_' . $z->ID ) ); ?>"><?php esc_html_e( 'Export CSV', 'libre-bite' ); ?></a>
						<a class="button-link" href="<?php echo esc_url( add_query_arg( 'location', $location_id, $page ) ); ?>"><?php esc_html_e( 'Back to current period', 'libre-bite' ); ?></a>
					</p>
					<?php
				endif;
			else :
				$report = self::build_report( $location_id, $float );
				?>
				<h2><?php esc_html_e( 'X report (current period)', 'libre-bite' ); ?></h2>
				<?php self::render_report_table( $report ); ?>
				<p><button type="button" class="button" onclick="window.print()"><?php esc_html_e( 'Print', 'libre-bite' ); ?></button></p>

				<h2><?php esc_html_e( 'Close the day (Z report)', 'libre-bite' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width: 640px;">
					<?php wp_nonce_field( 'lbite_close_cash_day' ); ?>
					<input type="hidden" name="action" value="lbite_close_cash_day">
					<input type="hidden" name="location_id" value="<?php echo esc_attr( $location_id ); ?>">
					<table class="form-table">
						<tr>
							<th><label for="lbite-opening-float"><?php esc_html_e( 'Opening float', 'libre-bite' ); ?></label></th>
							<td><input type="number" step="0.05" min="0" id="lbite-opening-float" name="opening_float" value="<?php echo esc_attr( $float ); ?>" class="small-text"> <?php echo esc_html( get_woocommerce_currency() ); ?></td>
						</tr>
						<tr>
							<th><label for="lbite-counted-cash"><?php esc_html_e( 'Counted cash', 'libre-bite' ); ?></label></th>
							<td>
								<input type="number" step="0.05" min="0" id="lbite-counted-cash" name="counted_cash" value="" class="small-text"> <?php echo esc_html( get_woocommerce_currency() ); ?>
								<p class="description"><?php esc_html_e( 'Optional. Count the drawer and enter the amount to see the difference.', 'libre-bite' ); ?></p>
							</td>
						</tr>
					</table>
					<p><button type="submit" class="button button-primary" onclick="return confirm(this.dataset.confirm)" data-confirm="<?php esc_attr_e( 'Close the day now? The Z report cannot be changed afterwards.', 'libre-bite' ); ?>"><?php esc_html_e( 'Save Z report', 'libre-bite' ); ?></button></p>
				</form>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Previous Z reports', 'libre-bite' ); ?></h2>
			<?php
			$previous = get_posts(
				array(
					'post_type'      => self::POST_TYPE,
					'post_status'    => 'private',
					'posts_per_page' => 30,
					'orderby'        => 'ID',
					'order'          => 'DESC',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Liste auf 30 Einträge begrenzt.
					'meta_query'     => array(
						array(
							'key'   => '_lbite_location_id',
							'value' => $location_id,
						),
					),
				)
			);
			if ( empty( $previous ) ) :
				echo '<p>' . esc_html__( 'No Z reports yet.', 'libre-bite' ) . '</p>';
			else :
				?>
				<table class="widefat striped" style="max-width: 640px;">
					<thead><tr><th><?php esc_html_e( 'Report', 'libre-bite' ); ?></th><th><?php esc_html_e( 'Gross revenue', 'libre-bite' ); ?></th><th><?php esc_html_e( 'Difference', 'libre-bite' ); ?></th></tr></thead>
					<tbody>
					<?php
					foreach ( $previous as $z ) :
						$data = (array) get_post_meta( $z->ID, '_lbite_z_data', true );
						?>
						<tr>
							<td><a href="<?php echo esc_url( add_query_arg( array( 'location' => $location_id, 'z' => $z->ID ), $page ) ); ?>"><?php echo esc_html( $z->post_title ); ?></a></td>
							<td><?php echo esc_html( self::money( isset( $data['gross'] ) ? $data['gross'] : 0 ) ); ?></td>
							<td><?php echo esc_html( isset( $data['difference'] ) && null !== $data['difference'] ? self::money( $data['difference'] ) : '–' ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<style>
			@media print {
				#adminmenumain, #wpadminbar, #wpfooter, .notice, .lbite-cash-report form, .lbite-cash-report .button, .lbite-cash-report .button-link { display: none !important; }
				#wpcontent, #wpbody-content { margin: 0 !important; padding: 0 !important; }
			}
		</style>
		<?php
	}

	/**
	 * Z-Bericht speichern (admin-post).
	 */
	public function handle_close_day() {
		check_admin_referer( 'lbite_close_cash_day' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'No permission', 'libre-bite' ) );
		}

		$location_id = isset( $_POST['location_id'] ) ? absint( $_POST['location_id'] ) : 0;
		if ( ! $location_id || ! LBite_Access::can_access_location( $location_id ) ) {
			wp_die( esc_html__( 'No permission for this location', 'libre-bite' ) );
		}

		$float   = isset( $_POST['opening_float'] ) ? max( 0, (float) sanitize_text_field( wp_unslash( $_POST['opening_float'] ) ) ) : 0.0;
		$counted = isset( $_POST['counted_cash'] ) ? sanitize_text_field( wp_unslash( $_POST['counted_cash'] ) ) : '';
		$counted = '' === $counted ? null : max( 0, (float) $counted );

		$now                    = time();
		$report                 = self::build_report( $location_id, $float, $now );
		$report['counted_cash'] = $counted;
		$report['difference']   = self::difference( $counted, $report['expected_cash'] );
		$report['closed_by']    = get_current_user_id();

		// Fortlaufende Nummer je Standort.
		$counters                 = (array) get_option( 'lbite_z_counters', array() );
		$number                   = isset( $counters[ $location_id ] ) ? (int) $counters[ $location_id ] + 1 : 1;
		$counters[ $location_id ] = $number;
		update_option( 'lbite_z_counters', $counters, false );
		$report['number'] = $number;

		$title = sprintf(
			/* translators: 1: report number, 2: location name, 3: date. */
			__( 'Z report #%1$d – %2$s – %3$s', 'libre-bite' ),
			$number,
			get_the_title( $location_id ),
			wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $now )
		);

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => $title,
				'post_author' => get_current_user_id(),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			wp_die( esc_html( $post_id->get_error_message() ) );
		}

		update_post_meta( $post_id, '_lbite_location_id', $location_id );
		update_post_meta( $post_id, '_lbite_period_end', $now );
		update_post_meta( $post_id, '_lbite_opening_float', $float );
		update_post_meta( $post_id, '_lbite_z_data', $report );

		/**
		 * Ein Z-Bericht wurde gespeichert (Tagesabschluss).
		 *
		 * @param int   $post_id     ID des Berichts.
		 * @param array $report      Berichtsdaten.
		 * @param int   $location_id Standort-ID.
		 */
		do_action( 'lbite_z_report_created', $post_id, $report, $location_id );

		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE, 'location' => $location_id, 'z' => $post_id, 'closed' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Z-Bericht als CSV ausgeben.
	 */
	public function maybe_export_csv() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce wird unten geprüft.
		if ( ! isset( $_GET['page'], $_GET['lbite_z_csv'] ) || self::PAGE !== $_GET['page'] ) {
			return;
		}

		$z_id = absint( $_GET['lbite_z_csv'] );
		check_admin_referer( 'lbite_z_csv_' . $z_id );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'No permission', 'libre-bite' ) );
		}

		$z = get_post( $z_id );
		if ( ! $z || self::POST_TYPE !== $z->post_type || ! LBite_Access::can_access_location( (int) get_post_meta( $z_id, '_lbite_location_id', true ) ) ) {
			wp_die( esc_html__( 'No permission for this location', 'libre-bite' ) );
		}

		$data = (array) get_post_meta( $z_id, '_lbite_z_data', true );
		$rows = array(
			array( __( 'Report', 'libre-bite' ), $z->post_title ),
			array( __( 'Orders', 'libre-bite' ), $data['orders'] ),
			array( __( 'Gross revenue', 'libre-bite' ), $data['gross'] ),
		);
		foreach ( (array) $data['payments'] as $key => $amount ) {
			$rows[] = array( self::payment_label( $key ), $amount );
		}
		foreach ( (array) $data['taxes'] as $label => $amount ) {
			$rows[] = array( $label, $amount );
		}
		$rows[] = array( __( 'Tips', 'libre-bite' ), $data['tips'] );
		$rows[] = array( __( 'Discounts', 'libre-bite' ), $data['discounts'] );
		$rows[] = array( __( 'Rounding', 'libre-bite' ), $data['rounding'] );
		$rows[] = array( __( 'Cancelled orders', 'libre-bite' ), $data['cancelled_count'] . ' / ' . $data['cancelled_total'] );
		$rows[] = array( __( 'Opening float', 'libre-bite' ), $data['opening_float'] );
		$rows[] = array( __( 'Cash sales', 'libre-bite' ), $data['cash_sales'] );
		$rows[] = array( __( 'Expected cash in drawer', 'libre-bite' ), $data['expected_cash'] );
		$rows[] = array( __( 'Counted cash', 'libre-bite' ), null === $data['counted_cash'] ? '' : $data['counted_cash'] );
		$rows[] = array( __( 'Difference', 'libre-bite' ), null === $data['difference'] ? '' : $data['difference'] );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="z-report-' . $z_id . '.csv"' );
		$fp = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Ausgabe-Stream, keine Datei.
		fprintf( $fp, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
		foreach ( $rows as $row ) {
			fputcsv( $fp, $row, ';', '"', '' );
		}
		fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- siehe fopen().
		exit;
	}
}
