<?php
/**
 * Template for /events/{slug}/ (the theme can replace it with pl-events/single-event.php).
 */
defined( 'ABSPATH' ) || exit;
get_header();
$pl_event = PL_Event_Page::current();
echo '<main id="content" class="site-main pl-main" tabindex="-1">';
echo $pl_event ? PL_Template::render( 'event-page', array( 'e' => $pl_event ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput
echo '</main>';
get_footer();
