<?php
/**
 * Plugin Name: PL sandbox helpers (tests only — never deployed)
 * Loads the prototype CSS so shortcode output can be compared with the prototype
 * before the child theme (4.2) exists.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( wp_get_theme()->get_stylesheet() === 'hello-pl-child' ) { return; }
	foreach ( [ 'base', 'proto' ] as $f ) {
		wp_enqueue_style( "pl-proto-$f", home_url( "/_proto/$f.css" ), [], null );
	}
} );
add_filter( 'body_class', fn( $c ) => array_merge( $c, array( 'pl' ) ) );
add_action( 'wp_head', function () { echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400..900;1,500&display=swap">'; } );
// Sandbox: drop Hello's own reset/look and page titles so only the prototype CSS styles the output.
add_action( 'wp_enqueue_scripts', function () { foreach ( array( 'hello-elementor', 'hello-elementor-theme-style', 'hello-elementor-header-footer' ) as $h ) { wp_dequeue_style( $h ); } }, 99 );
add_filter( 'hello_elementor_page_title', '__return_false' );
add_action( 'wp_head', function () { echo '<style>#site-header,.site-header,#site-footer,.site-footer{display:none}body{margin:0}.site-main,.page-content{max-width:none;padding:0;margin:0}</style>'; } );
