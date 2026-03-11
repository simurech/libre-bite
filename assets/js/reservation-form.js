/* global lbiteReservation, jQuery */
( function ( $ ) {
	'use strict';

	var cfg = lbiteReservation;

	var $form    = $( '#lbite-reservation-form' );
	var $submit  = $( '#lbite-res-submit' );
	var $error   = $( '#lbite-res-error' );
	var $success = $( '#lbite-res-success' );

	if ( ! $form.length ) {
		return;
	}

	// Formular absenden
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
