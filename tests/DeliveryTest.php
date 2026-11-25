<?php
/**
 * Tests für Liefergebiet und Liefergebühr (F44).
 *
 * @package LibreBite
 */

use PHPUnit\Framework\TestCase;

class DeliveryTest extends TestCase {

	/**
	 * @dataProvider postcode_cases
	 */
	public function test_postcode_rules( $postcode, $rules, $expected ) {
		$this->assertSame( $expected, LBite_Delivery::postcode_matches( $postcode, $rules ) );
	}

	public function postcode_cases() {
		$rules = "8000\n80*\n8400-8499, 3011";

		return array(
			'exakt'                         => array( '8000', '8000', true ),
			'Präfix'                        => array( '8057', '80*', true ),
			'Präfix trifft nicht'           => array( '8100', '80*', false ),
			'Bereich innen'                 => array( '8450', '8400-8499', true ),
			'Bereich Grenze'                => array( '8499', '8400-8499', true ),
			'Bereich aussen'                => array( '8500', '8400-8499', false ),
			'Bereich nur gleiche Länge'     => array( '84500', '8400-8499', false ),
			'mehrere Regeln, Komma'         => array( '3011', $rules, true ),
			'Leerzeichen und Kleinschrift'  => array( 'sw1a 1aa', 'SW1A1AA', true ),
			'leere PLZ'                     => array( '', $rules, false ),
			'keine Regeln'                  => array( '8000', '', false ),
		);
	}

	public function test_fee_is_charged_below_free_threshold() {
		$this->assertSame( 5.0, LBite_Delivery::calculate_fee( 39.95, 5, 40 ) );
	}

	public function test_fee_is_free_from_threshold() {
		$this->assertSame( 0.0, LBite_Delivery::calculate_fee( 40.00, 5, 40 ) );
	}

	public function test_zero_threshold_means_never_free() {
		$this->assertSame( 5.0, LBite_Delivery::calculate_fee( 1000, 5, 0 ) );
	}

	public function test_negative_fee_is_ignored() {
		$this->assertSame( 0.0, LBite_Delivery::calculate_fee( 10, -3, 0 ) );
	}
}
