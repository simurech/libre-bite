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

<div class="wrap lb-pos">
	<div class="lb-pos-header">
		<h1><?php esc_html_e( 'Kassensystem', 'libre-bite' ); ?></h1>
		<button type="button" id="lb-pos-fullscreen" class="button button-large" title="Vollbild">
			<span class="dashicons dashicons-editor-expand"></span>
		</button>
	</div>

	<!-- Standort-Auswahl -->
	<div class="lb-pos-location-selector">
		<label for="lb-pos-location">
			<strong><?php esc_html_e( 'Standort:', 'libre-bite' ); ?></strong>
		</label>
		<select id="lb-pos-location" class="lb-pos-location-select">
			<option value=""><?php esc_html_e( 'Bitte Standort wählen', 'libre-bite' ); ?></option>
			<?php
			$locations = LB_Locations::get_all_locations();
			$selected_location = get_user_meta( get_current_user_id(), 'lb_pos_location', true );
			foreach ( $locations as $location ) :
				?>
				<option value="<?php echo esc_attr( $location->ID ); ?>" <?php selected( $selected_location, $location->ID ); ?>>
					<?php echo esc_html( $location->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>

	<div class="lb-pos-container">
		<div class="lb-pos-products">
			<div class="lb-pos-categories">
				<button class="lb-category-btn active" data-category="all">
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
					<button class="lb-category-btn" data-category="<?php echo esc_attr( $category->term_id ); ?>">
						<?php echo esc_html( $category->name ); ?>
					</button>
				<?php endforeach; ?>
			</div>

			<div class="lb-pos-product-grid" id="lb-product-grid">
				<!-- Produkte werden via JS geladen -->
			</div>
		</div>

		<div class="lb-pos-cart">
			<h2><?php esc_html_e( 'Warenkorb', 'libre-bite' ); ?></h2>
			<div id="lb-pos-cart-items"></div>

			<div class="lb-pos-totals">
				<div class="lb-total-line">
					<span><?php esc_html_e( 'Zwischensumme:', 'libre-bite' ); ?></span>
					<span id="lb-pos-subtotal">0,00 <?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
				</div>
				<div class="lb-total-line lb-total-grand">
					<span><?php esc_html_e( 'Gesamt:', 'libre-bite' ); ?></span>
					<span id="lb-pos-total">0,00 <?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
				</div>
			</div>


		<div class="lb-pos-customer-name" style="margin: 15px 0;">
			<label for="lb-pos-customer-name" style="display: block; margin-bottom: 5px; font-weight: 600;">
				<?php esc_html_e( 'Vorname (optional):', 'libre-bite' ); ?>
			</label>
			<input type="text" id="lb-pos-customer-name" class="lb-input-large" placeholder="<?php esc_attr_e( 'z.B. Max', 'libre-bite' ); ?>" style="width: 100%; padding: 10px; font-size: 16px; border: 1px solid #ddd; border-radius: 4px;">
		</div>
			<div class="lb-pos-actions">
				<button type="button" class="button button-large" id="lb-pos-clear">
					<?php esc_html_e( 'Leeren', 'libre-bite' ); ?>
				</button>
				<button type="button" class="button button-primary button-large" id="lb-pos-checkout">
					<?php esc_html_e( 'Bestellung erstellen', 'libre-bite' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Modal für Produkt-Konfiguration (Varianten & Optionen) -->
	<div id="lb-product-modal" class="lb-modal" style="display: none;">
		<div class="lb-modal-overlay"></div>
		<div class="lb-modal-content">
			<div class="lb-modal-header">
				<h2 id="lb-modal-product-name"><?php esc_html_e( 'Produkt konfigurieren', 'libre-bite' ); ?></h2>
				<button type="button" class="lb-modal-close">&times;</button>
			</div>
			<div class="lb-modal-body" id="lb-modal-body">
				<!-- Wird dynamisch gefüllt -->
			</div>
			<div class="lb-modal-footer">
				<button type="button" class="button button-large" id="lb-modal-cancel"><?php esc_html_e( 'Abbrechen', 'libre-bite' ); ?></button>
				<button type="button" class="button button-primary button-large" id="lb-modal-add"><?php esc_html_e( 'Hinzufügen', 'libre-bite' ); ?></button>
			</div>
		</div>
	</div>
</div>

<style>
.lb-pos-location-selector {
	background: #fff;
	padding: 15px 20px;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
	margin-bottom: 20px;
	display: flex;
	align-items: center;
	gap: 15px;
}

.lb-pos-location-select {
	min-width: 300px;
	padding: 8px 12px;
	font-size: 15px;
	border: 1px solid #8c8f94;
	border-radius: 4px;
}

.lb-pos-container {
	display: grid;
	grid-template-columns: 1fr 400px;
	gap: 20px;
	margin-top: 20px;
}

.lb-pos-products {
	background: #fff;
	padding: 20px;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
}

.lb-pos-categories {
	display: flex;
	gap: 10px;
	margin-bottom: 20px;
	flex-wrap: wrap;
}

.lb-category-btn {
	padding: 10px 20px;
	border: 2px solid #ddd;
	background: #fff;
	border-radius: 4px;
	cursor: pointer;
	transition: all 0.3s;
}

.lb-category-btn.active,
.lb-category-btn:hover {
	border-color: #0073aa;
	background: #f0f8ff;
}

.lb-pos-product-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
	gap: 15px;
}

.lb-pos-product-item {
	padding: 15px;
	border: 2px solid #ddd;
	border-radius: 4px;
	text-align: center;
	cursor: pointer;
	transition: all 0.3s;
	background: #fff;
}

.lb-pos-product-item:hover {
	border-color: #0073aa;
	transform: translateY(-2px);
	box-shadow: 0 3px 6px rgba(0,0,0,0.1);
}

.lb-pos-cart {
	background: #fff;
	padding: 20px;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
	display: flex;
	flex-direction: column;
}

#lb-pos-cart-items {
	flex: 1;
	overflow-y: auto;
	max-height: 400px;
	margin: 20px 0;
}

