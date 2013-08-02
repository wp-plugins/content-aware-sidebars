<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

/**
 *
 * BuddyPress Member Page Module
 * 
 * Detects if current content is:
 * a) a specific buddypress member page
 *
 */
class CASModule_bp_member extends CASModule {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->id = 'bp_member';
		$this->name = __('BuddyPress Members',ContentAwareSidebars::DOMAIN);
		
		add_filter('cas-is-content-static', array(&$this,'static_is_content'));
		
	}
	
	/**
	 * Get member pages
	 * @global object $bp
	 * @return array 
	 */
	protected function _get_content() {
		global $bp;
		
		$components = $bp->loaded_components;
		unset($components['members'],$components['xprofile']);
		$components['profile'] = 'profile';
		
		$content = array();
		foreach((array)$components as $name) {
			$content[$name] = ucfirst($name);
		}
		
		return $content;
	}
	
	/**
	 * Determine if content is relevant
	 * @global object  $bp
	 * @return boolean 
	 */
	public function is_content() {
		global $bp;
		return $bp->displayed_user->domain != null;
	}
	
	/**
	 * Query where
	 * @global object $bp
	 * @return string
	 */
	public function db_where() {
		global $bp;
		return "(bp_member.meta_value IS NULL OR bp_member.meta_value IN ('".$bp->current_component."','".$bp->current_component."-".$bp->current_action."'))";

	}
	
	/**
	 * Meta box content
	 * @global object $post
	 * @global object $bp
	 * @return void 
	 */
	public function meta_box_content() {
		global $post, $bp;
		
		echo '<h4><a href="#">'.$this->name.'</a></h4>'."\n";
		echo '<div class="cas-rule-content" id="cas-' . $this->id . '">'."\n";
		$field = $this->id;
		$meta = get_post_meta($post->ID, ContentAwareSidebars::PREFIX . $field, false);
		$current = $meta != '' ? $meta : array();

		echo '<ul class="cas-contentlist categorychecklist form-no-clear">'."\n";
		foreach ($this->_get_content() as $id => $name) {
			echo '<li><label class="selectit"><input type="checkbox" name="' . $field . '[]" value="' . $id . '"' . (in_array($id, $current) ? ' checked="checked"' : '') . ' /> ' . $name . '</label></li>' . "\n";
			if(isset($bp->bp_options_nav[$id])) {
				echo '<ul class="children">';
				foreach($bp->bp_options_nav[$id] as $child) {
					echo '<li><label class="selectit"><input type="checkbox" name="' . $field . '[]" value="' . $id . '-'. $child['slug'].'"' . (in_array($id . '-'. $child['slug'], $current) ? ' checked="checked"' : '') . ' /> ' . $child['name'] . '</label></li>' . "\n";
				}
				echo '</ul>';
			}
			
		}

		echo '</ul>'."\n";
		echo '</div>'."\n";
	}
	
	/**
	 * Avoid collision with content of static module
	 * Somehow buddypress pages pass is_404()
	 * @param  boolean $content 
	 * @return boolean          
	 */
	public function static_is_content($content) {
		return $content && !$this->is_content();
	}
	
}
