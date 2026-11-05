<?php
/**
 * [pl_offices layout="default|compact|split"]
 *  default = office cards (Contact) · compact = footer columns · split = About two-colour split.
 *
 * @var array $a
 * @var array $offices
 */
defined( 'ABSPATH' ) || exit;
if ( 'split' === $a['layout'] ) : ?>
<section class="split" aria-label="Our offices">
	<?php foreach ( $offices as $i => $o ) : ?>
	<div class="<?php echo $i ? 'sand r' : ''; ?>" style="<?php echo $i ? '' : 'display:flex;justify-content:flex-end'; ?>"><div style="max-width:520px;display:flex;flex-direction:column;gap:12px">
		<span class="lab mut"><?php echo esc_html( $o['role'] ); ?></span>
		<strong class="of-c"><?php echo PL_Template::flag( $o['country'], true ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $o['city'] ); ?></strong>
		<span class="mut" style="font-size:16px;line-height:1.5"><?php echo esc_html( $o['address'] ); ?></span>
		<div class="row" style="gap:10px;margin-top:8px">
			<?php foreach ( $o['phones'] as $p ) : ?><a href="<?php echo esc_url( $p['tel'], array( 'tel' ) ); ?>" class="btn bs sm"><?php echo esc_html( $p['number'] ); ?></a><?php endforeach; ?>
			<?php $wa = array_values( array_filter( $o['phones'], fn( $p ) => $p['wa'] ) ); ?>
			<?php if ( $wa ) : ?><a href="<?php echo esc_url( $wa[0]['wa'] ); ?>" target="_blank" rel="noopener" class="btn bs sm">WhatsApp</a><?php endif; ?>
			<?php foreach ( $o['emails'] as $m ) : ?><a href="<?php echo esc_url( 'mailto:' . $m ); ?>" class="btn bs sm"><?php echo esc_html( $m ); ?></a><?php endforeach; ?>
		</div>
	</div></div>
	<?php endforeach; ?>
</section>
<?php elseif ( 'compact' === $a['layout'] ) : ?>
<div class="pl-offices-compact" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:28px;font-size:15px;line-height:1.5;align-content:start">
	<?php foreach ( $offices as $o ) { echo PL_Template::render( 'office-block', array( 'o' => $o ) ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
<?php else : ?>
<div class="pl-offices" style="display:flex;flex-direction:column;gap:28px">
	<?php foreach ( $offices as $o ) : ?><div class="card" style="padding:24px"><?php echo PL_Template::render( 'office-block', array( 'o' => $o ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div><?php endforeach; ?>
	<?php if ( ! array_filter( $offices, fn( $o ) => $o['hours'] ) ) : ?><span class="lab mut">Opening hours: TBC — added by PL</span><?php endif; ?>
</div>
<?php endif; ?>
