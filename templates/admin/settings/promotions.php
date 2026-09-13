<?php
/**
 * Einstellungen: Aktionen und Ankündigung
 *
 * Bis zu zehn Regeln. Produkte und Kategorien werden als kommagetrennte
 * IDs eingegeben — bewusst schlicht: ein Auswahlfeld mit Suche über
 * mehrere hundert Produkte wäre hier mehr Aufwand als Nutzen, und die IDs
 * stehen in der Produktliste ohnehin in der URL.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LBite_Promotions' ) ) {
	require_once LBITE_PLUGIN_DIR . 'includes/modules/promotions/class-promotions.php';
}

$lbite_promo_rules  = LBite_Promotions::get_rules();
$lbite_promo_types  = LBite_Promotions::get_types();
$lbite_promo_banner = LBite_Promotions::get_banner();
$lbite_promo_days   = array(
	'monday'    => __( 'Mon', 'libre-bite' ),
	'tuesday'   => __( 'Tue', 'libre-bite' ),
	'wednesday' => __( 'Wed', 'libre-bite' ),
	'thursday'  => __( 'Thu', 'libre-bite' ),
	'friday'    => __( 'Fri', 'libre-bite' ),
	'saturday'  => __( 'Sat', 'libre-bite' ),
	'sunday'    => __( 'Sun', 'libre-bite' ),
);
?>

<hr style="margin: 24px 0;">

<h3>
	<?php esc_html_e( 'Promotions', 'libre-bite' ); ?>
	<?php if ( ! $lbite_premium_allowed ) : ?>
		<span class="lbite-pro-badge">Pro</span>
	<?php endif; ?>
</h3>

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
				<?php esc_html_e( 'Discounts are applied before the tip and the 5-cent rounding, so the tip is never calculated on an amount nobody pays.', 'libre-bite' ); ?>
			</p>
		</td>
	</tr>
</table>

<?php for ( $lbite_pi = 0; $lbite_pi < 5; $lbite_pi++ ) :
	$lbite_rule = isset( $lbite_promo_rules[ $lbite_pi ] ) ? $lbite_promo_rules[ $lbite_pi ] : array();
	$lbite_sch  = isset( $lbite_rule['schedule'] ) && is_array( $lbite_rule['schedule'] ) ? $lbite_rule['schedule'] : array();
	$lbite_name = 'lbite_promotions[' . $lbite_pi . ']';
	?>
	<fieldset style="border:1px solid #dcdcde; border-radius:8px; padding:12px 16px; margin-bottom:12px;">
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

<h3><?php esc_html_e( 'Announcement bar', 'libre-bite' ); ?></h3>

<table class="form-table">
	<tr>
		<th><?php esc_html_e( 'Show bar', 'libre-bite' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="lbite_promo_banner[enabled]" value="1"
					<?php checked( ! empty( $lbite_promo_banner['enabled'] ) ); ?> <?php disabled( ! $lbite_premium_allowed ); ?>>
				<?php esc_html_e( 'Show a bar at the top of the shop', 'libre-bite' ); ?>
			</label>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Text', 'libre-bite' ); ?></th>
		<td>
			<input type="text" name="lbite_promo_banner[text]" class="large-text"
				value="<?php echo esc_attr( $lbite_promo_banner['text'] ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. Happy Hour until 6 pm — every third drink is on us', 'libre-bite' ); ?>"
				<?php disabled( ! $lbite_premium_allowed ); ?>>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Link', 'libre-bite' ); ?></th>
		<td>
			<input type="url" name="lbite_promo_banner[link]" class="regular-text"
				value="<?php echo esc_attr( $lbite_promo_banner['link'] ); ?>"
				<?php disabled( ! $lbite_premium_allowed ); ?>>
			<p class="description"><?php esc_html_e( 'Optional. Leave empty for plain text.', 'libre-bite' ); ?></p>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'When', 'libre-bite' ); ?></th>
		<td>
			<?php $lbite_bs = is_array( $lbite_promo_banner['schedule'] ) ? $lbite_promo_banner['schedule'] : array(); ?>
			<label>
				<input type="checkbox" name="lbite_promo_banner[schedule][enabled]" value="1"
					<?php checked( ! empty( $lbite_bs['enabled'] ) ); ?> <?php disabled( ! $lbite_premium_allowed ); ?>>
				<?php esc_html_e( 'Limit to certain days or times', 'libre-bite' ); ?>
			</label>
			<p style="margin:8px 0;">
				<?php foreach ( $lbite_promo_days as $lbite_dk => $lbite_dl ) : ?>
					<label style="display:inline-block; margin:0 8px 4px 0;">
						<input type="checkbox" name="lbite_promo_banner[schedule][days][<?php echo esc_attr( $lbite_dk ); ?>]" value="1"
							<?php checked( ! empty( $lbite_bs['days'][ $lbite_dk ] ) ); ?> <?php disabled( ! $lbite_premium_allowed ); ?>>
						<?php echo esc_html( $lbite_dl ); ?>
					</label>
				<?php endforeach; ?>
			</p>
			<label><?php esc_html_e( 'From', 'libre-bite' ); ?>
				<input type="time" name="lbite_promo_banner[schedule][from]" value="<?php echo esc_attr( isset( $lbite_bs['from'] ) ? $lbite_bs['from'] : '' ); ?>" <?php disabled( ! $lbite_premium_allowed ); ?>>
			</label>
			<label style="margin-left:8px;"><?php esc_html_e( 'To', 'libre-bite' ); ?>
				<input type="time" name="lbite_promo_banner[schedule][to]" value="<?php echo esc_attr( isset( $lbite_bs['to'] ) ? $lbite_bs['to'] : '' ); ?>" <?php disabled( ! $lbite_premium_allowed ); ?>>
			</label>
		</td>
	</tr>
</table>

<hr style="margin: 24px 0;">

<h3>
	<?php esc_html_e( 'Stamp Card', 'libre-bite' ); ?>
	<?php if ( ! $lbite_premium_allowed ) : ?>
		<span class="lbite-pro-badge">Pro</span>
	<?php endif; ?>
</h3>

<p class="description" style="margin-bottom: 12px;">
	<?php esc_html_e( 'Guests collect a stamp per completed order and get a voucher once the card is full. Only works for orders placed with a customer account — guest checkouts cannot be attributed to anyone.', 'libre-bite' ); ?>
</p>

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
			<input type="number" min="2" name="lbite_stampcard_target" class="small-text"
				value="<?php echo esc_attr( get_option( 'lbite_stampcard_target', 10 ) ); ?>" <?php disabled( ! $lbite_premium_allowed ); ?>>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Reward', 'libre-bite' ); ?></th>
		<td>
			<input type="number" min="1" max="100" name="lbite_stampcard_discount" class="small-text"
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
