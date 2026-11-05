<?php
/**
 * Countries + cities (bundled data, Step 2.2 D2/D3). Coordinates are never typed:
 * a city is matched to the GeoNames list for its country, else the pin sits on the
 * country centre and the city shows in the admin "Unmatched cities" list.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Geo {

	private static $countries;
	private static $cities;

	public static function countries() {
		if ( null === self::$countries ) {
			$d               = json_decode( (string) file_get_contents( PL_EVENTS_DIR . 'data/countries.json' ), true );
			self::$countries = $d['countries'] ?? array();
		}
		return self::$countries;
	}

	public static function credits() {
		return 'Map data: GeoNames (CC BY 4.0), mledoze/countries (ODbL)';
	}

	public static function country( $cc ) {
		$all = self::countries();
		$cc  = strtoupper( (string) $cc );
		if ( isset( $all[ $cc ] ) ) {
			return $all[ $cc ] + array( 'code' => $cc );
		}
		return array( 'code' => $cc, 'name' => $cc, 'tz' => '', 'centre' => array( 30.0, 30.0 ), 'dial' => '' );
	}

	public static function name( $cc ) {
		return self::country( $cc )['name'];
	}

	/** Time zone of the country (Step 2.2 D1). Falls back to PHP's per-country list, then Cairo. */
	public static function tz( $cc ) {
		$tz = self::country( $cc )['tz'];
		if ( ! $tz && 2 === strlen( (string) $cc ) ) {
			$list = DateTimeZone::listIdentifiers( DateTimeZone::PER_COUNTRY, strtoupper( $cc ) );
			$tz   = $list[0] ?? '';
		}
		return $tz ? $tz : 'Africa/Cairo';
	}

	/** Emoji flag from the ISO code (used in admin dropdowns). */
	public static function emoji( $cc ) {
		$cc = strtoupper( (string) $cc );
		if ( ! preg_match( '/^[A-Z]{2}$/', $cc ) ) {
			return '';
		}
		return mb_chr( 0x1F1E6 + ord( $cc[0] ) - 65 ) . mb_chr( 0x1F1E6 + ord( $cc[1] ) - 65 );
	}

	public static function flag_url( $cc ) {
		$cc = strtolower( (string) $cc );
		return is_readable( PL_EVENTS_DIR . "assets/flags/$cc.svg" ) ? PL_EVENTS_URL . "assets/flags/$cc.svg" : '';
	}

	public static function country_choices() {
		$out = array();
		foreach ( self::countries() as $cc => $c ) {
			$out[ $cc ] = trim( self::emoji( $cc ) . ' ' . $c['name'] );
		}
		return $out;
	}

	public static function dial_choices() {
		$out = array();
		foreach ( self::countries() as $cc => $c ) {
			if ( ! empty( $c['dial'] ) ) {
				$out[ $c['dial'] ] = trim( self::emoji( $cc ) . ' ' . $c['dial'] . ' ' . $c['name'] );
			}
		}
		return $out;
	}

	private static function cities() {
		if ( null === self::$cities ) {
			$d            = json_decode( (string) file_get_contents( PL_EVENTS_DIR . 'data/cities.json' ), true );
			self::$cities = $d['cities'] ?? array();
		}
		return self::$cities;
	}

	public static function city_names( $cc ) {
		return array_keys( self::cities()[ strtoupper( (string) $cc ) ] ?? array() );
	}

	/**
	 * @return array{0:float,1:float,2:bool} [lon, lat, matched]
	 */
	public static function locate( $city, $cc ) {
		$cc   = strtoupper( (string) $cc );
		$list = self::cities()[ $cc ] ?? array();
		$want = self::norm( $city );
		if ( '' !== $want ) {
			foreach ( $list as $name => $ll ) {
				if ( self::norm( $name ) === $want ) {
					return array( (float) $ll[0], (float) $ll[1], true );
				}
			}
		}
		$c = self::country( $cc )['centre'];
		return array( (float) $c[0], (float) $c[1], false );
	}

	private static function norm( $s ) {
		$s = remove_accents( (string) $s );
		return strtolower( preg_replace( '/[^a-z0-9]+/i', '', $s ) );
	}
}
