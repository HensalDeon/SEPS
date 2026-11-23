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
		'page_hero'       => 'hero',
		'media_text'      => 'media-text',
		'values'          => 'values',
		'message'         => 'message',
		'team'            => 'team',
		'features'        => 'features',
		'badge_accordion' => 'badge-accordion',
		'timetable'       => 'timetable',
		'documents'       => 'documents',
		'requirements'    => 'requirements',
		'info_cards'      => 'info-cards',
		'text_image'      => 'text-image',
		'checklist'       => 'checklist',
		'faq'             => 'faq',
		'card_list'       => 'card-list',
		'text'            => 'text',
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

	// Short rich text (bold, links, lists) without media.
	$rich = static fn( string $prefix, string $name, string $label, array $args = array() ): array => field(
		$prefix,
		$name,
		'wysiwyg',
		$label,
		array_merge(
			array(
				'tabs'         => 'visual',
				'toolbar'      => 'basic',
				'media_upload' => 0,
				'delay'        => 1,
			),
			$args
		)
	);

	$background = static fn( string $prefix, array $choices, string $default ): array => field(
		$prefix,
		'background',
		'button_group',
		__( 'Background', 'seps' ),
		array(
			'choices'       => $choices,
			'default_value' => $default,
		)
	);

	$icon = static fn( string $prefix, string $name = 'icon', string $label = '' ): array => field(
		$prefix,
		$name,
		'select',
		'' !== $label ? $label : __( 'Icon', 'seps' ),
		array(
			'choices'    => seps_content_icon_choices(),
			'allow_null' => 1,
			'ui'         => 0,
		)
	);

	// Every layout gets an optional Section ID (anchor) as its last field.
	$layout = static fn( string $name, string $label, array $fields ): array => array(
		'key'        => "layout_seps_ps_{$name}",
		'name'       => $name,
		'label'      => $label,
		'display'    => 'block',
		'sub_fields' => array_merge(
			$fields,
			array(
				field(
					"ps_{$name}",
					'anchor',
					'text',
					__( 'Section ID (optional)', 'seps' ),
					array(
						'instructions' => __( 'Lets links jump to this section, e.g. "mandatory-disclosure" → /page/#mandatory-disclosure.', 'seps' ),
						'wrapper'      => array( 'width' => '50' ),
					)
				),
			)
		),
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
				field( 'ps_hero', 'note', 'text', __( 'Small note above the button', 'seps' ), array( 'placeholder' => 'Admissions 2026–2027' ) ),
				link_field( 'ps_hero', 'button', __( 'Button', 'seps' ) ),
				image_field( 'ps_hero', 'image', __( 'Image', 'seps' ), array( 'required' => 1 ) ),
				field(
					'ps_hero',
					'image_focus',
					'button_group',
					__( 'Keep in view', 'seps' ),
					array(
						'instructions'  => __( 'Which part of the photo stays visible when it is cropped.', 'seps' ),
						'choices'       => array(
							'top'    => __( 'Top', 'seps' ),
							'upper'  => __( 'Upper third', 'seps' ),
							'center' => __( 'Centre', 'seps' ),
						),
						'default_value' => 'upper',
					)
				),
				field(
					'ps_hero',
					'decor',
					'button_group',
					__( 'Decoration on the image edge', 'seps' ),
					array(
						'choices'       => array(
							'none'   => __( 'None', 'seps' ),
							'sun'    => __( 'Sun', 'seps' ),
							'flower' => __( 'Flower', 'seps' ),
						),
						'default_value' => 'none',
					)
				),
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

		'layout_seps_ps_features'   => $layout(
			'features',
			__( 'Highlights & Focus Areas', 'seps' ),
			array(
				field( 'ps_features', 'heading', 'text', __( 'Heading', 'seps' ) ),
				field( 'ps_features', 'intro', 'textarea', __( 'Intro (large text)', 'seps' ), $text ),
				field(
					'ps_features',
					'tiles',
					'repeater',
					__( 'Highlight tiles', 'seps' ),
					array(
						'layout'       => 'table',
						'button_label' => __( 'Add tile', 'seps' ),
						'sub_fields'   => array(
							$icon( 'ps_features_tiles' ),
							field( 'ps_features_tiles', 'label', 'text', __( 'Label', 'seps' ) ),
						),
					)
				),
				field( 'ps_features', 'cards_heading', 'text', __( 'Cards heading', 'seps' ) ),
				field(
					'ps_features',
					'cards',
					'repeater',
					__( 'Cards', 'seps' ),
					array(
						'layout'       => 'table',
						'button_label' => __( 'Add card', 'seps' ),
						'sub_fields'   => array(
							$icon( 'ps_features_cards' ),
							field( 'ps_features_cards', 'title', 'text', __( 'Title', 'seps' ) ),
							field( 'ps_features_cards', 'text', 'textarea', __( 'Text', 'seps' ), array_merge( $text, array( 'rows' => 2 ) ) ),
						),
					)
				),
				$icon( 'ps_features', 'callout_icon', __( 'Callout: icon', 'seps' ) ),
				field( 'ps_features', 'callout_title', 'text', __( 'Callout: title', 'seps' ) ),
				field( 'ps_features', 'callout_text', 'textarea', __( 'Callout: text', 'seps' ), $text ),
				image_field( 'ps_features', 'callout_image', __( 'Callout: image', 'seps' ) ),
			)
		),

		'layout_seps_ps_badge_accordion' => $layout(
			'badge_accordion',
			__( 'Badge Accordion (e.g. courses)', 'seps' ),
			array(
				field( 'ps_badges', 'heading', 'text', __( 'Heading', 'seps' ) ),
				field( 'ps_badges', 'intro', 'textarea', __( 'Intro', 'seps' ), $text ),
				image_field( 'ps_badges', 'image', __( 'Image (beside the first group)', 'seps' ) ),
				field(
					'ps_badges',
					'groups',
					'repeater',
					__( 'Groups', 'seps' ),
					array(
						'layout'       => 'block',
						'min'          => 1,
						'button_label' => __( 'Add group', 'seps' ),
						'sub_fields'   => array(
							field( 'ps_badges_groups', 'heading', 'text', __( 'Group heading (optional)', 'seps' ) ),
							field(
								'ps_badges_groups',
								'items',
								'repeater',
								__( 'Items', 'seps' ),
								array(
									'layout'       => 'table',
									'button_label' => __( 'Add item', 'seps' ),
									'sub_fields'   => array(
										field( 'ps_badges_items', 'badge', 'text', __( 'Badge', 'seps' ), array( 'placeholder' => 'GRADE I–V' ) ),
										field( 'ps_badges_items', 'title', 'text', __( 'Title', 'seps' ), array( 'required' => 1 ) ),
										field( 'ps_badges_items', 'content', 'textarea', __( 'Content', 'seps' ), array_merge( $text, array( 'rows' => 2 ) ) ),
									),
								)
							),
						),
					)
				),
			)
		),

		'layout_seps_ps_timetable'  => $layout(
			'timetable',
			__( 'Timetable', 'seps' ),
			array(
				field( 'ps_timetable', 'heading', 'text', __( 'Heading', 'seps' ) ),
				field( 'ps_timetable', 'intro', 'textarea', __( 'Intro (large text)', 'seps' ), $text ),
				field(
					'ps_timetable',
					'days',
					'repeater',
					__( 'Days', 'seps' ),
					array(
						'layout'       => 'block',
						'button_label' => __( 'Add day', 'seps' ),
						'collapsed'    => 'field_seps_ps_timetable_days_label',
						'sub_fields'   => array(
							field( 'ps_timetable_days', 'label', 'text', __( 'Day label', 'seps' ), array( 'placeholder' => 'Monday – Thursday (Full School Day)' ) ),
							field(
								'ps_timetable_days',
								'rows',
								'repeater',
								__( 'Rows', 'seps' ),
								array(
									'layout'       => 'table',
									'button_label' => __( 'Add row', 'seps' ),
									'sub_fields'   => array(
										field(
											'ps_timetable_rows',
											'slots',
											'textarea',
											__( 'Slots', 'seps' ),
											array(
												'rows'         => 5,
												'new_lines'    => '',
												'instructions' => __( 'One per line: Name | Time, e.g. "Period 1 | 7:40 - 8:25 AM". The last slot of a day fills the row.', 'seps' ),
											)
										),
									),
								)
							),
						),
					)
				),
			)
		),

		'layout_seps_ps_documents'  => $layout(
			'documents',
			__( 'Documents', 'seps' ),
			array(
				field( 'ps_documents', 'heading', 'text', __( 'Heading', 'seps' ) ),
				field( 'ps_documents', 'intro', 'textarea', __( 'Intro', 'seps' ), $text ),
				field(
					'ps_documents',
					'items',
					'repeater',
					__( 'Documents', 'seps' ),
					array(
						'layout'       => 'block',
						'button_label' => __( 'Add document', 'seps' ),
						'collapsed'    => 'field_seps_ps_documents_items_title',
						'sub_fields'   => array(
							field( 'ps_documents_items', 'title', 'text', __( 'Title', 'seps' ), array( 'required' => 1 ) ),
							image_field( 'ps_documents_items', 'image', __( 'Preview image', 'seps' ) ),
							field(
								'ps_documents_items',
								'file',
								'file',
								__( 'Document (PDF or image)', 'seps' ),
								array(
									'return_format' => 'url',
									'library'       => 'all',
									'instructions'  => __( 'Opens in a new tab. If empty, the preview image opens instead.', 'seps' ),
								)
							),
						),
					)
				),
			)
		),

		'layout_seps_ps_requirements' => $layout(
			'requirements',
			__( 'Requirements (badges + checklist)', 'seps' ),
			array(
				field( 'ps_req', 'heading', 'text', __( 'Heading', 'seps' ) ),
				field( 'ps_req', 'intro', 'textarea', __( 'Intro', 'seps' ), $text ),
				link_field( 'ps_req', 'button', __( 'Button', 'seps' ) ),
				field( 'ps_req', 'left_title', 'text', __( 'Left column: title', 'seps' ), array( 'placeholder' => 'Age Requirements' ) ),
				field(
					'ps_req',
					'left_rows',
					'repeater',
					__( 'Left column: rows', 'seps' ),
					array(
						'layout'       => 'table',
						'button_label' => __( 'Add row', 'seps' ),
						'sub_fields'   => array(
							field( 'ps_req_rows', 'badge', 'text', __( 'Badge', 'seps' ), array( 'placeholder' => 'KG 1' ) ),
							field( 'ps_req_rows', 'text', 'textarea', __( 'Text', 'seps' ), array_merge( $text, array( 'rows' => 2 ) ) ),
						),
					)
				),
				field( 'ps_req', 'right_title', 'text', __( 'Right column: title', 'seps' ), array( 'placeholder' => 'Documents Required' ) ),
				field(
					'ps_req',
					'right_items',
					'textarea',
					__( 'Right column: checklist', 'seps' ),
					array(
						'rows'         => 8,
						'new_lines'    => '',
						'instructions' => __( 'One item per line.', 'seps' ),
					)
				),
			)
		),

		'layout_seps_ps_info_cards' => $layout(
			'info_cards',
			__( 'Info Cards (with photo)', 'seps' ),
			array(
				$background( 'ps_cards', array(
						'white' => __( 'White', 'seps' ),
						'lilac' => __( 'Lilac', 'seps' ),
						'navy'  => __( 'Navy', 'seps' ),
					), 'navy' ),
				field(
					'ps_cards',
					'corner',
					'true_false',
					__( 'Rounded top-right corner', 'seps' ),
					array( 'ui' => 1 )
				),
				field( 'ps_cards', 'heading', 'text', __( 'Heading', 'seps' ) ),
				$rich( 'ps_cards', 'intro', __( 'Intro', 'seps' ) ),
				link_field( 'ps_cards', 'button', __( 'Button', 'seps' ) ),
				field(
					'ps_cards',
					'icon_style',
					'button_group',
					__( 'Card icon position', 'seps' ),
					array(
						'choices'       => array(
							'above'  => __( 'Above the title', 'seps' ),
							'inline' => __( 'Beside the title', 'seps' ),
						),
						'default_value' => 'above',
					)
				),
				field(
					'ps_cards',
					'cards',
					'repeater',
					__( 'Cards', 'seps' ),
					array(
						'layout'       => 'block',
						'button_label' => __( 'Add card', 'seps' ),
						'collapsed'    => 'field_seps_ps_cards_items_title',
						'sub_fields'   => array(
							$icon( 'ps_cards_items' ),
							field( 'ps_cards_items', 'title', 'text', __( 'Title', 'seps' ), array( 'required' => 1 ) ),
							field(
								'ps_cards_items',
								'width',
								'button_group',
								__( 'Width', 'seps' ),
								array(
									'choices'       => array(
										'half' => __( 'Half', 'seps' ),
										'full' => __( 'Full', 'seps' ),
									),
									'default_value' => 'half',
								)
							),
							$rich( 'ps_cards_items', 'text', __( 'Text', 'seps' ) ),
							field(
								'ps_cards_items',
								'rows',
								'repeater',
								__( 'Details table (optional)', 'seps' ),
								array(
									'layout'       => 'table',
									'button_label' => __( 'Add row', 'seps' ),
									'sub_fields'   => array(
										field( 'ps_cards_rows', 'label', 'text', __( 'Label', 'seps' ) ),
										field( 'ps_cards_rows', 'value', 'text', __( 'Value', 'seps' ) ),
									),
								)
							),
						),
					)
				),
				image_field( 'ps_cards', 'image', __( 'Photo', 'seps' ) ),
				field(
					'ps_cards',
					'image_style',
					'button_group',
					__( 'Photo style', 'seps' ),
					array(
						'choices'       => array(
							'right'  => __( 'Rounded, right', 'seps' ),
							'circle' => __( 'Circle, left (with sun)', 'seps' ),
						),
						'default_value' => 'right',
					)
				),
			)
		),

		'layout_seps_ps_text_image' => $layout(
			'text_image',
			__( 'Text + Image', 'seps' ),
			array(
				$background( 'ps_ti', array(
						'white' => __( 'White', 'seps' ),
						'lilac' => __( 'Lilac', 'seps' ),
						'navy'  => __( 'Navy', 'seps' ),
					), 'white' ),
				field( 'ps_ti', 'heading', 'text', __( 'Heading', 'seps' ) ),
				$rich( 'ps_ti', 'content', __( 'Text', 'seps' ) ),
				field(
					'ps_ti',
					'buttons',
					'repeater',
					__( 'Buttons', 'seps' ),
					array(
						'layout'       => 'table',
						'max'          => 2,
						'button_label' => __( 'Add button', 'seps' ),
						'sub_fields'   => array(
							link_field( 'ps_ti_buttons', 'link', __( 'Link', 'seps' ) ),
							field(
								'ps_ti_buttons',
								'style',
								'select',
								__( 'Style', 'seps' ),
								array(
									'choices'       => array(
										'accent'  => __( 'Yellow', 'seps' ),
										'primary' => __( 'Blue', 'seps' ),
									),
									'default_value' => 'accent',
								)
							),
						),
					)
				),
				image_field( 'ps_ti', 'image', __( 'Photo', 'seps' ) ),
				field(
					'ps_ti',
					'image_size',
					'button_group',
					__( 'Photo size', 'seps' ),
					array(
						'choices'       => array(
							'narrow' => __( 'Narrow (fills height)', 'seps' ),
							'wide'   => __( 'Wide (landscape)', 'seps' ),
							'large'  => __( 'Large (half width)', 'seps' ),
						),
						'default_value' => 'narrow',
					)
				),
				field(
					'ps_ti',
					'image_position',
					'button_group',
					__( 'Photo position', 'seps' ),
					array(
						'choices'       => array(
							'right' => __( 'Right', 'seps' ),
							'left'  => __( 'Left', 'seps' ),
						),
						'default_value' => 'right',
					)
				),
				field(
					'ps_ti',
					'decor',
					'button_group',
					__( 'Decoration', 'seps' ),
					array(
						'choices'       => array(
							'none'   => __( 'None', 'seps' ),
							'flower' => __( 'Flower', 'seps' ),
							'halves' => __( 'Half circles', 'seps' ),
						),
						'default_value' => 'none',
					)
				),
			)
		),

		'layout_seps_ps_checklist'  => $layout(
			'checklist',
			__( 'Notice + Checklist', 'seps' ),
			array(
				field( 'ps_check', 'heading', 'text', __( 'Heading', 'seps' ) ),
				$icon( 'ps_check', 'notice_icon', __( 'Notice: icon', 'seps' ) ),
				field( 'ps_check', 'notice_title', 'text', __( 'Notice: title', 'seps' ) ),
				$rich( 'ps_check', 'notice_text', __( 'Notice: text', 'seps' ) ),
				image_field( 'ps_check', 'notice_image', __( 'Notice: photo', 'seps' ) ),
				field( 'ps_check', 'list_title', 'text', __( 'Checklist: title', 'seps' ) ),
				$rich( 'ps_check', 'list_intro', __( 'Checklist: intro', 'seps' ) ),
				$rich( 'ps_check', 'list', __( 'Checklist', 'seps' ), array( 'instructions' => __( 'Use a bulleted list; bold text is shown in brand blue.', 'seps' ) ) ),
				link_field( 'ps_check', 'button', __( 'Button', 'seps' ) ),
				image_field( 'ps_check', 'image', __( 'Photo (circle)', 'seps' ) ),
			)
		),

		'layout_seps_ps_faq'        => $layout(
			'faq',
			__( 'FAQs', 'seps' ),
			array(
				$background(
					'ps_faq',
					array(
						'lilac' => __( 'Lilac', 'seps' ),
						'white' => __( 'White', 'seps' ),
					),
					'lilac'
				),
				field( 'ps_faq', 'heading', 'textarea', __( 'Heading', 'seps' ), array( 'rows' => 2, 'new_lines' => '' ) ),
				image_field( 'ps_faq', 'image', __( 'Photo', 'seps' ) ),
				field(
					'ps_faq',
					'items',
					'relationship',
					__( 'Questions', 'seps' ),
					array(
						'post_type'     => array( 'seps_faq' ),
						'filters'       => array( 'search', 'taxonomy' ),
						'return_format' => 'id',
						'max'           => 8,
						'instructions'  => __( 'Pick and order questions (manage them under FAQs).', 'seps' ),
					)
				),
			)
		),

		'layout_seps_ps_card_list'  => $layout(
			'card_list',
			__( 'Cards (carousel or grid)', 'seps' ),
			array(
				$background( 'ps_cardlist', array(
						'white' => __( 'White', 'seps' ),
						'lilac' => __( 'Lilac', 'seps' ),
						'navy'  => __( 'Navy', 'seps' ),
					), 'lilac' ),
				field(
					'ps_cardlist',
					'heading',
					'textarea',
					__( 'Heading', 'seps' ),
					array(
						'rows'      => 2,
						'new_lines' => '',
					)
				),
				$rich( 'ps_cardlist', 'intro', __( 'Intro', 'seps' ) ),
				field(
					'ps_cardlist',
					'signature',
					'textarea',
					__( 'Sign-off (optional)', 'seps' ),
					array(
						'instructions' => __( 'The first line is shown in bold, e.g. "With appreciation," then "Faculty & Staff".', 'seps' ),
						'rows'         => 2,
						'new_lines'    => '',
					)
				),
				link_field( 'ps_cardlist', 'button', __( 'Button', 'seps' ) ),
				field(
					'ps_cardlist',
					'display',
					'button_group',
					__( 'Show cards as', 'seps' ),
					array(
						'choices'       => array(
							'carousel' => __( 'Carousel', 'seps' ),
							'grid'     => __( 'Grid', 'seps' ),
						),
						'default_value' => 'carousel',
					)
				),
				field(
					'ps_cardlist',
					'columns',
					'button_group',
					__( 'Cards per row (wide screens)', 'seps' ),
					array(
						'choices'       => array(
							'3' => '3',
							'4' => '4',
						),
						'default_value' => '3',
					)
				),
				field(
					'ps_cardlist',
					'image_height',
					'button_group',
					__( 'Card photo height', 'seps' ),
					array(
						'choices'       => array(
							'small'  => __( 'Small', 'seps' ),
							'medium' => __( 'Medium', 'seps' ),
							'large'  => __( 'Large', 'seps' ),
						),
						'default_value' => 'large',
					)
				),
				field(
					'ps_cardlist',
					'panel',
					'true_false',
					__( 'Show cards on a navy panel', 'seps' ),
					array( 'ui' => 1 )
				),
				field(
					'ps_cardlist',
					'panel_title',
					'text',
					__( 'Panel title (optional)', 'seps' ),
					array(
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_seps_ps_cardlist_panel',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					)
				),
				field(
					'ps_cardlist',
					'cards',
					'repeater',
					__( 'Cards', 'seps' ),
					array(
						'layout'       => 'block',
						'button_label' => __( 'Add card', 'seps' ),
						'collapsed'    => 'field_seps_ps_cardlist_cards_title',
						'sub_fields'   => array(
							image_field( 'ps_cardlist_cards', 'image', __( 'Photo', 'seps' ), array( 'wrapper' => array( 'width' => '30' ) ) ),
							field( 'ps_cardlist_cards', 'title', 'text', __( 'Title', 'seps' ), array( 'required' => 1 ) ),
							field( 'ps_cardlist_cards', 'text', 'textarea', __( 'Text', 'seps' ), $text ),
							link_field(
								'ps_cardlist_cards',
								'link',
								__( 'Link', 'seps' ),
								array( 'instructions' => __( 'With link text: shown as a button. Without: the whole card is clickable.', 'seps' ) )
							),
						),
					)
				),
				image_field(
					'ps_cardlist',
					'image',
					__( 'Side photo (optional)', 'seps' ),
					array( 'instructions' => __( 'Shown in a circle on a lilac shape to the right of the cards.', 'seps' ) )
				),
				field(
					'ps_cardlist',
					'decor',
					'button_group',
					__( 'Decoration', 'seps' ),
					array(
						'choices'       => array(
							'none' => __( 'None', 'seps' ),
							'sun'  => __( 'Sun', 'seps' ),
						),
						'default_value' => 'none',
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
