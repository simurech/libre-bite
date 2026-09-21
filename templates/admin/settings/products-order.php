<?php
/**
 * Einstellungen: Produktreihenfolge
 *
 * Ausgelagert aus products.php (weitere Aufteilung nach v3.2.0). Reine
 * AJAX-Drag&Drop-Seite (lbite_save_pos_product_order), kein <form>, keine
 * Beteiligung am zentralen Save-Switch.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_pos_order_products = get_posts( array(
	'post_type'      => 'product',
	'posts_per_page' => 500,
	'post_status'    => 'publish',
	'orderby'        => array(
		'menu_order' => 'ASC',
		'title'      => 'ASC',
	),
) );
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Drag products into the desired order. This order applies to the POS and to the store\'s catalog (when WooCommerce uses custom ordering).', 'libre-bite' ); ?>
</p>

<ul id="lbite-pos-product-order" style="max-width: 600px; margin: 0; padding: 0; list-style: none;">
	<?php foreach ( $lbite_pos_order_products as $lbite_pos_order_product ) : ?>
	<li data-id="<?php echo esc_attr( $lbite_pos_order_product->ID ); ?>" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; margin-bottom: 4px; background: var(--lbite-surface, #fff); border: 1px solid #ddd; border-radius: 4px; cursor: grab;">
		<span class="dashicons dashicons-menu" style="color: #aaa; flex-shrink: 0;"></span>
		<span><?php echo esc_html( $lbite_pos_order_product->post_title ); ?></span>
	</li>
	<?php endforeach; ?>
</ul>

<p style="margin-top: 12px;">
	<button type="button" id="lbite-save-pos-product-order" class="button button-primary">
		<?php esc_html_e( 'Save Order', 'libre-bite' ); ?>
	</button>
	<span id="lbite-pos-product-order-status" style="margin-left: 10px; color: #3c763d;"></span>
</p>
