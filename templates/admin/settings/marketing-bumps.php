<?php
/**
 * Einstellungen: Zusatzangebote (Order Bumps)
 *
 * Ausgelagert aus marketing.php (weitere Aufteilung nach v3.2.0).
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Offer up to three matching extras right above the pay button. They are added as real line items, so they appear on the kitchen ticket and count in the statistics. Variable products are not offered here, because they would need a selection first.', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="marketing_bumps">

	<h2>
		<?php esc_html_e( 'Order Bumps', 'libre-bite' ); ?>
		<?php if ( ! $lbite_premium_allowed ) : ?>
			<span class="lbite-pro-badge">Pro</span>
		<?php endif; ?>
	</h2>

	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Enable Order Bumps', 'libre-bite' ); ?></th>
			<td>
				<label class="<?php echo $lbite_premium_allowed ? '' : 'lbite-locked'; ?>">
					<input type="checkbox" name="lbite_feature_toggle[enable_order_bumps]" value="1"
						<?php checked( lbite_feature_enabled( 'enable_order_bumps' ), true ); ?>
						<?php disabled( ! $lbite_premium_allowed ); ?>>
					<?php esc_html_e( 'Show impulse offers in the checkout', 'libre-bite' ); ?>
				</label>
			</td>
		</tr>
		<?php
		if ( ! class_exists( 'LBite_Order_Bumps' ) ) {
			require_once LBITE_PLUGIN_DIR . 'includes/modules/order-bumps/class-order-bumps.php';
		}
		$lbite_bumps = LBite_Order_Bumps::get_bumps();
		for ( $lbite_i = 0; $lbite_i < 3; $lbite_i++ ) :
			$lbite_bump_pid  = isset( $lbite_bumps[ $lbite_i ]['product_id'] ) ? (int) $lbite_bumps[ $lbite_i ]['product_id'] : 0;
			$lbite_bump_text = isset( $lbite_bumps[ $lbite_i ]['text'] ) ? $lbite_bumps[ $lbite_i ]['text'] : '';
			?>
			<tr>
				<th>
					<?php
					printf(
						/* translators: %d: slot number */
						esc_html__( 'Offer %d', 'libre-bite' ),
						(int) $lbite_i + 1
					);
					?>
				</th>
				<td>
					<input type="number" min="0"
						name="lbite_order_bumps[<?php echo (int) $lbite_i; ?>][product_id]"
						value="<?php echo $lbite_bump_pid ? esc_attr( $lbite_bump_pid ) : ''; ?>"
						class="small-text"
						placeholder="<?php esc_attr_e( 'Product ID', 'libre-bite' ); ?>"
						<?php disabled( ! $lbite_premium_allowed ); ?>>
					<input type="text"
						name="lbite_order_bumps[<?php echo (int) $lbite_i; ?>][text]"
						value="<?php echo esc_attr( $lbite_bump_text ); ?>"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'Optional text, e.g. “Fries with that?”', 'libre-bite' ); ?>"
						<?php disabled( ! $lbite_premium_allowed ); ?>>
					<?php if ( $lbite_bump_pid ) : ?>
						<?php $lbite_bump_obj = wc_get_product( $lbite_bump_pid ); ?>
						<p class="description">
							<?php
							echo $lbite_bump_obj
								? esc_html( $lbite_bump_obj->get_name() )
								: esc_html__( 'Product not found.', 'libre-bite' );
							?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
		<?php endfor; ?>
	</table>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
