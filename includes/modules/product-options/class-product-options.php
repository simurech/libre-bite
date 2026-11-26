<?php
/**
 * Produkt-Optionen/Add-ons
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product-Options-Modul
 */
class LBite_Product_Options {

	/**
	 * Loader-Instanz
	 *
	 * @var LBite_Loader
	 */
	private $loader;

	/**
	 * Post-Type Name
	 *
	 * @var string
	 */
	const POST_TYPE = 'lbite_product_option';

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
		$this->loader->add_action( 'init', $this, 'register_post_type' );
		$this->loader->add_action( 'add_meta_boxes', $this, 'add_meta_boxes' );
		$this->loader->add_action( 'save_post_' . self::POST_TYPE, $this, 'save_option_meta', 10, 2 );

		// Produkt-Meta-Box
		$this->loader->add_action( 'add_meta_boxes', $this, 'add_product_meta_box' );
		$this->loader->add_action( 'woocommerce_process_product_meta', $this, 'save_product_options' );

		// Frontend: Produktseite
		$this->loader->add_action( 'woocommerce_before_add_to_cart_button', $this, 'render_product_options' );

		// Warenkorb
		$this->loader->add_filter( 'woocommerce_add_to_cart_validation', $this, 'validate_add_to_cart', 20, 3 );
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_product_page_assets' );
		$this->loader->add_filter( 'woocommerce_add_cart_item_data', $this, 'add_cart_item_data', 10, 2 );
		$this->loader->add_filter( 'woocommerce_get_item_data', $this, 'display_cart_item_data', 10, 2 );
		$this->loader->add_filter( 'woocommerce_add_cart_item', $this, 'add_cart_item_price', 10, 1 );
		$this->loader->add_filter( 'woocommerce_get_cart_item_from_session', $this, 'add_cart_item_price', 10, 1 );
		$this->loader->add_action( 'woocommerce_checkout_create_order_line_item', $this, 'add_order_item_meta', 10, 4 );

