<?php
/**
 * Site footer: identity, contact, subscribe form, menu columns, legal row.
 *
 * Column headings are the names of the menus assigned to each footer
 * location, so editors control both links and headings from Appearance → Menus.
 *
 * @package SEPS
 */

defined( 'ABSPATH' ) || exit;

$seps_heading = (string) seps_option( 'footer_heading' );
$seps_heading = '' !== $seps_heading ? $seps_heading : get_bloginfo( 'name' );
$seps_address = (string) seps_option( 'address' );
$seps_email   = sanitize_email( (string) seps_option( 'email' ) );
$seps_phone   = (string) seps_option( 'phone' );
$seps_prompt  = (string) seps_option( 'newsletter_text' );
$seps_form_id = absint( seps_option( 'newsletter_form' ) );
$seps_form    = '';

// Subscribe: a Contact Form 7 form (email + button); otherwise a link button.
if ( $seps_form_id && SEPS\Theme\CF7\is_active() && wpcf7_contact_form( $seps_form_id ) ) {
	SEPS\Theme\CF7\enqueue();
	$seps_form = do_shortcode( sprintf( '[contact-form-7 id="%d" html_class="newsletter__form"]', $seps_form_id ) );
}

$seps_cta = '' === $seps_form ? seps_button( seps_option( 'newsletter_button' ), 'light', 'newsletter__button' ) : '';
$seps_image   = seps_image(
	seps_option( 'footer_image' ),
	'seps-square',
	array(
		'class'   => 'site-footer__image',
		'sizes'   => '(min-width: 1536px) 390px, 1px',
		'loading' => 'lazy',
	)
);

$seps_columns = array();
foreach ( array( 'footer_campus', 'footer_quick', 'footer_portals' ) as $seps_location ) {
	$seps_menu = seps_menu( $seps_location, 'footer-nav__menu' );

	if ( '' !== $seps_menu ) {
		$seps_columns[ $seps_location ] = $seps_menu;
	}
}

$seps_legal = seps_menu( 'legal', 'site-footer__legal' );
?>
<footer class="site-footer">
	<?php echo seps_decor( 'flower-yellow', 'site-footer__decor' ); ?>

	<div class="site-footer__top">
		<div class="container site-footer__top-inner">
			<div class="site-footer__identity">
				<p class="site-footer__heading"><?php echo esc_html( $seps_heading ); ?></p>

				<address class="site-footer__contact">
					<?php if ( $seps_address ) : ?>
						<p class="contact-item">
							<?php echo seps_icon( 'location', 24, 'contact-item__icon' ); ?>
							<span><?php echo seps_multiline( $seps_address ); ?></span>
						</p>
					<?php endif; ?>

					<?php if ( $seps_email || $seps_phone ) : ?>
						<p class="contact-item">
							<?php echo seps_icon( 'headset', 24, 'contact-item__icon' ); ?>
							<span>
								<?php if ( $seps_email ) : ?>
									<a href="<?php echo esc_url( 'mailto:' . antispambot( $seps_email, 1 ) ); ?>"><?php echo esc_html( antispambot( $seps_email ) ); ?></a><br>
								<?php endif; ?>
								<?php if ( $seps_phone ) : ?>
									<a href="<?php echo esc_url( seps_tel_uri( $seps_phone ) ); ?>"><?php echo esc_html( $seps_phone ); ?></a>
								<?php endif; ?>
							</span>
						</p>
					<?php endif; ?>
				</address>
			</div>

			<?php if ( $seps_prompt || $seps_form || $seps_cta ) : ?>
				<div class="newsletter<?php echo $seps_form ? ' newsletter--form' : ''; ?>">
					<?php if ( $seps_prompt ) : ?>
						<p class="newsletter__text"><?php echo esc_html( $seps_prompt ); ?></p>
					<?php endif; ?>
					<?php echo $seps_form ? $seps_form : $seps_cta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Contact Form 7 output / pre-escaped template tag output. ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="site-footer__main">
		<div class="container site-footer__main-inner">
			<?php if ( $seps_columns ) : ?>
				<nav class="footer-nav" aria-label="<?php esc_attr_e( 'Footer', 'seps' ); ?>">
					<?php foreach ( $seps_columns as $seps_location => $seps_menu ) : ?>
						<div class="footer-nav__column footer-nav__column--<?php echo esc_attr( str_replace( '_', '-', $seps_location ) ); ?>">
							<h2 class="footer-nav__title"><?php echo seps_menu_label( $seps_location ); ?></h2>
							<?php echo $seps_menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
						</div>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>

			<?php if ( $seps_image ) : ?>
				<div class="site-footer__media"><?php echo $seps_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?></div>
			<?php endif; ?>
		</div>

		<div class="container site-footer__bottom">
			<p class="site-footer__copyright"><?php echo seps_copyright(); ?></p>
			<?php if ( $seps_legal ) : ?>
				<nav aria-label="<?php esc_attr_e( 'Legal', 'seps' ); ?>"><?php echo $seps_legal; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?></nav>
			<?php endif; ?>
		</div>
	</div>
</footer>
