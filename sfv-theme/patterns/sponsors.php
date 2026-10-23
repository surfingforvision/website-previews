<?php
/**
 * Title: Sponsors
 * Slug: sfv/sponsors
 * Categories: sfv-sections
 * Keywords: sponsors, partners, logos
 * Description: Sponsor logos. On the site this is used as one synced pattern. To add a sponsor, add an Image block to the logo grid and set its alternative text to the sponsor's name. Linked from the menu as #sponsors.
 * Viewport Width: 1440
 *
 * @package SFV_Theme
 */
?>
<!-- wp:group {"anchor":"sponsors","align":"full","className":"sfv-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"margin":{"top":"0"}}},"layout":{"type":"constrained"}} -->
<div id="sponsors" class="wp-block-group alignfull sfv-section" style="margin-top:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:paragraph {"align":"wide","className":"is-style-sfv-kicker"} -->
<p class="alignwide is-style-sfv-kicker">Sponsors</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"align":"wide"} -->
<h2 class="wp-block-heading alignwide">Surfing for Vision is proud to partner with the City of Long Branch and other local organizations.</h2>
<!-- /wp:heading -->

<!-- wp:group {"align":"wide","className":"sfv-logo-grid","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
<div class="wp-block-group alignwide sfv-logo-grid"><!-- wp:image {"sizeSlug":"full"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/sponsor-default.webp' ) ); ?>" alt="City of Long Branch"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"align":"wide"} -->
<p class="alignwide">Interested in becoming a sponsor? <a href="#contact">Contact us</a> to learn more.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
