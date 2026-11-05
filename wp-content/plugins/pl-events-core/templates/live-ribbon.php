<?php
/**
 * [pl_live_ribbon] — in the header while an event is live.
 *
 * @var array $live live events
 */
defined( 'ABSPATH' ) || exit;
$e = $live[0];
?>
<div class="ribbon"<?php echo PL_Template::status_attrs( $e ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><div class="wrap in">
	<span class="lab" style="display:inline-flex;align-items:center;gap:8px;color:var(--live)"><span class="ldot" aria-hidden="true"></span>Live now</span>
	<span style="font-weight:600"><?php echo esc_html( $e['name'] ); ?> · <?php echo PL_Template::flag( $e['country'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $e['city'] . ' · ' . $e['live_day'] ); ?><?php echo count( $live ) > 1 ? esc_html( ' · +' . ( count( $live ) - 1 ) . ' more' ) : ''; ?></span>
	<?php if ( $e['website'] ) : ?>
		<a href="<?php echo esc_url( $e['website'] ); ?>" target="_blank" rel="noopener" style="margin-left:auto;font-weight:700;color:var(--blue)">Congress website →</a>
	<?php else : ?>
		<a href="<?php echo esc_url( $e['url'] ); ?>" style="margin-left:auto;font-weight:700;color:var(--blue)">Details →</a>
	<?php endif; ?>
</div></div>
