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

<div class="lbite-tip-selection">
	<h3><?php esc_html_e( 'Trinkgeld hinzufügen?', 'libre-bite' ); ?></h3>
	<p><?php esc_html_e( 'Möchten Sie ein Trinkgeld geben?', 'libre-bite' ); ?></p>

	<div class="lbite-tip-options">
		<label class="lbite-tip-option">
			<input type="radio" name="lbite_tip_type" value="none" <?php checked( $default_selection, 'none' ); ?>>
			<span><?php esc_html_e( 'Kein Trinkgeld', 'libre-bite' ); ?></span>
		</label>

		<label class="lbite-tip-option">
			<input type="radio" name="lbite_tip_type" value="percentage" data-percentage="<?php echo esc_attr( $percentage_1 ); ?>" <?php checked( $default_selection, 'percentage_1' ); ?>>
			<input type="hidden" name="lbite_tip_percentage" class="lbite-tip-percentage-value" value="<?php echo $default_selection === 'percentage_1' ? esc_attr( $percentage_1 ) : ''; ?>" <?php echo $default_selection === 'percentage_1' ? '' : 'disabled'; ?>>
			<span><?php echo esc_html( $percentage_1 ); ?>%</span>
		</label>

		<label class="lbite-tip-option">
			<input type="radio" name="lbite_tip_type" value="percentage" data-percentage="<?php echo esc_attr( $percentage_2 ); ?>" <?php checked( $default_selection, 'percentage_2' ); ?>>
			<input type="hidden" name="lbite_tip_percentage" class="lbite-tip-percentage-value" value="<?php echo $default_selection === 'percentage_2' ? esc_attr( $percentage_2 ) : ''; ?>" <?php echo $default_selection === 'percentage_2' ? '' : 'disabled'; ?>>
			<span><?php echo esc_html( $percentage_2 ); ?>%</span>
		</label>

		<label class="lbite-tip-option">
			<input type="radio" name="lbite_tip_type" value="percentage" data-percentage="<?php echo esc_attr( $percentage_3 ); ?>" <?php checked( $default_selection, 'percentage_3' ); ?>>
			<input type="hidden" name="lbite_tip_percentage" class="lbite-tip-percentage-value" value="<?php echo $default_selection === 'percentage_3' ? esc_attr( $percentage_3 ) : ''; ?>" <?php echo $default_selection === 'percentage_3' ? '' : 'disabled'; ?>>
			<span><?php echo esc_html( $percentage_3 ); ?>%</span>
		</label>

		<label class="lbite-tip-option lbite-tip-custom">
			<input type="radio" name="lbite_tip_type" value="custom">
			<span><?php esc_html_e( 'Individuell:', 'libre-bite' ); ?></span>
			<input type="number" name="lbite_tip_custom" min="0" max="100" step="0.1" placeholder="0">
			<span>%</span>
		</label>
	</div>
</div>

