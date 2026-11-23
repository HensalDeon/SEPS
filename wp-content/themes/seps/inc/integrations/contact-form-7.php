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
