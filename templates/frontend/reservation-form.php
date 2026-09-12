<?php
/**
 * Frontend-Template: Reservierungsformular
 *
 * Verfügbare Variablen:
 *   $lbite_locations             - array von WP_Post (Standorte)
 *   $lbite_preselected_location  - int (vorgewählte Standort-ID, 0 = keine)
 *   $lbite_show_phone_field      - bool (Telefonfeld anzeigen)
 *   $lbite_show_notes_field      - bool (Notizfeld anzeigen)
 *
 * Dreistufiger Ablauf: 1) Wann & wie viele, 2) Deine Angaben, 3) Bestätigung.
 * Alle Felder bleiben Teil des Formulars, auch wenn ihr Schritt gerade nicht
 * sichtbar ist – CSS-`display: none` schliesst sie nicht von der Übermittlung aus.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_show_location_step = count( $lbite_locations ) > 1;
?>
<div class="lbite-reservation-wrap">
	<div class="lbite-reservation-form-container" id="lbite-reservation-form-container">

		<div class="lbite-res-step-indicator" aria-hidden="true">
			<div class="lbite-res-step-dot active" data-step-dot="1">
				<span class="lbite-res-step-dot-number">1</span>
				<span class="lbite-res-step-dot-label"><?php esc_html_e( 'When & How Many', 'libre-bite' ); ?></span>
			</div>
			<div class="lbite-res-step-connector"></div>
			<div class="lbite-res-step-dot" data-step-dot="2">
				<span class="lbite-res-step-dot-number">2</span>
				<span class="lbite-res-step-dot-label"><?php esc_html_e( 'Your Details', 'libre-bite' ); ?></span>
			</div>
			<div class="lbite-res-step-connector"></div>
			<div class="lbite-res-step-dot" data-step-dot="3">
				<span class="lbite-res-step-dot-number">3</span>
				<span class="lbite-res-step-dot-label"><?php esc_html_e( 'Confirmation', 'libre-bite' ); ?></span>
			</div>
		</div>

		<form id="lbite-reservation-form" class="lbite-reservation-form" novalidate>

			<div class="lbite-res-step active" data-step="1">
				<h3 class="lbite-res-step-heading"><?php esc_html_e( 'When & How Many', 'libre-bite' ); ?></h3>

				<?php if ( $lbite_show_location_step ) : ?>
				<div class="lbite-res-field">
					<label class="lbite-res-label lbite-res-required" for="lbite-res-location">
						<?php esc_html_e( 'Location', 'libre-bite' ); ?>
					</label>
					<select id="lbite-res-location" name="location_id" class="lbite-res-select" required>
						<option value=""><?php esc_html_e( '— Select Location —', 'libre-bite' ); ?></option>
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
							<?php esc_html_e( 'Date', 'libre-bite' ); ?>
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
							<?php esc_html_e( 'Time', 'libre-bite' ); ?>
						</label>
						<input type="time"
							id="lbite-res-time"
							name="time"
							class="lbite-res-input"
							required>
					</div>
				</div>

				<div class="lbite-res-field">
					<label class="lbite-res-label lbite-res-required" for="lbite-res-guests">
						<?php esc_html_e( 'Number of Guests', 'libre-bite' ); ?>
					</label>
					<input type="number"
						id="lbite-res-guests"
						name="guests"
						class="lbite-res-input"
						min="1"
						max="50"
						required>
				</div>

				<input type="hidden" name="table_id" value="0">

				<div class="lbite-res-step-actions">
					<span></span>
					<button type="button" class="lbite-res-step-next" data-next="2">
						<?php esc_html_e( 'Next', 'libre-bite' ); ?>
					</button>
				</div>
			</div>

			<div class="lbite-res-step" data-step="2">
				<h3 class="lbite-res-step-heading"><?php esc_html_e( 'Your Details', 'libre-bite' ); ?></h3>

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
							<?php esc_html_e( 'Email', 'libre-bite' ); ?>
						</label>
						<input type="email"
							id="lbite-res-email"
							name="email"
							class="lbite-res-input"
							autocomplete="email"
							required>
					</div>

					<?php if ( $lbite_show_phone_field ) : ?>
					<div class="lbite-res-field">
						<label class="lbite-res-label" for="lbite-res-phone">
							<?php esc_html_e( 'Phone (optional)', 'libre-bite' ); ?>
						</label>
						<input type="tel"
							id="lbite-res-phone"
							name="phone"
							class="lbite-res-input"
							autocomplete="tel">
					</div>
					<?php endif; ?>
				</div>

				<?php if ( $lbite_show_notes_field ) : ?>
				<div class="lbite-res-field">
					<label class="lbite-res-label" for="lbite-res-notes">
						<?php esc_html_e( 'Notes (optional)', 'libre-bite' ); ?>
					</label>
					<textarea id="lbite-res-notes"
						name="notes"
						class="lbite-res-textarea"
						rows="3"></textarea>
				</div>
				<?php endif; ?>

				<div class="lbite-res-step-actions">
					<button type="button" class="lbite-res-step-back" data-back="1">
						<?php esc_html_e( 'Back', 'libre-bite' ); ?>
					</button>
					<button type="button" class="lbite-res-step-next" data-next="3">
						<?php esc_html_e( 'Next', 'libre-bite' ); ?>
					</button>
				</div>
			</div>

			<div class="lbite-res-step" data-step="3">
				<h3 class="lbite-res-step-heading"><?php esc_html_e( 'Confirmation', 'libre-bite' ); ?></h3>

				<div class="lbite-res-summary">
					<?php if ( $lbite_show_location_step ) : ?>
					<div class="lbite-res-summary-row">
						<span class="lbite-res-summary-label"><?php esc_html_e( 'Location:', 'libre-bite' ); ?></span>
						<span class="lbite-res-summary-value" id="lbite-res-summary-location"></span>
					</div>
					<?php endif; ?>
					<div class="lbite-res-summary-row">
						<span class="lbite-res-summary-label"><?php esc_html_e( 'Date:', 'libre-bite' ); ?></span>
						<span class="lbite-res-summary-value" id="lbite-res-summary-date"></span>
					</div>
					<div class="lbite-res-summary-row">
						<span class="lbite-res-summary-label"><?php esc_html_e( 'Time:', 'libre-bite' ); ?></span>
						<span class="lbite-res-summary-value" id="lbite-res-summary-time"></span>
					</div>
					<div class="lbite-res-summary-row">
						<span class="lbite-res-summary-label"><?php esc_html_e( 'Guests:', 'libre-bite' ); ?></span>
						<span class="lbite-res-summary-value" id="lbite-res-summary-guests"></span>
					</div>
					<div class="lbite-res-summary-row">
						<span class="lbite-res-summary-label"><?php esc_html_e( 'Name:', 'libre-bite' ); ?></span>
						<span class="lbite-res-summary-value" id="lbite-res-summary-name"></span>
					</div>
					<div class="lbite-res-summary-row">
						<span class="lbite-res-summary-label"><?php esc_html_e( 'Email:', 'libre-bite' ); ?></span>
						<span class="lbite-res-summary-value" id="lbite-res-summary-email"></span>
					</div>
					<?php if ( $lbite_show_phone_field ) : ?>
					<div class="lbite-res-summary-row" id="lbite-res-summary-phone-row">
						<span class="lbite-res-summary-label"><?php esc_html_e( 'Phone:', 'libre-bite' ); ?></span>
						<span class="lbite-res-summary-value" id="lbite-res-summary-phone"></span>
					</div>
					<?php endif; ?>
					<?php if ( $lbite_show_notes_field ) : ?>
					<div class="lbite-res-summary-row" id="lbite-res-summary-notes-row">
						<span class="lbite-res-summary-label"><?php esc_html_e( 'Notes:', 'libre-bite' ); ?></span>
						<span class="lbite-res-summary-value" id="lbite-res-summary-notes"></span>
					</div>
					<?php endif; ?>
				</div>

				<div class="lbite-res-hp" aria-hidden="true">
					<label for="lbite-res-website"><?php esc_html_e( 'Website', 'libre-bite' ); ?></label>
					<input type="text" id="lbite-res-website" name="lbite_website" value="" tabindex="-1" autocomplete="off">
				</div>

				<div class="lbite-res-notice lbite-res-notice--error" id="lbite-res-error" style="display:none;"></div>

				<div class="lbite-res-step-actions">
					<button type="button" class="lbite-res-step-back" data-back="2">
						<?php esc_html_e( 'Back', 'libre-bite' ); ?>
					</button>
					<button type="submit" class="lbite-res-submit" id="lbite-res-submit">
						<?php esc_html_e( 'Send Reservation Request', 'libre-bite' ); ?>
					</button>
				</div>
			</div>
		</form>

		<div class="lbite-res-success" id="lbite-res-success" style="display:none;">
			<div class="lbite-res-success-icon">&#10003;</div>
			<h3><?php esc_html_e( 'Request sent successfully!', 'libre-bite' ); ?></h3>
			<p><?php esc_html_e( 'Your reservation request has been submitted successfully. We will get back to you shortly to confirm the reservation.', 'libre-bite' ); ?></p>
		</div>

	</div>
</div>
