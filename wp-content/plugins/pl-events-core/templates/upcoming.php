<?php
/**
 * [pl_upcoming] — Home "Our congresses." with tabs (3.4a H3). All cards are rendered;
 * the ones outside the current tab / limit carry `hidden`, so no-JS is a finished section
 * (tabs are links ?ht=…) and the JS switches tabs without a reload.
 * layout="row" (Home, Thread 8 — replaces the 3.4a H3 grid): one horizontal row, manual only
 * (never autoplays): ← → buttons, scroll-snap, swipe/trackpad, keyboard focus scrolls the row.
 * Without JS it is a plain horizontally scrollable row; the buttons' space is reserved (no CLS).
 *
 * @var array  $a      shortcode attributes
 * @var array  $events computed events, natural order (Live → Upcoming → Past)
 * @var array  $stats
 * @var string $tab
 */
defined( 'ABSPATH' ) || exit;
$pools = array(
	'all'      => $events,
	'live'     => array_values( array_filter( $events, fn( $e ) => 'live' === $e['status'] ) ),
	'upcoming' => array_values( array_filter( $events, fn( $e ) => 'upcoming' === $e['status'] ) ),
	'past'     => array_values( array_filter( $events, fn( $e ) => 'past' === $e['status'] ) ),
);
$want  = array_map( 'trim', explode( ',', $a['tabs'] ) );
$tabs  = array_filter( array( 'all' => 'All', 'live' => 'Live now', 'upcoming' => 'Upcoming', 'past' => 'Past' ), fn( $k ) => in_array( $k, $want, true ) && ( 'live' !== $k || $pools['live'] ), ARRAY_FILTER_USE_KEY );
$tab   = isset( $tabs[ $tab ] ) && $pools[ $tab ] ? $tab : ( isset( $tabs[ $a['default'] ] ) && $pools[ $a['default'] ] ? $a['default'] : 'all' );
$row   = 'row' === $a['layout'];
$limit = $row && ! (int) $a['limit'] ? 999 : max( 1, (int) $a['limit'] );
$shown = array_slice( array_column( $pools[ $tab ], 'id' ), 0, $limit );
$more  = count( $pools[ $tab ] ) - count( $shown );
$cur   = $stats['upcoming'] + $stats['live'];
?>
<section class="sec pl-upcoming" id="<?php echo esc_attr( $a['id'] ); ?>" data-pl="upcoming" data-limit="<?php echo (int) $limit; ?>">
	<div class="wrap" style="display:flex;flex-direction:column;gap:28px">
		<div class="sh">
			<div style="display:flex;flex-direction:column;gap:8px">
				<span class="lab mut"><?php echo esc_html( $cur . ' upcoming · ' . $stats['past'] . ' held since ' . $stats['since'] ); ?></span>
				<?php if ( $a['title'] ) : ?><h2 class="h2 m-title"><?php echo PL_Shortcodes::em( $a['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2><?php endif; ?>
			</div>
			<a href="<?php echo esc_url( PL_Template::events_url() ); ?>" class="btn bs">See all events</a>
		</div>
		<?php if ( count( $tabs ) > 1 || $row ) : ?>
		<div class="pl-tabrow">
		<?php endif; ?>
		<?php if ( count( $tabs ) > 1 ) : ?>
		<div role="tablist" aria-label="Show" class="tabs" style="align-self:flex-start">
			<?php foreach ( $tabs as $k => $label ) : ?>
				<a role="tab" href="<?php echo esc_url( add_query_arg( 'ht', $k ) . '#' . $a['id'] ); ?>" aria-selected="<?php echo $tab === $k ? 'true' : 'false'; ?>" class="tab<?php echo $tab === $k ? ' on' : ''; ?>" data-ht="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?> <span class="n">· <?php echo count( $pools[ $k ] ); ?></span></a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
		<?php if ( $row ) : ?>
		<div class="rail-nav" role="group" aria-label="Scroll the congresses">
			<button type="button" class="ic-btn rail-btn" data-rail="prev" aria-label="Previous" aria-controls="homeGrid" disabled><svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></button>
			<button type="button" class="ic-btn rail-btn" data-rail="next" aria-label="Next" aria-controls="homeGrid"><svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></button>
		</div>
		<?php endif; ?>
		<?php if ( count( $tabs ) > 1 || $row ) : ?>
		</div>
		<?php endif; ?>
		<?php if ( $row ) : ?><div class="pl-rail"><?php endif; ?>
		<div class="<?php echo $row ? 'rail' : 'grid3'; ?> swap" id="homeGrid"<?php echo $row ? ' role="region" aria-label="Congresses" tabindex="-1"' : ''; ?>>
			<?php
			if ( ! $events ) {
				echo '<div class="pl-empty"><strong>No events published yet.</strong></div>';
			}
			foreach ( $events as $e ) {
				echo PL_Template::render( 'card-event', array( 'e' => $e, 'hidden' => ! in_array( $e['id'], $shown, true ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</div>
		<?php if ( $row ) : ?></div><?php endif; ?>
		<a href="<?php echo esc_url( PL_Template::events_url( 'all' !== $tab ? array( 'tab' => $tab ) : array() ) ); ?>" class="btn bg pl-more" style="align-self:flex-start"<?php echo $more > 0 ? '' : ' hidden'; ?>>See <span class="pl-more-n"><?php echo (int) max( 0, $more ); ?></span> more in Events →</a>
	</div>
</section>
