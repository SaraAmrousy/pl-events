<?php
/**
 * SEO from the data (Step 5.3) — works with Rank Math Free, and stands alone without it.
 *  - Event pages: title + meta description built from the event (name, city, year, status),
 *    unless an editor typed their own in Rank Math.
 *  - Schema: Event on every event page (dates, place, status, organiser, registration offer);
 *    Organization enriched with both offices, emails, phones and social profiles.
 * Everything comes from PL_Data, so it can never disagree with what the page shows.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_SEO {

	public static function init() {
		add_filter( 'rank_math/frontend/title', array( __CLASS__, 'rm_title' ), 20 );
		add_filter( 'rank_math/frontend/description', array( __CLASS__, 'rm_description' ), 20 );
		add_filter( 'rank_math/json_ld', array( __CLASS__, 'rm_json_ld' ), 99, 2 );
		add_action( 'wp_head', array( __CLASS__, 'standalone' ), 30 );
	}

	private static function event() {
		return is_singular( 'event' ) && class_exists( 'PL_Event_Page' ) ? PL_Event_Page::current() : null;
	}

	/* ---------------------------------------------------------- title + desc */

	public static function event_title( array $e ) {
		// "4th DEOSS Congress & 17th ATMO — Tunis 2027 | PL Events": name + city + year, never twice.
		$name     = $e['name'];
		$has_year = false !== strpos( $name, (string) $e['year'] );
		$has_city = $e['city'] && false !== stripos( $name, $e['city'] );
		$place    = $e['online'] ? 'Online' : ( $has_city ? '' : $e['city'] );
		$place    = trim( $place . ( $has_year ? '' : ' ' . $e['year'] ) );
		$t        = $place ? $name . ' — ' . $place : $name;
		if ( mb_strlen( $t ) > 62 ) { // long names: the name alone, still with the year if it has one
			$t = $name;
		}
		return mb_strlen( $t ) > 62 ? $t : $t . ' | PL Events'; // brand only where it fits (~65 chars shown)
	}

	public static function event_description( array $e ) {
		$when  = $e['range'];
		$where = trim( ( $e['venue'] ? $e['venue'] . ', ' : '' ) . $e['place'] );
		$state = 'past' === $e['status'] ? 'Held' : ( 'live' === $e['status'] ? 'Happening now' : '' );
		$reg   = $e['can_register'] ? ' Registration is open.' : ( 'upcoming' === $e['status'] ? ' Registration opens soon.' : '' );
		$parts = array(
			trim( ( $state ? $state . ' ' : '' ) . $when . ' in ' . $where . '.' ) . $reg,
			trim( $e['type'] . ' in ' . implode( ', ', (array) $e['specialties'] ) ) . ', organised by PL Events.',
		);
		$d = $e['name'] . ': ' . implode( ' ', $parts );
		if ( mb_strlen( $d ) > 155 ) { // keep whole sentences, never cut a word with "…"
			$d = $e['name'] . ': ' . $parts[0];
		}
		if ( mb_strlen( $d ) > 155 ) {
			$d = trim( ( $state ? $state . ' ' : '' ) . $when . ' in ' . $e['place'] . '.' ) . $reg . ' Organised by PL Events.';
		}
		return $d;
	}

	private static function has_manual( $key ) {
		$id = get_queried_object_id();
		return $id && '' !== trim( (string) get_post_meta( $id, $key, true ) );
	}

	public static function rm_title( $title ) {
		$e = self::event();
		return ( $e && ! self::has_manual( 'rank_math_title' ) ) ? self::event_title( $e ) : $title;
	}

	public static function rm_description( $desc ) {
		$e = self::event();
		return ( $e && ! self::has_manual( 'rank_math_description' ) ) ? self::event_description( $e ) : $desc;
	}

	/* ---------------------------------------------------------------- schema */

	public static function organization() {
		$c   = PL_Data::company();
		$org = array(
			'@type'  => 'Organization',
			'@id'    => home_url( '/#organization' ),
			'name'   => 'PL Events',
			'url'    => home_url( '/' ),
			'slogan' => $c['tagline'],
		);
		$logo = apply_filters( 'pl_brand_logo', '' );
		if ( $logo ) {
			$org['logo'] = array( '@type' => 'ImageObject', 'url' => $logo );
		}
		$same = array_values( array_filter( array_map( fn( $s ) => $s['url'], (array) $c['social'] ) ) );
		if ( $same ) {
			$org['sameAs'] = $same;
		}
		$addresses = array();
		$points    = array();
		foreach ( PL_Data::offices() as $o ) {
			$addresses[] = array(
				'@type'           => 'PostalAddress',
				'name'            => $o['role'] . ' — ' . $o['city'],
				'streetAddress'   => $o['address'],
				'addressLocality' => $o['city'],
				'addressCountry'  => $o['country'],
			);
			$p = array( '@type' => 'ContactPoint', 'contactType' => strtolower( $o['role'] ) . ' (' . $o['city'] . ')', 'areaServed' => $o['covers'] ? $o['covers'] : $o['country'] );
			if ( ! empty( $o['phones'][0]['number'] ) ) {
				$p['telephone'] = str_replace( ' ', '', $o['phones'][0]['number'] );
			}
			if ( ! empty( $o['emails'][0] ) ) {
				$p['email'] = $o['emails'][0];
			}
			$points[] = $p;
		}
		if ( $addresses ) {
			$org['address']      = count( $addresses ) > 1 ? $addresses : $addresses[0];
			$org['contactPoint'] = $points;
		}
		return apply_filters( 'pl_schema_organization', $org );
	}

	public static function event_schema( array $e ) {
		$s = array(
			'@type'               => 'Event',
			'@id'                 => $e['url'] . '#event',
			'name'                => $e['name'],
			'url'                 => $e['url'],
			'startDate'           => $e['start'],
			'endDate'             => $e['end'],
			'eventStatus'         => 'https://schema.org/EventScheduled',
			'eventAttendanceMode' => $e['online'] ? 'https://schema.org/OnlineEventAttendanceMode' : 'https://schema.org/OfflineEventAttendanceMode',
			'organizer'           => array( '@id' => home_url( '/#organization' ) ),
			'description'         => self::event_description( $e ),
		);
		if ( $e['online'] ) {
			$s['location'] = array( '@type' => 'VirtualLocation', 'url' => $e['website'] ? $e['website'] : $e['url'] );
		} else {
			$s['location'] = array(
				'@type'   => 'Place',
				'name'    => $e['venue'] ? $e['venue'] : $e['city'],
				'address' => array( '@type' => 'PostalAddress', 'addressLocality' => $e['city'], 'addressCountry' => $e['country'] ),
			);
		}
		if ( $e['poster'] ) {
			$s['image'] = array( $e['poster'] );
		}
		if ( $e['website'] ) {
			$s['sameAs'] = $e['website'];
		}
		if ( $e['registration_url'] && 'past' !== $e['status'] ) {
			$s['offers'] = array(
				'@type'        => 'Offer',
				'url'          => $e['registration_url'],
				'availability' => $e['can_register'] ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder',
			);
		}
		return apply_filters( 'pl_schema_event', $s, $e );
	}

	/** Rank Math: replace its Organization with ours, add the Event on event pages. */
	public static function rm_json_ld( $data, $jsonld = null ) {
		$org = self::organization();
		$found = false;
		foreach ( $data as $k => $node ) {
			if ( is_array( $node ) && isset( $node['@type'] ) && in_array( $node['@type'], array( 'Organization', array( 'Person', 'Organization' ) ), true ) ) {
				$data[ $k ] = array_merge( $node, $org );
				$found      = true;
			}
		}
		if ( ! $found ) {
			$data['PLOrganization'] = $org;
		}
		$e = self::event();
		if ( $e ) {
			$data['PLEvent'] = self::event_schema( $e );
		}
		return $data;
	}

	/** Without Rank Math: print the same schema ourselves. */
	public static function standalone() {
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			return;
		}
		$graph = array( self::organization() );
		$e     = self::event();
		if ( $e ) {
			$graph[] = self::event_schema( $e );
		}
		printf( '<script type="application/ld+json">%s</script>' . "\n", wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	}
}
