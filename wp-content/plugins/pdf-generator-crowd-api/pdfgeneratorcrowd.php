<?php
/*
Plugin Name: PDF Generator Crowd API
Plugin URI: http://www.wibergsweb.se/plugins/pdf-generator-crowd
Description: a PDF generator that uses the PDF Crowd API to create pdf files.
Version: 1.1
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
        add_action( 'init', array( $this, 'loadjslanguage' ) );
        $this->options = get_option( 'pdfcrowd_option' );        
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
            'crowdpdfwpjs',
            plugins_url( '/js/wibergsweb.js' , __FILE__, array('jquery') )
        );      
        
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
        $link_title_default = __('Download PDF', 'pdfcrowd-wp');
        
        $defaults = array(
            'debug_mode' => 'no', //Display variables used for creating pdf etc.
            'use_ssl' => null,    //set to yes if you want to create a pdf from a site that uses ssl. Set to no if you dont want ssl. If set to null it would identify if current website has ssl enabled or not
            'show_clientinfo' => 'no', //When having debug_mode set to yes , show clients info as well (including username and password. Use with caution!)
            'convert_urls' => '', //Convert urls (set to current if you want to create pdf of current page/post or put a number between { and } and the plugin will fetch page with that post/page id. If having several urls separate them by using semicolon). 
            'out_files' => 'current', //What filename(s) to save as. If having having several urls defined, add equal many files separated by semicolon
            'overwrite_pdf' => 'no', //dont connect to PDF Crowd (or overwrite the pdf) if the pdf already exists (saves tokens in your account and resources on your server!!!!)
            'path' => 'pdfcrowd', //This is the base path AFTER the upload path of Wordpress (eg. pdfcrowd = /wp-content/uploads/pdfcrowd)            
            'create_downloadlink' => 'no', //Create a downloadable link to the created pdf
            'html_class' => '', //If you want to style the link use this class
            'link_titles' => $link_title_default, //What to show in link(s) creating a downloadlink. Several link should be separated by semicolon
            'data_postid' => null, //Data from postid. Fetch data from this specific post/page id
            'data_fields' => '',   //Tell name of fields that should be used when fetching data from a specific post/page
            'data_acfkeys' => '' //If using ACF, then tell key (this is important) of each value (this is used for retrieving labels in for example for usage in headers of a repeater-field)
        );
                
        if ( $this->options === false )
        {
            if ( $debug_mode === 'yes') {
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
        
        //Extract values from shortcode and if not set use defaults above
        $args = wp_parse_args( $attrs, $defaults );
        extract( $args );
        
        
        if ( $debug_mode === 'yes') {
            echo '<hr /><strong>';
            echo __('Arguments', 'pdfcrowd-wp');
            echo '</strong><br />';
            var_dump ( $args );            
        }
        
        $html_content = ''; //to return from shortcode
        
       //Go through all urls and files defined (could be one of each as well)
        $all_converturls = explode (';', $convert_urls);
        $all_outfiles = explode(';', $out_files);
        $all_linktitles = explode(';', $link_titles);
        
        //If not equaly many files as urls defined, use
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
        
        @mkdir ( $upload_basedir . '/pdfcrowd' ); //Create folder if it does not exist     
        
        global $post;
        
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
        
        foreach ( $all_converturls as $acu_key=>$acu_url ) 
        {
                //If out_file set to current, fetch current post/page
                if ( $acu_url === 'current' ) 
                {
                    $out_file = basename( get_the_permalink( $post->ID ) );
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
                    $acu_content_html = apply_filters('the_content', $acu_content->post_content);

                    $out_file = basename( $acu_url );
                    
                    $data_pid = (int)$data_postid;
                    
                    
                    $ex_datafields = explode(';', $data_fields);
                    $ex_datakeys = explode (';', $data_acfkeys);                        
                                            
                    if ( $debug_mode === 'yes' )
                    {
                        echo __('Fetching page/post...', 'pdfcrowd-wp');
                        var_dump ( $acu_url );
                        echo __('...with data from postid', 'pdfcrowd-wp');
                        var_dump ( $data_pid  );
                        echo __('...using these datafields', 'pdfcrowd-wp');                        
                        var_dump ( $ex_datafields );
                        echo __('..ACF datafieldkey values for each datafield'); 
                        var_dump ( $ex_datakeys );
                    }
                                            
                        //Go through datafields and replace fetched content with
                        //actual values from these fields (for data_postid)
                        $key_index = 0;
                        foreach ( $ex_datafields as $eda )
                        {                            
                            $datafield_value = get_field( $eda, $data_pid );
                            if ( is_array ( $ex_datakeys )  ) {
                                $key_datafield = $ex_datakeys[$key_index];
                            }
                            else {
                                $key_datafield = $key_index;
                            }
                            $key_index++;
                            
                            //If datafield value is array, then insert a table with that array
                            //(with values from array)
                            if ( is_array ( $datafield_value ) )
                            {
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
                                            if (function_exists('get_field_object') )
                                            {
                                                $acf_fieldobj  = get_field_object( $key_datafield );
                                                if ( isset ( $acf_fieldobj['sub_fields'])) {
                                                    $sub_fields_exists = true;
                                                }                                            
                                            }

                                            
                                            $include_tableheader = false;
                                            $dv .= '<thead><tr class="row-' . $table_row . '">';
                                            $table_col = 1;
                                            foreach ( $dfv as $subarr_key => $subarr_item )
                                            {
                                                //If this is a field where subfields exists (acf repeater)
                                                //then use label from subfields of repeater-field
                                                if ( $sub_fields_exists === true )
                                                {
                                                    $subarr_key = $acf_fieldobj['sub_fields'][$table_col-1]['label'];
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
                                        foreach ( $dfv as $subarr_item_value )
                                        {
                                            if (is_object( $subarr_item_value))
                                            {
                                                $subarr_item_value = $subarr_item_value->post_title;
                                            }
                                            $dv .= '<td class="col-' . $table_col . '">' .$subarr_item_value . '</td>';
                                            $table_col++;
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
                            
                            $htmlcontent_search_for = '[' . $eda . ']';
                            $acu_content_html = str_replace($htmlcontent_search_for, $datafield_value, $acu_content_html );
                        }
                        
                         if ( $debug_mode === 'yes' )
                         {
                            echo __('...content based on a specific post/page with actual datafields included', 'pdfcrowd-wp');                        
                            var_dump ( $acu_content_html );
                         }
                         

                }
                
                //If outfile has .pdf extension set, remove it here
                if ( stristr( $all_outfiles[$acu_key], '.pdf' ) !== false ) 
                {
                    $out_file = substr( $all_outfiles[$acu_key], 0, -4); //remove four last chars (.pdf)
                }
                else {
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

                     //Actual conversions to pdf
                     if ( strlen ( $acu_content_html ) >0 )
                     {
                         if ( $debug_mode === 'yes' )
                         {
                             echo __('Create pdf from html content',  'pdfcrowd-wp');          
                             var_dump ( $acu_content_html);
                         }
                         
                         $pdf = $client->convertHtml( $acu_content_html, fopen( $temp_uploadfile, 'wb'));
                     }
                     else if ( strlen ( $acu_url )> 0) {
                         if ( $debug_mode === 'yes' )
                         {
                             echo __('Create pdf from url ',  'pdfcrowd-wp');          
                             var_dump ( $acu_url );
                         }
                         
                             $pdf = $client->convertURI($acu_url, fopen( $temp_uploadfile, 'wb'));
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

                //PDF filename and location
                $pdf_filename = $out_file . '.pdf';                
                $pdf_location = "/wp-content/uploads/{$path}/{$pdf_filename}"; //TODO: Fixed bugg if having this path in a custom post type. Now path gets to be correct

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
        
        
        return $html_content;


      
        }
        
        
}
        
$pdfgeneratorcrowd = new pdfgeneratorcrowd();

if( is_admin() ) 
{
$pdfcrowdoptions = new pdfgeneratorcrowdoptions();
}

}