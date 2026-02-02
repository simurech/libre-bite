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

<div class="wrap lb-order-board">
	<div class="lb-board-header">
		<h1><?php esc_html_e( 'Bestellübersicht', 'libre-bite' ); ?></h1>
		<button type="button" id="lb-board-fullscreen" class="button button-large" title="Vollbild">
			<span class="dashicons dashicons-editor-expand"></span>
		</button>
	</div>

	<div class="lb-board-controls">
		<label>
			<?php esc_html_e( 'Standort:', 'libre-bite' ); ?>
			<select id="lb-board-location">
				<option value=""><?php esc_html_e( 'Bitte Standort wählen', 'libre-bite' ); ?></option>
				<?php
				$locations = get_posts(
					array(
						'post_type'      => 'lb_location',
						'posts_per_page' => -1,
						'post_status'    => 'publish',
					)
				);
				$saved_location = get_user_meta( get_current_user_id(), 'lb_board_location', true );

				foreach ( $locations as $location ) :
					?>
					<option value="<?php echo esc_attr( $location->ID ); ?>" <?php selected( $saved_location, $location->ID ); ?>>
						<?php echo esc_html( $location->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>

		<label class="lb-wake-lock-toggle">
			<input type="checkbox" id="lb-wake-lock" checked>
			<?php esc_html_e( 'Standby verhindern', 'libre-bite' ); ?>
		</label>

		<button type="button" id="lb-sound-toggle" class="button">
			<span class="dashicons dashicons-controls-volumeon"></span>
			<?php esc_html_e( 'Sound aktiv', 'libre-bite' ); ?>
		</button>
	</div>

	<!-- Platzhalter wenn kein Standort gewählt -->
	<div class="lb-no-location-selected" id="lb-no-location-message" style="<?php echo $saved_location ? 'display: none;' : ''; ?>">
		<div style="background: #fff; padding: 40px; margin: 40px 0; border: 2px dashed #ccc; border-radius: 8px; text-align: center;">
			<span class="dashicons dashicons-location" style="font-size: 48px; color: #999; margin-bottom: 20px;"></span>
			<h2 style="color: #666; margin: 10px 0;"><?php esc_html_e( 'Bitte wählen Sie einen Standort aus', 'libre-bite' ); ?></h2>
			<p style="color: #999;"><?php esc_html_e( 'Die Bestellübersicht wird für den gewählten Standort angezeigt.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<div class="lb-kanban-board" id="lb-kanban-board" style="<?php echo $saved_location ? '' : 'display: none;'; ?>">
		<div class="lb-kanban-column" data-status="incoming">
			<h2><?php esc_html_e( 'Eingang', 'libre-bite' ); ?></h2>
			<div class="lb-kanban-cards" id="lb-column-incoming"></div>
		</div>

		<div class="lb-kanban-column" data-status="preparing">
			<h2><?php esc_html_e( 'Zubereiten', 'libre-bite' ); ?></h2>
			<div class="lb-kanban-cards" id="lb-column-preparing"></div>
		</div>

		<div class="lb-kanban-column" data-status="ready">
			<h2><?php esc_html_e( 'Abholbereit', 'libre-bite' ); ?></h2>
			<div class="lb-kanban-cards" id="lb-column-ready"></div>
		</div>

		<div class="lb-kanban-column" data-status="completed">
			<h2><?php esc_html_e( 'Abgeschlossen', 'libre-bite' ); ?></h2>
			<div class="lb-kanban-cards" id="lb-column-completed"></div>
		</div>
	</div>
</div>

<style>
.lb-board-controls {
	background: #fff;
	padding: 15px 20px;
	margin: 20px 0;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
	display: flex;
	gap: 20px;
	align-items: center;
}

/* Standort-Dropdown links, Rest rechts */
.lb-board-controls > label:first-child {
	flex: 1;
	display: flex;
	align-items: center;
	gap: 10px;
}

.lb-board-controls > label:first-child select {
	flex: 1;
	max-width: 400px;
	min-width: 200px;
}

/* Wake-Lock und Sound-Toggle rechts */
.lb-wake-lock-toggle {
	display: flex !important;
	align-items: center;
	gap: 8px;
	white-space: nowrap;
	margin-left: auto;
}

.lb-wake-lock-toggle input[type="checkbox"] {
	margin: 0;
	width: 18px;
	height: 18px;
}

#lb-sound-toggle {
	display: flex;
	align-items: center;
	gap: 6px;
	white-space: nowrap;
}

.lb-kanban-board {
	display: grid;
	grid-template-columns: repeat(4, 1fr);
	gap: 20px;
	margin-top: 20px;
}

.lb-kanban-column {
	background: #f7f7f7;
	border-radius: 4px;
	padding: 15px;
	min-height: 500px;
}

.lb-kanban-column h2 {
	margin: 0 0 15px 0;
	padding-bottom: 10px;
	border-bottom: 2px solid #ddd;
	font-size: 16px;
}

.lb-kanban-cards {
	min-height: 400px;
}

.lb-kanban-card {
	background: #fff;
	padding: 15px;
	margin-bottom: 10px;
	border-radius: 4px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.1);
	cursor: move;
	transition: all 0.3s;
}

.lb-kanban-card:hover {
	box-shadow: 0 3px 6px rgba(0,0,0,0.15);
}

.lb-kanban-card.dragging {
	opacity: 0.5;
}

@media (max-width: 1200px) {
	.lb-kanban-board {
		grid-template-columns: repeat(2, 1fr);
	}
}

