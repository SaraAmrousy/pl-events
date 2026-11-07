<?php
/**
 * Zoho Campaigns API v1.1 (OAuth 2.0 "Self Client": client ID + secret + refresh token, made once from a
 * grant code). Called only after the person confirmed on the site. listsubscribe adds the contact at once
 * when the list has its signup form OFF; with the signup form on, Zoho sends its own confirmation email
 * (Zoho's developer docs, "Subscribe"). So the website uses a list with the signup form off; if Zoho still
 * answers "confirmation email sent", the admin page shows a warning.
 * Interests become Zoho tags; tags that fail are retried by cron.
 * Zoho has no notes API: the consent record is kept on the site (PL Website › Newsletter sign-ups).
 * Unsubscribes/bounces made in Zoho reach the site by webhook (Settings › Developer Space › Webhooks › Contacts).
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_NL_Zoho implements PL_NL_Provider {

	const DCS = array( 'com' => 'zoho.com (US)', 'eu' => 'zoho.eu (Europe)', 'in' => 'zoho.in (India)', 'com.au' => 'zoho.com.au (Australia)', 'jp' => 'zoho.jp (Japan)', 'sa' => 'zoho.sa (Saudi Arabia)' );

	private $dc;
	private $client_id;
	private $secret;
	private $refresh;
	private $list;

	public function __construct( array $s ) {
		$dc              = (string) ( $s['dc'] ?? 'com' );
		$this->dc        = isset( self::DCS[ $dc ] ) ? $dc : 'com';
		$this->client_id = trim( (string) ( $s['client_id'] ?? '' ) );
		$this->secret    = (string) ( $s['secret'] ?? '' );
		$this->refresh   = (string) ( $s['refresh'] ?? '' );
		$this->list      = trim( (string) ( $s['list'] ?? '' ) );
	}

	public function id() {
		return 'zoho';
	}

	public function name() {
		return 'Zoho Campaigns';
	}

	public function configured() {
		return '' !== $this->client_id && '' !== $this->secret && '' !== $this->refresh && '' !== $this->list;
	}

	private function accounts() {
		return apply_filters( 'pl_nl_endpoint', 'https://accounts.zoho.' . $this->dc, 'zoho-accounts' );
	}

	private function api() {
		return apply_filters( 'pl_nl_endpoint', 'https://campaigns.zoho.' . $this->dc . '/api/v1.1', 'zoho' );
	}

	/** Exchange a one-time grant code (API Console › Self Client) for a refresh token. */
	public static function exchange_grant( $dc, $client_id, $secret, $code ) {
		$p    = new self( array( 'dc' => $dc ) );
		$r    = wp_remote_post( $p->accounts() . '/oauth/v2/token', array( 'timeout' => 10, 'body' => array( 'grant_type' => 'authorization_code', 'client_id' => $client_id, 'client_secret' => $secret, 'code' => $code ) ) );
		$json = is_wp_error( $r ) ? null : json_decode( (string) wp_remote_retrieve_body( $r ), true );
		if ( empty( $json['refresh_token'] ) ) {
			return new WP_Error( 'pl_nl_zoho_grant', 'Zoho did not accept the code' . ( ! empty( $json['error'] ) ? ' (' . PL_Newsletter::scrub( (string) $json['error'] ) . ')' : '' ) . '. Codes expire after a few minutes — make a new one and paste it again.' );
		}
		return (string) $json['refresh_token'];
	}

	private function token() {
		$cache = 'pl_nl_zoho_token_' . md5( $this->client_id . $this->dc );
		$t     = get_transient( $cache );
		if ( $t ) {
			return $t;
		}
		$r    = wp_remote_post( $this->accounts() . '/oauth/v2/token', array( 'timeout' => 8, 'body' => array( 'grant_type' => 'refresh_token', 'client_id' => $this->client_id, 'client_secret' => $this->secret, 'refresh_token' => $this->refresh ) ) );
		$json = is_wp_error( $r ) ? null : json_decode( (string) wp_remote_retrieve_body( $r ), true );
		if ( empty( $json['access_token'] ) ) {
			return new WP_Error( 'pl_nl_zoho_token', 'Zoho sign-in failed' . ( ! empty( $json['error'] ) ? ' (' . PL_Newsletter::scrub( (string) $json['error'] ) . ')' : '' ) . '.', array( 'retry' => true ) );
		}
		set_transient( $cache, $json['access_token'], max( 300, (int) ( $json['expires_in'] ?? 3600 ) - 300 ) );
		return $json['access_token'];
	}

	private function call( $path, array $params, $method = 'POST' ) {
		$tok = $this->token();
		if ( is_wp_error( $tok ) ) {
			return $tok;
		}
		$url  = $this->api() . $path;
		$args = array( 'timeout' => 8, 'method' => $method, 'headers' => array( 'Authorization' => 'Zoho-oauthtoken ' . $tok ) );
		if ( 'GET' === $method ) {
			$url = add_query_arg( array_map( 'rawurlencode', $params ), $url );
		} else {
			$args['body'] = $params;
		}
		$r = wp_remote_request( $url, $args );
		if ( is_wp_error( $r ) ) {
			return new WP_Error( 'pl_nl_network', 'Zoho Campaigns could not be reached.', array( 'retry' => true ) );
		}
		$code = (int) wp_remote_retrieve_response_code( $r );
		$json = json_decode( (string) wp_remote_retrieve_body( $r ), true );
		$ok   = is_array( $json ) && ( 'success' === ( $json['status'] ?? '' ) || '0' === (string) ( $json['code'] ?? '' ) );
		if ( $code >= 200 && $code < 300 && $ok ) {
			return $json;
		}
		$msg = is_array( $json ) ? (string) ( $json['message'] ?? '' ) : '';
		return new WP_Error( 'pl_nl_zoho', 'Zoho ' . $code . ( $msg ? ' — ' . PL_Newsletter::scrub( $msg ) : '' ), array( 'retry' => 0 === $code || 429 === $code || $code >= 500 || 401 === $code ) );
	}

	public function subscribe( $email, $name, array $tags, array $consent ) {
		if ( ! $this->configured() ) {
			return new WP_Error( 'pl_nl_not_configured', 'Zoho Campaigns is not set up yet.', array( 'retry' => true ) );
		}
		$parts   = preg_split( '/\s+/', trim( (string) $name ), 2 );
		$contact = array( 'Contact Email' => $email );
		if ( '' !== trim( (string) $name ) ) {
			$contact['First Name'] = $parts[0];
			$contact['Last Name']  = $parts[1] ?? '';
		}
		$r = $this->call( '/json/listsubscribe', array( 'resfmt' => 'JSON', 'listkey' => $this->list, 'contactinfo' => wp_json_encode( $contact ), 'source' => 'PL Events website' ) );
		if ( is_wp_error( $r ) ) {
			return $r;
		}
		if ( false !== stripos( (string) ( $r['message'] ?? '' ), 'confirm' ) ) { // signup form on: Zoho sent its own confirmation
			update_option( 'pl_nl_zoho_doi', time(), false );
			return 'Zoho sent its own confirmation email (this list has its signup form on).';
		}
		return true;
	}

	public function unsubscribe( $email ) {
		if ( ! $this->configured() ) {
			return new WP_Error( 'pl_nl_not_configured', 'Zoho Campaigns is not set up yet.', array( 'retry' => true ) );
		}
		$r = $this->call( '/json/listunsubscribe', array( 'resfmt' => 'JSON', 'listkey' => $this->list, 'contactinfo' => wp_json_encode( array( 'Contact Email' => $email ) ) ) );
		if ( is_wp_error( $r ) && preg_match( '/not (found|exist|available)|invalid contact/i', $r->get_error_message() ) ) {
			return true; // not on the list
		}
		return is_wp_error( $r ) ? $r : true;
	}

	/**
	 * Tags in Zoho attach to existing contacts — i.e. after the person confirms. Returns the tags still pending.
	 */
	public function tag( $email, array $tags ) {
		$left = array();
		foreach ( $tags as $t ) {
			$this->call( '/tag/add', array( 'resfmt' => 'JSON', 'tagName' => $t ) ); // "already exists" is fine
			$r = $this->call( '/tag/associate', array( 'resfmt' => 'JSON', 'tagName' => $t, 'lead_email' => $email ) );
			if ( is_wp_error( $r ) ) {
				$left[] = $t;
			}
		}
		return $left;
	}

	public function test() {
		if ( ! $this->configured() ) {
			return new WP_Error( 'pl_nl_not_configured', 'Fill in the client ID, client secret, a grant code and the list key first.' );
		}
		$r = $this->call( '/getmailinglists', array( 'resfmt' => 'JSON', 'range' => '100' ), 'GET' );
		if ( is_wp_error( $r ) ) {
			return $r;
		}
		foreach ( (array) ( $r['list_of_details'] ?? array() ) as $l ) {
			if ( ( $l['listkey'] ?? '' ) === $this->list ) {
				return sprintf( '%s (%d contacts)', $l['listname'] ?? 'list', (int) ( $l['noofcontacts'] ?? 0 ) );
			}
		}
		return new WP_Error( 'pl_nl_zoho_list', 'Connected to Zoho, but the list key was not found in this account.' );
	}
}
