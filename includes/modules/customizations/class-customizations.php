<?php
/**
 * WordPress & WooCommerce Anpassungen
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Customizations-Modul
 */
class LBite_Customizations {

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
		// WordPress Posts deaktivieren - Mehrere Hooks mit hoher Priorität
		$this->loader->add_action( 'admin_menu', $this, 'remove_posts_menu', 999 );
		$this->loader->add_action( 'admin_bar_menu', $this, 'remove_posts_admin_bar', 999 );
		$this->loader->add_action( 'wp_before_admin_bar_render', $this, 'remove_posts_admin_bar_items' );
		$this->loader->add_filter( 'register_post_type_args', $this, 'disable_post_type_frontend', 10, 2 );

		// Zusätzliche Hooks für vollständige Deaktivierung
		$this->loader->add_action( 'init', $this, 'unregister_post_type', 999 );
		$this->loader->add_filter( 'post_type_link', $this, 'disable_post_links', 10, 2 );
		$this->loader->add_action( 'admin_head', $this, 'hide_posts_with_css' );

		// WooCommerce "Mein Konto" anpassen
		$this->loader->add_filter( 'woocommerce_account_menu_items', $this, 'customize_my_account_menu' );
	}

	/**
	 * Posts-Menü aus Admin entfernen
	 */
	public function remove_posts_menu() {
		remove_menu_page( 'edit.php' );
		remove_submenu_page( 'edit.php', 'post-new.php' );
		remove_submenu_page( 'edit.php', 'edit-tags.php?taxonomy=category' );
		remove_submenu_page( 'edit.php', 'edit-tags.php?taxonomy=post_tag' );
	}

	/**
	 * Posts aus Admin-Bar entfernen
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin-Bar-Objekt
	 */
	public function remove_posts_admin_bar( $wp_admin_bar ) {
		$wp_admin_bar->remove_node( 'new-post' );
	}

	/**
	 * Posts-Items aus Admin-Bar entfernen
	 */
	public function remove_posts_admin_bar_items() {
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu( 'new-post' );
	}

	/**
	 * Post-Type Frontend deaktivieren
	 *
	 * @param array  $args      Post-Type-Argumente
	 * @param string $post_type Post-Type
	 * @return array
	 */
	public function disable_post_type_frontend( $args, $post_type ) {
		if ( 'post' === $post_type ) {
			$args['public']              = false;
			$args['publicly_queryable']  = false;
			$args['exclude_from_search'] = true;
			$args['show_ui']             = false;
			$args['show_in_nav_menus']   = false;
			$args['show_in_admin_bar']   = false;
		}
		return $args;
	}

	/**
	 * Post-Type komplett deregistrieren
	 */
	public function unregister_post_type() {
		global $wp_post_types;
		if ( isset( $wp_post_types['post'] ) ) {
			$wp_post_types['post']->show_ui             = false;
			$wp_post_types['post']->show_in_menu        = false;
			$wp_post_types['post']->show_in_admin_bar   = false;
			$wp_post_types['post']->show_in_nav_menus   = false;
			$wp_post_types['post']->publicly_queryable  = false;
			$wp_post_types['post']->exclude_from_search = true;
		}
	}

	/**
	 * Post-Links deaktivieren
	 *
	 * @param string  $link      Post-Link
	 * @param WP_Post $post      Post-Objekt
	 * @return string
	 */
	public function disable_post_links( $link, $post ) {
		if ( 'post' === $post->post_type ) {
			return '';
		}
		return $link;
	}

	/**
	 * Posts-Menü via CSS verstecken (Fallback)
	 */
	public function hide_posts_with_css() {
		$css = '#menu-posts, #wp-admin-bar-new-post { display: none !important; }';
		wp_add_inline_style( 'wp-admin', $css );
	}

	/**
	 * WooCommerce "Mein Konto" Menü anpassen
	 *
	 * @param array $items Menü-Items
	 * @return array
	 */
	public function customize_my_account_menu( $items ) {
		// "Downloads" entfernen
		if ( isset( $items['downloads'] ) ) {
			unset( $items['downloads'] );
		}

		// "Adressen" entfernen
		if ( isset( $items['edit-address'] ) ) {
			unset( $items['edit-address'] );
		}

		return $items;
	}
}
