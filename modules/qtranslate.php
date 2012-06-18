<?php
/**
 * @package Content Aware Sidebars
 */

/**
 *
 * bbPress Module
 * 
 * Detects if current content is:
 * a) any or specific bbpress user profile
 *
 */
class CASModule_qtranslate extends CASModule {
	
	protected $id = 'language';
	
	public function __construct() {
		parent::__construct();
		
		add_filter('manage_edit-sidebar_columns',		array(&$this,'admin_column_headers'));
		
	}
	
	public function metadata($metadata) {
		global $q_config;
		$langs = array();
			
		foreach(get_option('qtranslate_enabled_languages') as $lng) {
			$langs[$lng] = $q_config['language_name'][$lng];
		}
			
		$metadata[$this->id] = array(
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
			'ca-sidebar-qtranslate',
			__('Languages', 'content-aware-sidebars'),
			array(&$class,'meta_box_checkboxes'),
			'sidebar',
			'side',
			'default',
			$this->id
		);
	}
	
	public function is_content() {
		return true;
	}
	
	public function db_where($where) {
		$where[$this->id] = "(language.meta_value IS NULL OR (language.meta_value LIKE '%language%' OR language.meta_value LIKE '%".serialize(qtrans_getLanguage())."%'))";
		return $where;
	}
	
	public function admin_column_headers($columns) {	
		unset($columns['language']);	
		return $columns;
	}
	
}
