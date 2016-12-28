<?php
if( !class_exists('pdfgeneratorcrowdoptions') ) {
            
class pdfgeneratorcrowdoptions
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            __('admin settings', 'pdfcrowd-wp'), 
            __('Generator PDF Crowd settings', 'pdfcrowd-wp'), 
            'manage_options', 
            'crowdpdf-wp-options', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'pdfcrowd_option' );
        ?>
        <div class="wrap">
            <h2><?php echo __('PDF Crowd', 'pdfcrowd-wp');?></h2>   
            <?php
            //Login to find out settings of user
            $username = $this->options['pdfcrowd_user'];
            $userpass = $this->options['pdfcrowd_key'];

            // create an API client instance
            try {
                $client = new Pdfcrowd( $username, $userpass);       
                echo '<span class="tokens-left">' . __('Tokens left', 'pdfcrowd-wp') . ':</span> ' . $client->numTokens();
            }
            catch (Exception $e) {
                echo '<span style="color:#ff0000;font-weight:bold;">' . $e->getMessage() . '</span>';
            }
                
            
            ?>            
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'pdfcrowd_option_group' );   
                do_settings_sections( 'pdfcrowd_options' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'pdfcrowd_option_group', // Option group
            'pdfcrowd_option', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        //User credentials section
        add_settings_section(
            'pdfcrowd_sectionuser', // ID
            __('User credentials for the Crowd PDF API', 'pdfcrowd-wp'), // Title
            array( $this, 'print_section_info' ), // Callback
            'pdfcrowd_options' // Page
        );  
        
        $field_arr = array();
        $field_arr['field_id'] = 'pdfcrowd_user'; //set in plugin
        $field_arr['field_type'] = 'text';
        $field_arr['class'] = 'credentials-input';
        
        add_settings_field(
             $field_arr['field_id'], // ID
            __('Username', 'pdfcrowd-wp') , // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_sectionuser', // Section           
            $field_arr
        );      
        
        $field_arr = array();
        $field_arr['field_id'] = 'pdfcrowd_key'; //set in plugin
        $field_arr['field_type'] = 'password';  
        $field_arr['class'] = 'credentials-input';
        
        add_settings_field(
             $field_arr['field_id'], // ID
            __('Key', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_sectionuser', // Section         
            $field_arr                
        );              
        
        //Width and margins section
        add_settings_section(
            'pdfcrowd_section_dimension', // ID 
            __('PDF widths and margins', 'pdfcrowd-wp'), // Title
            array( $this, 'dimension_section_info' ), // Callback
            'pdfcrowd_options' // Page
        ); 
        
        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_page_baseunit';     //set in plugin
        $field_arr['field_type'] = 'select';         
        $field_arr['field_values'] = array(
                                                                'mm' => __('Millimeters', 'pdfcrowd-wp'),
                                                                'cm' => __('Centimeters','pdfcrowd-wp'),
                                                                'in' => __('Inches','pdfcrowd-wp'),                                                                
                                                                'pt' => __('Points','pdfcrowd-wp')
                                                        );       
         add_settings_field(
             $field_arr['field_id'], // ID
            __('Specifies what unit to use in margins/layout of the pdf', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_dimension', // Section           
            $field_arr
        );     

                 
        $field_arr = array();
        $field_arr['field_id'] = 'pdfcrowd_pdf_width'; //set in plugin
        $field_arr['field_type'] = 'number';        
        add_settings_field(
             $field_arr['field_id'], // ID
            __('Width of PDF', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_dimension', // Section           
            $field_arr
        );   

        $field_arr = array();
        $field_arr['field_id'] = 'pdfcrowd_pdf_height'; //set in plugin
        $field_arr['field_type'] = 'number';        
        add_settings_field(
             $field_arr['field_id'] , // ID
            __('Height of PDF. Set -1 for a single page PDF.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_dimension', // Section           
            $field_arr
        );            

        $field_arr = array();        
        $field_arr['field_id'] = 'pdfcrowd_pdf_margin_top'; //set in plugin
        $field_arr['field_type'] = 'number';        
        add_settings_field(
             $field_arr['field_id'], // ID
            __('Top PDF margin', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_dimension', // Section           
            $field_arr
        );        
        
        $field_arr = array(); 
        $field_arr['field_id'] = 'pdfcrowd_pdf_margin_right'; //set in plugin
        $field_arr['field_type'] = 'number';        
        add_settings_field(
             $field_arr['field_id'], // ID
            __('Rigth PDF margin', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_dimension', // Section           
            $field_arr
        );         
        
        $field_arr = array(); 
        $field_arr['field_id'] = 'pdfcrowd_pdf_margin_bottom';         //set in plugin
        $field_arr['field_type'] = 'number';        
        add_settings_field(
             $field_arr['field_id'], // ID
            __('Bottom PDF margin', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_dimension', // Section           
            $field_arr
        );       
        
        $field_arr = array(); 
        $field_arr['field_id'] = 'pdfcrowd_pdf_margin_left';      //set in plugin
        $field_arr['field_type'] = 'number';        
        add_settings_field(
             $field_arr['field_id'], // ID
            __('Left PDF margin', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_dimension', // Section           
            $field_arr
        );         
        
        //Header and footer section        
        add_settings_section(
            'pdfcrowd_section_header_footer', // ID
            __('PDF header and footer', 'pdfcrowd-wp'), // Title
            array( $this, 'header_footer_section_info' ), // Callback
            'pdfcrowd_options' // Page
        ); 

    

        /*
       $field_arr = array();         
       $field_arr['field_id'] = 'pdfcrowd_pdf_footer_url';       //set in plugin
       $field_arr['field_type'] = 'text';       
       add_settings_field(
             $field_arr['field_id'], // ID
            'Load HTML code from the specified URL and place it inside the page footer. Same expanded variables as footer_html', // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_header_footer', // Section           
            $field_arr
        );          
       */

       $field_arr = array();        
       $field_arr['field_id'] = 'pdfcrowd_pdf_header_html';   //set in plugin
       $field_arr['class'] = 'textarea-option';
       $field_arr['field_type'] = 'textarea';             
       add_settings_field(
             $field_arr['field_id'], // ID
            __('Place the specified HTML code inside the page header.<br /><br />
                %u - URL to convert.
<br />%p - The current page number.
<br />%n - Total number of pages.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_header_footer', // Section           
            $field_arr
        );       
       
        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_footer_html'; //set in plugin
        $field_arr['field_type'] = 'textarea';        
       $field_arr['class'] = 'textarea-option';
        
        add_settings_field(
             $field_arr['field_id'], // ID
            __('Place the specified HTML code inside the page footer.<br /><br />
%u - URL to convert.<br />
%p - The current page number.<br />
%n - Total number of pages.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_header_footer', // Section           
            $field_arr
        );              
       
       /*
       $field_arr = array();        
       $field_arr['field_id'] = 'pdfcrowd_pdf_header_url';  //set in plugin
       $field_arr['field_type'] = 'text';             
       add_settings_field(
             $field_arr['field_id'] , // ID
            'Load HTML code from the specified URL and place it inside the page header. Same expanded variables as footer_html', // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_header_footer', // Section           
            $field_arr
        );          
       */
       
       $field_arr = array();        
       $field_arr['field_id'] = 'pdfcrowd_pdf_header_footer_page_exclude_list';   //set in plugin
       $field_arr['field_type'] = 'text';      
       $field_arr['class'] = 'exclude-list';
       
       add_settings_field(
             $field_arr['field_id'], // ID
            __('A comma seperated list of physical page numbers on which the header and footer are not printed.<br /><br />Negative numbers count backwards from the last page: -1 is the last page, -2 is the last but one page, and so on.
Example: "1,-1" will not print the header and footer on the first and the last page.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_header_footer', // Section           
            $field_arr
        );          

       $field_arr = array();        
       $field_arr['field_id'] = 'pdfcrowd_pdf_page_numbering_offset';      //set in plugin
       $field_arr['field_type'] = 'number';             
       add_settings_field(
             $field_arr['field_id'], // ID
            __('An offset between physical and logical page numbers. 
            Example: if set to "1" then the page numbering will start with 1 on the second page.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_header_footer', // Section           
            $field_arr
        );          

       
        //html options
        add_settings_section(
            'pdfcrowd_section_htmloptions', // ID
            __('HTML options', 'pdfcrowd-wp'), // Title
            array( $this, 'htmloptions_section_info' ), // Callback
            'pdfcrowd_options' // Page
        ); 
        
        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_no_images';        //set in plugin
        $field_arr['field_type'] = 'boolean';              
        add_settings_field(
             $field_arr['field_id'], // ID
            __('Do not print images.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_htmloptions', // Section           
            $field_arr
        );       

       $field_arr = array();         
       $field_arr['field_id'] = 'pdfcrowd_pdf_no_backgrounds';   //set in plugin
       $field_arr['field_type'] = 'boolean';              
        add_settings_field(
             $field_arr['field_id'], // ID
            __('Do not print backgrounds.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_htmloptions', // Section           
            $field_arr
        );            
             

        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_no_javascript';   //set in plugin
        $field_arr['field_type'] = 'boolean';              
        add_settings_field(
             $field_arr['field_id'], // ID
            __('Do not run JavaScript in web pages.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_htmloptions', // Section           
            $field_arr
        );             

        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_no_hyperlinks'; // set in plugin
        $field_arr['field_type'] = 'boolean';           
        add_settings_field(
             $field_arr['field_id'], // ID
            __('Do not create hyperlinks in the PDF.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_htmloptions', // Section           
            $field_arr
        );            

        /*
        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_text_encoding';     //set in plugin
        $field_arr['field_type'] = 'text';           
        add_settings_field(
             $field_arr['field_id'], // ID
            'The text encoding to use when none is specified in the web page. The default value is utf-8.', // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_htmloptions', // Section           
            $field_arr
        );            
        */

        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_use_print_media';    //set in plugin  
        $field_arr['field_type'] = 'boolean';           
        add_settings_field(
             $field_arr['field_id'], // ID
            __('Use the print CSS media type (if available).', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_htmloptions', // Section           
            $field_arr
        );           
        
        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_html_zoom';      //set in plugin
        $field_arr['field_type'] = 'number';              
        add_settings_field(
             $field_arr['field_id'], // ID
            __('HTML zoom in percents. It determines the precision used for rendering of the HTML content. Despite its name, it does not zoom the HTML content. Higher values can improve glyph positioning and can lead to overall better visual appearance of the generated PDF.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_htmloptions', // Section           
            $field_arr
        );               

        //pdf options
        add_settings_section(
            'pdfcrowd_section_pdfoptions', // ID
            __('PDF options', 'pdfcrowd-wp'), // Title
            array( $this, 'pdfoptions_section_info' ), // Callback
            'pdfcrowd_options' // Page
        ); 
        
        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_encrypted';   //set in plugin
        $field_arr['field_type'] = 'boolean';
        add_settings_field(
             $field_arr['field_id'] , // ID
            __('Encrypts the PDF. This prevents search engines from indexing the document.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_pdfoptions', // Section           
            $field_arr
        );     

        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_author';     //set in plugin
        $field_arr['field_type'] = 'text';    
        $field_arr['class'] = 'credentials-input';
        add_settings_field(
             $field_arr['field_id'], // ID
            __('Sets the PDF author field.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_pdfoptions', // Section           
            $field_arr
        );             

        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_user_pwd';   //set in plugin
        $field_arr['field_type'] = 'text';   
        $field_arr['class'] = 'credentials-input';        
        add_settings_field(
             $field_arr['field_id'], // ID
            __('Protects the PDF with an user password. When a PDF has an user password, it must be supplied in order to view the document and to perform operations allowed by the access permissions. At most 32 characters.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_pdfoptions', // Section           
            $field_arr
        );     

        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_owner_pwd';    //set in plugin       
        $field_arr['field_type'] = 'text';              
        $field_arr['class'] = 'credentials-input';        
        add_settings_field(
             $field_arr['field_id'], // ID
            __('Protects the PDF with an owner password. Supplying an owner password grants unlimited access to the PDF including changing the passwords and access permissions. At most 32 characters.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_pdfoptions', // Section           
            $field_arr
        );     
 
        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_no_print';       //set in plugin    
        $field_arr['field_type'] = 'boolean';              
         add_settings_field(
             $field_arr['field_id'], // ID
            __('Do not allow to print the generated PDF.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_pdfoptions', // Section           
            $field_arr
        );             

        $field_arr = array();          
        $field_arr['field_id'] = 'pdfcrowd_pdf_no_modify';     //set in plugin
        $field_arr['field_type'] = 'boolean';              
         add_settings_field(
             $field_arr['field_id'], // ID
            __('Do not allow to modify the PDF.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_pdfoptions', // Section           
            $field_arr
        );     

        $field_arr = array();          
        $field_arr['field_id'] = 'pdfcrowd_pdf_no_copy';     //set in plugin
        $field_arr['field_type'] = 'boolean';            
        add_settings_field(
            'pdfcrowd_pdf_no_copy', // ID
            __('Do not allow to extract text and graphics from the PDF.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_pdfoptions', // Section           
            $field_arr
        );            

        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_page_layout';     //set in plugin
        $field_arr['field_type'] = 'select';         
        $field_arr['field_values'] = array(
                                                                1 => __('Single page', 'pdfcrowd-wp') ,
                                                                2 => __('Continuous', 'pdfcrowd-wp'),
                                                                3 => __('Continuous facing', 'pdfcrowd-wp'),
                                                        );       
         add_settings_field(
             $field_arr['field_id'], // ID
            __('Specifies the initial page layout when the PDF is opened in a viewer:', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_pdfoptions', // Section           
            $field_arr
        );               

        $field_arr = array();          
        $field_arr['field_id'] = 'pdfcrowd_pdf_initial_pdf_zoom_type';       //set in plugin
        $field_arr['field_type'] = 'select';     
        $option_text = array();
        $option_text[0] = __('Zoom. The zoom is specified by the initial pdf zoom.', 'pdfcrowd-wp');
        $option_text[1] = __('Fit width', 'pdfcrowd-wp');
        $option_text[2] = __('Fit height', 'pdfcrowd-wp');
        $option_text[3] = __('Fit page', 'pdfcrowd-wp');
        
        $field_arr['field_values'] = array(
                                                                1 =>$option_text[1],
                                                                2 => $option_text[2],
                                                                3 => $option_text[3],
                                                                0 => $option_text[0]
                                                );              
         add_settings_field(
             $field_arr['field_id'], // ID
            __('Specifies the initial page zoom type when the PDF is opened in a viewer:', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_pdfoptions', // Section           
            $field_arr
        );          
         
         $field_arr = array();          
         $field_arr['field_id'] = 'pdfcrowd_pdf_initial_pdf_zoom';   //set in plugin
         $field_arr['field_type'] = 'number';    
         add_settings_field(
             $field_arr['field_id'], // ID
            __('Specifies the initial page zoom in percentage when the PDF is opened in a viewer:', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_pdfoptions', // Section           
            $field_arr
        );               

        $field_arr = array();          
        $field_arr['field_id'] = 'pdfcrowd_pdf_page_mode';  //set in plugin
        $field_arr['field_type'] = 'select';    
        $option_text = array();
        $option_text[0] = null;
        $option_text[1] = __('Neither document outline nor thumbnail images visible', 'pdfcrowd-wp');
        $option_text[2] = __('Thumbnail images visible', 'pdfcrowd-wp');
        $option_text[3] = __('Full-screen mode', 'pdfcrowd-wp');
        
        $field_arr['field_values'] = array(
                                                                1 => $option_text[1],
                                                                2 => $option_text[2],
                                                                3 => $option_text[3],
                                                        );       
        
         add_settings_field(
            'pdfcrowd_pdf_page_mode', // ID
            __('Specifies the appearance of the PDF when opened:', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_pdfoptions', // Section           
            $field_arr
        );    
         
         $field_arr = array();          
         $field_arr['field_id'] = 'pdfcrowd_pdf_max_pages';   //set in plugin
         $field_arr['field_type'] = 'number';    
         add_settings_field(
            'pdfcrowd_pdf_max_pages', // ID
            __('Prints at most the specified number of pages.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_pdfoptions', // Section           
            $field_arr
        );           

         /*
        $field_arr = array();          
        $field_arr['field_id'] = 'pdfcrowd_pdf_pdf_scaling_factor';  //set in plugin
        $field_arr['field_type'] = 'text';             
         add_settings_field(
             $field_arr['field_id'], // ID
            'The scaling factor used to convert between HTML and PDF. The default value is 1.333 (4/3) which makes the PDF content up to 1/3 larger than HTML.', // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_pdfoptions', // Section           
            $field_arr
        );        
         */

         /*
        $field_arr = array();          
        $field_arr['field_id'] = 'pdfcrowd_pdf_page_background_color';  //set in plugin
        $field_arr['field_type'] = 'text';             
         add_settings_field(
             $field_arr['field_id'], // ID
            'The page background color in RRGGBB hexadecimal format.', // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_pdfoptions', // Section           
            $field_arr
        );          
         */
         
        /*
        $field_arr = array();          
        $field_arr['field_id'] = 'pdfcrowd_pdf_page_transparent_background';  //set in plugin
        $field_arr['field_type'] = 'boolean';             
         add_settings_field(
             $field_arr['field_id'], // ID
            'Do not print the body background. Requires the following CSS rule to be declared:<br />'
                 . 'body {background-color:rgba(255,255,255,0.0);}', // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_pdfoptions', // Section           
            $field_arr
        );      
         */
         
         
        //Watermark         
        add_settings_section(
            'pdfcrowd_section_watermark', // ID
            __('Watermark', 'pdfcrowd-wp'), // Title
            array( $this, 'watermark_section_info' ), // Callback
            'pdfcrowd_options' // Page
        ); 

        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_watermark_url';           //set in plugin 
        $field_arr['field_type'] = 'text'; 
        $field_arr['class'] = 'larger-text';
        
        add_settings_field(
             $field_arr['field_id'], // ID
            __('A public absolute URL of the watermark image (must start either with http:// or https://). The supported formats are PNG and JPEG.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_watermark', // Section           
            $field_arr
        );      
        
        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_watermark_offset_x';    //set in plugin
        $field_arr['field_type'] = 'number';              
        add_settings_field(
             $field_arr['field_id'], // ID
            __('The horizontal watermark offset in units. The default value is 0.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_watermark', // Section           
            $field_arr
        );    

        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_watermark_offset_y';   //set in plugin      
        $field_arr['field_type'] = 'number';      
        add_settings_field(
             $field_arr['field_id'], // ID
            __('The vertical watermark offset in units. The default value is 0.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_watermark', // Section           
            $field_arr
        );        
        
        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_watermark_rotation';    //set in plugin
        $field_arr['field_type'] = 'number';      
        add_settings_field(
             $field_arr['field_id'], // ID
            __('The watermark rotation in degrees.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_watermark', // Section           
            $field_arr
        );        

        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_watermark_in_background';   //set in plugin
        $field_arr['field_type'] = 'boolean';      
        add_settings_field(
            'pdfcrowd_pdf_watermark_in_background', // ID
            __('When set to true, the watermark is be placed in the background. If false it would be placed in the foreground.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_watermark', // Section           
            $field_arr
        );       
        
        //Misc
        add_settings_section(
            'pdfcrowd_section_misc', // ID
            __('Miscellaneous', 'pdfcrowd-wp'), // Title
            array( $this, 'misc_section_info' ), // Callback
            'pdfcrowd_options' // Page
        ); 
        
        /*
        $field_arr = array();         
        $field_arr['field_id'] = 'pdfcrowd_pdf_fail_on_non200';      //set in plugin
        $field_arr['field_type'] = 'boolean';              
        add_settings_field(
            'pdfcrowd_pdf_fail_on_non200', // ID
            'If set to true, the conversion request will fail if the converted URL returns 4xx or 5xx HTTP status code.', // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_misc', // Section           
            $field_arr
        );      
        */
           
        
        $field_arr = array();   
        $field_arr['field_id'] = 'pdfcrowd_pdf_pdfcrowd_logo';     //set in plugin
        $field_arr['field_type'] = 'boolean';              
        add_settings_field(
             $field_arr['field_id'], // ID
            __('Insert the Pdfcrowd logo to the footer.', 'pdfcrowd-wp'), // Title 
            array( $this, 'sanitizefield' ), // Callback
            'pdfcrowd_options', // Page
            'pdfcrowd_section_misc', // Section           
            $field_arr
        );              
         
    }

    /**
     * Sanitize all fields used in option page and some specific field, do some other sanitiziting
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        foreach ($input as $key=>$v) {
            $new_input[$key] = sanitize_text_field( $v );
        }
        
        //Numeric only
        $numeric_keys = array();
        $numeric_keys[] = 'pdfcrowd_pdf_page_numbering_offset';
        $numeric_keys[] = 'pdfcrowd_pdf_no_images';
        $numeric_keys[] = 'pdfcrowd_pdf_no_backgrounds';
        $numeric_keys[] = 'pdfcrowd_pdf_html_zoom';
        $numeric_keys[] = 'pdfcrowd_pdf_use_print_media';
        $numeric_keys[] = 'pdfcrowd_pdf_no_print';
        $numeric_keys[] = 'pdfcrowd_pdf_no_modify';
        $numeric_keys[] = 'pdfcrowd_pdf_no_copy';
        $numeric_keys[] = 'pdfcrowd_pdf_page_layout';
        $numeric_keys[] = 'pdfcrowd_pdf_initial_pdf_zoom_type';        
        $numeric_keys[] = 'pdfcrowd_pdf_initial_pdf_zoom';
        $numeric_keys[] = 'pdfcrowd_pdf_page_mode';
        $numeric_keys[] = 'pdfcrowd_pdf_max_pages';
        $numeric_keys[] = 'pdfcrowd_pdf_watermark_offset_x';
        $numeric_keys[] = 'pdfcrowd_pdf_watermark_offset_y';
        $numeric_keys[] = 'pdfcrowd_pdf_watermark_rotation';
        $numeric_keys[] = 'pdfcrowd_pdf_watermark_in_background';
        $numeric_keys[] = 'pdfcrowd_pdf_fail_on_non200';      
        $numeric_keys[] = 'pdfcrowd_pdf_margin_top';
        $numeric_keys[] = 'pdfcrowd_pdf_margin_right';
        $numeric_keys[] = 'pdfcrowd_pdf_margin_bottom';
        $numeric_keys[] = 'pdfcrowd_pdf_margin_left';
        $numeric_keys[] = 'pdfcrowd_pdf_width';
        $numeric_keys[] = 'pdfcrowd_pdf_height';

        //Make input values with above numeric keys an int
        foreach ( $numeric_keys as $pdfcrowd_key) {
            $new_input[$pdfcrowd_key] = absint( $new_input[$pdfcrowd_key] );
        }
        
        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print __('Settings below will be used globally for all shortcodes for the PDF Crowd WP plugin. You get <strong>username</strong> and the <strong>key</strong> from your <strong>PDF Crowd account</strong> at <a target="_blank" href="http://pdfcrowd.com">PDF Crowd</a>', 'pdfcrowd-wp');
    }

    public function dimension_section_info() 
    {
        print __('Some parameters can be specified in inches (in), millimeters (mm), centimeters (cm), or points (pt). If no units are specified, points are assumed.', 'pdfcrowd-wp');
    }
    
    public function header_footer_section_info() 
    {
        print __('Header and footer text', 'pdfcrowd-wp');
    }
    
    public function htmloptions_section_info() 
    {
        print __('Options regarding to the layout/html', 'pdfcrowd-wp');
    }
    
    public function pdfoptions_section_info() {
        print __('Options for the PDF', 'pdfcrowd-wp');
    }
    
    public function watermark_section_info() {
        print __('If you want a watermark in the PDF, here are some settings', 'pdfcrowd-wp');
    }
    
    public function misc_section_info() {
        print __('Miscellaneous', 'pdfcrowd-wp');
    }    
    
    /** 
     * Get the settings option array and print one of its values
     */
    public function sanitizefield( $field_arr )
    {
        $fid = $field_arr['field_id'];
        $field_type = $field_arr['field_type'];
        if ( isset ( $field_arr['class']) ) {
            $field_class = ' class="' . $field_arr['class'] . '"';
        }
        else {
            $field_class = '';
        }
        
        if ( isset($this->options[$fid]) ) 
        {
            $current_value = $this->options[$fid];
        }
        else 
        {
            //Set default values when not saved (based on field type)
            if ($field_type === 'text') {
                $current_value = '';
            }
            else {
                $current_value = 0;
            }
        }
        
        switch ( $field_type ) {            
            case 'boolean':
                if ( (int)$this->options[$fid] === 1) {
                    $true_checked = ' checked="checked" ';
                    $false_checked = ' ';
                }
                else {
                    $true_checked = ' ';
                    $false_checked = ' checked="checked" ';
                }
                echo __('True','pdfcrowd-wp') .  ': <input' . $field_class . ' ' . $true_checked . ' type="radio" id="' .  $fid  . '" name="pdfcrowd_option[' . $fid . ']" value="1" />';
                echo __('False', 'pdfcrowd-wp') . ': <input' .$field_class . ' ' . $false_checked  .  ' type="radio" id="' .  $fid  . '" name="pdfcrowd_option[' . $fid . ']" value="0" />';
                break;

                
            case 'select':
                if ( isset($field_arr['field_values']) ) {
                    $options_html = '';
                    $field_values = $field_arr['field_values'];
                    foreach ( $field_values as $key_fv => $fv ) {
                        if ( $key_fv == $current_value ) {
                           $options_selected = ' selected ';
                        }
                        else {
                            $options_selected = ' ';
                        }
                        $options_html .= '<option' . $options_selected . 'value="' . $key_fv . '">' . $fv . '</option>';
                    };
                    echo  '<select' . $field_class . ' id="' .  $fid  . '" name="pdfcrowd_option[' . $fid . ']" />' . $options_html . '</select>';
                };
                break;
                
            case 'textarea':
                echo '<textarea' . $field_class . ' id="' .  $fid  . '" name="pdfcrowd_option[' . $fid . ']">'.  $current_value . '</textarea>';
                break;
            case 'password':
                if ( strlen ( $field_class) === 0 ) 
                {
                    $field_class = ' class="minor-text"';
                }
                echo '<input' . $field_class . ' type="password" id="' .  $fid  . '" name="pdfcrowd_option[' . $fid . ']" value="' . $current_value . '" />';
                break;               
                
            default:
                if ( strlen ( $field_class) === 0 ) 
                {
                    $field_class = ' class="minor-text"';
                }
                echo '<input' . $field_class . ' type="text" id="' .  $fid  . '" name="pdfcrowd_option[' . $fid . ']" value="' . $current_value . '" />';
                break;
        }
        
    }
    

}
}