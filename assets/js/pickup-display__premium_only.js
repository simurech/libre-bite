/**
 * Libre Bite – Abholanzeige
 *
 * Lädt die Nummern alle paar Sekunden neu. Neue Nummern in einer
 * abgeschlossenen Spalte («abholbereit») blinken kurz auf und lösen –
 * falls eingeschaltet – einen Ton aus. Browser spielen Ton erst nach einer
 * Berührung ab; dafür erscheint einmalig ein Knopf.
 */
( function () {
	'use strict';

	var cfg = window.lbitePickupDisplay;
	if ( ! cfg ) {
		return;
	}

	var root = document.getElementById( 'lbite-pd-columns' );
	var status = document.getElementById( 'lbite-pd-status' );
	var soundBtn = document.getElementById( 'lbite-pd-sound' );
	var clock = document.getElementById( 'lbite-pd-clock' );
	var audio = cfg.sound ? new window.Audio( cfg.sound ) : null;
	var soundReady = false;
	var known = {};
	var first = true;

	function tickClock() {
		var opts = { hour: '2-digit', minute: '2-digit', hour12: !! cfg.hour12 };
		// Zeitzone der Website, nicht die des Geräts (Fernseher stehen oft auf UTC).
		if ( cfg.timezone && /\//.test( cfg.timezone ) ) {
			opts.timeZone = cfg.timezone;
		}
		try {
			clock.textContent = new Date().toLocaleTimeString( document.documentElement.lang || undefined, opts );
		} catch ( e ) {
			delete opts.timeZone;
			clock.textContent = new Date().toLocaleTimeString( document.documentElement.lang || undefined, opts );
		}
	}

	function unlockSound() {
		if ( ! audio ) {
			return;
		}
		audio.play().then( function () {
			audio.pause();
			audio.currentTime = 0;
			soundReady = true;
			soundBtn.hidden = true;
		} ).catch( function () {
			soundBtn.textContent = cfg.strings.enableSound;
			soundBtn.hidden = false;
		} );
	}

	function render( data ) {
		var cols = data.columns || [];
		var newReady = false;
		root.style.setProperty( '--lbite-pd-cols', Math.max( 1, cols.length ) );
		root.textContent = '';

		cols.forEach( function ( col ) {
			var section = document.createElement( 'section' );
			section.className = 'lbite-pd__column' + ( col.done ? ' is-done' : '' );
			var h = document.createElement( 'h2' );
			h.className = 'lbite-pd__column-title';
			h.textContent = col.label;
			section.appendChild( h );

			var list = document.createElement( 'ul' );
			list.className = 'lbite-pd__numbers';
			( col.numbers || [] ).forEach( function ( num ) {
				var li = document.createElement( 'li' );
				li.className = 'lbite-pd__number';
				li.textContent = num;
				var id = col.key + ':' + num;
				if ( ! first && col.done && ! known[ id ] ) {
					li.className += ' is-new';
					newReady = true;
				}
				list.appendChild( li );
			} );
			section.appendChild( list );
			root.appendChild( section );
		} );

		known = {};
		cols.forEach( function ( col ) {
			( col.numbers || [] ).forEach( function ( num ) {
				known[ col.key + ':' + num ] = true;
			} );
		} );

		if ( newReady && audio && soundReady ) {
			audio.currentTime = 0;
			audio.play().catch( function () {} );
		}
		first = false;
	}

	function load() {
		window.fetch( cfg.endpoint, { credentials: 'omit', cache: 'no-store' } )
			.then( function ( r ) {
				if ( ! r.ok ) {
					throw new Error( r.status );
				}
				return r.json();
			} )
			.then( function ( data ) {
				status.textContent = '';
				render( data );
			} )
			.catch( function () {
				status.textContent = cfg.strings.offline;
			} )
			.then( function () {
				window.setTimeout( load, cfg.interval || 5000 );
			} );
	}

	// Vom Server bereits ausgegebene Nummern als bekannt merken.
	Array.prototype.forEach.call( root.querySelectorAll( '.lbite-pd__column' ), function ( section ) {
		Array.prototype.forEach.call( section.querySelectorAll( '.lbite-pd__number' ), function ( li ) {
			known[ section.getAttribute( 'data-key' ) + ':' + li.textContent ] = true;
		} );
	} );
	first = false;

	if ( audio ) {
		soundBtn.addEventListener( 'click', unlockSound );
		document.addEventListener( 'click', unlockSound, { once: true } );
		unlockSound();
	}

	tickClock();
	window.setInterval( tickClock, 15000 );
	window.setTimeout( load, cfg.interval || 5000 );
}() );
