<?php
/**
 * Onboarding – Einführungsseite nach Erstinstallation
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Onboarding-Klasse
 */
class LBite_Onboarding {

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
		$this->loader->add_action( 'admin_init', $this, 'handle_activation_redirect' );
		$this->loader->add_action( 'admin_menu', $this, 'register_onboarding_page' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_assets' );
		$this->loader->add_action( 'wp_ajax_lbite_complete_onboarding', $this, 'ajax_complete_onboarding' );
	}

	/**
	 * Einmaligen Aktivierungs-Redirect ausführen
	 */
	public function handle_activation_redirect() {
		if ( ! get_option( 'lbite_do_activation_redirect' ) ) {
			return;
		}

		// Nur wenn kein Bulk-Aktivierungs-Request
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Lese-Parameter zur Erkennung von Bulk-Aktivierungen; kein DB-Schreibzugriff.
		if ( isset( $_GET['activate-multi'] ) ) {
			return;
		}

		delete_option( 'lbite_do_activation_redirect' );

		wp_safe_redirect( admin_url( 'admin.php?page=lbite-onboarding' ) );
		exit;
	}

	/**
	 * Onboarding-Seite im Admin-Menü registrieren (versteckt)
	 */
	public function register_onboarding_page() {
		add_submenu_page(
			null, // Kein Eltern-Menü – versteckte Seite
			__( 'Einrichtung', 'libre-bite' ),
			__( 'Einrichtung', 'libre-bite' ),
			'lbite_manage_features',
			'lbite-onboarding',
			array( $this, 'render_onboarding_page' )
		);
	}

	/**
	 * Assets für Onboarding-Seite laden
	 *
	 * @param string $hook Aktuelle Admin-Seite
	 */
	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'lbite-onboarding' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'lbite-onboarding',
			LBITE_PLUGIN_URL . 'assets/css/admin-onboarding.css',
			array(),
			LBITE_VERSION
		);

		wp_enqueue_script(
			'lbite-onboarding',
			LBITE_PLUGIN_URL . 'assets/js/admin-onboarding.js',
			array( 'jquery' ),
			LBITE_VERSION,
			true
		);

		wp_localize_script(
			'lbite-onboarding',
			'lbiteOnboarding',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'lbite_onboarding_nonce' ),
				'dashboardUrl' => admin_url( 'admin.php?page=libre-bite' ),
				'strings'      => array(
					'saving'  => __( 'Wird gespeichert...', 'libre-bite' ),
					'success' => __( 'Einrichtung abgeschlossen!', 'libre-bite' ),
					'error'   => __( 'Fehler beim Speichern. Bitte versuche es erneut.', 'libre-bite' ),
				),
			)
		);
	}

	/**
	 * Onboarding-Seite rendern
	 */
	public function render_onboarding_page() {
		if ( ! current_user_can( 'lbite_manage_features' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung für diese Seite.', 'libre-bite' ) );
		}

		include LBITE_PLUGIN_DIR . 'templates/admin/onboarding.php';
	}

	/**
	 * AJAX: Onboarding abschliessen und Features speichern
	 */
	public function ajax_complete_onboarding() {
		check_ajax_referer( 'lbite_onboarding_nonce', 'nonce' );

		if ( ! current_user_can( 'lbite_manage_features' ) ) {
			wp_die( -1 );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON-String wird nach dem Decode über sanitize_key() und sanitize_text_field() validiert.
		$features_json = isset( $_POST['features'] ) ? wp_unslash( $_POST['features'] ) : '';
		$features      = json_decode( $features_json, true );

		if ( ! is_array( $features ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungültige Daten', 'libre-bite' ) ) );
		}

		// Feature-Werte als boolean validieren
		$sanitized = array();
		foreach ( $features as $key => $value ) {
			$sanitized[ sanitize_key( $key ) ] = (bool) $value;
		}

		update_option( 'lbite_features', $sanitized );
		update_option( 'lbite_onboarding_completed', true );

		wp_send_json_success( array( 'redirect' => admin_url( 'admin.php?page=libre-bite' ) ) );
	}
}
