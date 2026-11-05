<?php
/**
 * Status and date text (Step 2.2 "Worked out by the site").
 * Status is computed in the local time of the event's country (D1); online events use Cairo.
 * It is never stored and never typed.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Dates {

	const M3 = array( 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' );
	const MF = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );

	/** "Now" — filterable so tests (and the hourly check) can simulate a date. */
	public static function now() {
		$now = apply_filters( 'pl_now', null );
		return $now instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface( $now ) : new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
	}

	/** Today's date (Y-m-d) in a time zone. */
	public static function local_today( $tz ) {
		try {
			$zone = new DateTimeZone( $tz );
		} catch ( Exception $e ) {
			$zone = new DateTimeZone( 'Africa/Cairo' );
		}
		return self::now()->setTimezone( $zone )->format( 'Y-m-d' );
	}

	/** past | live | upcoming */
	public static function status( $start, $end, $tz ) {
		$today = self::local_today( $tz );
		if ( $today > $end ) {
			return 'past';
		}
		return $today >= $start ? 'live' : 'upcoming';
	}

	public static function parts( $iso ) {
		list( $y, $m, $d ) = array_map( 'intval', explode( '-', $iso ) + array( 0, 1, 1 ) );
		return array( 'y' => $y, 'm' => $m - 1, 'd' => $d );
	}

	public static function days_between( $a, $b ) {
		return (int) round( ( strtotime( $b . ' 00:00:00 UTC' ) - strtotime( $a . ' 00:00:00 UTC' ) ) / DAY_IN_SECONDS );
	}

	/** "29–30 Jan 2027", "5 Feb 2026", "30 Oct – 1 Nov 2026". Always the year. */
	public static function range( $s, $e ) {
		$a = self::parts( $s );
		$b = self::parts( $e );
		$m = self::M3;
		if ( $s === $e ) {
			return $a['d'] . ' ' . $m[ $a['m'] ] . ' ' . $a['y'];
		}
		if ( $a['y'] === $b['y'] && $a['m'] === $b['m'] ) {
			return $a['d'] . '–' . $b['d'] . ' ' . $m[ $a['m'] ] . ' ' . $a['y'];
		}
		if ( $a['y'] === $b['y'] ) {
			return $a['d'] . ' ' . $m[ $a['m'] ] . ' – ' . $b['d'] . ' ' . $m[ $b['m'] ] . ' ' . $b['y'];
		}
		return $a['d'] . ' ' . $m[ $a['m'] ] . ' ' . $a['y'] . ' – ' . $b['d'] . ' ' . $m[ $b['m'] ] . ' ' . $b['y'];
	}

	/** Date tile on the poster: [big, small]. */
	public static function tile( $s, $e ) {
		$a = self::parts( $s );
		$b = self::parts( $e );
		$m = self::M3;
		$big   = $s === $e ? (string) $a['d'] : ( $a['m'] === $b['m'] ? $a['d'] . '–' . $b['d'] : $a['d'] . ' ' . $m[ $a['m'] ] );
		$small = ( $a['m'] === $b['m'] || $s === $e ) ? $m[ $a['m'] ] . ' ' . $a['y'] : '– ' . $b['d'] . ' ' . $m[ $b['m'] ] . ' ' . $b['y'];
		return array( $big, $small );
	}

	public static function month_short( $iso ) {
		$p = self::parts( $iso );
		return self::M3[ $p['m'] ] . ' ' . $p['y'];
	}

	public static function month_long( $iso ) {
		$p = self::parts( $iso );
		return self::MF[ $p['m'] ] . ' ' . $p['y'];
	}
}
