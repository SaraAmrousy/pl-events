<?php
/** Offices — Contact, footer, About, map origin (Step 2.2 §3). */
defined( 'ABSPATH' ) || exit;
class PL_Module_Offices extends PL_Module {
	public $type = 'pl_office';
	public $slug = 'offices';
	public $one  = 'Office';
	public $many = 'Offices';
	public $title_label = 'Office name, e.g. Alexandria';

	public function to_data( WP_Post $p ) {
		$d           = parent::to_data( $p );
		$d['emails'] = array_values( array_filter( array_map( fn( $r ) => $r['email'], $d['emails'] ) ) );
		if ( '' === $d['role'] ) {
			$d['role'] = $d['head_office'] ? 'Head office' : 'Office';
		}
		$d['country_name'] = PL_Geo::name( $d['country'] );
		return $d;
	}

	public function from_data( array $item ) {
		$v = parent::from_data( $item );
		if ( isset( $item['emails'] ) ) {
			$v['emails'] = array_map( fn( $m ) => is_array( $m ) ? $m : array( 'label' => '', 'email' => $m ), $item['emails'] );
		}
		if ( isset( $item['role'] ) && ! isset( $item['head_office'] ) ) {
			$v['head_office'] = 0 === stripos( $item['role'], 'head' );
		}
		return $v;
	}
}
return new PL_Module_Offices();
