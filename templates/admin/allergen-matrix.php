<?php
/**
 * Druckbare Allergen-Matrix
 *
 * Zeilen sind Produkte, Spalten die 14 kennzeichnungspflichtigen Allergene
 * nach EU-Verordnung 1169/2011. Gedacht als Aushang beziehungsweise
 * Nachschlagewerk für das Personal — in der Schweiz und in der EU ein realer
 * Betriebsbedarf, der bisher nur produktweise einzeln abrufbar war.
 *
 * Das Druck-Stylesheet ist bewusst eingebettet und nicht ausgelagert: die
 * Seite soll ohne weitere Ladevorgänge druckbar sein, und die Regeln gelten
 * ausschliesslich hier.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LBite_Nutritional_Info' ) ) {
	return;
}

$lbite_allergens = LBite_Nutritional_Info::get_allergen_list();
$lbite_dietary   = LBite_Nutritional_Info::get_dietary_list();
$lbite_show_diet = lbite_feature_enabled( 'enable_dietary_filter' );

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reiner Anzeigefilter ohne Schreibzugriff.
$lbite_only_flagged = isset( $_GET['only_flagged'] ) && '1' === $_GET['only_flagged'];

$lbite_products = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 500,
		'orderby'        => 'title',
		'order'          => 'ASC',
	)
);

$lbite_rows = array();

foreach ( $lbite_products as $lbite_product ) {
	$lbite_set = get_post_meta( $lbite_product->ID, '_lbite_allergens', true );
	$lbite_set = is_array( $lbite_set ) ? $lbite_set : array();

	if ( $lbite_only_flagged && empty( $lbite_set ) ) {
		continue;
	}

	$lbite_rows[] = array(
		'id'        => $lbite_product->ID,
		'title'     => $lbite_product->post_title,
		'allergens' => $lbite_set,
		'dietary'   => LBite_Nutritional_Info::get_product_dietary( $lbite_product->ID ),
	);
}
?>
<div class="wrap lbite-allergen-matrix">

	<h1 class="lbite-no-print">
		<?php esc_html_e( 'Allergen Matrix', 'libre-bite' ); ?>
	</h1>

	<p class="description lbite-no-print">
		<?php esc_html_e( 'Overview of all published products and their declared allergens according to EU Regulation 1169/2011. Use the print button for a version suitable for putting up in the kitchen.', 'libre-bite' ); ?>
	</p>

	<p class="lbite-no-print">
		<button type="button" class="button button-primary" onclick="window.print();">
			<?php esc_html_e( 'Print', 'libre-bite' ); ?>
		</button>

		<?php if ( $lbite_only_flagged ) : ?>
			<a class="button" href="<?php echo esc_url( remove_query_arg( 'only_flagged' ) ); ?>">
				<?php esc_html_e( 'Show all products', 'libre-bite' ); ?>
			</a>
		<?php else : ?>
			<a class="button" href="<?php echo esc_url( add_query_arg( 'only_flagged', '1' ) ); ?>">
				<?php esc_html_e( 'Only products with allergens', 'libre-bite' ); ?>
			</a>
		<?php endif; ?>
	</p>

	<div class="lbite-print-header" aria-hidden="true">
		<strong><?php echo esc_html( get_bloginfo( 'name' ) ); ?></strong>
		&ndash; <?php esc_html_e( 'Allergen Matrix', 'libre-bite' ); ?>
		(<?php echo esc_html( wp_date( 'd.m.Y' ) ); ?>)
	</div>

	<?php if ( empty( $lbite_rows ) ) : ?>

		<p><?php esc_html_e( 'No products found.', 'libre-bite' ); ?></p>

	<?php else : ?>

		<div class="lbite-matrix-scroll">
			<table class="lbite-matrix-table">
				<thead>
					<tr>
						<th class="lbite-matrix-product"><?php esc_html_e( 'Product', 'libre-bite' ); ?></th>
						<?php foreach ( $lbite_allergens as $lbite_label ) : ?>
							<th class="lbite-matrix-allergen">
								<span><?php echo esc_html( $lbite_label ); ?></span>
							</th>
						<?php endforeach; ?>
						<?php if ( $lbite_show_diet ) : ?>
							<th class="lbite-matrix-diet"><?php esc_html_e( 'Dietary', 'libre-bite' ); ?></th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $lbite_rows as $lbite_row ) : ?>
						<tr>
							<td class="lbite-matrix-product">
								<a href="<?php echo esc_url( (string) get_edit_post_link( $lbite_row['id'] ) ); ?>">
									<?php echo esc_html( $lbite_row['title'] ); ?>
								</a>
							</td>
							<?php foreach ( array_keys( $lbite_allergens ) as $lbite_key ) : ?>
								<td class="lbite-matrix-cell<?php echo in_array( $lbite_key, $lbite_row['allergens'], true ) ? ' is-set' : ''; ?>">
									<?php echo in_array( $lbite_key, $lbite_row['allergens'], true ) ? '&#10003;' : ''; ?>
								</td>
							<?php endforeach; ?>
							<?php if ( $lbite_show_diet ) : ?>
								<td class="lbite-matrix-diet">
									<?php
									$lbite_names = array();
									foreach ( $lbite_row['dietary'] as $lbite_dkey ) {
										if ( isset( $lbite_dietary[ $lbite_dkey ] ) ) {
											$lbite_names[] = $lbite_dietary[ $lbite_dkey ];
										}
									}
									echo esc_html( implode( ', ', $lbite_names ) );
									?>
								</td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<p class="description lbite-no-print">
			<?php
			printf(
				/* translators: %d: number of products */
				esc_html__( '%d products listed. Products without any declared allergen show an empty row — that is not the same as “contains nothing”, it means nothing has been declared yet.', 'libre-bite' ),
				count( $lbite_rows )
			);
			?>
		</p>

	<?php endif; ?>
