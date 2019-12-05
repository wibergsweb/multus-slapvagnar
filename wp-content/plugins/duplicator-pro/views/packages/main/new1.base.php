<?php
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/class.package.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.storage.entity.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.package.template.entity.php');

global $wpdb;

//POST BACK
$action_updated = null;
if (isset($_POST['action']))
{
    switch ($_POST['action'])
    {
        case 'duplicator_pro_package_active' : $action_response = DUP_PRO_U::__('Package settings have been reset.');
            break;
    }
}

DUP_PRO_Util::InitSnapshotDirectory();

$Package = DUP_PRO_Package::get_temporary_package();
$package_hash = $Package->make_hash();

$dup_tests = array();
$dup_tests = DUP_PRO_Server::get_requirments();
$default_name = DUP_PRO_Package::get_default_name();

$view_state = DUP_PRO_UI::GetViewStateArray();
$ui_css_storage = (isset($view_state['dup-pack-storage-panel']) && $view_state['dup-pack-storage-panel']) ? 'display:block' : 'display:none';
$ui_css_archive = (isset($view_state['dup-pack-archive-panel']) && $view_state['dup-pack-archive-panel']) ? 'display:block' : 'display:none';
$ui_css_installer = (isset($view_state['dup-pack-installer-panel']) && $view_state['dup-pack-installer-panel']) ? 'display:block' : 'display:none';

$storage_list = DUP_PRO_Storage_Entity::get_all();
$storage_list_count = count($storage_list);
$dup_intaller_files = implode(", ", array_keys(DUP_PRO_Server::get_installer_files()));

?>

