<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
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
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->id = 'bb_profile';
		$this->name = __('bbPress User Profiles',ContentAwareSidebars::domain);
		
		add_filter('cas-db-where-post_types', array(&$this,'add_forum_dependency'));
	}
	
	/**
	 * Determine if content is relevant
	 * @return boolean 
	 */
	public function is_content() {
		return bbp_is_single_user();
	}
	
	/**
	 * Query where
	 * @return string 
	 */
	public function db_where() {
		return "(bb_profile.meta_value = 'bb_profile' OR bb_profile.meta_value = '".bbp_get_displayed_user_id()."')";	
	}

	/**
	 * Get authors
	 * @global object $wpdb
	 * @return array 
	 */
	protected function _get_content() {
		global $wpdb;
		$author_list = array();
		foreach($wpdb->get_results("SELECT ID, display_name FROM $wpdb->users ORDER BY ID ASC LIMIT 0,200") as $user) {
			$author_list[$user->ID] = $user->display_name;
		}
		return $author_list;
	}
	
	/**
	 * Sidebars to be displayed with forums will also 
	 * be dislpayed with respective topics and replies
	 * @param  string $where 
	 * @return string 
	 */
	public function add_forum_dependency($where) {
		if(is_singular(array('topic','reply'))) {
			$where = "(post_types.meta_value IS NULL OR post_types.meta_value IN('".get_post_type()."','".get_the_ID()."','".bbp_get_forum_id()."','forum'))";
		}
		return $where;
	}
	
}
