<?php
/**
 * Image sizes — ONE source (6.1b round 7). Used by:
 *  - PL_Images (frame minimums → the yellow "image is small" note),
 *  - the instructions under every image field (PL_Fields),
 *  - PL Help (the "image sizes" table, the hero / event-image tasks, the cheat-sheet line) through
 *    {{spec:…}} placeholders, and the guide's safe-zone drawings (tools/guide, via `wp pl image-spec`).
 *
 * Numbers measured on staging, 8 Nov 2026 (tools: /tmp scripts recorded in docs/Step-6.1b round 7):
 *  - Hero: the frame is 375×732 · 390×733 · 768×1024 · 1280×913 · 1440×914 · 1920×1080, object-fit cover
 *    from the centre. For a 16:9 image the part visible at EVERY size is the centre column, 28.8% of the
 *    width (x 35.6%–64.4%), full height. The headline sits over y 16–63% (phones) / 27–73% (desktop).
 *  - Event card (16:9): 296–355 px wide. A Poster is shown whole (contain); the status badge covers its
 *    top 13–16%, the date box its bottom-left 25–30% (height). Event page: 16:10.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Image_Spec {

	/** key => spec. Sizes in px (w, h); safe = fractions of the image (x0, y0, x1, y1). */
	public static function all() {
		$s = array(
			'hero'        => array(
				'label'   => 'Home hero photo (and showreel stills)',
				'ar'      => 'صورة الهوم (Hero)',
				'where'   => 'PL Website › Company › Showreel poster / Showreel stills',
				'frame'   => 'hero',
				'ratio'   => '16:9',
				'upload'  => array( 2400, 1350 ),
				'min'     => array( 1920, 1080 ),
				'formats' => 'JPG or WebP',
				'max_kb'  => 1024,
				'fit'     => 'cover',
				'shown'   => array( '375×732', '390×733', '768×1024', '1280×913', '1440×914', '1920×1080' ),
				'zones'   => array(
					'desktop' => array( 0.106, 0, 0.894, 1 ),    // visible at 1280×913 (the narrowest desktop)
					'mobile'  => array( 0.356, 0, 0.644, 1 ),    // visible at 375×732
					'safe'    => array( 0.37, 0.05, 0.63, 0.95 ), // faces / key detail: inside the mobile column, off the very edges
					'text'    => array( 0.11, 0.16, 0.89, 0.73 ), // where the headline + buttons sit (no text in the photo)
				),
				'note'    => 'The photo is the background behind the headline: no text in the photo. Keep faces and the key detail in the middle column; it is the only part phones show.',
			),
			'hero_video'  => array(
				'label'    => 'Home hero showreel (video)',
				'ar'       => 'فيديو الهوم (Showreel)',
				'where'    => 'PL Website › Company › Hero showreel (MP4) + Showreel poster',
				'frame'    => 'hero',
				'ratio'    => '16:9',
				'upload'   => array( 1920, 1080 ),
				'min'      => array( 1280, 720 ),
				'formats'  => 'MP4 (H.264), no sound',
				'max_kb'   => 2560,
				'duration' => '10–20 s, loops',
				'fit'      => 'cover',
				'zones'    => array(
					'desktop' => array( 0.106, 0, 0.894, 1 ),
					'mobile'  => array( 0.356, 0, 0.644, 1 ),
					'safe'    => array( 0.37, 0.05, 0.63, 0.95 ),
					'text'    => array( 0.11, 0.16, 0.89, 0.73 ),
				),
				'note'     => 'Plays on computers only (phones show the poster). Muted, no sound track. Poster = a still from the video, same size (1920 × 1080).',
			),
			'event_photo' => array(
				'label'   => 'Event image — Photo',
				'ar'      => 'صورة الإيفنت (Photo)',
				'where'   => 'PL Website › Events › the event › Poster / photo (+ Image type = Photo)',
				'frame'   => 'poster',
				'ratio'   => '16:10',
				'upload'  => array( 1600, 1000 ),
				'min'     => array( 1280, 800 ),
				'formats' => 'JPG or WebP',
				'max_kb'  => 2048,
				'fit'     => 'cover',
				'shown'   => array( 'card 16:9 (296–355 px wide)', 'event page 16:10 (up to 480 px)', 'list / editions thumbnail 16:9 (72–120 px)' ),
				'zones'   => array(
					'desktop' => array( 0, 0, 1, 1 ),
					'mobile'  => array( 0, 0.05, 1, 0.95 ),       // the 16:9 card crops 5% top and bottom of a 16:10 image
					'safe'    => array( 0.06, 0.08, 0.94, 0.92 ),
				),
				'note'    => 'Fills the frame: cropped from the centre. Keep the subject away from the edges.',
			),
			'event_poster' => array(
				'label'    => 'Event image — Poster',
				'ar'       => 'بوستر الإيفنت (Poster)',
				'where'    => 'PL Website › Events › the event › Poster / photo (+ Image type = Poster or Auto)',
				'frame'    => 'poster',
				'ratio'    => 'as designed (portrait, square…)',
				'upload'   => array( 1080, 1350 ),
				'min'      => array( 800, 1000 ),
				'formats'  => 'JPG, WebP or PNG — the original design file, not a screenshot',
				'max_kb'   => 2048,
				'fit'      => 'contain',
				'zones'    => array(
					'desktop' => array( 0, 0, 1, 1 ),
					'mobile'  => array( 0, 0, 1, 1 ),
					'safe'    => array( 0.08, 0.16, 0.92, 0.70 ), // card: badge covers the top 13–16%, the date the bottom-left 25–30%
				),
				'note'     => 'Shown whole on a blurred copy of itself: nothing is cut. On the card the status badge (top) and the date (bottom-left) sit on top of it, so put the congress name in the middle band.',
			),
			'logo'        => array(
				'label'   => 'Logos (event, society, sponsor, membership)',
				'ar'      => 'اللوجوهات',
				'where'   => 'the Logo field of the event / society / sponsor / membership',
				'frame'   => 'logo',
				'ratio'   => 'any',
				'upload'  => array( 800, 320 ),
				'min'     => array( 400, 160 ),
				'formats' => 'SVG, or PNG with a transparent background',
				'max_kb'  => 512,
				'fit'     => 'contain',
				'note'    => 'Shown whole inside a white tile, never cropped. Trim empty space around the logo before uploading.',
			),
			'gallery'     => array(
				'label'   => 'Event photos (gallery)',
				'ar'      => 'صور الإيفنت (Gallery)',
				'where'   => 'PL Website › Events › the event › Photos',
				'frame'   => 'photo',
				'ratio'   => '4:3',
				'upload'  => array( 1600, 1200 ),
				'min'     => array( 960, 720 ),
				'formats' => 'JPG or WebP',
				'max_kb'  => 2048,
				'fit'     => 'cover',
				'zones'   => array( 'safe' => array( 0.06, 0.06, 0.94, 0.94 ) ),
				'note'    => 'Equal 4:3 frames, cropped from the centre.',
			),
			'service'     => array(
				'label'   => 'Service photo',
				'ar'      => 'صورة الخدمة',
				'where'   => 'PL Website › Services › the group › Photo',
				'frame'   => 'photo',
				'ratio'   => '4:3',
				'upload'  => array( 1600, 1200 ),
				'min'     => array( 960, 720 ),
				'formats' => 'JPG or WebP',
				'max_kb'  => 2048,
				'fit'     => 'cover',
				'zones'   => array( 'safe' => array( 0.06, 0.06, 0.94, 0.94 ) ),
				'note'    => 'Cropped from the centre into a 4:3 frame.',
			),
			'wide'        => array(
				'label'   => 'Wide photo (About)',
				'ar'      => 'الصورة العريضة (About)',
				'where'   => 'the About page wide photo',
				'frame'   => 'wide',
				'ratio'   => '21:9 (4:3 on phones)',
				'upload'  => array( 2560, 1097 ),
				'min'     => array( 2048, 878 ),
				'formats' => 'JPG or WebP',
				'max_kb'  => 2048,
				'fit'     => 'cover',
				'zones'   => array(
					'desktop' => array( 0, 0, 1, 1 ),
					'mobile'  => array( 0.214, 0, 0.786, 1 ),     // 4:3 out of 21:9 = the middle 57%
					'safe'    => array( 0.23, 0.06, 0.77, 0.94 ),
				),
				'note'    => 'Phones show only the middle 57% of the width.',
			),
		);
		return apply_filters( 'pl_image_spec', $s );
	}

	public static function get( $key ) {
		return self::all()[ $key ] ?? null;
	}

	/** Minimum upload for a PL_Images frame (the yellow note). */
	public static function frame_min( $frame ) {
		$map = array( 'poster' => 'event_photo', 'poster10' => 'event_photo', 'photo' => 'gallery', 'wide' => 'wide', 'hero' => 'hero', 'logo' => 'logo' );
		$s   = self::get( $map[ $frame ] ?? '' );
		return $s ? $s['min'] : null;
	}

	/** "{{spec:hero.upload}}" → "2400 × 1350 px" (used by PL Help text). */
	public static function fill( $text ) {
		return preg_replace_callback(
			'/\{\{spec:([a-z_]+)\.([a-z_]+)\}\}/',
			static function ( $m ) {
				$s = self::get( $m[1] );
				if ( ! $s || ! isset( $s[ $m[2] ] ) ) {
					return $m[0];
				}
				$v = $s[ $m[2] ];
				if ( in_array( $m[2], array( 'upload', 'min' ), true ) ) {
					return $v[0] . ' × ' . $v[1] . ' px';
				}
				if ( 'max_kb' === $m[2] ) {
					return $v >= 1024 ? rtrim( rtrim( number_format( $v / 1024, 1 ), '0' ), '.' ) . ' MB' : $v . ' KB';
				}
				return is_array( $v ) ? implode( ' · ', $v ) : (string) $v;
			},
			(string) $text
		);
	}
}