<style>
    /* -----------------------------
    REQUIRMENTS*/
    div.dup-sys-section {margin:1px 0px 5px 0px}
    div.dup-sys-title {display:inline-block; width:250px; padding:1px; }
    div.dup-sys-title div {display:inline-block;float:right; }
    div.dup-sys-info {display:none; max-width: 98%; margin:4px 4px 12px 4px}	
    div.dup-sys-pass {display:inline-block; color:green;}
    div.dup-sys-fail {display:inline-block; color:#AF0000;}
    div.dup-sys-contact {padding:5px 0px 0px 10px; font-size:11px; font-style:italic}
    span.dup-toggle {float:left; margin:0 2px 2px 0; }
    table.dup-sys-info-results td:first-child {width:200px}

    div#mode-area {margin:1px 0px 15px 0px; padding:7px}
	div#mode-area input[type=radio] {margin-top:3px}
    div#mode-area label{font-size: 15px !important; padding: 1px;}
    div#mode-section-1, div#mode-section-2 {padding:5px 5px 2px 5px}
</style>

<!-- ====================
TOOL-BAR -->
<table class="dpro-edit-toolbar">
	<tr>
		<td>
			<div id="dup-wiz">
				<div id="dup-wiz-steps">
					<div class="active-step"><a><span>1</span> <?php DUP_PRO_U::_e('Setup'); ?></a></div>
					<div><a><span>2</span> <?php DUP_PRO_U::_e('Scan'); ?> </a></div>
					<div><a><span>3</span> <?php DUP_PRO_U::_e('Build'); ?> </a></div>
				</div>
				<div id="dup-wiz-title" style="white-space: nowrap">
					<?php DUP_PRO_U::_e('Step 1: Package Setup'); ?>
				</div> 
			</div>	
		</td>
		<td>
			<a href="<?php echo $packages_tab_url; ?>" class="add-new-h2"><i class="fa fa-archive"></i> <?php DUP_PRO_U::_e('All Packages'); ?></a>
			<span> <?php _e("Create New"); ?></span>
		</td>
	</tr>
</table>
<hr style="margin:2px 0 8px 0">

<?php if (!empty($action_response)) : ?>
    <div id="message" class="updated below-h2"><p><?php echo $action_response; ?></p></div>
<?php endif; ?>	

<!-- =========================================
SYSTEM REQUIREMENTS -->
<div class="dup-box">
    <div class="dup-box-title dup-box-title-fancy">
        <i class="fa fa-check-square-o"></i>
        <?php
        DUP_PRO_U::_e("Requirements:");
        echo ($dup_tests['Success']) ? ' <div class="dup-sys-pass">Pass</div>' : ' <div class="dup-sys-fail">Fail</div>';
        ?>
        <div class="dup-box-arrow"></div>
    </div>

    <div class="dup-box-panel" style="<?php echo ($dup_tests['Success']) ? 'display:none' : ''; ?>">

        <div class="dup-sys-section">
            <i><?php DUP_PRO_U::_e("System requirements must pass for the Duplicator to work properly.  Click each link for details."); ?></i>
        </div>

        <!-- PHP SUPPORT -->
        <div class='dup-sys-req'>
            <div class='dup-sys-title'>
                <a><?php DUP_PRO_U::_e('PHP Support'); ?></a>
                <div><?php echo $dup_tests['PHP']['ALL']; ?></div>
            </div>
            <div class="dup-sys-info dup-info-box">
                <table class="dup-sys-info-results">
                    <tr>
                        <td><?php printf("%s [%s]", DUP_PRO_U::__("PHP Version"), phpversion()); ?></td>
                        <td><?php echo $dup_tests['PHP']['VERSION'] ?></td>
                    </tr>
                    <?php 
                        $global = DUP_PRO_Global_Entity::get_instance();
                        
                        /* @var $global DUP_PRO_Global_Entity */
                        if($global->archive_build_mode == DUP_PRO_Archive_Build_Mode::ZipArchive)
                        {
                    ?>
                    <tr>
                        <td><?php DUP_PRO_U::_e('Zip Archive Enabled'); ?></td>
                        <td><?php echo $dup_tests['PHP']['ZIP'] ?></td>
                    </tr>	
                    <?php
                        }
                    ?>
					<tr>
						<td><?php DUP_PRO_U::_e('Function');?> <a href="http://php.net/manual/en/function.file-get-contents.php" target="_blank">file_get_contents</a></td>
						<td><?php echo $dup_tests['PHP']['FUNC_1'] ?></td>
					</tr>					
					<tr>
						<td><?php DUP_PRO_U::_e('Function');?> <a href="http://php.net/manual/en/function.file-put-contents.php" target="_blank">file_put_contents</a></td>
						<td><?php echo $dup_tests['PHP']['FUNC_2'] ?></td>
					</tr>
					<tr>
						<td><?php DUP_PRO_U::_e('Function');?> <a href="http://php.net/manual/en/mbstring.installation.php" target="_blank">mb_strlen</a></td>
						<td><?php echo $dup_tests['PHP']['FUNC_3'] ?></td>
					</tr>	
                </table>
                <small>
                    <?php DUP_PRO_U::_e("PHP versions 5.2.17+ or higher is required. Please note that in versioning logic a value such as 5.2.9 is less than 5.2.17. For compression to work the ZipArchive extension for PHP is required. Safe Mode should be set to 'Off' in you php.ini file and is deprecated as of PHP 5.3.0.  For any issues in this section please contact your hosting provider or server administrator.  For additional information see our online documentation."); ?>
                </small>
            </div>
        </div>		

        <!-- PERMISSIONS -->
        <div class='dup-sys-req'>
            <div class='dup-sys-title'>
                <a><?php DUP_PRO_U::_e('Permissions'); ?></a> <div><?php echo $dup_tests['IO']['ALL']; ?></div>
            </div>
            <div class="dup-sys-info dup-info-box">
                <b><?php DUP_PRO_U::_e("Required Paths"); ?></b>
                <div style="padding:3px 0px 0px 15px">
                    <?php
                    printf("<b>%s</b> &nbsp; [%s] <br/>", $dup_tests['IO']['WPROOT'], DUPLICATOR_PRO_WPROOTPATH);
                    printf("<b>%s</b> &nbsp; [%s] <br/>", $dup_tests['IO']['SSDIR'], DUPLICATOR_PRO_SSDIR_PATH);
                    printf("<b>%s</b> &nbsp; [%s] <br/>", $dup_tests['IO']['SSTMP'], DUPLICATOR_PRO_SSDIR_PATH_TMP);
                    ?>
                </div>

                <small>
                    <?php DUP_PRO_U::_e("Permissions can be difficult to resolve on some systems. If the plugin can not read the above paths here are a few things to try. 1) Set the above paths to have permissions of 755 for directories and 644 for files. You can temporarily try 777 however, be sure you don’t leave them this way. 2) Check the owner/group settings for both files and directories. The PHP script owner and the process owner are different. The script owner owns the PHP script but the process owner is the user the script is running as, thus determining its capabilities/privileges in the file system. For more details contact your host or server administrator or visit the 'Help' menu under Duplicator for additional online resources."); ?>
                </small>					
            </div>
        </div>

        <!-- SERVER SUPPORT -->
        <div class='dup-sys-req'>
            <div class='dup-sys-title'>
                <a><?php DUP_PRO_U::_e('Server Support'); ?></a>
                <div><?php echo $dup_tests['SRV']['ALL']; ?></div>
            </div>
            <div class="dup-sys-info dup-info-box">
                <table class="dup-sys-info-results">
                    <tr>
                        <td><?php printf("%s [%s]", DUP_PRO_U::__("MySQL Version"), $wpdb->db_version()); ?></td>
                        <td><?php echo $dup_tests['SRV']['MYSQL_VER'] ?></td>
                    </tr>
                </table>
                <small>
                    <?php
                    DUP_PRO_U::_e("MySQL version 5.0+ or better is required.  Contact your server administrator and request MySQL Server 5.0+ be installed.");
                    ?>										
                </small>
            </div>
        </div>

        <!-- RESERVED FILES -->
        <div class='dup-sys-req'>
            <div class='dup-sys-title'>
                <a><?php DUP_PRO_U::_e('Reserved Files'); ?></a> <div><?php echo $dup_tests['RES']['INSTALL']; ?></div>
            </div>
            <div class="dup-sys-info dup-info-box">
                <?php if ($dup_tests['RES']['INSTALL'] == 'Pass') : ?>
                    <?php 
						DUP_PRO_U::_e("No reserved installer files [{$dup_intaller_files}] where found from a previous install.  You are clear to create a new package."); 
					?>
                <?php else: ?>                     
                    <form method="post" action="admin.php?page=duplicator-pro-tools&tab=diagnostics&action=installer">
                        <?php DUP_PRO_U::_e("A reserved file(s) was found in the WordPress root directory. Reserved file names include [{$dup_intaller_files}].  To archive your data correctly please remove any of these files and try creating your package again."); ?>
                        <br/><input type='submit' class='button action' value='<?php DUP_PRO_U::_e('Remove Files Now') ?>' style='font-size:10px; margin-top:5px;' />
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- ONLINE SUPPORT -->
        <div class="dup-sys-contact">
            <?php
            printf("<i class='fa fa-question-circle'></i> %s <a href='admin.php?page=duplicator-pro-tools&tab=support'>[%s]</a>", DUP_PRO_U::__("For additional help please see the "), DUP_PRO_U::__("help page"));
            ?>
        </div>

    </div>
</div><br/>

<!-- =========================================
MODE: Quick OR Template -->
<div id="mode-area" class="wp-filter">
    <label><b><?php DUP_PRO_U::_e('Mode'); ?>:</b></label> 
    &nbsp;&nbsp;&nbsp;
    <input type="radio" name="mode" id="mode-1" value="1" onclick="DupPro.Pack.ChangeMode('manual');" checked="checked"  />
    <label for="mode-1"><?php DUP_PRO_U::_e('Manual'); ?></label>
    &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;
    <input type="radio" name="mode" id="mode-2" value="2"  onclick="DupPro.Pack.ChangeMode('template');" />
    <label for="mode-2"><?php DUP_PRO_U::_e('Template'); ?></label>
</div>


<?php require_once('new1.internals.php'); ?>

<script type="text/javascript">
    jQuery(document).ready(function ($) {
        
        DupPro.Pack.mode = "manual";
        
        DupPro.Pack.ChangeMode = function (mode) {
            
            DupPro.Pack.mode = mode;
            
            $('#dpro-package-specific-area, #dpro-template-specific-area').hide();
            
            if(mode == 'manual') {
                $("#dup-form-opts input").prop('disabled', false);
                $("#filter-dirs").prop('disabled', false).css('color', '#000');
                $("#filter-exts").prop('disabled', false);
                $("#filter-files").prop('disabled', false);
                                 
                $("#dup-form-opts-action").val('manual-create');
                $('#dpro-package-specific-area').show(0);
				$('#dpro-notes-area, .dpro-notes-add').show();
            } else {
                $("#dup-form-opts input").prop('disabled', true);
                $("#button-next").prop('disabled', false);
                $("#dup-form-opts-action").prop('disabled', false);
                $(".duppro-storage-input").prop('disabled', false);
                
                $("#filter-dirs").prop('disabled', true).css('color', '#A9A9AB');
                $("#filter-exts").prop('disabled', true).css('color', '#A9A9AB');
                $("#filter-files").prop('disabled', true).css('color', '#A9A9AB');
				$('#dpro-notes-area, .dpro-notes-add').hide();
                
                 
                $("#dup-form-opts-action").val('template-create');
                $('#dpro-template-specific-area').show(0);    
            }         
            
            DupPro.Pack.PopulateCurrentTemplate();
        }
        
        DupPro.Pack.ToggleSystemDetails = function(anchor) {
            
            $(anchor).parent().siblings('.dup-sys-info').toggle();
        }
        
        //Init: Toogle for system requirment detial links
        $('.dup-sys-title a').each(function () {
            $(this).attr('href', 'javascript:void(0)');
            $(this).click(function() { DupPro.Pack.ToggleSystemDetails(this); });
            $(this).prepend("<span class='ui-icon ui-icon-triangle-1-e dup-toggle' />");
        });

        //Init: Color code Pass/Fail/Warn items
        $('.dup-sys-title div').each(function () {
            $(this).addClass(($(this).text() == 'Pass') ? 'dup-sys-pass' : 'dup-sys-fail');
        });       
        
    });
</script>

