<?php
/**
 * [pl_hero] — Home hero (3.4b P4, P8, P11): showreel (video or stills) inside the iris
 * aperture, headline from Company settings, company figures + "Next congress" strip.
 * One shortcode because the iris needs one nested DOM (#hero > .media > #iris > #irisIn);
 * Elementor still places it. Without JS: full-bleed poster, everything visible.
 *
 * @var array $a       attributes
 * @var array $company PL_Data::company()
 * @var array $stats
 * @var array|null $next hero event
 */
defined( 'ABSPATH' ) || exit;
$sr     = $company['showreel'];
$stills = array_values( array_filter( array_merge( $sr['poster'] ? array( $sr['poster'] ) : array(), (array) $sr['stills'] ) ) );
$stills = array_values( array_unique( $stills ) );
$offs   = array_map( fn( $o ) => $o['city'], PL_Data::offices() );
$mark   = apply_filters( 'pl_brand_mark', '', 'ink' );
$hl     = $company['hero_headline'];
$words  = fn( $s ) => implode( ' ', array_map( fn( $w ) => '<span class="w">' . esc_html( $w ) . '</span>', preg_split( '/\s+/', trim( (string) $s ) ) ) );
$eyebrow = '' !== $a['eyebrow'] ? $a['eyebrow'] : 'PL Events · Medical congress organiser' . ( $offs ? ' · ' . implode( ' · ', $offs ) : '' );
$where   = $offs ? ( count( $offs ) > 1 ? implode( ', ', array_slice( $offs, 0, -1 ) ) . ' and ' . end( $offs ) : $offs[0] ) : '';
$intro   = '' !== $a['intro'] ? $a['intro'] : 'We plan and run medical congresses, symposia, wetlabs and webinars for scientific societies' . ( $where ? ' — from ' . $where . ' to ' . $stats['countries'] . ' ' . ( 1 === $stats['countries'] ? 'country' : 'countries' ) : '' ) . '.';
$moving  = $sr['video'] || count( $stills ) > 1;
?>
<section class="hero" id="hero" aria-label="Introduction" data-pl="hero">
	<div class="media grain" id="heroMedia">
		<div class="iris" id="iris"><div class="iris-in" id="irisIn">
			<?php foreach ( $stills as $i => $src ) : ?>
				<?php
				$att = attachment_url_to_postid( $src );
				$set = $att ? wp_get_attachment_image_srcset( $att, 'full' ) : '';
				?>
				<?php if ( 0 === $i ) : // the first still is the poster and the LCP image ?>
				<img class="still on" src="<?php echo esc_url( $src ); ?>"<?php echo $set ? ' srcset="' . esc_attr( $set ) . '" sizes="100vw"' : ''; ?> alt="" fetchpriority="high" decoding="async">
				<?php else : // the others only feed the desktop crossfade: iris.js loads them in the Full context ?>
				<img class="still" data-src="<?php echo esc_url( $src ); ?>"<?php echo $set ? ' data-srcset="' . esc_attr( $set ) . '" sizes="100vw"' : ''; ?> alt="" decoding="async">
				<?php endif; ?>
			<?php endforeach; ?>
			<?php if ( $sr['video'] ) : ?>
				<video class="still reel" muted playsinline loop preload="none" aria-hidden="true" data-src="<?php echo esc_url( $sr['video'] ); ?>"<?php echo $stills ? ' poster="' . esc_url( $stills[0] ) . '"' : ''; ?>></video>
			<?php endif; ?>
		</div></div>
		<div class="shade" id="shade"></div>
	</div>
	<?php if ( $mark ) : ?>
	<span id="apLogo" class="wm" aria-hidden="true" style="position:absolute;left:40px;top:24px;z-index:4;color:var(--ink);opacity:0"><img src="<?php echo esc_url( $mark ); ?>" alt="" style="height:38px;width:auto"><b>PL</b> <span>EVENTS</span></span>
	<?php endif; ?>
	<div class="wrap copy"><div id="heroFrame" style="position:relative;padding:56px 56px 60px;display:flex;flex-direction:column;gap:24px;max-width:1160px">
		<div class="brk vf vfw" aria-hidden="true"></div>
		<span class="lab hfade" style="color:#E3E6EE"><?php echo esc_html( $eyebrow ); ?></span>
		<h1 id="heroH1"><?php echo $words( $hl['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped per word. ?><?php if ( $hl['em'] ) : ?><br><span class="em"><?php echo $words( $hl['em'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?></h1>
		<p class="hfade hsub" style="font-size:21px;line-height:1.55;max-width:640px;color:#EEF0F5"><?php echo esc_html( $intro ); ?></p>
		<div class="row hfade">
			<a href="#<?php echo esc_attr( $a['browse'] ); ?>" class="btn bw">Browse events</a>
			<a href="<?php echo esc_url( PL_Template::contact_url( 'proposal' ) ); ?>" class="btn bso">Request a proposal</a>
		</div>
	</div></div>
	<div class="figbar hfade"><div class="wrap in">
		<?php echo PL_Shortcodes::company_figures( array( 'layout' => 'inline' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php echo $next ? PL_Template::render( 'next-event', array( 'a' => array( 'layout' => 'strip' ), 'e' => $next ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div></div>
	<?php if ( $moving || $sr['sample'] ) : ?>
	<div class="hero-ctrl row">
		<?php echo $sr['sample'] ? PL_Template::sample_tag( 'Sample showreel' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php if ( $moving ) : ?><button type="button" class="btn ic bso" data-act="pause" aria-label="Pause background video" aria-pressed="false" style="font-size:13px">❚❚</button><?php endif; ?>
	</div>
	<?php endif; ?>
</section>
