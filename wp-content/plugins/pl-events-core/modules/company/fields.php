<?php
defined( 'ABSPATH' ) || exit;
return array(
	array( 'name' => 'hero_text', 'label' => 'Hero headline', 'type' => 'text', 'required' => true, 'default' => 'Your science.', 'width' => 50, 'json' => false, 'help' => 'First part, e.g. "Your science."' ),
	array( 'name' => 'hero_em', 'label' => 'Hero headline — emphasis', 'type' => 'text', 'default' => 'Our stage.', 'width' => 50, 'json' => false, 'help' => 'Second part, shown in italic, e.g. "Our stage."' ),
	array( 'name' => 'tagline', 'label' => 'Brand signature', 'type' => 'text', 'default' => 'Science in focus.', 'width' => 50 ),
	array( 'name' => 'founding_year', 'label' => 'Founding year', 'type' => 'number', 'width' => 50, 'help' => 'Empty until PL confirms (2011 or 2013). When filled, "{N}+ years" is worked out every year.' ),
	array(
		'name' => 'figures', 'label' => 'Company figures', 'type' => 'repeater', 'button' => 'Add a figure',
		'help' => 'As stated by PL, e.g. "500+" · "conferences". Always shown static and labelled "Company figures" — never animated.',
		'sub'  => array(
			array( 'name' => 'value', 'label' => 'Value', 'type' => 'text', 'required' => true ),
			array( 'name' => 'label', 'label' => 'Label', 'type' => 'text', 'required' => true ),
		),
	),
	array( 'name' => 'portfolio_file', 'label' => 'Portfolio PDF', 'type' => 'file', 'mime' => 'pdf', 'width' => 50, 'json' => false, 'help' => 'Upload the PDF. The size is shown next to the link; keep it under 5 MB.' ),
	array( 'name' => 'portfolio_link', 'label' => 'Portfolio link (if not uploaded)', 'type' => 'url', 'width' => 50, 'json' => false ),
	array( 'name' => 'showreel_video', 'label' => 'Hero showreel (MP4)', 'type' => 'file', 'mime' => 'mp4', 'width' => 33, 'json' => false, 'help' => PL_Image_Spec::fill( 'Muted MP4 (H.264), {{spec:hero_video.upload}}, {{spec:hero_video.duration}}, up to {{spec:hero_video.max_kb}}. Plays on computers only; phones show the poster.' ) ),
	array( 'name' => 'showreel_poster', 'label' => 'Showreel poster', 'type' => 'image', 'width' => 33, 'frame' => 'hero', 'json' => false, 'help' => 'Shown first and on mobile.' ),
	array( 'name' => 'showreel_stills', 'label' => 'Showreel stills', 'type' => 'gallery', 'width' => 33, 'frame' => 'hero', 'json' => false, 'help' => 'Used until there is a video.' ),
	array( 'name' => 'showreel_sample', 'label' => 'Showreel is Sample', 'type' => 'switch', 'default' => 1, 'admin_only' => true, 'json' => false ),
	array(
		'name' => 'social', 'label' => 'Social links', 'type' => 'repeater', 'button' => 'Add a link',
		'help' => 'Paste the link; the name is worked out from the address.',
		'sub'  => array( array( 'name' => 'url', 'label' => 'Link', 'type' => 'url', 'required' => true ) ),
	),
	array( 'name' => 'support_email', 'label' => 'Website support — email', 'type' => 'email', 'width' => 50, 'json' => false, 'help' => 'Who the staff write to when they need help with the website (PL Help › "تكلموا مين"). Empty = the head office’s first email.' ),
	array( 'name' => 'support_whatsapp', 'label' => 'Website support — WhatsApp', 'type' => 'phone', 'json' => false, 'help' => 'Empty = the head office’s WhatsApp number.' ),
	array( 'name' => 'wa_inquiry_message', 'label' => 'WhatsApp inquiry message', 'type' => 'text', 'default' => "Hello, I'd like to ask about {event} ({dates}, {city}).", 'width' => 50, 'json' => false, 'help' => 'The message a doctor\'s WhatsApp opens with, when they tap WhatsApp on an event. {event} = event name, {dates} = its dates, {city} = its city. Empty = this default.' ),
	array( 'name' => 'careers_email', 'label' => 'CVs go to', 'type' => 'email', 'width' => 50, 'json' => false, 'help' => 'The email that receives the Careers form (with the CV). Empty = the head office’s first email. Contact messages go to the email of the office the visitor picks (PL Website › Offices).' ),
	array( 'name' => 'show_sample_tags', 'label' => 'Show "Sample" tags', 'type' => 'switch', 'default' => 1, 'admin_only' => true, 'json' => false, 'help' => 'Shows a small "Sample" tag on dummy content while the site is being prepared.' ),
	array( 'name' => 'sample_left', 'label' => 'Sample items left', 'type' => 'message', 'admin_only' => true, 'message' => array( 'PL_Data', 'sample_message' ) ),
);
