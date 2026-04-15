<?php
/**
 * Hilfe-Partial: Einstellungen
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_settings_url = admin_url( 'admin.php?page=lbite-settings' );
?>
<div class="lbite-help-section">
	<h2><?php esc_html_e( 'Settings', 'libre-bite' ); ?></h2>
	<p><?php esc_html_e( 'The settings are divided into separate tabs – organized by function. Tabs for disabled features are automatically hidden.', 'libre-bite' ); ?></p>

	<!-- Tab: Features -->
	<?php if ( current_user_can( 'manage_options' ) ) : ?>
	<div class="lbite-help-article">
		<h3>
			<span class="dashicons dashicons-admin-plugins" style="vertical-align: middle;"></span>
			<?php esc_html_e( 'Features', 'libre-bite' ); ?>
		</h3>
		<p><?php esc_html_e( 'Activate or deactivate individual feature modules of Libre Bite. Disabled features are hidden from the menu – including the corresponding settings tabs.', 'libre-bite' ); ?></p>
		<p><?php esc_html_e( 'Pro features are marked with a notice and require an active license or trial.', 'libre-bite' ); ?></p>
		<p><a href="<?php echo esc_url( add_query_arg( 'tab', 'features', $lbite_settings_url ) ); ?>" class="button"><?php esc_html_e( 'Open Features', 'libre-bite' ); ?></a></p>
	</div>
	<?php endif; ?>

	<!-- Tab: Allgemein -->
	<div class="lbite-help-article">
		<h3>
			<span class="dashicons dashicons-admin-settings" style="vertical-align: middle;"></span>
			<?php esc_html_e( 'General', 'libre-bite' ); ?>
		</h3>
		<p><?php esc_html_e( 'Basic settings for the plugin.', 'libre-bite' ); ?></p>
		<table class="widefat">
			<thead><tr><th><?php esc_html_e( 'Setting', 'libre-bite' ); ?></th><th><?php esc_html_e( 'What it does', 'libre-bite' ); ?></th></tr></thead>
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Location Page', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'The page where your locations are embedded with the shortcode [lbite_location_selector]. Used e.g. as a target for internal links.', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Preparation Time', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Earliest selectable pickup time for customers. A preparation time of 30 minutes means: the earliest time slot is 30 minutes from the order time. Pre-orders are also automatically moved to "Preparing" X minutes before pickup time.', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Time Slot Interval', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Distance between selectable pickup times. With 15 minutes, the customer sees e.g. 12:00, 12:15, 12:30, etc.', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Reminder Before Pickup', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'X minutes before the selected pickup time, the customer is automatically sent a reminder email (if enabled).', 'libre-bite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Branding', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Customize the logo, colors and name of the plugin in the backend – so the system matches your business.', 'libre-bite' ); ?></td>
				</tr>
			</tbody>
		</table>
		<p style="margin-top: 8px;"><a href="<?php echo esc_url( add_query_arg( 'tab', 'general', $lbite_settings_url ) ); ?>" class="button"><?php esc_html_e( 'Open General Settings', 'libre-bite' ); ?></a></p>
	</div>

	<!-- Tab: Trinkgeld -->
	<?php if ( lbite_feature_enabled( 'enable_tips' ) ) : ?>
	<div class="lbite-help-article">
		<h3>
			<span class="dashicons dashicons-star-filled" style="vertical-align: middle;"></span>
			<?php esc_html_e( 'Tips', 'libre-bite' ); ?>
		</h3>
		<p><?php esc_html_e( 'Offer your customers a tip option at checkout. You can define three percentages and set a default selection.', 'libre-bite' ); ?></p>
		<p><a href="<?php echo esc_url( add_query_arg( 'tab', 'tips', $lbite_settings_url ) ); ?>" class="button"><?php esc_html_e( 'Open Tip Settings', 'libre-bite' ); ?></a></p>
	</div>
	<?php endif; ?>

	<!-- Tab: Checkout (Felder + Optionen) -->
	<div class="lbite-help-article">
		<h3>
			<span class="dashicons dashicons-cart" style="vertical-align: middle;"></span>
			<?php esc_html_e( 'Checkout', 'libre-bite' ); ?>
		</h3>
		<p><?php esc_html_e( 'The Checkout tab contains two sections: field configuration and additional checkout options.', 'libre-bite' ); ?></p>
		<table class="widefat">
			<thead><tr><th><?php esc_html_e( 'Einstellung', 'libre-bite' ); ?></th><th><?php esc_html_e( 'Was es bewirkt', 'libre-bite' ); ?></th></tr></thead>
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Checkout Fields', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Choose which fields the customer must fill in when ordering (e.g. first name, email, phone). For take-away, often only the first name is needed.', 'libre-bite' ); ?></td>
				</tr>
				<?php if ( lbite_feature_enabled( 'enable_rounding' ) ) : ?>
				<tr>
					<td><strong><?php esc_html_e( 'Round Total Amount', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'Rounds the amount to 5 centimes (0.05 CHF). Recommended for Swiss businesses to avoid rounding errors at the cash register.', 'libre-bite' ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( lbite_feature_enabled( 'enable_optimized_checkout' ) ) : ?>
				<tr>
					<td><strong><?php esc_html_e( 'Checkout Mode', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( '"Standard" shows the normal WooCommerce checkout. "Optimized" (Pro) reduces it to the essentials: only name and receipt option – ideal for take-away without delivery.', 'libre-bite' ); ?></td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<p style="margin-top: 8px;"><a href="<?php echo esc_url( add_query_arg( 'tab', 'checkout', $lbite_settings_url ) ); ?>" class="button"><?php esc_html_e( 'Open Checkout Settings', 'libre-bite' ); ?></a></p>
	</div>

	<!-- Tab: Bestellübersicht -->
	<?php if ( lbite_feature_enabled( 'enable_kanban_board' ) || lbite_feature_enabled( 'enable_sound_notifications' ) ) : ?>
	<div class="lbite-help-article">
		<h3>
			<span class="dashicons dashicons-clipboard" style="vertical-align: middle;"></span>
			<?php esc_html_e( 'Order Overview', 'libre-bite' ); ?>
		</h3>
		<table class="widefat">
			<thead><tr><th><?php esc_html_e( 'Einstellung', 'libre-bite' ); ?></th><th><?php esc_html_e( 'Was es bewirkt', 'libre-bite' ); ?></th></tr></thead>
			<tbody>
				<?php if ( lbite_feature_enabled( 'enable_kanban_board' ) ) : ?>
				<tr>
					<td><strong><?php esc_html_e( 'Update Interval', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'How often the order overview checks for new orders in the background (in seconds). Recommendation: 30 seconds.', 'libre-bite' ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( lbite_feature_enabled( 'enable_sound_notifications' ) ) : ?>
				<tr>
					<td><strong><?php esc_html_e( 'Notification Sound', 'libre-bite' ); ?></strong></td>
					<td><?php esc_html_e( 'The sound played when new orders arrive. You can choose a custom sound from the media library or use the default sound.', 'libre-bite' ); ?></td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<p style="margin-top: 8px;"><a href="<?php echo esc_url( add_query_arg( 'tab', 'orders_settings', $lbite_settings_url ) ); ?>" class="button"><?php esc_html_e( 'Open Order Overview Settings', 'libre-bite' ); ?></a></p>
	</div>
	<?php endif; ?>

	<!-- Tab: Kassensystem -->
	<?php if ( lbite_feature_enabled( 'enable_pos' ) ) : ?>
	<div class="lbite-help-article">
		<h3>
			<span class="dashicons dashicons-store" style="vertical-align: middle;"></span>
			<?php esc_html_e( 'POS System', 'libre-bite' ); ?>
		</h3>
		<p><?php esc_html_e( 'Define which payment methods are shown in the POS payment modal (cash, card, Twint, other). You can disable payment methods and customize their labels.', 'libre-bite' ); ?></p>
		<p><a href="<?php echo esc_url( add_query_arg( 'tab', 'pos', $lbite_settings_url ) ); ?>" class="button"><?php esc_html_e( 'Open POS Settings', 'libre-bite' ); ?></a></p>
	</div>
	<?php endif; ?>
</div>
