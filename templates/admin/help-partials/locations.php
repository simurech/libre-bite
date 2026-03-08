<?php
/**
 * Hilfe-Partial: Standorte
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lbite-help-section">
	<h2><?php esc_html_e( 'Standorte verwalten', 'libre-bite' ); ?></h2>

	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Standort erstellen', 'libre-bite' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Gehen Sie zu "Libre Bite" → "Standorte"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Klicken Sie auf "Erstellen"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Geben Sie den Namen des Standorts ein', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Füllen Sie die Standort-Details aus:', 'libre-bite' ); ?>
				<ul>
					<li><?php esc_html_e( 'Adresse', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Telefon/E-Mail', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Öffnungszeiten', 'libre-bite' ); ?></li>
				</ul>
			</li>
			<li><?php esc_html_e( 'Veröffentlichen Sie den Standort', 'libre-bite' ); ?></li>
		</ol>
	</div>

	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Öffnungszeiten konfigurieren', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Die Öffnungszeiten bestimmen, wann Bestellungen aufgegeben werden können.', 'libre-bite' ); ?></p>

		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Feld', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Beschreibung', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Beispiel', 'libre-bite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Öffnet um', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Uhrzeit, ab der Bestellungen möglich sind', 'libre-bite' ); ?></td>
					<td>08:00</td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Schliesst um', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Uhrzeit, bis zu der Bestellungen möglich sind', 'libre-bite' ); ?></td>
					<td>18:00</td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Geschlossen', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Tag als Ruhetag markieren', 'libre-bite' ); ?></td>
					<td><?php esc_html_e( 'Sonntag', 'libre-bite' ); ?></td>
				</tr>
			</tbody>
		</table>

		<div class="lbite-help-tip">
			<span class="dashicons dashicons-lightbulb"></span>
			<p><?php esc_html_e( 'Tipp: Lassen Sie die Öffnungszeiten eines Tages leer, wenn der Standort an diesem Tag geschlossen ist.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Standort im Checkout', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Kunden wählen beim Checkout einen Standort aus. Die verfügbaren Abholzeiten werden automatisch basierend auf den Öffnungszeiten berechnet.', 'libre-bite' ); ?></p>

		<p><?php esc_html_e( 'Folgende Faktoren beeinflussen die Zeitauswahl:', 'libre-bite' ); ?></p>
		<ul>
			<li><strong><?php esc_html_e( 'Vorbereitungszeit:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Mindestzeit bis zur Abholung (z.B. 30 Minuten)', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Zeitslot-Intervall:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Abstand zwischen den Abholzeiten (z.B. alle 15 Minuten)', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Öffnungszeiten:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Nur Zeiten während der Öffnungszeiten werden angeboten', 'libre-bite' ); ?></li>
		</ul>
	</div>

	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Standort-Auswahl einbinden', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Mit dem folgenden Shortcode können Kunden einen Standort direkt auf einer Seite oder im Checkout wählen:', 'libre-bite' ); ?></p>

		<p><?php esc_html_e( 'Den Shortcode können Sie auf jeder beliebigen Seite (z.B. der Bestellseite oder Homepage) einfügen. Die gewählte Standort-Auswahl wird in der Session gespeichert und beim Checkout übernommen.', 'libre-bite' ); ?></p>

		<h4><?php esc_html_e( 'Einfache Verwendung', 'libre-bite' ); ?></h4>
		<div style="background: #f6f7f7; border: 1px solid #ccd0d4; border-radius: 3px; padding: 12px 16px; margin: 8px 0; font-family: monospace; font-size: 14px;">
			[lbite_location_selector]
		</div>

		<h4><?php esc_html_e( 'Parameter', 'libre-bite' ); ?></h4>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Parameter', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Mögliche Werte', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Standard', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Beschreibung', 'libre-bite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>style</code></td>
					<td><code>tiles</code>, <code>inline</code></td>
					<td><code>tiles</code></td>
					<td><?php esc_html_e( 'Darstellung: Kacheln (nebeneinander) oder Inline (kompakt)', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><code>show_time</code></td>
					<td><code>yes</code>, <code>no</code></td>
					<td><code>yes</code></td>
					<td><?php esc_html_e( 'Zeitauswahl anzeigen oder ausblenden', 'libre-bite' ); ?></td>
				</tr>
			</tbody>
		</table>

		<h4><?php esc_html_e( 'Beispiele', 'libre-bite' ); ?></h4>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Shortcode', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Ergebnis', 'libre-bite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>[lbite_location_selector]</code></td>
					<td><?php esc_html_e( 'Kachel-Ansicht mit Standort- und Zeitauswahl', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><code>[lbite_location_selector style="inline"]</code></td>
					<td><?php esc_html_e( 'Kompakte Inline-Ansicht mit Zeitauswahl', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><code>[lbite_location_selector show_time="no"]</code></td>
					<td><?php esc_html_e( 'Nur Standortauswahl, ohne Zeitauswahl', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><code>[lbite_location_selector style="inline" show_time="no"]</code></td>
					<td><?php esc_html_e( 'Kompakt, nur Standortauswahl', 'libre-bite' ); ?></td>
				</tr>
			</tbody>
		</table>

		<h4><?php esc_html_e( 'URL-Parameter (Deep-Links)', 'libre-bite' ); ?></h4>
		<p><?php esc_html_e( 'Sie können Standort und Bestelltyp auch direkt per URL vorauswählen – nützlich für QR-Codes, Flyer oder Links.', 'libre-bite' ); ?></p>

		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'URL-Parameter', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Mögliche Werte', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Beschreibung', 'libre-bite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>?location=ID</code></td>
					<td><?php esc_html_e( 'Standort-ID (Zahl)', 'libre-bite' ); ?></td>
					<td><?php esc_html_e( 'Wählt den Standort automatisch vor. Die ID finden Sie in der URL beim Bearbeiten des Standorts.', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><code>?order_type=now</code></td>
					<td><code>now</code></td>
					<td><?php esc_html_e( 'Setzt den Bestelltyp auf "Sofort" (schnellstmögliche Abholzeit).', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><code>?order_type=later</code></td>
					<td><code>later</code></td>
					<td><?php esc_html_e( 'Setzt den Bestelltyp auf "Vorbestellung" (Kunde wählt Abholzeit selbst).', 'libre-bite' ); ?></td>
				</tr>
			</tbody>
		</table>

		<p style="margin-top: 8px;"><?php esc_html_e( 'Beispiel-URL:', 'libre-bite' ); ?></p>
		<div style="background: #f6f7f7; border: 1px solid #ccd0d4; border-radius: 3px; padding: 10px 14px; font-family: monospace; font-size: 13px; word-break: break-all;">
			<?php echo esc_html( home_url( '/bestellen/?location=5&order_type=now' ) ); ?>
		</div>

		<div class="lbite-help-tip">
			<span class="dashicons dashicons-lightbulb"></span>
			<p><?php esc_html_e( 'Tipp: Aktivieren Sie das Feature "Standort-Auswahl" unter Einstellungen → Features, damit der Shortcode sichtbar ist.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Standort-Farbe', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Jedem Standort kann eine Akzentfarbe zugewiesen werden. Diese wird in folgenden Bereichen als visuelle Hervorhebung angezeigt:', 'libre-bite' ); ?></p>
		<ul>
			<li><strong><?php esc_html_e( 'Kassensystem (POS):', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Das Standort-Dropdown erhält einen farbigen Rahmen passend zur gewählten Farbe.', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Bestellübersicht:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Das Standort-Dropdown in der Bestellübersicht wird ebenfalls farbig hervorgehoben.', 'libre-bite' ); ?></li>
		</ul>
		<p><?php esc_html_e( 'Die Farbe wird im Standort-Bearbeitungsbereich unter "Farbe" mit dem WordPress-Farbwähler festgelegt.', 'libre-bite' ); ?></p>
	</div>

	<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lbite_location' ) ); ?>" class="button button-primary">
		<?php esc_html_e( 'Standorte verwalten', 'libre-bite' ); ?>
	</a>
</div>

