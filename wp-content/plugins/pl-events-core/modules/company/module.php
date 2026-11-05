<?php
/** Company — one screen: PL Website › Company (SCF Options Page, Step 2.2 D6). */
defined( 'ABSPATH' ) || exit;
class PL_Module_Company extends PL_Module {
	public $type = 'company';
	public $slug = 'company';
	public $one  = 'Company';
	public $many = 'Company';

	public function register() {
		if ( did_action( 'acf/init' ) ) {
			$this->options_page();
		} else {
			add_action( 'acf/init', array( $this, 'options_page' ) );
		}
	}

	public function options_page() {
		if ( function_exists( 'acf_add_options_sub_page' ) ) {
			acf_add_options_sub_page(
				array(
					'page_title'  => 'Company',
					'menu_title'  => 'Company',
					'menu_slug'   => 'pl-company',
					'parent_slug' => 'pl-website',
					'capability'  => 'edit_pl_items',
					'post_id'     => 'options',
					'autoload'    => true,
					'update_button' => 'Save company settings',
				)
			);
		}
	}

	/** The Company page has no Show on site / Sample switches. */
	public function fields() {
		return apply_filters( 'pl_fields_company', require PL_EVENTS_DIR . 'modules/company/fields.php' );
	}

	public function register_fields() {
		PL_Fields::register_group( 'company', 'Company settings', $this->fields(), array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'pl-company' ) ) ) );
	}

	public function items( $include_hidden = false ) {
		return array( $this->data() );
	}

	public function data() {
		$d    = PL_Fields::export( 'options', 'company' );
		$get  = fn( $n ) => PL_Fields::get( 'options', PL_Fields::def( 'company', $n ) );
		$file = (int) get_option( 'options_pl_portfolio_file' );
		$url  = $file ? (string) wp_get_attachment_url( $file ) : $get( 'portfolio_link' );
		$size = '';
		if ( $file && is_readable( (string) get_attached_file( $file ) ) ) {
			$size = size_format( filesize( get_attached_file( $file ) ), 1 );
		} elseif ( $url ) {
			$size = (string) get_option( 'pl_portfolio_size', '' );
		}
		$years = $d['founding_year'] ? max( 0, (int) PL_Dates::local_today( 'Africa/Cairo' ) - (int) $d['founding_year'] ) : null;
		$stills = $get( 'showreel_stills' );
		return array(
			'name'          => 'PL Events',
			'tagline'       => $d['tagline'],
			'hero_headline' => array( 'text' => $get( 'hero_text' ), 'em' => $get( 'hero_em' ) ),
			'founding_year' => $d['founding_year'],
			'years'         => $years,
			'figures'       => $d['figures'],
			'portfolio'     => array( 'url' => $url, 'size' => str_replace( ' ', ' ', $size ) ),
			'showreel'      => array(
				'video'  => $get( 'showreel_video' ),
				'poster' => $get( 'showreel_poster' ) ? $get( 'showreel_poster' ) : ( $stills[0] ?? '' ),
				'stills' => $stills,
				'sample' => $get( 'showreel_sample' ),
			),
			'social'        => array_map( fn( $r ) => array( 'label' => self::social_label( $r['url'] ), 'url' => $r['url'] ), $d['social'] ),
			'show_sample_tags' => $get( 'show_sample_tags' ),
		);
	}

	public function from_data( array $c ) {
		$v = array(
			'tagline' => $c['tagline'] ?? '',
			'figures' => $c['figures'] ?? array(),
			'social'  => array_map( fn( $s ) => array( 'url' => $s['url'] ), $c['social'] ?? array() ),
		);
		if ( isset( $c['hero_headline'] ) ) {
			$v['hero_text'] = $c['hero_headline']['text'] ?? '';
			$v['hero_em']   = $c['hero_headline']['em'] ?? '';
		}
		if ( ! empty( $c['portfolio']['url'] ) ) {
			$v['portfolio_link'] = $c['portfolio']['url'];
		}
		if ( isset( $c['showreel']['sample'] ) ) {
			$v['showreel_sample'] = (bool) $c['showreel']['sample'];
		}
		return $v;
	}

	/** Icon/name worked out from the address (Step 2.2 §3). */
	public static function social_label( $url ) {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$map  = array( 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'fb.' => 'Facebook', 'youtube' => 'YouTube', 'youtu.be' => 'YouTube', 'x.com' => 'X', 'twitter' => 'X', 'tiktok' => 'TikTok', 'wa.me' => 'WhatsApp', 'whatsapp' => 'WhatsApp', 'snapchat' => 'Snapchat', 'threads' => 'Threads' );
		foreach ( $map as $needle => $label ) {
			if ( false !== strpos( $host, $needle ) ) {
				return $label;
			}
		}
		return $host ? preg_replace( '/^www\./', '', $host ) : 'Link';
	}
}
return new PL_Module_Company();
