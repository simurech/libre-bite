<?php
/**
 * Hilfe-Seite für Administratoren (lbite_admin)
 *
 * Mittlere Dokumentation:
 * - Alle Personal-Funktionen
 * - Produktverwaltung
 * - Standorte
 * - Einstellungen
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-Datei, wird innerhalb einer Klassen-Methode via include geladen; Variablen befinden sich im Methoden-Scope, nicht im globalen Namespace.

$support_settings = get_option( 'lbite_support_settings', array() );
$support_email    = isset( $support_settings['support_email'] ) ? $support_settings['support_email'] : get_option( 'admin_email' );
$support_phone    = isset( $support_settings['support_phone'] ) ? $support_settings['support_phone'] : '';
$support_hours    = isset( $support_settings['support_hours'] ) ? $support_settings['support_hours'] : '';
$billing_note     = isset( $support_settings['support_billing_note'] ) ? $support_settings['support_billing_note'] : '';
$custom_text      = isset( $support_settings['support_custom_text'] ) ? $support_settings['support_custom_text'] : '';

// Aktiver Tab
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lese-Parameter für Tab-Navigation.
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview';
?>
<div class="wrap lbite-help-wrap">
	<h1><?php esc_html_e( 'Help & Support', 'libre-bite' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=overview' ) ); ?>"
		   class="nav-tab <?php echo 'overview' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Overview', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=orders' ) ); ?>"
		   class="nav-tab <?php echo 'orders' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Orders', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=products' ) ); ?>"
		   class="nav-tab <?php echo 'products' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Products', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=locations' ) ); ?>"
		   class="nav-tab <?php echo 'locations' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Locations', 'libre-bite' ); ?>
		</a>
		<?php if ( lbite_feature_enabled( 'enable_table_ordering' ) ) : ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=tables' ) ); ?>"
		   class="nav-tab <?php echo 'tables' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Tables', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=reservations' ) ); ?>"
		   class="nav-tab <?php echo 'reservations' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Reservations', 'libre-bite' ); ?>
		</a>
		<?php endif; ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=settings' ) ); ?>"
		   class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Settings', 'libre-bite' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=support' ) ); ?>"
		   class="nav-tab <?php echo 'support' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Support', 'libre-bite' ); ?>
		</a>
		<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=debug' ) ); ?>"
		   class="nav-tab <?php echo 'debug' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Debug Info', 'libre-bite' ); ?>
		</a>
		<?php endif; ?>
	</nav>

	<div class="lbite-help-content">
		<?php
		switch ( $active_tab ) {
			case 'orders':
				include __DIR__ . '/help-partials/orders.php';
				break;
			case 'products':
				include __DIR__ . '/help-partials/products.php';
				break;
			case 'locations':
				include __DIR__ . '/help-partials/locations.php';
				break;
			case 'tables':
				if ( lbite_feature_enabled( 'enable_table_ordering' ) ) {
					include __DIR__ . '/help-partials/tables.php';
				}
				break;
			case 'reservations':
				if ( lbite_feature_enabled( 'enable_table_ordering' ) ) {
					include __DIR__ . '/help-partials/reservations.php';
				}
				break;
			case 'settings':
				include __DIR__ . '/help-partials/settings.php';
				break;
			case 'support':
				include __DIR__ . '/help-partials/support.php';
				break;
			case 'debug':
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && current_user_can( 'manage_options' ) ) {
					$lbite_is_tab = true;
					include LBITE_PLUGIN_DIR . 'templates/admin/debug-info.php';
				}
				break;
			default:
				// Übersicht
				?>
				<div class="lbite-help-grid">
					<!-- Schnellstart -->
					<div class="lbite-help-card lbite-help-quickstart">
						<h2><span class="dashicons dashicons-flag"></span> <?php esc_html_e( 'Administrator Overview', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'As an administrator you can manage orders, configure products, set up locations and customize settings.', 'libre-bite' ); ?></p>

						<div class="lbite-quick-links">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-order-board' ) ); ?>" class="button button-primary">
								<span class="dashicons dashicons-clipboard"></span>
								<?php esc_html_e( 'Order Overview', 'libre-bite' ); ?>
							</a>
							<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lbite_location' ) ); ?>" class="button">
								<span class="dashicons dashicons-location"></span>
								<?php esc_html_e( 'Locations', 'libre-bite' ); ?>
							</a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-settings' ) ); ?>" class="button">
								<span class="dashicons dashicons-admin-settings"></span>
								<?php esc_html_e( 'Settings', 'libre-bite' ); ?>
							</a>
						</div>

					</div>

					<!-- Bestellungen -->
					<div class="lbite-help-card">
						<h2><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Orders', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Manage incoming orders with the Kanban board or POS system.', 'libre-bite' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Change order status via drag & drop', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Sound notification for new orders', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'View order details and customer information', 'libre-bite' ); ?></li>
						</ul>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=orders' ) ); ?>" class="button">
							<?php esc_html_e( 'Learn More', 'libre-bite' ); ?>
						</a>
					</div>

					<!-- Produkte -->
					<div class="lbite-help-card">
						<h2><span class="dashicons dashicons-products"></span> <?php esc_html_e( 'Products', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Configure product options and add-ons for your orders.', 'libre-bite' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Create product options (add-ons)', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Add surcharges to options', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Assign options to products', 'libre-bite' ); ?></li>
						</ul>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=products' ) ); ?>" class="button">
							<?php esc_html_e( 'Learn More', 'libre-bite' ); ?>
						</a>
					</div>

					<!-- Standorte -->
					<div class="lbite-help-card">
						<h2><span class="dashicons dashicons-location"></span> <?php esc_html_e( 'Locations', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Manage your locations with opening hours and contact information.', 'libre-bite' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Create and edit locations', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Set opening hours per day', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Store address and contact information', 'libre-bite' ); ?></li>
						</ul>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=locations' ) ); ?>" class="button">
							<?php esc_html_e( 'Learn More', 'libre-bite' ); ?>
						</a>
					</div>

					<!-- Tische -->
					<?php if ( lbite_feature_enabled( 'enable_table_ordering' ) ) : ?>
					<div class="lbite-help-card">
						<h2><span class="dashicons dashicons-grid-view"></span> <?php esc_html_e( 'Tables', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'QR code-based table ordering – guests scan and order directly at the table.', 'libre-bite' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Create tables and generate QR codes', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Configure seats per table', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Create multiple tables at once', 'libre-bite' ); ?></li>
						</ul>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=tables' ) ); ?>" class="button">
							<?php esc_html_e( 'Learn More', 'libre-bite' ); ?>
						</a>
					</div>
					

					<!-- Reservierungen -->
					<div class="lbite-help-card">
						<h2><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e( 'Reservations', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Receive table inquiries via a frontend form, manage and confirm via email.', 'libre-bite' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Include shortcode [lbite_reservation_form]', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Manage requests in the backend and set status', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Automatic email to guest and admin', 'libre-bite' ); ?></li>
						</ul>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=reservations' ) ); ?>" class="button">
							<?php esc_html_e( 'Learn More', 'libre-bite' ); ?>
						</a>
					</div>
					<?php endif; ?>

				<!-- Einstellungen -->
					<div class="lbite-help-card">
						<h2><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Settings', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( 'Customize Libre Bite to your needs.', 'libre-bite' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Configure checkout fields', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Customize tip options', 'libre-bite' ); ?></li>
							<li><?php esc_html_e( 'Set preparation time and time slots', 'libre-bite' ); ?></li>
						</ul>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help&tab=settings' ) ); ?>" class="button">
							<?php esc_html_e( 'Learn More', 'libre-bite' ); ?>
						</a>
					</div>

					<!-- Support -->
					<div class="lbite-help-card lbite-help-support">
						<h2><span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Support', 'libre-bite' ); ?></h2>
						<p><?php esc_html_e( "Didn't find what you were looking for? I'm happy to help.", 'libre-bite' ); ?></p>

						<div class="lbite-support-info">
							<?php if ( $support_hours ) : ?>
								<p>
									<span class="dashicons dashicons-clock"></span>
									<?php echo esc_html( $support_hours ); ?>
								</p>
							<?php endif; ?>

							<?php if ( $billing_note ) : ?>
								<p class="lbite-billing-note">
									<span class="dashicons dashicons-info"></span>
									<?php echo esc_html( $billing_note ); ?>
								</p>
							<?php endif; ?>

							<?php if ( $support_email ) : ?>
								<p>
									<span class="dashicons dashicons-email"></span>
									<a href="mailto:<?php echo esc_attr( $support_email ); ?>"><?php echo esc_html( $support_email ); ?></a>
								</p>
							<?php endif; ?>

							<?php if ( $support_phone ) : ?>
								<p>
									<span class="dashicons dashicons-phone"></span>
									<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $support_phone ) ); ?>"><?php echo esc_html( $support_phone ); ?></a>
								</p>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<?php
				break;
		}
		?>
	</div>
</div>

