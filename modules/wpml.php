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
			ICL_LANGUAGE_CODE
		);
	}

	/**
	 * Get languages
	 * @return array 
	 */
	protected function _get_content($args = array()) {
		$langs = array();

		foreach(icl_get_languages('skip_missing=N') as $lng) {
			$langs[$lng['language_code']] = $lng['native_name'];	
		}			

		if(isset($args['include'])) {
			$langs = array_intersect_key($langs,array_flip($args['include']));
		}
		return $langs;
	}
	
}
