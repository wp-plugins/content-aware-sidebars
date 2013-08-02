<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */
/*
Plugin Name: Content Aware Sidebars
Plugin URI: http://www.intox.dk/
Description: Manage and show sidebars according to the content being viewed.
Version: 1.3.4
Author: Joachim Jensen, Intox Studio
Author URI: http://www.intox.dk/
Text Domain: content-aware-sidebars
Domain Path: /lang/
License: GPL2

	Copyright 2011-2013  Joachim Jensen  (email : jv@intox.dk)

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

final class ContentAwareSidebars {
	
	/**
	 * Database version for update module
	 */
	const DB_VERSION		= 1.1;

	/**
	 * Prefix for data (keys) stored in database
	 */
	const PREFIX			= '_cas_';

	/**
	 * Post Type for sidebars
	 */
	const TYPE_SIDEBAR		= 'sidebar';

	/**
	 * Language domain
	 */
	const DOMAIN 			= 'content-aware-sidebars';
	
	/**
	 * Plugin basename
	 * @var string
	 */
	private $basename;

	/**
	 * Sidebar metadata
	 * @var array
	 */
	private $metadata		= array();

	/**
	 * Sidebars retrieved from database
	 * @var array
	 */
	private $sidebar_cache	= array();

	/**
	 * Modules for specific content or cases
	 * @var array
	 */
	private $modules		= array();

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->basename = dirname(plugin_basename(__FILE__));
		
		$this->_load_dependencies();

		// WordPress Hooks. Somewhat ordered by execution
		
		//For administration
		if(is_admin()) {
			
			add_action('wp_loaded',											array(&$this,'db_update'));	
			add_action('admin_enqueue_scripts',								array(&$this,'load_admin_scripts'));
			add_action('delete_post',										array(&$this,'remove_sidebar_widgets'));
			add_action('save_post',											array(&$this,'save_post'));
			add_action('add_meta_boxes_'.self::TYPE_SIDEBAR,				array(&$this,'create_meta_boxes'));
			add_action('in_admin_header',									array(&$this,'clear_admin_menu'),99);

			add_filter('request',											array(&$this,'admin_column_orderby'));
			add_filter('default_hidden_meta_boxes',							array(&$this,'change_default_hidden'),10,2);	
			add_filter('manage_edit-'.self::TYPE_SIDEBAR.'_columns',		array(&$this,'admin_column_headers'),99);
			add_filter('manage_edit-'.self::TYPE_SIDEBAR.'_sortable_columns',array(&$this,'admin_column_sortable_headers'));
			add_filter('manage_posts_custom_column',						array(&$this,'admin_column_rows'),10,3);
			add_filter('post_row_actions',									array(&$this,'sidebar_row_actions'),10,2);
			add_filter('post_updated_messages',								array(&$this,'sidebar_updated_messages'));

		//For frontend
		} else {

			add_filter('wp',												array(&$this,'replace_sidebar'));

		}

		//For both	
		add_action('plugins_loaded',										array(&$this,'deploy_modules'));
		add_action('init',													array(&$this,'init_sidebar_type'),99);
		add_action('widgets_init',											array(&$this,'create_sidebars'),99);
		add_action('wp_loaded',												array(&$this,'update_sidebars'),99);
		
	}
	
	/**
	 * Deploy modules
	 * @return void 
	 */
	public function deploy_modules() {

		load_plugin_textdomain(self::DOMAIN, false, $this->basename.'/lang/');
		
		// List modules
		$modules = array(
			'static'		=> true,
			'post_type'		=> true,
			'author'		=> true,
			'page_template'	=> true,
			'taxonomy'		=> true,
			'url'			=> false,
			'bbpress'		=> function_exists('bbp_get_version'),	// bbPress
			'bp_member'		=> defined('BP_VERSION'),				// BuddyPress
			'polylang'		=> defined('POLYLANG_VERSION'),			// Polylang
			'qtranslate'	=> defined('QT_SUPPORTED_WP_VERSION'),	// qTranslate
			'transposh'		=> defined('TRANSPOSH_PLUGIN_VER'),		// Transposh Translation Filter
			'wpml'			=> class_exists('SitePress')			// WPML Multilingual Blog/CMS
		);
		$modules = apply_filters('cas-module-pre-deploy',$modules);
		
		// Forge modules
		foreach($modules as $name => $enabled) {
			if($enabled && include('modules/'.$name .'.php')) {
				$class = 'CASModule_'.$name;
				$this->modules[$name] = new $class; 
			}
		}
		
	}
	
	/**
	 * Create post meta fields
	 * @global array $wp_registered_sidebars 
	 * @return void 
	 */
	private function _init_metadata() {
		global $wp_registered_sidebars;

		// List of sidebars
		$sidebar_list = array();
		foreach($wp_registered_sidebars as $sidebar) {
			$sidebar_list[$sidebar['id']] = $sidebar['name'];
		}

		// Meta fields
		$this->metadata['exposure'] = array(
			'name'	=> __('Exposure', self::DOMAIN),
			'id'	=> 'exposure',
			'desc'	=> '',
			'val'	=> 1,
			'type'	=> 'select',
			'list'	=> array(
				 __('Singular', self::DOMAIN),
				 __('Singular & Archive', self::DOMAIN),
				 __('Archive', self::DOMAIN)
			)
		);
		$this->metadata['handle'] = array(
			'name'	=> _x('Handle','option', self::DOMAIN),
			'id'	=> 'handle',
			'desc'	=> __('Replace host sidebar, merge with it or add sidebar manually.', self::DOMAIN),
			'val'	=> 0,
			'type'	=> 'select',
			'list'	=> array(
				__('Replace', self::DOMAIN),
				__('Merge', self::DOMAIN),
				__('Manual', self::DOMAIN)
			)
		);
		$this->metadata['host']	= array(
			'name'	=> __('Host Sidebar', self::DOMAIN),
			'id'	=> 'host',
			'desc'	=> '',
			'val'	=> 'sidebar-1',
			'type'	=> 'select',
			'list'	=> $sidebar_list
		);
		$this->metadata['merge-pos'] = array(
			'name'	=> __('Merge position', self::DOMAIN),
			'id'	=> 'merge-pos',
			'desc'	=> __('Place sidebar on top or bottom of host when merging.', self::DOMAIN),
			'val'	=> 1,
			'type'	=> 'select',
			'list'	=> array(
				__('Top', self::DOMAIN),
				__('Bottom', self::DOMAIN)
			)
		);
		
	}
	
	/**
	 * Create sidebar post type
	 * @return void 
	 */
	public function init_sidebar_type() {
		
		// Register the sidebar type
		register_post_type(self::TYPE_SIDEBAR,array(
			'labels'	=> array(
				'name'					=> __('Sidebars', self::DOMAIN),
				'singular_name'			=> __('Sidebar', self::DOMAIN),
				'add_new'				=> _x('Add New', 'sidebar', self::DOMAIN),
				'add_new_item'			=> __('Add New Sidebar', self::DOMAIN),
				'edit_item'				=> __('Edit Sidebar', self::DOMAIN),
				'new_item'				=> __('New Sidebar', self::DOMAIN),
				'all_items'				=> __('All Sidebars', self::DOMAIN),
				'view_item'				=> __('View Sidebar', self::DOMAIN),
				'search_items'			=> __('Search Sidebars', self::DOMAIN),
				'not_found'				=> __('No sidebars found', self::DOMAIN),
				'not_found_in_trash'	=> __('No sidebars found in Trash', self::DOMAIN)
			),
			'capabilities' => array(
				'edit_post'				=> 'edit_theme_options',
				'read_post'				=> 'edit_theme_options',
				'delete_post'			=> 'edit_theme_options',
				'edit_posts'			=> 'edit_theme_options',
				'edit_others_posts'		=> 'edit_theme_options',
				'publish_posts'			=> 'edit_theme_options',
				'read_private_posts'	=> 'edit_theme_options'
			),
			'show_ui'					=> true,
			'show_in_menu'				=> true, //current_user_can('edit_theme_options'),
			'query_var'					=> false,
			'rewrite'					=> false,
			'menu_position'				=> null,
			'supports'					=> array('title','page-attributes'),
			'menu_icon'					=> WP_PLUGIN_URL.'/'.$this->basename.'/img/icon-16.png'
		));
	}
	
	/**
	 * Create update messages
	 * @global object $post
	 * @param  array $messages 
	 * @return array           
	 */
	public function sidebar_updated_messages( $messages ) {
		$messages[self::TYPE_SIDEBAR] = array(
			0 => '',
			1 => sprintf(__('Sidebar updated. <a href="%s">Manage widgets</a>',self::DOMAIN),'widgets.php'),
			2 => '',
			3 => '',
			4 => __('Sidebar updated.',self::DOMAIN),
			5 => '',
			6 => sprintf(__('Sidebar published. <a href="%s">Manage widgets</a>',self::DOMAIN), 'widgets.php'),
			7 => __('Sidebar saved.',self::DOMAIN),
			8 => sprintf(__('Sidebar submitted. <a href="%s">Manage widgets</a>',self::DOMAIN),'widgets.php'),
			9 => sprintf(__('Sidebar scheduled for: <strong>%1$s</strong>. <a href="%2$s">Manage widgets</a>',self::DOMAIN),
			// translators: Publish box date format, see http://php.net/date
			date_i18n(__('M j, Y @ G:i'),strtotime(get_the_ID())),'widgets.php'),
			10 => __('Sidebar draft updated.',self::DOMAIN),
		);
		return $messages;
	}

	/**
	 * Add sidebars to widgets area
	 * Triggered in widgets_init to save location for each theme
	 * @return void
	 */
	public function create_sidebars() {
		//WP3.1 does not support (array) as post_status
		$posts = get_posts(array(
			'numberposts'	=> -1,
			'post_type'		=> self::TYPE_SIDEBAR,
			'post_status'	=> 'publish,private,future'
		));

		//Register sidebars to add them to the list
		foreach($posts as $post) {
			register_sidebar( array(
				'name'			=> $post->post_title,
				'id'			=> 'ca-sidebar-'.$post->ID
			));
		}
	}
	
	/**
	 * Update the created sidebars with metadata
	 * @return void 
	 */
	public function update_sidebars() {
		
		//WP3.1 does not support (array) as post_status
		$posts = get_posts(array(
			'numberposts'	=> -1,
			'post_type'		=> self::TYPE_SIDEBAR,
			'post_status'	=> 'publish,private,future'
		));

		//Init metadata
		$this->_init_metadata();

		//Now reregister sidebars with proper content
		foreach($posts as $post) {
			
			$handle = get_post_meta($post->ID, self::PREFIX . 'handle', true);
			//$handle = $post->{self::PREFIX . 'handle'};
			$desc = $this->metadata['handle']['list'][$handle];

			if ($handle < 2) {
				$host = get_post_meta($post->ID, self::PREFIX . 'host', true);
				$desc .= ": " . (isset($this->metadata['host']['list'][$host]) ? $this->metadata['host']['list'][$host] :  __('Please update Host Sidebar', self::DOMAIN) );
			}
			register_sidebar( array(
				'name'			=> $post->post_title,
				'description'	=> $desc,
				'id'			=> 'ca-sidebar-'.$post->ID,
				'before_widget'	=> '<li id="%1$s" class="widget-container %2$s">',
				'after_widget'	=> '</li>',
				'before_title'	=> '<h3 class="widget-title">',
				'after_title'	=> '</h3>',
			));
		}
	}
	
	/**
	 * Add admin column headers
	 * @param  array $columns 
	 * @return array          
	 */
	public function admin_column_headers($columns) {
		// Totally discard current columns and rebuild
		return array(
			'cb'		=> $columns['cb'],
			'title'		=> $columns['title'],
			'exposure'	=> __('Exposure', self::DOMAIN),
			'handle'	=> _x('Handle','option', self::DOMAIN),
			'merge-pos'	=> __('Merge position', self::DOMAIN),
			'date'		=> $columns['date']
		);
	}
		
	/**
	 * Make some columns sortable
	 * @param  array $columns 
	 * @return array
	 */
	public function admin_column_sortable_headers($columns) {
		return array_merge(
			array(
				'exposure'	=> 'exposure',
				'handle'	=> 'handle',
				'merge-pos'	=> 'merge-pos'
			), $columns
		);
	}
	
	/**
	 * Manage custom column sorting
	 * @param  array $vars 
	 * @return array 
	 */
	public function admin_column_orderby($vars) {
		if (isset($vars['orderby']) && in_array($vars['orderby'], array('exposure', 'handle', 'merge-pos'))) {
			$vars = array_merge($vars, array(
				'meta_key' => self::PREFIX . $vars['orderby'],
				'orderby' => 'meta_value'
			));
		}
		return $vars;
	}
	
	/**
	 * Add admin column rows
	 * @param  string $column_name 
	 * @param  int $post_id
	 * @return void
	 */
	public function admin_column_rows($column_name, $post_id) {

		if (get_post_type($post_id) != self::TYPE_SIDEBAR)
			return;

		// Load metadata
		if (!$this->metadata)
			$this->_init_metadata();

		$current = get_post_meta($post_id, self::PREFIX . $column_name, true);
		$current_from_list = $this->metadata[$column_name]['list'][$current];

		if ($column_name == 'handle' && $current < 2) {
			$host = get_post_meta($post_id, self::PREFIX . 'host', true);
			$current_from_list .= ": " . (isset($this->metadata['host']['list'][$host]) ? $this->metadata['host']['list'][$host] : '<span style="color:red;">' . __('Please update Host Sidebar', self::DOMAIN) . '</span>');
		}
		echo $current_from_list;
	}
	
	/**
	 * Remove widget when its sidebar is removed
	 * @param  int $post_id 
	 * @return void
	 */
	public function remove_sidebar_widgets($post_id) {

		// Authenticate and only continue on sidebar post type
		if (!current_user_can('edit_theme_options') || get_post_type($post_id) != self::TYPE_SIDEBAR)
			return;

		$id = 'ca-sidebar-' . $post_id;

		//Get widgets
		$sidebars_widgets = wp_get_sidebars_widgets();

		// Check if sidebar exists in database
		if (!isset($sidebars_widgets[$id]))
			return;

		// Remove widgets settings from sidebar
		foreach ($sidebars_widgets[$id] as $widget_id) {
			$widget_type = preg_replace('/-[0-9]+$/', '', $widget_id);
			$widget_settings = get_option('widget_' . $widget_type);
			$widget_id = substr($widget_id, strpos($widget_id, '-') + 1);
			if ($widget_settings && isset($widget_settings[$widget_id])) {
				unset($widget_settings[$widget_id]);
				update_option('widget_' . $widget_type, $widget_settings);
			}
		}

		// Remove sidebar
		unset($sidebars_widgets[$id]);
		wp_set_sidebars_widgets($sidebars_widgets);
	}
	
	/**
	 * Add admin rows actions
	 * @param  array $actions
	 * @param  object $post
	 * @return array 
	 */
	public function sidebar_row_actions($actions, $post) {
		if ($post->post_type == self::TYPE_SIDEBAR && $post->post_status != 'trash') {

			//View link is still there in WP3.1
			unset($actions['view']);

			return array_merge(
				array_slice($actions, 0, 2, true), array(
					'mng_widgets' => '<a href="widgets.php" title="' . esc_html(__('Manage Widgets', self::DOMAIN)) . '">' . __('Manage Widgets', self::DOMAIN) . '</a>'
				), $actions
			);
		}
		return $actions;
	}

	/**
	 * Replace or merge a sidebar with content aware sidebars.
	 * Handles sidebars with hosts
	 * @global array $_wp_sidebars_widgets
	 * @return void 
	 */
	public function replace_sidebar() {
		global $_wp_sidebars_widgets;

		$posts = $this->get_sidebars();
		if (!$posts)
			return;

		foreach ($posts as $post) {

			// TODO
//			// Filter out sidebars with dependent content rules not present. Archives not yet decided.
//			if(!(is_archive() || (is_home() && !is_front_page()))) {
//				$continue = false;
//				$continue = apply_filters('cas_exclude_sidebar', $continue, $post, self::PREFIX);
//				if($continue)
//					continue;
//			}
//			
			$id = 'ca-sidebar-' . $post->ID;
			$host = get_post_meta($post->ID, self::PREFIX . 'host', true);

			// Check for correct handling and if host exist
			if ($post->handle == 2 || !isset($_wp_sidebars_widgets[$host]))
				continue;

			// Sidebar might not have any widgets. Get it anyway!
			if (!isset($_wp_sidebars_widgets[$id]))
				$_wp_sidebars_widgets[$id] = array();

			// If host has already been replaced, merge with it instead. Might change in future.
			if ($post->handle || isset($handled_already[$host])) {
				if (get_post_meta($post->ID, self::PREFIX . 'merge-pos', true))
					$_wp_sidebars_widgets[$host] = array_merge($_wp_sidebars_widgets[$host], $_wp_sidebars_widgets[$id]);
				else
					$_wp_sidebars_widgets[$host] = array_merge($_wp_sidebars_widgets[$id], $_wp_sidebars_widgets[$host]);
			} else {
				$_wp_sidebars_widgets[$host] = $_wp_sidebars_widgets[$id];
				$handled_already[$host] = 1;
			}
		}
	}
	
	/**
	 * Show manually handled content aware sidebars
	 * @global array $_wp_sidebars_widgets
	 * @param  string|array $args 
	 * @return void 
	 */
	public function manual_sidebar($args) {
		global $_wp_sidebars_widgets;

		// Grab args or defaults
		$args = wp_parse_args($args, array(
			'include' => '',
			'before' => '<div id="sidebar" class="widget-area"><ul class="xoxo">',
			'after' => '</ul></div>'
		));
		extract($args, EXTR_SKIP);

		// Get sidebars
		$posts = $this->get_sidebars();
		if (!$posts)
			return;

		// Handle include argument
		if (!empty($include)) {
			if (!is_array($include))
				$include = explode(',', $include);
			// Fast lookup
			$include = array_flip($include);
		}

		$i = $host = 0;
		foreach ($posts as $post) {

			$id = 'ca-sidebar-' . $post->ID;

			// Check for manual handling, if sidebar exists and if id should be included
			if ($post->handle != 2 || !isset($_wp_sidebars_widgets[$id]) || (!empty($include) && !isset($include[$post->ID])))
				continue;

			// Merge if more than one. First one is host.
			if ($i > 0) {
				if (get_post_meta($post->ID, self::PREFIX . 'merge-pos', true))
					$_wp_sidebars_widgets[$host] = array_merge($_wp_sidebars_widgets[$host], $_wp_sidebars_widgets[$id]);
				else
					$_wp_sidebars_widgets[$host] = array_merge($_wp_sidebars_widgets[$id], $_wp_sidebars_widgets[$host]);
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

	/**
	 * Query sidebars according to content
	 * @global type $wpdb
	 * @return array|boolean 
	 */
	public function get_sidebars() {
		global $wpdb;
		
		if(post_password_required())
			return false;
		
		// Return cache if present
		if(!empty($this->sidebar_cache)) {
			if($this->sidebar_cache[0] == false)
				return false;
			else
				return $this->sidebar_cache;
		}
		
		$joins = array();
		$where = array();
		$where2 = array();
		
		// Get rules
		foreach($this->modules as $module) {
			if(apply_filters("cas-is-content-".$module->get_id(), $module->is_content())) {
				$joins[] = apply_filters("cas-db-join-".$module->get_id(), $module->db_join());
				$where[] = apply_filters("cas-db-where-".$module->get_id(), $module->db_where());
				$where2[] = $module->db_where2();
			}
		}
		
		// Check if there are any rules for this type of content
		if(empty($where))
			return false;

		// Do query and cache it
		$wpdb->query('SET OPTION SQL_BIG_SELECTS = 1');
		$this->sidebar_cache = $wpdb->get_results("
			SELECT
				posts.ID,
				handle.meta_value handle
			FROM $wpdb->posts posts
			LEFT JOIN $wpdb->postmeta handle
				ON handle.post_id = posts.ID
				AND handle.meta_key = '".self::PREFIX."handle'
			LEFT JOIN $wpdb->postmeta exposure
				ON exposure.post_id = posts.ID
				AND exposure.meta_key = '".self::PREFIX."exposure'
			".implode(' ',$joins)."
			WHERE
				posts.post_type = '".self::TYPE_SIDEBAR."' AND
				exposure.meta_value ".(is_archive() || is_home() ? '>' : '<')."= '1' AND
				posts.post_status ".(current_user_can('read_private_posts') ? "IN('publish','private')" : "= 'publish'")." AND 
				(".implode(' AND ',$where).($where2 ? ' AND ('.implode(' OR ',$where2).')' : '').")
			GROUP BY posts.ID
			ORDER BY posts.menu_order ASC, handle.meta_value DESC, posts.post_date DESC
		");
		
		// Return proper cache. If query was empty, tell the cache.
		return (empty($this->sidebar_cache) ? $this->sidebar_cache[0] = false : $this->sidebar_cache);
		
	}

	/**
	 * Remove unwanted meta boxes
	 * @return void 
	 */
	public function clear_admin_menu() {
		global $wp_meta_boxes;

		$screen = get_current_screen();		

		// Post type not set on all pages in WP3.1
		if(!(isset($screen->post_type) && $screen->post_type == self::TYPE_SIDEBAR && $screen->base == 'post'))
			return;

		// Names of whitelisted meta boxes
		$whitelist = array(
			'cas-spread-words'	=> 'cas-spread-words',
			'cas-rules'			=> 'cas-rules',
			'cas-options'		=> 'cas-options',
			'submitdiv'			=> 'submitdiv',
			'pageparentdiv' 	=> 'pageparentdiv',
			'slugdiv'			=> 'slugdiv'
		);

		// Loop through context (normal,advanced,side)
		foreach($wp_meta_boxes[self::TYPE_SIDEBAR] as $context_k => $context_v) {
			// Loop through priority (high,core,default,low)
			foreach($context_v as $priority_k => $priority_v) {
				// Loop through boxes
				foreach($priority_v as $box_k => $box_v) {
					// If box is not whitelisted, remove it
					if(!in_array($box_k,$whitelist)) {
						$wp_meta_boxes[self::TYPE_SIDEBAR][$context_k][$priority_k][$box_k] = false;
						//unset($whitelist[$box_k]);
					}
				}
			}
		}
	}

	/**
	 * Meta boxes for sidebar edit
	 * @global object $post
	 * @return void 
	 */
	public function create_meta_boxes() {
		
		// Remove ability to set self to host
		if(get_the_ID())
			unset($this->metadata['host']['list']['ca-sidebar-'.get_the_ID()]);

		$boxes = array(
			//Content
			array(
				'id'		=> 'cas-rules',
				'title'		=> __('Content', self::DOMAIN),
				'callback'	=> 'meta_box_rules',
				'context'	=> 'normal',
				'priority'	=> 'high'
			),
			//Add group
			// array(
			// 	'id'		=> 'cas-new-group',
			// 	'title'		=> __('Rule Group', self::DOMAIN),
			// 	'callback'	=> 'meta_box_rules',
			// 	'context'	=> 'normal',
			// 	'priority'	=> 'high'
			// )
			//Options
			array(
				'id'		=> 'cas-options',
				'title'		=> __('Options', self::DOMAIN),
				'callback'	=> 'meta_box_options',
				'context'	=> 'side',
				'priority'	=> 'default'
			),
			//About
			array(
				'id'		=> 'cas-spread-words',
				'title'		=> __('Spread the Word', self::DOMAIN),
				'callback'	=> 'meta_box_author_words',
				'context'	=> 'side',
				'priority'	=> 'high'
			)
		);

		//Add meta boxes
		foreach($boxes as $box) {
			add_meta_box(
				$box['id'],
				$box['title'],
				array(&$this, $box['callback']),
				self::TYPE_SIDEBAR,
				$box['context'],
				$box['priority']
			);
		}

	}
	
	/**
	 * Hide some meta boxes from start
	 * @param  array $hidden 
	 * @param  object $screen 
	 * @return array 
	 */
	public function change_default_hidden($hidden, $screen) {

		//WordPress 3.3 has changed get_hidden_meta_boxes().
		if (get_bloginfo('version') < 3.3) {
			$condition = $screen->base == self::TYPE_SIDEBAR;
		} else {
			$condition = $screen->post_type == self::TYPE_SIDEBAR;
		}

		if ($condition && get_user_option('metaboxhidden_sidebar') === false) {

			$hidden_meta_boxes = array('pageparentdiv');
			$hidden = array_merge($hidden, $hidden_meta_boxes);

			$user = wp_get_current_user();
			update_user_option($user->ID, 'metaboxhidden_sidebar', $hidden, true);
		}
		return $hidden;
	}

	public function meta_box_new_group() {
		echo '<input type="submit" value="Add new Rule Group"/>';
	}
	
	/**
	 * Meta box for content rules
	 * @return void 
	 */
	public function meta_box_rules() {

		echo '<div id="cas-accordion">'."\n";
		do_action('cas-module-admin-box');
		echo '</div>'."\n";
		
	}
	
	/**
	 * Meta box for options
	 * @return void
	 */
	public function meta_box_options() {

		$columns = array(
			'exposure',
			'handle' => 'handle,host',
			'merge-pos'
		);

		foreach ($columns as $key => $value) {

			echo '<span>' . $this->metadata[is_numeric($key) ? $value : $key]['name'] . ':';
			echo '<p>';
			$values = explode(',', $value);
			foreach ($values as $val) {
				$this->_form_field($val);
			}
			echo '</p></span>';
		}
	}
		
	/**
	 * Meta box for author words
	 * @return void 
	 */
	public function meta_box_author_words() {

		// Use nonce for verification
		wp_nonce_field(basename(__FILE__), '_ca-sidebar-nonce');
?>
				<div style="overflow:hidden;">
				<p><?php _e('If you love this plugin, please consider donating to support future development.', self::DOMAIN); ?></p>	
				<p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=KPZHE6A72LEN4&amp;lc=US&amp;item_name=WordPress%20Plugin%3a%20Content%20Aware%20Sidebars&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted"
				   target="_blank" title="PayPal - The safer, easier way to pay online!">
					<img align="center" border="0" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" width="147" height="47" alt="PayPal - The safer, easier way to pay online!">	
				</a></p>
				<p><?php _e('Or you could:',self::DOMAIN); ?></p>
				<ul>
					<li><a href="http://wordpress.org/support/view/plugin-reviews/content-aware-sidebars?rate=5#postform" target="_blank"><?php _e('Rate the plugin on WordPress.org',self::DOMAIN); ?></a></li>
					<li><a href="http://wordpress.org/extend/plugins/content-aware-sidebars/" target="_blank"><?php _e('Link to the plugin page',self::DOMAIN); ?></a></li>
				</ul>
				<br />
				<p>
					<a href="https://twitter.com/intoxstudio" class="twitter-follow-button" data-show-count="false">Follow @intoxstudio</a>
					<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></p>
				<p>
					<iframe src="//www.facebook.com/plugins/like.php?href=http%3A%2F%2Fwww.facebook.com%2Fintoxstudio&amp;send=false&amp;layout=button_count&amp;width=450&amp;show_faces=false&amp;font&amp;colorscheme=light&amp;action=like&amp;height=21&amp;appId=436031373100972" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:100%; height:21px;" allowTransparency="true"></iframe>	
				</p>
				</div>
		<?php
	}
		
	/**
	 * Create form field for metadata
	 * @global object $post
	 * @param  array $setting 
	 * @return void 
	 */
	private function _form_field($setting) {

		$meta = get_post_meta(get_the_ID(), self::PREFIX . $setting, true);
		$setting = $this->metadata[$setting];
		$current = $meta != '' ? $meta : $setting['val'];
		switch ($setting['type']) {
			case 'select' :
				echo '<select style="width:250px;" name="' . $setting['id'] . '">' . "\n";
				foreach ($setting['list'] as $key => $value) {
					echo '<option value="' . $key . '"' . selected($current,$key,false) . '>' . $value . '</option>' . "\n";
				}
				echo '</select>' . "\n";
				break;
			case 'checkbox' :
				echo '<ul>' . "\n";
				foreach ($setting['list'] as $key => $value) {
					echo '<li><label><input type="checkbox" name="' . $setting['id'] . '[]" value="' . $key . '"' . (in_array($key, $current) ? ' checked="checked"' : '') . ' /> ' . $value . '</label></li>' . "\n";
				}
				echo '</ul>' . "\n";
				break;
			case 'text' :
			default :
				echo '<input style="width:200px;" type="text" name="' . $setting['id'] . '" value="' . $current . '" />' . "\n";
				break;
		}
	}
		
	/**
	 * Save meta values for post
	 * @param  int $post_id 
	 * @return void 
	 */
	public function save_post($post_id) {

		// Save button pressed
		if (!isset($_POST['original_publish']) && !isset($_POST['save_post']))
			return;

		// Only sidebar type
		if (get_post_type($post_id) != self::TYPE_SIDEBAR)
			return;

		// Verify nonce
		if (!check_admin_referer(basename(__FILE__), '_ca-sidebar-nonce'))
			return;

		// Check permissions
		if (!current_user_can('edit_theme_options', $post_id))
			return;

		// Check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;

		// Load metadata
		$this->_init_metadata();

		// Update metadata
		foreach ($this->metadata as $field) {
			$new = isset($_POST[$field['id']]) ? $_POST[$field['id']] : '';
			$old = get_post_meta($post_id, self::PREFIX . $field['id'], true);

			if ($new != '' && $new != $old) {
				update_post_meta($post_id, self::PREFIX . $field['id'], $new);
			} elseif ($new == '' && $old != '') {
				delete_post_meta($post_id, self::PREFIX . $field['id'], $old);
			}
		}
		// Update module data
		do_action('cas-module-save-data',$post_id);
	}

	/**
	 * Database data update module
	 * @return void 
	 */
	public function db_update() {
		cas_run_db_update(self::DB_VERSION);
	}

	/**
	 * Load scripts and styles for administration
	 * @param  string $hook 
	 * @return void 
	 */
	public function load_admin_scripts($hook) {

		wp_register_script('cas_admin_script', WP_PLUGIN_URL . '/' . $this->basename . '/js/cas_admin.js', array('jquery'), '1.2', true);
		wp_register_style('cas_admin_style', WP_PLUGIN_URL . '/' . $this->basename . '/css/style.css', array(), '1.2');

		if ($hook == 'post.php' || $hook == 'post-new.php') {
			// WordPress < 3.3 does not have jQuery UI accordion and autocomplete
			if (get_bloginfo('version') < 3.3) {
				wp_register_script('cas-jquery-ui-autocomplete', WP_PLUGIN_URL . '/' . $this->basename . '/js/jquery.ui.autocomplete.js', array('jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-position'), '1.8.9', true);
				wp_register_script('cas-jquery-ui-accordion', WP_PLUGIN_URL . '/' . $this->basename . '/js/jquery.ui.accordion.js', array('jquery-ui-core', 'jquery-ui-widget'), '1.8.9', true);
				wp_enqueue_script('cas-jquery-ui-autocomplete');
				wp_enqueue_script('cas-jquery-ui-accordion');
			} else {
				wp_enqueue_script('jquery-ui-accordion');
				wp_enqueue_script('jquery-ui-autocomplete');
			}
			wp_enqueue_script('cas_admin_script');

			wp_enqueue_style('cas_admin_style');
		} else if ($hook == 'edit.php') {
			wp_enqueue_style('cas_admin_style');
		}
	}
	
	/**
	 * Load dependencies
	 * @return void
	 */
	private function _load_dependencies() {
		require('walker.php');
		require('update_db.php');
		require('modules/abstract.php');
	}

}

// Launch plugin
global $ca_sidebars;
$ca_sidebars = new ContentAwareSidebars();

/**
 * Template wrapper to display content aware sidebars
 * @global object $ca_sidebars
 * @param  array|string  $args 
 * @return void 
 */
function display_ca_sidebar($args = array()) {
	global $ca_sidebars;
	$ca_sidebars->manual_sidebar($args);
}
