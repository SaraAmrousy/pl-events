<?php
/**
 * Events: CPT `event` (no archive, URLs /events/{slug}/ — Step 2.2 D7), publish rules (D13),
 * gentle reminders, fixed slug at first publish, admin list columns and filters (§6).
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Module_Events extends PL_Module {

	public $type        = 'event';
	public $slug        = 'events';
	public $one         = 'Event';
	public $many        = 'Events';
	public $form_title  = 'Event details';
	public $title_label = 'Full official name, e.g. "38th ESOIRS Annual Congress"';

	public function cpt_args() {
		return array(
			'public'             => true,
			'publicly_queryable' => true,
			'exclude_from_search' => false,
			'has_archive'        => false,
			'rewrite'            => array( 'slug' => 'events', 'with_front' => false ),
			'supports'           => array( 'title', 'thumbnail' ),
			'menu_position'      => 1,
		);
	}

	public function register() {
		parent::register();
		add_filter( 'acf/validate_save_post', array( $this, 'validate' ), 10, 0 );
		add_action( 'acf/save_post', array( $this, 'after_save' ), 20 );
		add_action( 'admin_notices', array( $this, 'reminders' ) );
		add_filter( 'manage_event_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_event_posts_custom_column', array( $this, 'column' ), 10, 2 );
		add_filter( 'manage_edit-event_sortable_columns', fn( $c ) => $c + array( 'pl_start' => 'pl_start' ) );
		add_action( 'pre_get_posts', array( $this, 'admin_query' ) );
		add_action( 'restrict_manage_posts', array( $this, 'filters' ) );
	}

	public function to_data( WP_Post $p ) {
		$d = parent::to_data( $p );
		// pl-data.json order of keys.
		return array(
			'id'               => $d['id'],
			'name'             => $d['name'],
			'type'             => $d['type'],
			'specialties'      => $d['specialties'],
			'series'           => $d['series'],
			'edition'          => $d['edition'],
			'start'            => $d['start'],
			'end'              => $d['end'] ? $d['end'] : $d['start'],
			'country'          => $d['country'],
			'city'             => $d['city'],
			'venue'            => $d['venue'],
			'website'          => $d['website'],
			'registration_url' => $d['registration_url'],
			'registration'     => $d['registration'],
			'more_links'       => $d['more_links'],
			'phones'           => $d['phones'],
			'wa_inquiry'       => $d['wa_inquiry'],
			'email'            => $d['email'],
			'logo'             => $d['logo'],
			'poster'           => $d['poster'] ? $d['poster'] : (string) get_the_post_thumbnail_url( $p, 'large' ),
			'image_type'       => $d['image_type'],
			'photos'           => $d['photos'],
			'description'      => $d['description'],
			'delegates'        => $d['delegates'],
			'pin_home'         => $d['pin_home'],
			'show_on_site'     => $d['show_on_site'],
			'sample'           => $d['sample'],
			'url'              => get_permalink( $p ),
			'post_id'          => $p->ID,
			'order'            => $d['order'],
		);
	}

	/* ---------------------------------------------- D13: what blocks Publish */

	public function validate() {
		$in  = isset( $_POST['acf'] ) && is_array( $_POST['acf'] ) ? map_deep( wp_unslash( $_POST['acf'] ), 'sanitize_text_field' ) : array(); // phpcs:ignore WordPress.Security.NonceVerification -- SCF verified its nonce; values are only compared.
		$key = fn( $n ) => PL_Fields::field_key( 'event', $n );
		if ( ! isset( $in[ $key( 'start' ) ] ) ) {
			return; // not the event form.
		}
		$start = (string) $in[ $key( 'start' ) ];
		$end   = (string) ( $in[ $key( 'end' ) ] ?? '' );
		if ( $start && $end && $end < $start ) {
			acf_add_validation_error( 'acf[' . $key( 'end' ) . ']', 'The end date is before the start date. Pick an end date on or after the start (the same day for a one-day event).' );
		}
		$reg = PL_Guardrails::normalize_url( (string) ( $in[ $key( 'registration_url' ) ] ?? '' ) );
		if ( 'open' === ( $in[ $key( 'registration' ) ] ?? '' ) && ! PL_Guardrails::is_web_link( $reg ) ) {
			acf_add_validation_error( 'acf[' . $key( 'registration_url' ) . ']', 'Registration is "Open", so the site needs the registration link. Paste it here, or set Registration to "Not open yet".' );
		}
		foreach ( array( 'website', 'registration_url' ) as $n ) {
			$v = PL_Guardrails::normalize_url( (string) ( $in[ $key( $n ) ] ?? '' ) );
			if ( '' !== $v && ! PL_Guardrails::is_web_link( $v ) ) {
				acf_add_validation_error( 'acf[' . $key( $n ) . ']', 'This doesn’t look like a web address. Paste the full link from the browser’s address bar, e.g. https://www.example.org' );
			}
		}
		$type   = $in[ $key( 'type' ) ] ?? '';
		$term   = $type ? get_term( (int) $type, PL_Taxonomies::EVENT_TYPE ) : null;
		$online = $term && ! is_wp_error( $term ) && get_term_meta( $term->term_id, 'pl_online', true );
		if ( ! $online ) {
			if ( empty( $in[ $key( 'country' ) ] ) ) {
				acf_add_validation_error( 'acf[' . $key( 'country' ) . ']', 'Choose the country from the list. (Only online events can skip it.)' );
			}
			if ( '' === trim( (string) ( $in[ $key( 'city' ) ] ?? '' ) ) ) {
				acf_add_validation_error( 'acf[' . $key( 'city' ) . ']', 'Type the city in English, e.g. Alexandria. (Only online events can skip it.)' );
			}
		}
	}

	public static function is_web_link( $v ) {
		return (bool) preg_match( '#^https?://[^\s/$.?\#].[^\s]*$#i', (string) $v );
	}

	/** Page URL from series + year (or the name), set at first publish, then fixed. */
	public function after_save( $post_id ) {
		if ( 'event' !== get_post_type( (int) $post_id ) || 'publish' !== get_post_status( (int) $post_id ) || get_post_meta( (int) $post_id, '_pl_slug_fixed', true ) ) {
			return;
		}
		$series = PL_Fields::get( $post_id, PL_Fields::def( 'event', 'series' ) );
		$start  = PL_Fields::get( $post_id, PL_Fields::def( 'event', 'start' ) );
		$base   = $series && $start ? $series . ' ' . substr( $start, 0, 4 ) : get_the_title( $post_id );
		$slug   = wp_unique_post_slug( sanitize_title( $base ), $post_id, 'publish', 'event', 0 );
		remove_action( 'acf/save_post', array( $this, 'after_save' ), 20 );
		wp_update_post( array( 'ID' => $post_id, 'post_name' => $slug ) );
		add_action( 'acf/save_post', array( $this, 'after_save' ), 20 );
		update_post_meta( $post_id, '_pl_slug_fixed', 1 );
	}

	/* ------------------------------------------- gentle reminders (yellow) */

	public function reminders() {
		$screen = get_current_screen();
		if ( ! $screen || 'event' !== $screen->post_type || 'post' !== $screen->base ) {
			return;
		}
		$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification -- read-only screen notice
		if ( ! $id ) {
			return;
		}
		$e = PL_Data::event_by_id( $id );
		if ( ! $e ) {
			return;
		}
		$notes = array();
		if ( 'past' !== $e['status'] && ! $e['website'] && ! $e['registration_url'] ) {
			$notes[] = 'This upcoming event has no website and no registration link yet.';
		}
		if ( ! $e['online'] && $e['city'] && $e['on_centre'] ) {
			$notes[] = sprintf( 'City "%s" was not found in %s — the map pin sits on the country centre. Check the spelling (in English).', esc_html( $e['city'] ), esc_html( $e['country_name'] ) );
		}
		if ( $e['pin_home'] ) {
			$pinned = array_filter( PL_Data::events(), fn( $x ) => $x['pin_home'] && 'past' !== $x['status'] );
			if ( count( $pinned ) > 1 ) {
				$notes[] = 'Pin to Home is on for more than one event — the nearest pinned one is shown.';
			}
		}
		foreach ( $notes as $n ) {
			echo '<div class="notice notice-warning"><p>' . wp_kses_post( $n ) . '</p></div>';
		}
	}

	/* ------------------------------------------------- list columns (§6) */

	public function columns( $cols ) {
		return array(
			'cb'          => $cols['cb'],
			'title'       => 'Event',
			'pl_start'    => 'Start date',
			'pl_country'  => 'Country',
			'pl_city'     => 'City',
			'pl_status'   => 'Status',
			'pl_reg'      => 'Registration',
			'pl_show'     => 'Show on site',
		);
	}

	public function column( $col, $post_id ) {
		static $cache = array();
		if ( ! isset( $cache[ $post_id ] ) ) {
			$cache[ $post_id ] = PL_Data::event_by_id( $post_id, true );
		}
		$e = $cache[ $post_id ];
		if ( ! $e ) {
			echo '—';
			return;
		}
		switch ( $col ) {
			case 'pl_start':
				echo esc_html( $e['start'] ? $e['range'] : '—' );
				break;
			case 'pl_country':
				echo esc_html( $e['country'] ? PL_Geo::emoji( $e['country'] ) . ' ' . $e['country_name'] : ( $e['online'] ? 'Online' : '—' ) );
				break;
			case 'pl_city':
				echo esc_html( $e['city'] ) . ( $e['city'] && $e['on_centre'] ? ' <span title="City not found — pin on country centre" style="color:#b26a00">⚠</span>' : '' );
				break;
			case 'pl_status':
				$c = array( 'live' => '#C8102E', 'upcoming' => '#2C4894', 'past' => '#6B6B6B' )[ $e['status'] ];
				printf( '<span style="display:inline-block;padding:2px 8px;border-radius:99px;color:#fff;background:%s;font-weight:600">%s</span>', esc_attr( $c ), esc_html( ucfirst( $e['status'] ) ) );
				break;
			case 'pl_reg':
				echo esc_html( array( 'not_open' => 'Not open yet', 'open' => 'Open', 'closed' => 'Closed' )[ $e['registration'] ] ?? '—' );
				break;
			case 'pl_show':
				echo $e['show_on_site'] ? 'Yes' : '<strong>Hidden</strong>';
				break;
		}
	}

	/** Newest first; filter by year and status. */
	public function admin_query( WP_Query $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || 'event' !== $q->get( 'post_type' ) ) {
			return;
		}
		$orderby = $q->get( 'orderby' );
		if ( ! $orderby || 'pl_start' === $orderby ) {
			$q->set( 'meta_key', 'pl_start' );
			$q->set( 'orderby', 'meta_value' );
			$q->set( 'order', $q->get( 'order' ) ? $q->get( 'order' ) : 'DESC' );
		}
		$year = isset( $_GET['pl_year'] ) ? absint( $_GET['pl_year'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$st   = isset( $_GET['pl_status'] ) ? sanitize_key( $_GET['pl_status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( $year || $st ) {
			$ids = array();
			foreach ( PL_Data::events( array( 'include_hidden' => true ) ) as $e ) {
				if ( ( ! $year || (int) $e['year'] === $year ) && ( ! $st || $e['status'] === $st ) ) {
					$ids[] = $e['post_id'];
				}
			}
			$q->set( 'post__in', $ids ? $ids : array( 0 ) );
		}
	}

	public function filters( $post_type ) {
		if ( 'event' !== $post_type ) {
			return;
		}
		$years = array_unique( array_map( fn( $e ) => $e['year'], PL_Data::events( array( 'include_hidden' => true ) ) ) );
		rsort( $years );
		$cy = isset( $_GET['pl_year'] ) ? absint( $_GET['pl_year'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$cs = isset( $_GET['pl_status'] ) ? sanitize_key( $_GET['pl_status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		echo '<select name="pl_year"><option value="">All years</option>';
		foreach ( $years as $y ) {
			printf( '<option value="%1$d"%2$s>%1$d</option>', (int) $y, selected( $cy, (int) $y, false ) );
		}
		echo '</select><select name="pl_status"><option value="">All statuses</option>';
		foreach ( array( 'live' => 'Live', 'upcoming' => 'Upcoming', 'past' => 'Past' ) as $k => $l ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $cs, $k, false ), esc_html( $l ) );
		}
		echo '</select>';
	}
}

return new PL_Module_Events();
