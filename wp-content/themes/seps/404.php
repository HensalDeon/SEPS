<?php
/**
 * 404 template.
 *
 * @package SEPS
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="container page-shell page-shell--center">
	<h1 class="section-title"><?php esc_html_e( 'Page not found', 'seps' ); ?></h1>
	<p><?php esc_html_e( 'The page you are looking for may have moved. Try the menu above or head back home.', 'seps' ); ?></p>
	<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'seps' ); ?></a>
</div>
<?php
get_footer();
