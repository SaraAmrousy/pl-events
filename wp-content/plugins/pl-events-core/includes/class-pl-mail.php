<?php
/**
 * Every email the site sends speaks as the team, never as a person (6.1b round 7):
 *  - From name "PL Events Team" (set last on PHPMailer, so neither WordPress' "WordPress" default
 *    nor a mail plugin's setting can put a person's name there);
 *  - sign-off "— PL Events Team": WordPress' own "Regards, All at …" blocks are replaced, plain
 *    messages without a sign-off get one, and our HTML emails carry it already;
 *  - WordPress' password-reset / new-user / changed-password texts rewritten short and plain.
 * The staging mail safety net (deploy/mu/pl-staging.php) still sends everything to the site owner.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Mail {

	const FROM_NAME = 'PL Events Team';
	const SIGN      = '— PL Events Team';

	public static function init() {
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'from_name' ), 99 );
		add_action( 'phpmailer_init', array( __CLASS__, 'phpmailer' ), PHP_INT_MAX );
		add_filter( 'wp_mail', array( __CLASS__, 'sign' ), 99 );
		add_filter( 'retrieve_password_notification_email', array( __CLASS__, 'reset_email' ), 10, 4 );
		add_filter( 'wp_new_user_notification_email', array( __CLASS__, 'new_user_email' ), 10, 3 );
	}

	public static function from_name() {
		return self::FROM_NAME;
	}

	public static function phpmailer( $m ) {
		$m->FromName = self::FROM_NAME; // phpcs:ignore WordPress.NamingConventions.ValidVariableName -- PHPMailer property.
	}

	/** WordPress' "Regards, All at Site / URL" → the team sign-off; unsigned plain texts get it. */
	public static function sign( $args ) {
		$msg = (string) ( $args['message'] ?? '' );
		if ( '' === $msg || false !== strpos( $msg, self::SIGN ) ) {
			return $args;
		}
		$html = false !== stripos( implode( "\n", (array) ( $args['headers'] ?? array() ) ), 'text/html' ) || preg_match( '/<(p|div|br|table)\b/i', $msg );
		$new  = preg_replace( '/\n*(Regards|Thanks|Best regards),?\s*\n\s*(All at|The)\s[^\n]*(\n\s*https?:\/\/\S+)?\s*$/i', "\n\n" . self::SIGN, $msg, 1, $n );
		if ( $n ) {
			$args['message'] = $new;
		} elseif ( $html ) {
			$args['message'] = preg_match( '/<\/body>/i', $msg ) ? preg_replace( '/<\/body>/i', '<p>' . esc_html( self::SIGN ) . '</p></body>', $msg, 1 ) : $msg . '<p>' . esc_html( self::SIGN ) . '</p>';
		} else {
			$args['message'] = rtrim( $msg ) . "\n\n" . self::SIGN . "\n";
		}
		return $args;
	}

	/** The sign-in address (/plevents/, PL_Login) with WordPress' reset parameters. */
	private static function rp_url( $key, $login ) {
		$base = class_exists( 'PL_Login' ) ? PL_Login::url( 'login' ) : network_site_url( 'wp-login.php', 'login' );
		return add_query_arg( array( 'action' => 'rp', 'key' => $key, 'login' => rawurlencode( $login ) ), $base );
	}

	public static function reset_email( $defaults, $key, $user_login, $user ) {
		$url                 = self::rp_url( $key, $user_login );
		$defaults['message'] = "Hello,\n\nSomeone asked to reset the password of the PL Events website account \"" . $user_login . "\".\n\n"
			. "To choose a new password, open this link (it works once, for a short time):\n" . $url . "\n\n"
			. "If you didn't ask for this, ignore this email: nothing changes.\n\n" . self::SIGN . "\n";
		return $defaults;
	}

	public static function new_user_email( $email, $user, $blogname ) {
		$key = get_password_reset_key( $user );
		if ( is_wp_error( $key ) ) {
			return $email;
		}
		$url              = self::rp_url( $key, $user->user_login );
		$email['message'] = "Hello,\n\nAn account was made for you on the PL Events website.\n\nUsername: " . $user->user_login . "\n\n"
			. "To set your password, open this link:\n" . $url . "\n\n"
			. 'Then sign in at ' . ( class_exists( 'PL_Login' ) ? PL_Login::url() : wp_login_url() ) . " (bookmark it).\n\n" . self::SIGN . "\n";
		return $email;
	}
}
