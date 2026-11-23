<?php
/**
 * Paginated grid of the main query's posts (News & Events, archives,
 * search).
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $id    Section ID (anchor), e.g. "all-news".
 *     @type string $title Heading text (plain).
 *     @type string $html  Heading HTML instead of $title (pre-escaped).
 *     @type int    $level Heading level: 1 (page title) or 2.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_level = 2 === (int) ( $args['level'] ?? 1 ) ? 2 : 1;
$seps_id    = sanitize_title( (string) ( $args['id'] ?? '' ) );
$seps_title = isset( $args['html'] ) ? wp_kses_post( (string) $args['html'] ) : esc_html( (string) ( $args['title'] ?? '' ) );
?>
<section class="post-list"<?php echo $seps_id ? ' id="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<div class="container">
		<?php if ( '' !== $seps_title ) : ?>
			<header class="post-list__header">
				<?php printf( '<h%1$d class="section-title">%2$s</h%1$d>', (int) $seps_level, $seps_title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above. ?>
			</header>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<ul class="post-grid" role="list">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<li class="post-grid__item">
						<?php get_template_part( 'template-parts/components/post-card', null, array( 'heading_tag' => 1 === $seps_level ? 'h2' : 'h3' ) ); ?>
					</li>
				<?php endwhile; ?>
			</ul>

			<?php
			the_posts_pagination(
				array(
					'mid_size'  => 1,
					'prev_text' => esc_html__( 'Previous', 'seps' ),
					'next_text' => esc_html__( 'Next', 'seps' ),
				)
			);
			?>
		<?php else : ?>
			<p><?php esc_html_e( 'Nothing found.', 'seps' ); ?></p>
		<?php endif; ?>
	</div>
</section>
