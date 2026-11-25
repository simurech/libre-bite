<?php
/**
 * Tests für Rappenrundung und Split-Payment-Abgleich.
 *
 * Beide Methoden sind privat und werden per Reflection aufgerufen: Sie
 * entscheiden über Geldbeträge und sollen unabhängig von WooCommerce-Objekten
 * abgesichert sein.
 *
 * @package LibreBite
 */

use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase {

	private function rounding( $total ) {
		$m = new ReflectionMethod( 'LBite_Checkout', 'calculate_rounding_amount' );
		if ( PHP_VERSION_ID < 80100 ) { // Ab PHP 8.1 wirkungslos, ab 8.5 veraltet.
			$m->setAccessible( true );
		}

		return $m->invoke( null, $total );
	}

	private function reconcile( array $rows, $total ) {
		$admin = ( new ReflectionClass( 'LBite_Admin' ) )->newInstanceWithoutConstructor();
		$m     = new ReflectionMethod( 'LBite_Admin', 'reconcile_split_payments' );
		if ( PHP_VERSION_ID < 80100 ) { // Ab PHP 8.1 wirkungslos, ab 8.5 veraltet.
			$m->setAccessible( true );
		}

		return $m->invoke( $admin, $rows, $total );
	}

	/**
	 * @dataProvider rounding_cases
	 */
	public function test_rounds_to_five_cents( $total, $expected_total ) {
		$this->assertEqualsWithDelta( $expected_total, $total + $this->rounding( $total ), 0.0001 );
	}

	public function rounding_cases() {
		return array(
			'abrunden 1–2 Rappen'  => array( 12.32, 12.30 ),
			'aufrunden 3–4 Rappen' => array( 12.33, 12.35 ),
			'genau 5 Rappen'       => array( 12.35, 12.35 ),
			'2.5 Rappen'           => array( 0.025, 0.05 ),
			'aufrunden 8 Rappen'   => array( 9.98, 10.00 ),
		);
	}

	public function test_no_fee_for_floating_point_noise() {
		$this->assertSame( 0.0, $this->rounding( 0.1 + 0.2 + 4.70 ) );
	}

	public function test_split_matching_total_is_unchanged() {
		$rows = array(
			array( 'method' => 'cash', 'amount' => 20.00 ),
			array( 'method' => 'card', 'amount' => 15.50 ),
		);

		$this->assertSame( $rows, $this->reconcile( $rows, 35.50 ) );
	}

	public function test_small_difference_goes_to_largest_row() {
		$result = $this->reconcile(
			array(
				array( 'method' => 'cash', 'amount' => 10.00 ),
				array( 'method' => 'card', 'amount' => 25.47 ),
			),
			35.45
		);

		$this->assertEqualsWithDelta( 10.00, $result[0]['amount'], 0.0001 );
		$this->assertEqualsWithDelta( 25.45, $result[1]['amount'], 0.0001 );
	}

	public function test_difference_above_ten_cents_is_rejected() {
		$this->assertNull(
			$this->reconcile(
				array( array( 'method' => 'cash', 'amount' => 30.00 ) ),
				30.20
			)
		);
	}
}
