<?php
/**
 * Data fingerprint by group (round 7): `wp eval-file tools/guide/fingerprint.php` → one line per group.
 * Groups: pl/Company options · PL posts + their meta (events, offices, jobs…) · Forminator forms + settings ·
 * newsletter sign-ups · all postmeta · termmeta + terms · users + usermeta (minus login sessions) · other options.
 * Timers, caches and transients are left out (they change by themselves).
 */
global $wpdb;
$skip = "option_name NOT LIKE '%transient%' AND option_name NOT IN ('cron','rewrite_rules') AND option_name NOT LIKE '_site_transient%'";
$g = array(
	'options: pl_* + Company'   => "SELECT option_name, option_value FROM {$wpdb->options} WHERE (option_name LIKE 'pl\\_%' OR option_name LIKE 'options\\_pl\\_%' OR option_name LIKE '\\_options\\_pl\\_%' OR option_name LIKE 'plt\\_%') AND $skip ORDER BY option_name",
	'PL content (posts)'        => "SELECT ID, post_type, post_status, post_title, post_name, menu_order FROM {$wpdb->posts} WHERE post_type IN ('event','pl_office','pl_job','pl_service','pl_society','pl_sponsor','pl_membership') ORDER BY ID",
	'PL content (meta)'         => "SELECT m.post_id, m.meta_key, m.meta_value FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE p.post_type IN ('event','pl_office','pl_job','pl_service','pl_society','pl_sponsor','pl_membership') ORDER BY m.meta_id",
	'Forminator forms+settings' => "SELECT p.ID, p.post_status, m.meta_key, m.meta_value FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID WHERE p.post_type LIKE 'forminator%' ORDER BY p.ID, m.meta_id",
	'Forminator entries'        => "SELECT COUNT(*) FROM {$wpdb->prefix}frmt_form_entry",
	'newsletter sign-ups'       => "SELECT p.ID, p.post_title, m.meta_key, m.meta_value FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID WHERE p.post_type = 'pl_nl_signup' ORDER BY p.ID, m.meta_id",
	'all posts'                 => "SELECT ID, post_type, post_status, post_title, post_name FROM {$wpdb->posts} ORDER BY ID",
	'all postmeta'              => "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_key NOT IN ('_edit_lock','_edit_last') ORDER BY meta_id",
	'terms + termmeta'          => "SELECT t.term_id, t.name, t.slug, tt.taxonomy, tm.meta_key, tm.meta_value FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id LEFT JOIN {$wpdb->termmeta} tm ON tm.term_id = t.term_id ORDER BY t.term_id, tm.meta_id",
	'users + usermeta'          => "SELECT u.ID, u.user_login, u.user_email, um.meta_key, um.meta_value FROM {$wpdb->users} u LEFT JOIN {$wpdb->usermeta} um ON um.user_id = u.ID AND um.meta_key NOT IN ('session_tokens') ORDER BY u.ID, um.umeta_id",
	'other options'             => "SELECT option_name, option_value FROM {$wpdb->options} WHERE $skip ORDER BY option_name",
);
foreach ( $g as $name => $sql ) {
	$rows = $wpdb->get_results( $sql, ARRAY_N );
	echo str_pad( $name, 27 ), substr( hash( 'sha256', wp_json_encode( $rows ) ), 0, 12 ), '  (', count( $rows ), " rows)\n";
}
