<?php
/**
 * Template: Checkout-Felder-Verwaltung
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	echo '<div class="notice notice-success"><p>' . esc_html__( 'Einstellungen gespeichert.', 'libre-bite' ) . '</p></div>';
}

// Aktuelle Einstellungen laden
$saved_fields = get_option( 'lbite_checkout_fields', array() );

// Standard WooCommerce Felder
$default_fields = array(
	'billing'  => array(
		'first_name' => __( 'Vorname', 'libre-bite' ),
		'last_name'  => __( 'Nachname', 'libre-bite' ),
		'company'    => __( 'Firma', 'libre-bite' ),
		'address_1'  => __( 'Straße und Hausnummer', 'libre-bite' ),
		'address_2'  => __( 'Adresszusatz', 'libre-bite' ),
		'city'       => __( 'Ort', 'libre-bite' ),
		'postcode'   => __( 'Postleitzahl', 'libre-bite' ),
		'country'    => __( 'Land', 'libre-bite' ),
		'state'      => __( 'Bundesland', 'libre-bite' ),
		'email'      => __( 'E-Mail', 'libre-bite' ),
		'phone'      => __( 'Telefon', 'libre-bite' ),
	),
	'shipping' => array(
		'first_name' => __( 'Vorname', 'libre-bite' ),
		'last_name'  => __( 'Nachname', 'libre-bite' ),
		'company'    => __( 'Firma', 'libre-bite' ),
		'address_1'  => __( 'Straße und Hausnummer', 'libre-bite' ),
		'address_2'  => __( 'Adresszusatz', 'libre-bite' ),
		'city'       => __( 'Ort', 'libre-bite' ),
		'postcode'   => __( 'Postleitzahl', 'libre-bite' ),
		'country'    => __( 'Land', 'libre-bite' ),
		'state'      => __( 'Bundesland', 'libre-bite' ),
	),
);
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Checkout-Felder verwalten', 'libre-bite' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'Hier können Sie festlegen, welche Felder im Checkout angezeigt werden sollen und die Feldbezeichnungen anpassen.', 'libre-bite' ); ?>
	</p>

	<form method="post" action="">
		<?php wp_nonce_field( 'lbite_checkout_fields_save' ); ?>

		<!-- Allgemeine Einstellungen -->
		<div class="postbox" style="margin-top: 20px;">
			<h2 class="hndle" style="padding: 15px;">
				<?php esc_html_e( 'Allgemeine Einstellungen', 'libre-bite' ); ?>
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
								<strong><?php esc_html_e( 'Option "Lieferung an eine andere Adresse" anzeigen', 'libre-bite' ); ?></strong>
							</label>
							<p class="description" style="margin-left: 30px; margin-top: 5px;">
								<?php esc_html_e( 'Wenn deaktiviert, wird die Checkbox und die Lieferadress-Felder im Checkout nicht angezeigt.', 'libre-bite' ); ?>
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
								<strong><?php esc_html_e( 'Versand-Informationen anzeigen', 'libre-bite' ); ?></strong>
							</label>
							<p class="description" style="margin-left: 30px; margin-top: 5px;">
								<?php esc_html_e( 'Wenn aktiviert, werden Versand-Informationen in Warenkorb und Checkout angezeigt (Versandkosten, Versandmethoden, etc.).', 'libre-bite' ); ?>
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
								<strong><?php esc_html_e( 'Trinkgeld-Auswahl im Checkout anzeigen', 'libre-bite' ); ?></strong>
							</label>
							<p class="description" style="margin-left: 30px; margin-top: 5px;">
								<?php esc_html_e( 'Wenn deaktiviert, wird die Trinkgeld-Auswahl im Checkout nicht angezeigt.', 'libre-bite' ); ?>
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
								<strong><?php esc_html_e( 'Feld "Anmerkungen zur Bestellung" anzeigen', 'libre-bite' ); ?></strong>
							</label>
							<p class="description" style="margin-left: 30px; margin-top: 5px;">
								<?php esc_html_e( 'Wenn deaktiviert, wird das Anmerkungen-Feld im Checkout nicht angezeigt.', 'libre-bite' ); ?>
							</p>
							<div style="margin-top: 10px; margin-left: 30px;">
								<label style="display: block; margin-bottom: 5px;">
									<strong><?php esc_html_e( 'Label:', 'libre-bite' ); ?></strong>
								</label>
								<input type="text"
									name="lbite_order_comments_label"
									value="<?php echo esc_attr( isset( $saved_fields['_order_comments_label'] ) ? $saved_fields['_order_comments_label'] : '' ); ?>"
									placeholder="<?php esc_attr_e( 'Anmerkungen zur Bestellung', 'libre-bite' ); ?>"
									class="regular-text"
									style="width: 100%; max-width: 400px;">
								<p class="description">
									<?php esc_html_e( 'Eigene Feldbezeichnung (optional)', 'libre-bite' ); ?>
								</p>
							</div>
							<div style="margin-top: 10px; margin-left: 30px;">
								<label style="display: block; margin-bottom: 5px;">
									<strong><?php esc_html_e( 'Platzhalter:', 'libre-bite' ); ?></strong>
								</label>
								<input type="text"
									name="lbite_order_comments_placeholder"
									value="<?php echo esc_attr( isset( $saved_fields['_order_comments_placeholder'] ) ? $saved_fields['_order_comments_placeholder'] : '' ); ?>"
									placeholder="<?php esc_attr_e( 'Hinweise zu Ihrer Bestellung, z.B. besondere Hinweise für die Lieferung.', 'libre-bite' ); ?>"
									class="regular-text"
									style="width: 100%; max-width: 400px;">
								<p class="description">
									<?php esc_html_e( 'Eigener Platzhalter-Text (optional)', 'libre-bite' ); ?>
								</p>
							</div>
						</td>
					</tr>
					<tr>
						<td style="padding: 10px; border-top: 1px solid #ddd;">
							<label style="display: block; margin-bottom: 5px;">
								<strong><?php esc_html_e( 'Titel für Rechnungsdetails überschreiben', 'libre-bite' ); ?></strong>
							</label>
							<input type="text"
								name="lbite_billing_details_title"
								value="<?php echo esc_attr( isset( $saved_fields['_billing_details_title'] ) ? $saved_fields['_billing_details_title'] : '' ); ?>"
								placeholder="<?php esc_attr_e( 'Rechnungsdetails', 'libre-bite' ); ?>"
								class="regular-text"
								style="width: 100%; max-width: 400px;">
							<p class="description">
								<?php esc_html_e( 'Eigener Titel für den Abschnitt "Rechnungsdetails" im Checkout (optional)', 'libre-bite' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
			<!-- Rechnungsadresse -->
			<div class="postbox">
				<h2 class="hndle" style="padding: 15px;">
					<?php esc_html_e( 'Rechnungsadresse', 'libre-bite' ); ?>
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
											<?php esc_html_e( 'Eigene Bezeichnung (optional)', 'libre-bite' ); ?>
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
					<?php esc_html_e( 'Lieferadresse', 'libre-bite' ); ?>
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
											<?php esc_html_e( 'Eigene Bezeichnung (optional)', 'libre-bite' ); ?>
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
				<?php esc_html_e( 'Einstellungen speichern', 'libre-bite' ); ?>
			</button>
		</p>
	</form>
</div>

