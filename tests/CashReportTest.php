<?php
/**
 * Tests für den Kassenabschluss (F25).
 *
 * @package LibreBite
 */

use PHPUnit\Framework\TestCase;

class CashReportTest extends TestCase {

	private function row( array $overrides ) {
		return array_merge(
			array(
				'status'   => 'paid',
				'total'    => 0,
				'payments' => array(),
				'tip'      => 0,
				'discount' => 0,
				'rounding' => 0,
				'taxes'    => array(),
			),
			$overrides
		);
	}

	public function test_sums_payments_taxes_and_cash() {
		$report = LBite_Cash_Reports::aggregate(
			array(
				$this->row( array( 'total' => 24.50, 'payments' => array( 'cash' => 24.50 ), 'taxes' => array( 'MWST 8.1%' => 1.84 ), 'rounding' => -0.02 ) ),
				$this->row( array( 'total' => 12.00, 'payments' => array( 'card' => 12.00 ), 'taxes' => array( 'MWST 2.6%' => 0.30 ), 'tip' => 2.00 ) ),
				// Split: Bar und Karte in einer Bestellung.
				$this->row( array( 'total' => 30.00, 'payments' => array( 'cash' => 10.00, 'card' => 20.00 ), 'taxes' => array( 'MWST 8.1%' => 2.25 ), 'discount' => 3.00 ) ),
				// Online-Barzahlung bei Abholung zählt als Bargeld.
				$this->row( array( 'total' => 8.40, 'payments' => array( 'gateway:cod' => 8.40 ) ) ),
				$this->row( array( 'total' => 15.00, 'payments' => array( 'gateway:stripe' => 15.00 ) ) ),
			),
			200,
			array( 'cash', 'gateway:cod' )
		);

		$this->assertSame( 5, $report['orders'] );
		$this->assertEqualsWithDelta( 89.90, $report['gross'], 0.001 );
		$this->assertEqualsWithDelta( 32.00, $report['payments']['card'], 0.001 );
		$this->assertEqualsWithDelta( 34.50, $report['payments']['cash'], 0.001 );
		$this->assertEqualsWithDelta( 4.09, $report['taxes']['MWST 8.1%'], 0.001 );
		$this->assertEqualsWithDelta( 42.90, $report['cash_sales'], 0.001 );
		$this->assertEqualsWithDelta( 242.90, $report['expected_cash'], 0.001 );
		$this->assertEqualsWithDelta( 2.00, $report['tips'], 0.001 );
		$this->assertEqualsWithDelta( 3.00, $report['discounts'], 0.001 );
		$this->assertEqualsWithDelta( -0.02, $report['rounding'], 0.001 );
	}

	public function test_cancelled_orders_are_counted_separately() {
		$report = LBite_Cash_Reports::aggregate(
			array(
				$this->row( array( 'total' => 10.00, 'payments' => array( 'cash' => 10.00 ) ) ),
				$this->row( array( 'status' => 'cancelled', 'total' => 7.50, 'payments' => array( 'cash' => 7.50 ) ) ),
			),
			0,
			array( 'cash' )
		);

		$this->assertSame( 1, $report['orders'] );
		$this->assertEqualsWithDelta( 10.00, $report['gross'], 0.001 );
		$this->assertSame( 1, $report['cancelled_count'] );
		$this->assertEqualsWithDelta( 7.50, $report['cancelled_total'], 0.001 );
		$this->assertEqualsWithDelta( 10.00, $report['cash_sales'], 0.001, 'Stornierte Barbeträge gehören nicht in die Kasse.' );
	}

	public function test_floating_point_noise_is_rounded() {
		$rows = array();
		for ( $i = 0; $i < 10; $i++ ) {
			$rows[] = $this->row( array( 'total' => 0.1, 'payments' => array( 'cash' => 0.1 ) ) );
		}
		$report = LBite_Cash_Reports::aggregate( $rows, 0, array( 'cash' ) );

		$this->assertSame( 1.0, $report['gross'] );
		$this->assertSame( 1.0, $report['cash_sales'] );
	}

	public function test_difference() {
		$this->assertNull( LBite_Cash_Reports::difference( null, 100 ) );
		$this->assertNull( LBite_Cash_Reports::difference( '', 100 ) );
		$this->assertSame( -0.5, LBite_Cash_Reports::difference( 99.5, 100 ) );
		$this->assertSame( 0.0, LBite_Cash_Reports::difference( 100, 100 ) );
	}
}
