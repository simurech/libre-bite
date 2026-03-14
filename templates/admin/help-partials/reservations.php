<?php
/**
 * Hilfe: Reservierungen
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lbite-help-section">
	<h2><?php esc_html_e( 'Reservierungen', 'libre-bite' ); ?></h2>
	<p><?php esc_html_e( 'Das Reservierungsmodul ermöglicht Gästen, über ein Frontend-Formular eine Tischanfrage zu stellen. Jede Anfrage landet als Eintrag im Backend und wird per E-Mail an Gast und Admin bestätigt.', 'libre-bite' ); ?></p>
</div>

<div class="lbite-help-section">
	<h3><?php esc_html_e( 'Shortcode einbinden', 'libre-bite' ); ?></h3>
	<p><?php esc_html_e( 'Fügen Sie den folgenden Shortcode auf einer beliebigen Seite ein, um das Reservierungsformular anzuzeigen:', 'libre-bite' ); ?></p>
	<pre><code>[lbite_reservation_form]</code></pre>
	<p><?php esc_html_e( 'Optional kann ein Standort vorausgewählt werden:', 'libre-bite' ); ?></p>
	<pre><code>[lbite_reservation_form location_id="42"]</code></pre>
	<p class="description"><?php esc_html_e( 'Die Standort-ID finden Sie in der Adresszeile beim Bearbeiten des Standorts (post=…).', 'libre-bite' ); ?></p>
</div>

<div class="lbite-help-section">
	<h3><?php esc_html_e( 'Was der Gast ausfüllt', 'libre-bite' ); ?></h3>
	<ul>
		<li><strong><?php esc_html_e( 'Standort', 'libre-bite' ); ?></strong> — <?php esc_html_e( 'Bei mehreren Standorten wählt der Gast den gewünschten aus.', 'libre-bite' ); ?></li>
		<li><strong><?php esc_html_e( 'Datum & Uhrzeit', 'libre-bite' ); ?></strong> — <?php esc_html_e( 'Gewünschter Reservierungstermin.', 'libre-bite' ); ?></li>
		<li><strong><?php esc_html_e( 'Anzahl Personen', 'libre-bite' ); ?></strong> — <?php esc_html_e( 'Personenanzahl (1–50).', 'libre-bite' ); ?></li>
		<li><strong><?php esc_html_e( 'Name, E-Mail', 'libre-bite' ); ?></strong> — <?php esc_html_e( 'Pflichtangaben für die Kontaktaufnahme.', 'libre-bite' ); ?></li>
		<li><strong><?php esc_html_e( 'Telefon, Anmerkungen', 'libre-bite' ); ?></strong> — <?php esc_html_e( 'Optional.', 'libre-bite' ); ?></li>
	</ul>
	<p><?php esc_html_e( 'Eine Tischauswahl durch den Gast ist bewusst nicht vorgesehen — die Tischzuweisung erfolgt durch das Personal im Backend.', 'libre-bite' ); ?></p>
</div>

<div class="lbite-help-section">
	<h3><?php esc_html_e( 'E-Mail-Benachrichtigungen', 'libre-bite' ); ?></h3>
	<p><?php esc_html_e( 'Nach dem Absenden des Formulars werden automatisch zwei E-Mails verschickt:', 'libre-bite' ); ?></p>
	<ul>
		<li><strong><?php esc_html_e( 'Bestätigung an den Gast', 'libre-bite' ); ?></strong> — <?php esc_html_e( 'Enthält alle Reservierungsdetails und den Hinweis, dass die Anfrage noch bestätigt werden muss.', 'libre-bite' ); ?></li>
		<li><strong><?php esc_html_e( 'Benachrichtigung an den Admin', 'libre-bite' ); ?></strong> — <?php esc_html_e( 'Enthält alle Details sowie einen direkten Link zur Reservierung im Backend.', 'libre-bite' ); ?></li>
	</ul>
</div>

<div class="lbite-help-section">
	<h3><?php esc_html_e( 'Reservierungen verwalten', 'libre-bite' ); ?></h3>
	<p>
		<?php
		printf(
			/* translators: %s: link to reservations list */
			esc_html__( 'Alle eingegangenen Anfragen finden Sie unter %s.', 'libre-bite' ),
			'<a href="' . esc_url( admin_url( 'edit.php?post_type=lbite_reservation' ) ) . '">' . esc_html__( 'Libre Bite → Reservierungen', 'libre-bite' ) . '</a>'
		);
		?>
	</p>
	<table class="widefat" style="margin-top: 12px;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Status', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Bedeutung', 'libre-bite' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><span style="background:#f39c12;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;"><?php esc_html_e( 'Ausstehend', 'libre-bite' ); ?></span></td>
				<td><?php esc_html_e( 'Neue Anfrage, noch nicht bearbeitet.', 'libre-bite' ); ?></td>
			</tr>
			<tr>
				<td><span style="background:#27ae60;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;"><?php esc_html_e( 'Bestätigt', 'libre-bite' ); ?></span></td>
				<td><?php esc_html_e( 'Reservierung wurde bestätigt.', 'libre-bite' ); ?></td>
			</tr>
			<tr>
				<td><span style="background:#e74c3c;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;"><?php esc_html_e( 'Storniert', 'libre-bite' ); ?></span></td>
				<td><?php esc_html_e( 'Reservierung wurde abgesagt.', 'libre-bite' ); ?></td>
			</tr>
			<tr>
				<td><span style="background:#3498db;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;"><?php esc_html_e( 'Abgeschlossen', 'libre-bite' ); ?></span></td>
				<td><?php esc_html_e( 'Gast war anwesend, Reservierung erledigt.', 'libre-bite' ); ?></td>
			</tr>
		</tbody>
	</table>
	<p style="margin-top: 12px;"><?php esc_html_e( 'Den Status können Sie direkt in der Detailansicht einer Reservierung ändern. Filtern Sie die Liste nach Standort oder Status, um schnell den Überblick zu behalten.', 'libre-bite' ); ?></p>