@media (max-width: 768px) {
	.lb-kanban-board {
		grid-template-columns: 1fr;
	}
}

/* Header mit Vollbild-Button */
.lb-board-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20px;
}

.lb-board-header h1 {
	margin: 0;
}

#lb-board-fullscreen {
	display: flex;
	align-items: center;
	gap: 5px;
	min-height: 44px;
	padding: 8px 16px;
}

#lb-board-fullscreen .dashicons {
	font-size: 20px;
	width: 20px;
	height: 20px;
}

/* Touch-Optimierung für Bestellübersicht */
.lb-kanban-card {
	padding: 20px;
	font-size: 16px;
	touch-action: manipulation;
	min-height: 150px;
}

.lb-kanban-card h3 {
	font-size: 18px;
}

.lb-kanban-card-meta {
	font-size: 15px;
	line-height: 1.8;
}

.lb-kanban-card-items {
	font-size: 15px;
}

.lb-kanban-card-item {
	padding: 8px 0;
}

.lb-kanban-card-actions button {
	min-width: 54px;
	min-height: 54px;
	font-size: 24px;
	padding: 12px;
	touch-action: manipulation;
}

.lb-board-controls {
	flex-wrap: wrap;
}

.lb-board-controls > label:first-child,
.lb-board-controls button {
	min-height: 44px;
	font-size: 16px;
}

.lb-board-controls select {
	min-height: 44px;
	font-size: 16px;
	padding: 8px 12px;
}

/* Responsive: Bei kleinen Bildschirmen untereinander */
@media (max-width: 600px) {
	.lb-board-controls {
		flex-direction: column;
		align-items: stretch;
	}

	.lb-board-controls > label:first-child {
		flex-direction: column;
		align-items: stretch;
	}

	.lb-board-controls > label:first-child select {
		max-width: none;
		width: 100%;
	}

	.lb-wake-lock-toggle {
		margin-left: 0;
		justify-content: center;
	}

	#lb-sound-toggle {
		width: 100%;
		justify-content: center;
	}
}

/* Vollbild-Modus für Bestellübersicht */
body.lb-board-fullscreen-active #wpadminbar,
body.lb-board-fullscreen-active #adminmenumain,
body.lb-board-fullscreen-active .update-nag {
	display: none !important;
}

body.lb-board-fullscreen-active #wpcontent {
	margin-left: 0 !important;
	padding-left: 0 !important;
}

body.lb-board-fullscreen-active .lb-order-board {
	padding: 20px;
	max-width: none;
}

body.lb-board-fullscreen-active .lb-kanban-board {
	height: calc(100vh - 200px);
}

body.lb-board-fullscreen-active .lb-kanban-column {
	min-height: calc(100vh - 250px);
}

body.lb-board-fullscreen-active .lb-kanban-cards {
	min-height: calc(100vh - 350px);
	max-height: calc(100vh - 350px);
	overflow-y: auto;
}

/* Tablet-Optimierung für Touch */
@media (min-width: 768px) and (max-width: 1024px) {
	.lb-kanban-card {
		min-height: 180px;
	}

	.lb-kanban-card-actions button {
		min-width: 60px;
		min-height: 60px;
		font-size: 28px;
	}
}

/* Status- und Stornieren-Buttons */
.lb-status-button {
	transition: background-color 0.2s, transform 0.1s;
}

.lb-status-button:hover {
	filter: brightness(1.1);
	transform: translateY(-1px);
}

.lb-status-button:active {
	transform: translateY(0);
}

.lb-cancel-button {
	transition: background-color 0.2s, color 0.2s, transform 0.1s;
	flex-shrink: 0;
	min-width: 54px !important;
	min-height: 54px !important;
}

.lb-cancel-button:hover {
	background: #e74c3c !important;
	color: white !important;
	transform: translateY(-1px);
}

.lb-cancel-button:active {
	transform: translateY(0);
}

/* Touch-Optimierung für Buttons */
@media (hover: none) and (pointer: coarse) {
	.lb-status-button,
	.lb-cancel-button {
		min-height: 60px;
	}

	.lb-status-button {
		font-size: 18px !important;
		padding: 16px !important;
	}

	.lb-cancel-button {
		font-size: 24px !important;
		min-width: 60px !important;
		min-height: 60px !important;
	}
}

/* Abgeschlossene Bestellungen - ausgegraut */
#lb-column-completed .lb-kanban-card {
	opacity: 0.6;
	background: #f5f5f5;
	filter: grayscale(50%);
}

#lb-column-completed .lb-kanban-card:hover {
	opacity: 0.8;
	filter: grayscale(20%);
}

/* "Mehr laden" Button */
.lb-load-more-completed {
	width: 100%;
	padding: 15px;
	margin-top: 10px;
	background: #ffffff;
	border: 2px dashed #ccc;
	border-radius: 4px;
	cursor: pointer;
	font-size: 15px;
	color: #666;
	transition: all 0.2s;
	touch-action: manipulation;
}

.lb-load-more-completed:hover {
	background: #f9f9f9;
	border-color: #999;
	color: #333;
}

.lb-load-more-completed:active {
	transform: scale(0.98);
}

@media (hover: none) and (pointer: coarse) {
	.lb-load-more-completed {
		padding: 18px;
		font-size: 16px;
		min-height: 60px;
	}
}
</style>

<script>
// Dashboard-Funktionalität wird via dashboard.js geladen
</script>
