/* global lbiteReservation, jQuery */
( function ( $ ) {
	'use strict';

	var cfg = lbiteReservation;

	var $container = $( '#lbite-reservation-form-container' );
	var $form       = $( '#lbite-reservation-form' );
	var $submit     = $( '#lbite-res-submit' );
	var $error      = $( '#lbite-res-error' );
	var $success    = $( '#lbite-res-success' );
	var $steps      = $form.find( '.lbite-res-step' );
	var $dots       = $container.find( '.lbite-res-step-dot' );

	if ( ! $form.length ) {
		return;
	}

	/**
	 * Prüft alle Pflichtfelder innerhalb eines Schritts (native HTML5-Validierung).
	 * Da nur der aktive Schritt sichtbar ist, funktioniert reportValidity() korrekt
	 * (versteckte Felder sind von der Constraint-Validierung ausgenommen).
	 */
	function stepIsValid( $step ) {
		var valid = true;

		$step.find( 'input, select, textarea' ).each( function () {
			if ( ! this.checkValidity() ) {
				this.reportValidity();
				valid = false;
				return false; // Schleife bei erstem ungültigen Feld abbrechen.
			}
		} );

		return valid;
	}

	/**
	 * Wechselt zum angegebenen Schritt (1-basiert) und aktualisiert die Schritt-Anzeige.
	 *
	 * @param {number} targetStep Zielschritt
	 */
	function goToStep( targetStep ) {
		$steps.each( function () {
			var $step = $( this );
			$step.toggleClass( 'active', parseInt( $step.data( 'step' ), 10 ) === targetStep );
		} );

		$dots.each( function () {
			var $dot     = $( this );
			var dotStep  = parseInt( $dot.data( 'step-dot' ), 10 );
			$dot.toggleClass( 'active', dotStep === targetStep );
			$dot.toggleClass( 'completed', dotStep < targetStep );
		} );

		// Beim Erreichen des Fokus-Elements sanft nach oben scrollen (bei langen Seiten hilfreich).
		if ( $container.length && $container[ 0 ].scrollIntoView ) {
			$container[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
		}
	}

	/**
	 * Füllt die Zusammenfassung in Schritt 3 aus den bisherigen Formularwerten.
	 */
	function populateSummary() {
		var $locationSelect = $( '#lbite-res-location' );
		var locationLabel   = $locationSelect.length ? $locationSelect.find( 'option:selected' ).text() : '';

		var dateValue = $( '#lbite-res-date' ).val();
		var dateLabel = dateValue;
		if ( dateValue ) {
			var parsedDate = new Date( dateValue + 'T00:00:00' );
			if ( ! isNaN( parsedDate.getTime() ) ) {
				dateLabel = parsedDate.toLocaleDateString();
			}
		}

		$( '#lbite-res-summary-location' ).text( locationLabel );
		$( '#lbite-res-summary-date' ).text( dateLabel );
		$( '#lbite-res-summary-time' ).text( $( '#lbite-res-time' ).val() );
		$( '#lbite-res-summary-guests' ).text( $( '#lbite-res-guests' ).val() );
		$( '#lbite-res-summary-name' ).text( $( '#lbite-res-name' ).val() );
		$( '#lbite-res-summary-email' ).text( $( '#lbite-res-email' ).val() );

		var $phoneRow = $( '#lbite-res-summary-phone-row' );
		if ( $phoneRow.length ) {
			var phoneValue = $( '#lbite-res-phone' ).val();
			$( '#lbite-res-summary-phone' ).text( phoneValue );
			$phoneRow.toggle( !! phoneValue );
		}

		var $notesRow = $( '#lbite-res-summary-notes-row' );
		if ( $notesRow.length ) {
			var notesValue = $( '#lbite-res-notes' ).val();
			$( '#lbite-res-summary-notes' ).text( notesValue );
			$notesRow.toggle( !! notesValue );
		}
	}

	// "Weiter"-Buttons
	$form.on( 'click', '.lbite-res-step-next', function () {
		var $currentStep = $( this ).closest( '.lbite-res-step' );

		if ( ! stepIsValid( $currentStep ) ) {
			return;
		}

		var nextStep = parseInt( $( this ).data( 'next' ), 10 );

		if ( 3 === nextStep ) {
			populateSummary();
		}

		goToStep( nextStep );
	} );

	// "Zurück"-Buttons (keine Validierung nötig)
	$form.on( 'click', '.lbite-res-step-back', function () {
		goToStep( parseInt( $( this ).data( 'back' ), 10 ) );
	} );

	// Formular absenden (nur über den finalen Absenden-Button in Schritt 3 erreichbar)
	$form.on( 'submit', function ( e ) {
		e.preventDefault();

		$error.hide().text( '' );
		$submit.prop( 'disabled', true ).text( cfg.strings.sending );

		$.ajax( {
			url    : cfg.ajaxUrl,
			method : 'POST',
			data   : $form.serialize() + '&action=lbite_submit_reservation&nonce=' + cfg.nonce,
			success: function ( response ) {
				if ( response.success ) {
					$form.hide();
					$container.find( '.lbite-res-step-indicator' ).hide();
					$success.show();
				} else {
					var lbiteMsg = ( response.data && response.data.message ) ? response.data.message : cfg.strings.error;
					$error.text( lbiteMsg ).show();
					$submit.prop( 'disabled', false ).text( $submit.data( 'label-default' ) || 'Reservierungsanfrage senden' );
				}
			},
			error: function () {
				$error.text( cfg.strings.error ).show();
				$submit.prop( 'disabled', false );
			},
		} );
	} );

	// Original-Label für den Button merken
	$submit.data( 'label-default', $submit.text().trim() );

} )( jQuery );
