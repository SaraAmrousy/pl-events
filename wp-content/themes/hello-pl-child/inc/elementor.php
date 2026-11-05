<?php
/**
 * Elementor Free Global Colors and Fonts = the 3.2 design tokens (Step 3.2 "Elementor Globals").
 * Written into the active Elementor Kit on theme switch and by `wp eval 'plt_sync_elementor_kit();'`.
 * Elementor's own Entrance Animations stay off (locked 1): we never use the "Motion Effects" panel.
 *
 * @package hello-pl-child
 */

defined( 'ABSPATH' ) || exit;

function plt_design_tokens() {
	return array(
		'colors' => array(
			array( '_id' => 'primary', 'title' => 'Primary (blue)', 'color' => '#2C4894' ),
			array( '_id' => 'secondary', 'title' => 'Secondary (ink)', 'color' => '#0A0D18' ),
			array( '_id' => 'text', 'title' => 'Text (ink)', 'color' => '#0A0D18' ),
			array( '_id' => 'accent', 'title' => 'Accent (blue)', 'color' => '#2C4894' ),
		),
		'custom_colors' => array(
			array( '_id' => 'pl_paper', 'title' => 'Paper', 'color' => '#FAFAF7' ),
			array( '_id' => 'pl_sand', 'title' => 'Sand', 'color' => '#EFECE5' ),
			array( '_id' => 'pl_navy', 'title' => 'Navy', 'color' => '#142040' ),
			array( '_id' => 'pl_ink_raised', 'title' => 'Ink raised', 'color' => '#161C30' ),
			array( '_id' => 'pl_blue_on_ink', 'title' => 'Blue on ink', 'color' => '#9DB2F2' ),
			array( '_id' => 'pl_muted', 'title' => 'Text muted', 'color' => '#57534C' ),
			array( '_id' => 'pl_live', 'title' => 'Live', 'color' => '#C62828' ),
			array( '_id' => 'pl_open', 'title' => 'Open', 'color' => '#1B7A4A' ),
		),
		'typography' => array(
			array( '_id' => 'primary', 'title' => 'Primary (display)', 'typography_typography' => 'custom', 'typography_font_family' => 'Montserrat', 'typography_font_weight' => '800' ),
			array( '_id' => 'secondary', 'title' => 'Secondary', 'typography_typography' => 'custom', 'typography_font_family' => 'Montserrat', 'typography_font_weight' => '700' ),
			array( '_id' => 'text', 'title' => 'Text', 'typography_typography' => 'custom', 'typography_font_family' => 'Montserrat', 'typography_font_weight' => '400', 'typography_font_size' => array( 'unit' => 'px', 'size' => 17 ), 'typography_line_height' => array( 'unit' => 'em', 'size' => 1.65 ) ),
			array( '_id' => 'accent', 'title' => 'Accent (label)', 'typography_typography' => 'custom', 'typography_font_family' => 'Montserrat', 'typography_font_weight' => '700', 'typography_font_size' => array( 'unit' => 'px', 'size' => 12 ), 'typography_text_transform' => 'uppercase', 'typography_letter_spacing' => array( 'unit' => 'em', 'size' => 0.08 ) ),
		),
	);
}

/** Write the tokens into the active kit. Returns the kit ID or 0. */
function plt_sync_elementor_kit() {
	$kit = (int) get_option( 'elementor_active_kit' );
	if ( ! $kit && class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->kits_manager ) && method_exists( \Elementor\Plugin::$instance->kits_manager, 'create_default' ) ) {
		$kit = (int) \Elementor\Plugin::$instance->kits_manager->create_default();
		update_option( 'elementor_active_kit', $kit );
	}
	if ( ! $kit ) {
		return 0;
	}
	$t = plt_design_tokens();
	$s = get_post_meta( $kit, '_elementor_page_settings', true );
	$s = is_array( $s ) ? $s : array();
	$s['system_colors']     = $t['colors'];
	$s['custom_colors']     = $t['custom_colors'];
	$s['system_typography'] = $t['typography'];
	$s['default_generic_fonts'] = 'Segoe UI, system-ui, sans-serif';
	$s['container_width']   = array( 'unit' => 'px', 'size' => 1200 );
	$s['space_between_widgets'] = array( 'unit' => 'px', 'size' => 0, 'column' => '0', 'row' => '0', 'isLinked' => true );
	$s['page_title_selector'] = 'h1.entry-title';
	$s['body_background_background'] = 'classic';
	$s['body_background_color'] = '#FAFAF7';
	update_post_meta( $kit, '_elementor_page_settings', $s );
	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
	return $kit;
}
add_action( 'after_switch_theme', 'plt_sync_elementor_kit' );

/* Montserrat is self-hosted: stop Elementor loading it from Google, and keep its icon font off the front end. */
add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );
add_action( 'elementor/frontend/after_register_styles', static function () {
	wp_deregister_style( 'elementor-icons' );
	wp_register_style( 'elementor-icons', false, array(), PLT_VERSION );
}, 20 );
