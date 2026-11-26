<?php
/**
 * Tests für die Regeln der Modifikator-Gruppen (F39).
 *
 * @package LibreBite
 */

use PHPUnit\Framework\TestCase;

class OptionGroupsTest extends TestCase {

	private $prices = array(
		1 => 0.0, // Ketchup
		2 => 0.5, // Mayo
		3 => 1.0, // Cheddar
		4 => 1.5, // Bacon
		5 => 2.0, // Avocado
		6 => 0.8, // Extra Brot (ohne Gruppe)
	);

	private function groups() {
		return array(
			array( 'id' => 'sauce', 'type' => 'single', 'min' => 1, 'max' => 1, 'free' => 0, 'option_ids' => array( 1, 2 ) ),
			array( 'id' => 'top', 'type' => 'multiple', 'min' => 0, 'max' => 3, 'free' => 2, 'option_ids' => array( 3, 4, 5 ) ),
		);
	}

	public function test_required_single_choice_missing() {
		$r = LBite_Option_Groups::evaluate( $this->groups(), $this->prices, array( 3 ) );
		$this->assertFalse( $r['valid'] );
		$this->assertSame( 'sauce', $r['group'] );
		$this->assertSame( 'min', $r['reason'] );
	}

	public function test_single_choice_allows_only_one() {
		$r = LBite_Option_Groups::evaluate( $this->groups(), $this->prices, array( 1, 2 ) );
		$this->assertFalse( $r['valid'] );
		$this->assertSame( 'max', $r['reason'] );
	}

	public function test_cheapest_choices_are_included() {
		// Mayo 0.50 + drei Toppings (Maximum): Cheddar und Bacon gratis, Avocado 2.00 kostet.
		$r = LBite_Option_Groups::evaluate( $this->groups(), $this->prices, array( 2, 3, 4, 5 ) );
		$this->assertTrue( $r['valid'] );
		$this->assertSame( 2.5, $r['extra'] );

		// Mayo + Cheddar + Avocado: Cheddar und Avocado wären 3.00, zwei inklusive -> nur Mayo kostet.
		$r = LBite_Option_Groups::evaluate( $this->groups(), $this->prices, array( 2, 3, 5 ) );
		$this->assertTrue( $r['valid'] );
		$this->assertSame( 0.5, $r['extra'] );
		$this->assertSame( 0.0, $r['charged'][3] );
		$this->assertSame( 0.0, $r['charged'][5] );

		// Drei Toppings: die zwei günstigsten (Cheddar, Bacon) gratis, Avocado kostet.
		$r = LBite_Option_Groups::evaluate( $this->groups(), $this->prices, array( 1, 3, 4, 5 ) );
		$this->assertTrue( $r['valid'] );
		$this->assertSame( 2.0, $r['extra'] );
		$this->assertSame( 2.0, $r['charged'][5] );
	}

	public function test_unknown_option_is_rejected() {
		$r = LBite_Option_Groups::evaluate( $this->groups(), $this->prices, array( 1, 6 ) );
		$this->assertFalse( $r['valid'] );
		$this->assertSame( 'unknown', $r['reason'] );
	}

	public function test_open_group_without_rules_sums_prices() {
		$extras = array( array( 'id' => 'extras', 'type' => 'multiple', 'min' => 0, 'max' => 0, 'free' => 0, 'option_ids' => array( 2, 6 ) ) );
		$r      = LBite_Option_Groups::evaluate( $extras, $this->prices, array( 2, 6 ) );
		$this->assertTrue( $r['valid'] );
		$this->assertSame( 1.3, $r['extra'] );
	}

	public function test_duplicate_selection_counts_once() {
		$r = LBite_Option_Groups::evaluate( $this->groups(), $this->prices, array( 1, 1, 5, 5 ) );
		$this->assertTrue( $r['valid'] );
		$this->assertSame( 0.0, $r['extra'] );
	}
}
