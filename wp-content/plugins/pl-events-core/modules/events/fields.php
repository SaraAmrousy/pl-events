<?php
/**
 * Event form — Step 2.2 §2. Stable keys: never rename an entry's "name"; deprecate instead.
 * Add a field = add one entry here; it appears in wp-admin and in PL_Data / the JSON.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

return array(
	array( 'name' => 'type', 'label' => 'Type', 'type' => 'taxonomy', 'taxonomy' => PL_Taxonomies::EVENT_TYPE, 'required' => true, 'width' => 33, 'help' => 'e.g. Congress, Symposium, Wetlab, Webinar. Missing a type? Ask the site admin.' ),
	array( 'name' => 'specialties', 'label' => 'Specialty', 'type' => 'taxonomy', 'taxonomy' => PL_Taxonomies::SPECIALTY, 'multiple' => true, 'add' => true, 'required' => true, 'width' => 33, 'help' => 'Pick one or more. Type a new one with the + button if it is missing.' ),
	array( 'name' => 'series', 'label' => 'Series', 'type' => 'taxonomy', 'taxonomy' => PL_Taxonomies::SERIES, 'add' => true, 'width' => 33, 'help' => 'The congress family, e.g. ESOIRS, so all its years are grouped.' ),
	array( 'name' => 'edition', 'label' => 'Edition', 'type' => 'text', 'width' => 33, 'help' => 'e.g. "38th". Leave empty if unknown.' ),
	array( 'name' => 'start', 'label' => 'Start date', 'type' => 'date', 'required' => true, 'width' => 33 ),
	array( 'name' => 'end', 'label' => 'End date', 'type' => 'date', 'required' => true, 'width' => 33, 'help' => 'Same as start for one-day events.' ),
	array( 'name' => 'country', 'label' => 'Country', 'type' => 'country', 'width' => 33, 'help' => 'Required unless the Type is online.' ),
	array( 'name' => 'city', 'label' => 'City', 'type' => 'text', 'width' => 33, 'help' => 'In English, e.g. Alexandria. Required unless the Type is online.' ),
	array( 'name' => 'venue', 'label' => 'Venue', 'type' => 'text', 'width' => 33, 'help' => 'e.g. "Hilton Jeddah".' ),
	array( 'name' => 'website', 'label' => 'Congress website', 'type' => 'url', 'width' => 50, 'help' => 'Paste the full link.' ),
	array( 'name' => 'registration_url', 'label' => 'Registration link', 'type' => 'url', 'width' => 50, 'help' => 'Where delegates register.' ),
	array(
		'name'    => 'registration',
		'label'   => 'Registration',
		'type'    => 'select',
		'choices' => array( 'not_open' => 'Not open yet', 'open' => 'Open', 'closed' => 'Closed' ),
		'default' => 'not_open',
		'required' => true,
		'width'   => 50,
		'help'    => 'Choose "Open" only when the link works.',
	),
	array( 'name' => 'pin_home', 'label' => 'Pin to Home', 'type' => 'switch', 'default' => 0, 'width' => 50, 'help' => 'Turn on to show this event in the Home hero instead of the nearest one.' ),
	array(
		'name'   => 'more_links',
		'label'  => 'More links',
		'type'   => 'repeater',
		'button' => 'Add a link',
		'help'   => 'Anything else: programme, abstracts, hotel booking, online room. Shown on the event page.',
		'sub'    => array(
			array( 'name' => 'label', 'label' => 'Label', 'type' => 'text', 'required' => true ),
			array( 'name' => 'url', 'label' => 'Link', 'type' => 'url', 'required' => true ),
		),
	),
	array( 'name' => 'phones', 'label' => 'Phones', 'type' => 'phones', 'button' => 'Add a phone', 'help' => 'Add the Egyptian number and the local one for international events.' ),
	array( 'name' => 'email', 'label' => 'Email', 'type' => 'email', 'width' => 50, 'help' => 'Event contact, if different from the office.' ),
	array( 'name' => 'delegates', 'label' => 'Delegates', 'type' => 'number', 'width' => 50, 'help' => 'Only a real number; empty = nothing shown.' ),
	array( 'name' => 'description', 'label' => 'Short description', 'type' => 'textarea', 'soft_max' => 300, 'help' => 'One or two sentences.' ),
	array( 'name' => 'logo', 'label' => 'Logo', 'type' => 'image', 'width' => 50, 'frame' => 'logo', 'help' => 'Transparent PNG or SVG.' ),
	array( 'name' => 'poster', 'label' => 'Poster / photo', 'type' => 'image', 'width' => 50, 'frame' => 'poster', 'help' => 'Used on the event page and when the link is shared.' ),
	array( 'name' => 'photos', 'label' => 'Photos', 'type' => 'gallery', 'frame' => 'photo', 'help' => 'Optional, for past events.' ),
);
