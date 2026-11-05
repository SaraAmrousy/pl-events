<?php
/**
 * [pl_careers] — open roles or the empty state. The CV form (Forminator) sits under it in Elementor.
 *
 * @var array $jobs
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="pl-careers" style="display:flex;flex-direction:column;gap:20px">
	<h2 class="h3">Open roles</h2>
	<?php if ( $jobs ) : ?>
		<?php foreach ( $jobs as $j ) : ?>
		<details class="erow pl-job">
			<summary style="display:flex;flex-wrap:wrap;gap:12px;align-items:baseline;cursor:pointer"><strong><?php echo esc_html( $j['title'] ); ?></strong><span class="mut"><?php echo esc_html( implode( ' · ', array_filter( array( $j['office'], $j['job_type'], $j['closing'] ? 'Closes ' . PL_Dates::range( $j['closing'], $j['closing'] ) : '' ) ) ) ); ?></span></summary>
			<div style="padding-top:12px;white-space:pre-line"><?php echo esc_html( $j['description'] ); ?></div>
		</details>
		<?php endforeach; ?>
	<?php else : ?>
		<div style="display:flex;flex-direction:column;align-items:flex-start;gap:10px;padding:28px;border:1.5px dashed var(--cb);border-radius:10px;background:var(--surface)"><strong style="font-size:20px">No open roles right now.</strong><span class="mut">Send your CV below and we’ll contact you when a role fits.</span></div>
	<?php endif; ?>
</div>
