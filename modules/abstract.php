<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

/**
 *
 * All modules should extend this one.
 *
 */
abstract class CASModule {
	
	/**
	 * Module idenfification
	 * @var string
	 */
	protected $id;

	/**
	 * Module name
	 * @var string
	 */
	protected $name;

	/**
	 * Enable AJAX search in editor
	 * @var boolean
	 */
	protected $searchable = false;

	/**
	 * Enable display for all content of type
	 * @var boolean
	 */
	protected $type_display = false;

	protected $pagination = array(
		'per_page' => 20,
		'total_pages' => 1,
		'total_items' => 0 
	);

	protected $ajax = false;
	
	/**
	 *
	 * Constructor
	 *
	 */
	public function __construct($id, $title, $ajax = false) {
		$this->id = $id;
		$this->name = $title;
		$this->ajax = $ajax;

		add_action('cas-module-admin-box',array(&$this,'meta_box_content'));
		add_action('cas-module-save-data',array(&$this,'save_data'));
		add_filter('cas-module-print-data',array(&$this,'print_group_data'),10,2);

		add_filter('cas-context-data',array(&$this,'parse_context_data'));
		if($this->ajax) {
			add_action('wp_ajax_cas-module-'.$this->id,array(&$this,'ajax_get_content'));
		}

	}

	public function ajax_get_content() {
		echo "hejsa";
		die();
	}

	// public function ajax_get_content() {

	// 	//validation
	// 	$paged = isset($_POST['paged']) ? intval($_POST['paged']) : 0;
	// 	$search = isset($_POST['search']) ? $_POST['search'] : false;

	// 	$content = $this->_get_content($paged,$search);
	// 	if($_POST['format'] == 'plain') {
	// 		$response = "";
	// 	} else {
	// 		$response = "";
	// 	}

	// 	json_encode($response);
	// 	die();
	// }
	
	/**
	 * Default meta box content
	 * @global object $post
	 * @return void 
	 */
	public function meta_box_content() {
		global $post;
		
		if(!$this->_get_content())
			return;

		echo '<li class="control-section accordion-section">';		
		echo '<h3 class="accordion-section-title" title="'.$this->name.'" tabindex="0">'.$this->name.'</h3>'."\n";
		echo '<div class="accordion-section-content cas-rule-content" data-cas-module="'.$this->id.'" id="cas-'.$this->id.'">';

		if($this->type_display) {
			echo '<ul><li><label><input class="cas-chk-all" type="checkbox" name="'.$this->id.'[]" value="'.$this->id.'" /> '.sprintf(__('Display with All %s',ContentAwareSidebars::DOMAIN),$this->name).'</label></li></ul>'."\n";
		}

		$content = "";
		foreach($this->_get_content() as $id => $name) {
			$content .= '<li class="cas-'.$this->id.'-'.$id.'"><label><input class="cas-' . $this->id . '" type="checkbox" name="'.$this->id.'[]" title="'.$name.'" value="'.$id.'" /> '.$name.'</label></li>'."\n";
		}

		$tabs = array();
		$tabs['all'] = array(
			'title' => __('View All'),
			'status' => true,
			'content' => $content
		);

		if($this->searchable) {
			$tabs['search'] = array(
				'title' => __('Search'),
				'status' => false,
				'content' => '',
				'content_before' => '<p><input class="cas-autocomplete-' . $this->id . ' cas-autocomplete quick-search" id="cas-autocomplete-' . $this->id . '" type="search" name="cas-autocomplete" value="" placeholder="'.__('Search').'" autocomplete="off" /><span class="spinner"></span></p>'
			);
		}

		echo $this->create_tab_panels($this->id,$tabs);

		echo '<p class="button-controls">';

		echo '<span class="add-to-group"><input data-cas-condition="'.$this->id.'" data-cas-module="'.$this->id.'" type="button" name="cas-condition-add" class="js-cas-condition-add button" value="'.__('Add to Group',ContentAwareSidebars::DOMAIN).'"></span>';

		echo '</p>';

		echo '</div>';
		echo '</li>';
	}
	
	/**
	 * Default query join
	 * @global object $wpdb
	 * @return string 
	 */
	public function db_join() {
		global $wpdb;
		return "LEFT JOIN $wpdb->postmeta {$this->id} ON {$this->id}.post_id = posts.ID AND {$this->id}.meta_key = '".ContentAwareSidebars::PREFIX.$this->id."' ";
	}
	
