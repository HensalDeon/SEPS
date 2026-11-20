/**
 * Header navigation: off-canvas drawer (below the `nav` breakpoint) and
 * sub-menu disclosure buttons.
 *
 * Drawer behaviour: toggle button with aria-expanded, Escape to close,
 * click on the backdrop to close, focus kept inside while open, focus
 * returned to the toggle on close, and auto-reset when resizing to desktop.
 */

const DESKTOP_QUERY = '(min-width: 1440px)'; // Keep in sync with $breakpoints.nav.
const FOCUSABLE = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';

function initDrawer() {
	const toggle = document.querySelector( '[data-drawer-toggle]' );
	const drawer = document.querySelector( '[data-drawer]' );

	if ( ! toggle || ! drawer ) {
		return;
	}

	const desktop = window.matchMedia( DESKTOP_QUERY );
	const isOpen = () => toggle.getAttribute( 'aria-expanded' ) === 'true';

	// Hidden drawer content must not be reachable by keyboard / AT on mobile.
	const syncInert = () => {
		drawer.inert = ! desktop.matches && ! isOpen();
	};

	const open = () => {
		toggle.setAttribute( 'aria-expanded', 'true' );
		drawer.classList.add( 'is-open' );
		document.body.classList.add( 'is-drawer-open' );
		syncInert();
		drawer.querySelector( FOCUSABLE )?.focus();
	};

	const close = ( { restoreFocus = true } = {} ) => {
		toggle.setAttribute( 'aria-expanded', 'false' );
		drawer.classList.remove( 'is-open' );
		document.body.classList.remove( 'is-drawer-open' );
		syncInert();

		if ( restoreFocus ) {
			toggle.focus();
		}
	};

	toggle.addEventListener( 'click', () => ( isOpen() ? close() : open() ) );

	document.addEventListener( 'keydown', ( event ) => {
		if ( ! isOpen() ) {
			return;
		}

		if ( event.key === 'Escape' ) {
			close();
			return;
		}

		// Trap focus between the toggle (which stays visible) and the drawer.
		if ( event.key === 'Tab' ) {
			const focusables = [ toggle, ...drawer.querySelectorAll( FOCUSABLE ) ].filter(
				( el ) => el.offsetParent !== null
			);
			const first = focusables[ 0 ];
			const last = focusables[ focusables.length - 1 ];

			if ( event.shiftKey && document.activeElement === first ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && document.activeElement === last ) {
				event.preventDefault();
				first.focus();
			}
		}
	} );

	// Backdrop click (anything outside the drawer and its toggle).
	document.addEventListener( 'click', ( event ) => {
		if ( isOpen() && ! drawer.contains( event.target ) && ! toggle.contains( event.target ) ) {
			close( { restoreFocus: false } );
		}
	} );

	desktop.addEventListener( 'change', () => {
		if ( desktop.matches && isOpen() ) {
			close( { restoreFocus: false } );
		}
		syncInert();
	} );

	syncInert();
}

function initSubmenus() {
	const toggles = document.querySelectorAll( '[data-submenu-toggle]' );

	toggles.forEach( ( button ) => {
		const item = button.closest( '.menu-item' );

		button.addEventListener( 'click', () => {
			const expanded = button.getAttribute( 'aria-expanded' ) === 'true';

			button.setAttribute( 'aria-expanded', String( ! expanded ) );
			item?.classList.toggle( 'is-open', ! expanded );
		} );

		// Close an open desktop dropdown when focus leaves it.
		item?.addEventListener( 'focusout', ( event ) => {
			if ( ! item.contains( event.relatedTarget ) && window.matchMedia( DESKTOP_QUERY ).matches ) {
				button.setAttribute( 'aria-expanded', 'false' );
				item.classList.remove( 'is-open' );
			}
		} );
	} );
}

export default function initNavigation() {
	initDrawer();
	initSubmenus();
}
