<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

/**
 *
 * WPML Module
 * 
 * Detects if current content is:
 * a) in specific language
 *
 */
class CASModule_wpml extends CASModule {
	
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
		return "(language.meta_value IS NULL OR language.meta_value IN('language','".ICL_LANGUAGE_CODE."'))";	
	}

	/**
	 * Get languages
	 * @return array 
	 */
	protected function _get_content() {
		$langs = array();
		
		foreach(icl_get_languages('skip_missing=N') as $lng) {
			$langs[$lng['language_code']] = $lng['native_name'];	
		}
		return $langs;
	}
	
}
