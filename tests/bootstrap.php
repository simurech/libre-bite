<?php
/**
 * PHPUnit-Bootstrap für die reinen Logik-Tests.
 *
 * Bewusst ohne WordPress-Testsuite und ohne Datenbank: Getestet werden nur
 * Funktionen, die ausschliesslich mit PHP-Bordmitteln rechnen (Zeitpläne,
 * Rappenrundung, Split-Payment-Abgleich). Die Klassendateien prüfen beim
 * Laden nur ABSPATH; mehr WordPress braucht es dafür nicht.
 *
 * @package LibreBite
 */

define( 'ABSPATH', __DIR__ . '/' );

$lbite_root = dirname( __DIR__ );

require_once $lbite_root . '/includes/modules/menu-schedule/class-menu-schedule.php';
require_once $lbite_root . '/includes/modules/promotions/class-promotions.php';
require_once $lbite_root . '/includes/modules/checkout/class-checkout.php';
require_once $lbite_root . '/includes/admin/class-admin.php';
