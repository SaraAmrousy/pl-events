<?php
/**
 * Hello PL Child — look and motion only (Step 4.2). Logic lives in pl-events-core.
 *
 * inc/setup.php     styles, fonts, body class, Hello defaults off
 * inc/motion.php    GSAP + ScrollTrigger (triggers only, no pin), Lenis (Home only), motion modules
 * inc/elementor.php Elementor Free Global Colors / Fonts from the design tokens
 * inc/chrome.php    header + footer markup ([pl_site_header] / [pl_site_footer] in UAE HFB templates)
 * inc/provision.php idempotent setup: HFB templates, menus, pages (run by the deploy scripts)
 *
 * @package hello-pl-child
 */

defined( 'ABSPATH' ) || exit;

define( 'PLT_VERSION', '0.1.0' );
define( 'PLT_DIR', get_stylesheet_directory() . '/' );
define( 'PLT_URL', get_stylesheet_directory_uri() . '/' );

require PLT_DIR . 'inc/setup.php';
require PLT_DIR . 'inc/motion.php';
require PLT_DIR . 'inc/elementor.php';
require PLT_DIR . 'inc/chrome.php';
require PLT_DIR . 'inc/provision.php';
