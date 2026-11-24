<?php
/**
 * Contact Form 7 integration (optional plugin).
 *
 * The Enquiry form section renders a CF7 form whose template uses the
 * theme's markup and classes (see the `contact` seeder step), so:
 *
 * - CF7's automatic <p>/<br> insertion is turned off (it would break the
 *   field grid);
 * - CF7's script and stylesheet load only on pages that show a form
 *   (enqueue(), called by the section) instead of on every page.
 *
 * @package SEPS
 */

namespace SEPS\Theme\CF7;

defined( 'ABSPATH' ) || exit;

add_filter( 'wpcf7_autop_or_not', '__return_false' );
add_filter( 'wpcf7_load_js', '__return_false' );
add_filter( 'wpcf7_load_css', '__return_false' );

/**
 * Whether Contact Form 7 is active.
 */
function is_active(): bool {
	return class_exists( 'WPCF7_ContactForm' ) && function_exists( 'wpcf7_contact_form' );
}

/**
 * Enqueue CF7's assets for a page that shows a form.
 *
 * Called while rendering the page body: the script is a footer script, and
 * the small stylesheet is printed in the footer as a late style.
 */
function enqueue(): void {
	if ( function_exists( 'wpcf7_enqueue_scripts' ) ) {
		wpcf7_enqueue_scripts();
	}
	if ( function_exists( 'wpcf7_enqueue_styles' ) ) {
		wpcf7_enqueue_styles();
	}
}

/**
 * Conditionally required fields.
 *
 * A form-tag option `required-if:<field>:<value>` makes a field required
 * when another field was submitted with that value, e.g. on the careers
 * application: [text subject required-if:job-type:Academic].
 *
 * @param \WPCF7_Validation $result Validation result.
 * @param \WPCF7_FormTag    $tag    Field being validated.
 */
function required_if( $result, $tag ) {
	$rule = (string) $tag->get_option( 'required-if', '[-0-9a-zA-Z_]+:[-0-9a-zA-Z_]+', true );

	if ( '' === $rule ) {
		return $result;
	}

	[ $field, $value ] = explode( ':', $rule, 2 );

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Contact Form 7 verifies the submission.
	$trigger = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( (string) $_POST[ $field ] ) ) : '';
	$own     = isset( $_POST[ $tag->name ] ) ? trim( sanitize_textarea_field( wp_unslash( (string) $_POST[ $tag->name ] ) ) ) : '';
	// phpcs:enable

	if ( $value === $trigger && '' === $own ) {
		$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
	}

	return $result;
}

foreach ( array( 'text', 'textarea' ) as $seps_cf7_type ) {
	add_filter( "wpcf7_validate_{$seps_cf7_type}", __NAMESPACE__ . '\\required_if', 20, 2 );
}
unset( $seps_cf7_type );
