<?php
/**
 * Title: Season – save the date
 * Slug: sfv/season-save-the-date
 * Categories: sfv-sections
 * Keywords: save the date, next event, announcement, donate
 * Description: The next date is set but registration isn't open yet: thanks for last year, the new date and details (from Event settings) and a Donate button. Used as the "Save the date" homepage season.
 * Viewport Width: 1440
 *
 * Draft wording, awaiting the team's approval. The date and times come from
 * Pages → Event settings through the Event details block, so they never need
 * typing here.
 *
 * @package SFV_Theme
 */
?>
<!-- wp:group {"align":"full","className":"sfv-band","backgroundColor":"mist","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|60"},"margin":{"top":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull sfv-band has-mist-background-color has-background" style="margin-top:0;padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:group {"align":"wide","className":"is-style-sfv-card sfv-overlap","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide is-style-sfv-card sfv-overlap"><!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"width":"55%"} -->
<div class="wp-block-column" style="flex-basis:55%"><!-- wp:heading {"textColor":"teal"} -->
<h2 class="wp-block-heading has-teal-color has-text-color">Save the date!</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Thank you to the 150 surfers, family members and friends who registered last summer, and to every volunteer, lifeguard and sponsor who made the day happen. We had so much fun sharing the stoke with all of you!</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>We’re excited to announce that Surfing for Vision is coming back. Mark your calendar!</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>Registration opens soon.</strong> Follow <a href="https://www.instagram.com/surfingforvision/">@surfingforvision on Instagram</a> to hear the moment it opens.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>In the meantime, you can help keep the day free for every surfer.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"45%"} -->
<div class="wp-block-column" style="flex-basis:45%"><!-- wp:sfv/event-details /-->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( SFV_DONATE_URL ); ?>">Donate</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
