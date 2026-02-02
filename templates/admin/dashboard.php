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

<div class="wrap lb-admin-dashboard">
	<h1>
		<?php
		$plugin_name = apply_filters( 'lb_plugin_display_name', __( 'Libre Bite', 'libre-bite' ) );
		echo esc_html( $plugin_name . ' - ' . __( 'Dashboard', 'libre-bite' ) );
		?>
	</h1>

	<div class="lb-dashboard-widgets">
		<div class="lb-widget">
			<h2><?php esc_html_e( 'Heutige Bestellungen', 'libre-bite' ); ?></h2>
			<p class="lb-stat-number">
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

		<div class="lb-widget">
			<h2><?php esc_html_e( 'Standorte', 'libre-bite' ); ?></h2>
			<p class="lb-stat-number">
				<?php
				$locations = get_posts(
					array(
						'post_type'      => 'lb_location',
						'posts_per_page' => -1,
						'post_status'    => 'publish',
					)
				);
				echo count( $locations );
				?>
			</p>
			<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lb_location' ) ); ?>"><?php esc_html_e( 'Verwalten', 'libre-bite' ); ?></a></p>
		</div>

		<div class="lb-widget">
			<h2><?php esc_html_e( 'Schnellzugriff', 'libre-bite' ); ?></h2>
			<ul>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-order-board' ) ); ?>"><?php esc_html_e( 'Bestellübersicht', 'libre-bite' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-pos' ) ); ?>"><?php esc_html_e( 'Kassensystem', 'libre-bite' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-settings' ) ); ?>"><?php esc_html_e( 'Einstellungen', 'libre-bite' ); ?></a></li>
			</ul>
		</div>
	</div>
</div>

<style>
.lb-dashboard-widgets {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 20px;
	margin-top: 20px;
}

.lb-widget {
	background: #fff;
	padding: 20px;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
	box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.lb-widget h2 {
	margin-top: 0;
	font-size: 16px;
	border-bottom: 1px solid #eee;
	padding-bottom: 10px;
}

.lb-stat-number {
	font-size: 48px;
	font-weight: bold;
	margin: 20px 0;
	color: #0073aa;
}

.lb-widget ul {
	list-style: none;
	padding: 0;
	margin: 0;
}

.lb-widget ul li {
	padding: 8px 0;
	border-bottom: 1px solid #f0f0f0;
}

.lb-widget ul li:last-child {
	border-bottom: none;
}
</style>
