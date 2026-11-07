<?php
/**
 * Demo data for the guide recordings (sandbox only; run.sh restores the database afterwards).
 * Everything here is obviously test data: example.com addresses, "Test" names. No real person,
 * no real event data is invented — the events are the real seed.
 *   wp eval-file tools/guide/demo-data.php
 */
if ( 'local' !== wp_get_environment_type() ) {
	echo "sandbox only\n";
	return;
}
// Newsletter sign-ups: 3 confirmed, 1 waiting for the confirmation click.
$now = time();
$people = array(
	array( 'dr.test.one@example.com', 'Test Doctor One', array( 'specialty-ophthalmology', 'country-eg', 'source-footer' ), true, 9 ),
	array( 'test.two@example.com', 'Test Two', array( 'series-deoss', 'specialty-ophthalmology', 'country-eg', 'source-event' ), true, 6 ),
	array( 'test.three@example.com', '', array( 'source-contact' ), true, 3 ),
	array( 'test.four@example.com', '', array( 'specialty-vascular', 'source-footer' ), false, 1 ),
);
foreach ( $people as $p ) {
	$id = wp_insert_post( array( 'post_type' => PL_Newsletter::CPT, 'post_status' => 'private', 'post_title' => $p[0], 'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $now - $p[4] * DAY_IN_SECONDS ), 'post_date' => wp_date( 'Y-m-d H:i:s', $now - $p[4] * DAY_IN_SECONDS ) ) );
	update_post_meta( $id, '_pl_nl_name', $p[1] );
	update_post_meta( $id, '_pl_nl_tags', $p[2] );
	$c = array( 'date' => gmdate( 'c', $now - $p[4] * DAY_IN_SECONDS ), 'page' => home_url( '/' ), 'text' => PL_Newsletter::CONSENT, 'form' => str_replace( 'source-', '', end( $p[2] ) ) );
	if ( $p[3] ) {
		$c['confirmed'] = gmdate( 'c', $now - $p[4] * DAY_IN_SECONDS + 600 );
		update_post_meta( $id, '_pl_nl_status', PL_Newsletter::ST_DONE );
		update_post_meta( $id, '_pl_nl_zoho', 'sent' );
		update_post_meta( $id, '_pl_nl_mailchimp', 'sent' );
	} else {
		update_post_meta( $id, '_pl_nl_status', PL_Newsletter::ST_AWAIT );
	}
	update_post_meta( $id, '_pl_nl_consent', $c );
}
echo "newsletter: 4 test sign-ups\n";

// A small test image (600×340) in Media, to show the yellow "image is small" note.
$up  = wp_upload_dir();
$f   = $up['path'] . '/guide-small-test-image.png';
$im  = imagecreatetruecolor( 600, 340 );
imagefill( $im, 0, 0, imagecolorallocate( $im, 44, 72, 148 ) );
imagestring( $im, 5, 200, 160, 'TEST IMAGE 600 x 340', imagecolorallocate( $im, 255, 255, 255 ) );
imagepng( $im, $f );
$att = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => 'Test image (small)', 'post_status' => 'inherit' ), $f );
require_once ABSPATH . 'wp-admin/includes/image.php';
wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $f ) );
update_option( 'pl_guide_small_image', $att, false );
echo "media: small test image #$att\n";

// Quiet third-party nags so the pictures show our screens, not ads.
update_option( 'rank_math_wizard_completed', true );
delete_option( 'rank_math_redirect_about' );
update_option( 'wp_mail_smtp_activation_prevent_redirect', true );
update_option( 'elementor_onboarded', true );
update_option( 'elementor_tracker_notice', '1' );
foreach ( get_users( array( 'role' => 'administrator' ) ) as $u ) {
	update_user_meta( $u->ID, 'elementor_introduction', array( 'globals_introduction' => true, 'e_editor_one' => true, 'ai-get-started-announcement' => true, 'yoast-introduction' => true ) );
	update_user_meta( $u->ID, 'show_welcome_panel', 0 );
	update_user_meta( $u->ID, 'admin_color', 'fresh' );
}
echo "quiet notices: ok\n";
