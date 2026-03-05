<?php
/**
 * Template: Einstellungen (Tabbed)
 *
 * Konsolidiert alle Einstellungsseiten in einer tabellierten Ansicht.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_admin = current_user_can( 'manage_options' );

// Aktiven Tab ermitteln
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Anzeigesteuerung, kein Datenschreibvorgang
$lbite_active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';

// Tabs definieren (Capability-abhängig)
$lbite_tabs = array(
	'general'  => __( 'Allgemein', 'libre-bite' ),
	'checkout' => __( 'Checkout-Felder', 'libre-bite' ),
);

if ( $is_admin ) {
	$lbite_tabs['features'] = __( 'Features', 'libre-bite' );
	$lbite_tabs['roles']    = __( 'Rollen & Menüs', 'libre-bite' );
	$lbite_tabs['support']  = __( 'Support', 'libre-bite' );
}

// Sicherstellen dass aktiver Tab existiert
if ( ! array_key_exists( $lbite_active_tab, $lbite_tabs ) ) {
	$lbite_active_tab = 'general';
}

$lbite_plugin_name = apply_filters( 'lbite_plugin_display_name', __( 'Libre Bite', 'libre-bite' ) );
$lbite_settings_url = admin_url( 'admin.php?page=lbite-settings' );
?>

<div class="wrap">
	<h1><?php echo esc_html( $lbite_plugin_name . ' – ' . __( 'Einstellungen', 'libre-bite' ) ); ?></h1>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $lbite_tabs as $lbite_tab_key => $lbite_tab_label ) : ?>
			<a
				href="<?php echo esc_url( add_query_arg( 'tab', $lbite_tab_key, $lbite_settings_url ) ); ?>"
				class="nav-tab <?php echo $lbite_active_tab === $lbite_tab_key ? 'nav-tab-active' : ''; ?>"
			>
				<?php echo esc_html( $lbite_tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="lbite-settings-tab-content">
		<?php
		// $lbite_is_tab = true verhindert doppelte <div class="wrap"> und <h1> in den Sub-Templates
		$lbite_is_tab = true;

		switch ( $lbite_active_tab ) {
			case 'general':
				include LBITE_PLUGIN_DIR . 'templates/admin/settings.php';
				break;

			case 'checkout':
				include LBITE_PLUGIN_DIR . 'templates/admin/checkout-fields.php';
				break;

			case 'features':
				if ( $is_admin ) {
					include LBITE_PLUGIN_DIR . 'templates/admin/super-admin-settings.php';
				}
				break;

			case 'roles':
				if ( $is_admin ) {
					include LBITE_PLUGIN_DIR . 'templates/admin/admin-settings.php';
				}
				break;

			case 'support':
				if ( $is_admin ) {
					include LBITE_PLUGIN_DIR . 'templates/admin/support-settings.php';
				}
				break;
		}
		?>
	</div>
</div>
