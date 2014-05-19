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
		parent::__construct('bp_member',__('BuddyPress Members',ContentAwareSidebars::DOMAIN));
		
		add_filter('cas-is-content-static', array(&$this,'static_is_content'));
		
	}
	
	/**
	 * Get member pages
	 * @global object $bp
	 * @return array 
	 */
	protected function _get_content($args = array()) {
		global $bp;
		
		$components = $bp->loaded_components;
		unset($components['members'],$components['xprofile']);
		$components['profile'] = 'profile';
		
		$content = array();
		foreach((array)$components as $name) {
			$content[$name] = ucfirst($name);
		}
		if(isset($args['include'])) {
			$content = array_intersect_key($content,array_flip($args['include']));
		}
		
		return $content;
	}
	
	/**
	 * Determine if content is relevant
	 * @global object  $bp
	 * @return boolean 
	 */
	public function in_context() {
		global $bp;
		return $bp->displayed_user->domain != null;
	}

	/**
	 * Get data from context
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.0
	 * @return array
	 */
	public function get_context_data() {
		global $bp;
		return array(
			$bp->current_component,
			$bp->current_component."-".$bp->current_action
		);
	}
	
	/**
	 * Meta box content
	 * @global object $post
	 * @global object $bp
	 * @return void 
	 */
	public function meta_box_content() {
		global $post, $bp;
		
		echo '<li class="control-section accordion-section">';		
		echo '<h3 class="accordion-section-title" title="'.$this->name.'" tabindex="0">'.$this->name.'</h3>'."\n";
		echo '<div class="accordion-section-content cas-rule-content" data-cas-module="'.$this->id.'" id="cas-'.$this->id.'">';

		$field = $this->id;

		$tab_content = "";

		foreach ($this->_get_content() as $id => $name) {
			$tab_content .= '<li class="cas-'.$this->id.'-'.$id.'"><label class="selectit"><input type="checkbox" name="cas_condition[' . $field . '][]" value="' . $id . '" /> ' . $name . '</label></li>' . "\n";
			if(isset($bp->bp_options_nav[$id])) {
				$tab_content .= '<li><ul class="children">';
				foreach($bp->bp_options_nav[$id] as $child) {
					$tab_content .= '<li class="cas-'.$this->id.'-'.$id.'-'.$child['slug'].'"><label class="selectit"><input type="checkbox" name="cas_condition[' . $field . '][]" value="' . $id . '-'. $child['slug'].'" /> ' . $child['name'] . '</label></li>' . "\n";
				}
				$tab_content .= '</ul></li>';
			}
			
		}

		$tabs['all'] = array(
			'title' => __('View All'),
			'status' => true,
			'content' => $tab_content
		);

		echo $this->create_tab_panels($this->id,$tabs);

		echo '<p class="button-controls">';

		echo '<span class="add-to-group"><input data-cas-condition="'.$this->id.'" data-cas-module="'.$this->id.'" type="button" name="cas-condition-add" class="js-cas-condition-add button" value="'.__('Add to Group',ContentAwareSidebars::DOMAIN).'"></span>';

		echo '</p>';

		echo '</div>'."\n";
		echo '</li>';
	}
	
	/**
	 * Avoid collision with content of static module
	 * Somehow buddypress pages pass is_404()
	 * @param  boolean $content 
	 * @return boolean          
	 */
	public function static_is_content($content) {
		return $content && !$this->in_context();
	}
	
}
