<?php
/**
 * SCF field groups for the plugin's post types.
 *
 * @package SEPS\Core
 */

namespace SEPS\Core\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Testimonial fields: the quote and the author's relation to the school.
 * The author's name is the post title.
 */
function register_testimonial_fields(): void {
	acf_add_local_field_group(
		array(
			'key'                   => 'group_seps_testimonial',
			'title'                 => __( 'Testimonial', 'seps-core' ),
			'position'              => 'acf_after_title',
			'style'                 => 'seamless',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'seps_testimonial',
					),
				),
			),
			'fields'                => array(
				array(
					'key'          => 'field_seps_testimonial_quote',
					'name'         => 'quote',
					'label'        => __( 'Quote', 'seps-core' ),
					'type'         => 'textarea',
					'required'     => 1,
					'rows'         => 5,
					'new_lines'    => '',
					'instructions' => __( 'Without quotation marks; they are added automatically.', 'seps-core' ),
				),
				array(
					'key'         => 'field_seps_testimonial_author_role',
					'name'        => 'author_role',
					'label'       => __( 'Author details', 'seps-core' ),
					'type'        => 'text',
					'placeholder' => __( 'Parent of Grade 4 Student', 'seps-core' ),
				),
			),
		)
	);
}
add_action( 'acf/include_fields', __NAMESPACE__ . '\\register_testimonial_fields' );

/**
 * Staff fields. Name = post title, photo = featured image, team = taxonomy.
 */
function register_person_fields(): void {
	acf_add_local_field_group(
		array(
			'key'                   => 'group_seps_person',
			'title'                 => __( 'Staff member', 'seps-core' ),
			'position'              => 'acf_after_title',
			'style'                 => 'seamless',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'seps_person',
					),
				),
			),
			'fields'                => array(
				array(
					'key'          => 'field_seps_person_position',
					'name'         => 'position',
					'label'        => __( 'Position', 'seps-core' ),
					'type'         => 'textarea',
					'rows'         => 2,
					'new_lines'    => '',
					'placeholder'  => __( 'Grade 2–3 Primary Section Lead Teacher', 'seps-core' ),
					'instructions' => __( 'Line breaks are kept. Set the photo as the Featured image; use Order (Page Attributes) to sort.', 'seps-core' ),
				),
			),
		)
	);
}
add_action( 'acf/include_fields', __NAMESPACE__ . '\\register_person_fields' );
