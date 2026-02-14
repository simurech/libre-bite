<?php
/**
 * Admin-Einstellungen
 *
 * Erweiterte Einstellungen nur für Administratoren
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin-Einstellungen-Klasse
 */
class LBite_Admin_Settings {

	/**
	 * Loader-Instanz
	 *
	 * @var LBite_Loader
	 */
	private $loader;

	/**
	 * Konstruktor
	 *
	 * @param LBite_Loader $loader Loader-Instanz
	 */
	public function __construct( $loader ) {
		$this->loader = $loader;
		$this->init_hooks();
	}

	/**
	 * Hooks initialisieren
	 */
	private function init_hooks() {
		// Einstellungen speichern
		$this->loader->add_action( 'admin_init', $this, 'register_settings' );
		$this->loader->add_action( 'admin_init', $this, 'save_admin_settings' );

		// Plugin-Name überschreiben
		$this->loader->add_filter( 'lbite_plugin_display_name', $this, 'get_custom_plugin_name' );
		$this->loader->add_filter( 'lbite_plugin_menu_name', $this, 'get_custom_plugin_menu_name' );

		// Rollennamen überschreiben (mit spezifischem Filter statt gettext)
		$this->loader->add_filter( 'editable_roles', $this, 'customize_role_names' );
		$this->loader->add_filter( 'role_list', $this, 'customize_role_names' );

		// Deaktivierte Rollen filtern
		$this->loader->add_filter( 'editable_roles', $this, 'filter_disabled_roles' );

		// Menü-Sichtbarkeit filtern
		$this->loader->add_action( 'admin_menu', $this, 'filter_menu_visibility', 999 );
	}

