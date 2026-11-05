<?php
/**
 * [pl_services layout="zigzag"] — Services page: each photo beside its own group,
 * alternating sides (3.4b P9). Full text, never cut.
 *
 * @var array $services
 */
defined( 'ABSPATH' ) || exit;
foreach ( $services as $i => $s ) : ?>
<div class="svc pl-panel<?php echo $i % 2 ? ' svc-r' : ''; ?>" id="svc-<?php echo (int) $i; ?>">
	<?php if ( $s['image'] ) : ?><div class="ph grain" style="border-radius:12px;overflow:hidden"><img src="<?php echo esc_url( $s['image'] ); ?>" alt="<?php echo esc_attr( $s['image_alt'] ); ?>" class="photo rack" style="aspect-ratio:4/3" loading="lazy" decoding="async"></div><?php endif; ?>
	<div class="tx" style="display:flex;flex-direction:column;gap:14px">
		<span class="out" style="font-size:64px;-webkit-text-stroke-width:1.5px"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span>
		<h2 class="h3" style="font-size:34px"><?php echo esc_html( $s['group'] ); ?></h2>
		<p class="mut" style="font-size:18px"><?php echo esc_html( $s['line'] ); ?></p>
		<?php if ( $s['items'] ) : ?><ul><?php foreach ( $s['items'] as $x ) : ?><li><?php echo esc_html( $x ); ?></li><?php endforeach; ?></ul><?php endif; ?>
	</div>
</div>
<?php endforeach; ?>
