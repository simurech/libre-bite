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
		<?php esc_html_e( 'Ziehe Tische auf die gewünschte Position. Klicke auf einen Tisch, um die aktuelle Bestellung zu sehen.', 'libre-bite' ); ?>
	</p>

	<div class="lbite-fp-toolbar">
		<div class="lbite-fp-toolbar-left">
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
		</div>

		<div class="lbite-fp-toolbar-right">
			<button type="button" id="lbite-fp-refresh" class="button" title="<?php esc_attr_e( 'Status aktualisieren', 'libre-bite' ); ?>">&#8635;</button>
			<button type="button" id="lbite-fp-save" class="button button-primary" disabled>
				<?php esc_html_e( 'Positionen speichern', 'libre-bite' ); ?>
			</button>
			<span id="lbite-fp-status"></span>
		</div>
	</div>

	<div id="lbite-fp-empty" style="display:none;">
		<p><?php esc_html_e( 'Keine Tische für diesen Standort vorhanden.', 'libre-bite' ); ?></p>
		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=lbite_table' ) ); ?>" class="button">
			<?php esc_html_e( 'Tisch erstellen', 'libre-bite' ); ?>
		</a>
	</div>

	<div id="lbite-fp-canvas-wrap" class="lbite-fp-canvas-wrap" style="display:none;">
		<div id="lbite-fp-canvas" class="lbite-fp-canvas"></div>
	</div>

	<div id="lbite-fp-legend" class="lbite-fp-legend" style="display:none;">
		<span class="lbite-fp-legend-item lbite-fp-legend--free"><?php esc_html_e( 'Frei', 'libre-bite' ); ?></span>
		<span class="lbite-fp-legend-item lbite-fp-legend--occupied"><?php esc_html_e( 'Besetzt', 'libre-bite' ); ?></span>
		<span class="lbite-fp-legend-item lbite-fp-legend--preparing"><?php esc_html_e( 'In Zubereitung', 'libre-bite' ); ?></span>
		<span class="lbite-fp-legend-item lbite-fp-legend--ready"><?php esc_html_e( 'Bereit', 'libre-bite' ); ?></span>
		<span class="lbite-fp-legend-hint"><?php esc_html_e( 'Hover über Tisch → Form (◐) und Grösse (⊞) ändern · Auto-Refresh alle 30 Sek.', 'libre-bite' ); ?></span>
	</div>
</div>
