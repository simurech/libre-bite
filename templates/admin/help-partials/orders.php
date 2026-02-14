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
	<h2><?php esc_html_e( 'Bestellungen verwalten', 'libre-bite' ); ?></h2>

	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Bestellübersicht (Kanban-Board)', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Das Kanban-Board zeigt alle Bestellungen in vier Spalten:', 'libre-bite' ); ?></p>

		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Status', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Beschreibung', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Aktion', 'libre-bite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><span class="lbite-status-badge lbite-status-incoming"><?php esc_html_e( 'Eingehend', 'libre-bite' ); ?></span></td>
					<td><?php esc_html_e( 'Neue Bestellungen, die noch nicht bearbeitet wurden', 'libre-bite' ); ?></td>
					<td><?php esc_html_e( 'Bestellung annehmen und mit Zubereitung beginnen', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><span class="lbite-status-badge lbite-status-preparing"><?php esc_html_e( 'In Bearbeitung', 'libre-bite' ); ?></span></td>
					<td><?php esc_html_e( 'Bestellungen werden gerade zubereitet', 'libre-bite' ); ?></td>
					<td><?php esc_html_e( 'Bei Fertigstellung als "Bereit" markieren', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><span class="lbite-status-badge lbite-status-ready"><?php esc_html_e( 'Bereit', 'libre-bite' ); ?></span></td>
					<td><?php esc_html_e( 'Bestellungen sind fertig zur Abholung', 'libre-bite' ); ?></td>
					<td><?php esc_html_e( 'Bei Abholung durch Kunden als "Abgeholt" markieren', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><span class="lbite-status-badge lbite-status-completed"><?php esc_html_e( 'Abgeholt', 'libre-bite' ); ?></span></td>
					<td><?php esc_html_e( 'Bestellungen wurden vom Kunden abgeholt', 'libre-bite' ); ?></td>
					<td><?php esc_html_e( 'Abgeschlossen', 'libre-bite' ); ?></td>
				</tr>
			</tbody>
		</table>

		<h4><?php esc_html_e( 'Status ändern', 'libre-bite' ); ?></h4>
		<p><?php esc_html_e( 'Es gibt zwei Möglichkeiten, den Status zu ändern:', 'libre-bite' ); ?></p>
		<ol>
			<li><strong><?php esc_html_e( 'Drag & Drop:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Ziehen Sie die Bestellkarte in die gewünschte Spalte', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Button-Klick:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Klicken Sie auf den Status-Button in der Bestellkarte', 'libre-bite' ); ?></li>
		</ol>
	</div>

	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Kassensystem (POS)', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Das Kassensystem ermöglicht das Erstellen von Bestellungen direkt vor Ort.', 'libre-bite' ); ?></p>

		<h4><?php esc_html_e( 'Bestellung erstellen', 'libre-bite' ); ?></h4>
		<ol>
			<li><?php esc_html_e( 'Standort auswählen (falls mehrere vorhanden)', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Produkte durch Klicken zum Warenkorb hinzufügen', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Bei Bedarf Varianten/Optionen auswählen', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Optional: Kundennamen eingeben', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Auf "Bestellung abschliessen" klicken', 'libre-bite' ); ?></li>
		</ol>

		<div class="lbite-help-tip">
			<span class="dashicons dashicons-lightbulb"></span>
			<p><?php esc_html_e( 'Tipp: Verwenden Sie die Kategorie-Filter, um schneller zu den gewünschten Produkten zu gelangen.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Sound-Benachrichtigungen', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Bei neuen Bestellungen wird automatisch ein Signalton abgespielt.', 'libre-bite' ); ?></p>
		<p><?php esc_html_e( 'Die Sound-Benachrichtigung kann in den Einstellungen aktiviert/deaktiviert werden.', 'libre-bite' ); ?></p>

		<div class="lbite-help-notice">
			<span class="dashicons dashicons-warning"></span>
			<p><?php esc_html_e( 'Hinweis: Der Browser muss Sound-Wiedergabe erlauben. Bei manchen Browsern muss die Seite einmal angeklickt werden, bevor Sounds abgespielt werden können.', 'libre-bite' ); ?></p>
		</div>
	</div>
</div>

