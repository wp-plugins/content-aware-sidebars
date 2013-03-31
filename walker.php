<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

/**
 *
 * Walker for post types and taxonomies
 *
 */
class CAS_Walker_Checklist extends Walker {
	
	/**
	 * Constructor
	 * @param string $tree_type 
	 * @param array  $db_fields 
	 */
	function __construct($tree_type, $db_fields) {
		
		$this->tree_type = $tree_type;
		$this->db_fields = $db_fields;
		
	}
	
	/**
	 * Start outputting level
	 * @param string $output
	 * @param int    $depth
	 * @param array  $args 
	 * @return void 
	 */
	public function start_lvl(&$output, $depth, $args) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children'>\n";
	}
	
	/**
	 * End outputting level
	 * @param string $output
	 * @param int    $depth
	 * @param array  $args 
	 * @return void 
	 */
	public function end_lvl(&$output, $depth, $args) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}
	
	/**
	 * Start outputting element
	 * @param  string $output 
	 * @param  object $term   
	 * @param  int    $depth  
	 * @param  array  $args 
	 * @return void
	 */
	public function start_el(&$output, $term, $depth, $args) {
		extract($args);
		
		if(isset($post_type)) {
			
			if ( empty($post_type) ) {
				$output .= "\n<li>";
				return;
			}
			
			$output .= "\n".'<li id="'.$post_type->name.'-'.$term->ID.'"><label class="selectit"><input class="cas-post_types-'.$term->ID.' cas-post_types-'.$post_type->name.'" value="'.$term->ID.'" type="checkbox" name="post_types[]"'.checked(in_array($term->ID,$selected_cats),true,false).'/> '.esc_html( $term->post_title ).'</label>';
			
		} else {
			
			if ( empty($taxonomy) ) {
				$output .= "\n<li>";
				return;
			}
			
			$name = ($taxonomy->name == 'category' ? 'post_category' : 'tax_input['.$taxonomy->name.']');                   
			$value = ($taxonomy->hierarchical ? 'term_id' : 'slug');
			$class = (in_array( $term->term_id, $popular_terms ) ? ' class="popular-category"' : '');
                
			$output .= "\n".'<li id="'.$taxonomy->name.'-'.$term->term_id.'"'.$class.'><label class="selectit"><input class="cas-taxonomies-'.$taxonomy->name.'" value="'.$term->$value.'" type="checkbox" name="'.$name.'[]" id="in-'.$taxonomy->name.'-'.$term->term_id.'"'.checked(in_array($term->term_id,$selected_terms),true,false).'/> '.esc_html( apply_filters('the_category', $term->name )) . '</label>';
		
		}
	}

	/**
	 * End outputting element
	 * @param  string $output 
	 * @param  object $term   
	 * @param  int    $depth  
	 * @param  array  $args   
	 * @return void         
	 */
	public function end_el(&$output, $term, $depth, $args) {
		$output .= "</li>\n";
	}
	
}

/**
 * Show terms checklist
 * @param  int   $post_id 
 * @param  array $args 
 * @return void 
 */
function cas_terms_checklist($post_id = 0, $args = array()) {
 	$defaults = array(
		'popular_terms' => false,
		'taxonomy' => 'category',
		'terms' => null,
		'checked_ontop' => true
	);
	extract(wp_parse_args($args, $defaults), EXTR_SKIP);

	$walker = new CAS_Walker_Checklist('category',array('parent' => 'parent', 'id' => 'term_id'));

	if(!is_object($taxonomy))
		$taxonomy = get_taxonomy($taxonomy);
        
        $args = array(
		'taxonomy'	=> $taxonomy,
		'disabled'	=> !current_user_can($taxonomy->cap->assign_terms)
	);

	if ($post_id)
		$args['selected_terms'] = wp_get_object_terms($post_id, $taxonomy->name, array_merge($args, array('fields' => 'ids')));
	else
		$args['selected_terms'] = array();

	if (is_array($popular_terms))
		$args['popular_terms'] = $popular_terms;
	else
		$args['popular_terms'] = get_terms( $taxonomy->name, array( 'fields' => 'ids', 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hierarchical' => false ) );

	if(!$terms)
		$terms = (array) get_terms($taxonomy->name, array('get' => 'all'));

	if ($checked_ontop) {
		$checked_terms = array();
		$keys = array_keys( $terms );

		foreach($keys as $k) {
			if (in_array($terms[$k]->term_id, $args['selected_terms'])) {
				$checked_terms[] = $terms[$k];
				unset($terms[$k]);
			}
		}

		// Put checked terms on top
		echo call_user_func_array(array(&$walker, 'walk'), array($checked_terms, 0, $args));
	}
	// Then the rest of them
	echo call_user_func_array(array(&$walker, 'walk'), array($terms, 0, $args));
}

/**
 * Show checklist for popular terms
 * @global int     $post_ID
 * @param  object  $taxonomy 
 * @param  int     $default  
 * @param  int     $number   
 * @param  boolean $echo 
 * @return array            
 */	
function cas_popular_terms_checklist( $taxonomy, $default = 0, $number = 10, $echo = true ) {
	global $post_ID;

	if ( $post_ID )
		$checked_terms = wp_get_object_terms($post_ID, $taxonomy->name, array('fields'=>'ids'));
	else
		$checked_terms = array();

	$terms = get_terms( $taxonomy->name, array( 'orderby' => 'count', 'order' => 'DESC', 'number' => $number, 'hierarchical' => false ) );
	$disabled = current_user_can($taxonomy->cap->assign_terms) ? '' : ' disabled="disabled"';
	$popular_ids = array();

	foreach ( (array) $terms as $term ) {
		$popular_ids[] = $term->term_id;
		if ( !$echo ) // hack for AJAX use
			continue;
		$id = "popular-$taxonomy->name-$term->term_id";      
               ?>

		<li id="<?php echo $id; ?>" class="popular-category">
			<label class="selectit">
			<input class="cas-taxonomies-<?php echo $taxonomy->name; ?>" id="in-<?php echo $id; ?>" type="checkbox"<?php echo in_array( $term->term_id, $checked_terms ) ? ' checked="checked"' : ''; ?> value="<?php echo $term->term_id; ?>"<?php echo $disabled ?>/>
				<?php echo esc_html( apply_filters( 'the_category', $term->name ) ); ?>
			</label>
		</li>

		<?php
	}
	return $popular_ids;
}

/**
 * Show posts checklist
 * @param  int   $post_id 
 * @param  array $args 
 * @return void 
 */
function cas_posts_checklist($post_id = 0, $args = array()) {
 	$defaults = array(
		'post_type' => null,
		'posts' => null,
		'checked_ontop' => false
	);
	extract(wp_parse_args($args, $defaults), EXTR_SKIP);

	$walker = new CAS_Walker_Checklist('post',array ('parent' => 'post_parent', 'id' => 'ID'));

	$args = array(
		'post_type'	=> $post_type
	);

	if($post_id)
		$args['selected_cats'] = get_post_meta($post_id, ContentAwareSidebars::prefix.'post_types', false);
	else
		$args['selected_cats'] = array();

	if(!$posts)
		$posts = get_posts(array(
			'numberposts'	=> -1,
			'post_type'		=> $post_type->name,
			'post_status'	=> array('publish','private','future'),
		));	
	
	// Then the rest of them
	echo call_user_func_array(array(&$walker, 'walk'), array($posts, 0, $args));
}
