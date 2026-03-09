/* global lbiteReservation, jQuery */
( function ( $ ) {
	'use strict';

	var cfg = lbiteReservation;

	var $form      = $( '#lbite-reservation-form' );
	var $location  = $( '#lbite-res-location' );
	var $table     = $( '#lbite-res-table' );
	var $submit    = $( '#lbite-res-submit' );
	var $error     = $( '#lbite-res-error' );
	var $success   = $( '#lbite-res-success' );

	if ( ! $form.length ) {
		return;
	}

	/**
	 * Tische für gewählten Standort laden.
	 */
	function loadTables( locationId ) {
		$table.prop( 'disabled', true ).empty()
			.append( $( '<option>' ).val( '' ).text( cfg.strings.loadTables ) );

		$.ajax( {
			url    : cfg.ajaxUrl,
			method : 'POST',
			data   : {
				action      : 'lbite_get_reservation_tables',
				nonce       : cfg.nonce,
				location_id : locationId,
			},
			success: function ( response ) {
				$table.empty();

				if ( ! response.success || ! response.data.tables.length ) {
					$table.append( $( '<option>' ).val( '' ).text( cfg.strings.noTables ) );
					return;
				}

				$table.append( $( '<option>' ).val( '' ).text( cfg.strings.selectTable ) );
				$.each( response.data.tables, function ( i, t ) {
					var lbiteLabel = t.title + ( t.seats > 0 ? ' (' + t.seats + ')' : '' );
					$table.append( $( '<option>' ).val( t.id ).text( lbiteLabel ) );
				} );
				$table.prop( 'disabled', false );
			},
			error: function () {
				$table.empty().append( $( '<option>' ).val( '' ).text( cfg.strings.noTables ) );
			},
		} );
	}

	// Standort-Wechsel
	if ( $location.length ) {
		$location.on( 'change', function () {
			var lbiteLocationId = parseInt( $( this ).val(), 10 );
			if ( lbiteLocationId ) {
				loadTables( lbiteLocationId );
			} else {
				$table.prop( 'disabled', true ).empty()
					.append( $( '<option>' ).val( '' ).text( cfg.strings.selectTable ) );
			}
		} );

		// Vorgewählten Standort sofort laden
		var lbiteInitLocation = parseInt( $location.val(), 10 );
		if ( lbiteInitLocation ) {
			loadTables( lbiteInitLocation );
		}
	} else {
		// Verstecktes Standort-Feld (einzelner Standort vorgewählt)
		var lbiteHiddenLocation = parseInt( $form.find( 'input[name="location_id"]' ).val(), 10 );
		if ( lbiteHiddenLocation ) {
			loadTables( lbiteHiddenLocation );
		}
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
					$submit.prop( 'disabled', false ).text( $form.find( '.lbite-res-submit' ).data( 'label-default' ) || 'Reservierungsanfrage senden' );
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
