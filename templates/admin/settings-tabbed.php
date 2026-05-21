<?php
/**
 * Template: Einstellungen (Tabbed)
 *
 * Konsolidiert alle Einstellungsseiten in einer tabellierten Ansicht.
 * Feature-abhängige Tabs erscheinen nur, wenn das entsprechende Feature aktiv ist.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_is_admin          = current_user_can( 'manage_options' );
$lbite_premium_allowed   = function_exists( 'lbite_freemius' ) && lbite_freemius()->can_use_premium_code__premium_only();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Anzeigesteuerung.
$lbite_active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';

// Tabs definieren – neue Struktur v1.5.0
$lbite_tabs = array(
	'general'       => __( 'General', 'libre-bite' ),
	'locations'     => __( 'Locations', 'libre-bite' ),
	'orders'        => __( 'Orders', 'libre-bite' ),
	'pos'           => __( 'POS System', 'libre-bite' ),
	'checkout'      => __( 'Checkout', 'libre-bite' ),
	'products'      => __( 'Products', 'libre-bite' ),
	'tables'        => __( 'Tables', 'libre-bite' ),
	'reservations'  => __( 'Reservations', 'libre-bite' ),
	'notifications' => __( 'Notifications', 'libre-bite' ),
	'statistics'    => __( 'Statistics', 'libre-bite' ),
	'branding'      => __( 'Branding', 'libre-bite' ),
	'holidays'      => __( 'Holidays', 'libre-bite' ),
);

if ( $lbite_is_admin ) {
	$lbite_tabs['roles']   = __( 'Roles & Menus', 'libre-bite' );
	$lbite_tabs['support'] = __( 'Support', 'libre-bite' );
	$lbite_tabs['data']    = __( 'Data', 'libre-bite' );
}

// Aktiven Tab validieren
if ( ! array_key_exists( $lbite_active_tab, $lbite_tabs ) ) {
	$lbite_active_tab = 'general';
}

// Pro-Tabs-Liste (benötigt für Badge-Anzeige in Navigation)
$lbite_pro_tabs = array( 'tables', 'reservations' );

// Save-Logik für Feature-Tabs (checkout, orders, notifications, pos, branding, data, holidays)
// Die Save-Logik für 'general' liegt in settings.php
if ( isset( $_POST['lbite_save_settings'] ) && check_admin_referer( 'lbite_settings' ) ) {
	$lbite_save_tab = isset( $_POST['lbite_save_tab'] ) ? sanitize_key( wp_unslash( $_POST['lbite_save_tab'] ) ) : '';
	$lbite_did_save = false;

	switch ( $lbite_save_tab ) {
		case 'checkout':
			$lbite_co_values = lbite_enforce_pro_options( array(
				'lbite_checkout_mode'         => isset( $_POST['lbite_checkout_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_checkout_mode'] ) ) : 'standard',
				'lbite_tip_percentage_1'      => isset( $_POST['lbite_tip_percentage_1'] ) ? floatval( wp_unslash( $_POST['lbite_tip_percentage_1'] ) ) : 5,
				'lbite_tip_percentage_2'      => isset( $_POST['lbite_tip_percentage_2'] ) ? floatval( wp_unslash( $_POST['lbite_tip_percentage_2'] ) ) : 10,
				'lbite_tip_percentage_3'      => isset( $_POST['lbite_tip_percentage_3'] ) ? floatval( wp_unslash( $_POST['lbite_tip_percentage_3'] ) ) : 15,
				'lbite_tip_default_selection' => isset( $_POST['lbite_tip_default_selection'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_tip_default_selection'] ) ) : 'none',
				'lbite_tip_mode'              => ( isset( $_POST['lbite_tip_mode'] ) && 'fixed' === $_POST['lbite_tip_mode'] ) ? 'fixed' : 'percentage',
				'lbite_tip_title'             => isset( $_POST['lbite_tip_title'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_tip_title'] ) ) : '',
			) );
			update_option( 'lbite_enable_rounding', isset( $_POST['lbite_enable_rounding'] ) );
			update_option( 'lbite_checkout_mode', $lbite_co_values['lbite_checkout_mode'] );
			update_option( 'lbite_tip_percentage_1', $lbite_co_values['lbite_tip_percentage_1'] );
			update_option( 'lbite_tip_percentage_2', $lbite_co_values['lbite_tip_percentage_2'] );
			update_option( 'lbite_tip_percentage_3', $lbite_co_values['lbite_tip_percentage_3'] );
			update_option( 'lbite_tip_default_selection', $lbite_co_values['lbite_tip_default_selection'] );
			update_option( 'lbite_tip_mode', $lbite_co_values['lbite_tip_mode'] );
			update_option( 'lbite_tip_title', $lbite_co_values['lbite_tip_title'] );
			update_option( 'lbite_tip_label_none', isset( $_POST['lbite_tip_label_none'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_tip_label_none'] ) ) : '' );
			update_option( 'lbite_tip_label_1', isset( $_POST['lbite_tip_label_1'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_tip_label_1'] ) ) : '' );
			update_option( 'lbite_tip_label_2', isset( $_POST['lbite_tip_label_2'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_tip_label_2'] ) ) : '' );
			update_option( 'lbite_tip_label_3', isset( $_POST['lbite_tip_label_3'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_tip_label_3'] ) ) : '' );
			$lbite_did_save = true;
			break;

		case 'holidays':
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in loop below.
			$lbite_raw_holidays = isset( $_POST['lbite_holidays'] ) && is_array( $_POST['lbite_holidays'] ) ? wp_unslash( $_POST['lbite_holidays'] ) : array();
			$lbite_holidays     = array();
			foreach ( $lbite_raw_holidays as $lbite_h ) {
				if ( empty( $lbite_h['date'] ) || empty( $lbite_h['name'] ) ) {
					continue;
				}
				$lbite_locs = isset( $lbite_h['locations'] ) ? $lbite_h['locations'] : 'all';
				if ( is_array( $lbite_locs ) ) {
					$lbite_locs = array_map( 'intval', $lbite_locs );
				} else {
					$lbite_locs = 'all';
				}
				$lbite_holidays[] = array(
					'name'      => sanitize_text_field( $lbite_h['name'] ),
					'date'      => sanitize_text_field( $lbite_h['date'] ),
					'locations' => $lbite_locs,
					'type'      => in_array( sanitize_key( $lbite_h['type'] ?? '' ), array( 'closed', 'custom' ), true ) ? sanitize_key( $lbite_h['type'] ) : 'closed',
					'open'      => sanitize_text_field( $lbite_h['open'] ?? '' ),
					'close'     => sanitize_text_field( $lbite_h['close'] ?? '' ),
					'open2'     => sanitize_text_field( $lbite_h['open2'] ?? '' ),
					'close2'    => sanitize_text_field( $lbite_h['close2'] ?? '' ),
				);
			}
			update_option( 'lbite_holidays', $lbite_holidays );
			$lbite_did_save = true;
			break;

		case 'orders':
			$lbite_ord_values = lbite_enforce_pro_options( array(
				'lbite_show_future_orders' => isset( $_POST['lbite_show_future_orders'] ) ? 1 : 0,
				'lbite_dim_future_orders'  => isset( $_POST['lbite_dim_future_orders'] ) ? 1 : 0,
			) );
			update_option( 'lbite_dashboard_refresh_interval', isset( $_POST['lbite_dashboard_refresh_interval'] ) ? intval( wp_unslash( $_POST['lbite_dashboard_refresh_interval'] ) ) : 30 );
			update_option( 'lbite_reservation_refresh_interval', isset( $_POST['lbite_reservation_refresh_interval'] ) ? intval( wp_unslash( $_POST['lbite_reservation_refresh_interval'] ) ) : 60 );
			update_option( 'lbite_show_future_orders', $lbite_ord_values['lbite_show_future_orders'] );
			update_option( 'lbite_dim_future_orders', $lbite_ord_values['lbite_dim_future_orders'] );
			$lbite_did_save = true;
			break;

		case 'notifications':
			$lbite_not_values = lbite_enforce_pro_options( array(
				'lbite_pickup_reminder_time' => isset( $_POST['lbite_pickup_reminder_time'] ) ? intval( wp_unslash( $_POST['lbite_pickup_reminder_time'] ) ) : 15,
			) );
			update_option( 'lbite_notification_sound', isset( $_POST['lbite_notification_sound'] ) ? esc_url_raw( wp_unslash( $_POST['lbite_notification_sound'] ) ) : '' );
			update_option( 'lbite_pickup_reminder_time', $lbite_not_values['lbite_pickup_reminder_time'] );
			$lbite_did_save = true;
			break;

		case 'pos':
			$lbite_pos_defaults = array(
				array( 'key' => 'cash',  'label' => __( 'Cash', 'libre-bite' ),  'icon' => '💵' ),
				array( 'key' => 'card',  'label' => __( 'Card', 'libre-bite' ),  'icon' => '💳' ),
				array( 'key' => 'twint', 'label' => __( 'Twint', 'libre-bite' ), 'icon' => '📱' ),
				array( 'key' => 'other', 'label' => __( 'Other', 'libre-bite' ), 'icon' => '💱' ),
			);
			$lbite_payment_methods = array();
			foreach ( $lbite_pos_defaults as $lbite_pos_default ) {
				$lbite_pm_key     = $lbite_pos_default['key'];
				$lbite_pm_enabled = isset( $_POST['lbite_pm_enabled'][ $lbite_pm_key ] );
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Wert wird unmittelbar durch sanitize_text_field() und wp_unslash() bereinigt.
				$lbite_pm_label = isset( $_POST['lbite_pm_label'][ $lbite_pm_key ] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_pm_label'][ $lbite_pm_key ] ) ) : $lbite_pos_default['label'];
				if ( empty( $lbite_pm_label ) ) {
					$lbite_pm_label = $lbite_pos_default['label'];
				}
				$lbite_payment_methods[] = array(
					'key'     => $lbite_pm_key,
					'label'   => $lbite_pm_label,
					'enabled' => $lbite_pm_enabled,
				);
			}
			update_option( 'lbite_pos_payment_methods', $lbite_payment_methods );
			$lbite_did_save = true;
			break;

		case 'branding':
			update_option( 'lbite_brand_name', isset( $_POST['lbite_brand_name'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_brand_name'] ) ) : '' );
			update_option( 'lbite_brand_logo', isset( $_POST['lbite_brand_logo'] ) ? intval( wp_unslash( $_POST['lbite_brand_logo'] ) ) : 0 );
			update_option( 'lbite_color_primary', isset( $_POST['lbite_color_primary'] ) ? sanitize_hex_color( wp_unslash( $_POST['lbite_color_primary'] ) ) : '#0073aa' );
			update_option( 'lbite_color_secondary', isset( $_POST['lbite_color_secondary'] ) ? sanitize_hex_color( wp_unslash( $_POST['lbite_color_secondary'] ) ) : '#23282d' );
			update_option( 'lbite_color_accent', isset( $_POST['lbite_color_accent'] ) ? sanitize_hex_color( wp_unslash( $_POST['lbite_color_accent'] ) ) : '#00a32a' );
			$lbite_did_save = true;
			break;

		case 'data':
			update_option( 'lbite_delete_data_on_uninstall', isset( $_POST['lbite_delete_data_on_uninstall'] ) );
			$lbite_did_save = true;
			break;
	}

	if ( $lbite_did_save ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'lbite-settings',
					'tab'     => $lbite_save_tab,
					'updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}

$lbite_plugin_name  = apply_filters( 'lbite_plugin_display_name', __( 'Libre Bite', 'libre-bite' ) );
$lbite_settings_url = admin_url( 'admin.php?page=lbite-settings' );
?>

<div class="wrap">
	<h1><?php echo esc_html( $lbite_plugin_name . ' – ' . __( 'Settings', 'libre-bite' ) ); ?></h1>

	<?php if ( get_option( 'lbite_show_welcome_notice' ) ) : ?>
	<div class="lbite-welcome-notice" id="lbite-welcome-notice">
		<div class="lbite-welcome-notice__content">
			<h2><?php esc_html_e( 'Welcome to Libre Bite!', 'libre-bite' ); ?></h2>
			<p><?php esc_html_e( 'Configure each area of the plugin using the tabs below. Core features are active by default – you can adjust them at any time.', 'libre-bite' ); ?></p>
		</div>
		<button type="button" class="lbite-welcome-notice__dismiss" aria-label="<?php esc_attr_e( 'Dismiss', 'libre-bite' ); ?>">&#x2715;</button>
	</div>
	<?php endif; ?>

	<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lese-Parameter für Erfolgs-Hinweis nach Speichern; kein DB-Schreibzugriff. ?>
	<?php if ( isset( $_GET['updated'] ) && '1' === sanitize_key( wp_unslash( $_GET['updated'] ) ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved', 'libre-bite' ); ?></p>
		</div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $lbite_tabs as $lbite_tab_key => $lbite_tab_label ) : ?>
			<a
				href="<?php echo esc_url( add_query_arg( 'tab', $lbite_tab_key, $lbite_settings_url ) ); ?>"
				class="nav-tab <?php echo $lbite_active_tab === $lbite_tab_key ? 'nav-tab-active' : ''; ?>"
			>
				<?php echo esc_html( $lbite_tab_label ); ?>
				<?php if ( in_array( $lbite_tab_key, $lbite_pro_tabs, true ) && ! $lbite_premium_allowed ) : ?>
					<span class="lbite-pro-badge" style="margin-left:4px;">Pro</span>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="lbite-settings-tab-content">
		<?php
		// $lbite_is_tab = true verhindert doppelte <div class="wrap"> und <h1> in Sub-Templates
		$lbite_is_tab = true;

		switch ( $lbite_active_tab ) {
			case 'general':
				include LBITE_PLUGIN_DIR . 'templates/admin/settings.php';
				break;

			case 'locations':
				include LBITE_PLUGIN_DIR . 'templates/admin/settings/locations.php';
				break;

			case 'products':
				include LBITE_PLUGIN_DIR . 'templates/admin/settings/products.php';
				break;

			case 'notifications':
				include LBITE_PLUGIN_DIR . 'templates/admin/settings/notifications.php';
				break;

			case 'tables':
				$lbite_tpl = LBITE_PLUGIN_DIR . 'templates/admin/settings/tables__premium_only.php';
				if ( $lbite_premium_allowed && file_exists( $lbite_tpl ) ) {
					include $lbite_tpl;
				} else {
					$lbite_locked_title       = __( 'Table Management & Ordering', 'libre-bite' );
					$lbite_locked_description = __( 'Create tables, generate QR codes, and allow guests to order directly at the table. Available with Libre Bite Pro.', 'libre-bite' );
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/_pro-locked.php';
				}
				break;

			case 'reservations':
				$lbite_tpl = LBITE_PLUGIN_DIR . 'templates/admin/settings/reservations__premium_only.php';
				if ( $lbite_premium_allowed && file_exists( $lbite_tpl ) ) {
					include $lbite_tpl;
				} else {
					$lbite_locked_title       = __( 'Table Reservations', 'libre-bite' );
					$lbite_locked_description = __( 'Let customers reserve tables online via a frontend form. Available with Libre Bite Pro.', 'libre-bite' );
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/_pro-locked.php';
				}
				break;

			case 'statistics':
				include LBITE_PLUGIN_DIR . 'templates/admin/settings/statistics.php';
				break;

			case 'branding':
				include LBITE_PLUGIN_DIR . 'templates/admin/settings/branding.php';
				break;

			case 'holidays':
				include LBITE_PLUGIN_DIR . 'templates/admin/holidays-settings.php';
				break;

			case 'checkout':
				// Master-Toggle: Rundung (Free)
				$lbite_toggle_key         = 'enable_rounding';
				$lbite_toggle_label       = __( 'Price Rounding', 'libre-bite' );
				$lbite_toggle_description = __( 'Round total to 5 cents (0.05 CHF). Prevents rounding errors when combining vouchers and tips. Recommended for Swiss businesses.', 'libre-bite' );
				$lbite_toggle_is_pro      = false;
				include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';

				// Master-Toggle: Optimierter Checkout (Pro)
				$lbite_toggle_key             = 'enable_optimized_checkout';
				$lbite_toggle_label           = __( 'Optimized Checkout', 'libre-bite' );
				$lbite_toggle_description     = __( 'Replace WooCommerce fields with a minimal checkout: name and receipt option only.', 'libre-bite' );
				$lbite_toggle_is_pro          = true;
				$lbite_toggle_premium_allowed = $lbite_premium_allowed;
				include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';

				// Master-Toggle: Trinkgeld (Pro)
				$lbite_toggle_key             = 'enable_tips';
				$lbite_toggle_label           = __( 'Tips', 'libre-bite' );
				$lbite_toggle_description     = __( 'Allow customers to add a tip at checkout.', 'libre-bite' );
				$lbite_toggle_is_pro          = true;
				$lbite_toggle_premium_allowed = $lbite_premium_allowed;
				include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';

				// Detail-Einstellungen (nur wenn Premium aktiv und mind. ein Feature aktiv)
				if ( $lbite_premium_allowed && ( lbite_feature_enabled( 'enable_optimized_checkout' ) || lbite_feature_enabled( 'enable_tips' ) ) ) :
					$lbite_checkout_mode   = get_option( 'lbite_checkout_mode', 'standard' );
					$lbite_tip_pct_1       = get_option( 'lbite_tip_percentage_1', 5 );
					$lbite_tip_pct_2       = get_option( 'lbite_tip_percentage_2', 10 );
					$lbite_tip_pct_3       = get_option( 'lbite_tip_percentage_3', 15 );
					$lbite_tip_select      = get_option( 'lbite_tip_default_selection', 'none' );
					$lbite_tip_mode        = get_option( 'lbite_tip_mode', 'percentage' );
					$lbite_tip_title       = get_option( 'lbite_tip_title', '' );
					$lbite_tip_lbl_none    = get_option( 'lbite_tip_label_none', '' );
					$lbite_tip_lbl_1       = get_option( 'lbite_tip_label_1', '' );
					$lbite_tip_lbl_2       = get_option( 'lbite_tip_label_2', '' );
					$lbite_tip_lbl_3       = get_option( 'lbite_tip_label_3', '' );
					$lbite_tip_is_fixed    = 'fixed' === $lbite_tip_mode;
					?>
					<form method="post" style="margin-top: -8px; margin-bottom: 24px;">
						<?php wp_nonce_field( 'lbite_settings' ); ?>
						<input type="hidden" name="lbite_save_tab" value="checkout">
						<table class="form-table">
							<?php if ( lbite_feature_enabled( 'enable_optimized_checkout' ) ) : ?>
							<tr>
								<th><?php esc_html_e( 'Checkout Mode', 'libre-bite' ); ?></th>
								<td>
									<select name="lbite_checkout_mode">
										<option value="standard" <?php selected( $lbite_checkout_mode, 'standard' ); ?>><?php esc_html_e( 'Standard (all WooCommerce fields)', 'libre-bite' ); ?></option>
										<option value="optimized" <?php selected( $lbite_checkout_mode, 'optimized' ); ?>><?php esc_html_e( 'Optimized (name + receipt option only)', 'libre-bite' ); ?></option>
									</select>
									<p class="description"><?php esc_html_e( 'In optimized mode, only the name is requested and whether a receipt by email is desired.', 'libre-bite' ); ?></p>
									<div class="notice notice-warning inline" style="margin: 8px 0 0; padding: 8px 12px;">
										<p><strong><?php esc_html_e( 'Important:', 'libre-bite' ); ?></strong> <?php esc_html_e( 'The optimized checkout only works with the classic WooCommerce shortcode. Your checkout page must contain the shortcode', 'libre-bite' ); ?> <code>[woocommerce_checkout]</code><?php esc_html_e( ', not the WooCommerce Checkout Block.', 'libre-bite' ); ?></p>
									</div>
								</td>
							</tr>
							<?php endif; ?>
							<?php if ( lbite_feature_enabled( 'enable_tips' ) ) : ?>
							<tr><td colspan="2"><hr style="margin: 8px 0;"><strong><?php esc_html_e( 'Tip Settings', 'libre-bite' ); ?></strong></td></tr>
							<tr>
								<th><?php esc_html_e( 'Tip Title', 'libre-bite' ); ?></th>
								<td>
									<input type="text" name="lbite_tip_title" value="<?php echo esc_attr( $lbite_tip_title ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Add a tip?', 'libre-bite' ); ?>">
									<p class="description"><?php esc_html_e( 'Heading shown above the tip options. Leave empty to use the default.', 'libre-bite' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( '"No Tip" Label', 'libre-bite' ); ?></th>
								<td>
									<input type="text" name="lbite_tip_label_none" value="<?php echo esc_attr( $lbite_tip_lbl_none ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'No tip', 'libre-bite' ); ?>">
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Tip Mode', 'libre-bite' ); ?></th>
								<td>
									<label style="margin-right: 16px;">
										<input type="radio" name="lbite_tip_mode" value="percentage" <?php checked( $lbite_tip_mode, 'percentage' ); ?>>
										<?php esc_html_e( 'Percentage of order total', 'libre-bite' ); ?>
									</label>
									<label>
										<input type="radio" name="lbite_tip_mode" value="fixed" <?php checked( $lbite_tip_mode, 'fixed' ); ?>>
										<?php esc_html_e( 'Fixed amount', 'libre-bite' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Option 1', 'libre-bite' ); ?></th>
								<td>
									<input type="number" step="0.01" min="0" name="lbite_tip_percentage_1" value="<?php echo esc_attr( $lbite_tip_pct_1 ); ?>" class="small-text">
									<span class="lbite-tip-unit"><?php echo $lbite_tip_is_fixed ? esc_html( get_woocommerce_currency_symbol() ) : '%'; ?></span>
									&nbsp;&nbsp;
									<input type="text" name="lbite_tip_label_1" value="<?php echo esc_attr( $lbite_tip_lbl_1 ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Custom label (optional)', 'libre-bite' ); ?>">
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Option 2', 'libre-bite' ); ?></th>
								<td>
									<input type="number" step="0.01" min="0" name="lbite_tip_percentage_2" value="<?php echo esc_attr( $lbite_tip_pct_2 ); ?>" class="small-text">
									<span class="lbite-tip-unit"><?php echo $lbite_tip_is_fixed ? esc_html( get_woocommerce_currency_symbol() ) : '%'; ?></span>
									&nbsp;&nbsp;
									<input type="text" name="lbite_tip_label_2" value="<?php echo esc_attr( $lbite_tip_lbl_2 ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Custom label (optional)', 'libre-bite' ); ?>">
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Option 3', 'libre-bite' ); ?></th>
								<td>
									<input type="number" step="0.01" min="0" name="lbite_tip_percentage_3" value="<?php echo esc_attr( $lbite_tip_pct_3 ); ?>" class="small-text">
									<span class="lbite-tip-unit"><?php echo $lbite_tip_is_fixed ? esc_html( get_woocommerce_currency_symbol() ) : '%'; ?></span>
									&nbsp;&nbsp;
									<input type="text" name="lbite_tip_label_3" value="<?php echo esc_attr( $lbite_tip_lbl_3 ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Custom label (optional)', 'libre-bite' ); ?>">
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Default Selection', 'libre-bite' ); ?></th>
								<td>
									<select name="lbite_tip_default_selection">
										<option value="none" <?php selected( $lbite_tip_select, 'none' ); ?>><?php esc_html_e( 'No Tip (Default)', 'libre-bite' ); ?></option>
										<option value="percentage_1" <?php selected( $lbite_tip_select, 'percentage_1' ); ?>><?php esc_html_e( 'Option 1', 'libre-bite' ); ?></option>
										<option value="percentage_2" <?php selected( $lbite_tip_select, 'percentage_2' ); ?>><?php esc_html_e( 'Option 2', 'libre-bite' ); ?></option>
										<option value="percentage_3" <?php selected( $lbite_tip_select, 'percentage_3' ); ?>><?php esc_html_e( 'Option 3', 'libre-bite' ); ?></option>
									</select>
								</td>
							</tr>
							<?php endif; ?>
						</table>
						<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
					</form>
					<script>
					(function() {
						var radios = document.querySelectorAll('input[name="lbite_tip_mode"]');
						var units  = document.querySelectorAll('.lbite-tip-unit');
						var currency = <?php echo wp_json_encode( get_woocommerce_currency_symbol() ); ?>;
						radios.forEach(function(r) {
							r.addEventListener('change', function() {
								var isFixed = this.value === 'fixed';
								units.forEach(function(u) { u.textContent = isFixed ? currency : '%'; });
							});
						});
					})();
					</script>
				<?php endif; ?>

				<hr style="margin: 24px 0;">
				<h3><?php esc_html_e( 'Fields in Standard Checkout', 'libre-bite' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Choose which fields and options are displayed at checkout.', 'libre-bite' ); ?></p>
				<?php
				include LBITE_PLUGIN_DIR . 'templates/admin/checkout-fields.php';
				break;

			case 'orders':
			// Master-Toggle: Kanban-Board
			$lbite_toggle_key         = 'enable_kanban_board';
			$lbite_toggle_label       = __( 'Order Overview (Kanban)', 'libre-bite' );
			$lbite_toggle_description = __( 'Display incoming orders as a kanban board for quick status management.', 'libre-bite' );
			$lbite_toggle_is_pro      = false;
			include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';

			$lbite_refresh    = get_option( 'lbite_dashboard_refresh_interval', 30 );
			$lbite_res_refresh = get_option( 'lbite_reservation_refresh_interval', 60 );
			?>
			<form method="post">
				<?php wp_nonce_field( 'lbite_settings' ); ?>
				<input type="hidden" name="lbite_save_tab" value="orders">

				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Order Overview Refresh Interval', 'libre-bite' ); ?></th>
						<td>
							<input type="number" min="10" name="lbite_dashboard_refresh_interval" value="<?php echo esc_attr( $lbite_refresh ); ?>" class="small-text"> <?php esc_html_e( 'Seconds', 'libre-bite' ); ?>
							<p class="description"><?php esc_html_e( 'How often the order overview checks for new orders.', 'libre-bite' ); ?></p>
						</td>
					</tr>
					<?php if ( lbite_feature_enabled( 'enable_reservations' ) ) : ?>
					<tr>
						<th><?php esc_html_e( 'Reservations Overview Refresh Interval', 'libre-bite' ); ?></th>
						<td>
							<input type="number" min="10" name="lbite_reservation_refresh_interval" value="<?php echo esc_attr( $lbite_res_refresh ); ?>" class="small-text"> <?php esc_html_e( 'Seconds', 'libre-bite' ); ?>
							<p class="description"><?php esc_html_e( 'How often the reservations overview is updated. Default: 60 seconds.', 'libre-bite' ); ?></p>
						</td>
					</tr>
					<?php endif; ?>
					<tr>
						<th>
							<?php esc_html_e( 'Show Future Pre-orders', 'libre-bite' ); ?>
							<?php if ( ! $lbite_premium_allowed ) : ?>
								<span class="lbite-pro-badge">Pro</span>
							<?php endif; ?>
						</th>
						<td>
							<label class="<?php echo $lbite_premium_allowed ? '' : 'lbite-locked'; ?>">
								<input type="checkbox" name="lbite_show_future_orders" value="1"
									<?php checked( get_option( 'lbite_show_future_orders', 1 ), 1 ); ?>
									<?php disabled( ! $lbite_premium_allowed ); ?>>
								<?php esc_html_e( 'Show pre-orders with a pickup time further in the future than the preparation time in the Kanban board.', 'libre-bite' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e( 'Dim Future Pre-orders', 'libre-bite' ); ?>
							<?php if ( ! $lbite_premium_allowed ) : ?>
								<span class="lbite-pro-badge">Pro</span>
							<?php endif; ?>
						</th>
						<td>
							<label class="<?php echo $lbite_premium_allowed ? '' : 'lbite-locked'; ?>">
								<input type="checkbox" name="lbite_dim_future_orders" value="1"
									<?php checked( get_option( 'lbite_dim_future_orders', 1 ), 1 ); ?>
									<?php disabled( ! $lbite_premium_allowed ); ?>>
								<?php esc_html_e( 'Display future pre-orders dimmed (greyed out) in the Kanban board.', 'libre-bite' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
			</form>
			<?php
			break;

			case 'pos':
				// Master-Toggle: POS-System
				$lbite_toggle_key         = 'enable_pos';
				$lbite_toggle_label       = __( 'POS System', 'libre-bite' );
				$lbite_toggle_description = __( 'Enable the Point of Sale interface for in-person orders.', 'libre-bite' );
				$lbite_toggle_is_pro      = false;
				include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';

				$lbite_pos_methods_default = array(
					array( 'key' => 'cash',  'label' => __( 'Cash', 'libre-bite' ),  'icon' => '💵', 'enabled' => true ),
					array( 'key' => 'card',  'label' => __( 'Card', 'libre-bite' ),  'icon' => '💳', 'enabled' => true ),
					array( 'key' => 'twint', 'label' => __( 'Twint', 'libre-bite' ), 'icon' => '📱', 'enabled' => true ),
					array( 'key' => 'other', 'label' => __( 'Other', 'libre-bite' ), 'icon' => '💱', 'enabled' => true ),
				);
				$lbite_saved_pos_methods = get_option( 'lbite_pos_payment_methods', array() );
				$lbite_pos_payment_methods = array();
				foreach ( $lbite_pos_methods_default as $lbite_pos_def ) {
					$lbite_pos_s = array_filter( $lbite_saved_pos_methods, fn( $m ) => $m['key'] === $lbite_pos_def['key'] );
					$lbite_pos_s = $lbite_pos_s ? array_values( $lbite_pos_s )[0] : array();
					$lbite_pos_payment_methods[] = array(
						'key'     => $lbite_pos_def['key'],
						'label'   => ! empty( $lbite_pos_s['label'] ) ? $lbite_pos_s['label'] : $lbite_pos_def['label'],
						'icon'    => $lbite_pos_def['icon'],
						'enabled' => isset( $lbite_pos_s['enabled'] ) ? (bool) $lbite_pos_s['enabled'] : $lbite_pos_def['enabled'],
					);
				}
				?>
				<form method="post">
					<?php wp_nonce_field( 'lbite_settings' ); ?>
					<input type="hidden" name="lbite_save_tab" value="pos">

					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Payment Methods', 'libre-bite' ); ?></th>
							<td>
								<p class="description" style="margin-bottom: 12px;"><?php esc_html_e( 'Choose which payment methods are displayed in the POS payment modal and customize the labels.', 'libre-bite' ); ?></p>
								<table class="widefat" style="max-width: 480px;">
									<thead>
										<tr>
											<th style="width: 40px;"><?php esc_html_e( 'Active', 'libre-bite' ); ?></th>
											<th style="width: 32px;"></th>
											<th><?php esc_html_e( 'Label', 'libre-bite' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $lbite_pos_payment_methods as $lbite_pm ) : ?>
										<tr>
											<td><input type="checkbox" name="lbite_pm_enabled[<?php echo esc_attr( $lbite_pm['key'] ); ?>]" value="1" <?php checked( $lbite_pm['enabled'] ); ?>></td>
											<td style="font-size: 20px; text-align: center; line-height: 1;"><?php echo esc_html( $lbite_pm['icon'] ); ?></td>
											<td><input type="text" name="lbite_pm_label[<?php echo esc_attr( $lbite_pm['key'] ); ?>]" value="<?php echo esc_attr( $lbite_pm['label'] ); ?>" class="regular-text" style="max-width: 200px;"></td>
										</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
								<p class="description" style="margin-top: 8px;"><?php esc_html_e( 'At least one payment method must be active.', 'libre-bite' ); ?></p>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
				</form>
				<?php
				break;


			case 'roles':
				if ( $lbite_is_admin ) {
					include LBITE_PLUGIN_DIR . 'templates/admin/admin-settings.php';
				}
				break;

			case 'support':
				if ( $lbite_is_admin ) {
					include LBITE_PLUGIN_DIR . 'templates/admin/support-settings.php';
				}
				break;

			case 'data':
				if ( $lbite_is_admin ) :
					$lbite_delete_data = get_option( 'lbite_delete_data_on_uninstall', false );
					?>
					<form method="post">
						<?php wp_nonce_field( 'lbite_settings' ); ?>
						<input type="hidden" name="lbite_save_tab" value="data">

						<h2><?php esc_html_e( 'Uninstallation', 'libre-bite' ); ?></h2>
						<table class="form-table">
							<tr>
								<th><?php esc_html_e( 'Delete Data on Uninstall', 'libre-bite' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="lbite_delete_data_on_uninstall" value="1" <?php checked( $lbite_delete_data ); ?>>
										<?php esc_html_e( 'Completely delete all plugin data on uninstall', 'libre-bite' ); ?>
									</label>
									<p class="description" style="color: #d63638;">
										<strong><?php esc_html_e( 'Important:', 'libre-bite' ); ?></strong>
										<?php esc_html_e( 'This option will permanently delete all locations, product options, settings, and order metadata!', 'libre-bite' ); ?>
									</p>
								</td>
							</tr>
						</table>
						<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
					</form>
					<?php
				endif;
				break;
		}
		?>
	</div>
</div>
