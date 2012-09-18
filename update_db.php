<?php
/**
 * @package Content Aware Sidebars
 */

/**
 *
 * Run updates
 * 
 * @param type $current_version
 * @return boolean 
 */
function cas_run_db_update($current_version) {
	
        if(current_user_can('update_plugins')) {
                
                // Get current plugin db version
                $installed_version = get_option('cas_db_version');
		if($installed_version === false)
                        $installed_version = 0;
                
                // Database is up to date
                if($installed_version == $current_version)
                        return true;
                        
                $versions = array('0.8','1.1');
                
		//Launch updates            
                for($i = 0; $i < sizeof($versions); $i++){
                        $return = false;
                        
                        if(version_compare($installed_version,$versions[$i],'<')) {
                                
                                $function = 'cas_update_to_'.str_replace('.','',$versions[$i]);
                                
                                if(function_exists($function)) {
                                        call_user_func_array($function, array(&$return));
                                        if($return) {
                                                $installed_version = $versions[$i];
                                        }
                                }      
                        }
                }
                
                // Update database on success
                if($return)
                        update_option('cas_db_version',$installed_version);
                
                return $return;
        }
}

/**
 * Version 0.8 -> 1.1
 * Serialized metadata gets their own rows
 * 
 * @param boolean $return
 */
function cas_update_to_11($return) {
	
	$return = true;
}

/**
 *
 * Version 0 -> 0.8
 * Introduces database version management, adds preficed keys to metadata
 *
 * @global object $wpdb
 * @param boolean $return 
 */
function cas_update_to_08($return) {
        global $wpdb;
        
        $prefix = '_cas_';
        $metadata = array(
                'post_types',
		'taxonomies',
		'authors',
		'page_templates',
		'static',
		'exposure',
		'handle',
		'host',
		'merge-pos'
        );
        
        // Get all sidebars
        $posts = $wpdb->get_col($wpdb->prepare("
                SELECT ID 
                FROM $wpdb->posts
                WHERE post_type = %s
	",'sidebar'));
        
        //Check if there is any
        if(empty($posts)) {
                $return = true;
        } else {
                //Update the meta keys
                foreach($metadata as $meta) {
                        $wpdb->query("
                                UPDATE $wpdb->postmeta
                                SET meta_key = '".$prefix.$meta."'
                                WHERE meta_key = '".$meta."'
                                AND post_id IN(".implode(',',$posts).")
                        ");
                }
        }
        
        $return = true;
        
}     
