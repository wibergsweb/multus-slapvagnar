<?php
if( !class_exists('multustrailer') ) {
            
    class multustrailer
    {                    

    /*
    *  Constructor
    *
    *
    *  @param	N/A
    *  @return	N/A
    */	
    public function __construct() 
    {                       
         //These are needed and should only register custom post types when really neccessary
        register_activation_hook( __FILE__, array( $this, 'activate' ) );                        
        add_action( 'init', array( $this, 'registerandlanguage' ) );
    }
    
    public function activate() 
    {
        $this->register_post_types();
        flush_rewrite_rules(); 
    }
    

    /*
     * registerandlanguage
     * 
     * This function load language and register this post type
     *  
     *  @param	N/A
     *  @return	N/A
     *                 
     */    
    public function registerandlanguage() 
    {   
        //Load (if there are any) translations
        $basepath_nocore = str_replace( 'core', '', dirname( plugin_basename(__FILE__) )  );
        $loaded_translation = load_plugin_textdomain( 'multusmaster-wp', false, $basepath_nocore . '/lang/' );
        
        //register custom post type
        $post_labels = array(
              'name' => __('Trailer', 'multusmaster-wp'),
              'singular_name' => __('Trailer', 'multusmaster-wp'),
              'add_new' => __('Add trailer', 'multusmaster-wp'),
              'add_new_item' => __('Add new trailer', 'multusmaster-wp'),
              'edit_item' => __('Edit trailer', 'multusmaster-wp'),
              'new_item' => __('New trailer', 'multusmaster-wp'),
              'all_items' => __('All trailers', 'multusmaster-wp'),
              'view_item' => __('Show trailer', 'multusmaster-wp'),
              'search_items' => __('Search trailer', 'multusmaster-wp'),
              'not_found' => __('No trailers found', 'multusmaster-wp'),
              'not_found_in_trash' => __('No trailers found in trash', 'multusmaster-wp'),
              'parent_item_colon'  => '',
              'menu_name' => __('Trailers', 'multusmaster-wp')
            );
            $post_args = array(
              'labels' => $post_labels,
              'public' => true,
              'publicly_queryable' => true,
              'show_ui' => true,
              'show_in_menu' => true,
              'query_var' => true,
              'rewrite' => array( 'slug' => __('trailer', 'multusmaster-wp')),
              'capability_type' => 'post',
              'has_archive' => __('trailers', 'multusmaster-wp'),
              'hierarchical' => false,
              'menu_position' => null,
              'supports' => array('title')
            );
            register_post_type( 'trailer', $post_args );
                     
   }      
    
 }
 
 $multustrailer = new multustrailer();
}