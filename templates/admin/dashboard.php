<?php
/**
 * Template: Admin Dashboard
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap lbite-admin-dashboard">
	<h1>
		<?php
		$plugin_name = apply_filters( 'lbite_plugin_display_name', __( 'Libre Bite', 'libre-bite' ) );
		echo esc_html( $plugin_name . ' - ' . __( 'Dashboard', 'libre-bite' ) );
		?>
	</h1>

	<div class="lbite-dashboard-widgets">
		<div class="lbite-widget">
			<h2><?php esc_html_e( 'Heutige Bestellungen', 'libre-bite' ); ?></h2>
			<p class="lbite-stat-number">
				<?php
				$today_orders = wc_get_orders(
					array(
						'limit'        => -1,
						'date_created' => '>' . strtotime( 'today' ),
						'return'       => 'ids',
					)
				);
				echo count( $today_orders );
				?>
			</p>
		</div>

		<div class="lbite-widget">
			<h2><?php esc_html_e( 'Standorte', 'libre-bite' ); ?></h2>
			<p class="lbite-stat-number">
				<?php
				$locations = get_posts(
					array(
						'post_type'      => 'lbite_location',
						'posts_per_page' => -1,
						'post_status'    => 'publish',
					)
				);
				echo count( $locations );
				?>
			</p>
			<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lbite_location' ) ); ?>"><?php esc_html_e( 'Verwalten', 'libre-bite' ); ?></a></p>
		</div>

		<div class="lbite-widget">
			<h2><?php esc_html_e( 'Schnellzugriff', 'libre-bite' ); ?></h2>
			<ul>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-order-board' ) ); ?>"><?php esc_html_e( 'Bestellübersicht', 'libre-bite' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-pos' ) ); ?>"><?php esc_html_e( 'Kassensystem', 'libre-bite' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-settings' ) ); ?>"><?php esc_html_e( 'Einstellungen', 'libre-bite' ); ?></a></li>
			</ul>
		</div>
	</div>
</div>

