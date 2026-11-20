<?php
/**
 * Front page.
 *
 * Renders each section group from the "Home Page Sections" field group in the
 * order defined by SEPS\Theme\SCF\front_page_sections(). Each section template
 * receives its group array as $args and decides for itself whether it has
 * enough content to render.
 *
 * @package SEPS
 */

defined( 'ABSPATH' ) || exit;

// "Your homepage displays: Your latest posts" → behave like the blog index.
if ( 'page' !== get_option( 'show_on_front' ) ) {
	require get_home_template();
	return;
}

get_header();

$seps_rendered = 0;

foreach ( array_keys( SEPS\Theme\SCF\front_page_sections() ) as $seps_section ) {
	$seps_data = seps_field( $seps_section );

	if ( ! is_array( $seps_data ) || empty( $seps_data['enabled'] ) ) {
		continue;
	}

	get_template_part( 'template-parts/sections/' . str_replace( '_', '-', $seps_section ), null, $seps_data );
	++$seps_rendered;
}

// Nothing configured yet (or SCF inactive): fall back to the page content.
if ( 0 === $seps_rendered ) {
	while ( have_posts() ) {
		the_post();
		get_template_part( 'template-parts/content', 'page' );
	}
}

get_footer();
