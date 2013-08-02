<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

/**
 *
 * qTranslate Module
 * 
 * Detects if current content is:
 * a) in specific language
 *
 */
class CASModule_qtranslate extends CASModule {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->id = 'language';
		$this->name = __('Languages',ContentAwareSidebars::DOMAIN);
		
	}
	
	/**
	 * Determine if content is relevant
	 * @return boolean 
	 */
	public function is_content() {
		return true;
	}
	
	/**
	 * Where query
	 * @return string 
	 */
	public function db_where() {
		return "(language.meta_value IS NULL OR language.meta_value IN('language','".qtrans_getLanguage()."'))";
	}
	
	/**
	 * Get languages
	 * @global array $q_config
	 * @return array 
	 */
	protected function _get_content() {
		global $q_config;
		$langs = array();
			
		foreach(get_option('qtranslate_enabled_languages') as $lng) {
			$langs[$lng] = $q_config['language_name'][$lng];
		}
		return $langs;
	}
	
}
