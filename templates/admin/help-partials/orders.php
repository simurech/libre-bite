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
	<h2><?php esc_html_e( 'Orders with Libre Bite', 'libre-bite' ); ?></h2>
	<p><?php esc_html_e( 'Libre Bite adds a complete order management system for food service businesses to your WooCommerce store. New orders appear automatically in the order overview – no need to manually search WooCommerce orders.', 'libre-bite' ); ?></p>

	<!-- Wie läuft eine Bestellung ab? -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'How does an order work?', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'The typical flow of an online order:', 'libre-bite' ); ?></p>
		<ol>
			<li><strong><?php esc_html_e( 'Customer orders online', 'libre-bite' ); ?></strong> – <?php esc_html_e( 'The customer selects products, a location and a pickup time on the website.', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Order appears in the dashboard', 'libre-bite' ); ?></strong> – <?php esc_html_e( 'The order immediately appears in the "Incoming" column of the order overview. Optionally a sound plays.', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Staff prepares the order', 'libre-bite' ); ?></strong> – <?php esc_html_e( 'Order is moved to "Preparing". For pre-orders, this happens automatically X minutes before pickup time.', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Order is ready', 'libre-bite' ); ?></strong> – <?php esc_html_e( 'Set status to "Ready". Optionally the customer receives an automatic reminder email.', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Pickup', 'libre-bite' ); ?></strong> – <?php esc_html_e( 'After pickup, the order is set to "Picked Up" and disappears from the active view.', 'libre-bite' ); ?></li>
		</ol>
	</div>

	<!-- Bestellübersicht (Kanban) -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'The Order Overview (Kanban Board)', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'The Kanban board is your real-time overview of all active orders. At a glance you can see what is incoming, what is being prepared, and what is ready for pickup.', 'libre-bite' ); ?></p>

		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Column', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Meaning', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Next Step', 'libre-bite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Incoming', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'New orders that have not yet been processed. New orders are highlighted here in color.', 'libre-bite' ); ?></td>
					<td><?php esc_html_e( 'View the order and start preparing.', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Preparing', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Orders currently being prepared. Pre-orders are automatically placed here shortly before the pickup time.', 'libre-bite' ); ?></td>
					<td><?php esc_html_e( 'Mark as done when the order is ready for pickup.', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Ready', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Orders waiting for the customer. The customer may be notified by email.', 'libre-bite' ); ?></td>
					<td><?php esc_html_e( 'Mark as "Picked Up" after collection.', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Picked Up', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Completed orders for today. Older orders are stored separately.', 'libre-bite' ); ?></td>
					<td><?php esc_html_e( 'Done.', 'libre-bite' ); ?></td>
				</tr>
			</tbody>
		</table>

		<h4 style="margin-top: 16px;"><?php esc_html_e( 'Moving Status', 'libre-bite' ); ?></h4>
		<ul>
			<li><strong><?php esc_html_e( 'Drag & Drop:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Drag an order card to the next column – ideal on touchscreens and tablets.', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Button:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Click the status button directly on the order card.', 'libre-bite' ); ?></li>
		</ul>

		<div class="lbite-help-tip">
			<span class="dashicons dashicons-lightbulb"></span>
			<p><?php esc_html_e( 'Tip: The dashboard updates automatically. You do not need to manually reload the page to see new orders. The interval can be adjusted under Settings → Dashboard.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<!-- Sound-Benachrichtigungen -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Sound Notifications for New Orders', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'As soon as a new order arrives, the dashboard automatically plays an alert sound – so you never miss an order, even if you are not looking at the screen.', 'libre-bite' ); ?></p>
		<ul>
			<li><?php esc_html_e( 'The sound only plays when the browser tab with the dashboard is open.', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Some browsers only allow sounds after an interaction (e.g. clicking on the page once).', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'You can customize or disable the sound under Settings → Dashboard.', 'libre-bite' ); ?></li>
		</ul>
	</div>

	<!-- Vorbestellungen -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Pre-Orders (Scheduled Pickup Times)', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Customers can select a specific pickup time at checkout – e.g. "today at 12:30". Libre Bite manages these pre-orders automatically:', 'libre-bite' ); ?></p>
		<ul>
			<li><?php esc_html_e( 'Pre-orders initially appear in "Incoming" with the pickup time shown.', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'X minutes before pickup time (configurable under Settings → General), they are automatically moved to the "Preparing" column.', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'This way you always know exactly when to start preparing.', 'libre-bite' ); ?></li>
		</ul>
	</div>

	<!-- POS -->
	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'POS System for On-Site Orders', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'The POS system allows orders to be placed directly at the counter – e.g. for walk-in customers, phone orders, or table orders.', 'libre-bite' ); ?></p>

		<h4><?php esc_html_e( 'How the POS works', 'libre-bite' ); ?></h4>
		<ol>
			<li><?php esc_html_e( 'Select location (if multiple locations available)', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Add products to the cart by tapping – including variants and product options', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Optional: enter customer name and table', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( '"Complete Order" → choose payment method (cash, card, Twint, etc.)', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'The order immediately appears in the Kanban board under "Incoming"', 'libre-bite' ); ?></li>
		</ol>

		<div class="lbite-help-tip">
			<span class="dashicons dashicons-lightbulb"></span>
			<p><?php esc_html_e( 'Tip: The POS system is optimized for use on tablets or a second monitor – allowing staff to work independently from the dashboard.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-order-board' ) ); ?>" class="button button-primary">
		<?php esc_html_e( 'Go to Order Overview', 'libre-bite' ); ?>
	</a>
	<?php if ( lbite_feature_enabled( 'enable_pos' ) ) : ?>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-pos' ) ); ?>" class="button" style="margin-left: 8px;">
		<?php esc_html_e( 'Go to POS System', 'libre-bite' ); ?>
	</a>
	<?php endif; ?>
</div>
