<?php
/**
 * Template: Bestellübersicht (Kanban Board)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap lbite-order-board">
	<div class="lbite-board-header">
		<h1><?php esc_html_e( 'Bestellübersicht', 'libre-bite' ); ?></h1>
		<button type="button" id="lbite-board-fullscreen" class="button button-large" title="Vollbild">
			<span class="dashicons dashicons-editor-expand"></span>
		</button>
	</div>

	<div class="lbite-board-controls">
		<label>
			<?php esc_html_e( 'Standort:', 'libre-bite' ); ?>
			<select id="lbite-board-location">
				<option value=""><?php esc_html_e( 'Bitte Standort wählen', 'libre-bite' ); ?></option>
				<?php
				$locations = get_posts(
					array(
						'post_type'      => 'lbite_location',
						'posts_per_page' => -1,
						'post_status'    => 'publish',
					)
				);
				$saved_location = get_user_meta( get_current_user_id(), 'lbite_board_location', true );

				foreach ( $locations as $location ) :
					?>
					<option value="<?php echo esc_attr( $location->ID ); ?>" <?php selected( $saved_location, $location->ID ); ?>>
						<?php echo esc_html( $location->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>

		<label class="lbite-wake-lock-toggle">
			<input type="checkbox" id="lbite-wake-lock" checked>
			<?php esc_html_e( 'Standby verhindern', 'libre-bite' ); ?>
		</label>

		<button type="button" id="lbite-sound-toggle" class="button">
			<span class="dashicons dashicons-controls-volumeon"></span>
			<?php esc_html_e( 'Sound aktiv', 'libre-bite' ); ?>
		</button>
	</div>

	<!-- Platzhalter wenn kein Standort gewählt -->
	<div class="lbite-no-location-selected" id="lbite-no-location-message" style="<?php echo $saved_location ? 'display: none;' : ''; ?>">
		<div style="background: #fff; padding: 40px; margin: 40px 0; border: 2px dashed #ccc; border-radius: 8px; text-align: center;">
			<span class="dashicons dashicons-location" style="font-size: 48px; color: #999; margin-bottom: 20px;"></span>
			<h2 style="color: #666; margin: 10px 0;"><?php esc_html_e( 'Bitte wählen Sie einen Standort aus', 'libre-bite' ); ?></h2>
			<p style="color: #999;"><?php esc_html_e( 'Die Bestellübersicht wird für den gewählten Standort angezeigt.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<div class="lbite-kanban-board" id="lbite-kanban-board" style="<?php echo $saved_location ? '' : 'display: none;'; ?>">
		<div class="lbite-kanban-column" data-status="incoming">
			<h2><?php esc_html_e( 'Eingang', 'libre-bite' ); ?></h2>
			<div class="lbite-kanban-cards" id="lbite-column-incoming"></div>
		</div>

		<div class="lbite-kanban-column" data-status="preparing">
			<h2><?php esc_html_e( 'Zubereiten', 'libre-bite' ); ?></h2>
			<div class="lbite-kanban-cards" id="lbite-column-preparing"></div>
		</div>

		<div class="lbite-kanban-column" data-status="ready">
			<h2><?php esc_html_e( 'Abholbereit', 'libre-bite' ); ?></h2>
			<div class="lbite-kanban-cards" id="lbite-column-ready"></div>
		</div>

		<div class="lbite-kanban-column" data-status="completed">
			<h2><?php esc_html_e( 'Abgeschlossen', 'libre-bite' ); ?></h2>
			<div class="lbite-kanban-cards" id="lbite-column-completed"></div>
		</div>
	</div>
</div>


<?php
$lbite_inline_js = <<<'LBJS'
// Dashboard-Funktionalität wird via dashboard.js geladen
LBJS;
wp_add_inline_script( 'lbite-dashboard', $lbite_inline_js );
?>
