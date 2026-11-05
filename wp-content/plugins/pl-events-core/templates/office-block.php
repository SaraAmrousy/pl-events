<?php
/**
 * One office: label, city, address, phones (tel + WhatsApp), emails, hours.
 *
 * @var array $o office
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="pl-office" style="display:flex;flex-direction:column;gap:6px">
	<span class="lab mut"><?php echo esc_html( $o['role'] ); ?></span>
	<strong style="font-size:20px;display:flex;align-items:center;gap:8px"><?php echo PL_Template::flag( $o['country'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $o['city'] . ', ' . $o['country_name'] ); ?></strong>
	<span class="mut" style="overflow-wrap:anywhere"><?php echo esc_html( $o['address'] ); ?></span>
	<?php foreach ( $o['phones'] as $p ) : ?>
		<a href="<?php echo esc_url( $p['tel'], array( 'tel' ) ); ?>" style="font-weight:600"><?php echo esc_html( ( $p['label'] && ! preg_match( '/phone|whatsapp/i', $p['label'] ) ? $p['label'] . ': ' : '' ) . $p['number'] ); ?></a>
		<?php if ( $p['wa'] ) : ?><a href="<?php echo esc_url( $p['wa'] ); ?>" target="_blank" rel="noopener" style="font-weight:600">WhatsApp <?php echo esc_html( $p['number'] ); ?></a><?php endif; ?>
	<?php endforeach; ?>
	<?php foreach ( $o['emails'] as $m ) : ?>
		<a href="<?php echo esc_url( 'mailto:' . $m ); ?>" style="font-weight:600;overflow-wrap:anywhere"><?php echo esc_html( $m ); ?></a>
	<?php endforeach; ?>
	<?php if ( $o['hours'] ) : ?>
		<span class="mut pl-hours"><?php echo esc_html( implode( ' · ', array_map( fn( $h ) => trim( $h['days'] . ' ' . $h['time'] ), $o['hours'] ) ) ); ?></span>
	<?php endif; ?>
	<?php if ( $o['maps_url'] ) : ?><a href="<?php echo esc_url( $o['maps_url'] ); ?>" target="_blank" rel="noopener" style="font-weight:600">Open in Google Maps →</a><?php endif; ?>
</div>
