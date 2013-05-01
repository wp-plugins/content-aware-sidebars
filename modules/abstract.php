<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

/**
 *
 * All modules should extend this one.
 *
 */
abstract class CASModule {
	
	/**
	 * Module idenfification
	 * @var string
	 */
	protected $id;

	/**
	 * Module name
	 * @var string
	 */
	protected $name;

	/**
	 * Enable AJAX search in editor
	 * @var boolean
	 */
	protected $searchable = false;
	
	/**
	 *
	 * Constructor
	 *
	 */
	public function __construct() {
		$this->id = substr(get_class($this),strpos(get_class($this),'_')+1);
	}
	
	/**
	 * Default meta box content
	 * @global object $post
	 * @return void 
	 */
	public function meta_box_content() {
		global $post;
		
		if(!$this->_get_content())
			return;
		
		echo '<h4><a href="#">'.$this->name.'</a></h4>'."\n";
		echo '<div class="cas-rule-content" id="cas-'.$this->id.'">';
		$meta = get_post_meta($post->ID, ContentAwareSidebars::prefix.$this->id, false);
		$current = $meta != '' ? $meta : array();

		echo '<p><label><input class="cas-chk-all" type="checkbox" name="'.$this->id.'[]" value="'.$this->id.'"'.checked(in_array($this->id, $current), true, false).' /> '.sprintf(__('Show with All %s',ContentAwareSidebars::domain),$this->name).'</label></p>'."\n";
		
		// Show search if enabled and there is too much content
		if($this->searchable ) {
			echo _x('Search','verb',ContentAwareSidebars::domain).' <input class="cas-autocomplete-' . $this->id . ' cas-autocomplete" id="cas-autocomplete-' . $this->id . '" type="text" name="cas-autocomplete" value="" /> <input type="button" id="cas-add-' . $this->id . '" class="button" value="'.__('Add',ContentAwareSidebars::domain).'"/>'."\n";
		}

		echo '<ul id="cas-list-' . $this->id . '" class="cas-contentlist categorychecklist form-no-clear">'."\n";
		foreach($this->_get_content() as $id => $name) {
			echo '<li id="'.$this->id.'-'.$id.'"><label><input class="cas-' . $this->id . '" type="checkbox" name="'.$this->id.'[]" value="'.$id.'"'.checked(in_array($id,$current), true, false).' /> '.$name.'</label></li>'."\n";
		}	
		echo '</ul>'."\n";

		echo '</div>';
	}
	
	/**
	 * Default query join
	 * @global object $wpdb
	 * @return string 
	 */
	public function db_join() {
		global $wpdb;
		return "LEFT JOIN $wpdb->postmeta {$this->id} ON {$this->id}.post_id = posts.ID AND {$this->id}.meta_key = '".ContentAwareSidebars::prefix.$this->id."' ";
	}
	
	/**
	 * Exclude sidebar. TODO: revise
	 * @param  boolean $continue 
	 * @param  object $post     
	 * @param  string $prefix   
	 * @return boolean           
	 */
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
	
	/**
	 * Default where2 query
	 * @return string 
	 */
	public function db_where2() {
		return "{$this->id}.meta_value IS NOT NULL";
	}
	
	/**
	 * Idenficiation getter
	 * @return string 
	 */
	public function get_id() {
		return $this->id;
	}
	
	/**
	 * Get content for sidebar edit screen
	 * @return array 
	 */
	abstract protected function _get_content();

	/**
	 * Determine if current content is relevant
	 * @return boolean 
	 */
	abstract public function is_content();

	/**
	 * Where query
	 * @return string 
	 */
	abstract public function db_where();
	
}
