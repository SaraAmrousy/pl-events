<?php
/**
 * Encrypts API keys/tokens at rest (AES-256-GCM, key derived from the site's own salts in wp-config.php),
 * so a database export or backup does not carry usable keys. Never used for passwords people type to log in.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Secret {

	private static function key() {
		$salt = ( defined( 'AUTH_KEY' ) ? AUTH_KEY : '' ) . ( defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '' ) . 'pl-events-core';
		return hash( 'sha256', $salt, true );
	}

	public static function seal( $plain ) {
		$plain = (string) $plain;
		if ( '' === $plain ) {
			return '';
		}
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return 'b64:' . base64_encode( $plain ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- fallback only when OpenSSL is missing.
		}
		$iv  = random_bytes( 12 );
		$tag = '';
		$ct  = openssl_encrypt( $plain, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag );
		return 'v1:' . base64_encode( $iv . $tag . $ct ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}

	public static function open( $sealed ) {
		$sealed = (string) $sealed;
		if ( '' === $sealed ) {
			return '';
		}
		if ( 0 === strpos( $sealed, 'b64:' ) ) {
			return (string) base64_decode( substr( $sealed, 4 ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		}
		if ( 0 !== strpos( $sealed, 'v1:' ) || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}
		$raw = base64_decode( substr( $sealed, 3 ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		$out = openssl_decrypt( substr( $raw, 28 ), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, substr( $raw, 0, 12 ), substr( $raw, 12, 16 ) );
		return false === $out ? '' : $out;
	}
}
