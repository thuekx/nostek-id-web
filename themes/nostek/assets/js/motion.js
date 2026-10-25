/**
 * Nostek motion layer: sticky header, sliders and carousels, news ticker,
 * scroll reveal, back-to-top button and reading progress.
 *
 * No library. Every feature enhances server-rendered markup, so content stays
 * readable (and indexable) without JavaScript. Visitors who ask for reduced
 * motion get no autoplay, no ticker scroll and no reveal animation.
 */
( function () {
	'use strict';

	var root = document.documentElement;
	var reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var hasIO = 'IntersectionObserver' in window;

	root.classList.add( 'nk-js' );

	function each( selector, fn, scope ) {
		Array.prototype.forEach.call( ( scope || document ).querySelectorAll( selector ), fn );
	}

	function make( tag, className, attrs ) {
		var el = document.createElement( tag );
		el.className = className;
		Object.keys( attrs || {} ).forEach( function ( key ) {
			el.setAttribute( key, attrs[ key ] );
		} );
		return el;
	}

	/* Sticky header: compact once the page scrolls (hysteresis stops flicker). */
	var stuck = false;
	function onScroll() {
		var y = window.scrollY;
		if ( ! stuck && y > 80 ) {
			stuck = true;
			root.classList.add( 'nk-stuck' );
		} else if ( stuck && y < 20 ) {
			stuck = false;
			root.classList.remove( 'nk-stuck' );
		}
		if ( toTop ) {
			toTop.classList.toggle( 'is-visible', y > 700 );
		}
		if ( progress && article ) {
			var box = article.getBoundingClientRect();
			var total = box.height - window.innerHeight;
			var done = total > 0 ? Math.min( 1, Math.max( 0, -box.top / total ) ) : 1;
			progress.style.transform = 'scaleX(' + done + ')';
		}
	}

	/* Back-to-top button. */
	var toTop = make( 'button', 'nk-to-top', { type: 'button', 'aria-label': 'Kembali ke atas' } );
	toTop.innerHTML = '<span aria-hidden="true">&#9650;</span>';
	toTop.addEventListener( 'click', function () {
		window.scrollTo( { top: 0, behavior: reduce ? 'auto' : 'smooth' } );
	} );
	document.body.appendChild( toTop );

	/* Reading progress on single articles. */
	var article = document.body.classList.contains( 'single-post' ) ? document.querySelector( '.wp-block-post-content' ) : null;
	var progress = null;
	if ( article ) {
		progress = make( 'div', 'nk-progress', { 'aria-hidden': 'true' } );
		document.body.appendChild( progress );
	}

	var ticking = false;
	window.addEventListener( 'scroll', function () {
		if ( ! ticking ) {
			ticking = true;
			window.requestAnimationFrame( function () {
				ticking = false;
				onScroll();
			} );
		}
	}, { passive: true } );
	onScroll();

	/* Sliders (one slide per view, autoplay) and carousels (several cards per view). */
	function slider( el ) {
		var isCarousel = el.classList.contains( 'is-style-carousel' );
		var track = el.querySelector( '.wp-block-post-template' );
		if ( ! track ) {
			track = make( 'div', 'nk-slider__track' );
			while ( el.firstChild ) {
				track.appendChild( el.firstChild );
			}
			el.appendChild( track );
		}
		var slides = Array.prototype.filter.call( track.children, function ( child ) {
			return child.nodeType === 1;
		} );
		if ( slides.length < 2 ) {
			return;
		}

		el.classList.add( 'nk-slider--on' );
		el.setAttribute( 'role', 'region' );
		el.setAttribute( 'aria-roledescription', 'carousel' );
		if ( ! el.hasAttribute( 'aria-label' ) ) {
			el.setAttribute( 'aria-label', isCarousel ? 'Artikel terkait' : 'Sorotan' );
		}
		track.setAttribute( 'tabindex', '0' );
		slides.forEach( function ( slide, i ) {
			slide.setAttribute( 'aria-roledescription', 'slide' );
			slide.setAttribute( 'aria-label', ( i + 1 ) + ' / ' + slides.length );
		} );

		var current = 0;
		var prev = make( 'button', 'nk-slider__arrow nk-slider__arrow--prev', { type: 'button', 'aria-label': 'Sebelumnya' } );
		var next = make( 'button', 'nk-slider__arrow nk-slider__arrow--next', { type: 'button', 'aria-label': 'Berikutnya' } );
		prev.innerHTML = '<span aria-hidden="true">&#8249;</span>';
		next.innerHTML = '<span aria-hidden="true">&#8250;</span>';
		el.appendChild( prev );
		el.appendChild( next );

		var dots = [];
		if ( ! isCarousel ) {
			var nav = make( 'div', 'nk-slider__dots' );
			slides.forEach( function ( slide, i ) {
				var dot = make( 'button', 'nk-slider__dot', { type: 'button', 'aria-label': 'Slide ' + ( i + 1 ) } );
				dot.addEventListener( 'click', function () {
					go( i );
					pause( true );
				} );
				nav.appendChild( dot );
				dots.push( dot );
			} );
			el.appendChild( nav );
		}

		function atEnd() {
			return track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
		}

		function go( i ) {
			if ( i < 0 ) {
				i = slides.length - 1;
			} else if ( i >= slides.length || ( i > current && atEnd() ) ) {
				i = 0;
			}
			track.scrollTo( { left: slides[ i ].offsetLeft, behavior: reduce ? 'auto' : 'smooth' } );
		}

		function mark() {
			var step = slides[ 1 ].offsetLeft - slides[ 0 ].offsetLeft || 1;
			current = Math.min( slides.length - 1, Math.round( track.scrollLeft / step ) );
			slides.forEach( function ( slide, i ) {
				slide.classList.toggle( 'is-active', i === current );
			} );
			dots.forEach( function ( dot, i ) {
				dot.setAttribute( 'aria-current', i === current ? 'true' : 'false' );
			} );
		}

		var marking = false;
		track.addEventListener( 'scroll', function () {
			if ( ! marking ) {
				marking = true;
				window.requestAnimationFrame( function () {
					marking = false;
					mark();
				} );
			}
		}, { passive: true } );
		mark();

		prev.addEventListener( 'click', function () {
			go( current - 1 );
			pause( true );
		} );
		next.addEventListener( 'click', function () {
			go( current + 1 );
			pause( true );
		} );
		track.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'ArrowLeft' || e.key === 'ArrowRight' ) {
				e.preventDefault();
				go( current + ( e.key === 'ArrowRight' ? 1 : -1 ) );
				pause( true );
			}
		} );

		/* Autoplay: sliders only, stops for good once the visitor takes control. */
		var interval = parseInt( el.getAttribute( 'data-interval' ), 10 ) || 6500;
		var timer = null;
		var held = false;
		var stopped = isCarousel || reduce;
		var visible = true;

		function play() {
			clearInterval( timer );
			if ( ! stopped && ! held && visible && ! document.hidden ) {
				timer = setInterval( function () {
					go( current + 1 );
				}, interval );
			}
		}
		function pause( forever ) {
			if ( forever ) {
				stopped = true;
			}
			clearInterval( timer );
		}

		el.addEventListener( 'mouseenter', function () {
			held = true;
			pause();
		} );
		el.addEventListener( 'mouseleave', function () {
			held = false;
			play();
		} );
		el.addEventListener( 'focusin', function () {
			held = true;
			pause();
		} );
		el.addEventListener( 'focusout', function () {
			held = false;
			play();
		} );
		track.addEventListener( 'touchstart', function () {
			pause( true );
		}, { passive: true } );
		document.addEventListener( 'visibilitychange', play );
		if ( hasIO ) {
			new IntersectionObserver( function ( entries ) {
				visible = entries[ 0 ].isIntersecting;
				play();
			} ).observe( el );
		}
		play();
	}
	each( '.is-style-slider, .is-style-carousel', slider );

	/* News ticker: duplicate the list once so the loop is seamless. */
	each( '.nk-ticker .wp-block-post-template', function ( list ) {
		if ( reduce || list.children.length < 2 ) {
			return;
		}
		Array.prototype.slice.call( list.children ).forEach( function ( item ) {
			var copy = item.cloneNode( true );
			copy.setAttribute( 'aria-hidden', 'true' );
			each( 'a', function ( a ) {
				a.setAttribute( 'tabindex', '-1' );
			}, copy );
			list.appendChild( copy );
		} );
		list.style.setProperty( '--nk-ticker-time', ( list.children.length * 4 ) + 's' );
		list.classList.add( 'is-running' );
	} );

	/* Scroll reveal: only for blocks below the fold, so the first paint never waits. */
	if ( ! reduce && hasIO ) {
		var targets = [
			'main .wp-block-post-template > li',
			'main .is-style-divider',
			'main .is-style-frame',
			'main .is-style-band',
			'main .nostek-topics__item',
			'main .wp-block-columns > .wp-block-column',
			'main .wp-block-details',
			'main .wp-block-image',
			'.wp-site-blocks > footer .wp-block-column',
		].join( ',' );
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'nk-in' );
					io.unobserve( entry.target );
				}
			} );
		}, { rootMargin: '0px 0px -8% 0px' } );
		var fold = window.innerHeight;
		each( targets, function ( el ) {
			if ( el.closest( '.nk-slider--on, .nk-ticker, .wp-block-navigation' ) || el.getBoundingClientRect().top < fold ) {
				return;
			}
			var siblings = el.parentNode ? Array.prototype.indexOf.call( el.parentNode.children, el ) : 0;
			el.style.setProperty( '--nk-delay', ( ( siblings % 3 ) * 90 ) + 'ms' );
			el.classList.add( 'nk-reveal' );
			io.observe( el );
		} );
	}
}() );
