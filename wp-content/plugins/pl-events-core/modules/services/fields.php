<?php
defined( 'ABSPATH' ) || exit;
return array(
	array( 'name' => 'line', 'label' => 'Short line', 'type' => 'textarea', 'required' => true, 'soft_max' => 90, 'help' => 'One sentence under the heading.' ),
	array( 'name' => 'image', 'label' => 'Photo', 'type' => 'image', 'help' => 'From PL’s own events. The photo’s "Alternative text" in the Media library describes it for screen readers.' ),
	array(
		'name' => 'items', 'label' => 'Services in this group', 'type' => 'repeater', 'button' => 'Add a service', 'required' => true, 'min' => 1,
		'help' => 'Full text is always shown — never cut.',
		'sub'  => array( array( 'name' => 'text', 'label' => 'Service', 'type' => 'text', 'required' => true ) ),
	),
);
