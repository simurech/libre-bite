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
		<h1><?php esc_html_e( 'POS System', 'libre-bite' ); ?></h1>
		<button type="button" id="lbite-pos-fullscreen" class="button button-large" title="Vollbild">
			<span class="dashicons dashicons-editor-expand"></span>
		</button>
	</div>

	<!-- Standort-Auswahl -->
	<div class="lbite-pos-location-selector" style="display: flex; gap: 20px; align-items: flex-end; margin-bottom: 20px;">
		<div>
			<label for="lbite-pos-location">
				<strong><?php esc_html_e( 'Location:', 'libre-bite' ); ?></strong>
			</label>
			<select id="lbite-pos-location" class="lbite-pos-location-select">
				<option value=""><?php esc_html_e( 'Please select a location', 'libre-bite' ); ?></option>
				<?php
				$lbite_locations = LBite_Locations::get_all_locations();
				$lbite_selected_location = get_user_meta( get_current_user_id(), 'lbite_pos_location', true );
				foreach ( $lbite_locations as $lbite_location ) :
					?>
					<option value="<?php echo esc_attr( $lbite_location->ID ); ?>" <?php selected( $lbite_selected_location, $lbite_location->ID ); ?>>
						<?php echo esc_html( $lbite_location->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<?php if ( lbite_feature_enabled( 'enable_table_ordering' ) ) : ?>
		<div id="lbite-pos-table-selector-container" style="<?php echo ! $lbite_selected_location ? 'display: none;' : ''; ?>">
			<label for="lbite-pos-table">
				<strong><?php esc_html_e( 'Table (optional):', 'libre-bite' ); ?></strong>
			</label>
			<select id="lbite-pos-table" class="lbite-pos-table-select">
				<option value=""><?php esc_html_e( 'No table', 'libre-bite' ); ?></option>
				<?php
				if ( $lbite_selected_location ) {
					$lbite_tables = get_posts( array(
						'post_type'      => 'lbite_table',
						'posts_per_page' => 100,
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Tischabfrage für POS-Standort; auf 100 Tische begrenzt.
						'meta_query'     => array(
							array(
								'key'   => '_lbite_location_id',
								'value' => $lbite_selected_location,
							),
						),
					) );
					foreach ( $lbite_tables as $lbite_table ) :
						?>
						<option value="<?php echo esc_attr( $lbite_table->ID ); ?>">
							<?php echo esc_html( $lbite_table->post_title ); ?>
						</option>
						<?php
					endforeach;
				}
				?>
			</select>
		</div>
		<?php endif; ?>
	</div>

	<div class="lbite-pos-container">
		<div class="lbite-pos-products" data-no-location-text="<?php esc_attr_e( 'Please select a location first', 'libre-bite' ); ?>">
			<div class="lbite-pos-categories">
				<button class="lbite-category-btn active" data-category="all">
					<?php esc_html_e( 'All', 'libre-bite' ); ?>
				</button>
				<?php
				$lbite_product_categories = get_terms(
					array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => true,
					)
				);
				foreach ( $lbite_product_categories as $lbite_category ) :
					?>
					<button class="lbite-category-btn" data-category="<?php echo esc_attr( $lbite_category->term_id ); ?>">
						<?php echo esc_html( $lbite_category->name ); ?>
					</button>
				<?php endforeach; ?>
			</div>

			<div class="lbite-pos-product-grid" id="lbite-product-grid">
				<!-- Produkte werden via JS geladen -->
			</div>
		</div>

		<div class="lbite-pos-cart">
			<h2><?php esc_html_e( 'Cart', 'libre-bite' ); ?></h2>
			<div id="lbite-pos-cart-items"></div>

			<div class="lbite-pos-totals">
				<div class="lbite-total-line">
					<span><?php esc_html_e( 'Subtotal:', 'libre-bite' ); ?></span>
					<span id="lbite-pos-subtotal">0,00 <?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
				</div>
				<div class="lbite-total-line lbite-total-grand">
					<span><?php esc_html_e( 'Total:', 'libre-bite' ); ?></span>
					<span id="lbite-pos-total">0,00 <?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
				</div>
			</div>


		<div class="lbite-pos-customer-name" style="margin: 15px 0;">
			<label for="lbite-pos-customer-name" style="display: block; margin-bottom: 5px; font-weight: 600;">
				<?php esc_html_e( 'First Name (optional):', 'libre-bite' ); ?>
			</label>
			<input type="text" id="lbite-pos-customer-name" class="lbite-input-large" placeholder="<?php esc_attr_e( 'e.g. Max', 'libre-bite' ); ?>" style="width: 100%; padding: 10px; font-size: 16px; border: 1px solid #ddd; border-radius: 4px;">
		</div>
			<div class="lbite-pos-actions">
				<button type="button" class="button button-large" id="lbite-pos-clear">
					<?php esc_html_e( 'Clear', 'libre-bite' ); ?>
				</button>
				<button type="button" class="button button-primary button-large" id="lbite-pos-checkout">
					<?php esc_html_e( 'Create Order', 'libre-bite' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Modal: Zahlungsbestätigung -->
	<div id="lbite-payment-modal" class="lbite-modal" style="display: none;">
		<div id="lbite-payment-modal-overlay" class="lbite-modal-overlay"></div>
		<div class="lbite-modal-content lbite-payment-modal-content">
			<div class="lbite-modal-header">
				<h2><?php esc_html_e( 'Payment Confirmation', 'libre-bite' ); ?></h2>
			</div>
			<div class="lbite-modal-body">

				<!-- Bestellübersicht -->
				<div id="lbite-payment-modal-items" class="lbite-payment-modal-items">
					<!-- Wird dynamisch befüllt -->
				</div>

				<div class="lbite-payment-modal-total-row">
					<span><?php esc_html_e( 'Total Amount:', 'libre-bite' ); ?></span>
					<span id="lbite-payment-modal-total" class="lbite-payment-modal-total-amount"></span>
				</div>

				<!-- Zahlungsart wählen -->
				<?php
				$lbite_icons = array( 'cash' => '💵', 'card' => '💳', 'twint' => '📱', 'other' => '💱' );
				$lbite_saved_pm = get_option( 'lbite_pos_payment_methods', array() );
				// Fallback: alle vier aktiv wenn Option leer
				if ( empty( $lbite_saved_pm ) ) {
					$lbite_saved_pm = array(
						array( 'key' => 'cash',  'label' => 'Bar',    'enabled' => true ),
						array( 'key' => 'card',  'label' => 'Karte',  'enabled' => true ),
						array( 'key' => 'twint', 'label' => 'Twint',  'enabled' => true ),
						array( 'key' => 'other', 'label' => 'Andere', 'enabled' => true ),
					);
				}
				$lbite_active_pm = array_filter( $lbite_saved_pm, fn( $m ) => ! empty( $m['enabled'] ) );
				$lbite_active_pm = array_values( $lbite_active_pm );
				$lbite_first = true;
				?>
				<div class="lbite-payment-method-group">
					<p class="lbite-payment-method-label"><strong><?php esc_html_e( 'Payment Method:', 'libre-bite' ); ?></strong></p>
					<div class="lbite-payment-method-options">
						<?php foreach ( $lbite_active_pm as $lbite_pm ) : ?>
						<label class="lbite-payment-method-option">
							<input
								type="radio"
								id="lbite-payment-method-<?php echo esc_attr( $lbite_pm['key'] ); ?>"
								name="lbite-payment-method"
								value="<?php echo esc_attr( $lbite_pm['key'] ); ?>"
								<?php echo $lbite_first ? 'checked' : ''; ?>
							>
							<span class="lbite-payment-method-icon"><?php echo esc_html( $lbite_icons[ $lbite_pm['key'] ] ?? '💱' ); ?></span>
							<span><?php echo esc_html( $lbite_pm['label'] ); ?></span>
						</label>
						<?php $lbite_first = false; ?>
						<?php endforeach; ?>
					</div>
				</div>

			</div>
			<div class="lbite-modal-footer">
				<button type="button" class="button button-large" id="lbite-payment-modal-cancel">
					<?php esc_html_e( 'Back', 'libre-bite' ); ?>
				</button>
				<button type="button" class="button button-primary button-hero" id="lbite-payment-modal-confirm">
					<?php esc_html_e( 'Payment Confirmed – Create Order', 'libre-bite' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Modal für Produkt-Konfiguration (Varianten & Optionen) -->
	<div id="lbite-product-modal" class="lbite-modal" style="display: none;">
		<div class="lbite-modal-overlay"></div>
		<div class="lbite-modal-content">
			<div class="lbite-modal-header">
				<h2 id="lbite-modal-product-name"><?php esc_html_e( 'Configure Product', 'libre-bite' ); ?></h2>
				<button type="button" class="lbite-modal-close">&times;</button>
			</div>
			<div class="lbite-modal-body" id="lbite-modal-body">
				<!-- Wird dynamisch gefüllt -->
			</div>
			<div class="lbite-modal-footer">
				<button type="button" class="button button-large" id="lbite-modal-cancel"><?php esc_html_e( 'Cancel', 'libre-bite' ); ?></button>
				<button type="button" class="button button-primary button-large" id="lbite-modal-add"><?php esc_html_e( 'Add', 'libre-bite' ); ?></button>
			</div>
		</div>
	</div>
</div>