		// Admin-Spalten
		$this->loader->add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', $this, 'add_admin_columns' );
		$this->loader->add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', $this, 'render_admin_columns', 10, 2 );
	}

	/**
	 * Custom Post Type registrieren
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Product Options', 'libre-bite' ),
			'singular_name'      => __( 'Product Option', 'libre-bite' ),
			'menu_name'          => __( 'Product Options', 'libre-bite' ),
			'add_new'            => __( 'New Option', 'libre-bite' ),
			'add_new_item'       => __( 'Add New Option', 'libre-bite' ),
			'edit_item'          => __( 'Edit Option', 'libre-bite' ),
			'new_item'           => __( 'New Option', 'libre-bite' ),
			'view_item'          => __( 'View Option', 'libre-bite' ),
			'search_items'       => __( 'Search Options', 'libre-bite' ),
			'not_found'          => __( 'No options found', 'libre-bite' ),
			'not_found_in_trash' => __( 'No options in trash', 'libre-bite' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'query_var'           => true,
			'rewrite'             => false,
			'capability_type'     => 'post',
			// Eigene Capability statt der Standard-post-Rechte (Audit
			// 26.09.2026, AP-10).
			'capabilities'        => array(
				'edit_post'          => 'lbite_manage_options',
				'edit_posts'         => 'lbite_manage_options',
				'edit_others_posts'  => 'lbite_manage_options',
				'publish_posts'      => 'lbite_manage_options',
				'read_post'          => 'lbite_manage_options',
				'read_private_posts' => 'lbite_manage_options',
				'delete_post'        => 'lbite_manage_options',
			),
			'map_meta_cap'        => false,
			'has_archive'         => false,
			'hierarchical'        => false,
			'supports'            => array( 'title', 'editor' ),
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Meta-Boxen hinzufügen
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'lbite_option_price',
			__( 'Price', 'libre-bite' ),
			array( $this, 'render_price_meta_box' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Preis-Meta-Box rendern
	 *
	 * @param WP_Post $post Post-Objekt
	 */
	public function render_price_meta_box( $post ) {
		wp_nonce_field( 'lbite_save_option', 'lbite_option_nonce' );

		$price = get_post_meta( $post->ID, '_lbite_price', true );
		?>
		<p>
			<?php
			// WooCommerce liefert Symbole wie CHF als HTML-Entities
			// ("&#67;&#72;&#70;"), für direktes echo gedacht - esc_html()
			// escaped das "&" davon noch einmal und liess die Codes wörtlich
			// statt "CHF" erscheinen (Nutzer-Fund 2026-09-27).
			$lbite_currency_symbol = html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' );
			?>
			<label for="lbite_option_price"><?php esc_html_e( 'Price', 'libre-bite' ); ?> (<?php echo esc_html( $lbite_currency_symbol ); ?>)</label><br>
			<input type="number" step="0.01" min="0" id="lbite_option_price" name="lbite_option_price" value="<?php echo esc_attr( $price ); ?>" style="width: 100%;">
		</p>
		<p class="description">
			<?php esc_html_e( 'This price is added to the product price when the option is selected.', 'libre-bite' ); ?>
		</p>
		<?php
	}

	/**
	 * Options-Meta speichern
	 *
	 * @param int     $post_id Post-ID
	 * @param WP_Post $post    Post-Objekt
	 */
	public function save_option_meta( $post_id, $post ) {
		// Nonce prüfen.
		if ( ! isset( $_POST['lbite_option_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lbite_option_nonce'] ) ), 'lbite_save_option' ) ) {
			return;
		}

		// Autosave prüfen.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Berechtigungen prüfen.
		if ( ! current_user_can( 'lbite_manage_options', $post_id ) ) {
			return;
		}

		// Preis speichern.
		if ( isset( $_POST['lbite_option_price'] ) ) {
			$price = floatval( wp_unslash( $_POST['lbite_option_price'] ) );
			update_post_meta( $post_id, '_lbite_price', $price );
		}
	}

	/**
	 * Produkt-Meta-Box hinzufügen
	 */
	public function add_product_meta_box() {
		add_meta_box(
			'lbite_product_options',
			__( 'Product Options', 'libre-bite' ),
			array( $this, 'render_product_options_meta_box' ),
			'product',
			'normal',
			'default'
		);
	}

	/**
	 * Produkt-Optionen Meta-Box rendern
	 *
	 * @param WP_Post $post Post-Objekt
	 */
	public function render_product_options_meta_box( $post ) {
		wp_nonce_field( 'lbite_save_product_options', 'lbite_product_options_nonce' );

		$selected_options = get_post_meta( $post->ID, '_lbite_product_options', true );
		if ( ! is_array( $selected_options ) ) {
			$selected_options = array();
		}

		$options = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => 200, // Begrenzt für Performance.
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( empty( $options ) ) {
			echo '<p>' . esc_html__( 'No options available yet.', 'libre-bite' ) . ' ';
			echo '<a href="' . esc_url( admin_url( 'post-new.php?post_type=' . self::POST_TYPE ) ) . '">' . esc_html__( 'Create Now', 'libre-bite' ) . '</a></p>';
			return;
		}

		if ( self::groups_available() ) {
			echo '<h4 style="margin: 0 0 4px;">' . esc_html__( 'Extras without rules', 'libre-bite' ) . '</h4>';
			echo '<p class="description" style="margin-top: 0;">' . esc_html__( 'Guests can add these freely. For rules such as "choose 1", put the options into a group below.', 'libre-bite' ) . '</p>';
		}

		echo '<div style="max-height: 300px; overflow-y: auto;">';
		foreach ( $options as $option ) {
			$checked = in_array( $option->ID, $selected_options, true );
			$price   = get_post_meta( $option->ID, '_lbite_price', true );
			?>
			<label class="lbite-flat-option" data-option-id="<?php echo esc_attr( $option->ID ); ?>" style="display: block; margin-bottom: 8px; padding: 5px; background: #f9f9f9; border-radius: 3px;">
				<input type="checkbox" name="lbite_product_options[]" value="<?php echo esc_attr( $option->ID ); ?>" <?php checked( $checked ); ?>>
				<strong><?php echo esc_html( $option->post_title ); ?></strong>
				<?php if ( $price ) : ?>
					<span style="color: #666;">(+<?php echo wp_kses_post( wc_price( $price ) ); ?>)</span>
				<?php endif; ?>
				<?php if ( $option->post_content ) : ?>
					<br><span style="font-size: 12px; color: #666;"><?php echo esc_html( wp_trim_words( $option->post_content, 15 ) ); ?></span>
				<?php endif; ?>
				<span class="lbite-flat-option__group" style="display: none; font-size: 12px; color: #2271b1;"></span>
			</label>
			<?php
		}
		echo '</div>';

		// Modifikator-Gruppen (Pro, F39). Die Klasse existiert nur im Pro-Build.
		if ( self::groups_available() ) {
			LBite_Option_Groups::render_admin( $post, $options );
		}
	}

	/**
	 * Produkt-Optionen speichern
	 *
	 * @param int $post_id Post-ID
	 */
	public function save_product_options( $post_id ) {
		// Nonce prüfen.
		if ( ! isset( $_POST['lbite_product_options_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lbite_product_options_nonce'] ) ), 'lbite_save_product_options' ) ) {
			return;
		}

		// Berechtigungen prüfen.
		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		// Optionen speichern.
		$options = isset( $_POST['lbite_product_options'] ) && is_array( $_POST['lbite_product_options'] )
			? array_map( 'intval', wp_unslash( $_POST['lbite_product_options'] ) )
			: array();

		update_post_meta( $post_id, '_lbite_product_options', $options );

		if ( self::groups_available() ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Wird in LBite_Option_Groups::sanitize_groups() Feld für Feld bereinigt.
			$raw_groups = isset( $_POST['lbite_option_groups'] ) && is_array( $_POST['lbite_option_groups'] ) ? wp_unslash( $_POST['lbite_option_groups'] ) : array();
			LBite_Option_Groups::save( $post_id, $raw_groups );
		}
	}

	/*
	 * ------------------------------------------------------------------
	 * Gruppen (F39) – gemeinsame Grundlage für Produktseite, Warenkorb,
	 * Menü-Ansicht und Kasse
	 * ------------------------------------------------------------------
	 */

	/**
	 * Sind Modifikator-Gruppen nutzbar (Pro-Build, Lizenz, Schalter)?
	 *
	 * @return bool
	 */
	public static function groups_available() {
		return class_exists( 'LBite_Option_Groups' ) && lbite_feature_enabled( 'enable_modifier_groups' );
	}

	/**
	 * Eltern-Produkt-ID (Optionen und Gruppen hängen immer am Eltern-Produkt).
	 *
	 * @param int $product_id Produkt- oder Varianten-ID.
	 * @return int
	 */
	private static function base_product_id( $product_id ) {
		$product_id = (int) $product_id;
		if ( 'product_variation' === get_post_type( $product_id ) ) {
			$parent = (int) wp_get_post_parent_id( $product_id );
			if ( $parent ) {
				return $parent;
			}
		}

		return $product_id;
	}

	/**
	 * Alle Gruppen eines Produkts, mit Optionsdaten.
	 *
	 * Die bisherige flache Optionsliste erscheint als regellose Gruppe
	 * «Extras». Ohne Pro (oder ohne Lizenz) zählen auch die Optionen der
	 * Gruppen als solche Extras – nichts verschwindet, nur die Regeln entfallen.
	 *
	 * @param int $product_id Produkt- oder Varianten-ID.
	 * @return array[] Gruppen mit id, label, type, min, max, free, option_ids, options[id,name,price].
	 */
	public static function get_product_groups( $product_id ) {
		$base   = self::base_product_id( $product_id );
		$legacy = get_post_meta( $base, '_lbite_product_options', true );
		$legacy = is_array( $legacy ) ? array_map( 'intval', $legacy ) : array();
		$stored = get_post_meta( $base, '_lbite_option_groups', true );
		$stored = is_array( $stored ) ? $stored : array();
		$groups = array();

		if ( self::groups_available() ) {
			$groups = $stored;
		} else {
			foreach ( $stored as $g ) {
				$legacy = array_merge( $legacy, array_map( 'intval', (array) $g['option_ids'] ) );
			}
		}

		$in_groups = array();
		foreach ( $groups as $g ) {
			$in_groups = array_merge( $in_groups, array_map( 'intval', (array) $g['option_ids'] ) );
		}
		$extras = array_values( array_unique( array_diff( $legacy, $in_groups ) ) );
		if ( $extras ) {
			$groups[] = array(
				'id'         => 'extras',
				'label'      => __( 'Options', 'libre-bite' ),
				'type'       => 'multiple',
				'min'        => 0,
				'max'        => 0,
				'free'       => 0,
				'option_ids' => $extras,
			);
		}

		// Optionsdaten anhängen, nur veröffentlichte Optionen.
		foreach ( $groups as $index => $g ) {
			$options = array();
			$ids     = array();
			foreach ( (array) $g['option_ids'] as $option_id ) {
				$option = get_post( (int) $option_id );
				if ( ! $option || self::POST_TYPE !== $option->post_type || 'publish' !== $option->post_status ) {
					continue;
				}
				$ids[]     = (int) $option_id;
				$options[] = array(
					'id'    => (int) $option_id,
					'name'  => $option->post_title,
					'price' => (float) get_post_meta( $option_id, '_lbite_price', true ),
				);
			}

			if ( empty( $options ) ) {
				unset( $groups[ $index ] );
				continue;
			}

			$groups[ $index ]['option_ids'] = $ids;
			$groups[ $index ]['options']    = $options;
		}

		return array_values( $groups );
	}

	/**
	 * Gruppen für Oberflächen (Produktseite, Menü-Popup, Kasse) inklusive Texte.
	 *
	 * @param int $product_id Produkt- oder Varianten-ID.
	 * @return array[]
	 */
	public static function get_groups_for_client( $product_id ) {
		$groups = self::get_product_groups( $product_id );
		$rules  = class_exists( 'LBite_Option_Groups' );

		foreach ( $groups as $index => $g ) {
			$groups[ $index ]['hint']      = $rules ? LBite_Option_Groups::hint( $g ) : '';
			$groups[ $index ]['error_min'] = $rules ? LBite_Option_Groups::error_message( $g, 'min' ) : '';
			$groups[ $index ]['error_max'] = $rules ? LBite_Option_Groups::error_message( $g, 'max' ) : '';
		}

		return $groups;
	}

	/**
	 * Hat das Produkt überhaupt wählbare Optionen?
	 *
	 * @param int $product_id Produkt-ID.
	 * @return bool
	 */
	public static function product_has_options( $product_id ) {
		return ! empty( self::get_product_groups( $product_id ) );
	}

	/**
	 * Auswahl prüfen und Preise berechnen (einzige Rechenstelle).
	 *
	 * @param int   $product_id Produkt- oder Varianten-ID.
	 * @param int[] $selected   Gewählte Options-IDs.
	 * @return array{valid:bool,message:string,extra:float,charged:array<int,float>}
	 */
	public static function evaluate_selection( $product_id, array $selected ) {
		$groups = self::get_product_groups( $product_id );
		$prices = array();
		foreach ( $groups as $g ) {
			foreach ( $g['options'] as $option ) {
				$prices[ $option['id'] ] = $option['price'];
			}
		}

		if ( class_exists( 'LBite_Option_Groups' ) ) {
			$result = LBite_Option_Groups::evaluate( $groups, $prices, $selected );
		} else {
			// Gratis-Build: eine regellose Gruppe, Aufpreis = Summe.
			$selected = array_values( array_unique( array_map( 'intval', $selected ) ) );
			$result   = array(
				'valid'   => ! array_diff( $selected, array_keys( $prices ) ),
				'group'   => null,
				'reason'  => null,
				'extra'   => 0.0,
				'charged' => array(),
			);
			foreach ( $selected as $id ) {
				if ( isset( $prices[ $id ] ) ) {
					$result['charged'][ $id ] = (float) $prices[ $id ];
					$result['extra']         += (float) $prices[ $id ];
				}
			}
		}

		$message = '';
		if ( ! $result['valid'] ) {
			$message = __( 'Please check the selected options.', 'libre-bite' );
			foreach ( $groups as $g ) {
				if ( $g['id'] === $result['group'] && class_exists( 'LBite_Option_Groups' ) ) {
					$message = LBite_Option_Groups::error_message( $g, $result['reason'] );
				}
			}
		}

		return array(
			'valid'   => $result['valid'],
			'message' => $message,
			'extra'   => (float) $result['extra'],
			'charged' => $result['charged'],
		);
	}

	/**
	 * Auswahl aus dem Formular lesen: Checkboxen (lbite_options[]) und
	 * Einfachauswahl je Gruppe (lbite_options_g[gruppe]).
	 *
	 * @return int[]
	 */
	private static function get_posted_selection() {
		$ids = array();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Wird vom WooCommerce-Warenkorb nach eigener Prüfung aufgerufen.
		if ( isset( $_POST['lbite_options'] ) && is_array( $_POST['lbite_options'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- siehe oben.
			$ids = array_merge( $ids, array_map( 'intval', wp_unslash( $_POST['lbite_options'] ) ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- siehe oben.
		if ( isset( $_POST['lbite_options_g'] ) && is_array( $_POST['lbite_options_g'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- siehe oben.
			$ids = array_merge( $ids, array_map( 'intval', wp_unslash( $_POST['lbite_options_g'] ) ) );
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Regeln beim Hinzufügen zum Warenkorb durchsetzen.
	 *
	 * Greift auch für die Menü-Ansicht und den AJAX-Knopf im Shop: Produkte
	 * mit Pflichtgruppen lassen sich dort nicht ohne Auswahl hinzufügen,
	 * WooCommerce leitet dann auf die Produktseite weiter.
	 *
	 * @param bool $passed     Bisheriges Ergebnis.
	 * @param int  $product_id Produkt-ID.
	 * @param int  $quantity   Menge.
	 * @return bool
	 */
	public function validate_add_to_cart( $passed, $product_id, $quantity ) {
		if ( ! $passed ) {
			return $passed;
		}

		$selected = self::get_posted_selection();
		if ( empty( $selected ) && ! self::product_has_options( $product_id ) ) {
			return $passed;
		}

		$result = self::evaluate_selection( $product_id, $selected );
		if ( ! $result['valid'] ) {
			wc_add_notice( $result['message'], 'error' );
			return false;
		}

		return $passed;
	}

	/**
	 * Script für Gruppenregeln auf Produktseiten mit Optionen laden.
	 */
	public function enqueue_product_page_assets() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		$product_id = get_queried_object_id();
		if ( ! $product_id || ! self::product_has_options( $product_id ) ) {
			return;
		}

		wp_enqueue_script(
			'lbite-option-groups',
			LBITE_PLUGIN_URL . 'assets/js/option-groups.js',
			array(),
			LBITE_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Produkt-Optionen auf Produktseite anzeigen
	 */
	public function render_product_options() {
		global $product;

		if ( ! $product ) {
			return;
		}

		$groups = self::get_groups_for_client( $product->get_id() );
		if ( empty( $groups ) ) {
			return;
		}
		?>
		<div class="lbite-product-options lbite-option-groups" data-lbite-option-groups style="margin: 20px 0;">
			<?php foreach ( $groups as $g ) : ?>
				<?php $is_single = 'single' === $g['type']; ?>
				<fieldset class="lbite-option-group" data-group="<?php echo esc_attr( $g['id'] ); ?>" data-min="<?php echo esc_attr( $g['min'] ); ?>" data-max="<?php echo esc_attr( $is_single ? 1 : $g['max'] ); ?>" data-free="<?php echo esc_attr( $g['free'] ); ?>" data-error-min="<?php echo esc_attr( $g['error_min'] ); ?>" data-error-max="<?php echo esc_attr( $g['error_max'] ); ?>" style="margin: 0 0 16px; padding: 0; border: 0;">
					<legend style="font-weight: 600; margin-bottom: 6px;">
						<?php echo esc_html( $g['label'] ); ?>
						<?php if ( '' !== $g['hint'] ) : ?>
							<span class="lbite-option-group__hint" style="font-weight: 400; color: #646970;">(<?php echo esc_html( $g['hint'] ); ?>)</span>
						<?php endif; ?>
					</legend>
					<?php if ( $is_single && 0 === (int) $g['min'] ) : ?>
						<label style="display: block; margin-bottom: 8px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
							<input type="radio" name="lbite_options_g[<?php echo esc_attr( $g['id'] ); ?>]" value="" checked style="margin-right: 8px;">
							<?php esc_html_e( 'None', 'libre-bite' ); ?>
						</label>
					<?php endif; ?>
					<?php foreach ( $g['options'] as $option ) : ?>
						<label style="display: block; margin-bottom: 8px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
							<?php if ( $is_single ) : ?>
								<input type="radio" name="lbite_options_g[<?php echo esc_attr( $g['id'] ); ?>]" value="<?php echo esc_attr( $option['id'] ); ?>" data-price="<?php echo esc_attr( $option['price'] ); ?>" style="margin-right: 8px;">
							<?php else : ?>
								<input type="checkbox" name="lbite_options[]" value="<?php echo esc_attr( $option['id'] ); ?>" data-price="<?php echo esc_attr( $option['price'] ); ?>" style="margin-right: 8px;">
							<?php endif; ?>
							<strong><?php echo esc_html( $option['name'] ); ?></strong>
							<?php if ( $option['price'] > 0 ) : ?>
								<span style="color: #666;">(+<?php echo wp_kses_post( wc_price( $option['price'] ) ); ?>)</span>
							<?php endif; ?>
						</label>
					<?php endforeach; ?>
				</fieldset>
			<?php endforeach; ?>
			<p class="lbite-option-groups__error woocommerce-error" role="alert" hidden></p>
		</div>
		<?php
	}

	/**
	 * Optionen zu Warenkorb-Item hinzufügen
	 *
	 * @param array $cart_item_data Warenkorb-Item-Daten
	 * @param int   $product_id     Produkt-ID
	 * @return array
	 */
	public function add_cart_item_data( $cart_item_data, $product_id ) {
		$options = self::get_posted_selection();
		if ( ! empty( $options ) ) {
			// Nur IDs, die tatsächlich zu diesem Produkt gehören (validate_add_to_cart hat die Regeln geprüft).
			$result = self::evaluate_selection( $product_id, $options );
			if ( $result['valid'] ) {
				$cart_item_data['lbite_options'] = $options;
			}
		}

		return $cart_item_data;
	}

	/**
	 * Optionen im Warenkorb anzeigen
	 *
	 * @param array $item_data Item-Daten
	 * @param array $cart_item Warenkorb-Item
	 * @return array
	 */
	public function display_cart_item_data( $item_data, $cart_item ) {
		if ( ! empty( $cart_item['lbite_options'] ) ) {
			$option_labels = array();
			$charged       = self::evaluate_selection( $cart_item['product_id'], (array) $cart_item['lbite_options'] )['charged'];
			foreach ( $cart_item['lbite_options'] as $option_id ) {
				$option = get_post( $option_id );
				if ( $option ) {
					$price = isset( $charged[ (int) $option_id ] ) ? $charged[ (int) $option_id ] : 0;
					$label = $option->post_title;
					if ( $price > 0 ) {
						$label .= ' (+' . wp_strip_all_tags( wc_price( $price ) ) . ')';
					}
					$option_labels[] = $label;
				}
			}
			if ( ! empty( $option_labels ) ) {
				$item_data[] = array(
					'name'  => __( 'Options', 'libre-bite' ),
					'value' => implode( ', ', $option_labels ),
				);
			}
		}

		return $item_data;
	}

	/**
	 * Optionen-Preis zum Warenkorb-Item addieren
	 *
	 * @param array $cart_item Warenkorb-Item
	 * @return array
	 */
	public function add_cart_item_price( $cart_item ) {
		if ( ! empty( $cart_item['lbite_options'] ) ) {
			// Gruppenregeln (Gratis-Optionen) über die zentrale Auswertung.
			$extra_price = self::evaluate_selection( $cart_item['product_id'], (array) $cart_item['lbite_options'] )['extra'];

			if ( $extra_price > 0 ) {
				$cart_item['data']->set_price( $cart_item['data']->get_price() + $extra_price );
			}
		}

		return $cart_item;
	}

	/**
	 * Optionen zu Bestellung hinzufügen
	 *
	 * Jedes Add-on wird als separate WC_Order_Item_Fee erfasst;
	 * der Produktpreis in der Bestellung wird entsprechend reduziert.
	 * add_cart_item_price() hat den Add-on-Brutto bereits zum Produktpreis addiert.
	 *
	 * @param WC_Order_Item_Product $item          Order-Item
	 * @param string                $cart_item_key Cart-Item-Key
	 * @param array                 $values        Werte
	 * @param WC_Order              $order         Bestellung
	 */
	public function add_order_item_meta( $item, $cart_item_key, $values, $order ) {
		if ( empty( $values['lbite_options'] ) ) {
			return;
		}

		$product     = $item->get_product();
		$qty         = $item->get_quantity();
		$addon_names = array();
		$addon_items = array();

		$charged = self::evaluate_selection( $values['product_id'], (array) $values['lbite_options'] )['charged'];

		foreach ( $values['lbite_options'] as $option_id ) {
			$option    = get_post( $option_id );
			$opt_price = isset( $charged[ (int) $option_id ] ) ? (float) $charged[ (int) $option_id ] : 0.0;
			if ( ! $option ) {
				continue;
			}
			$addon_names[] = $option->post_title;
			if ( $opt_price > 0 ) {
				$addon_items[] = array(
					'name'  => $option->post_title,
					'gross' => $opt_price,
				);
			}
		}

		// Add-ons als separate Gebührenpositionen; Produktzwischensumme korrigieren.
		if ( ! empty( $addon_items ) && $product ) {
			$use_swiss_vat    = class_exists( 'LBite_Checkout' ) && lbite_feature_enabled( 'enable_swiss_vat' );
			$total_addon_excl = 0.0;
			foreach ( $addon_items as $addon ) {
				// Bei aktiver Schweizer MWST-Umschaltung Nettopreis zur Zielsteuerklasse berechnen,
				// da wc_get_price_excluding_tax() intern get_tax_class('unfiltered') aufruft.
				$addon_net = $use_swiss_vat
					? LBite_Checkout::gross_to_net_at_filtered_class( $product, $addon['gross'] )
					: wc_get_price_excluding_tax( $product, array( 'price' => $addon['gross'] ) );
				$total_addon_excl += $addon_net;

				$fee_item = new WC_Order_Item_Fee();
				$fee_item->set_name( $addon['name'] );
				$fee_item->set_amount( $addon_net * $qty );
				$fee_item->set_total( $addon_net * $qty );
				$fee_item->set_tax_class( $product->get_tax_class() );
				$fee_item->set_tax_status( $product->get_tax_status() );
				$order->add_item( $fee_item );
			}

			// Produktpreis um Add-on-Anteil reduzieren (Brutto wurde in add_cart_item_price addiert).
			$addon_excl_total = $total_addon_excl * $qty;
			$item->set_subtotal( max( 0.0, (float) $item->get_subtotal() - $addon_excl_total ) );
			$item->set_total( max( 0.0, (float) $item->get_total() - $addon_excl_total ) );
		}

		// Meta für Admin-Anzeige und E-Mails.
		if ( ! empty( $addon_names ) ) {
			$item->add_meta_data( __( 'Options', 'libre-bite' ), implode( ', ', $addon_names ), true );
		}
	}

	/**
	 * Admin-Spalten hinzufügen
	 *
	 * @param array $columns Spalten
	 * @return array
	 */
	public function add_admin_columns( $columns ) {
		$new_columns = array();
		$new_columns['cb']    = $columns['cb'];
		$new_columns['title'] = $columns['title'];
		$new_columns['price'] = __( 'Price', 'libre-bite' );
		$new_columns['date']  = $columns['date'];

		return $new_columns;
	}

	/**
	 * Admin-Spalten rendern
	 *
	 * @param string $column  Spaltenname
	 * @param int    $post_id Post-ID
	 */
	public function render_admin_columns( $column, $post_id ) {
		if ( 'price' === $column ) {
			$price = get_post_meta( $post_id, '_lbite_price', true );
			echo $price ? wp_kses_post( wc_price( $price ) ) : '—';
		}
	}
}
