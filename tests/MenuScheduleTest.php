<?php
/**
 * Tests für LBite_Menu_Schedule::is_active().
 *
 * @package LibreBite
 */

use PHPUnit\Framework\TestCase;

class MenuScheduleTest extends TestCase {

	/**
	 * Zeitstempel für ein lokales Datum/Uhrzeit (die Auswertung rechnet mit gmdate()).
	 *
	 * @param string $datetime "Y-m-d H:i".
	 * @return int
	 */
	private function ts( $datetime ) {
		return (int) strtotime( $datetime . ' UTC' );
	}

	private function schedule( array $overrides ) {
		return array_merge( LBite_Menu_Schedule::get_default_schedule(), array( 'enabled' => true ), $overrides );
	}

	public function test_disabled_schedule_is_always_active() {
		$this->assertTrue( LBite_Menu_Schedule::is_active( array( 'enabled' => false ), $this->ts( '2026-11-25 03:00' ) ) );
	}

	public function test_window_within_one_day() {
		$s = $this->schedule( array( 'from' => '11:00', 'to' => '14:00' ) );

		$this->assertFalse( LBite_Menu_Schedule::is_active( $s, $this->ts( '2026-11-25 10:59' ) ) );
		$this->assertTrue( LBite_Menu_Schedule::is_active( $s, $this->ts( '2026-11-25 11:00' ) ) );
		$this->assertTrue( LBite_Menu_Schedule::is_active( $s, $this->ts( '2026-11-25 13:59' ) ) );
		$this->assertFalse( LBite_Menu_Schedule::is_active( $s, $this->ts( '2026-11-25 14:00' ) ), 'Endzeit ist exklusiv.' );
	}

	public function test_window_across_midnight_uses_previous_day() {
		// Nur freitags aktiv, 22:00–02:00.
		$days = array_fill_keys( LBite_Menu_Schedule::DAYS, false );
		$days['friday'] = true;
		$s = $this->schedule( array( 'from' => '22:00', 'to' => '02:00', 'days' => $days ) );

		// 2026-11-27 ist ein Freitag.
		$this->assertTrue( LBite_Menu_Schedule::is_active( $s, $this->ts( '2026-11-27 23:30' ) ) );
		$this->assertTrue( LBite_Menu_Schedule::is_active( $s, $this->ts( '2026-11-28 01:30' ) ), 'Samstagmorgen gehört zum Freitagsfenster.' );
		$this->assertFalse( LBite_Menu_Schedule::is_active( $s, $this->ts( '2026-11-28 23:30' ) ), 'Samstagabend ist nicht freigeschaltet.' );
		$this->assertFalse( LBite_Menu_Schedule::is_active( $s, $this->ts( '2026-11-27 01:30' ) ), 'Freitagmorgen gehört zum Donnerstag.' );
	}

	public function test_date_range_is_inclusive() {
		$s = $this->schedule( array( 'from_date' => '2026-12-01', 'to_date' => '2026-12-24' ) );

		$this->assertFalse( LBite_Menu_Schedule::is_active( $s, $this->ts( '2026-11-30 12:00' ) ) );
		$this->assertTrue( LBite_Menu_Schedule::is_active( $s, $this->ts( '2026-12-01 00:00' ) ) );
		$this->assertTrue( LBite_Menu_Schedule::is_active( $s, $this->ts( '2026-12-24 23:59' ) ) );
		$this->assertFalse( LBite_Menu_Schedule::is_active( $s, $this->ts( '2026-12-25 00:00' ) ) );
	}

	public function test_identical_start_and_end_means_whole_day() {
		$s = $this->schedule( array( 'from' => '09:00', 'to' => '09:00' ) );

		$this->assertTrue( LBite_Menu_Schedule::is_active( $s, $this->ts( '2026-11-25 03:00' ) ) );
	}

	public function test_weekday_only_without_times() {
		$days = array_fill_keys( LBite_Menu_Schedule::DAYS, true );
		$days['sunday'] = false;
		$s = $this->schedule( array( 'days' => $days ) );

		$this->assertTrue( LBite_Menu_Schedule::is_active( $s, $this->ts( '2026-11-28 12:00' ) ) ); // Samstag.
		$this->assertFalse( LBite_Menu_Schedule::is_active( $s, $this->ts( '2026-11-29 12:00' ) ) ); // Sonntag.
	}
}
