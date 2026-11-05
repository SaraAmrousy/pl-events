<?php
/**
 * Event page (/events/{slug}/), its .ics (/events/{slug}/ics/) and share metadata.
 * Hidden events (Show on site off) answer 404 to visitors; editors still preview them.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Event_Page {

	private static $current;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'rewrite' ), 20 );
		add_filter( 'query_vars', fn( $v ) => array_merge( $v, array( 'pl_ics' ) ) );
		add_action( 'template_redirect', array( __CLASS__, 'route' ), 1 );
		add_filter( 'template_include', array( __CLASS__, 'template' ), 99 );
		add_action( 'wp_head', array( __CLASS__, 'meta' ), 5 );
	}

	public static function rewrite() {
		add_rewrite_rule( '^events/([^/]+)/ics/?$', 'index.php?event=$matches[1]&pl_ics=1', 'top' );
	}

	public static function current() {
		if ( null === self::$current && is_singular( 'event' ) ) {
			$id = get_queried_object_id();
			$e  = PL_Data::event_by_id( $id, true );
			if ( $e && ! $e['show_on_site'] && ! current_user_can( 'edit_post', $id ) ) {
				$e = null;
			}
			self::$current = $e ? $e : false;
		}
		return self::$current ? self::$current : null;
	}

	public static function route() {
		if ( ! is_singular( 'event' ) ) {
			return;
		}
		$e = self::current();
		if ( ! $e ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			return;
		}
		if ( get_query_var( 'pl_ics' ) ) {
			self::ics( $e );
		}
	}

	/** All-day .ics (RFC 5545). */
	public static function ics( array $e ) {
		$fold = static function ( $line ) {
			$out = '';
			while ( strlen( $line ) > 74 ) {
				$cut  = mb_strcut( $line, 0, 74 );
				$out .= $cut . "\r\n ";
				$line = substr( $line, strlen( $cut ) );
			}
			return $out . $line . "\r\n";
		};
		$esc  = fn( $s ) => str_replace( array( '\\', ';', ',', "\n" ), array( '\\\\', '\;', '\,', '\n' ), (string) $s );
		$loc  = trim( ( $e['venue'] ? $e['venue'] . ', ' : '' ) . $e['place'] );
		$end  = gmdate( 'Ymd', strtotime( $e['end'] . ' 00:00:00 UTC' ) + DAY_IN_SECONDS );
		$body = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//PL Events//pl-events-core//EN\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\nBEGIN:VEVENT\r\n";
		$body .= $fold( 'UID:' . $e['id'] . '@' . wp_parse_url( home_url(), PHP_URL_HOST ) );
		$body .= 'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ) . "\r\n";
		$body .= 'DTSTART;VALUE=DATE:' . str_replace( '-', '', $e['start'] ) . "\r\n";
		$body .= 'DTEND;VALUE=DATE:' . $end . "\r\n";
		$body .= $fold( 'SUMMARY:' . $esc( $e['name'] ) );
		$body .= $fold( 'LOCATION:' . $esc( $loc ) );
		$body .= $fold( 'DESCRIPTION:' . $esc( trim( ( $e['website'] ? $e['website'] . ' — ' : '' ) . 'Organised by PL Events. ' . $e['url'] ) ) );
		$body .= $fold( 'URL:' . ( $e['website'] ? $e['website'] : $e['url'] ) );
		$body .= "END:VEVENT\r\nEND:VCALENDAR\r\n";
		nocache_headers();
		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $e['id'] ) . '.ics"' );
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput -- calendar format, escaped above.
		exit;
	}

	public static function template( $template ) {
		if ( is_singular( 'event' ) && self::current() ) {
			PL_Shortcodes::enqueue();
			$theme = locate_template( array( 'pl-events/single-event.php' ) );
			return $theme ? $theme : PL_EVENTS_DIR . 'templates/single-event.php';
		}
		return $template;
	}

	/** Without Rank Math: description + share image (same text as PL_SEO builds for Rank Math). */
	public static function meta() {
		$e = self::current();
		if ( ! $e || defined( 'RANK_MATH_VERSION' ) ) {
			return;
		}
		$desc = PL_SEO::event_description( $e );
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $e['name'] ) );
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
		if ( $e['poster'] ) {
			printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $e['poster'] ) );
		}
	}
}
