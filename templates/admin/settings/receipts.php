<?php
/**
 * Einstellungen: Bon-Vorlagen
 *
 * Matrix aus Feldern (Zeilen) und Bontypen (Spalten). Bewusst eine Tabelle
 * statt drei getrennter Blöcke: der eigentliche Punkt ist der Vergleich –
 * welche Information erscheint auf welchem Beleg. Seit der weiteren
 * Aufteilung nach v3.2.0 eine eigenständige Seite mit eigenem Formular
 * (vorher Fragment innerhalb der Orders-Seite).
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LBite_Receipts' ) ) {
	return;
}

$lbite_types  = LBite_Receipts::get_types();
$lbite_defs   = LBite_Receipts::get_field_definitions();
$lbite_active = array();

foreach ( array_keys( $lbite_types ) as $lbite_type ) {
	$lbite_active[ $lbite_type ] = LBite_Receipts::get_fields( $lbite_type );
}
?>

<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Choose which information appears on which printed document. The kitchen needs items, notes and allergens but no prices; the guest needs prices but no preparation notes; the driver needs the address and phone number.', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="orders_receipts">

<div class="lbite-preview-box lbite-receipt-preview-wrap" aria-hidden="true">
	<p class="lbite-preview-box__label"><?php esc_html_e( 'Preview: what each receipt will show', 'libre-bite' ); ?></p>
	<div class="lbite-receipt-preview__cols">
		<?php foreach ( array_keys( $lbite_types ) as $lbite_type ) : ?>
			<div class="lbite-receipt-preview__col">
				<strong><?php echo esc_html( $lbite_types[ $lbite_type ] ); ?></strong>
				<ul data-receipt-type="<?php echo esc_attr( $lbite_type ); ?>">
					<?php foreach ( $lbite_defs as $lbite_key => $lbite_def ) : ?>
						<li data-field="<?php echo esc_attr( $lbite_key ); ?>" <?php echo empty( $lbite_active[ $lbite_type ][ $lbite_key ] ) ? 'hidden' : ''; ?>>
							<?php echo esc_html( $lbite_def['label'] ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<div class="lbite-table-scroll">
	<table class="lbite-table widefat">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Field', 'libre-bite' ); ?></th>
				<?php foreach ( $lbite_types as $lbite_label ) : ?>
					<th style="text-align:center;"><?php echo esc_html( $lbite_label ); ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $lbite_defs as $lbite_key => $lbite_def ) : ?>
				<tr>
					<td><?php echo esc_html( $lbite_def['label'] ); ?></td>
					<?php foreach ( array_keys( $lbite_types ) as $lbite_type ) : ?>
						<td style="text-align:center;">
							<input
								type="checkbox"
								class="lbite-receipt-field-toggle"
								data-receipt-type="<?php echo esc_attr( $lbite_type ); ?>"
								data-field="<?php echo esc_attr( $lbite_key ); ?>"
								name="lbite_receipt_fields[<?php echo esc_attr( $lbite_type ); ?>][<?php echo esc_attr( $lbite_key ); ?>]"
								value="1"
								<?php checked( ! empty( $lbite_active[ $lbite_type ][ $lbite_key ] ) ); ?>
							>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<script>
(function() {
	document.querySelectorAll('.lbite-receipt-field-toggle').forEach(function(box) {
		box.addEventListener('change', function() {
			var li = document.querySelector('[data-receipt-type="' + this.dataset.receiptType + '"] [data-field="' + this.dataset.field + '"]');
			if ( li ) { li.hidden = ! this.checked; }
		});
	});
})();
</script>

<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
