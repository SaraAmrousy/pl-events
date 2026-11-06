<?php
/**
 * Provisioning (idempotent): the UAE HFB header/footer templates (4.3), the menus, and —
 * from 4.4 — the pages from the Elementor template JSON in /elementor. Run by the deploy
 * scripts (`plt_provision()`), never on a normal page load. Existing items are left alone
 * unless $force, so edits made in wp-admin are never overwritten.
 *
 * @package hello-pl-child
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------- Elementor data helpers */

function plt_el_id() {
	return substr( md5( wp_generate_uuid4() ), 0, 7 );
}

/** Full-width container with no padding/gap, our CSS classes on it. */
function plt_el_container( array $children, $classes = 'pl-raw', array $extra = array() ) {
	$zero = array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true );
	return array(
		'id'       => plt_el_id(),
		'elType'   => 'container',
		'isInner'  => false,
		'settings' => array_merge(
			array(
				'content_width' => 'full',
				'padding'       => $zero,
				'margin'        => $zero,
				'flex_gap'      => array( 'unit' => 'px', 'size' => 0, 'column' => '0', 'row' => '0', 'isLinked' => true ),
				'css_classes'   => $classes,
			),
			$extra
		),
		'elements' => $children,
	);
}

function plt_el_shortcode( $code ) {
	return array( 'id' => plt_el_id(), 'elType' => 'widget', 'widgetType' => 'shortcode', 'isInner' => false, 'settings' => array( 'shortcode' => $code ), 'elements' => array() );
}

function plt_el_html( $html ) {
	return array( 'id' => plt_el_id(), 'elType' => 'widget', 'widgetType' => 'html', 'isInner' => false, 'settings' => array( 'html' => $html ), 'elements' => array() );
}

/** Save Elementor data on a post (the same meta Elementor writes). */
function plt_el_save( $post_id, array $elements, $template = 'elementor_header_footer' ) {
	update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $post_id, '_elementor_template_type', 'page' === get_post_type( $post_id ) ? 'wp-page' : 'wp-post' );
	update_post_meta( $post_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '4.0.0' );
	update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $elements ) ) );
	if ( $template && 'page' === get_post_type( $post_id ) ) {
		update_post_meta( $post_id, '_wp_page_template', $template );
	}
	delete_post_meta( $post_id, '_elementor_css' );
}

/* ----------------------------------------------------------- 4.3 HFB + menus */

/**
 * @return array log lines
 */
