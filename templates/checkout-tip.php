<?php
/**
 * Template: Trinkgeld-Auswahl im Checkout
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_is_fixed   = 'fixed' === $lbite_tip_mode;
$lbite_currency   = get_woocommerce_currency_symbol();
$lbite_heading    = $lbite_tip_title ? $lbite_tip_title : __( 'Add a tip?', 'libre-bite' );

// Labels: Custom-Label oder Auto-generiert
$lbite_label_1 = $lbite_tip_label_1 ?: ( $lbite_is_fixed
	? $lbite_currency . '&nbsp;' . number_format_i18n( floatval( $lbite_percentage_1 ), 2 )
	: floatval( $lbite_percentage_1 ) . '%' );
$lbite_label_2 = $lbite_tip_label_2 ?: ( $lbite_is_fixed
	? $lbite_currency . '&nbsp;' . number_format_i18n( floatval( $lbite_percentage_2 ), 2 )
	: floatval( $lbite_percentage_2 ) . '%' );
$lbite_label_3 = $lbite_tip_label_3 ?: ( $lbite_is_fixed
	? $lbite_currency . '&nbsp;' . number_format_i18n( floatval( $lbite_percentage_3 ), 2 )
	: floatval( $lbite_percentage_3 ) . '%' );
?>

<div class="lbite-tip-selection">
	<h3><?php echo esc_html( $lbite_heading ); ?></h3>

	<div class="lbite-tip-options">
		<label class="lbite-tip-option">
			<input type="radio" name="lbite_tip_type" value="none" <?php checked( $lbite_default_selection, 'none' ); ?>>
			<span><?php echo esc_html( $lbite_tip_label_none ?: __( 'No tip', 'libre-bite' ) ); ?></span>
		</label>

		<label class="lbite-tip-option">
			<input type="radio" name="lbite_tip_type" value="percentage" data-percentage="<?php echo esc_attr( $lbite_percentage_1 ); ?>" <?php checked( $lbite_default_selection, 'percentage_1' ); ?>>
			<input type="hidden" name="lbite_tip_percentage" class="lbite-tip-percentage-value" value="<?php echo 'percentage_1' === $lbite_default_selection ? esc_attr( $lbite_percentage_1 ) : ''; ?>" <?php echo 'percentage_1' === $lbite_default_selection ? '' : 'disabled'; ?>>
			<span><?php echo wp_kses( $lbite_label_1, array( 'span' => array() ) ); ?></span>
		</label>

		<label class="lbite-tip-option">
			<input type="radio" name="lbite_tip_type" value="percentage" data-percentage="<?php echo esc_attr( $lbite_percentage_2 ); ?>" <?php checked( $lbite_default_selection, 'percentage_2' ); ?>>
			<input type="hidden" name="lbite_tip_percentage" class="lbite-tip-percentage-value" value="<?php echo 'percentage_2' === $lbite_default_selection ? esc_attr( $lbite_percentage_2 ) : ''; ?>" <?php echo 'percentage_2' === $lbite_default_selection ? '' : 'disabled'; ?>>
			<span><?php echo wp_kses( $lbite_label_2, array( 'span' => array() ) ); ?></span>
		</label>

		<label class="lbite-tip-option">
			<input type="radio" name="lbite_tip_type" value="percentage" data-percentage="<?php echo esc_attr( $lbite_percentage_3 ); ?>" <?php checked( $lbite_default_selection, 'percentage_3' ); ?>>
			<input type="hidden" name="lbite_tip_percentage" class="lbite-tip-percentage-value" value="<?php echo 'percentage_3' === $lbite_default_selection ? esc_attr( $lbite_percentage_3 ) : ''; ?>" <?php echo 'percentage_3' === $lbite_default_selection ? '' : 'disabled'; ?>>
			<span><?php echo wp_kses( $lbite_label_3, array( 'span' => array() ) ); ?></span>
		</label>

		<label class="lbite-tip-option lbite-tip-custom">
			<input type="radio" name="lbite_tip_type" value="custom">
			<span><?php esc_html_e( 'Custom:', 'libre-bite' ); ?></span>
			<?php if ( $lbite_is_fixed ) : ?>
				<input type="number" name="lbite_tip_custom" min="0" step="0.05" placeholder="0">
				<span><?php echo esc_html( $lbite_currency ); ?></span>
			<?php else : ?>
				<input type="number" name="lbite_tip_custom" min="0" max="100" step="0.1" placeholder="0">
				<span>%</span>
			<?php endif; ?>
		</label>
	</div>
</div>
