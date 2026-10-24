<?php
/**
 * Title: Footer
 * Slug: sfv/footer
 * Inserter: no
 *
 * Site footer. After the fixed links it lists the links in the "Footer links"
 * shared section, which Editors manage (inc/footer-links.php). The legal name
 * and EIN were added to pass Google for Nonprofits verification; keep them. The street address was removed on purpose: it is a
 * volunteer's home address (see CLAUDE.md). Owned by Git; rendered by
 * parts/footer.html.
 *
 * @package SFV_Theme
 */

$sfv_donate_url = 'https://www.paypal.com/ncp/payment/YZ22T3386PE3J';
?>
<!-- wp:html -->
<div class="sfv-footer" id="contact">
	<div class="sfv-footer__inner">
		<div class="sfv-footer__brand">
			<img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo-stacked-inverse.svg' ) ); ?>" alt="Surfing for Vision" width="180" height="137">
		</div>
		<nav class="sfv-footer__nav" aria-label="Footer">
			<ul>
				<li><a href="<?php echo esc_url( home_url( '/register/' ) ); ?>"<?php sfv_current_attr( '/register/' ); ?>>Register</a></li>
				<li><a href="<?php echo esc_url( home_url( '/#about' ) ); ?>">About</a></li>
				<li><a href="<?php echo esc_url( home_url( '/#sponsors' ) ); ?>">Sponsors</a></li>
				<li><a href="<?php echo esc_url( $sfv_donate_url ); ?>">Donate</a></li>
				<?php foreach ( sfv_footer_links() as $sfv_link ) : ?>
					<li><a href="<?php echo esc_url( $sfv_link['url'] ); ?>"<?php sfv_current_attr( $sfv_link['url'] ); ?>><?php echo esc_html( $sfv_link['text'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<div class="sfv-footer__contact">
			<h2>Contact</h2>
			<p><a href="mailto:info@surfingforvision.com">info@surfingforvision.com</a></p>
			<p class="sfv-footer__legal">
				Surfing for Vision a NJ Nonprofit Corporation<br>
				EIN 39-3423583 · 501(c)(3) tax-exempt organization
			</p>
		</div>
		<div class="sfv-footer__social">
			<h2 class="screen-reader-text">Follow us</h2>
			<ul>
				<li><a href="https://www.instagram.com/surfingforvision/">Instagram</a></li>
			</ul>
		</div>
	</div>
	<div class="sfv-footer__bottom">
		<p>Surfing for Vision is a 501 (c) (3) nonprofit organization dedicated to providing a fun, safe surfing experience for the blind community.</p>
		<p>Copyright <?php echo esc_html( wp_date( 'Y' ) ); ?> Surfing for Vision</p>
	</div>
</div>
<!-- /wp:html -->
