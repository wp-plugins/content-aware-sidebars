<?php
/**
 * @package Content Aware Sidebars
 */
/*
Plugin Name: Content Aware Sidebars
Plugin URI: http://www.intox.dk/
Description: Manage and show sidebars according to the content being viewed.
Version: 0.8.1
Author: Joachim Jensen
Author URI: http://www.intox.dk/
Text Domain: content-aware-sidebars
Domain Path: /lang/
License: GPL2

    Copyright 2011  Joachim Jensen  (email : jv@intox.dk)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/
class ContentAwareSidebars {
	
	const db_version		= '0.8';
	const prefix			= '_cas_';
	
	protected $metadata		= array();
	protected $post_types		= array();
	protected $post_type_objects	= array();
	protected $taxonomies		= array();
	protected $taxonomy_objects	= array();
	protected $sidebar_cache	= array();

	/**
	 *
	 * Constructor
	 *
	 */
	public function __construct() {
		
		$this->_load_dependencies();
		
		add_filter('wp',					array(&$this,'replace_sidebar'));
		add_filter('request',					array(&$this,'admin_column_orderby'));
		add_filter('default_hidden_meta_boxes',			array(&$this,'change_default_hidden'),10,2);	
		add_filter('manage_edit-sidebar_columns',		array(&$this,'admin_column_headers'));
		add_filter('manage_edit-sidebar_sortable_columns',	array(&$this,'admin_column_sortable_headers'));
		add_filter('manage_posts_custom_column',		array(&$this,'admin_column_rows'),10,3);
		add_filter('post_row_actions',				array(&$this,'sidebar_row_actions'),10,2);
		add_filter('post_updated_messages', 			array(&$this,'sidebar_updated_messages'));
			
		add_action('init',					array(&$this,'init_sidebar_type'),50);
		add_action('widgets_init',				array(&$this,'create_sidebars'));
		add_action('add_meta_boxes_sidebar',			array(&$this,'create_meta_boxes'));
		add_action('admin_init',				array(&$this,'prepare_admin_scripts_styles'));
		add_action('admin_menu',				array(&$this,'clear_admin_menu'));
		add_action('admin_print_scripts-edit.php',		array(&$this,'load_admin_scripts'));
		add_action('admin_print_scripts-post-new.php',		array(&$this,'load_admin_scripts'));
		add_action('admin_print_scripts-post.php',		array(&$this,'load_admin_scripts'));
		add_action('save_post', 				array(&$this,'save_post'));
		add_action('delete_post',				array(&$this,'remove_sidebar_widgets'));
		add_action('wp_loaded',					array(&$this,'db_update'));
		
		register_activation_hook(__FILE__,			array(&$this,'plugin_activation'));
		register_deactivation_hook(__FILE__,			array(&$this,'plugin_deactivation'));
		
	}
	
	/**
	 *
	 * Create post meta fields
	 *
	 */
	private function _init_metadata() {
		global $post, $wp_registered_sidebars, $wpdb;

		// List of sidebars
		$sidebar_list = array();
		foreach($wp_registered_sidebars as $sidebar) {
			if(isset($post) && $sidebar['id'] != 'ca-sidebar-'.$post->ID)
				$sidebar_list[$sidebar['id']] = $sidebar['name'];
		}
		
		// List of authors
		$author_list = array();
		foreach($wpdb->get_results("SELECT ID, display_name FROM $wpdb->users ORDER BY ID ASC") as $user) {
			$author_list[$user->ID] = $user->display_name;
		}
		
		// Meta fields
		$this->metadata = array(
			'post_types'	=> array(
				'name'	=> __('Post Types', 'content-aware-sidebars'),
				'id'	=> 'post_types',
				'desc'	=> '',
				'val'	=> array(),
				'type'	=> 'checkbox',
				'list'	=> $this->post_types
			),
			'taxonomies'	=> array(
				'name'	=> __('Taxonomies', 'content-aware-sidebars'),
				'id'	=> 'taxonomies',
				'desc'	=> '',
				'val'	=> array(),
				'type'	=> 'checkbox',
				'list'	=> $this->taxonomies
			),
			'authors'	=> array(
				'name'	=> __('Authors', 'content-aware-sidebars'),
				'id'	=> 'authors',
				'desc'	=> '',
				'val'	=> array(),
				'type'	=> 'checkbox',
				'list'	=> $author_list
			),
			'page_templates'=> array(
				'name'	=> __('Page Templates', 'content-aware-sidebars'),
				'id'	=> 'page_templates',
				'desc'	=> '',
				'val'	=> array(),
				'type'	=> 'checkbox',
				'list'	=> array_flip(get_page_templates())
			),
			'static'	=> array(
				'name'	=> __('Static Pages', 'content-aware-sidebars'),
				'id'	=> 'static',
				'desc'	=> '',
				'val'	=> array(),
				'type'	=> 'checkbox',
				'list'	=> array(
					'front-page'	=> __('Front Page', 'content-aware-sidebars'),
					'search'	=> __('Search Results', 'content-aware-sidebars'),
					'404'		=> __('404 Page', 'content-aware-sidebars')
				)
			),
			'exposure'	=> array(
				'name'	=> __('Exposure', 'content-aware-sidebars'),
				'id'	=> 'exposure',
				'desc'	=> '',
				'val'	=> 1,
				'type'	=> 'select',
				'list'	=> array(
					 __('Singular', 'content-aware-sidebars'),
					 __('Singular & Archive', 'content-aware-sidebars'),
					 __('Archive', 'content-aware-sidebars')
				 )
			),
			'handle'	=> array(
				'name'	=> _x('Handle','option', 'content-aware-sidebars'),
				'id'	=> 'handle',
				'desc'	=> __('Replace host sidebar, merge with it or add sidebar manually.', 'content-aware-sidebars'),
				'val'	=> 0,
				'type'	=> 'select',
				'list'	=> array(
					__('Replace', 'content-aware-sidebars'),
					__('Merge', 'content-aware-sidebars'),
					__('Manual', 'content-aware-sidebars')
				)
			),
			'host'		=> array(
				'name'	=> __('Host Sidebar', 'content-aware-sidebars'),
				'id'	=> 'host',
				'desc'	=> '',
				'val'	=> 'sidebar-1',
				'type'	=> 'select',
				'list'	=> $sidebar_list
			),
			'merge-pos'	=> array(
				'name'	=> __('Merge position', 'content-aware-sidebars'),
				'id'	=> 'merge-pos',
				'desc'	=> __('Place sidebar on top or bottom of host when merging.', 'content-aware-sidebars'),
				'val'	=> 1,
				'type'	=> 'select',
				'list'	=> array(
					__('Top', 'content-aware-sidebars'),
					__('Bottom', 'content-aware-sidebars')
				)
			)
		);
	}
	
	/**
	 *
	 * Custom Post Type: Sidebar
	 *
	 */
	public function init_sidebar_type() {
		
		load_plugin_textdomain('content-aware-sidebars', false, dirname( plugin_basename(__FILE__)).'/lang/');
		
		// List public post types
		foreach(get_post_types(array('public'=>true),'objects') as $post_type) {
			$this->post_types[$post_type->name] = $post_type->label;
			$this->post_type_objects[$post_type->name] = $post_type;
		}
		
		// List public taxonomies
		foreach(get_taxonomies(array('public'=>true),'objects') as $tax) {
			$this->taxonomies[$tax->name] = $tax->label;
			$this->taxonomy_objects[$tax->name] = $tax;
		}
		
		// Register the sidebar type
		register_post_type('sidebar',array(
			'labels'	=> array(
				'name'			=> __('Sidebars', 'content-aware-sidebars'),
				'singular_name'		=> __('Sidebar', 'content-aware-sidebars'),
				'add_new'		=> _x('Add New', 'sidebar', 'content-aware-sidebars'),
				'add_new_item'		=> __('Add New Sidebar', 'content-aware-sidebars'),
				'edit_item'		=> __('Edit Sidebar', 'content-aware-sidebars'),
				'new_item'		=> __('New Sidebar', 'content-aware-sidebars'),
				'all_items'		=> __('All Sidebars', 'content-aware-sidebars'),
				'view_item'		=> __('View Sidebar', 'content-aware-sidebars'),
				'search_items'		=> __('Search Sidebars', 'content-aware-sidebars'),
				'not_found'		=> __('No sidebars found', 'content-aware-sidebars'),
				'not_found_in_trash'	=> __('No sidebars found in Trash', 'content-aware-sidebars')
			),
			'show_ui'	=> true, 
			'query_var'	=> false,
			'rewrite'	=> false,
			'menu_position' => null,
			'supports'	=> array('title','excerpt','page-attributes'),
			'taxonomies'	=> array_keys($this->taxonomies),
			'menu_icon'	=> WP_PLUGIN_URL.'/'.plugin_basename(dirname(__FILE__)).'/icon-16.png'
		));
	}
	
	/**
	 *
	 * Create update messages
	 *
	 */
	public function sidebar_updated_messages( $messages ) {
		global $post;
		$messages['sidebar'] = array(
			0 => '',
			1 => sprintf(__('Sidebar updated. <a href="%s">Manage widgets</a>','content-aware-sidebars'),'widgets.php'),
			2 => '',
			3 => '',
			4 => __('Sidebar updated.','content-aware-sidebars'),
			5 => '',
			6 => sprintf(__('Sidebar published. <a href="%s">Manage widgets</a>','content-aware-sidebars'), 'widgets.php'),
			7 => __('Sidebar saved.','content-aware-sidebars'),
			8 => sprintf(__('Sidebar submitted. <a href="%s">Manage widgets</a>','content-aware-sidebars'),'widgets.php'),
			9 => sprintf(__('Sidebar scheduled for: <strong>%1$s</strong>. <a href="%2$s">Manage widgets</a>','content-aware-sidebars'),
			// translators: Publish box date format, see http://php.net/date
			date_i18n(__('M j, Y @ G:i'),strtotime($post->post_date)),'widgets.php'),
			10 => sprintf(__('Sidebar draft updated. <a href="%s">Manage widgets</a>','content-aware-sidebars'),'widgets.php'),
		);
		return $messages;
	}

	/**
	 *
	 * Remove taxonomy shortcuts from menu and standard meta boxes.
	 *
	 */
	public function clear_admin_menu() {
		foreach($this->taxonomies as $key => $value) {
			remove_submenu_page('edit.php?post_type=sidebar','edit-tags.php?taxonomy='.$key.'&amp;post_type=sidebar');
			remove_meta_box('tagsdiv-'.$key, 'sidebar', 'side');
			remove_meta_box($key.'div', 'sidebar', 'side');
		}
	}
	
	/**
	 *
	 * Add sidebars to widgets area
	 *
	 */
	public function create_sidebars() {

		//WP3.1 does not support (array) as post_status
		$posts = get_posts(array(
			'numberposts'	=> -1,
			'post_type'	=> 'sidebar',
			'post_status'	=> 'publish,private,future'
		));
		foreach($posts as $post) {
			register_sidebar( array(
				'name'		=> $post->post_title,
				'description'	=> $post->post_excerpt,
				'id'		=> 'ca-sidebar-'.$post->ID,
				'before_widget'	=> '<li id="%1$s" class="widget-container %2$s">',
				'after_widget'	=> '</li>',
				'before_title'	=> '<h3 class="widget-title">',
				'after_title'	=> '</h3>',
			));
		}		
	}
	
	/**
	 *
	 * Add admin column headers
	 *
	 */
	public function admin_column_headers($columns) {
		unset($columns['categories'],$columns['tags']);
		return array_merge(
			array_slice($columns, 0, 2, true),
			array(
				'exposure'	=> __('Exposure', 'content-aware-sidebars'),
				'handle'	=> _x('Handle','option', 'content-aware-sidebars'),
				'merge-pos'	=> __('Merge position', 'content-aware-sidebars')
			),
			$columns
		);
	}
		
	/**
	 *
	 * Make some columns sortable
	 *
	 */
	public function admin_column_sortable_headers($columns) {
		return array_merge(
			array(
				'exposure'	=> 'exposure',
				'handle'	=> 'handle',
				'merge-pos'	=> 'merge-pos'
			),
			$columns
		);
	}
	
	/**
	 *
	 * Manage custom column sorting
	 * 
	 */
	public function admin_column_orderby($vars) {
		if (isset($vars['orderby']) && in_array($vars['orderby'],array('exposure','handle','merge-pos'))) {
			$vars = array_merge( $vars, array(
				'meta_key'	=> self::prefix.$vars['orderby'],
				'orderby'	=> 'meta_value'
			) );
		}
		return $vars;
	}
	
	/**
	 *
	 * Add admin column rows
	 *
	 */
	public function admin_column_rows($column_name,$post_id) {
		
		// Load metadata
		if(!$this->metadata) $this->_init_metadata();
		
		$current = get_post_meta($post_id,self::prefix.$column_name,true);
		$current_from_list = $this->metadata[$column_name]['list'][$current];
		
		if($column_name == 'handle' && $current < 2) {		
			$host = get_post_meta($post_id,self::prefix.'host',true);			
			$current_from_list .= ": ".(isset($this->metadata['host']['list'][$host]) ? $this->metadata['host']['list'][$host] : "HOST NOT FOUND");		
		}		
		echo $current_from_list;
	}
	
	/**
	 *
	 * Remove widget when its sidebar is removed
	 *
	 */
	public function remove_sidebar_widgets($post_id) {
		
		// Authenticate and only continue on sidebar post type
		if(!current_user_can('delete_posts') || get_post_type($post_id) != 'sidebar')
			return;
		
		$id = 'ca-sidebar-'.$post_id;		
		
		//Get widgets
		$sidebars_widgets = wp_get_sidebars_widgets();
		
		// Check if sidebar exists in database
		if(!isset($sidebars_widgets[$id]))
			return;
		
		// Remove widgets settings from sidebar
		foreach($sidebars_widgets[$id] as $widget_id) {
			$widget_type = preg_replace( '/-[0-9]+$/', '', $widget_id );
			$widget_settings = get_option('widget_'.$widget_type);
			$widget_id = substr($widget_id,strpos($widget_id,'-')+1);
			if($widget_settings && isset($widget_settings[$widget_id])) {
				unset($widget_settings[$widget_id]);
				update_option('widget_'.$widget_type,$widget_settings);
			}
		}
		
		// Remove sidebar
		unset($sidebars_widgets[$id]);
		wp_set_sidebars_widgets($sidebars_widgets);
		
		
	}
	
	/**
	 *
	 * Add admin rows actions
	 *
	 */
	public function sidebar_row_actions($actions, $post) {
		if($post->post_type == 'sidebar' && $post->post_status != 'trash') {
			//View link is still there in WP3.1
			if(isset($actions['view'])) unset($actions['view']);
			return array_merge(
				array_slice($actions, 0, 2, true),
				array(
				      'mng_widgets' => 	'<a href="widgets.php" title="'.esc_html(__( 'Manage Widgets','content-aware-sidebars')).'">'.__( 'Manage Widgets','content-aware-sidebars').'</a>'
				),
				$actions
			);
		}
		return $actions;
	}

	/**
	 *
	 * Replace or merge a sidebar with content aware sidebars
	 * Handles content aware sidebars with hosts
	 *
	 */
	public function replace_sidebar() {
		global $_wp_sidebars_widgets;
		
		$posts = $this->get_sidebars();
		if(!$posts)
			return;
		
		foreach($posts as $post) {
			
			$id = 'ca-sidebar-'.$post->ID;
			
			// Check for correct handling and if host exist
			if ($post->handle == 2 || !isset($_wp_sidebars_widgets[$post->host]))
				continue;
			
			// Sidebar might not have any widgets. Get it anyway!
			if(!isset($_wp_sidebars_widgets[$id]))
				$_wp_sidebars_widgets[$id] = array();
			
			// If host has already been replaced, merge with it instead. Might change in future.
			if($post->handle || isset($handled_already[$post->host])) {
				if($post->merge_pos)
					$_wp_sidebars_widgets[$post->host] = array_merge($_wp_sidebars_widgets[$post->host],$_wp_sidebars_widgets[$id]);
				else
					$_wp_sidebars_widgets[$post->host] = array_merge($_wp_sidebars_widgets[$id],$_wp_sidebars_widgets[$post->host]);
			} else {
				$_wp_sidebars_widgets[$post->host] = $_wp_sidebars_widgets[$id];
				$handled_already[$post->host] = 1;
			}		
		}
	}
	
	/**
	 *
	 * Query sidebars according to content
	 * @return array|bool
	 *
	 */
	public function get_sidebars() {
		global $wpdb, $post_type, $post;
		
		// Return cache if present
		if(!empty($this->sidebar_cache)) {
			if($this->sidebar_cache[0] == false)
				return false;
			else
				return $this->sidebar_cache;
		}
		
		$joins = "";
		$where = "";
		
		// Front page
		if(is_front_page()) {
			
			$joins .= "LEFT JOIN $wpdb->postmeta static ON static.post_id = posts.ID AND static.meta_key = '".self::prefix."static' ";
			
			$where .= "(static.meta_value LIKE '%front-page%') AND ";
			$where .= "exposure.meta_value <= '1' AND ";	
		
		// Single content
		} elseif(is_singular()) {
			
			// Post Type
			$joins .= "LEFT JOIN $wpdb->postmeta post_types ON post_types.post_id = posts.ID AND post_types.meta_key = '".self::prefix."post_types' ";
			$where .= "((post_types.meta_value IS NULL OR (post_types.meta_value LIKE '%".serialize(get_post_type())."%' OR post_types.meta_value LIKE '%".serialize((string)get_the_ID())."%'))";
			$where2 = "AND (post_types.meta_value IS NOT NULL ";
	
			//Author
			$joins .= "LEFT JOIN $wpdb->postmeta authors ON authors.post_id = posts.ID AND authors.meta_key = '".self::prefix."authors' ";			
			$where .= " AND (authors.meta_value IS NULL OR (authors.meta_value LIKE '%authors%' OR authors.meta_value LIKE '%".serialize((string)$post->post_author)."%'))";
			$where2 .= "OR authors.meta_value IS NOT NULL ";
			
			//Page Template
			$template = get_post_meta(get_the_ID(),'_wp_page_template',true);
			if($template && $template != 'default') {
				$joins .= "LEFT JOIN $wpdb->postmeta page_templates ON page_templates.post_id = posts.ID AND page_templates.meta_key = '".self::prefix."page_templates' ";		
				$where .= " AND (page_templates.meta_value IS NULL OR (page_templates.meta_value LIKE '%page_templates%' OR page_templates.meta_value LIKE '%".$template."%'))";
				$where2 .= "OR page_templates.meta_value IS NOT NULL ";
			}
			
			$where2 .= ")";
			
			// Check if content has any taxonomies supported
			$post_taxonomies = get_object_taxonomies(get_post_type());
			if($post_taxonomies) {
				$post_terms = wp_get_object_terms(get_the_ID(),$post_taxonomies);
				// Check if content has any actual taxonomy terms
				if($post_terms) {
					$terms = array();
					$taxonomies = array();
					
					//Grab posts terms and make where rules for taxonomies.
					foreach($post_terms as $term) {
						$terms[] = $term->slug;
						if(!isset($taxonomies[$term->taxonomy])) {
							$where .= " OR post_tax.meta_value LIKE '%".$taxonomies[$term->taxonomy] = $term->taxonomy."%'";
						}
					}
					
					$joins .= "LEFT JOIN $wpdb->term_relationships term ON term.object_id = posts.ID ";
					$joins .= "LEFT JOIN $wpdb->term_taxonomy taxonomy ON taxonomy.term_taxonomy_id = term.term_taxonomy_id ";
					$joins .= "LEFT JOIN $wpdb->terms terms ON terms.term_id = taxonomy.term_id ";
					$joins .= "LEFT JOIN $wpdb->postmeta post_tax ON post_tax.post_id = posts.ID AND post_tax.meta_key = '".self::prefix."taxonomies'";
					
					$where .= " OR terms.slug IN('".implode("','",$terms)."')";
				}
			}
			
			
					
			$where .= $where2.") AND ";
			$where .= "exposure.meta_value <= '1' AND ";
			
		// Taxonomy archives
		} elseif(is_tax() || is_category() || is_tag()) {
			
			$term = get_queried_object();
			
			$joins .= "LEFT JOIN $wpdb->term_relationships term ON term.object_id = posts.ID ";
			$joins .= "LEFT JOIN $wpdb->term_taxonomy taxonomy ON taxonomy.term_taxonomy_id = term.term_taxonomy_id ";
			$joins .= "LEFT JOIN $wpdb->terms terms ON terms.term_id = taxonomy.term_id ";	
			$joins .= "LEFT JOIN $wpdb->postmeta post_tax ON post_tax.post_id = posts.ID AND post_tax.meta_key = '".self::prefix."taxonomies'";
				
			$where .= "(terms.slug = '$term->slug'";
			$where .= " OR post_tax.meta_value LIKE '%".serialize($term->taxonomy)."%'";
			$where .= ") AND ";
			$where .= "exposure.meta_value >= '1' AND ";		
			
		// Post Type archives
		} elseif(is_post_type_archive() || is_home()) {
			
			// Home has post as default post type
			if(!$post_type) $post_type = 'post';
			
			$joins .= "LEFT JOIN $wpdb->postmeta post_types ON post_types.post_id = posts.ID AND post_types.meta_key = '".self::prefix."post_types' ";
			$where .= "(post_types.meta_value LIKE '%".serialize($post_type)."%') AND ";
			$where .= "exposure.meta_value >= '1' AND ";		
		
		// Author archive
		} elseif(is_author()) {

			$joins .= "LEFT JOIN $wpdb->postmeta authors ON authors.post_id = posts.ID AND authors.meta_key = '".self::prefix."authors' ";	
			$where .= "(authors.meta_value LIKE '%authors%' OR authors.meta_value LIKE '%".serialize((string)get_query_var('author'))."%') AND ";
			$where .= "exposure.meta_value >= '1' AND ";	
		
		// Search
		} elseif(is_search()) {
			
			$joins .= "LEFT JOIN $wpdb->postmeta static ON static.post_id = posts.ID AND static.meta_key = '".self::prefix."static' ";		
			$where .= "(static.meta_value LIKE '%search%') AND ";
			$where .= "exposure.meta_value <= '1' AND ";	
			
		// 404
		} elseif(is_404()) {
			
			$joins .= "LEFT JOIN $wpdb->postmeta static ON static.post_id = posts.ID AND static.meta_key = '".self::prefix."static' ";		
			$where .= "(static.meta_value LIKE '%404%') AND ";
			$where .= "exposure.meta_value <= '1' AND ";
			
		}
		
		// Check if there are any rules for this type of content
		if(!$where)
			return false;
		
		// Show private sidebars or not
		if(current_user_can('read_private_posts'))
			$post_status = "IN('publish','private')";
		else
			$post_status = "= 'publish'";		
		$where .= "posts.post_status ".$post_status."";
		
		// Do query and cache it
		$this->sidebar_cache = $wpdb->get_results("
			SELECT
				posts.ID,
				handle.meta_value handle,
				host.meta_value host,
				merge_pos.meta_value merge_pos
			FROM $wpdb->posts posts
			LEFT JOIN $wpdb->postmeta handle
				ON handle.post_id = posts.ID
				AND handle.meta_key = '".self::prefix."handle'
			LEFT JOIN $wpdb->postmeta host
				ON host.post_id = posts.ID
				AND host.meta_key = '".self::prefix."host'
			LEFT JOIN $wpdb->postmeta merge_pos
				ON merge_pos.post_id = posts.ID
				AND merge_pos.meta_key = '".self::prefix."merge-pos'
			LEFT JOIN $wpdb->postmeta exposure
				ON exposure.post_id = posts.ID
				AND exposure.meta_key = '".self::prefix."exposure'
			$joins
			WHERE
				posts.post_type = 'sidebar' AND
				$where
			GROUP BY posts.ID
			ORDER BY posts.menu_order ASC, handle.meta_value DESC, posts.post_date DESC
		");
		
		// Return proper cache. If query was empty, tell the cache.
		return empty($this->sidebar_cache) ? $this->sidebar_cache[0] = false : $this->sidebar_cache;
		
	}
	
	/**
	 *
	 * Meta boxes for sidebar edit
	 *
	 */
	public function create_meta_boxes() {
		
		// Load metadata
		$this->_init_metadata();
		
		// Add boxes
		// Author Words
		add_meta_box(
			'ca-sidebar-author-words',
			__('Words from the author', 'content-aware-sidebars'),
			array(&$this,'meta_box_author_words'),
			'sidebar',
			'side',
			'high'
		);
		// Post Types
		foreach($this->post_type_objects as $post_type) {
			add_meta_box(
				'ca-sidebar-post-type-'.$post_type->name,
				$post_type->label,
				array(&$this,'meta_box_post_type'),
				'sidebar',
				'normal',
				'high',
				$post_type
			);
		}
		// Taxonomies
		foreach($this->taxonomy_objects as $tax) {
			add_meta_box(
				'ca-sidebar-tax-'.$tax->name,
				$tax->label,
				array(&$this,'meta_box_taxonomy'),
				'sidebar',
				'side',
				'default',
				$tax
			);
		}
		
		// Author and Page Template lists
		$checkbox_meta_boxes = array('authors','page_templates');
		foreach($checkbox_meta_boxes as $meta_box) {
			add_meta_box(
				'ca-sidebar-'.$meta_box,
				$this->metadata[$meta_box]['name'],
				array(&$this,'meta_box_checkboxes'),
				'sidebar',
				'side',
				'default',
				$meta_box
			);
		}
		
		// Options
		add_meta_box(
			'ca-sidebar',
			__('Options', 'content-aware-sidebars'),
			array(&$this,'meta_box_content'),
			'sidebar',
			'normal',
			'high'
		);
	}
	
		
	/**
	 *
	 * Hide some meta boxes from start
	 *
	 */
	public function change_default_hidden( $hidden, $screen ) {
		global $wp_version;
		
		//WordPress 3.3 has changed get_hidden_meta_boxes().
		if($wp_version < 3.3) {
			$condition = $screen->base == 'sidebar';
		} else {
			$condition = $screen->post_type == 'sidebar';
		}
		
		if ($condition && get_user_option( 'metaboxhidden_sidebar' ) === false) {
		
			$hidden_meta_boxes = array('postexcerpt','pageparentdiv','ca-sidebar-tax-post_format','ca-sidebar-post-type-attachment','ca-sidebar-authors');
			$hidden = array_merge($hidden,$hidden_meta_boxes);
		
			$user = wp_get_current_user();
			update_user_option( $user->ID, 'metaboxhidden_sidebar', $hidden, true );
		
		}
		return $hidden;
	}
	
	/**
	 *
	 * Options content
	 *
	 */
	public function meta_box_content() {
		$columns = array(
			'static',
			'exposure',
			'handle' => 'handle,host',
			'merge-pos'
		);
		
		echo '<table class="form-table">';
		foreach($columns as $key => $value) {
			
			echo '<tr><th scope="row">'.$this->metadata[is_numeric($key) ? $value : $key]['name'].'</th>';
			echo '<td>';
			$values = explode(',',$value);
			foreach($values as $val) {
				$this->_form_field($val);
			}
			echo '</td></tr>';
		}
		echo '</table>';
	}
	
	/**
	 *
	 * Author words content
	 *
	 */
	public function meta_box_author_words() {
		// Use nonce for verification
		wp_nonce_field(basename(__FILE__),'_ca-sidebar-nonce');
		?>
		<div style="overflow:hidden;">	
		<p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=KPZHE6A72LEN4&lc=US&item_name=WordPress%20Plugin%3a%20Content%20Aware%20Sidebars&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted"
		   target="_blank" title="PayPal - The safer, easier way to pay online!">
			<img align="right" border="0" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" width="147" height="47" alt="PayPal - The safer, easier way to pay online!">	
		</a></p>
		<p><?php _e('If you love this plugin, please consider donating.', 'content-aware-sidebars'); ?></p>
		<br />
		<p><?php printf(__('Remember to <a class="button" href="%1$s" target="_blank">rate</a> and <a class="button" href="%2$s" target="_blank">share</a> it too!', 'content-aware-sidebars'),
			'http://wordpress.org/extend/plugins/content-aware-sidebars/',
			'http://twitter.com/?status='.__('Check out Content Aware Sidebars for %23WordPress! :)','content-aware-sidebars').' http://tiny.cc/ca-sidebars'
			); ?></p>
		</div>
		<?php
	}
	
	public function meta_box_taxonomy($post, $box) {
		$meta = get_post_meta($post->ID, self::prefix.'taxonomies', true);
		$current = $meta != '' ? $meta : array();
		
		$taxonomy = $box['args'];
		
		$terms = get_terms($taxonomy->name, array('get' => 'all'));

		if (!$terms || is_wp_error($terms)) {
			echo '<p>'.__('No items.').'</p>';	
		} else {
		
?>
	<div id="taxonomy-<?php echo $taxonomy->name; ?>" class="categorydiv">
		<ul id="<?php echo $taxonomy->name; ?>-tabs" class="category-tabs">
			<li class="hide-if-no-js"><a href="#<?php echo $taxonomy->name; ?>-pop" tabindex="3"><?php _e( 'Most Used' ); ?></a></li>
			<li class="tabs"><a href="#<?php echo $taxonomy->name; ?>-all" tabindex="3"><?php _e('View All'); ?></a></li>
		</ul>

		<div id="<?php echo $taxonomy->name; ?>-pop" class="tabs-panel" style="display: none;height:inherit;max-height:200px;">
			<ul id="<?php echo $taxonomy->name; ?>checklist-pop" class="categorychecklist form-no-clear" >
				<?php $popular_ids = cas_popular_terms_checklist($taxonomy); ?>
			</ul>
		</div>
		
		<div id="<?php echo $taxonomy->name; ?>-all" class="tabs-panel" style="height:inherit;max-height:200px;">
			<input type="hidden" name="<?php echo ($taxonomy->name == "category" ? "post_category[]" : "tax_input[$taxonomy->name]"); ?>" value="0" />
			<ul id="<?php echo $taxonomy->name; ?>checklist" class="list:<?php echo $taxonomy->name?> categorychecklist form-no-clear">
				<?php cas_terms_checklist($post->ID, array('taxonomy' => $taxonomy,'popular_terms' => $popular_ids, 'terms' => $terms)) ?>
			</ul>
		</div>
	</div>
<?php
		}
	
		echo '<p style="padding:6px 0 4px;">'."\n";
		echo '<label><input type="checkbox" name="taxonomies[]" value="'.$taxonomy->name.'"'.(in_array($taxonomy->name,$current) ? ' checked="checked"' : '').' /> '.sprintf(__('Show with %s'),$taxonomy->labels->all_items).'</label>'."\n";
		echo '</p>'."\n";
	
	}
	
	public function meta_box_post_type($post, $box) {
		$meta = get_post_meta($post->ID, self::prefix.'post_types', true);
		$current = $meta != '' ? $meta : array();
		$post_type = $box['args'];
		
		$exclude = array();
		if($post_type->name == 'page' && 'page' == get_option( 'show_on_front')) {
			$exclude[] = get_option('page_on_front');
			$exclude[] = get_option('page_for_posts');
		}
		
		//WP3.1 does not support (array) as post_status
		$posts = get_posts(array(
			'numberposts'	=> -1,
			'post_type'	=> $post_type->name,
			'post_status'	=> 'publish,private,future',
			'exclude'	=> $exclude
		));
		
		if (!$posts || is_wp_error($posts)) {
			echo '<p>'.__('No items.').'</p>';	
		} else {
		
?>	
		<div id="posttype-<?php echo $post_type->name; ?>" class="categorydiv">
		<ul id="posttype-<?php echo $post_type->name; ?>-tabs" class="category-tabs">
			<li class="tabs"><a href="#<?php echo $post_type->name; ?>-all" tabindex="3"><?php _e('View All'); ?></a></li>
		</ul>		
		<div id="<?php echo $post_type->name; ?>-all" class="tabs-panel" style="height:inherit;max-height:200px;">
			<ul id="<?php echo $post_type->name; ?>checklist" class="list:<?php echo $post_type->name?> categorychecklist form-no-clear">
				<?php cas_posts_checklist($post->ID, array('post_type' => $post_type,'posts'=>$posts)); ?>
			</ul>
		</div>
		</div>	
<?php	
		}
		
		//WP3.1.4 does not support $post_type->labels->all_items
		echo '<p style="padding:6px 0 4px;">'."\n";
		echo '<label><input type="checkbox" name="post_types[]" value="'.$post_type->name.'"'.(in_array($post_type->name,$current) ? ' checked="checked"' : '').' /> '.sprintf(__('Show with All %s'),$post_type->label).'</label>'."\n";
		echo '</p>'."\n";

	}
	
	public function meta_box_checkboxes($post, $box) {
		$field = $box['args'];
		$meta = get_post_meta($post->ID, self::prefix.$field, true);
		$current = $meta != '' ? $meta : array();
		?>
		<div id="list-<?php echo $field; ?>" class="categorydiv">
			<ul id="<?php echo $field; ?>-tabs" class="category-tabs">
				<li class="tabs"><a href="#<?php echo $field; ?>-all" tabindex="3"><?php _e('View All'); ?></a></li>
			</ul>		
			<div id="<?php echo $field; ?>-all" class="tabs-panel" style="height:inherit;max-height:200px;">
				<ul id="authorlistchecklist" class="list:<?php echo $field; ?> categorychecklist form-no-clear">
					<?php
					foreach($this->metadata[$field]['list'] as $id => $name) {
						echo '<li><label><input type="checkbox" name="'.$field.'[]" value="'.$id.'"'.(in_array($id,$current) ? ' checked="checked"' : '').' /> '.$name.'</label></li>'."\n";
					}
					?>
				</ul>
			</div>
		</div>
		<p style="padding:6px 0 4px;">
			<label><input type="checkbox" name="<?php echo $field; ?>[]" value="<?php echo $field; ?>"<?php echo (in_array($field,$current) ? ' checked="checked"' : ''); ?> /> <?php _e('Show with All '.$this->metadata[$field]['name']); ?></label>
		</p>
<?php	
	}
	
	/**
	 *
	 * Create form field for metadata
	 *
	 */
	private function _form_field($setting) {
		global $post;
		
		$meta = get_post_meta($post->ID, self::prefix.$setting, true);
		$setting = $this->metadata[$setting];
		$current = $meta != '' ? $meta : $setting['val'];
		switch($setting['type']) {
			case 'select' :			
				echo '<select style="width:200px;" name="'.$setting['id'].'">'."\n";
				foreach($setting['list'] as $key => $value) {
					echo '<option value="'.$key.'"'.($key == $current ? ' selected="selected"' : '').'>'.$value.'</option>'."\n";
				}
				echo '</select>'."\n";
				break;
			case 'checkbox' :
				echo '<ul>'."\n";
				foreach($setting['list'] as $key => $value) {
					echo '<li><label><input type="checkbox" name="'.$setting['id'].'[]" value="'.$key.'"'.(in_array($key,$current) ? ' checked="checked"' : '').' /> '.$value.'</label></li>'."\n";
				}
				echo '</ul>'."\n";
				break;
			case 'text' :
			default :
				echo '<input style="width:200px;" type="text" name="'.$setting['id'].'" value="'.$current.'" />'."\n";
				break;
		}
	}
	
	/**
	 *
	 * Save meta values for post
	 *
	 */
	public function save_post($post_id) {
		
		// Save button pressed
		if(!isset($_POST['original_publish']) && !isset($_POST['save_post']))
			return;
		
		// Only sidebar type
		if(get_post_type($post_id) != 'sidebar')
			return;	
		
		// Verify nonce
		if (!check_admin_referer(basename(__FILE__),'_ca-sidebar-nonce'))
			return;
		
		// Check permissions
		if (!current_user_can('edit_post', $post_id))
			return;
		
		// Check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;
		
		// Load metadata
		$this->_init_metadata();
		
		// Update metadata
		foreach ($this->metadata as $field) {
			$old = get_post_meta($post_id, self::prefix.$field['id'], true);			
			$new = isset($_POST[$field['id']]) ? $_POST[$field['id']] : '';

			if ($new != '' && $new != $old) {
				update_post_meta($post_id, self::prefix.$field['id'], $new);		
			} elseif ($new == '' && $old != '') {
				delete_post_meta($post_id, self::prefix.$field['id'], $old);	
			}
		}
	}
	
	/**
	 *
	 * Database data update module
	 *
	 */
	public function db_update() {
		cas_run_db_update(self::db_version);
	}
	
	/**
	 *
	 * Load scripts and styles for administration
	 *
	 */
	public function load_admin_scripts() {
		global $pagenow;
		if($pagenow != 'edit.php') {
			wp_enqueue_script('cas_admin_script');
		}
		wp_enqueue_style('cas_admin_style');
	}
	
	/**
	 *
	 * Prepare scripts and styles for administration
	 *
	 */
	public function prepare_admin_scripts_styles() {
		wp_register_script('cas_admin_script', WP_PLUGIN_URL.'/'.plugin_basename(dirname(__FILE__)).'/js/cas_admin.js', array('jquery'), '0.1');
		wp_register_style('cas_admin_style', WP_PLUGIN_URL.'/'.plugin_basename(dirname(__FILE__)).'/style.css', false, '0.1');
	}
	
	/**
	 *
	 * Load dependencies
	 *
	 */
	private function _load_dependencies() {
		
		require_once('walker.php');
		require_once('update_db.php');
		
	}
		
	/**
	 *
	 * Flush rewrite rules on plugin activation
	 *
	 */
	public function plugin_activation() {
		$this->init_sidebar_type();
		flush_rewrite_rules();
	}
	
	/**
	 *
	 * Flush rewrite rules on plugin deactivation
	 *
	 */
	public function plugin_deactivation() {
		flush_rewrite_rules();
	}
	
}

