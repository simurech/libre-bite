<?php
/**
 * Einrichtungsassistent
 *
 * Eine frische Installation startete bisher vollständig leer: keine
 * Standorte, keine Produkte, keine Öffnungszeiten. Wer das Plugin bewerten
 * wollte, musste erst alles von Hand anlegen.
 *
 * Der Assistent führt in vier Schritten durch die Ersteinrichtung und kann
 * auf Wunsch ein vollständiges Beispielsortiment anlegen.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse LBite_Setup_Wizard
 */
class LBite_Setup_Wizard {

	/**
	 * Seiten-Slug.
	 */
	const PAGE = 'lbite-setup';

	/**
	 * Meta-Schlüssel, mit dem importierte Beispieldaten markiert werden.
	 */
	const DEMO_META = '_lbite_demo_content';

	/**
	 * Option: Assistent abgeschlossen.
	 */
	const OPTION_DONE = 'lbite_setup_completed';

	/**
	 * Loader-Instanz.
	 *
	 * @var LBite_Loader
	 */
	private $loader;

	/**
	 * Konstruktor
	 *
	 * @param LBite_Loader $loader Hook-Loader.
	 */
	public function __construct( $loader ) {
		$this->loader = $loader;

		$this->loader->add_action( 'admin_menu', $this, 'register_page', 20 );
		$this->loader->add_action( 'wp_ajax_lbite_setup_import_demo', $this, 'ajax_import_demo' );
		$this->loader->add_action( 'wp_ajax_lbite_setup_finish', $this, 'ajax_finish' );
	}

	/**
	 * Seite registrieren, aber aus dem Menü entfernen
	 *
	 * Die Registrierung ist nötig, sonst verweigert WordPress den direkten
	 * Aufruf der URL. Dasselbe Muster nutzt bereits die Dashboard-Seite für
	 * Rollen ohne Einstellungsrecht.
	 */
	public function register_page() {
		add_submenu_page(
			'libre-bite',
			__( 'Setup', 'libre-bite' ),
			__( 'Setup', 'libre-bite' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render' )
		);

		remove_submenu_page( 'libre-bite', self::PAGE );
	}

	/**
	 * Ist der Assistent noch nicht abgeschlossen?
	 *
	 * @return bool
	 */
	public static function is_pending() {
		return ! get_option( self::OPTION_DONE, false );
	}

	/**
	 * URL des Assistenten
	 *
	 * @return string
	 */
	public static function get_url() {
		return admin_url( 'admin.php?page=' . self::PAGE );
	}

	/**
	 * Assistent rendern
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'libre-bite' ) );
		}

		wp_enqueue_media();
		include LBITE_PLUGIN_DIR . 'templates/admin/setup-wizard.php';
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Systemprüfung
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Umgebungsprüfungen für Schritt 2
	 *
	 * @return array Liste aus [ 'label', 'ok', 'hint' ].
	 */
	public static function get_system_checks() {
		$checks = array();

		$checks[] = array(
			'label' => __( 'WooCommerce is active', 'libre-bite' ),
			'ok'    => class_exists( 'WooCommerce' ),
			'hint'  => __( 'Libre Bite is a WooCommerce extension and cannot work without it.', 'libre-bite' ),
		);

		// Die Mindestversion wird aus dem Plugin-Header gelesen statt hier
		// zweitgeschrieben. Eine fest eingetragene Zahl läuft sonst
		// zwangsläufig irgendwann gegen den Header und meldet Nutzern
		// fälschlich, sie erfüllten die Anforderung nicht.
		$lbite_min_php = self::get_required_php();

		$checks[] = array(
			'label' => sprintf(
				/* translators: %s: PHP version */
				__( 'PHP %s or newer', 'libre-bite' ),
				$lbite_min_php
			),
			'ok'    => version_compare( PHP_VERSION, $lbite_min_php, '>=' ),
			'hint'  => sprintf(
				/* translators: %s: current PHP version */
				__( 'Currently running PHP %s.', 'libre-bite' ),
				PHP_VERSION
			),
		);

		$checks[] = array(
			'label' => __( 'A currency is configured', 'libre-bite' ),
			'ok'    => function_exists( 'get_woocommerce_currency' ) && '' !== get_woocommerce_currency(),
			'hint'  => __( 'Set your currency under WooCommerce → Settings → General.', 'libre-bite' ),
		);

		$checks[] = array(
			'label' => __( 'At least one payment method is enabled', 'libre-bite' ),
			'ok'    => self::has_active_gateway(),
			'hint'  => __( 'Without a payment method guests cannot complete an order.', 'libre-bite' ),
		);

		$checks[] = array(
			'label' => __( 'Scheduled tasks are running', 'libre-bite' ),
			'ok'    => ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) || (bool) wp_next_scheduled( 'lbite_check_scheduled_orders' ),
			'hint'  => __( 'Pre-orders and pickup reminders rely on WordPress scheduled tasks.', 'libre-bite' ),
		);

		return $checks;
	}

