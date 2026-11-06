<?php
/**
 * Overridable templates (Step 2.2 §8): {child theme}/pl-events/{name}.php wins over
 * {parent theme}/pl-events/{name}.php, which wins over the plugin's templates/{name}.php.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Template {

	public static function locate( $name ) {
		$name  = sanitize_file_name( $name ) . '.php';
		$found = locate_template( array( 'pl-events/' . $name ) );
		$file  = $found ? $found : PL_EVENTS_DIR . 'templates/' . $name;
		return apply_filters( 'pl_template_file', $file, $name );
	}

	/** Render a template to a string. Variables are available by name inside it. */
	public static function render( $name, array $vars = array() ) {
		$file = self::locate( $name );
		if ( ! is_readable( $file ) ) {
			return '';
		}
		$vars = apply_filters( 'pl_template_vars', $vars, $name );
		ob_start();
		( static function ( $__file, $__vars ) {
			extract( $__vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
			include $__file;
		} )( $file, $vars );
		return (string) ob_get_clean();
	}

	/* ---------------------------------------------------- template helpers */

	public static function flag( $cc, $big = false, $extra = '' ) {
		$url = PL_Geo::flag_url( $cc );
		if ( ! $url ) {
			return '';
		}
		$size = $big ? 'width="30" height="21"' : 'width="20" height="15"';
		return '<img class="flag" src="' . esc_url( $url ) . '" ' . $size . ' alt="" aria-hidden="true" loading="lazy" decoding="async"' . $extra . '>';
	}

	/** <img> for a Media Library URL in a frame (PL_Images): srcset/sizes from the frame's crops. */
	public static function img( $url, $alt = '', $class = '', $style = '', $sizes = '100vw', $eager = false, $frame = 'photo' ) {
		return PL_Images::img( $url, $frame, array( 'alt' => $alt, 'class' => $class, 'style' => $style, 'sizes' => $sizes, 'eager' => $eager ) );
	}

	/** Date box: "30 Oct – 1 Nov" over "2026", one size, one case (Thread 8). */
	public static function date_box( array $e ) {
		return '<span class="date"><span class="dd">' . esc_html( $e['tile'][0] ) . '</span><span class="dy">' . esc_html( $e['tile'][1] ) . '</span></span>';
	}

	/**
	 * Small 16:9 poster for list rows: the real poster (same crops/srcset as the cards), else the
	 * series' typographic poster in miniature. Decorative (the row's link carries the name).
	 */
	public static function thumb( array $e ) {
		$cls = 'poster mini' . ( 'past' === $e['status'] ? ' past' : '' );
		if ( $e['poster'] ) {
			return '<span class="' . $cls . ' r-th" aria-hidden="true">' . PL_Images::img( $e['poster'], 'poster', array( 'class' => 'pimg', 'sizes' => '(max-width: 767px) 72px, 120px' ) ) . '</span>';
		}
		return '<span class="' . $cls . ' r-th" aria-hidden="true"><span class="pw' . ( strlen( $e['poster_word'] ) > 9 ? ' long' : '' ) . '">' . esc_html( $e['poster_word'] ) . '</span></span>';
	}

	/** WhatsApp glyph (Simple Icons, CC0) — drawn white inside the brand-green circle (#25D366). */
	const WA_PATH = 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z';

	public static function wa_icon( $size = 20 ) {
		return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="#fff" d="' . self::WA_PATH . '"/></svg>';
	}

	/**
	 * Event actions, one renderer for card / row / page (Thread 8 card clean-up):
	 *  - "Register now" (primary) + "Website" (outline) side by side; past events: Website only.
	 *  - "Add to calendar" disclosure (Google · Apple .ics · Outlook) — upcoming/live only.
	 *  - WhatsApp share in its brand colour.
	 * The `pl_event_actions` filter still receives/returns the keyed array (register, website, calendar, whatsapp).
	 *
	 * @param string $ctx card|row|page
	 */
	public static function actions( array $e, $ctx = 'card' ) {
		$page = 'page' === $ctx;
		$a    = array();
		if ( $e['can_register'] ) {
			$a['register'] = sprintf( '<a href="%s" class="btn %s pl-reg" target="_blank" rel="noopener" aria-label="%s">Register now</a>', esc_url( $e['registration_url'] ), $page ? 'bw' : 'bp sm', esc_attr( $e['register_aria'] ) );
		}
		if ( $e['website'] ) {
			$label        = $page ? 'Congress website' : 'Website';
			$a['website'] = sprintf( '<a href="%s" class="btn %s pl-web" target="_blank" rel="noopener" aria-label="%s">%s</a>', esc_url( $e['website'] ), $page ? 'bso' : 'bs sm', esc_attr( $label . ': ' . $e['short_label'] ), esc_html( $label ) );
		}
		if ( 'past' !== $e['status'] ) {
			$chev          = '<svg class="chev" width="14" height="14" viewBox="0 0 16 16" aria-hidden="true"><path d="M3.5 6l4.5 4.5L12.5 6" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			$a['calendar'] = '<details class="calm pl-cal' . ( $page ? ' calm-down' : '' ) . '"><summary class="calbtn' . ( $page ? ' btn bso' : '' ) . '" aria-label="' . esc_attr( 'Add to calendar: ' . $e['short_label'] ) . '">Add to calendar ' . $chev . '</summary>'
				. '<div class="cal-menu">'
				. '<a href="' . esc_url( $e['gcal_url'] ) . '" target="_blank" rel="noopener">Google Calendar</a>'
				. '<a href="' . esc_url( $e['ics_url'] ) . '" type="text/calendar">Apple Calendar <span class="mut">(iPhone, Mac · .ics)</span></a>'
				. '<a href="' . esc_url( $e['outlook_url'] ) . '" target="_blank" rel="noopener">Outlook</a>'
				. '</div></details>';
		}
		$a['whatsapp'] = $page
			? '<a class="btn bso wa-text" href="' . esc_url( $e['wa_url'] ) . '" target="_blank" rel="noopener"><span class="wa-dot">' . self::wa_icon( 16 ) . '</span>Share on WhatsApp</a>'
			: '<a class="wa-btn" href="' . esc_url( $e['wa_url'] ) . '" target="_blank" rel="noopener" aria-label="Share on WhatsApp">' . self::wa_icon( 20 ) . '</a>';
		$a     = apply_filters( 'pl_event_actions', $a, $e, $ctx );
		$main  = ( $a['register'] ?? '' ) . ( $a['website'] ?? '' );
		$extra = implode( '', array_diff_key( $a, array_flip( array( 'register', 'website', 'calendar', 'whatsapp' ) ) ) ); // added through the filter
		if ( $page ) {
			return $main . ( $a['calendar'] ?? '' ) . ( $a['whatsapp'] ?? '' ) . $extra; // one wrapping row on the event page
		}
		if ( 'row' === $ctx ) {
			return '<span class="ra-main">' . $main . '</span>' . ( $a['calendar'] ?? '' ) . ( $a['whatsapp'] ?? '' ) . $extra;
		}
		$sub = ( $a['calendar'] ?? '' ) . '<span class="push"></span>' . $extra . ( $a['whatsapp'] ?? '' );
		return ( $main ? '<div class="ca-main">' . $main . '</div>' : '' ) . '<div class="ca-sub">' . $sub . '</div>';
	}

	public static function icon( $name ) {
		$i = array(
			'cal'    => '<svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>',
			'wa'     => '<svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20l1.3-3.9A8 8 0 1 1 8 19z"/><path d="M9 9.5c.5 2 2.5 4 4.5 4.5l1.2-1.2 2 1-.4 1.6c-3.6.4-8.1-4.1-7.7-7.7L10.2 7l1 2z"/></svg>',
			'search' => '<svg width="18" height="18" viewBox="0 0 20 20" aria-hidden="true"><circle cx="8.5" cy="8.5" r="6" stroke="#57534C" stroke-width="2" fill="none"/><path d="M13 13l5 5" stroke="#57534C" stroke-width="2" stroke-linecap="round"/></svg>',
			'x'      => '<svg width="18" height="18" viewBox="0 0 20 20" aria-hidden="true"><path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
		);
		return $i[ $name ] ?? '';
	}

	/** "Sample" tag, shown only while Company › Show "Sample" tags is on. */
	public static function sample_tag( $label = 'Sample' ) {
		$c = PL_Data::company();
		return ! empty( $c['show_sample_tags'] ) ? '<span class="smp">' . esc_html( $label ) . '</span>' : '';
	}

	public static function plural( $n, $one, $many = null ) {
		return $n . ' ' . ( 1 === (int) $n ? $one : ( $many ?? $one . 's' ) );
	}

	/** Attributes the JS uses to re-check status on screen (cached pages stay honest). */
	public static function status_attrs( array $e ) {
		return sprintf(
			' data-ev="%s" data-st="%s" data-start="%s" data-end="%s" data-tz="%s"',
			esc_attr( $e['id'] ),
			esc_attr( $e['status'] ),
			esc_attr( $e['start'] ),
			esc_attr( $e['end'] ),
			esc_attr( $e['tz'] )
		);
	}

	/** Attributes the calendar JS filters on (same rules as PL_Data::matches). */
	public static function filter_attrs( array $e ) {
		return sprintf(
			' data-spec="%s" data-cc="%s" data-year="%s" data-type="%s" data-q="%s"',
			esc_attr( implode( '|', (array) $e['specialties'] ) ),
			esc_attr( $e['country'] ),
			esc_attr( (string) $e['year'] ),
			esc_attr( $e['type'] ),
			esc_attr( strtolower( $e['name'] . ' ' . $e['city'] . ' ' . $e['country_name'] . ' ' . $e['series'] . ' ' . $e['venue'] ) )
		);
	}

	/** Contact page link with topic/office pre-set (2.1 P4). */
	public static function contact_url( $topic = 'proposal', $office = '' ) {
		$base = apply_filters( 'pl_contact_url', home_url( '/contact/' ) );
		return add_query_arg( array_filter( array( 'topic' => $topic, 'office' => $office ) ), $base );
	}

	public static function events_url( array $args = array() ) {
		return add_query_arg( $args, apply_filters( 'pl_events_page_url', home_url( '/events/' ) ) );
	}
}
