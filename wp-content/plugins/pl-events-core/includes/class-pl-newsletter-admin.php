<?php
/**
 * Settings › PL Newsletter (administrators only). Keys are write-only: once saved they are never printed
 * back (the field shows "saved" and stays empty; leaving it empty keeps the stored key).
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Newsletter_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_pl_nl_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_pl_nl_test', array( __CLASS__, 'test' ) );
	}

	public static function menu() {
		add_options_page( 'PL Newsletter', 'PL Newsletter', 'manage_options', 'pl-newsletter', array( __CLASS__, 'page' ) );
	}

	private static function back( $msg, $ok = true ) {
		set_transient( 'pl_nl_notice_' . get_current_user_id(), array( $msg, $ok ), 60 );
		wp_safe_redirect( admin_url( 'options-general.php?page=pl-newsletter' ) );
		exit;
	}

	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'pl_nl_save' );
		$in = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- each field is sanitized below.
		$s  = PL_Newsletter::settings();

		$d                = sanitize_key( $in['destination'] ?? '' );
		$s['destination'] = in_array( $d, array( '', 'mailchimp', 'zoho', 'both' ), true ) ? $d : '';

		$s['mailchimp']['list'] = sanitize_text_field( $in['mc_list'] ?? '' );
		$s['mailchimp']['dc']   = preg_replace( '/[^a-z0-9]/', '', strtolower( (string) ( $in['mc_dc'] ?? '' ) ) );
		if ( '' !== trim( (string) ( $in['mc_key'] ?? '' ) ) ) {
			$key                   = trim( (string) $in['mc_key'] );
			$s['mailchimp']['key'] = PL_Secret::seal( $key );
			if ( '' === $s['mailchimp']['dc'] && false !== strpos( $key, '-' ) ) {
				$s['mailchimp']['dc'] = substr( $key, strrpos( $key, '-' ) + 1 );
			}
		}
		if ( ! empty( $in['mc_forget'] ) ) {
			$s['mailchimp']['key'] = '';
		}

		$zdc                    = (string) ( $in['zo_dc'] ?? 'com' );
		$s['zoho']['dc']        = isset( PL_NL_Zoho::DCS[ $zdc ] ) ? $zdc : 'com';
		$s['zoho']['client_id'] = sanitize_text_field( $in['zo_client'] ?? '' );
		$s['zoho']['list']      = sanitize_text_field( $in['zo_list'] ?? '' );
		if ( '' !== trim( (string) ( $in['zo_secret'] ?? '' ) ) ) {
			$s['zoho']['secret'] = PL_Secret::seal( trim( (string) $in['zo_secret'] ) );
		}
		$msg = 'Saved.';
		$ok  = true;
		if ( '' !== trim( (string) ( $in['zo_grant'] ?? '' ) ) ) { // one-time code → refresh token
			$r = PL_NL_Zoho::exchange_grant( $s['zoho']['dc'], $s['zoho']['client_id'], PL_Secret::open( $s['zoho']['secret'] ), trim( (string) $in['zo_grant'] ) );
			if ( is_wp_error( $r ) ) {
				$msg = 'Saved, but: ' . $r->get_error_message();
				$ok  = false;
			} else {
				$s['zoho']['refresh'] = PL_Secret::seal( $r );
				$msg                  = 'Saved. Zoho is connected (the one-time code was exchanged and discarded).';
			}
		}
		if ( ! empty( $in['zo_forget'] ) ) {
			$s['zoho']['secret']  = '';
			$s['zoho']['refresh'] = '';
		}
		update_option( PL_Newsletter::OPT, $s, false ); // not autoloaded
		self::back( $msg, $ok );
	}

	public static function test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'pl_nl_test' );
		$pid = sanitize_key( $_POST['provider'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$ps  = PL_Newsletter::providers( $pid );
		if ( ! $ps ) {
			self::back( 'Unknown provider.', false );
		}
		$p = reset( $ps );
		$r = $p->test();
		self::back( is_wp_error( $r ) ? $p->name() . ': ' . $r->get_error_message() : $p->name() . ' connected — list: ' . $r, ! is_wp_error( $r ) );
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s      = PL_Newsletter::settings();
		$notice = get_transient( 'pl_nl_notice_' . get_current_user_id() );
		delete_transient( 'pl_nl_notice_' . get_current_user_id() );
		$saved  = fn( $v ) => '' !== (string) $v ? '<span style="color:#1a7f37;font-weight:600">saved ••••</span> — leave empty to keep it' : '<span style="color:#996800">not set</span>';
		$q      = get_posts( array( 'post_type' => PL_Newsletter::CPT, 'post_status' => 'private', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_pl_nl_status', 'meta_value' => 'queued', 'meta_compare' => 'LIKE' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		?>
<div class="wrap">
	<h1>PL Newsletter</h1>
	<p>The website does not send newsletters. It adds people who ask for updates to PL’s existing <strong>Zoho Campaigns</strong> or <strong>Mailchimp</strong> list, with <strong>double opt-in</strong> (they confirm by email first). Campaigns are written and sent from Zoho or Mailchimp.</p>
		<?php if ( PL_Newsletter::test_mode() ) : ?>
	<div class="notice notice-error inline"><p><strong>Staging:</strong> connect only <em>test</em> accounts and a test list here — never PL’s real accounts before the reveal. Every contact sent from staging is tagged <code>pl-staging-test</code>.</p></div>
		<?php endif; ?>
		<?php if ( $notice ) : ?>
	<div class="notice <?php echo $notice[1] ? 'notice-success' : 'notice-error'; ?> inline"><p><?php echo esc_html( $notice[0] ); ?></p></div>
		<?php endif; ?>
		<?php if ( $q ) : ?>
	<div class="notice notice-warning inline"><p><?php echo (int) count( $q ); ?> sign-up(s) are waiting to be sent (provider not set up or temporarily down). They are retried every hour — see PL Website › Newsletter sign-ups.</p></div>
		<?php endif; ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" autocomplete="off">
		<?php wp_nonce_field( 'pl_nl_save' ); ?>
		<input type="hidden" name="action" value="pl_nl_save">
		<h2>Destination</h2>
		<fieldset>
			<?php foreach ( array( '' => 'Not connected yet (sign-ups are saved and sent later)', 'zoho' => 'Zoho Campaigns', 'mailchimp' => 'Mailchimp', 'both' => 'Both' ) as $k => $l ) : ?>
			<label style="display:block;margin:6px 0"><input type="radio" name="destination" value="<?php echo esc_attr( $k ); ?>" <?php checked( $s['destination'], $k ); ?>> <?php echo esc_html( $l ); ?></label>
			<?php endforeach; ?>
			<p class="description" style="color:#996800"><strong>Both:</strong> each person is added to both accounts and counted twice. An unsubscribe in one account is <em>not</em> copied to the other. Pick one unless PL really runs both lists.</p>
		</fieldset>

		<h2>Zoho Campaigns</h2>
		<table class="form-table" role="presentation">
			<tr><th><label for="zo_dc">Data center</label></th><td><select id="zo_dc" name="zo_dc"><?php foreach ( PL_NL_Zoho::DCS as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['zoho']['dc'], $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select><p class="description">The address you log in at (zoho.com, zoho.eu, zoho.in…).</p></td></tr>
			<tr><th><label for="zo_client">Client ID</label></th><td><input id="zo_client" name="zo_client" type="text" class="regular-text" value="<?php echo esc_attr( $s['zoho']['client_id'] ); ?>"><p class="description">api-console.zoho.com › Self Client › Client Secret tab.</p></td></tr>
			<tr><th><label for="zo_secret">Client secret</label></th><td><input id="zo_secret" name="zo_secret" type="password" class="regular-text" value="" autocomplete="new-password"> <?php echo wp_kses_post( $saved( $s['zoho']['secret'] ) ); ?></td></tr>
			<tr><th><label for="zo_grant">One-time grant code</label></th><td><input id="zo_grant" name="zo_grant" type="password" class="regular-text" value="" autocomplete="off"> <?php echo '' !== $s['zoho']['refresh'] ? '<span style="color:#1a7f37;font-weight:600">connected ••••</span>' : '<span style="color:#996800">not connected</span>'; ?><p class="description">Self Client › Generate Code, scope <code>ZohoCampaigns.contact.ALL,ZohoCampaigns.campaign.READ</code>, 10 minutes. Paste and Save within those minutes; it is exchanged once and never stored.</p></td></tr>
			<tr><th><label for="zo_list">List key</label></th><td><input id="zo_list" name="zo_list" type="text" class="regular-text" value="<?php echo esc_attr( $s['zoho']['list'] ); ?>"><p class="description">Zoho Campaigns › Contacts › Manage Lists › the list › Setup › List key. Turn on double opt-in in that list’s settings.</p></td></tr>
			<tr><th>Forget</th><td><label><input type="checkbox" name="zo_forget" value="1"> Remove the stored Zoho secret and connection</label></td></tr>
		</table>

		<h2>Mailchimp</h2>
		<table class="form-table" role="presentation">
			<tr><th><label for="mc_key">API key</label></th><td><input id="mc_key" name="mc_key" type="password" class="regular-text" value="" autocomplete="new-password"> <?php echo wp_kses_post( $saved( $s['mailchimp']['key'] ) ); ?><p class="description">Mailchimp › Profile › Extras › API keys › Create a key.</p></td></tr>
			<tr><th><label for="mc_list">Audience ID</label></th><td><input id="mc_list" name="mc_list" type="text" class="regular-text" value="<?php echo esc_attr( $s['mailchimp']['list'] ); ?>"><p class="description">Audience › Settings › Audience name and defaults › Audience ID.</p></td></tr>
			<tr><th><label for="mc_dc">Data center</label></th><td><input id="mc_dc" name="mc_dc" type="text" class="small-text" value="<?php echo esc_attr( $s['mailchimp']['dc'] ); ?>" placeholder="us21"><p class="description">Filled from the key (the part after the dash, e.g. us21).</p></td></tr>
			<tr><th>Forget</th><td><label><input type="checkbox" name="mc_forget" value="1"> Remove the stored Mailchimp key</label></td></tr>
		</table>
		<?php submit_button( 'Save settings' ); ?>
	</form>

	<h2>Test the connection</h2>
	<p>Reads the list name only — adds nobody.</p>
		<?php foreach ( array( 'zoho' => 'Test Zoho Campaigns', 'mailchimp' => 'Test Mailchimp' ) as $pid => $label ) : ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:8px">
			<?php wp_nonce_field( 'pl_nl_test' ); ?>
		<input type="hidden" name="action" value="pl_nl_test"><input type="hidden" name="provider" value="<?php echo esc_attr( $pid ); ?>">
			<?php submit_button( $label, 'secondary', 'submit', false ); ?>
	</form>
		<?php endforeach; ?>
</div>
		<?php
	}
}
