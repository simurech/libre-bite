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

$lbite_premium_allowed = function_exists( 'lbite_freemius' ) && lbite_freemius()->can_use_premium_code__premium_only();
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
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Menu Page', 'libre-bite' ); ?></th>
			<td>
				<select name="lbite_menu_page_id">
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
	</table>
	<?php endif; ?>

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
