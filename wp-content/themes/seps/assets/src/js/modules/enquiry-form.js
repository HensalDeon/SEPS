/**
 * Enquiry form (Contact page): send in place instead of reloading.
 *
 * Markup: template-parts/sections/contact-form.php. Without this script the
 * form posts normally and the server redirects back with ?enquiry=<status>.
 * Here it posts the same data with "Accept: application/json" and shows the
 * server's message in the status line, keeping what was typed on errors.
 */

function initForm( form ) {
	const status = form.querySelector( '[data-enquiry-status]' );
	const button = form.querySelector( '[type="submit"]' );

	const show = ( message, ok ) => {
		status.textContent = message;
		status.classList.toggle( 'is-success', ok );
		status.classList.toggle( 'is-error', ! ok );
		status.hidden = false;
	};

	form.addEventListener( 'submit', async ( event ) => {
		event.preventDefault();

		if ( button.getAttribute( 'aria-busy' ) === 'true' ) {
			return;
		}

		button.setAttribute( 'aria-busy', 'true' );

		try {
			const response = await fetch( form.action, {
				method: 'POST',
				body: new FormData( form ),
				headers: { Accept: 'application/json' },
				credentials: 'same-origin',
			} );
			const result = await response.json();

			show( result.message, Boolean( result.success ) );

			if ( result.success ) {
				form.reset();
			} else if ( result.errors?.length ) {
				form.querySelector( `[name="enquiry[${ result.errors[ 0 ] }]"]` )?.focus();
			}
		} catch {
			// Network or unexpected error: fall back to a normal submission.
			form.submit();
			return;
		} finally {
			button.removeAttribute( 'aria-busy' );
		}

		status.scrollIntoView( { block: 'nearest', behavior: 'smooth' } );
	} );
}

export default function initEnquiryForms() {
	document.querySelectorAll( '[data-enquiry-form]' ).forEach( initForm );
}
