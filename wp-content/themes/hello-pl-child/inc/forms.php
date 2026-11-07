<?php
/**
 * Forms (Step 4.5) — Forminator Free: Contact (topic + office, pre-set from the link the visitor
 * came from, 2.1 P4) and Careers (CV upload). Created/updated by `plt_provision_forms()`; the
 * pages show them through [pl_form name="contact|careers"] (IDs kept in the option plt_forms).
 *
 * Routing: the Contact form's notification uses Forminator's own routing — office field = X
 * → that office's first email (from PL Website › Offices). Careers → the head office.
 * STAGING SAFETY: while the option `plt_mail_override` holds an address, every form email goes
 * there instead, with the address it would have gone to written at the top. Delete that option
 * at launch (Step 6.3) and the real routing takes over — nothing else changes.
 *
 * Labels and options mirror the prototype (views.js formHtml).
 *
 * @package hello-pl-child
 */

defined( 'ABSPATH' ) || exit;

function plt_form_topics() {
	return array(
		'proposal'     => 'Plan a congress (proposal)',
		'sponsor'      => 'Sponsorship or exhibition',
		'registration' => 'Help with a registration',
		'careers'      => 'Careers',
		'other'        => 'Something else',
	);
}

function plt_form_offices() {
	$out = array();
	foreach ( class_exists( 'PL_Data' ) ? PL_Data::offices() : array() as $o ) {
		$out[ $o['id'] ] = array( 'label' => $o['city'] . ' — ' . strtolower( $o['role'] ), 'email' => $o['emails'][0] ?? '' );
	}
	return $out;
}

/* ------------------------------------------------------------ field builders */

function plt_ff_row( $id, array $fields ) {
	return array( 'wrapper_id' => 'wrapper-' . $id, 'fields' => $fields );
}

function plt_ff( $element_id, $type, $label, array $extra = array() ) {
	return array_merge( array( 'element_id' => $element_id, 'type' => $type, 'cols' => '12', 'field_label' => $label, 'required' => false ), $extra );
}

function plt_ff_options( array $map ) {
	$o = array();
	foreach ( $map as $value => $label ) {
		$o[] = array( 'label' => $label, 'value' => $value, 'key' => $value );
	}
	return $o;
}

