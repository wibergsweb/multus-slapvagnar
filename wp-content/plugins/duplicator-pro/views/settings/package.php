<?php
global $wp_version;
global $wpdb;

$nonce_action = 'duppro-settings-package-edit';
$action_updated = null;
$action_response = DUP_PRO_U::__("Package Settings Saved");

/* @var $global DUP_PRO_Global_Entity */
$global = DUP_PRO_Global_Entity::get_instance();

$is_zip_available = (DUP_PRO_Util::get_zip_filepath() != null);
$max_execution_time = ini_get("max_execution_time");
if (empty($max_execution_time))
{
	$max_execution_time = 30;
}

$max_worker_cap_in_sec = (int) (0.7 * (float) $max_execution_time);

//SAVE RESULTS
if (isset($_POST['action']) && $_POST['action'] == 'save')
{
    check_admin_referer($nonce_action);

	//---------------------------
    //BUILD MODES
    $enable_mysqldump = isset($_REQUEST['_package_dbmode']) && $_REQUEST['_package_dbmode'] == 'mysql' ? "1" : "0";
    $global->package_mysqldump = $enable_mysqldump ? 1 : 0;
    $global->package_phpdump_qrylimit = isset($_REQUEST['_package_phpdump_qrylimit']) ? (int) $_REQUEST['_package_phpdump_qrylimit'] : 100;
    $global->package_mysqldump_path = trim($_REQUEST['_package_mysqldump_path']);
	$global->archive_build_mode =  ($is_zip_available) 
			? (int)$_REQUEST['archive_build_mode']
			: DUP_PRO_Archive_Build_Mode::ZipArchive;

    $global->max_package_runtime_in_min = (int)$_REQUEST['max_package_runtime_in_min'];
	$global->server_load_reduction = (int)$_REQUEST['server_load_reduction'];

	//---------------------------
	//ADVANCED
    DUP_PRO_Util::InitSnapshotDirectory();
	$global->ziparchive_chunk_size_in_mb = (int)$_REQUEST['ziparchive_chunk_size_in_mb'];
	$global->ziparchive_mode = (int)$_REQUEST['ziparchive_mode'];
	$global->lock_mode = (int)$_REQUEST['lock_mode'];
	$global->json_mode = (int)$_REQUEST['json_mode'];
    $global->php_max_worker_time_in_sec = $_REQUEST['php_max_worker_time_in_sec'];  
	$global->ajax_protocol = $_REQUEST['ajax_protocol'];
    $global->custom_ajax_url = $_REQUEST['custom_ajax_url'];
	$global->clientside_kickoff = isset($_REQUEST['_clientside_kickoff']);
    $global->basic_auth_enabled = isset($_REQUEST['_basic_auth_enabled']) ? 1 : 0;
    if($global->basic_auth_enabled == true)
    {    
        $global->basic_auth_user = trim($_REQUEST['basic_auth_user']);
        $global->basic_auth_password = $_REQUEST['basic_auth_password'];
    }
    else
    {
        $global->basic_auth_user = '';
        $global->basic_auth_password = '';
    }    
	$global->basic_auth_enabled = isset($_REQUEST['_basic_auth_enabled']) ? 1 : 0;
	$global->installer_base_name = isset($_REQUEST['_installer_base_name']) ? $_REQUEST['_installer_base_name'] : 'installer.php';
	
	          
    $action_updated = $global->save();
	$global->adjust_settings_for_system();	
}

$phpdump_chunkopts = array("20", "100", "500", "1000", "2000");
$mysqlDumpPath = DUP_PRO_Database::GetMySqlDumpPath();
$mysqlDumpFound = ($mysqlDumpPath) ? true : false;
?>

