<?php
/**
 * Einstellungen: Aktionen (Promotions)
 *
 * Ausgelagert aus promotions.php (weitere Aufteilung nach v3.2.0). Produkte
 * und Kategorien werden als kommagetrennte IDs eingegeben — bewusst schlicht.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LBite_Promotions' ) ) {
	require_once LBITE_PLUGIN_DIR . 'includes/modules/promotions/class-promotions.php';
}

$lbite_promo_rules = LBite_Promotions::get_rules();
$lbite_promo_types = LBite_Promotions::get_types();
$lbite_promo_days  = array(
	'monday'    => __( 'Mon', 'libre-bite' ),
	'tuesday'   => __( 'Tue', 'libre-bite' ),
	'wednesday' => __( 'Wed', 'libre-bite' ),
	'thursday'  => __( 'Thu', 'libre-bite' ),
	'friday'    => __( 'Fri', 'libre-bite' ),
	'saturday'  => __( 'Sat', 'libre-bite' ),
	'sunday'    => __( 'Sun', 'libre-bite' ),
);
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Up to five rule-based discounts: a percentage or fixed amount off, "buy several, pay for fewer", optionally limited to certain products, categories, days or times.', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="marketing_promotions">

	<h2>
		<?php esc_html_e( 'Promotions', 'libre-bite' ); ?>
		<?php if ( ! $lbite_premium_allowed ) : ?>
			<span class="lbite-pro-badge">Pro</span>
		<?php endif; ?>
	</h2>

	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Enable promotions', 'libre-bite' ); ?></th>
			<td>
				<label class="<?php echo $lbite_premium_allowed ? '' : 'lbite-locked'; ?>">
					<input type="checkbox" name="lbite_feature_toggle[enable_promotions]" value="1"
						<?php checked( lbite_feature_enabled( 'enable_promotions' ) ); ?>
						<?php disabled( ! $lbite_premium_allowed ); ?>>
					<?php esc_html_e( 'Apply rule-based offers and show an announcement bar', 'libre-bite' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Discounts are applied before the tip and the 5-cent rounding, so the tip is never calculated on an amount nobody pays. The Announcement Bar (its own settings page) also depends on this toggle.', 'libre-bite' ); ?>
				</p>
			</td>
		</tr>
	</table>

	<?php for ( $lbite_pi = 0; $lbite_pi < 5; $lbite_pi++ ) :
		$lbite_rule = isset( $lbite_promo_rules[ $lbite_pi ] ) ? $lbite_promo_rules[ $lbite_pi ] : array();
		$lbite_sch  = isset( $lbite_rule['schedule'] ) && is_array( $lbite_rule['schedule'] ) ? $lbite_rule['schedule'] : array();
		$lbite_name = 'lbite_promotions[' . $lbite_pi . ']';
		?>
		<fieldset style="border:1px solid var(--lbite-border, #dcdcde); border-radius:8px; padding:12px 16px; margin-bottom:12px;">
			<legend style="font-weight:600; padding:0 6px;">
				<?php
				printf(
					/* translators: %d: rule number */
					esc_html__( 'Rule %d', 'libre-bite' ),
					(int) $lbite_pi + 1
				);
				?>
			</legend>

			<table class="form-table" style="margin-top:0;">
				<tr>
					<th><?php esc_html_e( 'Active', 'libre-bite' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $lbite_name ); ?>[enabled]" value="1"
								<?php checked( ! empty( $lbite_rule['enabled'] ) ); ?> <?php disabled( ! $lbite_premium_allowed ); ?>>
							<?php esc_html_e( 'Rule is live', 'libre-bite' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Name', 'libre-bite' ); ?></th>
					<td>
						<input type="text" name="<?php echo esc_attr( $lbite_name ); ?>[label]" class="regular-text"
							value="<?php echo esc_attr( isset( $lbite_rule['label'] ) ? $lbite_rule['label'] : '' ); ?>"
							placeholder="<?php esc_attr_e( 'e.g. Happy Hour', 'libre-bite' ); ?>"
							<?php disabled( ! $lbite_premium_allowed ); ?>>
						<p class="description"><?php esc_html_e( 'Appears on the guest’s order as the reason for the discount.', 'libre-bite' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Type', 'libre-bite' ); ?></th>
					<td>
						<select name="<?php echo esc_attr( $lbite_name ); ?>[type]" <?php disabled( ! $lbite_premium_allowed ); ?>>
							<?php foreach ( $lbite_promo_types as $lbite_tk => $lbite_tl ) : ?>
								<option value="<?php echo esc_attr( $lbite_tk ); ?>"
									<?php selected( isset( $lbite_rule['type'] ) ? $lbite_rule['type'] : '', $lbite_tk ); ?>>
									<?php echo esc_html( $lbite_tl ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Discount', 'libre-bite' ); ?></th>
					<td>
						<input type="number" step="0.01" min="0" name="<?php echo esc_attr( $lbite_name ); ?>[amount]" class="small-text"
							value="<?php echo esc_attr( isset( $lbite_rule['amount'] ) ? $lbite_rule['amount'] : '' ); ?>"
							<?php disabled( ! $lbite_premium_allowed ); ?>>
						<label style="margin-left:10px;">
							<input type="checkbox" name="<?php echo esc_attr( $lbite_name ); ?>[is_percent]" value="1"
								<?php checked( ! empty( $lbite_rule['is_percent'] ) ); ?> <?php disabled( ! $lbite_premium_allowed ); ?>>
							<?php esc_html_e( 'as percent', 'libre-bite' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Not used for “buy several, pay for fewer” — there the cheapest items are free.', 'libre-bite' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Applies to', 'libre-bite' ); ?></th>
					<td>
						<input type="text" name="<?php echo esc_attr( $lbite_name ); ?>[product_ids]" class="regular-text"
							value="<?php echo esc_attr( implode( ',', isset( $lbite_rule['product_ids'] ) ? $lbite_rule['product_ids'] : array() ) ); ?>"
							placeholder="<?php esc_attr_e( 'Product IDs, comma separated', 'libre-bite' ); ?>"
							<?php disabled( ! $lbite_premium_allowed ); ?>><br>
						<input type="text" name="<?php echo esc_attr( $lbite_name ); ?>[category_ids]" class="regular-text" style="margin-top:6px;"
							value="<?php echo esc_attr( implode( ',', isset( $lbite_rule['category_ids'] ) ? $lbite_rule['category_ids'] : array() ) ); ?>"
							placeholder="<?php esc_attr_e( 'Category IDs, comma separated', 'libre-bite' ); ?>"
							<?php disabled( ! $lbite_premium_allowed ); ?>>
						<p class="description"><?php esc_html_e( 'Leave both empty to apply to everything.', 'libre-bite' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Buy / free', 'libre-bite' ); ?></th>
					<td>
						<input type="number" min="2" name="<?php echo esc_attr( $lbite_name ); ?>[buy_qty]" class="small-text"
							value="<?php echo esc_attr( isset( $lbite_rule['buy_qty'] ) ? $lbite_rule['buy_qty'] : 3 ); ?>"
							<?php disabled( ! $lbite_premium_allowed ); ?>>
						<input type="number" min="1" name="<?php echo esc_attr( $lbite_name ); ?>[free_qty]" class="small-text"
							value="<?php echo esc_attr( isset( $lbite_rule['free_qty'] ) ? $lbite_rule['free_qty'] : 1 ); ?>"
							<?php disabled( ! $lbite_premium_allowed ); ?>>
						<p class="description"><?php esc_html_e( 'Only for “buy several, pay for fewer”. Example 3 and 1 means: three in the basket, the cheapest one is free.', 'libre-bite' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Minimum order value', 'libre-bite' ); ?></th>
					<td>
						<input type="number" step="0.01" min="0" name="<?php echo esc_attr( $lbite_name ); ?>[min_total]" class="small-text"
							value="<?php echo esc_attr( isset( $lbite_rule['min_total'] ) ? $lbite_rule['min_total'] : '' ); ?>"
							<?php disabled( ! $lbite_premium_allowed ); ?>>
						<p class="description"><?php esc_html_e( 'Only for “discount on the whole order”.', 'libre-bite' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'When', 'libre-bite' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $lbite_name ); ?>[schedule][enabled]" value="1"
								<?php checked( ! empty( $lbite_sch['enabled'] ) ); ?> <?php disabled( ! $lbite_premium_allowed ); ?>>
							<?php esc_html_e( 'Limit to certain days or times', 'libre-bite' ); ?>
						</label>
						<p style="margin:8px 0;">
							<?php foreach ( $lbite_promo_days as $lbite_dk => $lbite_dl ) : ?>
								<label style="display:inline-block; margin:0 8px 4px 0;">
									<input type="checkbox" name="<?php echo esc_attr( $lbite_name ); ?>[schedule][days][<?php echo esc_attr( $lbite_dk ); ?>]" value="1"
										<?php checked( ! empty( $lbite_sch['days'][ $lbite_dk ] ) ); ?> <?php disabled( ! $lbite_premium_allowed ); ?>>
									<?php echo esc_html( $lbite_dl ); ?>
								</label>
							<?php endforeach; ?>
						</p>
						<label><?php esc_html_e( 'From', 'libre-bite' ); ?>
							<input type="time" name="<?php echo esc_attr( $lbite_name ); ?>[schedule][from]"
								value="<?php echo esc_attr( isset( $lbite_sch['from'] ) ? $lbite_sch['from'] : '' ); ?>"
								<?php disabled( ! $lbite_premium_allowed ); ?>>
						</label>
						<label style="margin-left:8px;"><?php esc_html_e( 'To', 'libre-bite' ); ?>
							<input type="time" name="<?php echo esc_attr( $lbite_name ); ?>[schedule][to]"
								value="<?php echo esc_attr( isset( $lbite_sch['to'] ) ? $lbite_sch['to'] : '' ); ?>"
								<?php disabled( ! $lbite_premium_allowed ); ?>>
						</label>
						<p class="description"><?php esc_html_e( 'A window may run past midnight, for example 22:00 to 02:00.', 'libre-bite' ); ?></p>
					</td>
				</tr>
			</table>
		</fieldset>
	<?php endfor; ?>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
