<?php
/**
 * @package Content Aware Sidebars
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
	
	protected $id = 'language';
	protected $name = 'Languages';
	
	public function is_content() {
		return true;
	}
	
	public function db_where($where) {
		return "(language.meta_value IS NULL OR (language.meta_value = 'language' OR language.meta_value = '".ICL_LANGUAGE_CODE."'))";	
	}

	public function _get_content() {
		$langs = array();
		
		foreach(icl_get_languages('skip_missing=N') as $lng) {
			$langs[$lng['language_code']] = $lng['native_name'];	
		}
		return $langs;
	}
	
}
