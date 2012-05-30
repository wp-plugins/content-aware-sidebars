<?php
/**
 * @package Content Aware Sidebars
 */

/**
 *
 * Static Pages Module
 *
 */
class CASModule_static extends CASModule {
	
	public function metadata($metadata) {
		$metadata[$this->id] = array(
			'name'	=> __('Static Pages', 'content-aware-sidebars'),
			'id'	=> $this->id,
			'desc'	=> '',
			'val'	=> array(),
			'type'	=> 'checkbox',
			'list'	=> array(
				'front-page'	=> __('Front Page', 'content-aware-sidebars'),
				'search'	=> __('Search Results', 'content-aware-sidebars'),
				'404'		=> __('404 Page', 'content-aware-sidebars')
			)
		);
		return $metadata;
	}
	
	public function admin_gui($class) {
		
	}
	
	public function is_content() {
		return is_front_page() || is_search() || is_404();
	}
	
	public function db_where($where) {
		if(is_front_page()) {
			$val = 'front-page';
		} else if(is_search()) {
			$val = 'search';
		} else {
			$val = '404';
		}
		$where[$this->id] = "(static.meta_value IS NULL OR static.meta_value LIKE '%".$val."%')";
		return $where;
	}
	
}

?>