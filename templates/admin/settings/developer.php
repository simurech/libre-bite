<?php
/**
 * Tab: Developer
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_rest_base = rest_url( LBite_REST_API::NAMESPACE_V1 . '/' );

$lbite_rest_routes = array(
	array( 'GET', '/ping', 'lbite_view_orders', __( 'Connection test, returns plugin version and server time.', 'libre-bite' ) ),
	array( 'GET', '/locations', 'lbite_view_orders', __( 'Locations the current user may access.', 'libre-bite' ) ),
	array( 'GET', '/columns', 'lbite_view_orders', __( 'Current Kanban column configuration.', 'libre-bite' ) ),
	array( 'GET', '/orders?location_id=N', 'lbite_view_orders', __( 'Board data for one location.', 'libre-bite' ) ),
	array( 'POST/PUT', '/orders/<id>/status', 'lbite_manage_orders', __( 'Set the Kanban status of an order.', 'libre-bite' ) ),
);

$lbite_rest_hooks = array(
	array( 'lbite_order_status_changed', '$order_id, $new_status, $old_status, $order', __( 'After every Kanban status change (AJAX and REST).', 'libre-bite' ) ),
	array( 'lbite_order_auto_moved_to_preparing', '$order_id', __( 'Cron moves a pre-order forward automatically.', 'libre-bite' ) ),
	array( 'lbite_pos_order_created', '$order_id, $order', __( 'An order is created at the POS.', 'libre-bite' ) ),
	array( 'lbite_tab_opened', '$order_id, $order', __( 'An open tab is started.', 'libre-bite' ) ),
	array( 'lbite_tab_round_added', '$order_id, $round, $order', __( 'Another round is booked onto a tab.', 'libre-bite' ) ),
	array( 'lbite_tab_closed', '$order_id, $order', __( 'An open tab is closed.', 'libre-bite' ) ),
	array( 'lbite_reservation_created', '$reservation_id, $data', __( 'A reservation request comes in.', 'libre-bite' ) ),
	array( 'lbite_reservation_board_data (filter)', '$data, $reservation_id', __( 'Card data on the reservation board before output.', 'libre-bite' ) ),
	array( 'lbite_stampcard_reward_issued', '$user_id, $code', __( 'A stamp card voucher is issued.', 'libre-bite' ) ),
);
?>
<div class="lbite-settings-card">
	<h2><?php esc_html_e( 'REST API', 'libre-bite' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'A REST namespace exists alongside the AJAX endpoints for external integrations — printer bridges, kitchen displays, or custom notifications. It shares the same core logic as the AJAX endpoints, so behaviour never diverges between the two.', 'libre-bite' ); ?>
	</p>

	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Base URL', 'libre-bite' ); ?></th>
			<td><code><?php echo esc_html( $lbite_rest_base ); ?></code></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Authentication', 'libre-bite' ); ?></th>
			<td>
				<p class="description">
					<?php esc_html_e( 'Uses WordPress\'s own authentication — no separate API key. Two ways to authenticate:', 'libre-bite' ); ?>
				</p>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><?php esc_html_e( 'Cookie session with a nonce — for code running inside wp-admin itself.', 'libre-bite' ); ?></li>
					<li>
						<?php
						printf(
							/* translators: %s: link to the user's own Application Passwords section */
							esc_html__( 'Application Passwords — for external tools. Create one under %s.', 'libre-bite' ),
							'<a href="' . esc_url( admin_url( 'profile.php#application-passwords-section' ) ) . '">' . esc_html__( 'your profile', 'libre-bite' ) . '</a>'
						);
						?>
					</li>
				</ul>
			</td>
		</tr>
	</table>
</div>

<div class="lbite-settings-card">
	<h2><?php esc_html_e( 'Routes', 'libre-bite' ); ?></h2>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Method', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Path', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Capability', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Description', 'libre-bite' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $lbite_rest_routes as $lbite_route ) : ?>
			<tr>
				<td><code><?php echo esc_html( $lbite_route[0] ); ?></code></td>
				<td><code><?php echo esc_html( $lbite_route[1] ); ?></code></td>
				<td><code><?php echo esc_html( $lbite_route[2] ); ?></code></td>
				<td><?php echo esc_html( $lbite_route[3] ); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<p style="margin-top: 16px;">
		<button type="button" id="lbite-dev-ping-test" class="button">
			<?php esc_html_e( 'Test /ping', 'libre-bite' ); ?>
		</button>
		<span id="lbite-dev-ping-result" style="margin-left: 10px; font-family: monospace;"></span>
	</p>
</div>

<div class="lbite-settings-card">
	<h2><?php esc_html_e( 'Extension Hooks', 'libre-bite' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Action hooks for building integrations without modifying plugin files.', 'libre-bite' ); ?>
	</p>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Hook', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Parameters', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'When', 'libre-bite' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $lbite_rest_hooks as $lbite_hook ) : ?>
			<tr>
				<td><code><?php echo esc_html( $lbite_hook[0] ); ?></code></td>
				<td><code><?php echo esc_html( $lbite_hook[1] ); ?></code></td>
				<td><?php echo esc_html( $lbite_hook[2] ); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<?php
ob_start();
?>
jQuery(document).ready(function($) {
	$('#lbite-dev-ping-test').on('click', function() {
		var $btn    = $(this);
		var $result = $('#lbite-dev-ping-result');
		$btn.prop('disabled', true);
		$result.text('<?php echo esc_js( __( 'Testing…', 'libre-bite' ) ); ?>');

		fetch(<?php echo wp_json_encode( rest_url( LBite_REST_API::NAMESPACE_V1 . '/ping' ) ); ?>, {
			headers: { 'X-WP-Nonce': <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?> }
		}).then(function(response) {
			return response.json().then(function(data) {
				return { ok: response.ok, data: data };
			});
		}).then(function(result) {
			$btn.prop('disabled', false);
			if (result.ok) {
				$result.css('color', '#1a7f37').text('✓ ' + JSON.stringify(result.data));
			} else {
				$result.css('color', '#d63638').text('✗ ' + JSON.stringify(result.data));
			}
		}).catch(function() {
			$btn.prop('disabled', false);
			$result.css('color', '#d63638').text('✗ <?php echo esc_js( __( 'Connection error', 'libre-bite' ) ); ?>');
		});
	});
});
<?php
wp_add_inline_script( 'lbite-admin', ob_get_clean() );
