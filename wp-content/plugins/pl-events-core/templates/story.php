<?php
/**
 * [pl_story layout="sticky explore" aspect="1.3"] — About: CSS-sticky map + natively
 * scrolling year chapters + explorer step (3.4b P3). No pin. Each chapter carries the
 * camera framing (data-vb) for the theme's story.js; with JS off it reads top to bottom.
 * Explorer: chips are links (?country=XX#story); all panels are rendered, one visible.
 *
 * @var array  $a
 * @var array  $events
 * @var array  $stats
 * @var string $country
 */
defined( 'ABSPATH' ) || exit;
$sticky = false !== strpos( $a['layout'], 'sticky' );
$explore = false !== strpos( $a['layout'], 'explore' );
$aspect = (float) $a['aspect'];
$narrow = (float) $a['mobile_aspect']; // < 1024 px the map is wider (3.4b: 1.3 desktop, 2.1 below)
$years  = $stats['year_list'];
sort( $years );
if ( count( $years ) <= 6 ) {
	$chapters = array_map( fn( $y ) => array( 'label' => (string) $y, 'from' => $y, 'to' => $y ), $years );
} else {
	$keep     = array_slice( $years, -5 );
	$chapters = array_merge( array( array( 'label' => $years[0] . '–' . ( $keep[0] - 1 ), 'from' => $years[0], 'to' => $keep[0] - 1 ) ), array_map( fn( $y ) => array( 'label' => (string) $y, 'from' => $y, 'to' => $y ), $keep ) );
}
$origin = PL_Data::origin();
$uniq   = fn( $arr ) => array_values( array_unique( $arr ) );
$n      = count( $chapters );
$country = isset( $stats['country_list'] ) && in_array( $country, $stats['country_list'], true ) ? $country : '';
?>
<section class="ink pl-story" id="story" aria-label="Our record on the map" data-pl="story">
	<div class="wrap" style="padding-top:88px;padding-bottom:96px;display:flex;flex-direction:column;gap:28px">
		<div style="display:flex;flex-direction:column;gap:10px">
			<?php $pl_since = PL_Template::plural( $stats['events'], 'event' ) . ' since ' . $stats['since']; ?>
			<?php if ( $sticky ) : /* wide screens (≥1024): the story; narrower: the explorer only (3.4b P3) */ ?>
			<span class="lab mut pl-wide"><?php echo esc_html( 'The story so far · ' . $pl_since ); ?></span>
			<h2 class="h2 m-title pl-wide">One head office. A region of <span class="em">congresses.</span></h2>
			<?php endif; ?>
			<span class="lab mut<?php echo $sticky ? ' pl-narrow' : ''; ?>"><?php echo esc_html( 'Our record · ' . $pl_since ); ?></span>
			<h2 class="h2 m-title<?php echo $sticky ? ' pl-narrow' : ''; ?>">Explore it <span class="em">yourself.</span></h2>
		</div>
		<div class="story2">
			<div class="mapcol"><div class="stick vf vfoi" id="mapWrap"><?php echo PL_Template::render( 'map', array( 'events' => $events, 'aspect' => $aspect, 'narrow' => $narrow ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div></div>
			<div class="steps">
				<?php
				if ( $sticky ) :
					foreach ( $chapters as $i => $ch ) :
						$yr     = array_values( array_filter( $events, fn( $e ) => $e['year'] >= $ch['from'] && $e['year'] <= $ch['to'] ) );
						$upto   = array_values( array_filter( $events, fn( $e ) => $e['year'] <= $ch['to'] ) );
						$before = array_map( fn( $e ) => $e['city'], array_filter( $events, fn( $e ) => $e['year'] < $ch['from'] ) );
						$ycity  = $uniq( array_filter( array_map( fn( $e ) => $e['city'], $yr ) ) );
						$new    = array_values( array_diff( $ycity, $before ) );
						$lls    = array_merge( array_column( PL_Map::points( $upto ), 'll' ), array( $origin['lonlat'] ) ); // camera frames everything on the map so far (prototype mapState)
						$annc   = (bool) array_filter( $yr, fn( $e ) => 'past' !== $e['status'] );
						$su     = PL_Data::stats( $upto );
						?>
				<article class="step pl-wide" data-ch="<?php echo (int) $i; ?>" data-vb="<?php echo esc_attr( PL_Map::view_box( $lls, 330, $aspect ) ); ?>" data-cities="<?php echo esc_attr( implode( '|', $ycity ) ); ?>">
					<div class="sidein" style="display:flex;flex-direction:column;gap:16px">
						<span class="lab mut">Chapter <?php echo (int) ( $i + 1 ); ?> / <?php echo (int) $n; ?></span>
						<span class="num" style="font-size:96px"><?php echo esc_html( $ch['label'] ); ?></span>
						<p style="font-size:18px;font-weight:600;line-height:1.45"><?php echo esc_html( PL_Template::plural( count( $yr ), 'event' ) . ' in ' . PL_Template::plural( count( $ycity ), 'city', 'cities' ) . ( $new ? ' · new on the map: ' . implode( ', ', $new ) : '' ) . ( $annc ? ' (announced)' : '' ) . '.' ); ?></p>
						<ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:10px;font-size:15px;line-height:1.45">
							<?php foreach ( $yr as $e ) : ?>
							<li style="display:flex;gap:10px;align-items:flex-start"><?php echo PL_Template::flag( $e['country'], false, ' style="margin-top:4px"' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><a href="<?php echo esc_url( $e['url'] ); ?>" style="font-weight:700;text-decoration:none"><?php echo esc_html( $e['name'] ); ?></a><br><span class="mut"><?php echo esc_html( $e['month_short'] . ' · ' . $e['place'] ); ?></span></span></li>
							<?php endforeach; ?>
						</ul>
						<div style="border-top:1.5px solid #2A3149;padding-top:14px;display:flex;gap:22px;flex-wrap:wrap">
							<?php foreach ( array( array( $su['congresses'], 1 === $su['congresses'] ? 'congress' : 'congresses' ), array( $su['cities'], 1 === $su['cities'] ? 'city' : 'cities' ), array( $su['countries'], 1 === $su['countries'] ? 'country' : 'countries' ) ) as $t ) : ?>
							<span style="display:flex;flex-direction:column;gap:2px"><span class="num" style="font-size:36px"><?php echo (int) $t[0]; ?></span><span class="mut" style="font-size:13px;font-weight:600"><?php echo esc_html( $t[1] ); ?></span></span>
							<?php endforeach; ?>
						</div>
						<span class="lab mut">Since <?php echo esc_html( $stats['since'] ); ?> · counted from the events on this site</span>
					</div>
				</article>
						<?php
					endforeach;
				endif;
				if ( $explore ) :
					$panels = array_merge( array( '' ), $stats['country_list'] );
					?>
				<div class="step stepx" data-explore="1">
					<?php if ( $sticky ) : ?><h3 class="h3 pl-wide" style="font-size:32px">Now explore it <span class="em">yourself.</span></h3><?php endif; ?>
					<div class="chips" id="cchips" role="group" aria-label="Choose a country">
						<?php foreach ( $panels as $cc ) : ?>
							<?php $n_cc = $cc ? count( array_filter( $events, fn( $e ) => $e['country'] === $cc ) ) : count( $events ); ?>
							<a class="choice<?php echo $country === $cc ? ' on' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'country', $cc ? $cc : false ) . '#story' ); ?>" data-cc="<?php echo esc_attr( $cc ); ?>" <?php echo $country === $cc ? 'aria-current="true"' : ''; ?>><?php echo $cc ? PL_Template::flag( $cc ) . esc_html( PL_Geo::name( $cc ) ) : 'All countries'; // phpcs:ignore WordPress.Security.EscapeOutput ?> <span style="opacity:.65"><?php echo (int) $n_cc; ?></span></a>
						<?php endforeach; ?>
					</div>
					<div id="side" aria-live="polite">
						<?php
						foreach ( $panels as $cc ) :
							$ex   = $cc ? array_values( array_filter( $events, fn( $e ) => $e['country'] === $cc ) ) : $events;
							$next = null;
							foreach ( $ex as $e ) {
								if ( 'past' !== $e['status'] ) {
									$next = $e;
									break;
								}
							}
							$pastl = array_values( array_filter( $ex, fn( $e ) => 'past' === $e['status'] ) );
							$last  = $pastl ? end( $pastl ) : null;
							$name  = $cc ? PL_Geo::name( $cc ) : 'All countries';
							$off   = PL_Data::office_for( $cc );
							$specs = $uniq( array_merge( array(), ...array_map( fn( $e ) => (array) $e['specialties'], $ex ) ) );
							$lls   = array_merge( array_column( PL_Map::points( $ex ), 'll' ), array( $origin['lonlat'] ) );
							?>
						<div class="sidein pl-explore" data-cc="<?php echo esc_attr( $cc ); ?>" data-vb="<?php echo esc_attr( PL_Map::view_box( $lls, 330, $aspect ) ); ?>" data-vb-narrow="<?php echo esc_attr( PL_Map::view_box( $lls, 330, $narrow ) ); ?>" style="display:flex;flex-direction:column;gap:16px"<?php echo $country === $cc ? '' : ' hidden'; ?>>
							<strong style="font-size:34px;line-height:1.1;letter-spacing:-.02em;display:flex;gap:12px;align-items:center"><?php echo $cc ? PL_Template::flag( $cc, true ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $name ); ?></strong>
							<dl class="dl">
								<dt>Congresses</dt><dd><?php echo count( $ex ); ?></dd>
								<dt>Cities</dt><dd><?php echo esc_html( implode( ', ', $uniq( array_filter( array_map( fn( $e ) => $e['city'], $ex ) ) ) ) ); ?></dd>
								<dt>First with PL</dt><dd><?php echo esc_html( $ex[0]['name'] . ', ' . $ex[0]['month_short'] ); ?></dd>
								<dt>Specialties</dt><dd><?php echo esc_html( implode( ', ', $specs ) ); ?></dd>
								<dt><?php echo $next ? 'Next' : 'Latest'; ?></dt><dd><?php if ( $next ) : ?><a href="<?php echo esc_url( $next['url'] ); ?>"><?php echo esc_html( $next['name'] ); ?></a>, <?php echo esc_html( $next['month_short'] ); ?><?php elseif ( $last ) : ?><?php echo esc_html( $last['name'] . ', ' . $last['month_short'] ); ?><?php else : ?>—<?php endif; ?></dd>
							</dl>
							<div class="row" style="margin-top:6px"><a href="<?php echo esc_url( PL_Template::events_url( $cc ? array( 'country' => $cc ) : array() ) ); ?>" class="btn bw"><?php echo esc_html( $cc ? 'See events in ' . $name : 'See all events' ); ?></a><a href="<?php echo esc_url( PL_Template::contact_url( 'proposal', $off ? $off['id'] : '' ) ); ?>" class="btn bso">Plan yours here</a></div>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
