<?php
/**
 * Zeitgesteuerte Verfügbarkeit von Produkten und Kategorien
 *
 * Bildet ab, was Gastronomie-Speisekarten von Handelswaren unterscheidet:
 * ein Frühstücksangebot gilt Mo–Fr von 07:00 bis 11:30, ein Saisonartikel nur
 * in einem Datumsfenster. Beides war bisher nicht abbildbar – Wochentag mal
 * Uhrzeit gab es nur pro Standort, nicht pro Artikel.
 *
 * Bewusst EIN Zeitfenster pro Regel statt beliebig vieler: das deckt die
 * realen Fälle ab und bleibt im Backend bedienbar. Wer zwei getrennte Fenster
 * am selben Tag braucht, legt zwei Kategorien an.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse LBite_Menu_Schedule
 */
class LBite_Menu_Schedule {

	/**
	 * Meta-Key auf Produkten und Kategorie-Terms.
	 */
	const META_KEY = '_lbite_menu_schedule';

	/**
	 * Wochentagsschlüssel in der Reihenfolge von date( 'N' ) − 1.
	 *
	 * @var string[]
	 */
	const DAYS = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );

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

		// Produkt-Metabox
		$this->loader->add_action( 'add_meta_boxes', $this, 'add_product_meta_box' );
		$this->loader->add_action( 'save_post_product', $this, 'save_product_schedule', 10, 2 );

		// Kategorie-Felder
		$this->loader->add_action( 'product_cat_add_form_fields', $this, 'render_term_add_field' );
		$this->loader->add_action( 'product_cat_edit_form_fields', $this, 'render_term_edit_field' );
		$this->loader->add_action( 'created_product_cat', $this, 'save_term_schedule' );
		$this->loader->add_action( 'edited_product_cat', $this, 'save_term_schedule' );

		// Durchsetzung im Shop
		$this->loader->add_filter( 'woocommerce_is_purchasable', $this, 'filter_is_purchasable', 10, 2 );
		$this->loader->add_filter( 'woocommerce_product_is_visible', $this, 'filter_is_visible', 10, 2 );
		$this->loader->add_filter( 'woocommerce_add_to_cart_validation', $this, 'validate_add_to_cart', 10, 2 );
		$this->loader->add_action( 'woocommerce_check_cart_items', $this, 'validate_cart_items' );
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Kern: Auswertung
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Leeren Zeitplan mit Standardwerten liefern
	 *
	 * @return array
	 */
	public static function get_default_schedule() {
		return array(
			'enabled'   => false,
			'days'      => array_fill_keys( self::DAYS, true ),
			'from'      => '',
			'to'        => '',
			'from_date' => '',
			'to_date'   => '',
		);
	}

	/**
	 * Rohdaten in einen gültigen Zeitplan überführen
	 *
	 * Akzeptiert sowohl gespeicherte Werte als auch Formulareingaben.
	 *
	 * @param mixed $raw Rohwert.
	 * @return array
	 */
	public static function sanitize_schedule( $raw ) {
		$schedule = self::get_default_schedule();

		if ( ! is_array( $raw ) ) {
			return $schedule;
		}

		$schedule['enabled'] = ! empty( $raw['enabled'] );

		if ( isset( $raw['days'] ) && is_array( $raw['days'] ) ) {
			foreach ( self::DAYS as $day ) {
				$schedule['days'][ $day ] = ! empty( $raw['days'][ $day ] );
			}
		}

		foreach ( array( 'from', 'to' ) as $key ) {
			$value = isset( $raw[ $key ] ) ? trim( (string) $raw[ $key ] ) : '';
			$schedule[ $key ] = preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $value ) ? $value : '';
		}

		foreach ( array( 'from_date', 'to_date' ) as $key ) {
			$value = isset( $raw[ $key ] ) ? trim( (string) $raw[ $key ] ) : '';
			$schedule[ $key ] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
		}

		return $schedule;
	}

	/**
	 * Ist ein Zeitplan zum gegebenen Zeitpunkt aktiv?
	 *
	 * Reine Funktion ohne Seiteneffekte – der Zeitpunkt wird übergeben, damit
	 * das Verhalten testbar bleibt und nicht von der Serveruhr abhängt.
	 *
	 * Ein Zeitfenster darf über Mitternacht laufen (22:00–02:00). Der
	 * Wochentag wird dabei am **Beginn** des Fensters geprüft: ein Fenster
	 * «Freitag 22:00–02:00» gilt also bis Samstag früh.
	 *
	 * @param mixed $schedule  Zeitplan (roh oder bereinigt).
	 * @param int   $timestamp Zu prüfender Zeitpunkt (lokale Zeit).
	 * @return bool
	 */
	public static function is_active( $schedule, $timestamp ) {
		$schedule = self::sanitize_schedule( $schedule );

		// Ohne aktivierten Zeitplan gilt keine Einschränkung.
		if ( empty( $schedule['enabled'] ) ) {
			return true;
		}

		$date = gmdate( 'Y-m-d', $timestamp );

		// Datumsfenster
		if ( '' !== $schedule['from_date'] && $date < $schedule['from_date'] ) {
			return false;
		}
		if ( '' !== $schedule['to_date'] && $date > $schedule['to_date'] ) {
			return false;
		}

		$minutes_now = ( (int) gmdate( 'H', $timestamp ) * 60 ) + (int) gmdate( 'i', $timestamp );
		$day_index   = (int) gmdate( 'N', $timestamp ) - 1;
		$today       = self::DAYS[ $day_index ];
		$yesterday   = self::DAYS[ ( $day_index + 6 ) % 7 ];

		// Ohne Uhrzeitfenster zählt nur der Wochentag.
		if ( '' === $schedule['from'] || '' === $schedule['to'] ) {
			return ! empty( $schedule['days'][ $today ] );
		}

		$from = self::to_minutes( $schedule['from'] );
		$to   = self::to_minutes( $schedule['to'] );

		if ( $from === $to ) {
			// Gleiche Start- und Endzeit ergibt kein sinnvolles Fenster –
			// als "ganzer Tag" behandeln statt als "nie".
			return ! empty( $schedule['days'][ $today ] );
		}

		if ( $from < $to ) {
			// Normales Fenster innerhalb eines Tages.
			return ! empty( $schedule['days'][ $today ] )
				&& $minutes_now >= $from
				&& $minutes_now < $to;
		}

		// Fenster über Mitternacht: entweder heute nach dem Start …
		if ( ! empty( $schedule['days'][ $today ] ) && $minutes_now >= $from ) {
			return true;
		}

		// … oder heute früh als Ausläufer des gestrigen Fensters.
		return ! empty( $schedule['days'][ $yesterday ] ) && $minutes_now < $to;
	}

	/**
	 * "HH:MM" in Minuten seit Mitternacht
	 *
	 * @param string $time Zeitangabe.
	 * @return int
	 */
	private static function to_minutes( $time ) {
		$parts = explode( ':', (string) $time );

		return ( (int) $parts[0] * 60 ) + ( isset( $parts[1] ) ? (int) $parts[1] : 0 );
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Auflösung Produkt ↔ Kategorie
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Zeitplan eines Produkts lesen
	 *
	 * @param int $product_id Produkt-ID.
	 * @return array
	 */
	public static function get_product_schedule( $product_id ) {
		return self::sanitize_schedule( get_post_meta( (int) $product_id, self::META_KEY, true ) );
	}

	/**
	 * Zeitplan einer Kategorie lesen
	 *
	 * @param int $term_id Term-ID.
	 * @return array
	 */
	public static function get_term_schedule( $term_id ) {
		return self::sanitize_schedule( get_term_meta( (int) $term_id, self::META_KEY, true ) );
	}

	/**
	 * Ist ein Produkt gerade verfügbar?
	 *
	 * Ein eigener Produkt-Zeitplan hat Vorrang. Fehlt er, entscheiden die
	 * Kategorien: Sobald mindestens eine Kategorie des Produkts einen aktiven
	 * Zeitplan hat, genügt eine davon, um das Produkt anzuzeigen. Kategorien
	 * ohne Zeitplan schränken nichts ein.
	 *
	 * @param int      $product_id Produkt-ID.
	 * @param int|null $timestamp  Zeitpunkt, Standard: jetzt (lokale Zeit).
	 * @return bool
	 */
	public static function is_product_available( $product_id, $timestamp = null ) {
		$product_id = (int) $product_id;

		if ( ! $product_id ) {
			return true;
		}

		if ( null === $timestamp ) {
			$timestamp = (int) current_time( 'timestamp' );
		}

		$own = self::get_product_schedule( $product_id );

		if ( ! empty( $own['enabled'] ) ) {
			return self::is_active( $own, $timestamp );
		}

		// Variationen erben vom übergeordneten Produkt.
		$parent_id = (int) wp_get_post_parent_id( $product_id );
		if ( $parent_id ) {
			$parent = self::get_product_schedule( $parent_id );
			if ( ! empty( $parent['enabled'] ) ) {
				return self::is_active( $parent, $timestamp );
			}
			$product_id = $parent_id;
		}

		$terms = get_the_terms( $product_id, 'product_cat' );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return true;
		}

		$has_scheduled_term = false;

		foreach ( $terms as $term ) {
			$schedule = self::get_term_schedule( $term->term_id );

			if ( empty( $schedule['enabled'] ) ) {
				continue;
			}

			$has_scheduled_term = true;

			if ( self::is_active( $schedule, $timestamp ) ) {
				return true;
			}
		}

		return ! $has_scheduled_term;
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Durchsetzung im Shop
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Nicht verfügbare Produkte sind nicht kaufbar
	 *
	 * @param bool       $purchasable Bisheriger Wert.
	 * @param WC_Product $product     Produkt.
	 * @return bool
	 */
	public function filter_is_purchasable( $purchasable, $product ) {
		if ( ! $purchasable || is_admin() ) {
			return $purchasable;
		}

		return self::is_product_available( $product->get_id() );
	}

	/**
	 * Nicht verfügbare Produkte im Katalog ausblenden
	 *
	 * @param bool $visible    Bisheriger Wert.
	 * @param int  $product_id Produkt-ID.
	 * @return bool
	 */
	public function filter_is_visible( $visible, $product_id ) {
		if ( ! $visible || is_admin() ) {
			return $visible;
		}

		return self::is_product_available( $product_id );
	}

	/**
	 * Hinzufügen zum Warenkorb ausserhalb der Zeit verhindern
	 *
	 * @param bool $passed     Bisheriges Ergebnis.
	 * @param int  $product_id Produkt-ID.
	 * @return bool
	 */
	public function validate_add_to_cart( $passed, $product_id ) {
		if ( ! $passed ) {
			return $passed;
		}

		if ( self::is_product_available( $product_id ) ) {
			return true;
		}

		wc_add_notice( self::get_unavailable_notice( $product_id ), 'error' );

		return false;
	}

	/**
	 * Warenkorb erneut prüfen
	 *
	 * Schliesst die Lücke, dass ein Zeitfenster ablaufen kann, während
	 * Artikel bereits im Warenkorb liegen. Bisher wurde nur beim Hinzufügen
	 * geprüft.
	 */
	public function validate_cart_items() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product_id = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;

			if ( ! $product_id || self::is_product_available( $product_id ) ) {
				continue;
			}

			wc_add_notice( self::get_unavailable_notice( $product_id ), 'error' );
		}
	}

	/**
	 * Hinweistext für ein nicht verfügbares Produkt
	 *
	 * @param int $product_id Produkt-ID.
	 * @return string
	 */
	private static function get_unavailable_notice( $product_id ) {
		return sprintf(
			/* translators: %s: product name */
			__( '"%s" is not available at the moment.', 'libre-bite' ),
			get_the_title( $product_id )
		);
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Oberfläche
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Metabox am Produkt registrieren
	 */
	public function add_product_meta_box() {
		add_meta_box(
			'lbite_menu_schedule',
			__( 'Availability Schedule', 'libre-bite' ),
			array( $this, 'render_product_meta_box' ),
			'product',
			'side',
			'default'
		);
	}

	/**
	 * Metabox am Produkt ausgeben
	 *
	 * @param WP_Post $post Produkt.
	 */
	public function render_product_meta_box( $post ) {
		wp_nonce_field( 'lbite_menu_schedule_save', 'lbite_menu_schedule_nonce' );
		$this->render_fields( self::get_product_schedule( $post->ID ) );
	}

	/**
	 * Zeitplan eines Produkts speichern
	 *
	 * @param int     $post_id Produkt-ID.
	 * @param WP_Post $post    Produkt.
	 */
	public function save_product_schedule( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['lbite_menu_schedule_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lbite_menu_schedule_nonce'] ) ), 'lbite_menu_schedule_save' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw = isset( $_POST['lbite_menu_schedule'] ) && is_array( $_POST['lbite_menu_schedule'] )
			? wp_unslash( $_POST['lbite_menu_schedule'] )
			: array();

		update_post_meta( $post_id, self::META_KEY, self::sanitize_schedule( $raw ) );
	}

	/**
	 * Feld beim Anlegen einer Kategorie
	 */
	public function render_term_add_field() {
		wp_nonce_field( 'lbite_menu_schedule_save', 'lbite_menu_schedule_nonce' );
		?>
		<div class="form-field">
			<label><?php esc_html_e( 'Availability Schedule', 'libre-bite' ); ?></label>
			<?php $this->render_fields( self::get_default_schedule() ); ?>
		</div>
		<?php
	}

	/**
	 * Feld beim Bearbeiten einer Kategorie
	 *
	 * @param WP_Term $term Kategorie.
	 */
	public function render_term_edit_field( $term ) {
		wp_nonce_field( 'lbite_menu_schedule_save', 'lbite_menu_schedule_nonce' );
		?>
		<tr class="form-field">
			<th scope="row"><label><?php esc_html_e( 'Availability Schedule', 'libre-bite' ); ?></label></th>
			<td><?php $this->render_fields( self::get_term_schedule( $term->term_id ) ); ?></td>
		</tr>
		<?php
	}

	/**
	 * Zeitplan einer Kategorie speichern
	 *
	 * @param int $term_id Term-ID.
	 */
	public function save_term_schedule( $term_id ) {
		if ( ! isset( $_POST['lbite_menu_schedule_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lbite_menu_schedule_nonce'] ) ), 'lbite_menu_schedule_save' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}

		$raw = isset( $_POST['lbite_menu_schedule'] ) && is_array( $_POST['lbite_menu_schedule'] )
			? wp_unslash( $_POST['lbite_menu_schedule'] )
			: array();

		update_term_meta( $term_id, self::META_KEY, self::sanitize_schedule( $raw ) );
	}

	/**
	 * Gemeinsame Felddarstellung für Produkt und Kategorie
	 *
	 * @param array $schedule Aktueller Zeitplan.
	 */
	private function render_fields( $schedule ) {
		$labels = array(
			'monday'    => __( 'Mon', 'libre-bite' ),
			'tuesday'   => __( 'Tue', 'libre-bite' ),
			'wednesday' => __( 'Wed', 'libre-bite' ),
			'thursday'  => __( 'Thu', 'libre-bite' ),
			'friday'    => __( 'Fri', 'libre-bite' ),
			'saturday'  => __( 'Sat', 'libre-bite' ),
			'sunday'    => __( 'Sun', 'libre-bite' ),
		);
		?>
		<div class="lbite-schedule-fields">
			<p>
				<label>
					<input type="checkbox" name="lbite_menu_schedule[enabled]" value="1" <?php checked( ! empty( $schedule['enabled'] ) ); ?>>
					<strong><?php esc_html_e( 'Limit availability to certain times', 'libre-bite' ); ?></strong>
				</label>
			</p>

			<p class="lbite-schedule-days">
				<?php foreach ( $labels as $lbite_day => $lbite_label ) : ?>
					<label style="display:inline-block; margin:0 8px 4px 0;">
						<input type="checkbox" name="lbite_menu_schedule[days][<?php echo esc_attr( $lbite_day ); ?>]" value="1" <?php checked( ! empty( $schedule['days'][ $lbite_day ] ) ); ?>>
						<?php echo esc_html( $lbite_label ); ?>
					</label>
				<?php endforeach; ?>
			</p>

			<p>
				<label><?php esc_html_e( 'From', 'libre-bite' ); ?>
					<input type="time" name="lbite_menu_schedule[from]" value="<?php echo esc_attr( $schedule['from'] ); ?>">
				</label>
				<label style="margin-left:8px;"><?php esc_html_e( 'To', 'libre-bite' ); ?>
					<input type="time" name="lbite_menu_schedule[to]" value="<?php echo esc_attr( $schedule['to'] ); ?>">
				</label>
			</p>
			<p class="description"><?php esc_html_e( 'Leave both times empty to limit by weekday only. A window may run past midnight, for example 22:00 to 02:00.', 'libre-bite' ); ?></p>

			<p>
				<label><?php esc_html_e( 'Only between', 'libre-bite' ); ?>
					<input type="date" name="lbite_menu_schedule[from_date]" value="<?php echo esc_attr( $schedule['from_date'] ); ?>">
				</label>
				<label style="margin-left:8px;"><?php esc_html_e( 'and', 'libre-bite' ); ?>
					<input type="date" name="lbite_menu_schedule[to_date]" value="<?php echo esc_attr( $schedule['to_date'] ); ?>">
				</label>
			</p>
			<p class="description"><?php esc_html_e( 'Optional date range, for seasonal items. Leave empty for no date limit.', 'libre-bite' ); ?></p>
		</div>
		<?php
	}
}
