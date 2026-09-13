<?php
/**
 * Gäste-Notizen
 *
 * Hält Hinweise zu wiederkehrenden Gästen fest: Allergien, Vorlieben,
 * «sitzt gern am Fenster». Bisher ging solches Wissen zwischen zwei
 * Besuchen verloren, weil jede Reservierung und jede Bestellung für sich
 * stand.
 *
 * Bewusst klein gehalten: kein eigener Inhaltstyp, kein separates
 * Dashboard, keine Besuchshistorie. Die Notizen hängen am
 * WooCommerce-Kundenkonto und werden im Reservierungsboard über die
 * Telefonnummer zugeordnet — das ist die einzige Kennung, die dort
 * zuverlässig vorliegt.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse LBite_Guest_Notes
 */
class LBite_Guest_Notes {

	/**
	 * Meta-Schlüssel für den Notiztext.
	 */
	const META_NOTES = '_lbite_guest_notes';

	/**
	 * Meta-Schlüssel für Allergiehinweise.
	 */
	const META_ALLERGIES = '_lbite_guest_allergies';

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

		$this->loader->add_action( 'show_user_profile', $this, 'render_fields' );
		$this->loader->add_action( 'edit_user_profile', $this, 'render_fields' );
		$this->loader->add_action( 'personal_options_update', $this, 'save_fields' );
		$this->loader->add_action( 'edit_user_profile_update', $this, 'save_fields' );

		// Reservierungsboard mit Gastnotizen anreichern.
		$this->loader->add_filter( 'lbite_reservation_board_data', $this, 'add_notes_to_reservation', 10, 2 );
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Zuordnung
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Telefonnummer auf eine vergleichbare Form bringen
	 *
	 * «+41 79 123 45 67», «0791234567» und «079 123 45 67» sind dieselbe
	 * Nummer. Ohne Normalisierung würde ein Gast bei jeder Schreibweise als
	 * neuer Gast gelten.
	 *
	 * @param string $phone Rohe Telefonnummer.
	 * @return string Nur Ziffern, führende Null und Ländervorwahl entfernt.
	 */
	public static function normalize_phone( $phone ) {
		$digits = preg_replace( '/\D+/', '', (string) $phone );

		if ( '' === $digits ) {
			return '';
		}

		// Schweizer Nummern: 0041… und +41… auf die nationale Form bringen.
		if ( 0 === strpos( $digits, '0041' ) ) {
			$digits = substr( $digits, 4 );
		} elseif ( 0 === strpos( $digits, '41' ) && strlen( $digits ) > 9 ) {
			$digits = substr( $digits, 2 );
		}

		// Führende Null der nationalen Schreibweise entfernen.
		$digits = ltrim( $digits, '0' );

		return $digits;
	}

	/**
	 * Kunden über die Telefonnummer finden
	 *
	 * @param string $phone Telefonnummer.
	 * @return int Benutzer-ID oder 0.
	 */
	public static function find_customer_by_phone( $phone ) {
		$normalized = self::normalize_phone( $phone );

		if ( '' === $normalized || strlen( $normalized ) < 6 ) {
			return 0;
		}

		$cached = wp_cache_get( 'lbite_guest_' . $normalized, 'lbite' );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		// Über die Billing-Telefonnummer suchen. Ein LIKE auf die letzten
		// Ziffern ist hier zuverlässiger als ein exakter Vergleich, weil die
		// Schreibweisen im Datenbestand uneinheitlich sind.
		$users = get_users(
			array(
				'number'     => 20,
				'fields'     => 'ID',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Einzelabfrage bei Anzeige eines Reservierungs-Boards, auf 20 Treffer begrenzt.
				'meta_query' => array(
					array(
						'key'     => 'billing_phone',
						'value'   => substr( $normalized, -6 ),
						'compare' => 'LIKE',
					),
				),
			)
		);

		$found = 0;

		foreach ( $users as $user_id ) {
			$candidate = get_user_meta( $user_id, 'billing_phone', true );

			if ( self::normalize_phone( $candidate ) === $normalized ) {
				$found = (int) $user_id;
				break;
			}
		}

		wp_cache_set( 'lbite_guest_' . $normalized, $found, 'lbite', 5 * MINUTE_IN_SECONDS );

		return $found;
	}

	/**
	 * Notizen eines Kunden lesen
	 *
	 * @param int $user_id Benutzer-ID.
	 * @return array {
	 *     @type string $notes     Freitext.
	 *     @type string $allergies Allergiehinweise.
	 * }
	 */
	public static function get_notes( $user_id ) {
		return array(
			'notes'     => (string) get_user_meta( (int) $user_id, self::META_NOTES, true ),
			'allergies' => (string) get_user_meta( (int) $user_id, self::META_ALLERGIES, true ),
		);
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Benutzerprofil
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Felder im Benutzerprofil ausgeben
	 *
	 * @param WP_User $user Benutzer.
	 */
	public function render_fields( $user ) {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}

		$data = self::get_notes( $user->ID );
		?>
		<h2><?php esc_html_e( 'Guest notes (Libre Bite)', 'libre-bite' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="lbite_guest_allergies"><?php esc_html_e( 'Allergies and intolerances', 'libre-bite' ); ?></label></th>
				<td>
					<input type="text" id="lbite_guest_allergies" name="lbite_guest_allergies"
						value="<?php echo esc_attr( $data['allergies'] ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Shown to staff when this guest reserves a table. Keep it short and factual.', 'libre-bite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="lbite_guest_notes"><?php esc_html_e( 'Notes', 'libre-bite' ); ?></label></th>
				<td>
					<textarea id="lbite_guest_notes" name="lbite_guest_notes" rows="3" class="large-text"><?php echo esc_textarea( $data['notes'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Preferences worth remembering, for example a favourite table. Visible to your staff, never to the guest.', 'libre-bite' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Felder speichern
	 *
	 * @param int $user_id Benutzer-ID.
	 */
	public function save_fields( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		check_admin_referer( 'update-user_' . $user_id );

		if ( isset( $_POST['lbite_guest_allergies'] ) ) {
			update_user_meta(
				$user_id,
				self::META_ALLERGIES,
				sanitize_text_field( wp_unslash( $_POST['lbite_guest_allergies'] ) )
			);
		}

		if ( isset( $_POST['lbite_guest_notes'] ) ) {
			update_user_meta(
				$user_id,
				self::META_NOTES,
				sanitize_textarea_field( wp_unslash( $_POST['lbite_guest_notes'] ) )
			);
		}
	}

	/* ═════════════════════════════════════════════════════════════════
	 * Reservierungsboard
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Reservierungsdaten um Gastnotizen ergänzen
	 *
	 * @param array $data           Bisherige Daten.
	 * @param int   $reservation_id Reservierungs-ID.
	 * @return array
	 */
	public function add_notes_to_reservation( $data, $reservation_id ) {
		$phone = isset( $data['phone'] ) ? $data['phone'] : '';

		if ( '' === $phone ) {
			return $data;
		}

		$user_id = self::find_customer_by_phone( $phone );

		if ( ! $user_id ) {
			return $data;
		}

		$notes = self::get_notes( $user_id );

		$data['guest_known']     = true;
		$data['guest_notes']     = $notes['notes'];
		$data['guest_allergies'] = $notes['allergies'];

		return $data;
	}
}
