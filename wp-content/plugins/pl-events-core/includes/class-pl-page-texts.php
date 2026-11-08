<?php
/**
 * Page texts (6.1b round 7): every fixed sentence on the pages is a field in PL Website › Page texts,
 * so nobody edits HTML inside Elementor. The pages print [pl_t k="…"] where the words used to be.
 *  - Registry (defaults, labels, where on the site, soft limits): data/page-texts.json, built by
 *    tools/build-elementor.py from the same table that builds the pages. Defaults = the original text,
 *    so the site doesn't change until someone edits a field.
 *  - Highlighted word (italic, blue) = its own field; no HTML is ever accepted (tags are removed).
 *  - Empty field = back to the original text. "Reset to original" per field (works without JS).
 *  - Saving clears every page cache (PL cache, LiteSpeed, Elementor's element cache).
 *  - SEO titles / descriptions stay in Rank Math.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Page_Texts {

	const OPT  = 'pl_page_texts';
	const SLUG = 'pl-page-texts';

	/** Rough characters per line on a 375 px phone, by kind (for "≈ N lines on phones"). */
	const CPL = array( 'label' => 30, 'h1' => 13, 'h2' => 17, 'lead' => 33, 'text' => 36, 'button' => 22, 'caption' => 38 );

	const PAGES = array( 'home' => 'Home', 'events' => 'Events', 'services' => 'Services', 'about' => 'About', 'careers' => 'Careers', 'contact' => 'Contact', 'privacy' => 'Privacy' );

	private static $reg = null;

	public static function init() {
		add_shortcode( 'pl_t', array( __CLASS__, 'shortcode' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 21 );
		add_action( 'admin_post_pl_page_texts_save', array( __CLASS__, 'save' ) );
	}

	/** key => definition. */
	public static function registry() {
		if ( null === self::$reg ) {
			$d         = json_decode( (string) file_get_contents( PL_EVENTS_DIR . 'data/page-texts.json' ), true );
			self::$reg = array();
			foreach ( (array) ( $d['texts'] ?? array() ) as $t ) {
				self::$reg[ $t['key'] ] = $t;
			}
		}
		return self::$reg;
	}

	/** Current [text, hl] (saved value, else the original). */
	public static function value( $key ) {
		$def   = self::registry()[ $key ] ?? null;
		$saved = (array) ( get_option( self::OPT, array() )[ $key ] ?? array() );
		if ( ! $def ) {
			return array( '', '' );
		}
		$text = isset( $saved['text'] ) && '' !== $saved['text'] ? $saved['text'] : $def['text'];
		$hl   = array_key_exists( 'hl', $saved ) ? $saved['hl'] : $def['hl'];
		return array( $text, $hl );
	}

	public static function render( $key ) {
		list( $text, $hl ) = self::value( $key );
		$html = esc_html( $text );
		if ( false !== strpos( $html, '{since}' ) ) {
			$html = str_replace( '{since}', esc_html( (string) ( PL_Data::stats()['since_long'] ?? '' ) ), $html );
		}
		return $html . ( '' !== (string) $hl ? ( '' !== $html ? ' ' : '' ) . '<span class="em">' . esc_html( $hl ) . '</span>' : '' );
	}

	public static function shortcode( $atts ) {
		$a = shortcode_atts( array( 'k' => '' ), $atts, 'pl_t' );
		return self::render( sanitize_key( $a['k'] ) );
	}

	/** Plain text for an attribute (e.g. a button label passed to another shortcode). */
	public static function plain( $key ) {
		list( $text, $hl ) = self::value( $key );
		return trim( $text . ( '' !== (string) $hl ? ' ' . $hl : '' ) );
	}

	/** Strip tags/shortcodes, collapse spaces. Returns [clean, had_html]. */
	public static function clean( $v ) {
		$v     = (string) $v;
		$clean = wp_strip_all_tags( strip_shortcodes( $v ) );
		$clean = trim( preg_replace( '/\s+/u', ' ', str_replace( array( '[', ']' ), '', $clean ) ) );
		return array( $clean, $clean !== trim( preg_replace( '/\s+/u', ' ', $v ) ) );
	}

	public static function page_url( $slug ) {
		if ( 'home' === $slug ) {
			return home_url( '/' );
		}
		$pg = get_page_by_path( $slug );
		return $pg ? get_permalink( $pg ) : home_url( '/' . $slug . '/' );
	}

	public static function lines( $len, $kind ) {
		return (int) max( 1, ceil( $len / ( self::CPL[ $kind ] ?? 34 ) ) );
	}

	/* ------------------------------------------------------------------ admin */

	public static function menu() {
		add_submenu_page( 'pl-website', 'Page texts', 'Page texts', 'edit_pl_items', self::SLUG, array( __CLASS__, 'page' ), 3 );
	}

	public static function page() {
		$reg   = self::registry();
		$saved = (array) get_option( self::OPT, array() );
		echo '<div class="wrap pl-pt"><h1>Page texts</h1>';
		echo '<p class="description" style="font-size:14px;max-width:760px">The fixed sentences on each page. Type plain words: no HTML. The <strong>Highlighted word</strong> is shown in italic blue at the end of a heading. An empty field goes back to the original text. SEO titles and descriptions are in <strong>Rank Math</strong>, not here.</p>';
		if ( isset( $_GET['saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success is-dismissible"><p>Saved. The pages show the new text now.</p></div>';
		}
		$notes = get_transient( 'pl_pt_notes_' . get_current_user_id() );
		if ( $notes ) {
			delete_transient( 'pl_pt_notes_' . get_current_user_id() );
			foreach ( (array) $notes as $n ) {
				echo '<div class="notice notice-warning"><p>' . wp_kses_post( $n ) . '</p></div>';
			}
		}
		echo '<p class="pl-pt-nav">';
		foreach ( self::PAGES as $p => $name ) {
			printf( '<a class="button" href="#pl-pt-%s">%s</a> ', esc_attr( $p ), esc_html( $name ) );
		}
		echo '</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'pl_page_texts' );
		echo '<input type="hidden" name="action" value="pl_page_texts_save">';
		foreach ( self::PAGES as $p => $name ) {
			echo '<h2 id="pl-pt-' . esc_attr( $p ) . '" style="margin-top:28px;padding-top:8px;border-top:1px solid #dcdcde">' . esc_html( $name ) . ' <a style="font-size:13px;font-weight:400" href="' . esc_url( self::page_url( $p ) ) . '" target="_blank" rel="noopener">View page ↗</a></h2><table class="form-table" role="presentation"><tbody>';
			foreach ( $reg as $k => $t ) {
				if ( $t['page'] !== $p ) {
					continue;
				}
				list( $text, $hl ) = self::value( $k );
				$changed = isset( $saved[ $k ] );
				$long    = in_array( $t['kind'], array( 'lead', 'text' ), true );
				$id      = 'pl-pt-' . $k;
				echo '<tr data-kind="' . esc_attr( $t['kind'] ) . '" data-max="' . (int) $t['max'] . '" data-cpl="' . (int) ( self::CPL[ $t['kind'] ] ?? 34 ) . '" data-default="' . esc_attr( $t['text'] ) . '" data-default-hl="' . esc_attr( $t['hl'] ) . '"><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $t['label'] ) . '</label><p class="description" style="font-weight:400">' . esc_html( $t['where'] ) . ( $changed ? ' · <strong>changed</strong>' : '' ) . '</p></th><td>';
				if ( $long ) {
					echo '<textarea class="large-text pl-pt-text" rows="3" id="' . esc_attr( $id ) . '" name="pt[' . esc_attr( $k ) . '][text]">' . esc_textarea( $text ) . '</textarea>';
				} else {
					echo '<input type="text" class="regular-text pl-pt-text" style="width:min(560px,100%)" id="' . esc_attr( $id ) . '" name="pt[' . esc_attr( $k ) . '][text]" value="' . esc_attr( $text ) . '">';
				}
				if ( '' !== (string) $t['hl'] ) {
					echo '<p style="margin:6px 0 0"><label>Highlighted word <input type="text" class="pl-pt-hl" style="width:200px" name="pt[' . esc_attr( $k ) . '][hl]" value="' . esc_attr( (string) $hl ) . '"></label> <span class="description">shown after the text, in italic blue</span></p>';
				}
				$len = mb_strlen( $text . ( $hl ? ' ' . $hl : '' ) );
				$over = $len > (int) $t['max'];
				echo '<p class="pl-pt-count description" aria-live="polite" style="margin:6px 0 0' . ( $over ? ';color:#8A5A00;font-weight:600' : '' ) . '">' . (int) $len . ' / ' . (int) $t['max'] . ' characters · ≈ ' . (int) self::lines( $len, $t['kind'] ) . ' line(s) on phones' . ( $over ? ' — longer than the design expects; it will wrap to more lines.' : '' ) . '</p>';
				echo '<p style="margin:6px 0 0"><button type="submit" class="button-link pl-pt-reset" name="pl_reset" value="' . esc_attr( $k ) . '">Reset to original</button> <span class="description">Original: “' . esc_html( trim( $t['text'] . ' ' . $t['hl'] ) ) . '”</span></p>';
				echo '</td></tr>';
			}
			echo '</tbody></table>';
		}
		submit_button( 'Save page texts' );
		echo '</form></div>';
		self::inline_js();
	}

	private static function inline_js() {
		?>
<script>
(function () {
  document.querySelectorAll('.pl-pt tr[data-max]').forEach(function (tr) {
    var t = tr.querySelector('.pl-pt-text'), h = tr.querySelector('.pl-pt-hl'), c = tr.querySelector('.pl-pt-count');
    var max = +tr.dataset.max, cpl = +tr.dataset.cpl;
    function upd() {
      var v = (t.value + (h && h.value ? ' ' + h.value : '')).trim(), n = Array.from(v).length, lines = Math.max(1, Math.ceil(n / cpl));
      var html = /<[^>]+>|\[[^\]]*\]/.test(t.value + (h ? h.value : ''));
      c.textContent = n + ' / ' + max + ' characters · ≈ ' + lines + ' line(s) on phones' + (n > max ? ' — longer than the design expects; it will wrap to more lines.' : '') + (html ? ' HTML and [codes] are removed when you save.' : '') + (!t.value.trim() ? ' Empty = the original text.' : '');
      c.style.color = n > max || html ? '#8A5A00' : ''; c.style.fontWeight = n > max || html ? '600' : '';
    }
    t.addEventListener('input', upd); if (h) h.addEventListener('input', upd);
  });
})();
</script>
		<?php
	}

	public static function save() {
		if ( ! current_user_can( 'edit_pl_items' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'pl_page_texts' );
		$reg   = self::registry();
		$saved = (array) get_option( self::OPT, array() );
		$in    = isset( $_POST['pt'] ) && is_array( $_POST['pt'] ) ? wp_unslash( $_POST['pt'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- cleaned below
		$reset = isset( $_POST['pl_reset'] ) ? sanitize_key( wp_unslash( $_POST['pl_reset'] ) ) : '';
		$notes = array();
		foreach ( $reg as $k => $def ) {
			if ( $reset === $k ) {
				unset( $saved[ $k ] );
				$notes[] = sprintf( '“%s” is back to the original text.', esc_html( $def['label'] ) );
				continue;
			}
			if ( ! isset( $in[ $k ] ) ) {
				continue;
			}
			list( $text, $h1 ) = self::clean( $in[ $k ]['text'] ?? '' );
			list( $hl, $h2 )   = self::clean( $in[ $k ]['hl'] ?? $def['hl'] );
			if ( $h1 || $h2 ) {
				$notes[] = sprintf( '“%s”: HTML and [codes] were removed. Type plain words only.', esc_html( $def['label'] ) );
			}
			if ( '' === $text ) {
				$text = $def['text']; // empty = original
			}
			if ( $text === $def['text'] && $hl === (string) $def['hl'] ) {
				unset( $saved[ $k ] );
				continue;
			}
			$saved[ $k ] = array( 'text' => $text, 'hl' => $hl );
			$len         = mb_strlen( trim( $text . ' ' . $hl ) );
			if ( $len > (int) $def['max'] ) {
				$notes[] = sprintf( '“%s” has %d characters (the design expects up to %d): it will wrap to about %d lines on phones. Shorter reads better.', esc_html( $def['label'] ), $len, (int) $def['max'], self::lines( $len, $def['kind'] ) );
			}
		}
		update_option( self::OPT, $saved, false );
		self::purge();
		if ( $notes ) {
			set_transient( 'pl_pt_notes_' . get_current_user_id(), $notes, 120 );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&saved=1' ) );
		exit;
	}

	/** Every cache that could still hold the old words. */
	public static function purge() {
		PL_Cache::bump();
		if ( method_exists( 'PL_Cache', 'purge_page_caches' ) ) {
			PL_Cache::purge_page_caches();
		}
		delete_post_meta_by_key( '_elementor_element_cache' );
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
		do_action( 'litespeed_purge_all' );
	}
}
