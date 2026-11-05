<?php
/** Memberships & awards — required proof link (Step 2.2 D10). Empty = proof strip not rendered. */
defined( 'ABSPATH' ) || exit;
class PL_Module_Memberships extends PL_Module {
	public $type = 'pl_membership';
	public $slug = 'memberships';
	public $one  = 'Membership / award';
	public $many = 'Memberships & awards';
	public $title_label = 'Name, e.g. ICCA member';
}
return new PL_Module_Memberships();
