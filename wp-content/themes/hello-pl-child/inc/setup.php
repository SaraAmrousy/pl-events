<?php
/**
 * Styles, fonts, body class and Hello Elementor defaults.
 *
 * @package hello-pl-child
 */

defined( 'ABSPATH' ) || exit;

/* Hello's own reset/theme CSS off: pl.css (from the prototype) is the whole look. */
add_filter( 'hello_elementor_enqueue_style', '__return_false' );
add_filter( 'hello_elementor_enqueue_theme_style', '__return_false' );
add_filter( 'hello_elementor_page_title', '__return_false' ); // H1s come from the page content.
add_filter( 'hello_elementor_description_meta_tag', '__return_false' ); // Rank Math writes it.

add_action( 'after_setup_theme', static function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	register_nav_menus( array( 'pl-main' => 'Main menu (header)', 'pl-footer' => 'Footer menu' ) );
}, 20 );

/* Every page is a PL page: tokens are scoped to .pl (prototype). */
add_filter( 'body_class', static fn( $c ) => array_merge( $c, array( 'pl' ) ) );

add_action( 'wp_enqueue_scripts', static function () {
	wp_enqueue_style( 'pl-theme', PLT_URL . 'assets/css/pl.css', array(), plt_ver( 'assets/css/pl.css' ) );
}, 20 );

/* Preload the main font file (LCP text) — the only preload on the page. */
add_action( 'wp_head', static function () {
	printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( PLT_URL . 'assets/fonts/montserrat-normal-latin.woff2' ) );
}, 1 );

/** Version = file time, so caches refresh on every deploy. */
function plt_ver( $rel ) {
	$f = PLT_DIR . $rel;
	return is_readable( $f ) ? (string) filemtime( $f ) : PLT_VERSION;
}

/* The plugin's templates ask the theme for the PL mark (hero aperture logo). */
add_filter( 'pl_brand_logo', static fn() => PLT_URL . 'assets/img/logo-ink-sm.png' ); // Organization schema logo.
add_filter( 'pl_brand_mark', static fn( $url, $variant = 'ink' ) => PLT_URL . 'assets/img/mark-' . ( 'white' === $variant ? 'white' : 'ink' ) . '.png', 10, 2 );

/* Elementor "Full Width" pages print no <main>: add it, so the skip link (#content) lands
   somewhere and screen readers get the main landmark. */
add_action( 'elementor/page_templates/header-footer/before_content', static function () {
	echo '<main id="content" class="site-main" tabindex="-1">';
} );
add_action( 'elementor/page_templates/header-footer/after_content', static function () {
	echo '</main>';
} );

/* 6.1b: no site icon yet → browsers asked the domain root for /favicon.ico (404 on every page, Lighthouse
   "errors in console"). An empty icon stops that request and changes nothing visible. When PL picks a
   favicon, set it in Appearance › Customize › Site Identity (WordPress then prints its own icon tags). */
add_action( 'wp_head', static function () {
	if ( ! has_site_icon() ) {
		echo '<link rel="icon" href="data:,">' . "\n";
	}
}, 2 );

