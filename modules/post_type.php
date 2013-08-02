<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
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
	
	/**
	 * Registered public post types
	 * @var array
	 */
	private $post_type_objects;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->id = 'post_types';
		$this->name = __('Post Types',ContentAwareSidebars::DOMAIN);
		
		add_action('transition_post_status', array(&$this,'post_ancestry_check'),10,3);

		add_action('wp_ajax_cas-autocomplete-'.$this->id, array(&$this,'ajax_content_search'));

	}
	
	/**
	 * Get registered post types
	 * @return array 
	 */
	protected function _get_content() {
		if (empty($this->post_type_objects)) {
			// List public post types
			foreach (get_post_types(array('public' => true), 'objects') as $post_type) {
				$this->post_type_objects[$post_type->name] = $post_type;
			}
		}
		return $this->post_type_objects;
	}
	
	/**
	 * Determine if content is relevant
	 * @return boolean 
	 */
	public function is_content() {
		return ((is_singular() || is_home()) && !is_front_page()) || is_post_type_archive();
	}
	
	/**
	 * Query where
	 * @global string $post_type
	 * @return string 
	 */
	public function db_where() {
		if(is_singular()) {
			return "(".$this->id.".meta_value IS NULL OR ".$this->id.".meta_value IN('".get_post_type()."','".get_the_ID()."'))";
		}
		global $post_type;
		
		// Home has post as default post type
		if(!$post_type) $post_type = 'post';
		return "(".$this->id.".meta_value IS NULL OR ".$this->id.".meta_value = '".$post_type."')";
	}

	/**
	 * Meta box content
	 * @global object $post
	 * @global object $wpdb
	 * @return void 
	 */
	public function meta_box_content() {
		global $post, $wpdb;

		foreach ($this->_get_content() as $post_type) {

			echo '<h4><a href="#">' . $post_type->label . '</a></h4>'."\n";
			echo '<div class="cas-rule-content" id="cas-' . $this->id . '-' . $post_type->name . '">'."\n";
			$meta = get_post_meta($post->ID, ContentAwareSidebars::PREFIX . $this->id, false);
			$current = $meta != '' ? $meta : array();

			$exclude = array();
			if ($post_type->name == 'page' && 'page' == get_option('show_on_front')) {
				$exclude[] = get_option('page_on_front');
				$exclude[] = get_option('page_for_posts');
			}

			$number_of_posts = (int)$wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = '{$post_type->name}' AND post_status IN('publish','private','future')");

			echo '<div style="min-height:100%;">'."\n";

			if($post_type->hierarchical) {
				echo '<p>' . "\n";
				echo '<label><input type="checkbox" name="'.$this->id.'[]" value="'.ContentAwareSidebars::PREFIX.'sub_' . $post_type->name . '"' . checked(in_array(ContentAwareSidebars::PREFIX."sub_" . $post_type->name, $current), true, false) . ' /> ' . __('Automatically select new children of a selected ancestor', ContentAwareSidebars::DOMAIN) . '</label>' . "\n";
				echo '</p>' . "\n";
			}
			
			//WP3.1.4 does not support $post_type->labels->all_items
			echo '<p>' . "\n";
			echo '<label><input class="cas-chk-all" type="checkbox" name="'.$this->id.'[]" value="' . $post_type->name . '"' . checked(in_array($post_type->name, $current), true, false) . ' /> ' . sprintf(__('Show with All %s', ContentAwareSidebars::DOMAIN), $post_type->label) . '</label>' . "\n";
			echo '</p>' . "\n";

			if (!$number_of_posts) {
				echo '<p>' . __('No items.') . '</p>';
			} else {

				//WP3.1 does not support (array) as post_status
				$posts = get_posts(array(
					'posts_per_page'	=> 20,
					'post_type'	=> $post_type->name,
					'post_status'	=> 'publish,private,future',
					'exclude'	=> array_merge($exclude,$current),
				));
				if(!empty($current)) {
					$selected = get_posts(array(
						'include'	=> $current,
						'post_type'	=> $post_type->name
					));
				} else {
					$selected = array();
				}

				if($number_of_posts > 20) {
					echo _x('Search','verb',ContentAwareSidebars::DOMAIN).' <input class="cas-autocomplete-' . $this->id . ' cas-autocomplete" id="cas-autocomplete-' . $this->id . '-' . $post_type->name . '" type="text" name="cas-autocomplete" value="" placeholder="'.$post_type->label.'" />'."\n";
				}

				echo '<ul id="cas-list-' . $this->id . '-' . $post_type->name . '" class="cas-contentlist categorychecklist form-no-clear">'."\n";
				$this->post_checklist($post->ID, $post_type, array_merge($selected,$posts), $current);
				echo '</ul>'."\n";
				
			}

			echo '</div>';
			echo '</div>';
		}
	}

	/**
	 * Show posts from a specific post type
	 * @param  int     $post_id      
	 * @param  object  $post_type    
	 * @param  array   $posts        
	 * @param  array   $selected_ids 
	 * @return void                
	 */
	private function post_checklist($post_id = 0, $post_type, $posts, $selected_ids) {

		$walker = new CAS_Walker_Checklist('post',array ('parent' => 'post_parent', 'id' => 'ID'));

		$args = array(
			'post_type'	=> $post_type,
			'selected_cats' => $post_id ? $selected_ids : array()
		);
		
		$checked_posts = array();
		
		foreach( $posts as $key => $value ) {
			if (in_array($posts[$key]->ID, $args['selected_cats'])) {
				$checked_posts[] = $posts[$key];
				unset($posts[$key]);
			}
		}
		
		//Put checked posts on top
		echo call_user_func_array(array(&$walker, 'walk'), array($checked_posts, 0, $args));
		// Then the rest of them
		echo call_user_func_array(array(&$walker, 'walk'), array($posts, 0, $args));
	}

	/**
	 * Get posts with AJAX search
	 * @return void
	 */
	public function ajax_content_search() {
		
		// Verify request
		check_ajax_referer(basename('content-aware-sidebars.php'),'nonce');
	
		$suggestions = array();
		if ( preg_match('/cas-autocomplete-'.$this->id.'-([a-zA-Z_-]*\b)/', $_REQUEST['type'], $matches) ) {
			if(get_post_type_object( $matches[1] )) {
				$exclude = array();
				if ($matches[1] == 'page' && 'page' == get_option('show_on_front')) {
					$exclude[] = get_option('page_on_front');
					$exclude[] = get_option('page_for_posts');
				}
				$posts = get_posts(array(
					'posts_per_page' => 10,
					'post_type' => $matches[1],
					's' => $_REQUEST['term'],
					'exclude' => $exclude,
					'orderby' => 'title',
					'order' => 'ASC',
					'post_status'	=> 'publish,private,future'
				));
				foreach($posts as $post) {
					$suggestions[] = array(
						'label' => $post->post_title,
						'value' => $post->post_title,
						'id'	=> $post->ID,
						'module' => $this->id,
						'name' => $this->id,
						'id2' => $this->id.'-'.$post->post_type,
						'elem' => $post->post_type.'-'.$post->ID
					);
				}
			}
		}

		echo json_encode($suggestions);
		die();
	}

	
	/**
	 * Automatically select child of selected parent
	 * @param  string $new_status 
	 * @param  string $old_status 
	 * @param  object $post       
	 * @return void 
	 */
	public function post_ancestry_check($new_status, $old_status, $post) {
		
		if($post->post_type != ContentAwareSidebars::TYPE_SIDEBAR) {
			
			$status = array('publish','private','future');
			// Only new posts are relevant
			if(!in_array($old_status,$status) && in_array($new_status,$status)) {
				
				$post_type = get_post_type_object($post->post_type);
				if($post_type->hierarchical && $post_type->public && $post->parent != '0') {
				
					// Get sidebars with post ancestor wanting to auto-select post
					$sidebars = new WP_Query(array(
						'post_type'				=> ContentAwareSidebars::TYPE_SIDEBAR,
						'meta_query'			=> array(
							'relation'			=> 'AND',
							array(
								'key'			=> ContentAwareSidebars::PREFIX . $this->id,
								'value'			=> ContentAwareSidebars::PREFIX.'sub_' . $post->post_type,
								'compare'		=> '='
							),
							array(
								'key'			=> ContentAwareSidebars::PREFIX . $this->id,
								'value'			=> get_ancestors($post->ID,$post->post_type),
								'type'			=> 'numeric',
								'compare'		=> 'IN'
							)
						)
					));
					if($sidebars) {
						foreach($sidebars as $sidebar) {
							add_post_meta($sidebar->ID, ContentAwareSidebars::PREFIX.$this->id, $post->ID);
						}
					}
				}
			}	
		}	
	}

}
