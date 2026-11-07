<?php
/**
 * "Import from JSON" (Step 2.2 §8, Step 4.0 S7). Seeds WordPress from data/pl-data.json —
 * content is never pushed as a database dump.
 *
 * Safe by default: an item that already exists (same stable key) is skipped, so editor
 * changes are never overwritten. "Overwrite" updates existing items from the file.
 * Runs from PL Website › Overview, WP-CLI (`wp pl import`) or REST (POST /pl/v1/import, admins).
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Importer {

	const KEY_META = '_pl_key';

	/** JSON section => post type */
	const MAP = array(
		'offices'     => 'pl_office',
		'societies'   => 'pl_society',
		'sponsors'    => 'pl_sponsor',
		'services'    => 'pl_service',
		'jobs'        => 'pl_job',
		'memberships' => 'pl_membership',
		'events'      => 'event',
	);

	private $base_dir = '';
	private $overwrite = false;
	private $report = array();

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'rest' ) );
	}

	public static function seed_file() {
		return PL_EVENTS_DIR . 'seed/pl-data.json';
	}

	public static function rest() {
		register_rest_route(
			'pl/v1',
			'/import',
			array(
				'methods'             => 'POST',
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
				'callback'            => function ( WP_REST_Request $r ) {
					$json = $r->get_param( 'json' );
					$data = $json ? json_decode( (string) $json, true ) : null;
					$res  = ( new self() )->run( $data ? $data : self::seed_file(), (bool) $r->get_param( 'overwrite' ) );
					return is_wp_error( $res ) ? $res : rest_ensure_response( $res );
				},
			)
		);
		register_rest_route(
			'pl/v1',
			'/data',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => fn() => rest_ensure_response( PL_Data::all() ),
			)
		);
	}

	/**
	 * @param string|array $source path to a JSON file, or decoded data
	 * @return array|WP_Error report
	 */
	public function run( $source, $overwrite = false ) {
		$this->overwrite = (bool) $overwrite;
		$this->report    = array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'media' => 0, 'errors' => array(), 'by_type' => array() );
		if ( is_string( $source ) ) {
			if ( ! is_readable( $source ) ) {
				return new WP_Error( 'pl_import_file', 'File not found: ' . $source );
			}
			$this->base_dir = trailingslashit( dirname( $source ) ); // not realpath: the seed may be a symlink.
			$data           = json_decode( (string) file_get_contents( $source ), true );
		} else {
			$data           = $source;
			$this->base_dir = trailingslashit( PL_EVENTS_DIR . 'seed' );
		}
		if ( ! is_array( $data ) || 0 !== strpos( (string) ( $data['meta']['schema'] ?? '' ), 'pl-events-core/' ) ) {
			return new WP_Error( 'pl_import_schema', 'This is not a pl-events-core data file (meta.schema missing).' );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_defer_term_counting( true );
		add_filter( 'pl_cache_disabled', '__return_true' );

		PL_Taxonomies::seed_defaults();
		foreach ( (array) ( $data['series'] ?? array() ) as $s ) {
			$id = PL_Taxonomies::ensure_term( $s['name'], PL_Taxonomies::SERIES );
			if ( $id && ! empty( $s['short'] ) && ( $this->overwrite || '' === (string) get_term_meta( $id, 'pl_short', true ) ) ) {
				update_term_meta( $id, 'pl_short', $s['short'] );
			}
		}
		if ( ! empty( $data['company'] ) ) {
			$this->company( $data['company'] );
		}
		foreach ( self::MAP as $section => $type ) {
			foreach ( array_values( (array) ( $data[ $section ] ?? array() ) ) as $i => $item ) {
				if ( is_array( $item ) ) {
					$this->item( $type, $item, $i );
				}
			}
		}

		remove_filter( 'pl_cache_disabled', '__return_true' );
		wp_defer_term_counting( false );
		PL_Cache::bump();
		update_option( 'pl_last_import', array( 'time' => time(), 'report' => $this->report ), false );
		return $this->report;
	}

	private function company( array $c ) {
		$m = PL_Plugin::module( 'company' );
		if ( ! $this->overwrite && get_option( 'options_pl_hero_text' ) ) {
			$this->count( 'company', 'skipped' );
			return;
		}
		foreach ( $m->from_data( $c ) as $name => $v ) {
			$f = PL_Fields::def( 'company', $name );
			if ( $f ) {
				PL_Fields::set( 'options', 'company', $f, $v );
			}
		}
		if ( ! empty( $c['portfolio']['size'] ) ) {
			update_option( 'pl_portfolio_size', $c['portfolio']['size'], false );
		}
		$poster = $c['showreel']['poster'] ?? '';
		if ( $poster && ( $id = $this->media( $poster ) ) ) {
			PL_Fields::set( 'options', 'company', PL_Fields::def( 'company', 'showreel_poster' ), $id );
		}
		$stills = array_filter( array_map( array( $this, 'media' ), (array) ( $c['showreel']['stills'] ?? array() ) ) );
		if ( $stills ) {
			PL_Fields::set( 'options', 'company', PL_Fields::def( 'company', 'showreel_stills' ), $stills );
		}
		$this->count( 'company', 'updated' );
	}

	private function item( $type, array $item, $index ) {
		$m = PL_Plugin::module( $type );
		if ( ! $m ) {
			return;
		}
		$title = (string) ( $item['name'] ?? $item['group'] ?? $item['title'] ?? $item['city'] ?? '' ); // offices have no name: the city is their name.
		$key   = sanitize_title( (string) ( $item['id'] ?? $title ) );
		if ( '' === $key ) {
			return;
		}
		$existing = get_posts( array( 'post_type' => $type, 'post_status' => 'any', 'numberposts' => 1, 'meta_key' => self::KEY_META, 'meta_value' => $key, 'fields' => 'ids' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $existing && ! $this->overwrite ) {
			$this->count( $type, 'skipped' );
			return;
		}
		$post = array(
			'post_type'   => $type,
			'post_title'  => $title,
			'post_status' => 'publish',
			'menu_order'  => (int) ( $item['order'] ?? $index ),
		);
		$post['post_name'] = $key; // stable slug for every type (events: the URL).
		if ( $existing ) {
			$post['ID'] = $existing[0];
			$id         = wp_update_post( $post, true );
		} else {
			$id = wp_insert_post( $post, true );
		}
		if ( is_wp_error( $id ) ) {
			$this->report['errors'][] = $type . ' ' . $key . ': ' . $id->get_error_message();
			return;
		}
		update_post_meta( $id, self::KEY_META, $key );
		if ( 'event' === $type ) {
			update_post_meta( $id, '_pl_slug_fixed', 1 );
		}
		$vals = $m->from_data( $item );
		if ( isset( $vals['registration'] ) && ! in_array( $vals['registration'], array( 'open', 'closed', 'not_open' ), true ) ) {
			$vals['registration'] = 'not_open';
		}
		foreach ( $m->fields() as $f ) {
			if ( ! array_key_exists( $f['name'], $vals ) || 'message' === $f['type'] ) {
				continue;
			}
			$v = $vals[ $f['name'] ];
			if ( in_array( $f['type'], array( 'image', 'file' ), true ) ) {
				$v = $v ? $this->media( $v, 'pl_service' === $type ? (string) ( $item['image_alt'] ?? '' ) : $title ) : '';
			} elseif ( 'gallery' === $f['type'] ) {
				$v = array_values( array_filter( array_map( array( $this, 'media' ), (array) $v ) ) );
			}
			PL_Fields::set( $id, $type, $f, $v );
		}
		$this->count( $type, $existing ? 'updated' : 'created' );
	}

	/** A photo from the bundled seed (e.g. "assets/hall-wide.jpg") → attachment ID, imported once. */
	public static function seed_media( $src, $alt = '' ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$i           = new self();
		$i->base_dir = trailingslashit( PL_EVENTS_DIR . 'seed' );
		$i->report   = array( 'media' => 0, 'errors' => array() );
		return $i->media( $src, $alt );
	}

	/** URL or seed-relative path → attachment ID (re-used on the next import). */
	public function media( $src, $alt = '' ) {
		$src = trim( (string) $src );
		if ( '' === $src ) {
			return 0;
		}
		$found = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'meta_key' => '_pl_source', 'meta_value' => $src, 'fields' => 'ids' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $found ) {
			return (int) $found[0];
		}
		if ( preg_match( '#^https?://#', $src ) ) {
			$tmp = download_url( $src, 30 );
		} else {
			$rel  = ltrim( $src, '/' );
			$path = $this->base_dir . $rel;
			if ( false !== strpos( $rel, '..' ) || ! preg_match( '/\.(jpe?g|png|webp|gif|svg|pdf|mp4)$/i', $rel ) || ! is_readable( $path ) ) {
				$this->report['errors'][] = 'media not found: ' . $src;
				return 0;
			}
			$tmp = wp_tempnam( basename( $path ) );
			copy( $path, $tmp );
		}
		if ( is_wp_error( $tmp ) ) {
			$this->report['errors'][] = 'media ' . $src . ': ' . $tmp->get_error_message();
			return 0;
		}
		$id = media_handle_sideload( array( 'name' => basename( wp_parse_url( $src, PHP_URL_PATH ) ), 'tmp_name' => $tmp ), 0 );
		if ( is_wp_error( $id ) ) {
			@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			$this->report['errors'][] = 'media ' . $src . ': ' . $id->get_error_message();
			return 0;
		}
		update_post_meta( $id, '_pl_source', $src );
		if ( $alt ) {
			update_post_meta( $id, '_wp_attachment_image_alt', $alt );
		}
		++$this->report['media'];
		return (int) $id;
	}

	private function count( $type, $what ) {
		++$this->report[ $what ];
		$this->report['by_type'][ $type ][ $what ] = ( $this->report['by_type'][ $type ][ $what ] ?? 0 ) + 1;
	}
}
