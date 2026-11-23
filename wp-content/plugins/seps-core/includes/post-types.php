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

	register_post_type(
		'seps_person',
		array_merge(
			$internal,
			array(
				'labels'        => array(
					'name'                  => __( 'Staff', 'seps-core' ),
					'singular_name'         => __( 'Staff member', 'seps-core' ),
					'add_new_item'          => __( 'Add staff member', 'seps-core' ),
					'edit_item'             => __( 'Edit staff member', 'seps-core' ),
					'new_item'              => __( 'New staff member', 'seps-core' ),
					'search_items'          => __( 'Search staff', 'seps-core' ),
					'not_found'             => __( 'No staff found.', 'seps-core' ),
					'not_found_in_trash'    => __( 'No staff found in Trash.', 'seps-core' ),
					'featured_image'        => __( 'Photo', 'seps-core' ),
					'set_featured_image'    => __( 'Set photo', 'seps-core' ),
					'remove_featured_image' => __( 'Remove photo', 'seps-core' ),
					'use_featured_image'    => __( 'Use as photo', 'seps-core' ),
					'menu_name'             => __( 'Staff', 'seps-core' ),
				),
				'menu_icon'     => 'dashicons-groups',
				'menu_position' => 23,
				'supports'      => array( 'title', 'thumbnail', 'page-attributes', 'revisions' ),
			)
		)
	);

	register_taxonomy(
		'seps_team',
		'seps_person',
		array(
			'labels'            => array(
				'name'          => __( 'Teams', 'seps-core' ),
				'singular_name' => __( 'Team', 'seps-core' ),
				'add_new_item'  => __( 'Add team', 'seps-core' ),
				'edit_item'     => __( 'Edit team', 'seps-core' ),
				'search_items'  => __( 'Search teams', 'seps-core' ),
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
		'seps_person'      => __( 'Full name, e.g. Mrs. Deepa Binu', 'seps-core' ),
		default            => $placeholder,
	};
}
add_filter( 'enter_title_here', __NAMESPACE__ . '\\title_placeholder', 10, 2 );

/**
 * Testimonials and staff are a title plus a few fields; the classic screen
 * shows those fields directly under the title instead of in the block
 * editor's bottom panel.
 *
 * @param bool   $use_block_editor Whether to use the block editor.
 * @param string $post_type        Post type.
 */
function classic_editor_for_simple_types( bool $use_block_editor, string $post_type ): bool {
	return in_array( $post_type, array( 'seps_testimonial', 'seps_person' ), true ) ? false : $use_block_editor;
}
add_filter( 'use_block_editor_for_post_type', __NAMESPACE__ . '\\classic_editor_for_simple_types', 10, 2 );
