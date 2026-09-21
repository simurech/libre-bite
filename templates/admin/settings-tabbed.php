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
			'holidays' => array(
				'label' => __( 'Holidays', 'libre-bite' ),
				'icon'  => 'dashicons-calendar',
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

// Save-Logik. Jeder Case ist strikt auf die Felder beschränkt, die auf der
// jeweiligen Unterseite tatsächlich vorkommen.
if ( isset( $_POST['lbite_save_settings'] ) && check_admin_referer( 'lbite_settings' ) ) {
	$lbite_save_tab = isset( $_POST['lbite_save_tab'] ) ? sanitize_key( wp_unslash( $_POST['lbite_save_tab'] ) ) : '';
	$lbite_did_save = false;

	switch ( $lbite_save_tab ) {
		case 'products_options':
			$lbite_features                            = get_option( 'lbite_features', array() );
			$lbite_features['enable_product_options']  = isset( $_POST['lbite_feature_toggle']['enable_product_options'] );
			update_option( 'lbite_features', $lbite_features );
			$lbite_did_save = true;
			break;

		case 'products_notes':
			$lbite_features                               = get_option( 'lbite_features', array() );
			$lbite_features['enable_item_notes_pos']      = isset( $_POST['lbite_feature_toggle']['enable_item_notes_pos'] );
			$lbite_features['enable_item_notes_checkout'] = isset( $_POST['lbite_feature_toggle']['enable_item_notes_checkout'] );
			update_option( 'lbite_features', $lbite_features );
			$lbite_did_save = true;
			break;

		case 'products_menu':
			$lbite_features = get_option( 'lbite_features', array() );
			if ( $lbite_premium_allowed ) {
				$lbite_features['enable_menu_view']     = isset( $_POST['lbite_feature_toggle']['enable_menu_view'] );
				$lbite_features['enable_menu_schedule'] = isset( $_POST['lbite_feature_toggle']['enable_menu_schedule'] );
			} else {
				$lbite_features['enable_menu_view']     = false;
				$lbite_features['enable_menu_schedule'] = false;
			}
			update_option( 'lbite_features', $lbite_features );

			if ( $lbite_premium_allowed ) {
				LBite_Admin_Settings::save_shortcode_page_picker(
					'lbite_menu_page_id',
					'lbite_menu_page_id',
					__( 'Menu', 'libre-bite' ),
					'[lbite_menu]'
				);
			}
			$lbite_did_save = true;
			break;

		case 'products_nutrition':
			$lbite_features = get_option( 'lbite_features', array() );
			if ( $lbite_premium_allowed ) {
				$lbite_features['enable_nutritional_info'] = isset( $_POST['lbite_feature_toggle']['enable_nutritional_info'] );
				$lbite_features['enable_allergens']        = isset( $_POST['lbite_feature_toggle']['enable_allergens'] );
				$lbite_features['enable_dietary_filter']   = isset( $_POST['lbite_feature_toggle']['enable_dietary_filter'] );
			} else {
				$lbite_features['enable_nutritional_info'] = false;
				$lbite_features['enable_allergens']        = false;
				$lbite_features['enable_dietary_filter']   = false;
			}
			update_option( 'lbite_features', $lbite_features );
			$lbite_did_save = true;
			break;

		case 'products_availability':
			$lbite_features = get_option( 'lbite_features', array() );
			if ( $lbite_premium_allowed ) {
				$lbite_features['enable_availability_hint_category'] = isset( $_POST['lbite_feature_toggle']['enable_availability_hint_category'] );
				$lbite_features['enable_availability_hint_product']  = isset( $_POST['lbite_feature_toggle']['enable_availability_hint_product'] );
				$lbite_features['enable_availability_filter']        = isset( $_POST['lbite_feature_toggle']['enable_availability_filter'] );
			} else {
				$lbite_features['enable_availability_hint_category'] = false;
				$lbite_features['enable_availability_hint_product']  = false;
				$lbite_features['enable_availability_filter']        = false;
			}
			update_option( 'lbite_features', $lbite_features );

			$lbite_hint_style_values = lbite_enforce_pro_options( array(
				'lbite_availability_hint_style' => isset( $_POST['lbite_availability_hint_style'] ) && in_array( sanitize_key( wp_unslash( $_POST['lbite_availability_hint_style'] ) ), array( 'popup', 'list', 'text' ), true )
					? sanitize_key( wp_unslash( $_POST['lbite_availability_hint_style'] ) )
					: 'popup',
			) );
			update_option( 'lbite_availability_hint_style', $lbite_hint_style_values['lbite_availability_hint_style'] );
			$lbite_did_save = true;
			break;

		case 'checkout_mode':
			$lbite_features = get_option( 'lbite_features', array() );
			if ( $lbite_premium_allowed ) {
				$lbite_features['enable_optimized_checkout']   = isset( $_POST['lbite_feature_toggle']['enable_optimized_checkout'] );
				$lbite_features['enable_order_type_selection'] = isset( $_POST['lbite_feature_toggle']['enable_order_type_selection'] );
			} else {
				$lbite_features['enable_optimized_checkout']   = false;
				$lbite_features['enable_order_type_selection'] = false;
			}
			update_option( 'lbite_features', $lbite_features );

			$lbite_co_values = lbite_enforce_pro_options( array(
				'lbite_checkout_mode' => isset( $_POST['lbite_checkout_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_checkout_mode'] ) ) : 'standard',
			) );
			update_option( 'lbite_checkout_mode', $lbite_co_values['lbite_checkout_mode'] );

			$lbite_email_gateways_raw = isset( $_POST['lbite_email_required_gateways'] )
				? array_map( 'sanitize_key', wp_unslash( (array) $_POST['lbite_email_required_gateways'] ) )
				: array();
			update_option( 'lbite_email_required_gateways', $lbite_email_gateways_raw );
			$lbite_did_save = true;
			break;

		case 'checkout_tips':
			$lbite_features = get_option( 'lbite_features', array() );
			$lbite_features['enable_tips'] = $lbite_premium_allowed && isset( $_POST['lbite_feature_toggle']['enable_tips'] );
			update_option( 'lbite_features', $lbite_features );

			$lbite_tip_values = lbite_enforce_pro_options( array(
				'lbite_tip_percentage_1'      => isset( $_POST['lbite_tip_percentage_1'] ) ? floatval( wp_unslash( $_POST['lbite_tip_percentage_1'] ) ) : 5,
				'lbite_tip_percentage_2'      => isset( $_POST['lbite_tip_percentage_2'] ) ? floatval( wp_unslash( $_POST['lbite_tip_percentage_2'] ) ) : 10,
				'lbite_tip_percentage_3'      => isset( $_POST['lbite_tip_percentage_3'] ) ? floatval( wp_unslash( $_POST['lbite_tip_percentage_3'] ) ) : 15,
				'lbite_tip_default_selection' => isset( $_POST['lbite_tip_default_selection'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_tip_default_selection'] ) ) : 'none',
				'lbite_tip_mode'              => ( isset( $_POST['lbite_tip_mode'] ) && 'fixed' === $_POST['lbite_tip_mode'] ) ? 'fixed' : 'percentage',
				'lbite_tip_title'             => isset( $_POST['lbite_tip_title'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_tip_title'] ) ) : '',
			) );
			update_option( 'lbite_tip_percentage_1', $lbite_tip_values['lbite_tip_percentage_1'] );
			update_option( 'lbite_tip_percentage_2', $lbite_tip_values['lbite_tip_percentage_2'] );
			update_option( 'lbite_tip_percentage_3', $lbite_tip_values['lbite_tip_percentage_3'] );
			update_option( 'lbite_tip_default_selection', $lbite_tip_values['lbite_tip_default_selection'] );
			update_option( 'lbite_tip_mode', $lbite_tip_values['lbite_tip_mode'] );
			update_option( 'lbite_tip_title', $lbite_tip_values['lbite_tip_title'] );
			update_option( 'lbite_tip_label_none', isset( $_POST['lbite_tip_label_none'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_tip_label_none'] ) ) : '' );
			update_option( 'lbite_tip_label_1', isset( $_POST['lbite_tip_label_1'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_tip_label_1'] ) ) : '' );
			update_option( 'lbite_tip_label_2', isset( $_POST['lbite_tip_label_2'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_tip_label_2'] ) ) : '' );
			update_option( 'lbite_tip_label_3', isset( $_POST['lbite_tip_label_3'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_tip_label_3'] ) ) : '' );
			$lbite_did_save = true;
			break;

		case 'prices_taxes':
			$lbite_features                    = get_option( 'lbite_features', array() );
			$lbite_features['enable_rounding'] = isset( $_POST['lbite_feature_toggle']['enable_rounding'] );
			update_option( 'lbite_enable_rounding', $lbite_features['enable_rounding'] );
			if ( $lbite_premium_allowed ) {
				$lbite_features['enable_swiss_vat'] = isset( $_POST['lbite_feature_toggle']['enable_swiss_vat'] );
				update_option( 'lbite_tax_class_takeaway', isset( $_POST['lbite_tax_class_takeaway'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_tax_class_takeaway'] ) ) : '' );
				update_option( 'lbite_tax_class_dine_in', isset( $_POST['lbite_tax_class_dine_in'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_tax_class_dine_in'] ) ) : '' );
			}
			update_option( 'lbite_features', $lbite_features );
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
					// «Alle»-Checkbox wird als Wert 'all' im selben Array übertragen; sonst geht der Sentinel bei intval() verloren.
					if ( in_array( 'all', $lbite_locs, true ) ) {
						$lbite_locs = 'all';
					} else {
						$lbite_locs = array_map( 'intval', $lbite_locs );
					}
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

		case 'orders_board':
			$lbite_features                        = get_option( 'lbite_features', array() );
			$lbite_features['enable_kanban_board'] = isset( $_POST['lbite_feature_toggle']['enable_kanban_board'] );
			update_option( 'lbite_features', $lbite_features );

			update_option( 'lbite_dashboard_refresh_interval', isset( $_POST['lbite_dashboard_refresh_interval'] ) ? intval( wp_unslash( $_POST['lbite_dashboard_refresh_interval'] ) ) : 30 );
			update_option( 'lbite_kds_timer_enabled', isset( $_POST['lbite_kds_timer_enabled'] ) ? 1 : 0 );
			update_option( 'lbite_kds_warn_minutes', isset( $_POST['lbite_kds_warn_minutes'] ) ? max( 0, intval( wp_unslash( $_POST['lbite_kds_warn_minutes'] ) ) ) : 0 );
			update_option( 'lbite_kds_late_minutes', isset( $_POST['lbite_kds_late_minutes'] ) ? max( 0, intval( wp_unslash( $_POST['lbite_kds_late_minutes'] ) ) ) : 0 );
			update_option( 'lbite_sound_repeat_interval', isset( $_POST['lbite_sound_repeat_interval'] ) ? max( 0, intval( wp_unslash( $_POST['lbite_sound_repeat_interval'] ) ) ) : 0 );
			$lbite_did_save = true;
			break;

		case 'orders_columns':
			$lbite_features = get_option( 'lbite_features', array() );
			$lbite_features['enable_kanban_customization'] = $lbite_premium_allowed && isset( $_POST['lbite_feature_toggle']['enable_kanban_customization'] );
			update_option( 'lbite_features', $lbite_features );

			$lbite_ord_values = lbite_enforce_pro_options( array(
				'lbite_show_future_orders'       => isset( $_POST['lbite_show_future_orders'] ) ? 1 : 0,
				'lbite_dim_future_orders'        => isset( $_POST['lbite_dim_future_orders'] ) ? 1 : 0,
				'lbite_kanban_drag_drop_enabled' => isset( $_POST['lbite_kanban_drag_drop_enabled'] ) ? 1 : 0,
			) );
			update_option( 'lbite_show_future_orders', $lbite_ord_values['lbite_show_future_orders'] );
			update_option( 'lbite_dim_future_orders', $lbite_ord_values['lbite_dim_future_orders'] );
			update_option( 'lbite_kanban_drag_drop_enabled', $lbite_ord_values['lbite_kanban_drag_drop_enabled'] );

			// Ohne dieses Gate würde ein Speichern bei ausgeschaltetem Feature
			// (kein "columns"-POST-Feld vorhanden) die zuvor konfigurierten
			// Spalten stillschweigend auf die 3 Standard-Spalten zurücksetzen.
			if ( isset( $_POST['columns'] ) && is_array( $_POST['columns'] ) ) {
				$lbite_kanban_full = array();
				if ( lbite_feature_enabled( 'enable_scheduled_orders' ) ) {
					$lbite_kanban_full[] = array(
						'key'                 => LBite_Order_Dashboard::KEY_PREORDER,
						'label'               => isset( $_POST['lbite_kanban_preorder_label'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_kanban_preorder_label'] ) ) : __( 'Pre-orders', 'libre-bite' ),
						'counts_as_completed' => false,
					);
				}
				$lbite_kanban_full[] = array(
					'key'                 => LBite_Order_Dashboard::KEY_ACTIVE,
					'label'               => isset( $_POST['lbite_kanban_active_label'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_kanban_active_label'] ) ) : __( 'Prepare Now', 'libre-bite' ),
					'counts_as_completed' => false,
				);
				foreach ( wp_unslash( $_POST['columns'] ) as $lbite_custom_col ) {
					$lbite_kanban_full[] = $lbite_custom_col;
				}

				$lbite_kanban_columns_val = lbite_enforce_pro_options( array(
					'lbite_kanban_columns' => LBite_Order_Dashboard::sanitize_columns_input( $lbite_kanban_full ),
				) );
				update_option( 'lbite_kanban_columns', $lbite_kanban_columns_val['lbite_kanban_columns'] );
			}
			$lbite_did_save = true;
			break;

		case 'orders_receipts':
			if ( class_exists( 'LBite_Receipts' ) ) {
				$lbite_receipt_raw = isset( $_POST['lbite_receipt_fields'] ) && is_array( $_POST['lbite_receipt_fields'] )
					? wp_unslash( $_POST['lbite_receipt_fields'] )
					: array();
				update_option( LBite_Receipts::OPTION_FIELDS, LBite_Receipts::sanitize_fields( $lbite_receipt_raw ) );
			}
			$lbite_did_save = true;
			break;

		case 'notifications':
			$lbite_features = get_option( 'lbite_features', array() );
			if ( $lbite_premium_allowed ) {
				$lbite_features['enable_pickup_reminders']  = isset( $_POST['lbite_feature_toggle']['enable_pickup_reminders'] );
				$lbite_features['enable_sms_notifications'] = isset( $_POST['lbite_feature_toggle']['enable_sms_notifications'] );
			} else {
				$lbite_features['enable_pickup_reminders']  = false;
				$lbite_features['enable_sms_notifications'] = false;
			}
			// Sound notifications are a free feature.
			$lbite_features['enable_sound_notifications'] = isset( $_POST['lbite_feature_toggle']['enable_sound_notifications'] );
			update_option( 'lbite_features', $lbite_features );

			$lbite_not_values = lbite_enforce_pro_options( array(
				'lbite_pickup_reminder_time' => isset( $_POST['lbite_pickup_reminder_time'] ) ? intval( wp_unslash( $_POST['lbite_pickup_reminder_time'] ) ) : 15,
			) );
			update_option( 'lbite_notification_sound', isset( $_POST['lbite_notification_sound'] ) ? esc_url_raw( wp_unslash( $_POST['lbite_notification_sound'] ) ) : '' );
			update_option( 'lbite_pickup_reminder_time', $lbite_not_values['lbite_pickup_reminder_time'] );

			// SMS-Zugangsdaten. Das Auth-Token wird verschlüsselt abgelegt und
			// nur überschrieben, wenn tatsächlich ein neuer Wert eingegeben
			// wurde — sonst würde das Absenden des Formulars mit leerem Feld
			// das gespeicherte Token löschen.
			if ( ! class_exists( 'LBite_SMS' ) ) {
				require_once LBITE_PLUGIN_DIR . 'includes/modules/sms/class-sms.php';
			}

			$lbite_sms_values = lbite_enforce_pro_options(
				array(
					'lbite_sms_account_sid'    => isset( $_POST['lbite_sms_account_sid'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_sms_account_sid'] ) ) : '',
					'lbite_sms_from'           => isset( $_POST['lbite_sms_from'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_sms_from'] ) ) : '',
					'lbite_sms_template'       => isset( $_POST['lbite_sms_template'] ) ? sanitize_textarea_field( wp_unslash( $_POST['lbite_sms_template'] ) ) : '',
					'lbite_sms_trigger_status' => isset( $_POST['lbite_sms_trigger_status'] ) ? sanitize_key( wp_unslash( $_POST['lbite_sms_trigger_status'] ) ) : '',
				)
			);
			update_option( 'lbite_sms_account_sid', $lbite_sms_values['lbite_sms_account_sid'] );
			update_option( 'lbite_sms_from', $lbite_sms_values['lbite_sms_from'] );
			update_option( 'lbite_sms_template', $lbite_sms_values['lbite_sms_template'] );
			update_option( 'lbite_sms_trigger_status', $lbite_sms_values['lbite_sms_trigger_status'] );

			$lbite_sms_token = isset( $_POST['lbite_sms_auth_token'] ) ? trim( (string) wp_unslash( $_POST['lbite_sms_auth_token'] ) ) : '';
			if ( '' !== $lbite_sms_token && $lbite_premium_allowed ) {
				update_option( 'lbite_sms_auth_token', LBite_SMS::encrypt( $lbite_sms_token ) );
			}

			update_option( 'lbite_sms_country_code', isset( $_POST['lbite_sms_country_code'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_sms_country_code'] ) ) : '+41' );

			$lbite_did_save = true;
			break;

		case 'marketing_bumps':
			$lbite_features = get_option( 'lbite_features', array() );
			$lbite_features['enable_order_bumps'] = $lbite_premium_allowed && isset( $_POST['lbite_feature_toggle']['enable_order_bumps'] );
			update_option( 'lbite_features', $lbite_features );

			if ( ! class_exists( 'LBite_Order_Bumps' ) ) {
				require_once LBITE_PLUGIN_DIR . 'includes/modules/order-bumps/class-order-bumps.php';
			}
			$lbite_bump_raw = isset( $_POST['lbite_order_bumps'] ) && is_array( $_POST['lbite_order_bumps'] )
				? wp_unslash( $_POST['lbite_order_bumps'] )
				: array();
			$lbite_bump_val = lbite_enforce_pro_options(
				array( 'lbite_order_bumps' => LBite_Order_Bumps::sanitize_bumps( $lbite_bump_raw ) )
			);
			update_option( 'lbite_order_bumps', $lbite_bump_val['lbite_order_bumps'] );
			$lbite_did_save = true;
			break;

		case 'marketing_promotions':
			$lbite_features = get_option( 'lbite_features', array() );
			$lbite_features['enable_promotions'] = $lbite_premium_allowed && isset( $_POST['lbite_feature_toggle']['enable_promotions'] );
			update_option( 'lbite_features', $lbite_features );

			if ( ! class_exists( 'LBite_Promotions' ) ) {
				require_once LBITE_PLUGIN_DIR . 'includes/modules/promotions/class-promotions.php';
			}
			$lbite_promo_val = lbite_enforce_pro_options(
				array( 'lbite_promotions' => LBite_Promotions::sanitize_rules( isset( $_POST['lbite_promotions'] ) && is_array( $_POST['lbite_promotions'] ) ? wp_unslash( $_POST['lbite_promotions'] ) : array() ) )
			);
			update_option( 'lbite_promotions', $lbite_promo_val['lbite_promotions'] );
			$lbite_did_save = true;
			break;

		case 'marketing_banner':
			if ( ! class_exists( 'LBite_Promotions' ) ) {
				require_once LBITE_PLUGIN_DIR . 'includes/modules/promotions/class-promotions.php';
			}
			$lbite_banner_val = lbite_enforce_pro_options(
				array( 'lbite_promo_banner' => LBite_Promotions::sanitize_banner( isset( $_POST['lbite_promo_banner'] ) && is_array( $_POST['lbite_promo_banner'] ) ? wp_unslash( $_POST['lbite_promo_banner'] ) : array() ) )
			);
			update_option( 'lbite_promo_banner', $lbite_banner_val['lbite_promo_banner'] );
			$lbite_did_save = true;
			break;

		case 'marketing_stampcard':
			$lbite_features = get_option( 'lbite_features', array() );
			$lbite_features['enable_stampcard'] = $lbite_premium_allowed && isset( $_POST['lbite_feature_toggle']['enable_stampcard'] );
			update_option( 'lbite_features', $lbite_features );

			$lbite_stamp_val = lbite_enforce_pro_options(
				array(
					'lbite_stampcard_target'        => isset( $_POST['lbite_stampcard_target'] ) ? max( 2, intval( wp_unslash( $_POST['lbite_stampcard_target'] ) ) ) : 10,
					'lbite_stampcard_discount'      => isset( $_POST['lbite_stampcard_discount'] ) ? min( 100, max( 1, intval( wp_unslash( $_POST['lbite_stampcard_discount'] ) ) ) ) : 50,
					'lbite_stampcard_min_total'     => isset( $_POST['lbite_stampcard_min_total'] ) ? max( 0, (float) wp_unslash( $_POST['lbite_stampcard_min_total'] ) ) : 0,
					'lbite_stampcard_validity_days' => isset( $_POST['lbite_stampcard_validity_days'] ) ? max( 1, intval( wp_unslash( $_POST['lbite_stampcard_validity_days'] ) ) ) : 90,
				)
			);
			foreach ( $lbite_stamp_val as $lbite_sk => $lbite_sv ) {
				update_option( $lbite_sk, $lbite_sv );
			}
			$lbite_did_save = true;
			break;

		case 'pos':
			$lbite_features = get_option( 'lbite_features', array() );
			$lbite_features['enable_pos'] = isset( $_POST['lbite_feature_toggle']['enable_pos'] );
			$lbite_features['enable_split_payment'] = $lbite_premium_allowed && isset( $_POST['lbite_feature_toggle']['enable_split_payment'] );
			$lbite_features['enable_open_tabs'] = $lbite_premium_allowed && isset( $_POST['lbite_feature_toggle']['enable_open_tabs'] );
			update_option( 'lbite_features', $lbite_features );

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
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$lbite_pm_icon = isset( $_POST['lbite_pm_icon'][ $lbite_pm_key ] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_pm_icon'][ $lbite_pm_key ] ) ) : $lbite_pos_default['icon'];
				if ( empty( $lbite_pm_icon ) ) {
					$lbite_pm_icon = $lbite_pos_default['icon'];
				}
				$lbite_payment_methods[] = array(
					'key'     => $lbite_pm_key,
					'label'   => $lbite_pm_label,
					'icon'    => $lbite_pm_icon,
					'enabled' => $lbite_pm_enabled,
				);
			}
			update_option( 'lbite_pos_payment_methods', $lbite_payment_methods );

			$lbite_pos_default_vat_type = isset( $_POST['lbite_pos_default_vat_type'] ) && 'dine_in' === sanitize_key( wp_unslash( $_POST['lbite_pos_default_vat_type'] ) ) ? 'dine_in' : 'takeaway';
			update_option( 'lbite_pos_default_vat_type', $lbite_pos_default_vat_type );

			$lbite_did_save = true;
			break;

		case 'locations':
			$lbite_features = get_option( 'lbite_features', array() );
			$lbite_features['enable_location_selector'] = isset( $_POST['lbite_feature_toggle']['enable_location_selector'] );
			$lbite_features['enable_scheduled_orders']  = isset( $_POST['lbite_feature_toggle']['enable_scheduled_orders'] );
			update_option( 'lbite_features', $lbite_features );

			// Location page (with create_new option)
			LBite_Admin_Settings::save_shortcode_page_picker(
				'lbite_location_page_id',
				'lbite_location_page_id',
				__( 'Locations', 'libre-bite' ),
				'[lbite_location_selector]'
			);

			// Kapazitätsmodul (Pro)
			$lbite_features = get_option( 'lbite_features', array() );
			$lbite_features['enable_slot_capacity'] = $lbite_premium_allowed && isset( $_POST['lbite_feature_toggle']['enable_slot_capacity'] );
			update_option( 'lbite_features', $lbite_features );

			// Time settings
			$lbite_loc_time = lbite_enforce_pro_options( array(
				'lbite_slot_buffer_start' => isset( $_POST['lbite_slot_buffer_start'] ) ? intval( wp_unslash( $_POST['lbite_slot_buffer_start'] ) ) : 0,
				'lbite_slot_buffer_end'   => isset( $_POST['lbite_slot_buffer_end'] ) ? intval( wp_unslash( $_POST['lbite_slot_buffer_end'] ) ) : 0,
				'lbite_max_orders_per_slot' => isset( $_POST['lbite_max_orders_per_slot'] ) ? max( 0, intval( wp_unslash( $_POST['lbite_max_orders_per_slot'] ) ) ) : 0,
			) );
			update_option( 'lbite_preparation_time', isset( $_POST['lbite_preparation_time'] ) ? intval( wp_unslash( $_POST['lbite_preparation_time'] ) ) : 30 );
			update_option( 'lbite_timeslot_interval', isset( $_POST['lbite_timeslot_interval'] ) ? intval( wp_unslash( $_POST['lbite_timeslot_interval'] ) ) : 15 );
			update_option( 'lbite_slot_buffer_start', $lbite_loc_time['lbite_slot_buffer_start'] );
			update_option( 'lbite_slot_buffer_end', $lbite_loc_time['lbite_slot_buffer_end'] );
			update_option( 'lbite_max_orders_per_slot', $lbite_loc_time['lbite_max_orders_per_slot'] );
			$lbite_did_save = true;
			break;

		case 'tables':
			if ( $lbite_premium_allowed ) {
				$lbite_features = get_option( 'lbite_features', array() );
				$lbite_features['enable_table_ordering'] = isset( $_POST['lbite_feature_toggle']['enable_table_ordering'] );
				update_option( 'lbite_features', $lbite_features );
				$lbite_tbl_values = lbite_enforce_pro_options( array(
					'lbite_table_order_page_id' => isset( $_POST['lbite_table_order_page_id'] ) ? intval( wp_unslash( $_POST['lbite_table_order_page_id'] ) ) : 0,
					'lbite_table_dropdown_sort' => isset( $_POST['lbite_table_dropdown_sort'] ) && 'menu_order' === $_POST['lbite_table_dropdown_sort'] ? 'menu_order' : 'natural',
				) );
				update_option( 'lbite_table_order_page_id', $lbite_tbl_values['lbite_table_order_page_id'] );
				update_option( 'lbite_table_dropdown_sort', $lbite_tbl_values['lbite_table_dropdown_sort'] );
			}
			$lbite_did_save = true;
			break;

		case 'reservations':
			if ( $lbite_premium_allowed ) {
				$lbite_features = get_option( 'lbite_features', array() );
				$lbite_features['enable_reservations'] = isset( $_POST['lbite_feature_toggle']['enable_reservations'] );
				$lbite_features['enable_guest_notes']  = isset( $_POST['lbite_feature_toggle']['enable_guest_notes'] );
				update_option( 'lbite_features', $lbite_features );
				update_option( 'lbite_reservation_refresh_interval', isset( $_POST['lbite_reservation_refresh_interval'] ) ? intval( wp_unslash( $_POST['lbite_reservation_refresh_interval'] ) ) : 60 );
				update_option(
					'lbite_reservation_fields',
					array(
						'phone' => array( 'enabled' => isset( $_POST['lbite_reservation_fields']['phone'] ) ),
						'notes' => array( 'enabled' => isset( $_POST['lbite_reservation_fields']['notes'] ) ),
					)
				);
			}
			$lbite_did_save = true;
			break;

		case 'branding':
			update_option( 'lbite_custom_plugin_name', isset( $_POST['lbite_custom_plugin_name'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_custom_plugin_name'] ) ) : '' );
			update_option( 'lbite_brand_name', isset( $_POST['lbite_brand_name'] ) ? sanitize_text_field( wp_unslash( $_POST['lbite_brand_name'] ) ) : '' );
			update_option( 'lbite_brand_logo', isset( $_POST['lbite_brand_logo'] ) ? intval( wp_unslash( $_POST['lbite_brand_logo'] ) ) : 0 );
			update_option( 'lbite_color_primary', isset( $_POST['lbite_color_primary'] ) ? sanitize_hex_color( wp_unslash( $_POST['lbite_color_primary'] ) ) : '#0073aa' );
			update_option( 'lbite_color_secondary', isset( $_POST['lbite_color_secondary'] ) ? sanitize_hex_color( wp_unslash( $_POST['lbite_color_secondary'] ) ) : '#23282d' );
			update_option( 'lbite_color_accent', isset( $_POST['lbite_color_accent'] ) ? sanitize_hex_color( wp_unslash( $_POST['lbite_color_accent'] ) ) : '#00a32a' );

			// Das Farbschema ist bewusst persönlich und landet deshalb in der
			// Benutzer-Meta, nicht in einer globalen Option – Küchen-Tablet und
			// Bürorechner sollen sich unterscheiden dürfen.
			if ( isset( $_POST['lbite_admin_theme'] ) ) {
				$lbite_theme_choice = sanitize_key( wp_unslash( $_POST['lbite_admin_theme'] ) );
				if ( in_array( $lbite_theme_choice, array( 'auto', 'light', 'dark' ), true ) ) {
					update_user_meta( get_current_user_id(), 'lbite_admin_theme', $lbite_theme_choice );
				}
			}

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
