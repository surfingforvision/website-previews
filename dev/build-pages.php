<?php
/**
 * Builds the sfv-theme content from the theme's patterns: sponsor alt text, the
 * synced patterns (Event details, Sponsors and the three homepage seasons), the
 * Home and Register pages. Idempotent: re-running updates what it created.
 *
 * Local:   npx wp-env run cli wp eval-file wp-content/sfv-dev/build-pages.php
 * Preview: run by the PR preview blueprint (.github/playground/) in WordPress
 *          Playground, where the production media library doesn't exist, so
 *          every photo falls back to the theme's default images.
 *
 * Attachment IDs match production because the local DB is a production copy,
 * except the About photo, which is imported here (see CLAUDE.md cutover notes).
 *
 * @package SFV_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SFV_HERO_ID     = 71; // Figma hero photo.
const SFV_REGISTER_ID = 52; // Chloe and friend surfing.

/**
 * Sponsor logos in display order, with the alt text a screen reader announces.
 */
$sfv_sponsors = array(
	79 => 'City of Long Branch',
	83 => 'City of Long Branch Ocean Rescue',
	75 => 'Beach House Classic Boardshop',
	76 => 'WCP Kitchen Bar Events',
	77 => 'TAK Waterman',
	78 => 'Jetty',
	80 => 'Ski Barn',
	81 => 'Surfers’ Environmental Alliance',
	82 => 'Lucky Dog Surf Co',
	84 => 'Best Day Foundation',
	87 => 'Baseline Social',
	92 => 'Wave Resort',
	93 => 'Solestra Systems',
);

/**
 * 2026 event photos for the Thank-you season, with alt text describing each.
 */
$sfv_event_photos = array(
	53 => 'A young surfer in a life jacket stands in shallow water beside a surfboard, surrounded by volunteers in matching shirts.',
	57 => 'Volunteers in the water gather around a surfer lying on a board, ready to push into a wave.',
	59 => 'Volunteers walk a surfer wearing a life jacket out of the ocean after a ride.',
	60 => 'Three smiling participants sit under a tent, giving a thumbs up and a shaka.',
	62 => 'A surfer in a red life jacket wades out of the water arm in arm with a volunteer.',
	63 => 'Two smiling people pose together on the beach, one wearing a life jacket.',
);

function sfv_dev_log( $message ) {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::log( $message );
	} else {
		echo esc_html( $message ) . "\n";
	}
}

function sfv_dev_fail( $message ) {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::error( $message );
	}
	throw new RuntimeException( $message );
}

function sfv_dev_is_image( $att_id ) {
	return 'attachment' === get_post_type( $att_id ) && wp_attachment_is_image( $att_id );
}

/**
 * Renders a theme pattern file (sfv/hero → patterns/hero.php) ourselves, so
 * nested pattern references are left for sfv_dev_resolve() to turn into
 * synced-pattern references.
 */
function sfv_dev_pattern( $slug ) {
	$path = get_theme_file_path( 'patterns/' . substr( $slug, strlen( 'sfv/' ) ) . '.php' );

	if ( ! str_starts_with( $slug, 'sfv/' ) || ! is_file( $path ) ) {
		sfv_dev_fail( "No pattern file for {$slug}. Is sfv-theme active?" );
	}

	ob_start();
	include $path;
	return trim( ob_get_clean() );
}

function sfv_dev_upsert( $post_type, $slug, $title, $content, $extra = array() ) {
	$existing = get_page_by_path( $slug, OBJECT, $post_type );
	$postarr  = array_merge(
		array(
			'post_type'    => $post_type,
			'post_name'    => $slug,
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => 'publish',
		),
		$extra
	);

	if ( $existing ) {
		$postarr['ID'] = $existing->ID;
	}

	$id = wp_insert_post( wp_slash( $postarr ), true );

	if ( is_wp_error( $id ) ) {
		sfv_dev_fail( $id->get_error_message() );
	}

	sfv_dev_log( sprintf( '%s %s #%d (%s)', $existing ? 'Updated' : 'Created', $post_type, $id, $slug ) );

	return $id;
}

/**
 * Expands the theme's nested pattern references: synced ones become block
 * references, the rest are inlined.
 */
