<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
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
	
	/**
	 * Registered public taxonomies
	 * @var array
	 */
	private $taxonomy_objects;

	/**
	 * Terms of a given singular
	 * @var array
	 */
	private $post_terms;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->id = 'taxonomies';
		$this->name = __('Taxonomies',ContentAwareSidebars::DOMAIN);

		add_action('init', array(&$this,'add_taxonomies_to_sidebar'),100);
		add_action('admin_menu',array(&$this,'clear_admin_menu'));
		add_action('created_term', array(&$this,'term_ancestry_check'),10,3);

		add_action('wp_ajax_cas-autocomplete-'.$this->id, array(&$this,'ajax_content_search'));
		
	}
	
	/**
	 * Determine if content is relevant
	 * @return boolean 
	 */
	public function is_content() {		
		if(is_singular()) {
			// Check if content has any taxonomies supported
			$taxonomies = get_object_taxonomies(get_post_type(),'object');
			//Only want public taxonomies
			$taxonomy_names = array();
			foreach($taxonomies as $taxonomy) {
				if($taxonomy->public)
					$taxonomy_names[] = $taxonomy->name;
			}
			if(!empty($taxonomy_names)) {
				// Check if content has any actual taxonomy terms
				$this->post_terms = wp_get_object_terms(get_the_ID(),$taxonomy_names);
				return !empty($this->post_terms);
			}
			return false;
		}
		return is_tax() || is_category() || is_tag();
	}
	
	/**
	 * Query join
	 * @return string 
	 */
	public function db_join() {
		global $wpdb;
		
		$joins = "LEFT JOIN $wpdb->term_relationships term ON term.object_id = posts.ID ";
		$joins .= "LEFT JOIN $wpdb->term_taxonomy taxonomy ON taxonomy.term_taxonomy_id = term.term_taxonomy_id ";
		$joins .= "LEFT JOIN $wpdb->terms terms ON terms.term_id = taxonomy.term_id ";
		$joins .= "LEFT JOIN $wpdb->postmeta taxonomies ON taxonomies.post_id = posts.ID AND taxonomies.meta_key = '".ContentAwareSidebars::PREFIX."taxonomies'";
		
		return $joins;
	
	}
	
	/**
	 * Query where
	 * @return string 
	 */
	public function db_where() {
		
		if(is_singular()) {
			$terms = array();

			//Grab posts taxonomies and terms and sort them
			foreach($this->post_terms as $term) {
				$terms[$term->taxonomy][] = $term->term_id;
			}
			
			// Make rules for taxonomies and terms
			foreach($terms as $taxonomy => $term_arr) {  
				$termrules[] = "(taxonomy.taxonomy = '".$taxonomy."' AND terms.term_id IN('".implode("','",$term_arr)."'))";
				$taxrules[] = $taxonomy;
			}

			return "(terms.slug IS NULL OR ".implode(" OR ",$termrules).") AND (taxonomies.meta_value IS NULL OR taxonomies.meta_value IN('".implode("','",$taxrules)."'))";
		
			
		}
		$term = get_queried_object();
		
		return "((taxonomy.taxonomy = '".$term->taxonomy."' AND terms.slug = '".$term->slug."') OR taxonomies.meta_value = '".$term->taxonomy."')";
		
	}
	
	/**
	 * Query where2
	 * @return string 
	 */
	public function db_where2() {
		return "terms.slug IS NOT NULL OR taxonomies.meta_value IS NOT NULL";
	}

	/**
	 * Get registered taxonomies
	 * @return array 
	 */
	protected function _get_content() {
		// List public taxonomies
		if (empty($this->taxonomy_objects)) {
			foreach (get_taxonomies(array('public' => true), 'objects') as $tax) {
				$this->taxonomy_objects[$tax->name] = $tax;
			}
		}
		return $this->taxonomy_objects;
	}

	/**
	 * Meta box content
	 * @global object $post
	 * @return void 
	 */
	public function meta_box_content() {
		global $post;

		foreach ($this->_get_content() as $taxonomy) {
			echo '<h4><a href="#">' . $taxonomy->label . '</a></h4>'."\n";
			echo '<div class="cas-rule-content" id="cas-' . $this->id . '-' . $taxonomy->name . '">';

			$meta = get_post_meta($post->ID, ContentAwareSidebars::PREFIX . 'taxonomies', false);
			$current = $meta != '' ? $meta : array();

			$number_of_terms = wp_count_terms($taxonomy->name,array('hide_empty'=>false));

			if($taxonomy->hierarchical) {
				echo '<p>' . "\n";
				echo '<label><input type="checkbox" name="taxonomies[]" value="'.ContentAwareSidebars::PREFIX.'sub_' . $taxonomy->name . '"' . checked(in_array(ContentAwareSidebars::PREFIX."_sub_" . $taxonomy->name, $current), true, false) . ' /> ' . __('Automatically select new children of a selected ancestor', ContentAwareSidebars::DOMAIN) . '</label>' . "\n";
				echo '</p>' . "\n";
			}
			echo '<p>' . "\n";
			echo '<label><input class="cas-chk-all" type="checkbox" name="taxonomies[]" value="' . $taxonomy->name . '"' . checked(in_array($taxonomy->name, $current), true, false) . ' /> ' . sprintf(__('Show with %s', ContentAwareSidebars::DOMAIN), $taxonomy->labels->all_items) . '</label>' . "\n";
			echo '</p>' . "\n";
			
			if (!$number_of_terms) {
				echo '<p>' . __('No items.') . '</p>';
			} else {
		
				$selected_ids = array();
				if(($selected = wp_get_object_terms($post->ID, $taxonomy->name))) {
					$selected_ids = wp_get_object_terms($post->ID, $taxonomy->name,  array('fields' => 'ids'));
				} else {
					$selected = array();
				}
				$terms = get_terms($taxonomy->name, array(
					'number' => 20,
					'hide_empty' => false,
					'exclude' => $selected_ids
				));

				if($number_of_terms > 20) {
					echo _x('Search','verb',ContentAwareSidebars::DOMAIN).' <input class="cas-autocomplete-' . $this->id . ' cas-autocomplete" id="cas-autocomplete-' . $this->id . '-' . $taxonomy->name . '" type="text" name="cas-autocomplete" value="" placeholder="' . $taxonomy->label . '" />'."\n";
				}

				echo '<input type="hidden" name="'.($taxonomy->name == "category" ? "post_category[]" : "tax_input[".$taxonomy->name."]").'" value="0" />';
				echo '<ul id="cas-list-' . $this->id . '-' . $taxonomy->name . '" class="cas-contentlist categorychecklist form-no-clear">'."\n";
				$this->term_checklist($post->ID, $taxonomy, array_merge($selected,$terms), $selected_ids);
				echo '</ul>'."\n";

			}
			echo '</div>'."\n";
		}
	}

	/**
	 * Show terms from a specific taxonomy
	 * @param  int     $post_id      
	 * @param  object  $taxonomy     
	 * @param  array   $terms        
	 * @param  array   $selected_ids 
	 * @return void 
	 */
	private function term_checklist($post_id = 0, $taxonomy, $terms, $selected_ids) {

		$walker = new CAS_Walker_Checklist('category',array ('parent' => 'parent', 'id' => 'term_id'));

		$args = array(
			'taxonomy'	=> $taxonomy,
			'selected_terms' => $post_id ? $selected_ids : array()
		);
		
		$checked_terms = array();
		
		foreach( $terms as $key => $value ) {
			if (in_array($terms[$key]->term_id, $args['selected_terms'])) {
				$checked_terms[] = $terms[$key];
				unset($terms[$key]);
			}
		}
		
		//Put checked posts on top
		echo call_user_func_array(array(&$walker, 'walk'), array($checked_terms, 0, $args));
		// Then the rest of them
		echo call_user_func_array(array(&$walker, 'walk'), array($terms, 0, $args));
	}

	/**
	 * Get terms with AJAX search
	 * @return void 
	 */
	public function ajax_content_search() {
		
		// Verify request
		check_ajax_referer(basename('content-aware-sidebars.php'),'nonce');
	
		$suggestions = array();
		if ( preg_match('/cas-autocomplete-'.$this->id.'-([a-zA-Z_-]*\b)/', $_REQUEST['type'], $matches) ) {
			if(($taxonomy = get_taxonomy( $matches[1] ))) {
				$terms = get_terms($taxonomy->name, array(
					'number' => 10,
					'hide_empty' => false,
					'search' => $_REQUEST['term']
				));
				$name = ($taxonomy->name == 'category' ? 'post_category' : 'tax_input['.$matches[1].']'); 
				$value = ($taxonomy->hierarchical ? 'term_id' : 'slug');
				foreach($terms as $term) {
					$suggestions[] = array(
						'label' => $term->name,
						'value' => $term->name,
						'id'	=> $term->$value,
						'module' => $this->id,
						'name' => $name,
						'id2' => $this->id.'-'.$term->taxonomy,
						'elem' => $term->taxonomy.'-'.$term->term_id
					);
				}
			}
		}

		echo json_encode($suggestions);
		die();
	}

	/**
	 * Register taxonomies to sidebar post type
	 * @return void 
	 */
	public function add_taxonomies_to_sidebar() {
		foreach($this->_get_content() as $tax) {
			register_taxonomy_for_object_type( $tax->name, ContentAwareSidebars::TYPE_SIDEBAR );
		}
	}


	/**
	 * Remove taxonomy shortcuts from menu and standard meta boxes.
	 * @return void
	 */
	public function clear_admin_menu() {
		if(current_user_can('edit_theme_options')) {
			foreach($this->_get_content() as $tax) {
				remove_submenu_page('edit.php?post_type='.ContentAwareSidebars::TYPE_SIDEBAR,'edit-tags.php?taxonomy='.$tax->name.'&amp;post_type='.ContentAwareSidebars::TYPE_SIDEBAR);
			}
		} else {
			// Remove those taxonomies left in the menu when it should be hidden
			foreach($this->_get_content() as $tax) {
				remove_menu_page('edit-tags.php?taxonomy='.$tax->name.'&amp;post_type='.ContentAwareSidebars::TYPE_SIDEBAR);
			}	
		}
	}
	
	/**
	 * Auto-select children of selected ancestor
	 * @param  int $term_id  
	 * @param  int $tt_id    
	 * @param  string $taxonomy 
	 * @return void           
	 */
	public function term_ancestry_check($term_id, $tt_id, $taxonomy) {
		
		if(is_taxonomy_hierarchical($taxonomy)) {
			$term = get_term($term_id, $taxonomy);

			if($term->parent != '0') {	
				// Get sidebars with term ancestor wanting to auto-select term
				$posts = new WP_Query(array(
					'post_type'					=> ContentAwareSidebars::TYPE_SIDEBAR,
					'meta_query'				=> array(
						array(
							'key'				=> ContentAwareSidebars::PREFIX . $this->id,
							'value'				=> ContentAwareSidebars::PREFIX.'sub_' . $taxonomy,
							'compare'			=> '='
						)
					),
					'tax_query' => array(
						array(
							'taxonomy'			=> $taxonomy,
							'field'				=> 'id',
							'terms'				=> get_ancestors($term_id, $taxonomy),
							'include_children'	=> false
						)
					)
				));
				if($posts) {
					foreach($posts as $post) {
						wp_set_post_terms($post->ID, $term_id, $taxonomy, true);
					}
				}
			}
		}
	}

}
