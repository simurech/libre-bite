<?php
/**
 * Template: Trinkgeld-Auswahl im Checkout
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="lb-tip-selection">
	<h3><?php esc_html_e( 'Trinkgeld hinzufügen?', 'libre-bite' ); ?></h3>
	<p><?php esc_html_e( 'Möchten Sie ein Trinkgeld geben?', 'libre-bite' ); ?></p>

	<div class="lb-tip-options">
		<label class="lb-tip-option">
			<input type="radio" name="lb_tip_type" value="none" <?php checked( $default_selection, 'none' ); ?>>
			<span><?php esc_html_e( 'Kein Trinkgeld', 'libre-bite' ); ?></span>
		</label>

		<label class="lb-tip-option">
			<input type="radio" name="lb_tip_type" value="percentage" data-percentage="<?php echo esc_attr( $percentage_1 ); ?>" <?php checked( $default_selection, 'percentage_1' ); ?>>
			<input type="hidden" name="lb_tip_percentage" class="lb-tip-percentage-value" value="<?php echo $default_selection === 'percentage_1' ? esc_attr( $percentage_1 ) : ''; ?>" <?php echo $default_selection === 'percentage_1' ? '' : 'disabled'; ?>>
			<span><?php echo esc_html( $percentage_1 ); ?>%</span>
		</label>

		<label class="lb-tip-option">
			<input type="radio" name="lb_tip_type" value="percentage" data-percentage="<?php echo esc_attr( $percentage_2 ); ?>" <?php checked( $default_selection, 'percentage_2' ); ?>>
			<input type="hidden" name="lb_tip_percentage" class="lb-tip-percentage-value" value="<?php echo $default_selection === 'percentage_2' ? esc_attr( $percentage_2 ) : ''; ?>" <?php echo $default_selection === 'percentage_2' ? '' : 'disabled'; ?>>
			<span><?php echo esc_html( $percentage_2 ); ?>%</span>
		</label>

		<label class="lb-tip-option">
			<input type="radio" name="lb_tip_type" value="percentage" data-percentage="<?php echo esc_attr( $percentage_3 ); ?>" <?php checked( $default_selection, 'percentage_3' ); ?>>
			<input type="hidden" name="lb_tip_percentage" class="lb-tip-percentage-value" value="<?php echo $default_selection === 'percentage_3' ? esc_attr( $percentage_3 ) : ''; ?>" <?php echo $default_selection === 'percentage_3' ? '' : 'disabled'; ?>>
			<span><?php echo esc_html( $percentage_3 ); ?>%</span>
		</label>

		<label class="lb-tip-option lb-tip-custom">
			<input type="radio" name="lb_tip_type" value="custom">
			<span><?php esc_html_e( 'Individuell:', 'libre-bite' ); ?></span>
			<input type="number" name="lb_tip_custom" min="0" max="100" step="0.1" placeholder="0">
			<span>%</span>
		</label>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Trinkgeld-Auswahl
	$('input[name="lb_tip_type"]').on('change', function() {
		// Alle versteckten Felder zurücksetzen
		$('.lb-tip-percentage-value').prop('disabled', true).val('');

		// Wenn percentage gewählt, entsprechendes verstecktes Feld aktivieren
		if ($(this).val() === 'percentage') {
			var percentage = $(this).data('percentage');
			var $hiddenField = $(this).siblings('.lb-tip-percentage-value');
			$hiddenField.val(percentage).prop('disabled', false);
		}

		$('body').trigger('update_checkout');
	});

	$('input[name="lb_tip_custom"]').on('input', function() {
		$(this).closest('.lb-tip-option').find('input[name="lb_tip_type"]').prop('checked', true);

		// Debounce
		clearTimeout(window.tipTimeout);
		window.tipTimeout = setTimeout(function() {
			$('body').trigger('update_checkout');
		}, 500);
	});
});
</script>

<style>
.lb-tip-selection {
	background: #fff;
	padding: 20px;
	margin-bottom: 20px;
	border-radius: 8px;
	border: 1px solid #e0e0e0;
}

.lb-tip-selection h3 {
	margin: 0 0 5px 0;
	font-size: 18px;
	color: var(--lb-color-secondary, #23282d);
}

.lb-tip-selection > p {
	margin: 0 0 15px 0;
	color: #666;
	font-size: 14px;
}

.lb-tip-options {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	gap: 10px;
}

.lb-tip-option {
	padding: 14px 12px;
	border: 2px solid #e0e0e0;
	border-radius: 8px;
	text-align: center;
	cursor: pointer;
	transition: all 0.2s;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8px;
	background: #fff;
}

.lb-tip-option:hover {
	border-color: #999;
	background: #fafafa;
}

.lb-tip-option input[type="radio"] {
	margin: 0;
	cursor: pointer;
}

.lb-tip-option > span {
	font-weight: 600;
	font-size: 15px;
	color: var(--lb-color-secondary, #23282d);
}

/* Individuell-Option über volle Breite */
.lb-tip-custom {
	grid-column: 1 / -1;
	justify-content: flex-start;
	padding: 12px 15px;
}

.lb-tip-custom input[type="number"] {
	width: 70px;
	padding: 8px 10px;
	border: 1px solid #ddd;
	border-radius: 6px;
	font-size: 14px;
	text-align: center;
}

.lb-tip-custom input[type="number"]:focus {
	outline: none;
	border-color: var(--lb-color-primary, #0073aa);
}

/* Ausgewählte Option */
.lb-tip-option:has(input[type="radio"]:checked) {
	border-color: var(--lb-color-primary, #0073aa);
	background: rgba(0, 115, 170, 0.05);
}

.lb-tip-option:has(input[type="radio"]:checked) > span {
	color: var(--lb-color-primary, #0073aa);
}

/* Responsive: Auf kleinen Screens 1 Spalte */
@media (max-width: 400px) {
	.lb-tip-options {
		grid-template-columns: 1fr;
	}
}
</style>