.lb-pos-totals {
	border-top: 2px solid #ddd;
	padding-top: 15px;
	margin-top: 15px;
}

.lb-total-line {
	display: flex;
	justify-content: space-between;
	padding: 5px 0;
	font-size: 16px;
}

.lb-total-grand {
	font-size: 20px;
	font-weight: bold;
	margin-top: 10px;
	padding-top: 10px;
	border-top: 2px solid #ddd;
}

.lb-pos-actions {
	display: grid;
	grid-template-columns: 1fr 2fr;
	gap: 10px;
	margin-top: 20px;
}

.lb-pos-actions button {
	width: 100%;
}

.lb-pos-cart-item {
	display: grid;
	grid-template-columns: 1fr auto auto auto;
	gap: 10px;
	align-items: center;
	padding: 10px;
	border-bottom: 1px solid #eee;
}

.lb-pos-cart-item-name {
	font-weight: 500;
}

.lb-pos-cart-item-qty {
	display: flex;
	gap: 5px;
	align-items: center;
}

.lb-cart-qty-minus,
.lb-cart-qty-plus {
	width: 25px;
	height: 25px;
	padding: 0;
	border: 1px solid #ddd;
	background: #f7f7f7;
	cursor: pointer;
	border-radius: 3px;
}

.lb-cart-qty-minus:hover,
.lb-cart-qty-plus:hover {
	background: #0073aa;
	color: #fff;
	border-color: #0073aa;
}

.lb-pos-cart-item-price {
	font-weight: 600;
	min-width: 70px;
	text-align: right;
}

.lb-pos-cart-item-remove {
	cursor: pointer;
	color: #dc3232;
}

.lb-pos-cart-item-remove:hover {
	color: #a00;
}

.lb-pos-product-name {
	font-weight: 500;
	margin: 5px 0;
}

.lb-pos-product-price {
	color: #0073aa;
	font-weight: 600;
}

