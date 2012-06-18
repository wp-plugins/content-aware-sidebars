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
	
	protected $id = 'authors';
	
	public function metadata($metadata) {
		global $wpdb;
		
		$author_list = array();
		foreach($wpdb->get_results("SELECT ID, display_name FROM $wpdb->users ORDER BY ID ASC") as $user) {
			$author_list[$user->ID] = $user->display_name;
		}
		
		$metadata[$this->id] = array(
			'name'	=> __('Authors', 'content-aware-sidebars'),
			'id'	=> $this->id,
			'desc'	=> '',
			'val'	=> array(),
			'type'	=> 'checkbox',
			'list'	=> $author_list
		);
		return $metadata;
		
	}
	
	public function admin_gui($class) {
		add_meta_box(
			'ca-sidebar-authors',
			__('Authors', 'content-aware-sidebars'),
			array(&$class,'meta_box_checkboxes'),
			'sidebar',
			'side',
			'default',
			$this->id
		);
	}
	
	public function is_content() {
		return (is_singular() && !is_front_page()) || is_author();
	}
	
	public function db_where($where) {
		global $post;
		$author = (string)(is_singular() ? $post->post_author : get_query_var('author'));
		$where[$this->id] = "(authors.meta_value IS NULL OR (authors.meta_value LIKE '%authors%' OR authors.meta_value LIKE '%".serialize($author)."%'))";
		return $where;
		
	}
	
}
