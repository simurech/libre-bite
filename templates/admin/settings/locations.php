<?php
/**
 * Tab: Standorte
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_toggle_key         = 'enable_location_selector';
$lbite_toggle_label       = __( 'Location Selection', 'libre-bite' );
$lbite_toggle_description = __( 'Show a location selector in the frontend so customers can choose their pickup location.', 'libre-bite' );
$lbite_toggle_is_pro      = false;
include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
?>
<p class="description" style="margin-bottom: 16px;">
	<?php esc_html_e( 'Locations are managed as individual entries. Each location has its own opening hours, timeslots, and holidays.', 'libre-bite' ); ?>
</p>
<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lbite_location' ) ); ?>" class="button">
	<?php esc_html_e( 'Manage Locations', 'libre-bite' ); ?>
</a>
