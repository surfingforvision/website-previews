<?php
/**
 * Builds the site's content from the theme's patterns: the shared sections
 * (synced patterns), the Home, Register, FAQ and Photos pages, alt text on
 * sponsor logos and event photos, and the homepage setting.
 *
 * Two modes:
 * - 'create' (Tools → Set up site content, used once at cutover): only adds what
 *   is missing and never changes anything that exists, so pressing it again is
 *   harmless. New example pages (FAQ, Photos) are drafts and Footer links starts
 *   empty, so nothing new goes public until an Editor publishes it.
 * - 'update' (dev/build-pages.php, locally and in PR previews): rebuilds
 *   everything from the current patterns and publishes the example pages.
 *
 * The sponsor and photo lists below are seed data for this one-time build, not
 * what the site renders: after the build, sponsors and photos are content that
 * Editors change in wp-admin.
 *
 * @package SFV_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SFV_Content_Builder {

	const HERO_ID     = 71; // Figma hero photo.
	const REGISTER_ID = 52; // Chloe and friend surfing.

	/**
	 * Sponsor logos in display order, with the alt text a screen reader announces.
	 */
	const SPONSORS = array(
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
	 * 2026 event photos, with alt text describing each.
	 */
	const EVENT_PHOTOS = array(
		53 => 'A young surfer in a life jacket stands in shallow water beside a surfboard, surrounded by volunteers in matching shirts.',
		57 => 'Volunteers in the water gather around a surfer lying on a board, ready to push into a wave.',
		59 => 'Volunteers walk a surfer wearing a life jacket out of the ocean after a ride.',
		60 => 'Three smiling participants sit under a tent, giving a thumbs up and a shaka.',
		62 => 'A surfer in a red life jacket wades out of the water arm in arm with a volunteer.',
		63 => 'Two smiling people pose together on the beach, one wearing a life jacket.',
	);

	const ABOUT_ALT = 'Young surfer standing on a surfboard with arms raised while riding a small wave.';

	/**
	 * Search-result descriptions (Yoast), set only where a page has none. Home and
	 * Register keep the live site's wording; FAQ and Photos are new pages (drafts).
	 */
	const DESCRIPTIONS = array(
		'home'     => 'Sharing the Stoke with the BVI',
		'register' => 'Register for the 2026 Surfing for Vision event on August 22 in Long Branch, NJ. Free registration is open to blind and visually impaired surfers, volunteers, and family guests.',
		'faq'      => 'Who can surf, what it costs, what to bring and where to go: answers about Surfing for Vision, a free adaptive surfing day in Long Branch, NJ.',
		'photos'   => 'Photos from Surfing for Vision, a free adaptive surfing day for people who are blind or visually impaired on the beach in Long Branch, NJ.',
	);

	/** @var string 'create' or 'update'. */
	private $mode;

	/** @var string[] */
	private $log = array();

	public function __construct( $mode = 'create' ) {
		$this->mode = 'update' === $mode ? 'update' : 'create';
	}

	/**
	 * Runs the build and returns what it did, one line per item.
	 *
	 * @return string[]
	 * @throws RuntimeException When a pattern file is missing or WordPress refuses a write.
	 */
	public function run() {
		$sponsors = array_filter( self::SPONSORS, array( $this, 'is_image' ), ARRAY_FILTER_USE_KEY );
		$photos   = array_filter( self::EVENT_PHOTOS, array( $this, 'is_image' ), ARRAY_FILTER_USE_KEY );

		foreach ( $sponsors + $photos as $att_id => $alt ) {
			$this->set_alt( $att_id, $alt );
		}
		$this->log( sprintf( 'Alt text checked on %d sponsor logos and %d event photos.', count( $sponsors ), count( $photos ) ) );

		$about_id = $this->about_photo();

		// Event details come from Pages → Event settings (inc/event.php).
		if ( ! get_option( SFV_EVENT_OPTION ) ) {
			update_option( SFV_EVENT_OPTION, sfv_event_defaults() );
			$this->log( 'Saved the event settings (date, times, place).' );
		}

		// Shared sections.
		$refs = array();

		$sponsor_section = $this->pattern( 'sfv/sponsors' );
		if ( $sponsors ) {
			$logos = '';
			foreach ( $sponsors as $att_id => $name ) {
				$logos .= $this->image_block( $att_id, $name );
			}
			$sponsor_section = $this->fill( $sponsor_section, '<div class="wp-block-group alignwide sfv-logo-grid">', '</div>', $logos );
		}
		$refs['sfv/sponsors'] = $this->upsert( 'wp_block', 'sponsors', 'Sponsors', $sponsor_section );

		// Homepage seasons (inc/seasons.php).
		$this->upsert( 'wp_block', 'season-registration', 'Season – registration open', $this->resolve( $this->pattern( 'sfv/register-band' ), $refs ) );
		$this->upsert( 'wp_block', 'season-thank-you', 'Season – thank you', $this->with_photos( $this->pattern( 'sfv/season-thank-you' ), $photos ) );
		$this->upsert( 'wp_block', 'season-get-involved', 'Season – get involved', $this->pattern( 'sfv/season-get-involved' ) );

		if ( ! get_option( SFV_SEASON_OPTION ) ) {
			update_option( SFV_SEASON_OPTION, 'registration' );
		}

		// Pages.
		$home = $this->resolve( $this->pattern( 'sfv/home' ), $refs );
		$home = $this->cover_image( $home, self::HERO_ID );
		$home = $this->about_image( $home, $about_id );

		// The hero holds the H1, so Home uses the template without a banner.
		$home_id = $this->upsert( 'page', 'home', 'Home', $home, array( 'page_template' => 'page-no-banner' ) );
		if ( ! get_post_meta( $home_id, '_yoast_wpseo_title', true ) ) {
			update_post_meta( $home_id, '_yoast_wpseo_title', '%%sitename%% %%sep%% %%sitedesc%%' );
		}

		$register_id = $this->upsert( 'page', 'register', 'Register for Surfing for Vision 2026!', $this->resolve( $this->pattern( 'sfv/register-page' ), $refs ) );
		$this->set_thumbnail( $register_id, self::REGISTER_ID );

		// Example pages: drafts on the live site, published locally and in previews.
		$example_status = 'update' === $this->mode ? 'publish' : 'draft';
		$faq_id         = $this->upsert( 'page', 'faq', 'FAQ', $this->pattern( 'sfv/faq' ), array( 'post_status' => $example_status ) );
		$photos_id      = $this->upsert( 'page', 'photos', 'Photos', $this->with_photos( $this->pattern( 'sfv/gallery' ), $photos ), array( 'post_status' => $example_status ) );
		$this->set_thumbnail( $faq_id, self::HERO_ID );
		$this->set_thumbnail( $photos_id, self::REGISTER_ID );

		$this->upsert( 'wp_block', SFV_FOOTER_LINKS_SLUG, 'Footer links', $this->footer_links( 'publish' === $example_status ? array( $faq_id, $photos_id ) : array() ) );

		// Private page showing every section pattern, for reviewing designs.
		$library = '';
		foreach ( array( 'sfv/announcement', 'sfv/photo-text', 'sfv/photo-text-right', 'sfv/gallery', 'sfv/faq', 'sfv/donate-band', 'sfv/season-thank-you', 'sfv/season-get-involved' ) as $slug ) {
			$library .= $this->resolve( $this->pattern( $slug ), $refs ) . "\n\n";
		}
		$this->upsert( 'page', 'pattern-library', 'Pattern library', $library, array( 'post_status' => 'private' ) );

		foreach ( array( 'home' => $home_id, 'register' => $register_id, 'faq' => $faq_id, 'photos' => $photos_id ) as $slug => $page_id ) {
			if ( 'update' === $this->mode || ! get_post_meta( $page_id, '_yoast_wpseo_metadesc', true ) ) {
				update_post_meta( $page_id, '_yoast_wpseo_metadesc', self::DESCRIPTIONS[ $slug ] );
			}
		}

		if ( 'update' === $this->mode ) {
			// Replaced by Pages → Event settings.
			$old_event = get_page_by_path( 'event-details', OBJECT, 'wp_block' );
			if ( $old_event && 'trash' !== $old_event->post_status ) {
				wp_trash_post( $old_event->ID );
				$this->log( 'Moved the old "Event details" shared section to the trash.' );
			}

			// Replaced by the homepage seasons.
			$old = get_page_by_path( 'home-offseason', OBJECT, 'page' );
			if ( $old && 'trash' !== $old->post_status ) {
				wp_trash_post( $old->ID );
				$this->log( 'Moved the old off-season homepage to the trash.' );
			}
		}

		if ( 'update' === $this->mode || 'page' !== get_option( 'show_on_front' ) || ! get_option( 'page_on_front' ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $home_id );
			$this->log( sprintf( 'Homepage set to "Home" (#%d).', $home_id ) );
		}

		$this->log( sprintf( 'Done. Homepage season: "%s".', get_option( SFV_SEASON_OPTION ) ) );

		return $this->log;
	}

	private function log( $message ) {
		$this->log[] = $message;
	}

	public function is_image( $att_id ) {
		return 'attachment' === get_post_type( $att_id ) && wp_attachment_is_image( $att_id );
	}

	/**
	 * In create mode, never replaces alt text someone has written.
	 */
	private function set_alt( $att_id, $alt ) {
		if ( 'create' === $this->mode && '' !== trim( (string) get_post_meta( $att_id, '_wp_attachment_image_alt', true ) ) ) {
			return;
		}

		update_post_meta( $att_id, '_wp_attachment_image_alt', $alt );
	}

	private function set_thumbnail( $post_id, $att_id ) {
		if ( $this->is_image( $att_id ) && ( 'update' === $this->mode || ! has_post_thumbnail( $post_id ) ) ) {
			set_post_thumbnail( $post_id, $att_id );
		}
	}

	/**
	 * The About photo, moved out of the theme folder into the media library.
	 */
	private function about_photo() {
		$about = get_page_by_path( 'sfv-about-surfer', OBJECT, 'attachment' );

		if ( $about ) {
			$this->set_alt( $about->ID, self::ABOUT_ALT );
			return $about->ID;
		}

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
			throw new RuntimeException( 'Could not add the About photo: ' . $about_id->get_error_message() );
		}

		update_post_meta( $about_id, '_wp_attachment_image_alt', self::ABOUT_ALT );
		$this->log( 'Added the About photo to the media library.' );

		return $about_id;
	}

	/**
	 * Creates the post, or in update mode rewrites it; in create mode an existing
	 * post is left exactly as it is.
	 */
	private function upsert( $post_type, $slug, $title, $content, $extra = array() ) {
		$existing = get_page_by_path( $slug, OBJECT, $post_type );

		if ( $existing && 'create' === $this->mode ) {
			$this->log( sprintf( 'Kept existing %s "%s" (#%d).', $this->type_label( $post_type ), $existing->post_title, $existing->ID ) );
			return (int) $existing->ID;
		}

		$postarr = array_merge(
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
			throw new RuntimeException( sprintf( 'Could not save "%s": %s', $title, $id->get_error_message() ) );
		}

		$this->log( sprintf( '%s %s "%s" (#%d)%s.', $existing ? 'Updated' : 'Created', $this->type_label( $post_type ), $title, $id, 'draft' === $postarr['post_status'] ? ' as a draft' : '' ) );

		return (int) $id;
	}

	private function type_label( $post_type ) {
		return 'wp_block' === $post_type ? 'shared section' : 'page';
	}

	/**
	 * Renders a theme pattern file (sfv/hero → patterns/hero.php) ourselves, so
	 * nested pattern references are left for resolve() to turn into shared
	 * section references.
	 */
	private function pattern( $slug ) {
		$path = get_theme_file_path( 'patterns/' . substr( $slug, strlen( 'sfv/' ) ) . '.php' );

		if ( ! str_starts_with( $slug, 'sfv/' ) || ! is_file( $path ) ) {
			throw new RuntimeException( "No pattern file for {$slug}." );
		}

		ob_start();
		include $path;
		return trim( ob_get_clean() );
	}

	/**
	 * Expands nested pattern references: shared ones become references to the
	 * shared section, the rest are inlined.
	 */
	private function resolve( $content, $refs ) {
		return preg_replace_callback(
			'#<!-- wp:pattern \{"slug":"([^"]+)"\} /-->#',
			function ( $m ) use ( $refs ) {
				if ( isset( $refs[ $m[1] ] ) ) {
					return '<!-- wp:block {"ref":' . (int) $refs[ $m[1] ] . '} /-->';
				}
				return $this->resolve( $this->pattern( $m[1] ), $refs );
			},
			$content
		);
	}

	private function image_block( $att_id, $alt, $size = 'full' ) {
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
	 * Swaps the theme's sample photos in a gallery for the event photos.
	 */
	private function with_photos( $content, $photos ) {
		if ( ! $photos ) {
			return $content;
		}

		$blocks = '';
		foreach ( $photos as $att_id => $alt ) {
			$blocks .= $this->image_block( $att_id, $alt, 'large' );
		}

		return $this->fill( $content, 'is-cropped sfv-gallery">', '</figure>' . "\n" . '<!-- /wp:gallery -->', $blocks );
	}

	/**
	 * Swaps the theme's sample image in a cover block for a media library image.
	 */
	private function cover_image( $content, $att_id ) {
		if ( ! $this->is_image( $att_id ) ) {
			return $content;
		}

		$url = esc_url( wp_get_attachment_image_url( $att_id, 'full' ) );

		$content = preg_replace( '#<!-- wp:cover \{"url":"[^"]+",#', '<!-- wp:cover {"url":"' . $url . '","id":' . (int) $att_id . ',', $content, 1 );
		$content = preg_replace( '#<img class="wp-block-cover__image-background" alt="" src="[^"]+"#', '<img class="wp-block-cover__image-background wp-image-' . (int) $att_id . '" alt="" src="' . $url . '"', $content, 1 );

		return $content;
	}

	private function about_image( $content, $att_id ) {
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
	private function fill( $content, $open_tag, $close, $blocks ) {
		$start = strpos( $content, $open_tag );
		$end   = false === $start ? false : strpos( $content, $close, $start );

		if ( false === $start || false === $end ) {
			return $content;
		}

		$start += strlen( $open_tag );

		return substr( $content, 0, $start ) . $blocks . substr( $content, $end );
	}

	/**
	 * The Footer links shared section: an instruction and a list of links.
	 */
	private function footer_links( $page_ids ) {
		$items = '';
		foreach ( $page_ids as $page_id ) {
			$items .= sprintf(
				"<!-- wp:list-item -->\n<li><a href=\"%s\">%s</a></li>\n<!-- /wp:list-item -->\n",
				esc_url( wp_make_link_relative( get_permalink( $page_id ) ) ),
				esc_html( get_the_title( $page_id ) )
			);
		}

		return "<!-- wp:paragraph -->\n<p>Each link below appears in the site footer, after Register, About, Sponsors and Donate. To add a page, add a line, type its name, select the text and use the link button. Only published pages should be linked.</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:list -->\n<ul class=\"wp-block-list\">" . $items . "</ul>\n<!-- /wp:list -->";
	}
}

/**
 * @param string $mode 'create' (only add what's missing) or 'update' (rebuild).
 * @return string[] What was done, one line per item.
 */
function sfv_build_site_content( $mode = 'create' ) {
	return ( new SFV_Content_Builder( $mode ) )->run();
}
