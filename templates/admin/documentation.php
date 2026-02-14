<?php
/**
 * Template: Dokumentation
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1>
		<?php
		$plugin_name = apply_filters( 'lbite_plugin_display_name', __( 'Libre Bite', 'libre-bite' ) );
		echo esc_html( $plugin_name . ' - ' . __( 'Dokumentation', 'libre-bite' ) );
		?>
	</h1>

	<div class="lbite-documentation">
		<!-- Shortcodes -->
		<div class="lbite-doc-section">
			<h2><?php esc_html_e( 'Shortcodes', 'libre-bite' ); ?></h2>

			<div class="lbite-doc-card">
				<h3>[lbite_location_selector]</h3>
				<p><?php esc_html_e( 'Zeigt eine Standort- und Zeitauswahl an. Standardmäßig als Kachel-Layout mit Zwei-Schritt-Prozess (erst Standort, dann Zeit) und automatischer Weiterleitung zur Shop-Seite.', 'libre-bite' ); ?></p>

				<h4><?php esc_html_e( 'Verwendung:', 'libre-bite' ); ?></h4>
				<code class="lbite-code-block">[lbite_location_selector]</code>

				<h4><?php esc_html_e( 'Parameter:', 'libre-bite' ); ?></h4>
				<ul>
					<li><code>show_time="yes"</code> - <?php esc_html_e( 'Zeitauswahl anzeigen (Standard: yes)', 'libre-bite' ); ?></li>
					<li><code>show_time="no"</code> - <?php esc_html_e( 'Nur Standortauswahl, keine Zeitwahl', 'libre-bite' ); ?></li>
					<li><code>style="tiles"</code> - <?php esc_html_e( 'Kachel-Layout mit Bildern und Zwei-Schritt-Prozess (Standard)', 'libre-bite' ); ?></li>
					<li><code>style="inline"</code> - <?php esc_html_e( 'Klassisches Dropdown-Formular', 'libre-bite' ); ?></li>
				</ul>

				<h4><?php esc_html_e( 'Beispiele:', 'libre-bite' ); ?></h4>
				<code class="lbite-code-block">[lbite_location_selector]</code>
				<p class="description"><?php esc_html_e( 'Kachel-Layout mit Standort- und Zeitauswahl (empfohlen)', 'libre-bite' ); ?></p>

				<code class="lbite-code-block">[lbite_location_selector show_time="no"]</code>
				<p class="description"><?php esc_html_e( 'Nur Standort-Kacheln, keine Zeitwahl', 'libre-bite' ); ?></p>

				<code class="lbite-code-block">[lbite_location_selector style="inline"]</code>
				<p class="description"><?php esc_html_e( 'Klassisches Dropdown-Formular (alte Ansicht)', 'libre-bite' ); ?></p>

				<div class="notice notice-success inline" style="margin-top: 15px;">
					<p><strong><?php esc_html_e( 'Neu:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Das Kachel-Layout zeigt optional Standort-Bilder an. Diese können in den Standort-Einstellungen hochgeladen werden.', 'libre-bite' ); ?></p>
				</div>

				<div class="notice notice-info inline" style="margin-top: 10px;">
					<p><strong><?php esc_html_e( 'Weiterleitung:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Nach der Auswahl wird der Benutzer automatisch zur Shop-Seite weitergeleitet.', 'libre-bite' ); ?></p>
				</div>

				<div class="notice notice-info inline" style="margin-top: 10px;">
					<p><strong><?php esc_html_e( 'Hinweis:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Wenn dieser Shortcode verwendet wird, wird das automatische Popup deaktiviert.', 'libre-bite' ); ?></p>
				</div>
			</div>
		</div>

		<!-- URL-Parameter -->
		<div class="lbite-doc-section">
			<h2><?php esc_html_e( 'URL-Parameter', 'libre-bite' ); ?></h2>

			<div class="lbite-doc-card">
				<p><?php esc_html_e( 'Sie können Standort und Bestelltyp über URL-Parameter vorwählen:', 'libre-bite' ); ?></p>

				<h4><?php esc_html_e( 'Verfügbare Parameter:', 'libre-bite' ); ?></h4>
				<ul>
					<li>
						<code>location</code> - <?php esc_html_e( 'Standort-ID', 'libre-bite' ); ?>
						<br><small><?php esc_html_e( 'Die ID finden Sie in der Standorte-Liste', 'libre-bite' ); ?></small>
					</li>
					<li>
						<code>order_type</code> - <?php esc_html_e( 'Bestelltyp (now oder later)', 'libre-bite' ); ?>
					</li>
				</ul>

				<h4><?php esc_html_e( 'Beispiele:', 'libre-bite' ); ?></h4>
				<code class="lbite-code-block">https://ihre-website.de/?location=123</code>
				<p class="description"><?php esc_html_e( 'Wählt Standort mit ID 123 vor', 'libre-bite' ); ?></p>

				<code class="lbite-code-block">https://ihre-website.de/?location=123&order_type=now</code>
				<p class="description"><?php esc_html_e( 'Wählt Standort 123 und "Sofort bestellen" vor', 'libre-bite' ); ?></p>

				<code class="lbite-code-block">https://ihre-website.de/?location=123&order_type=later</code>
				<p class="description"><?php esc_html_e( 'Wählt Standort 123 und "Für später vorbestellen" vor', 'libre-bite' ); ?></p>

				<div class="notice notice-success inline">
					<p><strong><?php esc_html_e( 'Tipp:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Diese URLs können Sie z.B. in E-Mails, QR-Codes oder Social Media verwenden.', 'libre-bite' ); ?></p>
				</div>
			</div>
		</div>

		<!-- Standort-ID finden -->
		<div class="lbite-doc-section">
			<h2><?php esc_html_e( 'Standort-ID herausfinden', 'libre-bite' ); ?></h2>

			<div class="lbite-doc-card">
				<p><?php esc_html_e( 'So finden Sie die ID eines Standorts:', 'libre-bite' ); ?></p>
				<ol>
					<li><?php esc_html_e( 'Gehen Sie zu "Libre Bite → Standorte"', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Bewegen Sie die Maus über einen Standort-Namen', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Schauen Sie in der Browser-Statusleiste (unten links) - dort sehen Sie die URL', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Die Zahl nach "post=" ist die Standort-ID', 'libre-bite' ); ?></li>
				</ol>

				<p><?php esc_html_e( 'Beispiel-URL:', 'libre-bite' ); ?></p>
				<code class="lbite-code-block">post.php?post=<strong style="color: #dc3232;">123</strong>&action=edit</code>
				<p class="description"><?php esc_html_e( 'Die Standort-ID ist hier: 123', 'libre-bite' ); ?></p>

				<?php
				// Verfügbare Standorte auflisten
				$locations = LBite_Locations::get_all_locations();
				if ( ! empty( $locations ) ) :
					?>
					<h4><?php esc_html_e( 'Ihre Standorte:', 'libre-bite' ); ?></h4>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Standort', 'libre-bite' ); ?></th>
								<th><?php esc_html_e( 'ID', 'libre-bite' ); ?></th>
								<th><?php esc_html_e( 'Link-Beispiel', 'libre-bite' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $locations as $location ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $location->post_title ); ?></strong></td>
									<td><code><?php echo esc_html( $location->ID ); ?></code></td>
									<td>
										<code><?php echo esc_url( home_url( '/?location=' . $location->ID ) ); ?></code>
										<button type="button" class="button button-small lbite-copy-btn" data-text="<?php echo esc_attr( home_url( '/?location=' . $location->ID ) ); ?>">
											<?php esc_html_e( 'Kopieren', 'libre-bite' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>

		<!-- Workflow -->
		<div class="lbite-doc-section">
			<h2><?php esc_html_e( 'Typischer Workflow', 'libre-bite' ); ?></h2>

			<div class="lbite-doc-card">
				<h4><?php esc_html_e( '1. Grundeinrichtung', 'libre-bite' ); ?></h4>
				<ol>
					<li><?php esc_html_e( 'Standorte anlegen unter "Libre Bite → Standorte"', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Produkt-Optionen erstellen unter "Libre Bite → Produkt-Optionen"', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Bei WooCommerce-Produkten die Standorte zuweisen', 'libre-bite' ); ?></li>
				</ol>

				<h4><?php esc_html_e( '2. Frontend einrichten', 'libre-bite' ); ?></h4>
				<ol>
					<li><?php esc_html_e( 'Shortcode [lbite_location_selector] auf Startseite einfügen', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Optional: Checkout-Felder anpassen unter "Checkout-Felder"', 'libre-bite' ); ?></li>
				</ol>

				<h4><?php esc_html_e( '3. Bestellungen verwalten', 'libre-bite' ); ?></h4>
				<ol>
					<li><?php esc_html_e( 'Bestellübersicht zeigt alle eingehenden Bestellungen', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Bestellungen per Drag & Drop zwischen Status verschieben', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Kassensystem für Walk-In Kunden nutzen', 'libre-bite' ); ?></li>
				</ol>
			</div>
		</div>

		<!-- Weitere Informationen -->
		<div class="lbite-doc-section">
			<h2><?php esc_html_e( 'Weitere Informationen', 'libre-bite' ); ?></h2>

			<div class="lbite-doc-card">
				<h4><?php esc_html_e( 'Support & Updates', 'libre-bite' ); ?></h4>
				<p>
					<?php esc_html_e( 'Plugin-Version:', 'libre-bite' ); ?> <strong><?php echo esc_html( LBITE_VERSION ); ?></strong><br>
					<?php esc_html_e( 'Entwickelt für WooCommerce 8.0+', 'libre-bite' ); ?>
				</p>

				<h4><?php esc_html_e( 'Wichtige Hinweise', 'libre-bite' ); ?></h4>
				<ul>
					<li><?php esc_html_e( 'Dieses Plugin benötigt WooCommerce', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Standorte müssen angelegt sein, bevor Bestellungen angenommen werden können', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Öffnungszeiten werden bei der Zeitslot-Auswahl automatisch berücksichtigt', 'libre-bite' ); ?></li>
				</ul>
			</div>
		</div>
	</div>
</div>


<?php ob_start(); ?>
jQuery(document).ready(function($) {
	// Kopieren-Funktion
	$('.lbite-copy-btn').on('click', function() {
		const text = $(this).data('text');
		const $button = $(this);

		// Temporäres Textfeld erstellen
		const $temp = $('<input>');
		$('body').append($temp);
		$temp.val(text).select();
		document.execCommand('copy');
		$temp.remove();

		// Button-Feedback
		const originalText = $button.text();
		$button.text('<?php echo esc_js( __( 'Kopiert!', 'libre-bite' ) ); ?>');
		setTimeout(function() {
			$button.text(originalText);
		}, 2000);
	});
});
<?php wp_add_inline_script( 'lbite-admin', ob_get_clean() ); ?>
