<?php
/**
 * Frontend-Template: Reservierungsformular
 *
 * Verfügbare Variablen:
 *   $lbite_locations           - array von WP_Post (Standorte)
 *   $lbite_preselected_location - int (vorgewählte Standort-ID, 0 = keine)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lbite-reservation-wrap">
	<div class="lbite-reservation-form-container" id="lbite-reservation-form-container">

		<form id="lbite-reservation-form" class="lbite-reservation-form" novalidate>

			<?php if ( count( $lbite_locations ) > 1 || 0 === $lbite_preselected_location ) : ?>
			<div class="lbite-res-field">
				<label class="lbite-res-label lbite-res-required" for="lbite-res-location">
					<?php esc_html_e( 'Standort', 'libre-bite' ); ?>
				</label>
				<select id="lbite-res-location" name="location_id" class="lbite-res-select" required>
					<option value=""><?php esc_html_e( '— Standort wählen —', 'libre-bite' ); ?></option>
					<?php foreach ( $lbite_locations as $lbite_loc ) : ?>
						<option value="<?php echo esc_attr( $lbite_loc->ID ); ?>"
							<?php selected( $lbite_preselected_location, $lbite_loc->ID ); ?>>
							<?php echo esc_html( $lbite_loc->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php else : ?>
				<input type="hidden" name="location_id" value="<?php echo esc_attr( $lbite_preselected_location ); ?>">
			<?php endif; ?>

			<div class="lbite-res-row">
				<div class="lbite-res-field">
					<label class="lbite-res-label lbite-res-required" for="lbite-res-date">
						<?php esc_html_e( 'Datum', 'libre-bite' ); ?>
					</label>
					<input type="date"
						id="lbite-res-date"
						name="date"
						class="lbite-res-input"
						min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>"
						required>
				</div>

				<div class="lbite-res-field">
					<label class="lbite-res-label lbite-res-required" for="lbite-res-time">
						<?php esc_html_e( 'Uhrzeit', 'libre-bite' ); ?>
					</label>
					<input type="time"
						id="lbite-res-time"
						name="time"
						class="lbite-res-input"
						required>
				</div>
			</div>

			<div class="lbite-res-row">
				<div class="lbite-res-field">
					<label class="lbite-res-label lbite-res-required" for="lbite-res-guests">
						<?php esc_html_e( 'Anzahl Personen', 'libre-bite' ); ?>
					</label>
					<input type="number"
						id="lbite-res-guests"
						name="guests"
						class="lbite-res-input"
						min="1"
						max="50"
						required>
				</div>

				<div class="lbite-res-field">
					<label class="lbite-res-label" for="lbite-res-table">
						<?php esc_html_e( 'Tisch (optional)', 'libre-bite' ); ?>
					</label>
					<select id="lbite-res-table" name="table_id" class="lbite-res-select" disabled>
						<option value=""><?php esc_html_e( '— Zuerst Standort wählen —', 'libre-bite' ); ?></option>
					</select>
				</div>
			</div>

			<hr class="lbite-res-divider">

			<div class="lbite-res-field">
				<label class="lbite-res-label lbite-res-required" for="lbite-res-name">
					<?php esc_html_e( 'Name', 'libre-bite' ); ?>
				</label>
				<input type="text"
					id="lbite-res-name"
					name="name"
					class="lbite-res-input"
					autocomplete="name"
					required>
			</div>

			<div class="lbite-res-row">
				<div class="lbite-res-field">
					<label class="lbite-res-label lbite-res-required" for="lbite-res-email">
						<?php esc_html_e( 'E-Mail', 'libre-bite' ); ?>
					</label>
					<input type="email"
						id="lbite-res-email"
						name="email"
						class="lbite-res-input"
						autocomplete="email"
						required>
				</div>

				<div class="lbite-res-field">
					<label class="lbite-res-label" for="lbite-res-phone">
						<?php esc_html_e( 'Telefon (optional)', 'libre-bite' ); ?>
					</label>
					<input type="tel"
						id="lbite-res-phone"
						name="phone"
						class="lbite-res-input"
						autocomplete="tel">
				</div>
			</div>

			<div class="lbite-res-field">
				<label class="lbite-res-label" for="lbite-res-notes">
					<?php esc_html_e( 'Anmerkungen (optional)', 'libre-bite' ); ?>
				</label>
				<textarea id="lbite-res-notes"
					name="notes"
					class="lbite-res-textarea"
					rows="3"></textarea>
			</div>

			<div class="lbite-res-notice lbite-res-notice--error" id="lbite-res-error" style="display:none;"></div>

			<div class="lbite-res-actions">
				<button type="submit" class="lbite-res-submit" id="lbite-res-submit">
					<?php esc_html_e( 'Reservierungsanfrage senden', 'libre-bite' ); ?>
				</button>
			</div>
		</form>

		<div class="lbite-res-success" id="lbite-res-success" style="display:none;">
			<div class="lbite-res-success-icon">&#10003;</div>
			<h3><?php esc_html_e( 'Anfrage erfolgreich gesendet!', 'libre-bite' ); ?></h3>
			<p><?php esc_html_e( 'Ihre Reservierungsanfrage wurde erfolgreich übermittelt. Wir werden uns in Kürze bei Ihnen melden, um die Reservierung zu bestätigen.', 'libre-bite' ); ?></p>
		</div>

	</div>
</div>
