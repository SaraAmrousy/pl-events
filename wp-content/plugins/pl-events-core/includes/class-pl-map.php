<?php
/**
 * The 2D dotted map (locked decision 6): cities, arcs, counts and camera framing are
 * generated from event data. Same projection as the prototype (equirectangular,
 * x = (lon + 14) × 10, y = (52 − lat) × 10 over a 783.5 × 469 dot grid).
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Map {

	const W = 783.5;
	const H = 469;

	public static function proj( array $ll ) {
		return array( ( $ll[0] + 14 ) * 10, ( 52 - $ll[1] ) * 10 );
	}

	public static function arc( array $a, array $b ) {
		list( $x1, $y1 ) = self::proj( $a );
		list( $x2, $y2 ) = self::proj( $b );
		$mx   = ( $x1 + $x2 ) / 2;
		$my   = ( $y1 + $y2 ) / 2;
		$lift = max( 20, hypot( $x2 - $x1, $y2 - $y1 ) * 0.3 );
		return sprintf( 'M%.1f,%.1f Q%.1f,%.1f %.1f,%.1f', $x1, $y1, $mx, $my - $lift, $x2, $y2 );
	}

	/** viewBox that frames a set of lon/lat points (camera framing). */
	public static function view_box( array $lls, $min_w = 300, $aspect = 2.1 ) {
		if ( ! $lls ) {
			return sprintf( '0 0 %.1f %.1f', self::W, self::H );
		}
		$p  = array_map( array( __CLASS__, 'proj' ), $lls );
		$xs = array_column( $p, 0 );
		$ys = array_column( $p, 1 );
		$x0 = min( $xs ) - 70;
		$x1 = max( $xs ) + 90;
		$y0 = min( $ys ) - 70;
		$y1 = max( $ys ) + 60;
		$w  = max( $min_w, $x1 - $x0, ( $y1 - $y0 ) * $aspect );
		$h  = $w / $aspect;
		if ( $h > self::H ) {
			$h = self::H;
			$w = $h * $aspect;
		}
		$x0 = max( 0, min( self::W - $w, ( $x0 + $x1 ) / 2 - $w / 2 ) );
		$y0 = max( 0, min( self::H - $h, ( $y0 + $y1 ) / 2 - $h / 2 ) );
		return sprintf( '%.1f %.1f %.1f %.1f', $x0, $y0, $w, $h );
	}

	/** Distinct map points of a set of events: [ city, cc, lonlat ]. */
	public static function points( array $events ) {
		$pts = array();
		foreach ( $events as $e ) {
			if ( empty( $e['lonlat'] ) ) {
				continue;
			}
			$k = $e['city'] . '|' . $e['country'];
			if ( ! isset( $pts[ $k ] ) ) {
				$pts[ $k ] = array( 'city' => $e['city'], 'cc' => $e['country'], 'll' => $e['lonlat'] );
			}
		}
		return array_values( $pts );
	}

	/** Label placement per city (dx, dy, anchor) — filterable, default right-above. */
	public static function label( $city ) {
		$l = apply_filters(
			'pl_map_labels',
			array(
				'Alexandria'  => array( 12, -10, 'start' ),
				'New Alamein' => array( -10, 16, 'end' ),
				'Tunis'       => array( -10, -10, 'end' ),
				'Jeddah'      => array( -10, 14, 'end' ),
				'Luxor'       => array( 10, 14, 'start' ),
			)
		);
		return $l[ $city ] ?? array( 10, -10, 'start' );
	}

	public static function dots_url() {
		return PL_EVENTS_URL . 'assets/img/map-dots-ink.svg';
	}
}