</div>

<div class="lbite-help-section">
	<h3><?php esc_html_e( 'Tisch zuweisen', 'libre-bite' ); ?></h3>
	<p><?php esc_html_e( 'Öffnen Sie eine Reservierung und tragen Sie im Feld «Tisch» den gewünschten Tisch ein. Diese Zuweisung ist nur intern sichtbar und dient der Planung im Restaurant.', 'libre-bite' ); ?></p>
</div>

<div class="lbite-help-section">
	<h3><?php esc_html_e( 'Reservierungsübersicht (Tagesansicht)', 'libre-bite' ); ?></h3>
	<p>
		<?php
		printf(
			/* translators: %s: link to reservation board */
			esc_html__( 'Die operative Tagesansicht finden Sie unter %s.', 'libre-bite' ),
			'<a href="' . esc_url( admin_url( 'admin.php?page=lbite-reservation-board' ) ) . '">' . esc_html__( 'Libre Bite → Reservierungsübersicht', 'libre-bite' ) . '</a>'
		);
		?>
	</p>
	<ul>
		<li>
			<strong><?php esc_html_e( 'Standort wählen', 'libre-bite' ); ?></strong> —
			<?php esc_html_e( 'Die Standort-Auswahl wird pro Benutzer gespeichert und beim nächsten Besuch automatisch vorausgewählt.', 'libre-bite' ); ?>
		</li>
		<li>
			<strong><?php esc_html_e( 'Datum navigieren', 'libre-bite' ); ?></strong> —
			<?php esc_html_e( 'Mit den Pfeiltasten ‹ und › navigieren Sie tageweise. Das Datumfeld akzeptiert auch direkte Eingaben. Klicken Sie auf «Heute», um zum aktuellen Tag zurückzukehren.', 'libre-bite' ); ?>
		</li>
		<li>
			<strong><?php esc_html_e( 'Status ändern', 'libre-bite' ); ?></strong> —
			<?php esc_html_e( 'Klicken Sie auf das farbige Status-Badge einer Reservierung, um den Status weiterzuschalten (Ausstehend → Bestätigt → Abgeschlossen → Storniert).', 'libre-bite' ); ?>
		</li>
		<li>
			<strong><?php esc_html_e( 'Tisch zuweisen', 'libre-bite' ); ?></strong> —
			<?php esc_html_e( 'Wählen Sie im Dropdown einer Reservierungskarte den gewünschten Tisch aus. Nur Tische des aktuell gewählten Standorts werden angezeigt.', 'libre-bite' ); ?>
		</li>
		<li>
			<strong><?php esc_html_e( 'Automatische Aktualisierung', 'libre-bite' ); ?></strong> —
			<?php
			printf(
				/* translators: %s: link to settings */
				esc_html__( 'Die Ansicht aktualisiert sich automatisch. Das Intervall kann unter %s angepasst werden.', 'libre-bite' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=lbite-settings&tab=orders_settings' ) ) . '">' . esc_html__( 'Einstellungen → Bestellübersicht', 'libre-bite' ) . '</a>'
			);
			?>
		</li>
	</ul>
</div>
