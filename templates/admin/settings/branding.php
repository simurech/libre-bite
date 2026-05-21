<?php
/**
 * Tab: Branding
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_enqueue_media();
wp_enqueue_style( 'wp-color-picker' );
wp_enqueue_script( 'wp-color-picker' );

$lbite_custom_plugin_name = get_option( 'lbite_custom_plugin_name', '' );
$lbite_brand_name         = get_option( 'lbite_brand_name', '' );
$lbite_brand_logo      = get_option( 'lbite_brand_logo', 0 );
$lbite_color_primary   = get_option( 'lbite_color_primary', '#0073aa' );
$lbite_color_secondary = get_option( 'lbite_color_secondary', '#23282d' );
$lbite_color_accent    = get_option( 'lbite_color_accent', '#00a32a' );

$lbite_color_presets = array(
	array( 'label' => __( 'Classic', 'libre-bite' ),  'primary' => '#0073aa', 'secondary' => '#23282d', 'accent' => '#00a32a' ),
	array( 'label' => __( 'Modern', 'libre-bite' ),   'primary' => '#2271b1', 'secondary' => '#1d2327', 'accent' => '#d63638' ),
	array( 'label' => __( 'Dark', 'libre-bite' ),     'primary' => '#1a1a2e', 'secondary' => '#16213e', 'accent' => '#e94560' ),
	array( 'label' => __( 'Summer', 'libre-bite' ),   'primary' => '#f5a623', 'secondary' => '#4a4a4a', 'accent' => '#27ae60' ),
	array( 'label' => __( 'Ocean', 'libre-bite' ),    'primary' => '#0077b6', 'secondary' => '#03045e', 'accent' => '#00b4d8' ),
	array( 'label' => __( 'Forest', 'libre-bite' ),   'primary' => '#2d6a4f', 'secondary' => '#1b4332', 'accent' => '#95d5b2' ),
);
?>
<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="branding">

	<h2><?php esc_html_e( 'Plugin Identity', 'libre-bite' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Plugin Name', 'libre-bite' ); ?></th>
			<td>
				<input type="text" name="lbite_custom_plugin_name" value="<?php echo esc_attr( $lbite_custom_plugin_name ); ?>" class="regular-text" placeholder="Libre Bite">
				<p class="description"><?php esc_html_e( 'Replaces "Libre Bite" throughout the admin menu and pages. Leave empty to use the default name.', 'libre-bite' ); ?></p>
			</td>
		</tr>
	</table>

	<h2><?php esc_html_e( 'Branding', 'libre-bite' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Brand Name', 'libre-bite' ); ?></th>
			<td>
				<input type="text" name="lbite_brand_name" value="<?php echo esc_attr( $lbite_brand_name ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'E.g. My Restaurant', 'libre-bite' ); ?>">
				<p class="description"><?php esc_html_e( 'Displayed on the confirmation page and in emails.', 'libre-bite' ); ?></p>
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
					<button type="button" class="button" id="lbite_upload_logo_button"><?php esc_html_e( 'Choose Logo', 'libre-bite' ); ?></button>
					<button type="button" class="button" id="lbite_remove_logo_button" <?php echo ! $lbite_brand_logo ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Remove', 'libre-bite' ); ?></button>
				</div>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Color Presets', 'libre-bite' ); ?></th>
			<td>
				<div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">
					<?php foreach ( $lbite_color_presets as $lbite_preset ) : ?>
						<button
							type="button"
							class="button lbite-color-preset"
							data-primary="<?php echo esc_attr( $lbite_preset['primary'] ); ?>"
							data-secondary="<?php echo esc_attr( $lbite_preset['secondary'] ); ?>"
							data-accent="<?php echo esc_attr( $lbite_preset['accent'] ); ?>"
							style="border-left: 6px solid <?php echo esc_attr( $lbite_preset['primary'] ); ?>;"
						><?php echo esc_html( $lbite_preset['label'] ); ?></button>
					<?php endforeach; ?>
				</div>
				<p class="description"><?php esc_html_e( 'Click a preset to apply the colors. Save to keep the changes.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Primary Color', 'libre-bite' ); ?></th>
			<td>
				<input type="text" name="lbite_color_primary" value="<?php echo esc_attr( $lbite_color_primary ); ?>" class="lbite-color-picker" data-default-color="#0073aa">
				<p class="description"><?php esc_html_e( 'Main color for buttons and important elements.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Secondary Color', 'libre-bite' ); ?></th>
			<td>
				<input type="text" name="lbite_color_secondary" value="<?php echo esc_attr( $lbite_color_secondary ); ?>" class="lbite-color-picker" data-default-color="#23282d">
				<p class="description"><?php esc_html_e( 'For texts and secondary elements.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Accent Color', 'libre-bite' ); ?></th>
			<td>
				<input type="text" name="lbite_color_accent" value="<?php echo esc_attr( $lbite_color_accent ); ?>" class="lbite-color-picker" data-default-color="#00a32a">
				<p class="description"><?php esc_html_e( 'For success and confirmation elements.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Inherit from Theme', 'libre-bite' ); ?></th>
			<td>
				<button type="button" class="button" id="lbite_inherit_theme_colors">
					<?php esc_html_e( 'Inherit Colors from Theme', 'libre-bite' ); ?>
				</button>
				<span class="spinner" id="lbite_theme_colors_spinner" style="float: none; margin-top: 0;"></span>
				<p class="description"><?php esc_html_e( 'Attempts to inherit colors from your active theme.', 'libre-bite' ); ?></p>
			</td>
		</tr>
	</table>

	<?php submit_button( __( 'Save Settings', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>

<div id="lbite-branding-preview" style="margin-top: 24px; border: 1px solid #dcdcde; border-radius: 6px; overflow: hidden; max-width: 480px; background: #fff;">
	<p style="margin: 0; padding: 8px 16px; font-weight: 600; color: #1d2327; background: #f6f7f7; border-bottom: 1px solid #dcdcde;"><?php esc_html_e( 'Live Preview', 'libre-bite' ); ?></p>
	<div id="lbite-preview-header" style="background: <?php echo esc_attr( $lbite_color_primary ); ?>; display: flex; align-items: center; padding: 0 16px; height: 48px;">
		<span id="lbite-preview-brand-name" style="color: #fff; font-weight: 700; font-size: 16px;"><?php echo esc_html( $lbite_brand_name ?: __( 'Brand Name', 'libre-bite' ) ); ?></span>
	</div>
	<div id="lbite-preview-secondary-bar" style="background: <?php echo esc_attr( $lbite_color_secondary ); ?>; height: 6px;"></div>
	<div style="padding: 16px;">
		<div style="display: flex; gap: 8px; margin-bottom: 12px;">
			<button type="button" id="lbite-preview-btn-main" style="flex: 1; padding: 10px; background: <?php echo esc_attr( $lbite_color_accent ); ?>; color: #fff; border: none; border-radius: 4px; cursor: default; font-weight: 600;"><?php esc_html_e( 'Order Now', 'libre-bite' ); ?></button>
			<button type="button" id="lbite-preview-btn-secondary" style="flex: 1; padding: 10px; background: <?php echo esc_attr( $lbite_color_secondary ); ?>; color: #fff; border: none; border-radius: 4px; cursor: default;"><?php esc_html_e( 'Back', 'libre-bite' ); ?></button>
		</div>
		<p id="lbite-preview-secondary-text" style="color: <?php echo esc_attr( $lbite_color_secondary ); ?>; margin: 0; font-size: 13px;"><?php esc_html_e( 'Secondary text and links', 'libre-bite' ); ?></p>
	</div>
</div>

<?php
ob_start();
?>
jQuery(document).ready(function($) {
	var lbiteLogoFrame;

	function lbiteUpdatePreview(primary, secondary, accent) {
		$('#lbite-preview-header').css('background', primary);
		$('#lbite-preview-secondary-bar').css('background', secondary);
		$('#lbite-preview-btn-main').css('background', accent);
		$('#lbite-preview-btn-secondary').css('background', secondary);
		$('#lbite-preview-secondary-text').css('color', secondary);
	}

	$('.lbite-color-picker').wpColorPicker({
		change: function(event, ui) {
			var primary   = $('input[name="lbite_color_primary"]').wpColorPicker('color');
			var secondary = $('input[name="lbite_color_secondary"]').wpColorPicker('color');
			var accent    = $('input[name="lbite_color_accent"]').wpColorPicker('color');
			lbiteUpdatePreview(primary, secondary, accent);
		}
	});

	$('input[name="lbite_brand_name"]').on('input', function() {
		var name = $(this).val() || '<?php echo esc_js( __( 'Brand Name', 'libre-bite' ) ); ?>';
		$('#lbite-preview-brand-name').text(name);
	});

	$('.lbite-color-preset').on('click', function() {
		var $btn      = $(this);
		var primary   = $btn.data('primary');
		var secondary = $btn.data('secondary');
		var accent    = $btn.data('accent');
		$('input[name="lbite_color_primary"]').wpColorPicker('color', primary);
		$('input[name="lbite_color_secondary"]').wpColorPicker('color', secondary);
		$('input[name="lbite_color_accent"]').wpColorPicker('color', accent);
		lbiteUpdatePreview(primary, secondary, accent);
	});

	$('#lbite_upload_logo_button').on('click', function(e) {
		e.preventDefault();
		if (lbiteLogoFrame) { lbiteLogoFrame.open(); return; }
		lbiteLogoFrame = wp.media({
			title: '<?php esc_html_e( 'Choose Logo', 'libre-bite' ); ?>',
			button: { text: '<?php esc_html_e( 'Use Logo', 'libre-bite' ); ?>' },
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
					alert('<?php esc_html_e( 'Colors inherited!', 'libre-bite' ); ?>');
				} else { alert('<?php esc_html_e( 'Could not find colors from theme.', 'libre-bite' ); ?>'); }
			},
			error: function() {
				$btn.prop('disabled', false);
				$spinner.removeClass('is-active');
				alert('<?php esc_html_e( 'Error retrieving theme colors.', 'libre-bite' ); ?>');
			}
		});
	});
});
<?php
wp_add_inline_script( 'lbite-admin', ob_get_clean() );
