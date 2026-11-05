<?php
defined( 'ABSPATH' ) || exit;
return array(
	array( 'name' => 'logo', 'label' => 'Logo', 'type' => 'image', 'width' => 33, 'frame' => 'logo', 'help' => 'Required for launch. Until then the name is shown as a text tile.' ),
	array( 'name' => 'url', 'label' => 'Website', 'type' => 'url', 'width' => 33 ),
	array( 'name' => 'link_logo', 'label' => 'Link the logo', 'type' => 'switch', 'default' => 0, 'width' => 33, 'help' => 'On = the logo opens the sponsor website.' ),
);
