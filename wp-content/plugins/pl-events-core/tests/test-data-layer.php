<?php
/**
 * Run: wp eval-file tests/test-data-layer.php   (sandbox only; excluded from deploy zips)
 * Checks: PL_Data::all() matches data/pl-data.json; status in country-local time; date text; phones; .ics.
 */
$fail = 0;
$ok   = function ( $cond, $msg ) use ( &$fail ) { echo ( $cond ? "  ✓ " : "  ✗ " ) . $msg . "\n"; if ( ! $cond ) { $fail++; } };
$seed = json_decode( file_get_contents( PL_Importer::seed_file() ), true );
$all  = PL_Data::all();

echo "Shape\n";
foreach ( array( 'meta', 'company', 'offices', 'events', 'societies', 'sponsors', 'services', 'jobs', 'memberships', 'geo' ) as $k ) {
	$ok( array_key_exists( $k, $all ), "top-level key $k" );
}
$ok( count( $all['events'] ) === count( $seed['events'] ), 'event count ' . count( $all['events'] ) );
$byId = array_column( $all['events'], null, 'id' );
$keys = array( 'name', 'type', 'specialties', 'series', 'edition', 'start', 'end', 'country', 'city', 'venue', 'website', 'registration_url', 'registration', 'more_links', 'email', 'description', 'delegates', 'pin_home', 'show_on_site', 'sample' );
$mism = array();
foreach ( $seed['events'] as $s ) {
	$e = $byId[ $s['id'] ] ?? null;
	if ( ! $e ) { $mism[] = $s['id'] . ' missing'; continue; }
	foreach ( $keys as $k ) {
		if ( $e[ $k ] !== $s[ $k ] ) { $mism[] = $s['id'] . ".$k: " . wp_json_encode( $e[ $k ] ) . ' ≠ ' . wp_json_encode( $s[ $k ] ); }
	}
}
$ok( ! $mism, 'every event field equals the seed' . ( $mism ? ":\n      " . implode( "\n      ", array_slice( $mism, 0, 12 ) ) : '' ) );
$o = $all['offices'][0];
$ok( 'Alexandria' === $o['city'] && $o['head_office'] && 'events@plevents.org' === $o['emails'][0], 'head office Alexandria first' );
$ok( '+20 102 555 9003' === $o['phones'][0]['number'] && 'https://wa.me/201025559003' === $o['phones'][0]['wa'] && 'tel:+201025559003' === $o['phones'][0]['tel'], 'phone display/tel/wa: ' . $o['phones'][0]['number'] );
$d = $all['offices'][1];
$ok( '+971 55 158 2689' === $d['phones'][0]['number'] && 'dina@plevents.org' === $d['emails'][0], 'Dubai phone + email: ' . $d['phones'][0]['number'] );
$ok( count( $all['societies'] ) === count( $seed['societies'] ) && count( $all['sponsors'] ) === count( $seed['sponsors'] ), 'societies ' . count( $all['societies'] ) . ', sponsors ' . count( $all['sponsors'] ) );
$ok( array_column( $all['services'], 'group' ) === array_column( $seed['services'], 'group' ) && $all['services'][0]['items'] === $seed['services'][0]['items'] && $all['services'][0]['image'], 'services groups, items, photos' );
$ok( $all['company']['hero_headline'] === array( 'text' => 'Your science.', 'em' => 'Our stage.' ) && $all['company']['figures'] === $seed['company']['figures'], 'company headline + figures' );
$ok( array_column( $all['company']['social'], 'label' ) === array_column( $seed['company']['social'], 'label' ), 'social labels detected from URLs' );
foreach ( $seed['geo']['countries'] as $cc => $c ) {
	if ( isset( $all['geo']['countries'][ $cc ] ) ) { $ok( $all['geo']['countries'][ $cc ]['tz'] === $c['tz'], "tz $cc = {$c['tz']}" ); }
}
$ok( $all['geo']['cities']['New Alamein'] === array( 28.95, 30.83 ), 'New Alamein matched' );

