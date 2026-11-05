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
// This host's PHP mail() fails as soon as a message has an extra header (Reply-To, HTML);
// piping to sendmail directly works. Its outbound filter also silently refuses some test-like
// subjects ("… test 7"): real names pass. (Tested 5 Nov 2026.)
add_action( 'phpmailer_init', function ( $m ) { $m->Sender = PL_STAGING_MAIL_FROM; $m->isSendmail(); $m->Sendmail = '/usr/sbin/sendmail'; }, 99 );

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
