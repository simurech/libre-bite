<?php
/**
 * Abholanzeige – eigenständige Vollbildseite ohne Theme
 *
 * Erwartet: $lbite_display_title, $lbite_display_data (siehe LBite_Pickup_Display::get_data()).
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template, wird innerhalb einer Klassenmethode eingebunden.
$lbite_heading = '' !== $lbite_display_title ? $lbite_display_title : $lbite_display_data['location'];
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( $lbite_heading ); ?></title>
	<?php wp_print_styles( array( 'lbite-pickup-display' ) ); ?>
</head>
<body class="lbite-pd">
	<header class="lbite-pd__header">
		<h1 class="lbite-pd__title"><?php echo esc_html( $lbite_heading ); ?></h1>
		<div class="lbite-pd__clock" id="lbite-pd-clock" aria-hidden="true"><?php echo esc_html( wp_date( get_option( 'time_format', 'H:i' ) ) ); ?></div>
	</header>

	<main class="lbite-pd__columns" id="lbite-pd-columns" style="--lbite-pd-cols: <?php echo esc_attr( max( 1, count( $lbite_display_data['columns'] ) ) ); ?>;">
		<?php foreach ( $lbite_display_data['columns'] as $lbite_column ) : ?>
			<section class="lbite-pd__column<?php echo $lbite_column['done'] ? ' is-done' : ''; ?>" data-key="<?php echo esc_attr( $lbite_column['key'] ); ?>" aria-live="polite">
				<h2 class="lbite-pd__column-title"><?php echo esc_html( $lbite_column['label'] ); ?></h2>
				<ul class="lbite-pd__numbers">
					<?php foreach ( $lbite_column['numbers'] as $lbite_number ) : ?>
						<li class="lbite-pd__number"><?php echo esc_html( $lbite_number ); ?></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endforeach; ?>
	</main>

	<p class="lbite-pd__status" id="lbite-pd-status" role="status"></p>
	<button type="button" class="lbite-pd__sound" id="lbite-pd-sound" hidden></button>

	<?php wp_print_scripts( array( 'lbite-pickup-display' ) ); ?>
</body>
</html>
