<?php
/**
 * wp-admin: the "PL Website" menu (Step 2.2 §1/§6), Overview (counts, Sample items left,
 * Unmatched cities, Import from JSON), list screens for the admin lists, city suggestions.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 9 );
		add_action( 'admin_menu', array( __CLASS__, 'editor_menu' ), 999 );
		add_filter( 'parent_file', array( __CLASS__, 'parent_file' ) );
		add_action( 'admin_post_pl_import', array( __CLASS__, 'handle_import' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_notices', array( __CLASS__, 'scf_missing' ) );
		add_action( 'admin_head', array( __CLASS__, 'hide_add_entries' ) );
	}

	public static function menu() {
		add_menu_page( 'PL Website', 'PL Website', 'edit_pl_items', 'pl-website', array( __CLASS__, 'overview' ), 'dashicons-calendar-alt', 3 );
		add_submenu_page( 'pl-website', 'Overview', 'Overview', 'edit_pl_items', 'pl-website', array( __CLASS__, 'overview' ), 0 );
		// "Add new" screens must be menu entries, or WordPress refuses post-new.php for non-admins
		// when a post type sits under a custom menu. Only "Add event" is shown; the rest are hidden.
		add_submenu_page( 'pl-website', 'Add event', '＋ Add event', 'edit_pl_items', 'post-new.php?post_type=event', '', 1 );
		foreach ( PL_Plugin::modules() as $type => $m ) {
			if ( 'event' !== $type && 'company' !== $type ) {
				add_submenu_page( 'pl-website', 'Add ' . strtolower( $m->one ), 'Add ' . strtolower( $m->one ), 'pl_service' === $type ? 'manage_options' : 'edit_pl_items', 'post-new.php?post_type=' . $type );
			}
		}
		$lists = array(
			PL_Taxonomies::SPECIALTY  => array( 'Specialties', 'manage_pl_terms', 'event' ),
			PL_Taxonomies::SERIES     => array( 'Series', 'manage_pl_terms', 'event' ),
			PL_Taxonomies::EVENT_TYPE => array( 'Event types', 'manage_options', 'event' ),
			PL_Taxonomies::JOB_TYPE   => array( 'Job types', 'manage_options', 'pl_job' ),
		);
		foreach ( $lists as $tax => $l ) {
			add_submenu_page( 'pl-website', $l[0], '↳ ' . $l[0], $l[1], 'edit-tags.php?taxonomy=' . $tax . '&post_type=' . $l[2] );
		}
	}

	/** PL Editor: PL Website + Media + profile only (Step 2.2 §6). */
	public static function editor_menu() {
		if ( current_user_can( 'manage_options' ) || ! current_user_can( 'edit_pl_items' ) ) {
			return;
		}
		global $menu;
		$keep = array( 'pl-website', 'upload.php', 'profile.php', 'index.php' );
		foreach ( (array) $menu as $item ) {
			if ( isset( $item[2] ) && ! in_array( $item[2], $keep, true ) && 0 !== strpos( (string) $item[2], 'separator' ) ) {
				remove_menu_page( $item[2] );
			}
		}
	}

	/** Hide the extra "Add …" entries (each list screen has its own Add new button). */
	public static function hide_add_entries() {
		echo '<style>#toplevel_page_pl-website .wp-submenu a[href^="post-new.php?post_type=pl_"]{display:none}</style>';
	}

	public static function parent_file( $file ) {
		global $current_screen;
		if ( $current_screen && in_array( $current_screen->taxonomy, PL_Taxonomies::all(), true ) ) {
			return 'pl-website';
		}
		return $file;
	}

	public static function scf_missing() {
		if ( ! function_exists( 'acf_add_local_field_group' ) && current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-error"><p><strong>PL Events Core</strong> needs the free plugin <strong>Secure Custom Fields</strong> to show its forms. Your content is safe; install and activate SCF to edit it.</p></div>';
		}
	}

	public static function assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, array( 'event', 'pl_office' ), true ) || 'post' !== $screen->base ) {
			return;
		}
		$used = array();
		foreach ( PL_Data::raw_events( true ) as $e ) {
			if ( $e['city'] ) {
				$used[ $e['country'] ][] = $e['city'];
			}
		}
		$cities = array();
		foreach ( PL_Geo::countries() as $cc => $c ) {
			$cities[ $cc ] = array_values( array_unique( array_merge( $used[ $cc ] ?? array(), PL_Geo::city_names( $cc ) ) ) );
		}
		wp_enqueue_script( 'pl-admin', PL_EVENTS_URL . 'assets/js/pl-admin.js', array( 'jquery' ), PL_EVENTS_VERSION, true );
		wp_add_inline_script( 'pl-admin', 'window.PL_CITIES=' . wp_json_encode( $cities ) . ';', 'before' );
	}

	public static function overview() {
		$s    = PL_Data::stats();
		$last = get_option( 'pl_last_import' );
		echo '<div class="wrap"><h1>PL Website</h1>';
		if ( isset( $_GET['pl_imported'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$r = $last['report'] ?? array();
			printf( '<div class="notice notice-success"><p>Import finished: %d created, %d updated, %d skipped, %d media.%s</p></div>', (int) ( $r['created'] ?? 0 ), (int) ( $r['updated'] ?? 0 ), (int) ( $r['skipped'] ?? 0 ), (int) ( $r['media'] ?? 0 ), ! empty( $r['errors'] ) ? ' Problems: ' . esc_html( implode( '; ', $r['errors'] ) ) : '' );
		}
		echo '<h2>On the site now</h2><p style="font-size:15px">';
		printf( '%d events · %d congresses · %d countries · %d cities · since %s — Live %d · Upcoming %d · Past %d', (int) $s['events'], (int) $s['congresses'], (int) $s['countries'], (int) $s['cities'], esc_html( $s['since'] ), (int) $s['live'], (int) $s['upcoming'], (int) $s['past'] );
		echo '</p><p class="description">These numbers are worked out from the published events with "Show on site" on. Nobody types them.</p>';

		if ( current_user_can( 'manage_options' ) ) {
			echo '<h2>Sample items left</h2><p>' . wp_kses_post( PL_Data::sample_message() ) . '</p>';
			$un = PL_Data::unmatched_cities();
			echo '<h2>Unmatched cities</h2>';
			if ( $un ) {
				echo '<p>These cities were not found in the bundled city list, so their pin sits on the country centre. Fix the spelling in the event, or leave it.</p><ul>';
				foreach ( $un as $k => $evs ) {
					list( $city, $cc ) = explode( '|', $k );
					printf( '<li><strong>%s</strong>, %s — %s</li>', esc_html( $city ), esc_html( PL_Geo::name( $cc ) ), wp_kses_post( implode( ', ', array_map( fn( $e ) => '<a href="' . esc_url( get_edit_post_link( $e['post_id'] ) ) . '">' . esc_html( $e['name'] ) . '</a>', $evs ) ) ) );
				}
				echo '</ul>';
			} else {
				echo '<p>None — every city is on the map.</p>';
			}
			echo '<h2>Import from JSON</h2><p>Seeds the site from a <code>pl-data.json</code> file (the bundled seed if you choose none). Items that already exist are <strong>skipped</strong> unless you tick Overwrite.</p>';
			echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'pl_import' );
			echo '<input type="hidden" name="action" value="pl_import"><p><input type="file" name="pl_file" accept=".json,application/json"></p>';
			echo '<p><label><input type="checkbox" name="overwrite" value="1"> Overwrite existing items with the file</label></p>';
			submit_button( 'Import', 'primary', 'submit', false );
			echo ' <a class="button" href="' . esc_url( rest_url( 'pl/v1/data' ) ) . '" target="_blank">Export current data (JSON)</a></form>';
			if ( $last ) {
				printf( '<p class="description">Last import: %s</p>', esc_html( wp_date( 'j M Y H:i', $last['time'] ) ) );
			}
			echo '<p class="description">' . esc_html( PL_Geo::credits() ) . ' · flags: flag-icons (MIT).</p>';
		}
		echo '</div>';
	}

	public static function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'pl_import' );
		$src = PL_Importer::seed_file();
		if ( ! empty( $_FILES['pl_file']['tmp_name'] ) && is_uploaded_file( $_FILES['pl_file']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$data = json_decode( (string) file_get_contents( $_FILES['pl_file']['tmp_name'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$src  = is_array( $data ) ? $data : $src;
		}
		( new PL_Importer() )->run( $src, ! empty( $_POST['overwrite'] ) );
		wp_safe_redirect( admin_url( 'admin.php?page=pl-website&pl_imported=1' ) );
		exit;
	}
}
