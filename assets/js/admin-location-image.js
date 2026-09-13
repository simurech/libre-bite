/**
 * Bild-Upload-Handler für den Standort-Editor
 *
 * Verwaltet Media-Library-Uploads im Backend. Bewusst auf mehrere
 * Instanzen pro Seite ausgelegt: neben dem Standort-Bild nutzt auch der
 * Grundriss des Tischplans denselben Baustein. Alle Selektoren sind
 * deshalb auf den jeweiligen Container gescopt.
 */
jQuery( document ).ready( function ( $ ) {
	var $containers = $( '.lbite-location-image-upload' );
	if ( ! $containers.length ) {
		return;
	}

	if ( typeof wp === 'undefined' || typeof wp.media === 'undefined' ) {
		$containers.find( '.lbite-upload-image-button' )
			.prop( 'disabled', true )
			.text( lbiteLocationImage.errorText );
		return;
	}

	$containers.each( function () {
	var $container = $( this );
	var $input     = $container.find( 'input[type="hidden"]' ).first();
	var $preview   = $container.find( '.lbite-image-preview' );
	var $uploadBtn = $container.find( '.lbite-upload-image-button' );
	var $removeBtn = $container.find( '.lbite-remove-image-button' );
	var imageFrame;

	$uploadBtn.on( 'click', function ( e ) {
		e.preventDefault();
		if ( imageFrame ) {
			imageFrame.open();
			return;
		}
		imageFrame = wp.media( {
			title:    lbiteLocationImage.title,
			button:   { text: lbiteLocationImage.buttonText },
			multiple: false,
		} );
		imageFrame.on( 'select', function () {
			var attachment = imageFrame.state().get( 'selection' ).first().toJSON();
			$input.val( attachment.id );
			$preview.html(
				$( '<img>' ).attr( {
					src:   attachment.url,
					style: 'max-width: 100%; height: auto; display: block;',
				} )
			);
			$removeBtn.show();
		} );
		imageFrame.open();
	} );

	$removeBtn.on( 'click', function ( e ) {
		e.preventDefault();
		$input.val( '' );
		$preview.html(
			$( '<p>' ).css( {
				'text-align':  'center',
				padding:       '20px',
				background:    '#f5f5f5',
				border:        '2px dashed #ddd',
			} ).text( lbiteLocationImage.noImageText )
		);
		$( this ).hide();
	} );
	} );
} );
