<?php
/**
 * Header and footer markup (Step 4.3), placed by UAE Header Footer Builder:
 * each HFB template holds one Elementor "Shortcode" widget — [pl_site_header] / [pl_site_footer].
 * HFB decides where they show (display rules); this file draws them exactly like the prototype
 * (same classes, so the sticky / transparent-over-hero / mobile-menu behaviour carries over).
 * Links come from Appearance › Menus ("Main menu (header)", "Footer menu"); with no menu set,
 * the six pages are linked by their slugs. Data (portfolio, offices, social, live event) comes
 * from pl-events-core.
 *
 * @package hello-pl-child
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'pl_site_header', 'plt_site_header' );
add_shortcode( 'pl_site_footer', 'plt_site_footer' );

add_action( 'wp_enqueue_scripts', static function () {
	wp_enqueue_script( 'plt-header', PLT_URL . 'assets/js/header.js', array(), plt_ver( 'assets/js/header.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
}, 30 );

/** Page link by slug (fallback when no menu is assigned). */
function plt_page_url( $slug ) {
	if ( 'home' === $slug ) {
		return home_url( '/' );
	}
	$p = get_page_by_path( $slug );
	return $p ? get_permalink( $p ) : home_url( '/' . $slug . '/' );
}

/** Is this link the current page? Event pages count as "Events". */
function plt_is_current( $url ) {
	$path = static fn( $u ) => untrailingslashit( (string) wp_parse_url( $u, PHP_URL_PATH ) );
	$here = untrailingslashit( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/', PHP_URL_PATH ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- compared only. (home_url(REQUEST_URI) doubles /pl/ in a sub-folder.)
	$link = $path( $url );
	if ( '' === $link || $path( home_url( '/' ) ) === $link ) {
		return is_front_page();
	}
	return $here === $link || ( is_singular( 'event' ) && $link === $path( plt_page_url( 'events' ) ) );
}

/** [ [label, url], … ] from a menu location, else the default pages. */
function plt_links( $location, array $defaults ) {
	$locs = get_nav_menu_locations();
	if ( ! empty( $locs[ $location ] ) ) {
		$items = wp_get_nav_menu_items( $locs[ $location ] );
		if ( $items ) {
			return array_map( static fn( $i ) => array( $i->title, $i->url, $i->target ), array_values( array_filter( $items, static fn( $i ) => ! $i->menu_item_parent ) ) );
		}
	}
	return array_map( static fn( $s ) => array( ucfirst( $s ), plt_page_url( $s ), '' ), $defaults );
}

function plt_portfolio() {
	$c = class_exists( 'PL_Data' ) ? PL_Data::company() : array();
	return array( 'url' => $c['portfolio']['url'] ?? '', 'size' => $c['portfolio']['size'] ?? '' );
}

function plt_contact_url( $topic = 'proposal' ) {
	return class_exists( 'PL_Template' ) ? PL_Template::contact_url( $topic ) : add_query_arg( 'topic', $topic, plt_page_url( 'contact' ) );
}

function plt_wordmark( $id_suffix = '' ) {
	return '<img class="mk-i" src="' . esc_url( PLT_URL . 'assets/img/mark-ink.png' ) . '" alt="" width="27" height="38" style="height:38px;width:auto"><img class="mk-w" src="' . esc_url( PLT_URL . 'assets/img/mark-white.png' ) . '" alt="" width="27" height="38" style="height:38px;width:auto"><b>PL</b> <span>EVENTS</span>';
}

function plt_site_header() {
	$links = plt_links( 'pl-main', array( 'home', 'events', 'services', 'about', 'careers', 'contact' ) );
	$pf    = plt_portfolio();
	$nav   = '';
	$mnav  = '';
	foreach ( $links as $l ) {
		$cur   = plt_is_current( $l[1] ) ? ' aria-current="page"' : '';
		$tgt   = $l[2] ? ' target="' . esc_attr( $l[2] ) . '" rel="noopener"' : '';
		$nav  .= '<a href="' . esc_url( $l[1] ) . '"' . $cur . $tgt . '>' . esc_html( $l[0] ) . '</a>';
		$mnav .= '<a class="l" href="' . esc_url( $l[1] ) . '"' . $cur . $tgt . '>' . esc_html( $l[0] ) . '</a>';
	}
	$pf_btn = $pf['url'] ? '<a href="' . esc_url( $pf['url'] ) . '" class="btn bp sm" target="_blank" rel="noopener" aria-label="' . esc_attr( 'Portfolio, PDF' . ( $pf['size'] ? ', ' . $pf['size'] : '' ) ) . '">Portfolio</a>' : '';
	$menu_icon = '<svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><path d="M3 6h14M3 10h14M3 14h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
	$x_icon    = '<svg width="18" height="18" viewBox="0 0 20 20" aria-hidden="true"><path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
	$ribbon    = shortcode_exists( 'pl_live_ribbon' ) ? do_shortcode( '[pl_live_ribbon]' ) : '';
	ob_start();
	?>
<?php // Skip link: UAE HFB prints its own ("Skip to main content" → #content) — not duplicated here. ?>
<div class="topbar"><?php echo $ribbon; // phpcs:ignore WordPress.Security.EscapeOutput -- plugin template, escaped there. ?>
<header class="hdr" id="hdr"><div class="wrap in">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="wm" aria-label="PL Events — Home"><?php echo plt_wordmark(); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
	<nav aria-label="Main" class="nav"><?php echo $nav; // phpcs:ignore WordPress.Security.EscapeOutput -- built with esc_* above. ?></nav>
	<div class="acts">
		<a href="<?php echo esc_url( plt_contact_url( 'proposal' ) ); ?>" class="btn bs sm">Request a proposal</a>
		<?php echo $pf_btn; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<button type="button" class="btn ic menu-btn" data-act="menu" aria-label="Open menu" aria-expanded="false" aria-controls="mmenu" style="border:1.5px solid currentColor;background:transparent;color:inherit"><?php echo $menu_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
	</div>
</div></header></div>
<?php if ( is_front_page() ) : // Over the hero from the first paint — set while parsing, so nothing shifts (CLS). Without JS the header stays solid. ?>
<script>(function(){var h=document.getElementById('hdr'),t=h&&h.parentNode;if(h){h.classList.add('over');t.classList.add('fx');}})();</script>
<?php endif; ?>
<div class="mmenu" id="mmenu" hidden role="dialog" aria-modal="true" aria-label="Menu">
	<div class="row" style="justify-content:space-between;margin-bottom:12px"><span class="wm"><img src="<?php echo esc_url( PLT_URL . 'assets/img/mark-ink.png' ); ?>" alt="" width="23" height="32" style="height:32px;width:auto"><b>PL</b> <span>EVENTS</span></span><button type="button" class="btn ic" data-act="menu-close" aria-label="Close menu" style="border:1.5px solid var(--cb)"><?php echo $x_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?></button></div>
	<?php echo $mnav; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<a href="<?php echo esc_url( plt_contact_url( 'proposal' ) ); ?>" class="btn bp" style="margin-top:18px">Request a proposal</a>
</div>
	<?php
	return (string) ob_get_clean();
}

function plt_site_footer() {
	$c       = class_exists( 'PL_Data' ) ? PL_Data::company() : array( 'tagline' => 'Science in focus.', 'social' => array() );
	$pf      = plt_portfolio();
	$links   = plt_links( 'pl-footer', array( 'events', 'services', 'about', 'careers', 'contact' ) );
	$offices = shortcode_exists( 'pl_offices' ) ? do_shortcode( '[pl_offices layout="compact" hide_on="contact"]' ) : '';
	$tag     = (string) ( $c['tagline'] ?? '' );
	$tag_html = preg_match( '/^(\S+)\s+(.+)$/', $tag, $m ) ? esc_html( $m[1] ) . '<br><span class="em">' . esc_html( $m[2] ) . '</span>' : esc_html( $tag );
	$credits = class_exists( 'PL_Geo' ) ? PL_Geo::credits() : '';
	ob_start();
	?>
<footer class="sand cutt foot" style="padding:112px 0 32px"><div class="wrap" style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:32px" id="ft">
	<div style="grid-column:span 4;display:flex;flex-direction:column;gap:20px" class="ftc"><img src="<?php echo esc_url( PLT_URL . 'assets/img/logo-ink-sm.png' ); ?>" alt="PL Events — Conference organization" width="120" height="98" style="width:120px;height:auto" loading="lazy" decoding="async"><p style="font-size:44px;font-weight:800;line-height:.95;letter-spacing:-.035em"><?php echo $tag_html; // phpcs:ignore WordPress.Security.EscapeOutput ?></p></div>
	<div style="grid-column:span 6;align-content:start" class="ftc"><?php echo '' !== $offices ? $offices : '<p class="mut" style="font-size:17px;margin:0">Both offices are listed above, with phones, WhatsApp and email.</p>'; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	<nav aria-label="Footer" class="nav ftc" style="grid-column:span 2;display:flex;flex-direction:column;gap:12px">
		<?php foreach ( $links as $l ) : ?><a href="<?php echo esc_url( $l[1] ); ?>"<?php echo plt_is_current( $l[1] ) ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $l[0] ); ?></a><?php endforeach; ?>
		<?php if ( $pf['url'] ) : ?><a href="<?php echo esc_url( $pf['url'] ); ?>" target="_blank" rel="noopener">Portfolio (PDF<?php echo $pf['size'] ? ', ' . esc_html( $pf['size'] ) : ''; ?>)</a><?php endif; ?>
	</nav>
	<div style="grid-column:1/-1;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;border-top:2px solid var(--ink);padding-top:20px;font-size:13px">
		<span class="mut">© <?php echo esc_html( wp_date( 'Y' ) ); ?> PL Events<?php if ( get_privacy_policy_url() ) : ?> · <a href="<?php echo esc_url( get_privacy_policy_url() ); ?>">Privacy</a><?php endif; ?><?php echo $credits ? ' · ' . esc_html( $credits ) : ''; ?></span>
		<span style="display:flex;gap:18px;font-weight:700;flex-wrap:wrap"><?php foreach ( (array) ( $c['social'] ?? array() ) as $s ) : ?><a href="<?php echo esc_url( $s['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $s['label'] ); ?></a><?php endforeach; ?></span>
	</div>
</div></footer>
	<?php
	return (string) ob_get_clean();
}
