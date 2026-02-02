<?php
/**
 * Hilfe-Partial: Produkte
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lb-help-section">
	<h2><?php esc_html_e( 'Produkte & Optionen', 'libre-bite' ); ?></h2>

	<div class="lb-help-article">
		<h3><?php esc_html_e( 'Produkt-Optionen (Add-ons)', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Produkt-Optionen sind Zusätze, die Kunden bei der Bestellung auswählen können (z.B. Extra Käse, Sauce, etc.).', 'libre-bite' ); ?></p>

		<h4><?php esc_html_e( 'Neue Option erstellen', 'libre-bite' ); ?></h4>
		<ol>
			<li><?php esc_html_e( 'Gehen Sie zu "Libre Bite" → "Produkt-Optionen"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Klicken Sie auf "Erstellen"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Geben Sie einen Namen ein (z.B. "Extra Käse")', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Setzen Sie optional einen Aufpreis', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Veröffentlichen Sie die Option', 'libre-bite' ); ?></li>
		</ol>

		<h4><?php esc_html_e( 'Option einem Produkt zuweisen', 'libre-bite' ); ?></h4>
		<ol>
			<li><?php esc_html_e( 'Öffnen Sie das gewünschte Produkt in WooCommerce', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Scrollen Sie zum Abschnitt "OOS Produkt-Optionen"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Wählen Sie die gewünschten Optionen aus', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Aktualisieren Sie das Produkt', 'libre-bite' ); ?></li>
		</ol>

		<div class="lb-help-tip">
			<span class="dashicons dashicons-lightbulb"></span>
			<p><?php esc_html_e( 'Tipp: Sie können dieselbe Option mehreren Produkten zuweisen, um konsistente Zusätze anzubieten.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<div class="lb-help-article">
		<h3><?php esc_html_e( 'WooCommerce-Produkte', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Das Libre Bite nutzt WooCommerce-Produkte als Grundlage. Alle Standard-WooCommerce-Funktionen stehen zur Verfügung:', 'libre-bite' ); ?></p>

		<ul>
			<li><strong><?php esc_html_e( 'Einfache Produkte:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Einzelprodukte ohne Varianten', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Variable Produkte:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Produkte mit Varianten (z.B. Grössen)', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Kategorien:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Produkte nach Kategorien organisieren', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Bilder:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Produktbilder werden im POS angezeigt', 'libre-bite' ); ?></li>
		</ul>

		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" class="button">
			<?php esc_html_e( 'Zu den Produkten', 'libre-bite' ); ?>
		</a>
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

.lb-help-article h4 {
	margin-top: 20px;
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
