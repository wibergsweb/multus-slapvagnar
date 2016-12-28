<?php
/*
Plugin Name: Multus Master
Plugin URI: http://www.wibergsweb.se/plugins/multusmaster
Description: Multus Master
Version: 1.0
Author: Wibergs Web
Author URI: http://www.wibergsweb.se/
Text Domain: multusmaster-wp
Domain Path: /lang
License: GPLv2
Copyright: Wibergs Web
 */
defined( 'ABSPATH' ) or die( 'No access allowed!' );
        
//require_once('core/options.php');
require_once('core/trailer.php');
require_once('core/trailertype.php');
require_once('core/customer.php');


if( !class_exists('multusmaster') ) {
            
    class multusmaster
    {                    
    private $errormessage = null;
    private $options;
    

    /*
    *  Constructor
    *
    *  This function will construct all the neccessary actions, filters and functions for the multusmaster plugin to work
    *
    *
    *  @param	N/A
    *  @return	N/A
    */	
    public function __construct() 
    {                        
        add_action( 'init', array( $this, 'loadjslanguage' ) );
    }
    

    /*
     * loadjs
     * 
     * This function load javascript and (if there are any) translations
     *  
     *  @param	N/A
     *  @return	N/A
     *                 
     */    
    public function loadjslanguage() 
    {                       
        wp_enqueue_script( 'jquery' );
            
        wp_enqueue_script(
            'multusmasterwpjs',
            plugins_url( '/js/wibergsweb.js' , __FILE__, array('jquery') )
        );      
        
        wp_enqueue_style(
            'multusmasterwpcss',
            plugins_url( '/css/wibergsweb.css', __FILE__)
        );              
        
        //Load (if there are any) translations
        $loaded_translation = load_plugin_textdomain( 'multusmaster-wp', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );
        
        $this->init();
   }      


    /*
     *  error_notice
     * 
     *  This function is used for handling administration notices when user has done something wrong when initiating this object
     *  Shortcode-equal to: No shortcode equavilent
     * 
     *  @param N/A
     *  @return N/A
     *                 
     */                 
    public function error_notice() 
    {
        $message = $this->errormessage;
        echo"<div class=\"error\"><strong>";
        echo  __('Multusmaster Error:</strong><p>', 'multusmaster-wp');
        echo "$message</p></div>"; 
    }


    /*
     *  init
     * 
     *  This function initiates the actual shortcodes etc
     *  Shortcode-equal to: No shortcode equavilent
     * 
     *  @param N/A
     *  @return N/A
     *                 
     */        
    public function init() 
    {               
            //Add shortcodes
    }
             

}
        
$multusmaster = new multusmaster();


}