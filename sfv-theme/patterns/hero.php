<?php
/**
 * Title: Hero
 * Slug: sfv/hero
 * Categories: sfv-sections
 * Keywords: banner, heading, photo
 * Description: Full-width photo with the main page heading.
 * Viewport Width: 1440
 *
 * @package SFV_Theme
 */

$sfv_img = esc_url( get_theme_file_uri( 'assets/images/hero-default.webp' ) );
?>
<!-- wp:cover {"url":"<?php echo $sfv_img; ?>","dimRatio":100,"customGradient":"linear-gradient(270deg,rgba(1,48,59,0) 31.562%,rgb(1,48,59) 75.536%)","focalPoint":{"x":0.7,"y":0.4},"minHeight":515,"isDark":true,"align":"full","className":"sfv-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull is-dark sfv-hero" style="min-height:515px"><img class="wp-block-cover__image-background" alt="" src="<?php echo $sfv_img; ?>" style="object-position:70% 40%" data-object-fit="cover" data-object-position="70% 40%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim wp-block-cover__gradient-background has-background-gradient" style="background:linear-gradient(270deg,rgba(1,48,59,0) 31.562%,rgb(1,48,59) 75.536%)"></span><div class="wp-block-cover__inner-container"><!-- wp:heading {"level":1,"align":"wide","textColor":"white"} -->
<h1 class="wp-block-heading alignwide has-white-color has-text-color">Sharing the stoke with the blind/visually impaired (BVI).</h1>
<!-- /wp:heading --></div></div>
<!-- /wp:cover -->
