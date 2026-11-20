<?php
/**
 * Default page template.
 *
 * @package SEPS
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) {
	the_post();
	get_template_part( 'template-parts/content', 'page' );
}

get_footer();
