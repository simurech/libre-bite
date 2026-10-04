<?php
/**
 * Zentrale Branding-Farblogik
 *
 * Bisher gab es zwei unabhängige Implementierungen derselben Farbtoken:
 * eine in LBite_Admin (Admin/POS) und eine separat nachgebaute in
 * LBite_Checkout::enqueue_frontend_assets() (Frontend) - mit leicht
 * unterschiedlichen Variablen und Aufhellungs-Algorithmen. LBite_Admin wird
 * nur geladen, wenn is_admin() zutrifft (class-plugin.php), ist im Frontend
 * also gar nicht verfügbar; diese Klasse wird deshalb immer geladen und ist
 * die einzige Quelle für beide Seiten (Nutzer-Fund 2026-09-28, Kontrast- und
 * Cache-Bugs in der Menü-Ansicht).
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Branding-Farbtoken-Klasse
 */
class LBite_Branding {

	/**
	 * Alle Branding-CSS-Variablen als Array (Name ohne führende --, Wert) berechnen.
	 *
	 * @return array<string,string>
	 */
	public static function get_tokens() {
		$lbite_primary   = sanitize_hex_color( (string) get_option( 'lbite_color_primary', '#0073aa' ) );
		$lbite_secondary = sanitize_hex_color( (string) get_option( 'lbite_color_secondary', '#23282d' ) );
		$lbite_accent    = sanitize_hex_color( (string) get_option( 'lbite_color_accent', '#00a32a' ) );

		$lbite_tokens = array();

		if ( $lbite_primary ) {
			$lbite_tokens['--lbite-color-primary']          = $lbite_primary;
			$lbite_tokens['--lbite-color-primary-hover']    = self::shade_hex_color( $lbite_primary, -0.18 );
			$lbite_tokens['--lbite-color-primary-soft']     = self::shade_hex_color( $lbite_primary, 0.92 );
			// Alias, von bestehendem Frontend-CSS unter diesem Namen erwartet
			// (identische Formel: 8 % Primärfarbe auf 92 % Weiss gemischt).
			$lbite_tokens['--lbite-color-primary-bg']       = $lbite_tokens['--lbite-color-primary-soft'];
			$lbite_tokens['--lbite-focus-ring']             = '0 0 0 2px var(--lbite-surface),0 0 0 4px ' . self::hex_to_rgba( $lbite_primary, 0.45 );
			$lbite_tokens['--lbite-color-primary-contrast'] = self::contrast_color( $lbite_primary );
		}

		if ( $lbite_secondary ) {
			$lbite_tokens['--lbite-color-secondary'] = $lbite_secondary;
			// Die Sekundärfarbe dient auch als Flächen-/Linienfarbe und darf hell
			// sein (z. B. #fefefe). Als Schriftfarbe auf hellem Grund braucht es
			// deshalb eine garantiert lesbare Variante.
			$lbite_tokens['--lbite-color-secondary-text'] = ( '#1d2327' === self::contrast_color( $lbite_secondary ) ) ? '#1d2327' : $lbite_secondary;
		}

		if ( $lbite_accent ) {
			$lbite_tokens['--lbite-color-accent']       = $lbite_accent;
			$lbite_tokens['--lbite-color-accent-hover'] = self::shade_hex_color( $lbite_accent, -0.18 );
		}

		return $lbite_tokens;
	}

	/**
	 * Branding-Token als fertigen :root{}-Block für wp_add_inline_style() ausgeben.
	 *
	 * @return string
	 */
	public static function get_inline_css() {
		$lbite_tokens = self::get_tokens();

		if ( empty( $lbite_tokens ) ) {
			return '';
		}

		$lbite_declarations = array();
		foreach ( $lbite_tokens as $lbite_name => $lbite_value ) {
			$lbite_declarations[] = $lbite_name . ':' . $lbite_value;
		}

		return ':root{' . implode( ';', $lbite_declarations ) . ';}';
	}

