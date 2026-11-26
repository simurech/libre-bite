<?php
/**
 * Modifikator-Gruppen (F39, Pro)
 *
 * Fasst die bestehenden Produktoptionen pro Produkt zu Gruppen mit Regeln
 * zusammen: Einfach- oder Mehrfachauswahl, Minimum, Maximum und eine Anzahl
 * Gratis-Optionen (die günstigsten zählen als gratis). Beispiel: «Sauce
 * wählen (genau 1)», «Toppings (bis 4, 2 inklusive)».
 *
 * Die Optionen selbst bleiben die wiederverwendbaren Beiträge vom Typ
 * lbite_product_option. Gruppen liegen als Produkt-Meta _lbite_option_groups.
 *
 * evaluate() ist die einzige Stelle, die Regeln prüft und Preise berechnet.
 * Warenkorb, Bestellung, Menü-Ansicht und Kasse rufen sie alle auf, damit
 * nirgends anders gerechnet wird. Die JS-Bibliothek option-groups.js spiegelt
 * die Regeln nur für die Bedienung; massgebend bleibt der Server.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Modifikator-Gruppen-Klasse
 */
class LBite_Option_Groups {

	/**
	 * Meta-Schlüssel am Produkt.
	 */
	const META = '_lbite_option_groups';

	/**
	 * Höchstzahl Gruppen pro Produkt.
	 */
	const MAX_GROUPS = 10;

	/*
	 * ------------------------------------------------------------------
	 * Reine Logik (ohne WordPress-Zustand, per PHPUnit getestet)
	 * ------------------------------------------------------------------
	 */

	/**
	 * Auswahl gegen Gruppenregeln prüfen und Aufpreis berechnen.
	 *
	 * @param array[] $groups   Gruppen: id, type (single|multiple), min, max (0 = unbegrenzt), free, option_ids.
	 * @param float[] $prices   Preis je Options-ID.
	 * @param int[]   $selected Gewählte Options-IDs.
	 * @return array{valid:bool,group:string|null,reason:string|null,extra:float,charged:array<int,float>}
	 *               reason: 'min', 'max' oder 'unknown' (Option gehört zu keiner Gruppe).
	 */
	public static function evaluate( array $groups, array $prices, array $selected ) {
		$selected = array_values( array_unique( array_map( 'intval', $selected ) ) );
		$result   = array(
			'valid'   => true,
			'group'   => null,
			'reason'  => null,
			'extra'   => 0.0,
			'charged' => array(),
		);

		$known = array();

		foreach ( $groups as $group ) {
			$ids   = array_map( 'intval', isset( $group['option_ids'] ) ? (array) $group['option_ids'] : array() );
			$known = array_merge( $known, $ids );
			$pick  = array_values( array_intersect( $selected, $ids ) );
			$count = count( $pick );
			$min   = max( 0, (int) ( isset( $group['min'] ) ? $group['min'] : 0 ) );
			$max   = max( 0, (int) ( isset( $group['max'] ) ? $group['max'] : 0 ) );
			if ( isset( $group['type'] ) && 'single' === $group['type'] ) {
				$max = 1;
			}

			if ( $result['valid'] && $count < $min ) {
				$result['valid']  = false;
				$result['group']  = (string) $group['id'];
				$result['reason'] = 'min';
			}
			if ( $result['valid'] && $max > 0 && $count > $max ) {
				$result['valid']  = false;
				$result['group']  = (string) $group['id'];
				$result['reason'] = 'max';
			}

			// Gratis: die günstigsten N gewählten Optionen.
			$group_prices = array();
			foreach ( $pick as $id ) {
				$group_prices[ $id ] = isset( $prices[ $id ] ) ? max( 0.0, (float) $prices[ $id ] ) : 0.0;
			}
			asort( $group_prices );
			$free = max( 0, (int) ( isset( $group['free'] ) ? $group['free'] : 0 ) );
			foreach ( $group_prices as $id => $price ) {
				$charged                  = $free > 0 ? 0.0 : $price;
				$free                     = max( 0, $free - 1 );
				$result['charged'][ $id ] = $charged;
				$result['extra']         += $charged;
			}
		}

		if ( $result['valid'] && array_diff( $selected, $known ) ) {
			$result['valid']  = false;
			$result['reason'] = 'unknown';
		}

		$result['extra'] = round( $result['extra'], 2 );

		return $result;
	}

