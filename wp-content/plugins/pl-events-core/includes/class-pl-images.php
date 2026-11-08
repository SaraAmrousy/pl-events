<?php
/**
 * Image fit: every image has a fixed frame, whatever size the editor uploads.
 *  - Photos (cover): cropped to the frame's ratio on the server (centre), so phones get small files
 *    and an odd panorama / tall portrait never shows bars or stretches. CSS cover is the safety net.
 *  - Logos (contain): never cropped; fit inside a tile with padding.
 *  - wp-admin: a soft yellow note under an image field when the picked image is smaller than the frame
 *    needs (never blocks saving or publishing).
 *  - SVG: allowed for logos only when the file is safe (no scripts, handlers or outside references).
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Images {

	/**
	 * Frame => ratio (w:h), server sizes (widths), fit. Minimum uploads: PL_Image_Spec (one source, round 7).
	 * Sizes are hard-cropped from the centre; WordPress never upscales, so a small upload simply
	 * falls back to its original file (and gets the yellow note).
	 */
	const FRAMES = array(
		'poster' => array( 'ratio' => array( 16, 9 ), 'widths' => array( 640, 1280 ), 'fit' => 'cover', 'label' => 'event cards (16:9) and the event page (16:10)' ),
		'poster10' => array( 'ratio' => array( 16, 10 ), 'widths' => array( 640, 1024 ), 'fit' => 'cover', 'label' => 'event page (16:10)' ),
		'photo'  => array( 'ratio' => array( 4, 3 ), 'widths' => array( 480, 960 ), 'fit' => 'cover', 'label' => '4:3 photo frame' ),
		'wide'   => array( 'ratio' => array( 21, 9 ), 'widths' => array( 1024, 2048 ), 'fit' => 'cover', 'label' => 'wide 21:9 photo frame' ),
		'hero'   => array( 'ratio' => null, 'widths' => array(), 'fit' => 'cover', 'label' => 'full-screen hero' ),
		'logo'   => array( 'ratio' => null, 'widths' => array( 480 ), 'fit' => 'contain', 'label' => 'logo tile' ),
	);

	public static function init() {
		add_action( 'after_setup_theme', array( __CLASS__, 'sizes' ) );
		add_filter( 'image_size_names_choose', array( __CLASS__, 'size_names' ) );
		add_filter( 'image_resize_dimensions', array( __CLASS__, 'keep_ratio' ), 10, 6 );
		add_filter( 'wp_editor_set_quality', array( __CLASS__, 'quality' ) );
		add_filter( 'image_editor_output_format', array( __CLASS__, 'png_to_webp' ) );
		add_filter( 'upload_mimes', array( __CLASS__, 'mimes' ) );
		add_filter( 'wp_check_filetype_and_ext', array( __CLASS__, 'svg_filetype' ), 10, 4 );
		add_filter( 'wp_handle_upload_prefilter', array( __CLASS__, 'svg_check' ) );
		add_filter( 'wp_handle_sideload_prefilter', array( __CLASS__, 'svg_check' ) ); // REST API + WP-CLI uploads
		add_filter( 'acf/render_field/type=image', array( __CLASS__, 'field_note' ), 20 );
		add_filter( 'acf/render_field/type=gallery', array( __CLASS__, 'field_note' ), 20 );
		add_action( 'acf/input/admin_footer', array( __CLASS__, 'field_note_js' ) );
		add_filter( 'wp_content_img_tag', array( __CLASS__, 'one_loading_attr' ), 99 );
		add_filter( 'wp_generate_attachment_metadata', array( __CLASS__, 'note_alpha' ), 10, 2 );
		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'alpha_class' ), 10, 2 );
	}

	/* ---------------------------------------------------------------- sizes */

	/** Resized copies of a PNG are made as WebP (keeps transparency; a PNG poster's copies were 5–10× heavier). The original file stays. */
	public static function png_to_webp( $map ) {
		if ( function_exists( 'wp_image_editor_supports' ) && wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
			$map['image/png'] = 'image/webp';
		}
		return $map;
	}

	public static function size_name( $frame, $w ) {
		return 'pl-' . $frame . '-' . $w;
	}

	public static function sizes() {
		// Blur-fill posters (round 6): uncropped copies limited by HEIGHT, so a portrait poster in a card has
		// a size between WordPress's 300 px "medium" and its ~1024 px "large" (phones pick ~384 px wide).
		add_image_size( 'pl-bf-480', 0, 480, false );
		add_image_size( 'pl-bf-800', 0, 800, false );
		foreach ( self::FRAMES as $frame => $f ) {
			foreach ( $f['widths'] as $w ) {
				if ( $f['ratio'] ) {
					add_image_size( self::size_name( $frame, $w ), $w, (int) round( $w * $f['ratio'][1] / $f['ratio'][0] ), array( 'center', 'center' ) );
				} else {
					add_image_size( self::size_name( $frame, $w ), $w, (int) round( $w / 2 ), false ); // logo: fit inside, no crop
				}
			}
		}
	}

	/**
	 * When the upload is smaller than a frame crop in one direction, WordPress would crop to
	 * min(source, size) on each side — a tall portrait becomes a "16:9" file of 600×720. For our frame
	 * ratios, crop instead the largest centred area with the exact ratio, never larger than the size.
	 */
	public static function keep_ratio( $out, $ow, $oh, $dw, $dh, $crop ) {
		if ( ! $crop || ! $dw || ! $dh || ( $ow >= $dw && $oh >= $dh ) ) {
			return $out; // big enough: WordPress's own crop is already exact
		}
		$r     = $dw / $dh;
		$match = false;
		foreach ( self::FRAMES as $f ) {
			if ( $f['ratio'] && abs( $r - $f['ratio'][0] / $f['ratio'][1] ) < 0.01 ) {
				$match = true;
			}
		}
		if ( ! $match ) {
			return $out;
		}
		$cw = min( $ow, $oh * $r ); // largest crop with ratio r inside the source
		$ch = $cw / $r;
		$nw = (int) round( min( $cw, $dw ) );
		$nh = (int) round( $nw / $r );
		if ( $nw < 1 || $nh < 1 || ( $nw === (int) $ow && $nh === (int) $oh ) ) {
			return false;
		}
		return array( 0, 0, (int) round( ( $ow - $cw ) / 2 ), (int) round( ( $oh - $ch ) / 2 ), $nw, $nh, (int) round( $cw ), (int) round( $ch ) );
	}

	/** Our frame crops are photos shown at fixed sizes: quality 72 (WordPress default 82) — ~35% lighter, no visible loss (6.1 LCP). */
	public static function quality( $q ) {
		return min( (int) $q, 72 );
	}

	/** Lets editors pick the 4:3 / 21:9 crops in the Elementor Image widget's size list. */
	public static function size_names( $names ) {
		return $names + array(
			'pl-photo-960' => 'PL photo 4:3',
			'pl-wide-2048' => 'PL wide 21:9',
			'pl-poster-1280' => 'PL poster 16:9',
		);
	}

	/* ---------------------------------------------------------------- output */

	/** True when a PNG/WebP/GIF has transparency (read from the file header once, at upload). */
	public static function file_has_alpha( $path ) {
		$h = (string) @file_get_contents( $path, false, null, 0, 65536 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
		$can = false;
		if ( "\x89PNG" === substr( $h, 0, 4 ) ) {
			$type = ord( $h[25] ?? "\0" ); // IHDR colour type: 4 = grey+alpha, 6 = RGBA; palette/grey/RGB may carry a tRNS chunk
			$can  = 4 === $type || 6 === $type || false !== strpos( $h, 'tRNS' );
		} elseif ( 'RIFF' === substr( $h, 0, 4 ) && 'WEBP' === substr( $h, 8, 4 ) ) {
			$can = false !== strpos( $h, 'ALPH' ) || ( 'VP8X' === substr( $h, 12, 4 ) && ( ord( $h[20] ?? "\0" ) & 16 ) );
		} else {
			return 'GIF' === substr( $h, 0, 3 ) && false !== strpos( $h, "\x21\xF9\x04" ) && ( ord( $h[ strpos( $h, "\x21\xF9\x04" ) + 3 ] ?? "\0" ) & 1 );
		}
		// An alpha CHANNEL isn't transparency: many posters are saved as RGBA with every pixel opaque
		// (MOCAT, 6.1b round 6). Look at the pixels: transparent only if some are really see-through.
		return $can ? self::pixels_transparent( $path, $h ) : false;
	}

	/** Sample a 48×48 grid (edges included); true when any sample is at least ~10% transparent. */
	private static function pixels_transparent( $path, $head ) {
		if ( ! function_exists( 'imagecreatefromstring' ) ) {
			return true; // can't look: keep the safe old answer
		}
		$im = @imagecreatefromstring( (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
		if ( ! $im ) {
			return true;
		}
		$w = imagesx( $im );
		$h = imagesy( $im );
		$n = 48;
		for ( $y = 0; $y < $n; $y++ ) {
			for ( $x = 0; $x < $n; $x++ ) {
				$c = imagecolorat( $im, (int) round( $x * ( $w - 1 ) / ( $n - 1 ) ), (int) round( $y * ( $h - 1 ) / ( $n - 1 ) ) );
				if ( ( ( $c >> 24 ) & 0x7F ) > 12 ) { // GD alpha: 0 = opaque … 127 = transparent
					imagedestroy( $im );
					return true;
				}
			}
		}
		imagedestroy( $im );
		return false;
	}

	public static function note_alpha( $meta, $id ) {
		$file = get_attached_file( $id );
		if ( $file && preg_match( '/\.(png|webp|gif)$/i', $file ) ) {
			update_post_meta( $id, '_pl_alpha2', self::file_has_alpha( wp_get_original_image_path( $id ) ? wp_get_original_image_path( $id ) : $file ) ? 1 : 0 );
		}
		return $meta;
	}

	/** Elementor Image widget / any wp_get_attachment_image(): mark transparent + SVG files so CSS shows them whole. */
	public static function alpha_class( $attr, $att ) {
		if ( self::has_alpha( $att->ID ) || self::is_svg( '', $att->ID ) ) {
			$attr['class'] = trim( ( $attr['class'] ?? '' ) . ' pl-alpha' );
		}
		return $attr;
	}

	/** Transparent images (logos, cut-outs) are shown whole, even in a photo frame. */
	public static function has_alpha( $id ) {
		if ( ! $id ) {
			return false;
		}
		$v = get_post_meta( $id, '_pl_alpha2', true ); // v2 (round 6): real transparency, not just an alpha channel
		if ( '' === $v ) { // uploaded before this check existed: read once and remember
			$file = get_attached_file( $id );
			$v    = $file && preg_match( '/\.(png|webp|gif)$/i', $file ) && self::file_has_alpha( $file ) ? 1 : 0;
			update_post_meta( $id, '_pl_alpha2', $v );
		}
		return (bool) $v;
	}

	/** Elementor's image optimiser adds loading="lazy" even when the tag already has one: keep one. */
	public static function one_loading_attr( $img ) {
		if ( substr_count( $img, ' loading=' ) > 1 ) {
			$img = preg_replace( '/^<img loading="[a-z]+"/i', '<img', $img, 1 );
		}
		if ( false !== strpos( $img, 'pl-bf' ) ) { // blur-fill: our sizes already match the visible poster; "auto" would use the frame's full width
			$img = str_replace( 'sizes="auto, ', 'sizes="', $img );
		}
		return $img;
	}

	public static function id( $src ) {
		if ( is_numeric( $src ) ) {
			return (int) $src;
		}
		static $cache = array();
		if ( ! isset( $cache[ $src ] ) ) {
			$id = $src ? (int) attachment_url_to_postid( $src ) : 0;
			if ( ! $id && $src && preg_match( '/-(\d+)x(\d+)(\.[a-z0-9]+)$/i', $src ) ) {
				// a resized copy ("…-811x1024.webp", e.g. a featured image's "large" URL): find its original
				$id = (int) attachment_url_to_postid( preg_replace( '/-(\d+)x(\d+)(\.[a-z0-9]+)$/i', '$3', $src ) );
				if ( ! $id ) {
					$id = (int) attachment_url_to_postid( preg_replace( '/-(\d+)x(\d+)(\.[a-z0-9]+)$/i', '-scaled$3', $src ) );
				}
			}
			$cache[ $src ] = $id;
		}
		return $cache[ $src ];
	}

	public static function is_svg( $src, $id = 0 ) {
		$mime = $id ? (string) get_post_mime_type( $id ) : '';
		return 'image/svg+xml' === $mime || (bool) preg_match( '/\.svg(\?|$)/i', (string) $src );
	}

	/**
	 * <img> for a frame. $src = attachment ID or Media Library URL (an outside URL still works, without srcset).
	 *
	 * @param array $o alt, class, style, sizes (attr), eager (bool), attrs (extra raw attributes, pre-escaped).
	 */
	public static function img( $src, $frame, array $o = array() ) {
		if ( ! $src ) {
			return '';
		}
		$o     = $o + array( 'alt' => '', 'class' => '', 'style' => '', 'sizes' => '100vw', 'eager' => false, 'attrs' => '' );
		$f     = self::FRAMES[ $frame ] ?? self::FRAMES['photo'];
		$id    = self::id( $src );
		$url   = is_numeric( $src ) ? (string) wp_get_attachment_url( $id ) : (string) $src;
		$set   = '';
		$w     = 0;
		$h     = 0;
		if ( $id && ! self::is_svg( $url, $id ) ) {
			$size = 'full';
			if ( $f['widths'] ) {
				$meta = wp_get_attachment_metadata( $id );
				foreach ( array_reverse( $f['widths'] ) as $fw ) { // largest frame size that exists
					if ( ! empty( $meta['sizes'][ self::size_name( $frame, $fw ) ] ) ) {
						$size = self::size_name( $frame, $fw );
						break;
					}
				}
			}
			$img = wp_get_attachment_image_src( $id, $size );
			if ( $img ) {
				list( $url, $w, $h ) = $img;
			}
			$set = (string) wp_get_attachment_image_srcset( $id, $size );
		}
		if ( ! $w && $f['ratio'] ) { // no metadata (outside URL, SVG, tiny file): reserve the frame's shape
			$w = 1600;
			$h = (int) round( 1600 * $f['ratio'][1] / $f['ratio'][0] );
		}
		$fit   = self::is_svg( $url, $id ) || self::has_alpha( $id ) ? 'contain' : $f['fit']; // vectors + transparent files: shown whole
		$class = trim( 'pl-img pl-' . $fit . ' ' . $o['class'] );
		return sprintf(
			'<img src="%s"%s alt="%s" class="%s"%s%s %s decoding="async"%s>',
			esc_url( $url ),
			$set ? ' srcset="' . esc_attr( $set ) . '" sizes="' . esc_attr( $o['sizes'] ) . '"' : '',
			esc_attr( $o['alt'] ),
			esc_attr( $class ),
			$o['style'] ? ' style="' . esc_attr( $o['style'] ) . '"' : '',
			$w ? ' width="' . (int) $w . '" height="' . (int) $h . '"' : '',
			$o['eager'] ? 'fetchpriority="high"' : 'loading="lazy"',
			$o['attrs'] ? ' ' . $o['attrs'] : ''
		);
	}

	/* ------------------------------------------------- posters: blur-fill (6.1b round 6) */

	/**
	 * "poster" (a designed image with text, or a shape far from the frame) or "photo".
	 * $setting = the event's "Image type" (auto | poster | photo). Auto: more than 15% away from 16:9 → poster.
	 */
	public static function poster_type( $src, $setting = 'auto' ) {
		$id = self::id( $src );
		if ( self::is_svg( $src, $id ) || self::has_alpha( $id ) ) {
			return 'logo'; // a transparent file / SVG used as the poster: shown whole on the brand colour (4.4)
		}
		if ( in_array( $setting, array( 'poster', 'photo' ), true ) ) {
			return $setting;
		}
		$d = self::dims( $id );
		if ( ! $d || ! $d[1] ) {
			return 'photo';
		}
		return abs( ( $d[0] / $d[1] ) / ( 16 / 9 ) - 1 ) > 0.15 ? 'poster' : 'photo';
	}

	/** Shrink every length of a sizes attribute by $f ("360px" → "calc((360px) * .45)"). */
	public static function scale_sizes( $sizes, $f ) {
		if ( $f >= 0.99 ) {
			return $sizes;
		}
		$out = array();
		foreach ( array_map( 'trim', explode( ',', (string) $sizes ) ) as $part ) {
			$cond = '';
			if ( preg_match( '/^(\([^)]*\))\s+(.+)$/', $part, $m ) ) {
				$cond = $m[1] . ' ';
				$part = $m[2];
			}
			$len   = preg_replace( '/^calc\((.*)\)$/', '$1', $part );
			$out[] = $cond . 'calc((' . $len . ') * ' . rtrim( rtrim( number_format( $f, 3, '.', '' ), '0' ), '.' ) . ')';
		}
		return implode( ', ', $out );
	}

	/**
	 * The image of an event card / row / page. Photo → img() (cover, frame crop). Poster → blur-fill:
	 *  - background: the SMALLEST uncropped copy (WordPress "medium", ~15 KB), cover + CSS blur/darken;
	 *  - front: the whole image (uncropped sizes only, so no text is ever cut), contain;
	 *  - sizes scaled to the width the poster really takes inside the frame (a portrait in 16:9 ≈ 45%),
	 *    so the browser picks a smaller file than before (LCP not worse). No JS: pure HTML + CSS.
	 * SVG / transparent files keep their own "contain" rule (img()).
	 */
	public static function poster( $src, $frame, $type, array $o = array() ) {
		if ( ! $src ) {
			return '';
		}
		$id = self::id( $src );
		if ( 'poster' !== $type || ! $id || self::is_svg( $src, $id ) || self::has_alpha( $id ) ) {
			return self::img( $src, $frame, $o );
		}
		$o    = $o + array( 'alt' => '', 'class' => '', 'sizes' => '100vw', 'eager' => false );
		$big  = wp_get_attachment_image_src( $id, 'large' );
		$bg   = wp_get_attachment_image_src( $id, 'medium' );
		if ( ! $big ) {
			return self::img( $src, $frame, $o );
		}
		$bg   = $bg ? $bg : $big;
		$set  = (string) wp_get_attachment_image_srcset( $id, 'large' ); // same shape only = uncropped copies
		$fr   = self::FRAMES[ $frame ]['ratio'] ?? array( 16, 9 );
		$f    = min( 1, ( $big[1] / max( 1, $big[2] ) ) / ( $fr[0] / $fr[1] ) );
		$load = $o['eager'] ? 'fetchpriority="high"' : 'loading="lazy"';
		return sprintf(
			'<img src="%s" alt="" aria-hidden="true" class="pl-bf-bg" width="%d" height="%d" %s decoding="async">',
			esc_url( $bg[0] ),
			(int) $bg[1],
			(int) $bg[2],
			$o['eager'] ? 'fetchpriority="low"' : 'loading="lazy"'
		) . sprintf(
			'<img src="%s"%s alt="%s" class="%s" width="%d" height="%d" %s decoding="async">',
			esc_url( $big[0] ),
			$set ? ' srcset="' . esc_attr( $set ) . '" sizes="' . esc_attr( self::scale_sizes( $o['sizes'], $f ) ) . '"' : '',
			esc_attr( $o['alt'] ),
			esc_attr( trim( 'pl-img pl-bf ' . $o['class'] ) ),
			(int) $big[1],
			(int) $big[2],
			$load
		);
	}

	/** Alt text stored in the Media Library (empty = decorative). */
	public static function alt( $src ) {
		$id = self::id( $src );
		return $id ? trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) : '';
	}

	/* ---------------------------------------------------------- wp-admin note */

	/** [w, h] of an attachment, or null (SVG / unknown: vector, never warned about). */
	public static function dims( $id ) {
		if ( ! $id || self::is_svg( '', $id ) ) {
			return null;
		}
		$m = wp_get_attachment_metadata( $id );
		return empty( $m['width'] ) ? null : array( (int) $m['width'], (int) $m['height'] );
	}

	/** Minimum upload [w, h] for a frame, from PL_Image_Spec. A poster-shaped event image uses the Poster minimum. */
	public static function min( $frame, $id = 0 ) {
		if ( in_array( $frame, array( 'poster', 'poster10' ), true ) && $id && 'poster' === self::poster_type( $id, 'auto' ) ) {
			return PL_Image_Spec::get( 'event_poster' )['min'];
		}
		return PL_Image_Spec::frame_min( $frame ) ?? array( 0, 0 );
	}

	public static function too_small( $id, $frame ) {
		$d = self::dims( $id );
		$m = isset( self::FRAMES[ $frame ] ) ? self::min( $frame, $id ) : null;
		return $d && $m && ( $d[0] < $m[0] || $d[1] < $m[1] ) ? $d : null;
	}

	public static function field_note( $field ) {
		$frame = $field['pl_frame'] ?? '';
		if ( ! $frame || empty( self::FRAMES[ $frame ] ) ) {
			return;
		}
		$f     = self::FRAMES[ $frame ];
		$small = array();
		foreach ( array_filter( (array) $field['value'] ) as $id ) {
			$d = self::too_small( (int) $id, $frame );
			if ( $d ) {
				$small[] = $d[0] . ' × ' . $d[1];
			}
		}
		$min  = self::min( $frame );
		$pmin = in_array( $frame, array( 'poster', 'poster10' ), true ) ? PL_Image_Spec::get( 'event_poster' )['min'] : $min;
		printf(
			'<p class="pl-imgnote" data-frame="%s" data-minw="%d" data-minh="%d" data-pminw="%d" data-pminh="%d" data-label="%s" style="%s"%s>%s</p>',
			esc_attr( $frame ),
			(int) $min[0],
			(int) $min[1],
			(int) $pmin[0],
			(int) $pmin[1],
			esc_attr( $f['label'] ),
			'margin:8px 0 0;padding:8px 10px;border-left:4px solid #DBA617;background:#FCF9E8;color:#3C3410;font-size:13px',
			$small ? '' : ' hidden',
			$small ? esc_html( self::note_text( $small, $f, self::min( $frame, (int) current( array_filter( (array) $field['value'] ) ) ) ) ) : ''
		);
	}

	private static function note_text( array $small, array $f, array $min ) {
		$n = count( $small );
		return sprintf(
			'%s smaller than the %s needs (%s px). Suggested: at least %d × %d px. It will still be published — it may look soft.',
			1 === $n ? 'This image is' : $n . ' images are',
			$f['label'],
			implode( ', ', array_slice( $small, 0, 4 ) ) . ( $n > 4 ? ', …' : '' ),
			$min[0],
			$min[1]
		);
	}

	/** Live update of the note when the editor picks another image (no reload). */
	public static function field_note_js() {
		?>
<script>
(function () {
	if (!window.acf || !window.wp || !wp.media) { return; }
	function check(field) {
		var note = field.$el.find('.pl-imgnote')[0];
		if (!note) { return; }
		var ids = field.$el.find('input[type="hidden"]').map(function () { return this.value; }).get().filter(function (v) { return /^\d+$/.test(v); });
		var minw = +note.dataset.minw, minh = +note.dataset.minh, small = [], left = ids.length;
		if (!left) { note.hidden = true; return; }
		ids.forEach(function (id) {
			var a = wp.media.attachment(id);
			a.fetch().always(function () {
				var w = a.get('width'), h = a.get('height');
				var mw = minw, mh = minh; // a poster-shaped event image has the Poster minimum (PL_Image_Spec)
				if (note.dataset.pminw && w && h && Math.abs((w / h) / (16 / 9) - 1) > 0.15) { mw = +note.dataset.pminw; mh = +note.dataset.pminh; }
				if (w && h && a.get('subtype') !== 'svg+xml' && (w < mw || h < mh)) { small.push(w + ' × ' + h); note.dataset.need = mw + ' × ' + mh; }
				if (--left) { return; }
				note.hidden = !small.length;
				note.textContent = small.length ? (small.length === 1 ? 'This image is' : small.length + ' images are') + ' smaller than the ' + note.dataset.label + ' needs (' + small.slice(0, 4).join(', ') + ' px). Suggested: at least ' + (note.dataset.need || (minw + ' × ' + minh)) + ' px. It will still be published — it may look soft.' : '';
			});
		});
	}
	['image', 'gallery'].forEach(function (t) {
		acf.addAction('new_field/type=' + t, function (field) {
			field.$el.on('change', 'input[type="hidden"]', function () { check(field); });
			if (field.$el.find('.pl-imgnote').length) { check(field); }
		});
	});
	document.addEventListener('click', function () { // gallery adds/removes items without a change event
		setTimeout(function () { acf.getFields({ type: 'gallery' }).forEach(check); }, 400);
	}, true);
})();
</script>
		<?php
	}

	/* ------------------------------------------------------------------ SVG */

	public static function mimes( $m ) {
		if ( current_user_can( 'upload_files' ) ) {
			$m['svg'] = 'image/svg+xml';
		}
		return $m;
	}

	public static function svg_filetype( $data, $file, $filename, $mimes ) {
		if ( preg_match( '/\.svg$/i', (string) $filename ) && current_user_can( 'upload_files' ) ) {
			return array( 'ext' => 'svg', 'type' => 'image/svg+xml', 'proper_filename' => $data['proper_filename'] ?? false );
		}
		return $data;
	}

	/** Reject (never "clean") an SVG that could run code or load anything from outside. */
	public static function svg_check( $file ) {
		if ( ! preg_match( '/\.svg$/i', (string) ( $file['name'] ?? '' ) ) ) {
			return $file;
		}
		$why = self::svg_problem( (string) @file_get_contents( $file['tmp_name'] ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
		if ( $why ) {
			$file['error'] = 'This SVG was not uploaded: ' . $why . ' Export it again as a plain SVG (or upload a PNG).';
		}
		return $file;
	}

	public static function svg_problem( $svg ) {
		if ( '' === trim( $svg ) || false === stripos( $svg, '<svg' ) ) {
			return 'it is not an SVG file.';
		}
		if ( preg_match( '/<!ENTITY|<!DOCTYPE/i', $svg ) ) {
			return 'it contains a DOCTYPE/ENTITY.';
		}
		if ( preg_match( '/<\s*(script|foreignObject|iframe|embed|object|handler|use\b[^>]*href\s*=\s*["\']\s*(https?:|data:|\/\/))/i', $svg ) ) {
			return 'it contains scripts or embedded content.';
		}
		if ( preg_match( '/\son[a-z]+\s*=/i', $svg ) ) {
			return 'it contains event handlers (on…=).';
		}
		if ( preg_match( '/(href|src)\s*=\s*["\']\s*(javascript:|https?:|\/\/)/i', $svg ) || preg_match( '/url\(\s*["\']?\s*(https?:|\/\/|javascript:)/i', $svg ) || stripos( $svg, '@import' ) !== false ) {
			return 'it links to outside files or javascript.';
		}
		return '';
	}
}
