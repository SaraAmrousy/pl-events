<?php
/**
 * Dotted map SVG: pins + arcs from the head office, framed on the points (Step 3.4b).
 *
 * @var array  $events computed events
 * @var float  $aspect
 * @var string $cls    extra class
 */
defined( 'ABSPATH' ) || exit;
$aspect = isset( $aspect ) ? (float) $aspect : 2.1;
$origin = PL_Data::origin();
$hq     = $origin['lonlat'];
$pts    = PL_Map::points( $events );
$has_hq = false;
foreach ( $pts as $p ) {
	$has_hq = $has_hq || $p['city'] === $origin['city'];
}
if ( ! $has_hq ) {
	$pts[] = array( 'city' => $origin['city'], 'cc' => $origin['country'], 'll' => $hq );
}
$vb   = PL_Map::view_box( array_merge( array_column( $pts, 'll' ), array( $hq ) ), 300, $aspect );
$hqp  = PL_Map::proj( $hq );
$offices = PL_Data::offices();
$office_city = array();
foreach ( $offices as $o ) {
	$office_city[ $o['city'] ] = $o['role'];
}
$cities = array_column( $pts, 'city' );
?>
<div class="map grain <?php echo esc_attr( $cls ?? '' ); ?>"><svg viewBox="<?php echo esc_attr( $vb ); ?>" data-full="<?php echo esc_attr( $vb ); ?>" data-aspect="<?php echo esc_attr( (string) $aspect ); ?>" role="img" aria-label="<?php echo esc_attr( 'Map of PL Events congresses: ' . implode( ', ', $cities ) ); ?>">
	<image href="<?php echo esc_url( PL_Map::dots_url() ); ?>" x="0" y="0" width="<?php echo esc_attr( (string) PL_Map::W ); ?>" height="<?php echo esc_attr( (string) PL_Map::H ); ?>"/>
	<?php
	foreach ( $pts as $p ) {
		$xy = PL_Map::proj( $p['ll'] );
		if ( $p['city'] === $origin['city'] || hypot( $xy[0] - $hqp[0], $xy[1] - $hqp[1] ) <= 18 ) {
			continue;
		}
		printf( '<path class="arc" data-city="%s" data-cc="%s" d="%s"/>', esc_attr( $p['city'] ), esc_attr( $p['cc'] ), esc_attr( PL_Map::arc( $hq, $p['ll'] ) ) );
	}
	foreach ( $pts as $p ) {
		list( $x, $y ) = PL_Map::proj( $p['ll'] );
		$l   = PL_Map::label( $p['city'] );
		$isq = $p['city'] === $origin['city'];
		$sub = $office_city[ $p['city'] ] ?? PL_Geo::name( $p['cc'] );
		printf(
			'<g class="pin" data-city="%1$s" data-cc="%2$s"><circle class="ring" cx="%3$.1f" cy="%4$.1f" r="4" fill="none" stroke="#FAFAF7" stroke-width="1" opacity="0"/><circle cx="%3$.1f" cy="%4$.1f" r="%5$d" fill="%6$s" stroke="#0A0D18" stroke-width="1.2"/><text class="ml" x="%7$.1f" y="%8$.1f" text-anchor="%9$s" fill="#FAFAF7" font-family="Montserrat,sans-serif" font-weight="700" font-size="10">%10$s</text><text class="ms" x="%7$.1f" y="%11$.1f" text-anchor="%9$s" fill="#B9BDC9" font-family="Montserrat,sans-serif" font-weight="600" font-size="7.5">%12$s</text></g>',
			esc_attr( $p['city'] ),
			esc_attr( $p['cc'] ),
			$x,
			$y,
			$isq ? 5 : 4,
			$isq ? '#FAFAF7' : '#9DB2F2',
			$x + $l[0],
			$y + $l[1],
			esc_attr( $l[2] ),
			esc_html( $p['city'] ),
			$y + $l[1] + 10,
			esc_html( $sub )
		);
	}
	?>
</svg></div>