function plt_provision_chrome( $force = false ) {
	$log = array();
	if ( ! post_type_exists( 'elementor-hf' ) ) {
		return array( 'UAE Header Footer Builder is not active — header/footer skipped.' );
	}
	foreach ( array( 'type_header' => array( 'PL header', '[pl_site_header]' ), 'type_footer' => array( 'PL footer', '[pl_site_footer]' ) ) as $type => $t ) {
		$found = get_posts( array( 'post_type' => 'elementor-hf', 'post_status' => 'any', 'numberposts' => 1, 'meta_key' => '_plt_key', 'meta_value' => $type, 'fields' => 'ids' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $found && ! $force ) {
			$log[] = "$t[0]: exists (#$found[0]) — left as is";
			continue;
		}
		$id = $found ? $found[0] : wp_insert_post( array( 'post_type' => 'elementor-hf', 'post_status' => 'publish', 'post_title' => $t[0] ) );
		update_post_meta( $id, '_plt_key', $type );
		update_post_meta( $id, 'ehf_template_type', $type );
		update_post_meta( $id, 'ehf_target_include_locations', array( 'rule' => array( 'basic-global' ), 'specific' => array() ) );
		update_post_meta( $id, 'ehf_target_exclude_locations', array() );
		update_post_meta( $id, 'ehf_target_user_roles', array( 'all' ) );
		update_post_meta( $id, 'display-on-canvas-template', '1' );
		plt_el_save( $id, array( plt_el_container( array( plt_el_shortcode( $t[1] ) ) ) ), '' );
		$log[] = "$t[0]: " . ( $found ? 'updated' : 'created' ) . " (#$id), whole site";
	}
	$log = array_merge( $log, plt_provision_menus( $force ) );
	return $log;
}

/** Main + footer menus from the pages that exist (Step 3.4a H5–H6). */
function plt_provision_menus( $force = false ) {
	$log  = array();
	$want = array(
		'pl-main'   => array( 'Main menu', array( 'home', 'events', 'services', 'about', 'careers', 'contact' ) ),
		'pl-footer' => array( 'Footer menu', array( 'events', 'services', 'about', 'careers', 'contact' ) ),
	);
	$locs = get_nav_menu_locations();
	foreach ( $want as $loc => $m ) {
		$expect = count( array_filter( $m[1], static fn( $slug ) => 'home' === $slug || get_page_by_path( $slug ) ) );
		if ( ! empty( $locs[ $loc ] ) && ! $force ) {
			$have = count( (array) wp_get_nav_menu_items( $locs[ $loc ] ) );
			$ours = (int) get_option( 'plt_menu_links_' . $loc, -1 ) === $have; // untouched since we built it.
			if ( $have >= $expect || ! $ours ) {
				$log[] = "$m[0]: assigned ($have links) — left as is";
				continue;
			}
		}
		$menu = wp_get_nav_menu_object( $m[0] );
		$id   = $menu ? $menu->term_id : wp_create_nav_menu( $m[0] );
		foreach ( (array) wp_get_nav_menu_items( $id ) as $item ) {
			wp_delete_post( $item->ID, true );
		}
		$n = 0;
		foreach ( $m[1] as $i => $slug ) {
			if ( 'home' === $slug ) {
				wp_update_nav_menu_item( $id, 0, array( 'menu-item-title' => 'Home', 'menu-item-url' => home_url( '/' ), 'menu-item-type' => 'custom', 'menu-item-status' => 'publish', 'menu-item-position' => $i + 1 ) );
				++$n;
				continue;
			}
			$p = get_page_by_path( $slug );
			if ( ! $p ) {
				continue;
			}
			wp_update_nav_menu_item( $id, 0, array( 'menu-item-title' => get_the_title( $p ), 'menu-item-object' => 'page', 'menu-item-object-id' => $p->ID, 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish', 'menu-item-position' => $i + 1 ) );
			++$n;
		}
		$locs[ $loc ] = $id;
		$log[]        = "$m[0]: $n links → $loc";
		update_option( 'plt_menu_links_' . $loc, $n, false );
	}
	set_theme_mod( 'nav_menu_locations', $locs );
	return $log;
}

/* ------------------------------------------------------------------ 4.4 pages */

/**
 * Pages from the Elementor templates in /elementor (pl-{slug}.json). A page is (re)written
 * when it is new, was never built with Elementor, or $force. post_content gets the same
 * shortcodes, so the page still works if Elementor is ever switched off.
 */
function plt_provision_pages( $force = false ) {
	$log = array();
	if ( class_exists( 'PL_Importer' ) && method_exists( 'PL_Importer', 'seed_media' ) ) {
		PL_Importer::seed_media( 'assets/hall-wide.jpg', 'Delegates seated in a congress hall set up by PL Events' );
	}
	foreach ( glob( PLT_DIR . 'elementor/pl-*.json' ) as $file ) {
		$tpl = json_decode( (string) file_get_contents( $file ), true );
		if ( empty( $tpl['pl']['slug'] ) || empty( $tpl['content'] ) ) {
			continue;
		}
		$m    = $tpl['pl'];
		$page = get_page_by_path( $m['slug'] );
		$codes = array();
		array_walk_recursive( $tpl['content'], static function ( $v, $k ) use ( &$codes ) { if ( 'shortcode' === $k ) { $codes[] = $v; } } );
		if ( $page && ! $force && get_post_meta( $page->ID, '_plt_page', true ) ) {
			$log[] = "page {$m['slug']}: exists (#{$page->ID}) — left as is";
		} else {
			$data = array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $m['title'], 'post_name' => $m['slug'], 'post_content' => implode( "\n\n", $codes ) );
			if ( $page ) {
				$data['ID'] = $page->ID;
			}
			$id = wp_insert_post( wp_slash( $data ), true );
			if ( is_wp_error( $id ) ) {
				$log[] = "page {$m['slug']}: ERROR " . $id->get_error_message();
				continue;
			}
			update_post_meta( $id, '_plt_page', basename( $file ) );
			plt_el_save( $id, $tpl['content'], $tpl['page_settings']['template'] ?? 'elementor_header_footer' );
			$log[] = "page {$m['slug']}: " . ( $page ? 'rewritten' : 'created' ) . " (#$id), " . count( $tpl['content'] ) . ' sections';
			$page = get_post( $id );
		}
		if ( ! empty( $m['front'] ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $page->ID );
		}
		if ( ! empty( $m['privacy'] ) ) {
			update_option( 'wp_page_for_privacy_policy', $page->ID );
		}
	}
	$tmp = get_page_by_path( 'pl-check' );
	if ( $tmp ) {
		wp_trash_post( $tmp->ID );
		$log[] = 'temporary /pl-check/ page moved to the bin';
	}
	return $log;
}

/**
 * Rank Math title + description per page, from elementor/pl-*.json → "pl.seo" (Step 5.3).
 * Fills only empty fields, so an editor's own text in Rank Math is never overwritten (unless $force).
 */
function plt_provision_seo( $force = false ) {
	$log = array();
	foreach ( glob( PLT_DIR . 'elementor/pl-*.json' ) as $file ) {
		$tpl  = json_decode( (string) file_get_contents( $file ), true );
		$m    = $tpl['pl'] ?? array();
		$page = empty( $m['slug'] ) || empty( $m['seo'] ) ? null : get_page_by_path( $m['slug'] );
		if ( ! $page ) {
			continue;
		}
		foreach ( array( 'title' => 'rank_math_title', 'description' => 'rank_math_description' ) as $k => $key ) {
			$now = (string) get_post_meta( $page->ID, $key, true );
			if ( $force || '' === trim( $now ) ) {
				update_post_meta( $page->ID, $key, wp_slash( $m['seo'][ $k ] ) );
				$log[] = "seo {$m['slug']}: $k set";
			} elseif ( $now !== $m['seo'][ $k ] ) {
				$log[] = "seo {$m['slug']}: $k kept (edited in Rank Math)";
			}
		}
	}
	return $log;
}

/**
 * Site Icon (favicon): the PL monogram tile from the theme (assets/img/icon/pl-icon-512.png) becomes the
 * WordPress Site Icon, so WordPress prints the 32/180/192/270 icons. Only when no icon is set (or $force).
 */
function plt_provision_site_icon( $force = false ) {
	if ( has_site_icon() && ! $force ) {
		return array( 'site icon: exists — left as is' );
	}
	$src = PLT_DIR . 'assets/img/icon/pl-icon-512.png';
	if ( ! is_readable( $src ) ) {
		return array( 'site icon: source missing' );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-site-icon.php';
	$icon = new WP_Site_Icon(); // WordPress' own icon sizes (32 / 180 / 192 / 270), as the Customizer does
	add_filter( 'intermediate_image_sizes_advanced', array( $icon, 'additional_sizes' ) );
	$tmp = wp_tempnam( 'pl-icon-512.png' );
	copy( $src, $tmp );
	$id = media_handle_sideload( array( 'name' => 'pl-site-icon.png', 'tmp_name' => $tmp ), 0, 'PL Events site icon' );
	if ( is_wp_error( $id ) ) {
		return array( 'site icon: ERROR ' . $id->get_error_message() );
	}
	remove_filter( 'intermediate_image_sizes_advanced', array( $icon, 'additional_sizes' ) );
	update_post_meta( $id, '_wp_attachment_context', 'site-icon' );
	update_option( 'site_icon', $id );
	return array( "site icon: set (#$id)" );
}

/** Everything: pages, header/footer, menus. */
function plt_provision( $force = false ) {
	$log = function_exists( 'plt_provision_pages' ) ? plt_provision_pages( $force ) : array();
	$log = array_merge( $log, plt_provision_chrome( $force ) ); // header, footer, then menus (pages exist by now).
	$log = array_merge( $log, plt_provision_seo( $force ) );
	$log = array_merge( $log, plt_provision_site_icon() );
	if ( function_exists( 'plt_provision_forms' ) ) {
		$log = array_merge( $log, plt_provision_forms( $force ) );
	}
	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
	return $log;
}
