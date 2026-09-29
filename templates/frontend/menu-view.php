<?php
/**
 * Menü-Ansicht – Ausgabe
 *
 * Erwartet aus dem Shortcode-Kontext: $lbite_sections, $lbite_layout,
 * $lbite_cart, $lbite_location_id.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lbite-menu-view lbite-menu-view--<?php echo esc_attr( $lbite_layout ); ?>" data-lbite-menu>

	<?php
	// Standort-Umschalter fehlte hier bisher komplett (Nutzer-Fund 2026-09-28):
	// die Menü-Ansicht liest den Standort nur passiv aus der Session, ohne eigene
	// Möglichkeit, ihn direkt hier zu wählen/wechseln.
	//
	// Zwei Zustände statt einem gemeinsamen Block (Nutzer-Fund 2026-09-29): Name
	// und Auswahl-Dropdown standen bisher gleichzeitig da und zeigten denselben
	// Standort doppelt an, sobald einer gewählt war - wie auf den Standard-Shop-
	// Seiten (LBite_Locations::render_shop_location_notice_placeholder()) zeigt
	// der Picker jetzt nur, solange noch kein Standort gewählt ist.
	if ( lbite_feature_enabled( 'enable_location_selector' ) && class_exists( 'LBite_Locations' ) ) :
		$lbite_menu_locations     = LBite_Locations::get_all_locations();
		$lbite_menu_location_name = '';

		if ( $lbite_location_id ) {
			foreach ( $lbite_menu_locations as $lbite_ml ) {
				if ( (int) $lbite_ml->ID === (int) $lbite_location_id ) {
					$lbite_menu_location_name = $lbite_ml->post_title;
					break;
				}
			}
		}

		if ( count( $lbite_menu_locations ) > 1 ) :
			?>
			<div class="lbite-menu-location-banner" data-lbite-menu-location>
				<?php if ( '' !== $lbite_menu_location_name ) : ?>
					<span class="lbite-menu-location-banner__text" data-lbite-location-current>
						📍 <?php echo esc_html( $lbite_menu_location_name ); ?>
					</span>
					<div class="lbite-menu-location-banner__actions" data-lbite-location-current>
						<?php if ( $lbite_unavailable_count > 0 ) : ?>
							<button type="button" class="lbite-menu-location-banner__filter" data-lbite-availability-toggle>
								<?php esc_html_e( 'Show only available products', 'libre-bite' ); ?>
							</button>
						<?php endif; ?>
						<button type="button" class="lbite-menu-location-banner__change" data-lbite-location-change>
							<?php esc_html_e( 'Change location', 'libre-bite' ); ?>
						</button>
					</div>
				<?php else : ?>
					<span class="lbite-menu-location-banner__text">
						📍 <?php esc_html_e( 'Choose a location to see what is available', 'libre-bite' ); ?>
					</span>
				<?php endif; ?>
				<select class="lbite-menu-location-banner__picker" data-lbite-location-picker <?php echo '' !== $lbite_menu_location_name ? 'hidden' : ''; ?>>
					<option value=""><?php esc_html_e( 'Please choose...', 'libre-bite' ); ?></option>
					<?php foreach ( $lbite_menu_locations as $lbite_ml ) : ?>
						<option value="<?php echo esc_attr( $lbite_ml->ID ); ?>" <?php selected( (int) $lbite_location_id, (int) $lbite_ml->ID ); ?>>
							<?php echo esc_html( $lbite_ml->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php
		endif;
	endif;
	?>

	<?php if ( empty( $lbite_sections ) ) : ?>

		<p class="lbite-menu-empty">
			<?php esc_html_e( 'No dishes are available right now. Please check back later.', 'libre-bite' ); ?>
		</p>

	<?php else : ?>

		<?php if ( count( $lbite_sections ) > 1 ) : ?>
			<nav class="lbite-menu-nav" aria-label="<?php esc_attr_e( 'Menu sections', 'libre-bite' ); ?>">
				<?php foreach ( $lbite_sections as $lbite_i => $lbite_section ) : ?>
					<a class="lbite-menu-nav__item"
						href="#lbite-menu-section-<?php echo (int) $lbite_i; ?>">
						<?php
						echo esc_html(
							$lbite_section['term']
								? $lbite_section['term']->name
								: __( 'More', 'libre-bite' )
						);
						?>
					</a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php
		// Ernährungsform-Filter fehlte hier bisher komplett (Nutzer-Fund 2026-09-28):
		// die Menü-Ansicht ist ein eigenständiges Template ohne WooCommerce-Loop-Hooks,
		// über die render_dietary_filter_bar() sonst überall sonst eingehängt wird.
		// Bewusst nach der Kategorien-Navigation (Nutzer-Fund 2026-09-29): die Kategorien
		// sind die primäre Navigation, die Ernährungsform-Filter sind ein Zusatzwerkzeug.
		if ( lbite_feature_enabled( 'enable_dietary_filter' ) && class_exists( 'LBite_Nutritional_Info' ) ) :
			$lbite_diet_filter_labels = LBite_Nutritional_Info::get_dietary_list();
			if ( ! empty( $lbite_diet_filter_labels ) ) :
				?>
				<div class="lbite-menu-dietary-filter" data-lbite-menu-dietary-filter>
					<span class="lbite-menu-dietary-filter__label"><?php esc_html_e( 'Show only:', 'libre-bite' ); ?></span>
					<?php foreach ( $lbite_diet_filter_labels as $lbite_fkey => $lbite_flabel ) : ?>
						<button type="button" class="lbite-menu-dietary-filter__btn" data-diet="<?php echo esc_attr( $lbite_fkey ); ?>">
							<?php echo esc_html( $lbite_flabel ); ?>
						</button>
					<?php endforeach; ?>
					<button type="button" class="lbite-menu-dietary-filter__reset" hidden>
						<?php esc_html_e( 'Reset', 'libre-bite' ); ?>
					</button>
				</div>
				<?php
			endif;
		endif;
		?>

		<?php foreach ( $lbite_sections as $lbite_i => $lbite_section ) : ?>
			<section class="lbite-menu-section" id="lbite-menu-section-<?php echo (int) $lbite_i; ?>">

				<h2 class="lbite-menu-section__title">
					<?php
					echo esc_html(
						$lbite_section['term']
							? $lbite_section['term']->name
							: __( 'More', 'libre-bite' )
					);
					?>
				</h2>

				<?php
				if ( $lbite_section['term'] && '' !== $lbite_section['term']->description ) :
					?>
					<p class="lbite-menu-section__desc"><?php echo esc_html( $lbite_section['term']->description ); ?></p>
				<?php endif; ?>

				<div class="lbite-menu-grid">
					<?php
					foreach ( $lbite_section['products'] as $lbite_product ) :
						$lbite_pid       = $lbite_product->get_id();
						$lbite_needs_mod = $lbite_product->is_type( 'variable' )
							|| ! empty( get_post_meta( $lbite_pid, '_lbite_product_options', true ) );
						$lbite_img       = wp_get_attachment_image_url( $lbite_product->get_image_id(), 'medium' );
						$lbite_diet      = class_exists( 'LBite_Nutritional_Info' )
							? LBite_Nutritional_Info::get_product_dietary( $lbite_pid )
							: array();
						// Am gewählten Standort ausgeschlossene Artikel bleiben sichtbar,
						// statt komplett zu verschwinden (Nutzer-Fund 2026-09-29) - wie im
						// Shop markiert nur eine Klasse + ein Hinweis-Badge sie.
						$lbite_available = ! $lbite_location_id || ! class_exists( 'LBite_Locations' )
							|| LBite_Locations::is_product_available_at_location( $lbite_pid, $lbite_location_id );
						?>
						<article class="lbite-menu-item<?php echo $lbite_available ? '' : ' lbite-unavailable'; ?>" data-product-id="<?php echo esc_attr( $lbite_pid ); ?>"
							data-needs-options="<?php echo $lbite_needs_mod ? '1' : '0'; ?>"
							data-diet="<?php echo esc_attr( implode( ' ', $lbite_diet ) ); ?>">

							<?php if ( $lbite_img ) : ?>
								<div class="lbite-menu-item__media">
									<img src="<?php echo esc_url( $lbite_img ); ?>" alt="" loading="lazy">
								</div>
							<?php endif; ?>

							<div class="lbite-menu-item__body">
								<h3 class="lbite-menu-item__name"><?php echo esc_html( $lbite_product->get_name() ); ?></h3>

								<?php if ( $lbite_product->get_short_description() ) : ?>
									<p class="lbite-menu-item__desc">
										<?php echo esc_html( wp_strip_all_tags( $lbite_product->get_short_description() ) ); ?>
									</p>
								<?php endif; ?>

								<?php if ( ! empty( $lbite_diet ) && lbite_feature_enabled( 'enable_dietary_labels' ) && class_exists( 'LBite_Nutritional_Info' ) ) : ?>
									<?php $lbite_diet_labels = LBite_Nutritional_Info::get_dietary_list(); ?>
									<div class="lbite-menu-item__tags">
										<?php foreach ( $lbite_diet as $lbite_dkey ) : ?>
											<?php if ( isset( $lbite_diet_labels[ $lbite_dkey ] ) ) : ?>
												<span class="lbite-menu-tag"><?php echo esc_html( $lbite_diet_labels[ $lbite_dkey ] ); ?></span>
											<?php endif; ?>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>

								<div class="lbite-menu-item__foot">
									<span class="lbite-menu-item__price"><?php echo wp_kses_post( LBite_Menu_View::get_display_price_html( $lbite_product ) ); ?></span>
									<button type="button" class="lbite-menu-item__add"
										aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Add %s', 'libre-bite' ), $lbite_product->get_name() ) ); ?>">
										<?php echo $lbite_needs_mod ? esc_html__( 'Choose', 'libre-bite' ) : esc_html__( 'Add', 'libre-bite' ); ?>
									</button>
								</div>

								<?php if ( class_exists( 'LBite_Locations' ) ) : ?>
									<?php LBite_Locations::render_availability_hint( $lbite_product, true ); ?>
								<?php endif; ?>

								<a class="lbite-menu-item__details" href="<?php echo esc_url( get_permalink( $lbite_pid ) ); ?>">
									<?php esc_html_e( 'View product details', 'libre-bite' ); ?>
								</a>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>

	<?php endif; ?>

	<!-- Produkt-Modal -->
	<div class="lbite-menu-modal" id="lbite-menu-modal" hidden>
		<div class="lbite-menu-modal__backdrop" data-lbite-close></div>
		<div class="lbite-menu-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="lbite-menu-modal-title">
			<button type="button" class="lbite-menu-modal__close" data-lbite-close aria-label="<?php esc_attr_e( 'Close', 'libre-bite' ); ?>">&times;</button>
			<div class="lbite-menu-modal__body" id="lbite-menu-modal-body"></div>
		</div>
	</div>

	<?php if ( $lbite_cart ) : ?>
		<!-- Warenkorb -->
		<button type="button" class="lbite-menu-cart-toggle" id="lbite-menu-cart-toggle" hidden>
			<span class="lbite-menu-cart-toggle__count">0</span>
			<span class="lbite-menu-cart-toggle__label"><?php esc_html_e( 'Your order', 'libre-bite' ); ?></span>
		</button>

		<aside class="lbite-menu-cart" id="lbite-menu-cart" hidden aria-label="<?php esc_attr_e( 'Your order', 'libre-bite' ); ?>">
			<header class="lbite-menu-cart__head">
				<h2><?php esc_html_e( 'Your order', 'libre-bite' ); ?></h2>
				<button type="button" class="lbite-menu-cart__close" data-lbite-cart-close aria-label="<?php esc_attr_e( 'Close', 'libre-bite' ); ?>">&times;</button>
			</header>
			<div class="lbite-menu-cart__body" id="lbite-menu-cart-body"></div>
			<footer class="lbite-menu-cart__foot">
				<button type="button" class="lbite-menu-cart__continue" data-lbite-cart-close>
					<?php esc_html_e( 'Continue shopping', 'libre-bite' ); ?>
				</button>
				<a class="lbite-menu-cart__checkout" href="<?php echo esc_url( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '#' ); ?>">
					<?php esc_html_e( 'Go to checkout', 'libre-bite' ); ?>
				</a>
			</footer>
		</aside>
		<div class="lbite-menu-cart__scrim" id="lbite-menu-cart-scrim" hidden data-lbite-cart-close></div>
	<?php endif; ?>
</div>
