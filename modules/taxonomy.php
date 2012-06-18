<?php
/**
 * @package Content Aware Sidebars
 */

/**
 *
 * Taxonomy Module
 *
 * Detects if current content has/is:
 * a) any term of specific taxonomy or specific term
 * b) taxonomy archive or specific term archive
 *
 */
class CASModule_taxonomy extends CASModule {
	
	protected $id = 'taxonomies';
	private $taxonomies;
	private $taxonomy_objects;
	private $post_taxonomies;
	private $post_terms;
	
	public function metadata($metadata) {
		
		// List public taxonomies
		foreach(get_taxonomies(array('public'=>true),'objects') as $tax) {
			$this->taxonomies[$tax->name] = $tax->label;
			$this->taxonomy_objects[$tax->name] = $tax;
		}
		
		$metadata[$this->id] = array(
			'name'	=> __('Taxonomies', 'content-aware-sidebars'),
			'id'	=> $this->id,
			'desc'	=> '',
			'val'	=> array(),
			'type'	=> 'checkbox',
			'list'	=> $this->taxonomies
		);
		return $metadata;
		
	}
	
	public function admin_gui($class) {
		foreach($this->taxonomy_objects as $tax) {
			add_meta_box(
				'ca-sidebar-tax-'.$tax->name,
				$tax->label,
				array(&$class,'meta_box_taxonomy'),
				'sidebar',
				'side',
				'default',
				$tax
			);
		}
	}
	
	public function is_content() {		
		if(is_singular()) {
			// Check if content has any taxonomies supported
			$this->post_taxonomies = get_object_taxonomies(get_post_type());
			if($this->post_taxonomies) {
				$this->post_terms = wp_get_object_terms(get_the_ID(),$this->post_taxonomies);
				// Check if content has any actual taxonomy terms
				if($this->post_terms) {
					return true;
				}
			}
		} else if(is_tax() || is_category() || is_tag()) {
			return true;
		}
		return false;
	}
	
	public function db_join($join, $prefix) {
		global $wpdb;
		
		$joins = "LEFT JOIN $wpdb->term_relationships term ON term.object_id = posts.ID ";
		$joins .= "LEFT JOIN $wpdb->term_taxonomy taxonomy ON taxonomy.term_taxonomy_id = term.term_taxonomy_id ";
		$joins .= "LEFT JOIN $wpdb->terms terms ON terms.term_id = taxonomy.term_id ";
		$joins .= "LEFT JOIN $wpdb->postmeta taxonomies ON taxonomies.post_id = posts.ID AND taxonomies.meta_key = '".$prefix."taxonomies'";
		
		$join[$this->id] = $joins;
		
		return $join;
	
	}
	
	public function db_where($where) {
		
		if(is_singular()) {
			$terms = array();
			$taxonomies = array();
						
			//Grab posts terms and make where rules for taxonomies.
			$tax_where[] = "taxonomies.meta_value IS NULL";
			foreach($this->post_terms as $term) {
				$terms[] = $term->slug;
				if(!isset($taxonomies[$term->taxonomy])) {
					$tax_where[] = "taxonomies.meta_value LIKE '%".$taxonomies[$term->taxonomy] = $term->taxonomy."%'";
				}
			}

			$where[$this->id] = "(terms.slug IS NULL OR terms.slug IN('".implode("','",$terms)."')) AND (".implode(' OR ',$tax_where).")";
			return $where;
		}
		$term = get_queried_object();
		
		$where[$this->id] = "(terms.slug = '$term->slug' OR taxonomies.meta_value LIKE '%".serialize($term->taxonomy)."%')";
		return $where;
		
	}
	
	public function db_where2($where) {
		$where[$this->id] = "terms.slug IS NOT NULL OR taxonomies.meta_value IS NOT NULL";
		return $where;
	}
}
