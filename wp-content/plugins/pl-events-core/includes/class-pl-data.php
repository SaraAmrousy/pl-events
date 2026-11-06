<?php
/**
 * The one data layer (Step 2.2 §8). Every shortcode, template and the JS read from here;
 * nothing else queries the database. PL_Data::all() returns the shape of data/pl-data.json.
 *
 * Stored values are cached (PL_Cache); time-dependent values (status, badges, countdown)
 * are computed on every call from the event country's local time, so they can never go stale.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Data {

	const SCHEMA = 'pl-events-core/1';

	/* ----------------------------------------------------------- raw content */

	private static function module_items( $type, $include_hidden = false ) {
		$m = PL_Plugin::module( $type );
		if ( ! $m ) {
			return array();
		}
		return PL_Cache::remember( 'items:' . $type . ':' . (int) $include_hidden, fn() => $m->items( $include_hidden ) );
	}

	/** Events as stored (+ map point, which never changes with time), sorted by start date. */
	public static function raw_events( $include_hidden = false ) {
		return PL_Cache::remember(
			'raw_events:' . (int) $include_hidden,
			static function () use ( $include_hidden ) {
				$list = PL_Plugin::module( 'event' )->items( $include_hidden );
				foreach ( $list as &$e ) {
					$flags        = PL_Taxonomies::type_flags( $e['type'] );
					$e['online']   = $flags['online'];
					$e['congress'] = $flags['congress'];
					if ( $e['online'] || ! $e['country'] ) {
						$e['lonlat']    = null;
						$e['on_centre'] = false;
					} else {
						list( $lon, $lat, $matched ) = PL_Geo::locate( $e['city'], $e['country'] );
						$e['lonlat']    = array( $lon, $lat );
						$e['on_centre'] = ! $matched;
					}
					$e['series_short'] = PL_Taxonomies::series_short( $e['series'] );
				}
				unset( $e );
				$list = array_values( array_filter( $list, fn( $e ) => (bool) $e['start'] ) );
				usort( $list, fn( $a, $b ) => strcmp( $a['start'], $b['start'] ) ?: strcmp( $a['name'], $b['name'] ) );
				return $list;
			}
		);
	}

	/* -------------------------------------------------------------- events */

	/** Computed fields (the prototype's model(), Step 3.4b §1). */
	public static function compute( array $e ) {
		$tz        = $e['online'] || ! $e['country'] ? 'Africa/Cairo' : PL_Geo::tz( $e['country'] );
		$today     = PL_Dates::local_today( $tz );
		$st        = PL_Dates::status( $e['start'], $e['end'], $tz );
		$p         = PL_Dates::parts( $e['start'] );
		$can_reg   = 'past' !== $st && 'open' === $e['registration'] && PL_Module_Events::is_web_link( $e['registration_url'] );
		$cname     = $e['country'] ? PL_Geo::name( $e['country'] ) : '';
		$tile      = PL_Dates::tile( $e['start'], $e['end'] );
		if ( 'live' === $st ) {
			$badge = array( 'Live now', 'b-live' );
		} elseif ( 'past' === $st ) {
			$badge = array( 'Held ' . PL_Dates::month_short( $e['start'] ), 'b-past' );
		} elseif ( $can_reg ) {
			$badge = array( 'Registration open', 'b-open' );
		} else {
			$badge = array( 'Registration opens soon', 'b-soon' );
		}
		$series = $e['series'] ? $e['series'] : '';
		$word   = $series ? $series : implode( ' ', array_slice( explode( ' ', $e['name'] ), 0, 2 ) );
		if ( strlen( $word ) > 12 ) {
			$word = preg_replace( '/([a-z])([A-Z])/', '$1 $2', $word );
		}
		$place = $e['online'] ? 'Online' : trim( $e['city'] . ( $cname ? ', ' . $cname : '' ), ', ' );
		$c     = array(
			'status'         => $st,
			'tz'             => $tz,
			'today'          => $today,
			'year'           => $p['y'],
			'month_short'    => PL_Dates::month_short( $e['start'] ),
			'range'          => PL_Dates::range( $e['start'], $e['end'] ),
			'tile'           => $tile,
			'country_name'   => $cname,
			'place'          => $place,
			'can_register'   => $can_reg,
			'badge'          => $badge,
			'register_label' => $series ? 'Register for ' . $e['series_short'] . ' ' . $p['y'] : 'Register',
			'spec_line'      => trim( $e['type'] . ' · ' . implode( ', ', (array) $e['specialties'] ), ' ·' ),
			'live_day'       => 'live' === $st ? 'day ' . ( PL_Dates::days_between( $e['start'], $today ) + 1 ) . ' of ' . ( PL_Dates::days_between( $e['start'], $e['end'] ) + 1 ) : '',
			'days_to_start'  => PL_Dates::days_between( $today, $e['start'] ),
			'poster_word'    => $word,
			'edition_text'   => ( (string) $p['y'] === trim( (string) $e['edition'] ) ) ? '' : $e['edition'], // display only: "2025" for a 2025 event is not an edition
			'ics_url'        => trailingslashit( $e['url'] ) . 'ics/',
			'gcal_url'       => self::gcal_url( $e ),
			'outlook_url'    => self::outlook_url( $e ),
			'ics_name'       => sanitize_file_name( strtolower( $series ? sanitize_title( $e['series_short'] ) . '-' . $p['y'] : $e['id'] ) ) . '.ics',
			'register_aria'  => 'Register now for ' . ( $series ? $e['series_short'] . ' ' . $p['y'] : $e['name'] ),
			'short_label'    => $series ? $e['series_short'] . ' ' . $p['y'] : $e['name'],
			'wa_url'         => 'https://wa.me/?text=' . rawurlencode( $e['name'] . ' · ' . PL_Dates::range( $e['start'], $e['end'] ) . ' · ' . $place . ' — ' . $e['url'] ),
		);
		return apply_filters( 'pl_event_data', array_merge( $e, $c ) );
	}

	/** Outlook.com / Microsoft 365 "add event" deeplink (all-day; end date is exclusive). */
	private static function outlook_url( array $e ) {
		$end = gmdate( 'Y-m-d', strtotime( $e['end'] . ' 00:00:00 UTC' ) + DAY_IN_SECONDS );
		$loc = trim( $e['venue'] . ', ' . $e['city'] . ', ' . ( $e['country'] ? PL_Geo::name( $e['country'] ) : '' ), ', ' );
		return 'https://outlook.live.com/calendar/0/action/compose?' . http_build_query(
			array(
				'path'     => '/calendar/action/compose',
				'rru'      => 'addevent',
				'allday'   => 'true',
				'startdt'  => $e['start'],
				'enddt'    => $end,
				'subject'  => $e['name'],
				'location' => $loc,
				'body'     => ( $e['website'] ? $e['website'] . ' — ' : '' ) . 'organised by PL Events',
			),
			'',
			'&',
			PHP_QUERY_RFC3986
		);
	}

	private static function gcal_url( array $e ) {
		$end = gmdate( 'Ymd', strtotime( $e['end'] . ' 00:00:00 UTC' ) + DAY_IN_SECONDS );
		$loc = trim( $e['venue'] . ', ' . $e['city'] . ', ' . ( $e['country'] ? PL_Geo::name( $e['country'] ) : '' ), ', ' );
		return 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . rawurlencode( $e['name'] ) . '&dates=' . str_replace( '-', '', $e['start'] ) . '/' . $end . '&location=' . rawurlencode( $loc ) . '&details=' . rawurlencode( ( $e['website'] ? $e['website'] . ' — ' : '' ) . 'organised by PL Events' );
	}

	/**
	 * Events with computed fields.
	 *
	 * @param array $args status (all|live|upcoming|past|current) · type · specialty · country · year · q · limit · include_hidden · order (natural|start)
	 */
	public static function events( array $args = array() ) {
		$a = wp_parse_args( $args, array( 'status' => 'all', 'type' => '', 'specialty' => '', 'country' => '', 'year' => '', 'q' => '', 'limit' => 0, 'include_hidden' => false, 'order' => 'start' ) );
		$list = array_map( array( __CLASS__, 'compute' ), self::raw_events( $a['include_hidden'] ) );
		if ( 'natural' === $a['order'] ) {
			$list = self::ordered( $list );
		}
		$list = array_values( array_filter( $list, fn( $e ) => self::matches( $e, $a ) ) );
		if ( $a['limit'] > 0 ) {
			$list = array_slice( $list, 0, (int) $a['limit'] );
		}
		return $list;
	}

	public static function matches( array $e, array $a ) {
		$csv = fn( $v ) => array_filter( array_map( 'trim', is_array( $v ) ? $v : explode( ',', (string) $v ) ) );
		if ( 'all' !== $a['status'] && $a['status'] ) {
			$want = 'current' === $a['status'] ? array( 'live', 'upcoming' ) : $csv( $a['status'] );
			if ( ! in_array( $e['status'], $want, true ) ) {
				return false;
			}
		}
		if ( $a['type'] && ! in_array( strtolower( $e['type'] ), array_map( 'strtolower', $csv( $a['type'] ) ), true ) ) {
			return false;
		}
		if ( $a['specialty'] && ! array_intersect( array_map( 'strtolower', (array) $e['specialties'] ), array_map( 'strtolower', $csv( $a['specialty'] ) ) ) ) {
			return false;
		}
		if ( $a['country'] && ! in_array( $e['country'], array_map( 'strtoupper', $csv( $a['country'] ) ), true ) ) {
			return false;
		}
		if ( $a['year'] && ! in_array( (string) $e['year'], $csv( $a['year'] ), true ) ) {
			return false;
		}
		if ( '' !== trim( (string) $a['q'] ) ) {
			$hay = strtolower( $e['name'] . ' ' . $e['city'] . ' ' . $e['country_name'] . ' ' . $e['series'] . ' ' . $e['venue'] );
			if ( false === strpos( $hay, strtolower( trim( $a['q'] ) ) ) ) {
				return false;
			}
		}
		return true;
	}

	/** Live → Upcoming (soonest first) → Past (newest first) — Step 3.4a H1. */
	public static function ordered( array $list ) {
		$by = fn( $s ) => array_values( array_filter( $list, fn( $e ) => $e['status'] === $s ) );
		return array_merge( $by( 'live' ), $by( 'upcoming' ), array_reverse( $by( 'past' ) ) );
	}

	public static function event_by_id( $post_id, $include_hidden = true ) {
		foreach ( self::events( array( 'include_hidden' => $include_hidden ) ) as $e ) {
			if ( (int) $e['post_id'] === (int) $post_id ) {
				return $e;
			}
		}
		return null;
	}

	public static function event_by_slug( $slug ) {
		foreach ( self::events() as $e ) {
			if ( $e['id'] === $slug ) {
				return $e;
			}
		}
		return null;
	}

	/** Home hero event: nearest pinned upcoming/live; else Live; else nearest upcoming. */
	public static function hero_event() {
		$cur    = self::events( array( 'status' => 'current' ) );
		$pinned = array_values( array_filter( $cur, fn( $e ) => $e['pin_home'] ) );
		if ( $pinned ) {
			return $pinned[0];
		}
		foreach ( $cur as $e ) {
			if ( 'live' === $e['status'] ) {
				return $e;
			}
		}
		return $cur[0] ?? null;
	}

	/* -------------------------------------------------------------- numbers */

	/** Site-wide numbers (Step 2.2 §4). Filter: pl_stats_items. */
	public static function stats( ?array $events = null ) {
		$ev        = null === $events ? self::events() : $events;
		$on_map    = array_filter( $ev, fn( $e ) => ! $e['online'] && $e['country'] );
		$countries = array_values( array_unique( array_map( fn( $e ) => $e['country'], $on_map ) ) );
		$cities    = array_values( array_unique( array_map( fn( $e ) => $e['city'] . '|' . $e['country'], $on_map ) ) );
		$specs     = array_values( array_unique( array_merge( array(), ...array_map( fn( $e ) => (array) $e['specialties'], $ev ?: array( array( 'specialties' => array() ) ) ) ) ) );
		$years     = array_values( array_unique( array_map( fn( $e ) => $e['year'], $ev ) ) );
		$types     = array_values( array_unique( array_filter( array_map( fn( $e ) => $e['type'], $ev ) ) ) );
		$first     = $ev[0] ?? null;
		$s         = array(
			'events'       => count( $ev ),
			'congresses'   => count( array_filter( $ev, fn( $e ) => $e['congress'] ) ),
			'countries'    => count( $countries ),
			'cities'       => count( $cities ),
			'specialties'  => count( $specs ),
			'since'        => $first ? PL_Dates::month_short( $first['start'] ) : '',
			'since_long'   => $first ? PL_Dates::month_long( $first['start'] ) : '',
			'since_year'   => $first ? $first['year'] : '',
			'live'         => count( array_filter( $ev, fn( $e ) => 'live' === $e['status'] ) ),
			'upcoming'     => count( array_filter( $ev, fn( $e ) => 'upcoming' === $e['status'] ) ),
			'past'         => count( array_filter( $ev, fn( $e ) => 'past' === $e['status'] ) ),
			'country_list' => $countries,
			'city_list'    => array_values( array_unique( array_map( fn( $e ) => $e['city'], $on_map ) ) ),
			'specialty_list' => $specs,
			'year_list'    => $years,
			'type_list'    => $types,
		);
		return apply_filters( 'pl_stats_items', $s, $ev );
	}

	/* ---------------------------------------------------------- other types */

	public static function company() {
		return PL_Cache::remember( 'company', fn() => PL_Plugin::module( 'company' )->data() );
	}

	public static function offices() {
		$o = self::module_items( 'pl_office' );
		usort( $o, fn( $a, $b ) => ( (int) $b['head_office'] - (int) $a['head_office'] ) ?: ( $a['order'] - $b['order'] ) );
		return $o;
	}

	public static function head_office() {
		$o = self::offices();
		return $o[0] ?? null;
	}

	/** Map origin = head office city (Alexandria). */
	public static function origin() {
		$h = self::head_office();
		if ( ! $h ) {
			return array( 'city' => 'Alexandria', 'country' => 'EG', 'lonlat' => array( 29.92, 31.2 ) );
		}
		list( $lon, $lat ) = PL_Geo::locate( $h['city'], $h['country'] );
		return array( 'city' => $h['city'], 'country' => $h['country'], 'lonlat' => array( $lon, $lat ) );
	}

	/** Office that covers a country (contact pre-set), else the head office. */
	public static function office_for( $cc ) {
		foreach ( self::offices() as $o ) {
			if ( $cc && in_array( strtoupper( $cc ), (array) $o['covers'], true ) ) {
				return $o;
			}
		}
		return self::head_office();
	}

	public static function societies() {
		return self::module_items( 'pl_society' );
	}

	public static function sponsors() {
		return self::module_items( 'pl_sponsor' );
	}

	public static function services() {
		return self::module_items( 'pl_service' );
	}

	public static function jobs() {
		$m = PL_Plugin::module( 'pl_job' );
		return $m ? $m->items() : array(); // not cached: closing dates hide jobs by date.
	}

	public static function memberships() {
		return self::module_items( 'pl_membership' );
	}

	/* --------------------------------------------------------------- export */

	/** Same shape as data/pl-data.json (schema pl-events-core/1). */
	public static function all() {
		$events = self::events();
		$geo    = array( 'countries' => array(), 'cities' => array(), 'credits' => PL_Geo::credits() );
		foreach ( $events as $e ) {
			if ( $e['country'] ) {
				$c                                 = PL_Geo::country( $e['country'] );
				$geo['countries'][ $e['country'] ] = array( 'name' => $c['name'], 'tz' => PL_Geo::tz( $e['country'] ), 'centre' => $c['centre'], 'dial' => $c['dial'] );
			}
			if ( $e['lonlat'] ) {
				$geo['cities'][ $e['city'] ] = $e['lonlat'];
			}
		}
		$o = self::origin();
		$geo['cities'][ $o['city'] ] = $o['lonlat'];
		$strip = fn( $e ) => array_diff_key( $e, array_flip( array( 'post_id', 'tz', 'today' ) ) );
		return apply_filters(
			'pl_data_all',
			array(
				'meta'        => array( 'schema' => self::SCHEMA, 'generated' => gmdate( 'c' ), 'source' => home_url( '/' ) ),
				'company'     => self::company(),
				'offices'     => self::offices(),
				'events'      => array_map( $strip, $events ),
				'societies'   => self::societies(),
				'sponsors'    => self::sponsors(),
				'services'    => self::services(),
				'jobs'        => self::jobs(),
				'memberships' => self::memberships(),
				'geo'         => $geo,
			)
		);
	}

	/* --------------------------------------------------------- sample + QA */

	public static function sample_count() {
		$n = 0;
		foreach ( PL_Plugin::modules() as $type => $m ) {
			if ( 'company' === $type ) {
				continue;
			}
			foreach ( $m->items( true ) as $i ) {
				$n += ! empty( $i['sample'] ) ? 1 : 0;
			}
		}
		$c = self::company();
		return $n + ( ! empty( $c['showreel']['sample'] ) ? 1 : 0 );
	}

	public static function sample_message() {
		$n = self::sample_count();
		return $n ? sprintf( '<strong style="color:#b26a00">%d item(s) still marked Sample.</strong> The site cannot launch until this is 0.', $n ) : '<strong>0</strong> — nothing is marked Sample.';
	}

	/** Admin-only "Unmatched cities" list (Step 2.2 D3). */
	public static function unmatched_cities() {
		$out = array();
		foreach ( self::events( array( 'include_hidden' => true ) ) as $e ) {
			if ( $e['on_centre'] && $e['city'] ) {
				$out[ $e['city'] . '|' . $e['country'] ][] = $e;
			}
		}
		return $out;
	}
}
