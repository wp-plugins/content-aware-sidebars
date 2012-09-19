<?php
/**
 * @package Content Aware Sidebars
 */

/**
 *
 * Author Module
 * 
 * Detects if current content is:
 * a) post type written by any or specific author
 * b) any or specific author archive
 *
 */
class CASModule_author extends CASModule {
	
	public function __construct() {
		parent::__construct();
		$this->id = 'authors';
		$this->name = __('Authors','content-aware-sidebars');
	}
	
	public function is_content() {
		return (is_singular() && !is_front_page()) || is_author();
	}
	
	public function db_where() {
		global $post;
		$author = (string)(is_singular() ? $post->post_author : get_query_var('author'));
		return "(authors.meta_value IS NULL OR (authors.meta_value = 'authors' OR authors.meta_value = '".$author."'))";
		
	}

	public function _get_content() {
		global $wpdb;
		$author_list = array();
		foreach($wpdb->get_results("SELECT ID, display_name FROM $wpdb->users ORDER BY ID ASC") as $user) {
			$author_list[$user->ID] = $user->display_name;
		}
		return $author_list;
	}
	
}
