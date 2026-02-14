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
			'name'               => __( 'Produkt-Optionen', 'libre-bite' ),
			'singular_name'      => __( 'Produkt-Option', 'libre-bite' ),
			'menu_name'          => __( 'Produkt-Optionen', 'libre-bite' ),
			'add_new'            => __( 'Neue Option', 'libre-bite' ),
			'add_new_item'       => __( 'Neue Option hinzufügen', 'libre-bite' ),
			'edit_item'          => __( 'Option bearbeiten', 'libre-bite' ),
			'new_item'           => __( 'Neue Option', 'libre-bite' ),
			'view_item'          => __( 'Option ansehen', 'libre-bite' ),
			'search_items'       => __( 'Optionen suchen', 'libre-bite' ),
			'not_found'          => __( 'Keine Optionen gefunden', 'libre-bite' ),
			'not_found_in_trash' => __( 'Keine Optionen im Papierkorb', 'libre-bite' ),
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
			'map_meta_cap'        => true,
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
			__( 'Preis', 'libre-bite' ),
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
			<label for="lbite_option_price"><?php esc_html_e( 'Preis', 'libre-bite' ); ?> (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</label><br>
			<input type="number" step="0.01" min="0" id="lbite_option_price" name="lbite_option_price" value="<?php echo esc_attr( $price ); ?>" style="width: 100%;">
		</p>
		<p class="description">
			<?php esc_html_e( 'Dieser Preis wird zum Produktpreis addiert, wenn die Option ausgewählt wird.', 'libre-bite' ); ?>
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
		if ( ! current_user_can( 'manage_lbite_options', $post_id ) ) {
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
			__( 'Produkt-Optionen', 'libre-bite' ),
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
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( empty( $options ) ) {
			echo '<p>' . esc_html__( 'Noch keine Optionen vorhanden.', 'libre-bite' ) . ' ';
			echo '<a href="' . esc_url( admin_url( 'post-new.php?post_type=' . self::POST_TYPE ) ) . '">' . esc_html__( 'Jetzt erstellen', 'libre-bite' ) . '</a></p>';
			return;
		}

		echo '<div style="max-height: 300px; overflow-y: auto;">';
		foreach ( $options as $option ) {
			$checked = in_array( $option->ID, $selected_options, true );
			$price   = get_post_meta( $option->ID, '_lbite_price', true );
			?>
			<label style="display: block; margin-bottom: 8px; padding: 5px; background: #f9f9f9; border-radius: 3px;">
				<input type="checkbox" name="lbite_product_options[]" value="<?php echo esc_attr( $option->ID ); ?>" <?php checked( $checked ); ?>>
				<strong><?php echo esc_html( $option->post_title ); ?></strong>
				<?php if ( $price ) : ?>
					<span style="color: #666;">(+<?php echo wp_kses_post( wc_price( $price ) ); ?>)</span>
				<?php endif; ?>
				<?php if ( $option->post_content ) : ?>
					<br><span style="font-size: 12px; color: #666;"><?php echo esc_html( wp_trim_words( $option->post_content, 15 ) ); ?></span>
				<?php endif; ?>
			</label>
			<?php
		}
		echo '</div>';
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
	}

	/**
	 * Produkt-Optionen auf Produktseite anzeigen
	 */
	public function render_product_options() {
		global $product;

		if ( ! $product ) {
			return;
		}

		$product_options = get_post_meta( $product->get_id(), '_lbite_product_options', true );
		if ( empty( $product_options ) || ! is_array( $product_options ) ) {
			return;
		}

		$options = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => -1,
				'post__in'       => $product_options,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( empty( $options ) ) {
			return;
		}

		?>
		<div class="lbite-product-options" style="margin: 20px 0;">
			<h4><?php esc_html_e( 'Optionen', 'libre-bite' ); ?></h4>
			<?php foreach ( $options as $option ) : ?>
				<?php
				$price       = get_post_meta( $option->ID, '_lbite_price', true );
				$description = $option->post_content;
				?>
				<label style="display: block; margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
					<input type="checkbox" name="lbite_options[]" value="<?php echo esc_attr( $option->ID ); ?>" style="margin-right: 8px;">
					<strong><?php echo esc_html( $option->post_title ); ?></strong>
					<?php if ( $price ) : ?>
						<span style="color: #666;">(+<?php echo wp_kses_post( wc_price( $price ) ); ?>)</span>
					<?php endif; ?>
					<?php if ( $description ) : ?>
						<br><span style="font-size: 13px; color: #666;"><?php echo esc_html( $description ); ?></span>
					<?php endif; ?>
				</label>
			<?php endforeach; ?>
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
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Hook wird von WooCommerce nach eigener Nonce-Pruefung aufgerufen.
		if ( isset( $_POST['lbite_options'] ) && is_array( $_POST['lbite_options'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Hook wird von WooCommerce nach eigener Nonce-Pruefung aufgerufen.
			$options = array_map( 'intval', wp_unslash( $_POST['lbite_options'] ) );
			if ( ! empty( $options ) ) {
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
			foreach ( $cart_item['lbite_options'] as $option_id ) {
				$option = get_post( $option_id );
				if ( $option ) {
					$price = get_post_meta( $option_id, '_lbite_price', true );
					$value = $option->post_title;
					if ( $price ) {
						$value .= ' (+' . wc_price( $price ) . ')';
					}

					$item_data[] = array(
						'name'  => __( 'Option', 'libre-bite' ),
						'value' => $value,
					);
				}
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
			$extra_price = 0;
			foreach ( $cart_item['lbite_options'] as $option_id ) {
				$price = get_post_meta( $option_id, '_lbite_price', true );
				if ( $price ) {
					$extra_price += floatval( $price );
				}
			}

			if ( $extra_price > 0 ) {
				$cart_item['data']->set_price( $cart_item['data']->get_price() + $extra_price );
			}
		}

		return $cart_item;
	}

	/**
	 * Optionen zu Bestellung hinzufügen
	 *
	 * @param WC_Order_Item_Product $item          Order-Item
	 * @param string                $cart_item_key Cart-Item-Key
	 * @param array                 $values        Werte
	 * @param WC_Order              $order         Bestellung
	 */
	public function add_order_item_meta( $item, $cart_item_key, $values, $order ) {
		if ( ! empty( $values['lbite_options'] ) ) {
			$option_names = array();
			foreach ( $values['lbite_options'] as $option_id ) {
				$option = get_post( $option_id );
				if ( $option ) {
					$option_names[] = $option->post_title;
				}
			}

			if ( ! empty( $option_names ) ) {
				$item->add_meta_data( __( 'Optionen', 'libre-bite' ), implode( ', ', $option_names ), true );
			}
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
		$new_columns['price'] = __( 'Preis', 'libre-bite' );
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
