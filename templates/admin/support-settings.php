<?php
/**
 * Support-Einstellungen (Super-Admin)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-Datei, wird innerhalb einer Klassen-Methode via include geladen; Variablen befinden sich im Methoden-Scope, nicht im globalen Namespace.

$support_settings = get_option( 'lbite_support_settings', array() );

$support_email        = isset( $support_settings['support_email'] ) ? $support_settings['support_email'] : get_option( 'admin_email' );
$support_phone        = isset( $support_settings['support_phone'] ) ? $support_settings['support_phone'] : '';
$support_hours        = isset( $support_settings['support_hours'] ) ? $support_settings['support_hours'] : '';
$support_billing_note = isset( $support_settings['support_billing_note'] ) ? $support_settings['support_billing_note'] : '';
$support_custom_text  = isset( $support_settings['support_custom_text'] ) ? $support_settings['support_custom_text'] : '';
?>
<?php if ( empty( $lbite_is_tab ) ) : ?>
<div class="wrap lbite-admin-wrap">
	<h1><?php esc_html_e( 'Support Settings', 'libre-bite' ); ?></h1>
<?php endif; ?>
	<p class="description"><?php esc_html_e( 'Configure the support contact information displayed on the help pages.', 'libre-bite' ); ?></p>

	<form id="lbite-support-settings-form" method="post">
		<?php wp_nonce_field( 'lbite_admin_nonce', 'lbite_nonce' ); ?>

		<div class="lbite-settings-card">
			<h2><?php esc_html_e( 'Contact Information', 'libre-bite' ); ?></h2>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="support_email"><?php esc_html_e( 'Support Email', 'libre-bite' ); ?></label>
						</th>
						<td>
							<input type="email"
								   id="support_email"
								   name="support_email"
								   value="<?php echo esc_attr( $support_email ); ?>"
								   class="regular-text">
							<p class="description"><?php esc_html_e( 'Email address for support requests', 'libre-bite' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="support_phone"><?php esc_html_e( 'Support Phone', 'libre-bite' ); ?></label>
						</th>
						<td>
							<input type="text"
								   id="support_phone"
								   name="support_phone"
								   value="<?php echo esc_attr( $support_phone ); ?>"
								   class="regular-text"
								   placeholder="<?php esc_attr_e( 'E.g. +41 44 123 45 67', 'libre-bite' ); ?>">
							<p class="description"><?php esc_html_e( 'Phone number for support (optional)', 'libre-bite' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="support_hours"><?php esc_html_e( 'Support Hours', 'libre-bite' ); ?></label>
						</th>
						<td>
							<input type="text"
								   id="support_hours"
								   name="support_hours"
								   value="<?php echo esc_attr( $support_hours ); ?>"
								   class="regular-text"
								   placeholder="<?php esc_attr_e( 'E.g. Mon–Fri 9 AM–5 PM', 'libre-bite' ); ?>">
							<p class="description"><?php esc_html_e( 'Times when support is available', 'libre-bite' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="lbite-settings-card">
			<h2><?php esc_html_e( 'Additional Information', 'libre-bite' ); ?></h2>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="support_billing_note"><?php esc_html_e( 'Billing Note', 'libre-bite' ); ?></label>
						</th>
						<td>
							<textarea id="support_billing_note"
									  name="support_billing_note"
									  rows="3"
									  class="large-text"
									  placeholder="<?php esc_attr_e( 'E.g. Support is billed at hourly rate (CHF 120.-/hour)', 'libre-bite' ); ?>"><?php echo esc_textarea( $support_billing_note ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Information about support billing (appears on help pages)', 'libre-bite' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="support_custom_text"><?php esc_html_e( 'Additional Text', 'libre-bite' ); ?></label>
						</th>
						<td>
							<textarea id="support_custom_text"
									  name="support_custom_text"
									  rows="5"
									  class="large-text"
									  placeholder="<?php esc_attr_e( 'Additional notes or information...', 'libre-bite' ); ?>"><?php echo esc_textarea( $support_custom_text ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Free text for additional information', 'libre-bite' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="lbite-settings-card">
			<h2><?php esc_html_e( 'Preview', 'libre-bite' ); ?></h2>
			<p class="description"><?php esc_html_e( 'This is how support information will be displayed on the help pages:', 'libre-bite' ); ?></p>

			<div class="lbite-support-preview">
				<div class="lbite-support-preview-content">
					<h3><?php esc_html_e( 'Contact Support', 'libre-bite' ); ?></h3>
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
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'libre-bite' ); ?></button>
			<span class="lbite-save-status"></span>
		</p>
	</form>
<?php if ( empty( $lbite_is_tab ) ) : ?>
</div>
<?php endif; ?>


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
		$('#preview-custom').text(val).toggle(val.length > 0);
	});

	// Formular absenden
	$('#lbite-support-settings-form').on('submit', function(e) {
		e.preventDefault();

		var $form = $(this);
		var $status = $form.find('.lbite-save-status');
		var $button = $form.find('button[type="submit"]');

		$button.prop('disabled', true);
		$status.text('<?php echo esc_js( __( 'Saving...', 'libre-bite' ) ); ?>');

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
					$status.text('<?php echo esc_js( __( 'Saved!', 'libre-bite' ) ); ?>');
					setTimeout(function() {
						$status.text('');
					}, 2000);
				} else {
					$status.text(response.data.message || '<?php echo esc_js( __( 'Error saving', 'libre-bite' ) ); ?>');
				}
			},
			error: function() {
				$status.text('<?php echo esc_js( __( 'Error saving', 'libre-bite' ) ); ?>');
			},
			complete: function() {
				$button.prop('disabled', false);
			}
		});
	});
});
<?php wp_add_inline_script( 'lbite-admin', ob_get_clean() ); ?>
