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

$lbite_is_admin = current_user_can( 'manage_options' );

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Anzeigesteuerung.
$lbite_active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ( $lbite_is_admin ? 'features' : 'general' );

// Tabs definieren – Reihenfolge: Features zuerst, dann Allgemein, dann Feature-Tabs, dann Admin-Tabs
$lbite_tabs = array();

if ( $lbite_is_admin ) {
	$lbite_tabs['features'] = __( 'Features', 'libre-bite' );
}

$lbite_tabs['general'] = __( 'General', 'libre-bite' );

if ( lbite_feature_enabled( 'enable_tips' ) ) {
	$lbite_tabs['tips'] = __( 'Tip', 'libre-bite' );
}

// Checkout-Tab immer anzeigen (enthält Checkout-Felder + ggf. Optimierter Checkout)
$lbite_tabs['checkout'] = __( 'Checkout', 'libre-bite' );

if ( lbite_feature_enabled( 'enable_kanban_board' ) || lbite_feature_enabled( 'enable_sound_notifications' ) ) {
	$lbite_tabs['orders_settings'] = __( 'Order Overview', 'libre-bite' );
}

if ( lbite_feature_enabled( 'enable_pos' ) ) {
	$lbite_tabs['pos'] = __( 'POS System', 'libre-bite' );
}

if ( $lbite_is_admin ) {
	$lbite_tabs['roles']   = __( 'Roles & Menus', 'libre-bite' );
	$lbite_tabs['support'] = __( 'Support', 'libre-bite' );
	$lbite_tabs['data']    = __( 'Data', 'libre-bite' );
}

// Aktiven Tab validieren
if ( ! array_key_exists( $lbite_active_tab, $lbite_tabs ) ) {
	$lbite_active_tab = 'general';
}

