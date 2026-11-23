<?php
/**
 * Field group: Page Sections (flexible content section builder).
 *
 * Every page except the front page and the posts page is built from an
 * ordered list of sections. Each layout maps to a template in
 * template-parts/sections/ and receives its row as $args, the same contract
 * the front page uses, so sections can be reused across pages.
 *
 * Adding a layout: define it in page_section_layouts(), map its template in
 * page_section_templates(), then build the template and its SCSS partial.
 *
 * Field keys: field_seps_ps_{layout}_{name} (never change once content exists).
 *
 * @package SEPS
 */

namespace SEPS\Theme\SCF;

defined( 'ABSPATH' ) || exit;

/**
 * Layout name => template slug in template-parts/sections/.
 *
 * @return array<string,string>
 */
function page_section_templates(): array {
	return array(
		'page_hero'  => 'hero',
		'media_text' => 'media-text',
		'values'     => 'values',
		'message'    => 'message',
		'team'       => 'team',
		'text'       => 'text',
	);
}

/**
 * Flexible content layouts.
 *
 * @return array<string,array<string,mixed>>
 */
function page_section_layouts(): array {
	$text = array(
		'rows'      => 3,
		'new_lines' => '',
	);

	$layout = static fn( string $name, string $label, array $fields ): array => array(
		'key'        => "layout_seps_ps_{$name}",
		'name'       => $name,
		'label'      => $label,
		'display'    => 'block',
		'sub_fields' => $fields,
	);

	return array(
		'layout_seps_ps_page_hero'  => $layout(
			'page_hero',
			__( 'Page Hero', 'seps' ),
			array(
				field(
					'ps_hero',
					'title',
					'text',
					__( 'Heading', 'seps' ),
					array( 'instructions' => __( 'Leave empty to use the page title.', 'seps' ) )
				),
				field( 'ps_hero', 'text', 'textarea', __( 'Intro', 'seps' ), $text ),
				link_field( 'ps_hero', 'button', __( 'Button', 'seps' ) ),
				image_field( 'ps_hero', 'image', __( 'Image', 'seps' ), array( 'required' => 1 ) ),
			)
		),

		'layout_seps_ps_media_text' => $layout(
			'media_text',
			__( 'Image + Text Blocks', 'seps' ),
			array(
				image_field( 'ps_media', 'image', __( 'Image (tall)', 'seps' ) ),
				field(
					'ps_media',
					'image_position',
					'button_group',
					__( 'Image position', 'seps' ),
					array(
						'choices'       => array(
							'left'  => __( 'Left', 'seps' ),
							'right' => __( 'Right', 'seps' ),
						),
						'default_value' => 'left',
					)
				),
				field(
					'ps_media',
					'blocks',
					'repeater',
					__( 'Text blocks', 'seps' ),
					array(
						'layout'       => 'block',
						'min'          => 1,
						'button_label' => __( 'Add block', 'seps' ),
						'collapsed'    => 'field_seps_ps_media_blocks_heading',
						'sub_fields'   => array(
							field( 'ps_media_blocks', 'heading', 'text', __( 'Heading', 'seps' ), array( 'required' => 1 ) ),
							field( 'ps_media_blocks', 'text', 'textarea', __( 'Text', 'seps' ), $text ),
							field(
								'ps_media_blocks',
								'cards',
								'repeater',
								__( 'Cards', 'seps' ),
								array(
									'layout'       => 'table',
									'button_label' => __( 'Add card', 'seps' ),
									'sub_fields'   => array(
										field( 'ps_media_cards', 'title', 'text', __( 'Title', 'seps' ) ),
										field( 'ps_media_cards', 'text', 'textarea', __( 'Text', 'seps' ), $text ),
									),
								)
							),
						),
					)
				),
			)
		),

		'layout_seps_ps_values'     => $layout(
			'values',
			__( 'Values & Guidelines', 'seps' ),
			array(
				field( 'ps_values', 'heading', 'text', __( 'Heading', 'seps' ) ),
				field( 'ps_values', 'intro', 'textarea', __( 'Intro', 'seps' ), $text ),
				field( 'ps_values', 'panel_title', 'text', __( 'Guidelines panel: title', 'seps' ) ),
				field(
					'ps_values',
					'panel_items',
					'repeater',
					__( 'Guidelines panel: points', 'seps' ),
					array(
						'layout'       => 'table',
						'button_label' => __( 'Add point', 'seps' ),
						'sub_fields'   => array(
							field( 'ps_values_points', 'label', 'text', __( 'Label (bold)', 'seps' ) ),
							field( 'ps_values_points', 'text', 'textarea', __( 'Text', 'seps' ), array_merge( $text, array( 'rows' => 2 ) ) ),
						),
					)
				),
				link_field( 'ps_values', 'panel_button', __( 'Guidelines panel: button', 'seps' ) ),
				field(
					'ps_values',
					'accordion',
					'repeater',
					__( 'Values (accordion)', 'seps' ),
					array(
						'layout'       => 'block',
						'button_label' => __( 'Add value', 'seps' ),
						'collapsed'    => 'field_seps_ps_values_accordion_title',
						'sub_fields'   => array(
							field( 'ps_values_accordion', 'title', 'text', __( 'Title (bold)', 'seps' ), array( 'required' => 1 ) ),
							field( 'ps_values_accordion', 'subtitle', 'text', __( 'Subtitle', 'seps' ) ),
							field(
								'ps_values_accordion',
								'points',
								'textarea',
								__( 'Points', 'seps' ),
								array(
									'rows'         => 5,
									'new_lines'    => '',
									'instructions' => __( 'One per line.', 'seps' ),
								)
							),
						),
					)
				),
			)
		),

		'layout_seps_ps_message'    => $layout(
			'message',
			__( 'Message (with photo and quote)', 'seps' ),
			array(
				field( 'ps_message', 'heading', 'text', __( 'Heading', 'seps' ) ),
				field(
					'ps_message',
					'message',
					'textarea',
					__( 'Message', 'seps' ),
					array(
						'rows'         => 10,
						'new_lines'    => 'wpautop',
						'instructions' => __( 'Separate paragraphs with a blank line.', 'seps' ),
					)
				),
				field( 'ps_message', 'name', 'text', __( 'Name', 'seps' ) ),
				field( 'ps_message', 'role', 'text', __( 'Designation', 'seps' ) ),
				image_field( 'ps_message', 'image', __( 'Photo', 'seps' ) ),
				field( 'ps_message', 'quote', 'textarea', __( 'Pull quote', 'seps' ), array_merge( $text, array( 'rows' => 2 ) ) ),
			)
		),

		'layout_seps_ps_team'       => $layout(
			'team',
			__( 'Team', 'seps' ),
			array(
				field( 'ps_team', 'heading', 'text', __( 'Heading', 'seps' ) ),
				field( 'ps_team', 'intro', 'textarea', __( 'Intro', 'seps' ), $text ),
				field(
					'ps_team',
					'groups',
					'repeater',
					__( 'Groups', 'seps' ),
					array(
						'layout'       => 'block',
						'min'          => 1,
						'button_label' => __( 'Add group', 'seps' ),
						'collapsed'    => 'field_seps_ps_team_groups_heading',
						'instructions' => __( 'Each group lists the Staff in a team, ordered by their Order value. Manage people under Staff.', 'seps' ),
						'sub_fields'   => array(
							field( 'ps_team_groups', 'heading', 'text', __( 'Heading', 'seps' ) ),
							field(
								'ps_team_groups',
								'team',
								'taxonomy',
								__( 'Team', 'seps' ),
								array(
									'taxonomy'      => 'seps_team',
									'field_type'    => 'select',
									'allow_null'    => 0,
									'add_term'      => 1,
									'save_terms'    => 0,
									'load_terms'    => 0,
									'return_format' => 'id',
									'required'      => 1,
								)
							),
							field(
								'ps_team_groups',
								'style',
								'button_group',
								__( 'Card size', 'seps' ),
								array(
									'choices'       => array(
										'featured' => __( 'Large (principals)', 'seps' ),
										'grid'     => __( 'Grid (4 per row)', 'seps' ),
									),
									'default_value' => 'grid',
								)
							),
						),
					)
				),
			)
		),

		'layout_seps_ps_text'       => $layout(
			'text',
			__( 'Text', 'seps' ),
			array(
				field( 'ps_text', 'heading', 'text', __( 'Heading', 'seps' ) ),
				field(
					'ps_text',
					'content',
					'wysiwyg',
					__( 'Content', 'seps' ),
					array(
						'tabs'         => 'all',
						'toolbar'      => 'full',
						'media_upload' => 1,
					)
				),
			)
		),
	);
}

