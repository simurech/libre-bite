/**
 * Standort-Bild Upload Handler
 *
 * Verwaltet den Media-Library-Upload für Standort-Bilder im Backend.
 * Wird nur auf der Standort-Bearbeitungsseite geladen.
 */
jQuery( document ).ready( function ( $ ) {
	var $container = $( '.lbite-location-image-upload' );
	if ( ! $container.length ) {
		return;
	}

	var $input     = $container.find( 'input[name="lbite_location_image"]' );
	var $preview   = $container.find( '.lbite-image-preview' );
	var $uploadBtn = $container.find( '.lbite-upload-image-button' );
	var $removeBtn = $container.find( '.lbite-remove-image-button' );
	var imageFrame;

	if ( typeof wp === 'undefined' || typeof wp.media === 'undefined' ) {
		$uploadBtn.prop( 'disabled', true ).text( lbiteLocationImage.errorText );
		return;
	}

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
