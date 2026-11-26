<?php
/**
 * Einstellungen: Menü-Darstellung
 *
 * Ausgelagert aus products.php (weitere Aufteilung nach v3.2.0).
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_premium_allowed = lbite_has_pro_license();
$lbite_menu_page_id    = get_option( 'lbite_menu_page_id', 0 );
$lbite_all_pages       = get_pages( array( 'post_status' => 'publish' ) );
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'A theme-independent menu layout and time-limited availability for products or whole categories.', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="products_menu">

	<?php
	$lbite_toggle_key             = 'enable_menu_view';
	$lbite_toggle_label           = __( 'Theme-independent Menu View', 'libre-bite' );
	$lbite_toggle_description     = __( 'Outputs your menu in its own layout — category navigation, product cards, a dialog for variations and add-ons, and a slide-in order panel. Add layout="list" to the shortcode for a compact list instead of cards.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<p class="description">
		<?php
		printf(
			/* translators: %s: shortcode */
			esc_html__( 'Add the menu to any page with the shortcode %s.', 'libre-bite' ),
			'<code>[lbite_menu]</code>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup, not user input.
		);
		?>
	</p>

	<?php if ( lbite_feature_enabled( 'enable_menu_view' ) ) : ?>
	<?php $lbite_menu_display = class_exists( 'LBite_Menu_View' ) ? LBite_Menu_View::get_display_settings() : array( 'layout' => 'grid', 'columns' => 'auto', 'image_ratio' => 'square', 'description' => true, 'cart' => true ); ?>
	<table class="form-table">
		<tr>
			<th><label for="lbite-menu-layout"><?php esc_html_e( 'Layout', 'libre-bite' ); ?></label></th>
			<td>
				<select id="lbite-menu-layout" name="lbite_menu_layout">
					<option value="grid" <?php selected( $lbite_menu_display['layout'], 'grid' ); ?>><?php esc_html_e( 'Cards (grid)', 'libre-bite' ); ?></option>
					<option value="list" <?php selected( $lbite_menu_display['layout'], 'list' ); ?>><?php esc_html_e( 'Compact list', 'libre-bite' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="lbite-menu-columns"><?php esc_html_e( 'Columns', 'libre-bite' ); ?></label></th>
			<td>
				<select id="lbite-menu-columns" name="lbite_menu_columns">
					<option value="auto" <?php selected( $lbite_menu_display['columns'], 'auto' ); ?>><?php esc_html_e( 'Automatic (as many as fit)', 'libre-bite' ); ?></option>
					<?php foreach ( array( '2', '3', '4' ) as $lbite_cols ) : ?>
						<option value="<?php echo esc_attr( $lbite_cols ); ?>" <?php selected( $lbite_menu_display['columns'], $lbite_cols ); ?>><?php echo esc_html( $lbite_cols ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Applies to the card layout on large screens. Tablets show at most two columns, phones one.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="lbite-menu-image-ratio"><?php esc_html_e( 'Image Format', 'libre-bite' ); ?></label></th>
			<td>
				<select id="lbite-menu-image-ratio" name="lbite_menu_image_ratio">
					<option value="square" <?php selected( $lbite_menu_display['image_ratio'], 'square' ); ?>><?php esc_html_e( 'Square', 'libre-bite' ); ?></option>
					<option value="landscape" <?php selected( $lbite_menu_display['image_ratio'], 'landscape' ); ?>><?php esc_html_e( 'Landscape (4:3)', 'libre-bite' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Descriptions', 'libre-bite' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="lbite_menu_show_description" value="1" <?php checked( $lbite_menu_display['description'] ); ?>>
					<?php esc_html_e( 'Show the short description of each product.', 'libre-bite' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Order Panel', 'libre-bite' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="lbite_menu_show_cart" value="1" <?php checked( $lbite_menu_display['cart'] ); ?>>
					<?php esc_html_e( 'Show the order panel with the cart next to the menu.', 'libre-bite' ); ?>
				</label>
				<p class="description">
					<?php
					printf(
						/* translators: %s: shortcode example */
						esc_html__( 'A single page can override these settings in its shortcode, for example %s.', 'libre-bite' ),
						'<code>[lbite_menu layout="list" columns="3" cart="no"]</code>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup, not user input.
					);
					?>
				</p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Product Images', 'libre-bite' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="lbite_menu_show_images" value="1"
						<?php checked( get_option( 'lbite_menu_show_images', 1 ), 1 ); ?>>
					<?php esc_html_e( 'Show product images in the menu grid.', 'libre-bite' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Turn off for a text-only menu without photos.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="lbite-menu-page-id"><?php esc_html_e( 'Menu Page', 'libre-bite' ); ?></label></th>
			<td>
				<select id="lbite-menu-page-id" name="lbite_menu_page_id">
					<option value="0"><?php esc_html_e( '-- Please select --', 'libre-bite' ); ?></option>
					<option value="create_new"><?php esc_html_e( '+ Create New Page', 'libre-bite' ); ?></option>
					<?php foreach ( $lbite_all_pages as $lbite_page ) : ?>
						<option value="<?php echo esc_attr( $lbite_page->ID ); ?>" <?php selected( $lbite_menu_page_id, $lbite_page->ID ); ?>>
							<?php echo esc_html( $lbite_page->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?php esc_html_e( 'Select the page where the shortcode is included, or create a new page. Turning the toggle above on has no visible effect until the shortcode is placed somewhere.', 'libre-bite' ); ?>
					<?php if ( $lbite_menu_page_id ) : ?>
						<br><a href="<?php echo esc_url( get_edit_post_link( $lbite_menu_page_id ) ); ?>" target="_blank"><?php esc_html_e( 'Edit Page', 'libre-bite' ); ?></a>
						|
						<a href="<?php echo esc_url( get_permalink( $lbite_menu_page_id ) ); ?>" target="_blank"><?php esc_html_e( 'View Page', 'libre-bite' ); ?></a>
					<?php endif; ?>
				</p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Theme Integration', 'libre-bite' ); ?></th>
			<td>
				<p class="description">
					<?php esc_html_e( 'The menu is a standalone layout and does not run WooCommerce loop hooks. Badges or icons your theme adds to products in the shop (for example tag labels or allergen icons) therefore do not appear here automatically. Developers can output them through the hooks lbite_menu_item_before_title and lbite_menu_item_after_desc, listed under Settings → Developer.', 'libre-bite' ); ?>
				</p>
			</td>
		</tr>
	</table>
	<?php endif; ?>

	<?php
	$lbite_toggle_key             = 'enable_menu_redirect_archives';
	$lbite_toggle_label           = __( 'Redirect Standard Archives to Menu', 'libre-bite' );
	$lbite_toggle_description     = __( 'Redirects the default WooCommerce shop, category and tag pages (301) to the menu page configured above, avoiding duplicate content between your theme\'s product grid and the menu.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<?php
	$lbite_toggle_key             = 'enable_menu_schedule';
	$lbite_toggle_label           = __( 'Scheduled Availability', 'libre-bite' );
	$lbite_toggle_description     = __( 'Limit products or whole categories to certain weekdays, times of day, or date ranges — a breakfast menu that disappears at 11:30, a seasonal item that only shows in December. Configured per product and per product category.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
