<?php
/**
 * Tests für die Zeitsteuerung der Ankündigungsleiste (mehrere Fenster pro Tag).
 *
 * @package LibreBite
 */

use PHPUnit\Framework\TestCase;

class BannerScheduleTest extends TestCase {

	private function ts( $datetime ) {
		return (int) strtotime( $datetime . ' UTC' );
	}

	public function test_invalid_times_are_dropped() {
		$clean = LBite_Promotions::sanitize_banner_schedule(
			array(
				'enabled' => '1',
				'windows' => array(
					array( 'from' => '08:00', 'to' => '12:00' ),
					array( 'from' => '25:00', 'to' => '12:00' ),
					array( 'from' => '14:00', 'to' => '' ),
				),
				'from_date' => '1.12.2026',
			)
		);

		$this->assertTrue( $clean['enabled'] );
		$this->assertCount( 1, $clean['windows'] );
		$this->assertSame( '', $clean['from_date'], 'Nur Y-m-d wird akzeptiert.' );
	}

	public function test_any_window_matches() {
		$s = array(
			'enabled' => true,
			'windows' => array(
				array( 'from' => '08:00', 'to' => '12:00' ),
				array( 'from' => '14:00', 'to' => '18:00' ),
			),
		);

		$this->assertTrue( LBite_Promotions::banner_schedule_is_active( $s, $this->ts( '2026-11-25 09:00' ) ) );
		$this->assertFalse( LBite_Promotions::banner_schedule_is_active( $s, $this->ts( '2026-11-25 13:00' ) ) );
		$this->assertTrue( LBite_Promotions::banner_schedule_is_active( $s, $this->ts( '2026-11-25 17:59' ) ) );
		$this->assertFalse( LBite_Promotions::banner_schedule_is_active( $s, $this->ts( '2026-11-25 18:00' ) ) );
	}

	public function test_disabled_banner_schedule_is_always_active() {
		$this->assertTrue( LBite_Promotions::banner_schedule_is_active( array(), $this->ts( '2026-11-25 03:00' ) ) );
	}
}
