<?php
/**
 * Field group: Front page.
 *
 * One tab + one Group field per section. Each group is passed as-is to its
 * template part (template-parts/sections/{name}.php), so the template never
 * reaches into SCF directly and could be reused by other pages later.
 *
 * Meta keys are therefore {section}_{field}, e.g. hero_title.
 *
 * @package SEPS
 */

namespace SEPS\Theme\SCF;

defined( 'ABSPATH' ) || exit;

/**
 * Section order on the front page. Also used by front-page.php.
 *
 * @return array<string,string> Group field name => admin label.
 */
function front_page_sections(): array {
	return array(
		'hero'         => __( 'Hero', 'seps' ),
		'about'        => __( 'About', 'seps' ),
		'programs'     => __( 'Programs', 'seps' ),
		'student_life' => __( 'Student Life', 'seps' ),
		'principal'    => __( 'Principal', 'seps' ),
		'facilities'   => __( 'Facilities', 'seps' ),
		'highlights'   => __( 'Highlights', 'seps' ),
		'testimonials' => __( 'Testimonials', 'seps' ),
		'faq'          => __( 'FAQs', 'seps' ),
		'news'         => __( 'News', 'seps' ),
	);
}

/**
 * Sub-fields for each section group.
 *
 * @return array<string,array<int,array<string,mixed>>>
 */
function front_page_section_fields(): array {
	$heading_args = array(
		'rows'         => 2,
		'new_lines'    => '',
		'instructions' => __( 'Line breaks are kept on large screens.', 'seps' ),
	);
	$text_args    = array(
		'rows'      => 3,
		'new_lines' => '',
	);

	return array(
		'hero'         => array(
			field( 'hero', 'title', 'textarea', __( 'Heading', 'seps' ), array_merge( $heading_args, array( 'required' => 1 ) ) ),
			field( 'hero', 'text', 'textarea', __( 'Intro', 'seps' ), $text_args ),
			link_field( 'hero', 'button', __( 'Button', 'seps' ) ),
			image_field(
				'hero',
				'image',
				__( 'Image', 'seps' ),
				array(
					'required'     => 1,
					'instructions' => __( 'Portrait or square crop, at least 1000px wide.', 'seps' ),
				)
			),
		),

		'about'        => array(
			field( 'about', 'heading', 'textarea', __( 'Heading', 'seps' ), $heading_args ),
			image_field( 'about', 'image', __( 'Main image (circle)', 'seps' ) ),
			field(
				'about',
				'features',
				'repeater',
				__( 'Highlights', 'seps' ),
				array(
					'layout'       => 'block',
					'max'          => 4,
					'button_label' => __( 'Add highlight', 'seps' ),
					'sub_fields'   => array(
						field(
							'about_features',
							'icon',
							'select',
							__( 'Icon', 'seps' ),
							array(
								'choices' => array(
									'certificate' => __( 'Certificate', 'seps' ),
									'student'     => __( 'Graduate', 'seps' ),
								),
							)
						),
						field( 'about_features', 'title', 'text', __( 'Title', 'seps' ) ),
						field( 'about_features', 'text', 'textarea', __( 'Text', 'seps' ), $text_args ),
					),
				)
			),
			field( 'about', 'intro', 'textarea', __( 'Side panel text', 'seps' ), array_merge( $text_args, array( 'rows' => 5 ) ) ),
			link_field( 'about', 'button', __( 'Side panel button', 'seps' ) ),
			image_field( 'about', 'image_secondary', __( 'Side panel image (circle)', 'seps' ) ),
		),

		'programs'     => array(
			field( 'programs', 'heading', 'textarea', __( 'Heading', 'seps' ), $heading_args ),
			field( 'programs', 'intro', 'textarea', __( 'Intro', 'seps' ), $text_args ),
			field(
				'programs',
				'items',
				'repeater',
				__( 'Programs', 'seps' ),
				array(
					'layout'       => 'block',
					'button_label' => __( 'Add program', 'seps' ),
					'collapsed'    => 'field_seps_programs_items_title',
					'sub_fields'   => array(
						image_field( 'programs_items', 'image', __( 'Image', 'seps' ) ),
						field( 'programs_items', 'title', 'text', __( 'Title', 'seps' ), array( 'required' => 1 ) ),
						field( 'programs_items', 'text', 'textarea', __( 'Description', 'seps' ), $text_args ),
						link_field( 'programs_items', 'button', __( 'Button', 'seps' ) ),
						field( 'programs_items', 'label', 'text', __( 'Grade label', 'seps' ), array( 'placeholder' => 'Grade I–V' ) ),
					),
				)
			),
		),

		'student_life' => array(
			field( 'student_life', 'heading', 'textarea', __( 'Heading', 'seps' ), $heading_args ),
			field( 'student_life', 'text', 'textarea', __( 'Text', 'seps' ), $text_args ),
			link_field( 'student_life', 'button', __( 'Button', 'seps' ) ),
			image_field( 'student_life', 'image', __( 'Image', 'seps' ) ),
			field( 'student_life', 'stat_value', 'text', __( 'Statistic', 'seps' ), array( 'placeholder' => '75%+' ) ),
			field( 'student_life', 'stat_text', 'textarea', __( 'Statistic description', 'seps' ), $text_args ),
		),

		'principal'    => array(
			field( 'principal', 'heading', 'text', __( 'Heading', 'seps' ) ),
			field(
				'principal',
				'message',
				'textarea',
				__( 'Message', 'seps' ),
				array(
					'rows'         => 8,
					'new_lines'    => 'wpautop',
					'instructions' => __( 'Separate paragraphs with a blank line.', 'seps' ),
				)
			),
			image_field( 'principal', 'image', __( 'Photo', 'seps' ) ),
			field( 'principal', 'name', 'text', __( 'Name', 'seps' ) ),
			field( 'principal', 'role', 'text', __( 'Designation', 'seps' ) ),
		),

		'facilities'   => array(
			field( 'facilities', 'heading', 'text', __( 'Heading', 'seps' ) ),
			field( 'facilities', 'intro', 'textarea', __( 'Intro', 'seps' ), $text_args ),
			link_field( 'facilities', 'button', __( 'Button', 'seps' ) ),
			field(
				'facilities',
				'items',
				'repeater',
				__( 'Facilities', 'seps' ),
				array(
					'layout'       => 'block',
					'button_label' => __( 'Add facility', 'seps' ),
					'collapsed'    => 'field_seps_facilities_items_title',
					'sub_fields'   => array(
						image_field( 'facilities_items', 'image', __( 'Image', 'seps' ) ),
						field( 'facilities_items', 'title', 'text', __( 'Title', 'seps' ), array( 'required' => 1 ) ),
						field(
							'facilities_items',
							'tags',
							'textarea',
							__( 'Tags', 'seps' ),
							array(
								'rows'         => 3,
								'new_lines'    => '',
								'instructions' => __( 'One per line.', 'seps' ),
							)
						),
					),
				)
			),
		),

		'highlights'   => array(
			field(
				'highlights',
				'items',
				'repeater',
				__( 'Highlights', 'seps' ),
				array(
					'layout'       => 'table',
					'button_label' => __( 'Add highlight', 'seps' ),
					'sub_fields'   => array(
						field(
							'highlights_items',
							'label',
							'text',
							__( 'Label', 'seps' ),
							array(
								'required'     => 1,
								'instructions' => __( 'Emoji are supported, e.g. "🏆 Academic Excellence".', 'seps' ),
							)
						),
					),
				)
			),
		),

		'testimonials' => array(
			field( 'testimonials', 'heading', 'text', __( 'Heading', 'seps' ) ),
			field( 'testimonials', 'intro', 'textarea', __( 'Intro', 'seps' ), $text_args ),
			link_field( 'testimonials', 'button', __( 'Button', 'seps' ) ),
			image_field( 'testimonials', 'image', __( 'Image (circle)', 'seps' ) ),
			field(
				'testimonials',
				'items',
				'relationship',
				__( 'Testimonials', 'seps' ),
				array(
					'post_type'     => array( 'seps_testimonial' ),
					'filters'       => array( 'search' ),
					'return_format' => 'id',
					'instructions'  => __( 'Pick and order testimonials. Leave empty to show the latest six.', 'seps' ),
				)
			),
		),

		'faq'          => array(
			field( 'faq', 'heading', 'textarea', __( 'Heading', 'seps' ), $heading_args ),
			image_field( 'faq', 'image', __( 'Image', 'seps' ) ),
			field(
				'faq',
				'items',
				'relationship',
				__( 'Questions', 'seps' ),
				array(
					'post_type'     => array( 'seps_faq' ),
					'filters'       => array( 'search', 'taxonomy' ),
					'return_format' => 'id',
					'max'           => 8,
					'instructions'  => __( 'Pick and order questions. Leave empty to show the first five by menu order.', 'seps' ),
				)
			),
		),

		'news'         => array(
			field( 'news', 'heading', 'text', __( 'Heading', 'seps' ) ),
			field(
				'news',
				'category',
				'taxonomy',
				__( 'Category', 'seps' ),
				array(
					'taxonomy'      => 'category',
					'field_type'    => 'select',
					'allow_null'    => 1,
					'add_term'      => 0,
					'save_terms'    => 0,
					'load_terms'    => 0,
					'return_format' => 'id',
					'instructions'  => __( 'Optional. Leave empty to show the four latest posts.', 'seps' ),
				)
			),
		),
	);
}

