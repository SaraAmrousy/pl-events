<?php
/**
 * Newsletter sign-up (Step 4.6): footer + event-page forms and the Contact-form checkbox feed PL's existing
 * Zoho Campaigns AND Mailchimp lists (PL runs campaigns from both; "Both" is the normal setting).
 *
 * Flow: form → saved on the site (private post type pl_nl_signup, with its consent record) → ONE confirmation
 * email from the site (PL_NL_Links, signed link, 7 days) → on confirmation, added to every destination as a
 * confirmed contact with its tags. Unsubscribes stay in sync: a provider webhook (PL_NL_Hooks), the site's own
 * unsubscribe link or an admin removes the person everywhere. If a provider is down or not set up, an hourly
 * cron retries. Secrets are stored encrypted (not autoloaded) and are never printed back or logged.
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
		PL_NL_Links::init();
		PL_NL_Hooks::init();
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
	public static function providers( $only = '', $configured = false ) {
		$s   = self::live_settings();
		$all = array(
			'mailchimp' => new PL_NL_Mailchimp( $s['mailchimp'] ),
			'zoho'      => new PL_NL_Zoho( $s['zoho'] ),
		);
		if ( $only ) {
			return isset( $all[ $only ] ) ? array( $only => $all[ $only ] ) : array();
		}
		if ( $configured ) { // unsubscribes go everywhere we can reach, whatever the destination
			return array_filter( $all, fn( $p ) => $p->configured() );
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
		return sprintf( 'Consent via the PL Events website — signed up %s — confirmed by email %s — form: %s — page: %s — text: "%s"', $c['date'] ?? '', $c['confirmed'] ?? '', $c['form'] ?? '', $c['page'] ?? '', $c['text'] ?? '' );
	}

	/* ------------------------------------------------------------------ subscribe */

	const ST_AWAIT    = 'awaiting confirmation';
	const ST_MAILQ    = 'confirm email queued';
	const ST_QUEUED   = 'queued';
	const ST_NODEST   = 'queued: no destination set';
	const ST_DONE     = 'subscribed';
	const ST_UNSUB    = 'unsubscribed';
	const ST_BOUNCED  = 'bounced';
	const KEEP_UNCONF = 30; // days an unconfirmed sign-up is kept before it is deleted

	public static function email( $id ) {
		return (string) get_post_field( 'post_title', $id, 'raw' ); // not get_the_title(): it prefixes "Private:"
	}

	public static function status( $id ) {
		return (string) get_post_meta( $id, '_pl_nl_status', true );
	}

	private static function records( $email, $after = '' ) {
		$q = array( 'post_type' => self::CPT, 'post_status' => 'private', 'title' => $email, 'fields' => 'ids', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC' );
		if ( $after ) {
			$q['date_query'] = array( array( 'after' => $after ) );
		}
		return get_posts( $q );
	}

	/**
	 * Save the sign-up and send ONE confirmation email (site-side double opt-in). Nothing reaches Zoho or
	 * Mailchimp until the person clicks the link.
	 *
	 * @return array state: 'ok' | 'invalid'
	 */
	public static function subscribe( $email, $name, array $tags, array $consent ) {
		$email = sanitize_email( (string) $email );
		if ( ! is_email( $email ) ) {
			return array( 'state' => 'invalid' );
		}
		$tags = array_values( array_unique( array_filter( array_map( 'sanitize_title', $tags ) ) ) );
		if ( self::test_mode() ) {
			$tags[] = 'pl-staging-test';
		}
		$consent = wp_parse_args( $consent, array( 'date' => gmdate( 'c' ), 'page' => '', 'text' => self::CONSENT, 'form' => '' ) );

		// Anti-abuse: one confirmation email per address per 24 h; already subscribed = nothing to do.
		foreach ( self::records( $email ) as $rid ) {
			$st = self::status( $rid );
			if ( self::ST_DONE === $st || in_array( $st, array( self::ST_QUEUED, self::ST_NODEST ), true ) ) {
				return array( 'state' => 'ok', 'id' => $rid, 'repeat' => true );
			}
			if ( in_array( $st, array( self::ST_AWAIT, self::ST_MAILQ ), true ) && get_post_time( 'U', true, $rid ) > time() - DAY_IN_SECONDS ) {
				return array( 'state' => 'ok', 'id' => $rid, 'repeat' => true );
			}
		}

		$id = wp_insert_post( array( 'post_type' => self::CPT, 'post_status' => 'private', 'post_title' => $email ) );
		if ( ! $id || is_wp_error( $id ) ) {
			return array( 'state' => 'error' );
		}
		update_post_meta( $id, '_pl_nl_name', sanitize_text_field( (string) $name ) );
		update_post_meta( $id, '_pl_nl_tags', $tags );
		update_post_meta( $id, '_pl_nl_consent', $consent );
		update_post_meta( $id, '_pl_nl_tries', 0 );
		update_post_meta( $id, '_pl_nl_status', PL_NL_Links::send_confirm( $id ) ? self::ST_AWAIT : self::ST_MAILQ );
		return array( 'state' => 'ok', 'id' => $id );
	}

	/** The person clicked "Confirm my subscription": record the time, then add them to the destination(s). */
	public static function confirm( $id ) {
		$st = self::status( $id );
		if ( ! in_array( $st, array( self::ST_AWAIT, self::ST_MAILQ ), true ) ) {
			return in_array( $st, array( self::ST_DONE, self::ST_QUEUED, self::ST_NODEST ), true ); // already confirmed = fine
		}
		$c              = (array) get_post_meta( $id, '_pl_nl_consent', true );
		$c['confirmed'] = gmdate( 'c' );
		update_post_meta( $id, '_pl_nl_consent', $c );
		update_post_meta( $id, '_pl_nl_targets', array_keys( self::providers() ) );
		update_post_meta( $id, '_pl_nl_status', self::ST_QUEUED );
		self::deliver( $id );
		return true;
	}

	/** Send one confirmed sign-up to every target that has not taken it yet. @return bool all done */
	public static function deliver( $id ) {
		if ( ! in_array( self::status( $id ), array( self::ST_QUEUED, self::ST_NODEST ), true ) ) {
			return false; // unconfirmed, unsubscribed or already done
		}
		$email   = self::email( $id );
		$name    = (string) get_post_meta( $id, '_pl_nl_name', true );
		$tags    = (array) get_post_meta( $id, '_pl_nl_tags', true );
		$consent = (array) get_post_meta( $id, '_pl_nl_consent', true );
		$targets = (array) get_post_meta( $id, '_pl_nl_targets', true );
		if ( ! $targets ) { // no destination chosen yet: keep it, the retry picks it up once one is set
			$targets = array_keys( self::providers() );
			update_post_meta( $id, '_pl_nl_targets', $targets );
			if ( ! $targets ) {
				update_post_meta( $id, '_pl_nl_status', self::ST_NODEST );
				return false;
			}
		}
		$all = true;
		foreach ( self::providers() as $pid => $p ) {
			if ( ! in_array( $pid, $targets, true ) ) {
				continue;
			}
			$state = (string) get_post_meta( $id, '_pl_nl_' . $pid, true );
			if ( 'sent' === $state || 'failed' === $state ) {
				continue;
			}
			$r = $p->subscribe( $email, $name, $tags, $consent );
			if ( ! is_wp_error( $r ) ) {
				update_post_meta( $id, '_pl_nl_' . $pid, 'sent' );
				delete_post_meta( $id, '_pl_nl_' . $pid . '_error' );
				if ( is_string( $r ) ) {
					update_post_meta( $id, '_pl_nl_' . $pid . '_note', self::scrub( $r ) );
				}
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
		update_post_meta( $id, '_pl_nl_status', $all ? self::ST_DONE : self::ST_QUEUED );
		return $all;
	}

	/**
	 * Someone left — by the site's unsubscribe link, an admin, or a provider webhook ($via = 'site' | 'admin' |
	 * 'mailchimp' | 'zoho'). Mark every local record and unsubscribe them everywhere else. Idempotent, so a
	 * webhook echoing our own API call changes nothing.
	 */
	public static function opt_out( $email, $via, $kind = self::ST_UNSUB ) {
		$email = sanitize_email( (string) $email );
		if ( ! is_email( $email ) ) {
			return false;
		}
		$kind = self::ST_BOUNCED === $kind ? self::ST_BOUNCED : self::ST_UNSUB;
		$ids  = self::records( $email );
		$open = array_filter( $ids, fn( $i ) => ! in_array( self::status( $i ), array( self::ST_UNSUB, self::ST_BOUNCED ), true ) );
		if ( $ids && ! $open ) {
			return true; // already out — nothing to sync again
		}
		if ( ! $ids ) { // a contact PL added in Zoho/Mailchimp directly: keep a local trace so the other list follows
			$ids = array( wp_insert_post( array( 'post_type' => self::CPT, 'post_status' => 'private', 'post_title' => $email ) ) );
			update_post_meta( $ids[0], '_pl_nl_consent', array( 'form' => 'outside the website' ) );
		}
		$out = array( 'date' => gmdate( 'c' ), 'via' => $via, 'kind' => $kind );
		foreach ( $ids as $i ) {
			update_post_meta( $i, '_pl_nl_status', $kind );
			update_post_meta( $i, '_pl_nl_out', $out );
		}
		$todo = array();
		foreach ( self::providers( '', true ) as $pid => $p ) {
			if ( $pid !== $via ) {
				$todo[] = $pid;
			}
		}
		update_post_meta( $ids[0], '_pl_nl_unsub_todo', self::unsub_run( $email, $todo ) );
		return true;
	}

	/** @return string[] providers still to do (temporary failures) */
	private static function unsub_run( $email, array $todo ) {
		$left = array();
		foreach ( $todo as $pid ) {
			$ps = self::providers( $pid );
			if ( ! $ps ) {
				continue;
			}
			$r = reset( $ps )->unsubscribe( $email );
			if ( is_wp_error( $r ) ) {
				$d = $r->get_error_data();
				if ( ! empty( $d['retry'] ) ) {
					$left[] = $pid;
				}
			}
		}
		return $left;
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( self::CRON ) ) {
			wp_schedule_event( time() + 300, 'hourly', self::CRON );
		}
	}

	private static function q( array $meta, $after = '' ) {
		$q = array( 'post_type' => self::CPT, 'post_status' => 'private', 'numberposts' => 50, 'fields' => 'ids', 'meta_query' => $meta ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $after ) {
			$q['date_query'] = array( array( 'after' => $after ) );
		}
		return get_posts( $q );
	}

	/**
	 * Hourly: unsent confirmation emails, confirmed sign-ups not yet delivered (up to MAX_TRIES), Zoho tags,
	 * unsubscribes not yet synced, and clean-up of sign-ups never confirmed.
	 */
	public static function retry() {
		foreach ( self::q( array( array( 'key' => '_pl_nl_status', 'value' => self::ST_MAILQ ) ), '7 days ago' ) as $id ) {
			if ( PL_NL_Links::send_confirm( $id ) ) {
				update_post_meta( $id, '_pl_nl_status', self::ST_AWAIT );
			}
		}
		foreach ( self::q( array( array( 'key' => '_pl_nl_status', 'value' => array( self::ST_QUEUED, self::ST_NODEST ), 'compare' => 'IN' ) ) ) as $id ) {
			$tries = (int) get_post_meta( $id, '_pl_nl_tries', true );
			if ( $tries >= self::MAX_TRIES ) {
				update_post_meta( $id, '_pl_nl_status', 'failed after ' . $tries . ' tries' );
				continue;
			}
			update_post_meta( $id, '_pl_nl_tries', $tries + 1 );
			self::deliver( $id );
		}
		foreach ( self::q( array( array( 'key' => '_pl_nl_zoho_tags', 'value' => 'a:0:{}', 'compare' => '!=' ) ), '7 days ago' ) as $id ) {
			if ( self::ST_DONE === self::status( $id ) ) {
				$z = self::providers( 'zoho' );
				if ( $z ) {
					update_post_meta( $id, '_pl_nl_zoho_tags', reset( $z )->tag( self::email( $id ), (array) get_post_meta( $id, '_pl_nl_zoho_tags', true ) ) );
				}
			}
		}
		foreach ( self::q( array( array( 'key' => '_pl_nl_unsub_todo', 'value' => 'a:0:{}', 'compare' => '!=' ) ) ) as $id ) {
			update_post_meta( $id, '_pl_nl_unsub_todo', self::unsub_run( self::email( $id ), (array) get_post_meta( $id, '_pl_nl_unsub_todo', true ) ) );
		}
		$old = get_posts( array( 'post_type' => self::CPT, 'post_status' => 'private', 'numberposts' => 100, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_pl_nl_status', 'value' => array( self::ST_AWAIT, self::ST_MAILQ ), 'compare' => 'IN' ) ), 'date_query' => array( array( 'before' => self::KEEP_UNCONF . ' days ago' ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		foreach ( $old as $id ) {
			wp_delete_post( $id, true ); // never confirmed = no consent: don't keep the address
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
		$ip  = PL_Client_IP::get();
		$key = 'pl_nl_rate_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= (int) apply_filters( 'pl_nl_rate_limit', 5 ) ) {
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
		$r = self::subscribe( (string) ( $p['email'] ?? '' ), (string) ( $p['name'] ?? '' ), $tags, array( 'page' => $page, 'form' => $form ) );
		return array( 'state' => $r['state'] ); // never expose record IDs
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
