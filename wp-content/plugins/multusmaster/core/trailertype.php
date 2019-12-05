<?php
if( !class_exists('multustrailertype') ) {
            
    class multustrailertype
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
              'name' => __('Vagnstyp', 'multusmaster-wp'),
              'singular_name' => __('Vagnstyp', 'multusmaster-wp'),
              'add_new' => __('Lägg till vagnstyp', 'multusmaster-wp'),
              'add_new_item' => __('Lägg till ny vagnstyp', 'multusmaster-wp'),
              'edit_item' => __('Redigera vagnstyp', 'multusmaster-wp'),
              'new_item' => __('Ny vagnstyp', 'multusmaster-wp'),
              'all_items' => __('Alla vagnstyper', 'multusmaster-wp'),
              'view_item' => __('Visa vagnstyp', 'multusmaster-wp'),
              'search_items' => __('Sök vagnstyp', 'multusmaster-wp'),
              'not_found' => __('Inga vagnstyper funna', 'multusmaster-wp'),
              'not_found_in_trash' => __('Inga vagnstyper funns i soptunnan', 'multusmaster-wp'),
              'parent_item_colon'  => '',
              'menu_name' => __('Trailertypes', 'multusmaster-wp')
            );
            $post_args = array(
              'labels' => $post_labels,
              'public' => true,
              'publicly_queryable' => true,
              'show_ui' => true,
              'show_in_menu' => true,
              'query_var' => true,
              'rewrite' => array( 'slug' => __('trailertype', 'multusmaster-wp')),
              'capability_type' => 'post',
              'has_archive' => __('trailertypes', 'multusmaster-wp'),
              'hierarchical' => false,
              'menu_position' => null,
              'supports' => array('title')
            );
            register_post_type( 'trailertype', $post_args );
                     
   }      
    
 }
 
 $multustrailertype = new multustrailertype();
}