/**
 * Register the front page field group.
 */
function register_front_page_fields(): void {
	$section_fields = front_page_section_fields();
	$fields         = array();

	foreach ( front_page_sections() as $name => $label ) {
		$fields[] = tab( 'front', $label );
		$fields[] = field(
			'front',
			$name,
			'group',
			$label,
			array(
				'layout'     => 'block',
				'sub_fields' => array_merge(
					array( enabled_toggle( $name ) ),
					$section_fields[ $name ] ?? array()
				),
			)
		);
	}

	acf_add_local_field_group(
		array(
			'key'                   => 'group_seps_front_page',
			'title'                 => __( 'Home Page Sections', 'seps' ),
			'style'                 => 'seamless',
			'position'              => 'acf_after_title',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => array( 'the_content', 'excerpt', 'discussion', 'comments', 'featured_image', 'format', 'send-trackbacks' ),
			'location'              => array(
				array(
					array(
						'param'    => 'page_type',
						'operator' => '==',
						'value'    => 'front_page',
					),
				),
			),
			'fields'                => $fields,
		)
	);
}
add_action( 'acf/include_fields', __NAMESPACE__ . '\\register_front_page_fields' );

/**
 * Edit the front page on the classic screen.
 *
 * Its content comes entirely from the section fields, and the block editor
 * would show an empty canvas above them (SCF's hide_on_screen only applies to
 * the classic screen).
 *
 * @param bool     $use_block_editor Whether to use the block editor.
 * @param \WP_Post $post             Post being edited.
 */
function front_page_classic_editor( bool $use_block_editor, \WP_Post $post ): bool {
	if ( 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === $post->ID ) {
		return false;
	}

	return $use_block_editor;
}
add_filter( 'use_block_editor_for_post', __NAMESPACE__ . '\\front_page_classic_editor', 10, 2 );
