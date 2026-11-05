<?php
/**
 * Services — one item per service group ("Scientific & content" …) with its list of services.
 * Groups are admin-managed (Step 2.2 D5): only an administrator can add or delete a group;
 * the PL Editor edits their text, photo and services.
 */
defined( 'ABSPATH' ) || exit;
class PL_Module_Services extends PL_Module {
	public $type = 'pl_service';
	public $slug = 'services';
	public $one  = 'Service group';
	public $many = 'Services';
	public $title_label = 'Group name, e.g. Scientific & content';

	public function cpt_args() {
		return array(
			'capabilities' => array(
				'create_posts'           => 'manage_options',
				'delete_posts'           => 'manage_options',
				'delete_published_posts' => 'manage_options',
				'delete_others_posts'    => 'manage_options',
			),
		);
	}

	public function to_data( WP_Post $p ) {
		$d = parent::to_data( $p );
		$id = (int) get_post_meta( $p->ID, 'pl_image', true );
		return array(
			'id'           => $d['id'],
			'group'        => $d['name'],
			'line'         => $d['line'],
			'image'        => $d['image'],
			'image_alt'    => $id ? (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) : '',
			'items'        => array_values( array_filter( array_map( fn( $r ) => $r['text'], $d['items'] ) ) ),
			'order'        => $d['order'],
			'show_on_site' => $d['show_on_site'],
			'sample'       => $d['sample'],
		);
	}

	public function from_data( array $item ) {
		$v = parent::from_data( $item );
		if ( isset( $item['items'] ) ) {
			$v['items'] = array_map( fn( $t ) => array( 'text' => $t ), $item['items'] );
		}
		return $v;
	}
}
return new PL_Module_Services();