	/**
	 * Gruppen aus dem Produktformular bereinigen.
	 *
	 * Jede Option darf pro Produkt nur in einer Gruppe stehen, sonst wäre die
	 * Zählung zweideutig. Max 0 = unbegrenzt; Min wird auf Max begrenzt.
	 *
	 * @param mixed $raw Rohdaten [ { id, label, type, min, max, free, option_ids[], remove } ].
	 * @return array[]
	 */
	public static function sanitize_groups( $raw ) {
		$groups = array();
		$used   = array();

		if ( ! is_array( $raw ) ) {
			return $groups;
		}

		foreach ( $raw as $g ) {
			if ( ! is_array( $g ) || ! empty( $g['remove'] ) ) {
				continue;
			}

			$label = isset( $g['label'] ) ? trim( sanitize_text_field( $g['label'] ) ) : '';
			$ids   = isset( $g['option_ids'] ) && is_array( $g['option_ids'] ) ? array_map( 'intval', $g['option_ids'] ) : array();
			$ids   = array_values( array_unique( array_filter( array_diff( $ids, $used ) ) ) );

			if ( '' === $label || empty( $ids ) ) {
				continue;
			}

			$type = isset( $g['type'] ) && 'single' === $g['type'] ? 'single' : 'multiple';
			$max  = 'single' === $type ? 1 : max( 0, (int) ( isset( $g['max'] ) ? $g['max'] : 0 ) );
			$min  = max( 0, (int) ( isset( $g['min'] ) ? $g['min'] : 0 ) );
			if ( $max > 0 ) {
				$min = min( $min, $max );
			}
			$min  = min( $min, count( $ids ) );
			$free = max( 0, (int) ( isset( $g['free'] ) ? $g['free'] : 0 ) );
			$id   = isset( $g['id'] ) && '' !== $g['id'] ? sanitize_key( $g['id'] ) : '';
			if ( '' === $id ) {
				$id = 'g' . substr( md5( $label . wp_rand() ), 0, 8 );
			}

			$groups[] = array(
				'id'         => $id,
				'label'      => function_exists( 'mb_substr' ) ? mb_substr( $label, 0, 60 ) : substr( $label, 0, 60 ),
				'type'       => $type,
				'min'        => $min,
				'max'        => $max,
				'free'       => $free,
				'option_ids' => $ids,
			);
			$used     = array_merge( $used, $ids );

			if ( count( $groups ) >= self::MAX_GROUPS ) {
				break;
			}
		}

		return $groups;
	}

	/*
	 * ------------------------------------------------------------------
	 * Texte
	 * ------------------------------------------------------------------
	 */

	/**
	 * Hinweis für Gäste, z. B. «Wähle 1» oder «Bis zu 4, 2 inklusive».
	 *
	 * @param array $group Gruppe.
	 * @return string
	 */
	public static function hint( array $group ) {
		$min  = (int) $group['min'];
		$max  = (int) $group['max'];
		$text = '';

		if ( 'single' === $group['type'] ) {
			$text = $min > 0 ? __( 'Choose 1', 'libre-bite' ) : __( 'Choose up to 1', 'libre-bite' );
		} elseif ( $min > 0 && $max > 0 && $min === $max ) {
			/* translators: %d: number of choices. */
			$text = sprintf( _n( 'Choose %d', 'Choose %d', $min, 'libre-bite' ), $min );
		} elseif ( $min > 0 && $max > 0 ) {
			/* translators: 1: minimum, 2: maximum number of choices. */
			$text = sprintf( __( 'Choose %1$d–%2$d', 'libre-bite' ), $min, $max );
		} elseif ( $min > 0 ) {
			/* translators: %d: minimum number of choices. */
			$text = sprintf( __( 'Choose at least %d', 'libre-bite' ), $min );
		} elseif ( $max > 0 ) {
			/* translators: %d: maximum number of choices. */
			$text = sprintf( __( 'Up to %d', 'libre-bite' ), $max );
		}

		if ( (int) $group['free'] > 0 ) {
			/* translators: %d: number of choices included in the price. */
			$free = sprintf( _n( '%d included', '%d included', (int) $group['free'], 'libre-bite' ), (int) $group['free'] );
			$text = '' !== $text ? $text . ' · ' . $free : $free;
		}

		return $text;
	}

	/**
	 * Fehlermeldung für eine verletzte Regel.
	 *
	 * @param array  $group  Gruppe.
	 * @param string $reason 'min' oder 'max'.
	 * @return string
	 */
	public static function error_message( array $group, $reason ) {
		if ( 'max' === $reason ) {
			/* translators: 1: group name, 2: maximum number of choices. */
			return sprintf( __( '%1$s: please choose at most %2$d.', 'libre-bite' ), $group['label'], 'single' === $group['type'] ? 1 : (int) $group['max'] );
		}

		/* translators: 1: group name, 2: minimum number of choices. */
		return sprintf( __( '%1$s: please choose at least %2$d.', 'libre-bite' ), $group['label'], max( 1, (int) $group['min'] ) );
	}

	/*
	 * ------------------------------------------------------------------
	 * Daten am Produkt
	 * ------------------------------------------------------------------
	 */

	/**
	 * Gespeicherte Gruppen eines Produkts (Varianten erben vom Eltern-Produkt).
	 *
	 * @param int $product_id Produkt- oder Varianten-ID.
	 * @return array[]
	 */
	public static function get_for_product( $product_id ) {
		$product_id = (int) $product_id;
		$parent     = (int) wp_get_post_parent_id( $product_id );
		if ( $parent && 'product_variation' === get_post_type( $product_id ) ) {
			$product_id = $parent;
		}

		$groups = get_post_meta( $product_id, self::META, true );

		return is_array( $groups ) ? $groups : array();
	}

