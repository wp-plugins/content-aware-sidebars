<?php
/**
 * @package Content Aware Sidebars
 */

/**
 *
 * Post Type Module
 *
 * Detects if current content is:
 * a) specific post type or specific post
 * b) specific post type archive or home
 * 
 */
class CASModule_post_type extends CASModule {
	
	protected $id = 'post_types';
	private $post_types;
	private $post_type_objects;
	
	public function metadata($metadata) {
	
		// List public post types
		foreach(get_post_types(array('public'=>true),'objects') as $post_type) {
			$this->post_types[$post_type->name] = $post_type->label;
			$this->post_type_objects[$post_type->name] = $post_type;
		}
	
		$metadata['post_types'] = array(
			'name'	=> __('Post Types', 'content-aware-sidebars'),
			'id'	=> 'post_types',
			'desc'	=> '',
			'val'	=> array(),
			'type'	=> 'checkbox',
			'list'	=> $this->post_types
		);
		return $metadata;
		
	}
	
	public function admin_gui($class) {
		foreach($this->post_type_objects as $post_type) {
			add_meta_box(
				'ca-sidebar-post-type-'.$post_type->name,
				$post_type->label,
				array(&$class,'meta_box_post_type'),
				'sidebar',
				'normal',
				'high',
				$post_type
			);
		}
	}
	
	public function is_content() {
		return ((is_singular() || is_home()) && !is_front_page()) || is_post_type_archive();
	}
	
	public function db_where($where) {
		if(is_singular()) {
			$where[$this->id] = "(post_types.meta_value IS NULL OR (post_types.meta_value LIKE '%".serialize(get_post_type())."%' OR post_types.meta_value LIKE '%".serialize((string)get_the_ID())."%'))";
			return $where;
		}
		global $post_type;
		
		// Home has post as default post type
		if(!$post_type) $post_type = 'post';
		$where[$this->id] = "(post_types.meta_value IS NULL OR post_types.meta_value LIKE '%".serialize($post_type)."%')";
		return $where;
	}
	
}
