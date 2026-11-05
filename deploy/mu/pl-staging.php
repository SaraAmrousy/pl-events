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
// 3) Never keep a foreign Authorization header around for plugins to read.
if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) && 0 !== stripos( $_SERVER['HTTP_AUTHORIZATION'], 'basic ' ) ) {
	unset( $_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] );
}

// 4) Mail on this host leaves only from the account's real address with that address as the
//    envelope sender (tested 5 Nov 2026: wordpress@… and noreply@… never arrived). Staging only;
//    the live host gets its own setting at launch (Step 6.3).
const PL_STAGING_MAIL_FROM = 'saraamro@saraamrousy.net';
add_filter( 'wp_mail_from', fn() => PL_STAGING_MAIL_FROM, 99 );
add_filter( 'wp_mail_from_name', fn( $n ) => ( 'WordPress' === $n || '' === $n ) ? 'PL Events (staging)' : $n, 99 );
add_filter( 'forminator_custom_form_mail_admin_from_email', fn() => PL_STAGING_MAIL_FROM, 99 );
add_action( 'phpmailer_init', function ( $m ) { $m->Sender = PL_STAGING_MAIL_FROM; }, 99 );
