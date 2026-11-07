<?php
/**
 * The visitor's IP for rate limits (login + newsletter).
 *
 * Rule: REMOTE_ADDR only — the address of the machine that actually opened the TCP connection; it can't be
 * faked by the visitor. Headers such as X-Forwarded-For / X-Real-IP / CF-Connecting-IP are written by the
 * client and are IGNORED, unless REMOTE_ADDR itself is one of OUR proxies, listed in wp-config.php:
 *
 *   define( 'PL_TRUSTED_PROXIES', '203.0.113.10, 198.51.100.0/24' );   // IPs or CIDR ranges, IPv4/IPv6
 *
 * Only then is X-Forwarded-For read, from the right: trusted proxies are skipped and the first address that
 * is not ours is the client (left-most entries can be forged, so they are never taken blindly).
 * X-Real-IP and CF-Connecting-IP are never read. Staging (7 Nov 2026): no proxy in front — REMOTE_ADDR is the
 * public client IP and no forwarding headers arrive — so PL_TRUSTED_PROXIES is not set.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Client_IP {

	public static function get() {
		$remote = self::valid( isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated as an IP.
		if ( '' === $remote || ! self::trusted( $remote ) || empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			return $remote;
		}
		$hops = array_reverse( array_map( 'trim', explode( ',', (string) wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- each hop validated below.
		foreach ( $hops as $h ) {
			$h = self::valid( $h );
			if ( '' === $h ) {
				return $remote; // garbage in the chain: don't guess
			}
			if ( ! self::trusted( $h ) ) {
				return $h;
			}
		}
		return $remote;
	}

	private static function valid( $ip ) {
		$ip = trim( (string) $ip );
		return false !== filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	/** @return string[] */
	public static function proxies() {
		$p = defined( 'PL_TRUSTED_PROXIES' ) ? PL_TRUSTED_PROXIES : array();
		$p = is_array( $p ) ? $p : explode( ',', (string) $p );
		return array_values( array_filter( array_map( 'trim', $p ) ) );
	}

	public static function trusted( $ip ) {
		foreach ( self::proxies() as $range ) {
			if ( self::in_range( $ip, $range ) ) {
				return true;
			}
		}
		return false;
	}

	public static function in_range( $ip, $range ) {
		$parts = explode( '/', $range, 2 );
		$net   = @inet_pton( $parts[0] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- invalid entries just don't match.
		$addr  = @inet_pton( $ip ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( false === $net || false === $addr || strlen( $net ) !== strlen( $addr ) ) {
			return false;
		}
		$bits = isset( $parts[1] ) ? (int) $parts[1] : strlen( $net ) * 8;
		$full = intdiv( $bits, 8 );
		if ( substr( $net, 0, $full ) !== substr( $addr, 0, $full ) ) {
			return false;
		}
		$rest = $bits % 8;
		if ( ! $rest ) {
			return true;
		}
		$mask = chr( ( 0xff << ( 8 - $rest ) ) & 0xff );
		return ( $net[ $full ] & $mask ) === ( $addr[ $full ] & $mask );
	}
}
