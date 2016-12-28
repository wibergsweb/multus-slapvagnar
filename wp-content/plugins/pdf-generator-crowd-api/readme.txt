=== PDF Generator Crowd API ===
Contributors: wibergsweb
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=93LWBQF7LY6SA
Tags: pdf, generator, crowd, realtime, on the fly
Requires at least: 3.5.1
Tested up to: 4.6
Stable tag: 1.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A PDF generator that really works. Creates PDF files on the fly with a simple shortcode. 

== Description ==

This plugin makes it very easy to create PDF's on the fly. With a single shortcode it could create X amount of PDF's "on the fly" / in realtime when
a user visits a specific page or post. t's possible to create PDF's from current page/post or from a specific given url. The plugin is available in swedish and english but could easily be translated into whatever language. 

You cannot have several shortcodes from this plugin on the same page/post (if both shortcodes are used for generating pdfs)

The generation itself is managed by connecting to PDF Crowd API so you will have to have an account
there. Each time a PDF is generated with this plugin tokens are drawn from the account. Number of tokens drawn depends of things as size of generated PDF.

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
* [pdfcrowd_generate] - Generates one or more pdf's from current page/post or from a specific url.

= [pdfcrowd_generate] attributes =
* debug_mode - sets the shortcode in debug mode which prints everything relevant for troubleshooting purposes.
* show_clientinfo - when having debug_mode set to yes , show clients info as well (including username and password. Use with caution!)
* use_ssl - set yes if you want to make a connection over ssl and no if you don't want to make a connection over ssl. If nothing set set, then the plugin identifies if your site is on ssl or not.
* convert_urls - convert urls (set to current if you want to create pdf of current page/post. If having several urls separate them by using semicolon)
* out_files - what filename(s) to save as. If having having several urls defined, add equal many files (defined in convert_urls) separated by semicolon
* overwrite_pdf - dont connect to PDF Crowd (or overwrite the pdf) if the pdf already exists (saves tokens in your PDF Crowd account and resources on your server)
* path' - this set the base path after the upload path of Wordpress site (eg. pdfcrowd = /wp-content/uploads/pdfcrowd)            
* create_downloadlink - create a downloadable link to the created pdf
* html_class - If you want to style the downloadable link use this class
* link_titles - what to show in link(s) creating a downloadlink. Several link should be separated by semicolon. Make sure number of titles equals number of files

= Default values =
* [pdfcrowd_generate debug_mode="no" use_ssl="{none}" show_client="no" convert_urls="{none}" out_files="current" overwrite_pdf="no" path="pdfcrowd" create_downloadlink="no" html_class="{none}" link_titles="{none}"]


== Frequently Asked Questions ==

= Why don't you include any css for the plugin? =

The goal is to make the plugin work as fast as possible as expected even with the design. By not supplying any css the developer has full control over
what's happening all the way.

= Do you have any pro version? =

No.


== Screenshots ==

1. Screenshot with the usage example.

== Changelog ==

= 1.0 =
* Plugin released


== Upgrade notice ==
Please tell me if you're missing something (in the support form) ! I will do my best to add the feature.

== Example of usage ==

= shortcodes in post(s)/page(s) =
* [pdfcrowd_generate debug_mode="no" create_downloadlink="yes" out_files="pdfcrowd;wibergsweb" overwrite_pdf="no" convert_urls="http://pdfcrowd.com/;http://wibergsweb.se/" link_titles="PDF Crowd PDF Site;Wibergs Web site" html_class="pdfdownloadlink"]
* [pdfcrowd_generate debug_mode="no" out_files="pdfcrowd;wibergsweb" overwrite_pdf="yes" convert_urls="http://pdfcrowd.com/;http://wibergsweb.se/" link_titles="PDF Crowd PDF Site;Wibergs Web site" html_class="pdfdownloadlink"]
* [pdfcrowd_generate debug_mode="yes" use_ssl="yes" out_files="pdfcrowd;wibergsweb" convert_urls="http://pdfcrowd.com/;http://wibergsweb.se/" link_titles="PDF Crowd PDF Site;Wibergs Web site" html_class="pdfdownloadlink"]


== Example css ==
* .pdfdownloadlink {display:block;background:#0000FF;}