<?php
/*
Plugin Name: PDF Generator Crowd API
Plugin URI: http://www.wibergsweb.se/plugins/pdf-generator-crowd
Description: a PDF generator that uses the PDF Crowd API to create pdf files from page(s), post(s) or Advanced Custom Fields. Happy generating!
Version: 1.35
Author: Wibergs Web
Author URI: http://www.wibergsweb.se/
Text Domain: 'pdfcrowd-wp
Domain Path: /lang
License: GPLv2
*/
defined( 'ABSPATH' ) or die( 'No access allowed!' );
        
require_once('core/pdfcrowd.php');
require_once('core/options.php');

if( !class_exists('pdfgeneratorcrowd') ) {
            
    class pdfgeneratorcrowd
    {                    
    private $errormessage = null;
    public $username = null;
    public $userpass = null;
    public $out_files = '';
    private $options;
    protected $autosum_fields_arr = array();   
    protected $vat = 25;
    private $check_path; //Full path where to check/save things
    private $metadata_before;
    private $metadata_after;
    private $remove_files = array();
    private $uses_acf = false;
    
    
    /*
    *  Constructor
    *
    *  This function will construct all the neccessary actions, filters and functions for the pdfgeneratorcrowd plugin to work
    *
    *
    *  @param	N/A
    *  @return	N/A
    */	
    public function __construct() 
    {                  
        $this->options = get_option( 'pdfcrowd_option' );      
        $upload_basedir = plugin_dir_path( __FILE__ );
        $this->check_path = $upload_basedir . '/';
        
        $link_title_default = __('Download PDF', 'pdfcrowd-wp');                
        
        //Use for shortcode and also external calls
        $this->default_values = array(
            'debug_mode' => 'no', //Display variables used for creating pdf etc.
            'skip_shortcode' => 'no', //skip this shortcode (for debugging purposes)
            'use_ssl' => null,    //set to yes if you want to create a pdf from a site that uses ssl. Set to no if you dont want ssl. If set to null it would identify if current website has ssl enabled or not
            'show_clientinfo' => 'no', //When having debug_mode set to yes , show clients info as well (including username and password. Use with caution!)
            'convert_urls' => '', //Convert urls (set to current if you want to create pdf of current page/post or put a number between { and } and the plugin will fetch page with that post/page id. If having several urls separate them by using semicolon). 
            'out_files' => 'current', //What filename(s) to save as. If having having several urls defined, add equal many files separated by semicolon
            'overwrite_pdf' => 'no', //(added: datachange = only overwrite when data has changed) dont connect to PDF Crowd (or overwrite the pdf) if the pdf already exists (saves tokens in your account and resources on your server!!!!)
            'path' => 'pdfcrowd', //This is the base path AFTER the upload path of Wordpress (eg. pdfcrowd = /wp-content/uploads/pdfcrowd)            
            'create_downloadlink' => 'no', //Create a downloadable link to the created pdf
            'targetblank'=>'no', //Target blank yes/no (when target blank opens link a new window)
            'html_class' => '', //If you want to style the link use this class
            'link_titles' => $link_title_default, //What to show in link(s) creating a downloadlink. Several link should be separated by semicolon
            'data_includeonlyfieldtrue' => null, //If you have a select field (true/false) then include post only when this field is true 
            'data_cpt' => 'post', //Fetch data from specific custom post type (default to normal POST)
            'data_postid' => null, //Data from specific post/page - id. If setting this to all, then use all posts defined by data_cpt
            'data_fields' => '',   //Tell name of fields that should be used when fetching data from a specific post/page
            'data_acfkeys' => '', //If using ACF, then tell key (this is important) of each value (this is used for retrieving labels in for example for usage in headers of a repeater-field)
            'add_related_fields' => null, //If you want to include a field in array (probably acf repeater field) that is related to another field
            'exclude_subfields' => null, //Tell what subfields to exclude (probably from acf repeater) 
            'autosum_fields' => 'no', //Auto summarize every column in subarrays
            'css_file' => null, //If specified, it must be a link with full path that is publicy available. If set to theme the plugin would try to find plugin directory with css. Css will be applied to PDF's that are generated from database post/pages
            'last_shortcode' => null, //If using data_fields then value of this must be set to yes or no
            'use_posttitle' => 'yes',   //Use post-title as name (Set [title] in template)
            'vat' => 25, //Vat used if calculation used for vat / after vat
            'pagebreak_afterpost' => 'yes', //Set yes/no for pagebreaking after each post
            'max_pages' => null,
            'nrformat_keyfields' => null, //Format keyfield(s) with four items for each keyfield (field, decimals, decimal point, thousand separator)
            'nrformat_autosumfields' => null, //applies to all autosumfields
            'roundup_totalaftervat' => 'no',
            'generate_fromhtml' => '' //Generate custom html to PDF
        );        
        
        add_action( 'post_updated', array( $this, 'datachangecheck' ), 30, 3 );            
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

        wp_enqueue_style(
            'crowdpdfwpcss',
            plugins_url( '/css/wibergsweb.css', __FILE__)
        );              
        
        //Load (if there are any) translations
        $loaded_translation = load_plugin_textdomain( 'pdfcrowd-wp', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );
        
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
        echo  __('PDF Crowd Error:</strong><p>', 'pdfcrowd-wp');
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
            add_shortcode( 'pdfcrowd_generate', array($this, 'generate_pdf') );
    }
    
    
    /*
     *   get_numberformat
     * 
     *  Helper function - formats a number based on a semicolon-separated string with arguments
     * 
     *  @param integer $number_value            number
     *  @param string $nrformat_autosumfields   string to explode
     *  @return array with value and arguments for number format
     *               
     */   
    private function get_numberformat( $number_value, $nrformat_autosumfields ) 
    {
        $return_value = $number_value;    
        $sf_values = array();

        if ( $nrformat_autosumfields != null )
        {
            $sf_values = explode( ";", $nrformat_autosumfields );

            //Default values if non set
            if ( !isset($sf_values[1] ) )
            {
                $sf_values[1] = ',';
            }
            if ( !isset($sf_values[2]))
            {
                $sf_values[2] = '';
            }
            //Apply number format
            $return_value = number_format((float)$number_value, $sf_values[0], $sf_values[1], $sf_values[2]);
        }

        return array('value' => $return_value, 'args' => $sf_values );
    }

    
    /*
     *   array_swap
     * 
     *  Helper function - swaps two items in given array
     * 
     *  @param  string &$array            array
     *  @param string $swap_a           key/index of array
     *  @param string $swap_b           key/index of array
     *  @return N/A
     *               
     */      
    function array_swap(&$array,$swap_a,$swap_b)
    {    
        list($array[$swap_a],$array[$swap_b]) = array($array[$swap_b],$array[$swap_a]);
    }
    
    
    /*
     *   datahaschanged_save
     * 
     *  Helper function - saves a file to upload-directory
     * 
     *  @param  N/A
     *  @return N/A
     *               
     */    
    private function datahaschanged_save( $post_id ) 
    {
        $file_datachanged = $this->check_path . 'datachanged-' . $post_id . '.ini';               
        $fp = fopen( $file_datachanged, 'w');
        fclose($fp);   
    }
    
    
    /*
     *   onelevel_array
     * 
     *  Helper function - makes a multilevel array with two levels one level only
     * 
     *  @param  string $arr_modify            array
     *  @return array                                   one-level array
     *               
     */        
    private function onelevel_array( $arr_modify ) 
    {
        if (!is_array( $arr_modify ) ) 
        {
            return array();
        }

        $onelevel_arr = array();
        foreach($arr_modify as $amitem) 
        {
            if ( is_array( $amitem) )
            {
                $onelevel_arr[] = $amitem[0];
            }
            else {
                $onelevel_arr[] = $amitem;
            }
        }      
        
        return $onelevel_arr;
    }

    
     /*
     *   datachangecheck
     * 
     *  This function is called upon hook post_updated. 
     *  The function checks if data for updated post has been changed,
     *  and saves a file when data has been changed
     * 
     *  @param string $post_id                      id of post affected
     *  @param WP_Post $post_after          WP_Post after post has been updated
     *  @param WP_Post $post_before       WP_Post before post has been updated
     *  @return N/A
     *               
     */   
    function datachangecheck( $post_id, $post_after, $post_before ) 
    {
        //Cast postobjects into arrays, so comparision is possible with builtin-php functions        
        $spf = (array)$post_before;
        $spa = (array)$post_after;
        
        //These would differ every update. so remove them for possible comparision
        unset ( $spf['post_modified']);
        unset ( $spf['post_modified_gmt']);
        unset ( $spa['post_modified']);
        unset ( $spa['post_modified_gmt']);
                    
        //Check if any difference between arrays (if empty no difference)
        //If not empty, save file that tells plugin that data has been changed
        $ard = array_diff ( $spf, $spa);            
        if ( !empty ( $ard ) )
        { 
            $this->datahaschanged_save( $post_id );
        }
        else 
        {
            //No change of post native data, check if any metapost data has been changed
            //Remove edit_last and edit_lock because they could differ without data being changed            
            $this->metadata_before = get_post_meta( $post_id );
            unset ( $this->metadata_before['_edit_last']);
            unset ( $this->metadata_before['_edit_lock']);
            add_action('updated_post_meta', array( $this, 'checkmetadata_after'), 10, 2);          
        }        
        return;
    }     
    
    
    /*
     *   checkmetadata_after
     * 
     *  This function is called upon hook updated_post_meta when data has been update, but no change in native post data
     *  has been made and saves a file when data has been changed
     * 
     *  @param string $post_id                      id of post affected
     *  @param WP_Post $post_after          WP_Post after post has been updated
     *  @param WP_Post $post_before       WP_Post before post has been updated
     *  @return N/A
     *               
     */   
    function checkmetadata_after( $meta_id, $post_id )
    {
            //Because updated_post_meta is used, now we can grab the actual updated values
            //Remove edit_last and edit_lock because they could differ without data being changed
            $this->metadata_after = get_post_meta( $post_id );
            unset ( $this->metadata_after['_edit_last']);
            unset ( $this->metadata_after['_edit_lock']);
                        
            //Make one-level index arrays of metadata
            //so array_diff works correctly down below
            $arr_mdb = $this->onelevel_array( $this->metadata_before );
            $arr_mda = $this->onelevel_array( $this->metadata_after );
            
            //Compare array with metapost values before and after
            //If not empty, save file that tells plugin that data has been changed
            $ard_metadata = array_diff ( $arr_mdb, $arr_mda );
            if (!empty ( $ard_metadata))
            {
                $this->datahaschanged_save( $post_id );                         
            }
            else
            {
                //remove datachanged-file now when all data is saved and change is not made in this post
                $file_datachanged = $this->check_path . 'datachanged-' . $post_id . '.ini';     
                @unlink ( $file_datachanged );
            }
            return;            
    }

    
    /*
     * Generate pdf from html content (probably from external source)
     * (but use same settings as in shortcode)
     * 
     * @param string[] $attrs          If want to override any default settings (same as shortcode attributes)
     * @param string $html_content     html string to generate to PDF
     * @return string $pdf             Link if set to downloadable
     * 
     */
    public function generatepdf_from_html( $attrs, $html_content = '') 
    {
        $this->default_values['generate_fromhtml'] = $html_content;
        $pdf = $this->generate_pdf( $attrs );      
        return $pdf;       
    }
    
    
    /*
     *   generate_pdf
     * 
     *  This function creates a PDF on the fly
     * 
     *  @param  string $attr             shortcode attributes
     *  @return   string                      PDF
     *                 
     */        
    public function generate_pdf( $attrs ) 
    {          
        $defaults = $this->default_values;
        if ( $this->options === false )
        {
            if ( $debug_mode === 'yes') 
            {
                echo __('You must set at least username and password for your PDF Crowd account in your wordpress admin. Look under settings / Generator PDF Crowd settings', 'pdfcrowd-wp');   
            }
        }
        
        //User credentials
        $this->username = $this->options['pdfcrowd_user'];
        $this->userpass = $this->options['pdfcrowd_key'];
        if ( $this->username === null || $this->userpass === null ) 
        {
            if ( $debug_mode === 'yes') 
            {
                echo __('Username or password not set', 'pdfcrowd-wp');               
            }
        }
        
        $args = wp_parse_args( $attrs, $defaults );
        extract( $args );
        
        //Skip shortcode (for debugging purposes)
        //(for example if having three shortcodes and do not want to generate pdf's for one of them when testing)
        if  ($skip_shortcode === 'yes' )
        {
            return '';
        }

        $this->vat = $vat; //Used for possible calculation
                
        if ( $debug_mode === 'yes') {
            echo '<h2>' . __('Start debug', 'pdfcrowd-wp') . '</h2>';
            echo '<hr /><strong>';
            echo __('Arguments', 'pdfcrowd-wp');
            echo '</strong><br />';
            var_dump ( $args );            
        }
        
        $html_content = ''; //to return from shortcode        
        $all_converturls = explode (';', $convert_urls);
        $all_outfiles = explode(';', $out_files);
        $all_linktitles = explode(';', $link_titles);
        
        //If not equally many files as urls defined, do nothing
        //
        $count_urls = count ( $all_converturls );
        
        if ( $count_urls !== count ( $all_outfiles ) ) 
        {
            if ( $debug_mode === 'yes') 
            {
                echo __('You must set equal many urls (convert_urls attribute) as files (out_files attribute)', 'pdfcrowd-wp');   
            }
            return;
        }
        
        if ( $count_urls !== count ( $all_linktitles) ) 
        {
            if ( $debug_mode === 'yes') 
            {
                echo __('Number of titles should be equal many as urls (convert_urls attribute). If that is not the case, the default title is used', 'pdfcrowd-wp');   
                echo '<hr />';
            }           
        }
 
        //Base upload path of uploads
        $upload_dir = wp_upload_dir();
        $upload_basedir = $upload_dir['basedir'];        
        @mkdir ( $upload_basedir . '/' . $path ); //Create folder if it does not exist     
                
        if ( function_exists('get_field') && function_exists('get_field_object')) 
        {
            $this->uses_acf = true;
        }

        //Go through all urls and files and create the pdf(s)
        $iteration_nr = 1;
        if ( $debug_mode === 'yes' ) {
            echo '<hr /><strong>';
            echo __('Convert urls and save as files (with pdf extension)', 'pdfcrowd-wp');
            echo '</strong><br />';
            var_dump ( $all_converturls );
            var_dump ( $all_outfiles );
            echo '<hr /><strong>';
            echo __('Titles set', 'pdfcrowd-wp');
            echo '</strong><br />';
            var_dump ( $all_linktitles );           
        }
        
        $client = null;
        $acu_content_html_total = '<html>';
        $acu_content_html_total .= '<head>';
        if ( $css_file !== null ) 
        {
            //If css file set to theme, then use themes style.css
            if ( $css_file === 'theme')
            {
                $css_file = get_stylesheet_directory_uri() . '/style.css';                
            }
            $acu_content_html_total .= '<link rel="stylesheet" href="' . $css_file . '" type="text/css" media="all" />';
        }
        
        $acu_content_html_total .= '</head>';
        $acu_content_html_total .= '<body>';  
                        
        foreach ( $all_converturls as $acu_key=>$acu_url ) 
        {
            //If out_file set to current, fetch current post/page base name
            if ( $acu_url === 'current' ) 
            {
                $out_file = basename( get_the_permalink() );     
            }
                
            //If convert_urls starts with an { and ends with a ] then
            //use content between {} as a postid and set the post to the url
            //that should be converted (can be used for given template page)
            if ( substr( $acu_url, 0, 1 ) == '{'  || substr ( $acu_url, -1, 1) == '}' ) 
            {
                $content_ps = str_replace( '{', '', $acu_url );
                $content_ps2 = str_replace( '}', '', $content_ps );

                $c_pid = intval( $content_ps2 );
                $acu_url = get_the_permalink( $c_pid ); //Get url of postid

                //Get full html content of post
                $acu_content = get_post( $c_pid ); 
                if (!is_object($acu_content)) 
                {
                    if ( $debug_mode === 'yes' )
                    {
                        echo __('Can not create content for pdf generation' , 'pdfcrowd-wpi');
                        echo __('This can be caused by setting data_post - attribute incorrectly ({55} is an example) or it could be a post that does not exist.', 'pdfcrowd-wp');
                    }
                    return; //Can't do the creation. No WP Post object is created from the 
                }
                $acu_content_html = apply_filters('the_content', $acu_content->post_content);

                $out_file = basename( $acu_url );
                if ( $data_postid === 'current') {
                    $data_postid = get_the_ID(); 
                }
                $data_pid = (int)$data_postid;
                $data_postids = array();

                //If data_pid is set to alla .Then fetch data from all post (based on specified post type in data_cpt)
                if ( $data_postid === 'all' || $data_postid === '{all}' ) {
                                        
                    //Get all posts from specific post type
                    //
                    $query_args = array(
                        'posts_per_page' => -1, //Ignore limit posts query
                        'post_type' => $data_cpt //Default = (native) post
                    );
                    
                    //Fetch posts from database
                    $all_posts_cpt = new WP_Query( $query_args );
                    $getposts_cpt = $all_posts_cpt->get_posts();

                    foreach ( $getposts_cpt as $cpt )
                    {
                        //If given in shortcode that a specific should use as filter (inluce posts when true)                        
                        if ($data_includeonlyfieldtrue !== null && $this->uses_acf === true )
                        {
                            //Because handling of false/true-value(s) then we need to overwrite pdf
                            //because its a change if it's true and it's a change if it's false
                            if ( $overwrite_pdf === 'datachange' )
                            {
                                $overwrite_pdf = 'yes';
                            }
                            
                            //Field to check
                            $field_check_truevalue = get_field($data_includeonlyfieldtrue, $cpt->ID);

                            if ( $debug_mode === 'yes' )
                            {
                                echo __('post-id' . $cpt->ID, 'pdfcrowd-wp');
                                echo __(' --- field to check if true:', 'pdfcrowd-wp');
                                var_dump ( $field_check_truevalue);
                            }

                            //Only include post(s) if field is checked for resp. post
                            if ( (bool)$field_check_truevalue === true )
                            {                                                            //If set to datachange, make it overwritab
                                $data_postids[] = $cpt->ID; 
                            }
                        }
                        else 
                        {
                            //Just include all postids
                            $data_postids[] = $cpt->ID; 
                        }
                    }
                }
                else {
                    $data_postids = array( $data_pid );                              
                }

                //Make array(s) of datafields and datakeys given
                $ex_datafields = explode(';', $data_fields);
                $ex_datakeys = explode (';', $data_acfkeys);          
                
                if ( $debug_mode === 'yes' )
                {
                    echo __('Fetching page/post...', 'pdfcrowd-wp');
                    var_dump ( $acu_url );
                    echo __('...with data from postid(s)', 'pdfcrowd-wp');
                    var_dump ( $data_postids  );
                    echo __('...using these datafields', 'pdfcrowd-wp');                        
                    var_dump ( $ex_datafields );
                    echo __('..ACF datafieldkey values for each datafield', 'pdfcrowd-wp');
                    var_dump ( $ex_datakeys );
                    echo __('Template when using data from acf', 'pdfcrowd-wp');
                    var_dump ( $acu_content_html );               

                    if (count($ex_datafields) !== count($ex_datakeys)) {
                        echo __('Number of datafields and datakeys should be equal. Else you could get a unpredictable result', 'pdfcrowd-wp');
                    }
                                   
                }
                                    
                //IS Advanced custom fields installed?
                if (  $this->uses_acf === true ) 
                {                    
                     if ( $debug_mode === 'yes' )
                    {
                        echo '<p>';
                        echo __('ACF (Advanced Custom Fields) exists and is activated and the plugin can be used for fetching data from fields', 'pdfcrowd-wp' );
                        echo '</p>';
                    }
                    
                    //If attempting to use datafield(s) and no datapost id is given, tell user
                    if ( strlen( $data_fields ) > 0 && $data_postid === null )
                    {
                        if  ( $debug_mode === 'yes' )
                        {
                            echo __('No data_postid is given', 'pdfcrowd-wp' );
                            echo '<hr />';
                            return;
                        }

                    }                    
                }
                else 
                {         
                    //If attempting to use datafield(s), ACF must be installed
                    if ( strlen ( $data_fields ) > 0 || $data_postid !== null )
                    {
                        if ( $debug_mode === 'yes' )
                        {
                            echo '<p class="error">';
                            echo __('ACF (Advanced Custom Fields) needs to be installed and activated for possible usage of data fetching', 'pdfcrowd-wp' );
                            echo '</p>';
                        }
                        return;
                    }
                    else 
                    {
                        //Make sure these are not used below when not trying to use datafields
                        $ex_datafields = array();
                        $ex_datakeys = array();                        
                    }

                }

                //last_shortcode must be when using data change mode and creating values from datafields
                //This is because the plugin needs to know when to delete some files
                if ( strlen ( $data_fields ) > 0 && $last_shortcode === null && $overwrite_pdf === 'datachange')
                {
                    if ( $debug_mode === 'yes' )
                    {
                        echo '<p class="error">';
                        echo __('last_shortcode must be set to yes or no when using data_fields', 'pdfcrowd-wp' );
                        echo '</p>';
                    }
                    return;
                }
                
                //Go through datafields and replace fetched content with
                //actual values from these fields (for data_postid ( $dpi in loop) )
                $dv = '';
                $datapost_count = 0;
                
                $cnt_postids = count ( $data_postids)-1;
                
                foreach ( $data_postids as $dpi )
                {
                    $new_html = $acu_content_html;

                $key_index = 0;
                foreach ( $ex_datafields as $eda )
                {            
                    $datafield_value = get_field( $eda, $dpi );
                    if ( is_array ( $ex_datakeys )  ) {
                        $key_datafield = $ex_datakeys[$key_index];
                    }
                    else {
                        $key_datafield = $key_index;
                    }                            

                    //If autosumfields are given by user, put them into an array
                   //with corresponding column index number (0 = column 1, 1 = column 2 etc)
                    if ($autosum_fields === 'yes' ) 
                    {                          
                        $this->autosum_fields_arr[$key_datafield]= 0;
                    }

                    //If datafield value is array, then insert a table with that array
                    //(with values from array)                   
                    if ( is_array ( $datafield_value ) )
                    {
                        $remove_subfields = array();
                        
                        $dv = '<table class="pdfcrowd-table">';
                        $include_tableheader = true;

                        $table_row = 1;
                        $include_tablebodycontent = true;

                        foreach ( $datafield_value as $dfv )
                        {                               
                            //Array contains a sub-array (probably custom acf repeater)
                            if ( is_array ( $dfv ) )
                            {
                                $tbody_html = '';
                                $tbody_end_html = '';

                                if ( $include_tablebodycontent === true )
                                {
                                    $tbody_html = '<tbody>';
                                    $tbody_end_html = '</tbody>';
                                }
                                $include_tablebodycontent = false;


                                //Headers table
                                if ( $include_tableheader === true ) 
                                {                                            
                                    //Make sure get_field_object is current (then ACF is activated)
                                    $sub_fields_exists = false;
                                    if ( $this->uses_acf === true )
                                    {
                                        $acf_fieldobj  = get_field_object( $key_datafield );
                                        if ( isset ( $acf_fieldobj['sub_fields'])) {
                                            $sub_fields_exists = true;
                                        }                                            
                                    }
                                                                        
                                    //Include headers in table
                                    $include_tableheader = false;
                                    $dv .= '<thead><tr class="row-' . $table_row . '">';
                                    $table_col = 1;
                                    
                                    //If exclude subfields is set, then
                                    //make an array separated by semicolon
                                    $ex_exklcude_sf = array();
                                    if ( $exclude_subfields !== null )
                                    {
                                        $ex_exlcude_sf = explode(';',  $exclude_subfields);
                                    }

                                    //If related field shoud be added, handle this here
                                    if ( $add_related_fields !== null)
                                    {
                                        //Split up add_related_field by ampersand (&)
                                        $split_relatedfields = explode ('&', $add_related_fields);

                                        //Create subfields array of subarray (so its possible 
                                        //to sort/swap positions of item in array)                                               
                                        $subfields = array();
                                        $subfield_index=0;
 
                                        foreach ( $acf_fieldobj['sub_fields'] as $afo)
                                        {
                                            //Add subfields, but if fieldname is excluded
                                            //in shortcode don't add it
                                            $sf_name = (string)$afo['name'];          
                                            if ( $exclude_subfields !== null )
                                            {
                                                if ( in_array($sf_name, $ex_exlcude_sf) === false )
                                                {
                                                    $subfields[]= $afo;
                                                }
                                            } 
                                            else
                                            {
                                                $subfields[]= $afo;    
                                            }
                                        } 
                                        
                                        foreach ($split_relatedfields as $splitval_key=>$splitvalue )
                                        {
                                            $arfarr = explode( ';', $splitvalue );
                                            $include_col = (int)$arfarr[0];
                                            $rtitle = array('label' => $arfarr[1],
                                                                  'name' => $arfarr[3],
                                                                  '_name' => $arfarr[3],
                                                                );
                                            $subfields[] = $rtitle; //Add title to subfields-array

                                            //Swap items so position get correct in index based on what 
                                            //user defined in shortcode
                                            $movefrom_item =  count($subfields)-1; //Newly added index                                                   
                                            $moveto_item = $include_col; //What user defined in shortcode

                                            //Swap items in array
                                            $this->array_swap($subfields, $movefrom_item, $moveto_item);

                                            if ( $debug_mode === 'yes')
                                            {
                                                echo __('Swap items (headers)', 'pdfcrowd-wp');
                                                var_dump ( $movefrom_item);
                                                var_dump ( $moveto_item);
                                            }
                                        }

                                    }
                                    else
                                    {

                                        //Create subfields array of subarray
                                        $subfields = array();                                   
                                        if ( isset( $acf_fieldobj['sub_fields'] ))
                                        {
                                            foreach ( $acf_fieldobj['sub_fields'] as $afo)
                                            {
                                                //Add subfields, but if fieldname is excluded
                                                //in shortcode don't add it
                                                $sf_name = (string)$afo['name'];     
                                                if ( $exclude_subfields !== null )
                                                {
                                                    if ( in_array($sf_name, $ex_exlcude_sf) === false )
                                                    {
                                                        $subfields[]= $afo;
                                                    }                      
                                                }
                                                else 
                                                {
                                                    $subfields[] = $afo;
                                                }
                                            }
                                        }
                                    }
                                                         
                                    //Go through all subfields and create headers
                                    foreach ( $subfields as $subarr_key => $subarr_item )
                                    {                                              
                                        //If this is a field where subfields exists (acf repeater)
                                        //then use label from subfields of repeater-field
                                        if ( $sub_fields_exists === true )
                                        {
                                            $subarr_key = $subarr_item['label']; 
                                        }
                                        
                                        $dv .= '<th class="col-' . $table_col . '">' .$subarr_key . '</th>';
                                        $table_col++;
                                    }
                                    $dv .= '</tr></thead>';
                                }

                                //Body content table
                                $dv .= $tbody_html;                                   
                                $dv .= '<tr class="row-'  . $table_row . '">';
                                $table_col = 1;

                                //If related fields should be handled
                                if ( $add_related_fields !== null)
                                {
                                    //Split up add_related_field by & (if user want to add several fields with relationships)
                                    $split_relatedfields = explode ('&', $add_related_fields);

                                    //Split up actual fields
                                    foreach ($split_relatedfields as $splitkey=>$splitvalue )
                                    {
                                        $arfarr = explode( ';', $splitvalue );
                                        $include_col = $arfarr[0];
                                        $include_search = $arfarr[2];
                                        $include_return = $arfarr[3];

                                        $pid_search = $dfv[$include_search]->ID;    
                                        $fields_of_pid = get_fields( $pid_search );                                                            
                                        $return_value = $fields_of_pid[$include_return];

                                        if ( is_object($return_value) )
                                        {
                                            $dfv[$include_return] = $return_value->post_title;
                                        }
                                        else 
                                        {                                                    
                                            $dfv[$include_return] = $return_value;
                                        }

                                        //Sort $dfv so values are related to correct headers     
                                        $dfarr = array();
                                        $dfv_keys = array();
                                        foreach ( $dfv as $dfk_key => $dfv_value ) 
                                        {
                                            $dfv_keys[] = $dfk_key; //Save this for rearranging keys below correctly
                                            $dfarr[][$dfk_key]= $dfv_value;
                                        }

                                        //Reorder array with numeric indexnumbers (eg. 1 switches with 2)                                                
                                        $movefrom_item =  count($dfv_keys)-1;       //Last index in array                                               
                                        $moveto_item = (int)$include_col;                      //What user defined in shortcode
                                        if ( $debug_mode === 'yes')
                                        {
                                            echo __('Swap items (values)', 'pdfcrowd-wp');
                                            var_dump ( $movefrom_item);
                                            var_dump ( $moveto_item);
                                        }                                                                    

                                        $this->array_swap($dfarr, $movefrom_item, $moveto_item);                                                         
                                        $this->array_swap($dfv_keys, $movefrom_item, $moveto_item);

                                        //Recreate array with new order                                                
                                         $inr = 0;
                                         $dfv = array();      
                                         foreach ( $dfv_keys as $ditem )
                                         {
                                             $dfv[$ditem] = $dfarr[$inr][$ditem];
                                             $inr++;
                                         }                                                
                                    }
                               }
                               
                                //Go through values of sub array
                                foreach ( $dfv as $d_key=>$subarr_item_value )
                                {            
                                    $include_col = true;                                    
                                    if ( $exclude_subfields !== null )
                                    {
                                        if ( in_array( $d_key, $ex_exlcude_sf) !== false )
                                        {
                                            $include_col = false;
                                        }
                                    }
                                    
                                    //Include this value in column?
                                    if ( $include_col === true )
                                    { 
                                        //If value is an array, chose first from array
                                        if ( is_array( $subarr_item_value ) )
                                        {
                                            $subarr_item_value = $subarr_item_value[0];
                                        }

                                        if (is_object( $subarr_item_value))
                                        {
                                            $subarr_item_value = $subarr_item_value->post_title;
                                        }                 
                                        
                                        //Summarize field(s) given in autosum_fields-attribute
                                        //and put them into 
                                        if ($autosum_fields === 'yes' ) 
                                        {             
                                            //Do calculation      
                                            $col_index = $table_col-1;
                                            $this->autosum_fields_arr[$key_datafield]= $this->autosum_fields_arr[$key_datafield] + $subarr_item_value; 
                                        }

                                        if ( $nrformat_keyfields != null )
                                        {
                                            //Explode nrformat_keyfields-attribute
                                            //0 = keyfield. 1= value
                                            $sf_values = explode( ";", $nrformat_keyfields );
                                            $cnt_sfvalues = count ( $sf_values );
                                            
                                            //nr format of specified key
                                            //based on phps native number_format()
                                            for($div4start=0; $div4start<$cnt_sfvalues; $div4start+=4) 
                                            {                                                
                                                if ($sf_values[$div4start] === $d_key )
                                                {
                                                    //string number_format ( float $number , int $decimals = 0 , string $dec_point = "." , string $thousands_sep = "," )
                                                    $sf_values_nrdecimals_index = $div4start+1;
                                                    $sf_values_dec_point_index = $div4start+2;
                                                    $sf_values_thousands_sep_index = $div4start+3;
                                                    
                                                    //Default values if non set
                                                    if ( !isset($sf_values[$sf_values_dec_point_index] ) )
                                                    {
                                                        $sf_values[$sf_values_dec_point_index] = ',';
                                                    }
                                                    if ( !isset($sf_values[$sf_values_thousands_sep_index]))
                                                    {
                                                        $sf_values[$sf_values_thousands_sep_index] = '';
                                                    }
                                                    
                                                    //Apply number format
                                                    $subarr_item_value = number_format((float)$subarr_item_value, $sf_values[$sf_values_nrdecimals_index], $sf_values[$sf_values_dec_point_index], $sf_values[$sf_values_thousands_sep_index]);
                                                }
                                                
                                            }
                                        }              
                                        
                                        $dv .= '<td class="col-' . $table_col . '">' .$subarr_item_value;
                                        $dv .= '</td>';

                                        $table_col++;
                                    }
                                }
                                $dv .= '</tr>';
                                $dv .= $tbody_end_html;
                            }
                            else 
                            {                              
                                //Body content table (no array)
                                $dv .= '<tbody>';
                                $dv .= '<tr class="row-' . $table_row . '"><td class="' . $table_col .'">' .$dfv . '</td></tr>';
                                $dv .= '</tr>';
                                $dv .= '</tbody>';                                       
                            }

                            $table_row++;

                        }
                        $dv .= '</table>';

                        $datafield_value = $dv;
                    }

                    if ( $debug_mode === 'yes' )
                    {
                        //If value not given/found tell user
                        if ( $datafield_value === null || strlen ( $datafield_value ) === 0 )
                        {
                            $datafield_value = '[' . __('Value not set', 'pdfcrowd-wp') . ']';
                        }
                    }
                                 
                    if ( $nrformat_keyfields != null )
                    {
                        //Explode nrformat_keyfields-attribute
                        //0 = keyfield. 1= value
                        $sf_values = explode( ";", $nrformat_keyfields );
                        $cnt_sfvalues = count ( $sf_values );

                        //nr format of specified key
                        //based on phps native number_format()
                        for($div4start=0; $div4start<$cnt_sfvalues; $div4start+=4) 
                        {
                            if ($sf_values[$div4start] === $key_datafield )
                            {
                                //string number_format ( float $number , int $decimals = 0 , string $dec_point = "." , string $thousands_sep = "," )
                                $sf_values_nrdecimals_index = $div4start+1;
                                $sf_values_dec_point_index = $div4start+2;
                                $sf_values_thousands_sep_index = $div4start+3;

                                //Default values if non set
                                if ( !isset($sf_values[$sf_values_dec_point_index] ) )
                                {
                                    $sf_values[$sf_values_dec_point_index] = ',';
                                }
                                if ( !isset($sf_values[$sf_values_thousands_sep_index]))
                                {
                                    $sf_values[$sf_values_thousands_sep_index] = '';
                                }

                                //Apply number format
                                $datafield_value = number_format((float)$datafield_value, $sf_values[$sf_values_nrdecimals_index], $sf_values[$sf_values_dec_point_index], $sf_values[$sf_values_thousands_sep_index]);
                            }
                        }
                    }                   
                        
                    $htmlcontent_search_for = '[' . $eda . ']';                    
                    $new_html = str_replace($htmlcontent_search_for, $datafield_value, $new_html );
                    $key_index++;
                }

                //If using post title, do replacement here after all datafields
                if ( $use_posttitle === 'yes' )
                {
                    $htmlcontent_search_for = '[title]'; 
                    $poj = get_post ( $dpi); //data post id
                    $rep_value = $poj->post_title;
                    $new_html = str_replace($htmlcontent_search_for, $rep_value, $new_html );
                }
                    
                
                //Page break;
                if ( $pagebreak_afterpost === 'yes')
                {
                    //If the last post, don't add a break
                    if ( (int)$datapost_count !== $cnt_postids )
                    {
                        $new_html .= '<div style="page-break-before:always">';                         
                    }
                }
                
                $acu_content_html_total .= $new_html;
                                
                //Replace [crowdpdf-total = {field} with calculated total value] 
                if ( $autosum_fields === 'yes')
                {
                    //Fetch from template (post/page)
                    //Replace [crowdpdf-total=field] with actual value
                    //using print_r within replace here, so no error given in case the total value would be an array
                    $acu_replace_totals  = $acu_content_html_total;
                    foreach ( $this->autosum_fields_arr as $autosum_key => $total_value) {
                        $total_v = $this->get_numberformat( $total_value, $nrformat_autosumfields );
                        $total_value = $total_v['value'];
                        $acu_replace_totals = str_replace("[crowdpdf-total=$autosum_key]", $total_value , $acu_content_html_total);
                    }
                    $acu_content_html_total = $acu_replace_totals;
                                       
                    //total-vat
                    $vat = $this->vat;                    
                    $acu_replace_totals_vat  = $acu_content_html_total;
                    foreach ( $this->autosum_fields_arr as $autosum_key => $total_value) {
                        $total_vat = ($vat/100) * $total_value;
                        $total_va = $this->get_numberformat( $total_vat, $nrformat_autosumfields );
                        $total_vat = $total_va['value'];
                        $acu_replace_totals_vat = str_replace("[crowdpdf-totalvat=$autosum_key]", print_r($total_vat, true) , $acu_content_html_total);
                    }
                    $acu_content_html_total = $acu_replace_totals_vat;
                    
                    //totalaftervat (total + vat)
                    $acu_replace_totalsum  = $acu_content_html_total;
                    foreach ( $this->autosum_fields_arr as $autosum_key => $total_value) {
                        $total_vat = ($vat/100) * $total_value;
                        $total_sum = $total_value + $total_vat;                        
                        $total_s = $this->get_numberformat( $total_sum, $nrformat_autosumfields );
                        $total_sum = $total_s['value'];
                        
                        if ( $roundup_totalaftervat === 'yes')
                        {          
                            $total_sum_args = $total_s['args'];
   
                            $total_sum = str_replace(',', '.' , $total_sum); //Make sure dots are used instead of commas when rounding up                            
                            $total_sum = number_format( round( (float)$total_sum, 0, PHP_ROUND_HALF_UP ), $total_sum_args[0], $total_sum_args[1], $total_sum_args[2] );
                        }                        
                        $acu_replace_totalsum = str_replace("[crowdpdf-totalaftervat=$autosum_key]", print_r($total_sum, true) , $acu_content_html_total);
                    }
                    $acu_content_html_total = $acu_replace_totalsum;    
                    
                    //comparision totalaftervat (total - total before roundup)
                    $acu_replace_totalsum  = $acu_content_html_total;
                    foreach ( $this->autosum_fields_arr as $autosum_key => $total_value) {
                        $total_vat = ($vat/100) * $total_value;
                        $total_sum = $total_value + $total_vat;                        
                        $total_s = $this->get_numberformat( $total_sum, $nrformat_autosumfields );
                        $total_sum = $total_s['value'];
                        $total_sum_beforeroundup = $total_sum;
                        
                        $total_sum_args = $total_s['args'];
                        if ( $roundup_totalaftervat === 'yes')
                        {          
                            $total_sum = str_replace(',', '.' , $total_sum); //Make sure dots are used instead of commas when rounding up                            
                            $total_sum_beforeroundup = (float)$total_sum;
                            $total_sum = number_format( round( (float)$total_sum, 0, PHP_ROUND_HALF_UP ), $total_sum_args[0], $total_sum_args[1], $total_sum_args[2] );
                        }
                        
                        $c_value = $total_sum - $total_sum_beforeroundup;
                        $compare_value = number_format( (float)$c_value, $total_sum_args[0], $total_sum_args[1], $total_sum_args[2] );
                        $acu_replace_comparesum = str_replace("[crowdpdf-totalaftervat-roundupcompare=$autosum_key]", print_r($compare_value, true) , $acu_content_html_total);                        
                    }                     
                    $acu_content_html_total = $acu_replace_comparesum; 
                    
                    if ( $debug_mode === 'yes' )
                    {
                        echo __('Autosum array', 'pdfcrowd-wp');
                        var_dump ( $this->autosum_fields_arr );
                    }
                }

                //Page break END, but don't do this on first post
                if ( $pagebreak_afterpost === 'yes')
                {
                    //If the last post, don't add a break
                    if ( (int)$datapost_count !== $cnt_postids )
                    {
                        $new_html .= '</div>';                         
                    }                                  
                }
                
                if ( $debug_mode === 'yes' )
                {
                    echo __('Current content', 'pdfcrowd-wp');       
                    var_dump ( $new_html );                                                
                }                
                
                //end of loop $data_postids as $dpi
                $datapost_count++;
                }
        }
        $acu_content_html_total .= '</body></html>';

        //Create name of pdf-file to use for saving
        //If outfile has .pdf extension set, remove it here
        if ( stristr( $all_outfiles[$acu_key], '.pdf' ) !== false ) 
        {
            $out_file = substr( $all_outfiles[$acu_key], 0, -4); //remove four last chars (.pdf)
        }
        else 
        {
            $out_file = $all_outfiles[$acu_key];
        }        
        $temp_uploadfile = $upload_basedir . '/' . $path . '/' . $out_file . '.pdf';

        if ( $debug_mode === 'yes') 
        {            
            echo '<hr /><strong>';
            echo __('Upload file:', 'pdfcrowd-wp');
            echo '</strong><br />' . $temp_uploadfile . '<br />';
        }

        //First iteration (from convert all urls)
        if ( $iteration_nr === 1 )
        {
            $connect_api = true;
        }
        else 
        {
            //If having more urls in same shortcode, dont connect to pdf crowd server each time
            $connect_api = false;
        }

        //If file exists dont connect to the PDF Crowd server (saves tokens) and dont create the pdf again
        $create_pdf = true;

        if ( $overwrite_pdf === 'no') 
        {
            if (file_exists( $temp_uploadfile ) ) 
            {
                $connect_api = false;
                $create_pdf = false;
            }
            else 
            {
                $create_pdf = true;

                //If client is not set, then connect to the PDF Crowd server for the first time
                //indepently of which iteration this is
                if ( $client === null ) {
                    $connect_api = true;
                }

                if ( $debug_mode === 'yes' ) 
                {
                    echo __('Uploaded file does not exist', 'pdfcrowd-wp');
                }
            }
        }
        
        if ( $overwrite_pdf === 'datachange') 
        {
            //Any post in PDF (in posts array) that should be created - has been changed?
            $datachanged = false; 
            if ( !isset( $data_postids ) ) {
                if ( $debug_mode === 'yes' )
                {
                    echo __('Datafields must be given', 'pdfcrowd-wp');
                }                
            }
            else 
            {
                foreach ( $data_postids as $xp )
                {    
                    $file_datachanged = $this->check_path . 'datachanged-' . $xp . '.ini';   
                    if ( $debug_mode === 'yes' )
                    {
                        echo __('Checking file', 'pdfcrowd-wp');
                        var_dump ( $file_datachanged );
                    }
                    if (file_exists($file_datachanged)) 
                    {
                        $this->remove_files[] = $file_datachanged;
                        $datachanged = true;
                    }    
                }            
            }
            
            //If data has changed, then connect to api and create pdf
            if ( $datachanged === true ) 
            {
                $connect_api = true;
                $create_pdf = true;       
            }               
            else 
            {
                //Data has not changed
                $connect_api = false;
                $create_pdf = false;                
            }
        }      
                        
        //Make viewed PDF "up to date" pdf by using a folder with the base
        //filename with dynamically created filenames within that folder
        //Every time all files within that folder is removed and thereafter
        //the new pdf with unique timestamp is created            
        $base_path = $upload_basedir . '/' . $path;           
        $base_path_outfile = $base_path . '/' . $out_file;
        
        //Try to create folder based on filename only (without extension). 
         //If it already exists don't do anything
         @mkdir ( $base_path );
         @mkdir ( $base_path_outfile ); 

         //add current post/page (viewed) id to folder-structure
        $xp = get_the_ID();
        @mkdir ( $base_path_outfile . '/' . $xp );            

        if ( $create_pdf === true )
        {

            //Remove all files within this (sub)folder
            //
            $files = glob( $base_path_outfile . '/' . $xp . '/*'); // get all file names            
            //Remove files in folder
            foreach($files as $file)
            {
              if( is_file($file)) 
              {
                    @unlink($file);
              }
            }

             //Create a new file within this folder
            $bfilename = time() . '.pdf';

        }
        else
        {                                
            //Copy from current file to new filename
           //and then remove the (current) file
           //If it should not be overwrritten, just use the first file in the folder
           $files = glob( $base_path_outfile . '/' . $xp . '/*'); // get all file names     
                          
           if ( !empty ( $files ) )
           {
               //If no overwrrting or if user for some reason doesn't want the PDF to be refreshed
                if ( $overwrite_pdf === 'no' )
                {
                    $bfilename = basename( $files[0] ); //Get existing file in folder (dont do any copy or such)
                }
                else 
                {
                    $bfilename = time() . '.pdf';
                    @copy ( $files[0], $base_path_outfile . '/' . $xp . '/' . $bfilename );                
                }
           }
           else
           {
               //No files in created folder
               //Create a new file within this folder
                $bfilename = time() . '.pdf';
                $create_pdf = true;
                $connect_api = true;
                $datachanged = true;
           }

           $dont_remove = $base_path_outfile . '/' . $xp . '/' . $bfilename;
            //Remove files in folder, but not the one copied/created above
            foreach($files as $file)
            {
              if( is_file($file)) 
              {
                if ( $file !== $dont_remove )
                {
                    @unlink($file);
                }
              }
            }

        }

        //Create the final link to the PDF
        $file_part = '/' . $out_file . '/' . $xp . '/' . $bfilename;
        $temp_uploadfile = $base_path . '/' . $file_part;
        $pdf_filename = $file_part;    
        
             
        //If creating pdf from generated html content that this plugin has generated
       if ( trim( $acu_content_html_total ) == '<div style="page-break-before:always">' )
       {
           if ( $debug_mode === 'yes' )
           {
               echo __('No PDF is generated because there are no content fetched based on values given in shortcode.','pdfcrowd-wp' );
           }
           return;
       }


        // create to PDF Crowd API server and create a client instance
        if ( $connect_api === true) 
        {
            try {
                $client = new Pdfcrowd( $this->username, $this->userpass);
                if ( $debug_mode === 'yes') 
                {
                   echo '<hr /><strong>';
                   echo __('Tokens left:', 'pdfcrowd-wp'); 
                   echo '</strong>';
                   echo $client->numTokens();
                   echo '<br />';
                }
            }
            catch (Exception $e) {
                if ( $debug_mode === 'yes' )
                {
                    echo $e->getMessage();
                }
            }                    
        }

        if ( $debug_mode === 'yes') {
            echo '<hr /><strong>';                      
            echo __('Iteration: ', 'pdfcrowd-wp');
            echo $iteration_nr . '</strong><br /><strong>';
            echo __('create pdf:','pdfcrowd-wp');
            echo '</strong>';
            var_dump ( $create_pdf );
            echo '<strong>';
            echo __('connect api', 'pdfcrowd-wp');
            echo '</strong>';
            var_dump ( $connect_api );
            echo '<br />';
        }             

        
        //If lastshortcode set on page, remove datachange(s) file
        //Used when creating a pdf with datafields
        if ( $last_shortcode === 'yes' )
        {
            foreach ( $this->remove_files as $rf )
            {                   
                if (file_exists($rf)) 
                {
                    @unlink ( $rf );
                }    
            }

        }        

        //Create the actual PDF with defined settings
        if ( $create_pdf === true ) 
        {
            //What unit to use when generating the PDF 
            $unit = $this->options['pdfcrowd_pdf_page_baseunit'];  

             //Settings/options (set in admin option page)
             $pdf_width = $this->options['pdfcrowd_pdf_width'];
             if ( $pdf_width !== 0) {
                 $client->setPageWidth( $pdf_width . $unit );
             }

             $pdf_height = $this->options['pdfcrowd_pdf_height'];
             if ( $pdf_height !== 0 ) { 
                 $client->setPageHeight ( $pdf_height . $unit );
             }

             //Margins (sets to zero if none given)
             $pdf_margin_top = $this->options['pdfcrowd_pdf_margin_top'];
             $pdf_margin_right = $this->options['pdfcrowd_pdf_margin_right'];
             $pdf_margin_bottom = $this->options['pdfcrowd_pdf_margin_bottom'];
             $pdf_margin_left = $this->options['pdfcrowd_pdf_margin_left'];

             //Set margins (but only when at least one margin is given)
             if ( $pdf_margin_top !== 0 && $pdf_margin_right !== 0 && $pdf_margin_bottom !==0 && $pdf_margin_left !== 0 )
             {                  
                 $client->setPageMargins( $pdf_margin_top . $unit, $pdf_margin_right . $unit, $pdf_margin_bottom . $unit, $pdf_margin_left . $unit );
             }

             //Footer
             $pdf_footer_html = $this->options['pdfcrowd_pdf_footer_html'];
             if ( strlen ( $pdf_footer_html ) > 0 ) {
                 $client->setFooterHtml( $pdf_footer_html );
             }

             //Header
             $pdf_header_html = $this->options['pdfcrowd_pdf_header_html'];
             if ( strlen ( $pdf_header_html ) > 0 ) {
                 $client->setHeaderHtml( $pdf_header_html );
             }        

             //Watermark
             $pdf_watermark_url = $this->options['pdfcrowd_pdf_watermark_url'];
             $pdf_watermark_offsetx = $this->options['pdfcrowd_pdf_watermark_offset_x'];
             $pdf_watermark_offsety = $this->options['pdfcrowd_pdf_watermark_offset_y'];
             if ( strlen ( $pdf_watermark_url) >0 ) {
                 $client->setWatermark( $pdf_watermark_url, $pdf_watermark_offsetx . $unit , $pdf_watermark_offsety . $unit);
             }        
             $pdf_watermark_rotation = $this->options['pdfcrowd_pdf_watermark_rotation']; //angle
             if ( $pdf_watermark_rotation !== 0) {
                 $client->setWatermarkRotation( $pdf_watermark_rotation );        
             }        
             $pdf_watermark_inbackground = $this->options['pdfcrowd_pdf_watermark_in_background'];
             $client->setWatermarkInBackground ( $pdf_watermark_inbackground );

             //Misc
             $pdf_fail_on_non200 = $this->options['pdfcrowd_pdf_fail_on_non200'];
             $client->setFailOnNon200( $pdf_fail_on_non200 );

             $pdf_footer_logo = $this->options['pdfcrowd_pdf_pdfcrowd_logo'];
             $client->enablePdfcrowdLogo( $pdf_footer_logo );

             //Prints at most the specified number of pages.
             $pdf_maxpages = (int)$this->options['pdfcrowd_pdf_max_pages'];
             if ( $pdf_maxpages !== 0) {
                 $client->setMaxPages( $pdf_maxpages );
             }
                          
             $pdf_pagemode = (int)$this->options['pdfcrowd_pdf_page_mode'];
             $client->setPageMode( $pdf_pagemode );

             $pdf_initialzoom = $this->options['pdfcrowd_pdf_initial_pdf_zoom'];
             if ( $pdf_initialzoom !== 0) {
                 $client->setInitialPdfExactZoom( $pdf_initialzoom );
             }

             $pdf_zoomtype = (int)$this->options['pdfcrowd_pdf_initial_pdf_zoom_type'];        
             if ( $pdf_zoomtype !== 0) {
                 $client->setInitialPdfZoomType( $pdf_zoomtype );
             }

             $pdf_pagelayout = (int)$this->options['pdfcrowd_pdf_page_layout'];
             $client->setPageLayout( $pdf_pagelayout );

             $pdf_nocopy = (int)$this->options['pdfcrowd_pdf_no_copy'];
             if ( $pdf_nocopy === 1) {
                 $client->setNoCopy( true );
             }
             else {
                 $client->setNoCopy( false );            
             }

             $pdf_nomodify = (int)$this->options['pdfcrowd_pdf_no_modify'];
             if ( $pdf_nomodify === 1 ) {
                 $client->setNoModify( true );
             }
             else {
                 $client->setNoModify( false );
             }

             $pdf_noprint = (int)$this->options['pdfcrowd_pdf_no_print'];
             if ( $pdf_noprint === 1 ) {
                 $client->setNoPrint( true );
             }
             else {
                 $client->setNoPrint( false );
             }

             $pdf_ownerpassword = $this->options['pdfcrowd_pdf_owner_pwd'];
             if ( strlen ( $pdf_ownerpassword) > 0) {
                 $client->setOwnerPassword( $pdf_ownerpassword );
             }

             $pdf_userpassword = $this->options['pdfcrowd_pdf_user_pwd'];
             if ( strlen( $pdf_userpassword ) > 0 ) {
                 $client->setUserPassword( $pdf_userpassword );
             }

             $pdf_author = $this->options['pdfcrowd_pdf_author'];
             if ( strlen ( $pdf_author) > 0 ) {
                 $client->setAuthor( $pdf_author );
             }

             $pdf_encrypted = (int)$this->options['pdfcrowd_pdf_encrypted'];
             if ( $pdf_encrypted === 1) {     
                 $client->setEncrypted( true );
             }

             $pdf_usecss = (int)$this->options['pdfcrowd_pdf_use_print_media'];
             if ( $pdf_usecss === 1) {
                $client->usePrintMedia( true );
             }
             else {
                $client->usePrintMedia( false );
             }

             $pdf_nohyperlinks = (int)$this->options['pdfcrowd_pdf_no_hyperlinks'];
             if ( $pdf_nohyperlinks === 1 ) {
                 $enable_hyperlinks = false;
             }
             else {
                 $enable_hyperlinks = true;
             }
             $client->enableHyperlinks ( $enable_hyperlinks );

             $pdf_nojs = (int)$this->options['pdfcrowd_pdf_no_javascript'];
             if ( $pdf_nojs === 1) {
                 $enable_js = false;
             }
             else {
                 $enable_js = true;
             }
             $client->enableJavaScript( $enable_js );

             $pdf_htmlzoom = $this->options['pdfcrowd_pdf_html_zoom'];
             if ( $pdf_htmlzoom !== 0 ) { 
                 $client->setHtmlZoom( $pdf_htmlzoom );
             }
             else {
                 $client->setHtmlZoom( 100 ); //If I don't set this it would be 200.'
             }

             $pdf_nobackgrounds = $this->options['pdfcrowd_pdf_no_backgrounds'];
             if ( $pdf_nobackgrounds === 1 ) {
                 $enabled_backgrounds = false;
             }
             else {
                 $enabled_backgrounds = true;
             }
             $client->enableBackgrounds( $enabled_backgrounds );


             $pdf_noimages = (int)$this->options['pdfcrowd_pdf_no_images'];
             if ( $pdf_noimages === 1) {
                 $enable_images = false;
             }
             else {
                 $enable_images = true;
             }
             $client->enableImages( $enable_images );

             $pdf_numbering_offset = $this->options['pdfcrowd_pdf_page_numbering_offset'];
             if ( $pdf_numbering_offset !== 0 ) {
                 $client->setPageNumberingOffset( $pdf_numbering_offset );
             }

             $pdf_excludelist = $this->options['pdfcrowd_pdf_header_footer_page_exclude_list'];
             if ( strlen ( $pdf_excludelist ) > 0 ) {
                 $client->setHeaderFooterPageExcludeList( $pdf_excludelist );
             }

             //Set SSL to true if connection is over SSL
            if (is_ssl() && $use_ssl === null) {
                 $client->useSSL(true); //do this when found this is an a ssl-site.        
            }

            //If speficially set to yes in shortcode, use ssl
            if ($use_ssl === 'yes') {
                $client->useSSL(true);
            }

            //If speficially set to no in shortcode, dont use ssl
            if ( $use_ssl === 'no') {
                $client->useSSL(false);
            }

            //Fetch content from current post
            if ($acu_url === 'current' )
            {
                $acu_url = ''; //Make sure not creating pdf from url. Because this is current page/post it grabs the content instead of the url                
                $data_postid = null;
                $acu_content_html_total = strip_shortcodes( get_the_content() );                        
            }
            
             //Actual conversions to pdf   
            
            //If code is generated from code (not from within this plugin)            
            if ( strlen ($generate_fromhtml) >0 )
            {
                $acu_url = ''; //Make sure first condition below is not true
                $acu_content_html_total = $generate_fromhtml; //Do creation down below
            }
                            
            //If creating directly from an url. If not specific data_postid (or all) is set, then use url set
            //If using current post, also then grab current permalink
             if ( ( strlen ( $acu_url )> 0 && strlen ( $acu_content_html_total ) > 0 && $data_postid === null )) 
             {
                 if ( $debug_mode === 'yes' )
                 {
                     echo __('Create pdf from url ',  'pdfcrowd-wp');          
                     var_dump ( $acu_url );
                 }
                 $pdf = $client->convertURI($acu_url, fopen( $temp_uploadfile, 'wb'));
             }
             else if ( strlen ( $acu_content_html_total ) >0 )
             {
                 if ( $debug_mode === 'yes' )
                 {
                    echo __('Create pdf from html content',  'pdfcrowd-wp');          
                    var_dump ( $acu_content_html_total );                     
                 }

                 $pdf = $client->convertHtml( $acu_content_html_total, fopen( $temp_uploadfile, 'w+'));
             }
             else 
             {
                 if ( $debug_mode === 'yes' )
                 {
                     echo '<p class="error">';
                     echo __('PDF can not be created. Probably some issues with wrong post/page-id or post/page id not set for convert_urls', 'pdfcrowd-wp' );
                     echo '</p>';
                 }
                 return;
             }
                          
            if ( $debug_mode === 'yes') 
            {
               echo '<hr /><strong>';
               echo __('Tokens left after creation:', 'pdfcrowd-wp'); 
               echo '</strong>';
               echo $client->numTokens();
               echo '<br />';
            }                     

        }
        //End (if $create_pdf === true)

        if ( $debug_mode === 'yes') 
        {
            echo '<hr /><strong>';
            echo __('Options', 'pdfcrowd-wp');
            echo '</strong><br />';

            //Dont show username and password in debugmode
            $show_options = $this->options;
            unset( $show_options['pdfcrowd_user'] );
            unset( $show_options['pdfcrowd_key'] );
            var_dump ( $show_options );

            if ( $show_clientinfo === 'yes' && $connect_api === true)
            {
                //Here the acual username and password is given
                //Use with caution!
                echo __('client', 'pdfcrowd-wp');
                var_dump ( $client );
            }
        }

        $pdf_location = "/wp-content/uploads/{$path}{$pdf_filename}"; //Question mark here at the end so PDF will be updated when opens up in browser

        //Create a pdf downloadable link to the created pdf?
        if ( $create_downloadlink === 'yes') 
        {
            //If title is set for this index in iteration, use that
            //If not set, use default
            if ( isset ( $all_linktitles[$acu_key]) ) {
                $link_title = $all_linktitles[$acu_key];
            }
            else {
                $link_title = $link_title_default;                        
            }

            $html_link = '<a';
            if ( $targetblank === 'yes')
            {
                $html_link .= ' target="_blank"';
            }                    
            if ( strlen ( $html_class) > 0) 
            {
                $html_link .= ' class="' . $html_class. '"';
            }        
            $html_link .= ' href="' . $pdf_location . '">';
            $html_link .=$link_title;
            $html_link .= '</a>';
            $html_content .= $html_link;
       }

        $iteration_nr++;               
} //END foreach all convert urls                

//Final html content
return $html_content;
}

}

$pdfgeneratorcrowd = new pdfgeneratorcrowd();

if( is_admin() ) 
{
$pdfcrowdoptions = new pdfgeneratorcrowdoptions();
}

}