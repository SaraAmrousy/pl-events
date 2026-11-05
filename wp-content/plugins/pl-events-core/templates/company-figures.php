<?php
/**
 * [pl_company_figures layout="inline|big"] — static, always labelled "Company figures".
 *
 * @var array $a
 * @var array $figures [ { value, label } ]
 * @var array $stats
 */
defined( 'ABSPATH' ) || exit;
if ( 'big' === $a['layout'] ) : ?>
<div class="figs pl-figs-big" style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:32px;align-items:end">
	<div style="grid-column:span 4;display:flex;flex-direction:column;gap:12px" class="fg-l"><span class="lab mut">Company figures</span><h2 class="h2 m-title" style="font-size:40px">The longer <span class="em">record.</span></h2><p class="mut" style="font-size:15px">As stated by PL Events, including work before this site’s records begin in <?php echo esc_html( $stats['since'] ); ?>.</p></div>
	<div style="grid-column:span 8;display:grid;grid-template-columns:repeat(<?php echo (int) min( 4, count( $figures ) ); ?>,minmax(0,1fr));gap:24px" class="fg-r">
		<?php foreach ( $figures as $f ) : ?>
			<div style="display:flex;flex-direction:column;gap:6px;border-top:2px solid var(--ink);padding-top:16px"><span class="num" style="font-size:88px"><?php echo esc_html( $f['value'] ); ?></span><span style="font-size:17px;font-weight:600"><?php echo esc_html( $f['label'] ); ?></span></div>
		<?php endforeach; ?>
	</div>
</div>
<?php else : ?>
<div class="row figs-row" style="gap:36px"><span class="lab d-only" style="color:var(--oim)">Company figures</span>
	<?php foreach ( $figures as $f ) : ?>
		<span style="display:inline-flex;align-items:baseline;gap:8px"><span class="num" style="font-size:34px"><?php echo esc_html( $f['value'] ); ?></span><span style="font-size:14px;font-weight:600"><?php echo esc_html( $f['label'] ); ?></span></span>
	<?php endforeach; ?>
</div>
<?php endif; ?>
