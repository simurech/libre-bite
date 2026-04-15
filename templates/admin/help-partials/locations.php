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
<div class="lbite-help-section">
	<h2><?php esc_html_e( 'Manage Locations', 'libre-bite' ); ?></h2>

	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Create Location', 'libre-bite' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Go to "Libre Bite" → "Locations"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Click on "Create"', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Enter the location name', 'libre-bite' ); ?></li>
			<li><?php esc_html_e( 'Fill in the location details:', 'libre-bite' ); ?>
				<ul>
					<li><?php esc_html_e( 'Address', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Phone/Email', 'libre-bite' ); ?></li>
					<li><?php esc_html_e( 'Opening Hours', 'libre-bite' ); ?></li>
				</ul>
			</li>
			<li><?php esc_html_e( 'Publish the location', 'libre-bite' ); ?></li>
		</ol>
	</div>

	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Configure Opening Hours', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Opening hours determine when orders can be placed.', 'libre-bite' ); ?></p>

		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Field', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Description', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Example', 'libre-bite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Opens at', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Time from which orders are possible', 'libre-bite' ); ?></td>
					<td>08:00</td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Closes at', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Time until which orders are possible', 'libre-bite' ); ?></td>
					<td>18:00</td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Closed', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Mark day as closed', 'libre-bite' ); ?></td>
					<td><?php esc_html_e( 'Sunday', 'libre-bite' ); ?></td>
				</tr>
			</tbody>
		</table>

		<div class="lbite-help-tip">
			<span class="dashicons dashicons-lightbulb"></span>
			<p><?php esc_html_e( 'Tip: Leave the opening hours of a day empty if the location is closed on that day.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Location in Checkout', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Customers select a location at checkout. Available pickup times are automatically calculated based on opening hours.', 'libre-bite' ); ?></p>

		<p><?php esc_html_e( 'The following factors affect the time selection:', 'libre-bite' ); ?></p>
		<ul>
			<li><strong><?php esc_html_e( 'Preparation Time:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Minimum time until pickup (e.g. 30 minutes)', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Time Slot Interval:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Distance between pickup times (e.g. every 15 minutes)', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Opening Hours:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'Only times during opening hours are offered', 'libre-bite' ); ?></li>
		</ul>
	</div>

	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Integrate Location Selection', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'With the following shortcode, customers can select a location directly on a page or at checkout:', 'libre-bite' ); ?></p>

		<p><?php esc_html_e( 'You can insert the shortcode on any page (e.g. the order page or homepage). The selected location is stored in the session and used at checkout.', 'libre-bite' ); ?></p>

		<h4><?php esc_html_e( 'Simple Usage', 'libre-bite' ); ?></h4>
		<div style="background: #f6f7f7; border: 1px solid #ccd0d4; border-radius: 3px; padding: 12px 16px; margin: 8px 0; font-family: monospace; font-size: 14px;">
			[lbite_location_selector]
		</div>

		<h4><?php esc_html_e( 'Parameters', 'libre-bite' ); ?></h4>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Parameter', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Possible Values', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Default', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Description', 'libre-bite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>style</code></td>
					<td><code>tiles</code>, <code>inline</code></td>
					<td><code>tiles</code></td>
					<td><?php esc_html_e( 'Display: Tiles (side by side) or inline (compact)', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><code>show_time</code></td>
					<td><code>yes</code>, <code>no</code></td>
					<td><code>yes</code></td>
					<td><?php esc_html_e( 'Show or hide time selection', 'libre-bite' ); ?></td>
				</tr>
			</tbody>
		</table>

		<h4><?php esc_html_e( 'Examples', 'libre-bite' ); ?></h4>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Shortcode', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Result', 'libre-bite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>[lbite_location_selector]</code></td>
					<td><?php esc_html_e( 'Tile view with location and time selection', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><code>[lbite_location_selector style="inline"]</code></td>
					<td><?php esc_html_e( 'Compact inline view with time selection', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><code>[lbite_location_selector show_time="no"]</code></td>
					<td><?php esc_html_e( 'Location selection only, without time selection', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><code>[lbite_location_selector style="inline" show_time="no"]</code></td>
					<td><?php esc_html_e( 'Compact, location selection only', 'libre-bite' ); ?></td>
				</tr>
			</tbody>
		</table>

		<h4><?php esc_html_e( 'URL Parameters (Deep Links)', 'libre-bite' ); ?></h4>
		<p><?php esc_html_e( 'You can also pre-select location and order type directly via URL – useful for QR codes, flyers or links.', 'libre-bite' ); ?></p>

		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'URL Parameter', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Possible Values', 'libre-bite' ); ?></th>
					<th><?php esc_html_e( 'Description', 'libre-bite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>?location=ID</code></td>
					<td><?php esc_html_e( 'Location ID (number)', 'libre-bite' ); ?></td>
					<td><?php esc_html_e( 'Automatically pre-selects the location. You can find the ID in the URL when editing the location.', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><code>?order_type=now</code></td>
					<td><code>now</code></td>
					<td><?php esc_html_e( 'Sets the order type to "Now" (fastest possible pickup time).', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><code>?order_type=later</code></td>
					<td><code>later</code></td>
					<td><?php esc_html_e( 'Sets the order type to "Pre-order" (customer chooses pickup time themselves).', 'libre-bite' ); ?></td>
				</tr>
			</tbody>
		</table>

		<p style="margin-top: 8px;"><?php esc_html_e( 'Example URL:', 'libre-bite' ); ?></p>
		<div style="background: #f6f7f7; border: 1px solid #ccd0d4; border-radius: 3px; padding: 10px 14px; font-family: monospace; font-size: 13px; word-break: break-all;">
			<?php echo esc_html( home_url( '/bestellen/?location=5&order_type=now' ) ); ?>
		</div>

		<div class="lbite-help-tip">
			<span class="dashicons dashicons-lightbulb"></span>
			<p><?php esc_html_e( 'Tip: Enable the "Location Selection" feature under Settings → Features for the shortcode to be visible.', 'libre-bite' ); ?></p>
		</div>
	</div>

	<div class="lbite-help-article">
		<h3><?php esc_html_e( 'Location Color', 'libre-bite' ); ?></h3>
		<p><?php esc_html_e( 'Each location can be assigned an accent color. This is displayed as a visual highlight in the following areas:', 'libre-bite' ); ?></p>
		<ul>
			<li><strong><?php esc_html_e( 'POS System:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'The location dropdown receives a colored border matching the selected color.', 'libre-bite' ); ?></li>
			<li><strong><?php esc_html_e( 'Order Overview:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'The location dropdown in the order overview is also highlighted in color.', 'libre-bite' ); ?></li>
		</ul>
		<p><?php esc_html_e( 'The color is set in the location editing area under "Color" with the WordPress color picker.', 'libre-bite' ); ?></p>
	</div>

	<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lbite_location' ) ); ?>" class="button button-primary">
		<?php esc_html_e( 'Manage Locations', 'libre-bite' ); ?>
	</a>
</div>

