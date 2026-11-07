<?php
/**
 * Mailchimp Marketing API v3 — double opt-in via status_if_new = "pending" (Mailchimp sends the
 * confirmation email). An existing member keeps their status: someone who unsubscribed is NOT re-added.
 * Tags = Mailchimp tags (segment on them in Audience › Tags). The consent record is added as a member note.
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
			'status_if_new' => 'pending', // double opt-in
		);
		if ( '' !== trim( (string) $name ) ) {
			$body['merge_fields'] = array( 'FNAME' => $parts[0], 'LNAME' => $parts[1] ?? '' );
		}
		$r = $this->call( 'PUT', '/lists/' . rawurlencode( $this->list ) . '/members/' . $hash, $body );
		if ( is_wp_error( $r ) ) {
			return $r;
		}
		if ( $tags ) {
			$this->call( 'POST', '/lists/' . rawurlencode( $this->list ) . '/members/' . $hash . '/tags', array( 'tags' => array_map( fn( $t ) => array( 'name' => $t, 'status' => 'active' ), array_values( $tags ) ) ) );
		}
		$this->call( 'POST', '/lists/' . rawurlencode( $this->list ) . '/members/' . $hash . '/notes', array( 'note' => PL_Newsletter::consent_note( $consent ) ) );
		return true;
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
