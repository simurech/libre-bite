<?php
/**
 * Feature-Toggles (Super-Admin Einstellungen)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$features = get_option( 'lb_features', array() );

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
<div class="wrap lb-admin-wrap">
	<h1><?php esc_html_e( 'Feature-Toggles', 'libre-bite' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Aktivieren oder deaktivieren Sie einzelne Funktionen des Libre Bites.', 'libre-bite' ); ?></p>

	<form id="lb-features-form" method="post">
		<?php wp_nonce_field( 'lb_admin_nonce', 'lb_nonce' ); ?>

		<div class="lb-features-grid">
			<?php foreach ( $feature_groups as $group_key => $group ) : ?>
				<div class="lb-feature-group">
					<h2><?php echo esc_html( $group['title'] ); ?></h2>
					<p class="description"><?php echo esc_html( $group['description'] ); ?></p>

					<table class="form-table lb-features-table">
						<tbody>
							<?php foreach ( $group['features'] as $feature_key => $feature ) : ?>
								<?php
								$is_enabled = isset( $features[ $feature_key ] ) ? $features[ $feature_key ] : $feature['default'];
								$is_premium = $feature['premium'];
								?>
								<tr class="<?php echo $is_premium ? 'lb-premium-feature' : ''; ?>">
									<th scope="row">
										<label for="<?php echo esc_attr( $feature_key ); ?>">
											<?php echo esc_html( $feature['label'] ); ?>
											<?php if ( $is_premium ) : ?>
												<span class="lb-premium-badge" title="<?php esc_attr_e( 'Premium-Feature', 'libre-bite' ); ?>">Premium</span>
											<?php endif; ?>
										</label>
									</th>
									<td>
										<label class="lb-toggle">
											<input type="checkbox"
												   id="<?php echo esc_attr( $feature_key ); ?>"
												   name="features[<?php echo esc_attr( $feature_key ); ?>]"
												   value="1"
												   <?php checked( $is_enabled ); ?>>
											<span class="lb-toggle-slider"></span>
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
			<span class="lb-save-status"></span>
		</p>
	</form>

	<div class="lb-developer-info" style="margin-top: 40px; padding: 20px; background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #72aee6; border-radius: 4px;">
		<h3><?php esc_html_e( 'Entwickler-Informationen', 'libre-bite' ); ?></h3>
		<p>
			<?php esc_html_e( 'Einige Funktionen sind als Premium markiert. Diese erfordern normalerweise ein aktives Freemius-Abonnement.', 'libre-bite' ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Pro-Modus erzwingen:', 'libre-bite' ); ?></strong><br>
			<?php esc_html_e( 'Um alle Premium-Funktionen zu Testzwecken freizuschalten, fügen Sie folgende Zeile in Ihre', 'libre-bite' ); ?> <code>wp-config.php</code> <?php esc_html_e( 'ein:', 'libre-bite' ); ?><br>
			<code>define( 'LB_PREMIUM_OVERRIDE', true );</code>
		</p>
	</div>
</div>

<style>
.lb-features-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
	gap: 20px;
	margin-top: 20px;
}

.lb-feature-group {
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 4px;
	padding: 20px;
}

.lb-feature-group h2 {
	margin-top: 0;
	padding-bottom: 10px;
	border-bottom: 1px solid #eee;
}

.lb-features-table {
	margin-top: 0;
}

.lb-features-table th {
	width: 200px;
	padding: 12px 10px 12px 0;
	vertical-align: top;
}

.lb-features-table td {
	padding: 12px 10px;
}

.lb-premium-badge {
	display: inline-block;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: #fff;
	font-size: 10px;
	font-weight: 600;
	padding: 2px 6px;
	border-radius: 3px;
	margin-left: 5px;
	vertical-align: middle;
	text-transform: uppercase;
}

.lb-premium-feature {
	background: #f9f9ff;
}

/* Toggle Switch */
.lb-toggle {
	position: relative;
	display: inline-block;
	width: 50px;
	height: 26px;
	vertical-align: middle;
}

.lb-toggle input {
	opacity: 0;
	width: 0;
	height: 0;
}

.lb-toggle-slider {
	position: absolute;
	cursor: pointer;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background-color: #ccc;
	transition: .3s;
	border-radius: 26px;
}

.lb-toggle-slider:before {
	position: absolute;
	content: "";
	height: 20px;
	width: 20px;
	left: 3px;
	bottom: 3px;
	background-color: white;
	transition: .3s;
	border-radius: 50%;
}

.lb-toggle input:checked + .lb-toggle-slider {
	background-color: #2271b1;
}

.lb-toggle input:checked + .lb-toggle-slider:before {
	transform: translateX(24px);
}

.lb-toggle input:focus + .lb-toggle-slider {
	box-shadow: 0 0 1px #2271b1;
}

.lb-save-status {
	margin-left: 10px;
	color: #00a32a;
}

@media screen and (max-width: 960px) {
	.lb-features-grid {
		grid-template-columns: 1fr;
	}
}
</style>

<script>
jQuery(document).ready(function($) {
	$('#lb-features-form').on('submit', function(e) {
		e.preventDefault();

		var $form = $(this);
		var $status = $form.find('.lb-save-status');
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
				action: 'lb_save_features',
				nonce: $('#lb_nonce').val(),
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
</script>