// Save-Logik für Feature-Tabs (tips, checkout, dashboard, pos, data)
// Die Save-Logik für 'general' liegt in settings.php
if ( isset( $_POST['lbite_save_settings'] ) && check_admin_referer( 'lbite_settings' ) ) {
	$lbite_save_tab = isset( $_POST['lbite_save_tab'] ) ? sanitize_key( wp_unslash( $_POST['lbite_save_tab'] ) ) : '';
	$lbite_did_save = false;

	switch ( $lbite_save_tab ) {
		case 'tips':
			update_option( 'lbite_tip_percentage_1', isset( $_POST['lbite_tip_percentage_1'] ) ? floatval( wp_unslash( $_POST['lbite_tip_percentage_1'] ) ) : 5 );
			update_option( 'lbite_tip_percentage_2', isset( $_POST['lbite_tip_percentage_2'] ) ? floatval( wp_unslash( $_POST['lbite_tip_percentage_2'] ) ) : 10 );
			update_option( 'lbite_tip_percentage_3', isset( $_POST['lbite_tip_percentage_3'] ) ? floatval( wp_unslash( $_POST['lbite_tip_percentage_3'] ) ) : 15 );
			update_option( 'lbite_tip_default_selection', isset( $_POST['lbite_tip_default_selection'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_tip_default_selection'] ) ) : 'none' );
			$lbite_did_save = true;
			break;

		case 'checkout':
			update_option( 'lbite_enable_rounding', isset( $_POST['lbite_enable_rounding'] ) );
			update_option( 'lbite_checkout_mode', isset( $_POST['lbite_checkout_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_checkout_mode'] ) ) : 'standard' );
			$lbite_did_save = true;
			break;

		case 'orders_settings':
			update_option( 'lbite_dashboard_refresh_interval', isset( $_POST['lbite_dashboard_refresh_interval'] ) ? intval( wp_unslash( $_POST['lbite_dashboard_refresh_interval'] ) ) : 30 );
			update_option( 'lbite_reservation_refresh_interval', isset( $_POST['lbite_reservation_refresh_interval'] ) ? intval( wp_unslash( $_POST['lbite_reservation_refresh_interval'] ) ) : 60 );
			update_option( 'lbite_notification_sound', isset( $_POST['lbite_notification_sound'] ) ? esc_url_raw( wp_unslash( $_POST['lbite_notification_sound'] ) ) : '' );
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

			case 'tips':
				$lbite_tip_pct_1  = get_option( 'lbite_tip_percentage_1', 5 );
				$lbite_tip_pct_2  = get_option( 'lbite_tip_percentage_2', 10 );
				$lbite_tip_pct_3  = get_option( 'lbite_tip_percentage_3', 15 );
				$lbite_tip_select = get_option( 'lbite_tip_default_selection', 'none' );
				?>
				<form method="post">
					<?php wp_nonce_field( 'lbite_settings' ); ?>
					<input type="hidden" name="lbite_save_tab" value="tips">

					<h2><?php esc_html_e( 'Tip Settings', 'libre-bite' ); ?></h2>
					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Percentage 1', 'libre-bite' ); ?></th>
							<td><input type="number" step="0.1" min="0" name="lbite_tip_percentage_1" value="<?php echo esc_attr( $lbite_tip_pct_1 ); ?>" class="small-text"> %</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Percentage 2', 'libre-bite' ); ?></th>
							<td><input type="number" step="0.1" min="0" name="lbite_tip_percentage_2" value="<?php echo esc_attr( $lbite_tip_pct_2 ); ?>" class="small-text"> %</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Percentage 3', 'libre-bite' ); ?></th>
							<td><input type="number" step="0.1" min="0" name="lbite_tip_percentage_3" value="<?php echo esc_attr( $lbite_tip_pct_3 ); ?>" class="small-text"> %</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Default Selection', 'libre-bite' ); ?></th>
							<td>
								<select name="lbite_tip_default_selection">
									<option value="none" <?php selected( $lbite_tip_select, 'none' ); ?>><?php esc_html_e( 'No Tip (Default)', 'libre-bite' ); ?></option>
									<option value="percentage_1" <?php selected( $lbite_tip_select, 'percentage_1' ); ?>><?php echo esc_html( $lbite_tip_pct_1 ); ?>%</option>
									<option value="percentage_2" <?php selected( $lbite_tip_select, 'percentage_2' ); ?>><?php echo esc_html( $lbite_tip_pct_2 ); ?>%</option>
									<option value="percentage_3" <?php selected( $lbite_tip_select, 'percentage_3' ); ?>><?php echo esc_html( $lbite_tip_pct_3 ); ?>%</option>
								</select>
								<p class="description"><?php esc_html_e( 'Which option should be pre-selected by default in checkout?', 'libre-bite' ); ?></p>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
				</form>
				<?php
				break;

			case 'checkout':
				// Checkout-Optionen (Rundung + ggf. Checkout-Modus ganz oben)
				if ( lbite_feature_enabled( 'enable_rounding' ) || lbite_feature_enabled( 'enable_optimized_checkout' ) ) :
					$lbite_checkout_mode   = get_option( 'lbite_checkout_mode', 'standard' );
					$lbite_enable_rounding = get_option( 'lbite_enable_rounding', false );
					?>
					<div class="postbox" style="margin-bottom: 20px;">
						<h2 class="hndle" style="padding: 12px 15px;"><?php esc_html_e( 'Checkout Options', 'libre-bite' ); ?></h2>
						<div class="inside">
							<form method="post">
								<?php wp_nonce_field( 'lbite_settings' ); ?>
								<input type="hidden" name="lbite_save_tab" value="checkout">
								<table class="form-table">
									<?php if ( lbite_feature_enabled( 'enable_rounding' ) ) : ?>
									<tr>
										<th><?php esc_html_e( 'Round Total Amount', 'libre-bite' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="lbite_enable_rounding" value="1" <?php checked( $lbite_enable_rounding ); ?>>
												<?php esc_html_e( 'Round total to 5 cents (0.05 CHF)', 'libre-bite' ); ?>
											</label>
											<p class="description"><?php esc_html_e( 'Prevents rounding errors when combining vouchers and tips. Recommended for Swiss businesses.', 'libre-bite' ); ?></p>
										</td>
									</tr>
									<?php endif; ?>
									<?php if ( lbite_feature_enabled( 'enable_optimized_checkout' ) ) : ?>
									<tr>
										<th><?php esc_html_e( 'Checkout Mode', 'libre-bite' ); ?></th>
										<td>
											<select name="lbite_checkout_mode">
												<option value="standard" <?php selected( $lbite_checkout_mode, 'standard' ); ?>><?php esc_html_e( 'Standard (all WooCommerce fields)', 'libre-bite' ); ?></option>
												<option value="optimized" <?php selected( $lbite_checkout_mode, 'optimized' ); ?>><?php esc_html_e( 'Optimized (name + receipt option only)', 'libre-bite' ); ?></option>
											</select>
											<p class="description"><?php esc_html_e( 'In optimized mode, only the name is requested and whether a receipt by email is desired.', 'libre-bite' ); ?></p>
										</td>
									</tr>
									<?php endif; ?>
								</table>
								<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
							</form>
						</div>
					</div>
					<?php
				endif;

				// Checkout-Felder (Standard-Checkout konfigurieren)
				?>
				<h2><?php esc_html_e( 'Fields in Standard Checkout', 'libre-bite' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Choose which fields and options are displayed at checkout.', 'libre-bite' ); ?></p>
				<?php
				include LBITE_PLUGIN_DIR . 'templates/admin/checkout-fields.php';
				break;

			case 'orders_settings':
				$lbite_refresh              = get_option( 'lbite_dashboard_refresh_interval', 30 );
				$lbite_res_refresh          = get_option( 'lbite_reservation_refresh_interval', 60 );
				$lbite_default_sound_url    = LBITE_PLUGIN_URL . 'assets/sounds/notification.mp3';
				$lbite_default_sound_exists = file_exists( LBITE_PLUGIN_DIR . 'assets/sounds/notification.mp3' );
				$lbite_notification_sound   = get_option( 'lbite_notification_sound', $lbite_default_sound_exists ? $lbite_default_sound_url : '' );
				wp_enqueue_media();
				?>
				<form method="post">
					<?php wp_nonce_field( 'lbite_settings' ); ?>
					<input type="hidden" name="lbite_save_tab" value="orders_settings">

					<h2><?php esc_html_e( 'Order Overview Settings', 'libre-bite' ); ?></h2>
					<table class="form-table">
						<?php if ( lbite_feature_enabled( 'enable_kanban_board' ) ) : ?>
						<tr>
							<th><?php esc_html_e( 'Order Overview Refresh Interval', 'libre-bite' ); ?></th>
							<td>
								<input type="number" min="10" name="lbite_dashboard_refresh_interval" value="<?php echo esc_attr( $lbite_refresh ); ?>" class="small-text"> <?php esc_html_e( 'Seconds', 'libre-bite' ); ?>
								<p class="description"><?php esc_html_e( 'How often the order overview checks for new orders.', 'libre-bite' ); ?></p>
							</td>
						</tr>
						<?php endif; ?>
						<?php if ( lbite_feature_enabled( 'enable_reservations' ) ) : ?>
						<tr>
							<th><?php esc_html_e( 'Reservations Overview Refresh Interval', 'libre-bite' ); ?></th>
							<td>
								<input type="number" min="10" name="lbite_reservation_refresh_interval" value="<?php echo esc_attr( $lbite_res_refresh ); ?>" class="small-text"> <?php esc_html_e( 'Seconds', 'libre-bite' ); ?>
								<p class="description"><?php esc_html_e( 'How often the reservations overview is updated. Default: 60 seconds.', 'libre-bite' ); ?></p>
							</td>
						</tr>
						<?php endif; ?>
						<?php if ( lbite_feature_enabled( 'enable_sound_notifications' ) ) : ?>
						<tr>
							<th><?php esc_html_e( 'Notification Sound', 'libre-bite' ); ?></th>
							<td>
								<div style="display: flex; align-items: center; gap: 10px;">
									<input type="text" id="lbite_notification_sound" name="lbite_notification_sound" value="<?php echo esc_attr( $lbite_notification_sound ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Sound URL', 'libre-bite' ); ?>" readonly>
									<button type="button" class="button" id="lbite_upload_sound_button"><?php esc_html_e( 'Select Sound', 'libre-bite' ); ?></button>
									<button type="button" class="button" id="lbite_remove_sound_button" <?php echo empty( $lbite_notification_sound ) ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Remove', 'libre-bite' ); ?></button>
								</div>
								<?php if ( $lbite_notification_sound ) : ?>
									<audio id="lbite_sound_preview" controls style="margin-top: 10px; max-width: 300px;">
										<source src="<?php echo esc_url( $lbite_notification_sound ); ?>" type="audio/mpeg">
									</audio>
								<?php endif; ?>
								<p class="description">
									<?php echo esc_html( $lbite_default_sound_exists ? __( 'Default sound is available. You can also select your own sound from the media library.', 'libre-bite' ) : __( 'Select a sound from your media library.', 'libre-bite' ) ); ?>
								</p>
							</td>
						</tr>
						<?php endif; ?>
					</table>
					<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
				</form>
				<?php ob_start(); ?>
jQuery(document).ready(function($) {
	var lbiteSoundFrame;
	$('#lbite_upload_sound_button').on('click', function(e) {
		e.preventDefault();
		if (lbiteSoundFrame) { lbiteSoundFrame.open(); return; }
		lbiteSoundFrame = wp.media({
			title: '<?php esc_html_e( 'Select Sound File', 'libre-bite' ); ?>',
			button: { text: '<?php esc_html_e( 'Use Sound', 'libre-bite' ); ?>' },
			library: { type: ['audio'] },
			multiple: false
		});
		lbiteSoundFrame.on('select', function() {
			var attachment = lbiteSoundFrame.state().get('selection').first().toJSON();
			$('#lbite_notification_sound').val(attachment.url);
			$('#lbite_remove_sound_button').show();
			var preview = $('#lbite_sound_preview');
			if (preview.length) { preview.find('source').attr('src', attachment.url); preview[0].load(); }
			else { $('#lbite_remove_sound_button').after('<audio id="lbite_sound_preview" controls style="margin-top:10px;max-width:300px;"><source src="' + attachment.url + '" type="audio/mpeg"></audio>'); }
		});
		lbiteSoundFrame.open();
	});
	$('#lbite_remove_sound_button').on('click', function(e) {
		e.preventDefault();
		$('#lbite_notification_sound').val('');
		$(this).hide();
		$('#lbite_sound_preview').remove();
	});
});
				<?php wp_add_inline_script( 'lbite-admin', ob_get_clean() ); ?>
				<?php
				break;

			case 'pos':
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

					<h2><?php esc_html_e( 'POS System', 'libre-bite' ); ?></h2>
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


			case 'features':
				if ( $lbite_is_admin ) {
					include LBITE_PLUGIN_DIR . 'templates/admin/super-admin-settings.php';
				}
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
