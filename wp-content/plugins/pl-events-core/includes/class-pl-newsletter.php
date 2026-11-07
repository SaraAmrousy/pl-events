<?php
/**
 * Newsletter sign-up (Step 4.6): footer + event-page forms and the Contact-form checkbox feed PL's existing
 * Zoho Campaigns and/or Mailchimp lists, always with double opt-in.
 *
 * Every sign-up is first saved on the site (private post type pl_nl_signup) with its consent record, then sent.
 * If a provider is down or not set up, the sign-up stays queued and an hourly cron retries it — the visitor's
 * form never breaks. Secrets are stored encrypted (not autoloaded) and are never printed back or logged.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Newsletter {

	const OPT        = 'pl_newsletter';
	const CPT        = 'pl_nl_signup';
	const CRON       = 'pl_nl_retry';
	const MAX_TRIES  = 8;
	const CONSENT    = 'Yes, email me about upcoming PL Events congresses. I’ll confirm by email and can unsubscribe at any time.';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'rest' ) );
		add_action( 'admin_post_nopriv_pl_subscribe', array( __CLASS__, 'post_fallback' ) );
		add_action( 'admin_post_pl_subscribe', array( __CLASS__, 'post_fallback' ) );
		add_action( self::CRON, array( __CLASS__, 'retry' ) );
		add_action( 'init', array( __CLASS__, 'schedule' ) );
		add_shortcode( 'pl_newsletter', array( __CLASS__, 'shortcode' ) );
		add_action( 'forminator_custom_form_submit_before_set_fields', array( __CLASS__, 'from_contact_form' ), 10, 3 );
		if ( is_admin() ) {
			PL_Newsletter_Admin::init();
		}
	}

	/* ------------------------------------------------------------------ settings */

	public static function settings() {
		$s = get_option( self::OPT, array() );
		$s = is_array( $s ) ? $s : array();
		return array_replace_recursive(
			array(
				'destination' => '',
				'mailchimp'   => array( 'key' => '', 'list' => '', 'dc' => '' ),
				'zoho'        => array( 'dc' => 'com', 'client_id' => '', 'secret' => '', 'refresh' => '', 'list' => '' ),
			),
			$s
		);
	}

	/** Settings with secrets decrypted — for the providers only, never for output. */
	private static function live_settings() {
		$s                       = self::settings();
		$s['mailchimp']['key']   = PL_Secret::open( $s['mailchimp']['key'] );
		$s['zoho']['secret']     = PL_Secret::open( $s['zoho']['secret'] );
		$s['zoho']['refresh']    = PL_Secret::open( $s['zoho']['refresh'] );
		return $s;
	}

	/** @return PL_NL_Provider[] keyed by id, for the chosen destination. */
	public static function providers( $only = '' ) {
		$s   = self::live_settings();
		$all = array(
			'mailchimp' => new PL_NL_Mailchimp( $s['mailchimp'] ),
			'zoho'      => new PL_NL_Zoho( $s['zoho'] ),
		);
		if ( $only ) {
			return isset( $all[ $only ] ) ? array( $only => $all[ $only ] ) : array();
		}
		$d = $s['destination'];
		return 'both' === $d ? $all : ( isset( $all[ $d ] ) ? array( $d => $all[ $d ] ) : array() );
	}

	/** Remove anything key-like from provider messages before they are stored or shown. */
	public static function scrub( $text ) {
		$text = preg_replace( '/[A-Za-z0-9]{24,}(-us\d+)?/', '[redacted]', (string) $text );
		return mb_substr( wp_strip_all_tags( $text ), 0, 200 );
	}

	public static function test_mode() {
		return defined( 'PL_NL_TEST_ONLY' ) && PL_NL_TEST_ONLY; // staging mu-plugin: test accounts only
	}

	/* ------------------------------------------------------------------ storage */

	public static function register() {
		register_post_type(
			self::CPT,
			array(
				'labels'          => array( 'name' => 'Newsletter sign-ups', 'singular_name' => 'Newsletter sign-up', 'menu_name' => 'Newsletter sign-ups' ),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'pl-website',
				'show_in_rest'    => false,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow', 'edit_posts' => 'manage_options', 'edit_others_posts' => 'manage_options', 'delete_posts' => 'manage_options', 'delete_others_posts' => 'manage_options', 'read_private_posts' => 'manage_options', 'publish_posts' => 'manage_options' ),
				'map_meta_cap'    => true,
			)
		);
	}

	public static function consent_note( array $c ) {
		return sprintf( 'Consent via the PL Events website — %s — form: %s — page: %s — text: "%s"', $c['date'] ?? '', $c['form'] ?? '', $c['page'] ?? '', $c['text'] ?? '' );
	}

	/* ------------------------------------------------------------------ subscribe */

	/**
	 * @return array state: 'ok' (sent to every destination) | 'queued' (saved; will retry) | 'invalid' | 'limited'
	 */
	public static function subscribe( $email, $name, array $tags, array $consent ) {
		$email = sanitize_email( (string) $email );
		if ( ! is_email( $email ) ) {
			return array( 'state' => 'invalid' );
		}
		$tags   = array_values( array_unique( array_filter( array_map( 'sanitize_title', $tags ) ) ) );
		if ( self::test_mode() ) {
			$tags[] = 'pl-staging-test';
		}
		$consent = wp_parse_args( $consent, array( 'date' => gmdate( 'c' ), 'page' => '', 'text' => self::CONSENT, 'form' => '' ) );

		// The same address within 24 h: do not trigger another confirmation email (anti-abuse).
		$recent = get_posts( array( 'post_type' => self::CPT, 'post_status' => 'private', 'title' => $email, 'date_query' => array( array( 'after' => '24 hours ago' ) ), 'fields' => 'ids', 'numberposts' => 1 ) );
		if ( $recent ) {
			return array( 'state' => 'ok', 'id' => $recent[0], 'repeat' => true );
		}

		$id = wp_insert_post( array( 'post_type' => self::CPT, 'post_status' => 'private', 'post_title' => $email ) );
		if ( ! $id || is_wp_error( $id ) ) {
			return array( 'state' => 'queued' );
		}
		update_post_meta( $id, '_pl_nl_name', sanitize_text_field( (string) $name ) );
		update_post_meta( $id, '_pl_nl_tags', $tags );
		update_post_meta( $id, '_pl_nl_consent', $consent );
		update_post_meta( $id, '_pl_nl_tries', 0 );
		$dest = array_keys( self::providers() );
		update_post_meta( $id, '_pl_nl_targets', $dest );
		$done = self::deliver( $id );
		return array( 'state' => $done ? 'ok' : 'queued', 'id' => $id );
	}

	/** Send one sign-up to every target that has not taken it yet. @return bool all done */
	public static function deliver( $id ) {
		$email   = (string) get_post_field( 'post_title', $id, 'raw' ); // not get_the_title(): it prefixes "Private:"
		$name    = (string) get_post_meta( $id, '_pl_nl_name', true );
		$tags    = (array) get_post_meta( $id, '_pl_nl_tags', true );
		$consent = (array) get_post_meta( $id, '_pl_nl_consent', true );
		$targets = (array) get_post_meta( $id, '_pl_nl_targets', true );
		if ( ! $targets ) { // no destination chosen yet: keep it, the retry picks it up once one is set
			$targets = array_keys( self::providers() );
			update_post_meta( $id, '_pl_nl_targets', $targets );
			if ( ! $targets ) {
				update_post_meta( $id, '_pl_nl_status', 'queued: no destination set' );
				return false;
			}
		}
		$all = true;
		foreach ( self::providers() as $pid => $p ) {
			if ( ! in_array( $pid, $targets, true ) ) {
				continue;
			}
			$state = (string) get_post_meta( $id, '_pl_nl_' . $pid, true );
			if ( 'sent' === $state ) {
				if ( 'zoho' === $pid && get_post_meta( $id, '_pl_nl_zoho_tags', true ) ) { // Zoho tags wait for the confirmation
					$left = $p->tag( $email, (array) get_post_meta( $id, '_pl_nl_zoho_tags', true ) );
					update_post_meta( $id, '_pl_nl_zoho_tags', $left );
				}
				continue;
			}
			if ( 'failed' === $state ) {
				continue;
			}
			$r = $p->subscribe( $email, $name, $tags, $consent );
			if ( true === $r ) {
				update_post_meta( $id, '_pl_nl_' . $pid, 'sent' );
				if ( 'zoho' === $pid && $tags ) {
					update_post_meta( $id, '_pl_nl_zoho_tags', $p->tag( $email, $tags ) );
				}
			} else {
				$data  = $r->get_error_data();
				$retry = ! empty( $data['retry'] );
				update_post_meta( $id, '_pl_nl_' . $pid, $retry ? 'retry' : 'failed' );
				update_post_meta( $id, '_pl_nl_' . $pid . '_error', self::scrub( $r->get_error_message() ) );
				$all = $all && ! $retry;
			}
		}
		update_post_meta( $id, '_pl_nl_status', $all ? 'done' : 'queued' );
		return $all;
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( self::CRON ) ) {
			wp_schedule_event( time() + 300, 'hourly', self::CRON );
		}
	}

	/** Hourly: retry queued sign-ups (up to MAX_TRIES), and Zoho tags waiting for a confirmation (up to 7 days). */
	public static function retry() {
		$q = get_posts(
			array(
				'post_type'   => self::CPT,
				'post_status' => 'private',
				'numberposts' => 50,
				'fields'      => 'ids',
				'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					'relation' => 'OR',
					array( 'key' => '_pl_nl_status', 'value' => 'queued', 'compare' => 'LIKE' ),
					array( 'key' => '_pl_nl_zoho_tags', 'value' => 'a:0:{}', 'compare' => '!=' ),
				),
				'date_query'  => array( array( 'after' => '7 days ago' ) ),
			)
		);
		foreach ( $q as $id ) {
			$tries = (int) get_post_meta( $id, '_pl_nl_tries', true );
			if ( $tries >= self::MAX_TRIES && 'done' !== get_post_meta( $id, '_pl_nl_status', true ) ) {
				update_post_meta( $id, '_pl_nl_status', 'failed after ' . $tries . ' tries' );
				continue;
			}
			update_post_meta( $id, '_pl_nl_tries', $tries + 1 );
			self::deliver( $id );
		}
	}

	/* ------------------------------------------------------------------ entry points */

	public static function rest() {
		register_rest_route(
			'pl/v1',
			'/subscribe',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true', // public form; guarded by same-origin, honeypot and a rate limit
				'callback'            => function ( WP_REST_Request $r ) {
					return rest_ensure_response( self::handle( $r->get_params() ) );
				},
			)
		);
	}

	/** Shared by REST (JS) and admin-post (no JS). */
	public static function handle( array $p ) {
		if ( ! self::same_origin() ) {
			return array( 'state' => 'error' );
		}
		if ( ! empty( $p['pl_hp'] ) ) {
			return array( 'state' => 'ok' ); // honeypot filled: look successful, do nothing
		}
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'pl_nl_rate_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= 5 ) {
			return array( 'state' => 'limited' );
		}
		set_transient( $key, $n + 1, HOUR_IN_SECONDS );

		$tags = array();
		foreach ( array( 'specialty', 'country', 'series' ) as $k ) {
			foreach ( (array) ( $p[ $k ] ?? array() ) as $v ) {
				$v = sanitize_text_field( (string) $v );
				if ( '' !== $v ) {
					$tags[] = $k . '-' . $v;
				}
			}
		}
		$form   = sanitize_key( (string) ( $p['form'] ?? 'footer' ) );
		$tags[] = 'source-' . $form;
		$page   = esc_url_raw( (string) ( $p['page'] ?? wp_get_referer() ) );
		return self::subscribe( (string) ( $p['email'] ?? '' ), (string) ( $p['name'] ?? '' ), $tags, array( 'page' => $page, 'form' => $form ) );
	}

	private static function same_origin() {
		$src = isset( $_SERVER['HTTP_ORIGIN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : ( isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '' );
		return '' !== $src && wp_parse_url( $src, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST );
	}

	public static function post_fallback() {
		$res  = self::handle( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput -- public form on cached pages; same-origin + honeypot + rate limit; each field is sanitized in handle().
		$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		wp_safe_redirect( add_query_arg( 'pl_nl', in_array( $res['state'], array( 'ok', 'queued' ), true ) ? 'ok' : $res['state'], remove_query_arg( 'pl_nl', $back ) ) . '#pl-nl' );
		exit;
	}

	/** Contact form: only when the (unticked by default) updates checkbox is ticked. */
	public static function from_contact_form( $entry, $form_id, $fields ) {
		$ids = (array) get_option( 'plt_forms', array() );
		if ( (int) $form_id !== (int) ( $ids['contact'] ?? 0 ) ) {
			return;
		}
		$v = array();
		foreach ( (array) $fields as $f ) {
			$v[ $f['name'] ?? '' ] = $f['value'] ?? '';
		}
		$opt = $v['checkbox-1'] ?? '';
		$on  = is_array( $opt ) ? in_array( 'updates', $opt, true ) : ( false !== strpos( (string) $opt, 'updates' ) || false !== stripos( (string) $opt, 'Send me updates' ) );
		if ( ! $on ) {
			return;
		}
		$name = $v['name-1'] ?? '';
		$name = is_array( $name ) ? implode( ' ', array_filter( $name ) ) : (string) $name;
		self::subscribe( (string) ( $v['email-1'] ?? '' ), $name, array( 'source-contact' ), array( 'form' => 'contact', 'page' => esc_url_raw( (string) wp_get_referer() ), 'text' => 'Send me updates about upcoming PL congresses' ) );
	}

	/* ------------------------------------------------------------------ form */

	/**
	 * [pl_newsletter context="footer|event" series="DEOSS" specialties="Ophthalmology" country="TN"]
	 */
	public static function shortcode( $atts ) {
		$a = shortcode_atts( array( 'context' => 'footer', 'series' => '', 'specialties' => '', 'country' => '', 'title' => '' ), $atts, 'pl_newsletter' );
		return PL_Template::render( 'newsletter-form', array( 'a' => $a ) );
	}
}
