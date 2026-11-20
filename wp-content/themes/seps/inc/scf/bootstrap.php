<?php
/**
 * Secure Custom Fields integration.
 *
 * Field groups are registered in code (see fields-*.php) so they are
 * version-controlled, reviewed, and identical across environments. They show
 * in SCF's UI as read-only "local" groups.
 *
 * @link https://wordpress.org/plugins/secure-custom-fields/
 *
 * @package SEPS
 */

namespace SEPS\Theme\SCF;

defined( 'ABSPATH' ) || exit;

/**
 * Whether the SCF registration API is available.
 */
function is_active(): bool {
	return function_exists( 'acf_add_local_field_group' );
}

/**
 * Build a field definition with a stable, namespaced key.
 *
 * Keys are what SCF uses to map stored values to field definitions; they must
 * never change once content exists. Convention: field_seps_{prefix}_{name}.
 *
 * @param string              $prefix Unique prefix (group/parent path).
 * @param string              $name   Field name (meta key segment).
 * @param string              $type   SCF field type.
 * @param string              $label  Admin label.
 * @param array<string,mixed> $args   Any extra SCF field settings.
 * @return array<string,mixed>
 */
function field( string $prefix, string $name, string $type, string $label, array $args = array() ): array {
	return array_merge(
		array(
			'key'   => "field_seps_{$prefix}_{$name}",
			'name'  => $name,
			'label' => $label,
			'type'  => $type,
		),
		$args
	);
}

/**
 * Tab field (presentational only, stores nothing).
 *
 * @param string $prefix Unique prefix.
 * @param string $label  Tab label.
 * @return array<string,mixed>
 */
function tab( string $prefix, string $label ): array {
	return array(
		'key'       => 'field_seps_' . $prefix . '_tab_' . sanitize_key( $label ),
		'label'     => $label,
		'name'      => '',
		'type'      => 'tab',
		'placement' => 'top',
	);
}

/**
 * Common "show this section" toggle used by every front-page section.
 *
 * @param string $prefix Unique prefix.
 * @return array<string,mixed>
 */
function enabled_toggle( string $prefix ): array {
	return field(
		$prefix,
		'enabled',
		'true_false',
		__( 'Show this section', 'seps' ),
		array(
			'ui'            => 1,
			'default_value' => 1,
		)
	);
}

/**
 * Image field returning an attachment ID (lets templates use srcset/sizes).
 *
 * @param string              $prefix Unique prefix.
 * @param string              $name   Field name.
 * @param string              $label  Label.
 * @param array<string,mixed> $args   Extra settings.
 * @return array<string,mixed>
 */
function image_field( string $prefix, string $name, string $label, array $args = array() ): array {
	return field(
		$prefix,
		$name,
		'image',
		$label,
		array_merge(
			array(
				'return_format' => 'id',
				'preview_size'  => 'medium',
				'library'       => 'all',
				'mime_types'    => 'jpg,jpeg,png,webp,avif',
			),
			$args
		)
	);
}

/**
 * Link field returning url/title/target.
 *
 * @param string              $prefix Unique prefix.
 * @param string              $name   Field name.
 * @param string              $label  Label.
 * @param array<string,mixed> $args   Extra settings.
 * @return array<string,mixed>
 */
function link_field( string $prefix, string $name, string $label, array $args = array() ): array {
	return field( $prefix, $name, 'link', $label, array_merge( array( 'return_format' => 'array' ), $args ) );
}

/**
 * Admin notice when SCF is missing.
 */
function missing_plugin_notice(): void {
	if ( is_active() || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
		esc_html__( 'SEPS theme:', 'seps' ),
		esc_html__( 'the Secure Custom Fields plugin is required to manage page content.', 'seps' ),
		esc_url( admin_url( 'plugin-install.php?s=secure-custom-fields&tab=search&type=term' ) ),
		esc_html__( 'Install Secure Custom Fields', 'seps' )
	);
}
add_action( 'admin_notices', __NAMESPACE__ . '\\missing_plugin_notice' );

/**
 * Global "Site Settings" options page (contact details, socials, footer).
 */
function register_options_page(): void {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}

	acf_add_options_page(
		array(
			'page_title' => __( 'Site Settings', 'seps' ),
			'menu_title' => __( 'Site Settings', 'seps' ),
			'menu_slug'  => 'seps-site-settings',
			'capability' => 'edit_theme_options',
			'icon_url'   => 'dashicons-admin-site-alt3',
			'position'   => 59,
			'redirect'   => false,
			'autoload'   => true,
		)
	);
}
add_action( 'acf/init', __NAMESPACE__ . '\\register_options_page' );
