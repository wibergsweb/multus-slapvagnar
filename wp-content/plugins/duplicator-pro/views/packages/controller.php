<?php

DUP_PRO_Util::CheckPermissions('export');

global $wpdb;

//COMMON HEADER DISPLAY
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/assets/js/javascript.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/views/inc.header.php');

$current_view =  (isset($_REQUEST['action']) && $_REQUEST['action'] == 'detail') ? 'detail' : 'main';
?>

<script type="text/javascript">
    jQuery(document).ready(function($) {

        /*	----------------------------------------
         *	METHOD: Triggers the download of an installer/package file
         *	@param name		Window name to open
         *	@param button	Button to change color */
        DupPro.Pack.DownloadFile = function(event, button) {
            if (event.data != undefined) {
                window.open(event.data.name, '_self');
            } else {
                $(button).addClass('dpro-btn-selected');
                window.open(event, '_self');
            }
            return false;
        }

        // which: 0=installer, 1=archive, 2=sql file, 3=log
        DupPro.Pack.DownloadPackageFile = function (which, packageID) {
    
            var actionLocation = ajaxurl + '?action=duplicator_pro_get_package_file&which=' + which + '&package_id=' + packageID;
    
            if(which == 3)
            {
                var win=window.open(actionLocation, '_blank');
                win.focus();    
            }
            else
            {
                location.href = actionLocation;            
            }        
        }
    });
</script>

<div class="wrap">
    <?php 
		//duplicator_pro_header(DUP_PRO_U::__("Packages"));

		    switch ($current_view) {
				case 'main': include('main/controller.php'); break;
				case 'detail' : include('details/controller.php'); break;
            break;	
    }
    ?>
</div>