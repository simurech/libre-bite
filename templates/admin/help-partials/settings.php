<?php
/**
 * Hilfe-Partial: Einstellungen
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lb-help-section">
	<h2><?php esc_html_e( 'Einstellungen', 'libre-bite' ); ?></h2>

	<div class="lb-help-article">
		<h3><?php esc_html_e( 'Checkout-Felder', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Definieren Sie, welche Felder im Checkout angezeigt werden.', 'libre-bite' ); ?></p>

		<p><strong><?php esc_html_e( 'Verfügbare Optionen:', 'libre-bite' ); ?></strong></p>
		<ul>
			<li><?php esc_html_e( 'E-Mail-Feld anzeigen/ausblenden', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Telefon-Feld anzeigen/ausblenden', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Felder als Pflichtfeld markieren', 'libre-bite' ); ?></li>
		</ul>

		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-checkout-fields' ) ); ?>" class="button">
			<?php esc_html_e( 'Checkout-Felder bearbeiten', 'libre-bite' ); ?>
		</a>
	</div>

	<div class="lb-help-article">
		<h3><?php esc_html_e( 'Allgemeine Einstellungen', 'libre-bite' ); ?></h3>

		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Einstellung', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Beschreibung', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Empfehlung', 'libre-bite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Vorbereitungszeit', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Mindestzeit von Bestellung bis Abholung', 'libre-bite' ); ?></td>
					<td>30 <?php esc_html_e( 'Minuten', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Zeitslot-Intervall', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Abstand zwischen verfügbaren Abholzeiten', 'libre-bite' ); ?></td>
					<td>15 <?php esc_html_e( 'Minuten', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Erinnerungszeit', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Wann vor Abholzeit die E-Mail-Erinnerung versendet wird', 'libre-bite' ); ?></td>
					<td>15 <?php esc_html_e( 'Minuten', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Dashboard-Aktualisierung', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Wie oft das Dashboard neue Bestellungen lädt', 'libre-bite' ); ?></td>
					<td>30 <?php esc_html_e( 'Sekunden', 'libre-bite' ); ?></td>
				</tr>
			</tbody>
		</table>

		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-settings' ) ); ?>" class="button">
			<?php esc_html_e( 'Einstellungen öffnen', 'libre-bite' ); ?>
		</a>
	</div>

	<div class="lb-help-article">
		<h3><?php esc_html_e( 'Trinkgeld-Einstellungen', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Definieren Sie drei Trinkgeld-Prozentsätze, die dem Kunden im Checkout angezeigt werden.', 'libre-bite' ); ?></p>

		<p><strong><?php esc_html_e( 'Standard-Werte:', 'libre-bite' ); ?></strong></p>
		<ul>
			<li><?php esc_html_e( 'Option 1: 5%', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Option 2: 10%', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Option 3: 15%', 'libre-bite' ); ?></li>
		</ul>

		<div class="lb-help-tip">
			<span class="dashicons dashicons-lightbulb"></span>
			<p><?php esc_html_e( 'Tipp: Das Trinkgeld wird automatisch zum Bestelltotal addiert und in der Bestellung separat ausgewiesen.', 'libre-bite' ); ?></p>
		</div>
	</div>
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
