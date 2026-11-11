<?php
/**
 * Title: About
 * Slug: sfv/about
 * Categories: sfv-sections
 * Keywords: about, mission, photo
 * Description: Dark section with a framed photo and what the day is about. Linked from the menu as #about.
 * Viewport Width: 1440
 *
 * @package SFV_Theme
 */
?>
<!-- wp:group {"anchor":"about","align":"full","className":"sfv-section","backgroundColor":"deep","textColor":"white","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"margin":{"top":"0"}}},"layout":{"type":"constrained"}} -->
<div id="about" class="wp-block-group alignfull sfv-section has-white-color has-deep-background-color has-text-color has-background" style="margin-top:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:paragraph {"align":"wide","className":"is-style-sfv-kicker"} -->
<p class="alignwide is-style-sfv-kicker">About</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"align":"wide","verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"45%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:45%"><!-- wp:image {"sizeSlug":"full","className":"is-style-sfv-framed"} -->
<figure class="wp-block-image size-full is-style-sfv-framed"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/about-default.webp' ) ); ?>" alt="Young surfer standing on a surfboard with arms raised while riding a small wave."/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"55%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:55%"><!-- wp:heading {"textColor":"lime"} -->
<h2 class="wp-block-heading has-lime-color has-text-color">Surfing for Vision is a day at the beach – there’s something for everyone.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Sharing the beach in a controlled &amp; safe environment, with New Jersey’s best surfers and lifeguards on tap, in addition to activities and volunteer opportunities alike for friends and family will get everyone psyched!</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Come surf with us, lend a hand, or just drop-in. The team assures you it will be firing – not the swell, but the day.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>Surfboard &amp; lifejacket provided, shaka &amp; stoke supply is endless, registration is limited.</strong></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
