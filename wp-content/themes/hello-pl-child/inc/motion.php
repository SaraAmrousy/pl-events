<?php
/**
 * Motion (Step 3.3 as amended by 3.4b P2/P3/P8): GSAP 3.15.1 + ScrollTrigger (triggers only,
 * never pin) on every page; Lenis 1.3.27 on Home only; modules per Step 3.4b §5:
 *   core.js        contexts (Full / Light / Reduced), header state, Lenis, module runner
 *   patterns.js    focus pull, cards/panels, focus sets, rack focus, outline, swaps, arcs
 *   iris.js        Home: iris hero, stills / showreel, Ken Burns, word focus pull, Pause
 *   viewfinder.js  Events + Home row: brackets glide to the card in focus
 *   story.js       About: camera per chapter, arcs draw, new cities ripple
 * All self-hosted, deferred, in the footer: no motion JS on the critical path.
 *
 * @package hello-pl-child
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', static function () {
	$js  = PLT_URL . 'assets/js/';
	$dfr = array( 'in_footer' => true, 'strategy' => 'defer' );
	wp_register_script( 'gsap', $js . 'vendor/gsap.min.js', array(), '3.15.1', $dfr );
	wp_register_script( 'gsap-st', $js . 'vendor/ScrollTrigger.min.js', array( 'gsap' ), '3.15.1', $dfr );
	wp_register_script( 'lenis', $js . 'vendor/lenis.min.js', array(), '1.3.27', $dfr );
	wp_register_script( 'plm-core', $js . 'core.js', array( 'gsap', 'gsap-st' ), plt_ver( 'assets/js/core.js' ), $dfr );
	foreach ( array( 'patterns', 'iris', 'viewfinder', 'story' ) as $m ) {
		wp_register_script( 'plm-' . $m, $js . $m . '.js', array( 'plm-core' ), plt_ver( "assets/js/$m.js" ), $dfr );
	}
	wp_enqueue_script( 'plm-core' );
	wp_enqueue_script( 'plm-patterns' );
	if ( is_front_page() ) {
		wp_enqueue_script( 'lenis' ); // P2: Lenis on Home only.
	}
}, 30 );

/* Page-specific modules load only where their section is rendered (Elementor or not). */
add_filter( 'pl_shortcode_html', static function ( $html, $tag ) {
	$map = array( 'pl_hero' => 'plm-iris', 'pl_calendar' => 'plm-viewfinder', 'pl_story' => 'plm-story' );
	if ( isset( $map[ $tag ] ) && '' !== $html ) {
		wp_enqueue_script( $map[ $tag ] );
	}
	if ( 'pl_upcoming' === $tag && false !== strpos( $html, 'pl-rail' ) ) {
		wp_enqueue_script( 'plm-viewfinder' ); // Home row: brackets on the centre card (Full only)
	}
	return $html;
}, 10, 2 );

/*
 * Before first paint: decide the motion context so CSS start states never flash, and mark
 * the first Home visit of the session for the iris. If motion scripts never arrive, the
 * page is revealed after 2.5 s (no-JS and slow-JS both end with a finished page).
 */
add_action( 'wp_head', static function () {
	?>
<script>(function(d){var h=d.documentElement,m=window.matchMedia,r=(m&&m('(prefers-reduced-motion: reduce)').matches)||(navigator.connection&&navigator.connection.saveData),c=r?'reduced':(m&&m('(min-width: 1024px) and (pointer: fine)').matches?'full':'light');h.setAttribute('data-motion',c);if(c!=='reduced'){h.classList.add('pl-mp');}try{if(c==='full'&&d.body===null&&!sessionStorage.getItem('plIris')&&<?php echo is_front_page() ? 'true' : 'false'; ?>&&window.scrollY<10){h.classList.add('pl-iris');}}catch(e){}setTimeout(function(){h.classList.remove('pl-mp','pl-iris');},2500);})(document);</script>
	<?php
}, 2 );
