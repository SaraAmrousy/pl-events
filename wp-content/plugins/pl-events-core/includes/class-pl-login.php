<?php
/**
 * Login address + login rate limit (Round 4).
 *
 * - Sign-in lives at /plevents/ (constant PL_LOGIN_SLUG in wp-config.php changes it). /wp-login.php and
 *   /wp-admin/ answer 404 to visitors who aren't signed in; signed-in users use /wp-admin/ as usual.
 *   admin-ajax.php, admin-post.php, wp-cron.php, the REST API (incl. application passwords) and XML-RPC are
 *   untouched. Every link WordPress builds to wp-login.php (password reset email, logout, "session expired"
 *   box, password-protected pages) is rewritten to the new address, and WordPress' /login and /admin
 *   shortcuts are switched off so they can't reveal it.
 * - Rate limit: 5 wrong passwords from one IP → sign-in from that IP is paused for 15 minutes (counted from
 *   the last wrong try). Applies to the login form and XML-RPC. No CAPTCHA.
 * - Fail-open by design: if this plugin is ever switched off, WordPress' normal /wp-login.php comes back,
 *   so nobody is locked out of the site.
 *
 * Why our own code and not WPS Hide Login: see docs/Step-6.1b round 4 — same technique (WordPress' own
 * wp-login.php is served at the new address), but a real 404 instead of a redirect, the rate limit in the
 * same place, one plugin less to keep updated, and it is covered by tools/click-sweep.js.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Login {

	const MAX_FAILS = 5;
	const LOCK      = 15 * MINUTE_IN_SECONDS;

	private static $serving = false;

	public static function init() {
		if ( ( defined( 'WP_CLI' ) && WP_CLI ) || ! apply_filters( 'pl_login_enabled', true ) ) {
			return;
		}
		add_action( 'wp_loaded', array( __CLASS__, 'route' ), 1 );
		foreach ( array( 'site_url', 'network_site_url', 'wp_redirect' ) as $f ) {
			add_filter( $f, array( __CLASS__, 'swap' ), 99 );
		}
		remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
		add_filter( 'authenticate', array( __CLASS__, 'gate' ), 99, 2 );
		add_action( 'wp_login_failed', array( __CLASS__, 'failed' ) );
		add_action( 'wp_login', array( __CLASS__, 'passed' ) );
	}

	public static function slug() {
		return trim( defined( 'PL_LOGIN_SLUG' ) ? (string) PL_LOGIN_SLUG : 'plevents', '/' );
	}

	public static function url( $scheme = null ) {
		return home_url( '/' . self::slug() . '/', $scheme );
	}

	private static function path() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- compared only.
		return (string) wp_parse_url( $uri, PHP_URL_PATH );
	}

	public static function route() {
		global $pagenow;
		$path = self::path();
		$home = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
		if ( untrailingslashit( $path ) === $home . '/' . self::slug() ) {
			if ( '/' !== substr( $path, -1 ) ) { // /plevents → /plevents/
				$q = isset( $_SERVER['QUERY_STRING'] ) && '' !== $_SERVER['QUERY_STRING'] ? '?' . wp_unslash( $_SERVER['QUERY_STRING'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				wp_safe_redirect( self::url() . $q, 301 );
				exit;
			}
			self::serve();
		}
		$script = basename( isset( $_SERVER['SCRIPT_NAME'] ) ? (string) wp_unslash( $_SERVER['SCRIPT_NAME'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( 'wp-login.php' === $script || 'wp-login.php' === $pagenow ) {
			self::not_found();
		}
		if ( is_admin() && ! is_user_logged_in() && ! wp_doing_ajax() && ! wp_doing_cron() && 'admin-post.php' !== $pagenow ) {
			self::not_found();
		}
	}

	/** Run WordPress' own login screen at the new address. */
	private static function serve() {
		global $error, $interim_login, $action, $user_login, $pagenow; // wp-login.php reads these as globals
		self::$serving = true;
		$pagenow       = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride -- wp-login.php expects it.
		nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'pl login' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- LiteSpeed's hook.
		require_once ABSPATH . 'wp-login.php';
		exit;
	}

	private static function not_found() {
		status_header( 404 );
		nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'pl login 404' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		header( 'Content-Type: text/html; charset=utf-8' );
		echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Page not found — PL Events</title>'
			. '<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#FAFAF7;color:#0A0D18;font:16px/1.5 Montserrat,Arial,sans-serif}main{padding:32px;max-width:480px}h1{font-size:40px;line-height:1;letter-spacing:-.03em;margin:0 0 12px}a{color:#2C4894;font-weight:700}</style></head>'
			. '<body><main><h1>Page not found</h1><p>This page doesn’t exist. <a href="' . esc_url( home_url( '/' ) ) . '">Go to the PL Events homepage</a>.</p></main></body></html>';
		exit;
	}

	/** Any link to wp-login.php → the new address (keeps the query: action=rp&key=…, loggedout, etc.). */
	public static function swap( $url ) {
		if ( ! is_string( $url ) || false === strpos( $url, 'wp-login.php' ) ) {
			return $url;
		}
		$parts = explode( '?', $url, 2 );
		if ( ! preg_match( '#(^|/)wp-login\.php$#', $parts[0] ) ) {
			return $url;
		}
		return self::url( is_ssl() || force_ssl_admin() ? 'https' : null ) . ( isset( $parts[1] ) ? '?' . $parts[1] : '' );
	}

	/* ------------------------------------------------------------------ rate limit */

	private static function key() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return 'pl_login_f_' . md5( $ip );
	}

	public static function locked() {
		return (int) get_transient( self::key() ) >= self::MAX_FAILS;
	}

	/** After WordPress checked the password: a paused IP gets the pause message even with the right one. */
	public static function gate( $user, $username ) {
		if ( '' !== (string) $username && self::locked() ) {
			return new WP_Error( 'pl_login_paused', '<strong>Sign-in is paused for 15 minutes</strong> after several wrong passwords from this connection. It’s a safety measure, nothing is wrong with your account. Try again later, or use “Lost your password?”.' );
		}
		return $user;
	}

	public static function failed() {
		if ( self::locked() ) {
			return; // already paused: don't extend the pause forever
		}
		set_transient( self::key(), (int) get_transient( self::key() ) + 1, self::LOCK );
	}

	public static function passed() {
		delete_transient( self::key() );
	}
}
