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
	public function start_lvl(&$output, $depth = 0, $args = array()) {
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
	public function end_lvl(&$output, $depth = 0, $args = array()) {
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
	public function start_el(&$output, $term, $depth = 0, $args = array(), $current_object_id = 0 ) {
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
                
			$output .= "\n".'<li id="'.$taxonomy->name.'-'.$term->term_id.'"><label class="selectit"><input class="cas-taxonomies-'.$taxonomy->name.'" value="'.$term->$value.'" type="checkbox" name="'.$name.'[]"'.checked(in_array($term->term_id,$selected_terms),true,false).'/> '.esc_html( $term->name ) . '</label>';
		
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
	public function end_el(&$output, $object, $depth = 0, $args = array() ) {
		$output .= "</li>\n";
	}
	
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
