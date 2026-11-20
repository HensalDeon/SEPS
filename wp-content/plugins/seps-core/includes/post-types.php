<?php
/**
 * Post types and taxonomies.
 *
 * Both types are content blocks rendered inside other pages, so they have no
 * public URLs (no single views, archives or rewrite rules).
 *
 * @package SEPS\Core
 */

namespace SEPS\Core\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Register post types and taxonomies.
 */
function register(): void {
	$internal = array(
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => true,
		'has_archive'         => false,
		'rewrite'             => false,
		'query_var'           => false,
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
	);

	register_post_type(
		'seps_testimonial',
		array_merge(
			$internal,
			array(
				'labels'        => array(
					'name'               => __( 'Testimonials', 'seps-core' ),
					'singular_name'      => __( 'Testimonial', 'seps-core' ),
					'add_new_item'       => __( 'Add testimonial', 'seps-core' ),
					'edit_item'          => __( 'Edit testimonial', 'seps-core' ),
					'new_item'           => __( 'New testimonial', 'seps-core' ),
					'search_items'       => __( 'Search testimonials', 'seps-core' ),
					'not_found'          => __( 'No testimonials found.', 'seps-core' ),
					'not_found_in_trash' => __( 'No testimonials found in Trash.', 'seps-core' ),
					'menu_name'          => __( 'Testimonials', 'seps-core' ),
				),
				'menu_icon'     => 'dashicons-format-quote',
				'menu_position' => 21,
				'supports'      => array( 'title', 'revisions' ),
			)
		)
	);

	register_post_type(
		'seps_faq',
		array_merge(
			$internal,
			array(
				'labels'        => array(
					'name'               => __( 'FAQs', 'seps-core' ),
					'singular_name'      => __( 'FAQ', 'seps-core' ),
					'add_new_item'       => __( 'Add question', 'seps-core' ),
					'edit_item'          => __( 'Edit question', 'seps-core' ),
					'new_item'           => __( 'New question', 'seps-core' ),
					'search_items'       => __( 'Search questions', 'seps-core' ),
					'not_found'          => __( 'No questions found.', 'seps-core' ),
					'not_found_in_trash' => __( 'No questions found in Trash.', 'seps-core' ),
					'menu_name'          => __( 'FAQs', 'seps-core' ),
				),
				'menu_icon'     => 'dashicons-editor-help',
				'menu_position' => 22,
				'supports'      => array( 'title', 'editor', 'page-attributes', 'revisions' ),
			)
		)
	);

	register_taxonomy(
		'seps_faq_topic',
		'seps_faq',
		array(
			'labels'            => array(
				'name'          => __( 'Topics', 'seps-core' ),
				'singular_name' => __( 'Topic', 'seps-core' ),
				'add_new_item'  => __( 'Add topic', 'seps-core' ),
				'edit_item'     => __( 'Edit topic', 'seps-core' ),
				'search_items'  => __( 'Search topics', 'seps-core' ),
			),
			'public'            => false,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'hierarchical'      => true,
			'rewrite'           => false,
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register' );

/**
 * Better title placeholders in the editor.
 *
 * @param string   $placeholder Default placeholder.
 * @param \WP_Post $post        Current post.
 */
function title_placeholder( string $placeholder, \WP_Post $post ): string {
	return match ( $post->post_type ) {
		'seps_testimonial' => __( 'Parent / author name', 'seps-core' ),
		'seps_faq'         => __( 'Question', 'seps-core' ),
		default            => $placeholder,
	};
}
add_filter( 'enter_title_here', __NAMESPACE__ . '\\title_placeholder', 10, 2 );

/**
 * Testimonials are just a title + two fields; the classic screen shows them
 * directly under the title instead of in the block editor's bottom panel.
 *
 * @param bool   $use_block_editor Whether to use the block editor.
 * @param string $post_type        Post type.
 */
function classic_editor_for_testimonials( bool $use_block_editor, string $post_type ): bool {
	return 'seps_testimonial' === $post_type ? false : $use_block_editor;
}
add_filter( 'use_block_editor_for_post_type', __NAMESPACE__ . '\\classic_editor_for_testimonials', 10, 2 );
