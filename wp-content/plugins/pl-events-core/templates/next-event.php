<?php
/**
 * [pl_next_event layout="strip|card"] — "Next congress" strip in the hero figures bar
 * (3.4b P8), "Happening now" while live. Countdown: days to start; hours in the last 48 h.
 *
 * @var array $a
 * @var array $e
 */
defined( 'ABSPATH' ) || exit;
$live = 'live' === $e['status'];
$d    = (int) $e['days_to_start'];
if ( $live ) {
	$when = 'Live now · ' . $e['live_day'];
} elseif ( $d <= 2 ) {
	$tz    = new DateTimeZone( $e['tz'] );
	$start = new DateTimeImmutable( $e['start'] . ' 00:00:00', $tz );
	$hours = max( 1, (int) ceil( ( $start->getTimestamp() - PL_Dates::now()->getTimestamp() ) / HOUR_IN_SECONDS ) );
	$when  = 'in ' . PL_Template::plural( $hours, 'hour' );
} else {
	$when = 'in ' . $d . ' days';
}
$name = ( $e['series'] ? $e['series_short'] . ' ' . $e['year'] : $e['name'] ) . ( $e['city'] ? ' · ' . $e['city'] : '' );
if ( 'card' === $a['layout'] ) {
	echo PL_Template::render( 'card-event', array( 'e' => $e ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}
?>
<a class="nextc"<?php echo PL_Template::status_attrs( $e ); // phpcs:ignore WordPress.Security.EscapeOutput ?> href="<?php echo esc_url( $e['url'] ); ?>"><span class="lab" style="color:<?php echo $live ? 'var(--liveoi)' : 'var(--boi)'; ?>"><?php echo $live ? '<span class="ldot" aria-hidden="true"></span> Happening now' : 'Next congress'; ?></span><span class="nc-n"><?php echo esc_html( $name ); ?></span><span class="nc-d"><?php echo esc_html( $when ); ?> →</span></a>
