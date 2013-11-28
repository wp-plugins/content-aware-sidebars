<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

/**
 *
 * Page Template Module
 * 
 * Detects if current content has:
 * a) any or specific page template
 *
 *
 */
class CASModule_page_template extends CASModule {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->id = 'page_templates';
		$this->name = __('Page Templates',ContentAwareSidebars::DOMAIN);
	}
	
	/**
	 * Determine if content is relevant
	 * @return boolean 
	 */
	public function is_content() {		
		if(is_singular() && !('page' == get_option( 'show_on_front') && get_option('page_on_front') == get_the_ID())) {
			$template = get_post_meta(get_the_ID(),'_wp_page_template',true);
			return ($template && $template != 'default');
		}
		return false;
	}
	
	/**
	 * Query where
	 * @return string 
	 */
	public function db_where() {
		$template = get_post_meta(get_the_ID(),'_wp_page_template',true);
		return "(page_templates.meta_value IS NULL OR page_templates.meta_value IN('page_templates','".$template."'))";
	}

	/**
	 * Get page templates
	 * @return array 
	 */
	protected function _get_content() {
		return array_flip(get_page_templates());
	}
	
}
