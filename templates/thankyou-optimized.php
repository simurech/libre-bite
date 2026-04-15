<?php
/**
 * Template: Optimierte Bestätigungsseite
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-Datei, wird innerhalb einer Klassen-Methode via include geladen; Variablen befinden sich im Methoden-Scope, nicht im globalen Namespace.

// Order-Objekt.
if ( ! $lbite_order ) {
	return;
}

$brand_name = get_option( 'lbite_brand_name', get_bloginfo( 'name' ) );
$brand_logo = get_option( 'lbite_brand_logo', 0 );
$logo_url   = $brand_logo ? wp_get_attachment_image_url( $brand_logo, 'medium' ) : '';

// Order-Meta.
$location_id   = $lbite_order->get_meta( '_lbite_location_id' );
$location_name = $lbite_order->get_meta( '_lbite_location_name' );
$lbite_order_type    = $lbite_order->get_meta( '_lbite_order_type' );
$lbite_pickup_time   = $lbite_order->get_meta( '_lbite_pickup_time' );
$lbite_customer_name = $lbite_order->get_billing_first_name();

// Fallback: Standort-Name aus Post laden wenn nicht in Meta.
if ( empty( $location_name ) && $location_id ) {
	$location_post = get_post( $location_id );
	if ( $location_post ) {
		$location_name = $location_post->post_title;
	}
}
// Standort-Adresse und Maps-URL.
$location_address  = $location_id ? LBite_Locations::get_formatted_address( $location_id ) : '';
$location_maps_url = $location_id ? LBite_Locations::get_maps_url( $location_id ) : '';

// Abholnummer (letzte 4 Ziffern der Bestellnummer).
$order_number  = $lbite_order->get_order_number();
$pickup_number = substr( $order_number, -4 );

// Steueranzeige-Einstellung (incl = Brutto, excl = Netto).
$tax_display = get_option( 'woocommerce_tax_display_cart', 'incl' );
?>

<div class="lbite-thankyou-optimized">
	<?php if ( $logo_url ) : ?>
		<div class="lbite-thankyou-logo">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $brand_name ); ?>">
		</div>
	<?php endif; ?>

	<div class="lbite-thankyou-success">
		<div class="lbite-thankyou-icon-wrapper">
			<svg class="lbite-thankyou-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
				<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
			</svg>
		</div>
		<h1><?php esc_html_e( 'Order successful!', 'libre-bite' ); ?></h1>
		<?php if ( $lbite_customer_name ) : ?>
			<p class="lbite-thankyou-greeting">
				<?php
				/* translators: %s: customer name */
				printf( esc_html__( 'Thank you, %s!', 'libre-bite' ), esc_html( $lbite_customer_name ) );
				?>
			</p>
		<?php endif; ?>
	</div>

	<div class="lbite-thankyou-pickup-number">
		<span class="lbite-pickup-label"><?php esc_html_e( 'Your Pickup Number', 'libre-bite' ); ?></span>
		<span class="lbite-pickup-number"><?php echo esc_html( $pickup_number ); ?></span>
	</div>

	<div class="lbite-thankyou-details">
		<?php if ( $location_name ) : ?>
			<div class="lbite-thankyou-detail">
				<svg class="lbite-detail-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
					<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
				</svg>
				<div class="lbite-detail-content">
					<span class="lbite-detail-label"><?php esc_html_e( 'Location', 'libre-bite' ); ?></span>
					<span class="lbite-detail-value"><?php echo esc_html( $location_name ); ?></span>
					<?php if ( $location_address ) : ?>
						<?php if ( $location_maps_url ) : ?>
							<a href="<?php echo esc_url( $location_maps_url ); ?>" target="_blank" rel="noopener noreferrer" class="lbite-detail-sub lbite-maps-link">
								<?php echo esc_html( $location_address ); ?>
								<svg class="lbite-external-link-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
									<polyline points="15 3 21 3 21 9"></polyline>
									<line x1="10" y1="14" x2="21" y2="3"></line>
								</svg>
							</a>
						<?php else : ?>
							<span class="lbite-detail-sub"><?php echo esc_html( $location_address ); ?></span>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="lbite-thankyou-detail">
			<svg class="lbite-detail-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
				<path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
			</svg>
			<div class="lbite-detail-content">
				<span class="lbite-detail-label"><?php esc_html_e( 'Pickup', 'libre-bite' ); ?></span>
				<span class="lbite-detail-value">
					<?php
					if ( 'later' === $lbite_order_type && $lbite_pickup_time ) {
						echo esc_html( wp_date( 'd.m.Y', lbite_local_time_to_timestamp( $lbite_pickup_time ) ) );
						echo ' ' . esc_html__( 'at', 'libre-bite' ) . ' ';
						echo esc_html( wp_date( 'H:i', lbite_local_time_to_timestamp( $lbite_pickup_time ) ) );
					} else {
						esc_html_e( 'As soon as ready', 'libre-bite' );
					}
					?>
				</span>
			</div>
		</div>

		<?php
		// Zahlungsart anzeigen.
		$lbite_payment_method       = $lbite_order->get_payment_method_title();
		$lbite_payment_instructions = '';
		$lbite_gateway              = WC()->payment_gateways ? WC()->payment_gateways->payment_gateways()[ $lbite_order->get_payment_method() ] ?? null : null;
		if ( $lbite_gateway ) {
			$lbite_payment_instructions = $lbite_gateway->instructions ?? '';
		}
		if ( $lbite_payment_method ) :
			?>
		<div class="lbite-thankyou-detail">
			<svg class="lbite-detail-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
				<path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
			</svg>
			<div class="lbite-detail-content">
				<span class="lbite-detail-label"><?php esc_html_e( 'Payment', 'libre-bite' ); ?></span>
				<span class="lbite-detail-value"><?php echo esc_html( $lbite_payment_method ); ?></span>
				<?php if ( $lbite_payment_instructions ) : ?>
					<span class="lbite-detail-sub"><?php echo wp_kses_post( $lbite_payment_instructions ); ?></span>
				<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>
	</div>

	<div class="lbite-thankyou-order">
		<h3><?php esc_html_e( 'Your Order', 'libre-bite' ); ?></h3>

		<table class="lbite-order-items">
			<tbody>
				<?php foreach ( $lbite_order->get_items() as $item_id => $item ) : ?>
					<tr>
						<td class="lbite-item-qty"><?php echo esc_html( $item->get_quantity() ); ?>x</td>
						<td class="lbite-item-name">
							<?php echo esc_html( $item->get_name() ); ?>
							<?php
							// Meta-Daten anzeigen.
							$item_meta = $item->get_meta( 'Konfiguration' );
							if ( $item_meta ) :
								?>
								<span class="lbite-item-meta"><?php echo esc_html( $item_meta ); ?></span>
							<?php endif; ?>
						</td>
						<td class="lbite-item-total">
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
				$fees    = $lbite_order->get_fees();
				$coupons = $lbite_order->get_coupons();
				$has_shipping = $lbite_order->get_shipping_total() > 0;
				$has_tax = $lbite_order->get_total_tax() > 0 && 'excl' === $tax_display;
				$has_extras = ! empty( $fees ) || ! empty( $coupons ) || $has_shipping || $has_tax;

				// Zwischensumme = Summe aller Artikelpreise (inkl. oder exkl. MwSt.).
				$subtotal = 0;
				foreach ( $lbite_order->get_items() as $calc_item ) {
					$subtotal += 'incl' === $tax_display
						? $calc_item->get_total() + $calc_item->get_total_tax()
						: $calc_item->get_total();
				}

				if ( $has_extras ) :
					?>
					<!-- Zwischensumme -->
					<tr class="lbite-subtotal-row">
						<td colspan="2"><?php esc_html_e( 'Subtotal', 'libre-bite' ); ?></td>
						<td><?php echo wp_kses_post( wc_price( $subtotal ) ); ?></td>
					</tr>

					<?php
					// Gutscheine anzeigen.
					foreach ( $coupons as $coupon ) :
						?>
						<tr class="lbite-coupon-row">
							<td colspan="2">
								<?php
								/* translators: %s: coupon code */
								printf( esc_html__( 'Coupon: %s', 'libre-bite' ), esc_html( $coupon->get_code() ) );
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
						<tr class="lbite-fee-row">
							<td colspan="2"><?php echo esc_html( $fee->get_name() ); ?></td>
							<td><?php echo wp_kses_post( wc_price( $fee_total ) ); ?></td>
						</tr>
					<?php endforeach; ?>

					<?php
					// Versand anzeigen wenn vorhanden.
					if ( $has_shipping ) :
						?>
						<tr class="lbite-shipping-row">
							<td colspan="2"><?php esc_html_e( 'Shipping', 'libre-bite' ); ?></td>
							<td><?php echo wp_kses_post( wc_price( $lbite_order->get_shipping_total() ) ); ?></td>
						</tr>
					<?php endif; ?>

					<?php
					// MwSt. anzeigen wenn Preise exkl. MwSt. angezeigt werden.
					if ( $has_tax ) :
						?>
						<tr class="lbite-tax-row">
							<td colspan="2"><?php esc_html_e( 'VAT', 'libre-bite' ); ?></td>
							<td><?php echo wp_kses_post( wc_price( $lbite_order->get_total_tax() ) ); ?></td>
						</tr>
					<?php endif; ?>

				<?php endif; ?>

				<!-- Total -->
				<tr class="lbite-total-row">
					<td colspan="2"><strong><?php esc_html_e( 'Total', 'libre-bite' ); ?></strong></td>
					<td><strong><?php echo wp_kses_post( wc_price( $lbite_order->get_total() ) ); ?></strong></td>
				</tr>
			</tfoot>
		</table>
	</div>

	<div class="lbite-thankyou-print-btn-wrap">
		<button type="button" class="lbite-print-btn" onclick="window.print()">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16" aria-hidden="true">
				<path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/>
			</svg>
			<?php esc_html_e( 'Print Receipt / Save as PDF', 'libre-bite' ); ?>
		</button>
	</div>

	<div class="lbite-thankyou-footer">
		<p><?php echo esc_html( $brand_name ); ?></p>
	</div>
</div>