</div>

<style>
/* Bildschirm */
.lbite-allergen-matrix .lbite-print-header { display: none; }
.lbite-matrix-scroll { overflow-x: auto; background: var(--lbite-surface, #fff); border: 1px solid var(--lbite-border, #ddd); border-radius: var(--lbite-radius-md, 10px); }
.lbite-matrix-table { width: 100%; border-collapse: collapse; font-size: var(--lbite-text-base, 13px); }
.lbite-matrix-table th,
.lbite-matrix-table td { border-bottom: 1px solid var(--lbite-border-subtle, #eee); padding: 8px 6px; }
.lbite-matrix-table thead th { position: sticky; top: 32px; background: var(--lbite-surface-muted, #f6f7f7); z-index: 2; }
.lbite-matrix-product { text-align: left; min-width: 220px; font-weight: 600; }
.lbite-matrix-allergen { width: 34px; }
/* Spaltenköpfe gedreht: 14 Spalten passen sonst nicht auf eine Seite. */
.lbite-matrix-allergen span { display: inline-block; writing-mode: vertical-rl; transform: rotate(180deg); white-space: nowrap; font-weight: 600; }
.lbite-matrix-cell { text-align: center; color: var(--lbite-danger, #b32d2e); font-weight: 700; }
.lbite-matrix-cell.is-set { background: var(--lbite-danger-soft, #fdeceb); }
.lbite-matrix-diet { min-width: 150px; font-size: var(--lbite-text-sm, 12px); color: var(--lbite-text-muted, #646970); }

/* Druck */
@media print {
	/* Das dunkle Farbschema gilt am Bildschirm, nicht auf Papier. Ohne diese
	   Rueckstellung druckt der helle Text des dunklen Schemas nahezu
	   unsichtbar auf weissem Papier. */
	.lbite-allergen-matrix, .lbite-allergen-matrix * { color: #000 !important; background: transparent !important; }
	#adminmenumain, #wpadminbar, #wpfooter, .lbite-no-print, .notice { display: none !important; }
	#wpcontent, #wpbody-content { margin: 0 !important; padding: 0 !important; float: none !important; }
	.lbite-allergen-matrix .lbite-print-header { display: block; margin-bottom: 8mm; font-size: 12pt; }
	.lbite-matrix-scroll { overflow: visible; border: none; border-radius: 0; }
	.lbite-matrix-table { font-size: 8pt; }
	.lbite-matrix-table thead th { position: static; background: transparent; border-bottom: 1pt solid #000; }
	.lbite-matrix-table th, .lbite-matrix-table td { padding: 2mm 1mm; border-bottom: 0.2pt solid #999; }
	.lbite-matrix-cell.is-set { background: transparent; }
	.lbite-matrix-table tr { page-break-inside: avoid; }
	@page { size: A4 landscape; margin: 10mm; }
}
</style>
