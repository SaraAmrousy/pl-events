<?php
/**
 * Plugin Name:       PL Events Core
 * Description:       Content engine for the PL Events site: events, offices, societies, sponsors, services, careers, memberships, company settings, the data layer and all [pl_*] shortcodes.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Requires Plugins:  secure-custom-fields
 * Author:            Sara Amrousy
 * Text Domain:       pl-events-core
 *
 * Structure (Step 2.2 §8 "Built to grow"):
 *   includes/            shared services (dates, geo, phones, cache), field registry, data layer, templates, shortcodes
 *   modules/{type}/      one folder per content type: module.php (CPT/taxonomies) + fields.php (field registry)
 *   templates/           HTML partials, overridable from the theme in {theme}/pl-events/{file}.php
 * Data survives the plugin: deactivating or deleting it never deletes content.
 */

defined( 'ABSPATH' ) || exit;

define( 'PL_EVENTS_VERSION', '0.1.0' );
define( 'PL_EVENTS_DB_VERSION', 1 );
define( 'PL_EVENTS_FILE', __FILE__ );
define( 'PL_EVENTS_DIR', plugin_dir_path( __FILE__ ) );
define( 'PL_EVENTS_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	static function ( $class ) {
		if ( 0 !== strpos( $class, 'PL_' ) ) {
			return;
		}
		$file = 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
		foreach ( array( 'includes/', 'includes/services/' ) as $dir ) {
			if ( is_readable( PL_EVENTS_DIR . $dir . $file ) ) {
				require PL_EVENTS_DIR . $dir . $file;
				return;
			}
		}
	}
);

register_activation_hook( __FILE__, array( 'PL_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PL_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'PL_Plugin', 'boot' ) );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require PL_EVENTS_DIR . 'includes/cli.php';
}
