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

$lbite_period     = isset( $_GET['lbite_period'] ) ? sanitize_key( wp_unslash( $_GET['lbite_period'] ) ) : '7days'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$lbite_filter_loc = isset( $_GET['lbite_location'] ) ? intval( wp_unslash( $_GET['lbite_location'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
	// Für Manager: Standort-Filter auf erlaubte IDs begrenzen.
	if ( $lbite_filter_loc && ! in_array( $lbite_filter_loc, $lbite_stat_allowed_ids, true ) ) {
		$lbite_filter_loc = 0;
	}
}
$lbite_valid_periods = array_keys( $lbite_periods );
if ( ! in_array( $lbite_period, $lbite_valid_periods, true ) ) {
	$lbite_period = 'today';
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

$lbite_totals           = array();
$lbite_payment_totals   = array();
$lbite_pm_config        = get_option( 'lbite_pos_payment_methods', array() );
$lbite_pm_label_map     = array(
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

foreach ( $lbite_stat_orders as $lbite_order ) {
	$lbite_loc_id = (int) $lbite_order->get_meta( '_lbite_location_id' );

	// Manager: nur zugeteilte Standorte auswerten.
	if ( null !== $lbite_stat_allowed_ids && ! in_array( $lbite_loc_id, $lbite_stat_allowed_ids, true ) ) {
		continue;
	}

	// Standort-Filter anwenden (alle Rollen).
	if ( $lbite_filter_loc && $lbite_loc_id !== $lbite_filter_loc ) {
		continue;
	}

	$lbite_loc_name = $lbite_loc_id ? get_the_title( $lbite_loc_id ) : __( 'No location', 'libre-bite' );
	if ( ! isset( $lbite_totals[ $lbite_loc_name ] ) ) {
		$lbite_totals[ $lbite_loc_name ] = array( 'count' => 0, 'revenue' => 0.0, 'products' => array() );
	}
	$lbite_totals[ $lbite_loc_name ]['count']++;
	$lbite_totals[ $lbite_loc_name ]['revenue'] += (float) $lbite_order->get_total();
	foreach ( $lbite_order->get_items() as $lbite_item ) {
		$lbite_pname = $lbite_item->get_name();
		$lbite_qty   = (int) $lbite_item->get_quantity();
		if ( ! isset( $lbite_totals[ $lbite_loc_name ]['products'][ $lbite_pname ] ) ) {
			$lbite_totals[ $lbite_loc_name ]['products'][ $lbite_pname ] = 0;
		}
		$lbite_totals[ $lbite_loc_name ]['products'][ $lbite_pname ] += $lbite_qty;
	}

	// Zahlungsart für POS-Statistik erfassen.
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

$lbite_total_revenue = array_sum( array_column( $lbite_totals, 'revenue' ) );
$lbite_total_orders  = array_sum( array_column( $lbite_totals, 'count' ) );
$lbite_avg_order     = $lbite_total_orders > 0 ? $lbite_total_revenue / $lbite_total_orders : 0;
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Statistics', 'libre-bite' ); ?></h1>

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
	</div>

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

	<?php if ( ! empty( $lbite_payment_totals ) ) : ?>
	<h2 style="margin-top: 32px;"><?php esc_html_e( 'Payment Methods (POS)', 'libre-bite' ); ?></h2>
	<table class="widefat" style="max-width: 500px; margin-bottom: 32px;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Payment Method', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Orders', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Revenue', 'libre-bite' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $lbite_payment_totals as $lbite_pm_n => $lbite_pm_d ) : ?>
			<tr>
				<td><strong><?php echo esc_html( $lbite_pm_n ); ?></strong></td>
				<td><?php echo esc_html( $lbite_pm_d['count'] ); ?></td>
				<td><?php echo wp_kses_post( wc_price( $lbite_pm_d['revenue'] ) ); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>

	<?php if ( ! empty( $lbite_totals ) ) : ?>
	<table class="widefat" style="max-width: 700px;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Location', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Orders', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Revenue', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Avg. Order Value', 'libre-bite' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $lbite_totals as $lbite_loc_n => $lbite_d ) :
				$lbite_loc_avg = $lbite_d['count'] > 0 ? $lbite_d['revenue'] / $lbite_d['count'] : 0;
				arsort( $lbite_d['products'] );
				$lbite_top_products = array_slice( $lbite_d['products'], 0, 5, true );
			?>
			<tr>
				<td><strong><?php echo esc_html( $lbite_loc_n ); ?></strong></td>
				<td><?php echo esc_html( $lbite_d['count'] ); ?></td>
				<td><?php echo wp_kses_post( wc_price( $lbite_d['revenue'] ) ); ?></td>
				<td><?php echo wp_kses_post( wc_price( $lbite_loc_avg ) ); ?></td>
			</tr>
			<?php if ( ! empty( $lbite_top_products ) ) : ?>
			<tr>
				<td colspan="4" style="padding: 0 16px 8px; background: #f9f9f9;">
					<details>
						<summary style="cursor: pointer; color: #2271b1; font-size: 12px; padding: 4px 0;">
							<?php
							printf(
								/* translators: %d: number of products */
								esc_html__( 'Top products (%d)', 'libre-bite' ),
								count( $lbite_top_products )
							);
							?>
						</summary>
						<ul style="margin: 4px 0 0 16px; padding: 0; font-size: 12px; color: #50575e;">
							<?php foreach ( $lbite_top_products as $lbite_pn => $lbite_pq ) : ?>
							<li><?php echo esc_html( $lbite_pn ); ?> &times; <?php echo esc_html( $lbite_pq ); ?></li>
							<?php endforeach; ?>
						</ul>
					</details>
				</td>
			</tr>
			<?php endif; ?>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php else : ?>
	<p class="description"><?php esc_html_e( 'No orders found for the selected period.', 'libre-bite' ); ?></p>
	<?php endif; ?>
</div>
