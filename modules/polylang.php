<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

/**
 *
 * Polylang Module
 * 
 * Detects if current content is:
 * a) in specific language
 *
 */
class CASModule_polylang extends CASModule {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->id = 'language';
		$this->name = __('Languages',ContentAwareSidebars::DOMAIN);
		
		add_filter('pll_get_post_types', array(&$this,'remove_sidebar_multilingual'));
		
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
	 * @return string 
	 */
	public function db_where() {
		return "(language.meta_value IS NULL OR language.meta_value IN('language','".pll_current_language()."'))";	
	}

	/**
	 * Get languages
	 * @global object $polylang
	 * @return array 
	 */
	protected function _get_content() {
		global $polylang;
		$langs = array();

		foreach($polylang->get_languages_list() as $lng) {
			$langs[$lng->slug] = $lng->name;	
		}
		return $langs;
	}
	
	/**
	 * Remove sidebars from multilingual list
	 * @param  array $post_types 
	 * @return array             
	 */
	public function remove_sidebar_multilingual($post_types) {
		unset($post_types[ContentAwareSidebars::TYPE_SIDEBAR]);
		return $post_types;
	}
	
}
