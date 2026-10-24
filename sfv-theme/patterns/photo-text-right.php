<?php
/**
 * Title: Photo and text (photo on the right)
 * Slug: sfv/photo-text-right
 * Categories: sfv-sections
 * Keywords: photo, image, text, story
 * Description: A heading, text and button beside a framed photo on the right.
 * Viewport Width: 1440
 *
 * @package SFV_Theme
 */
?>
<!-- wp:group {"align":"full","className":"sfv-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"margin":{"top":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull sfv-section" style="margin-top:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"align":"wide","verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"55%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:55%"><!-- wp:heading -->
<h2 class="wp-block-heading">A heading for this section</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Write a few sentences here. Replace the photo by selecting it and choosing Replace, and describe the new photo in Alternative text so people using screen readers know what it shows.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/register/">Register now</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"45%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:45%"><!-- wp:image {"sizeSlug":"full","className":"is-style-sfv-framed"} -->
<figure class="wp-block-image size-full is-style-sfv-framed"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/banner-default.webp' ) ); ?>" alt="Surfer standing on a board in the whitewater near the shore."/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
