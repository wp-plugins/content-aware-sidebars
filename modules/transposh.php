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
		parent::__construct('language',__('Languages',ContentAwareSidebars::DOMAIN));
	}
	
	/**
	 * Determine if content is relevant
	 * @return boolean 
	 */
	public function is_content() {
		return true;
	}

	/**
	 * Get data from context
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.0
	 * @return array
	 */
	public function get_context_data() {
		global $my_transposh_plugin;
		return array(
			$this->id,
			$my_transposh_plugin->tgl
		);
	}

	/**
	 * Get languages
	 * @global object $my_transposh_plugin
	 * @return array 
	 */
	protected function _get_content($args = array()) {
		global $my_transposh_plugin;
		$langs = array();
		foreach(explode(',',$my_transposh_plugin->options->get_viewable_langs()) as $lng) {
			$langs[$lng] = transposh_consts::get_language_orig_name($lng);
		}
		return $langs;
	}
	
}
