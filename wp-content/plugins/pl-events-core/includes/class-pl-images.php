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
	 * Frame => ratio (w:h), server sizes (widths), minimum recommended upload (w x h), fit.
	 * Sizes are hard-cropped from the centre; WordPress never upscales, so a small upload simply
	 * falls back to its original file (and gets the yellow note).
	 */
	const FRAMES = array(
		'poster' => array( 'ratio' => array( 16, 9 ), 'widths' => array( 640, 1280 ), 'min' => array( 1280, 800 ), 'fit' => 'cover', 'label' => 'event cards (16:9) and the event page (16:10)' ),
		'poster10' => array( 'ratio' => array( 16, 10 ), 'widths' => array( 640, 1024 ), 'min' => array( 1280, 800 ), 'fit' => 'cover', 'label' => 'event page (16:10)' ),
		'photo'  => array( 'ratio' => array( 4, 3 ), 'widths' => array( 480, 960 ), 'min' => array( 960, 720 ), 'fit' => 'cover', 'label' => '4:3 photo frame' ),
		'wide'   => array( 'ratio' => array( 21, 9 ), 'widths' => array( 1024, 2048 ), 'min' => array( 2048, 878 ), 'fit' => 'cover', 'label' => 'wide 21:9 photo frame' ),
		'hero'   => array( 'ratio' => null, 'widths' => array(), 'min' => array( 1920, 1080 ), 'fit' => 'cover', 'label' => 'full-screen hero' ),
		'logo'   => array( 'ratio' => null, 'widths' => array( 480 ), 'min' => array( 400, 160 ), 'fit' => 'contain', 'label' => 'logo tile' ),
	);

	public static function init() {
		add_action( 'after_setup_theme', array( __CLASS__, 'sizes' ) );
		add_filter( 'image_size_names_choose', array( __CLASS__, 'size_names' ) );
		add_filter( 'image_resize_dimensions', array( __CLASS__, 'keep_ratio' ), 10, 6 );
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

	public static function size_name( $frame, $w ) {
		return 'pl-' . $frame . '-' . $w;
	}

	public static function sizes() {
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
		if ( "\x89PNG" === substr( $h, 0, 4 ) ) {
			$type = ord( $h[25] ?? "\0" ); // IHDR colour type: 4 = grey+alpha, 6 = RGBA; palette/grey/RGB may carry a tRNS chunk
			return 4 === $type || 6 === $type || false !== strpos( $h, 'tRNS' );
		}
		if ( 'RIFF' === substr( $h, 0, 4 ) && 'WEBP' === substr( $h, 8, 4 ) ) {
			return false !== strpos( $h, 'ALPH' ) || ( 'VP8X' === substr( $h, 12, 4 ) && ( ord( $h[20] ?? "\0" ) & 16 ) );
		}
		return 'GIF' === substr( $h, 0, 3 ) && false !== strpos( $h, "\x21\xF9\x04" ) && ( ord( $h[ strpos( $h, "\x21\xF9\x04" ) + 3 ] ?? "\0" ) & 1 );
	}

	public static function note_alpha( $meta, $id ) {
		$file = get_attached_file( $id );
		if ( $file && preg_match( '/\.(png|webp|gif)$/i', $file ) ) {
			update_post_meta( $id, '_pl_alpha', self::file_has_alpha( wp_get_original_image_path( $id ) ? wp_get_original_image_path( $id ) : $file ) ? 1 : 0 );
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
		$v = get_post_meta( $id, '_pl_alpha', true );
		if ( '' === $v ) { // uploaded before this check existed: read once and remember
			$file = get_attached_file( $id );
			$v    = $file && preg_match( '/\.(png|webp|gif)$/i', $file ) && self::file_has_alpha( $file ) ? 1 : 0;
			update_post_meta( $id, '_pl_alpha', $v );
		}
		return (bool) $v;
	}

	/** Elementor's image optimiser adds loading="lazy" even when the tag already has one: keep one. */
	public static function one_loading_attr( $img ) {
		if ( substr_count( $img, ' loading=' ) > 1 ) {
			$img = preg_replace( '/^<img loading="[a-z]+"/i', '<img', $img, 1 );
		}
		return $img;
	}

	public static function id( $src ) {
		if ( is_numeric( $src ) ) {
			return (int) $src;
		}
		static $cache = array();
		if ( ! isset( $cache[ $src ] ) ) {
			$cache[ $src ] = $src ? (int) attachment_url_to_postid( $src ) : 0;
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

	public static function too_small( $id, $frame ) {
		$d = self::dims( $id );
		$f = self::FRAMES[ $frame ] ?? null;
		return $d && $f && ( $d[0] < $f['min'][0] || $d[1] < $f['min'][1] ) ? $d : null;
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
		printf(
			'<p class="pl-imgnote" data-frame="%s" data-minw="%d" data-minh="%d" data-label="%s" style="%s"%s>%s</p>',
			esc_attr( $frame ),
			(int) $f['min'][0],
			(int) $f['min'][1],
			esc_attr( $f['label'] ),
			'margin:8px 0 0;padding:8px 10px;border-left:4px solid #DBA617;background:#FCF9E8;color:#3C3410;font-size:13px',
			$small ? '' : ' hidden',
			$small ? esc_html( self::note_text( $small, $f ) ) : ''
		);
	}

	private static function note_text( array $small, array $f ) {
		$n = count( $small );
		return sprintf(
			'%s smaller than the %s needs (%s px). Suggested: at least %d × %d px. It will still be published — it may look soft.',
			1 === $n ? 'This image is' : $n . ' images are',
			$f['label'],
			implode( ', ', array_slice( $small, 0, 4 ) ) . ( $n > 4 ? ', …' : '' ),
			$f['min'][0],
			$f['min'][1]
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
				if (w && h && a.get('subtype') !== 'svg+xml' && (w < minw || h < minh)) { small.push(w + ' × ' + h); }
				if (--left) { return; }
				note.hidden = !small.length;
				note.textContent = small.length ? (small.length === 1 ? 'This image is' : small.length + ' images are') + ' smaller than the ' + note.dataset.label + ' needs (' + small.slice(0, 4).join(', ') + ' px). Suggested: at least ' + minw + ' × ' + minh + ' px. It will still be published — it may look soft.' : '';
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
