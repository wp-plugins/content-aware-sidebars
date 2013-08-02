<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

/**
 *
 * Transposh Module
 * 
 * Detects if current content is:
 * a) in specific language
 *
 */
class CASModule_transposh extends CASModule {
	
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
	 * @global object $my_transposh_plugin
	 * @return string 
	 */
	public function db_where() {
		global $my_transposh_plugin;
		return "(language.meta_value IS NULL OR language.meta_value IN('language','".$my_transposh_plugin->tgl."'))";
		
	}

	/**
	 * Get languages
	 * @global object $my_transposh_plugin
	 * @return array 
	 */
	protected function _get_content() {
		global $my_transposh_plugin;
		$langs = array();
		foreach(explode(',',$my_transposh_plugin->options->get_viewable_langs()) as $lng) {
			$langs[$lng] = transposh_consts::get_language_orig_name($lng);
		}
		return $langs;
	}
	
}
