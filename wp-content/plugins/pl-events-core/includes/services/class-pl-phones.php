<?php
/**
 * Phones (Step 2.2 D11): a repeater of label · country code · number · "On WhatsApp".
 * The site strips spaces and a leading 0 and builds the display text, tel: and wa.me links.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Phones {

	public static function subfields() {
		return array(
			array( 'name' => 'label', 'label' => 'Label (optional)', 'type' => 'text', 'help' => 'e.g. Egypt, Local organiser' ),
			array( 'name' => 'code', 'label' => 'Country code', 'type' => 'select', 'choices' => PL_Geo::dial_choices(), 'default' => '+20' ),
			array( 'name' => 'number', 'label' => 'Number', 'type' => 'text', 'required' => true, 'help' => 'Spaces and a leading 0 are fine.' ),
			array( 'name' => 'whatsapp', 'label' => 'On WhatsApp', 'type' => 'switch' ),
		);
	}

	/** Stored row → data-layer phone { label, number (display), whatsapp, tel, wa }. */
	public static function from_row( array $row ) {
		$code  = preg_replace( '/\D/', '', (string) ( $row['code'] ?? '20' ) );
		$local = ltrim( preg_replace( '/\D/', '', (string) ( $row['number'] ?? '' ) ), '0' );
		if ( '' === $local ) {
			return null;
		}
		if ( '' !== $code && 0 === strpos( $local, $code ) && strlen( $local ) > 9 ) {
			$local = substr( $local, strlen( $code ) ); // someone typed the code twice.
		}
		$digits = $code . $local;
		return array(
			'label'    => (string) ( $row['label'] ?? '' ),
			'number'   => '+' . $code . ' ' . self::group( $local ),
			'whatsapp' => ! empty( $row['whatsapp'] ),
			'tel'      => 'tel:+' . $digits,
			'wa'       => ! empty( $row['whatsapp'] ) ? 'https://wa.me/' . $digits : '',
		);
	}

	/** Data-layer phone ("+20 102 555 9003") → stored row (importer). */
	public static function to_row( $p ) {
		$p      = is_array( $p ) ? $p : array( 'number' => (string) $p );
		$number = trim( (string) ( $p['number'] ?? '' ) );
		$code   = '+20';
		if ( preg_match( '/^\+\s*(\d+)/', $number, $m ) ) {
			$digits = $m[1];
			$codes  = array_keys( PL_Geo::dial_choices() );
			usort( $codes, static fn( $a, $b ) => strlen( $b ) - strlen( $a ) );
			foreach ( $codes as $c ) {
				$cd = ltrim( $c, '+' );
				if ( 0 === strpos( preg_replace( '/\D/', '', $number ), $cd ) ) {
					$code   = $c;
					$number = substr( preg_replace( '/\D/', '', $number ), strlen( $cd ) );
					break;
				}
			}
			unset( $digits );
		}
		return array( 'label' => (string) ( $p['label'] ?? '' ), 'code' => $code, 'number' => $number, 'whatsapp' => ! empty( $p['whatsapp'] ) );
	}

	/** 1025559003 → "102 555 9003", 551582689 → "55 158 2689". */
	public static function group( $d ) {
		if ( strlen( $d ) <= 4 ) {
			return $d;
		}
		$last = substr( $d, -4 );
		$rest = substr( $d, 0, -4 );
		$mid  = substr( $rest, -3 );
		$head = substr( $rest, 0, -3 );
		return trim( ( '' !== $head ? $head . ' ' : '' ) . $mid . ' ' . $last );
	}
}
