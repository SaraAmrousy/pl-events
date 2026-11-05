#!/usr/bin/env bash
# Finishes staging setup (idempotent). Done earlier by API (docs/Step-4.0-Setup.md):
# PHP 8.3 for /pl, DB, WordPress, wp-config, admin "sara", deploy user.
# This script: staging guard mu-plugin (noindex), Hello Elementor + the free plugins,
# permalinks/timezone, then the 4.0 DoD test (deploy + activate pl-hello).
. "$(dirname "$0")/lib.sh"
load_env

say "mu-plugin: staging guard (noindex)"
cp_ls "$STAGING_DIR/wp-content" | grep -qx mu-plugins || api2 Fileman mkdir "path=$STAGING_DIR/wp-content" name=mu-plugins >/dev/null
cp_upload "$DEPLOY_DIR/mu/pl-staging.php" "$STAGING_DIR/wp-content/mu-plugins"
check_site

say "theme + free plugins from WordPress.org (downloaded by the server itself)"
run_php '
require_once ABSPATH . "wp-admin/includes/class-wp-upgrader.php";
require_once ABSPATH . "wp-admin/includes/plugin-install.php";
require_once ABSPATH . "wp-admin/includes/theme-install.php";
$skin = new Automatic_Upgrader_Skin();
if ( ! wp_get_theme( "hello-elementor" )->exists() ) { $r = ( new Theme_Upgrader( $skin ) )->install( "https://downloads.wordpress.org/theme/hello-elementor.latest-stable.zip" ); echo "theme hello-elementor: ", is_wp_error( $r ) ? $r->get_error_message() : "installed", PHP_EOL; }
switch_theme( "hello-elementor" ); echo "active theme: ", get_stylesheet(), PHP_EOL;
foreach ( array( "secure-custom-fields", "elementor", "header-footer-elementor", "forminator", "seo-by-rank-math" ) as $slug ) {
	$all = get_plugins(); $file = "";
	foreach ( $all as $f => $d ) { if ( 0 === strpos( $f, $slug . "/" ) ) { $file = $f; } }
	if ( ! $file ) {
		$r = ( new Plugin_Upgrader( new Automatic_Upgrader_Skin() ) )->install( "https://downloads.wordpress.org/plugin/" . $slug . ".latest-stable.zip" );
		if ( is_wp_error( $r ) || ! $r ) { echo "plugin $slug: install FAILED ", is_wp_error( $r ) ? $r->get_error_message() : "", PHP_EOL; continue; }
		wp_clean_plugins_cache(); foreach ( get_plugins() as $f => $d ) { if ( 0 === strpos( $f, $slug . "/" ) ) { $file = $f; } }
	}
	$a = activate_plugin( $file, "", false, true );
	$v = get_plugins()[ $file ]["Version"] ?? "";
	echo "plugin $file $v: ", is_wp_error( $a ) ? "ERROR " . $a->get_error_message() : "active", PHP_EOL;
}
delete_transient( "elementor_activation_redirect" ); delete_transient( "_hfe_activation_redirect" ); delete_option( "rank_math_redirect_about" );
update_option( "permalink_structure", "/%postname%/" ); update_option( "timezone_string", "Africa/Cairo" ); update_option( "default_comment_status", "closed" ); update_option( "blog_public", "0" );
flush_rewrite_rules();
echo "settings: permalinks /%postname%/, Africa/Cairo, comments closed", PHP_EOL;'

say "deploy test (4.0 DoD): pl-hello"
"$DEPLOY_DIR/deploy.sh" plugin "$DEPLOY_DIR/test-plugin/pl-hello"
run_php 'echo wp_json_encode( rest_do_request( new WP_REST_Request( "GET", "/pl-hello/v1/ping" ) )->get_data() ), PHP_EOL;'
say "noindex header: $(curl -sI "$STAGING_URL/" | grep -i x-robots-tag | tr -d '\r')"
