<?php
/**
 * Template: Debug-Informationen
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Nur für Administratoren.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Keine Berechtigung', 'libre-bite' ) );
}

$plugin_name = apply_filters( 'lbite_plugin_display_name', __( 'Libre Bite', 'libre-bite' ) );
?>

<div class="wrap">
	<h1><?php echo esc_html( $plugin_name . ' - ' . __( 'Debug-Informationen', 'libre-bite' ) ); ?></h1>

	<div class="lbite-debug-section">
		<h2><?php esc_html_e( 'System-Informationen', 'libre-bite' ); ?></h2>
		<table class="widefat striped">
			<tbody>
				<tr>
					<th><?php esc_html_e( 'WordPress Version', 'libre-bite' ); ?></th>
					<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'PHP Version', 'libre-bite' ); ?></th>
					<td><?php echo esc_html( PHP_VERSION ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'WooCommerce Version', 'libre-bite' ); ?></th>
					<td><?php echo defined( 'WC_VERSION' ) ? esc_html( WC_VERSION ) : esc_html__( 'Nicht installiert', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Plugin Version', 'libre-bite' ); ?></th>
					<td><?php echo defined( 'LBITE_VERSION' ) ? esc_html( LBITE_VERSION ) : '-'; ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Aktives Theme', 'libre-bite' ); ?></th>
					<td><?php echo esc_html( wp_get_theme()->get( 'Name' ) . ' (' . wp_get_theme()->get( 'Version' ) . ')' ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Debug-Modus', 'libre-bite' ); ?></th>
					<td><?php echo WP_DEBUG ? '<span style="color: green;">Aktiv</span>' : '<span style="color: gray;">Inaktiv</span>'; ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Speicherlimit', 'libre-bite' ); ?></th>
					<td><?php echo esc_html( WP_MEMORY_LIMIT ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Max. Upload-Grösse', 'libre-bite' ); ?></th>
					<td><?php echo esc_html( size_format( wp_max_upload_size() ) ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="lbite-debug-section" style="margin-top: 30px;">
		<h2><?php esc_html_e( 'Plugin-Einstellungen', 'libre-bite' ); ?></h2>
		<table class="widefat striped">
			<tbody>
				<tr>
					<th><?php esc_html_e( 'Standorte', 'libre-bite' ); ?></th>
					<td>
						<?php
						$locations = get_posts(
							array(
								'post_type'      => 'lbite_location',
								'posts_per_page' => -1,
								'post_status'    => 'publish',
							)
						);
						echo esc_html( count( $locations ) );
						?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Produkt-Optionen', 'libre-bite' ); ?></th>
					<td>
						<?php
						$options = get_posts(
							array(
								'post_type'      => 'lbite_product_option',
								'posts_per_page' => -1,
								'post_status'    => 'publish',
							)
						);
						echo esc_html( count( $options ) );
						?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Vorbereitungszeit', 'libre-bite' ); ?></th>
					<td><?php echo esc_html( get_option( 'lbite_preparation_time', 30 ) . ' ' . __( 'Minuten', 'libre-bite' ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Zeitslot-Intervall', 'libre-bite' ); ?></th>
					<td><?php echo esc_html( get_option( 'lbite_timeslot_interval', 15 ) . ' ' . __( 'Minuten', 'libre-bite' ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Rundung aktiviert', 'libre-bite' ); ?></th>
					<td><?php echo get_option( 'lbite_enable_rounding', false ) ? esc_html__( 'Ja', 'libre-bite' ) : esc_html__( 'Nein', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Trinkgeld-Prozentsätze', 'libre-bite' ); ?></th>
					<td>
						<?php
						echo esc_html(
							get_option( 'lbite_tip_percentage_1', 5 ) . '%, ' .
							get_option( 'lbite_tip_percentage_2', 10 ) . '%, ' .
							get_option( 'lbite_tip_percentage_3', 15 ) . '%'
						);
						?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Standort-Seite', 'libre-bite' ); ?></th>
					<td>
						<?php
						$location_page_id = get_option( 'lbite_location_page_id', 0 );
						if ( $location_page_id ) {
							$page = get_post( $location_page_id );
							echo $page ? esc_html( $page->post_title . ' (ID: ' . $location_page_id . ')' ) : esc_html__( 'Seite nicht gefunden', 'libre-bite' );
						} else {
							esc_html_e( 'Nicht konfiguriert', 'libre-bite' );
						}
						?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Daten bei Deinstallation löschen', 'libre-bite' ); ?></th>
					<td><?php echo get_option( 'lbite_delete_data_on_uninstall', false ) ? '<span style="color: red;">' . esc_html__( 'Ja', 'libre-bite' ) . '</span>' : esc_html__( 'Nein', 'libre-bite' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="lbite-debug-section" style="margin-top: 30px;">
		<h2><?php esc_html_e( 'Cron-Jobs', 'libre-bite' ); ?></h2>
		<table class="widefat striped">
			<tbody>
				<?php
				$cron_hooks = array(
					'lbite_check_pickup_reminders' => __( 'Pickup-Erinnerungen prüfen', 'libre-bite' ),
					'lbite_check_preorders'        => __( 'Vorbestellungen prüfen', 'libre-bite' ),
				);

				foreach ( $cron_hooks as $hook => $label ) :
					$next_run = wp_next_scheduled( $hook );
					?>
					<tr>
						<th><?php echo esc_html( $label ); ?></th>
						<td>
							<?php
							if ( $next_run ) {
								echo esc_html( wp_date( 'd.m.Y H:i:s', $next_run ) );
							} else {
								esc_html_e( 'Nicht geplant', 'libre-bite' );
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<?php if ( WP_DEBUG && WP_DEBUG_LOG ) : ?>
		<div class="lbite-debug-section" style="margin-top: 30px;">
			<h2><?php esc_html_e( 'Debug-Log (letzte 50 Zeilen)', 'libre-bite' ); ?></h2>
			<?php
			$log_file = WP_CONTENT_DIR . '/debug.log';
			if ( file_exists( $log_file ) ) :
				$log_content = file_get_contents( $log_file );
				$log_lines   = explode( "\n", $log_content );
				$last_lines  = array_slice( $log_lines, -50 );
				?>
				<textarea readonly style="width: 100%; height: 300px; font-family: monospace; font-size: 12px;"><?php echo esc_textarea( implode( "\n", $last_lines ) ); ?></textarea>
				<p class="description">
					<?php
					/* translators: %s: path to debug.log file */
					printf( esc_html__( 'Vollständiges Log: %s', 'libre-bite' ), '<code>' . esc_html( $log_file ) . '</code>' );
					?>
				</p>
			<?php else : ?>
				<p><?php esc_html_e( 'Keine Debug-Log-Datei gefunden.', 'libre-bite' ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>