echo "Status in the country's local time (D1)\n";
$at = function ( $iso ) { add_filter( 'pl_now', $f = fn() => new DateTimeImmutable( $iso ) ); return $f; };
$st = fn( $id ) => array_column( PL_Data::events(), 'status', 'id' )[ $id ];
// meom-2026 Dubai 30 Oct – 1 Nov 2026 (UTC+4)
$f = $at( '2026-10-29T19:59:00Z' ); $ok( 'upcoming' === $st( 'meom-2026' ), 'Dubai 29 Oct 23:59 local → upcoming' ); remove_filter( 'pl_now', $f );
$f = $at( '2026-10-29T20:00:00Z' ); $ok( 'live' === $st( 'meom-2026' ), 'Dubai 30 Oct 00:00 local → live (still 29 Oct in UTC)' );
$ev = array_column( PL_Data::events(), null, 'id' )['meom-2026'];
$ok( 'day 1 of 3' === $ev['live_day'] && 'b-live' === $ev['badge'][1], 'live day + badge: ' . $ev['live_day'] );
$ok( (bool) PL_Data::events( array( 'status' => 'live' ) ) && '' !== PL_Shortcodes::live_ribbon(), 'live ribbon renders while live' );
remove_filter( 'pl_now', $f );
$f = $at( '2026-11-01T20:00:00Z' ); $ok( 'past' === $st( 'meom-2026' ), 'Dubai 2 Nov 00:00 local → past' ); $ok( '' === PL_Shortcodes::live_ribbon(), 'no ribbon when nothing is live' ); remove_filter( 'pl_now', $f );
$f = $at( '2026-11-05T12:00:00Z' );
$up = PL_Data::events( array( 'status' => 'current' ) );
$ok( array_column( $up, 'id' ) === array( 'deoss-atmo-2027', 'mocat-6-2027' ), 'today: 2 upcoming (DEOSS ATMO, MOCAT 6)' );
$past = array_filter( PL_Data::events(), fn( $e ) => 'past' === $e['status'] );
$ok( ! array_filter( $past, fn( $e ) => $e['can_register'] ), 'no past event offers Register' );
$h = PL_Data::hero_event(); $ok( 'deoss-atmo-2027' === $h['id'], 'next congress = ' . $h['id'] . ' in ' . $h['days_to_start'] . ' days' );
remove_filter( 'pl_now', $f );

echo "Text\n";
$ok( '29–30 Jan 2027' === PL_Dates::range( '2027-01-29', '2027-01-30' ), 'range same month' );
$ok( '30 Oct – 1 Nov 2026' === PL_Dates::range( '2026-10-30', '2026-11-01' ), 'range across months' );
$ok( '5 Feb 2026' === PL_Dates::range( '2026-02-05', '2026-02-05' ), 'one day' );
$ok( '30 Dec 2026 – 2 Jan 2027' === PL_Dates::range( '2026-12-30', '2027-01-02' ), 'range across years' );
$b = array_column( PL_Data::events(), null, 'id' )['bridge-retina-2026'];
$ok( 'Register for BRC 2026' === $b['register_label'], 'series short name: ' . $b['register_label'] );
$ok( '+20 102 555 9003' === PL_Phones::from_row( array( 'code' => '+20', 'number' => '0102 555 9003' ) )['number'], 'leading 0 stripped' );

echo "Stats + shortcodes\n";
$s = PL_Data::stats(); $ok( 15 === $s['events'] && 7 === $s['cities'] && 5 === $s['countries'], "15 / 7 / 5" );
foreach ( PL_Shortcodes::TAGS as $t ) {
	$html = do_shortcode( "[$t]" );
	$ok( 'pl_live_ribbon' === $t || 'pl_careers' === $t || strlen( $html ) > 20, "[$t] renders (" . strlen( $html ) . ' bytes)' );
}
$ok( false === strpos( do_shortcode( '[pl_help]' ), '<' ), '[pl_help] does not exist' );
$ok( '5' === do_shortcode( '[pl_stats field="countries"]' ), '[pl_stats field="countries"] = 5' );
$ok( false !== strpos( do_shortcode( '[pl_upcoming country="IQ" limit="3"]' ), 'MOCAT' ) && false === strpos( do_shortcode( '[pl_upcoming country="IQ"]' ), 'ESOIRS' ), '[pl_upcoming country="IQ"] filters' );

echo $fail ? "\nFAILED: $fail\n" : "\nALL PASSED\n";
