<?php
/**
 * Einstellungen: Ankündigungsleiste
 *
 * Ausgelagert aus promotions.php (weitere Aufteilung nach v3.2.0). Hat
 * keinen eigenen Feature-Toggle - hängt am enable_promotions-Schalter auf
 * der Promotions-Seite (F34, gemeinsames Feature).
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LBite_Promotions' ) ) {
	require_once LBITE_PLUGIN_DIR . 'includes/modules/promotions/class-promotions.php';
}

$lbite_promo_banner = LBite_Promotions::get_banner();
$lbite_promo_days   = array(
	'monday'    => __( 'Mon', 'libre-bite' ),
	'tuesday'   => __( 'Tue', 'libre-bite' ),
	'wednesday' => __( 'Wed', 'libre-bite' ),
	'thursday'  => __( 'Thu', 'libre-bite' ),
	'friday'    => __( 'Fri', 'libre-bite' ),
	'saturday'  => __( 'Sat', 'libre-bite' ),
	'sunday'    => __( 'Sun', 'libre-bite' ),
);
?>
<p class="description lbite-settings-intro">
	<?php esc_html_e( 'A text bar at the top of the shop, e.g. for a running promotion or opening hours notice. Requires Promotions to be enabled on the Promotions page.', 'libre-bite' ); ?>
</p>

<form method="post">
	<?php wp_nonce_field( 'lbite_settings' ); ?>
	<input type="hidden" name="lbite_save_tab" value="marketing_banner">

	<div id="lbite-banner-preview" class="lbite-preview-box" aria-hidden="true">
		<p class="lbite-preview-box__label"><?php esc_html_e( 'Preview: how this looks at the top of the shop', 'libre-bite' ); ?></p>
		<div class="lbite-promo-banner-preview" id="lbite-promo-banner-preview-bar" style="<?php echo empty( $lbite_promo_banner['enabled'] ) ? 'opacity:.4;' : ''; ?>">
			<span id="lbite-promo-banner-preview-text"><?php echo esc_html( $lbite_promo_banner['text'] ? $lbite_promo_banner['text'] : __( 'e.g. Happy Hour until 6 pm — every third drink is on us', 'libre-bite' ) ); ?></span>
		</div>
	</div>

	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Show bar', 'libre-bite' ); ?></th>
			<td>
				<label>
					<input type="checkbox" id="lbite_promo_banner_enabled" name="lbite_promo_banner[enabled]" value="1"
						<?php checked( ! empty( $lbite_promo_banner['enabled'] ) ); ?> <?php disabled( ! $lbite_premium_allowed ); ?>>
					<?php esc_html_e( 'Show a bar at the top of the shop', 'libre-bite' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Text', 'libre-bite' ); ?></th>
			<td>
				<input type="text" id="lbite_promo_banner_text" name="lbite_promo_banner[text]" class="large-text"
					value="<?php echo esc_attr( $lbite_promo_banner['text'] ); ?>"
					placeholder="<?php esc_attr_e( 'e.g. Happy Hour until 6 pm — every third drink is on us', 'libre-bite' ); ?>"
					<?php disabled( ! $lbite_premium_allowed ); ?>>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Link', 'libre-bite' ); ?></th>
			<td>
				<input type="url" name="lbite_promo_banner[link]" class="regular-text"
					value="<?php echo esc_attr( $lbite_promo_banner['link'] ); ?>"
					<?php disabled( ! $lbite_premium_allowed ); ?>>
				<p class="description"><?php esc_html_e( 'Optional. Leave empty for plain text.', 'libre-bite' ); ?></p>
				<label>
					<input type="checkbox" name="lbite_promo_banner[open_in_new_tab]" value="1"
						<?php checked( ! empty( $lbite_promo_banner['open_in_new_tab'] ) ); ?> <?php disabled( ! $lbite_premium_allowed ); ?>>
					<?php esc_html_e( 'Open link in a new tab', 'libre-bite' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'When', 'libre-bite' ); ?></th>
			<td>
				<?php
				$lbite_bs      = is_array( $lbite_promo_banner['schedule'] ) ? $lbite_promo_banner['schedule'] : array();
				$lbite_windows = isset( $lbite_bs['windows'] ) && is_array( $lbite_bs['windows'] ) ? array_values( $lbite_bs['windows'] ) : array();
				if ( empty( $lbite_windows ) ) {
					$lbite_windows = array( array( 'from' => '', 'to' => '' ) );
				}
				?>
				<label>
					<input type="checkbox" name="lbite_promo_banner[schedule][enabled]" value="1"
						<?php checked( ! empty( $lbite_bs['enabled'] ) ); ?> <?php disabled( ! $lbite_premium_allowed ); ?>>
					<?php esc_html_e( 'Limit to certain days or times', 'libre-bite' ); ?>
				</label>
				<p style="margin:8px 0;">
					<?php foreach ( $lbite_promo_days as $lbite_dk => $lbite_dl ) : ?>
						<label style="display:inline-block; margin:0 8px 4px 0;">
							<input type="checkbox" name="lbite_promo_banner[schedule][days][<?php echo esc_attr( $lbite_dk ); ?>]" value="1"
								<?php checked( ! empty( $lbite_bs['days'][ $lbite_dk ] ) ); ?> <?php disabled( ! $lbite_premium_allowed ); ?>>
							<?php echo esc_html( $lbite_dl ); ?>
						</label>
					<?php endforeach; ?>
				</p>
				<div id="lbite-banner-windows" data-next-index="<?php echo (int) count( $lbite_windows ); ?>">
					<?php foreach ( $lbite_windows as $lbite_wi => $lbite_window ) : ?>
						<div class="lbite-banner-window-row" data-index="<?php echo (int) $lbite_wi; ?>" style="margin-bottom:6px;">
							<label><?php esc_html_e( 'From', 'libre-bite' ); ?>
								<input type="time" name="lbite_promo_banner[schedule][windows][<?php echo (int) $lbite_wi; ?>][from]"
									value="<?php echo esc_attr( isset( $lbite_window['from'] ) ? $lbite_window['from'] : '' ); ?>"
									<?php disabled( ! $lbite_premium_allowed ); ?>>
							</label>
							<label style="margin-left:8px;"><?php esc_html_e( 'To', 'libre-bite' ); ?>
								<input type="time" name="lbite_promo_banner[schedule][windows][<?php echo (int) $lbite_wi; ?>][to]"
									value="<?php echo esc_attr( isset( $lbite_window['to'] ) ? $lbite_window['to'] : '' ); ?>"
									<?php disabled( ! $lbite_premium_allowed ); ?>>
							</label>
							<button type="button" class="button lbite-banner-remove-window" <?php disabled( ! $lbite_premium_allowed ); ?>>&times;</button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button" id="lbite-banner-add-window" <?php disabled( ! $lbite_premium_allowed ); ?>>
					<?php esc_html_e( '+ Add time window', 'libre-bite' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'A window may run past midnight, for example 22:00 to 02:00. Add more than one window for a break, e.g. 8:00–12:00 and 14:00–18:00.', 'libre-bite' ); ?></p>
			</td>
		</tr>
	</table>

	<script>
	(function() {
		var bannerEnabled = document.getElementById('lbite_promo_banner_enabled');
		var bannerText    = document.getElementById('lbite_promo_banner_text');
		var bannerBar     = document.getElementById('lbite-promo-banner-preview-bar');
		var bannerTextEl  = document.getElementById('lbite-promo-banner-preview-text');
		var bannerFallback = <?php echo wp_json_encode( __( 'e.g. Happy Hour until 6 pm — every third drink is on us', 'libre-bite' ) ); ?>;

		function updateBannerPreview() {
			if ( ! bannerBar ) { return; }
			bannerBar.style.opacity = ( bannerEnabled && bannerEnabled.checked ) ? '1' : '.4';
			bannerTextEl.textContent = ( bannerText && bannerText.value ) ? bannerText.value : bannerFallback;
		}
		if ( bannerEnabled ) { bannerEnabled.addEventListener('change', updateBannerPreview); }
		if ( bannerText ) { bannerText.addEventListener('input', updateBannerPreview); }
		updateBannerPreview();
	})();
	</script>

	<?php submit_button( __( 'Save', 'libre-bite' ), 'primary', 'lbite_save_settings' ); ?>
</form>