	/**
	 * Vom Plugin-Header geforderte PHP-Mindestversion
	 *
	 * Einzige Quelle ist der Header — WordPress wertet ihn ohnehin aus, und
	 * eine zweite Angabe im Code würde früher oder später abweichen.
	 *
	 * @return string
	 */
	private static function get_required_php() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$lbite_data = get_plugin_data( LBITE_PLUGIN_FILE, false, false );

		return ! empty( $lbite_data['RequiresPHP'] ) ? $lbite_data['RequiresPHP'] : '7.4';
	}

	/**
	 * Ist mindestens ein Zahlungs-Gateway aktiv?
	 *
	 * @return bool
	 */
	private static function has_active_gateway() {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways ) {
			return false;
		}

		return ! empty( WC()->payment_gateways->get_available_payment_gateways() );
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Beispieldaten
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * AJAX: Beispieldaten anlegen
	 */
	public function ajax_import_demo() {
		check_ajax_referer( 'lbite_setup_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce is required.', 'libre-bite' ) ) );
		}

		$result = $this->import_demo_content();

		wp_send_json_success( $result );
	}

	/**
	 * Beispielsortiment anlegen
	 *
	 * Idempotent: bereits angelegte Beispielobjekte werden erkannt und nicht
	 * doppelt erzeugt. Ein zweiter Klick darf keinen zweiten Standort und
	 * keine doppelten Produkte hinterlassen.
	 *
	 * @return array Zusammenfassung des Imports.
	 */
	public function import_demo_content() {
		$created = array(
			'location'   => 0,
			'categories' => 0,
			'products'   => 0,
			'options'    => 0,
			'skipped'    => 0,
		);

		// ── Standort ──────────────────────────────────────────────────
		$location_id = $this->find_demo_post( 'lbite_location', 'demo-location' );

		if ( ! $location_id ) {
			$location_id = wp_insert_post(
				array(
					'post_type'   => 'lbite_location',
					'post_status' => 'publish',
					'post_title'  => __( 'Main Branch', 'libre-bite' ),
				)
			);

			if ( $location_id && ! is_wp_error( $location_id ) ) {
				update_post_meta( $location_id, self::DEMO_META, 'demo-location' );
				update_post_meta( $location_id, '_lbite_street', 'Bahnhofstrasse 1' );
				update_post_meta( $location_id, '_lbite_zip', '8001' );
				update_post_meta( $location_id, '_lbite_city', 'Zürich' );
				update_post_meta( $location_id, '_lbite_preparation_time', 30 );
				update_post_meta( $location_id, '_lbite_opening_hours', $this->get_demo_opening_hours() );
				$created['location'] = 1;
			}
		} else {
			$created['skipped']++;
		}

		// ── Kategorien ────────────────────────────────────────────────
		$category_ids = array();

		foreach ( $this->get_demo_categories() as $slug => $name ) {
			$term = get_term_by( 'slug', $slug, 'product_cat' );

			if ( $term ) {
				$category_ids[ $slug ] = (int) $term->term_id;
				$created['skipped']++;
				continue;
			}

			$new = wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) );

			if ( ! is_wp_error( $new ) ) {
				$category_ids[ $slug ] = (int) $new['term_id'];
				update_term_meta( $new['term_id'], self::DEMO_META, 1 );
				$created['categories']++;
			}
		}

		// ── Produktoptionen ───────────────────────────────────────────
		$option_ids = array();

		foreach ( $this->get_demo_options() as $key => $option ) {
			$existing = $this->find_demo_post( 'lbite_product_option', 'demo-option-' . $key );

			if ( $existing ) {
				$option_ids[] = $existing;
				$created['skipped']++;
				continue;
			}

			$oid = wp_insert_post(
				array(
					'post_type'   => 'lbite_product_option',
					'post_status' => 'publish',
					'post_title'  => $option['name'],
				)
			);

			if ( $oid && ! is_wp_error( $oid ) ) {
				update_post_meta( $oid, self::DEMO_META, 'demo-option-' . $key );
				update_post_meta( $oid, '_lbite_price', $option['price'] );
				$option_ids[] = $oid;
				$created['options']++;
			}
		}

		// ── Produkte ──────────────────────────────────────────────────
		foreach ( $this->get_demo_products() as $key => $item ) {
			if ( $this->find_demo_post( 'product', 'demo-product-' . $key ) ) {
				$created['skipped']++;
				continue;
			}

			$product = new WC_Product_Simple();
			$product->set_name( $item['name'] );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			$product->set_regular_price( (string) $item['price'] );
			$product->set_short_description( $item['description'] );
			$product->set_manage_stock( false );

			if ( isset( $category_ids[ $item['category'] ] ) ) {
				$product->set_category_ids( array( $category_ids[ $item['category'] ] ) );
			}

			$product_id = $product->save();

			if ( ! $product_id ) {
				continue;
			}

			update_post_meta( $product_id, self::DEMO_META, 'demo-product-' . $key );

			if ( ! empty( $item['options'] ) && ! empty( $option_ids ) ) {
				update_post_meta( $product_id, '_lbite_product_options', $option_ids );
			}

			if ( ! empty( $item['allergens'] ) ) {
				update_post_meta( $product_id, '_lbite_allergens', $item['allergens'] );
			}

			if ( ! empty( $item['dietary'] ) ) {
				update_post_meta( $product_id, '_lbite_dietary', $item['dietary'] );
			}

			$created['products']++;
		}

		return $created;
	}

	/**
	 * Bereits importiertes Beispielobjekt finden
	 *
	 * @param string $post_type Beitragstyp.
	 * @param string $marker    Wert des Demo-Meta-Schlüssels.
	 * @return int Beitrags-ID oder 0.
	 */
	private function find_demo_post( $post_type, $marker ) {
		$found = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Einmalige Prüfung beim Import, kein Laufzeitpfad.
				'meta_key'       => self::DEMO_META,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Siehe oben.
				'meta_value'     => $marker,
			)
		);

		return ! empty( $found ) ? (int) $found[0] : 0;
	}

	/**
	 * Öffnungszeiten für den Beispielstandort
	 *
	 * @return array
	 */
	private function get_demo_opening_hours() {
		$hours   = array();
		$weekday = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday' );

		foreach ( $weekday as $day ) {
			$hours[ $day ] = array(
				'closed' => false,
				'open'   => '11:00',
				'close'  => '14:00',
				'open2'  => '17:00',
				'close2' => '22:00',
			);
		}

		$hours['saturday'] = array(
			'closed' => false,
			'open'   => '11:00',
			'close'  => '23:00',
			'open2'  => '',
			'close2' => '',
		);

		$hours['sunday'] = array(
			'closed' => true,
			'open'   => '',
			'close'  => '',
			'open2'  => '',
			'close2' => '',
		);

		return $hours;
	}

	/**
	 * Beispielkategorien
	 *
	 * @return array
	 */
	private function get_demo_categories() {
		return array(
			'lbite-demo-mains'    => __( 'Main dishes', 'libre-bite' ),
			'lbite-demo-sides'    => __( 'Sides', 'libre-bite' ),
			'lbite-demo-drinks'   => __( 'Drinks', 'libre-bite' ),
		);
	}

	/**
	 * Beispiel-Produktoptionen
	 *
	 * @return array
	 */
	private function get_demo_options() {
		return array(
			'cheese' => array(
				'name'  => __( 'Extra cheese', 'libre-bite' ),
				'price' => 2.0,
			),
			'bacon'  => array(
				'name'  => __( 'Extra bacon', 'libre-bite' ),
				'price' => 3.0,
			),
			'salad'  => array(
				'name'  => __( 'Side salad', 'libre-bite' ),
				'price' => 4.5,
			),
			'spicy'  => array(
				'name'  => __( 'Extra spicy', 'libre-bite' ),
				'price' => 0.0,
			),
		);
	}

	/**
	 * Beispielprodukte
	 *
	 * @return array
	 */
	private function get_demo_products() {
		return array(
			'burger' => array(
				'name'        => __( 'Classic Burger', 'libre-bite' ),
				'description' => __( 'Beef patty, salad, tomato, house sauce.', 'libre-bite' ),
				'price'       => 18.50,
				'category'    => 'lbite-demo-mains',
				'options'     => true,
				'allergens'   => array( 'gluten', 'eggs', 'milk', 'mustard' ),
				'dietary'     => array(),
			),
			'veggie' => array(
				'name'        => __( 'Veggie Bowl', 'libre-bite' ),
				'description' => __( 'Seasonal vegetables, quinoa, herb dressing.', 'libre-bite' ),
				'price'       => 16.00,
				'category'    => 'lbite-demo-mains',
				'options'     => true,
				'allergens'   => array( 'celery' ),
				'dietary'     => array( 'vegan', 'vegetarian', 'gluten_free' ),
			),
			'pasta' => array(
				'name'        => __( 'Pasta of the Day', 'libre-bite' ),
				'description' => __( 'Fresh pasta with a changing sauce.', 'libre-bite' ),
				'price'       => 17.50,
				'category'    => 'lbite-demo-mains',
				'options'     => true,
				'allergens'   => array( 'gluten', 'eggs', 'milk' ),
				'dietary'     => array( 'vegetarian' ),
			),
			'soup' => array(
				'name'        => __( 'Soup of the Day', 'libre-bite' ),
				'description' => __( 'Ask our staff what is cooking today.', 'libre-bite' ),
				'price'       => 9.50,
				'category'    => 'lbite-demo-mains',
				'options'     => false,
				'allergens'   => array( 'celery' ),
				'dietary'     => array( 'vegetarian' ),
			),
			'fries' => array(
				'name'        => __( 'Fries', 'libre-bite' ),
				'description' => __( 'Crispy, with sea salt.', 'libre-bite' ),
				'price'       => 6.50,
				'category'    => 'lbite-demo-sides',
				'options'     => false,
				'allergens'   => array(),
				'dietary'     => array( 'vegan', 'vegetarian', 'gluten_free' ),
			),
			'salad' => array(
				'name'        => __( 'Green Salad', 'libre-bite' ),
				'description' => __( 'Leaf salad with vinaigrette.', 'libre-bite' ),
				'price'       => 7.50,
				'category'    => 'lbite-demo-sides',
				'options'     => false,
				'allergens'   => array( 'mustard' ),
				'dietary'     => array( 'vegan', 'vegetarian', 'gluten_free' ),
			),
			'water' => array(
				'name'        => __( 'Mineral Water 5dl', 'libre-bite' ),
				'description' => __( 'Sparkling or still.', 'libre-bite' ),
				'price'       => 4.50,
				'category'    => 'lbite-demo-drinks',
				'options'     => false,
				'allergens'   => array(),
				'dietary'     => array( 'vegan', 'vegetarian', 'gluten_free', 'lactose_free', 'alcohol_free' ),
			),
			'coffee' => array(
				'name'        => __( 'Coffee', 'libre-bite' ),
				'description' => __( 'Espresso, or with milk on request.', 'libre-bite' ),
				'price'       => 4.20,
				'category'    => 'lbite-demo-drinks',
				'options'     => false,
				'allergens'   => array(),
				'dietary'     => array( 'vegan', 'vegetarian', 'gluten_free', 'alcohol_free' ),
			),
		);
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Abschluss
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * AJAX: Assistent abschliessen
	 */
	public function ajax_finish() {
		check_ajax_referer( 'lbite_setup_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'No permission', 'libre-bite' ) ) );
		}

		// Gewählte Module übernehmen.
		$selected = isset( $_POST['features'] ) && is_array( $_POST['features'] )
			? array_map( 'sanitize_key', wp_unslash( $_POST['features'] ) )
			: array();

		$definitions = LBite_Features::get_definitions();
		$features    = get_option( 'lbite_features', array() );

		foreach ( self::get_wizard_features() as $key ) {
			if ( isset( $definitions[ $key ] ) ) {
				$features[ $key ] = in_array( $key, $selected, true );
			}
		}

		update_option( 'lbite_features', $features );
		update_option( self::OPTION_DONE, true );

		// Die alte Willkommens-Notice hat damit ausgedient.
		update_option( 'lbite_show_welcome_notice', false );

		wp_send_json_success( array( 'redirect' => admin_url( 'admin.php?page=libre-bite' ) ) );
	}

	/**
	 * Module, die im Assistenten zur Wahl stehen
	 *
	 * Bewusst eine kleine Auswahl: der Assistent soll die häufigsten
	 * Entscheidungen abnehmen, nicht die vollständige Einstellungsseite
	 * ersetzen.
	 *
	 * @return string[]
	 */
	public static function get_wizard_features() {
		return array(
			'enable_pos',
			'enable_kanban_board',
			'enable_scheduled_orders',
			'enable_location_selector',
			'enable_product_options',
		);
	}
}
