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
	<h2><?php esc_html_e( 'Reservations', 'libre-bite' ); ?></h2>
	<p><?php esc_html_e( 'The reservation module allows guests to submit a table request via a frontend form. Each request is created as an entry in the backend and confirmed by email to the guest and admin.', 'libre-bite' ); ?></p>
</div>

<div class="lbite-help-section">
	<h3><?php esc_html_e( 'Embed Shortcode', 'libre-bite' ); ?></h3>
	<p><?php esc_html_e( 'Insert the following shortcode on any page to display the reservation form:', 'libre-bite' ); ?></p>
	<pre><code>[lbite_reservation_form]</code></pre>
	<p><?php esc_html_e( 'A location can optionally be pre-selected:', 'libre-bite' ); ?></p>
	<pre><code>[lbite_reservation_form location_id="42"]</code></pre>
	<p class="description"><?php esc_html_e( 'You can find the location ID in the URL when editing the location (post=…).', 'libre-bite' ); ?></p>
</div>

<div class="lbite-help-section">
	<h3><?php esc_html_e( 'What the Guest Fills In', 'libre-bite' ); ?></h3>
	<ul>
		<li><strong><?php esc_html_e( 'Location', 'libre-bite' ); ?></strong> — <?php esc_html_e( 'If there are multiple locations, the guest selects the desired one.', 'libre-bite' ); ?></li>
		<li><strong><?php esc_html_e( 'Date & Time', 'libre-bite' ); ?></strong> — <?php esc_html_e( 'Desired reservation date.', 'libre-bite' ); ?></li>
		<li><strong><?php esc_html_e( 'Number of Guests', 'libre-bite' ); ?></strong> — <?php esc_html_e( 'Number of persons (1–50).', 'libre-bite' ); ?></li>
		<li><strong><?php esc_html_e( 'Name, Email', 'libre-bite' ); ?></strong> — <?php esc_html_e( 'Required fields for contact.', 'libre-bite' ); ?></li>
		<li><strong><?php esc_html_e( 'Phone, Notes', 'libre-bite' ); ?></strong> — <?php esc_html_e( 'Optional.', 'libre-bite' ); ?></li>
	</ul>
	<p><?php esc_html_e( 'A table selection by the guest is intentionally not provided — table assignment is done by staff in the backend.', 'libre-bite' ); ?></p>
</div>

<div class="lbite-help-section">
	<h3><?php esc_html_e( 'Email Notifications', 'libre-bite' ); ?></h3>
	<p><?php esc_html_e( 'After submitting the form, two emails are automatically sent:', 'libre-bite' ); ?></p>
	<ul>
		<li><strong><?php esc_html_e( 'Confirmation to the Guest', 'libre-bite' ); ?></strong> — <?php esc_html_e( 'Contains all reservation details and the note that the request still needs to be confirmed.', 'libre-bite' ); ?></li>
		<li><strong><?php esc_html_e( 'Notification to the Admin', 'libre-bite' ); ?></strong> — <?php esc_html_e( 'Contains all details and a direct link to the reservation in the backend.', 'libre-bite' ); ?></li>
	</ul>
</div>

<div class="lbite-help-section">
	<h3><?php esc_html_e( 'Manage Reservations', 'libre-bite' ); ?></h3>
	<p>
		<?php
		printf(
			/* translators: %s: link to reservations list */
			esc_html__( 'All incoming requests can be found under %s.', 'libre-bite' ),
			'<a href="' . esc_url( admin_url( 'edit.php?post_type=lbite_reservation' ) ) . '">' . esc_html__( 'Libre Bite → Reservations', 'libre-bite' ) . '</a>'
		);
		?>
	</p>
	<table class="widefat" style="margin-top: 12px;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Status', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Meaning', 'libre-bite' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><span style="background:#f39c12;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;"><?php esc_html_e( 'Pending', 'libre-bite' ); ?></span></td>
				<td><?php esc_html_e( 'New request, not yet processed.', 'libre-bite' ); ?></td>
			</tr>
			<tr>
				<td><span style="background:#27ae60;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;"><?php esc_html_e( 'Confirmed', 'libre-bite' ); ?></span></td>
				<td><?php esc_html_e( 'Reservation has been confirmed.', 'libre-bite' ); ?></td>
			</tr>
			<tr>
				<td><span style="background:#e74c3c;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;"><?php esc_html_e( 'Cancelled', 'libre-bite' ); ?></span></td>
				<td><?php esc_html_e( 'Reservation has been cancelled.', 'libre-bite' ); ?></td>
			</tr>
			<tr>
				<td><span style="background:#3498db;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;"><?php esc_html_e( 'Completed', 'libre-bite' ); ?></span></td>
				<td><?php esc_html_e( 'Guest was present, reservation completed.', 'libre-bite' ); ?></td>
			</tr>
		</tbody>
	</table>
	<p style="margin-top: 12px;"><?php esc_html_e( 'You can change the status directly in the detail view of a reservation. Filter the list by location or status to keep a quick overview.', 'libre-bite' ); ?></p>
</div>

<div class="lbite-help-section">
	<h3><?php esc_html_e( 'Assign Table', 'libre-bite' ); ?></h3>
	<p><?php esc_html_e( 'Open a reservation and enter the desired table in the «Table» field. This assignment is only visible internally and is used for planning in the restaurant.', 'libre-bite' ); ?></p>
</div>

<div class="lbite-help-section">
	<h3><?php esc_html_e( 'Reservation Overview (Daily View)', 'libre-bite' ); ?></h3>
	<p>
		<?php
		printf(
			/* translators: %s: link to reservation board */
			esc_html__( 'The operational daily view can be found under %s.', 'libre-bite' ),
			'<a href="' . esc_url( admin_url( 'admin.php?page=lbite-reservation-board' ) ) . '">' . esc_html__( 'Libre Bite → Reservation Overview', 'libre-bite' ) . '</a>'
		);
		?>
	</p>
	<ul>
		<li>
			<strong><?php esc_html_e( 'Select Location', 'libre-bite' ); ?></strong> —
			<?php esc_html_e( 'The location selection is saved per user and automatically pre-selected on the next visit.', 'libre-bite' ); ?>
		</li>
		<li>
			<strong><?php esc_html_e( 'Navigate Date', 'libre-bite' ); ?></strong> —
			<?php esc_html_e( 'Use the arrow keys ‹ and › to navigate day by day. The date field also accepts direct input. Click «Today» to return to the current day.', 'libre-bite' ); ?>
		</li>
		<li>
			<strong><?php esc_html_e( 'Change Status', 'libre-bite' ); ?></strong> —
			<?php esc_html_e( 'Click the colored status badge of a reservation to advance its status (Pending → Confirmed → Completed → Cancelled).', 'libre-bite' ); ?>
		</li>
		<li>
			<strong><?php esc_html_e( 'Assign Table', 'libre-bite' ); ?></strong> —
			<?php esc_html_e( 'Select the desired table from the dropdown on a reservation card. Only tables of the currently selected location are shown.', 'libre-bite' ); ?>
		</li>
		<li>
			<strong><?php esc_html_e( 'Automatic Update', 'libre-bite' ); ?></strong> —
			<?php
			printf(
				/* translators: %s: link to settings */
				esc_html__( 'The view updates automatically. The interval can be adjusted under %s.', 'libre-bite' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=lbite-settings&tab=orders_settings' ) ) . '">' . esc_html__( 'Settings → Order Overview', 'libre-bite' ) . '</a>'
			);
			?>
		</li>
	</ul>
</div>
