<?php
/**
 * [pl_stats layout="line"] — "⑮ congresses. ⑦ cities. ⑤ countries. One name." (3.4b P5).
 * Numbers in blue pills; never a count-up.
 *
 * @var array $s stats
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="nm pl-stats-line" style="display:flex;flex-direction:column;gap:20px">
	<p class="h2 nmh focus-set"><span class="hl-num"><?php echo (int) $s['congresses']; ?></span> <?php echo 1 === $s['congresses'] ? 'congress' : 'congresses'; ?>. <span class="hl-num"><?php echo (int) $s['cities']; ?></span> <?php echo 1 === $s['cities'] ? 'city' : 'cities'; ?>. <span class="hl-num"><?php echo (int) $s['countries']; ?></span> <?php echo 1 === $s['countries'] ? 'country' : 'countries'; ?>. One <span class="hl-city em">name.</span></p>
	<?php if ( $s['since_long'] ) : ?><p class="mut" style="font-size:17px;max-width:56ch">Counted from every event on this site since <?php echo esc_html( $s['since_long'] ); ?>. The societies below put their name next to ours.</p><?php endif; ?>
</div>
