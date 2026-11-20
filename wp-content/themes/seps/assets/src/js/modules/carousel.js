/**
 * Pagination dots for scroll-snap carousels.
 *
 * Markup (see template-parts/sections/testimonials.php):
 *   [data-carousel]
 *     [data-carousel-track]  > [data-carousel-slide]*
 *     [data-carousel-dots]   (empty, hidden until there is more than one position)
 *
 * One dot per reachable scroll position: with 2 slides in view and 4 slides,
 * there are 3 positions. Native scrolling does the rest.
 *
 * The (translated) dot label comes from PHP via data-dot-label, e.g.
 * "Go to slide %d".
 */

function initCarousel( root ) {
	const track = root.querySelector( '[data-carousel-track]' );
	const dotsWrap = root.querySelector( '[data-carousel-dots]' );
	const slides = [ ...root.querySelectorAll( '[data-carousel-slide]' ) ];

	if ( ! track || ! dotsWrap || slides.length < 2 ) {
		return;
	}

	const labelTemplate = dotsWrap.dataset.dotLabel || 'Go to slide %d';

	let dots = [];
	let frame = 0;

	const stride = () => ( slides[ 1 ].offsetLeft - slides[ 0 ].offsetLeft ) || track.clientWidth;
	const positions = () => {
		const perView = Math.max( 1, Math.round( track.clientWidth / stride() ) );
		return Math.max( 1, slides.length - perView + 1 );
	};

	const setActive = () => {
		const index = Math.min( dots.length - 1, Math.round( track.scrollLeft / stride() ) );

		dots.forEach( ( dot, i ) => {
			if ( i === index ) {
				dot.setAttribute( 'aria-current', 'true' );
			} else {
				dot.removeAttribute( 'aria-current' );
			}
		} );
	};

	const render = () => {
		const count = positions();

		dotsWrap.replaceChildren();
		dots = [];
		dotsWrap.hidden = count < 2;

		for ( let i = 0; i < count; i++ ) {
			const dot = document.createElement( 'button' );
			dot.type = 'button';
			dot.className = 'carousel__dot';
			dot.setAttribute( 'aria-label', labelTemplate.replace( '%d', String( i + 1 ) ) );
			dot.addEventListener( 'click', () => {
				track.scrollTo( { left: slides[ i ].offsetLeft - slides[ 0 ].offsetLeft, behavior: 'smooth' } );
			} );
			dotsWrap.append( dot );
			dots.push( dot );
		}

		setActive();
	};

	track.addEventListener(
		'scroll',
		() => {
			cancelAnimationFrame( frame );
			frame = requestAnimationFrame( setActive );
		},
		{ passive: true }
	);

	new ResizeObserver( render ).observe( track );
}

export default function initCarousels() {
	document.querySelectorAll( '[data-carousel]' ).forEach( initCarousel );
}
