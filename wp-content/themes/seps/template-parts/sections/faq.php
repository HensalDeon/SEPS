<?php
/**
 * Section: FAQs.
 *
 * Native <details>/<summary> accordion (keyboard and screen-reader support
 * for free); the shared name attribute makes it exclusive in modern browsers.
 * Also emits FAQPage structured data for search engines.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading Heading (line breaks preserved).
 *     @type int    $image   Attachment ID.
 *     @type int[]  $items   Selected FAQ IDs (optional).
 * }
 */

defined( 'ABSPATH' ) || exit;

if ( ! post_type_exists( 'seps_faq' ) ) {
	return;
}

$seps_ids   = array_filter( array_map( 'absint', (array) ( $args['items'] ?? array() ) ) );
$seps_query = array(
	'post_type'              => 'seps_faq',
	'post_status'            => 'publish',
	'posts_per_page'         => $seps_ids ? count( $seps_ids ) : 5,
	'orderby'                => $seps_ids ? 'post__in' : array(
		'menu_order' => 'ASC',
		'date'       => 'ASC',
	),
	'no_found_rows'          => true,
	'update_post_term_cache' => false,
	'update_post_meta_cache' => false,
);

if ( $seps_ids ) {
	$seps_query['post__in'] = $seps_ids;
}

$seps_faqs = get_posts( $seps_query );

if ( ! $seps_faqs ) {
	return;
}

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
$seps_image   = seps_image(
	$args['image'] ?? 0,
	'large',
	array(
		'class' => 'faq__image',
		'sizes' => '(min-width: 1280px) 665px, (min-width: 768px) 40vw, 100vw',
	)
);
$seps_schema  = array();
?>
<section class="faq"<?php echo $seps_heading ? ' aria-labelledby="faq-title"' : ''; ?>>
	<div class="container faq__inner">
		<?php if ( $seps_image ) : ?>
			<div class="faq__media"><?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?></div>
		<?php endif; ?>

		<div class="faq__content">
			<?php if ( $seps_heading ) : ?>
				<h2 id="faq-title" class="section-title faq__title"><?php echo seps_multiline( $seps_heading ); ?></h2>
			<?php endif; ?>

			<div class="faq__list">
				<?php foreach ( $seps_faqs as $seps_index => $seps_faq ) : ?>
					<?php
					$seps_answer   = apply_filters( 'the_content', $seps_faq->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
					$seps_question = get_the_title( $seps_faq );

					$seps_schema[] = array(
						'@type'          => 'Question',
						'name'           => wp_strip_all_tags( $seps_question ),
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => wp_strip_all_tags( $seps_answer ),
						),
					);
					?>
					<details class="faq-item" name="seps-faq" <?php echo 0 === $seps_index ? 'open' : ''; ?>>
						<summary class="faq-item__question">
							<span><?php echo esc_html( $seps_question ); ?></span>
							<?php echo seps_icon( 'chevron-circle', 20, 'faq-item__icon' ); ?>
						</summary>
						<div class="faq-item__answer"><?php echo wp_kses_post( $seps_answer ); ?></div>
					</details>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>

<script type="application/ld+json">
<?php
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON (JSON_HEX_TAG prevents breaking out of the script element).
echo wp_json_encode(
	array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $seps_schema,
	),
	JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
);
?>
</script>
