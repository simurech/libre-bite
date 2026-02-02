<?php
/**
 * Template: Einstellungen
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Einstellungen speichern
if ( isset( $_POST['lb_save_settings'] ) && check_admin_referer( 'lb_settings' ) ) {
	// Location page handling.
	if ( isset( $_POST['lb_location_page_id'] ) ) {
		$location_page_id = sanitize_text_field( wp_unslash( $_POST['lb_location_page_id'] ) );

		if ( 'create_new' === $location_page_id ) {
			// Create a new page with the shortcode.
			$new_page_id = wp_insert_post(
				array(
					'post_title'   => __( 'Standorte', 'libre-bite' ),
					'post_content' => '[lb_location_selector]',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
			if ( ! is_wp_error( $new_page_id ) ) {
				update_option( 'lb_location_page_id', $new_page_id );
			}
		} else {
			update_option( 'lb_location_page_id', intval( $location_page_id ) );
		}
	}

	update_option( 'lb_tip_percentage_1', isset( $_POST['lb_tip_percentage_1'] ) ? floatval( wp_unslash( $_POST['lb_tip_percentage_1'] ) ) : 5 );
	update_option( 'lb_tip_percentage_2', isset( $_POST['lb_tip_percentage_2'] ) ? floatval( wp_unslash( $_POST['lb_tip_percentage_2'] ) ) : 10 );
	update_option( 'lb_tip_percentage_3', isset( $_POST['lb_tip_percentage_3'] ) ? floatval( wp_unslash( $_POST['lb_tip_percentage_3'] ) ) : 15 );
	update_option( 'lb_tip_default_selection', isset( $_POST['lb_tip_default_selection'] ) ? sanitize_text_field( wp_unslash( $_POST['lb_tip_default_selection'] ) ) : 'none' );
	update_option( 'lb_enable_rounding', isset( $_POST['lb_enable_rounding'] ) );
	update_option( 'lb_checkout_mode', isset( $_POST['lb_checkout_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['lb_checkout_mode'] ) ) : 'standard' );

	// Branding settings.
	update_option( 'lb_brand_name', isset( $_POST['lb_brand_name'] ) ? sanitize_text_field( wp_unslash( $_POST['lb_brand_name'] ) ) : '' );
	update_option( 'lb_brand_logo', isset( $_POST['lb_brand_logo'] ) ? intval( wp_unslash( $_POST['lb_brand_logo'] ) ) : 0 );
	update_option( 'lb_color_primary', isset( $_POST['lb_color_primary'] ) ? sanitize_hex_color( wp_unslash( $_POST['lb_color_primary'] ) ) : '#0073aa' );
	update_option( 'lb_color_secondary', isset( $_POST['lb_color_secondary'] ) ? sanitize_hex_color( wp_unslash( $_POST['lb_color_secondary'] ) ) : '#23282d' );
	update_option( 'lb_color_accent', isset( $_POST['lb_color_accent'] ) ? sanitize_hex_color( wp_unslash( $_POST['lb_color_accent'] ) ) : '#00a32a' );
	update_option( 'lb_preparation_time', isset( $_POST['lb_preparation_time'] ) ? intval( wp_unslash( $_POST['lb_preparation_time'] ) ) : 30 );
	update_option( 'lb_pickup_reminder_time', isset( $_POST['lb_pickup_reminder_time'] ) ? intval( wp_unslash( $_POST['lb_pickup_reminder_time'] ) ) : 15 );
	update_option( 'lb_timeslot_interval', isset( $_POST['lb_timeslot_interval'] ) ? intval( wp_unslash( $_POST['lb_timeslot_interval'] ) ) : 15 );
	update_option( 'lb_dashboard_refresh_interval', isset( $_POST['lb_dashboard_refresh_interval'] ) ? intval( wp_unslash( $_POST['lb_dashboard_refresh_interval'] ) ) : 30 );
	update_option( 'lb_notification_sound', isset( $_POST['lb_notification_sound'] ) ? esc_url_raw( wp_unslash( $_POST['lb_notification_sound'] ) ) : '' );
	update_option( 'lb_delete_data_on_uninstall', isset( $_POST['lb_delete_data_on_uninstall'] ) );

	echo '<div class="notice notice-success"><p>' . esc_html__( 'Einstellungen gespeichert', 'libre-bite' ) . '</p></div>';
}

$location_page_id             = get_option( 'lb_location_page_id', 0 );
$tip_percentage_1             = get_option( 'lb_tip_percentage_1', 5 );
$tip_percentage_2             = get_option( 'lb_tip_percentage_2', 10 );
$tip_percentage_3             = get_option( 'lb_tip_percentage_3', 15 );
$tip_default_selection        = get_option( 'lb_tip_default_selection', 'none' );
$enable_rounding              = get_option( 'lb_enable_rounding', false );
$checkout_mode                = get_option( 'lb_checkout_mode', 'standard' );
$preparation_time             = get_option( 'lb_preparation_time', 30 );
$pickup_reminder_time         = get_option( 'lb_pickup_reminder_time', 15 );
$timeslot_interval            = get_option( 'lb_timeslot_interval', 15 );
$dashboard_refresh_interval   = get_option( 'lb_dashboard_refresh_interval', 30 );
$delete_data_on_uninstall     = get_option( 'lb_delete_data_on_uninstall', false );

// Branding settings.
$brand_name      = get_option( 'lb_brand_name', '' );
$brand_logo      = get_option( 'lb_brand_logo', 0 );
$color_primary   = get_option( 'lb_color_primary', '#0073aa' );
$color_secondary = get_option( 'lb_color_secondary', '#23282d' );
$color_accent    = get_option( 'lb_color_accent', '#00a32a' );

// Standard-Sound oder benutzerdefinierter Sound
$default_sound_url      = LB_PLUGIN_URL . 'assets/sounds/notification.mp3';
$default_sound_exists   = file_exists( LB_PLUGIN_DIR . 'assets/sounds/notification.mp3' );
$notification_sound     = get_option( 'lb_notification_sound', $default_sound_exists ? $default_sound_url : '' );

// Get all pages for dropdown
$all_pages = get_pages( array( 'post_status' => 'publish' ) );
?>

<div class="wrap">
	<h1>
		<?php
		$plugin_name = apply_filters( 'lb_plugin_display_name', __( 'Libre Bite', 'libre-bite' ) );
		echo esc_html( $plugin_name . ' - ' . __( 'Einstellungen', 'libre-bite' ) );
		?>
	</h1>

	<form method="post">
		<?php wp_nonce_field( 'lb_settings' ); ?>

		<h2><?php esc_html_e( 'Allgemeine Einstellungen', 'libre-bite' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Standort-Seite', 'libre-bite' ); ?></th>
				<td>
					<select name="lb_location_page_id">
						<option value="0"><?php esc_html_e( '-- Bitte wählen --', 'libre-bite' ); ?></option>
						<option value="create_new"><?php esc_html_e( '+ Neue Seite erstellen', 'libre-bite' ); ?></option>
						<?php foreach ( $all_pages as $page ) : ?>
							<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $location_page_id, $page->ID ); ?>>
								<?php echo esc_html( $page->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'Wählen Sie die Seite, auf der der Shortcode [lb_location_selector] eingebunden ist, oder erstellen Sie eine neue Seite.', 'libre-bite' ); ?>
						<?php if ( $location_page_id ) : ?>
							<br><a href="<?php echo esc_url( get_edit_post_link( $location_page_id ) ); ?>" target="_blank"><?php esc_html_e( 'Seite bearbeiten', 'libre-bite' ); ?></a>
							|
							<a href="<?php echo esc_url( get_permalink( $location_page_id ) ); ?>" target="_blank"><?php esc_html_e( 'Seite ansehen', 'libre-bite' ); ?></a>
						<?php endif; ?>
					</p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Trinkgeld-Einstellungen', 'libre-bite' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Prozentwert 1', 'libre-bite' ); ?></th>
				<td>
					<input type="number" step="0.1" min="0" name="lb_tip_percentage_1" value="<?php echo esc_attr( $tip_percentage_1 ); ?>" class="small-text"> %
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Prozentwert 2', 'libre-bite' ); ?></th>
				<td>
					<input type="number" step="0.1" min="0" name="lb_tip_percentage_2" value="<?php echo esc_attr( $tip_percentage_2 ); ?>" class="small-text"> %
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Prozentwert 3', 'libre-bite' ); ?></th>
				<td>
					<input type="number" step="0.1" min="0" name="lb_tip_percentage_3" value="<?php echo esc_attr( $tip_percentage_3 ); ?>" class="small-text"> %
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Standard-Auswahl', 'libre-bite' ); ?></th>
				<td>
					<select name="lb_tip_default_selection">
						<option value="none" <?php selected( $tip_default_selection, 'none' ); ?>><?php esc_html_e( 'Kein Trinkgeld (Standard)', 'libre-bite' ); ?></option>
						<option value="percentage_1" <?php selected( $tip_default_selection, 'percentage_1' ); ?>><?php echo esc_html( $tip_percentage_1 ); ?>%</option>
						<option value="percentage_2" <?php selected( $tip_default_selection, 'percentage_2' ); ?>><?php echo esc_html( $tip_percentage_2 ); ?>%</option>
						<option value="percentage_3" <?php selected( $tip_default_selection, 'percentage_3' ); ?>><?php echo esc_html( $tip_percentage_3 ); ?>%</option>
					</select>
					<p class="description"><?php esc_html_e( 'Welche Option soll im Checkout standardmäßig vorausgewählt sein?', 'libre-bite' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Checkout-Einstellungen', 'libre-bite' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Checkout-Modus', 'libre-bite' ); ?></th>
				<td>
					<select name="lb_checkout_mode">
						<option value="standard" <?php selected( $checkout_mode, 'standard' ); ?>><?php esc_html_e( 'Standard (alle WooCommerce-Felder)', 'libre-bite' ); ?></option>
						<option value="optimized" <?php selected( $checkout_mode, 'optimized' ); ?>><?php esc_html_e( 'Optimiert (nur Name + Beleg-Option)', 'libre-bite' ); ?></option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Im optimierten Modus wird nur nach dem Namen gefragt und ob ein Beleg per E-Mail gewünscht ist.', 'libre-bite' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Gesamtbetrag runden', 'libre-bite' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="lb_enable_rounding" value="1" <?php checked( $enable_rounding ); ?>>
						<?php esc_html_e( 'Gesamtbetrag auf 5 Rappen (0.05 CHF) runden', 'libre-bite' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Verhindert Rundungsfehler bei Kombination von Gutscheinen und Trinkgeld.', 'libre-bite' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Zeiteinstellungen', 'libre-bite' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Vorbereitungszeit', 'libre-bite' ); ?></th>
				<td>
					<input type="number" min="0" name="lb_preparation_time" value="<?php echo esc_attr( $preparation_time ); ?>" class="small-text"> <?php esc_html_e( 'Minuten', 'libre-bite' ); ?>
					<p class="description"><?php esc_html_e( 'Vorbestellungen werden X Minuten vor der Abholzeit automatisch von "Eingang" zu "Zubereiten" verschoben.', 'libre-bite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Erinnerung vor Abholung', 'libre-bite' ); ?></th>
				<td>
					<input type="number" min="0" name="lb_pickup_reminder_time" value="<?php echo esc_attr( $pickup_reminder_time ); ?>" class="small-text"> <?php esc_html_e( 'Minuten', 'libre-bite' ); ?>
					<p class="description"><?php esc_html_e( 'Reminder-E-Mail X Minuten vor der Abholzeit versenden.', 'libre-bite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Zeitslot-Intervall', 'libre-bite' ); ?></th>
				<td>
					<input type="number" min="5" step="5" name="lb_timeslot_interval" value="<?php echo esc_attr( $timeslot_interval ); ?>" class="small-text"> <?php esc_html_e( 'Minuten', 'libre-bite' ); ?>
					<p class="description"><?php esc_html_e( 'Abstand zwischen den Zeitslots für Vorbestellungen.', 'libre-bite' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Dashboard-Einstellungen', 'libre-bite' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Aktualisierungsintervall', 'libre-bite' ); ?></th>
				<td>
					<input type="number" min="10" name="lb_dashboard_refresh_interval" value="<?php echo esc_attr( $dashboard_refresh_interval ); ?>" class="small-text"> <?php esc_html_e( 'Sekunden', 'libre-bite' ); ?>
					<p class="description"><?php esc_html_e( 'Wie oft das Dashboard nach neuen Bestellungen prüft.', 'libre-bite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Benachrichtigungssound', 'libre-bite' ); ?></th>
				<td>
					<div style="display: flex; align-items: center; gap: 10px;">
						<input type="text" id="lb_notification_sound" name="lb_notification_sound" value="<?php echo esc_attr( $notification_sound ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Sound-URL', 'libre-bite' ); ?>" readonly>
						<button type="button" class="button" id="lb_upload_sound_button">
							<?php esc_html_e( 'Sound wählen', 'libre-bite' ); ?>
						</button>
						<button type="button" class="button" id="lb_remove_sound_button" <?php echo empty( $notification_sound ) ? 'style="display:none;"' : ''; ?>>
							<?php esc_html_e( 'Entfernen', 'libre-bite' ); ?>
						</button>
					</div>
					<?php if ( $notification_sound ) : ?>
						<audio id="lb_sound_preview" controls style="margin-top: 10px; max-width: 300px;">
							<source src="<?php echo esc_url( $notification_sound ); ?>" type="audio/mpeg">
						</audio>
					<?php endif; ?>
					<p class="description">
						<?php
						if ( $default_sound_exists ) {
							echo esc_html__( 'Standard-Sound ist vorhanden. Sie können auch einen eigenen Sound aus der Mediathek wählen.', 'libre-bite' );
						} else {
							echo esc_html__( 'Wählen Sie einen Sound aus Ihrer Mediathek oder legen Sie eine "notification.mp3" im Plugin-Ordner "assets/sounds/" ab.', 'libre-bite' );
						}
						?>
					</p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Branding', 'libre-bite' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Markenname', 'libre-bite' ); ?></th>
				<td>
					<input type="text" name="lb_brand_name" value="<?php echo esc_attr( $brand_name ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'z.B. Mein Restaurant', 'libre-bite' ); ?>">
					<p class="description"><?php esc_html_e( 'Wird auf der Bestätigungsseite und in E-Mails angezeigt.', 'libre-bite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Logo', 'libre-bite' ); ?></th>
				<td>
					<div class="lb-logo-upload">
						<?php
						$logo_url = $brand_logo ? wp_get_attachment_image_url( $brand_logo, 'medium' ) : '';
						?>
						<div class="lb-logo-preview" style="margin-bottom: 10px;">
							<?php if ( $logo_url ) : ?>
								<img src="<?php echo esc_url( $logo_url ); ?>" style="max-width: 200px; height: auto;">
							<?php endif; ?>
						</div>
						<input type="hidden" id="lb_brand_logo" name="lb_brand_logo" value="<?php echo esc_attr( $brand_logo ); ?>">
						<button type="button" class="button" id="lb_upload_logo_button"><?php esc_html_e( 'Logo wählen', 'libre-bite' ); ?></button>
						<button type="button" class="button" id="lb_remove_logo_button" <?php echo ! $brand_logo ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Entfernen', 'libre-bite' ); ?></button>
					</div>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Primärfarbe', 'libre-bite' ); ?></th>
				<td>
					<input type="text" name="lb_color_primary" value="<?php echo esc_attr( $color_primary ); ?>" class="lb-color-picker" data-default-color="#0073aa">
					<p class="description"><?php esc_html_e( 'Hauptfarbe für Buttons und wichtige Elemente.', 'libre-bite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Sekundärfarbe', 'libre-bite' ); ?></th>
				<td>
					<input type="text" name="lb_color_secondary" value="<?php echo esc_attr( $color_secondary ); ?>" class="lb-color-picker" data-default-color="#23282d">
					<p class="description"><?php esc_html_e( 'Für Texte und sekundäre Elemente.', 'libre-bite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Akzentfarbe', 'libre-bite' ); ?></th>
				<td>
					<input type="text" name="lb_color_accent" value="<?php echo esc_attr( $color_accent ); ?>" class="lb-color-picker" data-default-color="#00a32a">
					<p class="description"><?php esc_html_e( 'Für Erfolgs- und Bestätigungselemente.', 'libre-bite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Vom Theme übernehmen', 'libre-bite' ); ?></th>
				<td>
					<button type="button" class="button" id="lb_inherit_theme_colors">
						<?php esc_html_e( 'Farben vom Theme übernehmen', 'libre-bite' ); ?>
					</button>
					<span class="spinner" id="lb_theme_colors_spinner" style="float: none; margin-top: 0;"></span>
					<p class="description"><?php esc_html_e( 'Versucht, die Farben aus Ihrem aktiven Theme zu übernehmen.', 'libre-bite' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Deinstallation', 'libre-bite' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Daten bei Deinstallation löschen', 'libre-bite' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="lb_delete_data_on_uninstall" value="1" <?php checked( $delete_data_on_uninstall ); ?>>
						<?php esc_html_e( 'Alle Plugin-Daten bei der Deinstallation vollständig löschen', 'libre-bite' ); ?>
					</label>
					<p class="description" style="color: #d63638;">
						<strong><?php esc_html_e( 'Achtung:', 'libre-bite' ); ?></strong>
						<?php esc_html_e( 'Diese Option löscht alle Standorte, Produktoptionen, Einstellungen und Bestellungs-Metadaten unwiderruflich!', 'libre-bite' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Einstellungen speichern', 'libre-bite' ), 'primary', 'lb_save_settings' ); ?>
	</form>
</div>

<script>
jQuery(document).ready(function($) {
	var soundFrame;
	var logoFrame;

	// Color Picker initialisieren
	$('.lb-color-picker').wpColorPicker();

	// Media-Upload für Sound öffnen
	$('#lb_upload_sound_button').on('click', function(e) {
		e.preventDefault();

		if (soundFrame) {
			soundFrame.open();
			return;
		}

		soundFrame = wp.media({
			title: '<?php esc_html_e( 'Sound-Datei wählen', 'libre-bite' ); ?>',
			button: {
				text: '<?php esc_html_e( 'Sound verwenden', 'libre-bite' ); ?>'
			},
			library: {
				type: ['audio']
			},
			multiple: false
		});

		soundFrame.on('select', function() {
			var attachment = soundFrame.state().get('selection').first().toJSON();
			$('#lb_notification_sound').val(attachment.url);
			$('#lb_remove_sound_button').show();

			var preview = $('#lb_sound_preview');
			if (preview.length) {
				preview.find('source').attr('src', attachment.url);
				preview[0].load();
			} else {
				$('#lb_remove_sound_button').after(
					'<audio id="lb_sound_preview" controls style="margin-top: 10px; max-width: 300px;">' +
					'<source src="' + attachment.url + '" type="audio/mpeg">' +
					'</audio>'
				);
			}
		});

		soundFrame.open();
	});

	// Sound entfernen
	$('#lb_remove_sound_button').on('click', function(e) {
		e.preventDefault();
		$('#lb_notification_sound').val('');
		$(this).hide();
		$('#lb_sound_preview').remove();
	});

	// Logo-Upload
	$('#lb_upload_logo_button').on('click', function(e) {
		e.preventDefault();

		if (logoFrame) {
			logoFrame.open();
			return;
		}

		logoFrame = wp.media({
			title: '<?php esc_html_e( 'Logo wählen', 'libre-bite' ); ?>',
			button: {
				text: '<?php esc_html_e( 'Logo verwenden', 'libre-bite' ); ?>'
			},
			library: {
				type: ['image']
			},
			multiple: false
		});

		logoFrame.on('select', function() {
			var attachment = logoFrame.state().get('selection').first().toJSON();
			$('#lb_brand_logo').val(attachment.id);
			$('.lb-logo-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');
			$('#lb_remove_logo_button').show();
		});

		logoFrame.open();
	});

	// Logo entfernen
	$('#lb_remove_logo_button').on('click', function(e) {
		e.preventDefault();
		$('#lb_brand_logo').val('');
		$('.lb-logo-preview').empty();
		$(this).hide();
	});

	// Farben vom Theme übernehmen
	$('#lb_inherit_theme_colors').on('click', function(e) {
		e.preventDefault();
		var $btn = $(this);
		var $spinner = $('#lb_theme_colors_spinner');

		$btn.prop('disabled', true);
		$spinner.addClass('is-active');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'lb_get_theme_colors',
				nonce: '<?php echo esc_js( wp_create_nonce( 'lb_admin_nonce' ) ); ?>'
			},
			success: function(response) {
				$btn.prop('disabled', false);
				$spinner.removeClass('is-active');

				if (response.success && response.data) {
					if (response.data.primary) {
						$('input[name="lb_color_primary"]').wpColorPicker('color', response.data.primary);
					}
					if (response.data.secondary) {
						$('input[name="lb_color_secondary"]').wpColorPicker('color', response.data.secondary);
					}
					if (response.data.accent) {
						$('input[name="lb_color_accent"]').wpColorPicker('color', response.data.accent);
					}
					alert('<?php esc_html_e( 'Farben wurden übernommen!', 'libre-bite' ); ?>');
				} else {
					alert('<?php esc_html_e( 'Konnte keine Farben vom Theme finden.', 'libre-bite' ); ?>');
				}
			},
			error: function() {
				$btn.prop('disabled', false);
				$spinner.removeClass('is-active');
				alert('<?php esc_html_e( 'Fehler beim Abrufen der Theme-Farben.', 'libre-bite' ); ?>');
			}
		});
	});
});
</script>
<?php
// Scripts laden
wp_enqueue_media();
wp_enqueue_style( 'wp-color-picker' );
wp_enqueue_script( 'wp-color-picker' );
?>