	/**
	 * Gut lesbare Textfarbe (schwarz/weiss) für einen Hintergrund berechnen.
	 *
	 * WCAG-Relativluminanz (sRGB -> linear), Schwelle nahe der 4.5:1-Grenze.
	 *
	 * @param string $hex Hex-Farbe inkl. führendem #.
	 * @return string Hex-Farbe für den Text (#1d2327 oder #ffffff).
	 */
	public static function contrast_color( $hex ) {
		$lbite_rgb = self::hex_to_rgb( $hex );

		if ( null === $lbite_rgb ) {
			return '#ffffff';
		}

		$lbite_linear = array();
		foreach ( $lbite_rgb as $lbite_channel ) {
			$lbite_c        = $lbite_channel / 255;
			$lbite_linear[] = ( $lbite_c <= 0.03928 ) ? ( $lbite_c / 12.92 ) : pow( ( $lbite_c + 0.055 ) / 1.055, 2.4 );
		}

		$lbite_luminance = ( 0.2126 * $lbite_linear[0] ) + ( 0.7152 * $lbite_linear[1] ) + ( 0.0722 * $lbite_linear[2] );

		return ( $lbite_luminance > 0.179 ) ? '#1d2327' : '#ffffff';
	}

	/**
	 * Hex-Farbe abdunkeln oder aufhellen
	 *
	 * @param string $hex    Hex-Farbe inkl. führendem #.
	 * @param float  $amount Negativ = abdunkeln, positiv = Richtung Weiss mischen (0..1).
	 * @return string Hex-Farbe inkl. führendem #.
	 */
	public static function shade_hex_color( $hex, $amount ) {
		$lbite_rgb = self::hex_to_rgb( $hex );

		if ( null === $lbite_rgb ) {
			return $hex;
		}

		foreach ( $lbite_rgb as $lbite_i => $lbite_channel ) {
			if ( $amount < 0 ) {
				$lbite_value = $lbite_channel * ( 1 + $amount );
			} else {
				$lbite_value = $lbite_channel + ( ( 255 - $lbite_channel ) * $amount );
			}

			$lbite_rgb[ $lbite_i ] = max( 0, min( 255, (int) round( $lbite_value ) ) );
		}

		return sprintf( '#%02x%02x%02x', $lbite_rgb[0], $lbite_rgb[1], $lbite_rgb[2] );
	}

	/**
	 * Hex-Farbe als rgba()-String
	 *
	 * @param string $hex   Hex-Farbe inkl. führendem #.
	 * @param float  $alpha Deckkraft 0..1.
	 * @return string
	 */
	public static function hex_to_rgba( $hex, $alpha ) {
		$lbite_rgb = self::hex_to_rgb( $hex );

		if ( null === $lbite_rgb ) {
			return 'rgba(0,115,170,' . $alpha . ')';
		}

		return sprintf( 'rgba(%d,%d,%d,%s)', $lbite_rgb[0], $lbite_rgb[1], $lbite_rgb[2], $alpha );
	}

	/**
	 * Hex-Farbe in RGB-Kanäle zerlegen
	 *
	 * @param string $hex Hex-Farbe inkl. führendem #.
	 * @return array|null Array mit drei Kanälen oder null bei ungültiger Eingabe.
	 */
	public static function hex_to_rgb( $hex ) {
		$lbite_hex = ltrim( (string) $hex, '#' );

		if ( 3 === strlen( $lbite_hex ) ) {
			$lbite_hex = $lbite_hex[0] . $lbite_hex[0] . $lbite_hex[1] . $lbite_hex[1] . $lbite_hex[2] . $lbite_hex[2];
		}

		if ( 6 !== strlen( $lbite_hex ) || ! ctype_xdigit( $lbite_hex ) ) {
			return null;
		}

		return array(
			hexdec( substr( $lbite_hex, 0, 2 ) ),
			hexdec( substr( $lbite_hex, 2, 2 ) ),
			hexdec( substr( $lbite_hex, 4, 2 ) ),
		);
	}
}
