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
class LB_Locations {

	/**
	 * Loader-Instanz
	 *
	 * @var LB_Loader
	 */
	private $loader;

	/**
	 * Post-Type Name
	 *
	 * @var string
	 */
	const POST_TYPE = 'lb_location';

	/**
	 * Konstruktor
	 *
	 * @param LB_Loader $loader Loader-Instanz
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
		wp_nonce_field( 'lb_save_location', 'lb_location_nonce' );
	}

	/**
	 * Custom Post Type registrieren
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Standorte', 'libre-bite' ),
			'singular_name'      => __( 'Standort', 'libre-bite' ),
			'menu_name'          => __( 'Standorte', 'libre-bite' ),
			'add_new'            => __( 'Neuer Standort', 'libre-bite' ),
			'add_new_item'       => __( 'Neuen Standort hinzufügen', 'libre-bite' ),
			'edit_item'          => __( 'Standort bearbeiten', 'libre-bite' ),
			'new_item'           => __( 'Neuer Standort', 'libre-bite' ),
			'view_item'          => __( 'Standort ansehen', 'libre-bite' ),
			'search_items'       => __( 'Standorte suchen', 'libre-bite' ),
			'not_found'          => __( 'Keine Standorte gefunden', 'libre-bite' ),
			'not_found_in_trash' => __( 'Keine Standorte im Papierkorb', 'libre-bite' ),
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
			'lb_location_image',
			__( 'Standort-Bild', 'libre-bite' ),
			array( $this, 'render_image_meta_box' ),
			self::POST_TYPE,
			'side',
			'default'
		);

		add_meta_box(
			'lb_location_address',
			__( 'Adresse', 'libre-bite' ),
			array( $this, 'render_address_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'lb_location_hours',
			__( 'Öffnungszeiten', 'libre-bite' ),
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
		$image_id = get_post_meta( $post->ID, '_lb_location_image', true );
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
		?>
		<div class="lb-location-image-upload">
			<div class="lb-image-preview" style="margin-bottom: 10px;">
				<?php if ( $image_url ) : ?>
					<img src="<?php echo esc_url( $image_url ); ?>" style="max-width: 100%; height: auto; display: block;">
				<?php else : ?>
					<p style="text-align: center; padding: 20px; background: #f5f5f5; border: 2px dashed #ddd;">
						<?php esc_html_e( 'Kein Bild ausgewählt', 'libre-bite' ); ?>
					</p>
				<?php endif; ?>
			</div>
			<input type="hidden" id="lb_location_image" name="lb_location_image" value="<?php echo esc_attr( $image_id ); ?>">
			<button type="button" class="button button-secondary lb-upload-image-button" style="width: 100%; margin-bottom: 5px;">
				<?php esc_html_e( 'Bild auswählen', 'libre-bite' ); ?>
			</button>
			<button type="button" class="button button-secondary lb-remove-image-button" style="width: 100%; <?php echo $image_id ? '' : 'display:none;'; ?>">
				<?php esc_html_e( 'Bild entfernen', 'libre-bite' ); ?>
			</button>
			<p class="description" style="margin-top: 10px;">
				<?php esc_html_e( 'Optional: Wird in der Standort-Auswahl als Kachel-Bild angezeigt.', 'libre-bite' ); ?>
			</p>
		</div>
		<script>
		jQuery(document).ready(function($) {
			var $container = $('.lb-location-image-upload');
			var $input = $container.find('input[name="lb_location_image"]');
			var $preview = $container.find('.lb-image-preview');
			var $uploadBtn = $container.find('.lb-upload-image-button');
			var $removeBtn = $container.find('.lb-remove-image-button');
			var imageFrame;

			// Prüfen ob wp.media verfügbar ist.
			if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
				$uploadBtn.prop('disabled', true).text('<?php echo esc_js( __( 'Fehler: Media-Library nicht geladen', 'libre-bite' ) ); ?>');
				return;
			}

			$uploadBtn.on('click', function(e) {
				e.preventDefault();

				if (imageFrame) {
					imageFrame.open();
					return;
				}

				imageFrame = wp.media({
					title: '<?php echo esc_js( __( 'Standort-Bild wählen', 'libre-bite' ) ); ?>',
					button: {
						text: '<?php echo esc_js( __( 'Bild verwenden', 'libre-bite' ) ); ?>'
					},
					multiple: false
				});

				imageFrame.on('select', function() {
					var attachment = imageFrame.state().get('selection').first().toJSON();

					// Wert im Hidden-Feld setzen.
					$input.val(attachment.id);

					// Vorschau aktualisieren.
					$preview.html('<img src="' + attachment.url + '" style="max-width: 100%; height: auto; display: block;">');
					$removeBtn.show();
				});

				imageFrame.open();
			});

			$removeBtn.on('click', function(e) {
				e.preventDefault();
				$input.val('');
				$preview.html('<p style="text-align: center; padding: 20px; background: #f5f5f5; border: 2px dashed #ddd;"><?php echo esc_js( __( 'Kein Bild ausgewählt', 'libre-bite' ) ); ?></p>');
				$(this).hide();
			});
		});
		</script>
		<?php
	}

	/**
	 * Adress-Meta-Box rendern
	 *
	 * @param WP_Post $post Post-Objekt
	 */
	public function render_address_meta_box( $post ) {
		$street      = get_post_meta( $post->ID, '_lb_street', true );
		$zip         = get_post_meta( $post->ID, '_lb_zip', true );
		$city        = get_post_meta( $post->ID, '_lb_city', true );
		$country     = get_post_meta( $post->ID, '_lb_country', true );
		$maps_url    = get_post_meta( $post->ID, '_lb_maps_url', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="lb_street"><?php esc_html_e( 'Straße & Nr.', 'libre-bite' ); ?></label></th>
				<td>
					<input type="text" id="lb_street" name="lb_street" value="<?php echo esc_attr( $street ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="lb_zip"><?php esc_html_e( 'PLZ', 'libre-bite' ); ?></label></th>
				<td>
					<input type="text" id="lb_zip" name="lb_zip" value="<?php echo esc_attr( $zip ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="lb_city"><?php esc_html_e( 'Ort', 'libre-bite' ); ?></label></th>
				<td>
					<input type="text" id="lb_city" name="lb_city" value="<?php echo esc_attr( $city ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="lb_country"><?php esc_html_e( 'Land', 'libre-bite' ); ?></label></th>
				<td>
					<input type="text" id="lb_country" name="lb_country" value="<?php echo esc_attr( $country ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="lb_maps_url"><?php esc_html_e( 'Google Maps Link', 'libre-bite' ); ?></label></th>
				<td>
					<input type="url" id="lb_maps_url" name="lb_maps_url" value="<?php echo esc_attr( $maps_url ); ?>" class="large-text" placeholder="https://maps.google.com/...">
					<p class="description"><?php esc_html_e( 'Optional: Link zu Google Maps für diesen Standort. Die Adresse wird auf der Bestellbestätigung und in E-Mails verlinkt.', 'libre-bite' ); ?></p>
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
		$opening_hours = get_post_meta( $post->ID, '_lb_opening_hours', true );
		if ( ! is_array( $opening_hours ) ) {
			$opening_hours = array();
		}

		$days = array(
			'monday'    => __( 'Montag', 'libre-bite' ),
			'tuesday'   => __( 'Dienstag', 'libre-bite' ),
			'wednesday' => __( 'Mittwoch', 'libre-bite' ),
			'thursday'  => __( 'Donnerstag', 'libre-bite' ),
			'friday'    => __( 'Freitag', 'libre-bite' ),
			'saturday'  => __( 'Samstag', 'libre-bite' ),
			'sunday'    => __( 'Sonntag', 'libre-bite' ),
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
							<input type="checkbox" name="lb_hours[<?php echo esc_attr( $day_key ); ?>][closed]" value="1" <?php checked( $is_closed, true ); ?>>
							<?php esc_html_e( 'Geschlossen', 'libre-bite' ); ?>
						</label>
						<br>
						<label>
							<?php esc_html_e( 'Von', 'libre-bite' ); ?>
							<input type="time" name="lb_hours[<?php echo esc_attr( $day_key ); ?>][open]" value="<?php echo esc_attr( $open ); ?>">
						</label>
						<label>
							<?php esc_html_e( 'Bis', 'libre-bite' ); ?>
							<input type="time" name="lb_hours[<?php echo esc_attr( $day_key ); ?>][close]" value="<?php echo esc_attr( $close ); ?>">
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
		if ( ! isset( $_POST['lb_location_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lb_location_nonce'] ) ), 'lb_save_location' ) ) {
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
		if ( isset( $_POST['lb_location_image'] ) ) {
			$image_id = intval( wp_unslash( $_POST['lb_location_image'] ) );
			if ( $image_id ) {
				update_post_meta( $post_id, '_lb_location_image', $image_id );
			} else {
				delete_post_meta( $post_id, '_lb_location_image' );
			}
		}

		// Adresse speichern.
		if ( isset( $_POST['lb_street'] ) ) {
			update_post_meta( $post_id, '_lb_street', sanitize_text_field( wp_unslash( $_POST['lb_street'] ) ) );
		}
		if ( isset( $_POST['lb_zip'] ) ) {
			update_post_meta( $post_id, '_lb_zip', sanitize_text_field( wp_unslash( $_POST['lb_zip'] ) ) );
		}
		if ( isset( $_POST['lb_city'] ) ) {
			update_post_meta( $post_id, '_lb_city', sanitize_text_field( wp_unslash( $_POST['lb_city'] ) ) );
		}
		if ( isset( $_POST['lb_country'] ) ) {
			update_post_meta( $post_id, '_lb_country', sanitize_text_field( wp_unslash( $_POST['lb_country'] ) ) );
		}
		if ( isset( $_POST['lb_maps_url'] ) ) {
			update_post_meta( $post_id, '_lb_maps_url', esc_url_raw( wp_unslash( $_POST['lb_maps_url'] ) ) );
		}

		// Öffnungszeiten speichern.
		if ( isset( $_POST['lb_hours'] ) && is_array( $_POST['lb_hours'] ) ) {
			$hours = array();
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in loop below.
			foreach ( wp_unslash( $_POST['lb_hours'] ) as $day => $times ) {
				$hours[ sanitize_key( $day ) ] = array(
					'closed' => isset( $times['closed'] ),
					'open'   => isset( $times['open'] ) ? sanitize_text_field( $times['open'] ) : '',
					'close'  => isset( $times['close'] ) ? sanitize_text_field( $times['close'] ) : '',
				);
			}
			update_post_meta( $post_id, '_lb_opening_hours', $hours );
		}
	}

	/**
	 * Produkt-Meta-Box hinzufügen
	 */
	public function add_product_meta_box() {
		add_meta_box(
			'lb_product_locations',
			__( 'Standorte', 'libre-bite' ),
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
		wp_nonce_field( 'lb_save_product_locations', 'lb_product_locations_nonce' );

		$selected_locations = get_post_meta( $post->ID, '_lb_locations', true );
		if ( ! is_array( $selected_locations ) ) {
			$selected_locations = array();
		}

		$locations = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( empty( $locations ) ) {
			echo '<p>' . esc_html__( 'Noch keine Standorte vorhanden.', 'libre-bite' ) . '</p>';
			return;
		}

		echo '<div style="max-height: 200px; overflow-y: auto;">';
		foreach ( $locations as $location ) {
			$checked = in_array( $location->ID, $selected_locations, true );
			?>
			<label style="display: block; margin-bottom: 5px;">
				<input type="checkbox" name="lb_product_locations[]" value="<?php echo esc_attr( $location->ID ); ?>" <?php checked( $checked ); ?>>
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
		if ( ! isset( $_POST['lb_product_locations_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lb_product_locations_nonce'] ) ), 'lb_save_product_locations' ) ) {
			return;
		}

		// Berechtigungen prüfen.
		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		// Standorte speichern.
		$locations = isset( $_POST['lb_product_locations'] ) && is_array( $_POST['lb_product_locations'] )
			? array_map( 'intval', wp_unslash( $_POST['lb_product_locations'] ) )
			: array();

		update_post_meta( $post_id, '_lb_locations', $locations );
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
		$new_columns['address'] = __( 'Adresse', 'libre-bite' );
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
			$street = get_post_meta( $post_id, '_lb_street', true );
			$zip    = get_post_meta( $post_id, '_lb_zip', true );
			$city   = get_post_meta( $post_id, '_lb_city', true );

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
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
			)
		);
	}

	/**
	 * Öffnungszeiten für einen Standort abrufen
	 *
	 * @param int $location_id Standort-ID
	 * @return array
	 */
	public static function get_opening_hours( $location_id ) {
		return get_post_meta( $location_id, '_lb_opening_hours', true );
	}

	/**
	 * Formatierte Adresse für einen Standort abrufen
	 *
	 * @param int $location_id Standort-ID
	 * @return string
	 */
	public static function get_formatted_address( $location_id ) {
		$street  = get_post_meta( $location_id, '_lb_street', true );
		$zip     = get_post_meta( $location_id, '_lb_zip', true );
		$city    = get_post_meta( $location_id, '_lb_city', true );
		$country = get_post_meta( $location_id, '_lb_country', true );

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
		return get_post_meta( $location_id, '_lb_maps_url', true );
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

		$current_time     = current_time( 'timestamp' );
		$current_day      = strtolower( wp_date( 'l', $current_time ) );
		$current_hour_min = wp_date( 'H:i', $current_time );

		// Prüfen ob heute geöffnet.
		if ( isset( $opening_hours[ $current_day ] ) && ! $opening_hours[ $current_day ]['closed'] ) {
			$open_time  = $opening_hours[ $current_day ]['open'];
			$close_time = $opening_hours[ $current_day ]['close'];

			// Ist aktuell geöffnet?
			if ( $current_hour_min >= $open_time && $current_hour_min < $close_time ) {
				// Prüfen ob in weniger als 1 Stunde schliesst.
				$close_timestamp  = strtotime( 'today ' . $close_time );
				$time_until_close = $close_timestamp - $current_time;

				if ( $time_until_close <= 3600 && $time_until_close > 0 ) {
					return array(
						'type' => 'closing-soon',
						/* translators: %s: closing time */
						'text' => sprintf( __( 'Schliesst um %s', 'libre-bite' ), $close_time ),
					);
				}

				return array(
					'type' => 'open',
					'text' => __( 'Geöffnet', 'libre-bite' ),
				);
			}

			// Öffnet bald (30 Min vor Öffnung).
			if ( $current_hour_min < $open_time ) {
				$open_timestamp  = strtotime( 'today ' . $open_time );
				$time_until_open = $open_timestamp - $current_time;

				if ( $time_until_open <= 1800 && $time_until_open > 0 ) {
					return array(
						'type' => 'opening-soon',
						/* translators: %s: opening time */
						'text' => sprintf( __( 'Öffnet um %s', 'libre-bite' ), $open_time ),
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
			'text' => __( 'Geschlossen', 'libre-bite' ),
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

		$current_time      = current_time( 'timestamp' );
		$current_day_index = ( (int) wp_date( 'N', $current_time ) - 1 ); // 0 = Montag.

		// Bis zu 7 Tage in die Zukunft suchen.
		for ( $i = 0; $i <= 7; $i++ ) {
			$check_day_index = ( $current_day_index + $i ) % 7;
			$day_name        = $day_names[ $check_day_index ];

			if ( isset( $opening_hours[ $day_name ] ) && ! $opening_hours[ $day_name ]['closed'] ) {
				$open_time = $opening_hours[ $day_name ]['open'];

				if ( 0 === $i ) {
					// Heute: nur wenn Öffnung noch bevorsteht.
					if ( wp_date( 'H:i', $current_time ) < $open_time ) {
						/* translators: %s: opening time */
						return sprintf( __( 'Öffnet heute %s', 'libre-bite' ), $open_time );
					}
				} elseif ( 1 === $i ) {
					/* translators: %s: opening time */
					return sprintf( __( 'Öffnet morgen %s', 'libre-bite' ), $open_time );
				} else {
					$day_abbr = $day_names_de[ $day_name ];
					/* translators: %1$s: day abbreviation, %2$s: opening time */
					return sprintf( __( 'Öffnet %1$s %2$s', 'libre-bite' ), $day_abbr, $open_time );
				}
			}
		}

		return null;
	}
}
