<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
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
class CASModule_url extends CASModule {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->id = 'url';
		$this->name = __('URLs',ContentAwareSidebars::domain);
	}


	/**
	 * Meta box content
	 * @global object $post
	 * @return void 
	 */
	public function meta_box_content() {
		global $post;
		
		echo '<h4><a href="#">'.$this->name.'</a></h4>'."\n";
		echo '<div class="cas-rule-content" id="cas-'.$this->id.'">';
		$field = $this->id;
		$meta = get_post_meta($post->ID, ContentAwareSidebars::prefix.$field, false);
		$current = $meta != '' ? $meta : array();

		echo __('Search',ContentAwareSidebars::domain).' <input class="cas-' . $field . '" type="text" name="'.$field.'[]" value="" /> <input type="button" id="cas_add_url" class="button" value="'.__('Add',ContentAwareSidebars::domain).'"/>'."\n";	
		
		echo '</div>';
	}
	
	/**
	 * Determine if content is relevant
	 * @return boolean 
	 */
	public function is_content() {
		return true;
	}
	
	/**
	 * Query where
	 * @global object $post
	 * @return string
	 */
	public function db_where() {
		global $post;
		$author = (string)(is_singular() ? $post->post_author : get_query_var('author'));
		return "(authors.meta_value IS NULL OR authors.meta_value IN('authors','".$author."'))";
		
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
	
}
