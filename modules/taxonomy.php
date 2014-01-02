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
		parent::__construct('taxonomies',__('Taxonomies',ContentAwareSidebars::DOMAIN),true);
		$this->type_display = true;
		$this->searchable = true;

		//add_action('init', array(&$this,'add_taxonomies_to_sidebar'),100);
		add_action('created_term', array(&$this,'term_ancestry_check'),10,3);

		add_action('wp_ajax_cas-autocomplete-'.$this->id, array(&$this,'ajax_content_search'));
		
	}
	
	/**
	 * Determine if content is relevant
	 * @return boolean 
	 */
	public function in_context() {		
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
	 * Get data from context
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.0
	 * @return array
	 */
	public function get_context_data() {
		// for($i = 0; $i < 5000; $i++) {
		// 	add_post_meta(rand(1,5000),'_bogus_key','bogus-'.rand(10000,99999),true);
		// }
		

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
	protected function _get_content($args = array()) {
		$args = wp_parse_args($args, array(
			'include' => '',
			'taxonomy' => 'category',
			'number' => 20,
			'orderby' => 'name',
			'order' => 'ASC',
			'offset' => 0
		));
		extract($args);

		$terms = get_terms($taxonomy, array(
			'number' => $number,
			'hide_empty' => false,
			'include' => $include,
			'offset' => $offset,
			'orderby' => $orderby,
			'order' => $order
		));
		$total_items = wp_count_terms($taxonomy,array('hide_empty'=>false));
		$per_page = $number;
		$this->pagination = array(
			'paged' => $offset+1,
			'per_page' => $per_page,
			'total_pages' => $total_items/$per_page,
			'total_items' => $total_items
		);

		
		return $terms;
	}

	protected function _get_taxonomies() {
		// List public taxonomies
		if (empty($this->taxonomy_objects)) {
			foreach (get_taxonomies(array('public' => true), 'objects') as $tax) {
				$this->taxonomy_objects[$tax->name] = $tax;
			}
		}
		return $this->taxonomy_objects;		
	}

	public function print_group_data($post_id) {
		$ids = array_flip((array)get_post_custom_values(ContentAwareSidebars::PREFIX . $this->id, $post_id));

		foreach($this->_get_taxonomies() as $taxonomy) {

			$posts = wp_get_object_terms( $post_id, $taxonomy->name);
			if($posts || isset($ids[$taxonomy->name]) || isset($ids[ContentAwareSidebars::PREFIX.'sub_' . $taxonomy->name])) {
				echo '<div class="cas-condition cas-condition-'.$this->id.'-'.$taxonomy->name.'">';
				echo '<strong>'.$taxonomy->label.'</strong>';
				echo '<ul>';
				if(isset($ids[ContentAwareSidebars::PREFIX.'sub_' . $taxonomy->name])) {
					echo '<li class=""><label><input type="checkbox" name="taxonomies[]" value="'.ContentAwareSidebars::PREFIX.'sub_' . $taxonomy->name . '" checked="checked" /> ' . __('Automatically select new children of a selected ancestor', ContentAwareSidebars::DOMAIN) . '</label></li>' . "\n";
				}
				if(isset($ids[$taxonomy->name])) {
					echo '<li class=""><label><input type="checkbox" name="taxonomies[]" value="'.$taxonomy->name.'" checked="checked" /> '.$taxonomy->labels->all_items.'</label></li>' . "\n";
				}
				if($posts) {
					$selected = wp_get_object_terms($post_id, $taxonomy->name, array('fields' => ($taxonomy->hierarchical ? 'ids' : 'slugs')));
					echo $this->term_checklist($taxonomy, $posts, $selected);
				}
				echo '</ul>';
				echo '</div>';	
			}
		}

	}

	/**
	 * Meta box content
	 * @global object $post
	 * @return void 
	 */
	public function meta_box_content() {
		global $post;

		foreach ($this->_get_taxonomies() as $taxonomy) {

			echo '<li class="control-section accordion-section">';		
			echo '<h3 class="accordion-section-title" title="'.$taxonomy->label.'" tabindex="0">'.$taxonomy->label.'</h3>'."\n";
			echo '<div class="accordion-section-content cas-rule-content" data-cas-module="'.$this->id.'" id="cas-' . $this->id . '-' . $taxonomy->name . '">';

			$terms = $this->_get_content(array('taxonomy' => $taxonomy->name));
			

			if($taxonomy->hierarchical) {
				echo '<ul><li>' . "\n";
				echo '<label><input type="checkbox" name="taxonomies[]" value="'.ContentAwareSidebars::PREFIX.'sub_' . $taxonomy->name . '" /> ' . __('Automatically select new children of a selected ancestor', ContentAwareSidebars::DOMAIN) . '</label>' . "\n";
				echo '</li></ul>' . "\n";
			}
			echo '<ul><li>' . "\n";
			echo '<label><input class="cas-chk-all" type="checkbox" name="taxonomies[]" value="' . $taxonomy->name . '" /> ' . sprintf(__('Display with %s', ContentAwareSidebars::DOMAIN), $taxonomy->labels->all_items) . '</label>' . "\n";
			echo '</li></ul>' . "\n";
	
			if (!$terms) {
				echo '<p>' . __('No items.') . '</p>';
			} else {

				//No need to use two queries before knowing there are items
				if(count($terms) < 20) {
					$popular_terms = $terms;
				} else {
					$popular_terms = $this->_get_content(array('taxonomy' => $taxonomy->name, 'orderby' => 'count', 'order' => 'DESC'));
				}
				

				$tabs = array();
				$tabs['popular'] = array(
					'title' => __('Most Used'),
					'status' => true,
					'content' => $this->term_checklist($taxonomy, $popular_terms, array(), false)
				);
				$tabs['all'] = array(
					'title' => __('View All'),
					'status' => false,
					'content' => $this->term_checklist($taxonomy, $terms, array(), true)
				);
				if($this->searchable) {
					$tabs['search'] = array(
						'title' => __('Search'),
						'status' => false,
						'content' => '',
						'content_before' => '<p><input class="cas-autocomplete-' . $this->id . ' cas-autocomplete quick-search" id="cas-autocomplete-' . $this->id . '-' . $taxonomy->name . '" type="search" name="cas-autocomplete" value="" placeholder="'.__('Search').'" autocomplete="off" /><span class="spinner"></span></p>'
					);
				}

				echo $this->create_tab_panels($this->id . '-' . $taxonomy->name,$tabs);
				
			}

			echo '<p class="button-controls">';

			echo '<span class="add-to-group"><input data-cas-condition="'.$this->id.'-'.$taxonomy->name.'" type="button" name="" id="cas-' . $this->id . '-' . $taxonomy->name . '-add" class="js-cas-condition-add button" value="'.__('Add to Group',ContentAwareSidebars::DOMAIN).'"></span>';

			echo '</p>';

			echo '</div>'."\n";
			echo '</li>';
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
	private function term_checklist($taxonomy, $terms, $selected_terms = array(), $pagination = false) {

		$walker = new CAS_Walker_Checklist('category',array('parent' => 'parent', 'id' => 'term_id'));

		$args = array(
			'taxonomy'	=> $taxonomy,
			'selected_terms' => $selected_terms
		);

		$return = call_user_func_array(array(&$walker, 'walk'), array($terms, 0, $args));

		if($pagination) {
			$paginate = paginate_links(array(
				'base'         => admin_url( 'admin-ajax.php').'%_%',
				'format'       => '?paged=%#%',
				'total'        => $this->pagination['total_pages'],
				'current'      => $this->pagination['paged'],
				'mid_size'     => 2,
				'end_size'     => 1,
				'prev_next'    => true,
				'prev_text'    => 'prev',
				'next_text'    => 'next',
				'add_args'     => array('item_object'=>$taxonomy->name),
			));
			$return = $paginate.$return.$paginate;
		}

		return $return;

	}

	public function ajax_get_content() {

		//validation
		$paged = (isset($_POST['paged']) ? $_POST['paged'] : 1)-1;
		$search = isset($_POST['search']) ? $_POST['search'] : false;
		$taxonomy = get_taxonomy($_POST['item_object']);

		$posts = $this->_get_content(array('taxonomy' => $_POST['item_object'], 'orderby' => 'name', 'order' => 'ASC', 'offset' => $paged));
		$response = $this->term_checklist($taxonomy, $posts, array(), true);
		//$response = $_POST['paged'];
		echo json_encode($response);
		die();
	}

	/**
	 * Get terms with AJAX search
	 * @return void 
	 */
	public function ajax_content_search() {
		
		if(!isset($_POST['sidebar_id'])) {
			die(-1);
		}
		
		// Verify request
		check_ajax_referer(ContentAwareSidebars::SIDEBAR_PREFIX.$_POST['sidebar_id'],'nonce');
	
		$suggestions = array();
		if ( preg_match('/cas-autocomplete-'.$this->id.'-([a-zA-Z_-]*\b)/', $_REQUEST['type'], $matches) ) {
			if(($taxonomy = get_taxonomy( $matches[1] ))) {
				$terms = get_terms($taxonomy->name, array(
					'number' => 10,
					'hide_empty' => false,
					'search' => $_REQUEST['q']
				));
				$name = ($taxonomy->name == 'category' ? 'post_category' : 'tax_input['.$matches[1].']'); 
				$value = ($taxonomy->hierarchical ? 'term_id' : 'slug');
				foreach($terms as $term) {
					$suggestions[] = array(
						'label' => $term->name,
						'value' => $term->$value,
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

	public function save_data($post_id) {
		parent::save_data($post_id);

		$tax_input = isset($_POST['tax_input']) ? $_POST['tax_input'] : array();

		//Save terms
		//Loop through each public taxonomy
		foreach($this->_get_taxonomies() as $taxonomy) {

			if (current_user_can($taxonomy->cap->assign_terms) ) {

				//If no terms, maybe delete old ones
				if(!isset($tax_input[$taxonomy->name])) {
					$terms = null;
				} else {
					$terms = $tax_input[$taxonomy->name];

					//Hierarchical taxonomies use ids instead of slugs
					//see http://codex.wordpress.org/Function_Reference/wp_set_post_terms
					if($taxonomy->hierarchical) {
						$terms = array_unique(array_map('intval', $terms));
					}						
				}

				wp_set_object_terms( $post_id, $terms, $taxonomy->name );					
			}			

		}

	}

	/**
	 * Register taxonomies to sidebar post type
	 * @return void 
	 */
	public function add_taxonomies_to_sidebar() {
		foreach($this->_get_taxonomies() as $tax) {
			register_taxonomy_for_object_type( $tax->name, ContentAwareSidebars::TYPE_SIDEBAR );
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
					'post_type'					=> ContentAwareSidebars::TYPE_CONDITION_GROUP,
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
