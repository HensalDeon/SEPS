<?php
/**
 * Default page template: renders the page's sections (section builder),
 * falling back to the classic content for pages without sections.
 *
 * @package SEPS
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) {
	the_post();

	if ( 0 === seps_render_page_sections( get_the_ID() ) ) {
		get_template_part( 'template-parts/content', 'page' );
	}
}

get_footer();