/**
 * Register the Page Sections field group.
 */
function register_page_section_fields(): void {
	acf_add_local_field_group(
		array(
			'key'                   => 'group_seps_page_sections',
			'title'                 => __( 'Page Sections', 'seps' ),
			'style'                 => 'seamless',
			'position'              => 'acf_after_title',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => array( 'the_content', 'excerpt', 'discussion', 'comments', 'featured_image', 'format', 'send-trackbacks' ),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'page',
					),
					array(
						'param'    => 'page_type',
						'operator' => '!=',
						'value'    => 'front_page',
					),
					array(
						'param'    => 'page_type',
						'operator' => '!=',
						'value'    => 'posts_page',
					),
				),
			),
			'fields'                => array(
				array(
					'key'          => 'field_seps_page_sections',
					'name'         => 'page_sections',
					'label'        => __( 'Sections', 'seps' ),
					'type'         => 'flexible_content',
					'button_label' => __( 'Add section', 'seps' ),
					'instructions' => __( 'Build the page from sections. Drag to reorder. Start with a Page Hero.', 'seps' ),
					'layouts'      => page_section_layouts(),
				),
			),
		)
	);
}
add_action( 'acf/include_fields', __NAMESPACE__ . '\\register_page_section_fields' );

/**
 * Pages are edited with the section builder on the classic screen
 * (SCF's hide_on_screen does not apply in the block editor).
 *
 * @param bool   $use_block_editor Whether to use the block editor.
 * @param string $post_type        Post type.
 */
function pages_classic_editor( bool $use_block_editor, string $post_type ): bool {
	return 'page' === $post_type ? false : $use_block_editor;
}
add_filter( 'use_block_editor_for_post_type', __NAMESPACE__ . '\\pages_classic_editor', 10, 2 );
