<?php
/**
 * Base for one content type. A new type = a new folder in modules/ with a module.php
 * that returns an instance of a PL_Module subclass, plus its fields.php registry.
 *
 * @package pl-events-core
 */

defined( 'ABSPATH' ) || exit;

abstract class PL_Module {

	/** @var string post type */
	public $type;
	/** @var string folder name in modules/ */
	public $slug;
	/** @var string singular, plural */
	public $one;
	public $many;
	/** @var string field group title */
	public $form_title = 'Details';
	/** @var string what the title box is called */
	public $title_label = 'Name';

	public function cpt_args() {
		return array();
	}

	public function register() {
		register_post_type(
			$this->type,
			array_merge(
				array(
					'labels'          => array(
						'name'               => $this->many,
						'singular_name'      => $this->one,
						'add_new'            => 'Add new',
						'add_new_item'       => 'Add new ' . strtolower( $this->one ),
						'edit_item'          => 'Edit ' . strtolower( $this->one ),
						'new_item'           => 'New ' . strtolower( $this->one ),
						'search_items'       => 'Search ' . strtolower( $this->many ),
						'not_found'          => 'No ' . strtolower( $this->many ) . ' yet',
						'not_found_in_trash' => 'Nothing in the bin',
						'all_items'          => $this->many,
						'menu_name'          => $this->many,
					),
					'public'          => false,
					'show_ui'         => true,
					'show_in_menu'    => 'pl-website',
					'show_in_rest'    => true,
					'supports'        => array( 'title', 'page-attributes' ),
					'capability_type' => array( 'pl_item', 'pl_items' ),
					'map_meta_cap'    => true,
					'rewrite'         => false,
					'has_archive'     => false,
				),
				$this->cpt_args()
			)
		);
		add_filter( 'enter_title_here', array( $this, 'title_placeholder' ), 10, 2 );
	}

	public function title_placeholder( $text, $post ) {
		return $post->post_type === $this->type ? $this->title_label : $text;
	}

	/** Registry: modules/{slug}/fields.php + the fields every item has (Step 2.2 §1). */
	public function fields() {
		$own = require PL_EVENTS_DIR . 'modules/' . $this->slug . '/fields.php';
		$common = array(
			array( 'name' => 'show_on_site', 'label' => 'Show on site', 'type' => 'switch', 'default' => 1, 'width' => 50, 'help' => 'Off = hidden everywhere on the site, kept here.' ),
			array( 'name' => 'sample', 'label' => 'Sample', 'type' => 'switch', 'default' => 0, 'width' => 50, 'admin_only' => true, 'help' => 'Sara only: marks dummy content. The site cannot launch while anything is Sample.' ),
		);
		return apply_filters( 'pl_fields_' . $this->slug, array_merge( $own, $common ) );
	}

	public function register_fields() {
		PL_Fields::register_group(
			$this->type,
			$this->form_title,
			$this->fields(),
			array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => $this->type ) ) )
		);
	}

	/** Published posts of this type, in Order (menu_order), as data-layer arrays. */
	public function items( $include_hidden = false ) {
		$posts = get_posts(
			array(
				'post_type'        => $this->type,
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'orderby'          => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
				'suppress_filters' => false,
			)
		);
		$out = array();
		foreach ( $posts as $p ) {
			$item = $this->to_data( $p );
			if ( $include_hidden || ! empty( $item['show_on_site'] ) ) {
				$out[] = $item;
			}
		}
		return $out;
	}

	/** One post → the pl-data.json shape. Subclasses add/rename keys. */
	public function to_data( WP_Post $p ) {
		return array_merge(
			array(
				'id'    => $p->post_name,
				'name'  => $p->post_title, // raw: templates escape on output.
				'order' => (int) $p->menu_order,
			),
			PL_Fields::export( $p->ID, $this->type )
		);
	}

	/** Importer: one pl-data.json item → field values ( name => value ). */
	public function from_data( array $item ) {
		$vals = array();
		foreach ( $this->fields() as $f ) {
			$k = $f['json'] ?? $f['name'];
			if ( false !== $k && array_key_exists( $k, $item ) ) {
				$vals[ $f['name'] ] = $item[ $k ];
			}
		}
		return $vals;
	}
}
