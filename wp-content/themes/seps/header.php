<?php
/**
 * Site header.
 *
 * @package SEPS
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_print_inline_script_tag( "document.documentElement.classList.replace('no-js','js');" ); ?>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'seps' ); ?></a>

<header class="site-header">
	<?php get_template_part( 'template-parts/header/topbar' ); ?>
	<?php get_template_part( 'template-parts/header/masthead' ); ?>
</header>

<main id="main" class="site-main" tabindex="-1">
