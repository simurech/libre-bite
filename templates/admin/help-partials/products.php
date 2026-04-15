<?php
/**
 * Hilfe-Partial: Produkte
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lbite-help-section">
	<h2><?php esc_html_e( 'Products with Libre Bite', 'libre-bite' ); ?></h2>
	<p><?php esc_html_e( 'Libre Bite builds directly on your WooCommerce products. You do not need to create new products – you simply extend the existing ones with food service-specific features like product options (add-ons).', 'libre-bite' ); ?></p>

	<!-- WooCommerce-Produkte -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'WooCommerce Products as the Foundation', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'All products you manage in WooCommerce are automatically available in Libre Bite – in the online shop, in the POS system, and at checkout.', 'libre-bite' ); ?></p>
		<ul>
			<li><strong><?php esc_html_e( 'Simple Products:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'One product, one price – e.g. "Hamburger CHF 14.50".', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Variable Products:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Products with variants – e.g. "Pizza" in sizes S, M, L with different prices.', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Categories:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Categories are shown as filter buttons in the POS (e.g. "Burgers", "Drinks", "Desserts").', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Product Images:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Product images appear in the POS system – ideal for quickly finding the right items.', 'libre-bite' ); ?></li>
		</ul>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" class="button">
			<?php esc_html_e( 'Go to Products', 'libre-bite' ); ?>
		</a>
	</div>

	<!-- Produkt-Optionen (Add-ons) -->
	<?php if ( lbite_feature_enabled( 'enable_product_options' ) ) : ?>
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Product Options (Add-ons)', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Product options are additional choices customers can make when ordering – e.g. "Extra Cheese", "Sauce", "no onions". They appear directly on the product in the shop and are transmitted with the order.', 'libre-bite' ); ?></p>

		<h4><?php esc_html_e( 'Step 1: Create Product Option', 'libre-bite' ); ?></h4>
		<ol>
			<li><?php esc_html_e( 'Go to "Libre Bite" → "Product Options"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Click on "Create"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Enter a name, e.g. "Extra Cheese"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Optionally enter a surcharge, e.g. "+0.50"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Publish the option', 'libre-bite' ); ?></li>
		</ol>

		<h4><?php esc_html_e( 'Step 2: Assign Option to a Product', 'libre-bite' ); ?></h4>
		<ol>
			<li><?php esc_html_e( 'Open the desired product in WooCommerce', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Scroll to the section "Libre Bite Product Options"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Check the options that should be offered for this product', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Save the product', 'libre-bite' ); ?></li>
		</ol>

		<div class="lbite-help-tip">
			<span class="dashicons dashicons-lightbulb"></span>
			<p><?php esc_html_e( 'Tip: You can assign the same option to multiple products. If you want to offer "Extra Cheese" for all burger products, create the option once and then assign it to each burger.', 'libre-bite' ); ?></p>
		</div>

		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lbite_product_option' ) ); ?>" class="button">
			<?php esc_html_e( 'Go to Product Options', 'libre-bite' ); ?>
		</a>
	</div>
	<?php endif; ?>

	<!-- Tische -->
	<?php if ( lbite_feature_enabled( 'enable_table_ordering' ) ) : ?>
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Table Orders', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'QR-code-based table ordering: guests scan and order directly – without address or pickup time fields.', 'libre-bite' ); ?></p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=tables' ) ); ?>" class="button">
			<?php esc_html_e( 'Show Table Help', 'libre-bite' ); ?>
		</a>
	</div>
	<?php endif; ?>
</div>
