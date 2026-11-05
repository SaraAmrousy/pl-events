<?php
/**
 * Layout helpers for the Elementor pages (Step 4.4). Each page section is one Elementor
 * "Shortcode" widget holding [pl_section]…[/pl_section]: the prototype's static copy as HTML
 * plus the plugin's [pl_*] shortcodes. Layout only — no data here.
 *
 *   [pl_section class="sec" id="" style="" wrap="1" wrap_class="" wrap_style="…"] … [/pl_section]
 *   [pl_form name="contact|careers"]   the Forminator form chosen in Step 4.5
 *   [pl_media src="assets/hall-wide.jpg" alt="…" class="photo rack" style="…"]   a seed photo from the Media Library
 *
 * @package hello-pl-child
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'pl_section', static function ( $atts, $content = '' ) {
	$a = shortcode_atts( array( 'tag' => 'section', 'class' => 'sec', 'id' => '', 'style' => '', 'label' => '', 'wrap' => '1', 'wrap_class' => '', 'wrap_style' => '' ), $atts, 'pl_section' );
	$tag   = in_array( $a['tag'], array( 'section', 'div', 'aside' ), true ) ? $a['tag'] : 'section';
	$inner = do_shortcode( shortcode_unautop( (string) $content ) );
	if ( '1' === (string) $a['wrap'] ) {
		$inner = sprintf( '<div class="%s"%s>%s</div>', esc_attr( trim( 'wrap ' . $a['wrap_class'] ) ), $a['wrap_style'] ? ' style="' . esc_attr( $a['wrap_style'] ) . '"' : '', $inner );
	}
	return sprintf(
		'<%1$s class="%2$s"%3$s%4$s%5$s>%6$s</%1$s>',
		$tag,
		esc_attr( $a['class'] ),
		$a['id'] ? ' id="' . esc_attr( $a['id'] ) . '"' : '',
		$a['style'] ? ' style="' . esc_attr( $a['style'] ) . '"' : '',
		$a['label'] ? ' aria-label="' . esc_attr( $a['label'] ) . '"' : '',
		$inner
	);
} );

/* Forms are created in Step 4.5; their IDs are kept in an option so pages never hard-code them. */
add_shortcode( 'pl_form', static function ( $atts ) {
	$a   = shortcode_atts( array( 'name' => 'contact' ), $atts, 'pl_form' );
	$ids = (array) get_option( 'plt_forms', array() );
	$id  = (int) ( $ids[ $a['name'] ] ?? 0 );
	if ( ! $id || ! shortcode_exists( 'forminator_form' ) ) {
		return current_user_can( 'edit_pages' ) ? '<p class="mut">[Form "' . esc_html( $a['name'] ) . '" not set up yet — Step 4.5]</p>' : '';
	}
	return do_shortcode( '[forminator_form id="' . $id . '"]' );
} );

/* Seed photos (from PL's portfolio) live in the Media Library; pages refer to them by source name. */
add_shortcode( 'pl_media', static function ( $atts ) {
	$a  = shortcode_atts( array( 'src' => '', 'alt' => '', 'class' => '', 'style' => '', 'size' => 'large', 'loading' => 'lazy', 'frame' => '', 'sizes' => '100vw', 'frame_style' => '' ), $atts, 'pl_media' );
	if ( '' === trim( $a['src'] ) ) {
		return ''; // an empty src would otherwise match any attachment
	}
	$id = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'meta_key' => '_pl_source', 'meta_value' => $a['src'], 'fields' => 'ids' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( ! $id ) {
		return '';
	}
	if ( $a['frame'] && class_exists( 'PL_Images' ) ) { // fixed frame: server crop + cover (Image fit, Thread 8)
		$img = PL_Images::img( $id[0], $a['frame'], array( 'alt' => $a['alt'], 'class' => $a['class'], 'sizes' => $a['sizes'], 'eager' => 'eager' === $a['loading'] ) );
		return '<div class="pl-frame pl-ar-' . esc_attr( $a['frame'] ) . '" style="' . esc_attr( $a['frame_style'] ) . '">' . $img . '</div>';
	}
	return wp_get_attachment_image( $id[0], $a['size'], false, array( 'class' => $a['class'], 'style' => $a['style'], 'alt' => $a['alt'], 'loading' => $a['loading'], 'decoding' => 'async' ) );
} );

/* Internal links that survive the sub-folder (/pl/) and the move to plevents.org:
   [pl_url to="contact" topic="proposal" office="" anchor=""] → https://…/contact/?topic=proposal */
add_shortcode( 'pl_url', static function ( $atts ) {
	$a   = shortcode_atts( array( 'to' => 'home', 'topic' => '', 'office' => '', 'anchor' => '' ), $atts, 'pl_url' );
	$url = function_exists( 'plt_page_url' ) ? plt_page_url( sanitize_title( $a['to'] ) ) : home_url( '/' );
	$url = add_query_arg( array_filter( array( 'topic' => $a['topic'], 'office' => $a['office'] ) ), $url );
	return esc_url( $url . ( $a['anchor'] ? '#' . sanitize_title( $a['anchor'] ) : '' ) );
} );

/* [pl_sample label="Text TBC"] — the Sample tag (shown while Company › Show "Sample" tags is on). */
add_shortcode( 'pl_sample', static function ( $atts ) {
	$a = shortcode_atts( array( 'label' => 'Sample' ), $atts, 'pl_sample' );
	return class_exists( 'PL_Template' ) ? PL_Template::sample_tag( $a['label'] ) : '';
} );

/* [pl_credits] — dataset attribution (Step 2.2 critique 7: credits line on the Privacy page). */
add_shortcode( 'pl_credits', static function () {
	return esc_html( ( class_exists( 'PL_Geo' ) ? PL_Geo::credits() . ' · ' : '' ) . 'Flags: flag-icons (MIT) · Font: Montserrat (SIL OFL 1.1)' );
} );
