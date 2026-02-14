<?php
/**
 * Feature-Toggles (Super-Admin Einstellungen)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$features = get_option( 'lbite_features', array() );

// Feature-Gruppen definieren
$feature_groups = array(
	'order_system' => array(
		'title'       => __( 'Bestellsystem', 'libre-bite' ),
		'description' => __( 'Grundlegende Bestellfunktionen', 'libre-bite' ),
		'features'    => array(
			'enable_pos'                => array(
				'label'       => __( 'Kassensystem (POS)', 'libre-bite' ),
				'description' => __( 'Kassensystem für Vor-Ort-Bestellungen aktivieren', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
			'enable_scheduled_orders'   => array(
				'label'       => __( 'Vorbestellungen', 'libre-bite' ),
				'description' => __( 'Kunden können Bestellungen für später aufgeben', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
			'enable_order_notes'        => array(
				'label'       => __( 'Kundennotizen', 'libre-bite' ),
				'description' => __( 'Kunden können Notizen zur Bestellung hinzufügen', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
			'enable_order_cancellation' => array(
				'label'       => __( 'Stornierung', 'libre-bite' ),
				'description' => __( 'Kunden können Bestellungen selbst stornieren', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
		),
	),
	'checkout'     => array(
		'title'       => __( 'Checkout', 'libre-bite' ),
		'description' => __( 'Checkout-Prozess Einstellungen', 'libre-bite' ),
		'features'    => array(
			'enable_optimized_checkout' => array(
				'label'       => __( 'Optimierter Checkout', 'libre-bite' ),
				'description' => __( 'Vereinfachter Checkout-Flow', 'libre-bite' ),
				'default'     => true,
				'premium'     => true,
			),
			'enable_tips'               => array(
				'label'       => __( 'Trinkgeld-System', 'libre-bite' ),
				'description' => __( 'Trinkgeld-Optionen im Checkout anzeigen', 'libre-bite' ),
				'default'     => true,
				'premium'     => true,
			),
			'enable_rounding'           => array(
				'label'       => __( '5-Rappen-Rundung', 'libre-bite' ),
				'description' => __( 'Beträge auf 5 Rappen runden (Schweiz)', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
			'enable_guest_checkout'     => array(
				'label'       => __( 'Gast-Checkout', 'libre-bite' ),
				'description' => __( 'Checkout ohne Kundenkonto ermöglichen', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
			'enable_email_field'        => array(
				'label'       => __( 'E-Mail-Feld', 'libre-bite' ),
				'description' => __( 'E-Mail-Adresse im Checkout anzeigen', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
			'enable_phone_field'        => array(
				'label'       => __( 'Telefon-Feld', 'libre-bite' ),
				'description' => __( 'Telefonnummer im Checkout anzeigen', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
		),
	),
	'locations'    => array(
		'title'       => __( 'Standorte', 'libre-bite' ),
		'description' => __( 'Standort-Verwaltung', 'libre-bite' ),
		'features'    => array(
			'enable_multi_location'    => array(
				'label'       => __( 'Multi-Standort', 'libre-bite' ),
				'description' => __( 'Mehrere Standorte verwalten', 'libre-bite' ),
				'default'     => false,
				'premium'     => true,
			),
			'enable_location_selector' => array(
				'label'       => __( 'Standort-Auswahl', 'libre-bite' ),
				'description' => __( 'Standort-Auswahl im Frontend anzeigen', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
			'enable_opening_hours'     => array(
				'label'       => __( 'Öffnungszeiten', 'libre-bite' ),
				'description' => __( 'Öffnungszeiten pro Standort verwalten', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
		),
	),
	'notifications' => array(
		'title'       => __( 'Benachrichtigungen', 'libre-bite' ),
		'description' => __( 'E-Mail und Sound-Benachrichtigungen', 'libre-bite' ),
		'features'    => array(
			'enable_pickup_reminders'    => array(
				'label'       => __( 'Abhol-Erinnerungen', 'libre-bite' ),
				'description' => __( 'E-Mail-Erinnerung vor Abholzeit senden', 'libre-bite' ),
				'default'     => true,
				'premium'     => true,
			),
			'enable_sound_notifications' => array(
				'label'       => __( 'Sound-Benachrichtigung', 'libre-bite' ),
				'description' => __( 'Ton bei neuen Bestellungen abspielen', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
			'enable_admin_email'         => array(
				'label'       => __( 'Admin-Benachrichtigung', 'libre-bite' ),
				'description' => __( 'E-Mail an Admin bei neuen Bestellungen', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
		),
	),
	'products'     => array(
		'title'       => __( 'Produkte', 'libre-bite' ),
		'description' => __( 'Produkt-Erweiterungen', 'libre-bite' ),
		'features'    => array(
			'enable_product_options'  => array(
				'label'       => __( 'Produkt-Optionen', 'libre-bite' ),
				'description' => __( 'Zusatzoptionen für Produkte (Add-ons)', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
			'enable_nutritional_info' => array(
				'label'       => __( 'Nährwertangaben', 'libre-bite' ),
				'description' => __( 'Nährwertinformationen bei Produkten anzeigen', 'libre-bite' ),
				'default'     => false,
				'premium'     => false,
			),
			'enable_allergens'        => array(
				'label'       => __( 'Allergene', 'libre-bite' ),
				'description' => __( 'Allergen-Warnungen bei Produkten anzeigen', 'libre-bite' ),
				'default'     => false,
				'premium'     => false,
			),
		),
	),
	'dashboard'    => array(
		'title'       => __( 'Dashboard', 'libre-bite' ),
		'description' => __( 'Dashboard-Funktionen', 'libre-bite' ),
		'features'    => array(
			'enable_kanban_board'       => array(
				'label'       => __( 'Kanban-Board', 'libre-bite' ),
				'description' => __( 'Bestellungen als Kanban-Board anzeigen', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
			'enable_auto_status_change' => array(
				'label'       => __( 'Auto-Status', 'libre-bite' ),
				'description' => __( 'Automatischer Status-Wechsel bei Zeitüberschreitung', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
			'enable_fullscreen_mode'    => array(
				'label'       => __( 'Fullscreen-Modus', 'libre-bite' ),
				'description' => __( 'Vollbild-Ansicht für Tablets aktivieren', 'libre-bite' ),
				'default'     => true,
				'premium'     => false,
			),
		),
	),
);
?>
<div class="wrap lbite-admin-wrap">
	<h1><?php esc_html_e( 'Feature-Toggles', 'libre-bite' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Aktivieren oder deaktivieren Sie einzelne Funktionen des Libre Bites.', 'libre-bite' ); ?></p>

	<?php if ( function_exists( 'lbite_freemius' ) && ! lbite_freemius()->is_premium() ) : ?>
		<div class="lbite-upgrade-notice" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 25px; border-radius: 8px; margin: 20px 0; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
			<div style="flex: 1;">
				<h2 style="color: #fff; margin-top: 0; font-size: 22px;"><?php esc_html_e( 'Holen Sie sich Libre Bite Pro!', 'libre-bite' ); ?></h2>
				<p style="font-size: 16px; opacity: 0.9; margin-bottom: 0;">
					<?php esc_html_e( 'Schalten Sie Multi-Standort-Verwaltung, Trinkgeld-Optionen, Abhol-Erinnerungen und vieles mehr frei.', 'libre-bite' ); ?>
				</p>
			</div>
			<div style="margin-left: 20px;">
				<a href="<?php echo esc_url( lbite_freemius()->get_upgrade_url() ); ?>" class="button button-primary button-hero" style="background: #fff; color: #764ba2; border: none; font-weight: bold;">
					<?php esc_html_e( 'Jetzt upgraden', 'libre-bite' ); ?>
				</a>
			</div>
		</div>
	<?php endif; ?>

	<form id="lbite-features-form" method="post">
		<?php wp_nonce_field( 'lbite_admin_nonce', 'lbite_nonce' ); ?>

		<div class="lbite-features-grid">
			<?php foreach ( $feature_groups as $group_key => $group ) : ?>
				<div class="lbite-feature-group">
					<h2><?php echo esc_html( $group['title'] ); ?></h2>
					<p class="description"><?php echo esc_html( $group['description'] ); ?></p>

					<table class="form-table lbite-features-table">
						<tbody>
							<?php foreach ( $group['features'] as $feature_key => $feature ) : ?>
								<?php
								$is_enabled = isset( $features[ $feature_key ] ) ? $features[ $feature_key ] : $feature['default'];
								$is_premium = $feature['premium'];
								?>
								<tr class="<?php echo $is_premium ? 'lbite-premium-feature' : ''; ?>">
									<th scope="row">
										<label for="<?php echo esc_attr( $feature_key ); ?>">
											<?php echo esc_html( $feature['label'] ); ?>
											<?php if ( $is_premium ) : ?>
												<span class="lbite-premium-badge" title="<?php esc_attr_e( 'Premium-Feature', 'libre-bite' ); ?>">Premium</span>
											<?php endif; ?>
										</label>
									</th>
									<td>
										<label class="lbite-toggle">
											<input type="checkbox"
												   id="<?php echo esc_attr( $feature_key ); ?>"
												   name="features[<?php echo esc_attr( $feature_key ); ?>]"
												   value="1"
												   <?php checked( $is_enabled ); ?>>
											<span class="lbite-toggle-slider"></span>
										</label>
										<p class="description"><?php echo esc_html( $feature['description'] ); ?></p>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endforeach; ?>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Einstellungen speichern', 'libre-bite' ); ?></button>
			<span class="lbite-save-status"></span>
		</p>
	</form>

	<div class="lbite-developer-info" style="margin-top: 40px; padding: 20px; background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #72aee6; border-radius: 4px;">
		<h3><?php esc_html_e( 'Entwickler-Informationen', 'libre-bite' ); ?></h3>
		<p>
			<?php esc_html_e( 'Einige Funktionen sind als Premium markiert. Diese erfordern normalerweise ein aktives Freemius-Abonnement.', 'libre-bite' ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Pro-Modus erzwingen:', 'libre-bite' ); ?></strong><br>
			<?php esc_html_e( 'Um alle Premium-Funktionen zu Testzwecken freizuschalten, fügen Sie folgende Zeile in Ihre', 'libre-bite' ); ?> <code>wp-config.php</code> <?php esc_html_e( 'ein:', 'libre-bite' ); ?><br>
			<code>define( 'LBITE_PREMIUM_OVERRIDE', true );</code>
		</p>
	</div>
</div>


<?php ob_start(); ?>
jQuery(document).ready(function($) {
	$('#lbite-features-form').on('submit', function(e) {
		e.preventDefault();

		var $form = $(this);
		var $status = $form.find('.lbite-save-status');
		var $button = $form.find('button[type="submit"]');

		// Collect all feature states
		var features = {};
		$form.find('input[type="checkbox"]').each(function() {
			var key = $(this).attr('id');
			features[key] = $(this).is(':checked');
		});

		$button.prop('disabled', true);
		$status.text('<?php echo esc_js( __( 'Speichern...', 'libre-bite' ) ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'lbite_save_features',
				nonce: $('#lbite_nonce').val(),
				features: JSON.stringify(features)
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
