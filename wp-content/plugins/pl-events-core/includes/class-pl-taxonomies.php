<?php
/**
 * Lists in wp-admin (Step 2.2 D5).
 * Editor adds: Specialties, Series. Admin (Sara) only: Event types, Job types.
 * Event types carry two admin switches: "Online (not on the map)" and "Counts as a congress".
 * Series carry an optional short name used on buttons ("Register for BRC 2026").
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Taxonomies {

	const SPECIALTY  = 'pl_specialty';
	const SERIES     = 'pl_series';
	const EVENT_TYPE = 'pl_event_type';
	const JOB_TYPE   = 'pl_job_type';

	public static function all() {
		return array( self::SPECIALTY, self::SERIES, self::EVENT_TYPE, self::JOB_TYPE );
	}

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 0 );
		add_filter( 'pre_insert_term', array( __CLASS__, 'no_case_duplicates' ), 10, 2 );
		add_action( 'acf/init', array( __CLASS__, 'term_fields' ) );
	}

	public static function register() {
		$editor_list = array(
			'manage_terms' => 'manage_pl_terms',
			'edit_terms'   => 'manage_pl_terms',
			'delete_terms' => 'manage_options',
			'assign_terms' => 'edit_pl_items',
		);
		$admin_list  = array(
			'manage_terms' => 'manage_options',
			'edit_terms'   => 'manage_options',
			'delete_terms' => 'manage_options',
			'assign_terms' => 'edit_pl_items',
		);
		$base = array(
			'public'            => false,
			'show_ui'           => true,
			'show_in_menu'      => false, // listed under PL Website by PL_Admin.
			'show_in_rest'      => true,
			'show_admin_column' => false,
			'hierarchical'      => false,
			'meta_box_cb'       => false, // edited through the event form.
			'rewrite'           => false,
		);
		register_taxonomy( self::SPECIALTY, 'event', $base + array( 'labels' => self::labels( 'Specialty', 'Specialties' ), 'capabilities' => $editor_list ) );
		register_taxonomy( self::SERIES, 'event', $base + array( 'labels' => self::labels( 'Series', 'Series' ), 'capabilities' => $editor_list ) );
		register_taxonomy( self::EVENT_TYPE, 'event', $base + array( 'labels' => self::labels( 'Event type', 'Event types' ), 'capabilities' => $admin_list ) );
		register_taxonomy( self::JOB_TYPE, 'pl_job', $base + array( 'labels' => self::labels( 'Job type', 'Job types' ), 'capabilities' => $admin_list ) );
	}

	private static function labels( $one, $many ) {
		return array(
			'name'          => $many,
			'singular_name' => $one,
			'add_new_item'  => "Add new $one",
			'edit_item'     => "Edit $one",
			'search_items'  => "Search $many",
			'not_found'     => "No $many yet",
			'back_to_items' => "← Back to $many",
		);
	}

	/** Term fields (admin switches) registered in PHP like everything else. */
	public static function term_fields() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}
		acf_add_local_field_group(
			array(
				'key'      => 'group_pl_event_type_terms',
				'title'    => 'Event type settings',
				'fields'   => array(
					array( 'key' => 'field_pl_type_online', 'name' => 'pl_online', 'label' => 'Online (not on the map)', 'type' => 'true_false', 'ui' => 1, 'instructions' => 'Events of this type never get a map pin and need no country or city.' ),
					array( 'key' => 'field_pl_type_congress', 'name' => 'pl_congress', 'label' => 'Counts as a congress', 'type' => 'true_false', 'ui' => 1, 'default_value' => 1, 'instructions' => 'Counted in "{n} congresses" on the site.' ),
				),
				'location' => array( array( array( 'param' => 'taxonomy', 'operator' => '==', 'value' => self::EVENT_TYPE ) ) ),
			)
		);
		acf_add_local_field_group(
			array(
				'key'      => 'group_pl_series_terms',
				'title'    => 'Series settings',
				'fields'   => array(
					array( 'key' => 'field_pl_series_short', 'name' => 'pl_short', 'label' => 'Short name (optional)', 'type' => 'text', 'instructions' => 'Used on buttons, e.g. "BRC" → "Register for BRC 2026". Empty = the series name.' ),
				),
				'location' => array( array( array( 'param' => 'taxonomy', 'operator' => '==', 'value' => self::SERIES ) ) ),
			)
		);
	}

	public static function type_flags( $type_name ) {
		$t = $type_name ? get_term_by( 'name', $type_name, self::EVENT_TYPE ) : null;
		if ( ! $t ) {
			return array( 'online' => false, 'congress' => true );
		}
		$congress = get_term_meta( $t->term_id, 'pl_congress', true );
		return array(
			'online'   => (bool) get_term_meta( $t->term_id, 'pl_online', true ),
			'congress' => '' === $congress ? true : (bool) $congress,
		);
	}

	public static function series_short( $series ) {
		$t = $series ? get_term_by( 'name', $series, self::SERIES ) : null;
		$s = $t ? (string) get_term_meta( $t->term_id, 'pl_short', true ) : '';
		return '' !== $s ? $s : (string) $series;
	}

	/** Find a term case-insensitively, or create it. Returns the term ID. */
	public static function ensure_term( $name, $taxonomy ) {
		if ( is_numeric( $name ) && term_exists( (int) $name, $taxonomy ) ) {
			return (int) $name;
		}
		$found = self::find_ci( $name, $taxonomy );
		if ( $found ) {
			return $found->term_id;
		}
		$r = wp_insert_term( $name, $taxonomy );
		return is_wp_error( $r ) ? (int) ( $r->get_error_data( 'term_exists' ) ?? 0 ) : (int) $r['term_id'];
	}

	private static function find_ci( $name, $taxonomy ) {
		$want  = mb_strtolower( trim( (string) $name ) );
		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
		foreach ( is_wp_error( $terms ) ? array() : $terms as $t ) {
			if ( mb_strtolower( $t->name ) === $want ) {
				return $t;
			}
		}
		return null;
	}

	/** Step 2.2 critique 1: "Retina" and "retina" must not become two specialties. */
	public static function no_case_duplicates( $term, $taxonomy ) {
		if ( in_array( $taxonomy, self::all(), true ) && is_string( $term ) ) {
			$found = self::find_ci( $term, $taxonomy );
			if ( $found ) {
				return new WP_Error( 'term_exists', sprintf( '"%s" already exists — pick it from the list.', $found->name ), $found->term_id );
			}
		}
		return $term;
	}

	/** Default admin lists on first install (data, editable in wp-admin). */
	public static function seed_defaults() {
		$types = array( 'Congress' => array( 0, 1 ), 'Conference' => array( 0, 1 ), 'Symposium' => array( 0, 1 ), 'Forum' => array( 0, 1 ), 'Course' => array( 0, 0 ), 'Wetlab' => array( 0, 0 ), 'Workshop' => array( 0, 0 ), 'Webinar' => array( 1, 0 ) );
		foreach ( $types as $name => $flags ) {
			if ( ! term_exists( $name, self::EVENT_TYPE ) ) {
				$r = wp_insert_term( $name, self::EVENT_TYPE );
				if ( ! is_wp_error( $r ) ) {
					update_term_meta( $r['term_id'], 'pl_online', $flags[0] );
					update_term_meta( $r['term_id'], 'pl_congress', $flags[1] );
				}
			}
		}
		foreach ( array( 'Full time', 'Part time', 'Internship', 'Freelance' ) as $name ) {
			if ( ! term_exists( $name, self::JOB_TYPE ) ) {
				wp_insert_term( $name, self::JOB_TYPE );
			}
		}
	}
}
