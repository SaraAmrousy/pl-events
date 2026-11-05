<?php
/**
 * Plugin Name: PL Hello (deploy test)
 * Description: Proves the one-command deploy works. Safe to delete.
 * Version: 1.0.0
 */
defined( 'ABSPATH' ) || exit;
add_action( 'rest_api_init', function () {
	register_rest_route( 'pl-hello/v1', '/ping', [
		'methods'             => 'GET',
		'permission_callback' => fn() => current_user_can( 'manage_options' ),
		'callback'            => fn() => [ 'ok' => true, 'plugin' => 'pl-hello', 'php' => PHP_VERSION, 'wp' => get_bloginfo( 'version' ), 'time' => gmdate( 'c' ) ],
	] );
} );
