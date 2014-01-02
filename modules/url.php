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
		parent::__construct('url',__('URLs',ContentAwareSidebars::DOMAIN));
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
		$meta = get_post_meta($post->ID, ContentAwareSidebars::PREFIX.$field, false);
		$current = $meta != '' ? $meta[0] : array();

		echo '<input class="cas-' . $field . '" type="text" name="'.$field.'[]" value="'.$current.'" /> <input type="button" id="cas_add_url" class="button" value="'.__('Add',ContentAwareSidebars::DOMAIN).'"/>'."\n";	
		
		echo '</div>';
	}
	
	/**
	 * Determine if content is relevant
	 * @return boolean 
	 */
	public function in_context() {
		global $wp_query;
		return $wp_query->query['pagename'] != null;
	}
	
	/**
	 * Query where
	 * @global object $post
	 * @return string
	 */
	public function db_where() {
		global $post, $wp_query;
		//var_dump($wp_query);
		var_dump($wp_query->query['pagename']);
		var_dump($wp_query->query['name']);

		$name = $wp_query->query['pagename'];

		return "(url.meta_value IS NULL OR REPLACE(url.meta_value,'*','%') LIKE '".$name."')";
		
	}

	/**
	 * Get data from context
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.0
	 * @return array
	 */
	public function get_context_data() {
		return array();
	}

	/**
	 * Get authors
	 * @global object $wpdb
	 * @return array 
	 */
	protected function _get_content($args = array()) {
		return 0;
	}
	
}