	/**
	 * Idenficiation getter
	 * @return string 
	 */
	final public function get_id() {
		return $this->id;
	}

	/**
	 * Save data on POST
	 * @param  int  $post_id
	 * @return void
	 */
	public function save_data($post_id) {
		$meta_key = ContentAwareSidebars::PREFIX . $this->id;
		$new = isset($_POST[$this->id]) ? $_POST[$this->id] : '';
		$old = array_flip(get_post_meta($post_id, $meta_key, false));

		if (is_array($new)) {
			//$new = array_unique($new);
			// Skip existing data or insert new data
			foreach ($new as $new_single) {
				if (isset($old[$new_single])) {
					unset($old[$new_single]);
				} else {
					add_post_meta($post_id, $meta_key, $new_single);
				}
			}
			// Remove existing data that have not been skipped
			foreach ($old as $old_key => $old_value) {
				delete_post_meta($post_id, $meta_key, $old_key);
			}
		} elseif (!empty($old)) {
			// Remove any old values when $new is empty
			delete_post_meta($post_id, $meta_key);
		}
	}

	public function print_group_data($post_id) {
		$data = get_post_custom_values(ContentAwareSidebars::PREFIX . $this->id, $post_id);
		if($data) {
			echo '<div class="cas-condition cas-condition-'.$this->id.'">';

			echo '<strong>'.$this->name.'</strong>';
			echo '<ul>';

			if(in_array($this->id,$data)) {
				echo '<li><label><input type="checkbox" name="'.$this->id.'[]" value="'.$this->id.'" checked="checked" /> '.sprintf(__('All %s',ContentAwareSidebars::DOMAIN),$this->name).'</label></li>';
			}

			foreach($this->_get_content(array('include' => $data)) as $id => $name) {
				echo '<li><label><input type="checkbox" name="'.$this->id.'[]" value="'.$id.'" checked="checked" /> '.$name.'</label></li>'."\n";
			}
			echo '</ul>';
			echo '</div>';	
		}
	}
	
	/**
	 * Get content for sidebar edit screen
	 * @return array 
	 */
	abstract protected function _get_content($args = array());

	/**
	 * Determine if current content is relevant
	 * @return boolean 
	 */
	abstract public function in_context();

	/**
	 * Get data from current content
	 * @author Joachim Jensen <jv@intox.dk>
	 * @since  2.0
	 * @return array|string
	 */
	abstract public function get_context_data();

	/**
	 * Parse context data together with 
	 * table query
	 */
	final public function parse_context_data($data) {
		if(apply_filters("cas-is-content-{$this->id}", $this->in_context())) {
			$data['JOIN'][$this->id] = apply_filters("cas-db-join-{$this->id}", $this->db_join());

			$context_data = $this->get_context_data();

			if(is_array($context_data)) {
				$context_data = "({$this->id}.meta_value IS NULL OR {$this->id}.meta_value IN ('".implode("','",$context_data) ."'))";
			}
			$data['WHERE'][$this->id] = apply_filters("cas-db-where-{$this->id}", $context_data);

			
		} else {
			$data['EXCLUDE'][] = $this->id;
		}
		return $data;
	}

	final protected function create_tab_panels($id, $args) {
		$return = '<div id="'.$id.'" class="posttypediv">';
		$return .= '<ul class="category-tabs">';

		$return2 = '';
		$count = count($args);
		foreach($args as $key => $tab) {
			if($count > 1) {
				$return .= '<li'.($tab['status'] ? ' class="tabs"' : '').'>';
				$return .= '<a class="nav-tab-link" href="#tabs-panel-' . $id . '-'.$key.'" data-type="tabs-panel-' . $id . '-'.$key.'"> '.$tab['title'].' </a>';
				$return .= '</li>';				
			}
			$return2 .= '<div id="tabs-panel-' . $id . '-'.$key.'" class="tabs-panel'.($tab['status'] ? ' tabs-panel-active' : ' tabs-panel-inactive').'">';
			if(isset($tab['content_before'])) {
				$return2 .= $tab['content_before'];
			}
			$return2 .= '<ul id="cas-list-' . $id . '" class="cas-contentlist categorychecklist form-no-clear">'."\n";
			$return2 .= $tab['content'];
			$return2 .= '</ul>'."\n";
			$return2 .= '</div>';
		}
		$return .= '</ul>';
		$return .= $return2;
		$return .'</div>';

		return $return;
	}
	
}
