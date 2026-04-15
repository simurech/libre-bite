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
		$lbite_plugin_name = apply_filters( 'lbite_plugin_display_name', __( 'Libre Bite', 'libre-bite' ) );
		echo esc_html( $lbite_plugin_name . ' - ' . __( 'Dashboard', 'libre-bite' ) );
		?>
	</h1>

	<div class="lbite-dashboard-widgets">
		<div class="lbite-widget">
			<h2><?php esc_html_e( 'Today\'s Orders', 'libre-bite' ); ?></h2>
			<p class="lbite-stat-number">
				<?php
				$lbite_today_orders = wc_get_orders(
					array(
						'limit'        => 50,
						'date_created' => '>' . ( new DateTimeImmutable( wp_date( 'Y-m-d' ) . ' 00:00:00', wp_timezone() ) )->getTimestamp(),
						'return'       => 'ids',
					)
				);
				echo count( $lbite_today_orders );
				?>
			</p>
		</div>

		<div class="lbite-widget">
			<h2><?php esc_html_e( 'Locations', 'libre-bite' ); ?></h2>
			<p class="lbite-stat-number">
				<?php
				$lbite_locations = get_posts(
					array(
						'post_type'      => 'lbite_location',
						'posts_per_page' => 100, // Begrenzt für Performance.
						'post_status'    => 'publish',
					)
				);
				echo count( $lbite_locations );
				?>
			</p>
			<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lbite_location' ) ); ?>"><?php esc_html_e( 'Manage', 'libre-bite' ); ?></a></p>
		</div>

		<div class="lbite-widget">
			<h2><?php esc_html_e( 'Quick Access', 'libre-bite' ); ?></h2>
			<ul>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-order-board' ) ); ?>"><?php esc_html_e( 'Order Overview', 'libre-bite' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-pos' ) ); ?>"><?php esc_html_e( 'POS System', 'libre-bite' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'libre-bite' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-help' ) ); ?>"><?php esc_html_e( 'Help & Support', 'libre-bite' ); ?></a></li>
			</ul>
		</div>
	</div>
</div>

