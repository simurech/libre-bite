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

	<?php if ( empty( $lbite_sections ) ) : ?>

		<p class="lbite-menu-empty">
			<?php esc_html_e( 'No dishes are available right now. Please check back later.', 'libre-bite' ); ?>
		</p>

	<?php else : ?>

		<?php if ( count( $lbite_sections ) > 1 ) : ?>
			<nav class="lbite-menu-nav" aria-label="<?php esc_attr_e( 'Menu sections', 'libre-bite' ); ?>">
				<?php foreach ( $lbite_sections as $lbite_i => $lbite_section ) : ?>
					<a class="lbite-menu-nav__item<?php echo 0 === $lbite_i ? ' is-active' : ''; ?>"
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
						?>
						<article class="lbite-menu-item" data-product-id="<?php echo esc_attr( $lbite_pid ); ?>"
							data-needs-options="<?php echo $lbite_needs_mod ? '1' : '0'; ?>">

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

								<?php if ( ! empty( $lbite_diet ) && class_exists( 'LBite_Nutritional_Info' ) ) : ?>
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
									<span class="lbite-menu-item__price"><?php echo wp_kses_post( $lbite_product->get_price_html() ); ?></span>
									<button type="button" class="lbite-menu-item__add"
										aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Add %s', 'libre-bite' ), $lbite_product->get_name() ) ); ?>">
										<?php echo $lbite_needs_mod ? esc_html__( 'Choose', 'libre-bite' ) : esc_html__( 'Add', 'libre-bite' ); ?>
									</button>
								</div>
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
				<a class="lbite-menu-cart__checkout" href="<?php echo esc_url( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '#' ); ?>">
					<?php esc_html_e( 'Go to checkout', 'libre-bite' ); ?>
				</a>
			</footer>
		</aside>
		<div class="lbite-menu-cart__scrim" id="lbite-menu-cart-scrim" hidden data-lbite-cart-close></div>
	<?php endif; ?>
</div>
