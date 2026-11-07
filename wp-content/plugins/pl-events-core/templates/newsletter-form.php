<?php
/**
 * [pl_newsletter] — newsletter sign-up (Step 4.6). Works without JS (admin-post → redirect with ?pl_nl=),
 * JS adds inline states. Double opt-in: the success message tells people to confirm by email.
 * Override: {theme}/pl-events/newsletter-form.php
 *
 * @var array $a context (footer|event), series, specialties (comma list), country (ISO2), title
 */
defined( 'ABSPATH' ) || exit;
$ctx    = 'event' === $a['context'] ? 'event' : 'footer';
$uid    = 'pl-nl-' . $ctx;
$status = isset( $_GET['pl_nl'] ) ? sanitize_key( wp_unslash( $_GET['pl_nl'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- display only.
$title  = $a['title'] ? $a['title'] : ( 'event' === $ctx && $a['series'] ? 'Get updates on ' . $a['series'] : 'Get congress updates' );
$specs  = array_filter( array_map( 'trim', explode( ',', (string) $a['specialties'] ) ) );
$priv   = get_privacy_policy_url();
$msgs   = array(
	'ok'      => 'Almost done — check your inbox and confirm your email.',
	'invalid' => 'That email address doesn’t look complete. Please check it and try again.',
	'limited' => 'Too many tries from this connection. Please try again in an hour.',
	'error'   => 'Something went wrong on our side. Please try again.',
);
$all_specs = array();
$all_cc    = array();
if ( 'footer' === $ctx ) {
	foreach ( PL_Data::events() as $e ) {
		foreach ( (array) $e['specialties'] as $sp ) {
			$all_specs[ $sp ] = $sp;
		}
		if ( $e['country'] ) {
			$all_cc[ $e['country'] ] = $e['country_name'];
		}
	}
	ksort( $all_specs );
	asort( $all_cc );
}
?>
<form class="pl-nl pl-nl-<?php echo esc_attr( $ctx ); ?>" id="pl-nl" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pl="newsletter" data-rest="<?php echo esc_url( rest_url( 'pl/v1/subscribe' ) ); ?>" aria-labelledby="<?php echo esc_attr( $uid ); ?>-h" novalidate>
	<p class="pl-nl-h" id="<?php echo esc_attr( $uid ); ?>-h"><?php echo esc_html( $title ); ?></p>
	<input type="hidden" name="action" value="pl_subscribe">
	<input type="hidden" name="form" value="<?php echo esc_attr( $ctx ); ?>">
	<input type="hidden" name="page" value="">
	<?php if ( 'event' === $ctx ) : ?>
		<?php if ( $a['series'] ) : ?><input type="hidden" name="series[]" value="<?php echo esc_attr( sanitize_title( $a['series'] ) ); ?>"><?php endif; ?>
		<?php foreach ( $specs as $sp ) : ?><input type="hidden" name="specialty[]" value="<?php echo esc_attr( sanitize_title( $sp ) ); ?>"><?php endforeach; ?>
		<?php if ( $a['country'] ) : ?><input type="hidden" name="country[]" value="<?php echo esc_attr( strtolower( $a['country'] ) ); ?>"><?php endif; ?>
	<?php endif; ?>
	<div class="pl-nl-row">
		<label class="fld"><span class="fl">Email</span><input class="inp" type="email" name="email" required autocomplete="email" inputmode="email" aria-describedby="<?php echo esc_attr( $uid ); ?>-msg <?php echo esc_attr( $uid ); ?>-p"></label>
		<button type="submit" class="btn bp">Subscribe</button>
	</div>
	<?php if ( 'footer' === $ctx && ( $all_specs || $all_cc ) ) : ?>
	<details class="pl-nl-topics">
		<summary>Choose topics (optional)</summary>
		<fieldset><legend class="fl">Specialties</legend>
			<?php foreach ( $all_specs as $sp ) : ?><label class="pl-nl-chk"><input type="checkbox" name="specialty[]" value="<?php echo esc_attr( sanitize_title( $sp ) ); ?>"> <?php echo esc_html( $sp ); ?></label><?php endforeach; ?>
		</fieldset>
		<label class="fld"><span class="fl">Country (optional)</span><select class="sel" name="country[]"><option value="">Any country</option><?php foreach ( $all_cc as $cc => $cn ) : ?><option value="<?php echo esc_attr( strtolower( $cc ) ); ?>"><?php echo esc_html( $cn ); ?></option><?php endforeach; ?></select></label>
	</details>
	<?php endif; ?>
	<div class="pl-nl-hp" aria-hidden="true"><label>Leave this empty<input type="text" name="pl_hp" tabindex="-1" autocomplete="off"></label></div>
	<p class="pl-nl-msg" id="<?php echo esc_attr( $uid ); ?>-msg" role="status" aria-live="polite"<?php echo isset( $msgs[ $status ] ) ? ' data-state="' . esc_attr( $status ) . '"' : ' hidden'; ?>><?php echo isset( $msgs[ $status ] ) ? esc_html( $msgs[ $status ] ) : ''; ?></p>
	<p class="pl-nl-p mut" id="<?php echo esc_attr( $uid ); ?>-p"><?php echo esc_html( PL_Newsletter::CONSENT ); ?><?php if ( $priv ) : ?> <a href="<?php echo esc_url( $priv ); ?>">Privacy policy</a>.<?php endif; ?></p>
</form>
