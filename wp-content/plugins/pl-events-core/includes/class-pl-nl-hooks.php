<?php
/**
 * Newsletter webhooks (Step 4.6): Mailchimp (unsubscribe, cleaned) and Zoho Campaigns (unsubscribe, hard
 * bounce) tell the site when someone leaves; the site then removes them from the other list too.
 *
 *   POST/GET /wp-json/pl/v1/nl-hook/mailchimp?k=SECRET
 *   POST/GET /wp-json/pl/v1/nl-hook/zoho?k=SECRET
 *
 * Neither provider signs its webhooks, so the URL carries a 32-character random secret (compared in constant
 * time; a wrong or missing secret gets a plain 404). GET answers 200 because Mailchimp checks the URL that
 * way when the webhook is created. Mailchimp's webhook is created from the settings page by API; Zoho's is
 * added by hand (Settings › Developer Space › Webhooks › Contacts) and shows "Connected" once Zoho has
 * called it. Zoho's payload is read defensively (form or JSON, any field holding an email); only the field
 * names — never values — are logged when an event can't be read.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_NL_Hooks {

	const OPT = 'pl_nl_hooks';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'rest' ) );
	}

	private static function opt() {
		$o = get_option( self::OPT, array() );
		return is_array( $o ) ? $o : array();
	}

	public static function key() {
		$o = self::opt();
		if ( empty( $o['key'] ) ) {
			$o['key'] = wp_generate_password( 32, false );
			update_option( self::OPT, $o, false );
		}
		return $o['key'];
	}

	public static function regenerate() {
		$o        = self::opt();
		$o['key'] = wp_generate_password( 32, false );
		unset( $o['seen'], $o['mc_ok'] );
		update_option( self::OPT, $o, false );
		delete_transient( 'pl_nl_mc_hook' );
	}

	public static function url( $p ) {
		return add_query_arg( 'k', self::key(), rest_url( 'pl/v1/nl-hook/' . $p ) );
	}

	/** Last time a valid call arrived (GMT timestamp) or 0. */
	public static function seen( $p ) {
		$o = self::opt();
		return (int) ( $o['seen'][ $p ] ?? 0 );
	}

	/**
	 * 'connected' | 'not_set' (+ a reason). Mailchimp: checked on the audience by API (cached 10 min).
	 * Zoho: connected once Zoho has called the URL.
	 */
	public static function state( $p ) {
		if ( 'zoho' === $p ) {
			return self::seen( 'zoho' ) ? 'connected' : 'not_set';
		}
		$c = get_transient( 'pl_nl_mc_hook' );
		if ( false === $c ) {
			$ps = PL_Newsletter::providers( 'mailchimp' );
			$mc = reset( $ps );
			$c  = $mc && $mc->configured() && true === $mc->has_webhook( self::url( 'mailchimp' ) ) ? 'connected' : 'not_set';
			set_transient( 'pl_nl_mc_hook', $c, 10 * MINUTE_IN_SECONDS );
		}
		return $c;
	}

	public static function rest() {
		register_rest_route(
			'pl/v1',
			'/nl-hook/(?P<p>mailchimp|zoho)',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'permission_callback' => '__return_true', // checked in handle(): secret in the URL
				'callback'            => array( __CLASS__, 'handle' ),
			)
		);
	}

	public static function handle( WP_REST_Request $r ) {
		$p = (string) $r['p'];
		if ( ! hash_equals( self::key(), (string) $r->get_param( 'k' ) ) ) {
			return new WP_REST_Response( array( 'code' => 'rest_no_route' ), 404 );
		}
		$o               = self::opt();
		$o['seen'][ $p ] = time();
		update_option( self::OPT, $o, false );
		if ( 'GET' === $r->get_method() ) {
			return rest_ensure_response( array( 'ok' => true ) ); // Mailchimp's URL check
		}
		$raw    = 'mailchimp' === $p ? $r->get_body_params() : ( $r->get_json_params() ? $r->get_json_params() : $r->get_body_params() );
		$events = 'mailchimp' === $p ? self::mailchimp_events( $r->get_params() ) : self::zoho_events( $raw );
		self::keep_sample( $p, $raw, count( $events ) );
		foreach ( $events as $e ) {
			PL_Newsletter::opt_out( $e[0], $p, $e[1] );
		}
		if ( ! $events && 'zoho' === $p ) {
			error_log( '[pl-newsletter] Zoho webhook without an unsubscribe/bounce we could read. Fields: ' . implode( ',', array_keys( self::flat( (array) ( $r->get_json_params() ? $r->get_json_params() : $r->get_body_params() ) ) ) ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions -- field names only, no values.
		}
		return rest_ensure_response( array( 'ok' => true, 'handled' => count( $events ) ) );
	}

	/**
	 * Keep the last 3 payloads per provider, emails masked (a•••@domain), so the real shape can be checked
	 * against the parser (Step 4.6 connection test). Not autoloaded; never shown on the front end.
	 */
	private static function keep_sample( $p, $raw, $handled ) {
		$mask = static function ( $v ) use ( &$mask ) {
			if ( is_array( $v ) ) {
				return array_map( $mask, $v );
			}
			return is_string( $v ) ? preg_replace_callback( '/([^\s@"<>,;:]+)@([A-Za-z0-9.-]+)/', static fn( $m ) => substr( $m[1], 0, 1 ) . '•••@' . $m[2], $v ) : $v;
		};
		$o                    = self::opt();
		$list                 = (array) ( $o['samples'][ $p ] ?? array() );
		array_unshift( $list, array( 'time' => gmdate( 'c' ), 'handled' => $handled, 'type' => (string) ( $_SERVER['CONTENT_TYPE'] ?? '' ), 'body' => $mask( (array) $raw ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- stored, masked, admin-only.
		$o['samples'][ $p ]   = array_slice( $list, 0, 3 );
		update_option( self::OPT, $o, false );
	}

	public static function samples( $p ) {
		$o = self::opt();
		return (array) ( $o['samples'][ $p ] ?? array() );
	}

	/** Mailchimp: form-encoded type=unsubscribe|cleaned & data[email]=… */
	private static function mailchimp_events( array $p ) {
		$type  = (string) ( $p['type'] ?? '' );
		$email = (string) ( $p['data']['email'] ?? '' );
		if ( ! is_email( $email ) || ! in_array( $type, array( 'unsubscribe', 'cleaned' ), true ) ) {
			return array();
		}
		return array( array( $email, 'cleaned' === $type ? PL_Newsletter::ST_BOUNCED : PL_Newsletter::ST_UNSUB ) );
	}

	/** Zoho: walk the payload; every object holding an email + an unsubscribe / hard-bounce marker is an event. */
	private static function zoho_events( $data, array $ctx = array() ) {
		$out = array();
		if ( ! is_array( $data ) ) {
			return $out;
		}
		$email  = '';
		$labels = $ctx;
		foreach ( $data as $k => $v ) {
			if ( is_scalar( $v ) ) {
				$v = (string) $v;
				if ( '' === $email && preg_match( '/e-?mail/i', (string) $k ) && is_email( $v ) ) {
					$email = $v;
				} elseif ( preg_match( '/action|event|type|status|reason|activity/i', (string) $k ) ) {
					$labels[] = strtolower( $v );
				}
			}
		}
		foreach ( $data as $v ) {
			if ( is_array( $v ) ) {
				$out = array_merge( $out, self::zoho_events( $v, $labels ) );
			}
		}
		if ( $email && ! $out ) {
			$l = implode( ' ', $labels );
			if ( false !== strpos( $l, 'unsub' ) || false !== strpos( $l, 'opt-out' ) || false !== strpos( $l, 'optout' ) ) {
				$out[] = array( $email, PL_Newsletter::ST_UNSUB );
			} elseif ( false !== strpos( $l, 'bounce' ) && false === strpos( $l, 'soft' ) ) {
				$out[] = array( $email, PL_Newsletter::ST_BOUNCED );
			}
		}
		return $out;
	}

	private static function flat( array $a, $pre = '' ) {
		$o = array();
		foreach ( $a as $k => $v ) {
			if ( is_array( $v ) ) {
				$o += self::flat( $v, $pre . $k . '.' );
			} else {
				$o[ $pre . $k ] = 1;
			}
		}
		return $o;
	}
}
