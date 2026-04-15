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
	<h2><?php esc_html_e( 'Table Management', 'libre-bite' ); ?></h2>
	<p>
		<?php esc_html_e( 'With table management, guests can order directly at the table – they simply scan the QR code and are taken directly to checkout with the location and table pre-filled. Address and pickup time fields are automatically hidden.', 'libre-bite' ); ?>
		<span class="lbite-pro-badge">Pro</span>
	</p>

	<!-- Tisch erstellen -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Create Table', 'libre-bite' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Go to "Libre Bite" → "Tables"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Click on "Create"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Enter a name, e.g. "Table 1" or "Terrace A"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Select the associated location', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Optional: enter seats (preparation for reservations)', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Publish – the QR code is generated immediately', 'libre-bite' ); ?></li>
		</ol>
	</div>

	<!-- Mehrere Tische -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Create Multiple Tables at Once', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'For larger setups, you can create multiple tables in one step:', 'libre-bite' ); ?></p>
		<ol>
			<li><?php esc_html_e( 'Go to "Libre Bite" → "Tables" → "Create Multiple Tables"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Select location and prefix (e.g. "Table")', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Define numbering – e.g. from 1 to 20', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Optional: enter a uniform seat count', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Click "Create Tables"', 'libre-bite' ); ?></li>
		</ol>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-table-bulk-create' ) ); ?>" class="button">
			<?php esc_html_e( 'Create Multiple Tables', 'libre-bite' ); ?>
		</a>
	</div>

	<!-- QR-Code -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'QR Code', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'As soon as a table is assigned to a location and saved, the QR code is automatically generated.', 'libre-bite' ); ?></p>
		<ul>
			<li><strong><?php esc_html_e( 'Download:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Click "Download QR Code" – the PNG image can be printed directly or inserted into table stands.', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Print:', 'libre-bite' ); ?></strong> <?php esc_html_e( '"Print QR Code" opens a print-optimized view with the table name.', 'libre-bite' ); ?></li>
		</ul>
		<div class="lbite-help-tip">
			<span class="dashicons dashicons-lightbulb"></span>
			<p><?php esc_html_e( 'Tip: The QR link contains the location ID and table ID. When scanned, both are automatically saved in the session and used at checkout.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<!-- Bestellung via QR -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'How Guests Order', 'libre-bite' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Guest scans QR code at the table', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Is taken directly to the order page – location and table are pre-filled', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Address and pickup time fields are hidden at checkout', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Order appears in the dashboard with table number', 'libre-bite' ); ?></li>
		</ol>
	</div>

	<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lbite_table' ) ); ?>" class="button button-primary">
		<?php esc_html_e( 'Manage Tables', 'libre-bite' ); ?>
	</a>
</div>

<div class="lbite-help-section">
	<h2><?php esc_html_e( 'Table Plan', 'libre-bite' ); ?></h2>
	<p>
		<?php esc_html_e( 'The table plan shows all tables of a location as freely positionable tiles on a canvas. It serves both for maintaining the floor plan and as a live overview of table occupancy.', 'libre-bite' ); ?>
		<span class="lbite-pro-badge">Pro</span>
	</p>

	<!-- Grundriss einrichten -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Set Up Floor Plan', 'libre-bite' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Go to "Libre Bite" → "Table Plan"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Select a location', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Drag tables to the desired position on the floor plan', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Hover over a table: ◐ switches shape (square/round), ⊞ switches size (S/M/L)', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Click on "Save Positions"', 'libre-bite' ); ?></li>
		</ol>
	</div>

	<!-- Belegung überwachen -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Monitor Table Occupancy', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'As soon as a location is selected, the tables automatically color according to the current order status:', 'libre-bite' ); ?></p>
		<ul>
			<li><strong style="color:#46b450;"><?php esc_html_e( 'Green', 'libre-bite' ); ?></strong> — <?php esc_html_e( 'Free (no active order)', 'libre-bite' ); ?></li>
			<li><strong style="color:#dc3232;"><?php esc_html_e( 'Red', 'libre-bite' ); ?></strong> — <?php esc_html_e( 'Occupied (open order)', 'libre-bite' ); ?></li>
			<li><strong style="color:#ffb900;"><?php esc_html_e( 'Orange', 'libre-bite' ); ?></strong> — <?php esc_html_e( 'Preparing', 'libre-bite' ); ?></li>
			<li><strong style="color:#00a0d2;"><?php esc_html_e( 'Blue', 'libre-bite' ); ?></strong> — <?php esc_html_e( 'Ready for Pickup', 'libre-bite' ); ?></li>
		</ul>
		<p><?php esc_html_e( 'Click on an occupied table to see the order number, time, item count and total. Clicking "Show in Order Overview" opens the Kanban board. The status updates automatically every 30 seconds.', 'libre-bite' ); ?></p>
	</div>

	<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-floor-plan' ) ); ?>" class="button button-primary">
		<?php esc_html_e( 'Go to Table Plan', 'libre-bite' ); ?>
	</a>
</div>