/* Modal Styles */
.lb-modal {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 100000;
}

.lb-modal-overlay {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.7);
}

.lb-modal-content {
	position: relative;
	max-width: 600px;
	margin: 50px auto;
	background: #fff;
	border-radius: 8px;
	box-shadow: 0 5px 15px rgba(0,0,0,0.3);
	max-height: calc(100vh - 100px);
	display: flex;
	flex-direction: column;
}

.lb-modal-header {
	padding: 20px;
	border-bottom: 1px solid #ddd;
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.lb-modal-header h2 {
	margin: 0;
	font-size: 20px;
}

.lb-modal-close {
	background: none;
	border: none;
	font-size: 28px;
	cursor: pointer;
	color: #999;
	line-height: 1;
	padding: 0;
	width: 30px;
	height: 30px;
}

.lb-modal-close:hover {
	color: #333;
}

.lb-modal-body {
	padding: 20px;
	overflow-y: auto;
	flex: 1;
}

.lb-modal-footer {
	padding: 15px 20px;
	border-top: 1px solid #ddd;
	display: flex;
	gap: 10px;
	justify-content: flex-end;
}

.lb-option-group {
	margin-bottom: 20px;
}

.lb-option-group-label {
	display: block;
	font-weight: 600;
	margin-bottom: 10px;
	font-size: 16px;
}

.lb-option-choice {
	display: flex;
	align-items: center;
	padding: 16px;
	border: 2px solid #ddd;
	border-radius: 4px;
	margin-bottom: 10px;
	cursor: pointer;
	transition: all 0.2s;
	min-height: 60px;
	touch-action: manipulation;
	background: #fff;
}

.lb-option-choice:hover {
	background: #f7f7f7;
	border-color: #999;
}

.lb-option-choice:has(input:checked) {
	background: #e3f2fd;
	border-color: #2196F3;
}

.lb-option-choice input[type="radio"],
.lb-option-choice input[type="checkbox"] {
	-webkit-appearance: none;
	-moz-appearance: none;
	appearance: none;
	width: 24px;
	height: 24px;
	margin-right: 15px;
	cursor: pointer;
	flex-shrink: 0;
	border: 2px solid #999;
	background: #fff;
	position: relative;
	transition: all 0.2s;
}

.lb-option-choice input[type="radio"] {
	border-radius: 50%;
}

.lb-option-choice input[type="checkbox"] {
	border-radius: 4px;
}

.lb-option-choice input[type="radio"]:checked,
.lb-option-choice input[type="checkbox"]:checked {
	border-color: #2196F3;
	background: #2196F3;
}

.lb-option-choice input[type="radio"]::after {
	content: '';
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%) scale(0);
	width: 10px;
	height: 10px;
	border-radius: 50%;
	background: #fff;
	transition: transform 0.2s;
}

.lb-option-choice input[type="radio"]:checked::after {
	transform: translate(-50%, -50%) scale(1);
}

.lb-option-choice input[type="checkbox"]::after {
	content: '';
	position: absolute;
	top: 3px;
	left: 7px;
	width: 6px;
	height: 11px;
	border: solid #fff;
	border-width: 0 2px 2px 0;
	transform: rotate(45deg) scale(0);
	transition: transform 0.2s;
}

.lb-option-choice input[type="checkbox"]:checked::after {
	transform: rotate(45deg) scale(1);
}

.lb-option-choice-label {
	flex: 1;
	font-size: 16px;
	font-weight: 500;
}

.lb-option-choice-price {
	color: #0073aa;
	font-weight: 600;
	font-size: 16px;
	margin-left: 10px;
}

/* Touch-Optimierung */
@media (hover: none) and (pointer: coarse) {
	.lb-option-choice {
		padding: 20px;
		min-height: 70px;
	}

	.lb-option-choice input[type="radio"],
	.lb-option-choice input[type="checkbox"] {
		width: 28px;
		height: 28px;
	}

	.lb-option-choice input[type="radio"]::after {
		width: 12px;
		height: 12px;
	}

	.lb-option-choice input[type="checkbox"]::after {
		top: 4px;
		left: 9px;
		width: 7px;
		height: 13px;
	}

	.lb-option-choice-label,
	.lb-option-choice-price {
		font-size: 18px;
	}
}

