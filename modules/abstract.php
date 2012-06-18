<?php
/**
 * @package Content Aware Sidebars
 */

/**
 *
 * All modules should extend this one.
 *
 */
abstract class CASModule {
	
	protected $id;
	
	/**
	 *
	 * Constructor
	 *
	 */
	public function __construct() {
		if(!isset($this->id))
			$this->id = substr(get_class($this),strpos(get_class($this),'_')+1);
		
		add_filter('cas_metadata',	array(&$this,'metadata'));
		add_action('cas_admin_gui',	array(&$this,'admin_gui'));
		add_action('cas_sidebar_db',	array(&$this,'init_content'));
	
	}
	
	public function get_id() {
		return $this->id;
	}
	
	/**
	 *
	 * Add hooks to plugin
	 *
	 */
	public function init_content() {
		
		if($this->is_content()) {
			add_filter('cas_sidebar_db_join',	array(&$this,'db_join'),10,2);
			add_filter('cas_sidebar_db_where',	array(&$this,'db_where'));
			add_filter('cas_sidebar_db_where2',	array(&$this,'db_where2'));
		}
	}
	
	public function db_join($join, $prefix) {
		global $wpdb;
		$join[$this->id] = "LEFT JOIN $wpdb->postmeta {$this->id} ON {$this->id}.post_id = posts.ID AND {$this->id}.meta_key = '".$prefix.$this->id."' ";
		return $join;
	}
	
	public function exclude_sidebar($continue, $post, $prefix) {
		if(!$continue) {
			//print_r($this->id."<br />");
			if (get_post_meta($post->ID, $prefix.$this->id, true) != '') {
				//print_r($this->id." has<br />");
				$continue = true;
			}
		}
		return $continue;
		
	}
	
	public function db_where2($where) {
		$where[$this->id] = "{$this->id}.meta_value IS NOT NULL";
		return $where;
	}
	
	abstract public function admin_gui($class);
	abstract public function metadata($metadata);
	abstract public function is_content();
	abstract public function db_where($where);
	
}