function plt_form_definitions() {
	$common = array(
		plt_ff_row( 'name', array( plt_ff( 'name-1', 'name', 'Full name', array( 'cols' => '6', 'required' => 'true', 'placeholder' => '', 'required_message' => 'Please tell us your name.' ) ), plt_ff( 'email-1', 'email', 'Email', array( 'cols' => '6', 'required' => 'true', 'validation' => true, 'required_message' => 'We need an email address to reply.', 'validation_message' => 'This email address looks incomplete — check it, please.' ) ) ) ),
	);
	$phone = plt_ff( 'phone-1', 'phone', 'Phone or WhatsApp (optional)', array( 'cols' => '6', 'validation' => 'none', 'phone_validation_type' => 'standard' ) );
	$offices = plt_form_offices();

	$contact = array_merge(
		$common,
		array(
			plt_ff_row( 'topic', array(
				$phone,
				plt_ff( 'select-1', 'select', 'Topic', array( 'cols' => '6', 'value_type' => 'single', 'options' => plt_ff_options( plt_form_topics() ), 'prefill' => 'topic', 'default_value' => 'proposal' ) ),
			) ),
			plt_ff_row( 'office', array(
				plt_ff( 'select-2', 'select', 'Office', array( 'cols' => '6', 'value_type' => 'single', 'options' => plt_ff_options( array_map( static fn( $o ) => $o['label'], $offices ) ), 'prefill' => 'office', 'description' => 'Pre-set from the page you came from; change it if you like.' ) ),
			) ),
			plt_ff_row( 'msg', array( plt_ff( 'textarea-1', 'textarea', 'Message', array( 'input_type' => 'paragraph', 'limit' => '', 'required' => 'true', 'required_message' => 'Please write a short message.' ) ) ) ),
			// Step 4.6: optional, NOT ticked by default; only a ticked box adds the person (double opt-in) via PL_Newsletter.
			plt_ff_row( 'updates', array( plt_ff( 'checkbox-1', 'checkbox', 'Updates (optional)', array( 'value_type' => 'checkbox', 'layout' => 'list', 'options' => array( array( 'label' => 'Send me updates about upcoming PL congresses', 'value' => 'updates', 'key' => 'updates', 'default' => false ) ), 'description' => 'You’ll confirm by email first, and can unsubscribe at any time.' ) ) ) ),
		)
	);

	$careers = array_merge(
		$common,
		array(
			plt_ff_row( 'role', array( $phone, plt_ff( 'text-1', 'text', 'Role you’re interested in', array( 'cols' => '6', 'input_type' => 'line' ) ) ) ),
			plt_ff_row( 'cv', array( plt_ff( 'upload-1', 'upload', 'CV (PDF or Word, up to 5 MB)', array( 'required' => 'true', 'required_message' => 'Please attach your CV.', 'file-type' => 'single', 'custom-files' => 'true', 'filetypes' => array( 'pdf', 'doc', 'docx' ), 'upload-limit' => 5, 'filesize' => 'MB' ) ) ) ),
			plt_ff_row( 'msg', array( plt_ff( 'textarea-1', 'textarea', 'A few lines about you', array( 'input_type' => 'paragraph', 'limit' => '' ) ) ) ),
		)
	);

	$settings = static fn( $submit, $thanks ) => array(
		'formName'             => '',
		'form-type'            => 'default',
		'form-style'           => 'none', // the theme styles the form (prototype .form)
		'fields-style'         => 'open',
		'submission-behaviour' => 'behaviour-thankyou',
		'thankyou-message'     => $thanks,
		'submitData'           => array( 'custom-submit-text' => $submit, 'custom-invalid-form-message' => 'Some fields need attention — see the notes under them.' ),
		'enable-ajax'          => 'true',
		'validation-inline'    => true,
		'honeypot'             => 'true',
		'use-load-ajax'        => 'false',
		'store_submissions'    => '1',
		'form-expire'          => 'no_expire',
	);

	$head = '';
	foreach ( class_exists( 'PL_Data' ) ? PL_Data::offices() : array() as $o ) {
		if ( ! empty( $o['head_office'] ) ) {
			$head = $o['emails'][0] ?? '';
		}
	}
	$routing = array();
	foreach ( $offices as $id => $o ) {
		if ( $o['email'] ) {
			$routing[] = array( 'element_id' => 'select-2', 'rule' => 'is', 'value' => $id, 'email' => $o['email'] );
		}
	}
	return array(
		'contact' => array(
			'name'          => 'PL — Contact',
			'wrappers'      => $contact,
			'settings'      => $settings( 'Send message', 'Thank you — your message is with our team. We usually reply within one working day.' ),
			'notifications' => array( array(
				'slug'             => 'notification-pl-contact',
				'label'            => 'To the chosen office',
				'email-recipients' => 'routing',
				'routing'          => $routing,
				'recipients'       => $head,
				'email-subject'    => 'Website: {select-1} — {name-1}',
				'email-editor'     => 'New message from the PL Events website:<br/><br/>{all_fields}<br/>---<br/>Reply to this email to answer {name-1} directly.',
				'replyto-email'    => '{email-1}',
				'email-attachment' => 'true',
				'type'             => 'default',
			) ),
		),
		'careers' => array(
			'name'          => 'PL — Careers (CV)',
			'wrappers'      => $careers,
			'settings'      => $settings( 'Send my CV', 'Thank you — your CV has reached our team. We’ll contact you when a role fits.' ),
			'notifications' => array( array(
				'slug'             => 'notification-pl-careers',
				'label'            => 'To the head office',
				'email-recipients' => 'default',
				'recipients'       => $head,
				'email-subject'    => 'Website CV: {name-1} — {text-1}',
				'email-editor'     => 'A CV was sent through the PL Events website:<br/><br/>{all_fields}',
				'replyto-email'    => '{email-1}',
				'email-attachment' => 'true',
				'type'             => 'default',
			) ),
		),
	);
}

