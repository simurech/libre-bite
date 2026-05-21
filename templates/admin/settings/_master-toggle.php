<?php
/**
 * Partial: Master-Toggle für einen Feature-Bereich
 *
 * Zeigt eine Checkbox am Anfang eines Tabs mit optionalem Pro-Badge und Lock-State.
 * Speichern erfolgt via POST-Formular des jeweiligen Tabs.
 *
 * Erwartet:
 *   $lbite_toggle_key         string  Feature-Key (z.B. 'enable_pos')
 *   $lbite_toggle_label       string  Angezeigter Label
 *   $lbite_toggle_description string  Kurzbeschreibung (optional)
 *   $lbite_toggle_is_pro      bool    Pro-Badge anzeigen (optional, default false)
 *   $lbite_toggle_premium_allowed bool Premium aktiv (optional, default false)
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_toggle_is_pro          = $lbite_toggle_is_pro ?? false;
$lbite_toggle_premium_allowed = $lbite_toggle_premium_allowed ?? false;
$lbite_toggle_description     = $lbite_toggle_description ?? '';

$lbite_toggle_enabled = lbite_feature_enabled( $lbite_toggle_key );
$lbite_toggle_locked  = $lbite_toggle_is_pro && ! $lbite_toggle_premium_allowed;

$lbite_upgrade_url = function_exists( 'lbite_freemius' ) ? lbite_freemius()->get_upgrade_url() : '#';
?>
<div class="lbite-master-toggle-wrap" style="background: #fff; border: 1px solid #dcdcde; border-radius: 6px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px;">
	<div style="flex: 1;">
		<strong style="font-size: 14px;">
			<?php echo esc_html( $lbite_toggle_label ); ?>
			<?php if ( $lbite_toggle_is_pro ) : ?>
				<span class="lbite-pro-badge">Pro</span>
			<?php endif; ?>
		</strong>
		<?php if ( $lbite_toggle_description ) : ?>
			<p class="description" style="margin-top: 4px;"><?php echo esc_html( $lbite_toggle_description ); ?></p>
		<?php endif; ?>
		<?php if ( $lbite_toggle_locked ) : ?>
			<p style="margin-top: 6px;">
				<a href="<?php echo esc_url( $lbite_upgrade_url ); ?>" class="lbite-upgrade-cta">
					<?php esc_html_e( 'Upgrade to Pro', 'libre-bite' ); ?> →
				</a>
			</p>
		<?php endif; ?>
	</div>
	<label <?php echo $lbite_toggle_locked ? 'class="lbite-locked"' : ''; ?>>
		<input
			type="checkbox"
			name="lbite_feature_toggle[<?php echo esc_attr( $lbite_toggle_key ); ?>]"
			value="1"
			<?php checked( $lbite_toggle_enabled ); ?>
			<?php disabled( $lbite_toggle_locked ); ?>
		>
	</label>
</div>
