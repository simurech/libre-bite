<?php
/**
 * Zentrale Standort-Zugriffsprüfung
 *
 * Bündelt die Standort-Einschränkung, die bisher nur die REST-API
 * (`lbite/v1`) und die Statistik-Seite kannten (Audit 26.09.2026, AP-07).
 * Über AJAX konnte Personal bislang Bestellungen jedes Standorts sehen,
 * ändern, stornieren und Belege mit Kundendaten jeder WooCommerce-
 * Bestellung abrufen.
 *
 * Semantik von "leer": kein Eintrag in `lbite_assigned_locations` bedeutet
 * "alle Standorte" - konsistent mit der bisherigen REST-API und der UI
 * ("Uneingeschränkter Zugriff" bei leerer Zuweisung).
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Zugriffsprüfung Klasse
 */
class LBite_Access {

	/**
	 * Standorte, auf die ein Benutzer zugreifen darf.
	 *
	 * Personal (`lbite_assigned_location`, einzelner fester Standort) und
	 * Manager (`lbite_assigned_locations`, mehrere) nutzen unterschiedliche
	 * Meta-Felder - ein Checkbox-Raster für Personal würde ins Leere
	 * schreiben (siehe Nachtrag 2026-09-21 in CLAUDE.md).
	 *
	 * @param int|null $user_id Benutzer-ID, Standard: aktueller Benutzer.
	 * @return int[]|null Liste erlaubter IDs oder null für "alle".
	 */
	public static function get_allowed_location_ids( $user_id = null ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();

		if ( user_can( $user_id, 'manage_options' ) ) {
			return null;
		}

		$staff_location = (int) get_user_meta( $user_id, 'lbite_assigned_location', true );
		if ( $staff_location > 0 ) {
			return array( $staff_location );
		}

		$assigned = get_user_meta( $user_id, 'lbite_assigned_locations', true );

		if ( empty( $assigned ) || ! is_array( $assigned ) ) {
			return null;
		}

		return array_values( array_filter( array_map( 'absint', $assigned ) ) );
	}

	/**
	 * Zugriff auf einen bestimmten Standort prüfen.
	 *
	 * @param int      $location_id Standort-ID.
	 * @param int|null $user_id     Benutzer-ID, Standard: aktueller Benutzer.
	 * @return bool
	 */
	public static function can_access_location( $location_id, $user_id = null ) {
		$allowed = self::get_allowed_location_ids( $user_id );

		return null === $allowed || in_array( (int) $location_id, $allowed, true );
	}

	/**
	 * Zugriff auf eine Bestellung prüfen.
	 *
	 * Lehnt zusätzlich Bestellungen ohne `_lbite_location_id` ab, damit sich
	 * über diesen Weg keine beliebige WooCommerce-Bestellung (Belege,
	 * Kundendaten) abrufen lässt, die nie über Libre Bite lief.
	 *
	 * @param WC_Order|int $order   Bestellung oder Bestell-ID.
	 * @param int|null     $user_id Benutzer-ID, Standard: aktueller Benutzer.
	 * @return bool
	 */
	public static function can_access_order( $order, $user_id = null ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( (int) $order );
		}

		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		$location_id = (int) $order->get_meta( '_lbite_location_id', true );

		if ( ! $location_id ) {
			return false;
		}

		return self::can_access_location( $location_id, $user_id );
	}
}
