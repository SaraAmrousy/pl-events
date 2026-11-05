<?php
defined( 'ABSPATH' ) || exit;
return array(
	array( 'name' => 'office', 'label' => 'Office', 'type' => 'text', 'width' => 33, 'help' => 'City of the office, or "Remote".' ),
	array( 'name' => 'job_type', 'label' => 'Job type', 'type' => 'taxonomy', 'taxonomy' => PL_Taxonomies::JOB_TYPE, 'width' => 33, 'help' => 'Missing a type? Ask the site admin.' ),
	array( 'name' => 'closing', 'label' => 'Closing date', 'type' => 'date', 'width' => 33, 'help' => 'The role hides itself after this date.' ),
	array( 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => true, 'soft_max' => 600 ),
);
