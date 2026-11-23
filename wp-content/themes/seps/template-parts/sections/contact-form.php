<?php
/**
 * Section: Enquiry form ("We'd Love to Hear From You", Contact page).
 *
 * The form is a Contact Form 7 form (picked in the section), so its fields,
 * email recipients and messages are managed in Contact → Contact Forms. Its
 * template uses the theme's markup (enquiry__field, enquiry__control, …);
 * the seeder creates it. Without CF7 the section shows its heading and
 * photo, and a notice for administrators.
 *
 * @package SEPS
 *
 * @var array $args {
 *     @type string $heading Heading.
 *     @type string $intro   Intro text.
 *     @type int    $form    Contact Form 7 form ID.
 *     @type int    $image   Photo on the right.
 * }
 */

defined( 'ABSPATH' ) || exit;

$seps_heading = trim( (string) ( $args['heading'] ?? '' ) );
$seps_intro   = trim( (string) ( $args['intro'] ?? '' ) );
$seps_form_id = absint( $args['form'] ?? 0 );
$seps_form    = '';

if ( $seps_form_id && SEPS\Theme\CF7\is_active() && wpcf7_contact_form( $seps_form_id ) ) {
	SEPS\Theme\CF7\enqueue();
	$seps_form = do_shortcode( sprintf( '[contact-form-7 id="%d" html_class="enquiry__form"]', $seps_form_id ) );
}

if ( '' === $seps_heading && '' === $seps_form ) {
	return;
}

$seps_id    = wp_unique_id( 'seps-enquiry-' );
$seps_image = seps_image(
	$args['image'] ?? 0,
	'large',
	array(
		'class' => 'enquiry__image',
		'sizes' => '(min-width: 1024px) 409px, 100vw',
	)
);
?>
<section class="enquiry<?php echo $seps_image ? ' enquiry--has-media' : ''; ?>"<?php echo $seps_heading ? ' aria-labelledby="' . esc_attr( $seps_id ) . '"' : ''; ?>>
	<span id="enquiry-form" class="section-anchor"></span>

	<div class="container enquiry__inner">
		<div class="enquiry__main">
			<?php if ( $seps_heading || $seps_intro ) : ?>
				<header class="enquiry__header">
					<?php if ( $seps_heading ) : ?>
						<h2 id="<?php echo esc_attr( $seps_id ); ?>" class="section-title"><?php echo esc_html( $seps_heading ); ?></h2>
					<?php endif; ?>
					<?php if ( $seps_intro ) : ?>
						<p class="enquiry__intro"><?php echo esc_html( $seps_intro ); ?></p>
					<?php endif; ?>
				</header>
			<?php endif; ?>

			<?php if ( $seps_form ) : ?>
				<?php echo $seps_form; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Contact Form 7 output. ?>
			<?php elseif ( current_user_can( 'edit_pages' ) ) : ?>
				<p class="enquiry__notice"><?php esc_html_e( 'No form to show: activate Contact Form 7 and pick a form in this section.', 'seps' ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $seps_image ) : ?>
			<div class="enquiry__media">
				<?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
				<?php echo seps_decor( 'halves-pair-brand', 'enquiry__decor' ); ?>
			</div>
		<?php endif; ?>
	</div>
</section>
