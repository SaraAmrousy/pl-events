<?php
/**
 * One cache for computed data and shortcode HTML (Step 2.2 §5, D9).
 * A version number in the key: bumping it invalidates everything at once.
 * Bumped on any save of PL content, the Company page, list terms, and by the hourly
 * status check when an event changes status (Live / Past) — then page caches are purged.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Cache {

	const VERSION_OPTION = 'pl_cache_version';
	const STATUS_OPTION  = 'pl_status_signature';
	const CRON_HOOK      = 'pl_hourly_status_check';

	public static function init() {
		add_action( 'save_post', array( __CLASS__, 'on_save_post' ), 20, 2 );
		add_action( 'deleted_post', array( __CLASS__, 'on_delete_post' ), 20, 2 );
		add_action( 'trashed_post', array( __CLASS__, 'flush_for_post' ) );
		add_action( 'acf/save_post', array( __CLASS__, 'on_acf_save' ), 30 );
		foreach ( array( 'created_term', 'edited_term', 'delete_term' ) as $h ) {
			add_action( $h, array( __CLASS__, 'on_term' ), 20, 3 );
		}
		add_action( self::CRON_HOOK, array( __CLASS__, 'hourly_check' ) );
	}

	public static function version() {
		return (int) get_option( self::VERSION_OPTION, 1 );
	}

	public static function bump() {
		update_option( self::VERSION_OPTION, self::version() + 1, true );
		do_action( 'pl_cache_flushed' );
	}

	public static function remember( $key, callable $fn, $ttl = DAY_IN_SECONDS ) {
		if ( apply_filters( 'pl_cache_disabled', false ) ) {
			return $fn();
		}
		$k   = 'pl_' . self::version() . '_' . md5( $key );
		$hit = get_transient( $k );
		if ( false !== $hit ) {
			return $hit;
		}
		$val = $fn();
		set_transient( $k, $val, $ttl );
		return $val;
	}

	public static function on_save_post( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || ! $post || ! in_array( $post->post_type, PL_Plugin::post_types(), true ) ) {
			return;
		}
		self::bump();
	}

	public static function on_delete_post( $post_id, $post = null ) {
		if ( $post && in_array( $post->post_type, PL_Plugin::post_types(), true ) ) {
			self::bump();
		}
	}

	public static function flush_for_post( $post_id ) {
		if ( in_array( get_post_type( $post_id ), PL_Plugin::post_types(), true ) ) {
			self::bump();
		}
	}

	public static function on_acf_save( $post_id ) {
		if ( 'options' === $post_id || in_array( get_post_type( (int) $post_id ), PL_Plugin::post_types(), true ) ) {
			self::bump();
		}
	}

	public static function on_term( $term_id, $tt_id, $taxonomy ) {
		if ( in_array( $taxonomy, PL_Taxonomies::all(), true ) ) {
			self::bump();
		}
	}

	/** Hourly: if any event changed status, clear caches and purge page caches (D9). */
	public static function hourly_check() {
		$sig = array();
		foreach ( PL_Data::raw_events() as $e ) {
			$sig[ $e['id'] ] = PL_Dates::status( $e['start'], $e['end'], PL_Geo::tz( $e['country'] ?: 'EG' ) );
		}
		$hash = md5( wp_json_encode( $sig ) );
		if ( get_option( self::STATUS_OPTION ) === $hash ) {
			return false;
		}
		update_option( self::STATUS_OPTION, $hash, false );
		self::bump();
		self::purge_page_caches();
		do_action( 'pl_status_changed', $sig );
		return true;
	}

	/** Known free cache plugins + a hook for others. */
	public static function purge_page_caches() {
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
		}
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		if ( class_exists( 'LiteSpeed\Purge' ) ) {
			do_action( 'litespeed_purge_all' );
		}
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
		do_action( 'pl_purge_page_cache' );
	}
}
