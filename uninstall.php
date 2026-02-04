<?php
/**
 * Uninstall Libre Bite
 *
 * @package LibreBite
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Normalerweise übernimmt Freemius die Deinstallation über den 'after_uninstall' Hook.
 * Falls das Plugin jedoch ohne Freemius deinstalliert wird, führen wir hier 
 * den Standard-Cleanup aus.
 */
if ( file_exists( dirname( __FILE__ ) . '/includes/core/class-installer.php' ) ) {
	require_once dirname( __FILE__ ) . '/includes/core/class-installer.php';
	LB_Installer::uninstall_cleanup();
}