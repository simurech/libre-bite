<?php
/**
 * Support-Einstellungen (Super-Admin)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$support_settings = get_option( 'lbite_support_settings', array() );

$support_email        = isset( $support_settings['support_email'] ) ? $support_settings['support_email'] : get_option( 'admin_email' );
$support_phone        = isset( $support_settings['support_phone'] ) ? $support_settings['support_phone'] : '';
$support_hours        = isset( $support_settings['support_hours'] ) ? $support_settings['support_hours'] : '';
$support_billing_note = isset( $support_settings['support_billing_note'] ) ? $support_settings['support_billing_note'] : '';
$support_custom_text  = isset( $support_settings['support_custom_text'] ) ? $support_settings['support_custom_text'] : '';
?>
<div class="wrap lbite-admin-wrap">
	<h1><?php esc_html_e( 'Support-Einstellungen', 'libre-bite' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Konfigurieren Sie die Support-Kontaktdaten, die auf den Hilfe-Seiten angezeigt werden.', 'libre-bite' ); ?></p>

	<form id="lbite-support-settings-form" method="post">
		<?php wp_nonce_field( 'lbite_admin_nonce', 'lbite_nonce' ); ?>

		<div class="lbite-settings-card">
			<h2><?php esc_html_e( 'Kontaktdaten', 'libre-bite' ); ?></h2>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="support_email"><?php esc_html_e( 'Support E-Mail', 'libre-bite' ); ?></label>
						</th>
						<td>
							<input type="email"
								   id="support_email"
								   name="support_email"
								   value="<?php echo esc_attr( $support_email ); ?>"
								   class="regular-text">
							<p class="description"><?php esc_html_e( 'E-Mail-Adresse für Support-Anfragen', 'libre-bite' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="support_phone"><?php esc_html_e( 'Support Telefon', 'libre-bite' ); ?></label>
						</th>
						<td>
							<input type="text"
								   id="support_phone"
								   name="support_phone"
								   value="<?php echo esc_attr( $support_phone ); ?>"
								   class="regular-text"
								   placeholder="<?php esc_attr_e( 'z.B. +41 44 123 45 67', 'libre-bite' ); ?>">
							<p class="description"><?php esc_html_e( 'Telefonnummer für Support (optional)', 'libre-bite' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="support_hours"><?php esc_html_e( 'Support-Zeiten', 'libre-bite' ); ?></label>
						</th>
						<td>
							<input type="text"
								   id="support_hours"
								   name="support_hours"
								   value="<?php echo esc_attr( $support_hours ); ?>"
								   class="regular-text"
								   placeholder="<?php esc_attr_e( 'z.B. Mo-Fr 9-17 Uhr', 'libre-bite' ); ?>">
							<p class="description"><?php esc_html_e( 'Zeiten, zu denen Support verfügbar ist', 'libre-bite' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="lbite-settings-card">
			<h2><?php esc_html_e( 'Zusätzliche Informationen', 'libre-bite' ); ?></h2>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="support_billing_note"><?php esc_html_e( 'Hinweis zur Verrechnung', 'libre-bite' ); ?></label>
						</th>
						<td>
							<textarea id="support_billing_note"
									  name="support_billing_note"
									  rows="3"
									  class="large-text"
									  placeholder="<?php esc_attr_e( 'z.B. Support wird nach Aufwand verrechnet (CHF 120.-/Stunde)', 'libre-bite' ); ?>"><?php echo esc_textarea( $support_billing_note ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Informationen zur Support-Verrechnung (erscheint auf Hilfe-Seiten)', 'libre-bite' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="support_custom_text"><?php esc_html_e( 'Zusätzlicher Text', 'libre-bite' ); ?></label>
						</th>
						<td>
							<textarea id="support_custom_text"
									  name="support_custom_text"
									  rows="5"
									  class="large-text"
									  placeholder="<?php esc_attr_e( 'Zusätzliche Hinweise oder Informationen...', 'libre-bite' ); ?>"><?php echo esc_textarea( $support_custom_text ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Freier Text für zusätzliche Hinweise', 'libre-bite' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="lbite-settings-card">
			<h2><?php esc_html_e( 'Vorschau', 'libre-bite' ); ?></h2>
			<p class="description"><?php esc_html_e( 'So werden die Support-Informationen auf den Hilfe-Seiten angezeigt:', 'libre-bite' ); ?></p>

			<div class="lbite-support-preview">
				<div class="lbite-support-preview-content">
					<h3><?php esc_html_e( 'Support kontaktieren', 'libre-bite' ); ?></h3>
					<p id="preview-email">
						<span class="dashicons dashicons-email"></span>
						<a href="mailto:<?php echo esc_attr( $support_email ); ?>"><?php echo esc_html( $support_email ); ?></a>
					</p>
					<p id="preview-phone" <?php echo empty( $support_phone ) ? 'style="display:none;"' : ''; ?>>
						<span class="dashicons dashicons-phone"></span>
						<span><?php echo esc_html( $support_phone ); ?></span>
					</p>
					<p id="preview-hours" <?php echo empty( $support_hours ) ? 'style="display:none;"' : ''; ?>>
						<span class="dashicons dashicons-clock"></span>
						<span><?php echo esc_html( $support_hours ); ?></span>
					</p>
					<p id="preview-billing" class="lbite-billing-note" <?php echo empty( $support_billing_note ) ? 'style="display:none;"' : ''; ?>>
						<span class="dashicons dashicons-info"></span>
						<span><?php echo esc_html( $support_billing_note ); ?></span>
					</p>
					<div id="preview-custom" <?php echo empty( $support_custom_text ) ? 'style="display:none;"' : ''; ?>>
						<?php echo wp_kses_post( wpautop( $support_custom_text ) ); ?>
					</div>
				</div>
			</div>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Einstellungen speichern', 'libre-bite' ); ?></button>
			<span class="lbite-save-status"></span>
		</p>
	</form>
</div>


<?php ob_start(); ?>
jQuery(document).ready(function($) {
	// Live-Vorschau
	$('#support_email').on('input', function() {
		var val = $(this).val() || '<?php echo esc_js( get_option( 'admin_email' ) ); ?>';
		$('#preview-email a').attr('href', 'mailto:' + val).text(val);
	});

	$('#support_phone').on('input', function() {
		var val = $(this).val();
		$('#preview-phone span:last').text(val);
		$('#preview-phone').toggle(val.length > 0);
	});

	$('#support_hours').on('input', function() {
		var val = $(this).val();
		$('#preview-hours span:last').text(val);
		$('#preview-hours').toggle(val.length > 0);
	});

	$('#support_billing_note').on('input', function() {
		var val = $(this).val();
		$('#preview-billing span:last').text(val);
		$('#preview-billing').toggle(val.length > 0);
	});

	$('#support_custom_text').on('input', function() {
		var val = $(this).val();
		$('#preview-custom').html(val.replace(/\n/g, '<br>')).toggle(val.length > 0);
	});

	// Formular absenden
	$('#lbite-support-settings-form').on('submit', function(e) {
		e.preventDefault();

		var $form = $(this);
		var $status = $form.find('.lbite-save-status');
		var $button = $form.find('button[type="submit"]');

		$button.prop('disabled', true);
		$status.text('<?php echo esc_js( __( 'Speichern...', 'libre-bite' ) ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'lbite_save_support_settings',
				nonce: $('#lbite_nonce').val(),
				support_email: $('#support_email').val(),
				support_phone: $('#support_phone').val(),
				support_hours: $('#support_hours').val(),
				support_billing_note: $('#support_billing_note').val(),
				support_custom_text: $('#support_custom_text').val()
			},
			success: function(response) {
				if (response.success) {
					$status.text('<?php echo esc_js( __( 'Gespeichert!', 'libre-bite' ) ); ?>');
					setTimeout(function() {
						$status.text('');
					}, 2000);
				} else {
					$status.text(response.data.message || '<?php echo esc_js( __( 'Fehler beim Speichern', 'libre-bite' ) ); ?>');
				}
			},
			error: function() {
				$status.text('<?php echo esc_js( __( 'Fehler beim Speichern', 'libre-bite' ) ); ?>');
			},
			complete: function() {
				$button.prop('disabled', false);
			}
		});
	});
});
<?php wp_add_inline_script( 'lbite-admin', ob_get_clean() ); ?>
