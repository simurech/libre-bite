<?php
/**
 * Hilfe-Partial: Tische
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lbite-help-section">
	<h2><?php esc_html_e( 'Tischverwaltung', 'libre-bite' ); ?></h2>
	<p>
		<?php esc_html_e( 'Mit der Tischverwaltung können Gäste direkt am Tisch bestellen – sie scannen einfach den QR-Code und gelangen direkt zum Checkout mit vorausgefülltem Standort und Tisch. Adress- und Abholzeitfelder werden dabei automatisch ausgeblendet.', 'libre-bite' ); ?>
		<span class="lbite-pro-badge">Pro</span>
	</p>

	<!-- Tisch erstellen -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Tisch erstellen', 'libre-bite' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Gehen Sie zu "Libre Bite" → "Tische"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Klicken Sie auf "Erstellen"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Geben Sie einen Namen ein, z.B. "Tisch 1" oder "Terrasse A"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Wählen Sie den zugehörigen Standort', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Optional: Sitzplätze eintragen (Vorbereitung für Reservationen)', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Veröffentlichen – der QR-Code wird sofort generiert', 'libre-bite' ); ?></li>
		</ol>
	</div>

	<!-- Mehrere Tische -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Mehrere Tische auf einmal erstellen', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Für grössere Setups können Sie mehrere Tische in einem Schritt anlegen:', 'libre-bite' ); ?></p>
		<ol>
			<li><?php esc_html_e( 'Gehen Sie zu "Libre Bite" → "Tische" → "Mehrere Tische erstellen"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Standort und Präfix wählen (z.B. "Tisch")', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Nummerierung festlegen – z.B. von 1 bis 20', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Optional: einheitliche Sitzplatzanzahl angeben', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( '"Tische erstellen" klicken', 'libre-bite' ); ?></li>
		</ol>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-table-bulk-create' ) ); ?>" class="button">
			<?php esc_html_e( 'Mehrere Tische erstellen', 'libre-bite' ); ?>
		</a>
	</div>

	<!-- QR-Code -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'QR-Code', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Sobald ein Tisch einem Standort zugewiesen und gespeichert wurde, wird der QR-Code automatisch generiert.', 'libre-bite' ); ?></p>
		<ul>
			<li><strong><?php esc_html_e( 'Herunterladen:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Klicken Sie auf "QR-Code herunterladen" – das PNG-Bild kann direkt gedruckt oder in Tischaufsteller eingefügt werden.', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Drucken:', 'libre-bite' ); ?></strong> <?php esc_html_e( '"QR-Code drucken" öffnet eine druckoptimierte Ansicht mit Tischnamen.', 'libre-bite' ); ?></li>
		</ul>
		<div class="lbite-help-tip">
			<span class="dashicons dashicons-lightbulb"></span>
			<p><?php esc_html_e( 'Tipp: Der QR-Link enthält Standort-ID und Tisch-ID. Beim Scannen werden beide automatisch in der Session gespeichert und beim Checkout übernommen.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<!-- Bestellung via QR -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Wie Gäste bestellen', 'libre-bite' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Gast scannt QR-Code am Tisch', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Wird direkt auf die Bestellseite weitergeleitet – Standort und Tisch sind vorausgefüllt', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Adress- und Abholzeitfelder werden im Checkout ausgeblendet', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Bestellung erscheint im Dashboard mit Tischnummer', 'libre-bite' ); ?></li>
		</ol>
	</div>

	<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lbite_table' ) ); ?>" class="button button-primary">
		<?php esc_html_e( 'Tische verwalten', 'libre-bite' ); ?>
	</a>
</div>
