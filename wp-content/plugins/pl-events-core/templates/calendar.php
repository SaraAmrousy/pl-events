<?php
/**
 * [pl_calendar] — Events page (locked decision 5). A GET form: works without JS
 * (state in the URL: ?tab=&q=&specialty=&country=&yr=&type=&view=). The JS filters in
 * place, keeps the URL in sync, and moves the filters into a bottom sheet on mobile.
 * Every event is rendered; the ones that don't match carry `hidden`.
 *
 * @var array $a      attributes
 * @var array $events computed events, natural order
 * @var array $stats
 * @var array $req    sanitized query args
 */
defined( 'ABSPATH' ) || exit;
$live    = array_values( array_filter( $events, fn( $e ) => 'live' === $e['status'] ) );
$hasLive = (bool) $live;
$counts  = array( 'all' => count( $events ), 'live' => count( $live ), 'upcoming' => $stats['upcoming'], 'past' => $stats['past'] );
$tabs    = array( 'all' => 'All', 'live' => 'Live now', 'upcoming' => 'Upcoming', 'past' => 'Past' );
if ( ! $hasLive ) {
	unset( $tabs['live'] );
}
$tab  = $req['tab'] ?? $a['default'];
$tab  = isset( $tabs[ $tab ] ) ? $tab : 'all';
$view = in_array( $req['view'] ?? '', array( 'grid', 'list' ), true ) ? $req['view'] : $a['view'];
$q    = (string) ( $req['q'] ?? '' );
$F    = array(
	'specialty' => (string) ( $req['specialty'] ?? '' ),
	'country'   => strtoupper( (string) ( $req['country'] ?? '' ) ),
	'yr'        => (string) ( $req['yr'] ?? '' ),
	'type'      => (string) ( $req['type'] ?? '' ),
);
$want  = array_map( 'trim', explode( ',', $a['filters'] ) );
$fdefs = array();
if ( in_array( 'specialty', $want, true ) ) {
	$fdefs['specialty'] = array( 'Specialty', 'All specialties', array_combine( $stats['specialty_list'], $stats['specialty_list'] ) ?: array() );
}
if ( in_array( 'country', $want, true ) ) {
	$c = array();
	foreach ( $stats['country_list'] as $cc ) {
		$c[ $cc ] = PL_Geo::name( $cc );
	}
	$fdefs['country'] = array( 'Country', 'All countries', $c );
}
if ( in_array( 'year', $want, true ) ) {
	$y = $stats['year_list'];
	rsort( $y );
	$fdefs['yr'] = array( 'Year', 'All years', array_combine( array_map( 'strval', $y ), array_map( 'strval', $y ) ) ?: array() );
}
if ( in_array( 'type', $want, true ) ) {
	$fdefs['type'] = array( 'Type', 'All types', array_combine( $stats['type_list'], $stats['type_list'] ) ?: array() );
}
$match = static function ( $e ) use ( $tab, $hasLive, $F, $q ) {
	if ( 'all' === $tab && $hasLive && 'live' === $e['status'] ) {
		return false; // shown in the Live band.
	}
	if ( 'all' !== $tab && $e['status'] !== $tab ) {
		return false;
	}
	return PL_Data::matches( $e, array( 'status' => 'all', 'specialty' => $F['specialty'], 'country' => $F['country'], 'year' => $F['yr'], 'type' => $F['type'], 'q' => $q ) );
};
$shown = array_filter( $events, $match );
$chips = array();
if ( $F['specialty'] ) {
	$chips['specialty'] = 'Specialty: ' . $F['specialty'];
}
if ( $F['country'] ) {
	$chips['country'] = 'Country: ' . PL_Geo::name( $F['country'] );
}
if ( $F['yr'] ) {
	$chips['yr'] = 'Year: ' . $F['yr'];
}
if ( $F['type'] ) {
	$chips['type'] = 'Type: ' . $F['type'];
}
if ( '' !== trim( $q ) ) {
	$chips['q'] = '“' . trim( $q ) . '”';
}
$fcount = count( array_filter( $F ) );
$self   = remove_query_arg( PL_Shortcodes::CAL_ARGS );
$views  = array_map( 'trim', explode( ',', $a['views'] ) );
$filter_fields = static function ( $suffix ) use ( $fdefs, $F ) {
	$out = '';
	foreach ( $fdefs as $k => $d ) {
		$out .= '<label class="fld"><span class="fl">' . esc_html( $d[0] ) . '</span><select class="sel" name="' . esc_attr( $k ) . '" data-f="' . esc_attr( $k ) . '" id="pl-f-' . esc_attr( $k . $suffix ) . '"><option value="">' . esc_html( $d[1] ) . '</option>';
		foreach ( $d[2] as $v => $l ) {
			$out .= '<option value="' . esc_attr( $v ) . '"' . selected( (string) $F[ $k ], (string) $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		$out .= '</select></label>';
	}
	return $out;
};
?>
<div class="pl-calendar" data-pl="calendar" data-default="<?php echo esc_attr( $a['default'] ); ?>">
<?php if ( $a['heading'] ) : ?>
<section style="padding:72px 0 36px" class="pg-head"><div class="wrap sh" style="gap:32px"><div style="display:flex;flex-direction:column;gap:14px"><span class="lab mut"><?php echo esc_html( 'Calendar · ' . $stats['events'] . ' events since ' . $stats['since'] ); ?></span><h1 class="h1"><?php echo esc_html( $a['heading'] ); ?></h1></div><?php if ( $a['intro'] ) : ?><p class="mut lead" style="max-width:520px;font-size:18px"><?php echo esc_html( str_replace( '{since}', $stats['since_long'], $a['intro'] ) ); ?></p><?php endif; ?></div></section>
<?php endif; ?>
<form class="ebar" id="ebar" method="get" action="<?php echo esc_url( $self ); ?>" role="search" aria-label="Filter events">
	<div class="wrap in">
		<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
		<input type="hidden" name="view" value="<?php echo esc_attr( $view ); ?>">
		<div class="row" style="gap:16px">
			<div role="tablist" aria-label="When" class="tabs">
				<?php foreach ( $tabs as $k => $l ) : ?>
					<button type="submit" name="tab" value="<?php echo esc_attr( $k ); ?>" role="tab" aria-selected="<?php echo $tab === $k ? 'true' : 'false'; ?>" class="tab<?php echo $tab === $k ? ' on' : ''; ?>" data-tab="<?php echo esc_attr( $k ); ?>"><?php echo 'live' === $k ? '<span class="ldot" aria-hidden="true" style="margin-right:6px"></span>' : ''; ?><?php echo esc_html( $l ); ?> <span class="n">· <?php echo (int) $counts[ $k ]; ?></span></button>
				<?php endforeach; ?>
			</div>
			<?php if ( '1' === (string) $a['search'] ) : ?>
			<label class="sbox"><span class="sr">Search events</span><?php echo PL_Template::icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><input class="inp search" id="q" name="q" type="search" placeholder="Name, city or society" value="<?php echo esc_attr( $q ); ?>" autocomplete="off"></label>
			<?php endif; ?>
			<button type="button" class="btn bs sm m-only pl-js-only" data-act="sheet" style="flex-grow:1" hidden>Filters (<span class="pl-fcount"><?php echo (int) $fcount; ?></span>)</button>
			<?php if ( count( $views ) > 1 ) : ?>
			<div role="group" aria-label="View" class="tabs">
				<?php foreach ( array( 'grid' => array( '▦ Grid', 'Grid view' ), 'list' => array( '☰ List', 'List view' ) ) as $k => $v ) : ?>
					<?php if ( in_array( $k, $views, true ) ) : ?>
					<button type="submit" name="view" value="<?php echo esc_attr( $k ); ?>" aria-pressed="<?php echo $view === $k ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( $v[1] ); ?>" class="tab<?php echo $view === $k ? ' on' : ''; ?>" data-view="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v[0] ); ?></button>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
		<div class="frow" id="pl-frow">
			<?php echo $filter_fields( '' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the closure. ?>
			<button type="submit" class="btn bs sm pl-nojs">Show events</button>
			<span id="count" aria-live="polite" style="font-weight:800;font-size:16px;min-height:48px;display:flex;align-items:center;white-space:nowrap"><?php echo esc_html( PL_Template::plural( count( $shown ), 'event' ) ); ?></span>
		</div>
		<div class="row" id="chips" style="gap:8px">
			<?php foreach ( $chips as $k => $label ) : ?>
				<a class="chip" href="<?php echo esc_url( remove_query_arg( $k ) ); ?>" data-unchip="<?php echo esc_attr( $k ); ?>" aria-label="<?php echo esc_attr( 'Remove filter ' . $label ); ?>"><?php echo esc_html( $label ); ?> <span class="x" aria-hidden="true">×</span></a>
			<?php endforeach; ?>
			<?php if ( $chips ) : ?><a class="btn bg sm" href="<?php echo esc_url( add_query_arg( array( 'tab' => $tab, 'view' => $view ), $self ) ); ?>" data-act="clear">Clear all</a><?php endif; ?>
		</div>
	</div>
</form>
<?php if ( $hasLive ) : ?>
<section class="ink" id="liveBand" style="padding:28px 0"><div class="wrap" style="display:flex;flex-direction:column;gap:16px">
	<?php foreach ( $live as $e ) : ?>
	<div class="row" style="gap:24px"<?php echo PL_Template::status_attrs( $e ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><span class="lab" style="display:inline-flex;align-items:center;gap:8px;color:var(--liveoi)"><span class="ldot" aria-hidden="true"></span>Live now</span><div style="flex:1 1 400px;display:flex;flex-direction:column;gap:2px"><a href="<?php echo esc_url( $e['url'] ); ?>" style="font-size:22px;font-weight:700;text-decoration:none"><?php echo esc_html( $e['name'] ); ?></a><span class="mut" style="font-size:15px"><?php echo esc_html( implode( ' · ', array_filter( array( $e['range'], $e['place'], $e['venue'], $e['live_day'] ) ) ) ); ?></span></div><?php if ( $e['website'] ) : ?><a href="<?php echo esc_url( $e['website'] ); ?>" target="_blank" rel="noopener" class="btn bw">Congress website</a><?php endif; ?></div>
	<?php endforeach; ?>
</div></section>
<?php endif; ?>
<section style="padding:44px 0 96px;min-height:520px" aria-labelledby="pl-results-h"><div class="wrap"><h2 class="sr" id="pl-results-h">Results</h2><div class="results swap" id="results" data-view="<?php echo esc_attr( $view ); ?>">
	<div class="grid3 pl-view-grid"<?php echo 'grid' === $view ? '' : ' hidden'; ?>>
		<?php
		foreach ( $events as $e ) {
			echo PL_Template::render( 'card-event', array( 'e' => $e, 'hidden' => ! $match( $e ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		?>
	</div>
	<div class="pl-view-list"<?php echo 'list' === $view ? '' : ' hidden'; ?>>
		<?php
		foreach ( $events as $e ) {
			echo PL_Template::render( 'row-event', array( 'e' => $e, 'hidden' => ! $match( $e ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		?>
	</div>
	<?php
	$near = null;
	foreach ( $events as $e ) {
		if ( ! $F['specialty'] || in_array( $F['specialty'], (array) $e['specialties'], true ) ) {
			$near = $e;
			break;
		}
	}
	?>
	<div class="pl-empty pl-empty-filtered" style="display:flex;flex-direction:column;align-items:flex-start;gap:12px;padding:28px;border:1.5px dashed var(--cb);border-radius:10px;background:var(--surface)"<?php echo ( ! $shown && $chips ) ? '' : ' hidden'; ?>>
		<strong style="font-size:20px">No events match these filters.</strong>
		<span class="mut"><?php if ( $near ) : ?>Closest match: <a href="<?php echo esc_url( $near['url'] ); ?>"><?php echo esc_html( $near['name'] ); ?></a> · <?php echo esc_html( $near['range'] . ' · ' . $near['city'] ); ?>.<?php else : ?>Try a wider search.<?php endif; ?></span>
		<a class="btn bs" href="<?php echo esc_url( add_query_arg( array( 'tab' => $tab, 'view' => $view ), $self ) ); ?>" data-act="clear">Clear filters</a>
	</div>
	<div class="pl-empty pl-empty-tab" style="display:flex;flex-direction:column;align-items:flex-start;gap:12px;padding:28px;border:1.5px dashed var(--cb);border-radius:10px;background:var(--surface)"<?php echo ( ! $shown && ! $chips ) ? '' : ' hidden'; ?>>
		<strong style="font-size:20px" class="pl-empty-title"><?php echo 'upcoming' === $tab ? 'No upcoming congresses announced yet.' : 'Nothing here yet.'; ?></strong>
		<span class="mut">New dates appear here as soon as societies confirm them.</span>
		<a class="btn bs" href="<?php echo esc_url( add_query_arg( array( 'tab' => 'past', 'view' => $view ), $self ) ); ?>" data-tab="past">See past congresses</a>
	</div>
</div></div></section>
<div id="sheet" hidden><div class="backdrop" data-act="sheet-close"></div><div class="sheetp" role="dialog" aria-modal="true" aria-label="Filters"><div class="row" style="justify-content:space-between"><strong style="font-size:20px">Filters</strong><button type="button" class="btn ic" style="border:1.5px solid var(--cb);background:var(--surface)" aria-label="Close filters" data-act="sheet-close"><?php echo PL_Template::icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button></div><div class="pl-sheet-fields"></div><div class="row" style="margin-top:6px"><button type="button" class="btn bs" data-act="clear">Clear all</button><button type="button" class="btn bp" style="flex-grow:1" data-act="sheet-close" id="sheetGo">Show events</button></div></div></div>
</div>
