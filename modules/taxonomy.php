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
	protected $name = 'Taxonomies';
	private $taxonomy_objects;
	private $post_taxonomies;
	private $post_terms;
	
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
	
	public function db_join() {
		global $wpdb;
		
		$joins = "LEFT JOIN $wpdb->term_relationships term ON term.object_id = posts.ID ";
		$joins .= "LEFT JOIN $wpdb->term_taxonomy taxonomy ON taxonomy.term_taxonomy_id = term.term_taxonomy_id ";
		$joins .= "LEFT JOIN $wpdb->terms terms ON terms.term_id = taxonomy.term_id ";
		$joins .= "LEFT JOIN $wpdb->postmeta taxonomies ON taxonomies.post_id = posts.ID AND taxonomies.meta_key = '".ContentAwareSidebars::prefix."taxonomies'";
		
		return $joins;
	
	}
	
	public function db_where() {
		
		if(is_singular()) {
			$terms = array();
			$taxonomies = array();
						
			//Grab posts terms and make where rules for taxonomies.
			$tax_where[] = "taxonomies.meta_value IS NULL";
			foreach($this->post_terms as $term) {
				$terms[] = $term->slug;
				if(!isset($taxonomies[$term->taxonomy])) {
					$tax_where[] = "taxonomies.meta_value = '".$taxonomies[$term->taxonomy] = $term->taxonomy."'";
				}
			}

			return "(terms.slug IS NULL OR terms.slug IN('".implode("','",$terms)."')) AND (".implode(' OR ',$tax_where).")";
		}
		$term = get_queried_object();
		
		return "(terms.slug = '$term->slug' OR taxonomies.meta_value = '".$term->taxonomy."')";
		
	}
	
	public function db_where2() {
		return "terms.slug IS NOT NULL OR taxonomies.meta_value IS NOT NULL";
	}

	public function _get_content() {
		
	}
	
	public function meta_box_tab() {
		foreach ($this->_get_taxonomies() as $taxonomy) {
			echo '<li><a href="#cas-' . $this->id . '-' . $taxonomy->name . '">' . $taxonomy->label . '</a></li>';
		}
	}

	public function meta_box_content() {
		global $post;

		foreach ($this->_get_taxonomies() as $taxonomy) {
			echo '<div class="cas-rule-content" id="cas-' . $this->id . '-' . $taxonomy->name . '">';

			$meta = get_post_meta($post->ID, ContentAwareSidebars::prefix . 'taxonomies', false);
			$current = $meta != '' ? $meta : array();

			$terms = get_terms($taxonomy->name, array('get' => 'all','number' => 200));

			echo '<p>' . "\n";
			echo '<label><input type="checkbox" name="taxonomies[]" value="' . $taxonomy->name . '"' . checked(in_array($taxonomy->name, $current), true, false) . ' /> ' . sprintf(__('Show with %s', 'content-aware-sidebars'), $taxonomy->labels->all_items) . '</label>' . "\n";
			echo '</p>' . "\n";
			if (!$terms || is_wp_error($terms)) {
				echo '<p>' . __('No items.') . '</p>';
			} else {
				?>
					<div id="taxonomy-<?php echo $taxonomy->name; ?>" class="categorydiv" style="min-height:100%;">
						<ul id="<?php echo $taxonomy->name; ?>-tabs" class="category-tabs">
							<li class="hide-if-no-js"><a href="#<?php echo $taxonomy->name; ?>-pop" tabindex="3"><?php _e('Most Used'); ?></a></li>
							<li class="tabs"><a href="#<?php echo $taxonomy->name; ?>-all" tabindex="3"><?php _e('View All'); ?></a></li>
						</ul>

						<div id="<?php echo $taxonomy->name; ?>-pop" class="tabs-panel" style="display: none;min-height:100%;">
							<ul id="<?php echo $taxonomy->name; ?>checklist-pop" class="categorychecklist form-no-clear" >
				<?php $popular_ids = cas_popular_terms_checklist($taxonomy); ?>
							</ul>
						</div>
						
						<div id="<?php echo $taxonomy->name; ?>-all" class="tabs-panel" style="min-height:100%;">
							<input type="hidden" name="<?php echo ($taxonomy->name == "category" ? "post_category[]" : "tax_input[$taxonomy->name]"); ?>" value="0" />
							<ul id="<?php echo $taxonomy->name; ?>checklist" class="list:<?php echo $taxonomy->name ?> categorychecklist form-no-clear">
				<?php cas_terms_checklist($post->ID, array('taxonomy' => $taxonomy, 'popular_terms' => $popular_ids, 'terms' => $terms)) ?>
							</ul>
						</div>
					</div>
				<?php
			}
			echo '</div>';
		}
	}
	
	private function _get_taxonomies() {
		// List public taxonomies
		if (empty($this->taxonomy_objects)) {
			foreach (get_taxonomies(array('public' => true), 'objects') as $tax) {
				$this->taxonomy_objects[$tax->name] = $tax;
			}
		}
		return $this->taxonomy_objects;
	}

}
