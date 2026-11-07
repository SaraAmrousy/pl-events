<?php
/**
 * Boot, modules, roles/capabilities, activation and versioned upgrades.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Plugin {

	/** @var PL_Module[] post type => module */
	private static $modules = array();

	/** Capabilities shared by every PL content type (capability_type "pl_item"). */
	const ITEM_CAPS = array( 'edit_pl_items', 'edit_others_pl_items', 'edit_published_pl_items', 'edit_private_pl_items', 'publish_pl_items', 'read_private_pl_items', 'delete_pl_items', 'delete_others_pl_items', 'delete_published_pl_items', 'delete_private_pl_items' );

	public static function boot() {
		foreach ( glob( PL_EVENTS_DIR . 'modules/*/module.php' ) as $file ) {
			$module = require $file;
			if ( $module instanceof PL_Module ) {
				self::$modules[ $module->type ] = $module;
			}
		}
		PL_Fields::init();
		PL_Cache::init();
		PL_Taxonomies::init();
		add_action( 'init', array( __CLASS__, 'init' ), 1 ); // before SCF fires acf/init (init:5).
		add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade' ) );
		PL_Admin::init();
		PL_Shortcodes::init();
		PL_Event_Page::init();
		PL_SEO::init();
		PL_Images::init();
		PL_Guardrails::init();
		PL_Newsletter::init();
		PL_Login::init();
		PL_Importer::init();
		do_action( 'pl_loaded' );
	}

	public static function init() {
		foreach ( self::$modules as $m ) {
			$m->register();
			$m->register_fields();
		}
		do_action( 'pl_registered' );
	}

	public static function modules() {
		return self::$modules;
	}

	public static function module( $type ) {
		return self::$modules[ $type ] ?? null;
	}

	public static function post_types() {
		return array_values( array_filter( array_keys( self::$modules ), 'post_type_exists' ) );
	}

	public static function activate() {
		self::boot_for_activation();
		self::add_roles();
		if ( ! wp_next_scheduled( PL_Cache::CRON_HOOK ) ) {
			wp_schedule_event( time() + 300, 'hourly', PL_Cache::CRON_HOOK );
		}
		self::maybe_upgrade();
		flush_rewrite_rules();
	}

	/** Deactivation never deletes content (Step 2.2 §8 "Data survives the plugin"). */
	public static function deactivate() {
		wp_clear_scheduled_hook( PL_Cache::CRON_HOOK );
		flush_rewrite_rules();
	}

	private static function boot_for_activation() {
		if ( ! did_action( 'pl_loaded' ) ) {
			self::boot();
		}
		self::init();
		PL_Taxonomies::register();
		PL_Event_Page::rewrite();
	}

	public static function add_roles() {
		$admin = get_role( 'administrator' );
		foreach ( self::ITEM_CAPS as $cap ) {
			$admin && $admin->add_cap( $cap );
		}
		$admin && $admin->add_cap( 'manage_pl_terms' );
		$caps = array( 'read' => true, 'upload_files' => true, 'manage_pl_terms' => true );
		foreach ( self::ITEM_CAPS as $cap ) {
			$caps[ $cap ] = true;
		}
		remove_role( 'pl_editor' );
		add_role( 'pl_editor', 'PL Editor', $caps );
	}

	/** Versioned upgrades: add a method upgrade_{n}() to move old data when the model changes. */
	public static function maybe_upgrade() {
		$current = (int) get_option( 'pl_events_db_version', 0 );
		if ( $current >= PL_EVENTS_DB_VERSION ) {
			return;
		}
		for ( $v = $current + 1; $v <= PL_EVENTS_DB_VERSION; $v++ ) {
			if ( method_exists( __CLASS__, 'upgrade_' . $v ) ) {
				call_user_func( array( __CLASS__, 'upgrade_' . $v ) );
			}
			update_option( 'pl_events_db_version', $v, true );
		}
	}

	/** v1: first install — roles + default admin lists. */
	private static function upgrade_1() {
		self::add_roles();
		PL_Taxonomies::seed_defaults();
	}
}
