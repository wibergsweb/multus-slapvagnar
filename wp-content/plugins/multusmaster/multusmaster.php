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
//require_once('core/trailer.php');
require_once('core/trailertype.php');
require_once('core/trailermodel.php');
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
        add_action('admin_menu', array ( $this, 'trailersmenu') );
        add_action( 'init', array( $this, 'loadjslanguage' ) );       
    }   
    
    public function trailersmenu() 
    {
            //add_dashboard_page('Släpvagnar', 'Släpvagnar', 'read', 'multmastertrailers', array ( $this, 'trailers_customers') );    
            //add_menu_page( "custom menu", "Wedding Photo plugin","manage options", "edit.php?post_type=wphoto_customer", false, false);
            add_submenu_page( 'edit.php?post_type=customer', 'Släpvagnar', 'Släpvagnar', 'read', 'trailercustomer', array( $this, 'trailers_customers' ) );                             
            add_submenu_page( 'edit.php?post_type=customer', 'Följesedlar', 'Följesedlar', 'read', 'deliverynote', array( $this, 'deliverynote_generate' ) );                             
    }
    
    public function deliverynote_generate()
    {
        $html_content = '<h1>Följesedlar</h1>';
        $html_content .= do_shortcode( '[pdfcrowd_generate roundup_totalaftervat="yes" nrformat_autosumfields="2;,;" nrformat_keyfields="acf_customer_trailers_rent;2;,;;" last_shortcode="yes" debug_mode="no" autosum_fields="yes" targetblank="yes" create_downloadlink="yes" out_files="foljesedelkunder" overwrite_pdf="datachange" convert_urls="{22}" data_postid="all" data_cpt="customer" data_fields="acf_customer_ort;acf_customer_streetaddress;acf_customer_postnr;acf_customer_phone;acf_customer_contact;acf_customer_trailers" data_acfkeys="field_585b0866afcf5;field_5877f52c93b9a;field_585b084fafcf4;field_585b0870afcf6;field_585b08357c2a1;field_585b0b3288d2e" link_titles="Följesedel för alla kunder (med autofakturering)" data_includeonlyfieldtrue="acf_customer_autoinvoice" exclude_subfields="acf_customer_trailers_delivery;acf_customer_trailers_yearmodel;acf_customer_trailers_totalweight" css_file="theme"]');

    
        echo $html_content;
    }
    
    public function trailers_customers()
    {
        $html_content = '<h1 class="wp-heading-inline">Släpvagnar</h1>';
                
        //Get info about all trailers in the system
        //
        $query_args = array
        (
            'posts_per_page' => -1, //Ignore limit posts query
            'post_type' => 'customer', //Default = (native) post
            'orderby' => 'title',
            'order' => 'ASC'
        );
        $all_posts_cpt = new WP_Query( $query_args );
        $array_of_trailers = $all_posts_cpt->get_posts();
        
        $cnt_customers = count ( $array_of_trailers );
        
        //Create new easier maintable array
        $array_values = array();
        $cnt_trailers = 0;
        $cnt_trailers_total = 0;
        $total_trailers_rent = 0;        
        $current_trailertypes = array();
        
        foreach ( $array_of_trailers as $cpt )
        {
            $customer_id = $cpt->ID;
            $customer_name = $cpt->post_title;
            
            $trailers_post = get_field('field_585b0b3288d2e', $customer_id );
            if (!empty ( $trailers_post ))
            {
                foreach ( $trailers_post as $tp )
                {
                    $trailers_regnr = $tp['acf_customer_trailers_reg'];
                    $trailers_type = $tp['acf_customer_relation_type']->post_title;
                    $trailers_lev = $tp['acf_customer_relation_model']->post_title;
                    $trailers_rent = $tp['acf_customer_trailers_rent'];
                    
                    //Generate the html-table rows for customers and trailers
                    //(if thera are trailers related to customer)
                    if ( $trailers_regnr !== null )
                    {
                        $array_values[] = array(
                                                'customer_id' => $customer_id,
                                                'customer_name' => $customer_name,
                                                'trailers_regnr' => $trailers_regnr, 
                                                'customer' => $customer_name, 
                                                'trailers_type' => $trailers_type,
                                                'trailers_lev' => $trailers_lev,
                                                'trailers_rent' => $trailers_rent
                                                );
                        if ( strlen($trailers_regnr) >0 ) {
                            $cnt_trailers++;
                        }
                        $total_trailers_rent = $total_trailers_rent + $trailers_rent;
                        $cnt_trailers_total++;
                        
                        //Nr of trailertypes? Add to array if it does not exists
                        //to figure out how many different trailertypes
                        if ( !in_array($trailers_type, $current_trailertypes)) {
                            $current_trailertypes[] = $trailers_type;
                        }
                        
                    }
                }
            }
        }
        
        $cnt_trailertypes = count ( $current_trailertypes );
        
        //Create html tables
        $customer_sort = $this->fetch_html_trailers( $array_values, 'Sortering efter kunder');
        $html_customer = $customer_sort;
       
        //trailer sort
        usort( $array_values, array($this, 'sort_by_trailer') );
        
        $trailer_sort = $this->fetch_html_trailers( $array_values, 'Sortering efter släpvagnar');
        $html_trailer = $trailer_sort;

        $html_content .= '<table class="wp-list-table widefat fixed striped posts tablesorter"><thead>';
        $html_content .= '<tr><th>Antal släpvagnar</th><th>Antal släpvagnar <strong>med regnr</strong></th><th>Antal släpvagnstyper</th><th>Antal kunder</th><th>Hyra</th></tr>';
        $html_content .= '</thead>';
        $html_content .= '<tbody>';
        $html_content .= '<tr><td>' . $cnt_trailers_total . '</td><td>' . $cnt_trailers . '</td><td>' . $cnt_trailertypes . '</td><td>' . $cnt_customers . '</td><td>' . $total_trailers_rent . '</td></tr>';
        $html_content .= '</tbody>';
        $html_content .= '</table></table>';
        

        
        $html_content .= $html_trailer;
        $html_content .= $html_customer;
        
        echo $html_content;
       
    }
    
    private function sort_by_trailer($a, $b) {
        return strcmp($a["trailers_regnr"], $b["trailers_regnr"]);
    }             
    
    private function fetch_html_trailers( $array_of_trailers, $sort_after_title = '' ) 
    {
        $array_values = array();
        
        $html_content = '<h2>' . $sort_after_title . '</h2>';
        $html_content .= '<table class="wp-list-table widefat fixed striped posts tablesorter"><thead>';
        $html_content .= '<tr>';
        $html_content .= '<th>Släpvagn</th>';        
        $html_content .= '<th>Kund</th>';     
        $html_content .= '<th>Vagnstyp</th>';
        $html_content .= '<th>Fabrikat</th>';
        $html_content .= '<th>Hyra</th>';        
        $html_content .= '</tr>';
        $html_content .= '</thead><tbody>';
        

        foreach ( $array_of_trailers as $row_trailer )
        {
            $html_content .= '<tr>';
            $html_content .= '<td>' . $row_trailer['trailers_regnr'] . '</td>';
            $html_content .= '<td><a href="post.php?post=' . $row_trailer['customer_id'] . '&action=edit">' . $row_trailer['customer_name'] . '</a></td>';
            $html_content .= '<td>' . $row_trailer['trailers_type'] . '</td>';
            $html_content .= '<td>' . $row_trailer['trailers_lev'] . '</td>';     
            $html_content .= '<td>' . $row_trailer['trailers_rent'] . '</td>';    
            $html_content .= '</tr>';
        }
      
        $html_content .= '</tbody></table>';
 
        return $html_content;
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