/**
 * Create or update both forms. Existing forms are updated only with $force (so edits made in
 * Forminator are kept); routing is always refreshed from the Offices data.
 */
function plt_provision_forms( $force = false ) {
	if ( ! class_exists( 'Forminator_API' ) || ! class_exists( 'Forminator_Form_Model' ) ) {
		return array( 'Forminator is not active — forms skipped.' );
	}
	$log = array();
	$ids = (array) get_option( 'plt_forms', array() );
	foreach ( plt_form_definitions() as $key => $def ) {
		$id     = (int) ( $ids[ $key ] ?? 0 );
		$exists = $id && Forminator_API::get_form( $id ) && ! is_wp_error( Forminator_API::get_form( $id ) );
		if ( ! $exists ) {
			$id = Forminator_API::add_form( $def['name'], $def['wrappers'], array_merge( $def['settings'], array( 'formName' => $def['name'] ) ) );
			if ( is_wp_error( $id ) ) {
				$log[] = "form $key: ERROR " . $id->get_error_message();
				continue;
			}
			$log[] = "form $key: created (#$id)";
		} elseif ( $force ) {
			Forminator_API::update_form( $id, $def['wrappers'], array_merge( $def['settings'], array( 'formName' => $def['name'] ) ) );
			$log[] = "form $key: updated (#$id)";
		} else {
			$log[] = "form $key: exists (#$id) — fields left as is";
		}
		$model = Forminator_Form_Model::model()->load( $id );
		if ( $model ) {
			$model->notifications = $def['notifications'];
			$model->save();
			$log[] = "form $key: email " . ( 'routing' === $def['notifications'][0]['email-recipients'] ? 'routed by office (' . implode( ', ', wp_list_pluck( $def['notifications'][0]['routing'], 'email' ) ) . ')' : 'to ' . $def['notifications'][0]['recipients'] );
		}
		$ids[ $key ] = $id;
	}
	update_option( 'plt_forms', $ids, false );
	$o = get_option( 'plt_mail_override' );
	$log[] = $o ? "STAGING: all form emails go to $o (option plt_mail_override)" : 'form emails go to the real office addresses';
	return $log;
}

/* ------------------------------------------------------------- staging safety */

/* Forminator's filter is "forminator_{module_slug}_get_admin_email_recipients" with module_slug
   "form" (checked in 1.58.4). Both spellings are hooked in case a version renames it; the staging
   mu-plugin also replaces recipients in wp_mail itself as a last safety net. */
$plt_override = static function ( $emails ) {
	$o = get_option( 'plt_mail_override' );
	if ( $o && is_email( $o ) ) {
		$GLOBALS['plt_mail_intended'] = $emails;
		return array( $o );
	}
	return $emails;
};
add_filter( 'forminator_form_get_admin_email_recipients', $plt_override, 99 );
add_filter( 'forminator_custom_form_get_admin_email_recipients', $plt_override, 99 );

add_filter( 'forminator_custom_form_mail_admin_message', static function ( $message ) {
	if ( ! empty( $GLOBALS['plt_mail_intended'] ) ) {
		$message = '<p style="background:#FFE3A3;padding:8px 12px"><strong>STAGING</strong> — on the live site this goes to: ' . esc_html( implode( ', ', (array) $GLOBALS['plt_mail_intended'] ) ) . '</p>' . $message;
	}
	return $message;
}, 99 );

add_filter( 'forminator_custom_form_mail_admin_subject', static function ( $subject ) {
	return get_option( 'plt_mail_override' ) ? '[PL staging] ' . $subject : $subject;
}, 99 );
