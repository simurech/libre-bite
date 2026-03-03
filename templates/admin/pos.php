<?php
/**
 * Template: POS / Kassensystem
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap lbite-pos">
	<div class="lbite-pos-header">
		<h1><?php esc_html_e( 'Kassensystem', 'libre-bite' ); ?></h1>
		<button type="button" id="lbite-pos-fullscreen" class="button button-large" title="Vollbild">
			<span class="dashicons dashicons-editor-expand"></span>
		</button>
	</div>

	<!-- Standort-Auswahl -->
	<div class="lbite-pos-location-selector">
		<label for="lbite-pos-location">
			<strong><?php esc_html_e( 'Standort:', 'libre-bite' ); ?></strong>
		</label>
		<select id="lbite-pos-location" class="lbite-pos-location-select">
			<option value=""><?php esc_html_e( 'Bitte Standort wählen', 'libre-bite' ); ?></option>
			<?php
			$locations = LBite_Locations::get_all_locations();
			$selected_location = get_user_meta( get_current_user_id(), 'lbite_pos_location', true );
			foreach ( $locations as $location ) :
				?>
				<option value="<?php echo esc_attr( $location->ID ); ?>" <?php selected( $selected_location, $location->ID ); ?>>
					<?php echo esc_html( $location->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>

	<div class="lbite-pos-container">
		<div class="lbite-pos-products">
			<div class="lbite-pos-categories">
				<button class="lbite-category-btn active" data-category="all">
					<?php esc_html_e( 'Alle', 'libre-bite' ); ?>
				</button>
				<?php
				$product_categories = get_terms(
					array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => true,
					)
				);
				foreach ( $product_categories as $category ) :
					?>
					<button class="lbite-category-btn" data-category="<?php echo esc_attr( $category->term_id ); ?>">
						<?php echo esc_html( $category->name ); ?>
					</button>
				<?php endforeach; ?>
			</div>

			<div class="lbite-pos-product-grid" id="lbite-product-grid">
				<!-- Produkte werden via JS geladen -->
			</div>
		</div>

		<div class="lbite-pos-cart">
			<h2><?php esc_html_e( 'Warenkorb', 'libre-bite' ); ?></h2>
			<div id="lbite-pos-cart-items"></div>

			<div class="lbite-pos-totals">
				<div class="lbite-total-line">
					<span><?php esc_html_e( 'Zwischensumme:', 'libre-bite' ); ?></span>
					<span id="lbite-pos-subtotal">0,00 <?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
				</div>
				<div class="lbite-total-line lbite-total-grand">
					<span><?php esc_html_e( 'Gesamt:', 'libre-bite' ); ?></span>
					<span id="lbite-pos-total">0,00 <?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
				</div>
			</div>


		<div class="lbite-pos-customer-name" style="margin: 15px 0;">
			<label for="lbite-pos-customer-name" style="display: block; margin-bottom: 5px; font-weight: 600;">
				<?php esc_html_e( 'Vorname (optional):', 'libre-bite' ); ?>
			</label>
			<input type="text" id="lbite-pos-customer-name" class="lbite-input-large" placeholder="<?php esc_attr_e( 'z.B. Max', 'libre-bite' ); ?>" style="width: 100%; padding: 10px; font-size: 16px; border: 1px solid #ddd; border-radius: 4px;">
		</div>
			<div class="lbite-pos-actions">
				<button type="button" class="button button-large" id="lbite-pos-clear">
					<?php esc_html_e( 'Leeren', 'libre-bite' ); ?>
				</button>
				<button type="button" class="button button-primary button-large" id="lbite-pos-checkout">
					<?php esc_html_e( 'Bestellung erstellen', 'libre-bite' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Modal: Zahlungsbestätigung -->
	<div id="lbite-payment-modal" class="lbite-modal" style="display: none;">
		<div id="lbite-payment-modal-overlay" class="lbite-modal-overlay"></div>
		<div class="lbite-modal-content lbite-payment-modal-content">
			<div class="lbite-modal-header">
				<h2><?php esc_html_e( 'Zahlungsbestätigung', 'libre-bite' ); ?></h2>
			</div>
			<div class="lbite-modal-body">

				<!-- Bestellübersicht -->
				<div id="lbite-payment-modal-items" class="lbite-payment-modal-items">
					<!-- Wird dynamisch befüllt -->
				</div>

				<div class="lbite-payment-modal-total-row">
					<span><?php esc_html_e( 'Gesamtbetrag:', 'libre-bite' ); ?></span>
					<span id="lbite-payment-modal-total" class="lbite-payment-modal-total-amount"></span>
				</div>

				<!-- Zahlungsart wählen -->
				<?php
				$icons = array( 'cash' => '💵', 'card' => '💳', 'twint' => '📱', 'other' => '💱' );
				$saved_pm = get_option( 'lbite_pos_payment_methods', array() );
				// Fallback: alle vier aktiv wenn Option leer
				if ( empty( $saved_pm ) ) {
					$saved_pm = array(
						array( 'key' => 'cash',  'label' => 'Bar',    'enabled' => true ),
						array( 'key' => 'card',  'label' => 'Karte',  'enabled' => true ),
						array( 'key' => 'twint', 'label' => 'Twint',  'enabled' => true ),
						array( 'key' => 'other', 'label' => 'Andere', 'enabled' => true ),
					);
				}
				$active_pm = array_filter( $saved_pm, fn( $m ) => ! empty( $m['enabled'] ) );
				$active_pm = array_values( $active_pm );
				$first     = true;
				?>
				<div class="lbite-payment-method-group">
					<p class="lbite-payment-method-label"><strong><?php esc_html_e( 'Zahlungsart:', 'libre-bite' ); ?></strong></p>
					<div class="lbite-payment-method-options">
						<?php foreach ( $active_pm as $pm ) : ?>
						<label class="lbite-payment-method-option">
							<input
								type="radio"
								id="lbite-payment-method-<?php echo esc_attr( $pm['key'] ); ?>"
								name="lbite-payment-method"
								value="<?php echo esc_attr( $pm['key'] ); ?>"
								<?php echo $first ? 'checked' : ''; ?>
							>
							<span class="lbite-payment-method-icon"><?php echo esc_html( $icons[ $pm['key'] ] ?? '💱' ); ?></span>
							<span><?php echo esc_html( $pm['label'] ); ?></span>
						</label>
						<?php $first = false; ?>
						<?php endforeach; ?>
					</div>
				</div>

			</div>
			<div class="lbite-modal-footer">
				<button type="button" class="button button-large" id="lbite-payment-modal-cancel">
					<?php esc_html_e( 'Zurück', 'libre-bite' ); ?>
				</button>
				<button type="button" class="button button-primary button-hero" id="lbite-payment-modal-confirm">
					<?php esc_html_e( 'Zahlung bestätigt – Bestellung anlegen', 'libre-bite' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Modal für Produkt-Konfiguration (Varianten & Optionen) -->
	<div id="lbite-product-modal" class="lbite-modal" style="display: none;">
		<div class="lbite-modal-overlay"></div>
		<div class="lbite-modal-content">
			<div class="lbite-modal-header">
				<h2 id="lbite-modal-product-name"><?php esc_html_e( 'Produkt konfigurieren', 'libre-bite' ); ?></h2>
				<button type="button" class="lbite-modal-close">&times;</button>
			</div>
			<div class="lbite-modal-body" id="lbite-modal-body">
				<!-- Wird dynamisch gefüllt -->
			</div>
			<div class="lbite-modal-footer">
				<button type="button" class="button button-large" id="lbite-modal-cancel"><?php esc_html_e( 'Abbrechen', 'libre-bite' ); ?></button>
				<button type="button" class="button button-primary button-large" id="lbite-modal-add"><?php esc_html_e( 'Hinzufügen', 'libre-bite' ); ?></button>
			</div>
		</div>
	</div>
</div>
