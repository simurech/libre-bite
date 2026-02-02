<?php
/**
 * Template: Optimierte Bestätigungsseite
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Order-Objekt.
if ( ! $order ) {
	return;
}

$brand_name = get_option( 'lb_brand_name', get_bloginfo( 'name' ) );
$brand_logo = get_option( 'lb_brand_logo', 0 );
$logo_url   = $brand_logo ? wp_get_attachment_image_url( $brand_logo, 'medium' ) : '';

// Order-Meta.
$location_id   = $order->get_meta( '_lb_location_id' );
$location_name = $order->get_meta( '_lb_location_name' );
$order_type    = $order->get_meta( '_lb_order_type' );
$pickup_time   = $order->get_meta( '_lb_pickup_time' );
$customer_name = $order->get_billing_first_name();

// Fallback: Standort-Name aus Post laden wenn nicht in Meta.
if ( empty( $location_name ) && $location_id ) {
	$location_post = get_post( $location_id );
	if ( $location_post ) {
		$location_name = $location_post->post_title;
	}
}

// Fallback: Standort aus Session laden wenn nicht in Order-Meta.
if ( empty( $location_name ) && WC()->session ) {
	$session_location_id = WC()->session->get( 'lb_location_id' );
	if ( $session_location_id ) {
		$location_post = get_post( $session_location_id );
		if ( $location_post ) {
			$location_name = $location_post->post_title;
			$location_id   = $session_location_id;
		}
	}
}

// Standort-Details.
$location_address = '';
$location_maps_url = '';
if ( $location_id ) {
	$location_address  = LB_Locations::get_formatted_address( $location_id );
	$location_maps_url = LB_Locations::get_maps_url( $location_id );
}

// Abholnummer (letzte 4 Ziffern der Bestellnummer).
$order_number  = $order->get_order_number();
$pickup_number = substr( $order_number, -4 );

// Steueranzeige-Einstellung (incl = Brutto, excl = Netto).
$tax_display = get_option( 'woocommerce_tax_display_cart', 'incl' );
?>

<div class="lb-thankyou-optimized">
	<?php if ( $logo_url ) : ?>
		<div class="lb-thankyou-logo">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $brand_name ); ?>">
		</div>
	<?php endif; ?>

	<div class="lb-thankyou-success">
		<div class="lb-thankyou-icon-wrapper">
			<svg class="lb-thankyou-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
				<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
			</svg>
		</div>
		<h1><?php esc_html_e( 'Bestellung erfolgreich!', 'libre-bite' ); ?></h1>
		<?php if ( $customer_name ) : ?>
			<p class="lb-thankyou-greeting">
				<?php
				/* translators: %s: customer name */
				printf( esc_html__( 'Danke, %s!', 'libre-bite' ), esc_html( $customer_name ) );
				?>
			</p>
		<?php endif; ?>
	</div>

	<div class="lb-thankyou-pickup-number">
		<span class="lb-pickup-label"><?php esc_html_e( 'Deine Abholnummer', 'libre-bite' ); ?></span>
		<span class="lb-pickup-number"><?php echo esc_html( $pickup_number ); ?></span>
	</div>

	<div class="lb-thankyou-details">
		<?php if ( $location_name ) : ?>
			<div class="lb-thankyou-detail">
				<svg class="lb-detail-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
					<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
				</svg>
				<div class="lb-detail-content">
					<span class="lb-detail-label"><?php esc_html_e( 'Standort', 'libre-bite' ); ?></span>
					<span class="lb-detail-value"><?php echo esc_html( $location_name ); ?></span>
					<?php if ( $location_address ) : ?>
						<?php if ( $location_maps_url ) : ?>
							<a href="<?php echo esc_url( $location_maps_url ); ?>" target="_blank" rel="noopener noreferrer" class="lb-detail-sub lb-maps-link">
								<?php echo esc_html( $location_address ); ?>
								<svg class="lb-external-link-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
									<polyline points="15 3 21 3 21 9"></polyline>
									<line x1="10" y1="14" x2="21" y2="3"></line>
								</svg>
							</a>
						<?php else : ?>
							<span class="lb-detail-sub"><?php echo esc_html( $location_address ); ?></span>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="lb-thankyou-detail">
			<svg class="lb-detail-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
				<path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
			</svg>
			<div class="lb-detail-content">
				<span class="lb-detail-label"><?php esc_html_e( 'Abholung', 'libre-bite' ); ?></span>
				<span class="lb-detail-value">
					<?php
					if ( 'later' === $order_type && $pickup_time ) {
						echo esc_html( wp_date( 'd.m.Y', strtotime( $pickup_time ) ) );
						echo ' ' . esc_html__( 'um', 'libre-bite' ) . ' ';
						echo esc_html( wp_date( 'H:i', strtotime( $pickup_time ) ) );
						echo ' ' . esc_html__( 'Uhr', 'libre-bite' );
					} else {
						esc_html_e( 'Sobald fertig', 'libre-bite' );
					}
					?>
				</span>
			</div>
		</div>
	</div>

	<div class="lb-thankyou-order">
		<h3><?php esc_html_e( 'Deine Bestellung', 'libre-bite' ); ?></h3>

		<table class="lb-order-items">
			<tbody>
				<?php foreach ( $order->get_items() as $item_id => $item ) : ?>
					<tr>
						<td class="lb-item-qty"><?php echo esc_html( $item->get_quantity() ); ?>x</td>
						<td class="lb-item-name">
							<?php echo esc_html( $item->get_name() ); ?>
							<?php
							// Meta-Daten anzeigen.
							$item_meta = $item->get_meta( 'Konfiguration' );
							if ( $item_meta ) :
								?>
								<span class="lb-item-meta"><?php echo esc_html( $item_meta ); ?></span>
							<?php endif; ?>
						</td>
						<td class="lb-item-total">
							<?php
							// Preis inkl. oder exkl. MwSt. je nach Shop-Einstellung.
							$item_price = 'incl' === $tax_display
								? $item->get_total() + $item->get_total_tax()
								: $item->get_total();
							echo wp_kses_post( wc_price( $item_price ) );
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<?php
				// Prüfen ob zusätzliche Zeilen nötig sind (Fees, Coupons, Versand, Steuern).
				$fees    = $order->get_fees();
				$coupons = $order->get_coupons();
				$has_shipping = $order->get_shipping_total() > 0;
				$has_tax = $order->get_total_tax() > 0 && 'excl' === $tax_display;
				$has_extras = ! empty( $fees ) || ! empty( $coupons ) || $has_shipping || $has_tax;

				// Zwischensumme berechnen (inkl. oder exkl. MwSt.).
				$subtotal = 'incl' === $tax_display
					? $order->get_subtotal() + $order->get_cart_tax() - array_sum( wp_list_pluck( $fees, 'total_tax' ) )
					: $order->get_subtotal();

				// Einfacher: Summe aller Artikelpreise.
				$subtotal = 0;
				foreach ( $order->get_items() as $calc_item ) {
					$subtotal += 'incl' === $tax_display
						? $calc_item->get_total() + $calc_item->get_total_tax()
						: $calc_item->get_total();
				}

				if ( $has_extras ) :
					?>
					<!-- Zwischensumme -->
					<tr class="lb-subtotal-row">
						<td colspan="2"><?php esc_html_e( 'Zwischensumme', 'libre-bite' ); ?></td>
						<td><?php echo wp_kses_post( wc_price( $subtotal ) ); ?></td>
					</tr>

					<?php
					// Gutscheine anzeigen.
					foreach ( $coupons as $coupon ) :
						?>
						<tr class="lb-coupon-row">
							<td colspan="2">
								<?php
								/* translators: %s: coupon code */
								printf( esc_html__( 'Gutschein: %s', 'libre-bite' ), esc_html( $coupon->get_code() ) );
								?>
							</td>
							<td>-<?php echo wp_kses_post( wc_price( $coupon->get_discount() ) ); ?></td>
						</tr>
					<?php endforeach; ?>

					<?php
					// Fees (Trinkgeld, Rundung, etc.) anzeigen.
					foreach ( $fees as $fee ) :
						$fee_total = $fee->get_total();
						$fee_sign  = $fee_total < 0 ? '' : '';
						?>
						<tr class="lb-fee-row">
							<td colspan="2"><?php echo esc_html( $fee->get_name() ); ?></td>
							<td><?php echo wp_kses_post( wc_price( $fee_total ) ); ?></td>
						</tr>
					<?php endforeach; ?>

					<?php
					// Versand anzeigen wenn vorhanden.
					if ( $has_shipping ) :
						?>
						<tr class="lb-shipping-row">
							<td colspan="2"><?php esc_html_e( 'Versand', 'libre-bite' ); ?></td>
							<td><?php echo wp_kses_post( wc_price( $order->get_shipping_total() ) ); ?></td>
						</tr>
					<?php endif; ?>

					<?php
					// MwSt. anzeigen wenn Preise exkl. MwSt. angezeigt werden.
					if ( $has_tax ) :
						?>
						<tr class="lb-tax-row">
							<td colspan="2"><?php esc_html_e( 'MwSt.', 'libre-bite' ); ?></td>
							<td><?php echo wp_kses_post( wc_price( $order->get_total_tax() ) ); ?></td>
						</tr>
					<?php endif; ?>

				<?php endif; ?>

				<!-- Total -->
				<tr class="lb-total-row">
					<td colspan="2"><strong><?php esc_html_e( 'Total', 'libre-bite' ); ?></strong></td>
					<td><strong><?php echo wp_kses_post( wc_price( $order->get_total() ) ); ?></strong></td>
				</tr>
			</tfoot>
		</table>
	</div>

	<div class="lb-thankyou-footer">
		<p><?php echo esc_html( $brand_name ); ?></p>
	</div>
</div>
