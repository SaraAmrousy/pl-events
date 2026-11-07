<?php
/**
 * Newsletter links (Step 4.6): the ONE confirmation email the site sends, and the two pages behind its links.
 *
 * - Confirm:     /?pl_nl_act=confirm&r=ID&x=EXPIRES&s=SIG     (valid 7 days)
 * - Unsubscribe: /?pl_nl_act=unsubscribe&r=ID&s=SIG           (no expiry)
 * SIG = HMAC-SHA256 over action|id|email|expiry with a key derived from the site's salts, so a link can't be
 * guessed or edited. Opening a link shows one button; the change happens on the POST. That way mail
 * scanners that open every link (common at hospitals and universities) can't confirm or unsubscribe anyone.
 * Bad or expired links get a calm page that tells the person what to do — never "you did something wrong".
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_NL_Links {

	const TTL = 7 * DAY_IN_SECONDS;

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'route' ), 0 );
	}

	private static function sig( $act, $id, $email, $x = '' ) {
		return substr( hash_hmac( 'sha256', $act . '|' . (int) $id . '|' . strtolower( $email ) . '|' . $x, wp_salt( 'auth' ) . 'pl-newsletter' ), 0, 40 );
	}

	public static function confirm_url( $id, $now = null ) {
		$x = (int) ( $now ?? time() ) + self::TTL;
		return add_query_arg( array( 'pl_nl_act' => 'confirm', 'r' => (int) $id, 'x' => $x, 's' => self::sig( 'confirm', $id, PL_Newsletter::email( $id ), $x ) ), home_url( '/' ) );
	}

	public static function unsub_url( $id ) {
		return add_query_arg( array( 'pl_nl_act' => 'unsubscribe', 'r' => (int) $id, 's' => self::sig( 'unsubscribe', $id, PL_Newsletter::email( $id ) ) ), home_url( '/' ) );
	}

	/** @return int record ID, 0 = tampered/unknown, -1 = expired */
	public static function check( $act, array $q ) {
		$id = (int) ( $q['r'] ?? 0 );
		if ( ! $id || PL_Newsletter::CPT !== get_post_type( $id ) ) {
			return 0;
		}
		$x = 'confirm' === $act ? (string) (int) ( $q['x'] ?? 0 ) : '';
		if ( ! hash_equals( self::sig( $act, $id, PL_Newsletter::email( $id ), $x ), (string) ( $q['s'] ?? '' ) ) ) {
			return 0;
		}
		return ( 'confirm' === $act && (int) $x < time() ) ? -1 : $id;
	}

	/* ------------------------------------------------------------------ email */

	/** @return bool sent */
	public static function send_confirm( $id ) {
		$email = PL_Newsletter::email( $id );
		$name  = trim( (string) get_post_meta( $id, '_pl_nl_name', true ) );
		$url   = self::confirm_url( $id );
		$hi    = $name ? 'Hello ' . strtok( $name, ' ' ) . ',' : 'Hello,';
		$subj  = 'Confirm your subscription to PL Events updates';
		$intro = 'Please confirm that you’d like emails from PL Events about upcoming medical congresses.';
		$after = 'The link works for 7 days. If you didn’t ask for this, ignore this email — you won’t be added.';
		$html  = '<div style="font-family:Montserrat,Arial,sans-serif;font-size:16px;line-height:1.5;color:#0A0D18;max-width:560px">'
			. '<p>' . esc_html( $hi ) . '</p><p>' . esc_html( $intro ) . '</p>'
			. '<p style="margin:28px 0"><a href="' . esc_url( $url ) . '" style="display:inline-block;background:#2C4894;color:#ffffff;text-decoration:none;font-weight:700;padding:14px 24px;border-radius:999px">Confirm my subscription</a></p>'
			. '<p style="font-size:14px;color:#555">' . esc_html( $after ) . '</p>'
			. '<p style="font-size:14px;color:#555">Button not working? Copy this link into your browser:<br><a href="' . esc_url( $url ) . '" style="color:#2C4894;word-break:break-all">' . esc_html( $url ) . '</a></p>'
			. '<p style="font-size:14px;color:#555">— PL Events · <a href="' . esc_url( home_url( '/' ) ) . '" style="color:#2C4894">' . esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ) . '</a></p></div>';
		$text  = $hi . "\n\n" . $intro . "\n\nConfirm my subscription:\n" . $url . "\n\n" . $after . "\n\n— PL Events · " . home_url( '/' );
		$alt   = static function ( $m ) use ( $text ) {
			$m->AltBody = $text; // phpcs:ignore WordPress.NamingConventions.ValidVariableName -- PHPMailer property.
		};
		add_action( 'phpmailer_init', $alt );
		$ok = wp_mail( $email, $subj, $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
		remove_action( 'phpmailer_init', $alt );
		update_post_meta( $id, '_pl_nl_mailed', $ok ? gmdate( 'c' ) : '' );
		return (bool) $ok;
	}

	/* ------------------------------------------------------------------ pages */

	public static function route() {
		$act = isset( $_GET['pl_nl_act'] ) ? sanitize_key( wp_unslash( $_GET['pl_nl_act'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- signed link (HMAC), see check().
		if ( ! in_array( $act, array( 'confirm', 'unsubscribe' ), true ) ) {
			return;
		}
		nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'pl newsletter link' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- LiteSpeed's hook.
		$q    = array_map( 'sanitize_text_field', wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
		$id   = self::check( $act, $q );
		$post = ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] )
			|| ( 'confirm' === $act && apply_filters( 'pl_nl_one_click', false ) ); // true = the email link confirms directly (no button page)
		$form = static fn( $label ) => '<form method="post" action="' . esc_url( add_query_arg( array() ) ) . '" style="margin-top:28px"><button type="submit" class="btn bp">' . esc_html( $label ) . '</button></form>';

		if ( 'confirm' === $act ) {
			if ( $id <= 0 ) {
				self::page( $id < 0 ? 'This link has expired' : 'This link isn’t complete', '<p class="lead">Confirmation links work for 7 days' . ( $id < 0 ? ', and this one has run out' : ', and this one is missing a part — some email apps cut long links' ) . '. No problem: sign up again with the form at the bottom of this page and we’ll send you a fresh link.</p>' );
			}
			$email = PL_Newsletter::email( $id );
			$st    = PL_Newsletter::status( $id );
			if ( in_array( $st, array( PL_Newsletter::ST_UNSUB, PL_Newsletter::ST_BOUNCED ), true ) ) {
				self::page( 'This link was already used', '<p class="lead">' . esc_html( $email ) . ' isn’t subscribed at the moment. To get PL Events updates again, use the sign-up form at the bottom of this page.</p>' );
			}
			$done = in_array( $st, array( PL_Newsletter::ST_DONE, PL_Newsletter::ST_QUEUED, PL_Newsletter::ST_NODEST ), true );
			if ( $post && ! $done ) {
				$done = PL_Newsletter::confirm( $id );
			}
			if ( $done ) {
				self::page( 'You’re subscribed', '<p class="lead">Thank you — ' . esc_html( $email ) . ' will get emails from PL Events about upcoming congresses.</p><p class="mut">Changed your mind? <a href="' . esc_url( self::unsub_url( $id ) ) . '">Unsubscribe</a> at any time.</p>', 'confirmed' );
			}
			self::page( 'Confirm your subscription', '<p class="lead">One click to finish: confirm that ' . esc_html( $email ) . ' should get emails from PL Events about upcoming congresses.</p>' . $form( 'Confirm my subscription' ) );
		}

		// unsubscribe
		if ( $id <= 0 ) {
			self::page( 'This link isn’t complete', '<p class="lead">This unsubscribe link is missing a part — some email apps cut long links. Use the unsubscribe link at the bottom of any of our emails, or <a href="' . esc_url( class_exists( 'PL_Template' ) ? PL_Template::contact_url( 'other' ) : home_url( '/contact/' ) ) . '">write to us</a> and we’ll remove you.</p>' );
		}
		$email = PL_Newsletter::email( $id );
		$out   = in_array( PL_Newsletter::status( $id ), array( PL_Newsletter::ST_UNSUB, PL_Newsletter::ST_BOUNCED ), true );
		if ( $post && ! $out ) {
			$out = PL_Newsletter::opt_out( $email, 'site' );
		}
		if ( $out ) {
			self::page( 'You’re unsubscribed', '<p class="lead">' . esc_html( $email ) . ' won’t get PL Events updates any more, from any of our mailing lists.</p><p class="mut">Sorry to see you go. You can sign up again at any time with the form at the bottom of this page.</p>', 'unsubscribed' );
		}
		self::page( 'Unsubscribe from PL Events updates?', '<p class="lead">' . esc_html( $email ) . ' will stop getting PL Events emails, from all our mailing lists.</p>' . $form( 'Unsubscribe me' ) );
	}

	/** A plain page in the site's header and footer. Exits. */
	private static function page( $title, $body, $state = '' ) {
		global $wp_query;
		$wp_query->init(); // not the front page: the header stays solid (no "over the hero" mode)
		status_header( 200 );
		add_filter( 'pre_get_document_title', static fn() => $title . ' — PL Events', 99 );
		add_filter( 'wp_robots', 'wp_robots_no_robots' );
		add_filter( 'body_class', static fn( $c ) => array_merge( $c, array( 'pl-nl-page' ) ) );
		get_header();
		echo '<main id="content" class="pl site-main"><section class="sec"><div class="wrap pl-nl-done"' . ( $state ? ' data-state="' . esc_attr( $state ) . '"' : '' ) . '><h1 class="h2">' . esc_html( $title ) . '</h1>' . $body . '</div></section></main>'; // phpcs:ignore WordPress.Security.EscapeOutput -- every dynamic part of $body is escaped where it is built (route()).
		get_footer();
		exit;
	}
}
