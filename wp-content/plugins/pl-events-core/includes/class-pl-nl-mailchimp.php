<?php
/**
 * Mailchimp Marketing API v3. Called only after the person confirmed on the site, so the member is added as
 * "subscribed" (+ the audience's Email marketing permission when GDPR fields are on). Someone who earlier
 * unsubscribed in Mailchimp can't be re-subscribed by API ("Member In Compliance State"): then Mailchimp's
 * own re-confirmation is used (status "pending") — rare, and noted on the record.
 * Tags = Mailchimp tags (segment on them in Audience › Tags). The consent record is added as a member note.
 * Unsubscribes made in Mailchimp reach the site by webhook (connect_webhook()).
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_NL_Mailchimp implements PL_NL_Provider {

	private $key;
	private $list;
	private $dc;

	public function __construct( array $s ) {
		$this->key  = (string) ( $s['key'] ?? '' );
		$this->list = trim( (string) ( $s['list'] ?? '' ) );
		$dc         = trim( (string) ( $s['dc'] ?? '' ) );
		if ( '' === $dc && false !== strpos( $this->key, '-' ) ) {
			$dc = substr( $this->key, strrpos( $this->key, '-' ) + 1 ); // keys end in "-us21"
		}
		$this->dc = preg_replace( '/[^a-z0-9]/', '', strtolower( $dc ) );
	}

	public function id() {
		return 'mailchimp';
	}

	public function name() {
		return 'Mailchimp';
	}

	public function configured() {
		return '' !== $this->key && '' !== $this->list && '' !== $this->dc;
	}

	private function base() {
		return apply_filters( 'pl_nl_endpoint', 'https://' . $this->dc . '.api.mailchimp.com/3.0', 'mailchimp' );
	}

	private function call( $method, $path, $body = null ) {
		$args = array(
			'method'  => $method,
			'timeout' => 8,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'pl:' . $this->key ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- HTTP Basic auth.
				'Content-Type'  => 'application/json',
			),
		);
		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}
		$r = wp_remote_request( $this->base() . $path, $args );
		if ( is_wp_error( $r ) ) {
			return new WP_Error( 'pl_nl_network', 'Mailchimp could not be reached.', array( 'retry' => true ) );
		}
		$code = (int) wp_remote_retrieve_response_code( $r );
		$json = json_decode( (string) wp_remote_retrieve_body( $r ), true );
		if ( $code >= 200 && $code < 300 ) {
			return is_array( $json ) ? $json : array();
		}
		$detail = is_array( $json ) ? trim( ( $json['title'] ?? '' ) . ': ' . ( $json['detail'] ?? '' ), ': ' ) : '';
		return new WP_Error( 'pl_nl_mailchimp_' . $code, 'Mailchimp ' . $code . ( $detail ? ' — ' . PL_Newsletter::scrub( $detail ) : '' ), array( 'retry' => 429 === $code || $code >= 500 ) );
	}

	public function subscribe( $email, $name, array $tags, array $consent ) {
		if ( ! $this->configured() ) {
			return new WP_Error( 'pl_nl_not_configured', 'Mailchimp is not set up yet.', array( 'retry' => true ) );
		}
		$hash  = md5( strtolower( $email ) );
		$parts = preg_split( '/\s+/', trim( (string) $name ), 2 );
		$body  = array(
			'email_address' => $email,
			'status_if_new' => 'subscribed', // confirmed on the site (double opt-in done there)
			'status'        => 'subscribed',
		);
		if ( '' !== trim( (string) $name ) ) {
			$body['merge_fields'] = array( 'FNAME' => $parts[0], 'LNAME' => $parts[1] ?? '' );
		}
		$mp = $this->permissions();
		if ( $mp ) {
			$body['marketing_permissions'] = array_map( fn( $id ) => array( 'marketing_permission_id' => $id, 'enabled' => true ), $mp );
		}
		$member = '/lists/' . rawurlencode( $this->list ) . '/members/' . $hash;
		$note   = true;
		$r      = $this->call( 'PUT', $member, $body );
		if ( is_wp_error( $r ) && false !== stripos( $r->get_error_message(), 'compliance' ) ) {
			$body['status'] = $body['status_if_new'] = 'pending'; // earlier unsubscribed in Mailchimp: Mailchimp re-confirms
			$r              = $this->call( 'PUT', $member, $body );
			$note           = 'Mailchimp asked the person to re-confirm (they had unsubscribed there before).';
		}
		if ( is_wp_error( $r ) ) {
			return $r;
		}
		if ( $tags ) {
			$this->call( 'POST', '/lists/' . rawurlencode( $this->list ) . '/members/' . $hash . '/tags', array( 'tags' => array_map( fn( $t ) => array( 'name' => $t, 'status' => 'active' ), array_values( $tags ) ) ) );
		}
		$this->call( 'POST', $member . '/notes', array( 'note' => PL_Newsletter::consent_note( $consent ) ) );
		return $note;
	}

	/** Email marketing-permission IDs when the audience has GDPR fields (read from one member, cached 12 h). */
	private function permissions() {
		$ck = 'pl_nl_mc_mp_' . md5( $this->list );
		$c  = get_transient( $ck );
		if ( false !== $c ) {
			return (array) $c;
		}
		$r   = $this->call( 'GET', '/lists/' . rawurlencode( $this->list ) . '/members?count=1&fields=members.marketing_permissions' );
		$ids = array();
		if ( ! is_wp_error( $r ) ) {
			foreach ( (array) ( $r['members'][0]['marketing_permissions'] ?? array() ) as $m ) {
				if ( ! empty( $m['marketing_permission_id'] ) && preg_match( '/e-?mail/i', (string) ( $m['text'] ?? '' ) ) ) {
					$ids[] = (string) $m['marketing_permission_id'];
				}
			}
			set_transient( $ck, $ids, 12 * HOUR_IN_SECONDS );
		}
		return $ids;
	}

	public function unsubscribe( $email ) {
		if ( ! $this->configured() ) {
			return new WP_Error( 'pl_nl_not_configured', 'Mailchimp is not set up yet.', array( 'retry' => true ) );
		}
		$r = $this->call( 'PATCH', '/lists/' . rawurlencode( $this->list ) . '/members/' . md5( strtolower( $email ) ), array( 'status' => 'unsubscribed' ) );
		if ( is_wp_error( $r ) && 'pl_nl_mailchimp_404' === $r->get_error_code() ) {
			return true; // not on the list
		}
		return is_wp_error( $r ) ? $r : true;
	}

	/** Is our webhook on the audience? @return bool|WP_Error */
	public function has_webhook( $url ) {
		$r = $this->call( 'GET', '/lists/' . rawurlencode( $this->list ) . '/webhooks' );
		if ( is_wp_error( $r ) ) {
			return $r;
		}
		foreach ( (array) ( $r['webhooks'] ?? array() ) as $w ) {
			if ( ( $w['url'] ?? '' ) === $url && ! empty( $w['events']['unsubscribe'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Add the webhook (unsubscribe + cleaned, made by the person or an admin — not by API, so our own
	 * unsubscribe calls don't echo back). Mailchimp checks the URL with a GET first.
	 */
	public function connect_webhook( $url ) {
		if ( ! $this->configured() ) {
			return new WP_Error( 'pl_nl_not_configured', 'Fill in the API key and the Audience ID first.' );
		}
		$has = $this->has_webhook( $url );
		if ( true === $has || is_wp_error( $has ) ) {
			return $has;
		}
		$r = $this->call(
			'POST',
			'/lists/' . rawurlencode( $this->list ) . '/webhooks',
			array(
				'url'     => $url,
				'events'  => array( 'subscribe' => false, 'unsubscribe' => true, 'profile' => false, 'cleaned' => true, 'upemail' => false, 'campaign' => false ),
				'sources' => array( 'user' => true, 'admin' => true, 'api' => false ),
			)
		);
		return is_wp_error( $r ) ? $r : true;
	}

	public function test() {
		if ( ! $this->configured() ) {
			return new WP_Error( 'pl_nl_not_configured', 'Fill in the API key and the Audience ID first.' );
		}
		$r = $this->call( 'GET', '/lists/' . rawurlencode( $this->list ) . '?fields=name,stats.member_count' );
		if ( is_wp_error( $r ) ) {
			return $r;
		}
		return sprintf( '%s (%d contacts)', $r['name'] ?? 'audience', (int) ( $r['stats']['member_count'] ?? 0 ) );
	}
}
