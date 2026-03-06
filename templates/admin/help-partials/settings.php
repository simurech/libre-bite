<?php
/**
 * Hilfe-Partial: Einstellungen
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_settings_url = admin_url( 'admin.php?page=lbite-settings' );
?>
<div class="lbite-help-section">
	<h2><?php esc_html_e( 'Einstellungen', 'libre-bite' ); ?></h2>
	<p><?php esc_html_e( 'Die Einstellungen sind in separate Tabs aufgeteilt – übersichtlich nach Funktion. Tabs für deaktivierte Features werden automatisch ausgeblendet.', 'libre-bite' ); ?></p>

	<!-- Tab: Allgemein -->
	<div class="lbite-help-article">
		<h3>
			<span class="dashicons dashicons-admin-settings" style="vertical-align: middle;"></span>
			<?php esc_html_e( 'Allgemein', 'libre-bite' ); ?>
		</h3>
		<p><?php esc_html_e( 'Grundlegende Einstellungen für das Plugin.', 'libre-bite' ); ?></p>
		<table class="widefat">
			<thead><tr><th><?php esc_html_e( 'Einstellung', 'libre-bite' ); ?></th><th><?php esc_html_e( 'Was es bewirkt', 'libre-bite' ); ?></th></tr></thead>
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Standort-Seite', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Die Seite, auf der deine Standorte mit dem Shortcode [lbite_location_selector] eingebunden sind. Wird u.a. als Ziel für interne Links verwendet.', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Vorbereitungszeit', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Früheste wählbare Abholzeit für Kunden. Eine Vorbereitungszeit von 30 Minuten bedeutet: der früheste Zeitslot ist in 30 Minuten ab Bestellzeitpunkt. Ausserdem werden Vorbestellungen X Minuten vor der Abholzeit automatisch in "Zubereitung" verschoben.', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Zeitslot-Intervall', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Abstand zwischen den wählbaren Abholzeiten. Bei 15 Minuten sieht der Kunde z.B. 12:00, 12:15, 12:30 usw.', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Erinnerung vor Abholung', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'X Minuten vor der gewählten Abholzeit wird dem Kunden automatisch eine Erinnerungs-E-Mail gesendet (falls aktiviert).', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Branding', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Logo, Farben und Name des Plugins im Backend anpassen – damit das System zu deinem Betrieb passt.', 'libre-bite' ); ?></td>
				</tr>
			</tbody>
		</table>
		<p style="margin-top: 8px;"><a href="<?php echo esc_url( add_query_arg( 'tab', 'general', $lbite_settings_url ) ); ?>" class="button"><?php esc_html_e( 'Allgemeine Einstellungen öffnen', 'libre-bite' ); ?></a></p>
	</div>

	<!-- Tab: Trinkgeld -->
	<?php if ( lbite_feature_enabled( 'enable_tips' ) ) : ?>
	<div class="lbite-help-article">
		<h3>
			<span class="dashicons dashicons-star-filled" style="vertical-align: middle;"></span>
			<?php esc_html_e( 'Trinkgeld', 'libre-bite' ); ?>
		</h3>
		<p><?php esc_html_e( 'Biete deinen Kunden im Checkout eine Trinkgeld-Option an. Du kannst drei Prozentsätze definieren und eine Standardauswahl festlegen.', 'libre-bite' ); ?></p>
		<p><a href="<?php echo esc_url( add_query_arg( 'tab', 'tips', $lbite_settings_url ) ); ?>" class="button"><?php esc_html_e( 'Trinkgeld-Einstellungen öffnen', 'libre-bite' ); ?></a></p>
	</div>
	<?php endif; ?>

	<!-- Tab: Checkout -->
	<?php if ( lbite_feature_enabled( 'enable_optimized_checkout' ) || lbite_feature_enabled( 'enable_rounding' ) ) : ?>
	<div class="lbite-help-article">
		<h3>
			<span class="dashicons dashicons-cart" style="vertical-align: middle;"></span>
			<?php esc_html_e( 'Checkout', 'libre-bite' ); ?>
		</h3>
		<table class="widefat">
			<thead><tr><th><?php esc_html_e( 'Einstellung', 'libre-bite' ); ?></th><th><?php esc_html_e( 'Was es bewirkt', 'libre-bite' ); ?></th></tr></thead>
			<tbody>
				<?php if ( lbite_feature_enabled( 'enable_optimized_checkout' ) ) : ?>
				<tr>
					<td><strong><?php esc_html_e( 'Checkout-Modus', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( '"Standard" zeigt den normalen WooCommerce-Checkout mit allen Adress- und Kontaktfeldern. "Optimiert" (Pro) reduziert ihn auf das Wesentliche: nur Name und Beleg-Option – ideal für Take-Away ohne Lieferung.', 'libre-bite' ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( lbite_feature_enabled( 'enable_rounding' ) ) : ?>
				<tr>
					<td><strong><?php esc_html_e( 'Gesamtbetrag runden', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Rundet den Betrag auf 5 Rappen (0.05 CHF). Empfohlen für Schweizer Betriebe, um Rundungsfehler bei Bargeldkasse zu vermeiden.', 'libre-bite' ); ?></td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<p style="margin-top: 8px;"><a href="<?php echo esc_url( add_query_arg( 'tab', 'checkout', $lbite_settings_url ) ); ?>" class="button"><?php esc_html_e( 'Checkout-Einstellungen öffnen', 'libre-bite' ); ?></a></p>
	</div>
	<?php endif; ?>

	<!-- Tab: Dashboard -->
	<?php if ( lbite_feature_enabled( 'enable_kanban_board' ) || lbite_feature_enabled( 'enable_sound_notifications' ) ) : ?>
	<div class="lbite-help-article">
		<h3>
			<span class="dashicons dashicons-clipboard" style="vertical-align: middle;"></span>
			<?php esc_html_e( 'Dashboard', 'libre-bite' ); ?>
		</h3>
		<table class="widefat">
			<thead><tr><th><?php esc_html_e( 'Einstellung', 'libre-bite' ); ?></th><th><?php esc_html_e( 'Was es bewirkt', 'libre-bite' ); ?></th></tr></thead>
			<tbody>
				<?php if ( lbite_feature_enabled( 'enable_kanban_board' ) ) : ?>
				<tr>
					<td><strong><?php esc_html_e( 'Aktualisierungsintervall', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Wie oft die Bestellübersicht im Hintergrund nach neuen Bestellungen prüft (in Sekunden). Empfehlung: 30 Sekunden.', 'libre-bite' ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( lbite_feature_enabled( 'enable_sound_notifications' ) ) : ?>
				<tr>
					<td><strong><?php esc_html_e( 'Benachrichtigungssound', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Der Sound, der bei neuen Bestellungen abgespielt wird. Du kannst einen eigenen Sound aus der Mediathek wählen oder den Standard-Sound verwenden.', 'libre-bite' ); ?></td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<p style="margin-top: 8px;"><a href="<?php echo esc_url( add_query_arg( 'tab', 'orders_settings', $lbite_settings_url ) ); ?>" class="button"><?php esc_html_e( 'Bestellübersicht-Einstellungen öffnen', 'libre-bite' ); ?></a></p>
	</div>
	<?php endif; ?>

	<!-- Tab: Kassensystem -->
	<?php if ( lbite_feature_enabled( 'enable_pos' ) ) : ?>
	<div class="lbite-help-article">
		<h3>
			<span class="dashicons dashicons-store" style="vertical-align: middle;"></span>
			<?php esc_html_e( 'Kassensystem', 'libre-bite' ); ?>
		</h3>
		<p><?php esc_html_e( 'Lege fest, welche Zahlungsarten im POS-Zahlungs-Modal angezeigt werden (Bar, Karte, Twint, Andere). Du kannst Zahlungsarten deaktivieren und die Bezeichnungen anpassen.', 'libre-bite' ); ?></p>
		<p><a href="<?php echo esc_url( add_query_arg( 'tab', 'pos', $lbite_settings_url ) ); ?>" class="button"><?php esc_html_e( 'Kassensystem-Einstellungen öffnen', 'libre-bite' ); ?></a></p>
	</div>
	<?php endif; ?>

	<!-- Tab: Checkout (Felder + Optimierter Checkout) -->
	<div class="lbite-help-article">
		<h3>
			<span class="dashicons dashicons-forms" style="vertical-align: middle;"></span>
			<?php esc_html_e( 'Checkout-Felder', 'libre-bite' ); ?>
		</h3>
		<p><?php esc_html_e( 'Wähle, welche Informationen der Kunde beim Bestellen angeben muss. Ein Haken bedeutet: Das Feld wird angezeigt. Kein Haken: Das Feld wird ausgeblendet.', 'libre-bite' ); ?></p>
		<p><?php esc_html_e( 'Für eine schnelle Bestellung reicht oft nur der Vorname – alles andere kann ausgeblendet werden.', 'libre-bite' ); ?></p>
		<p><a href="<?php echo esc_url( add_query_arg( 'tab', 'checkout', $lbite_settings_url ) ); ?>" class="button"><?php esc_html_e( 'Checkout-Einstellungen öffnen', 'libre-bite' ); ?></a></p>
	</div>

	<!-- Tab: Features -->
	<?php if ( current_user_can( 'manage_options' ) ) : ?>
	<div class="lbite-help-article">
		<h3>
			<span class="dashicons dashicons-admin-plugins" style="vertical-align: middle;"></span>
			<?php esc_html_e( 'Features', 'libre-bite' ); ?>
		</h3>
		<p><?php esc_html_e( 'Aktiviere oder deaktiviere einzelne Funktionsmodule von Libre Bite. Deaktivierte Features werden aus dem Menü ausgeblendet – auch die dazugehörigen Einstellungs-Tabs verschwinden.', 'libre-bite' ); ?></p>
		<p><?php esc_html_e( 'Pro-Features sind mit einem Hinweis markiert und erfordern eine aktive Lizenz oder einen Trial.', 'libre-bite' ); ?></p>
		<p><a href="<?php echo esc_url( add_query_arg( 'tab', 'features', $lbite_settings_url ) ); ?>" class="button"><?php esc_html_e( 'Features öffnen', 'libre-bite' ); ?></a></p>
	</div>
	<?php endif; ?>
</div>