function sfv_dev_resolve( $content, $refs ) {
	return preg_replace_callback(
		'#<!-- wp:pattern \{"slug":"([^"]+)"\} /-->#',
		function ( $m ) use ( $refs ) {
			if ( isset( $refs[ $m[1] ] ) ) {
				return '<!-- wp:block {"ref":' . (int) $refs[ $m[1] ] . '} /-->';
			}
			return sfv_dev_resolve( sfv_dev_pattern( $m[1] ), $refs );
		},
		$content
	);
}

function sfv_dev_image_block( $att_id, $alt, $size = 'full' ) {
	return sprintf(
		"<!-- wp:image {\"id\":%d,\"sizeSlug\":\"%s\",\"linkDestination\":\"none\"} -->\n<figure class=\"wp-block-image size-%s\"><img src=\"%s\" alt=\"%s\" class=\"wp-image-%d\"/></figure>\n<!-- /wp:image -->\n\n",
		$att_id,
		$size,
		$size,
		esc_url( wp_get_attachment_image_url( $att_id, $size ) ),
		esc_attr( $alt ),
		$att_id
	);
}

/**
 * Swaps a theme default image in a cover block for a media library image.
 */
function sfv_dev_cover_image( $content, $att_id ) {
	if ( ! sfv_dev_is_image( $att_id ) ) {
		return $content;
	}

	$url = esc_url( wp_get_attachment_image_url( $att_id, 'full' ) );

	$content = preg_replace( '#<!-- wp:cover \{"url":"[^"]+",#', '<!-- wp:cover {"url":"' . $url . '","id":' . (int) $att_id . ',', $content, 1 );
	$content = preg_replace( '#<img class="wp-block-cover__image-background" alt="" src="[^"]+"#', '<img class="wp-block-cover__image-background wp-image-' . (int) $att_id . '" alt="" src="' . $url . '"', $content, 1 );

	return $content;
}

function sfv_dev_about_image( $content, $att_id ) {
	$url = esc_url( wp_get_attachment_image_url( $att_id, 'full' ) );

	return preg_replace(
		'#<!-- wp:image \{"sizeSlug":"full","className":"is-style-sfv-framed"\} -->\s*<figure class="wp-block-image size-full is-style-sfv-framed"><img src="[^"]+"#',
		'<!-- wp:image {"id":' . (int) $att_id . ',"sizeSlug":"full","className":"is-style-sfv-framed"} -->' . "\n" . '<figure class="wp-block-image size-full is-style-sfv-framed"><img src="' . $url . '" class="wp-image-' . (int) $att_id . '"',
		$content,
		1
	);
}

/**
 * Replaces what sits between $open_tag and the next $close with $blocks.
 */
function sfv_dev_fill( $content, $open_tag, $close, $blocks ) {
	$start = strpos( $content, $open_tag );
	$end   = false === $start ? false : strpos( $content, $close, $start );

	if ( false === $start || false === $end ) {
		return $content;
	}

	$start += strlen( $open_tag );

	return substr( $content, 0, $start ) . $blocks . substr( $content, $end );
}

// ---------------------------------------------------------------------------
// Media: alt text, and the About photo moved out of the theme folder
// ---------------------------------------------------------------------------

$sfv_sponsors     = array_filter( $sfv_sponsors, 'sfv_dev_is_image', ARRAY_FILTER_USE_KEY );
$sfv_event_photos = array_filter( $sfv_event_photos, 'sfv_dev_is_image', ARRAY_FILTER_USE_KEY );

foreach ( $sfv_sponsors + $sfv_event_photos as $att_id => $alt ) {
	update_post_meta( $att_id, '_wp_attachment_image_alt', $alt );
}
sfv_dev_log( sprintf( 'Set alt text on %d sponsor logos and %d event photos.', count( $sfv_sponsors ), count( $sfv_event_photos ) ) );

$about_alt = 'Young surfer standing on a surfboard with arms raised while riding a small wave.';
$about     = get_page_by_path( 'sfv-about-surfer', OBJECT, 'attachment' );

if ( ! $about ) {
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = wp_tempnam( 'sfv-about-surfer.webp' );
	copy( get_theme_file_path( 'assets/images/about-default.webp' ), $tmp );
	$about_id = media_handle_sideload(
		array( 'name' => 'sfv-about-surfer.webp', 'tmp_name' => $tmp ),
		0,
		'Young surfer riding a small wave',
		array( 'post_name' => 'sfv-about-surfer' )
	);

	if ( is_wp_error( $about_id ) ) {
		sfv_dev_fail( $about_id->get_error_message() );
	}
} else {
	$about_id = $about->ID;
}
update_post_meta( $about_id, '_wp_attachment_image_alt', $about_alt );

