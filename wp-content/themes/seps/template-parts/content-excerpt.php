<?php
/**
 * Post summary used in lists.
 *
 * @package SEPS
 */

defined( 'ABSPATH' ) || exit;
?>
<article <?php post_class( 'news-card news-card--compact' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="news-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( 'seps-thumb', array( 'class' => 'news-card__image' ) ); ?>
		</a>
	<?php endif; ?>
	<div class="news-card__body">
		<h2 class="news-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<p class="news-card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
	</div>
</article>
