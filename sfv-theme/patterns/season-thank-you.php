<?php
/**
 * Title: Season – thank you
 * Slug: sfv/season-thank-you
 * Categories: sfv-sections
 * Keywords: thank you, photos, gallery, after the event
 * Description: After the event: thanks to everyone who came, with photos from the day. Used as the "Thank you" homepage season.
 * Viewport Width: 1440
 *
 * @package SFV_Theme
 */

$sfv_photos = array(
	array( 'hero-default.webp', 'Surfer riding a wave with a volunteer alongside in the water.' ),
	array( 'banner-default.webp', 'Surfer standing on a board in the whitewater near the shore.' ),
	array( 'about-default.webp', 'Young surfer standing on a surfboard with arms raised while riding a small wave.' ),
);
?>
<!-- wp:group {"align":"full","className":"sfv-band","backgroundColor":"mist","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|60"},"margin":{"top":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull sfv-band has-mist-background-color has-background" style="margin-top:0;padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:group {"align":"wide","className":"is-style-sfv-card sfv-overlap","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide is-style-sfv-card sfv-overlap"><!-- wp:heading {"textColor":"teal"} -->
<h2 class="wp-block-heading has-teal-color has-text-color">Thank you for an amazing day!</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Thank you to every surfer, family member, volunteer, lifeguard and sponsor who joined us on the beach in Long Branch. Here are a few moments from the day.</p>
<!-- /wp:paragraph -->

<!-- wp:gallery {"columns":3,"imageCrop":true,"linkTo":"none","sizeSlug":"large","className":"sfv-gallery"} -->
<figure class="wp-block-gallery has-nested-images columns-3 is-cropped sfv-gallery"><?php foreach ( $sfv_photos as $sfv_photo ) : ?><!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/' . $sfv_photo[0] ) ); ?>" alt="<?php echo esc_attr( $sfv_photo[1] ); ?>"/></figure>
<!-- /wp:image -->

<?php endforeach; ?></figure>
<!-- /wp:gallery -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--50)"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="https://www.instagram.com/surfingforvision/">More photos on Instagram</a></div>
<!-- /wp:button -->

<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://www.paypal.com/ncp/payment/YZ22T3386PE3J">Donate</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
