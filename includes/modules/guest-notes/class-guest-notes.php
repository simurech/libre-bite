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
	 * Meta-Schlüssel für die normalisierte Telefonnummer (siehe normalize_phone()).
	 *
	 * Immer synchron zu billing_phone gehalten (sync_normalized_phone()) -
	 * erlaubt find_customer_by_phone() eine exakte statt einer nicht
	 * indexierbaren LIKE-Suche über alle Benutzer (Audit 26.09.2026, AP-21).
	 */
	const META_PHONE_NORMALIZED = '_lbite_phone_normalized';

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

		// Normalisierte Telefonnummer synchron zu billing_phone halten.
		$this->loader->add_action( 'added_user_meta', $this, 'maybe_sync_normalized_phone', 10, 4 );
		$this->loader->add_action( 'updated_user_meta', $this, 'maybe_sync_normalized_phone', 10, 4 );

		// Notizen und Allergiehinweise sind personenbezogene (bei
		// Allergien sogar gesundheitsbezogene) Daten - ohne diese beiden
		// Filter tauchten sie in Werkzeuge → Persönliche Daten weder im
		// Export noch bei der Löschung auf (Audit 26.09.2026, AP-16).
		$this->loader->add_filter( 'wp_privacy_personal_data_exporters', $this, 'register_privacy_exporter' );
		$this->loader->add_filter( 'wp_privacy_personal_data_erasers', $this, 'register_privacy_eraser' );
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
	 * Normalisierte Telefonnummer synchron zu billing_phone halten.
	 *
	 * @param int    $meta_id    Meta-ID (ungenutzt).
	 * @param int    $user_id    Benutzer-ID.
	 * @param string $meta_key   Geänderter Meta-Key.
	 * @param mixed  $meta_value Neuer Wert.
	 */
	public function maybe_sync_normalized_phone( $meta_id, $user_id, $meta_key, $meta_value ) {
		if ( 'billing_phone' !== $meta_key ) {
			return;
		}

		update_user_meta( $user_id, self::META_PHONE_NORMALIZED, self::normalize_phone( $meta_value ) );
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

		// Exakter Abgleich über die normalisierte Nummer statt eines LIKE
		// über alle billing_phone-Werte - ein LIKE mit Wildcard auf beiden
		// Seiten kann keinen Index nutzen und wird mit wachsendem
		// Kundenstamm zunehmend langsamer (Audit 26.09.2026, AP-21).
		$users = get_users(
			array(
				'number'     => 5,
				'fields'     => 'ID',
				'meta_key'   => self::META_PHONE_NORMALIZED, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Exakter Abgleich, auf 5 Treffer begrenzt.
				'meta_value' => $normalized, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- s.o.
			)
		);

		$found = ! empty( $users ) ? (int) $users[0] : 0;

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
	 * Datenschutz (Persönliche Daten exportieren/löschen)
	 * ═════════════════════════════════════════════════════════════════ */

	/**
	 * Exporteur registrieren
	 *
	 * @param array $exporters Bestehende Exporteure.
	 * @return array
	 */
	public function register_privacy_exporter( $exporters ) {
		$exporters['lbite-guest-notes'] = array(
			'exporter_friendly_name' => __( 'Libre Bite Guest Notes', 'libre-bite' ),
			'callback'               => array( $this, 'export_data' ),
		);
		return $exporters;
	}

	/**
	 * Daten für den Export zusammenstellen
	 *
	 * @param string $email_address E-Mail-Adresse der betroffenen Person.
	 * @return array
	 */
	public function export_data( $email_address ) {
		$user        = get_user_by( 'email', $email_address );
		$export_data = array();

		if ( $user ) {
			$notes = self::get_notes( $user->ID );
			$items = array();

			if ( '' !== $notes['notes'] ) {
				$items[] = array( 'name' => __( 'Notes', 'libre-bite' ), 'value' => $notes['notes'] );
			}
			if ( '' !== $notes['allergies'] ) {
				$items[] = array( 'name' => __( 'Allergies', 'libre-bite' ), 'value' => $notes['allergies'] );
			}

			if ( ! empty( $items ) ) {
				$export_data[] = array(
					'group_id'    => 'lbite-guest-notes',
					'group_label' => __( 'Guest Notes', 'libre-bite' ),
					'item_id'     => 'lbite-guest-notes',
					'data'        => $items,
				);
			}
		}

		return array(
			'data' => $export_data,
			'done' => true,
		);
	}

	/**
	 * Eraser registrieren
	 *
	 * @param array $erasers Bestehende Eraser.
	 * @return array
	 */
	public function register_privacy_eraser( $erasers ) {
		$erasers['lbite-guest-notes'] = array(
			'eraser_friendly_name' => __( 'Libre Bite Guest Notes', 'libre-bite' ),
			'callback'             => array( $this, 'erase_data' ),
		);
		return $erasers;
	}

	/**
	 * Notizen und Allergiehinweise löschen
	 *
	 * @param string $email_address E-Mail-Adresse der betroffenen Person.
	 * @return array
	 */
	public function erase_data( $email_address ) {
		$user          = get_user_by( 'email', $email_address );
		$items_removed = false;

		if ( $user ) {
			if ( '' !== (string) get_user_meta( $user->ID, self::META_NOTES, true ) ) {
				delete_user_meta( $user->ID, self::META_NOTES );
				$items_removed = true;
			}
			if ( '' !== (string) get_user_meta( $user->ID, self::META_ALLERGIES, true ) ) {
				delete_user_meta( $user->ID, self::META_ALLERGIES );
				$items_removed = true;
			}
		}

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
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
