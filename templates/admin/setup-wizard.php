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
$lbite_catalogue  = LBite_Setup_Wizard::get_module_catalogue();
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
			<div class="lbite-wizard__progress" aria-live="polite">
				<div class="lbite-wizard__progress-bar">
					<div class="lbite-wizard__progress-fill" id="lbite-wizard-progress-fill" style="width: 0%;"></div>
				</div>
				<span class="lbite-wizard__progress-label" id="lbite-wizard-progress-label"></span>
			</div>
		</header>

		<!-- Schritt 1 -->
		<section class="lbite-wizard__step is-active" data-step="1">
			<h1><?php esc_html_e( 'Welcome to Libre Bite', 'libre-bite' ); ?></h1>
			<p class="lbite-wizard__lead">
				<?php esc_html_e( 'This short setup gets your restaurant running. It takes about two minutes and nothing here is permanent — every choice can be changed later under Settings.', 'libre-bite' ); ?>
			</p>
			<div class="lbite-wizard__highlights">
				<div class="lbite-card lbite-wizard__highlight">
					<span class="dashicons dashicons-yes-alt lbite-wizard__highlight-icon" aria-hidden="true"></span>
					<strong><?php esc_html_e( 'Quick check', 'libre-bite' ); ?></strong>
					<p><?php esc_html_e( 'Confirms your shop is ready to take orders.', 'libre-bite' ); ?></p>
				</div>
				<div class="lbite-card lbite-wizard__highlight">
					<span class="dashicons dashicons-admin-plugins lbite-wizard__highlight-icon" aria-hidden="true"></span>
					<strong><?php esc_html_e( 'Pick your modules', 'libre-bite' ); ?></strong>
					<p><?php esc_html_e( 'Only what you switch on ever appears in the menu.', 'libre-bite' ); ?></p>
				</div>
				<div class="lbite-card lbite-wizard__highlight">
					<span class="dashicons dashicons-carrot lbite-wizard__highlight-icon" aria-hidden="true"></span>
					<strong><?php esc_html_e( 'Sample menu', 'libre-bite' ); ?></strong>
					<p><?php esc_html_e( 'Optionally start from a filled shop instead of an empty one.', 'libre-bite' ); ?></p>
				</div>
			</div>
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

		<?php
		// Je eingeschaltetem Modul ein eigener Schritt. Gerendert werden alle
		// in Frage kommenden Module; welche davon sichtbar sind, entscheidet
		// das Skript anhand der Auswahl aus Schritt 3.
		$lbite_step_no = 3;
		foreach ( $lbite_catalogue as $lbite_mod => $lbite_cfg ) :
			if ( empty( $lbite_cfg['settings'] ) && empty( $lbite_cfg['hint'] ) ) { continue; }
			if ( ! isset( $lbite_defs[ $lbite_mod ] ) ) { continue; }
			$lbite_step_no++;
			?>
			<section class="lbite-wizard__step" data-step="<?php echo esc_attr( $lbite_step_no ); ?>" data-module="<?php echo esc_attr( $lbite_mod ); ?>">
				<h1><?php echo esc_html( $lbite_defs[ $lbite_mod ]['label'] ); ?></h1>
				<p class="lbite-wizard__lead"><?php echo esc_html( $lbite_defs[ $lbite_mod ]['description'] ); ?></p>

				<?php if ( ! empty( $lbite_cfg['hint'] ) ) : ?>
					<p class="lbite-wizard__note"><?php echo esc_html( $lbite_cfg['hint'] ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $lbite_cfg['settings'] ) ) : ?>
					<div class="lbite-wizard__fields">
						<?php foreach ( $lbite_cfg['settings'] as $lbite_set ) : ?>
							<?php $lbite_val = LBite_Setup_Wizard::get_setting_value( $lbite_set ); ?>
							<div class="lbite-wizard__field">
								<?php if ( 'checkbox' === $lbite_set['type'] ) : ?>
									<label class="lbite-wizard__module">
										<input type="hidden" name="lbite_set[<?php echo esc_attr( $lbite_set['option'] ); ?>]" value="0">
										<input type="checkbox" id="<?php echo esc_attr( $lbite_set['option'] ); ?>" name="lbite_set[<?php echo esc_attr( $lbite_set['option'] ); ?>]" value="1" <?php checked( (int) $lbite_val, 1 ); ?>>
										<span>
											<strong><?php echo esc_html( $lbite_set['label'] ); ?></strong>
											<?php if ( ! empty( $lbite_set['description'] ) ) : ?>
												<em><?php echo esc_html( $lbite_set['description'] ); ?></em>
											<?php endif; ?>
										</span>
									</label>
								<?php elseif ( 'select' === $lbite_set['type'] ) : ?>
									<label>
										<strong><?php echo esc_html( $lbite_set['label'] ); ?></strong>
										<select id="<?php echo esc_attr( $lbite_set['option'] ); ?>" name="lbite_set[<?php echo esc_attr( $lbite_set['option'] ); ?>]">
											<?php foreach ( $lbite_set['choices'] as $lbite_ck => $lbite_cl ) : ?>
												<option value="<?php echo esc_attr( $lbite_ck ); ?>" <?php selected( $lbite_val, $lbite_ck ); ?>><?php echo esc_html( $lbite_cl ); ?></option>
											<?php endforeach; ?>
										</select>
									</label>
								<?php else : ?>
									<label>
										<strong><?php echo esc_html( $lbite_set['label'] ); ?></strong>
										<span class="lbite-wizard__inline">
											<input type="number"
												id="<?php echo esc_attr( $lbite_set['option'] ); ?>"
												name="lbite_set[<?php echo esc_attr( $lbite_set['option'] ); ?>]"
												value="<?php echo esc_attr( $lbite_val ); ?>"
												<?php echo isset( $lbite_set['min'] ) ? ' min="' . esc_attr( $lbite_set['min'] ) . '"' : ''; ?>
												<?php echo isset( $lbite_set['max'] ) ? ' max="' . esc_attr( $lbite_set['max'] ) . '"' : ''; ?>
												<?php echo isset( $lbite_set['step'] ) ? ' step="' . esc_attr( $lbite_set['step'] ) . '"' : ''; ?>>
											<?php if ( ! empty( $lbite_set['suffix'] ) ) : ?>
												<em><?php echo esc_html( $lbite_set['suffix'] ); ?></em>
											<?php endif; ?>
										</span>
										<?php if ( ! empty( $lbite_set['description'] ) ) : ?>
											<em class="lbite-wizard__hint"><?php echo esc_html( $lbite_set['description'] ); ?></em>
										<?php endif; ?>
									</label>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>

					<?php if ( 'enable_stampcard' === $lbite_mod ) : ?>
						<div id="lbite-wizard-stampcard-preview" class="lbite-wizard__preview" aria-hidden="true">
							<p class="lbite-wizard__preview-label"><?php esc_html_e( 'Preview: progress card shown to the guest', 'libre-bite' ); ?></p>
							<div class="lbite-wizard__stampcard-dots" id="lbite-wizard-stampcard-dots"></div>
							<p class="lbite-wizard__preview-note" id="lbite-wizard-stampcard-note"></p>
						</div>
						<script>
						(function() {
							var target   = document.getElementById('lbite_stampcard_target');
							var discount = document.getElementById('lbite_stampcard_discount');
							var dotsWrap = document.getElementById('lbite-wizard-stampcard-dots');
							var noteEl   = document.getElementById('lbite-wizard-stampcard-note');
							function update() {
								if ( ! dotsWrap ) { return; }
								var t = Math.max(2, parseInt(target && target.value, 10) || 10);
								var d = parseInt(discount && discount.value, 10) || 0;
								dotsWrap.innerHTML = '';
								for (var i = 0; i < t; i++) {
									var dot = document.createElement('span');
									dot.className = 'lbite-wizard__stampcard-dot' + (i === 0 ? ' is-filled' : '');
									dotsWrap.appendChild(dot);
								}
								noteEl.textContent = t + ' ' + <?php echo wp_json_encode( __( 'stamps →', 'libre-bite' ) ); ?> + ' ' + d + '% ' + <?php echo wp_json_encode( __( 'off the next order', 'libre-bite' ) ); ?>;
							}
							if ( target ) { target.addEventListener('input', update); }
							if ( discount ) { discount.addEventListener('input', update); }
							update();
						})();
						</script>
					<?php endif; ?>

					<?php if ( 'enable_tips' === $lbite_mod ) : ?>
						<div id="lbite-wizard-tip-preview" class="lbite-wizard__preview" aria-hidden="true">
							<p class="lbite-wizard__preview-label"><?php esc_html_e( 'Preview: tip buttons shown at checkout', 'libre-bite' ); ?></p>
							<div class="lbite-wizard__tip-row" id="lbite-wizard-tip-row"></div>
						</div>
						<script>
						(function() {
							var mode = document.getElementById('lbite_tip_mode');
							var p1 = document.getElementById('lbite_tip_percentage_1');
							var p2 = document.getElementById('lbite_tip_percentage_2');
							var p3 = document.getElementById('lbite_tip_percentage_3');
							var row = document.getElementById('lbite-wizard-tip-row');
							var currency = <?php echo wp_json_encode( function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '%' ); ?>;
							function update() {
								if ( ! row ) { return; }
								var unit = ( mode && 'fixed' === mode.value ) ? currency : '%';
								row.innerHTML = '';
								[ p1, p2, p3 ].forEach(function(input) {
									var pill = document.createElement('span');
									pill.className = 'lbite-wizard__tip-pill';
									pill.textContent = (input ? (input.value || '0') : '0') + unit;
									row.appendChild(pill);
								});
							}
							[ mode, p1, p2, p3 ].forEach(function(el) {
								if ( el ) { el.addEventListener('input', update); el.addEventListener('change', update); }
							});
							update();
						})();
						</script>
					<?php endif; ?>
				<?php endif; ?>

				<?php if ( ! empty( $lbite_cfg['link_tab'] ) ) : ?>
					<p class="lbite-wizard__note">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-settings&tab=' . $lbite_cfg['link_tab'] ) ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( 'All settings for this module', 'libre-bite' ); ?>
						</a>
					</p>
				<?php endif; ?>

				<div class="lbite-wizard__actions">
					<button type="button" class="lbite-wizard__back"><?php esc_html_e( 'Back', 'libre-bite' ); ?></button>
					<button type="button" class="lbite-wizard__skip"><?php esc_html_e( 'Skip', 'libre-bite' ); ?></button>
					<button type="button" class="lbite-wizard__btn lbite-wizard__next"><?php esc_html_e( 'Continue', 'libre-bite' ); ?></button>
				</div>
			</section>
		<?php endforeach; ?>

		<?php
		$lbite_refs = array_filter(
			LBite_Setup_Wizard::get_referenced_modules(),
			function ( $tab, $key ) use ( $lbite_defs ) { return isset( $lbite_defs[ $key ] ); },
			ARRAY_FILTER_USE_BOTH
		);
		if ( $lbite_refs ) :
			$lbite_step_no++;
			?>
			<section class="lbite-wizard__step" data-step="<?php echo esc_attr( $lbite_step_no ); ?>">
				<h1><?php esc_html_e( 'Further modules', 'libre-bite' ); ?></h1>
				<p class="lbite-wizard__lead">
					<?php esc_html_e( 'These have more settings than fit into a setup flow. They are listed here so you know where to find them.', 'libre-bite' ); ?>
				</p>
				<div class="lbite-wizard__fields">
					<?php foreach ( $lbite_refs as $lbite_key => $lbite_tab ) : ?>
						<p class="lbite-wizard__note">
							<strong><?php echo esc_html( $lbite_defs[ $lbite_key ]['label'] ); ?></strong> —
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=lbite-settings&tab=' . $lbite_tab ) ); ?>" target="_blank" rel="noopener">
								<?php esc_html_e( 'open settings', 'libre-bite' ); ?>
							</a>
						</p>
					<?php endforeach; ?>
				</div>
				<div class="lbite-wizard__actions">
					<button type="button" class="lbite-wizard__back"><?php esc_html_e( 'Back', 'libre-bite' ); ?></button>
					<button type="button" class="lbite-wizard__btn lbite-wizard__next"><?php esc_html_e( 'Continue', 'libre-bite' ); ?></button>
				</div>
			</section>
		<?php endif; ?>

		<!-- Letzter Schritt: Beispieldaten -->
		<section class="lbite-wizard__step" data-step="<?php echo esc_attr( $lbite_step_no + 1 ); ?>">
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
		/* translators: 1: current step number, 2: total number of steps */
		'stepOf'   => __( 'Step %1$d of %2$d', 'libre-bite' ),
	) ); ?>;

	var steps = root.querySelectorAll( '.lbite-wizard__step' );
	var current = 1;

	// Ein Schritt ist nur dann an der Reihe, wenn sein Modul in Schritt 3
	// eingeschaltet wurde. Ausgeschaltete Module werden uebersprungen, damit
	// niemand etwas konfiguriert, das er gar nicht nutzt.
	function relevant( el ) {
		var modul = el.dataset.module;
		if ( ! modul ) { return true; }
		var box = root.querySelector( 'input[name="lbite_wizard_features[]"][value="' + modul + '"]' );
		return !! ( box && box.checked );
	}

	function relevantSteps() {
		return Array.prototype.filter.call( steps, relevant );
	}

	function updateProgress() {
		var rel = relevantSteps();
		var total = rel.length;
		var pos = 1;
		for ( var i = 0; i < rel.length; i++ ) {
			if ( Number( rel[ i ].dataset.step ) === current ) { pos = i + 1; break; }
		}
		var fill = document.getElementById( 'lbite-wizard-progress-fill' );
		var label = document.getElementById( 'lbite-wizard-progress-label' );
		if ( fill ) { fill.style.width = Math.round( ( pos / total ) * 100 ) + '%'; }
		if ( label ) { label.textContent = config.stepOf.replace( '%1$d', pos ).replace( '%2$d', total ); }
	}

	function show( step ) {
		current = Math.min( Math.max( step, 1 ), steps.length );
		steps.forEach( function ( el ) {
			el.classList.toggle( 'is-active', Number( el.dataset.step ) === current );
		} );
		updateProgress();
		root.scrollIntoView( { behavior: 'smooth', block: 'start' } );
	}

	function naechster( von, richtung ) {
		var ziel = von + richtung;
		while ( ziel >= 1 && ziel <= steps.length ) {
			var el = root.querySelector( '.lbite-wizard__step[data-step="' + ziel + '"]' );
			if ( ! el || relevant( el ) ) { return ziel; }
			ziel += richtung;
		}
		return Math.min( Math.max( von, 1 ), steps.length );
	}

	root.addEventListener( 'click', function ( e ) {
		if ( e.target.classList.contains( 'lbite-wizard__next' ) || e.target.classList.contains( 'lbite-wizard__skip' ) ) {
			show( naechster( current, 1 ) );
		}
		if ( e.target.classList.contains( 'lbite-wizard__back' ) ) {
			show( naechster( current, -1 ) );
		}
	} );

	// Ein Modul-Häkchen in Schritt 3 verändert, wie viele Schritte insgesamt
	// relevant sind - die Anzeige muss das sofort widerspiegeln, auch bevor
	// weitergeblättert wird.
	root.querySelectorAll( 'input[name="lbite_wizard_features[]"]' ).forEach( function ( box ) {
		box.addEventListener( 'change', updateProgress );
	} );

	updateProgress();

	function post( action, extra, done ) {
		var body = new URLSearchParams();
		body.append( 'action', action );
		body.append( 'nonce', config.nonce );
		Object.keys( extra || {} ).forEach( function ( k ) {
			if ( Array.isArray( extra[ k ] ) ) {
				extra[ k ].forEach( function ( v ) { body.append( k + '[]', v ); } );
			} else if ( extra[ k ] && 'object' === typeof extra[ k ] ) {
				// Verschachtelt uebergeben, damit PHP daraus ein Array baut.
				Object.keys( extra[ k ] ).forEach( function ( unter ) {
					body.append( k + '[' + unter + ']', extra[ k ][ unter ] );
				} );
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

		// Nur Felder aus Schritten mitsenden, deren Modul eingeschaltet ist –
		// so fasst der Assistent die Einstellungen abgewaehlter Module nicht an.
		var settings = {};
		root.querySelectorAll( '.lbite-wizard__step[data-module]' ).forEach( function ( sec ) {
			if ( ! relevant( sec ) ) { return; }
			sec.querySelectorAll( '[name^="lbite_set["]' ).forEach( function ( feld ) {
				var name = feld.name.slice( 'lbite_set['.length, -1 );
				if ( 'checkbox' === feld.type && ! feld.checked ) { return; }
				settings[ name ] = feld.value;
			} );
		} );

		post( 'lbite_setup_finish', { features: chosen, settings: settings }, function ( res ) {
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
