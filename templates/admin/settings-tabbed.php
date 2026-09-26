<?php
/**
 * Template: Einstellungen (vertikale Navigation)
 *
 * Konsolidiert alle Einstellungsseiten in einer Ansicht mit vertikaler,
 * gruppierter Navigation (v3.2.0 - vorher horizontale WP-Tabs; weitere
 * Aufteilung in kürzere Unterseiten direkt danach, siehe git-Historie).
 * Feature-abhängige Einträge erscheinen nur, wenn das entsprechende Feature
 * aktiv ist. Tab-Wechsel bleibt ein normaler Seiten-Reload
 * (?page=lbite-settings&tab=X) - kein SPA-Umbau, kein Risiko von verlorenem
 * Formularstate.
 *
 * Jede Unterseite hat ihr eigenes <form>, einen eigenen lbite_save_tab-Wert
 * und einen eigenen, auf ihre eigenen Felder beschränkten Save-Case -
 * bewusst so, damit das Absenden einer Unterseite keine benachbarte
 * Einstellung stillschweigend zurücksetzt (siehe v2.4.0-Kanban-Spalten-
 * Vorfall in der Projekthistorie).
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_is_admin        = current_user_can( 'manage_options' );
$lbite_premium_allowed = function_exists( 'lbite_freemius' ) && lbite_freemius()->can_use_premium_code__premium_only();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Anzeigesteuerung.
$lbite_active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'locations';

// Gruppierte Navigationsstruktur. Jede Gruppe hat ein Label und eine
// geordnete Liste von Tabs; jeder Tab ein Dashicon und ein Label. Die
// Reihenfolge der Gruppen folgt dem Weg, den ein Neukunde beim Einrichten
// geht: erst das Geschäft aufsetzen, dann den laufenden Betrieb
// konfigurieren, dann Marketing/Darstellung/System.
$lbite_tab_groups = array(
	'setup' => array(
		'label' => __( 'Setup', 'libre-bite' ),
		'tabs'  => array(
			'locations'             => array(
				'label' => __( 'Locations', 'libre-bite' ),
				'icon'  => 'dashicons-store',
			),
			'holidays'              => array(
				'label' => __( 'Holidays', 'libre-bite' ),
				'icon'  => 'dashicons-calendar',
			),
			'products_options'      => array(
				'label' => __( 'Product Options', 'libre-bite' ),
				'icon'  => 'dashicons-carrot',
			),
			'products_notes'        => array(
				'label' => __( 'Item Notes', 'libre-bite' ),
				'icon'  => 'dashicons-edit-page',
			),
			'products_menu'         => array(
				'label' => __( 'Menu Display', 'libre-bite' ),
				'icon'  => 'dashicons-book-alt',
				'pro'   => true,
			),
			'products_nutrition'    => array(
				'label' => __( 'Nutritional & Dietary', 'libre-bite' ),
				'icon'  => 'dashicons-heart',
				'pro'   => true,
			),
			'products_availability' => array(
				'label' => __( 'Availability Hint & Filter', 'libre-bite' ),
				'icon'  => 'dashicons-visibility',
				'pro'   => true,
			),
			'products_order'        => array(
				'label' => __( 'Product Order', 'libre-bite' ),
				'icon'  => 'dashicons-sort',
			),
			'checkout_mode'         => array(
				'label' => __( 'Checkout', 'libre-bite' ),
				'icon'  => 'dashicons-cart',
			),
			'checkout_tips'         => array(
				'label' => __( 'Tips', 'libre-bite' ),
				'icon'  => 'dashicons-money-alt',
				'pro'   => true,
			),
			'checkout_fields'       => array(
				'label' => __( 'Checkout Fields', 'libre-bite' ),
				'icon'  => 'dashicons-forms',
			),
			'prices_taxes'          => array(
				'label' => __( 'Prices & Taxes', 'libre-bite' ),
				'icon'  => 'dashicons-calculator',
			),
		),
	),
	'operations' => array(
		'label' => __( 'Operations', 'libre-bite' ),
		'tabs'  => array(
			'orders_board'   => array(
				'label' => __( 'Order Board', 'libre-bite' ),
				'icon'  => 'dashicons-clipboard',
			),
			'orders_columns' => array(
				'label' => __( 'Kanban Columns', 'libre-bite' ),
				'icon'  => 'dashicons-columns',
				'pro'   => true,
			),
			'orders_receipts' => array(
				'label' => __( 'Receipts', 'libre-bite' ),
				'icon'  => 'dashicons-media-text',
			),
			'pos'            => array(
				'label' => __( 'POS System', 'libre-bite' ),
				'icon'  => 'dashicons-tablet',
			),
			'tables'         => array(
				'label' => __( 'Tables', 'libre-bite' ),
				'icon'  => 'dashicons-layout',
				'pro'   => true,
			),
			'reservations'   => array(
				'label' => __( 'Reservations', 'libre-bite' ),
				'icon'  => 'dashicons-calendar-alt',
				'pro'   => true,
			),
		),
	),
	'marketing' => array(
		'label' => __( 'Marketing & Communication', 'libre-bite' ),
		'tabs'  => array(
			'marketing_bumps'      => array(
				'label' => __( 'Order Bumps', 'libre-bite' ),
				'icon'  => 'dashicons-plus-alt',
				'pro'   => true,
			),
			'marketing_promotions' => array(
				'label' => __( 'Promotions', 'libre-bite' ),
				'icon'  => 'dashicons-tag',
				'pro'   => true,
			),
			'marketing_banner'     => array(
				'label' => __( 'Announcement Bar', 'libre-bite' ),
				'icon'  => 'dashicons-megaphone',
			),
			'marketing_stampcard'  => array(
				'label' => __( 'Stamp Card', 'libre-bite' ),
				'icon'  => 'dashicons-tickets-alt',
				'pro'   => true,
			),
			'notifications'        => array(
				'label' => __( 'Notifications', 'libre-bite' ),
				'icon'  => 'dashicons-bell',
			),
		),
	),
	'appearance' => array(
		'label' => __( 'Appearance', 'libre-bite' ),
		'tabs'  => array(
			'branding' => array(
				'label' => __( 'Branding', 'libre-bite' ),
				'icon'  => 'dashicons-admin-customizer',
			),
		),
	),
);

if ( $lbite_is_admin ) {
	$lbite_tab_groups['system'] = array(
		'label' => __( 'System', 'libre-bite' ),
		'tabs'  => array(
			'roles_access'   => array(
				'label' => __( 'Standard Role Access', 'libre-bite' ),
				'icon'  => 'dashicons-admin-users',
			),
			'roles_names'    => array(
				'label' => __( 'Manage User Roles', 'libre-bite' ),
				'icon'  => 'dashicons-id',
			),
			'roles_menus'    => array(
				'label' => __( 'Menu Visibility', 'libre-bite' ),
				'icon'  => 'dashicons-menu-alt',
			),
			'roles_managers' => array(
				'label' => __( 'Manager Assignments', 'libre-bite' ),
				'icon'  => 'dashicons-groups',
				'pro'   => true,
			),
			'content_account' => array(
				'label' => __( 'Content & Account', 'libre-bite' ),
				'icon'  => 'dashicons-admin-post',
			),
			'data'           => array(
				'label' => __( 'Uninstallation', 'libre-bite' ),
				'icon'  => 'dashicons-trash',
			),
			'support'        => array(
				'label' => __( 'Support', 'libre-bite' ),
				'icon'  => 'dashicons-sos',
			),
			'developer'      => array(
				'label' => __( 'Developer', 'libre-bite' ),
				'icon'  => 'dashicons-editor-code',
			),
		),
	);
}

// Flache Tab-Liste (Key => Label) aus der Gruppenstruktur ableiten - wird für
// die Validierung des aktiven Tabs gebraucht, ohne die Gruppierung selbst
// beim Speichern/Redirect verwalten zu müssen.
$lbite_tabs = array();
foreach ( $lbite_tab_groups as $lbite_group ) {
	foreach ( $lbite_group['tabs'] as $lbite_tab_key => $lbite_tab_def ) {
		$lbite_tabs[ $lbite_tab_key ] = $lbite_tab_def['label'];
	}
}

// Aktiven Tab validieren
if ( ! array_key_exists( $lbite_active_tab, $lbite_tabs ) ) {
	$lbite_active_tab = 'locations';
}

// Die Save-Logik läuft nicht mehr hier: WordPress gibt admin-header.php
// bereits aus, bevor dieses Template eingebunden wird, wp_safe_redirect()
// nach dem Speichern träfe also auf bereits gesendete Header (Audit
// 26.09.2026, AP-05). Sie läuft jetzt in templates/admin/settings-save.php
// über LBite_Admin::maybe_save_settings() auf dem load-{page_hook}-Hook,
// lange bevor admin-header.php etwas ausgibt.

$lbite_plugin_name  = apply_filters( 'lbite_plugin_display_name', __( 'Libre Bite', 'libre-bite' ) );
$lbite_settings_url = admin_url( 'admin.php?page=lbite-settings' );
?>

<div class="wrap lbite-settings-wrap">
	<h1>
		<?php echo esc_html( $lbite_plugin_name . ' – ' . __( 'Settings', 'libre-bite' ) ); ?>
		<?php if ( class_exists( 'LBite_Setup_Wizard' ) && LBite_Setup_Wizard::current_user_can_run() ) : ?>
			<a href="<?php echo esc_url( LBite_Setup_Wizard::get_url() ); ?>" class="button" style="vertical-align: middle; margin-left: 12px; font-size: 13px;">
				<?php esc_html_e( 'Run setup assistant', 'libre-bite' ); ?>
			</a>
		<?php endif; ?>
	</h1>

	<?php if ( get_option( 'lbite_show_welcome_notice' ) ) : ?>
	<div class="lbite-welcome-notice" id="lbite-welcome-notice">
		<div class="lbite-welcome-notice__content">
			<h2><?php esc_html_e( 'Welcome to Libre Bite!', 'libre-bite' ); ?></h2>
			<p><?php esc_html_e( 'Configure each area of the plugin using the navigation on the left. Core features are active by default – you can adjust them at any time.', 'libre-bite' ); ?></p>
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

	<div class="lbite-settings-layout">
		<nav class="lbite-settings-nav" aria-label="<?php esc_attr_e( 'Settings sections', 'libre-bite' ); ?>">
			<?php foreach ( $lbite_tab_groups as $lbite_group ) : ?>
				<div class="lbite-settings-nav__group">
					<p class="lbite-settings-nav__group-title"><?php echo esc_html( $lbite_group['label'] ); ?></p>
					<?php foreach ( $lbite_group['tabs'] as $lbite_tab_key => $lbite_tab_def ) : ?>
						<a
							href="<?php echo esc_url( add_query_arg( 'tab', $lbite_tab_key, $lbite_settings_url ) ); ?>"
							class="lbite-settings-nav__item <?php echo $lbite_active_tab === $lbite_tab_key ? 'is-active' : ''; ?>"
						>
							<span class="dashicons <?php echo esc_attr( $lbite_tab_def['icon'] ); ?>" aria-hidden="true"></span>
							<span class="lbite-settings-nav__item-label"><?php echo esc_html( $lbite_tab_def['label'] ); ?></span>
							<?php if ( ! empty( $lbite_tab_def['pro'] ) && ! $lbite_premium_allowed ) : ?>
								<span class="lbite-pro-badge">Pro</span>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</nav>

		<div class="lbite-settings-content">
			<?php
			// $lbite_is_tab = true verhindert doppelte <div class="wrap"> und <h1> in Sub-Templates
			$lbite_is_tab = true;

			switch ( $lbite_active_tab ) {
				case 'locations':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/locations.php';
					break;

				case 'products_options':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/products-options.php';
					break;

				case 'products_notes':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/products-notes.php';
					break;

				case 'products_menu':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/products-menu.php';
					break;

				case 'products_nutrition':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/products-nutrition.php';
					break;

				case 'products_availability':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/products-availability.php';
					break;

				case 'products_order':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/products-order.php';
					break;

				case 'checkout_mode':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/checkout-mode.php';
					break;

				case 'checkout_tips':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/checkout-tips.php';
					break;

				case 'checkout_fields':
					include LBITE_PLUGIN_DIR . 'templates/admin/checkout-fields.php';
					break;

				case 'prices_taxes':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/prices-taxes.php';
					break;

				case 'orders_board':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/orders-board.php';
					break;

				case 'orders_columns':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/orders-columns.php';
					break;

				case 'orders_receipts':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/receipts.php';
					break;

				case 'pos':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/pos.php';
					break;

				case 'marketing_bumps':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/marketing-bumps.php';
					break;

				case 'marketing_promotions':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/marketing-promotions.php';
					break;

				case 'marketing_banner':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/marketing-banner.php';
					break;

				case 'marketing_stampcard':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/marketing-stampcard.php';
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

				case 'branding':
					include LBITE_PLUGIN_DIR . 'templates/admin/settings/branding.php';
					break;

				case 'holidays':
					include LBITE_PLUGIN_DIR . 'templates/admin/holidays-settings.php';
					break;

				case 'roles_access':
					if ( $lbite_is_admin ) {
						include LBITE_PLUGIN_DIR . 'templates/admin/settings/roles-access.php';
					}
					break;

				case 'roles_names':
					if ( $lbite_is_admin ) {
						include LBITE_PLUGIN_DIR . 'templates/admin/settings/roles-names.php';
					}
					break;

				case 'roles_menus':
					if ( $lbite_is_admin ) {
						include LBITE_PLUGIN_DIR . 'templates/admin/settings/roles-menus.php';
					}
					break;

				case 'roles_managers':
					if ( $lbite_is_admin ) {
						include LBITE_PLUGIN_DIR . 'templates/admin/settings/roles-managers.php';
					}
					break;

				case 'content_account':
					if ( $lbite_is_admin ) {
						include LBITE_PLUGIN_DIR . 'templates/admin/settings/content-account.php';
					}
					break;

				case 'data':
					if ( $lbite_is_admin ) {
						include LBITE_PLUGIN_DIR . 'templates/admin/settings/uninstall.php';
					}
					break;

				case 'support':
					if ( $lbite_is_admin ) {
						include LBITE_PLUGIN_DIR . 'templates/admin/support-settings.php';
					}
					break;

				case 'developer':
					if ( $lbite_is_admin ) {
						include LBITE_PLUGIN_DIR . 'templates/admin/settings/developer.php';
					}
					break;
			}
			?>
		</div>
	</div>
</div>
