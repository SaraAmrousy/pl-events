<?php
/** Sponsors — equal tiles (Step 2.2 §3). */
defined( 'ABSPATH' ) || exit;
class PL_Module_Sponsors extends PL_Module {
	public $type = 'pl_sponsor';
	public $slug = 'sponsors';
	public $one  = 'Sponsor';
	public $many = 'Sponsors';
	public $title_label = 'Company name, e.g. Alcon';
}
return new PL_Module_Sponsors();
