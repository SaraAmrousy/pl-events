<?php
/**
 * Newsletter provider interface (Step 4.6). The site feeds PL's existing Zoho Campaigns and Mailchimp
 * accounts; it never becomes a mailing tool itself.
 *
 * Double opt-in happens ON THE SITE (one confirmation email, see PL_NL_Links). A provider is only called
 * after the person has confirmed, so subscribe() adds them as an active/confirmed contact.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

interface PL_NL_Provider {

	/** 'mailchimp' | 'zoho' */
	public function id();

	/** Human name for the admin. */
	public function name();

	/** True when every setting needed to call the API is present. */
	public function configured();

	/**
	 * Add a CONFIRMED contact (the site already did the double opt-in) with its tags.
	 *
	 * @param string $email   validated email.
	 * @param string $name    optional full name.
	 * @param array  $tags    slugs, e.g. specialty-ophthalmology, country-eg, series-deoss, source-footer.
	 * @param array  $consent date, page, text, form, confirmed.
	 * @return true|string|WP_Error  true = subscribed; a string = done with a note (e.g. the provider asked
	 *                               the person to re-confirm); WP_Error data 'retry' => true = temporary failure.
	 */
	public function subscribe( $email, $name, array $tags, array $consent );

	/** Unsubscribe from the list. Not on the list = success. @return true|WP_Error */
	public function unsubscribe( $email );

	/** @return string|WP_Error  list/audience name on success. */
	public function test();
}
