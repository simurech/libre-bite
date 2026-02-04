<?php
/**
 * Template: Quick Start Guide
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$locations_count = wp_count_posts( 'lb_location' )->publish;
$products_count  = wp_count_posts( 'product' )->publish;
?>

<div class="wrap lb-quick-start">
	<h1><?php esc_html_e( 'Willkommen bei Libre Bite 🚀', 'libre-bite' ); ?></h1>

	<p class="about-text">
		<?php esc_html_e( 'Vielen Dank, dass Sie Libre Bite installiert haben! Befolgen Sie diese Schritte, um Ihr Gastronomie-System einzurichten.', 'libre-bite' ); ?>
	</p>

	<div class="lb-steps-container">

		<!-- Schritt 1: Standort -->
		<div class="lb-step <?php echo $locations_count > 0 ? 'completed' : ''; ?>">
			<div class="lb-step-icon">
				<?php if ( $locations_count > 0 ) : ?>
					<span class="dashicons dashicons-yes-alt"></span>
				<?php else : ?>
					<span class="dashicons dashicons-location"></span>
				<?php endif; ?>
			</div>
			<div class="lb-step-content">
				<h3><?php esc_html_e( '1. Standort erstellen', 'libre-bite' ); ?></h3>
				<p><?php esc_html_e( 'Erstellen Sie Ihren ersten Standort mit Adresse und Öffnungszeiten.', 'libre-bite' ); ?></p>
				<?php if ( $locations_count > 0 ) : ?>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lb_location' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Standorte verwalten', 'libre-bite' ); ?>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=lb_location' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Jetzt Standort anlegen', 'libre-bite' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<!-- Schritt 2: Produkte -->
		<div class="lb-step <?php echo $products_count > 0 ? 'completed' : ''; ?>">
			<div class="lb-step-icon">
				<?php if ( $products_count > 0 ) : ?>
					<span class="dashicons dashicons-yes-alt"></span>
				<?php else : ?>
					<span class="dashicons dashicons-cart"></span>
				<?php endif; ?>
			</div>
			<div class="lb-step-content">
				<h3><?php esc_html_e( '2. Produkte anlegen', 'libre-bite' ); ?></h3>
				<p><?php esc_html_e( 'Legen Sie Ihre Speisen und Getränke als WooCommerce-Produkte an.', 'libre-bite' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Zu den Produkten', 'libre-bite' ); ?>
				</a>
			</div>
		</div>

		<!-- Schritt 3: POS testen -->
		<div class="lb-step">
			<div class="lb-step-icon">
				<span class="dashicons dashicons-store"></span>
			</div>
			<div class="lb-step-content">
				<h3><?php esc_html_e( '3. Kassensystem öffnen', 'libre-bite' ); ?></h3>
				<p><?php esc_html_e( 'Starten Sie das POS-System, um Bestellungen vor Ort aufzunehmen.', 'libre-bite' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-pos' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Kassensystem öffnen', 'libre-bite' ); ?>
				</a>
			</div>
		</div>

		<!-- Schritt 4: Kanban Board -->
		<div class="lb-step">
			<div class="lb-step-icon">
				<span class="dashicons dashicons-list-view"></span>
			</div>
			<div class="lb-step-content">
				<h3><?php esc_html_e( '4. Küchen-Monitor', 'libre-bite' ); ?></h3>
				<p><?php esc_html_e( 'Verwalten Sie eingehende Bestellungen live auf dem Kanban-Board.', 'libre-bite' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lb-order-board' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Zum Dashboard', 'libre-bite' ); ?>
				</a>
			</div>
		</div>

	</div>
</div>

<style>
.lb-steps-container {
	max-width: 800px;
	margin-top: 30px;
}
.lb-step {
	background: #fff;
	border: 1px solid #ccd0d4;
	padding: 20px;
	margin-bottom: 20px;
	display: flex;
	align-items: flex-start;
	border-radius: 4px;
	box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}
.lb-step.completed {
	border-left: 5px solid #46b450;
}
.lb-step-icon {
	margin-right: 20px;
	font-size: 30px;
	color: #444;
	width: 40px;
	text-align: center;
}
.lb-step.completed .lb-step-icon {
	color: #46b450;
}
.lb-step-icon .dashicons {
	font-size: 40px;
	width: 40px;
	height: 40px;
}
.lb-step-content h3 {
	margin-top: 0;
}
</style>
