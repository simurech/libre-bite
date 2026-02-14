<?php
/**
 * Hook/Action Loader
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verwaltet alle Hooks und Actions des Plugins
 */
class LBite_Loader {

	/**
	 * Actions
	 *
	 * @var array
	 */
	protected $actions = array();

	/**
	 * Filters
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Action hinzufügen
	 *
	 * @param string $hook          Hook-Name
	 * @param object $component     Komponenten-Objekt
	 * @param string $callback      Callback-Methode
	 * @param int    $priority      Priorität (Standard: 10)
	 * @param int    $accepted_args Anzahl Argumente (Standard: 1)
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Filter hinzufügen
	 *
	 * @param string $hook          Hook-Name
	 * @param object $component     Komponenten-Objekt
	 * @param string $callback      Callback-Methode
	 * @param int    $priority      Priorität (Standard: 10)
	 * @param int    $accepted_args Anzahl Argumente (Standard: 1)
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Hook zum Array hinzufügen
	 *
	 * @param array  $hooks         Hooks-Array
	 * @param string $hook          Hook-Name
	 * @param object $component     Komponenten-Objekt
	 * @param string $callback      Callback-Methode
	 * @param int    $priority      Priorität
	 * @param int    $accepted_args Anzahl Argumente
	 * @return array
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}

	/**
	 * Alle Hooks registrieren
	 */
	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}
