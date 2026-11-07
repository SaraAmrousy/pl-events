<?php
/**
 * Newsletter provider interface (Step 4.6). The site feeds PL's existing Zoho Campaigns and/or Mailchimp
 * accounts; it never becomes a mailing tool itself.
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
	 * Add a contact with DOUBLE OPT-IN (the provider sends the confirmation email).
	 *
	 * @param string $email   validated email.
	 * @param string $name    optional full name.
	 * @param array  $tags    slugs, e.g. specialty-ophthalmology, country-eg, series-deoss, source-footer.
	 * @param array  $consent date, page, text, form.
	 * @return true|WP_Error  WP_Error data 'retry' => true for temporary failures (network, 5xx, 429, no token).
	 */
	public function subscribe( $email, $name, array $tags, array $consent );

	/** @return string|WP_Error  list/audience name on success. */
	public function test();
}
