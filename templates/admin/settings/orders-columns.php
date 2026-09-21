<?php
/**
 * Einstellungen: Kanban-Spalten (Pro)
 *
 * Ausgelagert aus orders.php (weitere Aufteilung nach v3.2.0). Liest lesend
 * enable_scheduled_orders (Locations-Tab) um zu entscheiden, ob die fixe
 * Vorbestellungen-Spalte existiert - keine Schreib-Überschneidung.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'Rename, add, remove and reorder the columns on the Kanban board.', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="orders_columns">

	<?php
	$lbite_toggle_key         = 'enable_kanban_customization';
	$lbite_toggle_label       = __( 'Customizable Kanban Columns', 'libre-bite' );
	$lbite_toggle_description = __( 'Rename, add, remove and reorder Kanban board columns; enables a one-step back action.', 'libre-bite' );
	$lbite_toggle_is_pro      = true;
	$lbite_toggle_premium_allowed = $lbite_premium_allowed;
	include LBITE_PLUGIN_DIR . 'templates/admin/settings/_master-toggle.php';
	?>

	<table class="form-table">
		<tr>
			<th>
				<?php esc_html_e( 'Drag & Drop', 'libre-bite' ); ?>
				<?php if ( ! $lbite_premium_allowed ) : ?>
					<span class="lbite-pro-badge">Pro</span>
				<?php endif; ?>
			</th>
			<td>
				<label class="<?php echo $lbite_premium_allowed ? '' : 'lbite-locked'; ?>">
					<input type="checkbox" name="lbite_kanban_drag_drop_enabled" value="1"
						<?php checked( get_option( 'lbite_kanban_drag_drop_enabled', 0 ), 1 ); ?>
						<?php disabled( ! $lbite_premium_allowed ); ?>>
					<?php esc_html_e( 'Allow dragging order cards between columns on the Kanban board.', 'libre-bite' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th>
				<?php esc_html_e( 'Show Future Pre-orders', 'libre-bite' ); ?>
				<?php if ( ! $lbite_premium_allowed ) : ?>
					<span class="lbite-pro-badge">Pro</span>
				<?php endif; ?>
			</th>
			<td>
				<label class="<?php echo $lbite_premium_allowed ? '' : 'lbite-locked'; ?>">
					<input type="checkbox" name="lbite_show_future_orders" value="1"
						<?php checked( get_option( 'lbite_show_future_orders', 1 ), 1 ); ?>
						<?php disabled( ! $lbite_premium_allowed ); ?>>
					<?php esc_html_e( 'Show pre-orders with a pickup time further in the future than the preparation time in the Kanban board.', 'libre-bite' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th>
				<?php esc_html_e( 'Dim Future Pre-orders', 'libre-bite' ); ?>
				<?php if ( ! $lbite_premium_allowed ) : ?>
					<span class="lbite-pro-badge">Pro</span>
				<?php endif; ?>
			</th>
			<td>
				<label class="<?php echo $lbite_premium_allowed ? '' : 'lbite-locked'; ?>">
					<input type="checkbox" name="lbite_dim_future_orders" value="1"
						<?php checked( get_option( 'lbite_dim_future_orders', 1 ), 1 ); ?>
						<?php disabled( ! $lbite_premium_allowed ); ?>>
					<?php esc_html_e( 'Display future pre-orders dimmed (greyed out) in the Kanban board.', 'libre-bite' ); ?>
				</label>
			</td>
		</tr>
		<?php if ( lbite_feature_enabled( 'enable_kanban_customization' ) ) : ?>
		<?php
		$lbite_kanban_cols       = LBite_Order_Dashboard::get_columns();
		$lbite_kanban_fixed      = array();
		$lbite_kanban_custom     = array();
		foreach ( $lbite_kanban_cols as $lbite_col ) {
			if ( LBite_Order_Dashboard::KEY_PREORDER === $lbite_col['key'] || LBite_Order_Dashboard::KEY_ACTIVE === $lbite_col['key'] ) {
				$lbite_kanban_fixed[ $lbite_col['key'] ] = $lbite_col;
			} else {
				$lbite_kanban_custom[] = $lbite_col;
			}
		}
		// enable_scheduled_orders gehört zum Locations-Tab - hier nur lesend
		// verwendet, um die maximale Spaltenzahl zu berechnen.
		$lbite_kanban_custom_max = lbite_feature_enabled( 'enable_scheduled_orders' ) ? 3 : 4;
		?>
		<tr>
			<th><?php esc_html_e( 'Fixed Columns', 'libre-bite' ); ?></th>
			<td>
				<?php if ( isset( $lbite_kanban_fixed[ LBite_Order_Dashboard::KEY_PREORDER ] ) ) : ?>
				<p>
					<input type="text" name="lbite_kanban_preorder_label" value="<?php echo esc_attr( $lbite_kanban_fixed[ LBite_Order_Dashboard::KEY_PREORDER ]['label'] ); ?>" class="regular-text" <?php disabled( ! $lbite_premium_allowed ); ?>>
					<span class="description"><?php esc_html_e( 'Pre-orders — only shown because "Pre-orders" is enabled under Settings → Locations. Always the first column.', 'libre-bite' ); ?></span>
				</p>
				<?php endif; ?>
				<p>
					<input type="text" name="lbite_kanban_active_label" value="<?php echo esc_attr( $lbite_kanban_fixed[ LBite_Order_Dashboard::KEY_ACTIVE ]['label'] ); ?>" class="regular-text" <?php disabled( ! $lbite_premium_allowed ); ?>>
					<span class="description"><?php esc_html_e( 'Always present — every new order that is not a pre-order starts here.', 'libre-bite' ); ?></span>
				</p>
				<p class="description"><?php esc_html_e( 'These two columns cannot be removed, renamed to a different key, or reordered — they carry the automatic pre-order routing. You can still rename their label.', 'libre-bite' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Own Columns', 'libre-bite' ); ?></th>
			<td>
				<div id="lbite-kanban-columns-preview" class="lbite-preview-box" aria-hidden="true">
					<p class="lbite-preview-box__label"><?php esc_html_e( 'Preview: column order on the board', 'libre-bite' ); ?></p>
					<div class="lbite-kanban-preview__row" id="lbite-kanban-preview-row"></div>
				</div>

				<div id="lbite-kanban-columns-editor" data-next-index="<?php echo (int) count( $lbite_kanban_custom ); ?>" data-max-columns="<?php echo (int) $lbite_kanban_custom_max; ?>">
					<?php foreach ( $lbite_kanban_custom as $lbite_i => $lbite_col ) : ?>
					<div class="lbite-kanban-column-row" data-index="<?php echo (int) $lbite_i; ?>">
						<span class="dashicons dashicons-menu lbite-kanban-drag-handle"></span>
						<input type="hidden" name="columns[<?php echo (int) $lbite_i; ?>][key]" value="<?php echo esc_attr( $lbite_col['key'] ); ?>">
						<input type="text" name="columns[<?php echo (int) $lbite_i; ?>][label]" value="<?php echo esc_attr( $lbite_col['label'] ); ?>" class="regular-text" <?php disabled( ! $lbite_premium_allowed ); ?>>
						<label>
							<input type="checkbox" name="columns[<?php echo (int) $lbite_i; ?>][counts_as_completed]" value="1" <?php checked( $lbite_col['counts_as_completed'] ); ?> <?php disabled( ! $lbite_premium_allowed ); ?>>
							<?php esc_html_e( 'Counts as completed', 'libre-bite' ); ?>
						</label>
						<button type="button" class="button lbite-kanban-remove-column" title="<?php esc_attr_e( 'Remove column', 'libre-bite' ); ?>" <?php disabled( ! $lbite_premium_allowed ); ?>>&times;</button>
					</div>
					<?php endforeach; ?>
				</div>
				<button type="button" id="lbite-kanban-add-column" class="button" <?php disabled( ! $lbite_premium_allowed ); ?>><?php esc_html_e( 'Add column', 'libre-bite' ); ?></button>
				<p class="description">
					<?php
					printf(
						/* translators: %d: maximum number of additional columns */
						esc_html__( 'Drag rows to reorder. These columns always come after the fixed columns above. At least one column needs "Counts as completed" checked, or a default "Completed" column is added automatically. Up to %d additional columns.', 'libre-bite' ),
						(int) $lbite_kanban_custom_max
					);
					?>
				</p>
				<script>
				(function() {
					var row = document.getElementById('lbite-kanban-preview-row');
					var fixedLabels = [
						<?php if ( isset( $lbite_kanban_fixed[ LBite_Order_Dashboard::KEY_PREORDER ] ) ) : ?>
						document.querySelector('input[name="lbite_kanban_preorder_label"]'),
						<?php endif; ?>
						document.querySelector('input[name="lbite_kanban_active_label"]')
					];

					function render() {
						if ( ! row ) { return; }
						row.innerHTML = '';
						fixedLabels.forEach(function(input) {
							if ( ! input ) { return; }
							var chip = document.createElement('span');
							chip.className = 'lbite-kanban-preview__chip lbite-kanban-preview__chip--fixed';
							chip.textContent = input.value || '…';
							row.appendChild(chip);
						});
						document.querySelectorAll('#lbite-kanban-columns-editor .lbite-kanban-column-row').forEach(function(colRow) {
							var input = colRow.querySelector('input[type="text"]');
							var chip = document.createElement('span');
							chip.className = 'lbite-kanban-preview__chip';
							chip.textContent = ( input && input.value ) || '…';
							row.appendChild(chip);
						});
					}

					document.addEventListener('input', function(e) {
						if ( e.target.closest('#lbite-kanban-columns-editor') || fixedLabels.indexOf(e.target) !== -1 ) {
							render();
						}
					});
					var editor = document.getElementById('lbite-kanban-columns-editor');
					if ( editor && window.MutationObserver ) {
						new MutationObserver(render).observe(editor, { childList: true, subtree: true });
					}
					render();
				})();
				</script>
			</td>
		</tr>
		<?php endif; ?>
	</table>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
