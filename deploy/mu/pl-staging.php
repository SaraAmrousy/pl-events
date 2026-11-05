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
