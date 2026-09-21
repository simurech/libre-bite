<?php
/**
 * Einstellungen: Stempelkarte
 *
 * Ausgelagert aus promotions.php (weitere Aufteilung nach v3.2.0).
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Guests collect a stamp per completed order and get a voucher once the card is full. Only works for orders placed with a customer account — guest checkouts cannot be attributed to anyone.', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="marketing_stampcard">

	<h2>
		<?php esc_html_e( 'Stamp Card', 'libre-bite' ); ?>
		<?php if ( ! $lbite_premium_allowed ) : ?>
			<span class="lbite-pro-badge">Pro</span>
		<?php endif; ?>
	</h2>

	<div id="lbite-stampcard-preview" class="lbite-preview-box" aria-hidden="true">
		<p class="lbite-preview-box__label"><?php esc_html_e( 'Preview: progress card shown to the guest', 'libre-bite' ); ?></p>
		<div class="lbite-stampcard-preview__dots" id="lbite-stampcard-preview-dots"></div>
		<p class="lbite-stampcard-preview__note" id="lbite-stampcard-preview-note"></p>
	</div>

	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Enable stamp card', 'libre-bite' ); ?></th>
			<td>
				<label class="<?php echo $lbite_premium_allowed ? '' : 'lbite-locked'; ?>">
					<input type="checkbox" name="lbite_feature_toggle[enable_stampcard]" value="1"
						<?php checked( lbite_feature_enabled( 'enable_stampcard' ) ); ?>
						<?php disabled( ! $lbite_premium_allowed ); ?>>
					<?php esc_html_e( 'Collect stamps and issue vouchers', 'libre-bite' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Show the card anywhere with the shortcode [lbite_stampcard]. It also appears in the customer account.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Stamps needed', 'libre-bite' ); ?></th>
			<td>
				<input type="number" min="2" id="lbite_stampcard_target" name="lbite_stampcard_target" class="small-text"
					value="<?php echo esc_attr( get_option( 'lbite_stampcard_target', 10 ) ); ?>" <?php disabled( ! $lbite_premium_allowed ); ?>>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Reward', 'libre-bite' ); ?></th>
			<td>
				<input type="number" min="1" max="100" id="lbite_stampcard_discount" name="lbite_stampcard_discount" class="small-text"
					value="<?php echo esc_attr( get_option( 'lbite_stampcard_discount', 50 ) ); ?>" <?php disabled( ! $lbite_premium_allowed ); ?>> %
				<p class="description"><?php esc_html_e( 'Issued as a single-use WooCommerce coupon, tied to the guest’s email address so it cannot be passed on.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Minimum order value', 'libre-bite' ); ?></th>
			<td>
				<input type="number" step="0.01" min="0" name="lbite_stampcard_min_total" class="small-text"
					value="<?php echo esc_attr( get_option( 'lbite_stampcard_min_total', 0 ) ); ?>" <?php disabled( ! $lbite_premium_allowed ); ?>>
				<p class="description"><?php esc_html_e( 'Orders below this value do not earn a stamp. 0 means every order counts.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Voucher valid for', 'libre-bite' ); ?></th>
			<td>
				<input type="number" min="1" name="lbite_stampcard_validity_days" class="small-text"
					value="<?php echo esc_attr( get_option( 'lbite_stampcard_validity_days', 90 ) ); ?>" <?php disabled( ! $lbite_premium_allowed ); ?>>
				<?php esc_html_e( 'days', 'libre-bite' ); ?>
			</td>
		</tr>
	</table>

	<script>
	(function() {
		var stampTarget   = document.getElementById('lbite_stampcard_target');
		var stampDiscount = document.getElementById('lbite_stampcard_discount');
		var dotsWrap      = document.getElementById('lbite-stampcard-preview-dots');
		var noteEl        = document.getElementById('lbite-stampcard-preview-note');

		function updateStampPreview() {
			if ( ! dotsWrap ) { return; }
			var target = Math.max(2, parseInt(stampTarget && stampTarget.value, 10) || 10);
			var reward = parseInt(stampDiscount && stampDiscount.value, 10) || 0;
			dotsWrap.innerHTML = '';
			for (var i = 0; i < target; i++) {
				var dot = document.createElement('span');
				dot.className = 'lbite-stampcard-preview__dot' + (i === 0 ? ' is-filled' : '');
				dotsWrap.appendChild(dot);
			}
			noteEl.textContent = target + ' ' + <?php echo wp_json_encode( __( 'stamps →', 'libre-bite' ) ); ?> + ' ' + reward + '% ' + <?php echo wp_json_encode( __( 'off the next order', 'libre-bite' ) ); ?>;
		}
		if ( stampTarget ) { stampTarget.addEventListener('input', updateStampPreview); }
		if ( stampDiscount ) { stampDiscount.addEventListener('input', updateStampPreview); }
		updateStampPreview();
	})();
	</script>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
