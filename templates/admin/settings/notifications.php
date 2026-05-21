<?php
/**
 * Tab: Benachrichtigungen
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_premium_allowed = function_exists( 'lbite_freemius' ) && lbite_freemius()->can_use_premium_code__premium_only();

if ( $lbite_premium_allowed && lbite_feature_enabled( 'enable_sound_notifications' ) ) {
	wp_enqueue_media();
}

$lbite_default_sound_url    = LBITE_PLUGIN_URL . 'assets/sounds/notification.mp3';
$lbite_default_sound_exists = file_exists( LBITE_PLUGIN_DIR . 'assets/sounds/notification.mp3' );
$lbite_notification_sound   = get_option( 'lbite_notification_sound', $lbite_default_sound_exists ? $lbite_default_sound_url : '' );
?>
<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="notifications">

	<?php
	$lbite_toggle_key             = 'enable_pickup_reminders';
	$lbite_toggle_label           = __( 'Pickup Reminders', 'libre-bite' );
	$lbite_toggle_description     = __( 'Send an automatic email reminder before the scheduled pickup time.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';

	$lbite_toggle_key             = 'enable_sound_notifications';
	$lbite_toggle_label           = __( 'Sound Notifications', 'libre-bite' );
	$lbite_toggle_description     = __( 'Play a sound when a new order arrives in the order overview.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<?php if ( $lbite_premium_allowed && lbite_feature_enabled( 'enable_sound_notifications' ) ) : ?>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Notification Sound', 'libre-bite' ); ?></th>
			<td>
				<div style="display: flex; align-items: center; gap: 10px;">
					<input type="text" id="lbite_notification_sound" name="lbite_notification_sound"
						value="<?php echo esc_attr( $lbite_notification_sound ); ?>" class="regular-text"
						placeholder="<?php esc_attr_e( 'Sound URL', 'libre-bite' ); ?>" readonly>
					<button type="button" class="button" id="lbite_upload_sound_button">
						<?php esc_html_e( 'Select Sound', 'libre-bite' ); ?>
					</button>
					<button type="button" class="button" id="lbite_remove_sound_button"
						<?php echo empty( $lbite_notification_sound ) ? 'style="display:none;"' : ''; ?>>
						<?php esc_html_e( 'Remove', 'libre-bite' ); ?>
					</button>
				</div>
				<?php if ( $lbite_notification_sound ) : ?>
					<audio id="lbite_sound_preview" controls style="margin-top: 10px; max-width: 300px;">
						<source src="<?php echo esc_url( $lbite_notification_sound ); ?>" type="audio/mpeg">
					</audio>
				<?php endif; ?>
				<p class="description">
					<?php echo esc_html( $lbite_default_sound_exists
						? __( 'Default sound is available. You can also select your own sound from the media library.', 'libre-bite' )
						: __( 'Select a sound from your media library.', 'libre-bite' )
					); ?>
				</p>
			</td>
		</tr>
	</table>
	<?php endif; ?>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>

<?php if ( $lbite_premium_allowed && lbite_feature_enabled( 'enable_sound_notifications' ) ) : ?>
<script>
jQuery(document).ready(function($) {
	var lbiteSoundFrame;
	$('#lbite_upload_sound_button').on('click', function(e) {
		e.preventDefault();
		if (lbiteSoundFrame) { lbiteSoundFrame.open(); return; }
		lbiteSoundFrame = wp.media({
			title: '<?php esc_html_e( 'Select Sound File', 'libre-bite' ); ?>',
			button: { text: '<?php esc_html_e( 'Use Sound', 'libre-bite' ); ?>' },
			library: { type: ['audio'] },
			multiple: false
		});
		lbiteSoundFrame.on('select', function() {
			var attachment = lbiteSoundFrame.state().get('selection').first().toJSON();
			$('#lbite_notification_sound').val(attachment.url);
			$('#lbite_remove_sound_button').show();
			var preview = $('#lbite_sound_preview');
			if (preview.length) { preview.find('source').attr('src', attachment.url); preview[0].load(); }
			else { $('#lbite_remove_sound_button').after('<audio id="lbite_sound_preview" controls style="margin-top:10px;max-width:300px;"><source src="' + attachment.url + '" type="audio/mpeg"></audio>'); }
		});
		lbiteSoundFrame.open();
	});
	$('#lbite_remove_sound_button').on('click', function(e) {
		e.preventDefault();
		$('#lbite_notification_sound').val('');
		$(this).hide();
		$('#lbite_sound_preview').remove();
	});
});
</script>
<?php endif; ?>
