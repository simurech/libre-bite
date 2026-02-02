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
<div class="lb-help-section">
	<h2><?php esc_html_e( 'Standorte verwalten', 'libre-bite' ); ?></h2>

	<div class="lb-help-article">
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

	<div class="lb-help-article">
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

		<div class="lb-help-tip">
			<span class="dashicons dashicons-lightbulb"></span>
			<p><?php esc_html_e( 'Tipp: Lassen Sie die Öffnungszeiten eines Tages leer, wenn der Standort an diesem Tag geschlossen ist.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<div class="lb-help-article">
		<h3><?php esc_html_e( 'Standort im Checkout', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Kunden wählen beim Checkout einen Standort aus. Die verfügbaren Abholzeiten werden automatisch basierend auf den Öffnungszeiten berechnet.', 'libre-bite' ); ?></p>

		<p><?php esc_html_e( 'Folgende Faktoren beeinflussen die Zeitauswahl:', 'libre-bite' ); ?></p>
		<ul>
			<li><strong><?php esc_html_e( 'Vorbereitungszeit:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Mindestzeit bis zur Abholung (z.B. 30 Minuten)', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Zeitslot-Intervall:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Abstand zwischen den Abholzeiten (z.B. alle 15 Minuten)', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Öffnungszeiten:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Nur Zeiten während der Öffnungszeiten werden angeboten', 'libre-bite' ); ?></li>
		</ul>
	</div>

	<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lb_location' ) ); ?>" class="button button-primary">
		<?php esc_html_e( 'Standorte verwalten', 'libre-bite' ); ?>
	</a>
</div>

<style>
.lb-help-section {
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 4px;
	padding: 20px;
}

.lb-help-article {
	margin-bottom: 30px;
	padding-bottom: 30px;
	border-bottom: 1px solid #eee;
}

.lb-help-article:last-child {
	margin-bottom: 0;
	padding-bottom: 0;
	border-bottom: none;
}

.lb-help-article h3 {
	margin-top: 0;
}

.lb-help-article table {
	margin: 15px 0;
}

.lb-help-tip {
	display: flex;
	align-items: flex-start;
	gap: 10px;
	background: #fff8e5;
	padding: 12px;
	border-radius: 4px;
	border-left: 3px solid #dba617;
	margin-top: 15px;
}

.lb-help-tip .dashicons {
	color: #dba617;
}

.lb-help-tip p {
	margin: 0;
}
</style>
