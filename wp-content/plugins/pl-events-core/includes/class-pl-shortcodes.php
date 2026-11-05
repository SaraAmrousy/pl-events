<?php
/**
 * All [pl_*] shortcodes (Step 2.2 §5 as updated by Step 3.4b §5). Server-rendered:
 * with JS off every shortcode is a finished section; assets/js/pl-events.js only enhances.
 * Output is escaped in the templates and cached per hour (PL_Cache) — the hour in the key
 * plus the hourly status check keep cached HTML honest, and the JS re-checks on screen.
 *
 * Not here on purpose: [pl_help] (3.4b P1), any saved-list / favourites (3.4a H4), [pl_hero_map].
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Shortcodes {

	const TAGS = array( 'pl_hero', 'pl_upcoming', 'pl_calendar', 'pl_story', 'pl_stats', 'pl_company', 'pl_company_figures', 'pl_next_event', 'pl_logos', 'pl_services', 'pl_offices', 'pl_careers', 'pl_live_ribbon' );

	/** Query args the calendar reads (no WP reserved names such as "year"). */
	const CAL_ARGS = array( 'tab', 'q', 'specialty', 'country', 'yr', 'type', 'view' );

	public static function init() {
		foreach ( self::TAGS as $tag ) {
			add_shortcode( $tag, array( __CLASS__, 'dispatch' ) );
		}
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	public static function register_assets() {
		wp_register_style( 'pl-events', PL_EVENTS_URL . 'assets/css/pl-events-core.css', array(), PL_EVENTS_VERSION );
		wp_register_script( 'pl-events', PL_EVENTS_URL . 'assets/js/pl-events.js', array(), PL_EVENTS_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}

	public static function enqueue() {
		if ( ! wp_script_is( 'pl-events', 'registered' ) ) {
			self::register_assets();
		}
		wp_enqueue_script( 'pl-events' );
		if ( did_action( 'wp_head' ) ) {
			wp_print_styles( 'pl-events' ); // shortcode rendered after <head>: print once, inline.
		} else {
			wp_enqueue_style( 'pl-events' );
		}
	}

	public static function dispatch( $atts, $content, $tag ) {
		$atts = (array) $atts;
		$fn   = substr( $tag, 3 );
		if ( ! method_exists( __CLASS__, $fn ) ) {
			return '';
		}
		self::enqueue();
		$atts = apply_filters( 'pl_shortcode_atts', $atts, $tag );
		$req  = array();
		if ( in_array( $tag, array( 'pl_calendar', 'pl_upcoming', 'pl_story' ), true ) ) {
			foreach ( array_merge( self::CAL_ARGS, array( 'ht' ) ) as $k ) {
				if ( isset( $_GET[ $k ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- read-only filters.
					$req[ $k ] = sanitize_text_field( wp_unslash( $_GET[ $k ] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				}
			}
		}
		$key  = $tag . wp_json_encode( $atts ) . wp_json_encode( $req ) . gmdate( 'YmdH', PL_Dates::now()->getTimestamp() ) . get_queried_object_id();
		$html = PL_Cache::remember( 'sc:' . $key, fn() => (string) call_user_func( array( __CLASS__, $fn ), $atts, $req ), HOUR_IN_SECONDS );
		return apply_filters( 'pl_shortcode_html', $html, $tag, $atts );
	}

	/** "Our *congresses.*" → Our <span class="em">congresses.</span> */
	public static function em( $text ) {
		return preg_replace( '/\*([^*]+)\*/', '<span class="em">$1</span>', esc_html( $text ) );
	}

	private static function event_filters( array $a ) {
		return array_intersect_key( $a, array_flip( array( 'type', 'specialty', 'country', 'year' ) ) );
	}

	/* ---------------------------------------------------------------- events */

	/** [pl_hero eyebrow="" intro="" browse="congresses"] — Home hero with the iris (4.2). */
	public static function hero( $atts ) {
		$a = shortcode_atts( array( 'eyebrow' => '', 'intro' => '', 'browse' => 'congresses' ), $atts, 'pl_hero' );
		return PL_Template::render( 'hero', array( 'a' => $a, 'company' => PL_Data::company(), 'stats' => PL_Data::stats(), 'next' => PL_Data::hero_event() ) );
	}

	/** [pl_upcoming layout="grid" tabs="all,live,upcoming,past" default="all" limit="6"] — Home "Our congresses." */
	public static function upcoming( $atts, $req ) {
		$a = shortcode_atts(
			array( 'layout' => 'grid', 'tabs' => 'all,live,upcoming,past', 'default' => 'all', 'limit' => 6, 'type' => '', 'specialty' => '', 'country' => '', 'year' => '', 'title' => 'Our *congresses.*', 'more' => '1', 'id' => 'congresses' ),
			$atts,
			'pl_upcoming'
		);
		$events = PL_Data::events( self::event_filters( $a ) + array( 'order' => 'natural' ) );
		return PL_Template::render( 'upcoming', array( 'a' => $a, 'events' => $events, 'stats' => PL_Data::stats( PL_Data::events( self::event_filters( $a ) ) ), 'tab' => $req['ht'] ?? $a['default'] ) );
	}

	/** [pl_calendar default="all" search="1" filters="specialty,country,year,type" views="grid,list"] — Events page. */
	public static function calendar( $atts, $req ) {
		$a = shortcode_atts(
			array( 'default' => 'all', 'search' => '1', 'filters' => 'specialty,country,year,type', 'views' => 'grid,list', 'view' => 'grid', 'type' => '', 'specialty' => '', 'country' => '', 'year' => '', 'heading' => 'Events', 'intro' => 'Every PL Events congress, symposium, course and webinar — live, upcoming and past, with dates, cities and registration.' ),
			$atts,
			'pl_calendar'
		);
		$all = PL_Data::events( self::event_filters( $a ) + array( 'order' => 'natural' ) );
		return PL_Template::render( 'calendar', array( 'a' => $a, 'events' => $all, 'stats' => PL_Data::stats( PL_Data::events( self::event_filters( $a ) ) ), 'req' => $req ) );
	}

	/** [pl_next_event layout="strip|card"] */
	public static function next_event( $atts ) {
		$a = shortcode_atts( array( 'layout' => 'strip' ), $atts, 'pl_next_event' );
		$e = PL_Data::hero_event();
		return $e ? PL_Template::render( 'next-event', array( 'a' => $a, 'e' => $e ) ) : '';
	}

	/** [pl_live_ribbon] — empty unless an event is live. */
	public static function live_ribbon() {
		$live = PL_Data::events( array( 'status' => 'live' ) );
		return $live ? PL_Template::render( 'live-ribbon', array( 'live' => $live ) ) : '';
	}

	/** [pl_story layout="sticky explore" aspect="1.3"] — About: story map → country explorer. */
	public static function story( $atts, $req ) {
		$a = shortcode_atts( array( 'layout' => 'sticky explore', 'aspect' => '1.3', 'mobile_aspect' => '2.1' ), $atts, 'pl_story' );
		$events = PL_Data::events();
		if ( ! $events ) {
			return '';
		}
		return PL_Template::render( 'story', array( 'a' => $a, 'events' => $events, 'stats' => PL_Data::stats( $events ), 'country' => strtoupper( $req['country'] ?? '' ) ) );
	}

	/* ------------------------------------------------------------- numbers */

	/**
	 * [pl_stats field="countries"]                → 5
	 * [pl_stats layout="line"]                    → "⑮ congresses. ⑦ cities. ⑤ countries. One name."
	 * [pl_stats layout="sponsor_line"]            → "2 upcoming congresses in Tunis and Baghdad are open to sponsors."
	 * [pl_stats layout="number" field="cities"]   → number in a blue pill
	 */
	public static function stats( $atts ) {
		$a = shortcode_atts( array( 'layout' => '', 'field' => '', 'type' => '', 'specialty' => '', 'country' => '', 'year' => '', 'tag' => 'p' ), $atts, 'pl_stats' );
		$s = PL_Data::stats( PL_Data::events( self::event_filters( $a ) ) );
		if ( $a['field'] && '' === $a['layout'] ) {
			return esc_html( (string) ( $s[ $a['field'] ] ?? '' ) );
		}
		if ( 'number' === $a['layout'] ) {
			return '<span class="hl-num">' . esc_html( (string) ( $s[ $a['field'] ] ?? '' ) ) . '</span>';
		}
		if ( 'sponsor_line' === $a['layout'] ) {
			$up = PL_Data::events( array( 'status' => 'current' ) + self::event_filters( $a ) );
			$tag = in_array( $a['tag'], array( 'p', 'span' ), true ) ? $a['tag'] : 'p';
			return '<' . $tag . ' class="pl-sponsor-line">' . esc_html( self::sponsor_line( $up ) ) . '</' . $tag . '>';
		}
		return PL_Template::render( 'stats-line', array( 's' => $s ) );
	}

	public static function sponsor_line( array $up ) {
		if ( ! $up ) {
			return 'Tell us the specialty you want to reach — we’ll match you to the right congress.';
		}
		$cities = array_values( array_unique( array_map( fn( $e ) => $e['city'] ? $e['city'] : 'online', $up ) ) );
		$last   = array_pop( $cities );
		$where  = $cities ? implode( ', ', $cities ) . ' and ' . $last : $last;
		return ( 1 === count( $up ) ? '1 upcoming congress' : count( $up ) . ' upcoming congresses' ) . ' in ' . $where . ( 1 === count( $up ) ? ' is' : ' are' ) . ' open to sponsors.';
	}

	/* ------------------------------------------------------------- company */

	/** [pl_company field="hero_headline|tagline|portfolio_url|portfolio_size|portfolio_link|years|founding_year|name" tag=""] */
	public static function company( $atts ) {
		$a = shortcode_atts( array( 'field' => 'tagline', 'tag' => '', 'class' => '', 'label' => 'Portfolio' ), $atts, 'pl_company' );
		$c = PL_Data::company();
		switch ( $a['field'] ) {
			case 'hero_headline':
				$words = fn( $s ) => implode( ' ', array_map( fn( $w ) => '<span class="w">' . esc_html( $w ) . '</span>', preg_split( '/\s+/', trim( (string) $s ) ) ) );
				$html  = $words( $c['hero_headline']['text'] ) . ( $c['hero_headline']['em'] ? '<br><span class="em">' . $words( $c['hero_headline']['em'] ) . '</span>' : '' );
				$tag   = $a['tag'] ? $a['tag'] : 'h1';
				return sprintf( '<%1$s id="heroH1" class="%2$s">%3$s</%1$s>', tag_escape( $tag ), esc_attr( $a['class'] ), $html );
			case 'tagline':
				$t    = (string) $c['tagline'];
				$html = preg_match( '/^(\S+)\s+(.+)$/', $t, $m ) ? esc_html( $m[1] ) . ' <span class="em">' . esc_html( $m[2] ) . '</span>' : esc_html( $t );
				break;
			case 'portfolio_url':
				return esc_url( $c['portfolio']['url'] );
			case 'portfolio_size':
				$html = esc_html( $c['portfolio']['size'] );
				break;
			case 'portfolio_link':
				if ( ! $c['portfolio']['url'] ) {
					return '';
				}
				$label = $a['label'] . ( $c['portfolio']['size'] ? ' (PDF, ' . $c['portfolio']['size'] . ')' : ' (PDF)' );
				return sprintf( '<a href="%s" class="%s" target="_blank" rel="noopener">%s</a>', esc_url( $c['portfolio']['url'] ), esc_attr( $a['class'] ? $a['class'] : 'btn bp' ), esc_html( $label ) );
			case 'years':
				$html = null === $c['years'] ? 'TBC' : esc_html( $c['years'] . '+' );
				break;
			default:
				$v    = $c[ $a['field'] ] ?? '';
				$html = is_scalar( $v ) ? esc_html( (string) $v ) : '';
		}
		return $a['tag'] ? sprintf( '<%1$s class="%2$s">%3$s</%1$s>', tag_escape( $a['tag'] ), esc_attr( $a['class'] ), $html ) : $html;
	}

	/** [pl_company_figures layout="inline|big"] — static, labelled "Company figures" (locked 14). */
	public static function company_figures( $atts ) {
		$a = shortcode_atts( array( 'layout' => 'inline' ), $atts, 'pl_company_figures' );
		$c = PL_Data::company();
		return $c['figures'] ? PL_Template::render( 'company-figures', array( 'a' => $a, 'figures' => $c['figures'], 'stats' => PL_Data::stats() ) ) : '';
	}

	/* ---------------------------------------------------------- other types */

	/** [pl_logos type="society|sponsor|award" columns="5"] */
	public static function logos( $atts ) {
		$a = shortcode_atts( array( 'type' => 'society', 'columns' => '' ), $atts, 'pl_logos' );
		switch ( $a['type'] ) {
			case 'sponsor':
				$items = PL_Data::sponsors();
				break;
			case 'award':
			case 'membership':
				$items = PL_Data::memberships();
				break;
			default:
				$items = PL_Data::societies();
		}
		return $items ? PL_Template::render( 'logos', array( 'a' => $a, 'items' => $items ) ) : '';
	}

	/** [pl_services layout="numbered|zigzag"] */
	public static function services( $atts ) {
		$a = shortcode_atts( array( 'layout' => 'numbered' ), $atts, 'pl_services' );
		$s = PL_Data::services();
		return $s ? PL_Template::render( 'zigzag' === $a['layout'] ? 'services-zigzag' : 'services-numbered', array( 'a' => $a, 'services' => $s ) ) : '';
	}

	/** [pl_offices layout="default|compact|split" hide_on="contact"] */
	public static function offices( $atts ) {
		$a = shortcode_atts( array( 'layout' => 'default', 'hide_on' => '' ), $atts, 'pl_offices' );
		if ( $a['hide_on'] ) {
			$slugs = array_map( 'trim', explode( ',', $a['hide_on'] ) );
			$obj   = get_queried_object();
			if ( $obj instanceof WP_Post && in_array( $obj->post_name, $slugs, true ) ) {
				return '';
			}
		}
		$o = PL_Data::offices();
		return $o ? PL_Template::render( 'offices', array( 'a' => $a, 'offices' => $o ) ) : '';
	}

	/** [pl_careers] — open roles, or the empty state (the CV form is Forminator, next to it). */
	public static function careers() {
		return PL_Template::render( 'careers', array( 'jobs' => PL_Data::jobs() ) );
	}
}
