/* Nostek Tabs on the site: one panel visible, arrow keys move between tabs. */
( function () {
	document.querySelectorAll( '.nk-tabs' ).forEach( function ( box ) {
		var tabs = Array.prototype.slice.call( box.querySelectorAll( ':scope > .nk-tabs__bar > .nk-tabs__tab' ) );
		function show( tab, focus ) {
			tabs.forEach( function ( t ) {
				var on = t === tab;
				t.classList.toggle( 'is-on', on );
				t.setAttribute( 'aria-selected', on ? 'true' : 'false' );
				t.tabIndex = on ? 0 : -1;
				var panel = document.getElementById( t.getAttribute( 'aria-controls' ) );
				if ( panel ) {
					panel.hidden = ! on;
				}
			} );
			if ( focus ) {
				tab.focus();
			}
		}
		tabs.forEach( function ( t, i ) {
			t.addEventListener( 'click', function () {
				show( t, false );
			} );
			t.addEventListener( 'keydown', function ( e ) {
				var step = { ArrowRight: 1, ArrowLeft: -1 }[ e.key ];
				if ( step ) {
					e.preventDefault();
					show( tabs[ ( i + step + tabs.length ) % tabs.length ], true );
				}
			} );
		} );
	} );
} )();
