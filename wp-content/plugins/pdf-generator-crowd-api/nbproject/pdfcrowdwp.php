<?php
/*
Plugin Name: PDF Crowd WP
Plugin URI: http://www.wibergsweb.se/plugins/pdf-crowd-wp
Description: a PDF generator that uses the PDF Crowd API to create pdf files
Version: 1.0
Author: Wibergs Web
Author URI: http://www.wibergsweb.se/
Text Domain: pdfcrowd-wp
Domain Path: /lang
License: GPLv2
*/
defined( 'ABSPATH' ) or die( 'No access allowed!' );

require_once('core/pdfcrowd.php');
require_once('core/options.php');

if( !class_exists('pdfcrowdwp') ) {
            
    class pdfcrowdwp
    {                    
    private $errormessage = null;
    public $username = null;
    public $userpass = null;
    public $out_file = '';
    private $options;
    

    /*
    *  Constructor
    *
    *  This function will construct all the neccessary actions, filters and functions for the pdfcrowdwp plugin to work
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
        $link_title = __('Download PDF', 'pdfcrowd-wp');
        
        $defaults = array(
            'debug_mode' => 'no', //Display variables used for creating pdf etc.
            'show_clientinfo' => 'no', //When having debug_mode set to yes , show clients info as well (including username and password. Use with caution!)
            'convert_url' => '', //Convert an url (set to current if you want to create pdf of current page/post)
            'out_file' => null, //What filename to save as
            'overwrite_pdf' => 'no', //If out_file is set, dont connect to PDF Crowd if the pdf already exists (saves tokens in your account and resources on your server!!!!)
            'path' => 'pdfcrowd', //This is the base path AFTER the upload path of Wordpress (eg. pdfcrowd = /wp-content/uploads/pdfcrowd)            
            'remove_tempfiles' => 'yes', //Remove temporary files from pdfcrowd folder after pdf creation (except the one created with this shortcode)
            'create_downloadlink' => 'no', //Create a downloadable link to the created pdf
            'link_title' => $link_title, //What to show in link creating a downloadlink
            'link_class' => '' //If you want to style the link use this class
        );
                
        if ( $this->options === false )
        {
            if ( $debug_mode === 'yes') {
                echo __('You must set at least username and password for your PDF Crowd account in your wordpress admin. Look under settings / Crowd PDF settings', 'pdfcrowd-wp');   
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
        
        //If setting convert_url to current, then convert_url would be set to current page or post
        if ( $convert_url === 'current' )
        {
            global $post;
            $permalink = get_the_permalink( $post->ID );
            $convert_url = $permalink;
            $args['convert_url'] = $convert_url;
        }
        
        if ( $debug_mode === 'yes') {
            echo __('Arguments', 'pdfcrowd-wp');
            var_dump ( $args );            
        }
                
        //Base upload path of uploads
        $upload_dir = wp_upload_dir();
        $upload_basedir = $upload_dir['basedir'];
        
        //Create a file set for temporary pdf file upload on the server
        $rand_nr = rand();
        @mkdir ( $upload_basedir . '/pdfcrowd' ); //Create folder if it does not exist        
        
        //Use has specific defined a file to save to                
        if ( strlen ( $out_file) > 0) 
        {
            //Remove .pdf (if) set by user in shortcode
            if (substr( $out_file, -4, 4) === '.pdf') {
                $out_file = substr( $out_file, 0, -4);
            }
            $rand_nr = $out_file; //Use so temp upload variable sets filename as the one user has defined            
        }
        
       $temp_uploadfile = $upload_basedir . '/' . $path . '/' . $rand_nr . '.pdf';

        if ( $debug_mode === 'yes') {
            echo __('Upload file:', 'pdfcrowd-wp');
            var_dump ( $temp_uploadfile );
        }
                
        $connect_api = true;
        
        //If file exists dont connect to the PDF Crowd server and create pdf if overwrite_pdf iset set to no
        //This saves tokens!!!        
        if ( $overwrite_pdf === 'no') 
        {
            if (file_exists( $temp_uploadfile) ) 
            {
                $connect_api = false;
            }
            else 
            {
                if ( $debug_mode === 'yes' ) 
                {
                     echo __('Uploaded file does not exist', 'pdfcrowd-wp');
                }
            }
        }
        
        // create an API client instance and save the pdf in a (temporary or user-defined) file
        // if connect_api is set to true and user not in admin mode
        if ( $connect_api === true) 
        {
            try {
                $client = new Pdfcrowd( $this->username, $this->userpass);
            }
            catch (Exception $e) {
                if ( $debug_mode === 'yes' )
                {
                    echo $e->getMessage();
                }
            }
        
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

             //Set SSL to true if connection is ove SSL
             if (is_ssl()) {
                 $client->useSSL(true); //do this when found this is an a ssl-site.        
            }

             //Actual conversions to pdf
             if ( strlen ( $convert_url )> 0) {
                 try {
                     $pdf = $client->convertURI($convert_url, fopen( $temp_uploadfile, 'wb'));
                 }
                 catch (Exception $e) {            
                     if ( $debug_mode === 'yes') 
                     {
                         echo $e->getMessage();
                     }
                 }
             }

             //Remove files from temporary folder, but only if not a specific filename is set
             if ($remove_tempfiles === 'yes' && $outfile !== null ) 
             {
                 $files = glob($upload_basedir . '/' . $path . '/*'); // All file names in this folder
                 foreach($files as $file)
                 {
                   //Remove file (s) except this one just created
                   if(is_file($file) && $file !=  $temp_uploadfile) 
                   {
                     @unlink($file); // delete file- Suppress errors if there are permission denied or such failure
                   }
                 }
             }
                
        }
        //End if ( $connect_api === true) 

        if ( $debug_mode === 'yes') 
        {
            echo __('options', 'pdfcrowd-wp');
            var_dump ( $this->options );

            if ( $show_clientinfo === 'yes' && $connect_api === true)
            {
                echo __('client', 'pdfcrowd-wp');
                var_dump ( $client );
            }

        }
        
        $pdf_location = "../wp-content/uploads/pdfcrowd/{$rand_nr}.pdf";
            
        //Create a pdf downloadable link to the created pdf?
        if ( $create_downloadlink === 'yes') 
        {
            $html_link = '<a';
            if ( strlen ( $link_class) > 0) 
            {
                $html_link .= ' class="' . $link_class. '"';
            }        
            $html_link .= ' href="' . $pdf_location . '">';
            $html_link .= $link_title;
            $html_link .= '</a>';
            return $html_link;
        }
               
        return '';


      
        }
        
        
}
        
$pdfcrowdwp = new pdfcrowdwp();

if( is_admin() ) 
{
$pdfcrowdoptions = new pdfcrowdwpoptions();
}

}