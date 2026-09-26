<?php
/**
 * Plugin Installation & Deinstallation
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installer-Klasse
 */
class LBite_Installer {

	/**
	 * Plugin-Aktivierung
	 */
	public static function activate() {
		// Rollen und Capabilities erstellen
		require_once LBITE_PLUGIN_DIR . 'includes/admin/class-roles.php';
		LBite_Roles::create_roles();

		// Standard-Optionen setzen
		self::set_default_options();

		// Feature-Toggles initialisieren
		self::set_default_features();

		// Support-Einstellungen initialisieren
		self::set_default_support_settings();

		// Cron-Jobs nur einplanen, wenn das zugehörige Feature aktiv ist -
		// sonst liefe die Minutenschleife auf jeder Installation permanent,
		// auch wenn Vorbestellungen/Erinnerungen nie genutzt werden
		// (Audit 26.09.2026, AP-21). self::set_default_features() oben hat
		// die Optionen bereits geschrieben, lbite_feature_enabled() ist ab
		// hier sicher nutzbar.
		self::sync_cron_jobs();

		// Flush rewrite rules
		flush_rewrite_rules();

		// Erstinstallation erkennen, bevor die Version geschrieben wird – danach
		// wäre die Abfrage immer falsch und der Willkommens-Hinweis erschiene nie.
		$is_fresh_install = ! get_option( 'lbite_version' );

		// Version speichern
		update_option( 'lbite_version', LBITE_VERSION );

		// Das Installationsdatum bleibt das erste; eine Reaktivierung setzt es nicht zurück.
		add_option( 'lbite_installed_date', current_time( 'mysql' ) );

		// Welcome-Notice bei Erstinstallation anzeigen
		if ( $is_fresh_install ) {
			add_option( 'lbite_show_welcome_notice', true );

			// Einmalige Weiterleitung zum Einrichtungsassistenten nach der ersten
			// Aktivierung – nicht bei Massen- oder Netzwerk-Aktivierung und nicht
			// per WP-CLI, da es dort keinen sinnvollen Redirect-Ziel-Request gibt
			// (Audit 26.09.2026, AP-20). Kurze Lebensdauer, damit ein sehr später
			// nächster Admin-Request (z. B. Cron-getrieben) nicht plötzlich
			// jemanden umleitet, der die Aktivierung längst nicht mehr vor Augen hat.
			if ( ! ( defined( 'WP_CLI' ) && WP_CLI )
				&& ! wp_doing_ajax()
				&& ! ( function_exists( 'is_network_admin' ) && is_network_admin() )
				&& ! isset( $_GET['activate-multi'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lesezugriff zur Erkennung einer Massen-Aktivierung, kein DB-Write.
			) {
				set_transient( 'lbite_activation_redirect', true, 30 );
			}
		}
	}

	/**
	 * Plugin-Deaktivierung
	 */
	public static function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();

		// Geplante Cron-Jobs entfernen
		wp_clear_scheduled_hook( 'lbite_check_scheduled_orders' );
		wp_clear_scheduled_hook( 'lbite_send_pickup_reminders' );
	}

	/**
	 * Cron-Jobs mit dem aktuellen Feature-Zustand abgleichen.
	 *
	 * Plant den jeweiligen Job ein, wenn das zugehörige Feature aktiv ist und
	 * der Job noch fehlt, und entfernt ihn wieder, wenn das Feature inzwischen
	 * deaktiviert wurde - sonst liefe die Minutenschleife auf jeder
	 * Installation permanent, unabhängig davon, ob Vorbestellungen oder
	 * Erinnerungen je genutzt werden (Audit 26.09.2026, AP-21). Wird sowohl
	 * bei der Aktivierung als auch bei jedem admin_init (via maybe_upgrade())
	 * aufgerufen, damit ein nachträglich umgeschaltetes Feature den Job ohne
	 * Reaktivierung des Plugins ein- bzw. ausplant.
	 */
	public static function sync_cron_jobs() {
		$jobs = array(
			'lbite_check_scheduled_orders' => 'enable_scheduled_orders',
			'lbite_send_pickup_reminders'  => 'enable_pickup_reminders',
		);

		foreach ( $jobs as $hook => $feature ) {
			$should_run = lbite_feature_enabled( $feature );
			$event      = wp_get_scheduled_event( $hook );

			if ( ! $should_run ) {
				if ( $event ) {
					wp_clear_scheduled_hook( $hook );
				}
				continue;
			}

			// Auf Installationen, die den Job noch unter dem alten,
			// generischen Intervallnamen "every_minute" laufen haben (vor
			// AP-21), einmalig neu einplanen – WP-Cron plant ein
			// wiederkehrendes Event sonst für immer unter demselben Namen
			// neu, den es beim ersten Mal erhalten hat.
			if ( $event && 'lbite_every_minute' !== $event->schedule ) {
				wp_clear_scheduled_hook( $hook );
				$event = null;
			}

			if ( ! $event ) {
				wp_schedule_event( time(), 'lbite_every_minute', $hook );
			}
		}
	}

	/**
	 * Vollständige Daten-Bereinigung bei Deinstallation
	 */
	public static function uninstall() {
		// Prüfen ob Datenlöschung aktiviert ist.
		$delete_data = get_option( 'lbite_delete_data_on_uninstall', false ) || get_option( 'oos_delete_data_on_uninstall', false );

		if ( ! $delete_data ) {
			return;
		}

		global $wpdb;

		// 1. Custom Post Types löschen (Standorte, Produkt-Optionen).
		$post_types = array( 'lbite_location', 'oos_location', 'lbite_product_option', 'oos_product_option', 'lbite_table', 'lbite_reservation' );
		foreach ( $post_types as $post_type ) {
			// Begrenzt auf 500 zur Sicherheit, in der Regel gibt es nicht so viele Standorte/Optionen.
			$posts = get_posts(
				array(
					'post_type'   => $post_type,
					'numberposts' => 500,
					'fields'      => 'ids',
					'post_status' => 'any',
				)
			);
			foreach ( $posts as $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}

		// 2. Optionen löschen. `lbite_` per Wildcard, `oos_` bewusst NICHT als
		// Wildcard – das Präfix ist zu generisch und hätte fremde
		// Plugin-Optionen mit demselben Präfix mitgelöscht. Stattdessen nur
		// der eine tatsächlich bekannte alte Optionsname (Audit 26.09.2026,
		// AP-16).
		// Direkte SQL-Abfrage notwendig: DELETE mit LIKE-Wildcard ist in WP-API nicht möglich (delete_option() nur für exakte Keys).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'lbite_' ) . '%' ) );
		delete_option( 'oos_delete_data_on_uninstall' );

		// 3. Metadaten löschen.
		// Direkte SQL-Abfragen notwendig: Bulk-Delete mit LIKE-Pattern, kein WP-API-Äquivalent.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s", $wpdb->esc_like( '_lbite_' ) . '%' ) );

		// Unter HPOS liegen Bestell-Metadaten NICHT in wp_postmeta, sondern
		// in einer eigenen Tabelle – ohne diesen Schritt blieben sie bei
		// aktivem HPOS vollständig zurück (Audit 26.09.2026, AP-16). Tabelle
		// existiert nur, wenn HPOS je aktiv war/ist.
		$lbite_orders_meta_table = $wpdb->prefix . 'wc_orders_meta';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $lbite_orders_meta_table ) ) === $lbite_orders_meta_table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$lbite_orders_meta_table} WHERE meta_key LIKE %s", $wpdb->esc_like( '_lbite_' ) . '%' ) );
		}

		// Bestellpositions-Metadaten (Tab-Runden, Aktionsrabatte) – eigene,
		// von HPOS unabhängige Tabelle, in der bisherigen Abfrage komplett
		// vergessen.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key LIKE %s", $wpdb->esc_like( '_lbite_' ) . '%' ) );

		// Kategorie-Zeitplan (`_lbite_menu_schedule`) liegt als Term-Meta vor.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->termmeta} WHERE meta_key LIKE %s", $wpdb->esc_like( '_lbite_' ) . '%' ) );

		// Benutzer-Meta: zwei Präfix-Familien mit UND ohne führenden
		// Unterstrich (z. B. `lbite_admin_theme` vs. `_lbite_guest_notes`/
		// `_lbite_stamps`) – die bisherige Abfrage erfasste nur die Variante
		// ohne Unterstrich und liess Allergiehinweise (Gesundheitsdaten!) und
		// Stempelkarten-Stände zurück (Audit 26.09.2026, AP-16).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s OR meta_key LIKE %s", $wpdb->esc_like( 'lbite_' ) . '%', $wpdb->esc_like( '_lbite_' ) . '%' ) );

		// 4. Rollen & Capabilities entfernen – dieselbe Methode wie bei der
		// Deaktivierung, damit beide Stellen nie auseinanderlaufen. Die
		// bisherige, hier fest kopierte Rollen-/Cap-Liste war veraltet: sie
		// kannte weder `lbite_manager`/`lbite_staff` noch die seither
		// ergänzten Capabilities (`lbite_manage_reservations`,
		// `lbite_view_statistics`, `lbite_run_setup`,
		// `lbite_manage_location_settings`) (Audit 26.09.2026, AP-16).
		LBite_Roles::remove_roles();

		// 5. Cron Jobs entfernen.
		$cron_hooks = array( 'lbite_check_scheduled_orders', 'lbite_send_pickup_reminders' );
		foreach ( $cron_hooks as $hook ) {
			wp_clear_scheduled_hook( $hook );
		}

		// 6. Transients löschen.
		// Direkte SQL-Abfrage notwendig: delete_transient() nur für exakte Keys; Bulk-Delete mit Prefix nicht über WP-API möglich.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_lbite_' ) . '%', $wpdb->esc_like( '_transient_timeout_lbite_' ) . '%' ) );
	}

	/**
	 * Standard-Optionen setzen
	 */
	private static function set_default_options() {
		$defaults = array(
			// POS-Zahlungsarten. Label bewusst leer statt fest codiertem Deutsch
			// gespeichert – jede Ausgabestelle (class-admin.php, statistics.php,
			// templates/admin/pos.php, .../settings/pos.php) fällt bei leerem
			// Label bereits auf einen zur Laufzeit übersetzten Standardnamen
			// zurück. Ein gespeicherter deutscher Text hätte diese Übersetzung
			// auf jeder Website dauerhaft überschrieben, unabhängig von deren
			// Sprache (Audit 26.09.2026, AP-19).
			'lbite_pos_payment_methods'       => array(
				array( 'key' => 'cash',  'label' => '', 'enabled' => true ),
				array( 'key' => 'card',  'label' => '', 'enabled' => true ),
				array( 'key' => 'twint', 'label' => '', 'enabled' => true ),
				array( 'key' => 'other', 'label' => '', 'enabled' => true ),
			),

			// Checkout-Einstellungen
			'lbite_checkout_fields'           => array(),

			// Trinkgeld-Einstellungen
			'lbite_tip_percentage_1'          => 5,
			'lbite_tip_percentage_2'          => 10,
			'lbite_tip_percentage_3'          => 15,

			// Vorbestellungs-Einstellungen
			'lbite_preparation_time'          => 30, // Minuten
			'lbite_pickup_reminder_time'      => 15, // Minuten vor Abholung

			// Zeitslot-Einstellungen
			'lbite_timeslot_interval'         => 15, // Minuten

			// Dashboard-Einstellungen
			'lbite_dashboard_refresh_interval' => 45, // Sekunden
			'lbite_sound_enabled'             => true,

			// E-Mail-Einstellungen
			'lbite_email_pickup_reminder'     => true,
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}

	/**
	 * Standard Feature-Toggles setzen
	 */
	private static function set_default_features() {
		require_once LBITE_PLUGIN_DIR . 'includes/core/class-features.php';

		if ( false === get_option( 'lbite_features' ) ) {
			add_option( 'lbite_features', LBite_Features::get_default_values() );
		}
	}

	/**
	 * Standard Support-Einstellungen setzen
	 */
	private static function set_default_support_settings() {
		$default_support = array(
			'support_email'        => get_option( 'admin_email' ),
			'support_phone'        => '',
			'support_hours'        => '',
			'support_billing_note' => __( 'Support is free of charge.', 'libre-bite' ),
			'support_custom_text'  => '',
		);

		if ( false === get_option( 'lbite_support_settings' ) ) {
			add_option( 'lbite_support_settings', $default_support );
		}
	}

	/**
	 * Migration bei Plugin-Update durchführen
	 */
	public static function maybe_upgrade() {
		$current_version = get_option( 'lbite_version', '0' );

		// Migration für Rollen-System
		require_once LBITE_PLUGIN_DIR . 'includes/admin/class-roles.php';
		if ( LBite_Roles::needs_migration() ) {
			LBite_Roles::migrate_existing_users();
		}

		// Feature-Toggles initialisieren falls nicht vorhanden
		if ( false === get_option( 'lbite_features' ) ) {
			self::set_default_features();
		}

		// Läuft bei jedem admin_init, nicht nur einmal pro Version, damit ein
		// nachträglich umgeschaltetes Feature den zugehörigen Cron sofort
		// ein-/ausplant (Audit 26.09.2026, AP-21).
		self::sync_cron_jobs();

		// Migration auf 1.5.0: veraltete Toggle-Keys entfernen
		if ( version_compare( $current_version, '1.5.0', '<' ) ) {
			self::migrate_features_to_1_5();
		}

		// Support-Einstellungen hinzufügen falls nicht vorhanden
		if ( false === get_option( 'lbite_support_settings' ) ) {
			self::set_default_support_settings();
		}

		// Migration auf 2.2.0: Produkt-Standort-Zuordnung von Opt-In auf Opt-Out umstellen
		if ( version_compare( $current_version, '2.2.0', '<' ) ) {
			self::migrate_product_locations_to_opt_out();
		}

		// Migration auf 3.2.9: lbite_enable_rounding in lbite_features übernehmen
		if ( version_compare( $current_version, '3.2.9', '<' ) ) {
			self::migrate_rounding_option();
		}

		// Migration auf 3.4.4: _lbite_pickup_date für bestehende Vorbestellungen nachtragen
		if ( version_compare( $current_version, '3.4.4', '<' ) ) {
			self::migrate_pickup_dates();
		}

		// Migration auf 3.4.10: unveränderte deutsche POS-Zahlungsart-Labels
		// auf leer zurücksetzen, damit sie zur Laufzeit übersetzt werden.
		if ( version_compare( $current_version, '3.4.10', '<' ) ) {
			self::migrate_pos_payment_labels();
		}

		// Migration auf 3.5.1: normalisierte Telefonnummer für bestehende
		// Benutzer nachtragen (siehe LBite_Guest_Notes::META_PHONE_NORMALIZED).
		if ( version_compare( $current_version, '3.5.1', '<' ) ) {
			self::migrate_normalized_phone_numbers();
		}

		// Version aktualisieren
		if ( version_compare( $current_version, LBITE_VERSION, '<' ) ) {
			update_option( 'lbite_version', LBITE_VERSION );
		}
	}

	/**
	 * Produkt-Standort-Zuordnung von Whitelist (_lbite_locations) auf
	 * Blacklist (_lbite_locations_excluded) umstellen, ohne das bestehende
	 * Verhalten für bereits eingeschränkte Produkte zu verändern.
	 */
	private static function migrate_product_locations_to_opt_out() {
		if ( ! class_exists( 'LBite_Locations' ) ) {
			require_once LBITE_PLUGIN_DIR . 'includes/modules/locations/class-locations.php';
		}

		$all_location_ids = wp_list_pluck( LBite_Locations::get_all_locations(), 'ID' );

		$products = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_lbite_locations',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		foreach ( $products as $product_id ) {
			$included = get_post_meta( $product_id, '_lbite_locations', true );
			$included = is_array( $included ) ? array_map( 'intval', $included ) : array();

			if ( ! empty( $included ) ) {
				$excluded = array_values( array_diff( $all_location_ids, $included ) );
				update_post_meta( $product_id, '_lbite_locations_excluded', $excluded );
			}

			delete_post_meta( $product_id, '_lbite_locations' );
		}
	}

	/**
	 * Migration auf 3.2.9: separate Rundungs-Option in lbite_features übernehmen.
	 *
	 * Bisher zwei getrennte Wahrheiten für dieselbe Einstellung (Audit
	 * 26.09.2026, AP-04): der Feature-Schalter `enable_rounding` (Default an)
	 * und diese separate Option (Default aus, erst beim ersten Speichern der
	 * Einstellungen gesetzt) - dadurch rundete eine frische CHF-Installation
	 * trotz aktivem Feature nicht, bis einmal gespeichert wurde. Übernimmt
	 * den bisherigen Options-Wert nur, wenn er je gespeichert wurde; sonst
	 * entscheidet ab jetzt der währungsabhängige Feature-Default
	 * (`LBite_Features::get_default_values()`).
	 */
	private static function migrate_rounding_option() {
		$sentinel = '__lbite_unset__';
		$legacy   = get_option( 'lbite_enable_rounding', $sentinel );

		if ( $sentinel !== $legacy ) {
			$features                    = get_option( 'lbite_features', array() );
			$features['enable_rounding'] = (bool) $legacy;
			update_option( 'lbite_features', $features );
		}

		delete_option( 'lbite_enable_rounding' );
	}

	/**
	 * _lbite_pickup_date für bestehende Vorbestellungen nachtragen.
	 *
	 * Neu ab AP-13 (Audit 26.09.2026): die Kapazitätszählung filtert jetzt
	 * nach diesem Feld statt nach dem Erstellungsdatum. Ohne die Migration
	 * würden bereits aufgegebene, noch nicht abgeholte Vorbestellungen aus
	 * der Zählung fallen und ihr Zeitfenster liesse sich überbuchen.
	 */
	private static function migrate_pickup_dates() {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return;
		}

		// In Batches statt in einer Abfrage, damit ein grosser Bestellbestand
		// nicht auf einmal geladen werden muss.
		for ( $i = 0; $i < 50; $i++ ) {
			$orders = wc_get_orders(
				array(
					'limit'      => 200,
					'status'     => 'any',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Einmalige Migration, in Batches von 200.
					'meta_query' => array(
						array(
							'key'     => '_lbite_pickup_time',
							'compare' => 'EXISTS',
						),
						array(
							'key'     => '_lbite_pickup_date',
							'compare' => 'NOT EXISTS',
						),
					),
				)
			);

			if ( empty( $orders ) ) {
				break;
			}

			foreach ( $orders as $order ) {
				$pickup_time = (string) $order->get_meta( '_lbite_pickup_time', true );
				if ( '' === $pickup_time ) {
					continue;
				}
				$order->update_meta_data( '_lbite_pickup_date', substr( str_replace( 'T', ' ', $pickup_time ), 0, 10 ) );
				$order->save();
			}
		}
	}

	/**
	 * Unveränderte deutsche POS-Zahlungsart-Labels auf leer zurücksetzen.
	 *
	 * Nur Labels, die noch exakt dem alten hartcodierten deutschen Default
	 * entsprechen, werden geleert – ein von Hand angepasstes Label (auch
	 * wenn es zufällig "Bar" heisst) bleibt unangetastet, da es sich dann
	 * nicht mehr unterscheiden lässt von einer bewussten Änderung. Ein
	 * geleertes Label fällt an jeder Ausgabestelle automatisch auf einen
	 * zur Laufzeit übersetzten Namen zurück (Audit 26.09.2026, AP-19).
	 */
	private static function migrate_pos_payment_labels() {
		$methods = get_option( 'lbite_pos_payment_methods', array() );

		if ( ! is_array( $methods ) || empty( $methods ) ) {
			return;
		}

		$old_german_defaults = array(
			'cash'  => 'Bar',
			'card'  => 'Karte',
			'other' => 'Andere',
		);

		$changed = false;

		foreach ( $methods as $index => $method ) {
			$key = isset( $method['key'] ) ? $method['key'] : '';
			if ( isset( $old_german_defaults[ $key ] ) && isset( $method['label'] ) && $old_german_defaults[ $key ] === $method['label'] ) {
				$methods[ $index ]['label'] = '';
				$changed                    = true;
			}
		}

		if ( $changed ) {
			update_option( 'lbite_pos_payment_methods', $methods );
		}
	}

	/**
	 * Normalisierte Telefonnummer für bestehende Benutzer nachtragen.
	 *
	 * Ohne diesen einmaligen Nachtrag würde find_customer_by_phone() für
	 * jeden vor dieser Version angelegten Benutzer ins Leere laufen, da die
	 * exakte Suche auf `_lbite_phone_normalized` sonst erst nach der
	 * nächsten Änderung von `billing_phone` gefüllt wird (Audit 26.09.2026,
	 * AP-21). In Batches, wie migrate_pickup_dates().
	 */
	private static function migrate_normalized_phone_numbers() {
		if ( ! class_exists( 'LBite_Guest_Notes' ) ) {
			require_once LBITE_PLUGIN_DIR . 'includes/modules/guest-notes/class-guest-notes.php';
		}

		$paged = 1;

		while ( true ) {
			$users = get_users(
				array(
					'number'     => 200,
					'paged'      => $paged,
					'fields'     => array( 'ID' ),
					'meta_key'   => 'billing_phone', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Einmalige Migration, in Batches von 200.
					'meta_value' => '', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- s.o.
					'meta_compare' => '!=',
				)
			);
			++$paged;

			if ( empty( $users ) ) {
				break;
			}

			foreach ( $users as $user ) {
				$phone = get_user_meta( $user->ID, 'billing_phone', true );
				if ( '' === $phone ) {
					continue;
				}
				update_user_meta( $user->ID, LBite_Guest_Notes::META_PHONE_NORMALIZED, LBite_Guest_Notes::normalize_phone( $phone ) );
			}
		}
	}

	/**
	 * Feature-Toggle-Keys entfernen, die in v1.5.0 aufgegeben wurden
	 */
	private static function migrate_features_to_1_5() {
		$removed_keys = array(
			'enable_guest_checkout',
			'enable_email_field',
			'enable_phone_field',
			'enable_multi_location',
			'enable_order_notes',
			'enable_order_cancellation',
			'enable_fullscreen_mode',
			'enable_auto_status_change',
			'enable_opening_hours',
		);

		$features = get_option( 'lbite_features', array() );
		$changed  = false;

		foreach ( $removed_keys as $key ) {
			if ( array_key_exists( $key, $features ) ) {
				unset( $features[ $key ] );
				$changed = true;
			}
		}

		if ( $changed ) {
			update_option( 'lbite_features', $features );
		}
	}
}
