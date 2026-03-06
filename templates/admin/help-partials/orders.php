<?php
/**
 * Hilfe-Partial: Bestellungen
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lbite-help-section">
	<h2><?php esc_html_e( 'Bestellungen mit Libre Bite', 'libre-bite' ); ?></h2>
	<p><?php esc_html_e( 'Libre Bite fügt deinem WooCommerce-Shop eine vollständige Bestellverwaltung für Gastronomiebetriebe hinzu. Neue Bestellungen erscheinen automatisch in der Bestellübersicht – ohne dass du WooCommerce-Bestellungen manuell durchsuchen musst.', 'libre-bite' ); ?></p>

	<!-- Wie läuft eine Bestellung ab? -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Wie läuft eine Bestellung ab?', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Der typische Ablauf einer Online-Bestellung:', 'libre-bite' ); ?></p>
		<ol>
			<li><strong><?php esc_html_e( 'Kunde bestellt online', 'libre-bite' ); ?></strong> – <?php esc_html_e( 'Über die Webseite wählt der Kunde Produkte, einen Standort und eine Abholzeit.', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Bestellung erscheint im Dashboard', 'libre-bite' ); ?></strong> – <?php esc_html_e( 'Die Bestellung erscheint sofort in der Spalte "Eingang" der Bestellübersicht. Optional wird ein Sound abgespielt.', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Personal bereitet vor', 'libre-bite' ); ?></strong> – <?php esc_html_e( 'Bestellung wird auf "Zubereitung" verschoben. Bei Vorbestellungen passiert das automatisch X Minuten vor der Abholzeit.', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Bestellung ist bereit', 'libre-bite' ); ?></strong> – <?php esc_html_e( 'Status auf "Bereit" setzen. Optional erhält der Kunde automatisch eine Erinnerungs-E-Mail.', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Abholung', 'libre-bite' ); ?></strong> – <?php esc_html_e( 'Nach der Abholung wird die Bestellung auf "Abgeholt" gesetzt und verschwindet aus der aktiven Ansicht.', 'libre-bite' ); ?></li>
		</ol>
	</div>

	<!-- Bestellübersicht (Kanban) -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Die Bestellübersicht (Kanban-Board)', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Das Kanban-Board ist deine Echtzeit-Übersicht über alle laufenden Bestellungen. Du siehst auf einen Blick, was gerade ankommt, was in Arbeit ist und was bereit zur Abholung steht.', 'libre-bite' ); ?></p>

		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Spalte', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Bedeutung', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Nächster Schritt', 'libre-bite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Eingang', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Neue Bestellungen, die noch nicht bearbeitet wurden. Neue Bestellungen werden hier farblich hervorgehoben.', 'libre-bite' ); ?></td>
					<td><?php esc_html_e( 'Bestellung ansehen und mit der Zubereitung beginnen.', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Zubereitung', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Bestellungen, die gerade zubereitet werden. Vorbestellungen werden automatisch kurz vor der Abholzeit hier abgelegt.', 'libre-bite' ); ?></td>
					<td><?php esc_html_e( 'Fertigmelden, sobald die Bestellung abholbereit ist.', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Bereit', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Bestellungen warten auf den Kunden. Kundin wird ggf. per E-Mail informiert.', 'libre-bite' ); ?></td>
					<td><?php esc_html_e( 'Nach der Abholung als "Abgeholt" markieren.', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Abgeholt', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Erledigte Bestellungen des heutigen Tages. Ältere Bestellungen werden separat gespeichert.', 'libre-bite' ); ?></td>
					<td><?php esc_html_e( 'Erledigt.', 'libre-bite' ); ?></td>
				</tr>
			</tbody>
		</table>

		<h4 style="margin-top: 16px;"><?php esc_html_e( 'Status verschieben', 'libre-bite' ); ?></h4>
		<ul>
			<li><strong><?php esc_html_e( 'Drag & Drop:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Bestellkarte in die nächste Spalte ziehen – ideal auf Touchscreens und Tablets.', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Button:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Den Status-Button direkt auf der Bestellkarte anklicken.', 'libre-bite' ); ?></li>
		</ul>

		<div class="lbite-help-tip">
			<span class="dashicons dashicons-lightbulb"></span>
			<p><?php esc_html_e( 'Tipp: Das Dashboard aktualisiert sich automatisch. Du musst die Seite nicht manuell neu laden, um neue Bestellungen zu sehen. Das Intervall kannst du unter Einstellungen → Dashboard anpassen.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<!-- Sound-Benachrichtigungen -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Sound-Benachrichtigungen bei neuen Bestellungen', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Sobald eine neue Bestellung eingeht, spielt das Dashboard automatisch einen Signalton ab – damit du keine Bestellung übersiehst, auch wenn du gerade nicht auf den Bildschirm schaust.', 'libre-bite' ); ?></p>
		<ul>
			<li><?php esc_html_e( 'Der Sound spielt nur, wenn der Browser-Tab mit dem Dashboard geöffnet ist.', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Manche Browser erlauben Sounds erst nach einer Interaktion (z.B. einmal auf die Seite klicken).', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Den Sound kannst du unter Einstellungen → Dashboard anpassen oder deaktivieren.', 'libre-bite' ); ?></li>
		</ul>
	</div>

	<!-- Vorbestellungen -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Vorbestellungen (geplante Abholzeiten)', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Kunden können beim Checkout eine bestimmte Abholzeit wählen – z.B. "heute um 12:30 Uhr". Libre Bite verwaltet diese Vorbestellungen automatisch:', 'libre-bite' ); ?></p>
		<ul>
			<li><?php esc_html_e( 'Vorbestellungen erscheinen zunächst im "Eingang" mit Angabe der Abholzeit.', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'X Minuten vor der Abholzeit (konfigurierbar in Einstellungen → Allgemein) werden sie automatisch in die Spalte "Zubereitung" verschoben.', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'So weisst du immer genau, wann du mit der Zubereitung beginnen musst.', 'libre-bite' ); ?></li>
		</ul>
	</div>

	<!-- POS -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Kassensystem (POS) für Bestellungen vor Ort', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Das Kassensystem ermöglicht es, Bestellungen direkt an der Kasse entgegenzunehmen – z.B. für Laufkundschaft, Telefonbestellungen oder Tischbestellungen.', 'libre-bite' ); ?></p>

		<h4><?php esc_html_e( 'So funktioniert der POS', 'libre-bite' ); ?></h4>
		<ol>
			<li><?php esc_html_e( 'Standort wählen (falls mehrere Standorte vorhanden)', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Produkte durch Antippen in den Warenkorb legen – inkl. Varianten und Produkt-Optionen', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Optional: Kundennamen und Tisch eingeben', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( '"Bestellung abschliessen" → Zahlungsart wählen (Bar, Karte, Twint etc.)', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Die Bestellung erscheint sofort im Kanban-Board unter "Eingang"', 'libre-bite' ); ?></li>
		</ol>

		<div class="lbite-help-tip">
			<span class="dashicons dashicons-lightbulb"></span>
			<p><?php esc_html_e( 'Tipp: Das Kassensystem ist für die Nutzung auf Tablets oder einem zweiten Monitor optimiert – so kann das Personal unabhängig vom Dashboard arbeiten.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-order-board' ) ); ?>" class="button button-primary">
		<?php esc_html_e( 'Zur Bestellübersicht', 'libre-bite' ); ?>
	</a>
	<?php if ( lbite_feature_enabled( 'enable_pos' ) ) : ?>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-pos' ) ); ?>" class="button" style="margin-left: 8px;">
		<?php esc_html_e( 'Zum Kassensystem', 'libre-bite' ); ?>
	</a>
	<?php endif; ?>
</div>
