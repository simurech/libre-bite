<?php
/**
 * Template: Checkout-Felder-Verwaltung
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-Datei, wird innerhalb einer Klassen-Methode via include geladen; Variablen befinden sich im Methoden-Scope, nicht im globalen Namespace.

// Speichern.
if ( isset( $_POST['lbite_save_checkout_fields'] ) && check_admin_referer( 'lbite_checkout_fields_save' ) ) {
	// Standard-Feldliste definieren.
	$standard_fields = array(
		'billing'  => array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'postcode', 'country', 'state', 'email', 'phone' ),
		'shipping' => array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'postcode', 'country', 'state' ),
	);

	$checkout_fields = array();

	// Get posted checkout_fields array safely.
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in loop below.
	$posted_checkout_fields = isset( $_POST['checkout_fields'] ) && is_array( $_POST['checkout_fields'] ) ? wp_unslash( $_POST['checkout_fields'] ) : array();

	// Alle Standard-Felder durchlaufen.
	foreach ( $standard_fields as $fieldset => $field_ids ) {
		foreach ( $field_ids as $field_id ) {
			// Prüfen ob Checkbox angehakt ist.
			$enabled = isset( $posted_checkout_fields[ $fieldset ][ $field_id ]['enabled'] );

			// Label holen.
			$label = '';
			if ( isset( $posted_checkout_fields[ $fieldset ][ $field_id ]['label'] ) ) {
				$label = sanitize_text_field( $posted_checkout_fields[ $fieldset ][ $field_id ]['label'] );
			}

			$checkout_fields[ $fieldset ][ $field_id ] = array(
				'enabled' => $enabled,
				'label'   => $label,
			);
		}
	}

	// Allgemeine Optionen speichern.
	$checkout_fields['_enable_shipping_address']     = isset( $_POST['lbite_enable_shipping_address'] );
	$checkout_fields['_show_shipping_info']          = isset( $_POST['lbite_show_shipping_info'] );
	$checkout_fields['_enable_tip_selection']        = isset( $_POST['lbite_enable_tip_selection'] );
	$checkout_fields['_enable_order_comments']       = isset( $_POST['lbite_enable_order_comments'] );
	$checkout_fields['_order_comments_label']        = isset( $_POST['lbite_order_comments_label'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_order_comments_label'] ) ) : '';
	$checkout_fields['_order_comments_placeholder']  = isset( $_POST['lbite_order_comments_placeholder'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_order_comments_placeholder'] ) ) : '';
	$checkout_fields['_billing_details_title']       = isset( $_POST['lbite_billing_details_title'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_billing_details_title'] ) ) : '';

	update_option( 'lbite_checkout_fields', $checkout_fields );
	echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'libre-bite' ) . '</p></div>';
}

// Aktuelle Einstellungen laden
$saved_fields = get_option( 'lbite_checkout_fields', array() );

// Standard WooCommerce Felder
$default_fields = array(
	'billing'  => array(
		'first_name' => __( 'First Name', 'libre-bite' ),
		'last_name'  => __( 'Last Name', 'libre-bite' ),
		'company'    => __( 'Company', 'libre-bite' ),
		'address_1'  => __( 'Street and Number', 'libre-bite' ),
		'address_2'  => __( 'Address Line 2', 'libre-bite' ),
		'city'       => __( 'City', 'libre-bite' ),
		'postcode'   => __( 'Postal Code', 'libre-bite' ),
		'country'    => __( 'Country', 'libre-bite' ),
		'state'      => __( 'State', 'libre-bite' ),
		'email'      => __( 'Email', 'libre-bite' ),
		'phone'      => __( 'Phone', 'libre-bite' ),
	),
	'shipping' => array(
		'first_name' => __( 'First Name', 'libre-bite' ),
		'last_name'  => __( 'Last Name', 'libre-bite' ),
		'company'    => __( 'Company', 'libre-bite' ),
		'address_1'  => __( 'Street and Number', 'libre-bite' ),
		'address_2'  => __( 'Address Line 2', 'libre-bite' ),
		'city'       => __( 'City', 'libre-bite' ),
		'postcode'   => __( 'Postal Code', 'libre-bite' ),
		'country'    => __( 'Country', 'libre-bite' ),
		'state'      => __( 'State', 'libre-bite' ),
	),
);
?>

<?php if ( empty( $lbite_is_tab ) ) : ?>
<div class="wrap">
	<h1><?php esc_html_e( 'Manage Checkout Fields', 'libre-bite' ); ?></h1>
<?php endif; ?>

	<p class="description">
		<?php esc_html_e( 'Here you can specify which fields should be displayed in checkout and customize field labels.', 'libre-bite' ); ?>
	</p>

	<form method="post" action="">
		<?php wp_nonce_field( 'lbite_checkout_fields_save' ); ?>

		<!-- Allgemeine Einstellungen -->
		<div class="postbox" style="margin-top: 20px;">
			<h2 class="hndle" style="padding: 15px;">
				<?php esc_html_e( 'General Settings', 'libre-bite' ); ?>
			</h2>
			<div class="inside">
				<table class="form-table">
					<tr>
						<td style="padding: 10px;">
							<label style="display: flex; align-items: center; gap: 10px;">
								<input type="checkbox"
									name="lbite_enable_shipping_address"
									value="1"
									<?php checked( isset( $saved_fields['_enable_shipping_address'] ) ? $saved_fields['_enable_shipping_address'] : false ); ?>>
								<strong><?php esc_html_e( 'Show "Ship to a different address" option', 'libre-bite' ); ?></strong>
							</label>
							<p class="description" style="margin-left: 30px; margin-top: 5px;">
								<?php esc_html_e( 'If disabled, the checkbox and shipping address fields will not be shown in checkout.', 'libre-bite' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<td style="padding: 10px;">
							<label style="display: flex; align-items: center; gap: 10px;">
								<input type="checkbox"
									name="lbite_show_shipping_info"
									value="1"
									<?php checked( isset( $saved_fields['_show_shipping_info'] ) ? $saved_fields['_show_shipping_info'] : false ); ?>>
								<strong><?php esc_html_e( 'Show Shipping Information', 'libre-bite' ); ?></strong>
							</label>
							<p class="description" style="margin-left: 30px; margin-top: 5px;">
								<?php esc_html_e( 'If enabled, shipping information will be displayed in cart and checkout (shipping costs, shipping methods, etc.).', 'libre-bite' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<td style="padding: 10px;">
							<label style="display: flex; align-items: center; gap: 10px;">
								<input type="checkbox"
									name="lbite_enable_tip_selection"
									value="1"
									<?php checked( isset( $saved_fields['_enable_tip_selection'] ) ? $saved_fields['_enable_tip_selection'] : true ); ?>>
								<strong><?php esc_html_e( 'Show Tip Selection in Checkout', 'libre-bite' ); ?></strong>
							</label>
							<p class="description" style="margin-left: 30px; margin-top: 5px;">
								<?php esc_html_e( 'If disabled, the tip selection will not be shown in checkout.', 'libre-bite' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<td style="padding: 10px;">
							<label style="display: flex; align-items: center; gap: 10px;">
								<input type="checkbox"
									name="lbite_enable_order_comments"
									value="1"
									<?php checked( isset( $saved_fields['_enable_order_comments'] ) ? $saved_fields['_enable_order_comments'] : true ); ?>>
								<strong><?php esc_html_e( 'Show "Order Notes" field', 'libre-bite' ); ?></strong>
							</label>
							<p class="description" style="margin-left: 30px; margin-top: 5px;">
								<?php esc_html_e( 'If disabled, the notes field will not be shown in checkout.', 'libre-bite' ); ?>
							</p>
							<div style="margin-top: 10px; margin-left: 30px;">
								<label style="display: block; margin-bottom: 5px;">
									<strong><?php esc_html_e( 'Label:', 'libre-bite' ); ?></strong>
								</label>
								<input type="text"
									name="lbite_order_comments_label"
									value="<?php echo esc_attr( isset( $saved_fields['_order_comments_label'] ) ? $saved_fields['_order_comments_label'] : '' ); ?>"
									placeholder="<?php esc_attr_e( 'Order Notes', 'libre-bite' ); ?>"
									class="regular-text"
									style="width: 100%; max-width: 400px;">
								<p class="description">
									<?php esc_html_e( 'Custom field label (optional)', 'libre-bite' ); ?>
								</p>
							</div>
							<div style="margin-top: 10px; margin-left: 30px;">
								<label style="display: block; margin-bottom: 5px;">
									<strong><?php esc_html_e( 'Placeholder:', 'libre-bite' ); ?></strong>
								</label>
								<input type="text"
									name="lbite_order_comments_placeholder"
									value="<?php echo esc_attr( isset( $saved_fields['_order_comments_placeholder'] ) ? $saved_fields['_order_comments_placeholder'] : '' ); ?>"
									placeholder="<?php esc_attr_e( 'Notes about your order, e.g. special delivery instructions.', 'libre-bite' ); ?>"
									class="regular-text"
									style="width: 100%; max-width: 400px;">
								<p class="description">
									<?php esc_html_e( 'Custom placeholder text (optional)', 'libre-bite' ); ?>
								</p>
							</div>
						</td>
					</tr>
					<tr>
						<td style="padding: 10px; border-top: 1px solid #ddd;">
							<label style="display: block; margin-bottom: 5px;">
								<strong><?php esc_html_e( 'Override Billing Details Title', 'libre-bite' ); ?></strong>
							</label>
							<input type="text"
								name="lbite_billing_details_title"
								value="<?php echo esc_attr( isset( $saved_fields['_billing_details_title'] ) ? $saved_fields['_billing_details_title'] : '' ); ?>"
								placeholder="<?php esc_attr_e( 'Billing Details', 'libre-bite' ); ?>"
								class="regular-text"
								style="width: 100%; max-width: 400px;">
							<p class="description">
								<?php esc_html_e( 'Custom title for the "Billing Details" section in checkout (optional)', 'libre-bite' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<p class="description" style="margin-top: 16px;">
			<span class="dashicons dashicons-info" style="vertical-align: middle;"></span>
			<?php esc_html_e( 'Checked = field is shown in checkout. Unchecked = field is hidden. The label can optionally be overridden.', 'libre-bite' ); ?>
		</p>

		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
			<!-- Rechnungsadresse -->
			<div class="postbox">
				<h2 class="hndle" style="padding: 15px;">
					<?php esc_html_e( 'Billing Address', 'libre-bite' ); ?>
				</h2>
				<div class="inside">
					<table class="form-table">
						<?php foreach ( $default_fields['billing'] as $field_id => $field_label ) : ?>
							<?php
							$enabled      = ! isset( $saved_fields['billing'][ $field_id ]['enabled'] ) || $saved_fields['billing'][ $field_id ]['enabled'];
							$custom_label = isset( $saved_fields['billing'][ $field_id ]['label'] ) ? $saved_fields['billing'][ $field_id ]['label'] : '';
							?>
							<tr>
								<td style="padding: 10px;">
									<label style="display: flex; align-items: center; gap: 10px;">
										<input type="checkbox"
											name="checkout_fields[billing][<?php echo esc_attr( $field_id ); ?>][enabled]"
											value="1"
											<?php checked( $enabled ); ?>>
										<strong><?php echo esc_html( $field_label ); ?></strong>
									</label>
									<div style="margin-top: 5px; margin-left: 30px;">
										<input type="text"
											name="checkout_fields[billing][<?php echo esc_attr( $field_id ); ?>][label]"
											value="<?php echo esc_attr( $custom_label ); ?>"
											placeholder="<?php echo esc_attr( $field_label ); ?>"
											class="regular-text"
											style="width: 100%;">
										<p class="description">
											<?php esc_html_e( 'Custom label (optional)', 'libre-bite' ); ?>
										</p>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>
			</div>

			<!-- Lieferadresse -->
			<div class="postbox">
				<h2 class="hndle" style="padding: 15px;">
					<?php esc_html_e( 'Shipping Address', 'libre-bite' ); ?>
				</h2>
				<div class="inside">
					<table class="form-table">
						<?php foreach ( $default_fields['shipping'] as $field_id => $field_label ) : ?>
							<?php
							$enabled      = ! isset( $saved_fields['shipping'][ $field_id ]['enabled'] ) || $saved_fields['shipping'][ $field_id ]['enabled'];
							$custom_label = isset( $saved_fields['shipping'][ $field_id ]['label'] ) ? $saved_fields['shipping'][ $field_id ]['label'] : '';
							?>
							<tr>
								<td style="padding: 10px;">
									<label style="display: flex; align-items: center; gap: 10px;">
										<input type="checkbox"
											name="checkout_fields[shipping][<?php echo esc_attr( $field_id ); ?>][enabled]"
											value="1"
											<?php checked( $enabled ); ?>>
										<strong><?php echo esc_html( $field_label ); ?></strong>
									</label>
									<div style="margin-top: 5px; margin-left: 30px;">
										<input type="text"
											name="checkout_fields[shipping][<?php echo esc_attr( $field_id ); ?>][label]"
											value="<?php echo esc_attr( $custom_label ); ?>"
											placeholder="<?php echo esc_attr( $field_label ); ?>"
											class="regular-text"
											style="width: 100%;">
										<p class="description">
											<?php esc_html_e( 'Custom label (optional)', 'libre-bite' ); ?>
										</p>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>
			</div>
		</div>

		<p class="submit">
			<button type="submit" name="lbite_save_checkout_fields" class="button button-primary button-large">
				<?php esc_html_e( 'Save Settings', 'libre-bite' ); ?>
			</button>
		</p>
	</form>
<?php if ( empty( $lbite_is_tab ) ) : ?>
</div>
<?php endif; ?>

