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

	/** <img> for a Media Library URL, with srcset/sizes when the attachment is known. */
	public static function img( $url, $alt = '', $class = '', $style = '', $sizes = '100vw', $eager = false ) {
		if ( ! $url ) {
			return '';
		}
		$id  = attachment_url_to_postid( $url );
		$set = $id ? wp_get_attachment_image_srcset( $id, 'full' ) : '';
		$wh  = $id ? wp_get_attachment_image_src( $id, 'full' ) : null;
		return sprintf(
			'<img src="%s"%s alt="%s" class="%s" style="%s"%s %s decoding="async">',
			esc_url( $url ),
			$set ? ' srcset="' . esc_attr( $set ) . '" sizes="' . esc_attr( $sizes ) . '"' : '',
			esc_attr( $alt ),
			esc_attr( $class ),
			esc_attr( $style ),
			$wh ? ' width="' . (int) $wh[1] . '" height="' . (int) $wh[2] . '"' : '',
			$eager ? 'fetchpriority="high"' : 'loading="lazy"'
		);
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
