<?php
/**
 * Footer links Editors manage.
 *
 * The footer itself is code, but after its fixed links it lists whatever links
 * are in the "Footer links" shared section (a synced pattern holding a list of
 * links). Editors add a new page there, under Pages → Shared sections, without a
 * pull request. Only each link's text and address are used, so formatting in
 * the editor can't change how the footer looks.
 *
 * @package SFV_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SFV_FOOTER_LINKS_SLUG = 'footer-links';

/**
 * Links from the Footer links shared section, in order.
 *
 * @return array<int, array{text: string, url: string}>
 */
function sfv_footer_links() {
	$block = get_page_by_path( SFV_FOOTER_LINKS_SLUG, OBJECT, 'wp_block' );

	if ( ! $block || 'publish' !== $block->post_status ) {
		return array();
	}

	$links = array();

	preg_match_all( '#(<a\b[^>]*>)(.*?)</a>#is', do_blocks( $block->post_content ), $matches, PREG_SET_ORDER );

	foreach ( $matches as $match ) {
		$tag = new WP_HTML_Tag_Processor( $match[1] );
		$tag->next_tag( 'A' );
		$url  = $tag->get_attribute( 'href' );
		$text = trim( wp_strip_all_tags( $match[2] ) );

		if ( is_string( $url ) && '' !== trim( $url ) && '' !== $text ) {
			$links[] = array(
				'url'  => $url,
				'text' => $text,
			);
		}
	}

	return $links;
}
