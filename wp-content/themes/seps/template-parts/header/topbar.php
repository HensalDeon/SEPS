<?php
/**
 * Header top bar: contact details, utility menu and social links.
 *
 * @package SEPS
 */

defined( 'ABSPATH' ) || exit;

$seps_email   = sanitize_email( (string) seps_option( 'email' ) );
$seps_phone   = (string) seps_option( 'phone' );
$seps_utility = seps_menu( 'utility', 'topbar__menu' );
$seps_social  = seps_social_links( 'social' );

if ( '' === $seps_email . $seps_phone . $seps_utility . $seps_social ) {
	return;
}
?>
<div class="topbar">
	<div class="container topbar__inner">
		<?php if ( $seps_email || $seps_phone ) : ?>
			<ul class="topbar__contact" role="list">
				<?php if ( $seps_email ) : ?>
					<li>
						<a class="topbar__link" href="<?php echo esc_url( 'mailto:' . antispambot( $seps_email, 1 ) ); ?>">
							<?php echo seps_icon( 'email', 14 ); ?>
							<span><?php echo esc_html( antispambot( $seps_email ) ); ?></span>
						</a>
					</li>
				<?php endif; ?>
				<?php if ( $seps_phone ) : ?>
					<li>
						<a class="topbar__link" href="<?php echo esc_url( seps_tel_uri( $seps_phone ) ); ?>">
							<?php echo seps_icon( 'phone', 14 ); ?>
							<span><?php echo esc_html( $seps_phone ); ?></span>
						</a>
					</li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>

		<div class="topbar__end">
			<?php if ( $seps_utility ) : ?>
				<nav class="topbar__nav" aria-label="<?php esc_attr_e( 'Utility', 'seps' ); ?>">
					<?php echo $seps_utility; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
				</nav>
			<?php endif; ?>
			<?php echo $seps_social; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
		</div>
	</div>
</div>
