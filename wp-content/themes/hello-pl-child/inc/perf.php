<?php
/**
 * Front-end diet (Step 4.4, from the mobile-4G Lighthouse run). Front end only — never in
 * wp-admin or the Elementor editor/preview, where these assets are needed.
 *  - UAE HFB widget styles + Font Awesome: our header/footer use none of HFB's widgets.
 *  - Hello's header/footer CSS: HFB replaces Hello's header/footer.
 *  - jQuery to the footer, without jquery-migrate: nothing in <head> needs it.
 *
 * @package hello-pl-child
 */

defined( 'ABSPATH' ) || exit;

function plt_is_builder_request() {
	if ( is_admin() || is_customize_preview() ) {
		return true;
	}
	if ( isset( $_GET['elementor-preview'] ) || isset( $_GET['elementor_library'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return true;
	}
	return class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->preview ) && \Elementor\Plugin::$instance->preview->is_preview_mode();
}

add_action( 'wp_enqueue_scripts', static function () {
	if ( plt_is_builder_request() ) {
		return;
	}
	foreach ( array( 'hfe-widgets-style', 'hfe-elementor-icons', 'hfe-icons-list', 'hfe-social-icons', 'hfe-social-share-icons-brands', 'hfe-social-share-icons-fontawesome', 'hfe-nav-menu-icons', 'hello-elementor-header-footer', 'font-awesome', 'elementor-icons-fa-solid', 'elementor-icons-fa-brands', 'elementor-icons-shared-0', 'widget-icon-list', 'widget-social-icons' ) as $h ) {
		wp_dequeue_style( $h );
	}
}, 999 );

/* jQuery: footer, no migrate (front end only). */
add_action( 'wp_default_scripts', static function ( $scripts ) {
	if ( plt_is_builder_request() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( isset( $scripts->registered['jquery'] ) ) {
		$scripts->registered['jquery']->deps = array( 'jquery-core' );
	}
	foreach ( array( 'jquery', 'jquery-core', 'jquery-migrate' ) as $h ) {
		if ( isset( $scripts->registered[ $h ] ) ) {
			$scripts->add_data( $h, 'group', 1 );
		}
	}
} );
