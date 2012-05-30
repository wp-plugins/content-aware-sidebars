<?php
/**
 * @package Content Aware Sidebars
 */

/**
 *
 * WPML Module
 * 
 * Detects if current content is:
 * a) any or specific bbpress user profile
 *
 */
class CASModule_wpml extends CASModule {
	
	protected $id = 'language';
	
	public function metadata($metadata) {
		$langs = array();
		
		foreach(icl_get_languages('skip_missing=N') as $lng) {
			$langs[$lng['language_code']] = $lng['native_name'];	
		}
		
		$metadata['language'] = array(
			'name'	=> __('Languages', 'content-aware-sidebars'),
			'id'	=> 'language',
			'val'	=> array(),
			'type'	=> 'checkbox',
			'list'	=> $langs
		);
		return $metadata;
	}
	
	public function admin_gui($class) {
		add_meta_box(
			'ca-sidebar-wpml',
			__('Languages', 'content-aware-sidebars'),
			array(&$class,'meta_box_checkboxes'),
			'sidebar',
			'side',
			'default',
			'language'
		);
	}
	
	public function is_content() {
		return true;
	}
	
	public function db_where($where) {
		$where[$this->id] = "(language.meta_value IS NULL OR (language.meta_value LIKE '%language%' OR language.meta_value LIKE '%".serialize(ICL_LANGUAGE_CODE)."%'))";
		return $where;
		
	}
	
}

?>