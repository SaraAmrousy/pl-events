<?php
/**
 * PL Help (Step 6.2b): the training guide inside wp-admin.
 *  - PL Website › Help: hero + search + chapter cards; every task opens with its video, steps,
 *    screenshot (numbered callouts + legend), pairing shot, tip / warning, official links.
 *  - A "?" button on every screen that has a task opens that task in a side panel.
 *  - Cheat sheet: "Print / Save as PDF" → one clean A4 page.
 * Content: help/guide.json (built from tools/guide/guide.json by tools/guide/build.js — one source).
 * Media: wp-content/uploads/pl-help/ (shots, pairs, video). Direct access is denied (.htaccess);
 * files are served only to signed-in staff through admin-post.php?action=pl_help_media — so videos
 * are never public, never in Media, never in a sitemap. Range requests are supported (video seek).
 * Content language: Egyptian Arabic, RTL, inside the English wp-admin.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Help {

	const CAP  = 'edit_pl_items';
	const SLUG = 'pl-help';

	private static $guide = null;

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		add_action( 'admin_post_pl_help_media', array( __CLASS__, 'media' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_footer', array( __CLASS__, 'panel' ) );
		add_action( 'admin_init', array( __CLASS__, 'protect' ) );
	}

	/* ------------------------------------------------------------------ data */

	public static function guide() {
		if ( null === self::$guide ) {
			$f           = PL_EVENTS_DIR . 'help/guide.json';
			self::$guide = is_readable( $f ) ? (array) json_decode( (string) file_get_contents( $f ), true ) : array();
		}
		return self::$guide;
	}

	public static function tasks() {
		return (array) ( self::guide()['tasks'] ?? array() );
	}

	public static function task( $id ) {
		foreach ( self::tasks() as $t ) {
			if ( $t['id'] === $id ) {
				return $t;
			}
		}
		return null;
	}

	/** Tasks whose "screens" list the current admin screen. */
	public static function tasks_for_screen() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return array();
		}
		$keys = array_filter( array( $screen->id, $screen->base, $screen->post_type ? $screen->base . ':' . $screen->post_type : '' ) );
		if ( 'post' === $screen->base && $screen->post_type ) {
			$keys[] = $screen->post_type; // "event" = the event form (new + edit)
		}
		$out = array();
		foreach ( self::tasks() as $t ) {
			if ( array_intersect( $keys, (array) ( $t['screens'] ?? array() ) ) ) {
				$out[] = $t;
			}
		}
		return $out;
	}

	public static function media_dir() {
		return trailingslashit( wp_upload_dir( null, false )['basedir'] ) . 'pl-help/';
	}

	public static function media_url( $file ) {
		return add_query_arg( array( 'action' => 'pl_help_media', 'f' => rawurlencode( $file ), 'v' => (string) ( self::guide()['built'] ?? '' ) ), admin_url( 'admin-post.php' ) );
	}

	private static function has( $file ) {
		return $file && is_readable( self::media_dir() . $file );
	}

	/* ------------------------------------------------------------------ menu */

	public static function menu() {
		add_submenu_page( 'pl-website', 'PL Help', 'Help', self::CAP, self::SLUG, array( __CLASS__, 'page' ), 2 );
	}

	public static function assets() {
		if ( ! current_user_can( self::CAP ) || ! self::guide() ) {
			return;
		}
		$on_help = isset( $_GET['page'] ) && self::SLUG === $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $on_help && ! self::tasks_for_screen() ) {
			return;
		}
		wp_enqueue_style( 'pl-help', PL_EVENTS_URL . 'assets/css/pl-help.css', array(), PL_EVENTS_VERSION . '.' . filemtime( PL_EVENTS_DIR . 'assets/css/pl-help.css' ) );
		wp_add_inline_style( 'pl-help', self::font_css() );
		wp_enqueue_script( 'pl-help', PL_EVENTS_URL . 'assets/js/pl-help.js', array(), PL_EVENTS_VERSION . '.' . filemtime( PL_EVENTS_DIR . 'assets/js/pl-help.js' ), true );
	}

	private static function font_css() {
		$u = PL_EVENTS_URL . 'assets/fonts/';
		return '@font-face{font-family:"PL Arabic";font-weight:400 600;font-display:swap;src:url(' . $u . 'plex-arabic-500.woff2) format("woff2")}'
			. '@font-face{font-family:"PL Arabic";font-weight:700 900;font-display:swap;src:url(' . $u . 'plex-arabic-700.woff2) format("woff2")}'
			. '@font-face{font-family:"PL Latin";font-weight:400 800;font-display:swap;src:url(' . $u . 'montserrat-normal-latin.woff2) format("woff2")}';
	}

	/* ------------------------------------------------------------- media gate */

	public static function media() {
		if ( ! is_user_logged_in() || ! current_user_can( self::CAP ) ) {
			status_header( 404 );
			exit;
		}
		$f = isset( $_GET['f'] ) ? rawurldecode( sanitize_text_field( wp_unslash( $_GET['f'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- read-only, capability-checked
		if ( ! preg_match( '#^(shots|pairs|video)/[a-z0-9-]+\.(png|jpg|mp4|vtt)$#', $f ) || ! self::has( $f ) ) {
			status_header( 404 );
			exit;
		}
		$path  = self::media_dir() . $f;
		$size  = filesize( $path );
		$types = array( 'png' => 'image/png', 'jpg' => 'image/jpeg', 'mp4' => 'video/mp4', 'vtt' => 'text/vtt; charset=utf-8' );
		$start = 0;
		$end   = $size - 1;
		nocache_headers();
		header( 'Cache-Control: private, max-age=86400' );
		header( 'X-Robots-Tag: noindex, nofollow' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Type: ' . $types[ pathinfo( $f, PATHINFO_EXTENSION ) ] );
		header( 'Accept-Ranges: bytes' );
		if ( isset( $_SERVER['HTTP_RANGE'] ) && preg_match( '/bytes=(\d*)-(\d*)/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_RANGE'] ) ), $m ) ) {
			$start = '' === $m[1] ? max( 0, $size - (int) $m[2] ) : (int) $m[1];
			$end   = ( '' !== $m[1] && '' !== $m[2] ) ? min( (int) $m[2], $size - 1 ) : $end;
			if ( $start > $end || $start >= $size ) {
				status_header( 416 );
				header( "Content-Range: bytes */$size" );
				exit;
			}
			status_header( 206 );
			header( "Content-Range: bytes $start-$end/$size" );
		}
		header( 'Content-Length: ' . ( $end - $start + 1 ) );
		$fh = fopen( $path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fseek( $fh, $start );
		$left = $end - $start + 1;
		while ( $left > 0 && ! feof( $fh ) ) {
			$chunk = fread( $fh, (int) min( 262144, $left ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			echo $chunk; // phpcs:ignore WordPress.Security.EscapeOutput -- binary file
			$left -= strlen( $chunk );
			flush();
		}
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	/* --------------------------------------------------------------- render */

	/** Arabic number agreement: 1 مهمة واحدة · 2 مهمتين · 3–10 مهام · 11+ مهمة. */
	public static function count_ar( $n, $one, $two, $few ) {
		$n = (int) $n;
		return 1 === $n ? $one . ' واحدة' : ( 2 === $n ? $two : ( $n <= 10 ? $n . ' ' . $few : $n . ' ' . $one ) );
	}

	/** Direct access to uploads/pl-help is denied; files go through the media gate only. */
	public static function protect() {
		$d = self::media_dir();
		if ( is_dir( $d ) && ! file_exists( $d . '.htaccess' ) ) {
			file_put_contents( $d . '.htaccess', "# PL Help media: staff only, served by admin-post.php?action=pl_help_media\nRequire all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			file_put_contents( $d . 'index.php', "<?php // Silence.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}

	/** "**Publish**" → <strong><bdi>Publish</bdi></strong>; `x` → <code>; escapes the rest. */
	public static function md( $text ) {
		$t = esc_html( PL_Image_Spec::fill( (string) $text ) );
		$t = preg_replace( '/\*\*(.+?)\*\*/u', '<strong><bdi>$1</bdi></strong>', $t );
		$t = preg_replace( '/`(.+?)`/u', '<code dir="ltr">$1</code>', $t );
		$t = preg_replace( '/^\[(\d+)\]\s*/u', '', $t );
		return $t;
	}

	private static function chapter( $id ) {
		foreach ( (array) ( self::guide()['chapters'] ?? array() ) as $c ) {
			if ( $c['id'] === $id ) {
				return $c;
			}
		}
		return array( 'title' => '', 'n' => '' );
	}

	private static function figure( array $s, $cls = '' ) {
		$file = $s['file'] ?? ( 'shots/' . $s['id'] . '.png' );
		if ( ! self::has( $file ) ) {
			return '';
		}
		$alt = $s['alt'] ?? '';
		$h   = '<figure class="plh-fig ' . esc_attr( $cls ) . '"><a href="' . esc_url( self::media_url( $file ) ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr__( 'Open the picture full size', 'pl-events-core' ) . '"><img loading="lazy" decoding="async" src="' . esc_url( self::media_url( $file ) ) . '" alt="' . esc_attr( $alt ) . '"></a>';
		if ( ! empty( $s['callouts'] ) ) {
			$h .= '<ol class="plh-legend">';
			foreach ( $s['callouts'] as $c ) {
				$h .= '<li><span class="plh-n" aria-hidden="true">' . (int) $c['n'] . '</span>' . self::md( $c['text'] ) . '</li>';
			}
			$h .= '</ol>';
		}
		return $h . '</figure>';
	}

	/** One task, fully rendered (used on the Help page and in the side panel). */
	public static function render_task( array $t, $context = 'page' ) {
		$id  = 'plh-' . $t['id'] . ( 'panel' === $context ? '-p' : '' );
		$c   = self::chapter( $t['chapter'] );
		$h   = '<div class="plh-task-body">';
		$h  .= '<p class="plh-why"><span class="plh-lab">ليه؟</span> ' . self::md( $t['why'] ) . '</p>';
		$vid = 'video/' . $t['id'] . '.mp4';
		if ( ! empty( $t['video'] ) && self::has( $vid ) ) {
			$poster = 'video/' . $t['id'] . '.jpg';
			$h     .= '<div class="plh-video"><video controls preload="none" playsinline' . ( self::has( $poster ) ? ' data-poster="' . esc_url( self::media_url( $poster ) ) . '"' : '' ) . ' aria-label="' . esc_attr( 'فيديو: ' . $t['title'] ) . '"><source src="' . esc_url( self::media_url( $vid ) ) . '" type="video/mp4">';
			if ( self::has( 'video/' . $t['id'] . '.vtt' ) ) {
				$h .= '<track kind="captions" srclang="ar" label="عربي" src="' . esc_url( self::media_url( 'video/' . $t['id'] . '.vtt' ) ) . '">';
			}
			$h .= '</video><p class="plh-note">الفيديو صامت، والشرح مكتوب عليه. مابيشتغلش لوحده: اضغطوا ▶ لما تكونوا جاهزين.' . ( ! empty( $t['video_seconds'] ) ? ' (' . (int) $t['video_seconds'] . ' ثانية)' : '' ) . '</p></div>';
		}
		$h .= '<ol class="plh-steps">';
		foreach ( (array) $t['steps'] as $s ) {
			$h .= '<li>' . self::md( $s ) . '</li>';
		}
		$h .= '</ol>';
		if ( ! empty( $t['spec_table'] ) ) {
			$h .= self::spec_table();
		}
		if ( ! empty( $t['support'] ) ) {
			$h .= self::support_buttons( $context );
		}
		if ( ! empty( $t['table'] ) ) {
			$h .= '<table class="plh-table"><thead><tr><th scope="col">شايفين كده؟</th><th scope="col">اعملوا كده</th></tr></thead><tbody>';
			foreach ( $t['table'] as $r ) {
				$h .= '<tr><td>' . self::md( $r[0] ) . '</td><td>' . self::md( $r[1] ) . '</td></tr>';
			}
			$h .= '</tbody></table>';
		}
		if ( ! empty( $t['tip'] ) ) {
			$h .= '<p class="plh-tip"><span class="plh-ico" aria-hidden="true"></span><strong>نصيحة:</strong> ' . self::md( $t['tip'] ) . '</p>';
		}
		if ( ! empty( $t['warning'] ) ) {
			$h .= '<p class="plh-warn"><span class="plh-ico" aria-hidden="true"></span><strong>خلي بالكم:</strong> ' . self::md( $t['warning'] ) . '</p>';
		}
		foreach ( (array) $t['screenshots'] as $s ) {
			$h .= self::figure( $s, ! empty( $s['diagram'] ) ? 'is-diagram' : '' );
		}
		if ( ! empty( $t['pair'] ) ) {
			$p     = $t['pair'];
			$p_img = array( 'file' => 'pairs/' . $p['id'] . '.png', 'alt' => $p['title'], 'callouts' => array_map( fn( $txt, $i ) => array( 'n' => $i + 1, 'text' => $txt ), $p['legend'] ?? array(), array_keys( $p['legend'] ?? array() ) ) );
			$h    .= self::figure( $p_img, 'is-pair' );
		}
		if ( ! empty( $t['placeholders'] ) ) {
			$h .= '<div class="plh-ph">';
			foreach ( $t['placeholders'] as $ph ) {
				$h .= '<div class="plh-ph-box"><span class="plh-ico" aria-hidden="true"></span>' . self::md( $ph ) . '</div>';
			}
			$h .= '</div>';
		}
		if ( ! empty( $t['links'] ) ) {
			$h .= '<p class="plh-links"><strong>الشرح الرسمي:</strong></p><ul class="plh-links">';
			foreach ( $t['links'] as $l ) {
				$h .= '<li><a href="' . esc_url( $l['url'] ) . '" target="_blank" rel="noopener noreferrer"><bdi>' . esc_html( $l['label'] ) . '</bdi> ↗</a></li>';
			}
			$h .= '</ul>';
		}
		if ( 'panel' === $context ) {
			$h .= '<p><a class="plh-more" href="' . esc_url( admin_url( 'admin.php?page=' . self::SLUG . '&task=' . rawurlencode( $t['id'] ) . '#plh-' . $t['id'] ) ) . '">افتحوا الشرح كله في صفحة Help ←</a></p>';
		}
		return $h . '</div>';
	}

	/** "مقاسات كل الصور" — built from PL_Image_Spec, so it always matches the field notes and the yellow warning. */
	public static function spec_table() {
		$h = '<table class="plh-table plh-spec"><thead><tr><th scope="col">الصورة</th><th scope="col">ارفعوها بمقاس</th><th scope="col">أقل مقاس</th><th scope="col">النسبة</th><th scope="col">الفورمات</th><th scope="col">أقصى حجم</th></tr></thead><tbody>';
		foreach ( PL_Image_Spec::all() as $k => $s ) {
			$h .= '<tr><td><strong>' . esc_html( $s['ar'] ) . '</strong><br><small><bdi>' . esc_html( $s['where'] ) . '</bdi></small></td><td><bdi>' . (int) $s['upload'][0] . ' × ' . (int) $s['upload'][1] . '</bdi></td><td><bdi>' . (int) $s['min'][0] . ' × ' . (int) $s['min'][1] . '</bdi></td><td><bdi>' . esc_html( $s['ratio'] ) . '</bdi></td><td><bdi>' . esc_html( $s['formats'] ) . '</bdi></td><td><bdi>' . esc_html( PL_Image_Spec::fill( '{{spec:' . $k . '.max_kb}}' ) ) . '</bdi>' . ( ! empty( $s['duration'] ) ? '<br><small><bdi>' . esc_html( $s['duration'] ) . '</bdi></small>' : '' ) . '</td></tr>';
		}
		return $h . '</tbody></table>';
	}

	/** Website support (PL Website › Company), else the head office's first email / WhatsApp. */
	public static function support() {
		$email = sanitize_email( (string) get_option( 'options_pl_support_email', '' ) );
		$wa    = class_exists( 'PL_Fields' ) && PL_Fields::def( 'company', 'support_whatsapp' ) ? PL_Fields::get( 'options', PL_Fields::def( 'company', 'support_whatsapp' ) ) : '';
		$head  = class_exists( 'PL_Data' ) ? PL_Data::head_office() : null;
		if ( ! $email && $head ) {
			$email = (string) ( $head['emails'][0] ?? '' );
		}
		if ( ! $wa && $head ) {
			foreach ( (array) $head['phones'] as $p ) {
				if ( ! empty( $p['whatsapp'] ) ) {
					$wa = $p['number'];
					break;
				}
			}
		}
		return array( 'email' => is_email( $email ) ? $email : '', 'wa' => preg_replace( '/\D/', '', (string) $wa ) );
	}

	private static function support_buttons( $context ) {
		$s      = self::support();
		$screen = function_exists( 'get_current_screen' ) && get_current_screen() ? get_current_screen() : null;
		$where  = 'page' === $context ? 'PL Help' : ( $screen ? ( get_admin_page_title() ? get_admin_page_title() : $screen->id ) : 'wp-admin' );
		$msg    = 'Hello PL Team, I need help with the website: ' . wp_strip_all_tags( (string) $where );
		$h      = '<div class="plh-support">';
		if ( strlen( $s['wa'] ) >= 8 ) {
			$h .= '<a class="plh-btn plh-wa" href="' . esc_url( 'https://wa.me/' . $s['wa'] . '?text=' . rawurlencode( $msg ) ) . '" target="_blank" rel="noopener" data-msg="' . esc_attr( $msg ) . '">ابعتوا واتساب لـ <bdi>PL Team</bdi></a>';
		}
		if ( $s['email'] ) {
			$h .= '<a class="plh-btn plh-mail" href="' . esc_url( 'mailto:' . $s['email'] . '?subject=' . rawurlencode( 'Website help: ' . wp_strip_all_tags( (string) $where ) ) . '&body=' . rawurlencode( $msg . "\n\n" ) ) . '">ابعتوا إيميل لـ <bdi>PL Team</bdi></a>';
		}
		if ( '<div class="plh-support">' === $h ) {
			$h .= '<p class="plh-note">لسه مفيش وسيلة تواصل متسجلة: <bdi>PL Website › Company › Website support</bdi>.</p>';
		}
		return $h . '<p class="plh-note">الأرقام والإيميل دول من <bdi>PL Website › Company › Website support</bdi> (ولو فاضيين: المكتب الرئيسي).</p></div>';
	}

	public static function page() {
		$g = self::guide();
		echo '<div class="wrap plh" dir="rtl" lang="ar">';
		echo '<h1 class="screen-reader-text">PL Help</h1>';
		if ( ! $g ) {
			echo '<p>The guide isn’t installed yet (help/guide.json is missing).</p></div>';
			return;
		}
		$open  = isset( $_GET['task'] ) ? sanitize_key( wp_unslash( $_GET['task'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$tasks = self::tasks();
		$total = count( array_filter( $tasks, fn( $t ) => ! empty( $t['video'] ) && self::has( 'video/' . $t['id'] . '.mp4' ) ) );
		echo '<header class="plh-hero"><div class="plh-vf" aria-hidden="true"><i></i><i></i><i></i><i></i></div>';
		echo '<p class="plh-k" dir="ltr">PL WEBSITE · HELP</p><h2 class="plh-title">إزاي تديروا الموقع <em>لوحدكم.</em></h2>';
		echo '<p class="plh-lead">' . esc_html( count( $tasks ) . ' مهمة، و' . $total . ' فيديو قصير، بالخطوات والصور.' ) . ' وفي أي شاشة ليها شرح، زرار <strong>?</strong> الأزرق تحت على اليمين بيفتحه على الجنب.</p>';
		echo '<form class="plh-search" role="search" onsubmit="return false"><label for="plh-q">دوّروا في الشرح</label><input id="plh-q" type="search" placeholder="مثلاً: واتساب، Register now، CV" autocomplete="off"><p id="plh-count" class="plh-note" aria-live="polite"></p></form></header>';

		echo '<nav class="plh-chapters" aria-label="الفصول"><ol>';
		foreach ( (array) $g['chapters'] as $c ) {
			$n = count( array_filter( $tasks, fn( $t ) => $t['chapter'] === $c['id'] ) );
			echo '<li><a href="#plh-' . esc_attr( $c['id'] ) . '" class="plh-card' . ( 'c9' === $c['id'] ? ' is-danger' : '' ) . '"><span class="plh-cn" dir="ltr">' . esc_html( str_pad( (string) $c['n'], 2, '0', STR_PAD_LEFT ) ) . '</span><span class="plh-ct">' . esc_html( $c['title'] ) . '</span><span class="plh-cd">' . esc_html( $c['intro'] ) . '</span><span class="plh-cc">' . (int) $n . ' ' . ( $n > 2 ? 'مهام' : 'مهمة' ) . '</span></a></li>';
		}
		echo '</ol></nav>';

		foreach ( (array) $g['chapters'] as $c ) {
			echo '<section class="plh-chapter" id="plh-' . esc_attr( $c['id'] ) . '" aria-labelledby="plh-' . esc_attr( $c['id'] ) . '-h">';
			echo '<h2 class="plh-ch" id="plh-' . esc_attr( $c['id'] ) . '-h"><span dir="ltr">' . esc_html( str_pad( (string) $c['n'], 2, '0', STR_PAD_LEFT ) ) . '</span> ' . esc_html( $c['title'] ) . '</h2>';
			foreach ( $tasks as $t ) {
				if ( $t['chapter'] !== $c['id'] ) {
					continue;
				}
				if ( ! empty( $t['cheatsheet'] ) ) {
					self::cheatsheet( $t );
					continue;
				}
				$hay = wp_strip_all_tags( $t['title'] . ' ' . $t['why'] . ' ' . implode( ' ', (array) $t['steps'] ) . ' ' . ( $t['tip'] ?? '' ) . ' ' . ( $t['warning'] ?? '' ) );
				echo '<details class="plh-task' . ( ! empty( $t['danger'] ) ? ' is-danger' : '' ) . '" id="plh-' . esc_attr( $t['id'] ) . '" data-q="' . esc_attr( str_replace( '*', '', $hay ) ) . '"' . ( $open === $t['id'] ? ' open' : '' ) . '>';
				echo '<summary><span class="plh-st">' . self::md( $t['title'] ) . '</span>' . ( ! empty( $t['video'] ) && self::has( 'video/' . $t['id'] . '.mp4' ) ? '<span class="plh-badge">▶ فيديو</span>' : '' ) . ( ! empty( $t['danger'] ) ? '<span class="plh-badge is-danger">ممنوع</span>' : '' ) . '</summary>'; // phpcs:ignore WordPress.Security.EscapeOutput
				echo self::render_task( $t ); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts
				echo '</details>';
			}
			echo '</section>';
		}
		echo '<p class="plh-foot plh-note">آخر تحديث للشرح: ' . esc_html( (string) ( $g['updated'] ?? '' ) ) . '. الصور والفيديوهات من نسخة تجربة، فممكن تختلف في تفاصيل صغيرة.</p>';
		echo '</div>';
	}

	private static function cheatsheet( array $t ) {
		$top = array_filter( self::tasks(), fn( $x ) => ! empty( $x['cheat'] ) );
		usort( $top, fn( $a, $b ) => $a['cheat'] - $b['cheat'] );
		echo '<div class="plh-cheat" id="plh-cheatsheet"><div class="plh-cheat-head"><div><p class="plh-k" dir="ltr">PL WEBSITE · CHEAT SHEET</p><h3>' . esc_html( $t['title'] ) . '</h3><p class="plh-note">' . self::md( $t['why'] ) . ' الدخول: <bdi dir="ltr">plevents.org/plevents/</bdi></p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<button type="button" class="button button-primary plh-print" data-print="cheat">Print / Save as PDF</button></div><ol class="plh-cheat-list">';
		foreach ( $top as $x ) {
			echo '<li><h4>' . self::md( $x['title'] ) . '</h4><ol>'; // phpcs:ignore WordPress.Security.EscapeOutput
			foreach ( array_slice( (array) $x['steps'], 0, 4 ) as $s ) {
				echo '<li>' . self::md( $s ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</ol></li>';
		}
		$sp = self::support();
		echo '</ol><p class="plh-cheat-sizes">' . esc_html( PL_Image_Spec::fill( 'مقاسات الصور: الهوم {{spec:hero.upload}} · صورة إيفنت (Photo) {{spec:event_photo.upload}} · بوستر: الملف الأصلي، أقل حاجة {{spec:event_poster.min}} · لوجو SVG أو PNG شفاف.' ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<p class="plh-cheat-foot">ممنوع: ماتعدّلوش جوه Elementor، ولا Plugins / Themes / Settings. أي حاجة غريبة: screenshot + لينك الصفحة ← <bdi>PL Team</bdi>' . ( $sp['wa'] ? ' (واتساب <bdi dir="ltr">+' . esc_html( $sp['wa'] ) . '</bdi>)' : '' ) . ( $sp['email'] ? ' · <bdi>' . esc_html( $sp['email'] ) . '</bdi>' : '' ) . '.</p></div>';
	}

	/** "?" button + side panel on screens that have a task. */
	public static function panel() {
		if ( ! current_user_can( self::CAP ) || ( isset( $_GET['page'] ) && self::SLUG === $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$tasks = self::tasks_for_screen();
		if ( ! $tasks ) {
			return;
		}
		echo '<button type="button" id="plh-fab" class="plh-fab" aria-expanded="false" aria-controls="plh-panel" title="Help for this screen (?)"><span aria-hidden="true">?</span><span class="screen-reader-text">Help for this screen</span></button>';
		echo '<div id="plh-panel" class="plh plh-panel" role="dialog" aria-modal="false" aria-labelledby="plh-panel-h" dir="rtl" lang="ar" hidden tabindex="-1">';
		echo '<div class="plh-panel-head"><h2 id="plh-panel-h">الشرح لهذه الشاشة</h2><button type="button" class="plh-close" aria-label="Close help (Esc)">×</button></div><div class="plh-panel-body"></div></div>';
		echo '<template id="plh-panel-tpl">'; // built only when "?" is pressed: nothing loads on the screen before that
		foreach ( $tasks as $i => $t ) {
			echo '<details class="plh-task' . ( ! empty( $t['danger'] ) ? ' is-danger' : '' ) . '"' . ( 0 === $i ? ' open' : '' ) . '><summary><span class="plh-st">' . self::md( $t['title'] ) . '</span></summary>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo self::render_task( $t, 'panel' ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo '</details>';
		}
		echo '<p class="plh-note"><a href="' . esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ) . '">كل الشرح: PL Website › Help</a></p></template>';
	}
}
