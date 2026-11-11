<?php
/**
 * Title: Photo gallery
 * Slug: sfv/gallery
 * Categories: sfv-sections
 * Keywords: photos, gallery, images
 * Description: A heading and a grid of photos. Give every photo Alternative text describing it.
 * Viewport Width: 1440
 *
 * @package SFV_Theme
 */
?>
<?php
$sfv_photos = array(
	array( 'hero-default.webp', 'Surfer riding a wave with a volunteer alongside in the water.' ),
	array( 'banner-default.webp', 'Surfer standing on a board in the whitewater near the shore.' ),
	array( 'about-default.webp', 'Young surfer standing on a surfboard with arms raised while riding a small wave.' ),
);
?>
<!-- wp:group {"align":"full","className":"sfv-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"margin":{"top":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull sfv-section" style="margin-top:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:paragraph {"align":"wide","className":"is-style-sfv-kicker"} -->
<p class="alignwide is-style-sfv-kicker">Photos</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"align":"wide"} -->
<h2 class="wp-block-heading alignwide">Photos from the day</h2>
<!-- /wp:heading -->

<!-- wp:gallery {"columns":3,"imageCrop":true,"linkTo":"none","sizeSlug":"large","align":"wide","className":"sfv-gallery"} -->
<figure class="wp-block-gallery alignwide has-nested-images columns-3 is-cropped sfv-gallery"><?php foreach ( $sfv_photos as $sfv_photo ) : ?><!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/' . $sfv_photo[0] ) ); ?>" alt="<?php echo esc_attr( $sfv_photo[1] ); ?>"/></figure>
<!-- /wp:image -->

<?php endforeach; ?></figure>
<!-- /wp:gallery --></div>
<!-- /wp:group -->
