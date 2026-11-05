<?php
/**
 * Plugin Name: PL staging guard
 * Description: Staging only — keeps saraamrousy.net/pl out of search engines. Remove at launch (Step 6.3).
 */
defined( 'ABSPATH' ) || exit;

// 1) "Discourage search engines" stays on, whatever is clicked.
add_filter( 'pre_option_blog_public', fn() => '0' );
// 2) noindex on every response WordPress serves (pages, feeds, REST).
add_action( 'send_headers', fn() => header( 'X-Robots-Tag: noindex, nofollow', true ) );
add_filter( 'rest_post_dispatch', function ( $r ) { if ( $r instanceof WP_REST_Response ) { $r->header( 'X-Robots-Tag', 'noindex, nofollow' ); } return $r; } );
add_filter( 'wp_robots', fn( $robots ) => array_merge( $robots, [ 'noindex' => true, 'nofollow' => true ] ) );
// Rank Math writes its own robots meta: force noindex there too (Step 5.3).
add_filter( 'rank_math/frontend/robots', fn( $robots ) => array_merge( (array) $robots, [ 'index' => 'noindex', 'follow' => 'nofollow' ] ), 99 );
// Staging never tells a search engine anything: block sitemap pings / IndexNow / indexing APIs at the HTTP layer.
add_filter( 'pre_http_request', function ( $pre, $args, $url ) {
	$host = (string) wp_parse_url( $url, PHP_URL_HOST );
	foreach ( [ 'indexnow', 'bing.com', 'google.com/ping', 'googleapis.com/indexing', 'yandex', 'seznam', 'naver' ] as $needle ) {
		if ( false !== stripos( $host . (string) wp_parse_url( $url, PHP_URL_PATH ), $needle ) ) {
			error_log( '[pl-staging] blocked search-engine request: ' . $url );
			return new WP_Error( 'pl_staging_blocked', 'Staging: search-engine requests are blocked.' );
		}
	}
	return $pre;
}, 1, 3 );
// 3) Never keep a foreign Authorization header around for plugins to read.
if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) && 0 !== stripos( $_SERVER['HTTP_AUTHORIZATION'], 'basic ' ) ) {
	unset( $_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] );
}

// 4) Authenticated SMTP (Step 4.5 close, 6 Nov 2026). PHP mail/sendmail on this host dropped
//    messages silently, so mail goes through the mailbox pl-staging@saraamrousy.net with the free
//    WP Mail SMTP plugin, configured here by its constants. The password is read from
//    ~/pl-deploy/.env (outside public_html, 0700) — never stored in the database or in git.
//    Staging only; the live host gets PL's own mailbox at launch (Step 6.3).
( function () {
	$env = dirname( ABSPATH, 2 ) . '/pl-deploy/.env'; // ABSPATH = /home/<user>/public_html/pl/ → /home/<user>/pl-deploy/.env
	if ( ! is_readable( $env ) ) {
		return;
	}
	$v = array();
	foreach ( (array) file( $env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $line ) {
		if ( preg_match( '/^(SMTP_[A-Z]+)=(.*)$/', $line, $m ) ) {
			$v[ $m[1] ] = trim( $m[2] );
		}
	}
	if ( empty( $v['SMTP_USER'] ) || empty( $v['SMTP_PASS'] ) ) {
		return;
	}
	defined( 'WPMS_ON' ) || define( 'WPMS_ON', true );
	defined( 'WPMS_MAILER' ) || define( 'WPMS_MAILER', 'smtp' );
	defined( 'WPMS_SMTP_HOST' ) || define( 'WPMS_SMTP_HOST', $v['SMTP_HOST'] ?? 'mail.saraamrousy.net' );
	defined( 'WPMS_SMTP_PORT' ) || define( 'WPMS_SMTP_PORT', (int) ( $v['SMTP_PORT'] ?? 465 ) );
	defined( 'WPMS_SSL' ) || define( 'WPMS_SSL', 'ssl' );
	defined( 'WPMS_SMTP_AUTH' ) || define( 'WPMS_SMTP_AUTH', true );
	defined( 'WPMS_SMTP_USER' ) || define( 'WPMS_SMTP_USER', $v['SMTP_USER'] );
	defined( 'WPMS_SMTP_PASS' ) || define( 'WPMS_SMTP_PASS', $v['SMTP_PASS'] );
	defined( 'WPMS_MAIL_FROM' ) || define( 'WPMS_MAIL_FROM', $v['SMTP_USER'] );
	defined( 'WPMS_MAIL_FROM_FORCE' ) || define( 'WPMS_MAIL_FROM_FORCE', true );
	defined( 'WPMS_MAIL_FROM_NAME' ) || define( 'WPMS_MAIL_FROM_NAME', 'PL Events (staging)' );
	defined( 'WPMS_SMTP_AUTOTLS' ) || define( 'WPMS_SMTP_AUTOTLS', true );
	add_filter( 'forminator_custom_form_mail_admin_from_email', fn() => $v['SMTP_USER'], 99 );
} )();

// 5) Staging mail log: every outgoing email (recipients + subject only) goes to the debug log.
// 6) HARD SAFETY NET: on staging no email may reach anyone but the override address (Sara).
//    Whatever plugin sends it, the recipients are replaced here and named in the first line.
add_filter( 'wp_mail', function ( $a ) {
	$to       = is_array( $a['to'] ) ? $a['to'] : array_map( 'trim', explode( ',', (string) $a['to'] ) );
	$override = get_option( 'plt_mail_override' );
	$override = is_email( $override ) ? $override : 'saraamrousy@gmail.com';
	if ( array_diff( array_map( 'strtolower', $to ), array( strtolower( $override ) ) ) ) {
		$note = 'STAGING — on the live site this goes to: ' . implode( ', ', $to );
		$a['message'] = ( false !== stripos( (string) $a['message'], '<' ) ? '<p style="background:#FFE3A3;padding:8px 12px"><strong>' . esc_html( $note ) . '</strong></p>' : $note . "\n\n" ) . $a['message'];
		$a['headers'] = array_values( array_filter( (array) ( is_array( $a['headers'] ) ? $a['headers'] : explode( "\n", (string) $a['headers'] ) ), fn( $h ) => ! preg_match( '/^\s*(cc|bcc)\s*:/i', $h ) ) );
		$a['to'] = array( $override );
	}
	error_log( 'PL staging mail → ' . implode( ', ', (array) $a['to'] ) . ' (intended: ' . implode( ', ', $to ) . ') | ' . $a['subject'] );
	return $a;
}, 999 );
add_action( 'wp_mail_failed', function ( $e ) { error_log( 'PL staging mail FAILED: ' . $e->get_error_message() ); } );
