<?php
/**
 * Tab: Benachrichtigungen
 *
 * Drei klar getrennte Abschnitte in einem einzigen Formular: Sound, E-Mail,
 * SMS. Vorher endete das Formular vor dem SMS-Block — die SMS-Felder lagen
 * ausserhalb jedes <form> und konnten nie gespeichert werden. Jetzt umschliesst
 * ein durchgehendes Formular alle drei Abschnitte.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_premium_allowed = function_exists( 'lbite_freemius' ) && lbite_freemius()->can_use_premium_code__premium_only();

if ( lbite_feature_enabled( 'enable_sound_notifications' ) && $lbite_premium_allowed ) {
	wp_enqueue_media();
}

$lbite_default_sound_url    = LBITE_PLUGIN_URL . 'assets/sounds/notification.mp3';
$lbite_default_sound_exists = file_exists( LBITE_PLUGIN_DIR . 'assets/sounds/notification.mp3' );
$lbite_notification_sound   = get_option( 'lbite_notification_sound', $lbite_default_sound_exists ? $lbite_default_sound_url : '' );

if ( ! class_exists( 'LBite_SMS' ) ) {
	require_once LBITE_PLUGIN_DIR . 'includes/modules/sms/class-sms.php';
}

$lbite_sms_configured = LBite_SMS::is_configured();
$lbite_sms_columns    = class_exists( 'LBite_Order_Dashboard' ) ? LBite_Order_Dashboard::get_columns() : array();
$lbite_sms_trigger    = (string) get_option( 'lbite_sms_trigger_status', '' );
?>
<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="notifications">

	<!-- Sound -->
	<h2><?php esc_html_e( 'Sound', 'libre-bite' ); ?></h2>
	<?php
	$lbite_toggle_key             = 'enable_sound_notifications';
	$lbite_toggle_label           = __( 'Sound Notifications', 'libre-bite' );
	$lbite_toggle_description     = __( 'Play a sound when a new order arrives in the order overview.', 'libre-bite' );
	$lbite_toggle_is_pro          = false;
	$lbite_toggle_premium_allowed = true;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<?php if ( lbite_feature_enabled( 'enable_sound_notifications' ) ) : ?>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Notification Sound', 'libre-bite' ); ?></th>
			<td>
				<?php if ( $lbite_premium_allowed ) : ?>
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
				<?php else : ?>
				<?php if ( $lbite_default_sound_exists ) : ?>
					<audio controls style="max-width: 300px;">
						<source src="<?php echo esc_url( $lbite_default_sound_url ); ?>" type="audio/mpeg">
					</audio>
				<?php endif; ?>
				<p class="description">
					<?php esc_html_e( 'The default notification sound is used. Upgrade to Pro to use a custom sound from your media library.', 'libre-bite' ); ?>
				</p>
				<?php endif; ?>
			</td>
		</tr>
	</table>
	<?php endif; ?>

	<hr style="margin: 24px 0;">

	<!-- E-Mail -->
	<h2><?php esc_html_e( 'Email', 'libre-bite' ); ?></h2>
	<?php
	$lbite_toggle_key             = 'enable_pickup_reminders';
	$lbite_toggle_label           = __( 'Pickup Reminders', 'libre-bite' );
	$lbite_toggle_description     = __( 'Send an automatic email reminder before the scheduled pickup time.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<?php if ( lbite_feature_enabled( 'enable_pickup_reminders' ) ) : ?>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Send reminders', 'libre-bite' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="lbite_email_pickup_reminder" value="1"
						<?php checked( (bool) get_option( 'lbite_email_pickup_reminder', true ) ); ?>
						<?php disabled( ! $lbite_premium_allowed ); ?>>
					<?php esc_html_e( 'Send the reminder email', 'libre-bite' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Switch off to keep the feature configured but pause the emails, e.g. during a busy period.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="lbite_pickup_reminder_time"><?php esc_html_e( 'Send before pickup', 'libre-bite' ); ?></label></th>
			<td>
				<input type="number" min="1" id="lbite_pickup_reminder_time" name="lbite_pickup_reminder_time" class="small-text"
					value="<?php echo esc_attr( get_option( 'lbite_pickup_reminder_time', 15 ) ); ?>"
					<?php disabled( ! $lbite_premium_allowed ); ?>>
				<?php esc_html_e( 'minutes', 'libre-bite' ); ?>
			</td>
		</tr>
	</table>
	<?php endif; ?>

	<hr style="margin: 24px 0;">

	<!-- SMS -->
	<h2><?php esc_html_e( 'SMS', 'libre-bite' ); ?></h2>
	<?php
	$lbite_toggle_key             = 'enable_sms_notifications';
	$lbite_toggle_label           = __( 'SMS Notifications', 'libre-bite' );
	$lbite_toggle_description     = __( 'Sends a short text message when an order reaches a chosen column. You use your own Twilio account, so no order or customer data passes through us and the cost stays transparent.', 'libre-bite' );
	$lbite_toggle_is_pro          = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<?php if ( lbite_feature_enabled( 'enable_sms_notifications' ) || ! $lbite_premium_allowed ) : ?>
	<p class="description" style="margin-bottom: 12px;">
		<strong><?php esc_html_e( 'Please note:', 'libre-bite' ); ?></strong>
		<?php esc_html_e( 'A message can only be sent if the order has a phone number. The phone field is optional in the standard checkout and is removed entirely in table ordering and in the optimised checkout — in those cases no SMS will go out.', 'libre-bite' ); ?>
	</p>

	<table class="form-table">
		<?php if ( $lbite_sms_configured ) : ?>
		<tr>
			<th></th>
			<td><p class="description" style="color:#1a7f37;"><?php esc_html_e( 'Credentials are stored.', 'libre-bite' ); ?></p></td>
		</tr>
		<?php endif; ?>
		<tr>
			<th><label for="lbite_sms_account_sid"><?php esc_html_e( 'Account SID', 'libre-bite' ); ?></label></th>
			<td>
				<input type="text" id="lbite_sms_account_sid" name="lbite_sms_account_sid" class="regular-text"
					value="<?php echo esc_attr( get_option( 'lbite_sms_account_sid', '' ) ); ?>"
					<?php disabled( ! $lbite_premium_allowed ); ?>>
			</td>
		</tr>
		<tr>
			<th><label for="lbite_sms_auth_token"><?php esc_html_e( 'Auth Token', 'libre-bite' ); ?></label></th>
			<td>
				<input type="password" id="lbite_sms_auth_token" name="lbite_sms_auth_token" class="regular-text"
					value="" autocomplete="new-password"
					placeholder="<?php echo esc_attr( $lbite_sms_configured ? __( 'Stored — leave empty to keep', 'libre-bite' ) : '' ); ?>"
					<?php disabled( ! $lbite_premium_allowed ); ?>>
				<p class="description"><?php esc_html_e( 'Stored encrypted. It is never shown again — leave the field empty to keep the current token.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="lbite_sms_from"><?php esc_html_e( 'Sender number', 'libre-bite' ); ?></label></th>
			<td>
				<input type="text" id="lbite_sms_from" name="lbite_sms_from" class="regular-text"
					value="<?php echo esc_attr( get_option( 'lbite_sms_from', '' ) ); ?>"
					placeholder="+41…" <?php disabled( ! $lbite_premium_allowed ); ?>>
			</td>
		</tr>
		<tr>
			<th><label for="lbite_sms_country_code"><?php esc_html_e( 'Default country code', 'libre-bite' ); ?></label></th>
			<td>
				<input type="text" id="lbite_sms_country_code" name="lbite_sms_country_code" class="small-text"
					value="<?php echo esc_attr( get_option( 'lbite_sms_country_code', '+41' ) ); ?>"
					<?php disabled( ! $lbite_premium_allowed ); ?>>
				<p class="description"><?php esc_html_e( 'Added to numbers that guests enter without one.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="lbite_sms_trigger_status"><?php esc_html_e( 'Send when moved to', 'libre-bite' ); ?></label></th>
			<td>
				<select id="lbite_sms_trigger_status" name="lbite_sms_trigger_status" <?php disabled( ! $lbite_premium_allowed ); ?>>
					<option value=""><?php esc_html_e( '— never —', 'libre-bite' ); ?></option>
					<?php foreach ( $lbite_sms_columns as $lbite_sms_col ) : ?>
						<option value="<?php echo esc_attr( $lbite_sms_col['key'] ); ?>" <?php selected( $lbite_sms_trigger, $lbite_sms_col['key'] ); ?>>
							<?php echo esc_html( $lbite_sms_col['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'One message per order at most.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="lbite_sms_template"><?php esc_html_e( 'Message', 'libre-bite' ); ?></label></th>
			<td>
				<textarea id="lbite_sms_template" name="lbite_sms_template" rows="3" class="large-text"
					<?php disabled( ! $lbite_premium_allowed ); ?>><?php
					echo esc_textarea(
						get_option(
							'lbite_sms_template',
							__( 'Your order {order_number} is ready for pickup. Thank you! {site}', 'libre-bite' )
						)
					);
				?></textarea>
				<p class="description">
					<?php esc_html_e( 'Placeholders: {order_number}, {first_name}, {site}, {location}, {total}', 'libre-bite' ); ?>
				</p>
			</td>
		</tr>
	</table>
	<?php endif; ?>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>

<?php if ( lbite_feature_enabled( 'enable_sound_notifications' ) && $lbite_premium_allowed ) : ?>
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
