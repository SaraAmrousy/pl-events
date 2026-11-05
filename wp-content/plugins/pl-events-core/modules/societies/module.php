<?php
/** Societies — "Societies that trust us" (Step 2.2 §3). */
defined( 'ABSPATH' ) || exit;
class PL_Module_Societies extends PL_Module {
	public $type = 'pl_society';
	public $slug = 'societies';
	public $one  = 'Society';
	public $many = 'Societies';
	public $title_label = 'Society name, e.g. ESOIRS';
}
return new PL_Module_Societies();
