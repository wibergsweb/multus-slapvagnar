<?php
if( !class_exists('multuscustomer') ) {
            
    class multuscustomer
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
        $loaded_translation = load_plugin_textdomain( 'multusmaster-wp', false, $basepath_nocore . 'lang/' );

        //register custom post type
        $post_labels = array(
              'name' => __('Kund'),
              'singular_name' => __('Kund', 'multusmaster-wp'),
              'add_new' => __('Lägg till kund', 'multusmaster-wp'),
              'add_new_item' => __('Lägg till ny kund', 'multusmaster-wp'),
              'edit_item' => __('Redigera kund', 'multusmaster-wp'),
              'new_item' => __('Ny kund', 'multusmaster-wp'),
              'all_items' => __('Alla kunder', 'multusmaster-wp'),
              'view_item' => __('Visa kund', 'multusmaster-wp'),
              'search_items' => __('Sök kund', 'multusmaster-wp'),
              'not_found' => __('Inga kunder funna', 'multusmaster-wp'),
              'not_found_in_trash' => __('Inga kunder hittades i soptunnan', 'multusmaster-wp'),
              'parent_item_colon'  => '',
              'menu_name' => __('Kunder', 'multusmaster-wp')
            );
            $post_args = array(
              'labels' => $post_labels,
              'public' => true,
              'publicly_queryable' => true,
              'show_ui' => true,
              'show_in_menu' => true,
              'query_var' => true,
              'rewrite' => array( 'slug' => __('customer', 'multusmaster-wp')),
              'capability_type' => 'post',
              'has_archive' => __('customers', 'multusmaster-wp'),
              'hierarchical' => false,
              'menu_position' => null,
              'supports' => array('title')
            );
            register_post_type( 'customer', $post_args );
                     
            //Store fetched array of projects in variable
   }      
    
 }
 
 $multuscustomer = new multuscustomer();
}