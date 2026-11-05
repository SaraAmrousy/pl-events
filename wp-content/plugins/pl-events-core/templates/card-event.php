<?php
/**
 * Event card (grid). Override: {theme}/pl-events/card-event.php
 *
 * @var array $e computed event (PL_Data::compute)
 * @var bool  $hidden render with the hidden attribute (filtered out server-side)
 */
defined( 'ABSPATH' ) || exit;
$hidden = ! empty( $hidden );
$actions = array();
if ( $e['can_register'] ) {
	$actions['register'] = '<a href="' . esc_url( $e['registration_url'] ) . '" class="btn bp sm pl-reg" target="_blank" rel="noopener">' . esc_html( $e['register_label'] ) . '</a>';
}
if ( $e['website'] ) {
	$actions['website'] = '<a href="' . esc_url( $e['website'] ) . '" class="btn bs sm" target="_blank" rel="noopener">Website</a>';
}
$actions['push'] = '<span class="push"></span>';
if ( 'past' !== $e['status'] ) {
	$actions['calendar'] = '<a class="ic-btn pl-cal" href="' . esc_url( $e['ics_url'] ) . '" aria-label="' . esc_attr( 'Add ' . $e['name'] . ' to your calendar' ) . '">' . PL_Template::icon( 'cal' ) . '</a>';
}
$actions['whatsapp'] = '<a class="ic-btn" href="' . esc_url( $e['wa_url'] ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( 'Share ' . $e['name'] . ' on WhatsApp' ) . '">' . PL_Template::icon( 'wa' ) . '</a>';
$actions = apply_filters( 'pl_event_actions', $actions, $e, 'card' );
?>
<article class="card pl-card"<?php echo PL_Template::status_attrs( $e ) . PL_Template::filter_attrs( $e ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?><?php echo $hidden ? ' hidden' : ''; ?>>
	<div class="<?php echo 'past' === $e['status'] ? 'poster past' : 'poster'; ?>" aria-hidden="true">
		<?php echo $e['poster'] ? PL_Images::img( $e['poster'], 'poster', array( 'class' => 'pimg', 'sizes' => '(max-width: 767px) 100vw, (max-width: 1023px) 50vw, 400px' ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
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
		<span class="cm" style="display:flex;gap:8px;align-items:flex-start"><?php echo PL_Template::flag( $e['country'], false, ' style="margin-top:3px"' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $e['range'] . ' · ' . $e['place'] ); ?><?php if ( $e['venue'] ) : ?><br><?php echo esc_html( $e['venue'] ); ?><?php endif; ?></span></span>
		<div class="ca"><?php echo implode( '', $actions ); // phpcs:ignore WordPress.Security.EscapeOutput -- built above with escaping. ?></div>
	</div>
</article>
