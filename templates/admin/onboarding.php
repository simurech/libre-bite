<?php
/**
 * Onboarding-Seite – Ersteinrichtung nach Aktivierung
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Aktuelle Feature-Einstellungen laden (Standard: alle false bei Erstinstallation)
$current_features = get_option( 'lbite_features', array() );

// Feature-Gruppen mit Metadaten für die Darstellung
$feature_groups = array(
	array(
		'label'    => __( 'Bestellsystem', 'libre-bite' ),
		'icon'     => '🍽️',
		'features' => array(
			array(
				'key'         => 'enable_kanban_board',
				'label'       => __( 'Bestellübersicht (Kanban)', 'libre-bite' ),
				'description' => __( 'Visuelles Kanban-Board für eingehende Bestellungen mit Status-Tracking.', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_pos',
				'label'       => __( 'Kassensystem (POS)', 'libre-bite' ),
				'description' => __( 'Internes Kassensystem für Bestellungen direkt vor Ort.', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_auto_status_change',
				'label'       => __( 'Automatische Statusänderung', 'libre-bite' ),
				'description' => __( 'Vorbestellungen werden automatisch zur Vorbereitung verschoben.', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_scheduled_orders',
				'label'       => __( 'Vorbestellungen', 'libre-bite' ),
				'description' => __( 'Kunden können Abholzeiten im Voraus wählen.', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_order_notes',
				'label'       => __( 'Bestellnotizen', 'libre-bite' ),
				'description' => __( 'Kunden können Sonderwünsche als Notiz angeben.', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_order_cancellation',
				'label'       => __( 'Bestellstornierung', 'libre-bite' ),
				'description' => __( 'Bestellungen können über das Dashboard storniert werden.', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_fullscreen_mode',
				'label'       => __( 'Vollbild-Modus', 'libre-bite' ),
				'description' => __( 'POS und Dashboard im Vollbild betreiben.', 'libre-bite' ),
				'pro'         => false,
			),
		),
	),
	array(
		'label'    => __( 'Checkout', 'libre-bite' ),
		'icon'     => '🛒',
		'features' => array(
			array(
				'key'         => 'enable_optimized_checkout',
				'label'       => __( 'Optimierter Checkout', 'libre-bite' ),
				'description' => __( 'Vereinfachter Checkout-Prozess speziell für Gastronomie.', 'libre-bite' ),
				'pro'         => true,
			),
			array(
				'key'         => 'enable_tips',
				'label'       => __( 'Trinkgeld', 'libre-bite' ),
				'description' => __( 'Kunden können beim Checkout ein Trinkgeld auswählen.', 'libre-bite' ),
				'pro'         => true,
			),
			array(
				'key'         => 'enable_rounding',
				'label'       => __( '5-Rappen-Rundung', 'libre-bite' ),
				'description' => __( 'Beträge auf 5 Rappen runden (CH).', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_guest_checkout',
				'label'       => __( 'Gast-Checkout', 'libre-bite' ),
				'description' => __( 'Bestellungen ohne Kundenkonto ermöglichen.', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_email_field',
				'label'       => __( 'E-Mail-Feld', 'libre-bite' ),
				'description' => __( 'E-Mail-Adresse beim Checkout abfragen.', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_phone_field',
				'label'       => __( 'Telefon-Feld', 'libre-bite' ),
				'description' => __( 'Telefonnummer beim Checkout abfragen.', 'libre-bite' ),
				'pro'         => false,
			),
		),
	),
	array(
		'label'    => __( 'Standorte', 'libre-bite' ),
		'icon'     => '📍',
		'features' => array(
			array(
				'key'         => 'enable_location_selector',
				'label'       => __( 'Standortauswahl', 'libre-bite' ),
				'description' => __( 'Kunden wählen beim Bestellen ihren Abholstandort.', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_opening_hours',
				'label'       => __( 'Öffnungszeiten', 'libre-bite' ),
				'description' => __( 'Bestellungen nur während der konfigurierten Öffnungszeiten erlauben.', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_multi_location',
				'label'       => __( 'Multi-Standort', 'libre-bite' ),
				'description' => __( 'Mehrere Standorte mit eigenen Einstellungen verwalten.', 'libre-bite' ),
				'pro'         => true,
			),
		),
	),
	array(
		'label'    => __( 'Benachrichtigungen', 'libre-bite' ),
		'icon'     => '🔔',
		'features' => array(
			array(
				'key'         => 'enable_admin_email',
				'label'       => __( 'Admin-E-Mail', 'libre-bite' ),
				'description' => __( 'E-Mail-Benachrichtigung bei neuen Bestellungen.', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_sound_notifications',
				'label'       => __( 'Sound-Benachrichtigungen', 'libre-bite' ),
				'description' => __( 'Akustisches Signal bei neuen Bestellungen im Dashboard.', 'libre-bite' ),
				'pro'         => true,
			),
			array(
				'key'         => 'enable_pickup_reminders',
				'label'       => __( 'Abhol-Erinnerungen', 'libre-bite' ),
				'description' => __( 'Automatische E-Mail-Erinnerung an Kunden kurz vor der Abholzeit.', 'libre-bite' ),
				'pro'         => true,
			),
		),
	),
	array(
		'label'    => __( 'Produkte', 'libre-bite' ),
		'icon'     => '🧩',
		'features' => array(
			array(
				'key'         => 'enable_product_options',
				'label'       => __( 'Produkt-Optionen', 'libre-bite' ),
				'description' => __( 'Zusatzoptionen und Extras pro Produkt konfigurieren (z.B. Saucen, Beilagen).', 'libre-bite' ),
				'pro'         => false,
			),
			array(
				'key'         => 'enable_nutritional_info',
				'label'       => __( 'Nährwertangaben', 'libre-bite' ),
				'description' => __( 'Kalorien und Nährwerte pro Produkt anzeigen.', 'libre-bite' ),
				'pro'         => true,
			),
			array(
				'key'         => 'enable_allergens',
				'label'       => __( 'Allergene', 'libre-bite' ),
				'description' => __( 'Allergenkennzeichnung nach EU-Lebensmittelinformationsverordnung.', 'libre-bite' ),
				'pro'         => true,
			),
		),
	),
);

// Prüfen ob Premium aktiv
$is_premium = function_exists( 'lbite_freemius' ) && lbite_freemius()->is_premium();
?>
<div class="lbite-onboarding-page">

	<div class="lbite-onboarding-header">
		<div class="lbite-onboarding-header-inner">
			<h1><?php esc_html_e( 'Willkommen bei Libre Bite!', 'libre-bite' ); ?></h1>
			<p><?php esc_html_e( 'Aktiviere die Funktionen, die du für dein Restaurant oder Café brauchst. Du kannst diese Auswahl jederzeit unter Feature-Toggles anpassen.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<div class="lbite-onboarding-body">

		<?php foreach ( $feature_groups as $group ) : ?>
		<div class="lbite-onboarding-group">
			<h2 class="lbite-onboarding-group-title">
				<span class="lbite-onboarding-group-icon"><?php echo esc_html( $group['icon'] ); ?></span>
				<?php echo esc_html( $group['label'] ); ?>
			</h2>
			<div class="lbite-onboarding-cards">
				<?php foreach ( $group['features'] as $feature ) :
					$is_enabled = isset( $current_features[ $feature['key'] ] ) ? (bool) $current_features[ $feature['key'] ] : false;
					$is_pro_locked = $feature['pro'] && ! $is_premium;
				?>
				<div class="lbite-onboarding-card <?php echo $is_pro_locked ? 'lbite-onboarding-card--pro' : ''; ?>" data-feature="<?php echo esc_attr( $feature['key'] ); ?>">
					<?php if ( $feature['pro'] ) : ?>
					<span class="lbite-onboarding-pro-badge"><?php esc_html_e( 'Pro', 'libre-bite' ); ?></span>
					<?php endif; ?>

					<div class="lbite-onboarding-card-header">
						<span class="lbite-onboarding-card-label"><?php echo esc_html( $feature['label'] ); ?></span>
						<button
							type="button"
							class="lbite-onboarding-toggle <?php echo $is_enabled ? 'is-active' : ''; ?> <?php echo $is_pro_locked ? 'is-locked' : ''; ?>"
							data-feature="<?php echo esc_attr( $feature['key'] ); ?>"
							aria-pressed="<?php echo $is_enabled ? 'true' : 'false'; ?>"
							<?php echo $is_pro_locked ? 'disabled' : ''; ?>
						>
							<span class="lbite-onboarding-toggle-track">
								<span class="lbite-onboarding-toggle-thumb"></span>
							</span>
							<span class="lbite-onboarding-toggle-label">
								<?php echo $is_enabled ? esc_html__( 'AN', 'libre-bite' ) : esc_html__( 'AUS', 'libre-bite' ); ?>
							</span>
						</button>
					</div>
					<p class="lbite-onboarding-card-description"><?php echo esc_html( $feature['description'] ); ?></p>

					<?php if ( $is_pro_locked ) : ?>
					<div class="lbite-onboarding-pro-notice">
						<?php
						printf(
							/* translators: %s: URL zu Upgrade-Seite */
							'<a href="%s" target="_blank">%s</a>',
							esc_url( admin_url( 'admin.php?page=lbite-pricing' ) ),
							esc_html__( 'Pro-Version freischalten →', 'libre-bite' )
						);
						?>
					</div>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endforeach; ?>

		<div class="lbite-onboarding-next-steps">
			<h2><?php esc_html_e( 'Nächste Schritte', 'libre-bite' ); ?></h2>
			<ul class="lbite-onboarding-checklist">
				<li>
					<span class="lbite-onboarding-check-icon">☐</span>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=lbite_location' ) ); ?>">
						<?php esc_html_e( 'Ersten Standort anlegen', 'libre-bite' ); ?>
					</a>
					<span class="lbite-onboarding-check-hint"><?php esc_html_e( 'Ohne Standort können keine Bestellungen angenommen werden.', 'libre-bite' ); ?></span>
				</li>
				<li>
					<span class="lbite-onboarding-check-icon">☐</span>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-settings' ) ); ?>">
						<?php esc_html_e( 'Öffnungszeiten und Zeitslots konfigurieren', 'libre-bite' ); ?>
					</a>
					<span class="lbite-onboarding-check-hint"><?php esc_html_e( 'Lege fest, wann Bestellungen möglich sind.', 'libre-bite' ); ?></span>
				</li>
				<li>
					<span class="lbite-onboarding-check-icon">☐</span>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-order-board' ) ); ?>">
						<?php esc_html_e( 'Bestellübersicht ausprobieren', 'libre-bite' ); ?>
					</a>
					<span class="lbite-onboarding-check-hint"><?php esc_html_e( 'Hier siehst du alle eingehenden Bestellungen.', 'libre-bite' ); ?></span>
				</li>
			</ul>
		</div>

		<div class="lbite-onboarding-footer">
			<button type="button" id="lbite-onboarding-complete" class="button button-primary button-hero">
				<?php esc_html_e( 'Einrichtung abschliessen', 'libre-bite' ); ?>
				<span class="dashicons dashicons-arrow-right-alt" style="margin-left: 5px; margin-top: 2px;"></span>
			</button>
			<p class="lbite-onboarding-footer-hint">
				<?php esc_html_e( 'Du kannst alle Einstellungen jederzeit unter Feature-Toggles ändern.', 'libre-bite' ); ?>
			</p>
		</div>

	</div>
</div>
