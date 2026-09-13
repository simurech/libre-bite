<?php
/**
 * Einrichtungsassistent – Ansicht
 *
 * Bewusst ohne WordPress-Chrome: die Ersteinrichtung soll wie ein
 * eigenständiger Ablauf wirken und nicht wie eine weitere Einstellungsseite
 * zwischen zwanzig Menüpunkten.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lbite_checks     = LBite_Setup_Wizard::get_system_checks();
$lbite_wiz_feats  = LBite_Setup_Wizard::get_wizard_features();
$lbite_defs       = LBite_Features::get_definitions();
$lbite_blocking   = false;

foreach ( $lbite_checks as $lbite_check ) {
	if ( ! $lbite_check['ok'] && false !== strpos( $lbite_check['label'], 'WooCommerce' ) ) {
		$lbite_blocking = true;
	}
}
?>
<div class="lbite-wizard" id="lbite-wizard">

	<div class="lbite-wizard__card">

		<header class="lbite-wizard__head">
			<div class="lbite-wizard__brand"><?php echo esc_html( get_option( 'lbite_brand_name', 'Libre Bite' ) ); ?></div>
			<nav class="lbite-wizard__steps" aria-label="<?php esc_attr_e( 'Setup steps', 'libre-bite' ); ?>">
				<span class="is-active" data-step-label="1"><?php esc_html_e( 'Welcome', 'libre-bite' ); ?></span>
				<span data-step-label="2"><?php esc_html_e( 'Check', 'libre-bite' ); ?></span>
				<span data-step-label="3"><?php esc_html_e( 'Modules', 'libre-bite' ); ?></span>
				<span data-step-label="4"><?php esc_html_e( 'Sample data', 'libre-bite' ); ?></span>
			</nav>
		</header>

		<!-- Schritt 1 -->
		<section class="lbite-wizard__step is-active" data-step="1">
			<h1><?php esc_html_e( 'Welcome to Libre Bite', 'libre-bite' ); ?></h1>
			<p class="lbite-wizard__lead">
				<?php esc_html_e( 'This short setup gets your restaurant running. It takes about two minutes and nothing here is permanent — every choice can be changed later under Settings.', 'libre-bite' ); ?>
			</p>
			<ul class="lbite-wizard__list">
				<li><?php esc_html_e( 'Check that your shop is ready to take orders', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'Pick the modules your business actually needs', 'libre-bite' ); ?></li>
				<li><?php esc_html_e( 'Optionally start with a sample menu instead of an empty shop', 'libre-bite' ); ?></li>
			</ul>
			<div class="lbite-wizard__actions">
				<a class="lbite-wizard__skip" href="<?php echo esc_url( admin_url( 'admin.php?page=libre-bite' ) ); ?>">
					<?php esc_html_e( 'Skip setup', 'libre-bite' ); ?>
				</a>
				<button type="button" class="lbite-wizard__btn lbite-wizard__next"><?php esc_html_e( 'Get started', 'libre-bite' ); ?></button>
			</div>
		</section>

		<!-- Schritt 2 -->
		<section class="lbite-wizard__step" data-step="2">
			<h1><?php esc_html_e( 'System check', 'libre-bite' ); ?></h1>
			<p class="lbite-wizard__lead">
				<?php esc_html_e( 'A quick look at whether everything Libre Bite relies on is in place.', 'libre-bite' ); ?>
			</p>

			<ul class="lbite-wizard__checks">
				<?php foreach ( $lbite_checks as $lbite_check ) : ?>
					<li class="<?php echo $lbite_check['ok'] ? 'is-ok' : 'is-warn'; ?>">
						<span class="lbite-wizard__check-icon" aria-hidden="true"><?php echo $lbite_check['ok'] ? '&#10003;' : '!'; ?></span>
						<span>
							<strong><?php echo esc_html( $lbite_check['label'] ); ?></strong>
							<?php if ( ! $lbite_check['ok'] ) : ?>
								<em><?php echo esc_html( $lbite_check['hint'] ); ?></em>
							<?php endif; ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php if ( $lbite_blocking ) : ?>
				<p class="lbite-wizard__blocking">
					<?php esc_html_e( 'WooCommerce must be installed and active before Libre Bite can do anything. Please take care of that first.', 'libre-bite' ); ?>
				</p>
			<?php endif; ?>

			<div class="lbite-wizard__actions">
				<button type="button" class="lbite-wizard__back"><?php esc_html_e( 'Back', 'libre-bite' ); ?></button>
				<button type="button" class="lbite-wizard__btn lbite-wizard__next" <?php disabled( $lbite_blocking ); ?>><?php esc_html_e( 'Continue', 'libre-bite' ); ?></button>
			</div>
		</section>

		<!-- Schritt 3 -->
		<section class="lbite-wizard__step" data-step="3">
			<h1><?php esc_html_e( 'Which modules do you need?', 'libre-bite' ); ?></h1>
			<p class="lbite-wizard__lead">
				<?php esc_html_e( 'Only what you switch on appears in the menu. Everything else stays out of your way and can be enabled any time.', 'libre-bite' ); ?>
			</p>

			<div class="lbite-wizard__modules">
				<?php foreach ( $lbite_wiz_feats as $lbite_key ) : ?>
					<?php if ( ! isset( $lbite_defs[ $lbite_key ] ) ) { continue; } ?>
					<label class="lbite-wizard__module">
						<input type="checkbox" name="lbite_wizard_features[]" value="<?php echo esc_attr( $lbite_key ); ?>"
							<?php checked( lbite_feature_enabled( $lbite_key ) ); ?>>
						<span>
							<strong><?php echo esc_html( $lbite_defs[ $lbite_key ]['label'] ); ?></strong>
							<em><?php echo esc_html( $lbite_defs[ $lbite_key ]['description'] ); ?></em>
						</span>
					</label>
				<?php endforeach; ?>
			</div>

			<div class="lbite-wizard__actions">
				<button type="button" class="lbite-wizard__back"><?php esc_html_e( 'Back', 'libre-bite' ); ?></button>
				<button type="button" class="lbite-wizard__btn lbite-wizard__next"><?php esc_html_e( 'Continue', 'libre-bite' ); ?></button>
			</div>
		</section>

		<!-- Schritt 4 -->
		<section class="lbite-wizard__step" data-step="4">
			<h1><?php esc_html_e( 'Start with a sample menu?', 'libre-bite' ); ?></h1>
			<p class="lbite-wizard__lead">
				<?php esc_html_e( 'An empty shop is hard to judge. Libre Bite can create one location with opening hours, three categories, eight dishes and a few add-ons, so you can click through everything straight away.', 'libre-bite' ); ?>
			</p>
			<p class="lbite-wizard__note">
				<?php esc_html_e( 'Everything created here is marked as sample content and can be deleted like any other product. Running the import twice does not create duplicates.', 'libre-bite' ); ?>
			</p>

			<div class="lbite-wizard__result" id="lbite-wizard-result" hidden></div>

			<div class="lbite-wizard__actions">
				<button type="button" class="lbite-wizard__back"><?php esc_html_e( 'Back', 'libre-bite' ); ?></button>
				<button type="button" class="lbite-wizard__btn lbite-wizard__btn--ghost" id="lbite-wizard-import">
					<?php esc_html_e( 'Create sample menu', 'libre-bite' ); ?>
				</button>
				<button type="button" class="lbite-wizard__btn" id="lbite-wizard-finish">
					<?php esc_html_e( 'Finish setup', 'libre-bite' ); ?>
				</button>
			</div>
		</section>

	</div>
</div>

<script>
( function () {
	var root = document.getElementById( 'lbite-wizard' );
	if ( ! root ) { return; }

	var config = <?php echo wp_json_encode( array(
		'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'lbite_setup_nonce' ),
		'importing' => __( 'Creating sample menu…', 'libre-bite' ),
		'imported' => __( 'Sample menu created.', 'libre-bite' ),
		'failed'   => __( 'Something went wrong. Please try again.', 'libre-bite' ),
		'saving'   => __( 'Saving…', 'libre-bite' ),
	) ); ?>;

	var steps = root.querySelectorAll( '.lbite-wizard__step' );
	var marks = root.querySelectorAll( '.lbite-wizard__steps span' );
	var current = 1;

	function show( step ) {
		current = Math.min( Math.max( step, 1 ), steps.length );
		steps.forEach( function ( el ) {
			el.classList.toggle( 'is-active', Number( el.dataset.step ) === current );
		} );
		marks.forEach( function ( el ) {
			el.classList.toggle( 'is-active', Number( el.dataset.stepLabel ) <= current );
		} );
		root.scrollIntoView( { behavior: 'smooth', block: 'start' } );
	}

	root.addEventListener( 'click', function ( e ) {
		if ( e.target.classList.contains( 'lbite-wizard__next' ) ) { show( current + 1 ); }
		if ( e.target.classList.contains( 'lbite-wizard__back' ) ) { show( current - 1 ); }
	} );

	function post( action, extra, done ) {
		var body = new URLSearchParams();
		body.append( 'action', action );
		body.append( 'nonce', config.nonce );
		Object.keys( extra || {} ).forEach( function ( k ) {
			if ( Array.isArray( extra[ k ] ) ) {
				extra[ k ].forEach( function ( v ) { body.append( k + '[]', v ); } );
			} else {
				body.append( k, extra[ k ] );
			}
		} );

		fetch( config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( r ) { return r.json(); } )
			.then( done )
			.catch( function () { done( null ); } );
	}

	var $result = document.getElementById( 'lbite-wizard-result' );

	document.getElementById( 'lbite-wizard-import' ).addEventListener( 'click', function () {
		var btn = this;
		btn.disabled = true;
		$result.hidden = false;
		$result.className = 'lbite-wizard__result';
		$result.textContent = config.importing;

		post( 'lbite_setup_import_demo', {}, function ( res ) {
			btn.disabled = false;
			if ( res && res.success ) {
				$result.classList.add( 'is-ok' );
				$result.textContent = config.imported;
			} else {
				$result.classList.add( 'is-error' );
				$result.textContent = ( res && res.data && res.data.message ) || config.failed;
			}
		} );
	} );

	document.getElementById( 'lbite-wizard-finish' ).addEventListener( 'click', function () {
		var btn = this;
		btn.disabled = true;
		btn.textContent = config.saving;

		var chosen = [];
		root.querySelectorAll( 'input[name="lbite_wizard_features[]"]:checked' ).forEach( function ( el ) {
			chosen.push( el.value );
		} );

		post( 'lbite_setup_finish', { features: chosen }, function ( res ) {
			if ( res && res.success && res.data && res.data.redirect ) {
				window.location.href = res.data.redirect;
			} else {
				btn.disabled = false;
				$result.hidden = false;
				$result.className = 'lbite-wizard__result is-error';
				$result.textContent = config.failed;
			}
		} );
	} );
} )();
</script>
