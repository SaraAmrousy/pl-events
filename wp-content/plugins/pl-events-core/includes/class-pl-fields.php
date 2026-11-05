<?php
/**
 * Field registry → SCF field groups, plus a reader/writer for the stored values.
 *
 * Every module declares its fields once in modules/{type}/fields.php. From that one
 * array this class builds the wp-admin form (SCF local field group, registered in PHP),
 * reads values for the data layer and writes values for the importer.
 *
 * Storage = the standard SCF/ACF format in post meta (or options for the Company page),
 * so the data layer keeps working even if SCF is switched off, and nothing is lost.
 *
 * Field definition keys:
 *   name      stable key (never rename — deprecate instead). Meta key = "pl_{name}".
 *   label     label shown to the editor
 *   type      text|textarea|url|email|number|date|select|switch|image|gallery|file|country|countries|phones|repeater|taxonomy|message
 *   json      key in the data-layer output / pl-data.json (default = name; false = not exported)
 *   required, help, default, choices, sub (repeater), taxonomy + multiple + add (taxonomy),
 *   soft_max  soft length counter (Step 2.2 D12 — never blocks)
 *   admin_only  hidden from the PL Editor role (e.g. Sample)
 *   width     50 / 33 … (form layout only)
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

class PL_Fields {

	/** @var array<string,array> registered groups: type => [ 'title' =>, 'fields' =>, 'location' => ] */
	private static $groups = array();

	public static function meta_key( $name ) {
		return 'pl_' . $name;
	}

	public static function field_key( $group, $name ) {
		return 'field_pl_' . $group . '_' . $name;
	}

	/**
	 * @param string $group    group id (post type or "company")
	 * @param string $title    group title in wp-admin
	 * @param array  $fields   registry
	 * @param array  $location SCF location rules
	 */
	public static function register_group( $group, $title, array $fields, array $location ) {
		self::$groups[ $group ] = compact( 'title', 'fields', 'location' );
	}

	public static function fields( $group ) {
		return isset( self::$groups[ $group ] ) ? self::$groups[ $group ]['fields'] : array();
	}

	public static function def( $group, $name ) {
		foreach ( self::fields( $group ) as $f ) {
			if ( $f['name'] === $name ) {
				return $f;
			}
		}
		return null;
	}

	/* --------------------------------------------------------------------- SCF */

	public static function init() {
		add_action( 'acf/init', array( __CLASS__, 'register_with_scf' ) );
		add_filter( 'acf/prepare_field', array( __CLASS__, 'hide_admin_only' ) );
	}

	public static function register_with_scf() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}
		foreach ( self::$groups as $group => $g ) {
			$fields = array();
			foreach ( $g['fields'] as $f ) {
				$fields[] = self::to_scf( $group, $f );
			}
			acf_add_local_field_group(
				array(
					'key'                   => 'group_pl_' . $group,
					'title'                 => $g['title'],
					'fields'                => $fields,
					'location'              => $g['location'],
					'position'              => 'acf_after_title',
					'style'                 => 'seamless',
					'label_placement'       => 'top',
					'instruction_placement' => 'label',
					'hide_on_screen'        => array( 'the_content', 'excerpt', 'discussion', 'comments', 'slug', 'author', 'format', 'send-trackbacks' ),
				)
			);
		}
	}

	/** Registry entry → SCF field array. */
	public static function to_scf( $group, array $f, $parent_key = '' ) {
		$key = $parent_key ? $parent_key . '_' . $f['name'] : self::field_key( $group, $f['name'] );
		$out = array(
			'key'          => $key,
			'name'         => $parent_key ? $f['name'] : self::meta_key( $f['name'] ),
			'label'        => $f['label'],
			'instructions' => $f['help'] ?? '',
			'required'     => ! empty( $f['required'] ) ? 1 : 0,
			'wrapper'      => array( 'width' => $f['width'] ?? '' ),
			'pl_admin_only' => ! empty( $f['admin_only'] ),
			'pl_frame'     => $f['frame'] ?? '',
		);
		if ( ! empty( $f['frame'] ) && isset( PL_Images::FRAMES[ $f['frame'] ] ) ) {
			$fr = PL_Images::FRAMES[ $f['frame'] ];
			$out['instructions'] .= ( $out['instructions'] ? ' ' : '' ) . ( 'contain' === $fr['fit']
				? sprintf( 'Shown whole inside a tile (never cropped). Best: SVG, or PNG at least %d × %d px.', $fr['min'][0], $fr['min'][1] )
				: sprintf( 'Cropped from the centre to fit the %s. Best: at least %d × %d px.', $fr['label'], $fr['min'][0], $fr['min'][1] ) );
		}
		if ( isset( $f['default'] ) ) {
			$out['default_value'] = $f['default'];
		}
		if ( ! empty( $f['soft_max'] ) ) {
			$out['instructions'] .= ( $out['instructions'] ? ' ' : '' ) . sprintf( 'Long texts may wrap to more lines (about %d characters reads best).', (int) $f['soft_max'] );
		}
		switch ( $f['type'] ) {
			case 'date':
				return $out + array( 'type' => 'date_picker', 'display_format' => 'j M Y', 'return_format' => 'Y-m-d', 'first_day' => 6 );
			case 'textarea':
				return $out + array( 'type' => 'textarea', 'rows' => 3, 'new_lines' => '' );
			case 'number':
				return $out + array( 'type' => 'number', 'min' => 0 );
			case 'select':
				return $out + array( 'type' => 'select', 'choices' => $f['choices'], 'ui' => 0, 'return_format' => 'value' );
			case 'switch':
				return $out + array( 'type' => 'true_false', 'ui' => 1, 'ui_on_text' => 'Yes', 'ui_off_text' => 'No' );
			case 'image':
				return $out + array( 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all' );
			case 'gallery':
				return $out + array( 'type' => 'gallery', 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all' );
			case 'file':
				return $out + array( 'type' => 'file', 'return_format' => 'id', 'library' => 'all', 'mime_types' => $f['mime'] ?? '' );
			case 'country':
				return $out + array( 'type' => 'select', 'choices' => PL_Geo::country_choices(), 'ui' => 1, 'allow_null' => 1, 'return_format' => 'value', 'placeholder' => 'Choose a country' );
			case 'countries':
				return $out + array( 'type' => 'select', 'choices' => PL_Geo::country_choices(), 'ui' => 1, 'multiple' => 1, 'allow_null' => 1, 'return_format' => 'value' );
			case 'phones':
				$f['sub'] = PL_Phones::subfields();
				// fall through.
			case 'repeater':
				$subs = array();
				foreach ( $f['sub'] as $s ) {
					$subs[] = self::to_scf( $group, $s, $key );
				}
				return $out + array(
					'type'         => 'repeater',
					'layout'       => 'table',
					'button_label' => $f['button'] ?? 'Add row',
					'sub_fields'   => $subs,
					'min'          => $f['min'] ?? 0,
				);
			case 'taxonomy':
				return $out + array(
					'type'          => 'taxonomy',
					'taxonomy'      => $f['taxonomy'],
					'field_type'    => ! empty( $f['multiple'] ) ? 'multi_select' : 'select',
					'allow_null'    => 1,
					'add_term'      => ! empty( $f['add'] ) ? 1 : 0,
					'save_terms'    => 1,
					'load_terms'    => 1,
					'return_format' => 'id',
				);
			case 'message':
				return array( 'key' => $key, 'name' => '', 'label' => $f['label'], 'type' => 'message', 'message' => is_callable( $f['message'] ?? null ) ? '' : ( $f['message'] ?? '' ), 'pl_message_cb' => $f['message'] ?? null, 'wrapper' => array( 'width' => $f['width'] ?? '' ) );
			default: // text, url, email.
				return $out + array( 'type' => in_array( $f['type'], array( 'url', 'email' ), true ) ? $f['type'] : 'text' );
		}
	}

	public static function hide_admin_only( $field ) {
		if ( ! empty( $field['pl_admin_only'] ) && ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		if ( 'message' === ( $field['type'] ?? '' ) && is_callable( $field['pl_message_cb'] ?? null ) ) {
			$field['message'] = call_user_func( $field['pl_message_cb'] );
		}
		return $field;
	}

	/* ------------------------------------------------------------------ Reader */

	/**
	 * Read a field the way the data layer needs it (plain PHP values, URLs for media).
	 *
	 * @param int|string $object post ID or "options"
	 */
	public static function get( $object, array $f ) {
		$name = self::meta_key( $f['name'] );
		if ( 'taxonomy' === $f['type'] ) {
			$terms = wp_get_post_terms( (int) $object, $f['taxonomy'], array( 'orderby' => 'term_order' ) );
			$names = is_wp_error( $terms ) ? array() : wp_list_pluck( $terms, 'name' );
			return ! empty( $f['multiple'] ) ? array_values( $names ) : ( $names[0] ?? '' );
		}
		$raw = self::raw( $object, $name );
		if ( in_array( $f['type'], array( 'repeater', 'phones' ), true ) ) {
			$rows = array();
			$subs = 'phones' === $f['type'] ? PL_Phones::subfields() : $f['sub'];
			for ( $i = 0; $i < (int) $raw; $i++ ) {
				$row = array();
				foreach ( $subs as $s ) {
					$row[ $s['name'] ] = self::cast( $s, self::raw( $object, $name . '_' . $i . '_' . $s['name'] ) );
				}
				$rows[] = $row;
			}
			return 'phones' === $f['type'] ? array_values( array_filter( array_map( array( 'PL_Phones', 'from_row' ), $rows ) ) ) : $rows;
		}
		return self::cast( $f, $raw );
	}

	private static function raw( $object, $name ) {
		return 'options' === $object ? get_option( 'options_' . $name, null ) : get_post_meta( (int) $object, $name, true );
	}

	private static function cast( array $f, $v ) {
		switch ( $f['type'] ) {
			case 'date':
				return ( is_string( $v ) && preg_match( '/^(\d{4})-?(\d{2})-?(\d{2})$/', $v, $m ) ) ? "$m[1]-$m[2]-$m[3]" : '';
			case 'switch':
				if ( '' === $v || null === $v ) {
					return (bool) ( $f['default'] ?? false );
				}
				return (bool) $v;
			case 'number':
				return ( '' === $v || null === $v ) ? null : (int) $v;
			case 'image':
			case 'file':
				return $v ? (string) wp_get_attachment_url( (int) $v ) : '';
			case 'gallery':
				return array_values( array_filter( array_map( static fn( $id ) => (string) wp_get_attachment_url( (int) $id ), (array) maybe_unserialize( $v ) ) ) );
			case 'countries':
				return array_values( array_filter( (array) maybe_unserialize( $v ) ) );
			case 'select':
				return ( '' === $v || null === $v ) ? (string) ( $f['default'] ?? '' ) : (string) $v;
			default:
				return null === $v ? '' : (string) $v;
		}
	}

	/* ------------------------------------------------------------------ Writer */

	/**
	 * Write a value in SCF's storage format (used by the importer and upgrades).
	 * Media values must already be attachment IDs.
	 */
	public static function set( $object, $group, array $f, $value ) {
		$name = self::meta_key( $f['name'] );
		$key  = self::field_key( $group, $f['name'] );
		if ( 'taxonomy' === $f['type'] ) {
			$ids = array();
			foreach ( (array) $value as $term ) {
				if ( '' === $term || null === $term ) {
					continue;
				}
				$ids[] = PL_Taxonomies::ensure_term( $term, $f['taxonomy'] );
			}
			$ids = array_filter( $ids );
			wp_set_object_terms( (int) $object, $ids, $f['taxonomy'] );
			self::put( $object, $name, ! empty( $f['multiple'] ) ? array_map( 'strval', $ids ) : (string) ( $ids[0] ?? '' ), $key );
			return;
		}
		if ( in_array( $f['type'], array( 'repeater', 'phones' ), true ) ) {
			$rows = 'phones' === $f['type'] ? array_map( array( 'PL_Phones', 'to_row' ), (array) $value ) : (array) $value;
			$subs = 'phones' === $f['type'] ? PL_Phones::subfields() : $f['sub'];
			$old  = (int) self::raw( $object, $name );
			for ( $i = count( $rows ); $i < $old; $i++ ) {
				foreach ( $subs as $s ) {
					self::del( $object, $name . '_' . $i . '_' . $s['name'] );
				}
			}
			foreach ( array_values( $rows ) as $i => $row ) {
				foreach ( $subs as $s ) {
					$v = is_array( $row ) ? ( $row[ $s['name'] ] ?? '' ) : $row;
					self::put( $object, $name . '_' . $i . '_' . $s['name'], self::store( $s, $v ), $key . '_' . $s['name'] );
				}
			}
			self::put( $object, $name, count( $rows ), $key );
			return;
		}
		self::put( $object, $name, self::store( $f, $value ), $key );
	}

	private static function store( array $f, $v ) {
		switch ( $f['type'] ) {
			case 'date':
				return $v ? str_replace( '-', '', (string) $v ) : '';
			case 'switch':
				return $v ? 1 : 0;
			case 'number':
				return ( null === $v || '' === $v ) ? '' : (int) $v;
			case 'gallery':
			case 'countries':
				return array_values( array_map( 'strval', (array) $v ) );
			default:
				return null === $v ? '' : $v;
		}
	}

	private static function put( $object, $name, $value, $key ) {
		if ( 'options' === $object ) {
			update_option( 'options_' . $name, $value, false );
			update_option( '_options_' . $name, $key, false );
		} else {
			update_post_meta( (int) $object, $name, $value );
			update_post_meta( (int) $object, '_' . $name, $key );
		}
	}

	private static function del( $object, $name ) {
		if ( 'options' === $object ) {
			delete_option( 'options_' . $name );
			delete_option( '_options_' . $name );
		} else {
			delete_post_meta( (int) $object, $name );
			delete_post_meta( (int) $object, '_' . $name );
		}
	}

	/** Read every exported field of a group into a json-keyed array. */
	public static function export( $object, $group ) {
		$out = array();
		foreach ( self::fields( $group ) as $f ) {
			if ( 'message' === $f['type'] || false === ( $f['json'] ?? null ) ) {
				continue;
			}
			$out[ $f['json'] ?? $f['name'] ] = self::get( $object, $f );
		}
		return $out;
	}
}
