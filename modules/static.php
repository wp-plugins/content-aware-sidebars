<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

/**
 *
 * Static Pages Module
 * 
 * Detects if current content is:
 * a) front page
 * b) search results
 * c) 404 page
 *
 */
class CASModule_static extends CASModule {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct('static',__('Static Pages',ContentAwareSidebars::DOMAIN));
		$this->type_display = false;
	}
	
	/**
	 * Get static content
	 * @return array 
	 */
	protected function _get_content($args = array()) {
		return array(
				'front-page'	=> __('Front Page', ContentAwareSidebars::DOMAIN),
				'search'		=> __('Search Results', ContentAwareSidebars::DOMAIN),
				'404'			=> __('404 Page', ContentAwareSidebars::DOMAIN)
			);
	}

	/**
	 * Determine if content is relevant
	 * @return boolean 
	 */
	public function is_content() {
		return is_front_page() || is_search() || is_404();
	}
	
	/**
	 * Get data from context
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.0
	 * @return array
	 */
	public function get_context_data() {
		if(is_front_page()) {
			$val = 'front-page';
		} else if(is_search()) {
			$val = 'search';
		} else {
			$val = '404';
		}
		return array(
			$val
		);			
	}
	
	// /**
	//  * Meta box content
	//  * @global object $post
	//  * @return void 
	//  */
	// public function meta_box_content() {
	// 	global $post;

	// 	echo '<li class="control-section accordion-section">';		
	// 	echo '<h3 class="accordion-section-title" title="'.$this->name.'" tabindex="0">'.$this->name.'</h3>'."\n";
	// 	echo '<div class="accordion-section-content cas-rule-content" id="cas-' . $this->id . '">'. "\n";
	// 	$meta = get_post_meta($post->ID, ContentAwareSidebars::PREFIX . $this->id, false);
	// 	$current = $meta != '' ? $meta : array();

	// 	echo '<ul id="cas-list-' . $this->id . '" class="cas-contentlist categorychecklist form-no-clear">'. "\n";
	// 	foreach ($this->_get_content() as $id => $name) {
	// 		echo '<li><label><input type="checkbox" name="' . $this->id . '[]" value="' . $id . '"' . (in_array($id, $current) ? ' checked="checked"' : '') . ' /> ' . $name . '</label></li>' . "\n";
	// 	}
	// 	echo '</ul>'. "\n";
	// 	echo '</div>'. "\n";
	// 	echo '</li>';
	// }
	
}
