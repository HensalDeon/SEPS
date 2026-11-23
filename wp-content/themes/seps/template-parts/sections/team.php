<?php
/**
 * Section: Team (e.g. "Senior Leadership Team").
 *
 * Each group lists the Staff (seps_person) in one Team, ordered by menu order.
 * Photo = featured image; staff without a photo get an initials placeholder.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading Heading.
 *     @type string $intro   Intro text.
 *     @type array  $groups  Repeater: heading, team (term ID), style ('featured'|'grid').
 * }
 */

defined( 'ABSPATH' ) || exit;

if ( ! post_type_exists( 'seps_person' ) ) {
	return;
}

$seps_groups = array();

foreach ( (array) ( $args['groups'] ?? array() ) as $seps_group ) {
	$seps_term = absint( $seps_group['team'] ?? 0 );

	if ( ! $seps_term ) {
		continue;
	}

	$seps_people = get_posts(
		array(
			'post_type'              => 'seps_person',
			'post_status'            => 'publish',
			'posts_per_page'         => 100,
			'orderby'                => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Small, bounded taxonomy.
				array(
					'taxonomy' => 'seps_team',
					'terms'    => $seps_term,
				),
			),
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		)
	);

	if ( $seps_people ) {
		$seps_groups[] = array(
			'heading' => trim( (string) ( $seps_group['heading'] ?? '' ) ),
			'style'   => 'featured' === ( $seps_group['style'] ?? 'grid' ) ? 'featured' : 'grid',
			'people'  => $seps_people,
		);
	}
}

if ( ! $seps_groups ) {
	return;
}

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
$seps_intro   = trim( (string) ( $args['intro'] ?? '' ) );
$seps_id      = wp_unique_id( 'seps-team-' );

/**
 * Initials for the no-photo placeholder, e.g. "Mrs. Deepa Binu" → "DB".
 *
 * @param string $name Full name with optional title.
 */
$seps_initials = static function ( string $name ): string {
	$words = preg_split( '/\s+/', trim( preg_replace( '/^(mr|mrs|ms|miss|dr|prof)\.?\s+/i', '', $name ) ) );
	$first = mb_substr( (string) ( $words[0] ?? '' ), 0, 1 );
	$last  = count( $words ) > 1 ? mb_substr( (string) end( $words ), 0, 1 ) : '';

	return mb_strtoupper( $first . $last );
};
?>
<section class="team"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<?php echo seps_decor( 'flower-yellow', 'team__decor' ); ?>

	<div class="container">
		<?php if ( $seps_heading || $seps_intro ) : ?>
			<header class="team__header">
				<?php if ( $seps_heading ) : ?>
					<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title"><?php echo esc_html( $seps_heading ); ?></h2>
				<?php endif; ?>
				<?php if ( $seps_intro ) : ?>
					<p class="team__intro"><?php echo esc_html( $seps_intro ); ?></p>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<?php foreach ( $seps_groups as $seps_group ) : ?>
			<div class="team__group team__group--<?php echo esc_attr( $seps_group['style'] ); ?>">
				<?php if ( $seps_group['heading'] ) : ?>
					<h3 class="team__group-title"><?php echo esc_html( $seps_group['heading'] ); ?></h3>
				<?php endif; ?>

				<ul class="team__grid" role="list">
					<?php foreach ( $seps_group['people'] as $seps_person ) : ?>
						<?php
						$seps_name     = get_the_title( $seps_person );
						$seps_position = (string) seps_field( 'position', $seps_person->ID );
						?>
						<li class="person-card">
							<div class="person-card__media">
								<?php if ( has_post_thumbnail( $seps_person ) ) : ?>
									<?php
									echo get_the_post_thumbnail(
										$seps_person,
										'large',
										array(
											'class' => 'person-card__image',
											'alt'   => '',
											'sizes' => 'featured' === $seps_group['style'] ? '(min-width: 768px) 429px, 100vw' : '(min-width: 1280px) 390px, (min-width: 640px) 50vw, 100vw',
										)
									);
									?>
								<?php else : ?>
									<span class="person-card__initials" aria-hidden="true"><?php echo esc_html( $seps_initials( $seps_name ) ); ?></span>
								<?php endif; ?>
							</div>
							<div class="person-card__body">
								<p class="person-card__name"><?php echo esc_html( $seps_name ); ?></p>
								<?php if ( '' !== trim( $seps_position ) ) : ?>
									<p class="person-card__position"><?php echo seps_multiline( $seps_position ); ?></p>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>
	</div>
</section>
