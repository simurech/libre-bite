<?php
/**
 * Einstellungen: Trinkgeld
 *
 * Ausgelagert aus checkout.php (weitere Aufteilung nach v3.2.0).
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_tip_pct_1      = get_option( 'lbite_tip_percentage_1', 5 );
$lbite_tip_pct_2      = get_option( 'lbite_tip_percentage_2', 10 );
$lbite_tip_pct_3      = get_option( 'lbite_tip_percentage_3', 15 );
$lbite_tip_select     = get_option( 'lbite_tip_default_selection', 'none' );
$lbite_tip_mode       = get_option( 'lbite_tip_mode', 'percentage' );
$lbite_tip_title      = get_option( 'lbite_tip_title', '' );
$lbite_tip_lbl_none   = get_option( 'lbite_tip_label_none', '' );
$lbite_tip_lbl_1      = get_option( 'lbite_tip_label_1', '' );
$lbite_tip_lbl_2      = get_option( 'lbite_tip_label_2', '' );
$lbite_tip_lbl_3      = get_option( 'lbite_tip_label_3', '' );
$lbite_tip_is_fixed   = 'fixed' === $lbite_tip_mode;
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Let customers add a tip at checkout, as a percentage of the order or a fixed amount.', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="checkout_tips">

	<?php
	$lbite_toggle_key             = 'enable_tips';
	$lbite_toggle_label           = __( 'Tips', 'libre-bite' );
	$lbite_toggle_description     = __( 'Allow customers to add a tip at checkout.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<?php if ( $lbite_premium_allowed && lbite_feature_enabled( 'enable_tips' ) ) : ?>
	<p class="description" style="margin-bottom: 8px;">
		<?php esc_html_e( 'A further switch to hide tips without turning this feature off entirely is available under "Fields in Standard Checkout".', 'libre-bite' ); ?>
	</p>

	<div id="lbite-tip-preview" class="lbite-preview-box" aria-hidden="true">
		<p class="lbite-preview-box__label"><?php esc_html_e( 'Preview: how this looks at checkout', 'libre-bite' ); ?></p>
		<div class="lbite-preview-box__body">
			<strong id="lbite-tip-preview-title"><?php echo esc_html( $lbite_tip_title ? $lbite_tip_title : __( 'Add a tip?', 'libre-bite' ) ); ?></strong>
			<div class="lbite-tip-preview__row">
				<span class="lbite-tip-preview__btn" data-slot="none"><?php echo esc_html( $lbite_tip_lbl_none ? $lbite_tip_lbl_none : __( 'No tip', 'libre-bite' ) ); ?></span>
				<span class="lbite-tip-preview__btn" data-slot="1"></span>
				<span class="lbite-tip-preview__btn" data-slot="2"></span>
				<span class="lbite-tip-preview__btn" data-slot="3"></span>
			</div>
		</div>
	</div>

	<table class="form-table">
	<tr>
		<th><?php esc_html_e( 'Tip Title', 'libre-bite' ); ?></th>
		<td>
			<input type="text" id="lbite_tip_title" name="lbite_tip_title" value="<?php echo esc_attr( $lbite_tip_title ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Add a tip?', 'libre-bite' ); ?>">
			<p class="description"><?php esc_html_e( 'Heading shown above the tip options. Leave empty to use the default.', 'libre-bite' ); ?></p>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( '"No Tip" Label', 'libre-bite' ); ?></th>
		<td>
			<input type="text" id="lbite_tip_label_none" name="lbite_tip_label_none" value="<?php echo esc_attr( $lbite_tip_lbl_none ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'No tip', 'libre-bite' ); ?>">
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Tip Mode', 'libre-bite' ); ?></th>
		<td>
			<label style="margin-right: 16px;">
				<input type="radio" name="lbite_tip_mode" value="percentage" <?php checked( $lbite_tip_mode, 'percentage' ); ?>>
				<?php esc_html_e( 'Percentage of order total', 'libre-bite' ); ?>
			</label>
			<label>
				<input type="radio" name="lbite_tip_mode" value="fixed" <?php checked( $lbite_tip_mode, 'fixed' ); ?>>
				<?php esc_html_e( 'Fixed amount', 'libre-bite' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'Percentage of the order total, or a fixed amount in your shop currency — either way, the same three options are offered to the guest.', 'libre-bite' ); ?></p>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Option 1', 'libre-bite' ); ?></th>
		<td>
			<input type="number" step="0.01" min="0" id="lbite_tip_percentage_1" name="lbite_tip_percentage_1" value="<?php echo esc_attr( $lbite_tip_pct_1 ); ?>" class="small-text">
			<span class="lbite-tip-unit"><?php echo $lbite_tip_is_fixed ? esc_html( get_woocommerce_currency_symbol() ) : '%'; ?></span>
			&nbsp;&nbsp;
			<input type="text" id="lbite_tip_label_1" name="lbite_tip_label_1" value="<?php echo esc_attr( $lbite_tip_lbl_1 ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Custom label (optional)', 'libre-bite' ); ?>">
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Option 2', 'libre-bite' ); ?></th>
		<td>
			<input type="number" step="0.01" min="0" id="lbite_tip_percentage_2" name="lbite_tip_percentage_2" value="<?php echo esc_attr( $lbite_tip_pct_2 ); ?>" class="small-text">
			<span class="lbite-tip-unit"><?php echo $lbite_tip_is_fixed ? esc_html( get_woocommerce_currency_symbol() ) : '%'; ?></span>
			&nbsp;&nbsp;
			<input type="text" id="lbite_tip_label_2" name="lbite_tip_label_2" value="<?php echo esc_attr( $lbite_tip_lbl_2 ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Custom label (optional)', 'libre-bite' ); ?>">
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Option 3', 'libre-bite' ); ?></th>
		<td>
			<input type="number" step="0.01" min="0" id="lbite_tip_percentage_3" name="lbite_tip_percentage_3" value="<?php echo esc_attr( $lbite_tip_pct_3 ); ?>" class="small-text">
			<span class="lbite-tip-unit"><?php echo $lbite_tip_is_fixed ? esc_html( get_woocommerce_currency_symbol() ) : '%'; ?></span>
			&nbsp;&nbsp;
			<input type="text" id="lbite_tip_label_3" name="lbite_tip_label_3" value="<?php echo esc_attr( $lbite_tip_lbl_3 ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Custom label (optional)', 'libre-bite' ); ?>">
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Default Selection', 'libre-bite' ); ?></th>
		<td>
			<select name="lbite_tip_default_selection">
				<option value="none" <?php selected( $lbite_tip_select, 'none' ); ?>><?php esc_html_e( 'No Tip (Default)', 'libre-bite' ); ?></option>
				<option value="percentage_1" <?php selected( $lbite_tip_select, 'percentage_1' ); ?>><?php esc_html_e( 'Option 1', 'libre-bite' ); ?></option>
				<option value="percentage_2" <?php selected( $lbite_tip_select, 'percentage_2' ); ?>><?php esc_html_e( 'Option 2', 'libre-bite' ); ?></option>
				<option value="percentage_3" <?php selected( $lbite_tip_select, 'percentage_3' ); ?>><?php esc_html_e( 'Option 3', 'libre-bite' ); ?></option>
			</select>
		</td>
	</tr>
	</table>
	<script>
	(function() {
		var radios = document.querySelectorAll('input[name="lbite_tip_mode"]');
		var units  = document.querySelectorAll('.lbite-tip-unit');
		var currency = <?php echo wp_json_encode( get_woocommerce_currency_symbol() ); ?>;

		function refreshUnits() {
			var isFixed = document.querySelector('input[name="lbite_tip_mode"]:checked').value === 'fixed';
			units.forEach(function(u) { u.textContent = isFixed ? currency : '%'; });
			return isFixed;
		}

		radios.forEach(function(r) {
			r.addEventListener('change', function() { refreshUnits(); updateTipPreview(); });
		});

		var fields = [ 'lbite_tip_title', 'lbite_tip_label_none', 'lbite_tip_percentage_1', 'lbite_tip_label_1',
			'lbite_tip_percentage_2', 'lbite_tip_label_2', 'lbite_tip_percentage_3', 'lbite_tip_label_3' ];

		function updateTipPreview() {
			var preview = document.getElementById('lbite-tip-preview');
			if ( ! preview ) { return; }
			var isFixed = document.querySelector('input[name="lbite_tip_mode"]:checked').value === 'fixed';
			var unit = isFixed ? currency : '%';

			var titleEl = document.getElementById('lbite-tip-preview-title');
			var titleInput = document.getElementById('lbite_tip_title');
			titleEl.textContent = titleInput.value || <?php echo wp_json_encode( __( 'Add a tip?', 'libre-bite' ) ); ?>;

			var noneInput = document.getElementById('lbite_tip_label_none');
			preview.querySelector('[data-slot="none"]').textContent = noneInput.value || <?php echo wp_json_encode( __( 'No tip', 'libre-bite' ) ); ?>;

			[1, 2, 3].forEach(function(i) {
				var pct   = document.getElementById('lbite_tip_percentage_' + i).value || '0';
				var label = document.getElementById('lbite_tip_label_' + i).value;
				var slot  = preview.querySelector('[data-slot="' + i + '"]');
				slot.textContent = label ? (label + ' (' + pct + unit + ')') : (pct + unit);
			});
		}

		fields.forEach(function(id) {
			var el = document.getElementById(id);
			if ( el ) { el.addEventListener('input', updateTipPreview); }
		});

		refreshUnits();
		updateTipPreview();
	})();
	</script>
	<?php endif; ?>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
