<?php
/**
 * Template: Einstellungen – Allgemein
 *
 * Wird als Tab-Inhalt in settings-tabbed.php geladen.
 * Enthält: Standort-Seite, Zeiteinstellungen, Branding.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Einstellungen speichern
if ( isset( $_POST['lbite_save_settings'] ) && check_admin_referer( 'lbite_settings' ) ) {
	$lbite_save_tab = isset( $_POST['lbite_save_tab'] ) ? sanitize_key( wp_unslash( $_POST['lbite_save_tab'] ) ) : '';

	// Nur wenn dieser Tab die Daten abschickt
	if ( 'general' === $lbite_save_tab ) {
		// Standort-Seite
		if ( isset( $_POST['lbite_location_page_id'] ) ) {
			$lbite_loc_page = sanitize_text_field( wp_unslash( $_POST['lbite_location_page_id'] ) );
			if ( 'create_new' === $lbite_loc_page ) {
				$lbite_new_page_id = wp_insert_post(
					array(
						'post_title'   => __( 'Standorte', 'libre-bite' ),
						'post_content' => '[lbite_location_selector]',
						'post_status'  => 'publish',
						'post_type'    => 'page',
					)
				);
				if ( ! is_wp_error( $lbite_new_page_id ) ) {
					update_option( 'lbite_location_page_id', $lbite_new_page_id );
				}
			} else {
				update_option( 'lbite_location_page_id', intval( $lbite_loc_page ) );
			}
		}

		// Zeiteinstellungen
		update_option( 'lbite_preparation_time', isset( $_POST['lbite_preparation_time'] ) ? intval( wp_unslash( $_POST['lbite_preparation_time'] ) ) : 30 );
		update_option( 'lbite_pickup_reminder_time', isset( $_POST['lbite_pickup_reminder_time'] ) ? intval( wp_unslash( $_POST['lbite_pickup_reminder_time'] ) ) : 15 );
		update_option( 'lbite_timeslot_interval', isset( $_POST['lbite_timeslot_interval'] ) ? intval( wp_unslash( $_POST['lbite_timeslot_interval'] ) ) : 15 );

		// Branding
		update_option( 'lbite_brand_name', isset( $_POST['lbite_brand_name'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_brand_name'] ) ) : '' );
		update_option( 'lbite_brand_logo', isset( $_POST['lbite_brand_logo'] ) ? intval( wp_unslash( $_POST['lbite_brand_logo'] ) ) : 0 );
		update_option( 'lbite_color_primary', isset( $_POST['lbite_color_primary'] ) ? sanitize_hex_color( wp_unslash( $_POST['lbite_color_primary'] ) ) : '#0073aa' );
		update_option( 'lbite_color_secondary', isset( $_POST['lbite_color_secondary'] ) ? sanitize_hex_color( wp_unslash( $_POST['lbite_color_secondary'] ) ) : '#23282d' );
		update_option( 'lbite_color_accent', isset( $_POST['lbite_color_accent'] ) ? sanitize_hex_color( wp_unslash( $_POST['lbite_color_accent'] ) ) : '#00a32a' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'lbite-settings',
					'tab'     => 'general',
					'updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}

// Optionen laden
$lbite_location_page_id = get_option( 'lbite_location_page_id', 0 );
$lbite_preparation_time = get_option( 'lbite_preparation_time', 30 );
$lbite_pickup_reminder  = get_option( 'lbite_pickup_reminder_time', 15 );
$lbite_timeslot_int     = get_option( 'lbite_timeslot_interval', 15 );
$lbite_brand_name       = get_option( 'lbite_brand_name', '' );
$lbite_brand_logo       = get_option( 'lbite_brand_logo', 0 );
$lbite_color_primary    = get_option( 'lbite_color_primary', '#0073aa' );
$lbite_color_secondary  = get_option( 'lbite_color_secondary', '#23282d' );
$lbite_color_accent     = get_option( 'lbite_color_accent', '#00a32a' );
$lbite_all_pages        = get_pages( array( 'post_status' => 'publish' ) );

wp_enqueue_media();
wp_enqueue_style( 'wp-color-picker' );
wp_enqueue_script( 'wp-color-picker' );
?>

<?php if ( empty( $lbite_is_tab ) ) : ?>
<div class="wrap">
	<h1><?php echo esc_html( apply_filters( 'lbite_plugin_display_name', __( 'Libre Bite', 'libre-bite' ) ) . ' - ' . __( 'Einstellungen', 'libre-bite' ) ); ?></h1>
<?php endif; ?>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="general">

	<h2><?php esc_html_e( 'Standort-Seite', 'libre-bite' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Standort-Seite', 'libre-bite' ); ?></th>
			<td>
				<select name="lbite_location_page_id">
					<option value="0"><?php esc_html_e( '-- Bitte wählen --', 'libre-bite' ); ?></option>
					<option value="create_new"><?php esc_html_e( '+ Neue Seite erstellen', 'libre-bite' ); ?></option>
					<?php foreach ( $lbite_all_pages as $lbite_page ) : ?>
						<option value="<?php echo esc_attr( $lbite_page->ID ); ?>" <?php selected( $lbite_location_page_id, $lbite_page->ID ); ?>>
							<?php echo esc_html( $lbite_page->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?php esc_html_e( 'Wählen Sie die Seite, auf der der Shortcode [lbite_location_selector] eingebunden ist, oder erstellen Sie eine neue Seite.', 'libre-bite' ); ?>
					<?php if ( $lbite_location_page_id ) : ?>
						<br><a href="<?php echo esc_url( get_edit_post_link( $lbite_location_page_id ) ); ?>" target="_blank"><?php esc_html_e( 'Seite bearbeiten', 'libre-bite' ); ?></a>
						|
						<a href="<?php echo esc_url( get_permalink( $lbite_location_page_id ) ); ?>" target="_blank"><?php esc_html_e( 'Seite ansehen', 'libre-bite' ); ?></a>
					<?php endif; ?>
				</p>
			</td>
		</tr>
	</table>

	<h2><?php esc_html_e( 'Zeiteinstellungen', 'libre-bite' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Vorbereitungszeit', 'libre-bite' ); ?></th>
			<td>
				<input type="number" min="0" name="lbite_preparation_time" value="<?php echo esc_attr( $lbite_preparation_time ); ?>" class="small-text"> <?php esc_html_e( 'Minuten', 'libre-bite' ); ?>
				<p class="description"><?php esc_html_e( 'Vorbestellungen werden X Minuten vor der Abholzeit automatisch von "Eingang" zu "Zubereiten" verschoben.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Erinnerung vor Abholung', 'libre-bite' ); ?></th>
			<td>
				<input type="number" min="0" name="lbite_pickup_reminder_time" value="<?php echo esc_attr( $lbite_pickup_reminder ); ?>" class="small-text"> <?php esc_html_e( 'Minuten', 'libre-bite' ); ?>
				<p class="description"><?php esc_html_e( 'Reminder-E-Mail X Minuten vor der Abholzeit versenden.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Zeitslot-Intervall', 'libre-bite' ); ?></th>
			<td>
				<input type="number" min="5" step="5" name="lbite_timeslot_interval" value="<?php echo esc_attr( $lbite_timeslot_int ); ?>" class="small-text"> <?php esc_html_e( 'Minuten', 'libre-bite' ); ?>
				<p class="description"><?php esc_html_e( 'Abstand zwischen den Zeitslots für Vorbestellungen.', 'libre-bite' ); ?></p>
			</td>
		</tr>
	</table>

	<h2><?php esc_html_e( 'Branding', 'libre-bite' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Markenname', 'libre-bite' ); ?></th>
			<td>
				<input type="text" name="lbite_brand_name" value="<?php echo esc_attr( $lbite_brand_name ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'z.B. Mein Restaurant', 'libre-bite' ); ?>">
				<p class="description"><?php esc_html_e( 'Wird auf der Bestätigungsseite und in E-Mails angezeigt.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Logo', 'libre-bite' ); ?></th>
			<td>
				<div class="lbite-logo-upload">
					<?php $lbite_logo_url = $lbite_brand_logo ? wp_get_attachment_image_url( $lbite_brand_logo, 'medium' ) : ''; ?>
					<div class="lbite-logo-preview" style="margin-bottom: 10px;">
						<?php if ( $lbite_logo_url ) : ?>
							<img src="<?php echo esc_url( $lbite_logo_url ); ?>" style="max-width: 200px; height: auto;">
						<?php endif; ?>
					</div>
					<input type="hidden" id="lbite_brand_logo" name="lbite_brand_logo" value="<?php echo esc_attr( $lbite_brand_logo ); ?>">
					<button type="button" class="button" id="lbite_upload_logo_button"><?php esc_html_e( 'Logo wählen', 'libre-bite' ); ?></button>
					<button type="button" class="button" id="lbite_remove_logo_button" <?php echo ! $lbite_brand_logo ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Entfernen', 'libre-bite' ); ?></button>
				</div>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Primärfarbe', 'libre-bite' ); ?></th>
			<td>
				<input type="text" name="lbite_color_primary" value="<?php echo esc_attr( $lbite_color_primary ); ?>" class="lbite-color-picker" data-default-color="#0073aa">
				<p class="description"><?php esc_html_e( 'Hauptfarbe für Buttons und wichtige Elemente.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Sekundärfarbe', 'libre-bite' ); ?></th>
			<td>
				<input type="text" name="lbite_color_secondary" value="<?php echo esc_attr( $lbite_color_secondary ); ?>" class="lbite-color-picker" data-default-color="#23282d">
				<p class="description"><?php esc_html_e( 'Für Texte und sekundäre Elemente.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Akzentfarbe', 'libre-bite' ); ?></th>
			<td>
				<input type="text" name="lbite_color_accent" value="<?php echo esc_attr( $lbite_color_accent ); ?>" class="lbite-color-picker" data-default-color="#00a32a">
				<p class="description"><?php esc_html_e( 'Für Erfolgs- und Bestätigungselemente.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Vom Theme übernehmen', 'libre-bite' ); ?></th>
			<td>
				<button type="button" class="button" id="lbite_inherit_theme_colors">
					<?php esc_html_e( 'Farben vom Theme übernehmen', 'libre-bite' ); ?>
				</button>
				<span class="spinner" id="lbite_theme_colors_spinner" style="float: none; margin-top: 0;"></span>
				<p class="description"><?php esc_html_e( 'Versucht, die Farben aus Ihrem aktiven Theme zu übernehmen.', 'libre-bite' ); ?></p>
			</td>
		</tr>
	</table>

	<?php submit_button( __( 'Einstellungen speichern', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>

<?php if ( empty( $lbite_is_tab ) ) : ?>
</div>
<?php endif; ?>

<?php ob_start(); ?>
jQuery(document).ready(function($) {
	var lbiteLogoFrame;

	$('.lbite-color-picker').wpColorPicker();

	$('#lbite_upload_logo_button').on('click', function(e) {
		e.preventDefault();
		if (lbiteLogoFrame) { lbiteLogoFrame.open(); return; }
		lbiteLogoFrame = wp.media({
			title: '<?php esc_html_e( 'Logo wählen', 'libre-bite' ); ?>',
			button: { text: '<?php esc_html_e( 'Logo verwenden', 'libre-bite' ); ?>' },
			library: { type: ['image'] },
			multiple: false
		});
		lbiteLogoFrame.on('select', function() {
			var attachment = lbiteLogoFrame.state().get('selection').first().toJSON();
			$('#lbite_brand_logo').val(attachment.id);
			$('.lbite-logo-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');
			$('#lbite_remove_logo_button').show();
		});
		lbiteLogoFrame.open();
	});

	$('#lbite_remove_logo_button').on('click', function(e) {
		e.preventDefault();
		$('#lbite_brand_logo').val('');
		$('.lbite-logo-preview').empty();
		$(this).hide();
	});

	$('#lbite_inherit_theme_colors').on('click', function(e) {
		e.preventDefault();
		var $btn = $(this);
		var $spinner = $('#lbite_theme_colors_spinner');
		$btn.prop('disabled', true);
		$spinner.addClass('is-active');
		$.ajax({
			url: ajaxurl, type: 'POST',
			data: { action: 'lbite_get_theme_colors', nonce: '<?php echo esc_js( wp_create_nonce( 'lbite_admin_nonce' ) ); ?>' },
			success: function(response) {
				$btn.prop('disabled', false);
				$spinner.removeClass('is-active');
				if (response.success && response.data) {
					if (response.data.primary) $('input[name="lbite_color_primary"]').wpColorPicker('color', response.data.primary);
					if (response.data.secondary) $('input[name="lbite_color_secondary"]').wpColorPicker('color', response.data.secondary);
					if (response.data.accent) $('input[name="lbite_color_accent"]').wpColorPicker('color', response.data.accent);
					alert('<?php esc_html_e( 'Farben wurden übernommen!', 'libre-bite' ); ?>');
				} else { alert('<?php esc_html_e( 'Konnte keine Farben vom Theme finden.', 'libre-bite' ); ?>'); }
			},
			error: function() {
				$btn.prop('disabled', false);
				$spinner.removeClass('is-active');
				alert('<?php esc_html_e( 'Fehler beim Abrufen der Theme-Farben.', 'libre-bite' ); ?>');
			}
		});
	});
});
<?php wp_add_inline_script( 'lbite-admin', ob_get_clean() ); ?>
