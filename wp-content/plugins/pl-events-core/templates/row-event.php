<?php
/**
 * Event row (list view). Override: {theme}/pl-events/row-event.php
 *
 * @var array $e
 * @var bool  $hidden
 */
defined( 'ABSPATH' ) || exit;
$hidden = ! empty( $hidden );
?>
<div class="erow r-ev pl-card"<?php echo PL_Template::status_attrs( $e ) . PL_Template::filter_attrs( $e ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo $hidden ? ' hidden' : ''; ?>>
	<?php echo PL_Template::thumb( $e ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
	<span class="r-dt" style="font-weight:800;font-size:16px;font-variant-numeric:tabular-nums"><?php echo esc_html( $e['range'] ); ?></span>
	<span class="r-nm" style="display:flex;flex-direction:column;gap:2px;min-width:0">
		<a href="<?php echo esc_url( $e['url'] ); ?>" style="font-weight:700;font-size:16px;line-height:1.3;text-decoration:none;overflow-wrap:anywhere"><?php echo esc_html( $e['name'] ); ?></a>
		<span class="mut" style="font-size:13px"><?php echo esc_html( $e['spec_line'] ); ?></span>
		<span class="mut m-only" style="font-size:13px"><?php echo esc_html( $e['place'] ); ?></span>
	</span>
	<span class="d-only r-pl" style="display:flex;gap:8px;align-items:center;font-size:14px;font-weight:600"><?php echo PL_Template::flag( $e['country'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $e['place'] ); ?></span>
	<span class="d-only r-bd"><span class="badge <?php echo esc_attr( $e['badge'][1] ); ?>"><i aria-hidden="true"></i><span class="bt"><?php echo esc_html( $e['badge'][0] ); ?></span></span></span>
	<span class="r-ac" style="display:flex;gap:8px;align-items:center;justify-content:flex-end">
		<?php if ( $e['can_register'] ) : ?><a href="<?php echo esc_url( $e['registration_url'] ); ?>" class="btn bp sm pl-reg" target="_blank" rel="noopener">Register</a><?php endif; ?>
		<?php if ( $e['website'] ) : ?><a href="<?php echo esc_url( $e['website'] ); ?>" class="btn bs sm d-only" target="_blank" rel="noopener">Website</a><?php endif; ?>
	</span>
</div>
