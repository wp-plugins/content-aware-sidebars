<?php
/**
 * @package Content Aware Sidebars
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
	
	protected $id = 'language';
	
	public function metadata($metadata) {
		global $my_transposh_plugin;
		$langs = array();
			
		foreach(explode(',',$my_transposh_plugin->options->get_viewable_langs()) as $lng) {
			$langs[$lng] = transposh_consts::get_language_orig_name($lng);
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
			'ca-sidebar-transposh',
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
		global $my_transposh_plugin;
		$where[$this->id] = "(language.meta_value IS NULL OR (language.meta_value LIKE '%language%' OR language.meta_value LIKE '%".serialize($my_transposh_plugin->tgl)."%'))";
		return $where;
		
	}
	
}
