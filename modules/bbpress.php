<?php
/**
 * @package Content Aware Sidebars
 */

/**
 *
 * bbPress Module
 * 
 * Detects if current content is:
 * a) any or specific bbpress user profile
 *
 */
class CASModule_bbpress extends CASModule {
	
	protected $id = 'authors';
	protected $name = 'bbPress';
	
	public function is_content() {
		return bbp_is_single_user();
	}
	
	public function db_where() {
		return "(authors.meta_value = 'authors' OR authors.meta_value = '".bbp_get_displayed_user_id()."')";	
	}

	public function _get_content() {
		return 0;
	}
	
}
