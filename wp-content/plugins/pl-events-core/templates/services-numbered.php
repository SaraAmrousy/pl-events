<?php
/**
 * [pl_services layout="numbered"] — Home "Behind the lens." 4 groups.
 *
 * @var array $services
 */
defined( 'ABSPATH' ) || exit;
$url = apply_filters( 'pl_services_page_url', home_url( '/services/' ) );
?>
<div class="svc4" style="display:grid;grid-template-columns:repeat(<?php echo (int) min( 4, count( $services ) ); ?>,minmax(0,1fr));gap:20px;align-items:start">
	<?php foreach ( $services as $i => $s ) : ?>
		<a href="<?php echo esc_url( $url . '#svc-' . $i ); ?>" class="card pl-panel" style="text-decoration:none;overflow:hidden;<?php echo $i % 2 ? 'margin-top:40px' : ''; ?>">
			<?php echo $s['image'] ? PL_Template::img( $s['image'], $s['image_alt'], 'photo rack', 'aspect-ratio:4/3', '(max-width: 767px) 100vw, (max-width: 1023px) 50vw, 25vw' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="cb" style="gap:10px"><span class="out" style="font-size:56px;-webkit-text-stroke-width:1.5px"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span><strong style="font-size:19px;line-height:1.3"><?php echo esc_html( $s['group'] ); ?></strong><span class="mut" style="font-size:15px;line-height:1.5"><?php echo esc_html( $s['line'] ); ?></span><?php echo $s['sample'] ? PL_Template::sample_tag() : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		</a>
	<?php endforeach; ?>
</div>
