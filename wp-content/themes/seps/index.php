<?php
/**
 * Fallback template: archives and search (the posts page uses home.php).
 *
 * @package SEPS
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( is_search() ) {
	/* translators: %s: Search query. */
	$seps_title = sprintf( __( 'Search results for “%s”', 'seps' ), get_search_query() );
	$seps_args  = array( 'title' => $seps_title );
} elseif ( is_archive() ) {
	$seps_args = array( 'html' => get_the_archive_title() );
} else {
	$seps_args = array( 'title' => __( 'Latest News', 'seps' ) );
}

get_template_part( 'template-parts/post-list', null, $seps_args );

get_footer();
