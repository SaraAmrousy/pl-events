<?php
/** Careers — hides itself after the closing date (Step 2.2 §3). */
defined( 'ABSPATH' ) || exit;
class PL_Module_Jobs extends PL_Module {
	public $type = 'pl_job';
	public $slug = 'jobs';
	public $one  = 'Job';
	public $many = 'Careers';
	public $title_label = 'Job title';

	public function to_data( WP_Post $p ) {
		$d          = parent::to_data( $p );
		$d['title'] = $d['name'];
		return $d;
	}

	public function items( $include_hidden = false ) {
		$today = PL_Dates::local_today( 'Africa/Cairo' );
		return array_values( array_filter( parent::items( $include_hidden ), fn( $j ) => $include_hidden || ! $j['closing'] || $j['closing'] >= $today ) );
	}
}
return new PL_Module_Jobs();
