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
<div class="lbite-help-section">
	<h2><?php esc_html_e( 'Produkte mit Libre Bite', 'libre-bite' ); ?></h2>
	<p><?php esc_html_e( 'Libre Bite baut direkt auf deinen WooCommerce-Produkten auf. Du brauchst keine neuen Produkte zu erstellen – du erweiterst einfach die bestehenden um Gastro-spezifische Funktionen wie Produkt-Optionen (Add-ons).', 'libre-bite' ); ?></p>

	<!-- WooCommerce-Produkte -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'WooCommerce-Produkte als Grundlage', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Alle Produkte, die du in WooCommerce verwaltest, sind automatisch auch in Libre Bite verfügbar – im Online-Shop, im Kassensystem (POS) und im Checkout.', 'libre-bite' ); ?></p>
		<ul>
			<li><strong><?php esc_html_e( 'Einfache Produkte:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Ein Produkt, ein Preis – z.B. "Hamburger CHF 14.50".', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Variable Produkte:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Produkte mit Varianten – z.B. "Pizza" in den Grössen S, M, L mit unterschiedlichen Preisen.', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Kategorien:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Kategorien werden im POS als Filter-Buttons angezeigt (z.B. "Burger", "Getränke", "Desserts").', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Produktbilder:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Produktbilder erscheinen im Kassensystem – ideal für schnelles Finden der richtigen Artikel.', 'libre-bite' ); ?></li>
		</ul>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" class="button">
			<?php esc_html_e( 'Zu den Produkten', 'libre-bite' ); ?>
		</a>
	</div>

	<!-- Produkt-Optionen (Add-ons) -->
	<?php if ( lbite_feature_enabled( 'enable_product_options' ) ) : ?>
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Produkt-Optionen (Add-ons)', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Produkt-Optionen sind Zusatzauswahlen, die Kunden beim Bestellen treffen können – z.B. "Extra Käse", "Sauce", "ohne Zwiebeln". Sie erscheinen direkt beim Produkt im Shop und werden mit der Bestellung übermittelt.', 'libre-bite' ); ?></p>

		<h4><?php esc_html_e( 'Schritt 1: Produkt-Option erstellen', 'libre-bite' ); ?></h4>
		<ol>
			<li><?php esc_html_e( 'Gehe zu "Libre Bite" → "Produkt-Optionen"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Klicke auf "Erstellen"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Gib einen Namen ein, z.B. "Extra Käse"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Trage optional einen Aufpreis ein, z.B. "+0.50"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Veröffentliche die Option', 'libre-bite' ); ?></li>
		</ol>

		<h4><?php esc_html_e( 'Schritt 2: Option einem Produkt zuweisen', 'libre-bite' ); ?></h4>
		<ol>
			<li><?php esc_html_e( 'Öffne das gewünschte Produkt in WooCommerce', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Scrolle zum Abschnitt "Libre Bite Produkt-Optionen"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Hake die Optionen an, die bei diesem Produkt angeboten werden sollen', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Speichere das Produkt', 'libre-bite' ); ?></li>
		</ol>

		<div class="lbite-help-tip">
			<span class="dashicons dashicons-lightbulb"></span>
			<p><?php esc_html_e( 'Tipp: Du kannst dieselbe Option mehreren Produkten zuweisen. Wenn du z.B. "Extra Käse" für alle Burger-Produkte anbieten willst, erstellst du die Option einmal und ordnest sie dann jedem Burger zu.', 'libre-bite' ); ?></p>
		</div>

		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lbite_product_option' ) ); ?>" class="button">
			<?php esc_html_e( 'Zu den Produkt-Optionen', 'libre-bite' ); ?>
		</a>
	</div>
	<?php endif; ?>

	<!-- Tische -->
	<?php if ( lbite_feature_enabled( 'enable_table_ordering' ) ) : ?>
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Tischbestellungen', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'QR-Code-basierte Tischbestellung: Gäste scannen und bestellen direkt – ohne Adress- oder Abholzeitfelder.', 'libre-bite' ); ?></p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=tables' ) ); ?>" class="button">
			<?php esc_html_e( 'Tisch-Hilfe anzeigen', 'libre-bite' ); ?>
		</a>
	</div>
	<?php endif; ?>
</div>
