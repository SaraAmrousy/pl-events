<?php
/**
 * Event card (grid). Override: {theme}/pl-events/card-event.php
 *
 * @var array $e computed event (PL_Data::compute)
 * @var bool  $hidden render with the hidden attribute (filtered out server-side)
 * @var bool  $eager  first visible card: load its poster eagerly with high priority (LCP, 6.1 QA)
 */
defined( 'ABSPATH' ) || exit;
$hidden = ! empty( $hidden );
?>
<article class="card pl-card"<?php echo PL_Template::status_attrs( $e ) . PL_Template::filter_attrs( $e ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?><?php echo $hidden ? ' hidden' : ''; ?>>
	<div class="<?php echo 'past' === $e['status'] ? 'poster past' : 'poster'; ?>" aria-hidden="true">
		<?php echo $e['poster'] ? PL_Images::img( $e['poster'], 'poster', array( 'class' => 'pimg', 'sizes' => '(max-width: 767px) calc(100vw - 40px), (max-width: 1023px) 45vw, 360px', 'eager' => ! empty( $eager ) ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
		<?php if ( $e['logo'] ) : ?><span class="plogo"><?php echo PL_Images::img( $e['logo'], 'logo', array( 'sizes' => '96px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
		<?php if ( ! $e['poster'] ) : // typographic poster only when there is no real one (Thread 8) ?>
		<span class="pw<?php echo strlen( $e['poster_word'] ) > 9 ? ' long' : ''; ?>"><?php echo esc_html( $e['poster_word'] ); ?></span>
		<span class="pe"><?php echo esc_html( $e['edition_text'] ); ?><br><?php echo esc_html( $e['city'] ); ?></span>
		<?php endif; ?>
		<?php echo PL_Template::date_box( $e ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
	</div>
	<span class="badge <?php echo esc_attr( $e['badge'][1] ); ?>"><?php echo 'live' === $e['status'] ? '<span class="ldot" style="background:#fff" aria-hidden="true"></span>' : '<i aria-hidden="true"></i>'; ?><span class="bt"><?php echo esc_html( $e['badge'][0] ); ?></span></span>
	<div class="cb">
		<span class="lab mut"><?php echo esc_html( $e['spec_line'] ); ?><?php echo $e['sample'] ? ' ' . PL_Template::sample_tag() : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<h3 class="ct"><a href="<?php echo esc_url( $e['url'] ); ?>" style="text-decoration:none"><?php echo esc_html( $e['name'] ); ?></a></h3>
		<span class="cm"><?php echo PL_Template::flag( $e['country'], false, ' style="margin-top:3px"' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $e['place'] . ( $e['venue'] ? ' · ' . $e['venue'] : '' ) ); ?></span></span>
		<div class="ca"><?php echo PL_Template::actions( $e, 'card' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?></div>
	</div>
</article>
