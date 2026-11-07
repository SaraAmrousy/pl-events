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
		add_action( 'admin_post_pl_nl_hook', array( __CLASS__, 'hook' ) );
		add_action( 'admin_post_pl_nl_export', array( __CLASS__, 'export' ) );
		add_action( 'admin_post_pl_nl_admin_unsub', array( __CLASS__, 'admin_unsub' ) );
		add_filter( 'manage_' . PL_Newsletter::CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . PL_Newsletter::CPT . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_action( 'manage_posts_extra_tablenav', array( __CLASS__, 'export_button' ) );
	}

	private static function guard( $nonce ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.', 403 );
		}
		check_admin_referer( $nonce );
	}

	/** Connect the Mailchimp webhook by API, or make a new webhook secret. */
	public static function hook() {
		self::guard( 'pl_nl_hook' );
		$do = sanitize_key( $_POST['do'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		delete_transient( 'pl_nl_mc_hook' );
		if ( 'regenerate' === $do ) {
			PL_NL_Hooks::regenerate();
			self::back( 'New webhook address made. Connect Mailchimp again and paste the new Zoho address in Zoho — the old ones stop working now.' );
		}
		$ps = PL_Newsletter::providers( 'mailchimp' );
		$r  = reset( $ps )->connect_webhook( PL_NL_Hooks::url( 'mailchimp' ) );
		self::back( is_wp_error( $r ) ? 'Mailchimp webhook: ' . $r->get_error_message() : 'Mailchimp webhook connected (unsubscribes and cleaned addresses).', ! is_wp_error( $r ) );
	}

	/** CSV backup of every sign-up (administrators only). */
	public static function export() {
		self::guard( 'pl_nl_export' );
		$cell = static function ( $v ) {
			$v = is_array( $v ) ? implode( ' ', $v ) : (string) $v;
			return preg_match( '/^[=+\-@\t\r]/', $v ) ? "'" . $v : $v; // no formulas in Excel / Sheets
		};
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="pl-newsletter-signups-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- BOM so Excel reads UTF-8.
		fputcsv( $out, array( 'email', 'name', 'status', 'tags', 'form', 'page', 'consent_text', 'signed_up_utc', 'confirmed_utc', 'left_utc', 'left_via', 'zoho', 'mailchimp' ) );
		$page = 1;
		do {
			$ids = get_posts( array( 'post_type' => PL_Newsletter::CPT, 'post_status' => 'private', 'numberposts' => 500, 'paged' => $page++, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
			foreach ( $ids as $id ) {
				$c = (array) get_post_meta( $id, '_pl_nl_consent', true );
				$o = (array) get_post_meta( $id, '_pl_nl_out', true );
				fputcsv( $out, array_map( $cell, array( PL_Newsletter::email( $id ), get_post_meta( $id, '_pl_nl_name', true ), PL_Newsletter::status( $id ), (array) get_post_meta( $id, '_pl_nl_tags', true ), $c['form'] ?? '', $c['page'] ?? '', $c['text'] ?? '', $c['date'] ?? '', $c['confirmed'] ?? '', $o['date'] ?? '', $o['via'] ?? '', get_post_meta( $id, '_pl_nl_zoho', true ), get_post_meta( $id, '_pl_nl_mailchimp', true ) ) ) );
			}
		} while ( count( $ids ) === 500 );
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	public static function admin_unsub() {
		$id = (int) ( $_GET['id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification -- checked by guard().
		self::guard( 'pl_nl_unsub_' . $id );
		PL_Newsletter::opt_out( PL_Newsletter::email( $id ), 'admin' );
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . PL_Newsletter::CPT ) );
		exit;
	}

	public static function columns( $c ) {
		return array( 'cb' => $c['cb'] ?? '', 'title' => 'Email', 'pl_status' => 'Status', 'pl_tags' => 'Tags', 'pl_conf' => 'Confirmed (UTC)', 'date' => 'Signed up' );
	}

	public static function column( $col, $id ) {
		if ( 'pl_status' === $col ) {
			$o = (array) get_post_meta( $id, '_pl_nl_out', true );
			echo esc_html( PL_Newsletter::status( $id ) . ( ! empty( $o['via'] ) ? ' (via ' . $o['via'] . ')' : '' ) );
			foreach ( array( 'zoho' => 'Zoho', 'mailchimp' => 'Mailchimp' ) as $p => $n ) {
				$st = (string) get_post_meta( $id, '_pl_nl_' . $p, true );
				$er = (string) get_post_meta( $id, '_pl_nl_' . $p . '_error', true ) . (string) get_post_meta( $id, '_pl_nl_' . $p . '_note', true );
				if ( $st ) {
					echo '<br><small>' . esc_html( $n . ': ' . $st . ( $er ? ' — ' . $er : '' ) ) . '</small>';
				}
			}
		} elseif ( 'pl_tags' === $col ) {
			echo esc_html( implode( ', ', (array) get_post_meta( $id, '_pl_nl_tags', true ) ) );
		} elseif ( 'pl_conf' === $col ) {
			$c = (array) get_post_meta( $id, '_pl_nl_consent', true );
			echo esc_html( $c['confirmed'] ?? '—' );
		}
	}

	public static function row_actions( $a, $post ) {
		if ( PL_Newsletter::CPT !== $post->post_type ) {
			return $a;
		}
		$a = array_intersect_key( $a, array( 'trash' => 1, 'delete' => 1 ) );
		if ( ! in_array( PL_Newsletter::status( $post->ID ), array( PL_Newsletter::ST_UNSUB, PL_Newsletter::ST_BOUNCED ), true ) ) {
			$u          = wp_nonce_url( admin_url( 'admin-post.php?action=pl_nl_admin_unsub&id=' . $post->ID ), 'pl_nl_unsub_' . $post->ID );
			$a['pl_un'] = '<a href="' . esc_url( $u ) . '">Unsubscribe from all lists</a>';
		}
		return $a;
	}

	public static function export_button( $which ) {
		if ( 'top' === $which && PL_Newsletter::CPT === get_current_screen()->post_type && current_user_can( 'manage_options' ) ) {
			echo '<div class="alignleft actions"><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
			wp_nonce_field( 'pl_nl_export' );
			echo '<input type="hidden" name="action" value="pl_nl_export"><button class="button">Export CSV (backup)</button></form></div>';
		}
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
	<p>The website does not send newsletters. It sends <strong>one confirmation email</strong> (double opt-in), and once the person confirms it adds them to PL’s existing <strong>Zoho Campaigns</strong> and/or <strong>Mailchimp</strong> list with their tags. Campaigns are written and sent from Zoho or Mailchimp. Unsubscribes in either account are copied to the other through the webhooks below.</p>
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
			<p class="description"><strong>Both</strong> is the normal setting: PL sends campaigns from both accounts. The person gets <em>one</em> confirmation email from the website and is then added to both lists with the same tags.</p>
			<?php if ( 'both' === $s['destination'] && ( 'connected' !== PL_NL_Hooks::state( 'mailchimp' ) || 'connected' !== PL_NL_Hooks::state( 'zoho' ) ) ) : ?>
			<div class="notice notice-warning inline"><p><strong>Unsubscribes aren’t synced yet.</strong> Someone who unsubscribes in one account would still get campaigns from the other. Connect both webhooks below.</p></div>
			<?php endif; ?>
			<?php if ( get_option( 'pl_nl_zoho_doi' ) ) : ?>
			<div class="notice notice-warning inline"><p><strong>Zoho sent its own confirmation email</strong> (last on <?php echo esc_html( wp_date( 'j M Y, H:i', (int) get_option( 'pl_nl_zoho_doi' ) ) ); ?>): the Zoho list has its signup form on, so people get a second confirmation. Use a Zoho list with the signup form off for the website (see Step 4.6 §6).</p></div>
			<?php endif; ?>
		</fieldset>

		<h2>Zoho Campaigns</h2>
		<table class="form-table" role="presentation">
			<tr><th><label for="zo_dc">Data center</label></th><td><select id="zo_dc" name="zo_dc"><?php foreach ( PL_NL_Zoho::DCS as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['zoho']['dc'], $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select><p class="description">The address you log in at (zoho.com, zoho.eu, zoho.in…).</p></td></tr>
			<tr><th><label for="zo_client">Client ID</label></th><td><input id="zo_client" name="zo_client" type="text" class="regular-text" value="<?php echo esc_attr( $s['zoho']['client_id'] ); ?>"><p class="description">api-console.zoho.com › Self Client › Client Secret tab.</p></td></tr>
			<tr><th><label for="zo_secret">Client secret</label></th><td><input id="zo_secret" name="zo_secret" type="password" class="regular-text" value="" autocomplete="new-password"> <?php echo wp_kses_post( $saved( $s['zoho']['secret'] ) ); ?></td></tr>
			<tr><th><label for="zo_grant">One-time grant code</label></th><td><input id="zo_grant" name="zo_grant" type="password" class="regular-text" value="" autocomplete="off"> <?php echo '' !== $s['zoho']['refresh'] ? '<span style="color:#1a7f37;font-weight:600">connected ••••</span>' : '<span style="color:#996800">not connected</span>'; ?><p class="description">Self Client › Generate Code, scope <code>ZohoCampaigns.contact.ALL,ZohoCampaigns.campaign.READ</code>, 10 minutes. Paste and Save within those minutes; it is exchanged once and never stored.</p></td></tr>
			<tr><th><label for="zo_list">List key</label></th><td><input id="zo_list" name="zo_list" type="text" class="regular-text" value="<?php echo esc_attr( $s['zoho']['list'] ); ?>"><p class="description">Zoho Campaigns › Contacts › Manage Lists › the list › Setup › List key. The website already confirms every address, so use a list whose <strong>signup form is off</strong> — otherwise Zoho sends a second confirmation email.</p></td></tr>
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

	<h2>Unsubscribe sync (webhooks)</h2>
	<p>When someone unsubscribes (or their address bounces) in one account, the website is told and removes them from the other one too. The website’s own unsubscribe link removes them from both.</p>
	<table class="form-table" role="presentation">
		<?php foreach ( array( 'mailchimp' => 'Mailchimp', 'zoho' => 'Zoho Campaigns' ) as $pid => $label ) : $st = PL_NL_Hooks::state( $pid ); ?>
		<tr><th><?php echo esc_html( $label ); ?></th><td>
			<?php echo 'connected' === $st ? '<span style="color:#1a7f37;font-weight:600">Connected</span>' : '<span style="color:#996800;font-weight:600">Not set</span>'; ?>
			<?php if ( PL_NL_Hooks::seen( $pid ) ) : ?><span class="description"> · last call from <?php echo esc_html( $label ); ?>: <?php echo esc_html( wp_date( 'j M Y, H:i', PL_NL_Hooks::seen( $pid ) ) ); ?></span><?php endif; ?>
			<?php if ( 'mailchimp' === $pid ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:8px"><?php wp_nonce_field( 'pl_nl_hook' ); ?><input type="hidden" name="action" value="pl_nl_hook"><input type="hidden" name="do" value="connect"><?php submit_button( 'connected' === $st ? 'Check Mailchimp webhook' : 'Connect Mailchimp webhook', 'secondary', 'submit', false ); ?></form>
			<p class="description">Adds the webhook to the audience by API (events: unsubscribes + cleaned; sources: the person and admins).</p>
			<?php else : ?>
			<p><input type="text" class="large-text code" readonly value="<?php echo esc_attr( PL_NL_Hooks::url( 'zoho' ) ); ?>" onclick="this.select()" aria-label="Zoho webhook URL"></p>
			<p class="description">Zoho Campaigns › Settings › Developer Space › Webhooks › <strong>Contacts</strong> › Create: paste this URL, method POST, events <strong>Unsubscribe</strong> and <strong>Bounce</strong>, for the website’s list. It shows “Connected” after Zoho’s first call (unsubscribe a test contact once). Keep this URL private: it contains the webhook secret.</p>
			<?php endif; ?>
		</td></tr>
		<?php endforeach; ?>
	</table>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'pl_nl_hook' ); ?><input type="hidden" name="action" value="pl_nl_hook"><input type="hidden" name="do" value="regenerate"><?php submit_button( 'Make a new webhook secret', 'secondary small', 'submit', false ); ?> <span class="description">Only if the Zoho URL was shared by mistake.</span></form>

	<h2>Backup</h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'pl_nl_export' ); ?><input type="hidden" name="action" value="pl_nl_export"><?php submit_button( 'Export all sign-ups (CSV)', 'secondary', 'submit', false ); ?></form>
	<p class="description">Email, name, status, tags, consent (page, text, sign-up and confirmation times), unsubscribes. Keep the file private — it holds personal data.</p>
</div>
		<?php
	}
}
