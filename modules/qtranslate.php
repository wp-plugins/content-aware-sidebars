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
		parent::__construct('language',__('Languages',ContentAwareSidebars::DOMAIN));
	}
	
	/**
	 * Determine if content is relevant
	 * @return boolean 
	 */
	public function in_context() {
		return true;
	}

	/**
	 * Get data from context
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.0
	 * @return array
	 */
	public function get_context_data() {
		return array(
			$this->id,
			qtrans_getLanguage()
		);
	}
	
	/**
	 * Get languages
	 * @global array $q_config
	 * @return array 
	 */
	protected function _get_content($args = array()) {
		global $q_config;
		$langs = array();
			
		foreach(get_option('qtranslate_enabled_languages') as $lng) {
			$langs[$lng] = $q_config['language_name'][$lng];
		}
		if(isset($args['include'])) {
			$langs = array_intersect_key($langs,array_flip($args['include']));
		}
		return $langs;
	}
	
}
