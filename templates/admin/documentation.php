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
		$lbite_plugin_name = apply_filters( 'lbite_plugin_display_name', __( 'Libre Bite', 'libre-bite' ) );
		echo esc_html( $lbite_plugin_name . ' - ' . __( 'Documentation', 'libre-bite' ) );
		?>
	</h1>

	<div class="lbite-documentation">
		<!-- Shortcodes -->
		<div class="lbite-doc-section">
			<h2><?php esc_html_e( 'Shortcodes', 'libre-bite' ); ?></h2>

			<div class="lbite-doc-card">
				<h3>[lbite_location_selector]</h3>
				<p><?php esc_html_e( 'Displays a location and time selection. By default as a tile layout with two-step process (location first, then time) and automatic redirect to the shop page.', 'libre-bite' ); ?></p>

				<h4><?php esc_html_e( 'Usage:', 'libre-bite' ); ?></h4>
				<code class="lbite-code-block">[lbite_location_selector]</code>

				<h4><?php esc_html_e( 'Parameters:', 'libre-bite' ); ?></h4>
				<ul>
					<li><code>show_time="yes"</code> - <?php esc_html_e( 'Show time selection (Default: yes)', 'libre-bite' ); ?></li>
					<li><code>show_time="no"</code> - <?php esc_html_e( 'Location selection only, no time selection', 'libre-bite' ); ?></li>
					<li><code>style="tiles"</code> - <?php esc_html_e( 'Tile layout with images and two-step process (default)', 'libre-bite' ); ?></li>
					<li><code>style="inline"</code> - <?php esc_html_e( 'Classic dropdown form', 'libre-bite' ); ?></li>
				</ul>

				<h4><?php esc_html_e( 'Examples:', 'libre-bite' ); ?></h4>
				<code class="lbite-code-block">[lbite_location_selector]</code>
				<p class="description"><?php esc_html_e( 'Tile layout with location and time selection (recommended)', 'libre-bite' ); ?></p>

				<code class="lbite-code-block">[lbite_location_selector show_time="no"]</code>
				<p class="description"><?php esc_html_e( 'Location tiles only, no time selection', 'libre-bite' ); ?></p>

				<code class="lbite-code-block">[lbite_location_selector style="inline"]</code>
				<p class="description"><?php esc_html_e( 'Classic dropdown form (old view)', 'libre-bite' ); ?></p>

				<div class="notice notice-success inline" style="margin-top: 15px;">
					<p><strong><?php esc_html_e( 'New:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'The tile layout optionally displays location images. These can be uploaded in location settings.', 'libre-bite' ); ?></p>
				</div>

				<div class="notice notice-info inline" style="margin-top: 10px;">
					<p><strong><?php esc_html_e( 'Redirect:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'After selection, the user is automatically redirected to the shop page.', 'libre-bite' ); ?></p>
				</div>

				<div class="notice notice-info inline" style="margin-top: 10px;">
					<p><strong><?php esc_html_e( 'Note:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'When this shortcode is used, the automatic popup is disabled.', 'libre-bite' ); ?></p>
				</div>
			</div>
		</div>

		<!-- URL-Parameter -->
		<div class="lbite-doc-section">
			<h2><?php esc_html_e( 'URL Parameters', 'libre-bite' ); ?></h2>

			<div class="lbite-doc-card">
				<p><?php esc_html_e( 'You can pre-select location and order type via URL parameters:', 'libre-bite' ); ?></p>

				<h4><?php esc_html_e( 'Available Parameters:', 'libre-bite' ); ?></h4>
				<ul>
					<li>
						<code>location</code> - <?php esc_html_e( 'Location ID', 'libre-bite' ); ?>
						<br><small><?php esc_html_e( 'You can find the ID in the locations list', 'libre-bite' ); ?></small>
					</li>
					<li>
						<code>order_type</code> - <?php esc_html_e( 'Order type (now or later)', 'libre-bite' ); ?>
					</li>
				</ul>

				<h4><?php esc_html_e( 'Examples:', 'libre-bite' ); ?></h4>
				<code class="lbite-code-block">https://ihre-website.de/?location=123</code>
				<p class="description"><?php esc_html_e( 'Pre-selects location with ID 123', 'libre-bite' ); ?></p>

				<code class="lbite-code-block">https://ihre-website.de/?location=123&order_type=now</code>
				<p class="description"><?php esc_html_e( 'Pre-selects location 123 and "Order Now"', 'libre-bite' ); ?></p>

				<code class="lbite-code-block">https://ihre-website.de/?location=123&order_type=later</code>
				<p class="description"><?php esc_html_e( 'Pre-selects location 123 and "Pre-order for Later"', 'libre-bite' ); ?></p>

				<div class="notice notice-success inline">
					<p><strong><?php esc_html_e( 'Tip:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'You can use these URLs in emails, QR codes, or on social media, for example.', 'libre-bite' ); ?></p>
				</div>
			</div>
		</div>

		<!-- Standort-ID finden -->
		<div class="lbite-doc-section">
			<h2><?php esc_html_e( 'Find Location ID', 'libre-bite' ); ?></h2>

			<div class="lbite-doc-card">
				<p><?php esc_html_e( 'How to find the ID of a location:', 'libre-bite' ); ?></p>
				<ol>
					<li><?php esc_html_e( 'Go to "Libre Bite → Locations"', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Hover over a location name', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Look at the browser status bar (bottom left) – you\'ll see the URL there', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'The number after "post=" is the location ID', 'libre-bite' ); ?></li>
				</ol>

				<p><?php esc_html_e( 'Example URL:', 'libre-bite' ); ?></p>
				<code class="lbite-code-block">post.php?post=<strong style="color: #dc3232;">123</strong>&action=edit</code>
				<p class="description"><?php esc_html_e( 'The location ID here is: 123', 'libre-bite' ); ?></p>

				<?php
				// Verfügbare Standorte auflisten
				$lbite_locations = LBite_Locations::get_all_locations();
				if ( ! empty( $lbite_locations ) ) :
					?>
					<h4><?php esc_html_e( 'Your Locations:', 'libre-bite' ); ?></h4>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Location', 'libre-bite' ); ?></th>
								<th><?php esc_html_e( 'ID', 'libre-bite' ); ?></th>
								<th><?php esc_html_e( 'Link Example', 'libre-bite' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $lbite_locations as $lbite_location ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $lbite_location->post_title ); ?></strong></td>
									<td><code><?php echo esc_html( $lbite_location->ID ); ?></code></td>
									<td>
										<code><?php echo esc_url( home_url( '/?location=' . $lbite_location->ID ) ); ?></code>
										<button type="button" class="button button-small lbite-copy-btn" data-text="<?php echo esc_attr( home_url( '/?location=' . $lbite_location->ID ) ); ?>">
											<?php esc_html_e( 'Copy', 'libre-bite' ); ?>
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
			<h2><?php esc_html_e( 'Typical Workflow', 'libre-bite' ); ?></h2>

			<div class="lbite-doc-card">
				<h4><?php esc_html_e( '1. Basic Setup', 'libre-bite' ); ?></h4>
				<ol>
					<li><?php esc_html_e( 'Create locations under "Libre Bite → Locations"', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Create product options under "Libre Bite → Product Options"', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Assign locations to WooCommerce products', 'libre-bite' ); ?></li>
				</ol>

				<h4><?php esc_html_e( '2. Set Up Frontend', 'libre-bite' ); ?></h4>
				<ol>
					<li><?php esc_html_e( 'Insert shortcode [lbite_location_selector] on the homepage', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Optional: Customize checkout fields under "Checkout Fields"', 'libre-bite' ); ?></li>
				</ol>

				<h4><?php esc_html_e( '3. Manage Orders', 'libre-bite' ); ?></h4>
				<ol>
					<li><?php esc_html_e( 'Order overview shows all incoming orders', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Move orders between statuses via drag & drop', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Use POS system for walk-in customers', 'libre-bite' ); ?></li>
				</ol>
			</div>
		</div>

		<!-- Tischverwaltung -->
		<div class="lbite-doc-section">
			<h2><?php esc_html_e( 'Table Management & QR Ordering', 'libre-bite' ); ?></h2>

			<div class="lbite-doc-card">
				<p><?php esc_html_e( 'With table management you can create tables for your locations and generate QR codes for contactless ordering.', 'libre-bite' ); ?></p>

				<h4><?php esc_html_e( 'How to proceed:', 'libre-bite' ); ?></h4>
				<ol>
					<li><?php esc_html_e( 'Create tables under "Libre Bite → Tables".', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Assign a location to each table.', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Print the QR code or copy the link for your table stands.', 'libre-bite' ); ?></li>
				</ol>

				<h4><?php esc_html_e( 'How it works:', 'libre-bite' ); ?></h4>
				<ul>
					<li><strong><?php esc_html_e( 'Automatic Location:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'When scanning the QR code, the location is automatically preset in the customer\'s browser.', 'libre-bite' ); ?></li>
					<li><strong><?php esc_html_e( 'Simplified Checkout:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Customers at a table don\'t need to enter address data. The system only asks for name and (optional) email.', 'libre-bite' ); ?></li>
					<li><strong><?php esc_html_e( 'Table Info on the Board:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'In the order overview and POS, the table number is displayed directly with the order.', 'libre-bite' ); ?></li>
				</ul>

				<div class="notice notice-info inline">
					<p><strong><?php esc_html_e( 'Tip:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'In the order overview (Kanban) you can set a filter in the top right to show only table orders or only take-away.', 'libre-bite' ); ?></p>
				</div>
			</div>
		</div>

		<!-- Weitere Informationen -->
		<div class="lbite-doc-section">
			<h2><?php esc_html_e( 'More Information', 'libre-bite' ); ?></h2>

			<div class="lbite-doc-card">
				<h4><?php esc_html_e( 'Support & Updates', 'libre-bite' ); ?></h4>
				<p>
					<?php esc_html_e( 'Plugin Version:', 'libre-bite' ); ?> <strong><?php echo esc_html( LBITE_VERSION ); ?></strong><br>
					<?php esc_html_e( 'Developed for WooCommerce 8.0+', 'libre-bite' ); ?>
				</p>

				<h4><?php esc_html_e( 'Important Notes', 'libre-bite' ); ?></h4>
				<ul>
					<li><?php esc_html_e( 'This plugin requires WooCommerce', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Locations must be created before orders can be accepted', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Opening hours are automatically considered when selecting time slots', 'libre-bite' ); ?></li>
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
		$button.text('<?php echo esc_js( __( 'Copied!', 'libre-bite' ) ); ?>');
		setTimeout(function() {
			$button.text(originalText);
		}, 2000);
	});
});
<?php wp_add_inline_script( 'lbite-admin', ob_get_clean() ); ?>
