<?php
defined( 'ABSPATH' ) || exit;
return array(
	array( 'name' => 'kind', 'label' => 'Kind', 'type' => 'select', 'choices' => array( 'membership' => 'Membership', 'award' => 'Award' ), 'default' => 'membership', 'width' => 33 ),
	array( 'name' => 'year', 'label' => 'Year', 'type' => 'number', 'width' => 33 ),
	array( 'name' => 'logo', 'label' => 'Logo', 'type' => 'image', 'width' => 33, 'frame' => 'logo' ),
	array( 'name' => 'proof_url', 'label' => 'Proof link', 'type' => 'url', 'required' => true, 'help' => 'A public page that proves it (e.g. the member directory). No proof, no badge — it is never faked.' ),
);