	/**
	 * Einstellungen registrieren
	 */
	public function register_settings() {
		register_setting(
			'lbite_admin_settings',
			'lbite_admin_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_admin_settings' ),
			)
		);
	}

	/**
	 * Sanitize admin settings.
	 *
	 * @param mixed $input Input value.
	 * @return array Sanitized value.
	 */
	public function sanitize_admin_settings( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}
		return $input;
	}

	/**
	 * Admin-Einstellungen speichern
	 */
	public function save_admin_settings() {
		if ( ! isset( $_POST['lbite_admin_settings_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lbite_admin_settings_nonce'] ) ), 'lbite_save_admin_settings' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Plugin-Name speichern.
		if ( isset( $_POST['lbite_custom_plugin_name'] ) ) {
			$custom_name = sanitize_text_field( wp_unslash( $_POST['lbite_custom_plugin_name'] ) );
			update_option( 'lbite_custom_plugin_name', $custom_name );
		}

		// Rollennamen speichern.
		if ( isset( $_POST['lbite_custom_role_names'] ) && is_array( $_POST['lbite_custom_role_names'] ) ) {
			$custom_role_names = array();
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in loop below.
			foreach ( wp_unslash( $_POST['lbite_custom_role_names'] ) as $role_key => $role_name ) {
				$custom_role_names[ sanitize_key( $role_key ) ] = sanitize_text_field( $role_name );
			}
			update_option( 'lbite_custom_role_names', $custom_role_names );
		} else {
			update_option( 'lbite_custom_role_names', array() );
		}

		// Deaktivierte Rollen speichern.
		if ( isset( $_POST['lbite_disabled_roles'] ) && is_array( $_POST['lbite_disabled_roles'] ) ) {
			$disabled_roles = array_map( 'sanitize_text_field', wp_unslash( $_POST['lbite_disabled_roles'] ) );
			update_option( 'lbite_disabled_roles', $disabled_roles );
		} else {
			update_option( 'lbite_disabled_roles', array() );
		}

		// Menü-Sichtbarkeit speichern.
		if ( isset( $_POST['lbite_menu_visibility'] ) && is_array( $_POST['lbite_menu_visibility'] ) ) {
			$menu_visibility = array();
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in loop below.
			foreach ( wp_unslash( $_POST['lbite_menu_visibility'] ) as $role => $menus ) {
				if ( is_array( $menus ) ) {
					$menu_visibility[ sanitize_key( $role ) ] = array_map( 'sanitize_text_field', $menus );
				}
			}
			update_option( 'lbite_menu_visibility', $menu_visibility );
		} else {
			update_option( 'lbite_menu_visibility', array() );
		}

		// Erfolgs-Notice.
		add_settings_error(
			'lbite_admin_settings',
			'lbite_admin_settings_saved',
			__( 'Einstellungen erfolgreich gespeichert.', 'libre-bite' ),
			'success'
		);
	}

	/**
	 * Benutzerdefinierten Plugin-Namen abrufen
	 *
	 * @param string $default Standard-Name
	 * @return string
	 */
	public function get_custom_plugin_name( $default = 'Libre Bite' ) {
		$custom_name = get_option( 'lbite_custom_plugin_name', '' );
		return ! empty( $custom_name ) ? $custom_name : $default;
	}

	/**
	 * Benutzerdefinierten Plugin-Menünamen abrufen
	 *
	 * @param string $default Standard-Name
	 * @return string
	 */
	public function get_custom_plugin_menu_name( $default = 'Libre Bite' ) {
		$custom_name = get_option( 'lbite_custom_plugin_name', '' );
		// Kürzen für Menü, falls zu lang
		if ( ! empty( $custom_name ) ) {
			return strlen( $custom_name ) > 20 ? substr( $custom_name, 0, 20 ) . '...' : $custom_name;
		}
		return $default;
	}

	/**
	 * Rollennamen anpassen
	 *
	 * @param array $roles Verfügbare Rollen
	 * @return array
	 */
	public function customize_role_names( $roles ) {
		$custom_role_names = get_option( 'lbite_custom_role_names', array() );

		if ( empty( $custom_role_names ) ) {
			return $roles;
		}

		foreach ( $roles as $role_key => $role_data ) {
			if ( isset( $custom_role_names[ $role_key ] ) && ! empty( $custom_role_names[ $role_key ] ) ) {
				// Wenn $role_data ein Array ist (bei editable_roles)
				if ( is_array( $role_data ) && isset( $role_data['name'] ) ) {
					$roles[ $role_key ]['name'] = $custom_role_names[ $role_key ];
				} else {
					// Wenn $role_data ein String ist
					$roles[ $role_key ] = $custom_role_names[ $role_key ];
				}
			}
		}

		return $roles;
	}

	/**
	 * Deaktivierte Rollen aus der Liste entfernen
	 *
	 * @param array $roles Verfügbare Rollen
	 * @return array
	 */
	public function filter_disabled_roles( $roles ) {
		$disabled_roles = get_option( 'lbite_disabled_roles', array() );

		if ( empty( $disabled_roles ) ) {
			return $roles;
		}

		foreach ( $disabled_roles as $role_key ) {
			if ( isset( $roles[ $role_key ] ) ) {
				unset( $roles[ $role_key ] );
			}
		}

		return $roles;
	}

	/**
	 * Menü-Sichtbarkeit nach Rollen filtern
	 */
	public function filter_menu_visibility() {
		global $menu, $submenu;

		// Nur für Nicht-Administratoren
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		$menu_visibility = get_option( 'lbite_menu_visibility', array() );

		if ( empty( $menu_visibility ) ) {
			return;
		}

		// Aktuelle Benutzerrollen
		$user = wp_get_current_user();
		$user_roles = (array) $user->roles;

		// Liste der zu versteckenden Menüs sammeln
		$hidden_menus = array();

		foreach ( $user_roles as $role ) {
			if ( isset( $menu_visibility[ $role ] ) ) {
				$hidden_menus = array_merge( $hidden_menus, $menu_visibility[ $role ] );
			}
		}

		if ( empty( $hidden_menus ) ) {
			return;
		}

		// Menüs entfernen
		if ( is_array( $menu ) ) {
			foreach ( $menu as $key => $menu_item ) {
				if ( isset( $menu_item[2] ) && ! empty( $menu_item[2] ) && in_array( $menu_item[2], $hidden_menus, true ) ) {
					remove_menu_page( $menu_item[2] );
				}
			}
		}

		// Submenüs entfernen
		if ( is_array( $submenu ) ) {
			foreach ( $submenu as $parent_slug => $submenu_items ) {
				if ( ! is_array( $submenu_items ) ) {
					continue;
				}
				foreach ( $submenu_items as $key => $submenu_item ) {
					if ( isset( $submenu_item[2] ) && ! empty( $submenu_item[2] ) && in_array( $submenu_item[2], $hidden_menus, true ) ) {
						remove_submenu_page( $parent_slug, $submenu_item[2] );
					}
				}
			}
		}
	}

	/**
	 * Alle verfügbaren Menüeinträge abrufen
	 *
	 * @return array
	 */
	public static function get_all_menu_items() {
		global $menu, $submenu;

		// Sicherstellen, dass $menu und $submenu initialisiert sind
		if ( ! is_array( $menu ) ) {
			$menu = array();
		}
		if ( ! is_array( $submenu ) ) {
			$submenu = array();
		}

		$menu_items = array();
		$main_menu_slugs = array();

		// ERST Hauptmenüs erfassen
		foreach ( $menu as $menu_item ) {
			if ( ! is_array( $menu_item ) ) {
				continue;
			}

			// Index 0 = Titel, Index 2 = Slug
			if ( isset( $menu_item[0], $menu_item[2] ) && ! empty( $menu_item[2] ) ) {
				$menu_title = (string) $menu_item[0];
				$slug       = (string) $menu_item[2];

				// Separator überspringen
				if ( strpos( $slug, 'separator' ) !== false ) {
					continue;
				}

				// Leere Einträge überspringen
				if ( '' === $menu_title || '' === trim( $menu_title ) ) {
					continue;
				}

				// Menü-Item-Titel bereinigen (HTML-Tags und Zähler entfernen).
				$title = wp_strip_all_tags( $menu_title );
				// Zähler wie "0" oder "3" am Ende entfernen.
				$title = (string) preg_replace( '/\s+\d+$/', '', $title );

				$main_menu_slugs[] = $slug;

				$menu_items[ $slug ] = array(
					'title'  => trim( $title ),
					'slug'   => $slug,
					'parent' => '',
				);
			}
		}

		// DANN Submenüs (aber nur wenn sie NICHT das Hauptmenü selbst sind)
		foreach ( $submenu as $parent_slug => $submenu_items ) {
			if ( ! is_array( $submenu_items ) ) {
				continue;
			}

			foreach ( $submenu_items as $submenu_item ) {
				if ( ! is_array( $submenu_item ) ) {
					continue;
				}

				// Index 0 = Titel, Index 2 = Slug
				if ( isset( $submenu_item[0], $submenu_item[2] ) ) {
					$submenu_title = (string) $submenu_item[0];
					$slug          = (string) $submenu_item[2];

					// Leere Titel überspringen
					if ( '' === $submenu_title ) {
						continue;
					}

					// Überspringen, wenn dieser Slug bereits als Hauptmenü existiert
					// ABER: nur wenn der Slug identisch mit dem Parent ist (das erste Submenü)
					if ( $slug === $parent_slug && in_array( $slug, $main_menu_slugs, true ) ) {
						continue;
					}

					// Titel bereinigen.
					$title = wp_strip_all_tags( $submenu_title );
					// Zähler entfernen.
					$title = (string) preg_replace( '/\s+\d+$/', '', $title );

					// Nur hinzufügen, wenn es noch nicht existiert oder als Submenü überschrieben werden soll
					if ( ! isset( $menu_items[ $slug ] ) || ! in_array( $slug, $main_menu_slugs, true ) ) {
						$menu_items[ $slug ] = array(
							'title'  => trim( $title ),
							'slug'   => $slug,
							'parent' => (string) $parent_slug,
						);
					}
				}
			}
		}

		return $menu_items;
	}

	/**
	 * Alle WordPress-Rollen abrufen
	 *
	 * @param bool $include_admin Administrator-Rolle einschließen
	 * @return array
	 */
	public static function get_all_roles( $include_admin = false ) {
		global $wp_roles;

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$roles = array();

		foreach ( $wp_roles->roles as $role_key => $role_data ) {
			// Administrator überspringen (hat immer vollen Zugriff)
			if ( $role_key === 'administrator' && ! $include_admin ) {
				continue;
			}

			$roles[ $role_key ] = $role_data['name'];
		}

		return $roles;
	}
}