// Launch plugin
global $ca_sidebars;
$ca_sidebars = new ContentAwareSidebars();

// Template function
function display_ca_sidebar($args = array()) {
	global $ca_sidebars, $_wp_sidebars_widgets;
	
	// Grab args or defaults
	$defaults = array (
		'include'	=> '',
 		'before'	=> '<div id="sidebar" class="widget-area"><ul class="xoxo">',
		'after'		=> '</ul></div>'
	);
	$args = wp_parse_args($args,$defaults);
	extract($args,EXTR_SKIP);
	
	// Get sidebars
	$posts = $ca_sidebars->get_sidebars();
	if(!$posts)
		return;
	
	// Handle include argument
	if(!empty($include)) {
		if(!is_array($include))
			$include = explode(',',$include);
		// Fast lookup
		$include = array_flip($include);
	}
	
	$i = $host = 0;	
	foreach($posts as $post) {

		$id = 'ca-sidebar-'.$post->ID;
			
		// Check for manual handling, if sidebar exists and if id should be included
		if ($post->handle != 2 || !isset($_wp_sidebars_widgets[$id]) || (!empty($include) && !isset($include[$post->ID])))
			continue;
		
		// Merge if more than one. First one is host.
		if($i > 0) {
			if($post->merge_pos)
				$_wp_sidebars_widgets[$host] = array_merge($_wp_sidebars_widgets[$host],$_wp_sidebars_widgets[$id]);
			else
				$_wp_sidebars_widgets[$host] = array_merge($_wp_sidebars_widgets[$id],$_wp_sidebars_widgets[$host]);
		} else {
			$host = $id;
		}
		$i++;
	}
	
	if ($host) {
		echo $before;
		dynamic_sidebar($host);
		echo $after;
	}
}