<style>    
    input#package_mysqldump_path_found {margin-top:5px}
    div.dup-feature-found {padding:3px; border:1px solid silver; background: #f7fcfe; border-radius: 3px; width:400px; font-size: 12px}
    div.dup-feature-notfound {padding:3px; border:1px solid silver; background: #fcf3ef; border-radius: 3px; width:400px; font-size: 12px}
</style>

<form id="dup-settings-form" action="<?php echo self_admin_url('admin.php?page=' . DUP_PRO_Constants::$SETTINGS_SUBMENU_SLUG); ?>" method="post" data-parsley-validate>

    <?php wp_nonce_field($nonce_action); ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="page"   value="<?php echo DUP_PRO_Constants::$SETTINGS_SUBMENU_SLUG ?>">
    <input type="hidden" name="tab"   value="package">

    <?php if ($action_updated) : ?>
        <div id="message" class="updated below-h2"><p><?php echo $action_response; ?></p></div>
    <?php endif; ?>	

    <!-- ===============================
    BUILD MODES -->
    <h3 class="title"><?php DUP_PRO_U::_e("Processing") ?> </h3>
    <hr size="1" />
    <table class="form-table">       
         <tr>
            <th scope="row"><label><?php DUP_PRO_U::_e("Database"); ?></label></th>
            <td>
                <input type="radio" name="_package_dbmode" id="package_phpdump" value="php" <?php echo DUP_PRO_U::echo_checked(!$global->package_mysqldump); ?> />
                <label for="package_phpdump"><?php DUP_PRO_U::_e("Use PHP"); ?></label> &nbsp;
                <div style="margin:5px 0px 0px 25px">
                    <label for="_package_phpdump_qrylimit"><?php DUP_PRO_U::_e("Query Limit Size"); ?></label> &nbsp;
                    <select name="_package_phpdump_qrylimit" id="_package_phpdump_qrylimit">
                        <?php
                        foreach ($phpdump_chunkopts as $value)
                        {
                            $selected = ( $global->package_phpdump_qrylimit == $value ? "selected='selected'" : '' );
                            echo "<option {$selected} value='{$value}'>" . number_format($value) . '</option>';
                        }
                        ?>
                    </select>
                    <i style="font-size:12px">(<?php DUP_PRO_U::_e("a higher value speeds up build time but uses more memory"); ?>)</i> 
                </div><br/>

                <?php if (!DUP_PRO_Util::IsShellExecAvailable()) : ?>
                    <p class="description">
                        <?php
							DUP_PRO_U::_e("This server does not have shell_exec configured to run.");
							echo '<br/>';
							DUP_PRO_U::_e("Please contact the server administrator to enable this feature.");
                        ?>
                    </p>
                <?php else : ?>
                    <input type="radio" name="_package_dbmode" value="mysql" id="package_mysqldump" <?php echo DUP_PRO_U::echo_checked($global->package_mysqldump); ?> />
                    <label for="package_mysqldump"><?php DUP_PRO_U::_e("Use mysqldump"); ?></label> &nbsp;
                    <i style="font-size:12px">(<?php DUP_PRO_U::_e("Recommended method - much faster."); ?>)</i> <br/><br/>
                    <div style="margin:5px 0px 0px 25px">
                        <?php if ($mysqlDumpFound) : ?>
                            <div class="dup-feature-found">
                                <?php DUP_PRO_U::_e("Working Path:"); ?> &nbsp;
                                <i><?php echo $mysqlDumpPath ?></i>
                            </div><br/>
                        <?php else : ?>
                            <div class="dup-feature-notfound">
                                <?php
                                DUP_PRO_U::_e('Mysqldump was not found at its default location or the location provided.  Please enter a path to a valid location where mysqldump can run.  If the problem persist contact your server administrator.');
                                ?>
                            </div><br/>
                        <?php endif; ?>

                        <label><?php DUP_PRO_U::_e("Add Custom Path:"); ?></label><br/>
                        <input class="wide-input" type="text" name="_package_mysqldump_path" id="_package_mysqldump_path" value="<?php echo $global->package_mysqldump_path; ?> " />
                        <p class="description">
                            <?php  DUP_PRO_U::_e("This is the path to your mysqldump program."); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </td>
        </tr>	
       
        <tr>
            <th scope="row"><label><?php DUP_PRO_U::_e("Archive Engine"); ?></label></th>
            <td>
                <?php if ($is_zip_available) : ?>				
					<input type="radio" name="archive_build_mode" id="archive_build_mode1" value="<?php echo DUP_PRO_Archive_Build_Mode::Shell_Exec; ?>" <?php echo DUP_PRO_U::echo_checked($global->archive_build_mode == DUP_PRO_Archive_Build_Mode::Shell_Exec); ?> />
					<label for="archive_build_mode1"><?php DUP_PRO_U::_e("Shell Exec"); ?></label> &nbsp;
					<input type="radio" name="archive_build_mode" id="archive_build_mode2"  value="<?php echo DUP_PRO_Archive_Build_Mode::ZipArchive; ?>" <?php echo DUP_PRO_U::echo_checked($global->archive_build_mode == DUP_PRO_Archive_Build_Mode::ZipArchive); ?> />
					<label for="archive_build_mode2"><?php DUP_PRO_U::_e("ZipArchive"); ?></label> &nbsp;
                <?php else : ?>
					<p class="description" style="margin-top:-4px;">
						<?php DUP_PRO_U::_e("Slower ZipArchive engine auto-selected since Shell_Exec zip isn't available on this server.");	?>
					</p>
                <?php endif; ?>
            </td>
        </tr>
		
        <tr>
            <th scope="row"><label><?php DUP_PRO_U::_e("Server Throttle"); ?></label></th>
            <td>
                <input type="radio" name="server_load_reduction" value="<?php echo DUP_PRO_Email_Build_Mode::No_Emails; ?>" <?php echo DUP_PRO_U::echo_checked($global->server_load_reduction == DUP_PRO_Server_Load_Reduction::None); ?> />
                <label for="server_load_reduction"><?php DUP_PRO_U::_e("Off"); ?></label> &nbsp;
                <input type="radio" name="server_load_reduction" value="<?php echo DUP_PRO_Server_Load_Reduction::A_Bit; ?>" <?php echo DUP_PRO_U::echo_checked($global->server_load_reduction == DUP_PRO_Server_Load_Reduction::A_Bit); ?> />
                <label for="server_load_reduction"><?php DUP_PRO_U::_e("Low"); ?></label> &nbsp;
                <input type="radio" name="server_load_reduction"  value="<?php echo DUP_PRO_Server_Load_Reduction::More; ?>" <?php echo DUP_PRO_U::echo_checked($global->server_load_reduction == DUP_PRO_Server_Load_Reduction::More); ?> />
                <label for="server_load_reduction"><?php DUP_PRO_U::_e("Medium"); ?></label> &nbsp;
                <input type="radio" name="server_load_reduction"  value="<?php echo DUP_PRO_Server_Load_Reduction::A_Lot ?>" <?php echo DUP_PRO_U::echo_checked($global->server_load_reduction == DUP_PRO_Server_Load_Reduction::A_Lot); ?> />
                <label for="server_load_reduction"><?php DUP_PRO_U::_e("High"); ?></label> &nbsp;
                <p class="description"><?php  DUP_PRO_U::_e("Throttle to prevent resource complaints on budget hosts. The higher the value the slower the backup.");  ?></p>
            </td>
        </tr>
		
        <tr valign="top">           
            <th scope="row"><label><?php DUP_PRO_U::_e("Max Build Time"); ?></label></th>
            <td>
                <input style="float:left;display:block;margin-right:6px;" data-parsley-required data-parsley-errors-container="#max_package_runtime_in_min_error_container" data-parsley-min="0" data-parsley-type="number" class="narrow-input" type="text" name="max_package_runtime_in_min" id="max_package_runtime_in_min" value="<?php echo $global->max_package_runtime_in_min; ?>" />                 
                <p style="margin-left:4px;"><?php DUP_PRO_U::_e('Minutes'); ?></p>
                <div id="max_package_runtime_in_min_error_container" class="duplicator-error-container"></div>
                <p class="description">  <?php DUP_PRO_U::_e('Max build and storage time until package is auto-cancelled. Set to 0 for no limit.'); ?>  </p>
            </td>
        </tr>		
    </table><br/>

    <!-- ===============================
    ADVANCED SETTINGS -->
    <h3 class="title"><?php DUP_PRO_U::_e("Advanced") ?> </h3>
    <hr size="1" />
	<p class="description" style="color:maroon">
		<?php DUP_PRO_U::_e("Please do not modify advanced settings unless you know the expected result or have talked to support."); ?>
	</p>
    <table class="form-table">	
		<tr>
            <th scope="row"><label><?php DUP_PRO_U::_e("ZipArchive"); ?></label></th>
            <td>
				<input type="radio" name="ziparchive_mode" value="<?php echo DUP_PRO_ZipArchive_Mode::Legacy ?>" <?php echo DUP_PRO_U::echo_checked($global->ziparchive_mode == DUP_PRO_ZipArchive_Mode::Legacy); ?> />
                <label for="ziparchive_legacy_mode"><?php DUP_PRO_U::_e("Legacy"); ?></label> &nbsp; 
				
				<input type="radio" name="ziparchive_mode" value="<?php echo DUP_PRO_ZipArchive_Mode::Enhanced ?>" <?php echo DUP_PRO_U::echo_checked($global->ziparchive_mode == DUP_PRO_ZipArchive_Mode::Enhanced); ?> />
                <label for="ziparchive_enhanced_mode"><?php DUP_PRO_U::_e("Enhanced"); ?></label> <br/><br/>
				
                <label><?php DUP_PRO_U::_e("Buffer Size"); ?></label>
				<input style="width:40px;" data-parsley-required data-parsley-errors-container="#ziparchive_chunk_size_error_container" data-parsley-min="5" data-<?php DUP_PRO_U::_e("Chunck Size"); ?>parsley-type="number" type="text" name="ziparchive_chunk_size_in_mb" id="ziparchive_chunk_size_in_mb" value="<?php echo $global->ziparchive_chunk_size_in_mb; ?>" /> 
                <label><?php DUP_PRO_U::_e('MB'); ?></label>
                <div id="ziparchive_chunk_size_error_container" class="duplicator-error-container"></div>
            </td>
        </tr>
		
		<tr>
            <th scope="row"><label><?php DUP_PRO_U::_e("Thread Lock"); ?></label></th>
            <td>
                <input type="radio" name="lock_mode" value="<?php echo DUP_PRO_Thread_Lock_Mode::Flock; ?>" <?php echo DUP_PRO_U::echo_checked($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock); ?> />
                <label for="lock_mode"><?php DUP_PRO_U::_e("File"); ?></label> &nbsp;
                <input type="radio" name="lock_mode" value="<?php echo DUP_PRO_Thread_Lock_Mode::SQL_Lock; ?>" <?php echo DUP_PRO_U::echo_checked($global->lock_mode == DUP_PRO_Thread_Lock_Mode::SQL_Lock); ?> />
                <label for="lock_mode"><?php DUP_PRO_U::_e("SQL"); ?></label> &nbsp;
            </td>
        </tr>

		<tr>
            <th scope="row"><label><?php DUP_PRO_U::_e("JSON"); ?></label></th>
            <td>
                <input type="radio" name="json_mode" value="<?php echo DUP_PRO_JSON_Mode::PHP; ?>" <?php echo DUP_PRO_U::echo_checked($global->json_mode == DUP_PRO_JSON_Mode::PHP); ?> />
                <label for="json_mode"><?php DUP_PRO_U::_e("PHP"); ?></label> &nbsp;
                <input type="radio" name="json_mode" value="<?php echo DUP_PRO_JSON_Mode::Custom; ?>" <?php echo DUP_PRO_U::echo_checked($global->json_mode == DUP_PRO_JSON_Mode::Custom); ?> />
                <label for="json_mode"><?php DUP_PRO_U::_e("Custom"); ?></label> &nbsp;
            </td>
        </tr>
		
        <tr valign="top">           
            <th scope="row"><label><?php DUP_PRO_U::_e("Max Worker Time"); ?></label></th>
            <td>
                <input style="float:left;display:block;margin-right:6px;" data-parsley-required data-parsley-errors-container="#php_max_worker_time_in_sec_error_container" data-parsley-min="10" data-parsley-type="number" class="narrow-input" type="text" name="php_max_worker_time_in_sec" id="php_max_worker_time_in_sec" value="<?php echo $global->php_max_worker_time_in_sec; ?>" />                 
                <p style="margin-left:4px;"><?php DUP_PRO_U::_e('Seconds'); ?></p>
                <div id="php_max_worker_time_in_sec_error_container" class="duplicator-error-container"></div>
                <p class="description">
					<?php
					DUP_PRO_U::_e("Lower is more reliable but slower. Max is $max_worker_cap_in_sec sec based on PHP setting 'max_execution_time'.");
					?>
                </p>
            </td>
        </tr>   
                
		<tr>
            <th scope="row"><label><?php DUP_PRO_U::_e("Ajax"); ?></label></th>
            <td>
                <input type="radio" name="ajax_protocol" value="admin" <?php echo DUP_PRO_U::echo_checked($global->ajax_protocol == 'admin'); ?> />
                <label for="ajax_protocol"><?php DUP_PRO_U::_e("Auto"); ?></label> &nbsp;
                <input type="radio" name="ajax_protocol" value="http" <?php echo DUP_PRO_U::echo_checked($global->ajax_protocol == 'http'); ?> />
                <label for="ajax_protocol"><?php DUP_PRO_U::_e("HTTP"); ?></label> &nbsp;
                <input type="radio" name="ajax_protocol"  value="https" <?php echo DUP_PRO_U::echo_checked($global->ajax_protocol == 'https'); ?> />
                <label for="ajax_protocol"><?php DUP_PRO_U::_e("HTTPS"); ?></label> &nbsp;
                <input type="radio" name="ajax_protocol"  value="custom" <?php echo DUP_PRO_U::echo_checked($global->ajax_protocol == 'custom'); ?> />
                <label for="ajax_protocol"><?php DUP_PRO_U::_e("Custom URL:"); ?></label> &nbsp;
                <input style="width:353px" type="text"id="custom_ajax_url" name="custom_ajax_url" placeholder="<?php DUP_PRO_U::_e('Consult support before changing.'); ?>" value="<?php echo $global->custom_ajax_url; ?>" data-parsley-trigger="change" data-parsley-type="url"/>
                <p class="description">
					<?php DUP_PRO_U::_e("Used to kick off build worker. Only change if packages get stuck at start of build."); 	?>
                </p>
            </td>
        </tr>

		<tr valign="top">
            <th scope="row"><label><?php DUP_PRO_U::_e('Client-side Kickoff'); ?></label></th>
            <td>
                <input type="checkbox" name="_clientside_kickoff" id="_uninstall_settings" <?php DUP_PRO_U::echo_checked($global->clientside_kickoff); ?> /> 
                <label for="_clientside_kickoff"><?php DUP_PRO_U::_e("Enabled") ?> </label><br/>
				<p class="description">
					<?php DUP_PRO_U::_e('Initiate package build from client. Only check this if instructed to by Snap Creek support.'); ?>
                </p>
            </td>
        </tr> 
		
        <tr valign="top">
            <th scope="row"><label><?php DUP_PRO_U::_e("Basic Auth"); ?></label></th>
            <td>
                <input type="checkbox" name="_basic_auth_enabled" id="_basic_auth_enabled" <?php DUP_PRO_U::echo_checked($global->basic_auth_enabled); ?> /> 
                <label for="_basic_auth_enabled"><?php DUP_PRO_U::_e("Enabled") ?> </label><br/>
                <input style="margin-top:8px;width:200px;" class="wide-input" autocomplete="off"  placeholder="<?php DUP_PRO_U::_e('User'); ?>" type="text" name="basic_auth_user" id="basic_auth_user" value="<?php echo $global->basic_auth_user; ?>" />
                <input id='auth_password' autocomplete="off" style="width:200px;" class="wide-input"  placeholder="<?php DUP_PRO_U::_e('Password'); ?>" type="password" name="basic_auth_password" id="basic_auth_password" value="<?php echo $global->basic_auth_password; ?>" />
                <label for="auth_password">
                    <i class="dpro-edit-info">
                        <input type="checkbox" onclick="DupPro.UI.TogglePasswordDisplay(this.checked, 'auth_password');" /> <?php DUP_PRO_U::_e('Show Password') ?>
                    </i>
                </label>
            </td>
        </tr> 
		
		<tr valign="top">
            <th scope="row"><label><?php DUP_PRO_U::_e('Installer Name'); ?></label></th>
            <td>
               <input type="text" name="_installer_base_name" id="_installer_base_name" value="<?php echo $global->installer_base_name; ?>"
					  data-parsley-required 
					  data-parsley-minlength="10"
					  data-parsley-errors-container="#installer_base_name_error_container" /> 
				<div id="installer_base_name_error_container" class="duplicator-error-container"></div>
				<p class="description">
					<?php DUP_PRO_U::_e('The base name of the installer file. Only change if host prevents using installer.php'); ?>
                </p>
            </td>
        </tr> 
		
		
    </table>

    <p class="submit" style="margin: 20px 0px 0xp 5px;">
        <br/><input type="submit" name="submit" id="submit" class="button-primary" value="<?php DUP_PRO_U::_e('Save Package Settings') ?>" style="display: inline-block;" />
    </p>
</form>

<script type="text/javascript">
    jQuery(document).ready(function ($) {

    });
</script>
