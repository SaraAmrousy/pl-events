<?php
/**
 * Guardrails (Step 6.2b): stop the mistakes that would make the site wrong, warn about the ones
 * that might be, and quietly fix the ones a machine can fix. Messages are short, never blame,
 * and always say how to fix it.
 *
 *  Blocks Publish/Update   Registration "Open" without a link · End date before Start date
 *                          · removing the only Head office · a link that isn't a web address
 *  Warns (yellow)          the event has ended but Registration is still "Open" · a phone number
 *                          that looks too short/long for its country code · an http:// link
 *  Fixes on save           links without https:// get it · phone spaces/dashes/brackets removed,
 *                          a code typed into the number moves to "Country code"
 *                          · a new Head office switches the old one off (only one head office)
 *
 * The browser half (live hints while typing) is assets/js/pl-guard.js. The server half below runs
 * whatever the browser does, so the rules hold for imports and for no-JS too.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Guardrails {

	/** Rough length of the national number (after the code, without the leading 0) per code. */
	const PHONE_LENGTHS = array(
		'20'  => array( 8, 10 ),  // Egypt: Alexandria 3 xxx xxxx … mobiles 1x xxxx xxxx
		'971' => array( 8, 9 ),   // UAE
		'966' => array( 8, 9 ),   // Saudi Arabia
		'216' => array( 8, 8 ),   // Tunisia
		'964' => array( 8, 10 ),  // Iraq
		'962' => array( 8, 9 ),   // Jordan
		'961' => array( 7, 8 ),   // Lebanon
		'965' => array( 8, 8 ),   // Kuwait
		'974' => array( 8, 8 ),   // Qatar
		'973' => array( 8, 8 ),   // Bahrain
		'968' => array( 8, 8 ),   // Oman
		'212' => array( 9, 9 ),   // Morocco
		'213' => array( 8, 9 ),   // Algeria
		'218' => array( 8, 9 ),   // Libya
		'249' => array( 9, 9 ),   // Sudan
		'44'  => array( 9, 10 ),  // UK
		'1'   => array( 10, 10 ), // US/Canada
	);

	const TYPES = array( 'event', 'pl_office' );

	public static function init() {
		add_filter( 'acf/validate_value/type=url', array( __CLASS__, 'validate_url' ), 20, 4 );
		add_filter( 'acf/update_value/type=url', array( __CLASS__, 'save_url' ), 5, 3 );
		add_filter( 'acf/validate_save_post', array( __CLASS__, 'validate_office' ), 10, 0 );
		add_action( 'acf/save_post', array( __CLASS__, 'after_save' ), 20 );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_action( 'wp_trash_post', array( __CLASS__, 'guard_trash' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/* ------------------------------------------------------------------ links */

	/** "www.deoss.org" → "https://www.deoss.org"; "//x.org" → "https://x.org". Empty stays empty. */
	public static function normalize_url( $v ) {
		$v = trim( (string) $v );
		if ( '' === $v ) {
			return '';
		}
		if ( 0 === strpos( $v, '//' ) ) {
			return 'https:' . $v;
		}
		if ( ! preg_match( '#^[a-z][a-z0-9+.-]*:#i', $v ) && preg_match( '#^[^\s/]+\.[a-z]{2,}([/?\#:].*)?$#i', $v ) ) {
			return 'https://' . $v;
		}
		return $v;
	}

	/** A real web address: http(s)://host.tld… (no "#", no spaces). */
	public static function is_web_link( $v ) {
		$v = (string) $v;
		if ( '' === $v || preg_match( '/\s/', $v ) ) {
			return false;
		}
		$p = wp_parse_url( $v );
		return is_array( $p ) && in_array( strtolower( $p['scheme'] ?? '' ), array( 'http', 'https' ), true )
			&& ! empty( $p['host'] ) && (bool) preg_match( '/^[a-z0-9-]+(\.[a-z0-9-]+)*\.[a-z]{2,}$/i', $p['host'] );
	}

	private static function ours( $field ) {
		return 0 === strpos( (string) ( $field['key'] ?? '' ), 'field_pl_' );
	}

	public static function validate_url( $valid, $value, $field, $input ) {
		if ( ! self::ours( $field ) || '' === trim( (string) $value ) || ! is_string( $value ) ) {
			return $valid;
		}
		return self::is_web_link( self::normalize_url( $value ) )
			? true
			: 'This doesn’t look like a web address. Paste the full link from the browser’s address bar, e.g. https://www.example.org';
	}

	public static function save_url( $value, $post_id, $field ) {
		return self::ours( $field ) && is_string( $value ) ? self::normalize_url( $value ) : $value;
	}

	/* ----------------------------------------------------------------- phones */

	/** Dial codes we know, longest first ("971" before "97"). */
	private static function codes() {
		static $codes = null;
		if ( null === $codes ) {
			$codes = array_map( fn( $c ) => ltrim( $c, '+' ), array_keys( PL_Geo::dial_choices() ) );
			usort( $codes, fn( $a, $b ) => strlen( $b ) - strlen( $a ) );
		}
		return $codes;
	}

	/**
	 * Clean one phone: returns [ code "+20", national digits, changed-the-code? ].
	 * "+971 55-158 2689" typed with code +20 → [ "+971", "551582689", true ].
	 */
	public static function clean_phone( $code, $number ) {
		$code   = '+' . preg_replace( '/\D/', '', (string) $code );
		$raw    = trim( (string) $number );
		$intl   = (bool) preg_match( '/^(\+|00)/', $raw );
		$digits = preg_replace( '/\D/', '', $raw );
		$moved  = false;
		if ( $intl ) {
			$digits = preg_replace( '/^00/', '', $digits );
			foreach ( self::codes() as $c ) {
				if ( 0 === strpos( $digits, $c ) ) {
					$moved  = '+' . $c !== $code;
					$code   = '+' . $c;
					$digits = substr( $digits, strlen( $c ) );
					break;
				}
			}
		} else {
			$cd  = ltrim( $code, '+' );
			$len = self::PHONE_LENGTHS[ $cd ] ?? array( 6, 12 );
			if ( '' !== $cd && 0 === strpos( $digits, $cd ) && strlen( $digits ) > $len[1] && strlen( $digits ) - strlen( $cd ) >= $len[0] ) {
				$digits = substr( $digits, strlen( $cd ) ); // the code typed twice ("20 102…" with +20).
			}
		}
		return array( $code, ltrim( $digits, '0' ), $moved );
	}

	/** null = fine, else a short English hint with the fix. */
	public static function phone_issue( $code, $digits ) {
		$cd = ltrim( (string) $code, '+' );
		$n  = strlen( (string) $digits );
		if ( 0 === $n ) {
			return null;
		}
		list( $min, $max ) = self::PHONE_LENGTHS[ $cd ] ?? array( 6, 12 );
		if ( $n < $min ) {
			return sprintf( 'looks short for %s (%d digits after the code). Check it, or choose the right country code.', '+' . $cd, $n );
		}
		if ( $n > $max ) {
			return sprintf( 'looks long for %s (%d digits after the code). Is it from another country? Choose its code in "Country code".', '+' . $cd, $n );
		}
		return null;
	}

	/** Stored phone rows of a post: [ i => [ code, number, label ] ]. */
	private static function phone_rows( $post_id ) {
		$rows = array();
		$n    = (int) get_post_meta( $post_id, 'pl_phones', true );
		for ( $i = 0; $i < $n; $i++ ) {
			$rows[ $i ] = array(
				'code'   => (string) get_post_meta( $post_id, "pl_phones_{$i}_code", true ),
				'number' => (string) get_post_meta( $post_id, "pl_phones_{$i}_number", true ),
				'label'  => (string) get_post_meta( $post_id, "pl_phones_{$i}_label", true ),
			);
		}
		return $rows;
	}

	/** Fix every phone of an event/office in place. Returns the notes for the editor. */
	public static function fix_phones( $post_id ) {
		$notes = array();
		foreach ( self::phone_rows( $post_id ) as $i => $r ) {
			if ( '' === trim( $r['number'] ) ) {
				continue;
			}
			list( $code, $digits, $moved ) = self::clean_phone( '' !== $r['code'] ? $r['code'] : '+20', $r['number'] );
			if ( $digits !== $r['number'] ) {
				update_post_meta( $post_id, "pl_phones_{$i}_number", $digits );
			}
			if ( $code !== $r['code'] && isset( PL_Geo::dial_choices()[ $code ] ) ) {
				update_post_meta( $post_id, "pl_phones_{$i}_code", $code );
			}
			if ( $moved ) {
				$notes[] = sprintf( 'Phone %d: the number started with %s, so "Country code" is now %s.', $i + 1, $code, $code );
			}
		}
		return $notes;
	}

	public static function phone_warnings( $post_id ) {
		$out = array();
		foreach ( self::phone_rows( $post_id ) as $i => $r ) {
			$issue = self::phone_issue( $r['code'], preg_replace( '/\D/', '', $r['number'] ) );
			if ( $issue ) {
				$out[] = sprintf( 'Phone %d (%s %s) %s', $i + 1, esc_html( $r['code'] ), esc_html( PL_Phones::group( preg_replace( '/\D/', '', $r['number'] ) ) ), $issue );
			}
		}
		return $out;
	}

	/* ------------------------------------------------------------ head office */

	/** Published offices with Head office on (IDs), optionally ignoring one. */
	public static function head_offices( $except = 0 ) {
		$ids = get_posts(
			array(
				'post_type'   => 'pl_office',
				'post_status' => 'publish',
				'numberposts' => -1,
				'fields'      => 'ids',
				'meta_key'    => 'pl_head_office', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'  => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		return array_values( array_diff( array_map( 'intval', $ids ), array( (int) $except ) ) );
	}

	public static function validate_office() {
		$in  = isset( $_POST['acf'] ) && is_array( $_POST['acf'] ) ? map_deep( wp_unslash( $_POST['acf'] ), 'sanitize_text_field' ) : array(); // phpcs:ignore WordPress.Security.NonceVerification -- SCF verified its nonce.
		$key = PL_Fields::field_key( 'pl_office', 'head_office' );
		if ( ! array_key_exists( $key, $in ) ) {
			return;
		}
		$id     = isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : ( isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$others = self::head_offices( $id );
		if ( empty( $in[ $key ] ) && ! $others ) {
			acf_add_validation_error(
				'acf[' . $key . ']',
				get_post_meta( $id, 'pl_head_office', true )
					? 'The site needs one head office. To move it, open the other office and switch Head office on there; this one switches off by itself.'
					: 'The site has no head office yet. Switch Head office on here (or on the office that is the head office).'
			);
		}
	}

	/* -------------------------------------------------------------- after save */

	public static function after_save( $post_id ) {
		$type = get_post_type( (int) $post_id );
		if ( ! in_array( $type, self::TYPES, true ) ) {
			return;
		}
		$notes = self::fix_phones( (int) $post_id );
		if ( 'pl_office' === $type && '' === trim( get_the_title( (int) $post_id ) ) ) {
			$city = (string) get_post_meta( (int) $post_id, 'pl_city', true );
			if ( '' !== $city ) { // an office without a name shows as "(no title)" in wp-admin: use its city.
				remove_action( 'acf/save_post', array( __CLASS__, 'after_save' ), 20 );
				wp_update_post( array( 'ID' => (int) $post_id, 'post_title' => $city ) );
				add_action( 'acf/save_post', array( __CLASS__, 'after_save' ), 20 );
			}
		}
		if ( 'pl_office' === $type && get_post_meta( (int) $post_id, 'pl_head_office', true ) && 'publish' === get_post_status( (int) $post_id ) ) {
			foreach ( self::head_offices( (int) $post_id ) as $old ) {
				update_post_meta( $old, 'pl_head_office', 0 );
				$notes[] = sprintf( '%s is now the head office. %s was switched off, because the site has one head office.', esc_html( get_the_title( (int) $post_id ) ), esc_html( get_the_title( $old ) ) );
			}
			PL_Cache::bump();
		}
		if ( $notes ) {
			set_transient( 'pl_guard_' . get_current_user_id(), $notes, 120 );
		}
	}

	/** Trashing the head office (while others exist) would leave the map without its origin. */
	public static function guard_trash( $post_id ) {
		if ( 'pl_office' !== get_post_type( $post_id ) || ! get_post_meta( $post_id, 'pl_head_office', true ) || self::head_offices( $post_id ) ) {
			return;
		}
		$others = get_posts( array( 'post_type' => 'pl_office', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'exclude' => array( $post_id ) ) ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams
		if ( ! $others ) {
			return; // the last office: nothing to move the head office to.
		}
		wp_die(
			wp_kses_post( sprintf( '<p><strong>%s is the head office</strong>, so it can’t go to the bin yet.</p><p>To fix it: open the office that should be the head office, switch <strong>Head office</strong> on and click Update. Then you can remove this one.</p>', esc_html( get_the_title( $post_id ) ) ) ),
			'Head office',
			array( 'response' => 200, 'back_link' => true )
		);
	}

	/* ---------------------------------------------------------------- notices */

	public static function notices() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}
		$saved = get_transient( 'pl_guard_' . get_current_user_id() );
		if ( $saved && 'post' === $screen->base && in_array( $screen->post_type, self::TYPES, true ) ) {
			delete_transient( 'pl_guard_' . get_current_user_id() );
			foreach ( (array) $saved as $n ) {
				echo '<div class="notice notice-info is-dismissible pl-guard-note"><p>' . wp_kses_post( $n ) . '</p></div>';
			}
		}
		$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification -- read-only notice
		if ( 'post' === $screen->base && $id && in_array( $screen->post_type, self::TYPES, true ) ) {
			$warn = self::phone_warnings( $id );
			if ( 'event' === $screen->post_type ) {
				$e = PL_Data::event_by_id( $id, true );
				if ( $e && 'past' === $e['status'] && 'open' === $e['registration'] ) {
					array_unshift( $warn, 'This event has ended, but <strong>Registration</strong> is still <strong>Open</strong>. To fix it: set Registration to <strong>Closed</strong> and click Update.' );
				}
			}
			foreach ( $warn as $w ) {
				echo '<div class="notice notice-warning pl-guard-warn"><p>' . wp_kses_post( $w ) . '</p></div>';
			}
		}
		if ( 'edit-pl_office' === $screen->id || ( 'post' === $screen->base && 'pl_office' === $screen->post_type ) ) {
			$heads = self::head_offices();
			if ( 1 !== count( $heads ) && ! $saved ) {
				echo '<div class="notice notice-warning pl-guard-warn"><p>' . ( $heads
					? sprintf( '%d offices are marked <strong>Head office</strong>; the site uses one. To fix it: open the office that is not the head office, switch Head office off and click Update.', count( $heads ) )
					: 'No office is marked <strong>Head office</strong>, so the map has no starting point. To fix it: open the head office, switch Head office on and click Update.' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
		}
		if ( 'edit-event' === $screen->id ) {
			$stale = array_filter( PL_Data::events( array( 'include_hidden' => true ) ), fn( $e ) => 'past' === $e['status'] && 'open' === $e['registration'] );
			if ( $stale ) {
				$links = array_map( fn( $e ) => '<a href="' . esc_url( get_edit_post_link( $e['post_id'] ) ) . '">' . esc_html( $e['name'] ) . '</a>', array_values( $stale ) );
				echo '<div class="notice notice-warning pl-guard-warn"><p>' . wp_kses_post( sprintf( 'These events have ended but Registration is still <strong>Open</strong>: %s. Open each one, set Registration to <strong>Closed</strong>, click Update.', implode( ', ', $links ) ) ) . '</p></div>';
			}
		}
	}

	/* ----------------------------------------------------------------- browser */

	public static function assets() {
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base || ! in_array( $screen->post_type, array( 'event', 'pl_office', 'pl_society', 'pl_sponsor', 'pl_membership', 'pl_job', 'pl_service' ), true ) ) {
			if ( ! $screen || false === strpos( (string) $screen->id, 'pl-company' ) ) {
				return;
			}
		}
		wp_enqueue_script( 'pl-guard', PL_EVENTS_URL . 'assets/js/pl-guard.js', array( 'jquery' ), PL_EVENTS_VERSION, true );
		wp_add_inline_script(
			'pl-guard',
			'window.PL_GUARD=' . wp_json_encode(
				array(
					'codes'   => self::codes(),
					'lengths' => self::PHONE_LENGTHS,
					'today'   => wp_date( 'Y-m-d' ),
				)
			) . ';',
			'before'
		);
	}
}
