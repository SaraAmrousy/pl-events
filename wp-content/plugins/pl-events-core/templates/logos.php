<?php
/**
 * [pl_logos type="society|sponsor|award"] — equal tiles; a logo when there is one, else the name.
 * Memberships/awards always link to their proof.
 *
 * @var array $a
 * @var array $items
 */
defined( 'ABSPATH' ) || exit;
$cols = (int) $a['columns'] ? (int) $a['columns'] : ( 'sponsor' === $a['type'] ? 8 : 5 );
?>
<div class="pl-tiles pl-logos-<?php echo esc_attr( $a['type'] ); ?>" style="display:grid;grid-template-columns:repeat(<?php echo (int) $cols; ?>,minmax(0,1fr));gap:<?php echo 'sponsor' === $a['type'] ? 10 : 12; ?>px">
	<?php
	foreach ( $items as $i ) :
		$inner = $i['logo'] ? PL_Images::img( $i['logo'], 'logo', array( 'alt' => $i['name'], 'sizes' => '240px' ) ) : esc_html( $i['name'] );
		$link  = '';
		if ( 'award' === $a['type'] || 'membership' === $a['type'] ) {
			$link = $i['proof_url'] ?? '';
		} elseif ( 'sponsor' === $a['type'] ) {
			$link = ! empty( $i['link_logo'] ) ? ( $i['url'] ?? '' ) : '';
		} else {
			$link = $i['url'] ?? '';
		}
		$style = 'sponsor' === $a['type'] ? 'min-height:56px;font-size:13px' : 'font-size:13px';
		if ( $link ) {
			printf( '<a class="tile" style="%s" href="%s" target="_blank" rel="noopener">%s</a>', esc_attr( $style ), esc_url( $link ), $inner ); // phpcs:ignore WordPress.Security.EscapeOutput
		} else {
			printf( '<div class="tile" style="%s">%s</div>', esc_attr( $style ), $inner ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	endforeach;
	?>
</div>
