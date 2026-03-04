/* global lbiteTableData */
/**
 * Admin-Tables: QR-Code Druck-Funktion
 *
 * @package LibreBite
 */
document.addEventListener( 'DOMContentLoaded', function() {
	var btn = document.querySelector( '.lbite-print-qr-btn' );
	if ( ! btn ) {
		return;
	}

	btn.addEventListener( 'click', function() {
		var title   = this.dataset.title;
		var qrUrl   = this.dataset.qr;
		var scanText = ( typeof lbiteTableData !== 'undefined' && lbiteTableData.scanText ) ? lbiteTableData.scanText : '';
		var win = window.open( '', '_blank', 'width=400,height=500' );
		win.document.write( '<html><head><title>Print QR</title><style>body{text-align:center;font-family:sans-serif;padding:40px;border:2px solid #eee;margin:20px;} img{width:250px;} h1{font-size:32px;margin:20px 0 10px;} p{color:#666;font-size:18px;}</style></head><body>' );
		win.document.write( '<img src="' + qrUrl + '">' );
		win.document.write( '<h1>' + title + '</h1>' );
		win.document.write( '<p>' + scanText + '</p>' );
		win.document.write( '</body></html>' );
		win.document.close();
		setTimeout( function() { win.print(); }, 500 );
	} );
} );
