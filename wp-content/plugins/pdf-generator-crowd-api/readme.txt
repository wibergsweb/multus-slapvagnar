=== PDF Generator Crowd API ===
Contributors: wibergsweb
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=93LWBQF7LY6SA
Tags: create, generate, pdf, generator, acf, post, page, template, database, crowd, realtime, on the fly
Requires at least: 3.5.1
Tested up to: 4.7
Stable tag: 1.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A PDF generator that really works. Creates PDF files on the fly with a simple shortcode from post(s), custom post type(s) or page(s). Supports ACF.

== Description ==

This plugin makes it very easy to create PDF's on the fly. With a single shortcode it could create X amount of PDF's "on the fly" / in realtime when
a user visits a specific page or post. It's possible to create PDF's from current page/post/acf-field(s) or from a specific given url. The plugin is available in swedish and english but could easily be translated into whatever language. 

The generation itself is managed by connecting to PDF Crowd API so you will need to have an account
there to use this plugin. Every time the plugin is making a connection to the API a token is drawn. There is a free version of the API that has limited number
of tokens. From version 1.18 of this plugin it's possible to connect to the API only when data changes in any page or post which makes a huge difference in number of tokens that are used. 
Number of tokens drawn for each connection depends of the size of generated PDF(s) amongst some other parameters. It's possible to use a page/post-template and 
fetch data from ACF (Advanced Custom Fields) with this plugin.

Pricing for the PDF Crowd API service (and option to evalue the service) is shown here: http://pdfcrowd.com/pricing/ (Select option "HTML to PDF API" and make your choice). The PDF Crowd API service
is not created by the author so please have that in mind if you ask questions regarding the actual generation(s) of the PDF(s).  The service is used by the 
plugin because it was simply the best PDF generation software that was found (by the plugin author).

PDF Crowd states:
"Each API call makes a POST request to our servers. We do not disclose or keep copies of submitted data and generated files. They are kept only for the time necessary to efficiently process conversion requests and then permanently deleted."


