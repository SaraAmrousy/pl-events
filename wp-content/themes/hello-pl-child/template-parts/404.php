<?php
/**
 * 404 — overrides Hello Elementor's template part, which hides its H1 because the theme turns page
 * titles off (H1s come from page content). Found in 6.1 QA: the 404 page had no H1.
 * Old plevents.org URLs are 301'd at launch (deploy/launch/redirects.csv); this catches the rest.
 *
 * @package hello-pl-child
 */

defined( 'ABSPATH' ) || exit;
$events = function_exists( 'plt_page_url' ) ? plt_page_url( 'events' ) : home_url( '/events/' );
?>
<main id="content" class="site-main pl-404">
	<section class="sec" style="padding:96px 0 112px">
		<div class="wrap" style="display:flex;flex-direction:column;gap:20px;max-width:760px">
			<span class="lab mut">Error 404</span>
			<h1 class="h1" style="font-size:56px">This page isn’t <span class="em">here.</span></h1>
			<p class="lead">The link may be from our old site, or the page has moved. Every congress since <?php echo esc_html( class_exists( 'PL_Data' ) ? PL_Data::stats( PL_Data::events() )['since_long'] : '2024' ); ?> is in the events calendar.</p>
			<div class="row">
				<a href="<?php echo esc_url( $events ); ?>" class="btn bp">See all events</a>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn bs">Go to the home page</a>
				<a href="<?php echo esc_url( function_exists( 'plt_contact_url' ) ? plt_contact_url( 'other' ) : home_url( '/contact/' ) ); ?>" class="btn bg">Contact the team</a>
			</div>
		</div>
	</section>
</main>
