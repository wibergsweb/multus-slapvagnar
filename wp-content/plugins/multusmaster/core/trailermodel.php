<?php
if( !class_exists('multustrailermodel') ) {
            
    class multustrailermodel
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
              'name' => __('Fabrikat', 'multusmaster-wp'),
              'singular_name' => __('Fabrikat', 'multusmaster-wp'),
              'add_new' => __('Lägg till fabrikat', 'multusmaster-wp'),
              'add_new_item' => __('Lägg till nytt fabrikat', 'multusmaster-wp'),
              'edit_item' => __('Redigera fabrikat', 'multusmaster-wp'),
              'new_item' => __('Nytt fabrikat', 'multusmaster-wp'),
              'all_items' => __('Alla fabrikat', 'multusmaster-wp'),
              'view_item' => __('Visa fabrikat', 'multusmaster-wp'),
              'search_items' => __('Sök fabrikat', 'multusmaster-wp'),
              'not_found' => __('Inga fabrikat funna', 'multusmaster-wp'),
              'not_found_in_trash' => __('Inga fabrikat funna i soptunnan', 'multusmaster-wp'),
              'parent_item_colon'  => '',
              'menu_name' => __('Fabrikat', 'multusmaster-wp')
            );
            $post_args = array(
              'labels' => $post_labels,
              'public' => true,
              'publicly_queryable' => true,
              'show_ui' => true,
              'show_in_menu' => true,
              'query_var' => true,
              'rewrite' => array( 'slug' => __('trailermodel', 'multusmaster-wp')),
              'capability_type' => 'post',
              'has_archive' => __('trailermodels', 'multusmaster-wp'),
              'hierarchical' => false,
              'menu_position' => null,
              'supports' => array('title')
            );
            register_post_type( 'trailermodel', $post_args );
                     
   }      
    
 }
 
 $multustrailermodel = new multustrailermodel();
}