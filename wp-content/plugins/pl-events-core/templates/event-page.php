<?php
/**
 * Event page body (/events/{slug}/): poster, facts, actions, mini map with arc from the
 * head office, editions in the series, contact, more upcoming. Override in the theme.
 *
 * @var array $e computed event
 */
defined( 'ABSPATH' ) || exit;
$all  = PL_Data::events( array( 'order' => 'natural' ) );
$eds  = $e['series'] ? array_values( array_filter( $all, fn( $x ) => $x['series'] === $e['series'] ) ) : array();
usort( $eds, fn( $x, $y ) => strcmp( $y['start'], $x['start'] ) );
$more = array_slice( array_values( array_filter( $all, fn( $x ) => $x['id'] !== $e['id'] && 'past' !== $x['status'] && array_intersect( (array) $x['specialties'], (array) $e['specialties'] ) ) ), 0, 3 );
$ho   = PL_Data::head_office();
$origin  = PL_Data::origin();
?>
<div class="pl-event"<?php echo PL_Template::status_attrs( $e ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
<section class="ev-hero" style="padding:64px 0 72px"><div class="wrap evh" style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:40px;align-items:start">
	<div style="grid-column:span 7;display:flex;flex-direction:column;gap:18px" class="evh-l">
		<a href="<?php echo esc_url( PL_Template::events_url() ); ?>" style="font-weight:700;color:var(--boi);text-decoration:none">← All events</a>
		<div class="row"><span class="badge <?php echo esc_attr( $e['badge'][1] ); ?>"><?php echo 'live' === $e['status'] ? '<span class="ldot" style="background:#fff" aria-hidden="true"></span>' : '<i aria-hidden="true"></i>'; ?><span class="bt"><?php echo esc_html( $e['badge'][0] ); ?></span></span><span class="lab" style="color:var(--oim)"><?php echo esc_html( $e['spec_line'] . ( $e['series'] ? ' · ' . $e['series'] . ' series' : '' ) ); ?></span><?php echo $e['sample'] ? PL_Template::sample_tag() : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<h1 class="h1 h1ev"><?php echo esc_html( $e['name'] ); ?></h1>
		<dl class="dl" style="font-size:17px">
			<dt>When</dt><dd><?php echo esc_html( $e['range'] . ( 'live' === $e['status'] ? ' · ' . $e['live_day'] : '' ) ); ?></dd>
			<dt>Where</dt><dd style="display:flex;gap:8px;align-items:center;flex-wrap:wrap"><?php echo PL_Template::flag( $e['country'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( trim( ( $e['venue'] ? $e['venue'] . ', ' : '' ) . $e['place'] ) ); ?></dd>
			<?php if ( $e['edition_text'] ) : ?><dt>Edition</dt><dd><?php echo esc_html( $e['edition_text'] ); ?></dd><?php endif; ?>
			<?php if ( $e['delegates'] ) : ?><dt>Delegates</dt><dd><?php echo esc_html( number_format_i18n( $e['delegates'] ) ); ?></dd><?php endif; ?>
		</dl>
		<?php if ( $e['description'] ) : ?><p class="lead" style="color:var(--oim)"><?php echo esc_html( $e['description'] ); ?></p><?php endif; ?>
		<div class="row ev-acts"><?php echo PL_Template::actions( $e, 'page' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?></div>
		<?php if ( ! $e['can_register'] && 'past' !== $e['status'] ) : ?><p class="mut" style="font-size:15px">Registration opens soon — the button appears here when it does.</p><?php endif; ?>
		<?php if ( $e['more_links'] ) : ?>
			<ul class="pl-more-links" style="margin:0;padding:0;list-style:none;display:flex;flex-wrap:wrap;gap:10px 22px">
				<?php foreach ( $e['more_links'] as $l ) : ?><li><a href="<?php echo esc_url( $l['url'] ); ?>" target="_blank" rel="noopener" style="font-weight:700"><?php echo esc_html( $l['label'] ); ?> →</a></li><?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<div style="grid-column:span 5;display:flex;flex-direction:column;gap:16px" class="evh-r">
		<div class="<?php echo 'past' === $e['status'] ? 'poster past' : 'poster'; ?> grain" style="border-radius:10px;aspect-ratio:16/10" aria-hidden="true">
			<?php echo $e['poster'] ? PL_Images::img( $e['poster'], 'poster10', array( 'class' => 'pimg', 'sizes' => '(max-width: 1023px) 100vw, 480px', 'eager' => true ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
			<?php if ( ! $e['poster'] ) : ?><span class="pw" style="font-size:52px;top:28px"><?php echo esc_html( $e['poster_word'] ); ?></span><span class="pe"><?php echo esc_html( $e['edition_text'] ); ?><br><?php echo esc_html( $e['city'] ); ?></span><?php endif; ?><?php echo PL_Template::date_box( $e ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<?php if ( $e['logo'] ) : ?><div class="tile pl-evlogo"><?php echo PL_Images::img( $e['logo'], 'logo', array( 'alt' => $e['name'] . ' logo', 'sizes' => '240px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div><?php endif; ?>
		<?php
		if ( $e['lonlat'] ) :
			$near = hypot( ...array_map( fn( $a, $b ) => $a - $b, PL_Map::proj( $e['lonlat'] ), PL_Map::proj( $origin['lonlat'] ) ) ) < 18;
			$vb   = PL_Map::view_box( array( $origin['lonlat'], $e['lonlat'] ), 260 );
			list( $x, $y )   = PL_Map::proj( $e['lonlat'] );
			list( $hx, $hy ) = PL_Map::proj( $origin['lonlat'] );
			?>
		<div class="map grain evmap"><svg viewBox="<?php echo esc_attr( $vb ); ?>" role="img" aria-label="<?php echo esc_attr( 'Map: ' . $e['place'] . ( $near ? '' : ', linked to PL’s head office in ' . $origin['city'] ) ); ?>">
			<image href="<?php echo esc_url( PL_Map::dots_url() ); ?>" x="0" y="0" width="783.5" height="469"/>
			<?php if ( ! $near ) : ?>
			<path class="arc evarc" d="<?php echo esc_attr( PL_Map::arc( $origin['lonlat'], $e['lonlat'] ) ); ?>"/>
			<circle cx="<?php echo esc_attr( sprintf( '%.1f', $hx ) ); ?>" cy="<?php echo esc_attr( sprintf( '%.1f', $hy ) ); ?>" r="4" fill="#FAFAF7" stroke="#0A0D18" stroke-width="1.2"/>
			<text x="<?php echo esc_attr( sprintf( '%.1f', $hx + 8 ) ); ?>" y="<?php echo esc_attr( sprintf( '%.1f', $hy - 8 ) ); ?>" fill="#B9BDC9" style="font:600 8px Montserrat,sans-serif"><?php echo esc_html( $origin['city'] ); ?></text>
			<?php endif; ?>
			<circle class="evring" cx="<?php echo esc_attr( sprintf( '%.1f', $x ) ); ?>" cy="<?php echo esc_attr( sprintf( '%.1f', $y ) ); ?>" r="10" fill="none" stroke="#9DB2F2" stroke-width="1.5" opacity=".7"/>
			<circle cx="<?php echo esc_attr( sprintf( '%.1f', $x ) ); ?>" cy="<?php echo esc_attr( sprintf( '%.1f', $y ) ); ?>" r="5.5" fill="#9DB2F2" stroke="#0A0D18" stroke-width="1.2"/>
			<text x="<?php echo esc_attr( sprintf( '%.1f', $x + 12 ) ); ?>" y="<?php echo esc_attr( sprintf( '%.1f', $y + 4 ) ); ?>" fill="#FAFAF7" style="font:700 11px Montserrat,sans-serif"><?php echo esc_html( $e['city'] ); ?></text>
		</svg></div>
		<?php endif; ?>
	</div>
</div></section>
<?php if ( $e['photos'] ) : ?>
<section class="sec pl-gallery-sec"><div class="wrap" style="display:flex;flex-direction:column;gap:18px"><h2 class="h3" style="font-size:32px">Photos</h2>
	<ul class="pl-gallery">
		<?php foreach ( $e['photos'] as $ph ) : ?>
		<li><a class="pl-frame" href="<?php echo esc_url( $ph ); ?>" target="_blank" rel="noopener"><?php echo PL_Images::img( $ph, 'photo', array( 'alt' => PL_Images::alt( $ph ) ? PL_Images::alt( $ph ) : 'Photo from ' . $e['name'], 'sizes' => '(max-width: 767px) 50vw, 25vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></li>
		<?php endforeach; ?>
	</ul>
</div></section>
<?php endif; ?>
<?php if ( count( $eds ) > 1 ) : ?>
<section class="sec"><div class="wrap" style="display:flex;flex-direction:column;gap:18px"><h2 class="h3" style="font-size:32px"><?php echo esc_html( $e['series'] ); ?> editions with PL</h2><div>
	<?php foreach ( $eds as $x ) : ?>
	<div class="erow erow-ed"><?php echo PL_Template::thumb( $x ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="r-dt" style="font-weight:800"><?php echo esc_html( $x['range'] ); ?></span><a href="<?php echo esc_url( $x['url'] ); ?>" style="font-weight:700;text-decoration:none"<?php echo $x['id'] === $e['id'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $x['name'] . ( $x['id'] === $e['id'] ? ' (this page)' : '' ) ); ?></a><span class="badge <?php echo esc_attr( $x['badge'][1] ); ?>"><i aria-hidden="true"></i><?php echo esc_html( $x['badge'][0] ); ?></span></div>
	<?php endforeach; ?>
</div></div></section>
<?php endif; ?>
<section class="sand sec" style="padding:64px 0"><div class="wrap evc" style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:32px">
	<div style="grid-column:span 5;display:flex;flex-direction:column;gap:10px" class="evc-l"><h2 class="h3">Contact for this event</h2>
	<?php if ( $e['phones'] || $e['email'] ) : ?>
		<div style="display:flex;flex-direction:column;gap:6px">
		<?php foreach ( $e['phones'] as $p ) : ?><a href="<?php echo esc_url( $p['tel'], array( 'tel' ) ); ?>" style="font-weight:600"><?php echo esc_html( ( $p['label'] ? $p['label'] . ': ' : '' ) . $p['number'] ); ?></a><?php if ( $p['wa'] ) : ?><a href="<?php echo esc_url( $p['wa'] ); ?>" target="_blank" rel="noopener" style="font-weight:600">WhatsApp <?php echo esc_html( $p['number'] ); ?></a><?php endif; ?><?php endforeach; ?>
		<?php if ( $e['email'] ) : ?><a href="<?php echo esc_url( 'mailto:' . $e['email'] ); ?>" style="font-weight:600"><?php echo esc_html( $e['email'] ); ?></a><?php endif; ?>
		</div>
	<?php else : ?><p class="mut">PL’s head office handles this event’s questions.</p><?php endif; ?>
	</div>
	<div style="grid-column:span 7" class="evc-r"><?php echo $ho ? PL_Template::render( 'office-block', array( 'o' => $ho ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
</div></section>
<?php if ( $more ) : ?>
<section class="sec"><div class="wrap" style="display:flex;flex-direction:column;gap:24px"><div class="sh"><h2 class="h3" style="font-size:32px">More upcoming <?php echo esc_html( strtolower( (string) ( $e['specialties'][0] ?? '' ) ) ); ?> congresses</h2><a href="<?php echo esc_url( PL_Template::events_url() ); ?>" class="btn bs">Back to all events</a></div><div class="grid3">
	<?php foreach ( $more as $x ) { echo PL_Template::render( 'card-event', array( 'e' => $x ) ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div></div></section>
<?php endif; ?>
</div>
