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

$lbite_stamp_cats = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
	)
);
if ( is_wp_error( $lbite_stamp_cats ) ) {
	$lbite_stamp_cats = array();
}
$lbite_stamp_type       = 'fixed' === get_option( 'lbite_stampcard_discount_type', 'percent' ) ? 'fixed' : 'percent';
$lbite_stamp_limit_cats = array_map( 'strval', (array) get_option( 'lbite_stampcard_limit_categories', array() ) );
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
				<p class="description">
					<?php
					printf(
						/* translators: %s: shortcode */
						esc_html__( 'Show the card anywhere with the shortcode %s. It also appears in the customer account.', 'libre-bite' ),
						'<code>[lbite_stampcard]</code>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup, not user input.
					);
					?>
				</p>
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
				<label style="margin-right:12px;">
					<input type="radio" name="lbite_stampcard_discount_type" value="percent"
						<?php checked( 'percent', $lbite_stamp_type ); ?> <?php disabled( ! $lbite_premium_allowed ); ?>>
					<?php esc_html_e( 'Percentage off', 'libre-bite' ); ?>
				</label>
				<label>
					<input type="radio" name="lbite_stampcard_discount_type" value="fixed"
						<?php checked( 'fixed', $lbite_stamp_type ); ?> <?php disabled( ! $lbite_premium_allowed ); ?>>
					<?php esc_html_e( 'Fixed amount off', 'libre-bite' ); ?>
				</label>
				<br>
				<input type="number" min="1" <?php echo 'percent' === $lbite_stamp_type ? 'max="100"' : ''; ?> step="0.01" id="lbite_stampcard_discount" name="lbite_stampcard_discount" class="small-text"
					value="<?php echo esc_attr( get_option( 'lbite_stampcard_discount', 50 ) ); ?>" <?php disabled( ! $lbite_premium_allowed ); ?>>
				<span id="lbite-stampcard-discount-unit"><?php echo 'percent' === $lbite_stamp_type ? '%' : esc_html( get_woocommerce_currency() ); ?></span>
				<p class="description"><?php esc_html_e( 'Issued as a single-use WooCommerce coupon, tied to the guest’s email address so it cannot be passed on.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr id="lbite-stampcard-max-amount-row" <?php echo 'percent' === $lbite_stamp_type ? '' : 'style="display:none;"'; ?>>
			<th><?php esc_html_e( 'Maximum amount', 'libre-bite' ); ?></th>
			<td>
				<input type="number" step="0.01" min="0" name="lbite_stampcard_max_amount" class="small-text"
					value="<?php echo esc_attr( get_option( 'lbite_stampcard_max_amount', 0 ) ); ?>" <?php disabled( ! $lbite_premium_allowed ); ?>>
				<p class="description"><?php esc_html_e( 'Caps a percentage reward, e.g. 100% off but never more than 19.00. 0 means no cap.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Limit to categories', 'libre-bite' ); ?></th>
			<td>
				<?php if ( empty( $lbite_stamp_cats ) ) : ?>
					<p class="description"><?php esc_html_e( 'No product categories exist yet.', 'libre-bite' ); ?></p>
				<?php else : ?>
					<?php foreach ( $lbite_stamp_cats as $lbite_stamp_cat ) : ?>
						<label style="display:inline-block; margin:0 12px 4px 0;">
							<input type="checkbox"
								name="lbite_stampcard_limit_categories[]"
								value="<?php echo esc_attr( $lbite_stamp_cat->term_id ); ?>"
								<?php checked( in_array( (string) $lbite_stamp_cat->term_id, $lbite_stamp_limit_cats, true ) ); ?>
								<?php disabled( ! $lbite_premium_allowed ); ?>>
							<?php echo esc_html( $lbite_stamp_cat->name ); ?>
						</label>
					<?php endforeach; ?>
				<?php endif; ?>
				<p class="description"><?php esc_html_e( 'Leave empty to allow the reward on any product.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Item limit', 'libre-bite' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="lbite_stampcard_limit_to_one_item" value="1"
						<?php checked( (bool) get_option( 'lbite_stampcard_limit_to_one_item', 0 ) ); ?> <?php disabled( ! $lbite_premium_allowed ); ?>>
					<?php esc_html_e( 'Apply the reward to only 1 item in the next order, not the whole order', 'libre-bite' ); ?>
				</label>
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
		var stampTarget    = document.getElementById('lbite_stampcard_target');
		var stampDiscount  = document.getElementById('lbite_stampcard_discount');
		var dotsWrap       = document.getElementById('lbite-stampcard-preview-dots');
		var noteEl         = document.getElementById('lbite-stampcard-preview-note');
		var unitEl         = document.getElementById('lbite-stampcard-discount-unit');
		var maxAmountRow   = document.getElementById('lbite-stampcard-max-amount-row');
		var typeRadios     = document.querySelectorAll('input[name="lbite_stampcard_discount_type"]');
		var currencySymbol = <?php echo wp_json_encode( html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) ); ?>;

		function currentType() {
			for (var i = 0; i < typeRadios.length; i++) {
				if (typeRadios[i].checked) { return typeRadios[i].value; }
			}
			return 'percent';
		}

		function updateStampPreview() {
			if ( ! dotsWrap ) { return; }
			var type   = currentType();
			var target = Math.max(2, parseInt(stampTarget && stampTarget.value, 10) || 10);
			var reward = parseFloat(stampDiscount && stampDiscount.value) || 0;
			dotsWrap.innerHTML = '';
			for (var i = 0; i < target; i++) {
				var dot = document.createElement('span');
				dot.className = 'lbite-stampcard-preview__dot' + (i === 0 ? ' is-filled' : '');
				dotsWrap.appendChild(dot);
			}
			if (unitEl) { unitEl.textContent = type === 'fixed' ? currencySymbol : '%'; }
			if (maxAmountRow) { maxAmountRow.style.display = type === 'fixed' ? 'none' : ''; }
			if (stampDiscount) { stampDiscount.max = type === 'percent' ? '100' : ''; }
			var rewardLabel = type === 'fixed' ? (reward + ' ' + currencySymbol) : (reward + '%');
			noteEl.textContent = target + ' ' + <?php echo wp_json_encode( __( 'stamps →', 'libre-bite' ) ); ?> + ' ' + rewardLabel + ' ' + <?php echo wp_json_encode( __( 'off the next order', 'libre-bite' ) ); ?>;
		}
		if ( stampTarget ) { stampTarget.addEventListener('input', updateStampPreview); }
		if ( stampDiscount ) { stampDiscount.addEventListener('input', updateStampPreview); }
		for (var j = 0; j < typeRadios.length; j++) {
			typeRadios[j].addEventListener('change', updateStampPreview);
		}
		updateStampPreview();
	})();
	</script>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
