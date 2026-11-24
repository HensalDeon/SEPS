/**
 * Extras for the long forms (Contact Form 7 templates seeded by the theme):
 *
 * - Repeatable groups, e.g. siblings. The extra groups are in the form but
 *   hidden; "+ Add Another Sibling" reveals the next one. Markup:
 *     [data-repeat="siblings"][hidden]           (groups 2, 3, …)
 *     button[data-repeat-add="siblings"][hidden] (shown when there is a group to add)
 * - File uploads: the chosen file's name replaces the hint, and the box is
 *   highlighted while a file is dragged over it. Markup:
 *     .enquiry__drop > [data-file-hint] + input[type=file]
 *
 * Without JavaScript the form still works: one sibling group, plain uploads.
 */

function initRepeat( button ) {
	const form = button.closest( 'form' );
	const name = button.dataset.repeatAdd;
	const hiddenGroups = () => [ ...form.querySelectorAll( `[data-repeat="${ name }"][hidden]` ) ];

	button.hidden = hiddenGroups().length === 0;

	button.addEventListener( 'click', () => {
		const [ next ] = hiddenGroups();

		if ( ! next ) {
			return;
		}

		next.hidden = false;
		next.querySelector( 'input, select, textarea' )?.focus();
		button.hidden = hiddenGroups().length === 0;
	} );
}

function initUpload( input ) {
	const box = input.closest( '.enquiry__drop' );
	const hint = box?.querySelector( '[data-file-hint]' );

	if ( ! box || ! hint ) {
		return;
	}

	const original = hint.textContent;

	const update = () => {
		const file = input.files?.[ 0 ];
		hint.textContent = file ? file.name : original;
		hint.classList.toggle( 'is-file', Boolean( file ) );
	};

	input.addEventListener( 'change', update );
	input.form?.addEventListener( 'reset', () => window.setTimeout( update ) );

	[ 'dragenter', 'dragover' ].forEach( ( type ) => input.addEventListener( type, () => box.classList.add( 'is-dragover' ) ) );
	[ 'dragleave', 'drop' ].forEach( ( type ) => input.addEventListener( type, () => box.classList.remove( 'is-dragover' ) ) );
}

export default function initFormExtras() {
	document.querySelectorAll( '[data-repeat-add]' ).forEach( initRepeat );
	document.querySelectorAll( '.enquiry__drop input[type="file"]' ).forEach( initUpload );
}
