<?php
/**
 * Nährwerte & Allergene
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Nutritional-Info-Modul
 */
class LBite_Nutritional_Info {

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
		// Meta-Boxen
		$this->loader->add_action( 'add_meta_boxes', $this, 'add_meta_boxes' );
		$this->loader->add_action( 'woocommerce_process_product_meta', $this, 'save_product_meta' );

		// Frontend-Anzeige
		$this->loader->add_action( 'woocommerce_product_meta_end', $this, 'display_nutritional_info' );
		$this->loader->add_action( 'woocommerce_product_meta_end', $this, 'display_allergens' );
	}

	/**
	 * Meta-Boxen hinzufügen
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'lbite_nutritional_info',
			__( 'Nutritional Information', 'libre-bite' ),
			array( $this, 'render_nutritional_meta_box' ),
			'product',
			'normal',
			'default'
		);

		add_meta_box(
			'lbite_allergens',
			__( 'Allergens & Ingredients', 'libre-bite' ),
			array( $this, 'render_allergens_meta_box' ),
			'product',
			'normal',
			'default'
		);
	}

	/**
	 * Nährwert-Meta-Box rendern
	 *
	 * @param WP_Post $post Post-Objekt
	 */
	public function render_nutritional_meta_box( $post ) {
		wp_nonce_field( 'lbite_save_nutritional_info', 'lbite_nutritional_nonce' );

		$serving_size = get_post_meta( $post->ID, '_lbite_serving_size', true );
		$energy_kcal  = get_post_meta( $post->ID, '_lbite_energy_kcal', true );
		$energy_kj    = get_post_meta( $post->ID, '_lbite_energy_kj', true );
		$fat          = get_post_meta( $post->ID, '_lbite_fat', true );
		$saturated    = get_post_meta( $post->ID, '_lbite_saturated', true );
		$carbs        = get_post_meta( $post->ID, '_lbite_carbs', true );
		$sugar        = get_post_meta( $post->ID, '_lbite_sugar', true );
		$protein      = get_post_meta( $post->ID, '_lbite_protein', true );
		$salt         = get_post_meta( $post->ID, '_lbite_salt', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="lbite_serving_size"><?php esc_html_e( 'Serving Size', 'libre-bite' ); ?></label></th>
				<td>
					<input type="text" id="lbite_serving_size" name="lbite_serving_size" value="<?php echo esc_attr( $serving_size ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'E.g. "100g" or "1 serving (250g)"', 'libre-bite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="lbite_energy_kcal"><?php esc_html_e( 'Energy (kcal)', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" step="0.1" min="0" id="lbite_energy_kcal" name="lbite_energy_kcal" value="<?php echo esc_attr( $energy_kcal ); ?>" class="small-text">
				</td>
			</tr>
			<tr>
				<th><label for="lbite_energy_kj"><?php esc_html_e( 'Energy (kJ)', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" step="0.1" min="0" id="lbite_energy_kj" name="lbite_energy_kj" value="<?php echo esc_attr( $energy_kj ); ?>" class="small-text">
				</td>
			</tr>
			<tr>
				<th><label for="lbite_fat"><?php esc_html_e( 'Fat (g)', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" step="0.1" min="0" id="lbite_fat" name="lbite_fat" value="<?php echo esc_attr( $fat ); ?>" class="small-text">
				</td>
			</tr>
			<tr>
				<th><label for="lbite_saturated"><?php esc_html_e( 'of which saturated fatty acids (g)', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" step="0.1" min="0" id="lbite_saturated" name="lbite_saturated" value="<?php echo esc_attr( $saturated ); ?>" class="small-text">
				</td>
			</tr>
			<tr>
				<th><label for="lbite_carbs"><?php esc_html_e( 'Carbohydrates (g)', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" step="0.1" min="0" id="lbite_carbs" name="lbite_carbs" value="<?php echo esc_attr( $carbs ); ?>" class="small-text">
				</td>
			</tr>
			<tr>
				<th><label for="lbite_sugar"><?php esc_html_e( 'of which sugars (g)', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" step="0.1" min="0" id="lbite_sugar" name="lbite_sugar" value="<?php echo esc_attr( $sugar ); ?>" class="small-text">
				</td>
			</tr>
			<tr>
				<th><label for="lbite_protein"><?php esc_html_e( 'Protein (g)', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" step="0.1" min="0" id="lbite_protein" name="lbite_protein" value="<?php echo esc_attr( $protein ); ?>" class="small-text">
				</td>
			</tr>
			<tr>
				<th><label for="lbite_salt"><?php esc_html_e( 'Salt (g)', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" step="0.1" min="0" id="lbite_salt" name="lbite_salt" value="<?php echo esc_attr( $salt ); ?>" class="small-text">
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Allergene-Meta-Box rendern
	 *
	 * @param WP_Post $post Post-Objekt
	 */
	public function render_allergens_meta_box( $post ) {
		$allergens           = get_post_meta( $post->ID, '_lbite_allergens', true );
		$other_ingredients   = get_post_meta( $post->ID, '_lbite_other_ingredients', true );

		if ( ! is_array( $allergens ) ) {
			$allergens = array();
		}

		$allergen_list = array(
			'gluten'      => __( 'Gluten', 'libre-bite' ),
			'crustaceans' => __( 'Crustaceans', 'libre-bite' ),
			'eggs'        => __( 'Eggs', 'libre-bite' ),
			'fish'        => __( 'Fish', 'libre-bite' ),
			'peanuts'     => __( 'Peanuts', 'libre-bite' ),
			'soy'         => __( 'Soy', 'libre-bite' ),
			'milk'        => __( 'Milk/Lactose', 'libre-bite' ),
			'nuts'        => __( 'Tree nuts', 'libre-bite' ),
			'celery'      => __( 'Celery', 'libre-bite' ),
			'mustard'     => __( 'Mustard', 'libre-bite' ),
			'sesame'      => __( 'Sesame', 'libre-bite' ),
			'sulfites'    => __( 'Sulfites', 'libre-bite' ),
			'lupine'      => __( 'Lupins', 'libre-bite' ),
			'molluscs'    => __( 'Molluscs', 'libre-bite' ),
		);
		?>
		<div style="padding: 10px;">
			<p><strong><?php esc_html_e( 'Select allergens:', 'libre-bite' ); ?></strong></p>
			<div style="column-count: 2; column-gap: 20px;">
				<?php foreach ( $allergen_list as $key => $label ) : ?>
					<label style="display: block; margin-bottom: 8px;">
						<input type="checkbox" name="lbite_allergens[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $allergens, true ) ); ?>>
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
			</div>

			<hr style="margin: 20px 0;">

			<p><strong><?php esc_html_e( 'Other Ingredients:', 'libre-bite' ); ?></strong></p>
			<textarea name="lbite_other_ingredients" rows="4" style="width: 100%;"><?php echo esc_textarea( $other_ingredients ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Other ingredients or notes (optional)', 'libre-bite' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Produkt-Meta speichern
	 *
	 * @param int $post_id Post-ID
	 */
	public function save_product_meta( $post_id ) {
		// Nonce prüfen.
		if ( ! isset( $_POST['lbite_nutritional_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lbite_nutritional_nonce'] ) ), 'lbite_save_nutritional_info' ) ) {
			return;
		}

		// Berechtigungen prüfen.
		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		// Nährwerte speichern.
		$nutritional_fields = array(
			'serving_size',
			'energy_kcal',
			'energy_kj',
			'fat',
			'saturated',
			'carbs',
			'sugar',
			'protein',
			'salt',
		);

		foreach ( $nutritional_fields as $field ) {
			$key = 'lbite_' . $field;
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, '_' . $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}

		// Allergene speichern.
		$allergens = isset( $_POST['lbite_allergens'] ) && is_array( $_POST['lbite_allergens'] )
			? array_map( 'sanitize_text_field', wp_unslash( $_POST['lbite_allergens'] ) )
			: array();
		update_post_meta( $post_id, '_lbite_allergens', $allergens );

		// Weitere Inhaltsstoffe.
		if ( isset( $_POST['lbite_other_ingredients'] ) ) {
			update_post_meta( $post_id, '_lbite_other_ingredients', sanitize_textarea_field( wp_unslash( $_POST['lbite_other_ingredients'] ) ) );
		}
	}

	/**
	 * Nährwertangaben im Frontend anzeigen
	 */
	public function display_nutritional_info() {
		global $product;

		if ( ! $product ) {
			return;
		}

		$serving_size = get_post_meta( $product->get_id(), '_lbite_serving_size', true );
		$energy_kcal  = get_post_meta( $product->get_id(), '_lbite_energy_kcal', true );
		$energy_kj    = get_post_meta( $product->get_id(), '_lbite_energy_kj', true );
		$fat          = get_post_meta( $product->get_id(), '_lbite_fat', true );
		$saturated    = get_post_meta( $product->get_id(), '_lbite_saturated', true );
		$carbs        = get_post_meta( $product->get_id(), '_lbite_carbs', true );
		$sugar        = get_post_meta( $product->get_id(), '_lbite_sugar', true );
		$protein      = get_post_meta( $product->get_id(), '_lbite_protein', true );
		$salt         = get_post_meta( $product->get_id(), '_lbite_salt', true );

		// Prüfen ob Daten vorhanden sind
		if ( ! $energy_kcal && ! $fat && ! $carbs && ! $protein ) {
			return;
		}

		?>
		<div class="lbite-nutritional-info" style="margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
			<h3 style="margin-top: 0;"><?php esc_html_e( 'Nutritional Information', 'libre-bite' ); ?></h3>
			<?php if ( $serving_size ) : ?>
				<p style="font-size: 13px; color: #666; margin-bottom: 10px;">
					<?php
					/* translators: %s: serving size (e.g., "100g") */
					echo esc_html( sprintf( __( 'per %s', 'libre-bite' ), $serving_size ) );
					?>
				</p>
			<?php endif; ?>
			<table style="width: 100%; border-collapse: collapse;">
				<tbody>
					<?php if ( $energy_kcal || $energy_kj ) : ?>
						<tr style="border-bottom: 1px solid #eee;">
							<td style="padding: 8px 10px;"><strong><?php esc_html_e( 'Energy', 'libre-bite' ); ?></strong></td>
							<td style="padding: 8px 10px; text-align: right;">
								<?php if ( $energy_kcal ) : ?>
									<?php echo esc_html( $energy_kcal ); ?> kcal
								<?php endif; ?>
								<?php if ( $energy_kj ) : ?>
									/ <?php echo esc_html( $energy_kj ); ?> kJ
								<?php endif; ?>
							</td>
						</tr>
					<?php endif; ?>
					<?php if ( $fat ) : ?>
						<tr style="border-bottom: 1px solid #eee;">
							<td style="padding: 8px 10px;"><?php esc_html_e( 'Fat', 'libre-bite' ); ?></td>
							<td style="padding: 8px 10px; text-align: right;"><?php echo esc_html( $fat ); ?> g</td>
						</tr>
					<?php endif; ?>
					<?php if ( $saturated ) : ?>
						<tr style="border-bottom: 1px solid #eee;">
							<td style="padding: 8px 10px; font-size: 13px; color: #555;"><?php esc_html_e( 'of which saturated fatty acids', 'libre-bite' ); ?></td>
							<td style="padding: 8px 10px; text-align: right; font-size: 13px;"><?php echo esc_html( $saturated ); ?> g</td>
						</tr>
					<?php endif; ?>
					<?php if ( $carbs ) : ?>
						<tr style="border-bottom: 1px solid #eee;">
							<td style="padding: 8px 10px;"><?php esc_html_e( 'Carbohydrates', 'libre-bite' ); ?></td>
							<td style="padding: 8px 10px; text-align: right;"><?php echo esc_html( $carbs ); ?> g</td>
						</tr>
					<?php endif; ?>
					<?php if ( $sugar ) : ?>
						<tr style="border-bottom: 1px solid #eee;">
							<td style="padding: 8px 10px; font-size: 13px; color: #555;"><?php esc_html_e( 'of which sugars', 'libre-bite' ); ?></td>
							<td style="padding: 8px 10px; text-align: right; font-size: 13px;"><?php echo esc_html( $sugar ); ?> g</td>
						</tr>
					<?php endif; ?>
					<?php if ( $protein ) : ?>
						<tr style="border-bottom: 1px solid #eee;">
							<td style="padding: 8px 10px;"><?php esc_html_e( 'Protein', 'libre-bite' ); ?></td>
							<td style="padding: 8px 10px; text-align: right;"><?php echo esc_html( $protein ); ?> g</td>
						</tr>
					<?php endif; ?>
					<?php if ( $salt ) : ?>
						<tr>
							<td style="padding: 8px 10px;"><?php esc_html_e( 'Salt', 'libre-bite' ); ?></td>
							<td style="padding: 8px 10px; text-align: right;"><?php echo esc_html( $salt ); ?> g</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Allergene im Frontend anzeigen
	 */
	public function display_allergens() {
		global $product;

		if ( ! $product ) {
			return;
		}

		$allergens         = get_post_meta( $product->get_id(), '_lbite_allergens', true );
		$other_ingredients = get_post_meta( $product->get_id(), '_lbite_other_ingredients', true );

		if ( empty( $allergens ) && empty( $other_ingredients ) ) {
			return;
		}

		$allergen_labels = array(
			'gluten'      => __( 'Gluten', 'libre-bite' ),
			'crustaceans' => __( 'Crustaceans', 'libre-bite' ),
			'eggs'        => __( 'Eggs', 'libre-bite' ),
			'fish'        => __( 'Fish', 'libre-bite' ),
			'peanuts'     => __( 'Peanuts', 'libre-bite' ),
			'soy'         => __( 'Soy', 'libre-bite' ),
			'milk'        => __( 'Milk/Lactose', 'libre-bite' ),
			'nuts'        => __( 'Tree nuts', 'libre-bite' ),
			'celery'      => __( 'Celery', 'libre-bite' ),
			'mustard'     => __( 'Mustard', 'libre-bite' ),
			'sesame'      => __( 'Sesame', 'libre-bite' ),
			'sulfites'    => __( 'Sulfites', 'libre-bite' ),
			'lupine'      => __( 'Lupins', 'libre-bite' ),
			'molluscs'    => __( 'Molluscs', 'libre-bite' ),
		);
		?>
		<div class="lbite-allergens" style="margin: 20px 0; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">
			<h3 style="margin-top: 0; color: #856404;"><?php esc_html_e( 'Allergens & Ingredients', 'libre-bite' ); ?></h3>

			<?php if ( ! empty( $allergens ) && is_array( $allergens ) ) : ?>
				<p><strong><?php esc_html_e( 'Contains:', 'libre-bite' ); ?></strong></p>
				<ul style="margin: 10px 0; padding-left: 20px;">
					<?php foreach ( $allergens as $allergen ) : ?>
						<?php if ( isset( $allergen_labels[ $allergen ] ) ) : ?>
							<li><?php echo esc_html( $allergen_labels[ $allergen ] ); ?></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( $other_ingredients ) : ?>
				<p><strong><?php esc_html_e( 'Other Ingredients:', 'libre-bite' ); ?></strong></p>
				<p style="margin: 10px 0;"><?php echo esc_html( $other_ingredients ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
