<?php
/**
 * @package Content Aware Sidebars
 */

/**
 *
 * Page Template Module
 * 
 * Detects if current content has:
 * a) any or specific page template
 *
 *
 */
class CASModule_page_template extends CASModule {
	
	protected $id = 'page_templates';
	
	public function metadata($metadata) {
		$metadata['page_templates'] = array(
			'name'	=> __('Page Templates', 'content-aware-sidebars'),
			'id'	=> 'page_templates',
			'desc'	=> '',
			'val'	=> array(),
			'type'	=> 'checkbox',
			'list'	=> array_flip(get_page_templates())
		);
		return $metadata;
	}
	
	public function admin_gui($class) {
		add_meta_box(
				'ca-sidebar-page_templates',
				__('Page Templates', 'content-aware-sidebars'),
				array(&$class,'meta_box_checkboxes'),
				'sidebar',
				'side',
				'default',
				'page_templates'
			);
	}
	
	public function is_content() {		
		if(is_singular() && !('page' == get_option( 'show_on_front') && get_option('page_on_front') == get_the_ID())) {
			$template = get_post_meta(get_the_ID(),'_wp_page_template',true);
			if($template && $template != 'default') {
				return true;
			}
		}
		return false;
	}
	
	public function db_where($where) {
		$template = get_post_meta(get_the_ID(),'_wp_page_template',true);
		$where[$this->id] = "(page_templates.meta_value IS NULL OR (page_templates.meta_value LIKE '%page_templates%' OR page_templates.meta_value LIKE '%".$template."%'))";
		return $where;
	}
	
}
