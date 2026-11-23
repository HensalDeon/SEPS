<?php
/**
 * Posts page (News & Events).
 *
 * Page 1 renders the page's sections (section builder), then the list of all
 * posts. Later pages (/page/2/ …) show the list only.
 *
 * @package SEPS
 */

defined( 'ABSPATH' ) || exit;

get_header();

$seps_page_id  = (int) get_option( 'page_for_posts' );
$seps_sections = ( $seps_page_id && ! is_paged() ) ? seps_render_page_sections( $seps_page_id ) : 0;

get_template_part(
	'template-parts/post-list',
	null,
	array(
		'id'    => 'all-news',
		// With sections above, the list is a sub-section (h2); otherwise it is the page (h1).
		'title' => $seps_sections ? __( 'All News', 'seps' ) : single_post_title( '', false ),
		'level' => $seps_sections ? 2 : 1,
	)
);

get_footer();