	/**
	 * Gruppen-Bereich in der Produkt-Metabox ausgeben.
	 *
	 * Bestehende Gruppen plus zwei leere Vorlagen – ohne JavaScript, eine
	 * weitere Gruppe erscheint nach dem Speichern.
	 *
	 * @param WP_Post   $post    Produkt.
	 * @param WP_Post[] $options Alle verfügbaren Optionen.
	 */
	public static function render_admin( $post, array $options ) {
		$groups   = self::get_for_product( $post->ID );
		$groups[] = array();
		$groups[] = array();
		?>
		<hr>
		<h4 style="margin-bottom: 4px;"><?php esc_html_e( 'Option groups', 'libre-bite' ); ?></h4>
		<p class="description"><?php esc_html_e( 'Group options with rules, e.g. "Sauce: choose 1" or "Toppings: up to 4, 2 included". The cheapest choices count as included. Each option can belong to one group per product.', 'libre-bite' ); ?></p>
		<?php foreach ( $groups as $index => $g ) : ?>
			<?php
			$g = wp_parse_args(
				$g,
				array(
					'id'         => '',
					'label'      => '',
					'type'       => 'multiple',
					'min'        => 0,
					'max'        => 0,
					'free'       => 0,
					'option_ids' => array(),
				)
			);
			$n = 'lbite_option_groups[' . (int) $index . ']';
			?>
			<fieldset style="margin: 12px 0; padding: 10px; border: 1px solid #dcdcde; border-radius: 4px;">
				<input type="hidden" name="<?php echo esc_attr( $n ); ?>[id]" value="<?php echo esc_attr( $g['id'] ); ?>">
				<p style="margin-top: 0;">
					<input type="text" name="<?php echo esc_attr( $n ); ?>[label]" value="<?php echo esc_attr( $g['label'] ); ?>" placeholder="<?php esc_attr_e( 'Group name, e.g. Sauce', 'libre-bite' ); ?>" style="width: 100%;">
				</p>
				<p>
					<select name="<?php echo esc_attr( $n ); ?>[type]">
						<option value="multiple" <?php selected( $g['type'], 'multiple' ); ?>><?php esc_html_e( 'Several choices', 'libre-bite' ); ?></option>
						<option value="single" <?php selected( $g['type'], 'single' ); ?>><?php esc_html_e( 'One choice', 'libre-bite' ); ?></option>
					</select>
					<label><?php esc_html_e( 'Min', 'libre-bite' ); ?> <input type="number" min="0" max="50" class="small-text" name="<?php echo esc_attr( $n ); ?>[min]" value="<?php echo esc_attr( $g['min'] ); ?>"></label>
					<label><?php esc_html_e( 'Max', 'libre-bite' ); ?> <input type="number" min="0" max="50" class="small-text" name="<?php echo esc_attr( $n ); ?>[max]" value="<?php echo esc_attr( $g['max'] ); ?>"></label>
					<label><?php esc_html_e( 'Included', 'libre-bite' ); ?> <input type="number" min="0" max="50" class="small-text" name="<?php echo esc_attr( $n ); ?>[free]" value="<?php echo esc_attr( $g['free'] ); ?>"></label>
				</p>
				<p class="description" style="margin: 0 0 6px;"><?php esc_html_e( 'Min 0 = optional, Max 0 = unlimited.', 'libre-bite' ); ?></p>
				<div style="max-height: 160px; overflow-y: auto;">
					<?php foreach ( $options as $option ) : ?>
						<label style="display: inline-block; margin: 0 12px 4px 0;">
							<input type="checkbox" name="<?php echo esc_attr( $n ); ?>[option_ids][]" value="<?php echo esc_attr( $option->ID ); ?>" <?php checked( in_array( (int) $option->ID, array_map( 'intval', (array) $g['option_ids'] ), true ) ); ?>>
							<?php echo esc_html( $option->post_title ); ?>
						</label>
					<?php endforeach; ?>
				</div>
				<?php if ( '' !== $g['id'] ) : ?>
					<p style="margin-bottom: 0;"><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[remove]" value="1"> <?php esc_html_e( 'Remove group', 'libre-bite' ); ?></label></p>
				<?php endif; ?>
			</fieldset>
		<?php endforeach; ?>
		<?php
	}

	/**
	 * Gruppen aus der Produkt-Metabox speichern (Nonce und Rechte prüft der Aufrufer).
	 *
	 * @param int   $post_id Produkt-ID.
	 * @param mixed $raw     Formulardaten.
	 */
	public static function save( $post_id, $raw ) {
		$groups = self::sanitize_groups( $raw );

		if ( empty( $groups ) ) {
			delete_post_meta( $post_id, self::META );
			return;
		}

		update_post_meta( $post_id, self::META, $groups );
	}
}
