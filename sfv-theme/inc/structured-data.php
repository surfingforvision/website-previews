<?php
/**
 * Structured data (schema.org JSON-LD) for Google and AI assistants.
 *
 * - Organization: the nonprofit's legal name, EIN and 501(c)(3) status, added to
 *   Yoast's own Organization entry so search engines see one organisation, not
 *   two. These were added to pass Google for Nonprofits verification; keep them.
 * - Event: the next event, from Pages → Event settings, on the homepage and the
 *   Register page, only while it's still upcoming.
 * - FAQPage: built from any page's Details (question/answer) blocks.
 *
 * With Yoast SEO active everything joins Yoast's @graph; without it the theme
 * prints the same data itself.
 *
 * @package SFV_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sfv_schema_org_id() {
	return home_url( '/#organization' );
}

/**
 * What we know about the organisation that Yoast doesn't.
 */
function sfv_schema_org_details() {
	return array(
		'legalName'       => 'Surfing for Vision a NJ Nonprofit Corporation',
		'description'     => 'Surfing for Vision is a 501(c)(3) nonprofit organization dedicated to providing a fun, safe surfing experience for the blind and visually impaired community.',
		'email'           => 'info@surfingforvision.com',
		'taxID'           => '39-3423583',
		'nonprofitStatus' => 'https://schema.org/Nonprofit501c3',
		// Region only: the registered street address is a volunteer's home.
		'address'         => array(
			'@type'          => 'PostalAddress',
			'addressRegion'  => 'NJ',
			'addressCountry' => 'US',
		),
		'sameAs'          => array( 'https://www.instagram.com/surfingforvision/' ),
	);
}

/**
 * The next event, or null when there's no date or it has already ended.
 */
function sfv_schema_event() {
	$event = sfv_event();
	$start = sfv_event_datetime( $event['start'] );
	$end   = sfv_event_datetime( $event['end'] );

	if ( ! $start || ! $end || $end < new DateTimeImmutable( 'now', sfv_event_timezone() ) ) {
		return null;
	}

	$register     = get_page_by_path( 'register' );
	$register_url = $register ? get_permalink( $register ) : home_url( '/register/' );
	$image        = $register ? get_the_post_thumbnail_url( $register, 'full' ) : '';
	$free         = '0' === (string) $event['cost'];
	$door         = sfv_event_datetime( $event['checkin_start'] );

	$schema = array(
		'@type'               => 'Event',
		'@id'                 => home_url( '/#event-' . $event['date'] ),
		'name'                => 'Surfing for Vision ' . $start->format( 'Y' ),
		// The live site's wording (legacy/koinonia/inc/sfv-seo.php).
		'description'         => 'A free annual surfing event for the blind and visually impaired community. Expert surfers, water-certified safety personnel, and lifeguards on hand. Surfboards and life jackets provided.',
		'startDate'           => $start->format( 'c' ),
		'endDate'             => $end->format( 'c' ),
		'eventStatus'         => 'https://schema.org/EventScheduled',
		'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
		'isAccessibleForFree' => $free,
		'location'            => array(
			'@type'   => 'Place',
			'name'    => $event['place'],
			'address' => array(
				'@type'           => 'PostalAddress',
				'addressLocality' => $event['locality'],
				'addressRegion'   => $event['region'],
				'addressCountry'  => 'US',
			),
		),
		'organizer'           => array( '@id' => sfv_schema_org_id() ),
		'offers'              => array(
			'@type'         => 'Offer',
			'price'         => '' === (string) $event['cost'] ? '0' : (string) $event['cost'],
			'priceCurrency' => 'USD',
			'url'           => $register_url,
			'availability'  => 'registration' === get_option( SFV_SEASON_OPTION, 'registration' ) ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder',
		),
		'url'                 => $register_url,
	);

	if ( $door ) {
		$schema['doorTime'] = $door->format( 'c' );
	}

	if ( $image ) {
		$schema['image'] = $image;
	}

	return $schema;
}

/**
 * Question/answer pairs from the current page's Details blocks.
 */
function sfv_schema_faq() {
	if ( ! is_singular() ) {
		return null;
	}

	$questions = array();
	$walk      = function ( $blocks ) use ( &$walk, &$questions ) {
		foreach ( $blocks as $block ) {
			if ( 'core/details' === $block['blockName'] && preg_match( '#<summary[^>]*>(.*?)</summary>(.*)#s', render_block( $block ), $m ) ) {
				$question = trim( wp_strip_all_tags( $m[1] ) );
				$answer   = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $m[2] ) ) );

				if ( '' !== $question && '' !== $answer ) {
					$questions[] = array(
						'@type'          => 'Question',
						'name'           => $question,
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => $answer,
						),
					);
				}
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$walk( $block['innerBlocks'] );
			}
		}
	};
	$walk( parse_blocks( get_post_field( 'post_content', get_queried_object_id() ) ) );

	if ( ! $questions ) {
		return null;
	}

	return array(
		'@type'      => 'FAQPage',
		'@id'        => get_permalink( get_queried_object_id() ) . '#faq',
		'mainEntity' => $questions,
	);
}

function sfv_schema_page_shows_event() {
	return is_front_page() || is_page( 'register' );
}

// ---------------------------------------------------------------------------
// With Yoast SEO: join its graph.
// ---------------------------------------------------------------------------

function sfv_schema_yoast_organization( $data ) {
	$details = sfv_schema_org_details();

	$data['@type']  = array_values( array_unique( array_merge( (array) ( $data['@type'] ?? 'Organization' ), array( 'Organization', 'NGO' ) ) ) );
	$data['sameAs'] = array_values( array_unique( array_merge( (array) ( $data['sameAs'] ?? array() ), $details['sameAs'] ) ) );
	unset( $details['sameAs'] );

	return array_merge( $details, $data, array( '@type' => $data['@type'], 'sameAs' => $data['sameAs'] ) );
}
add_filter( 'wpseo_schema_organization', 'sfv_schema_yoast_organization' );

function sfv_schema_yoast_graph( $graph ) {
	if ( sfv_schema_page_shows_event() ) {
		$event = sfv_schema_event();
		if ( $event ) {
			$graph[] = $event;
		}
	}

	$faq = sfv_schema_faq();
	if ( $faq ) {
		$graph[] = $faq;
	}

	return $graph;
}
add_filter( 'wpseo_schema_graph', 'sfv_schema_yoast_graph' );

// ---------------------------------------------------------------------------
// Without Yoast SEO: print the same data ourselves.
// ---------------------------------------------------------------------------

function sfv_schema_fallback() {
	if ( defined( 'WPSEO_VERSION' ) ) {
		return;
	}

	$graph = array();

	if ( is_front_page() ) {
		$graph[] = array_merge(
			array(
				'@type' => array( 'Organization', 'NGO' ),
				'@id'   => sfv_schema_org_id(),
				'name'  => 'Surfing for Vision',
				'url'   => home_url( '/' ),
				'logo'  => get_theme_file_uri( 'assets/images/logo-stacked.png' ),
			),
			sfv_schema_org_details()
		);
	}

	$graph = sfv_schema_yoast_graph( $graph );

	if ( $graph ) {
		echo '<script type="application/ld+json">'
			. wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
			. '</script>' . "\n";
	}
}
add_action( 'wp_head', 'sfv_schema_fallback' );
