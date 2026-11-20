<?php
/**
 * Field group: Site Settings (options page).
 *
 * Global data shared by the header and footer.
 *
 * @package SEPS
 */

namespace SEPS\Theme\SCF;

defined( 'ABSPATH' ) || exit;

/**
 * Register the Site Settings field group.
 */
function register_site_settings_fields(): void {
	$p = 'settings';

	acf_add_local_field_group(
		array(
			'key'                   => 'group_seps_site_settings',
			'title'                 => __( 'Site Settings', 'seps' ),
			'style'                 => 'seamless',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'location'              => array(
				array(
					array(
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'seps-site-settings',
					),
				),
			),
			'fields'                => array(
				// Contact.
				tab( $p, __( 'Contact', 'seps' ) ),
				field( $p, 'email', 'email', __( 'Email address', 'seps' ) ),
				field( $p, 'phone', 'text', __( 'Phone number', 'seps' ), array( 'placeholder' => '+971 2 552 9989' ) ),
				field(
					$p,
					'address',
					'textarea',
					__( 'Postal address', 'seps' ),
					array(
						'rows'      => 3,
						'new_lines' => '',
					)
				),

				// Social.
				tab( $p, __( 'Social', 'seps' ) ),
				field(
					$p,
					'social_links',
					'repeater',
					__( 'Social profiles', 'seps' ),
					array(
						'layout'       => 'table',
						'button_label' => __( 'Add profile', 'seps' ),
						'sub_fields'   => array(
							field(
								"{$p}_social",
								'network',
								'select',
								__( 'Network', 'seps' ),
								array(
									'choices'  => seps_social_networks(),
									'required' => 1,
								)
							),
							field( "{$p}_social", 'url', 'url', __( 'Profile URL', 'seps' ), array( 'required' => 1 ) ),
						),
					)
				),

				// Header.
				tab( $p, __( 'Header', 'seps' ) ),
				link_field(
					$p,
					'header_button',
					__( 'Header call-to-action', 'seps' ),
					array( 'instructions' => __( 'Shown at the end of the main navigation, e.g. "Register Now".', 'seps' ) )
				),

				// Footer.
				tab( $p, __( 'Footer', 'seps' ) ),
				field(
					$p,
					'footer_heading',
					'text',
					__( 'Footer heading', 'seps' ),
					array( 'instructions' => __( 'Defaults to the site title when empty.', 'seps' ) )
				),
				field(
					$p,
					'newsletter_text',
					'textarea',
					__( 'Subscribe prompt', 'seps' ),
					array(
						'rows'      => 2,
						'new_lines' => '',
					)
				),
				link_field( $p, 'newsletter_button', __( 'Subscribe button', 'seps' ) ),
				image_field( $p, 'footer_image', __( 'Footer photo', 'seps' ), array( 'instructions' => __( 'Shown cropped to a circle. Square images work best.', 'seps' ) ) ),
				field(
					$p,
					'copyright',
					'text',
					__( 'Copyright line', 'seps' ),
					array( 'instructions' => __( 'Use {year} for the current year.', 'seps' ) )
				),
			),
		)
	);
}
add_action( 'acf/include_fields', __NAMESPACE__ . '\\register_site_settings_fields' );
