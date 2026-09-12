<?php
/**
 * Einstellungen: Bon-Vorlagen
 *
 * Matrix aus Feldern (Zeilen) und Bontypen (Spalten). Bewusst eine Tabelle
 * statt drei getrennter Blöcke: der eigentliche Punkt ist der Vergleich –
 * welche Information erscheint auf welchem Beleg.
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

<h3><?php esc_html_e( 'Receipt Templates', 'libre-bite' ); ?></h3>
<p class="description" style="margin-bottom: 16px;">
	<?php esc_html_e( 'Choose which information appears on which printed document. The kitchen needs items, notes and allergens but no prices; the guest needs prices but no preparation notes; the driver needs the address and phone number.', 'libre-bite' ); ?>
</p>

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
