<?php
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.global.entity.php');

global $wp_version;
global $wpdb;

/* @var $global DUP_PRO_Global_Entity */
$global = DUP_PRO_Global_Entity::get_instance();

$nonce_action = 'duppro-default-storage-edit';
$was_updated = false;

if (isset($_REQUEST['action']))
{
    check_admin_referer($nonce_action);
    if ($_REQUEST['action'] == 'save')
    {        
        $gdrive_error_message = NULL;
        
		$global->max_default_store_files = (int)$_REQUEST['max_default_store_files'];
        
        $global->save();
        
        $local_folder_created = false;
        $local_folder_creation_error = false;
        
        $was_updated = true;
        $edit_create_text = DUP_PRO_U::__('Edit Default');
    }
}

?>

<style>
    table.dpro-edit-toolbar select {float:left}
    table.dpro-edit-toolbar input[type=button] {margin-top:-2px}
    #dup-storage-form input[type="text"], input[type="password"] { width: 250px;}
	#dup-storage-form input#name {width:100%; max-width: 500px}
    #dup-storage-form #ftp_timeout, #ftp_max_files {width:100px !important} 
	#dup-storage-form input#_local_storage_folder, input#_ftp_storage_folder {width:100% !important; max-width: 500px}
	td.dpro-sub-title {padding:0; margin: 0}
	td.dpro-sub-title b{padding:20px 0; margin: 0; display:block; font-size:1.25em;}
	
	input#max_default_store_files {width:50px !important}
</style>
<?php 
		if ($was_updated) 
        {                  
			$update_message = 'Default Storage Provider Updated';
			echo "<div id='message' class='updated below-h2'><p>$update_message</p></div>";          
        }
	?>
 <!-- ====================
	TOOL-BAR -->
    <table class="dpro-edit-toolbar">
        <tr>
            <td>
		    </td>
            <td>
                <a href="<?php echo $storage_tab_url; ?>" class="add-new-h2"> <i class="fa fa-database"></i> <?php DUP_PRO_U::_e('All Storage Providers'); ?></a>
                <span><?php DUP_PRO_U::_e('Edit Default Storage'); ?></span>
            </td>
        </tr>
    </table>
    <hr class="dpro-edit-toolbar-divider"/>
	 
<form id="dpro-default-storage-form" action="<?php echo $edit_default_storage_url; ?>" method="post" data-parsley-ui-enabled="true">
    <?php wp_nonce_field($nonce_action); ?>
    <input type="hidden" id="dup-storage-form-action" name="action" value="save">
 
    <table class="provider form-table">	
		<tr valign="top">
            <th scope="row"><label><?php DUP_PRO_U::_e("Name"); ?></label></th>
            <td>
               <?php DUP_PRO_U::_e('Default'); ?>
			</td>
        </tr>	
        <tr valign="top">
            <th scope="row"><label><?php DUP_PRO_U::_e("Type"); ?></label></th>
            <td>
				<?php DUP_PRO_U::_e('Local Server'); ?>
            </td>
        </tr>	
        <tr>
            <th scope="row"><label for=""><?php DUP_PRO_U::_e("File Deletion"); ?></label></th>
            <td>
                <label for="max_default_store_files">
					<input data-parsley-errors-container="#max_default_store_files_error_container" id="max_default_store_files" name="max_default_store_files" type="text" data-parsley-type="number" data-parsley-min="0" data-parsley-required="true" value="<?php echo $global->max_default_store_files; ?>" maxlength="4">&nbsp;
				</label>
				<p>    <i><?php DUP_PRO_U::_e("Number of packages to keep in default storage. Set to 0 for no limit."); ?></i></p>
                <div id="max_default_store_files_error_container" class="duplicator-error-container"></div>
            </td>
        </tr>
    </table>

    <br style="clear:both" />
    <button class="button button-primary" type="submit"><?php DUP_PRO_U::_e('Save Provider'); ?></button>
</form>

<script>
    jQuery(document).ready(function ($) {

	$('#dpro-default-storage-form').parsley();  

//		DupPro.Storage.BindParsley = function (mode) {
//
//            if(counter++ > 0)
//            {
//                $('#dup-storage-form').parsley().destroy();
//            }
//
//            $('#dup-storage-form input').removeAttr('data-parsley-required');
//            $('#dup-storage-form input').removeAttr('data-parsley-type');
//            $('#dup-storage-form input').removeAttr('data-parsley-range');
//            $('#dup-storage-form input').removeAttr('data-parsley-min');
//
//
//            // Now add the appropriate attributes
//            $('#name').attr('data-parsley-required', 'true');
//            
//            switch (parseInt(mode)) {
//
//                case DupPro.Storage.Modes.LOCAL:
//					$('#_local_storage_folder').attr('data-parsley-required', 'true');
//                    break;
//
//                case DupPro.Storage.Modes.DROPBOX:
//                    $('#dropbox_max_files').attr('data-parsley-required', 'true');
//                    $('#dropbox_max_files').attr('data-parsley-type', 'number');
//                    $('#dropbox_max_files').attr('data-parsley-min', '0');                    
//                    break;
//                    
//                case DupPro.Storage.Modes.FTP:
//                    $('#ftp_server').attr('data-parsley-required', 'true');
//                    $('#ftp_port').attr('data-parsley-required', 'true');
//					
//					$('#ftp_password, #ftp_password2').attr('data-parsley-required', 'true');
//                    $('#ftp_max_files').attr('data-parsley-required', 'true');
//                    $('#ftp_timeout').attr('data-parsley-required', 'true');
//
//                    $('#ftp_port').attr('data-parsley-type', 'number');
//                    $('#ftp_max_files').attr('data-parsley-type', 'number');
//                    $('#ftp_timeout').attr('data-parsley-type', 'number');
//
//                    $('#ftp_port').attr('data-parsley-range', '[1,65535]');
//
//                    $('#ftp_max_files').attr('data-parsley-min', '0');
//                    $('#ftp_timeout').attr('data-parsley-min', '10');
//                    break;
//					
//				case DupPro.Storage.Modes.GDRIVE:
//                    $('#gdrive_max_files').attr('data-parsley-required', 'true');
//                    $('#gdrive_max_files').attr('data-parsley-type', 'number');
//                    $('#gdrive_max_files').attr('data-parsley-min', '0');                    
//                    break;
//            };
//            $('#dup-storage-form').parsley();      
        
       // };

    });
</script>
