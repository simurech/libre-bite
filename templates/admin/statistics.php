<?php
/**
 * Statistik-Seite
 *
 * Eigenständige Admin-Seite. Zeigt Umsatz und Bestellanzahl pro Standort,
 * filterbar nach Zeitraum. Manager sehen nur ihre zugeteilten Standorte.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'lbite_view_statistics' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'libre-bite' ) );
}

// Manager-Filter: null = alle Standorte (Admin), Array = erlaubte Location-IDs (Manager)
$lbite_stat_allowed_ids = null;
if ( ! current_user_can( 'lbite_manage_settings' ) ) {
	$lbite_stat_allowed_ids = get_user_meta( get_current_user_id(), 'lbite_assigned_locations', true );
	if ( ! is_array( $lbite_stat_allowed_ids ) ) {
		$lbite_stat_allowed_ids = array();
	}
	$lbite_stat_allowed_ids = array_map( 'intval', $lbite_stat_allowed_ids );
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$lbite_period     = isset( $_GET['lbite_period'] ) ? sanitize_key( wp_unslash( $_GET['lbite_period'] ) ) : '7days';
$lbite_filter_loc = isset( $_GET['lbite_location'] ) ? intval( wp_unslash( $_GET['lbite_location'] ) ) : 0;
// phpcs:enable

$lbite_periods    = array(
	'today'  => __( 'Today', 'libre-bite' ),
	'7days'  => __( 'Last 7 Days', 'libre-bite' ),
	'30days' => __( 'Last 30 Days', 'libre-bite' ),
	'year'   => __( 'This Year', 'libre-bite' ),
);

// Standort-Liste für Admin-Dropdown laden.
$lbite_stat_locations = get_posts(
	array(
		'post_type'      => 'lbite_location',
		'posts_per_page' => 100,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	)
);

// Manager: Auswahl auf zugewiesene Standorte einschränken.
if ( null !== $lbite_stat_allowed_ids ) {
	$lbite_stat_locations = array_filter(
		$lbite_stat_locations,
		function( $loc ) use ( $lbite_stat_allowed_ids ) {
			return in_array( $loc->ID, $lbite_stat_allowed_ids, true );
		}
	);
	$lbite_stat_locations = array_values( $lbite_stat_locations );
	if ( $lbite_filter_loc && ! in_array( $lbite_filter_loc, $lbite_stat_allowed_ids, true ) ) {
		$lbite_filter_loc = 0;
	}
}

$lbite_valid_periods = array_keys( $lbite_periods );
if ( ! in_array( $lbite_period, $lbite_valid_periods, true ) ) {
	$lbite_period = '7days';
}

$lbite_now = current_time( 'timestamp' );
switch ( $lbite_period ) {
	case '7days':
		$lbite_date_after = date( 'Y-m-d', strtotime( '-7 days', $lbite_now ) );
		break;
	case '30days':
		$lbite_date_after = date( 'Y-m-d', strtotime( '-30 days', $lbite_now ) );
		break;
	case 'year':
		$lbite_date_after = date( 'Y-01-01', $lbite_now );
		break;
	default:
		$lbite_date_after = date( 'Y-m-d', $lbite_now );
}

$lbite_stat_orders = wc_get_orders( array(
	'status'     => array( 'wc-completed', 'wc-processing' ),
	'date_after' => $lbite_date_after,
	'limit'      => -1,
	'return'     => 'objects',
) );

// Zahlungsarten-Label-Map aufbauen.
$lbite_pm_config    = get_option( 'lbite_pos_payment_methods', array() );
$lbite_pm_label_map = array(
	'cash'  => __( 'Cash', 'libre-bite' ),
	'card'  => __( 'Card', 'libre-bite' ),
	'twint' => __( 'Twint', 'libre-bite' ),
	'other' => __( 'Other', 'libre-bite' ),
);
foreach ( $lbite_pm_config as $lbite_pm ) {
	if ( ! empty( $lbite_pm['key'] ) && ! empty( $lbite_pm['label'] ) ) {
		$lbite_pm_label_map[ $lbite_pm['key'] ] = $lbite_pm['label'];
	}
}

// Datenstrukturen.
$lbite_totals         = array(); // Pro Standort.
$lbite_payment_totals = array(); // Pro Zahlungsart.
$lbite_product_totals = array(); // Global: Produkte [name => ['qty', 'revenue']].
$lbite_addon_totals   = array(); // Global: Add-ons [name => ['qty', 'revenue']].
$lbite_addon_combos   = array(); // Add-on → Produkt-Kombination [addon => [product => count]].

foreach ( $lbite_stat_orders as $lbite_order ) {
	$lbite_loc_id = (int) $lbite_order->get_meta( '_lbite_location_id' );

	if ( null !== $lbite_stat_allowed_ids && ! in_array( $lbite_loc_id, $lbite_stat_allowed_ids, true ) ) {
		continue;
	}
	if ( $lbite_filter_loc && $lbite_loc_id !== $lbite_filter_loc ) {
		continue;
	}

	$lbite_loc_name = $lbite_loc_id ? get_the_title( $lbite_loc_id ) : __( 'No location', 'libre-bite' );
	if ( ! isset( $lbite_totals[ $lbite_loc_name ] ) ) {
		$lbite_totals[ $lbite_loc_name ] = array( 'count' => 0, 'revenue' => 0.0 );
	}
	$lbite_totals[ $lbite_loc_name ]['count']++;
	$lbite_totals[ $lbite_loc_name ]['revenue'] += (float) $lbite_order->get_total();

	// Produkte auswerten.
	foreach ( $lbite_order->get_items() as $lbite_item ) {
		$lbite_pname = $lbite_item->get_name();
		$lbite_qty   = (int) $lbite_item->get_quantity();
		$lbite_prev  = (float) $lbite_item->get_total();
		if ( ! isset( $lbite_product_totals[ $lbite_pname ] ) ) {
			$lbite_product_totals[ $lbite_pname ] = array( 'qty' => 0, 'revenue' => 0.0 );
		}
		$lbite_product_totals[ $lbite_pname ]['qty']     += $lbite_qty;
		$lbite_product_totals[ $lbite_pname ]['revenue'] += $lbite_prev;

		// Addon-Kombination aus Produkt-Meta auslesen.
		$lbite_addon_meta = $lbite_item->get_meta( 'Add-on' );
		if ( $lbite_addon_meta ) {
			foreach ( array_map( 'trim', explode( ',', $lbite_addon_meta ) ) as $lbite_an ) {
				if ( ! isset( $lbite_addon_combos[ $lbite_an ] ) ) {
					$lbite_addon_combos[ $lbite_an ] = array();
				}
				if ( ! isset( $lbite_addon_combos[ $lbite_an ][ $lbite_pname ] ) ) {
					$lbite_addon_combos[ $lbite_an ][ $lbite_pname ] = 0;
				}
				$lbite_addon_combos[ $lbite_an ][ $lbite_pname ]++;
			}
		}
	}

	// Add-on-Gebühren auswerten (WC_Order_Item_Fee, ohne Trinkgeld/Rundung).
	$lbite_tip_amount = (float) $lbite_order->get_meta( '_lbite_tip_amount' );
	foreach ( $lbite_order->get_fees() as $lbite_fee ) {
		$lbite_fee_total = (float) $lbite_fee->get_total();
		// Trinkgeld und Rundungsbeträge ausblenden.
		if ( $lbite_tip_amount > 0 && abs( $lbite_fee_total - $lbite_tip_amount ) < 0.01 ) {
			continue;
		}
		if ( abs( $lbite_fee_total ) <= 0.05 ) {
			continue;
		}
		$lbite_fn = $lbite_fee->get_name();
		if ( ! isset( $lbite_addon_totals[ $lbite_fn ] ) ) {
			$lbite_addon_totals[ $lbite_fn ] = array( 'qty' => 0, 'revenue' => 0.0 );
		}
		$lbite_addon_totals[ $lbite_fn ]['qty']++;
		$lbite_addon_totals[ $lbite_fn ]['revenue'] += $lbite_fee_total;
	}

	// Zahlungsart erfassen.
	$lbite_pm_key = $lbite_order->get_meta( '_lbite_payment_method' );
	if ( $lbite_pm_key ) {
		$lbite_pm_display = isset( $lbite_pm_label_map[ $lbite_pm_key ] ) ? $lbite_pm_label_map[ $lbite_pm_key ] : $lbite_pm_key;
		if ( ! isset( $lbite_payment_totals[ $lbite_pm_display ] ) ) {
			$lbite_payment_totals[ $lbite_pm_display ] = array( 'count' => 0, 'revenue' => 0.0 );
		}
		$lbite_payment_totals[ $lbite_pm_display ]['count']++;
		$lbite_payment_totals[ $lbite_pm_display ]['revenue'] += (float) $lbite_order->get_total();
	}
}

// Gesamtwerte.
$lbite_total_revenue = array_sum( array_column( $lbite_totals, 'revenue' ) );
$lbite_total_orders  = array_sum( array_column( $lbite_totals, 'count' ) );
$lbite_avg_order     = $lbite_total_orders > 0 ? $lbite_total_revenue / $lbite_total_orders : 0;

// Top-Produkte sortieren.
$lbite_top_by_qty     = $lbite_product_totals;
$lbite_top_by_revenue = $lbite_product_totals;
uasort( $lbite_top_by_qty, fn( $a, $b ) => $b['qty'] <=> $a['qty'] );
uasort( $lbite_top_by_revenue, fn( $a, $b ) => $b['revenue'] <=> $a['revenue'] );
$lbite_top_by_qty     = array_slice( $lbite_top_by_qty, 0, 10, true );
$lbite_top_by_revenue = array_slice( $lbite_top_by_revenue, 0, 10, true );

// Add-ons sortieren.
uasort( $lbite_addon_totals, fn( $a, $b ) => $b['qty'] <=> $a['qty'] );

// CSV-Export: WordPress hat zu diesem Zeitpunkt bereits HTML ausgegeben (Admin-Header).
// ob_end_clean() leert alle Puffer, damit nur sauberes CSV gesendet wird.
if ( isset( $_GET['lbite_export'] ) && 'csv' === sanitize_key( wp_unslash( $_GET['lbite_export'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$lbite_nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! wp_verify_nonce( $lbite_nonce, 'lbite_stat_export' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'libre-bite' ) );
	}
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}
	$lbite_filename = 'lbite-statistics-' . $lbite_period . '-' . date( 'Y-m-d' ) . '.csv';
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $lbite_filename . '"' );
	$lbite_fp = fopen( 'php://output', 'w' );
	fprintf( $lbite_fp, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) ); // UTF-8 BOM für Excel.
	fputcsv( $lbite_fp, array(
		__( 'Order #', 'libre-bite' ),
		__( 'Date', 'libre-bite' ),
		__( 'Location', 'libre-bite' ),
		__( 'Total', 'libre-bite' ),
		__( 'Payment Method', 'libre-bite' ),
		__( 'Order Type', 'libre-bite' ),
		__( 'Products', 'libre-bite' ),
	), ';' );
	foreach ( $lbite_stat_orders as $lbite_csv_order ) {
		$lbite_csv_loc_id  = (int) $lbite_csv_order->get_meta( '_lbite_location_id' );
		if ( null !== $lbite_stat_allowed_ids && ! in_array( $lbite_csv_loc_id, $lbite_stat_allowed_ids, true ) ) {
			continue;
		}
		if ( $lbite_filter_loc && $lbite_csv_loc_id !== $lbite_filter_loc ) {
			continue;
		}
		$lbite_csv_pm_key  = $lbite_csv_order->get_meta( '_lbite_payment_method' );
		$lbite_csv_pm      = isset( $lbite_pm_label_map[ $lbite_csv_pm_key ] ) ? $lbite_pm_label_map[ $lbite_csv_pm_key ] : $lbite_csv_pm_key;
		$lbite_csv_stype   = $lbite_csv_order->get_meta( '_lbite_service_type' );
		$lbite_csv_items   = array();
		foreach ( $lbite_csv_order->get_items() as $lbite_csv_item ) {
			$lbite_csv_items[] = $lbite_csv_item->get_quantity() . 'x ' . $lbite_csv_item->get_name();
		}
		fputcsv( $lbite_fp, array(
			$lbite_csv_order->get_order_number(),
			$lbite_csv_order->get_date_created()->date( 'Y-m-d H:i' ),
			$lbite_csv_loc_id ? get_the_title( $lbite_csv_loc_id ) : '',
			number_format( (float) $lbite_csv_order->get_total(), 2, '.', '' ),
			$lbite_csv_pm,
			$lbite_csv_stype,
			implode( ' | ', $lbite_csv_items ),
		), ';' );
	}
	fclose( $lbite_fp );
	exit;
}

// CSV-Export-URL.
$lbite_export_url = wp_nonce_url(
	add_query_arg( array( 'lbite_export' => 'csv', 'lbite_period' => $lbite_period, 'lbite_location' => $lbite_filter_loc ?: '' ) ),
	'lbite_stat_export'
);
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Statistics', 'libre-bite' ); ?></h1>

	<!-- Filter-Leiste -->
	<div style="margin: 16px 0 20px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
		<?php foreach ( $lbite_periods as $lbite_pk => $lbite_plabel ) : ?>
			<a
				href="<?php echo esc_url( add_query_arg( array( 'lbite_period' => $lbite_pk, 'lbite_location' => $lbite_filter_loc ?: '' ) ) ); ?>"
				class="button <?php echo $lbite_period === $lbite_pk ? 'button-primary' : ''; ?>"
			><?php echo esc_html( $lbite_plabel ); ?></a>
		<?php endforeach; ?>

		<?php if ( ! empty( $lbite_stat_locations ) ) : ?>
		<span style="margin-left: 12px; color: #50575e;"><?php esc_html_e( 'Location:', 'libre-bite' ); ?></span>
		<select id="lbite-stat-location" onchange="window.location.href=this.value;" style="min-height: 32px; font-size: 13px;">
			<option value="<?php echo esc_url( add_query_arg( 'lbite_location', '' ) ); ?>" <?php selected( $lbite_filter_loc, 0 ); ?>>
				<?php esc_html_e( 'All Locations', 'libre-bite' ); ?>
			</option>
			<?php foreach ( $lbite_stat_locations as $lbite_stat_loc ) : ?>
			<option value="<?php echo esc_url( add_query_arg( 'lbite_location', $lbite_stat_loc->ID ) ); ?>" <?php selected( $lbite_filter_loc, $lbite_stat_loc->ID ); ?>>
				<?php echo esc_html( $lbite_stat_loc->post_title ); ?>
			</option>
			<?php endforeach; ?>
		</select>
		<?php endif; ?>

		<a href="<?php echo esc_url( $lbite_export_url ); ?>" class="button" style="margin-left: auto;">
			⬇ <?php esc_html_e( 'Export CSV', 'libre-bite' ); ?>
		</a>
	</div>

	<!-- Kennzahlen-Kacheln -->
	<div style="display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap;">
		<div style="background:#fff; border:1px solid #dcdcde; border-radius:6px; padding:20px 24px; min-width:160px; flex:1;">
			<div style="font-size:28px; font-weight:700; color:#1d2327;"><?php echo wp_kses_post( wc_price( $lbite_total_revenue ) ); ?></div>
			<div style="color:#50575e; font-size:13px; margin-top:4px;"><?php esc_html_e( 'Revenue', 'libre-bite' ); ?></div>
		</div>
		<div style="background:#fff; border:1px solid #dcdcde; border-radius:6px; padding:20px 24px; min-width:160px; flex:1;">
			<div style="font-size:28px; font-weight:700; color:#1d2327;"><?php echo esc_html( $lbite_total_orders ); ?></div>
			<div style="color:#50575e; font-size:13px; margin-top:4px;"><?php esc_html_e( 'Orders', 'libre-bite' ); ?></div>
		</div>
		<div style="background:#fff; border:1px solid #dcdcde; border-radius:6px; padding:20px 24px; min-width:160px; flex:1;">
			<div style="font-size:28px; font-weight:700; color:#1d2327;"><?php echo wp_kses_post( wc_price( $lbite_avg_order ) ); ?></div>
			<div style="color:#50575e; font-size:13px; margin-top:4px;"><?php esc_html_e( 'Avg. Order Value', 'libre-bite' ); ?></div>
		</div>
	</div>

	<!-- Zahlungsarten -->
	<?php if ( ! empty( $lbite_payment_totals ) ) :
		$lbite_pm_total_rev = array_sum( array_column( $lbite_payment_totals, 'revenue' ) );
	?>
	<h2><?php esc_html_e( 'Payment Methods (POS)', 'libre-bite' ); ?></h2>
	<table class="widefat" style="max-width: 560px; margin-bottom: 32px;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Payment Method', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Orders', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Revenue', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Share', 'libre-bite' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $lbite_payment_totals as $lbite_pm_n => $lbite_pm_d ) :
				$lbite_share = $lbite_pm_total_rev > 0 ? round( $lbite_pm_d['revenue'] / $lbite_pm_total_rev * 100, 1 ) : 0;
			?>
			<tr>
				<td><strong><?php echo esc_html( $lbite_pm_n ); ?></strong></td>
				<td><?php echo esc_html( $lbite_pm_d['count'] ); ?></td>
				<td><?php echo wp_kses_post( wc_price( $lbite_pm_d['revenue'] ) ); ?></td>
				<td>
					<div style="display:flex; align-items:center; gap:8px;">
						<div style="background:#e1e1e1; border-radius:4px; height:8px; width:80px;">
							<div style="background:#2271b1; border-radius:4px; height:8px; width:<?php echo esc_attr( $lbite_share ); ?>%;"></div>
						</div>
						<?php echo esc_html( $lbite_share ); ?>%
					</div>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>

	<!-- Top-Produkte -->
	<?php if ( ! empty( $lbite_product_totals ) ) : ?>
	<h2><?php esc_html_e( 'Top Products', 'libre-bite' ); ?></h2>
	<div style="display:flex; gap:24px; flex-wrap:wrap; margin-bottom:32px;">
		<!-- Nach Menge -->
		<div style="flex:1; min-width:280px;">
			<h3 style="margin-top:0; font-size:14px; color:#50575e;"><?php esc_html_e( 'By Quantity', 'libre-bite' ); ?></h3>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Product', 'libre-bite' ); ?></th>
						<th style="text-align:right;"><?php esc_html_e( 'Qty', 'libre-bite' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $lbite_top_by_qty as $lbite_pn => $lbite_pd ) : ?>
					<tr>
						<td><?php echo esc_html( $lbite_pn ); ?></td>
						<td style="text-align:right; font-weight:600;"><?php echo esc_html( $lbite_pd['qty'] ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<!-- Nach Umsatz -->
		<div style="flex:1; min-width:280px;">
			<h3 style="margin-top:0; font-size:14px; color:#50575e;"><?php esc_html_e( 'By Revenue', 'libre-bite' ); ?></h3>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Product', 'libre-bite' ); ?></th>
						<th style="text-align:right;"><?php esc_html_e( 'Revenue', 'libre-bite' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $lbite_top_by_revenue as $lbite_pn => $lbite_pd ) : ?>
					<tr>
						<td><?php echo esc_html( $lbite_pn ); ?></td>
						<td style="text-align:right; font-weight:600;"><?php echo wp_kses_post( wc_price( $lbite_pd['revenue'] ) ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php endif; ?>

	<!-- Add-ons -->
	<?php if ( ! empty( $lbite_addon_totals ) ) : ?>
	<h2><?php esc_html_e( 'Add-ons', 'libre-bite' ); ?></h2>
	<table class="widefat" style="max-width: 700px; margin-bottom: 32px;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Add-on', 'libre-bite' ); ?></th>
				<th style="text-align:right;"><?php esc_html_e( 'Qty', 'libre-bite' ); ?></th>
				<th style="text-align:right;"><?php esc_html_e( 'Revenue', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Combined with', 'libre-bite' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $lbite_addon_totals as $lbite_an => $lbite_ad ) :
				$lbite_combos = isset( $lbite_addon_combos[ $lbite_an ] ) ? $lbite_addon_combos[ $lbite_an ] : array();
				arsort( $lbite_combos );
				$lbite_combos_top = array_slice( $lbite_combos, 0, 3, true );
			?>
			<tr>
				<td><strong><?php echo esc_html( $lbite_an ); ?></strong></td>
				<td style="text-align:right;"><?php echo esc_html( $lbite_ad['qty'] ); ?></td>
				<td style="text-align:right;"><?php echo wp_kses_post( wc_price( $lbite_ad['revenue'] ) ); ?></td>
				<td style="font-size:12px; color:#50575e;">
					<?php
					$lbite_combo_parts = array();
					foreach ( $lbite_combos_top as $lbite_cprod => $lbite_ccnt ) {
						$lbite_combo_parts[] = esc_html( $lbite_cprod ) . ' (' . esc_html( $lbite_ccnt ) . '×)';
					}
					echo implode( ', ', $lbite_combo_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bereits escaped.
					?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>

	<!-- Standort-Übersicht -->
	<?php if ( ! empty( $lbite_totals ) ) : ?>
	<h2><?php esc_html_e( 'By Location', 'libre-bite' ); ?></h2>
	<table class="widefat" style="max-width: 600px; margin-bottom: 24px;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Location', 'libre-bite' ); ?></th>
				<th style="text-align:right;"><?php esc_html_e( 'Orders', 'libre-bite' ); ?></th>
				<th style="text-align:right;"><?php esc_html_e( 'Revenue', 'libre-bite' ); ?></th>
				<th style="text-align:right;"><?php esc_html_e( 'Avg. Order Value', 'libre-bite' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $lbite_totals as $lbite_loc_n => $lbite_d ) :
				$lbite_loc_avg = $lbite_d['count'] > 0 ? $lbite_d['revenue'] / $lbite_d['count'] : 0;
			?>
			<tr>
				<td><strong><?php echo esc_html( $lbite_loc_n ); ?></strong></td>
				<td style="text-align:right;"><?php echo esc_html( $lbite_d['count'] ); ?></td>
				<td style="text-align:right;"><?php echo wp_kses_post( wc_price( $lbite_d['revenue'] ) ); ?></td>
				<td style="text-align:right;"><?php echo wp_kses_post( wc_price( $lbite_loc_avg ) ); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php else : ?>
	<p class="description"><?php esc_html_e( 'No orders found for the selected period.', 'libre-bite' ); ?></p>
	<?php endif; ?>
</div>
