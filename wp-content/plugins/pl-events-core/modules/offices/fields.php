<?php
defined( 'ABSPATH' ) || exit;
return array(
	array( 'name' => 'head_office', 'label' => 'Head office', 'type' => 'switch', 'default' => 0, 'width' => 33, 'json' => 'head_office', 'help' => 'The head office is where the map arcs start.' ),
	array( 'name' => 'role', 'label' => 'Label', 'type' => 'text', 'width' => 33, 'help' => 'e.g. Head office, Regional office.' ),
	array( 'name' => 'country', 'label' => 'Country', 'type' => 'country', 'required' => true, 'width' => 33 ),
	array( 'name' => 'city', 'label' => 'City', 'type' => 'text', 'required' => true, 'width' => 33 ),
	array( 'name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'required' => true, 'width' => 66 ),
	array( 'name' => 'phones', 'label' => 'Phones', 'type' => 'phones', 'min' => 1, 'required' => true, 'button' => 'Add a phone' ),
	array(
		'name' => 'emails', 'label' => 'Emails', 'type' => 'repeater', 'min' => 1, 'required' => true, 'button' => 'Add an email',
		'sub'  => array(
			array( 'name' => 'label', 'label' => 'Label (optional)', 'type' => 'text' ),
			array( 'name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true ),
		),
	),
	array( 'name' => 'maps_url', 'label' => 'Google Maps link', 'type' => 'url' ),
	array(
		'name' => 'hours', 'label' => 'Opening hours', 'type' => 'repeater', 'button' => 'Add a line', 'help' => 'e.g. "Sun–Thu" · "9:00–17:00". Empty = not shown.',
		'sub'  => array(
			array( 'name' => 'days', 'label' => 'Days', 'type' => 'text' ),
			array( 'name' => 'time', 'label' => 'Hours', 'type' => 'text' ),
		),
	),
	array( 'name' => 'covers', 'label' => 'Countries this office covers', 'type' => 'countries', 'help' => 'Used to pre-set the office on the contact form ("Plan yours here").' ),
);
