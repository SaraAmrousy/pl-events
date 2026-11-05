<?php
/**
 * Plugin Name: PL sandbox helpers (tests only — never deployed)
 * Before the child theme existed (4.1), this loaded the prototype CSS so shortcode output
 * could be compared with the prototype. With hello-pl-child active it does nothing.
 */
add_action( 'after_setup_theme', function () {
	if ( 'hello-pl-child' === get_stylesheet() ) {
		return;
	}
	add_action( 'wp_enqueue_scripts', function () {
		foreach ( array( 'base', 'proto' ) as $f ) {
			wp_enqueue_style( "pl-proto-$f", home_url( "/_proto/$f.css" ), array(), null );
		}
		foreach ( array( 'hello-elementor', 'hello-elementor-theme-style', 'hello-elementor-header-footer' ) as $h ) {
			wp_dequeue_style( $h );
		}
	}, 99 );
	add_filter( 'body_class', fn( $c ) => array_merge( $c, array( 'pl' ) ) );
	add_filter( 'hello_elementor_page_title', '__return_false' );
	add_action( 'wp_head', function () {
		echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400..900;1,500&display=swap">';
		echo '<style>#site-header,.site-header,#site-footer,.site-footer{display:none}body{margin:0}.site-main,.page-content{max-width:none;padding:0;margin:0}</style>';
	} );
} );