.lb-product-has-config {
	position: relative;
}

.lb-product-has-config::after {
	content: '⚙️';
	position: absolute;
	top: 5px;
	right: 5px;
	font-size: 20px;
}

/* Header mit Vollbild-Button */
.lb-pos-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20px;
}

.lb-pos-header h1 {
	margin: 0;
}

#lb-pos-fullscreen {
	display: flex;
	align-items: center;
	gap: 5px;
	min-height: 44px;
	padding: 8px 16px;
}

#lb-pos-fullscreen .dashicons {
	font-size: 20px;
	width: 20px;
	height: 20px;
}

/* Touch-Optimierung */
.lb-pos-product-item {
	min-height: 120px;
	padding: 20px;
	font-size: 16px;
	touch-action: manipulation;
}

.lb-pos-product-name {
	font-size: 16px;
}

.lb-pos-product-price {
	font-size: 18px;
}

.lb-category-btn {
	min-height: 48px;
	padding: 12px 24px;
	font-size: 16px;
	touch-action: manipulation;
}

.lb-cart-qty-minus,
.lb-cart-qty-plus {
	min-width: 44px;
	min-height: 44px;
	font-size: 20px;
	touch-action: manipulation;
}

.lb-pos-cart-item-remove {
	min-width: 44px;
	min-height: 44px;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 24px;
}

.lb-pos-actions button {
	min-height: 54px;
	font-size: 18px;
	touch-action: manipulation;
}

.lb-option-choice {
	min-height: 54px;
	padding: 15px;
	font-size: 16px;
	touch-action: manipulation;
}

.lb-option-choice input[type="radio"],
.lb-option-choice input[type="checkbox"] {
	min-width: 24px;
	min-height: 24px;
	margin-right: 15px;
}

.lb-modal-footer button {
	min-height: 54px;
	padding: 12px 24px;
	font-size: 18px;
}

.lb-input-large {
	width: 100%;
	padding: 12px;
	font-size: 18px;
	min-height: 54px;
	border: 1px solid #8c8f94;
	border-radius: 4px;
}

/* Vollbild-Modus */
body.lb-fullscreen-active #wpadminbar,
body.lb-fullscreen-active #adminmenumain,
body.lb-fullscreen-active .update-nag {
	display: none !important;
}

body.lb-fullscreen-active #wpcontent {
	margin-left: 0 !important;
	padding-left: 0 !important;
}

body.lb-fullscreen-active .lb-pos {
	padding: 20px;
	max-width: none;
}

body.lb-fullscreen-active .lb-pos-container {
	height: calc(100vh - 200px);
}

body.lb-fullscreen-active #lb-pos-cart-items {
	max-height: calc(100vh - 500px);
}

/* Tablet-Optimierung */
@media (min-width: 768px) and (max-width: 1024px) {
	.lb-pos-product-grid {
		grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
	}

	.lb-pos-container {
		grid-template-columns: 1fr 450px;
	}
}

/* Mobile-Optimierung */
@media (max-width: 767px) {
	.lb-pos-container {
		grid-template-columns: 1fr;
	}

	.lb-pos-cart {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		max-height: 50vh;
		z-index: 1000;
		box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
	}
}
</style>

<script>
jQuery(document).ready(function($) {
	// Standort-Auswahl speichern
	$('#lb-pos-location').on('change', function() {
		const locationId = $(this).val();

		// Standort per AJAX speichern
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'lb_save_pos_location',
				nonce: lbAdmin.nonce,
				location_id: locationId
			},
			success: function(response) {
				if (response.success) {
					// Seite neu laden um Produkte für diesen Standort anzuzeigen
					location.reload();
				}
			}
		});
	});
});

// POS initialisiert via pos.js
</script>
