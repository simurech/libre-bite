<?php
/**
 * Template: Feiertage-Einstellungen
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_holidays  = get_option( 'lbite_holidays', array() );
$lbite_locations = get_posts(
	array(
		'post_type'      => 'lbite_location',
		'posts_per_page' => 100,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'post_status'    => 'publish',
	)
);

$lbite_day_labels = array(
	'monday'    => __( 'Mon', 'libre-bite' ),
	'tuesday'   => __( 'Tue', 'libre-bite' ),
	'wednesday' => __( 'Wed', 'libre-bite' ),
	'thursday'  => __( 'Thu', 'libre-bite' ),
	'friday'    => __( 'Fri', 'libre-bite' ),
	'saturday'  => __( 'Sat', 'libre-bite' ),
	'sunday'    => __( 'Sun', 'libre-bite' ),
);
?>
<form method="post" id="lbite-holidays-form">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="holidays">

	<h2><?php esc_html_e( 'Holidays', 'libre-bite' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Define holidays on which a location is closed or has different opening hours. Holidays override the regular weekly schedule.', 'libre-bite' ); ?></p>

	<table class="widefat striped" id="lbite-holidays-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Date', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Locations', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Type', 'libre-bite' ); ?></th>
				<th><?php esc_html_e( 'Hours', 'libre-bite' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody id="lbite-holidays-body">
			<?php if ( empty( $lbite_holidays ) ) : ?>
				<tr id="lbite-no-holidays-row">
					<td colspan="6" style="text-align:center; color:#888;"><?php esc_html_e( 'No holidays defined yet.', 'libre-bite' ); ?></td>
				</tr>
			<?php endif; ?>
			<?php foreach ( $lbite_holidays as $lbite_hi => $lbite_h ) : ?>
				<?php
				$lbite_h_locs = isset( $lbite_h['locations'] ) ? $lbite_h['locations'] : 'all';
				$lbite_h_type = isset( $lbite_h['type'] ) ? $lbite_h['type'] : 'closed';
				$lbite_h_idx  = $lbite_hi;
				?>
				<tr data-index="<?php echo esc_attr( $lbite_h_idx ); ?>">
					<td>
						<input type="text" name="lbite_holidays[<?php echo esc_attr( $lbite_h_idx ); ?>][name]" value="<?php echo esc_attr( $lbite_h['name'] ); ?>" class="regular-text" required>
					</td>
					<td>
						<input type="date" name="lbite_holidays[<?php echo esc_attr( $lbite_h_idx ); ?>][date]" value="<?php echo esc_attr( $lbite_h['date'] ); ?>" required>
					</td>
					<td>
						<?php if ( ! empty( $lbite_locations ) ) : ?>
							<label style="display:block; white-space:nowrap;">
								<input type="checkbox" name="lbite_holidays[<?php echo esc_attr( $lbite_h_idx ); ?>][locations][]" value="all" class="lbite-loc-all" <?php checked( 'all' === $lbite_h_locs ); ?>>
								<?php esc_html_e( 'All', 'libre-bite' ); ?>
							</label>
							<?php foreach ( $lbite_locations as $lbite_loc ) : ?>
								<label style="display:block; white-space:nowrap;" class="lbite-loc-specific">
									<input type="checkbox" name="lbite_holidays[<?php echo esc_attr( $lbite_h_idx ); ?>][locations][]" value="<?php echo esc_attr( $lbite_loc->ID ); ?>" <?php checked( is_array( $lbite_h_locs ) && in_array( (int) $lbite_loc->ID, array_map( 'intval', $lbite_h_locs ), true ) ); ?>>
									<?php echo esc_html( $lbite_loc->post_title ); ?>
								</label>
							<?php endforeach; ?>
						<?php else : ?>
							<span class="description"><?php esc_html_e( 'All', 'libre-bite' ); ?></span>
							<input type="hidden" name="lbite_holidays[<?php echo esc_attr( $lbite_h_idx ); ?>][locations]" value="all">
						<?php endif; ?>
					</td>
					<td>
						<select name="lbite_holidays[<?php echo esc_attr( $lbite_h_idx ); ?>][type]" class="lbite-holiday-type">
							<option value="closed" <?php selected( $lbite_h_type, 'closed' ); ?>><?php esc_html_e( 'Closed', 'libre-bite' ); ?></option>
							<option value="custom" <?php selected( $lbite_h_type, 'custom' ); ?>><?php esc_html_e( 'Custom hours', 'libre-bite' ); ?></option>
						</select>
					</td>
					<td class="lbite-holiday-hours" style="<?php echo 'custom' !== $lbite_h_type ? 'display:none;' : ''; ?>">
						<?php esc_html_e( 'From', 'libre-bite' ); ?>
						<input type="time" name="lbite_holidays[<?php echo esc_attr( $lbite_h_idx ); ?>][open]" value="<?php echo esc_attr( $lbite_h['open'] ?? '' ); ?>">
						<?php esc_html_e( 'To', 'libre-bite' ); ?>
						<input type="time" name="lbite_holidays[<?php echo esc_attr( $lbite_h_idx ); ?>][close]" value="<?php echo esc_attr( $lbite_h['close'] ?? '' ); ?>">
						<br>
						<span style="color:#888; font-size:11px;"><?php esc_html_e( '2nd window:', 'libre-bite' ); ?></span>
						<input type="time" name="lbite_holidays[<?php echo esc_attr( $lbite_h_idx ); ?>][open2]" value="<?php echo esc_attr( $lbite_h['open2'] ?? '' ); ?>">
						<?php esc_html_e( 'To', 'libre-bite' ); ?>
						<input type="time" name="lbite_holidays[<?php echo esc_attr( $lbite_h_idx ); ?>][close2]" value="<?php echo esc_attr( $lbite_h['close2'] ?? '' ); ?>">
					</td>
					<td>
						<button type="button" class="button lbite-delete-holiday" aria-label="<?php esc_attr_e( 'Delete', 'libre-bite' ); ?>">&#x2715;</button>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<p style="margin-top: 16px;">
		<button type="button" class="button button-secondary" id="lbite-add-holiday">
			+ <?php esc_html_e( 'Add holiday', 'libre-bite' ); ?>
		</button>
	</p>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>

<template id="lbite-holiday-row-tpl">
	<tr>
		<td>
			<input type="text" name="lbite_holidays[__IDX__][name]" value="" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. Christmas', 'libre-bite' ); ?>" required>
		</td>
		<td>
			<input type="date" name="lbite_holidays[__IDX__][date]" value="" required>
		</td>
		<td>
			<?php if ( ! empty( $lbite_locations ) ) : ?>
				<label style="display:block; white-space:nowrap;">
					<input type="checkbox" name="lbite_holidays[__IDX__][locations][]" value="all" class="lbite-loc-all" checked>
					<?php esc_html_e( 'All', 'libre-bite' ); ?>
				</label>
				<?php foreach ( $lbite_locations as $lbite_loc ) : ?>
					<label style="display:block; white-space:nowrap;" class="lbite-loc-specific">
						<input type="checkbox" name="lbite_holidays[__IDX__][locations][]" value="<?php echo esc_attr( $lbite_loc->ID ); ?>">
						<?php echo esc_html( $lbite_loc->post_title ); ?>
					</label>
				<?php endforeach; ?>
			<?php else : ?>
				<span class="description"><?php esc_html_e( 'All', 'libre-bite' ); ?></span>
				<input type="hidden" name="lbite_holidays[__IDX__][locations]" value="all">
			<?php endif; ?>
		</td>
		<td>
			<select name="lbite_holidays[__IDX__][type]" class="lbite-holiday-type">
				<option value="closed"><?php esc_html_e( 'Closed', 'libre-bite' ); ?></option>
				<option value="custom"><?php esc_html_e( 'Custom hours', 'libre-bite' ); ?></option>
			</select>
		</td>
		<td class="lbite-holiday-hours" style="display:none;">
			<?php esc_html_e( 'From', 'libre-bite' ); ?>
			<input type="time" name="lbite_holidays[__IDX__][open]" value="">
			<?php esc_html_e( 'To', 'libre-bite' ); ?>
			<input type="time" name="lbite_holidays[__IDX__][close]" value="">
			<br>
			<span style="color:#888; font-size:11px;"><?php esc_html_e( '2nd window:', 'libre-bite' ); ?></span>
			<input type="time" name="lbite_holidays[__IDX__][open2]" value="">
			<?php esc_html_e( 'To', 'libre-bite' ); ?>
			<input type="time" name="lbite_holidays[__IDX__][close2]" value="">
		</td>
		<td>
			<button type="button" class="button lbite-delete-holiday" aria-label="<?php esc_attr_e( 'Delete', 'libre-bite' ); ?>">&#x2715;</button>
		</td>
	</tr>
</template>

<?php ob_start(); ?>
(function($) {
	var lbiteHolidayIdx = <?php echo (int) count( $lbite_holidays ); ?>;

	// Zeilen-Index beim Speichern normalisieren (verhindert Lücken nach Löschen).
	$('#lbite-holidays-form').on('submit', function() {
		$('#lbite-holidays-body tr[data-index]').each(function(i) {
			$(this).find('[name]').each(function() {
				$(this).attr('name', $(this).attr('name').replace(/\[\d+\]/, '[' + i + ']'));
			});
		});
	});

	// Feiertag hinzufügen.
	$('#lbite-add-holiday').on('click', function() {
		var tpl = document.getElementById('lbite-holiday-row-tpl').innerHTML;
		tpl = tpl.replace(/__IDX__/g, lbiteHolidayIdx);
		var $row = $(tpl);
		$row.attr('data-index', lbiteHolidayIdx);
		lbiteHolidayIdx++;
		$('#lbite-no-holidays-row').remove();
		$('#lbite-holidays-body').append($row);
	});

	// Feiertag löschen.
	$(document).on('click', '.lbite-delete-holiday', function() {
		$(this).closest('tr').remove();
		if ($('#lbite-holidays-body tr').length === 0) {
			$('#lbite-holidays-body').append('<tr id="lbite-no-holidays-row"><td colspan="6" style="text-align:center;color:#888;"><?php echo esc_js( __( 'No holidays defined yet.', 'libre-bite' ) ); ?></td></tr>');
		}
	});

	// Stunden-Felder ein-/ausblenden je nach Typ.
	$(document).on('change', '.lbite-holiday-type', function() {
		var $hours = $(this).closest('tr').find('.lbite-holiday-hours');
		if ($(this).val() === 'custom') {
			$hours.show();
		} else {
			$hours.hide();
		}
	});

	// «Alle Standorte» Toggle.
	$(document).on('change', '.lbite-loc-all', function() {
		var $row = $(this).closest('tr');
		if ($(this).is(':checked')) {
			$row.find('.lbite-loc-specific input').prop('checked', false);
		}
	});
	$(document).on('change', '.lbite-loc-specific input', function() {
		var $row = $(this).closest('tr');
		if ($(this).is(':checked')) {
			$row.find('.lbite-loc-all').prop('checked', false);
		}
	});
}(jQuery));
<?php wp_add_inline_script( 'lbite-admin-settings', ob_get_clean() ); ?>
