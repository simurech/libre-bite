<?php
/**
 * Einstellungen: POS-System
 *
 * Ausgelagert aus settings-tabbed.php (v3.2.0) im Zuge der Umstellung auf
 * vertikale Navigation. Speicherlogik bleibt unverändert im zentralen
 * Save-Switch in settings-tabbed.php.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_pos_methods_default = array(
	array( 'key' => 'cash',  'label' => __( 'Cash', 'libre-bite' ),  'icon' => '💵', 'enabled' => true ),
	array( 'key' => 'card',  'label' => __( 'Card', 'libre-bite' ),  'icon' => '💳', 'enabled' => true ),
	array( 'key' => 'twint', 'label' => __( 'Twint', 'libre-bite' ), 'icon' => '📱', 'enabled' => true ),
	array( 'key' => 'other', 'label' => __( 'Other', 'libre-bite' ), 'icon' => '💱', 'enabled' => true ),
);
$lbite_saved_pos_methods   = get_option( 'lbite_pos_payment_methods', array() );
$lbite_pos_payment_methods = array();
foreach ( $lbite_pos_methods_default as $lbite_pos_def ) {
	$lbite_pos_s = array_filter( $lbite_saved_pos_methods, fn( $m ) => $m['key'] === $lbite_pos_def['key'] );
	$lbite_pos_s = $lbite_pos_s ? array_values( $lbite_pos_s )[0] : array();
	$lbite_pos_payment_methods[] = array(
		'key'     => $lbite_pos_def['key'],
		'label'   => ! empty( $lbite_pos_s['label'] ) ? $lbite_pos_s['label'] : $lbite_pos_def['label'],
		'icon'    => ! empty( $lbite_pos_s['icon'] ) ? $lbite_pos_s['icon'] : $lbite_pos_def['icon'],
		'enabled' => isset( $lbite_pos_s['enabled'] ) ? (bool) $lbite_pos_s['enabled'] : $lbite_pos_def['enabled'],
	);
}
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Set up the till used for orders taken in person: which payment methods staff can choose from, and the default order type when the till is opened.', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="pos">

	<?php
	$lbite_toggle_key         = 'enable_pos';
	$lbite_toggle_label       = __( 'POS System', 'libre-bite' );
	$lbite_toggle_description = __( 'Enable the Point of Sale interface for in-person orders.', 'libre-bite' );
	$lbite_toggle_is_pro      = false;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';

	$lbite_toggle_key             = 'enable_split_payment';
	$lbite_toggle_label           = __( 'Split Payment', 'libre-bite' );
	$lbite_toggle_description     = __( 'Allow splitting the total across multiple payment methods in the POS payment modal.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';

	$lbite_toggle_key             = 'enable_open_tabs';
	$lbite_toggle_label           = __( 'Open Tabs (Table Service)', 'libre-bite' );
	$lbite_toggle_description     = lbite_feature_enabled( 'enable_table_ordering' )
		? __( 'Keep orders open per table and add items until the guests pay.', 'libre-bite' )
		: __( 'Keep orders open per table and add items until the guests pay. Requires the Table Management module to select a table.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Payment Methods', 'libre-bite' ); ?></th>
			<td>
				<p class="description" style="margin-bottom: 12px;"><?php esc_html_e( 'Choose which payment methods are displayed in the POS payment modal and customize the labels.', 'libre-bite' ); ?></p>
				<table class="widefat" style="max-width: 600px;">
					<thead>
						<tr>
							<th style="width: 40px;"><?php esc_html_e( 'Active', 'libre-bite' ); ?></th>
							<th style="width: 72px;"><?php esc_html_e( 'Icon', 'libre-bite' ); ?></th>
							<th><?php esc_html_e( 'Label', 'libre-bite' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $lbite_pos_payment_methods as $lbite_pm ) : ?>
						<tr>
							<td><input type="checkbox" name="lbite_pm_enabled[<?php echo esc_attr( $lbite_pm['key'] ); ?>]" value="1" <?php checked( $lbite_pm['enabled'] ); ?>></td>
							<td><input type="text" name="lbite_pm_icon[<?php echo esc_attr( $lbite_pm['key'] ); ?>]" value="<?php echo esc_attr( $lbite_pm['icon'] ); ?>" class="small-text" style="width: 56px; text-align: center; font-size: 18px;"></td>
							<td><input type="text" name="lbite_pm_label[<?php echo esc_attr( $lbite_pm['key'] ); ?>]" value="<?php echo esc_attr( $lbite_pm['label'] ); ?>" class="regular-text" style="max-width: 200px;"></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="description" style="margin-top: 8px;"><?php esc_html_e( 'At least one payment method must be active.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<?php if ( lbite_feature_enabled( 'enable_swiss_vat' ) || lbite_feature_enabled( 'enable_table_ordering' ) ) : ?>
		<?php $lbite_pos_default_vat = get_option( 'lbite_pos_default_vat_type', 'takeaway' ); ?>
		<tr>
			<th><?php esc_html_e( 'Default Order Type', 'libre-bite' ); ?></th>
			<td>
				<label style="margin-right: 16px;">
					<input type="radio" name="lbite_pos_default_vat_type" value="takeaway" <?php checked( $lbite_pos_default_vat, 'takeaway' ); ?>>
					<?php esc_html_e( 'Takeaway', 'libre-bite' ); ?>
				</label>
				<label>
					<input type="radio" name="lbite_pos_default_vat_type" value="dine_in" <?php checked( $lbite_pos_default_vat, 'dine_in' ); ?>>
					<?php esc_html_e( 'Dine-in', 'libre-bite' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Which order type is pre-selected when the POS is opened or reloaded.', 'libre-bite' ); ?></p>
				<?php if ( lbite_feature_enabled( 'enable_swiss_vat' ) ) : ?>
				<p class="description">
					<?php
					printf(
						/* translators: %s: link to Prices & Taxes settings */
						esc_html__( 'The VAT rate per order type is configured under %s.', 'libre-bite' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=lbite-settings&tab=prices_taxes' ) ) . '">' . esc_html__( 'Prices & Taxes', 'libre-bite' ) . '</a>'
					);
					?>
				</p>
				<?php endif; ?>
			</td>
		</tr>
		<?php endif; ?>
	</table>
	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
