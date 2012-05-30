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
	
	public function metadata($metadata) {
		return $metadata;
	}
	
	public function admin_gui($class) {
		
	}
	
	public function is_content() {
		return bbp_is_single_user();
	}
	
	public function db_where($where) {
		$where[$this->id] = "(authors.meta_value LIKE '%authors%' OR authors.meta_value LIKE '%".serialize((string)bbp_get_displayed_user_id())."%')";
		return $where;
		
	}
	
}

?>