// ---------------------------------------------------------------------------
// Synced patterns
// ---------------------------------------------------------------------------

$refs = array(
	'sfv/event-details' => sfv_dev_upsert( 'wp_block', 'event-details', 'Event details', sfv_dev_pattern( 'sfv/event-details' ) ),
);

$sponsors = sfv_dev_pattern( 'sfv/sponsors' );
if ( $sfv_sponsors ) {
	$logos = '';
	foreach ( $sfv_sponsors as $att_id => $name ) {
		$logos .= sfv_dev_image_block( $att_id, $name );
	}
	$sponsors = sfv_dev_fill( $sponsors, '<div class="wp-block-group alignwide sfv-logo-grid">', '</div>', $logos );
}
$refs['sfv/sponsors'] = sfv_dev_upsert( 'wp_block', 'sponsors', 'Sponsors', $sponsors );

// Homepage seasons (see sfv-theme/inc/seasons.php).
sfv_dev_upsert( 'wp_block', 'season-registration', 'Season – registration open', sfv_dev_resolve( sfv_dev_pattern( 'sfv/register-band' ), $refs ) );

$thank_you = sfv_dev_pattern( 'sfv/season-thank-you' );
if ( $sfv_event_photos ) {
	$photos = '';
	foreach ( $sfv_event_photos as $att_id => $alt ) {
		$photos .= sfv_dev_image_block( $att_id, $alt, 'large' );
	}
	$thank_you = sfv_dev_fill( $thank_you, 'columns-3 is-cropped sfv-gallery">', '</figure>' . "\n" . '<!-- /wp:gallery -->', $photos );
}
sfv_dev_upsert( 'wp_block', 'season-thank-you', 'Season – thank you', $thank_you );

sfv_dev_upsert( 'wp_block', 'season-get-involved', 'Season – get involved', sfv_dev_pattern( 'sfv/season-get-involved' ) );

if ( ! get_option( SFV_SEASON_OPTION ) ) {
	update_option( SFV_SEASON_OPTION, 'registration' );
}

// ---------------------------------------------------------------------------
// Pages
// ---------------------------------------------------------------------------

$home = sfv_dev_resolve( sfv_dev_pattern( 'sfv/home' ), $refs );
$home = sfv_dev_cover_image( $home, SFV_HERO_ID );
$home = sfv_dev_about_image( $home, $about_id );

// The hero holds the H1, so Home uses the template without a banner.
$home_id = sfv_dev_upsert( 'page', 'home', 'Home', $home, array( 'page_template' => 'page-no-banner' ) );
update_post_meta( $home_id, '_yoast_wpseo_title', '%%sitename%% %%sep%% %%sitedesc%%' );

// Replaced by the homepage seasons.
$old = get_page_by_path( 'home-offseason', OBJECT, 'page' );
if ( $old ) {
	wp_trash_post( $old->ID );
	sfv_dev_log( 'Moved the old off-season homepage to the trash.' );
}

$register_id = sfv_dev_upsert(
	'page',
	'register',
	'Register for Surfing for Vision 2026!',
	sfv_dev_resolve( sfv_dev_pattern( 'sfv/register-page' ), $refs )
);
if ( sfv_dev_is_image( SFV_REGISTER_ID ) ) {
	set_post_thumbnail( $register_id, SFV_REGISTER_ID );
}

// Private page showing every section pattern, for reviewing designs (logged in only).
$library = '';
foreach ( array( 'sfv/announcement', 'sfv/photo-text', 'sfv/photo-text-right', 'sfv/gallery', 'sfv/faq', 'sfv/donate-band', 'sfv/season-thank-you', 'sfv/season-get-involved' ) as $slug ) {
	$library .= sfv_dev_resolve( sfv_dev_pattern( $slug ), $refs ) . "\n\n";
}
sfv_dev_upsert( 'page', 'pattern-library', 'Pattern library', $library, array( 'post_status' => 'private' ) );

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home_id );

sfv_dev_log( sprintf( 'Done. Front page is #%d, Register is #%d, season is "%s".', $home_id, $register_id, get_option( SFV_SEASON_OPTION ) ) );
