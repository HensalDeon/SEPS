<?php
/**
 * Header masthead: brand, primary navigation and CTA.
 *
 * Desktop: inline navigation. Below the lg breakpoint the same markup becomes
 * an off-canvas drawer (see assets/src/js/modules/navigation.js).
 *
 * @package SEPS
 */

defined( 'ABSPATH' ) || exit;

$seps_logo_id  = (int) get_theme_mod( 'custom_logo' );
$seps_tagline  = get_bloginfo( 'description', 'display' );
$seps_primary  = seps_menu( 'primary', 'site-nav__menu' ); // Single level: no dropdowns.
$seps_utility  = seps_menu( 'utility', 'site-drawer__utility' );
$seps_button   = seps_button( seps_option( 'header_button' ), 'primary', 'masthead__cta' );
$seps_has_menu = '' !== $seps_primary . $seps_button . $seps_utility;
?>
<div class="masthead">
	<div class="container masthead__inner">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php
			if ( $seps_logo_id ) {
				echo wp_get_attachment_image(
					$seps_logo_id,
					'full',
					false,
					array(
						'class'   => 'brand__logo',
						'alt'     => '',
						'loading' => 'eager',
					)
				);
			}
			?>
			<span class="brand__text">
				<span class="brand__name"><?php bloginfo( 'name' ); ?></span>
				<?php if ( $seps_tagline ) : ?>
					<span class="brand__tagline"><?php echo esc_html( $seps_tagline ); ?></span>
				<?php endif; ?>
			</span>
		</a>

		<?php if ( $seps_has_menu ) : ?>
			<button
				type="button"
				class="menu-toggle"
				aria-controls="site-drawer"
				aria-expanded="false"
				data-drawer-toggle
			>
				<span class="menu-toggle__bars" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'seps' ); ?></span>
			</button>

			<div class="site-drawer" id="site-drawer" data-drawer>
				<?php if ( $seps_primary ) : ?>
					<nav class="site-nav" aria-label="<?php esc_attr_e( 'Primary', 'seps' ); ?>">
						<?php echo $seps_primary; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
					</nav>
				<?php endif; ?>

				<?php echo $seps_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>

				<?php if ( $seps_utility ) : ?>
					<nav class="site-drawer__secondary" aria-label="<?php esc_attr_e( 'Utility (mobile)', 'seps' ); ?>">
						<?php echo $seps_utility; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped template tag output. ?>
					</nav>
				<?php endif; ?>

				<?php echo seps_social_links( 'social social--drawer' ); ?>
			</div>
		<?php endif; ?>
	</div>
</div>
