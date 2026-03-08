<?php
/**
 * Tischplan – Admin-Seite
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_locations = get_posts(
	array(
		'post_type'      => 'lbite_location',
		'posts_per_page' => 100,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	)
);

// Letzten gewählten Standort aus User-Meta laden
$lbite_selected_location = intval( get_user_meta( get_current_user_id(), '_lbite_floor_plan_location', true ) );
?>
<div class="wrap lbite-admin-wrap">
	<h1><?php esc_html_e( 'Tischplan', 'libre-bite' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Ziehe die Tisch-Kacheln, um ihre Anzeigereihenfolge pro Standort festzulegen.', 'libre-bite' ); ?>
	</p>

	<div class="lbite-floor-plan-toolbar">
		<label for="lbite-floor-plan-location"><?php esc_html_e( 'Standort:', 'libre-bite' ); ?></label>
		<select id="lbite-floor-plan-location">
			<option value=""><?php esc_html_e( '— Standort wählen —', 'libre-bite' ); ?></option>
			<?php foreach ( $lbite_locations as $lbite_location ) : ?>
				<option value="<?php echo esc_attr( $lbite_location->ID ); ?>"
					<?php selected( $lbite_selected_location, $lbite_location->ID ); ?>>
					<?php echo esc_html( $lbite_location->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<button type="button" id="lbite-floor-plan-save" class="button button-primary" disabled>
			<?php esc_html_e( 'Reihenfolge speichern', 'libre-bite' ); ?>
		</button>
		<span id="lbite-floor-plan-status"></span>
	</div>

	<div id="lbite-floor-plan-empty" class="lbite-floor-plan-empty" style="display:none;">
		<p><?php esc_html_e( 'Keine Tische für diesen Standort vorhanden.', 'libre-bite' ); ?></p>
		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=lbite_table' ) ); ?>" class="button">
			<?php esc_html_e( 'Tisch erstellen', 'libre-bite' ); ?>
		</a>
	</div>

	<div id="lbite-floor-plan-grid" class="lbite-floor-plan-grid" style="display:none;">
		<!-- Tisch-Kacheln werden per AJAX geladen -->
	</div>

	<p id="lbite-floor-plan-hint" class="description" style="display:none;">
		<?php esc_html_e( 'Die Reihenfolge gilt pro Standort und wird im POS sowie in der Bestellübersicht verwendet.', 'libre-bite' ); ?>
	</p>
</div>