If you like the plugin, please consider donating. 

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin folder pdfgeneratorcrowd to the `/wp-content/plugins/' directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Put shortcode(s) on the Wordpress post or page you want to display it on and add css to change layout for those.

= Shortcodes =
* [pdfcrowd_generate] - Generates one or more pdf's from current page/post or from a specific url or by fetching data from a post(s)/page(s)

= [pdfcrowd_generate] attributes =
* debug_mode - sets the shortcode in debug mode which prints everything relevant for troubleshooting purposes.
* skip_shortcode - if having several shortcodes and you want to test/debug one of them you could set this to yes for every shortcode you don't want to debug
* show_clientinfo - when having debug_mode set to yes , show clients info as well (including username and password. Use with caution!)
* use_ssl - set yes if you want to make a connection over ssl and no if you don't want to make a connection over ssl. If nothing set set, then the plugin identifies if your site is on ssl or not.
* convert_urls - convert urls (set to current if you want to create pdf of current page/post. If having several urls separate them by using semicolon)
* out_files - what filename(s) to save as. If having having several urls defined, add equal many files (defined in convert_urls) separated by semicolon
* overwrite_pdf - yes/no/datachange - yes = always overwrite existing PDF, no = never overwrite existing PDF, datachange = only create/overwrite PDF when data changes (for usage with ACF)
* path - this set the base path after the upload path of Wordpress site (eg. pdfcrowd = /wp-content/uploads/pdfcrowd)            
* create_downloadlink - create a downloadable link to the created pdf
* targetblank - set yes to open download link (pdf) in new windows, else no
* html_class - if you want to style the downloadable link use this class
* link_titles - what to show in link(s) creating a downloadlink. Several link should be separated by semicolon. Make sure number of titles equals number of files
* data_includeonlyfieldtrue - if you have a select field (true/false) then include post only when this field is true 
* data_cpt - fetch data from specific custom post type (default to normal POST)
* data_postid - data from specific post/page - id. If setting this to all, then use all posts defined by data_cpt
* data_fields - tell name of fields that should be used when fetching data from a specific post/page
* data_acfkeys - if using ACF, then tell key (this is important) of each value
* add_related_fields - if you want to include a field in array (probably acf repeater field) that is related to another field
* exclude_subfields - Tell what subfield(names) to exclude (probably from acf repeater) in the PDF
* autosum_fields - auto summarize every column in subarrays (probably repeater-field)
* css_file - if specified, it must be a link with full path to a css file that is publicy available. If set to theme the plugin uses style.css in the (child)themes directory. 
* last_shortcode - If using data_fields then value of this must be set to yes or no that tells if this is the last shortcode on page or not
* use_posttitle - use post-title as name (Set [title] in template)
* vat - used if calculation used for vat / after vat
* pagebreak_afterpost - set yes/no for pagebreaking after each post
* max_pages - sets max number of pages in generated pdf document
* refresh_pdf - removed since 1.28
* nrformat_keyfields - specify numberformat of given field(s)
* nrformat_autosumfields - same as nrformat_keyfields but this only affects special tag fields that calculates sum (that starts with crowdpdf- below)
* roundup_totalaftervat - do rounding of total after vat been calculated
* generate_fromhtml - generate custom html. This html could be created anywhere. In shortcode(not so practical though). In code: Just create a new instance of this plugin and call function generatepdf_from_html( $attrs, $html_content = '') $attr is used for setting values in key/pair instead of shortcut and $html_content is the html you want to use for createion of PDF

= Special template tags =
* [title] - fetches post title (if use_posttitle set to yes)
* [crowdpdf-total] - calculates sum of a specific field (in subarray/repeater-field)  (if autosum_fields is set to yes)
* [crowdpdf-totalvat] - calculates vat of sum based on value given in attribute vat  (if autosum_fields is set to yes)
* [crowdpdf-totalaftervat] - calculates total sum (total net + vat) (if autosum_fields is set to yes)
* [crowdpdf-totalaftervat-roundupcompare] - if using roundup, then this could be used to show how much rounding of the price is affected ( crowdpdf-totalaftervat - (crowdpdf-total + crowdpdf-totalvat) )

= Default values =
* [pdfcrowd_generate debug_mode="no" skip_shortcode="no"  use_ssl="{none}" show_client="no" convert_urls="{none}" out_files="current" overwrite_pdf="no" path="pdfcrowd" create_downloadlink="no" targetblank="no" html_class="{none}" link_titles="{none}" data_includeonlyfieldtrue="{none}" data_cpt="post"  data_postid="{none}"  data_fields="" data_acfkeys="" add_related_fields="{none}" autosum_fields="no" last_shortcode="{none}" use_posttitle="yes" vat="25" pagebreak_afterpost="yes" max_pages="{none}" nrformat_keyfields="{none}" nrformat_autosumfields="{none}" roundup_totalaftervat="no"]


== Frequently Asked Questions ==

= Why don't you include any css for the plugin? =

The goal is to make the plugin work as fast as possible as expected even with the design. By not supplying any css the developer has full control over
what's happening all the way.

= Do you have any pro version? =

No.

= Why is the name of the field shown instead of the actual value? =

There can be different reasons.
1. Fieldname has an incorrect spelling.
2. If using ACF, then acf_keyfields must be specified in exactly the same order as the acf fieldnames 
3. (Less obvious reason) Repeater-field must be att the end of the acf-fields list (acf_contact;acf_phone;acf_repeater_field) and NOT (acf_repeater_field;acf_contact_acf;acf_phone)


== Screenshots ==

1. Screenshot with the usage example.

== Changelog ==

= 1.35 =
* It's now possible to create pdf's from custom html

= 1.34 =
* The plugin is now grabbing content directly from page/post instead of going through an url and also removes the actual shortcode from content
* Possible to skip shortcode directly for debugging purposes
* Format numbers for specifc fields
* Format numbers for autosum fields
* Roundup totalsum field after vat

= 1.29 =
* Bugfix: When fetching posts from custom post type - ignore limit set in Wordpress dashboard

= 1.28 =
* Bugfix: simple url creation failed to create pdf. Now it works.
* Using data_includeonlyfieldtrue-attribute failed when changing from true to false. Now it works.
* Faster creation of PDF from ACF-fields.
* pdf_refresh-attribute no longer used (refreshes all pdfs)

= 1.24 =
* Bugfix: Stopped rendering at a specific point, but now it works.

= 1.23 =
* Specify css to use with PDF generated from post(s) or page(s)
* Possible to exclude specific subfields (probably from ACF Repeater field) 
* Better handling of refreshing PDF which makes every change in PDF refreshed in browser independ of browser used
* Reconnects and recreates PDF if it does not exist as a file

= 1.19 =
* Autorefresh update - bugfix

= 1.18 =
* Extended debugging functionality
* Create pdfs only when a post/page has changed 
* Show newly created PDF without refreshing page in (most) browsers
* Open newly created PDF in new window/new tab(s)
* Fetch a specific post or page
* Interaction with plugin ACF (Advanced Custom Fields) for creating template/data - relationsships. 
* Handles ACF-repeater fields
* Include all data from a specific custom post type (or native posts)
* Include data from a specific page/post
* Include data filtered out by a specific ACF boolean checkfield value (except when including data from only one post)
* Use page/post as template for data
* Special template tags
* Possible to insert page breaks between each post in generated pdf
* Downloadable links that opens in a new window/tab
* Add data in an subarray (acf repeater field) that are related (e.g if custom post type car has a model-field -it would be possible to add model header and values related to the car within the shortcode)
* Autosummarize fields in subarrays
* Add totals with or without specific vat given
* Several shortcodes can be used on same page generating different PDF's from different sources


= 1.0 =
* Plugin released


== Upgrade notice ==
Please tell me if you're missing something (in the support form) ! I will do my best to add the feature.

== Example of usage ==

= shortcodes in post(s)/page(s) =
* [pdfcrowd_generate debug_mode="no" create_downloadlink="yes" out_files="pdfcrowd;wibergsweb" overwrite_pdf="no" convert_urls="http://pdfcrowd.com/;http://wibergsweb.se/" link_titles="PDF Crowd PDF Site;Wibergs Web site" html_class="pdfdownloadlink"]
* [pdfcrowd_generate debug_mode="no" out_files="pdfcrowd;wibergsweb" overwrite_pdf="yes" convert_urls="http://pdfcrowd.com/;http://wibergsweb.se/" link_titles="PDF Crowd PDF Site;Wibergs Web site" html_class="pdfdownloadlink"]
* [pdfcrowd_generate debug_mode="yes" use_ssl="yes" out_files="pdfcrowd;wibergsweb" convert_urls="http://pdfcrowd.com/;http://wibergsweb.se/" link_titles="PDF Crowd PDF Site;Wibergs Web site" html_class="pdfdownloadlink"]
* [pdfcrowd_generate debug_mode="yes" create_downloadlink="yes" out_files="invoice" overwrite_pdf="datachange" data_fields="acf_surname;acf_lastname"  data_acfkeys="field_5859b22623ca5;field_5859b260bb104" link_titles="PDFmaster" convert_urls="{681}" data_postid="all" pagebreak_afterpost="yes" last_shortcode="no"]

= If using ACF (Advanced Custom Fields) =
* convert_urls="{100}" means grab url from page/post with id 100. In cases below it could be a page template (with id 100):
* [pdfcrowd_generate last_shortcode="no" debug_mode="no" autosum_fields="yes" targetblank="yes" create_downloadlink="yes" out_files="invoice" overwrite_pdf="datachange" convert_urls="{100}" data_postid="all" data_cpt="customer" data_fields="acf_customer_phone;acf_customer_name;acf_customer_trailers" data_acfkeys="field_285b0870afcf6;field_285b064d43bec;field_585b0b3288d6e" link_titles="Invoices"]
* [pdfcrowd_generate last_shortcode="no" debug_mode="no" autosum_fields="yes" targetblank="yes" create_downloadlink="yes" out_files="invoice" overwrite_pdf="datachange" convert_urls="{100}" data_postid="all" data_cpt="customer" data_fields="acf_customer_phone;acf_customer_name;acf_customer_trailers" data_acfkeys="field_285b0870afcf6;field_285b064d43bec;field_585b0b3288d6e" link_titles="Invoices" add_related_fields="1;Model of car;acf_customer_cars_reg;acf_cars_model"]
* [pdfcrowd_generate last_shortcode="no" debug_mode="no" autosum_fields="yes" targetblank="yes" create_downloadlink="yes" out_files="invoice" overwrite_pdf="datachange" convert_urls="{100}" data_postid="55" data_cpt="customer" data_fields="acf_customer_phone;acf_customer_name;acf_customer_trailers" data_acfkeys="field_285b0870afcf6;field_285b064d43bec;field_585b0b3288d6e" link_titles="Invoices" add_related_fields="1;Model of car;acf_customer_cars_reg;acf_cars_model"]

= Page template could look something like this =
<h1>Invoice</h1>
<h2>[title]</h2>
<p><strong>Phonenumber:</strong>:&nbsp;[acf_customer_phone]</p>
<h2>Cars</h2>
[acf_customer_cars]

<strong>Net:</strong>
[crowdpdf-total=field_585b0b3288d2e]

<strong>Vat:</strong>
[crowdpdf-totalvat=field_585b0b3288d2e]

<strong>Gross:</strong>
[crowdpdf-totalaftervat=field_585b0b3288d2e]

= Explanation of the shortcode =
* [pdfcrowd_generate last_shortcode="yes" debug_mode="no" autosum_fields="yes" targetblank="yes" create_downloadlink="yes" out_files="invoice" overwrite_pdf="datachange" convert_urls="{100}" data_postid="55" data_cpt="customer" data_fields="acf_customer_phone;acf_customer_name;acf_customer_trailers" data_acfkeys="field_285b0870afcf6;field_285b064d43bec;field_585b0b3288d6e" link_titles="Invoice" add_related_fields="1;Model of car;acf_customer_cars_reg;acf_cars_model"]
* It's only one shortcode on the page, so it's the last
* No debugmode
* Tell plugin to autosum specific fields 
* Create downloadable link
* Open newly created download pdf in new tab/window
* Create a file called invoice
* Only generate new pdf/connect to PDF Crowd server when data has been changed
* Use page with id 100 as a template page
* Grab data from post type customer
* Grab data from customer with id 55
* Use datafields called acf_customer_phone (with key field_285b0870afcf6), acf_customer_name (with keyfield_285b064d43bec) and acf_customer_cars (acf_customer_cars is a repeater-field with keyfield field_585b0b3288d6e)
* Use Invoice as title on the download link
* acf_customer_cars is a repeater field with these subfields (Name, color)
* A new column is added in the repeater field in the PDF (but not in the admin) with the titlte "Model of car" at the second position in the index (1) and fetches the actual carmodel from related model of the car

[title] - shows the actual 
field_585b0b3288d2e is the keyfield of the ACF that is being autosummarized (in the repeater field)

Start with setting debug_mode to yes when creating your attributes. It might be more helpful than you think! Happy generating! :-)

== Example css ==
* .pdfdownloadlink {display:block;background:#0000FF;}