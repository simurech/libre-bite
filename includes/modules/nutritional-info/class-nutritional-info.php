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
class LB_Nutritional_Info {

	/**
	 * Loader-Instanz
	 *
	 * @var LB_Loader
	 */
	private $loader;

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
			'lb_nutritional_info',
			__( 'Nährwertangaben', 'libre-bite' ),
			array( $this, 'render_nutritional_meta_box' ),
			'product',
			'normal',
			'default'
		);

		add_meta_box(
			'lb_allergens',
			__( 'Allergene & Inhaltsstoffe', 'libre-bite' ),
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
		wp_nonce_field( 'lb_save_nutritional_info', 'lb_nutritional_nonce' );

		$serving_size = get_post_meta( $post->ID, '_lb_serving_size', true );
		$energy_kcal  = get_post_meta( $post->ID, '_lb_energy_kcal', true );
		$energy_kj    = get_post_meta( $post->ID, '_lb_energy_kj', true );
		$fat          = get_post_meta( $post->ID, '_lb_fat', true );
		$saturated    = get_post_meta( $post->ID, '_lb_saturated', true );
		$carbs        = get_post_meta( $post->ID, '_lb_carbs', true );
		$sugar        = get_post_meta( $post->ID, '_lb_sugar', true );
		$protein      = get_post_meta( $post->ID, '_lb_protein', true );
		$salt         = get_post_meta( $post->ID, '_lb_salt', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="lb_serving_size"><?php esc_html_e( 'Portionsgröße', 'libre-bite' ); ?></label></th>
				<td>
					<input type="text" id="lb_serving_size" name="lb_serving_size" value="<?php echo esc_attr( $serving_size ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'z.B. "100g" oder "1 Portion (250g)"', 'libre-bite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="lb_energy_kcal"><?php esc_html_e( 'Energie (kcal)', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" step="0.1" min="0" id="lb_energy_kcal" name="lb_energy_kcal" value="<?php echo esc_attr( $energy_kcal ); ?>" class="small-text">
				</td>
			</tr>
			<tr>
				<th><label for="lb_energy_kj"><?php esc_html_e( 'Energie (kJ)', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" step="0.1" min="0" id="lb_energy_kj" name="lb_energy_kj" value="<?php echo esc_attr( $energy_kj ); ?>" class="small-text">
				</td>
			</tr>
			<tr>
				<th><label for="lb_fat"><?php esc_html_e( 'Fett (g)', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" step="0.1" min="0" id="lb_fat" name="lb_fat" value="<?php echo esc_attr( $fat ); ?>" class="small-text">
				</td>
			</tr>
			<tr>
				<th><label for="lb_saturated"><?php esc_html_e( 'davon gesättigte Fettsäuren (g)', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" step="0.1" min="0" id="lb_saturated" name="lb_saturated" value="<?php echo esc_attr( $saturated ); ?>" class="small-text">
				</td>
			</tr>
			<tr>
				<th><label for="lb_carbs"><?php esc_html_e( 'Kohlenhydrate (g)', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" step="0.1" min="0" id="lb_carbs" name="lb_carbs" value="<?php echo esc_attr( $carbs ); ?>" class="small-text">
				</td>
			</tr>
			<tr>
				<th><label for="lb_sugar"><?php esc_html_e( 'davon Zucker (g)', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" step="0.1" min="0" id="lb_sugar" name="lb_sugar" value="<?php echo esc_attr( $sugar ); ?>" class="small-text">
				</td>
			</tr>
			<tr>
				<th><label for="lb_protein"><?php esc_html_e( 'Eiweiß (g)', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" step="0.1" min="0" id="lb_protein" name="lb_protein" value="<?php echo esc_attr( $protein ); ?>" class="small-text">
				</td>
			</tr>
			<tr>
				<th><label for="lb_salt"><?php esc_html_e( 'Salz (g)', 'libre-bite' ); ?></label></th>
				<td>
					<input type="number" step="0.1" min="0" id="lb_salt" name="lb_salt" value="<?php echo esc_attr( $salt ); ?>" class="small-text">
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
		$allergens           = get_post_meta( $post->ID, '_lb_allergens', true );
		$other_ingredients   = get_post_meta( $post->ID, '_lb_other_ingredients', true );

		if ( ! is_array( $allergens ) ) {
			$allergens = array();
		}

		$allergen_list = array(
			'gluten'      => __( 'Gluten', 'libre-bite' ),
			'crustaceans' => __( 'Krebstiere', 'libre-bite' ),
			'eggs'        => __( 'Eier', 'libre-bite' ),
			'fish'        => __( 'Fisch', 'libre-bite' ),
			'peanuts'     => __( 'Erdnüsse', 'libre-bite' ),
			'soy'         => __( 'Soja', 'libre-bite' ),
			'milk'        => __( 'Milch/Laktose', 'libre-bite' ),
			'nuts'        => __( 'Schalenfrüchte', 'libre-bite' ),
			'celery'      => __( 'Sellerie', 'libre-bite' ),
			'mustard'     => __( 'Senf', 'libre-bite' ),
			'sesame'      => __( 'Sesam', 'libre-bite' ),
			'sulfites'    => __( 'Sulfite', 'libre-bite' ),
			'lupine'      => __( 'Lupinen', 'libre-bite' ),
			'molluscs'    => __( 'Weichtiere', 'libre-bite' ),
		);
		?>
		<div style="padding: 10px;">
			<p><strong><?php esc_html_e( 'Allergene auswählen:', 'libre-bite' ); ?></strong></p>
			<div style="column-count: 2; column-gap: 20px;">
				<?php foreach ( $allergen_list as $key => $label ) : ?>
					<label style="display: block; margin-bottom: 8px;">
						<input type="checkbox" name="lb_allergens[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $allergens, true ) ); ?>>
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
			</div>

			<hr style="margin: 20px 0;">

			<p><strong><?php esc_html_e( 'Weitere Inhaltsstoffe:', 'libre-bite' ); ?></strong></p>
			<textarea name="lb_other_ingredients" rows="4" style="width: 100%;"><?php echo esc_textarea( $other_ingredients ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Weitere Inhaltsstoffe oder Hinweise (optional)', 'libre-bite' ); ?></p>
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
		if ( ! isset( $_POST['lb_nutritional_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lb_nutritional_nonce'] ) ), 'lb_save_nutritional_info' ) ) {
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
			$key = 'lb_' . $field;
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, '_' . $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}

		// Allergene speichern.
		$allergens = isset( $_POST['lb_allergens'] ) && is_array( $_POST['lb_allergens'] )
			? array_map( 'sanitize_text_field', wp_unslash( $_POST['lb_allergens'] ) )
			: array();
		update_post_meta( $post_id, '_lb_allergens', $allergens );

		// Weitere Inhaltsstoffe.
		if ( isset( $_POST['lb_other_ingredients'] ) ) {
			update_post_meta( $post_id, '_lb_other_ingredients', sanitize_textarea_field( wp_unslash( $_POST['lb_other_ingredients'] ) ) );
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

		$serving_size = get_post_meta( $product->get_id(), '_lb_serving_size', true );
		$energy_kcal  = get_post_meta( $product->get_id(), '_lb_energy_kcal', true );
		$energy_kj    = get_post_meta( $product->get_id(), '_lb_energy_kj', true );
		$fat          = get_post_meta( $product->get_id(), '_lb_fat', true );
		$saturated    = get_post_meta( $product->get_id(), '_lb_saturated', true );
		$carbs        = get_post_meta( $product->get_id(), '_lb_carbs', true );
		$sugar        = get_post_meta( $product->get_id(), '_lb_sugar', true );
		$protein      = get_post_meta( $product->get_id(), '_lb_protein', true );
		$salt         = get_post_meta( $product->get_id(), '_lb_salt', true );

		// Prüfen ob Daten vorhanden sind
		if ( ! $energy_kcal && ! $fat && ! $carbs && ! $protein ) {
			return;
		}

		?>
		<div class="lb-nutritional-info" style="margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
			<h3 style="margin-top: 0;"><?php esc_html_e( 'Nährwertangaben', 'libre-bite' ); ?></h3>
			<?php if ( $serving_size ) : ?>
				<p style="font-size: 13px; color: #666; margin-bottom: 10px;">
					<?php
					/* translators: %s: serving size (e.g., "100g") */
					echo esc_html( sprintf( __( 'pro %s', 'libre-bite' ), $serving_size ) );
					?>
				</p>
			<?php endif; ?>
			<table style="width: 100%; border-collapse: collapse;">
				<tbody>
					<?php if ( $energy_kcal || $energy_kj ) : ?>
						<tr style="border-bottom: 1px solid #eee;">
							<td style="padding: 8px 0;"><strong><?php esc_html_e( 'Energie', 'libre-bite' ); ?></strong></td>
							<td style="padding: 8px 0; text-align: right;">
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
							<td style="padding: 8px 0;"><?php esc_html_e( 'Fett', 'libre-bite' ); ?></td>
							<td style="padding: 8px 0; text-align: right;"><?php echo esc_html( $fat ); ?> g</td>
						</tr>
					<?php endif; ?>
					<?php if ( $saturated ) : ?>
						<tr style="border-bottom: 1px solid #eee;">
							<td style="padding: 8px 0 8px 20px; font-size: 13px;"><?php esc_html_e( 'davon gesättigte Fettsäuren', 'libre-bite' ); ?></td>
							<td style="padding: 8px 0; text-align: right; font-size: 13px;"><?php echo esc_html( $saturated ); ?> g</td>
						</tr>
					<?php endif; ?>
					<?php if ( $carbs ) : ?>
						<tr style="border-bottom: 1px solid #eee;">
							<td style="padding: 8px 0;"><?php esc_html_e( 'Kohlenhydrate', 'libre-bite' ); ?></td>
							<td style="padding: 8px 0; text-align: right;"><?php echo esc_html( $carbs ); ?> g</td>
						</tr>
					<?php endif; ?>
					<?php if ( $sugar ) : ?>
						<tr style="border-bottom: 1px solid #eee;">
							<td style="padding: 8px 0 8px 20px; font-size: 13px;"><?php esc_html_e( 'davon Zucker', 'libre-bite' ); ?></td>
							<td style="padding: 8px 0; text-align: right; font-size: 13px;"><?php echo esc_html( $sugar ); ?> g</td>
						</tr>
					<?php endif; ?>
					<?php if ( $protein ) : ?>
						<tr style="border-bottom: 1px solid #eee;">
							<td style="padding: 8px 0;"><?php esc_html_e( 'Eiweiß', 'libre-bite' ); ?></td>
							<td style="padding: 8px 0; text-align: right;"><?php echo esc_html( $protein ); ?> g</td>
						</tr>
					<?php endif; ?>
					<?php if ( $salt ) : ?>
						<tr>
							<td style="padding: 8px 0;"><?php esc_html_e( 'Salz', 'libre-bite' ); ?></td>
							<td style="padding: 8px 0; text-align: right;"><?php echo esc_html( $salt ); ?> g</td>
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

		$allergens         = get_post_meta( $product->get_id(), '_lb_allergens', true );
		$other_ingredients = get_post_meta( $product->get_id(), '_lb_other_ingredients', true );

		if ( empty( $allergens ) && empty( $other_ingredients ) ) {
			return;
		}

		$allergen_labels = array(
			'gluten'      => __( 'Gluten', 'libre-bite' ),
			'crustaceans' => __( 'Krebstiere', 'libre-bite' ),
			'eggs'        => __( 'Eier', 'libre-bite' ),
			'fish'        => __( 'Fisch', 'libre-bite' ),
			'peanuts'     => __( 'Erdnüsse', 'libre-bite' ),
			'soy'         => __( 'Soja', 'libre-bite' ),
			'milk'        => __( 'Milch/Laktose', 'libre-bite' ),
			'nuts'        => __( 'Schalenfrüchte', 'libre-bite' ),
			'celery'      => __( 'Sellerie', 'libre-bite' ),
			'mustard'     => __( 'Senf', 'libre-bite' ),
			'sesame'      => __( 'Sesam', 'libre-bite' ),
			'sulfites'    => __( 'Sulfite', 'libre-bite' ),
			'lupine'      => __( 'Lupinen', 'libre-bite' ),
			'molluscs'    => __( 'Weichtiere', 'libre-bite' ),
		);
		?>
		<div class="lb-allergens" style="margin: 20px 0; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">
			<h3 style="margin-top: 0; color: #856404;"><?php esc_html_e( 'Allergene & Inhaltsstoffe', 'libre-bite' ); ?></h3>

			<?php if ( ! empty( $allergens ) && is_array( $allergens ) ) : ?>
				<p><strong><?php esc_html_e( 'Enthält:', 'libre-bite' ); ?></strong></p>
				<ul style="margin: 10px 0; padding-left: 20px;">
					<?php foreach ( $allergens as $allergen ) : ?>
						<?php if ( isset( $allergen_labels[ $allergen ] ) ) : ?>
							<li><?php echo esc_html( $allergen_labels[ $allergen ] ); ?></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( $other_ingredients ) : ?>
				<p><strong><?php esc_html_e( 'Weitere Inhaltsstoffe:', 'libre-bite' ); ?></strong></p>
				<p style="margin: 10px 0;"><?php echo esc_html( $other_ingredients ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
