<?php
/**
 * Seed check (round 7): the sandbox the guide is recorded on must hold the seed data and the defaults,
 * not leftovers of a test. `wp eval-file tools/guide/seed-check.php` → "SEED OK" or the differences.
 */
$seed = json_decode( (string) file_get_contents( PL_EVENTS_DIR . 'seed/pl-data.json' ), true );
$diff = array();
$d    = static fn( $s ) => preg_replace( '/\D/', '', (string) $s );
foreach ( $seed['offices'] as $o ) {
	$live = null;
	foreach ( PL_Data::offices( true ) as $x ) {
		if ( $x['id'] === $o['id'] ) {
			$live = $x;
		}
	}
	if ( ! $live ) {
		$diff[] = "office {$o['id']}: missing";
		continue;
	}
	if ( array_values( $live['emails'] ) !== array_values( $o['emails'] ) ) {
		$diff[] = "office {$o['id']} emails: " . implode( ',', $live['emails'] ) . ' ≠ seed ' . implode( ',', $o['emails'] );
	}
	$lp = array_map( fn( $p ) => substr( $d( $p['number'] ), -9 ), $live['phones'] );
	$sp = array_map( fn( $p ) => substr( $d( is_array( $p ) ? $p['number'] : $p ), -9 ), $o['phones'] );
	if ( $lp !== $sp ) {
		$diff[] = "office {$o['id']} phones: " . implode( ',', $lp ) . ' ≠ seed ' . implode( ',', $sp );
	}
	if ( ! empty( $live['hours'] ) && empty( $o['hours'] ) ) {
		$diff[] = "office {$o['id']}: opening hours not in the seed";
	}
}
$events = array();
foreach ( PL_Data::events( array( 'include_hidden' => true ) ) as $e ) {
	$events[ $e['id'] ] = $e;
}
foreach ( $seed['events'] as $s ) {
	$e = $events[ $s['id'] ] ?? null;
	if ( ! $e ) {
		$diff[] = "event {$s['id']}: missing";
		continue;
	}
	foreach ( array( 'start', 'end', 'registration', 'registration_url', 'website', 'city', 'country', 'venue' ) as $k ) {
		if ( isset( $s[ $k ] ) && (string) $s[ $k ] !== (string) $e[ $k ] ) {
			$diff[] = "event {$s['id']} $k: " . $e[ $k ] . ' ≠ seed ' . $s[ $k ];
		}
	}
	if ( ! empty( $e['wa_inquiry'] ) ) {
		$diff[] = "event {$s['id']}: WhatsApp for inquiries set (" . $e['wa_inquiry'] . ')';
	}
	if ( empty( $e['show_on_site'] ) ) {
		$diff[] = "event {$s['id']}: hidden";
	}
}
foreach ( array( 'options_pl_careers_email', 'options_pl_support_email', 'options_pl_support_whatsapp_number', 'options_pl_wa_inquiry_message', 'pl_page_texts' ) as $opt ) {
	$v = get_option( $opt, '' );
	if ( '' !== $v && array() !== $v && "Hello, I'd like to ask about {event} ({dates}, {city})." !== $v ) {
		$diff[] = "option $opt is set: " . ( is_array( $v ) ? wp_json_encode( $v ) : $v );
	}
}
foreach ( array( 'pl_job' => 'jobs', 'pl_nl_signup' => 'newsletter sign-ups', 'pl_membership' => 'memberships' ) as $type => $label ) {
	$n = count( get_posts( array( 'post_type' => $type, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) );
	$want = count( (array) ( $seed[ array( 'pl_job' => 'jobs', 'pl_nl_signup' => '-', 'pl_membership' => 'memberships' )[ $type ] ] ?? array() ) );
	if ( $n !== $want ) {
		$diff[] = "$label: $n (seed $want)";
	}
}
if ( function_exists( 'plt_form_definitions' ) && class_exists( 'Forminator_Form_Model' ) ) {
	$ids = (array) get_option( 'plt_forms', array() );
	foreach ( plt_form_definitions() as $k => $def ) {
		$m = ! empty( $ids[ $k ] ) ? Forminator_Form_Model::model()->load( (int) $ids[ $k ] ) : null;
		if ( $m && wp_json_encode( $m->notifications ) !== wp_json_encode( $def['notifications'] ) ) {
			$diff[] = "form $k: email settings differ from Offices/Company";
		}
	}
}
foreach ( get_users() as $u ) {
	if ( ! in_array( $u->user_login, array( 'admin' ), true ) ) {
		$diff[] = "extra user {$u->user_login}";
	}
}
echo $diff ? "SEED DIFF\n- " . implode( "\n- ", $diff ) . "\n" : "SEED OK\n";
