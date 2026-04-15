<?php
/**
 * Standortverwaltung
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Locations-Modul
 */
class LBite_Locations {

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
	const POST_TYPE = 'lbite_location';

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
		$this->loader->add_action( 'save_post_' . self::POST_TYPE, $this, 'save_location_meta', 10, 2 );

		// Nonce-Feld immer ausgeben (unabhängig von Meta-Boxen).
		$this->loader->add_action( 'edit_form_after_title', $this, 'output_nonce_field' );

		// Admin Scripts für Media-Bibliothek.
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_scripts' );

		// Produkt-Meta-Box hinzufügen
		$this->loader->add_action( 'add_meta_boxes', $this, 'add_product_meta_box' );
		$this->loader->add_action( 'woocommerce_process_product_meta', $this, 'save_product_locations' );

		// Admin-Spalten
		$this->loader->add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', $this, 'add_admin_columns' );
		$this->loader->add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', $this, 'render_admin_columns', 10, 2 );
	}

	/**
	 * Admin-Scripts laden
	 *
	 * @param string $hook Aktuelle Admin-Seite.
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Nur auf Post-Edit-Seiten.
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		// Screen-Objekt verwenden (zuverlässiger als global $post_type).
		$screen = get_current_screen();
		if ( $screen && self::POST_TYPE === $screen->post_type ) {
			wp_enqueue_media();

			// WP Color Picker für Standort-Farbe.
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
			wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){ $(".lbite-color-picker").wpColorPicker(); });' );

			// Bild-Upload-Script laden (nach media-views, damit wp.media verfügbar ist).
			wp_enqueue_script(
				'lbite-admin-location-image',
				LBITE_PLUGIN_URL . 'assets/js/admin-location-image.js',
				array( 'jquery', 'media-views' ),
				LBITE_VERSION,
				true
			);
			wp_localize_script(
				'lbite-admin-location-image',
				'lbiteLocationImage',
				array(
					'title'      => __( 'Choose Location Image', 'libre-bite' ),
					'buttonText' => __( 'Use Image', 'libre-bite' ),
					'noImageText' => __( 'No image selected', 'libre-bite' ),
					'errorText'  => __( 'Error: Media library not loaded', 'libre-bite' ),
				)
			);
		}
	}

	/**
	 * Nonce-Feld ausgeben (unabhängig von Meta-Boxen)
	 *
	 * @param WP_Post $post Post-Objekt.
	 */
	public function output_nonce_field( $post ) {
		if ( self::POST_TYPE !== $post->post_type ) {
			return;
		}
		wp_nonce_field( 'lbite_save_location', 'lbite_location_nonce' );
	}

	/**
	 * Custom Post Type registrieren
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Locations', 'libre-bite' ),
			'singular_name'      => __( 'Location', 'libre-bite' ),
			'menu_name'          => __( 'Locations', 'libre-bite' ),
			'add_new'            => __( 'New Location', 'libre-bite' ),
			'add_new_item'       => __( 'Add New Location', 'libre-bite' ),
			'edit_item'          => __( 'Edit Location', 'libre-bite' ),
			'new_item'           => __( 'New Location', 'libre-bite' ),
			'view_item'          => __( 'View Location', 'libre-bite' ),
			'search_items'       => __( 'Search Locations', 'libre-bite' ),
			'not_found'          => __( 'No locations found', 'libre-bite' ),
			'not_found_in_trash' => __( 'No locations in trash', 'libre-bite' ),
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
			'supports'            => array( 'title' ),
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Meta-Boxen hinzufügen
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'lbite_location_image',
			__( 'Location Image', 'libre-bite' ),
			array( $this, 'render_image_meta_box' ),
			self::POST_TYPE,
			'side',
			'default'
		);

		add_meta_box(
			'lbite_location_color',
			__( 'Color', 'libre-bite' ),
			array( $this, 'render_color_meta_box' ),
			self::POST_TYPE,
			'side',
			'default'
		);

		add_meta_box(
			'lbite_location_address',
			__( 'Address', 'libre-bite' ),
			array( $this, 'render_address_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'lbite_location_hours',
			__( 'Opening Hours', 'libre-bite' ),
			array( $this, 'render_hours_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Standort-Bild Meta-Box rendern
	 *
	 * @param WP_Post $post Post-Objekt
	 */
	public function render_image_meta_box( $post ) {
		$image_id = get_post_meta( $post->ID, '_lbite_location_image', true );
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
		?>
		<div class="lbite-location-image-upload">
			<div class="lbite-image-preview" style="margin-bottom: 10px;">
				<?php if ( $image_url ) : ?>
					<img src="<?php echo esc_url( $image_url ); ?>" style="max-width: 100%; height: auto; display: block;">
				<?php else : ?>
					<p style="text-align: center; padding: 20px; background: #f5f5f5; border: 2px dashed #ddd;">
						<?php esc_html_e( 'No image selected', 'libre-bite' ); ?>
					</p>
				<?php endif; ?>
			</div>
			<input type="hidden" id="lbite_location_image" name="lbite_location_image" value="<?php echo esc_attr( $image_id ); ?>">
			<button type="button" class="button button-secondary lbite-upload-image-button" style="width: 100%; margin-bottom: 5px;">
				<?php esc_html_e( 'Select Image', 'libre-bite' ); ?>
			</button>
			<button type="button" class="button button-secondary lbite-remove-image-button" style="width: 100%; <?php echo $image_id ? '' : 'display:none;'; ?>">
				<?php esc_html_e( 'Remove Image', 'libre-bite' ); ?>
			</button>
			<p class="description" style="margin-top: 10px;">
				<?php esc_html_e( 'Optional: Displayed as tile image in location selection.', 'libre-bite' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Farb-Meta-Box rendern
	 *
	 * @param WP_Post $post Post-Objekt
	 */
	public function render_color_meta_box( $post ) {
		$color = get_post_meta( $post->ID, '_lbite_location_color', true );
		?>
		<p>
			<label for="lbite_location_color"><?php esc_html_e( 'Displayed as accent color in POS and order overview.', 'libre-bite' ); ?></label>
		</p>
		<input type="text"
			id="lbite_location_color"
			name="lbite_location_color"
			class="lbite-color-picker"
			value="<?php echo esc_attr( $color ); ?>"
			data-default-color="">
		<?php
	}

	/**
	 * Adress-Meta-Box rendern
	 *
	 * @param WP_Post $post Post-Objekt
	 */
	public function render_address_meta_box( $post ) {
		$street      = get_post_meta( $post->ID, '_lbite_street', true );
		$zip         = get_post_meta( $post->ID, '_lbite_zip', true );
		$city        = get_post_meta( $post->ID, '_lbite_city', true );
		$country     = get_post_meta( $post->ID, '_lbite_country', true );
		$maps_url    = get_post_meta( $post->ID, '_lbite_maps_url', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="lbite_street"><?php esc_html_e( 'Street & Number', 'libre-bite' ); ?></label></th>
				<td>
					<input type="text" id="lbite_street" name="lbite_street" value="<?php echo esc_attr( $street ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="lbite_zip"><?php esc_html_e( 'ZIP Code', 'libre-bite' ); ?></label></th>
				<td>
					<input type="text" id="lbite_zip" name="lbite_zip" value="<?php echo esc_attr( $zip ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="lbite_city"><?php esc_html_e( 'City', 'libre-bite' ); ?></label></th>
				<td>
					<input type="text" id="lbite_city" name="lbite_city" value="<?php echo esc_attr( $city ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="lbite_country"><?php esc_html_e( 'Country', 'libre-bite' ); ?></label></th>
				<td>
					<input type="text" id="lbite_country" name="lbite_country" value="<?php echo esc_attr( $country ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="lbite_maps_url"><?php esc_html_e( 'Google Maps Link', 'libre-bite' ); ?></label></th>
				<td>
					<input type="url" id="lbite_maps_url" name="lbite_maps_url" value="<?php echo esc_attr( $maps_url ); ?>" class="large-text" placeholder="https://maps.google.com/...">
					<p class="description"><?php esc_html_e( 'Optional: Link to Google Maps for this location. The address will be linked on order confirmation and in emails.', 'libre-bite' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Öffnungszeiten-Meta-Box rendern
	 *
	 * @param WP_Post $post Post-Objekt
	 */
	public function render_hours_meta_box( $post ) {
		$opening_hours = get_post_meta( $post->ID, '_lbite_opening_hours', true );
		if ( ! is_array( $opening_hours ) ) {
			$opening_hours = array();
		}

		$days = array(
			'monday'    => __( 'Monday', 'libre-bite' ),
			'tuesday'   => __( 'Tuesday', 'libre-bite' ),
			'wednesday' => __( 'Wednesday', 'libre-bite' ),
			'thursday'  => __( 'Thursday', 'libre-bite' ),
			'friday'    => __( 'Friday', 'libre-bite' ),
			'saturday'  => __( 'Saturday', 'libre-bite' ),
			'sunday'    => __( 'Sunday', 'libre-bite' ),
		);
		?>
		<table class="form-table">
			<?php foreach ( $days as $day_key => $day_label ) : ?>
				<?php
				$is_closed = isset( $opening_hours[ $day_key ]['closed'] ) ? $opening_hours[ $day_key ]['closed'] : false;
				$open      = isset( $opening_hours[ $day_key ]['open'] ) ? $opening_hours[ $day_key ]['open'] : '09:00';
				$close     = isset( $opening_hours[ $day_key ]['close'] ) ? $opening_hours[ $day_key ]['close'] : '18:00';
				?>
				<tr>
					<th><?php echo esc_html( $day_label ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="lbite_hours[<?php echo esc_attr( $day_key ); ?>][closed]" value="1" <?php checked( $is_closed, true ); ?>>
							<?php esc_html_e( 'Closed', 'libre-bite' ); ?>
						</label>
						<br>
						<label>
							<?php esc_html_e( 'From', 'libre-bite' ); ?>
							<input type="time" name="lbite_hours[<?php echo esc_attr( $day_key ); ?>][open]" value="<?php echo esc_attr( $open ); ?>">
						</label>
						<label>
							<?php esc_html_e( 'To', 'libre-bite' ); ?>
							<input type="time" name="lbite_hours[<?php echo esc_attr( $day_key ); ?>][close]" value="<?php echo esc_attr( $close ); ?>">
						</label>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}

	/**
	 * Standort-Meta speichern
	 *
	 * @param int     $post_id Post-ID
	 * @param WP_Post $post    Post-Objekt
	 */
	public function save_location_meta( $post_id, $post ) {
		// Nonce prüfen.
		if ( ! isset( $_POST['lbite_location_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lbite_location_nonce'] ) ), 'lbite_save_location' ) ) {
			return;
		}

		// Autosave prüfen.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Berechtigungen prüfen.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Bild speichern.
		if ( isset( $_POST['lbite_location_image'] ) ) {
			$image_id = intval( wp_unslash( $_POST['lbite_location_image'] ) );
			if ( $image_id ) {
				update_post_meta( $post_id, '_lbite_location_image', $image_id );
			} else {
				delete_post_meta( $post_id, '_lbite_location_image' );
			}
		}

		// Farbe speichern.
		if ( isset( $_POST['lbite_location_color'] ) ) {
			$color = sanitize_hex_color( wp_unslash( $_POST['lbite_location_color'] ) );
			if ( $color ) {
				update_post_meta( $post_id, '_lbite_location_color', $color );
			} else {
				delete_post_meta( $post_id, '_lbite_location_color' );
			}
		}

		// Adresse speichern.
		if ( isset( $_POST['lbite_street'] ) ) {
			update_post_meta( $post_id, '_lbite_street', sanitize_text_field( wp_unslash( $_POST['lbite_street'] ) ) );
		}
		if ( isset( $_POST['lbite_zip'] ) ) {
			update_post_meta( $post_id, '_lbite_zip', sanitize_text_field( wp_unslash( $_POST['lbite_zip'] ) ) );
		}
		if ( isset( $_POST['lbite_city'] ) ) {
			update_post_meta( $post_id, '_lbite_city', sanitize_text_field( wp_unslash( $_POST['lbite_city'] ) ) );
		}
		if ( isset( $_POST['lbite_country'] ) ) {
			update_post_meta( $post_id, '_lbite_country', sanitize_text_field( wp_unslash( $_POST['lbite_country'] ) ) );
		}
		if ( isset( $_POST['lbite_maps_url'] ) ) {
			update_post_meta( $post_id, '_lbite_maps_url', esc_url_raw( wp_unslash( $_POST['lbite_maps_url'] ) ) );
		}

		// Öffnungszeiten speichern.
		if ( isset( $_POST['lbite_hours'] ) && is_array( $_POST['lbite_hours'] ) ) {
			$hours = array();
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in loop below.
			foreach ( wp_unslash( $_POST['lbite_hours'] ) as $day => $times ) {
				$hours[ sanitize_key( $day ) ] = array(
					'closed' => isset( $times['closed'] ),
					'open'   => isset( $times['open'] ) ? sanitize_text_field( $times['open'] ) : '',
					'close'  => isset( $times['close'] ) ? sanitize_text_field( $times['close'] ) : '',
				);
			}
			update_post_meta( $post_id, '_lbite_opening_hours', $hours );
		}

		// Farben-Cache invalidieren (Standort-Farbe kann sich geändert haben).
		delete_transient( 'lbite_location_colors' );
	}

	/**
	 * Produkt-Meta-Box hinzufügen
	 */
	public function add_product_meta_box() {
		add_meta_box(
			'lbite_product_locations',
			__( 'Locations', 'libre-bite' ),
			array( $this, 'render_product_locations_meta_box' ),
			'product',
			'side',
			'default'
		);
	}

	/**
	 * Produkt-Standorte Meta-Box rendern
	 *
	 * @param WP_Post $post Post-Objekt
	 */
	public function render_product_locations_meta_box( $post ) {
		wp_nonce_field( 'lbite_save_product_locations', 'lbite_product_locations_nonce' );

		$selected_locations = get_post_meta( $post->ID, '_lbite_locations', true );
		if ( ! is_array( $selected_locations ) ) {
			$selected_locations = array();
		}

		$locations = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => 100, // Begrenzt für Performance.
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( empty( $locations ) ) {
			echo '<p>' . esc_html__( 'No locations available yet.', 'libre-bite' ) . '</p>';
			return;
		}

		echo '<div style="max-height: 200px; overflow-y: auto;">';
		foreach ( $locations as $location ) {
			$checked = in_array( $location->ID, $selected_locations, true );
			?>
			<label style="display: block; margin-bottom: 5px;">
				<input type="checkbox" name="lbite_product_locations[]" value="<?php echo esc_attr( $location->ID ); ?>" <?php checked( $checked ); ?>>
				<?php echo esc_html( $location->post_title ); ?>
			</label>
			<?php
		}
		echo '</div>';
	}

	/**
	 * Produkt-Standorte speichern
	 *
	 * @param int $post_id Post-ID
	 */
	public function save_product_locations( $post_id ) {
		// Nonce prüfen.
		if ( ! isset( $_POST['lbite_product_locations_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lbite_product_locations_nonce'] ) ), 'lbite_save_product_locations' ) ) {
			return;
		}

		// Berechtigungen prüfen.
		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		// Standorte speichern.
		$locations = isset( $_POST['lbite_product_locations'] ) && is_array( $_POST['lbite_product_locations'] )
			? array_map( 'intval', wp_unslash( $_POST['lbite_product_locations'] ) )
			: array();

		update_post_meta( $post_id, '_lbite_locations', $locations );
	}

	/**
	 * Admin-Spalten hinzufügen
	 *
	 * @param array $columns Spalten
	 * @return array
	 */
	public function add_admin_columns( $columns ) {
		$new_columns = array();
		$new_columns['cb']      = $columns['cb'];
		$new_columns['title']   = $columns['title'];
		$new_columns['address'] = __( 'Address', 'libre-bite' );
		$new_columns['date']    = $columns['date'];

		return $new_columns;
	}

	/**
	 * Admin-Spalten rendern
	 *
	 * @param string $column  Spaltenname
	 * @param int    $post_id Post-ID
	 */
	public function render_admin_columns( $column, $post_id ) {
		if ( 'address' === $column ) {
			$street = get_post_meta( $post_id, '_lbite_street', true );
			$zip    = get_post_meta( $post_id, '_lbite_zip', true );
			$city   = get_post_meta( $post_id, '_lbite_city', true );

			if ( $street || $zip || $city ) {
				echo esc_html( $street . ', ' . $zip . ' ' . $city );
			} else {
				echo '—';
			}
		}
	}

	/**
	 * Alle Standorte abrufen
	 *
	 * @return array
	 */
	public static function get_all_locations() {
		return get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => 100, // Begrenzt für Performance.
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
			)
		);
	}

	/**
	 * Farbe eines Standorts abrufen
	 *
	 * @param int $location_id Standort-ID
	 * @return string Hex-Farbe oder leerer String
	 */
	public static function get_location_color( $location_id ) {
		return (string) get_post_meta( $location_id, '_lbite_location_color', true );
	}

	/**
	 * Farben aller Standorte abrufen (gecacht)
	 *
	 * Ergebnis wird 1 Stunde gecacht (Transient lbite_location_colors).
	 * Invalidierung in save_location_meta() bei Standort-Speicherung.
	 *
	 * @return array Assoziatives Array: Standort-ID => Hex-Farbe.
	 */
	public static function get_all_location_colors() {
		$cached = get_transient( 'lbite_location_colors' );
		if ( false !== $cached ) {
			return $cached;
		}

		$colors    = array();
		$locations = self::get_all_locations();

		foreach ( $locations as $loc ) {
			$color = get_post_meta( $loc->ID, '_lbite_location_color', true );
			if ( $color ) {
				$colors[ $loc->ID ] = $color;
			}
		}

		set_transient( 'lbite_location_colors', $colors, HOUR_IN_SECONDS );

		return $colors;
	}

	/**
	 * Öffnungszeiten für einen Standort abrufen
	 *
	 * @param int $location_id Standort-ID
	 * @return array
	 */
	public static function get_opening_hours( $location_id ) {
		return get_post_meta( $location_id, '_lbite_opening_hours', true );
	}

	/**
	 * Formatierte Adresse für einen Standort abrufen
	 *
	 * @param int $location_id Standort-ID
	 * @return string
	 */
	public static function get_formatted_address( $location_id ) {
		$street  = get_post_meta( $location_id, '_lbite_street', true );
		$zip     = get_post_meta( $location_id, '_lbite_zip', true );
		$city    = get_post_meta( $location_id, '_lbite_city', true );
		$country = get_post_meta( $location_id, '_lbite_country', true );

		$parts = array();

		if ( $street ) {
			$parts[] = $street;
		}

		$city_parts = array();
		if ( $zip ) {
			$city_parts[] = $zip;
		}
		if ( $city ) {
			$city_parts[] = $city;
		}
		if ( ! empty( $city_parts ) ) {
			$parts[] = implode( ' ', $city_parts );
		}

		if ( $country ) {
			$parts[] = $country;
		}

		return implode( ', ', $parts );
	}

	/**
	 * Google Maps URL für einen Standort abrufen
	 *
	 * @param int $location_id Standort-ID
	 * @return string
	 */
	public static function get_maps_url( $location_id ) {
		return get_post_meta( $location_id, '_lbite_maps_url', true );
	}

	/**
	 * Zeitstring (z.B. «9:00» oder «09:00») normalisiert auf «HH:MM» zurückgeben.
	 *
	 * @param string $time Zeitstring.
	 * @return string Normalisierter Zeitstring im Format HH:MM.
	 */
	private static function normalize_time( $time ) {
		// strtotime mit festem Datum, damit Sommer-/Winterzeit keine Rolle spielt.
		return date( 'H:i', strtotime( '2000-01-01 ' . $time ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
	}

	/**
	 * Status eines Standorts berechnen
	 *
	 * @param array $opening_hours Öffnungszeiten.
	 * @return array|null Status-Daten (type, text) oder null.
	 */
	public static function get_location_status( $opening_hours ) {
		if ( ! $opening_hours || ! is_array( $opening_hours ) ) {
			return null;
		}

		// DateTime mit WP-Timezone: format('l') gibt immer englische Tagnamen zurück.
		$lbite_now    = new DateTime( 'now', wp_timezone() );
		$current_hhmm = $lbite_now->format( 'H:i' );
		$current_day  = strtolower( $lbite_now->format( 'l' ) );

		// Prüfen ob heute geöffnet.
		if ( isset( $opening_hours[ $current_day ] ) && ! $opening_hours[ $current_day ]['closed'] ) {
			$open_hhmm  = self::normalize_time( $opening_hours[ $current_day ]['open'] );
			$close_hhmm = self::normalize_time( $opening_hours[ $current_day ]['close'] );

			// Ist aktuell geöffnet?
			if ( $current_hhmm >= $open_hhmm && $current_hhmm < $close_hhmm ) {
				// Schliesst in weniger als 60 Minuten?
				$close_ts   = strtotime( wp_date( 'Y-m-d' ) . ' ' . $close_hhmm ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				$current_ts = strtotime( wp_date( 'Y-m-d' ) . ' ' . $current_hhmm ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				$remaining  = $close_ts - $current_ts;

				if ( $remaining <= 3600 && $remaining > 0 ) {
					return array(
						'type' => 'closing-soon',
						/* translators: %s: closing time */
						'text' => sprintf( __( 'Closes at %s', 'libre-bite' ), $close_hhmm ),
					);
				}

				return array(
					'type' => 'open',
					'text' => __( 'Open', 'libre-bite' ),
				);
			}

			// Öffnet bald (30 Min vor Öffnung)?
			if ( $current_hhmm < $open_hhmm ) {
				$open_ts    = strtotime( wp_date( 'Y-m-d' ) . ' ' . $open_hhmm ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				$current_ts = strtotime( wp_date( 'Y-m-d' ) . ' ' . $current_hhmm ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				if ( ( $open_ts - $current_ts ) <= 1800 ) {
					return array(
						'type' => 'opening-soon',
						/* translators: %s: opening time */
						'text' => sprintf( __( 'Opens at %s', 'libre-bite' ), $open_hhmm ),
					);
				}
			}
		}

		// Geschlossen - nächste Öffnungszeit finden.
		$next_opening = self::find_next_opening( $opening_hours );

		if ( $next_opening ) {
			return array(
				'type' => 'closed',
				'text' => $next_opening,
			);
		}

		return array(
			'type' => 'closed',
			'text' => __( 'Closed', 'libre-bite' ),
		);
	}

	/**
	 * Nächste Öffnungszeit finden
	 *
	 * @param array $opening_hours Öffnungszeiten.
	 * @return string|null Formatierter Text oder null.
	 */
	public static function find_next_opening( $opening_hours ) {
		$day_names    = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
		$day_names_de = array(
			'monday'    => 'Mo',
			'tuesday'   => 'Di',
			'wednesday' => 'Mi',
			'thursday'  => 'Do',
			'friday'    => 'Fr',
			'saturday'  => 'Sa',
			'sunday'    => 'So',
		);

		$lbite_now         = new DateTime( 'now', wp_timezone() );
		$current_hhmm      = $lbite_now->format( 'H:i' );
		$current_day_index = ( (int) $lbite_now->format( 'N' ) - 1 ); // 0 = Montag.

		// Bis zu 7 Tage in die Zukunft suchen.
		for ( $i = 0; $i <= 7; $i++ ) {
			$check_day_index = ( $current_day_index + $i ) % 7;
			$day_name        = $day_names[ $check_day_index ];

			if ( isset( $opening_hours[ $day_name ] ) && ! $opening_hours[ $day_name ]['closed'] ) {
				$open_hhmm = self::normalize_time( $opening_hours[ $day_name ]['open'] );

				if ( 0 === $i ) {
					// Heute: nur wenn Öffnung noch bevorsteht (robuster HH:MM-Vergleich).
					if ( $current_hhmm < $open_hhmm ) {
						/* translators: %s: opening time */
						return sprintf( __( 'Opens today %s', 'libre-bite' ), $open_hhmm );
					}
				} elseif ( 1 === $i ) {
					/* translators: %s: opening time */
					return sprintf( __( 'Opens tomorrow %s', 'libre-bite' ), $open_hhmm );
				} else {
					$day_abbr = $day_names_de[ $day_name ];
					/* translators: %1$s: day abbreviation, %2$s: opening time */
					return sprintf( __( 'Opens %1$s %2$s', 'libre-bite' ), $day_abbr, $open_hhmm );
				}
			}
		}

		return null;
	}
}
