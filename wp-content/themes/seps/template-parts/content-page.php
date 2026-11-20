<?php
/**
 * Generic page content.
 *
 * @package SEPS
 */

defined( 'ABSPATH' ) || exit;
?>
<article <?php post_class( 'container page-shell entry' ); ?>>
	<header class="page-shell__header">
		<h1 class="section-title"><?php the_title(); ?></h1>
	</header>
	<div class="entry__content">
		<?php the_content(); ?>
	</div>
</article>
