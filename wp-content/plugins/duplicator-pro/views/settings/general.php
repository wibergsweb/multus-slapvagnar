<?php
global $wp_version;
global $wpdb;

$nonce_action = 'duppro-settings-general-edit';
$action_updated = null;
$action_response = DUP_PRO_U::__("General Settings Saved");

$global = DUP_PRO_Global_Entity::get_instance();

//SAVE RESULTS
if (isset($_POST['action']) && $_POST['action'] == 'save')
{
    check_admin_referer($nonce_action);

    //General Tab
    //Plugin
    $global->uninstall_settings = isset($_REQUEST['_uninstall_settings']) ? 1 : 0;
    $global->uninstall_files = isset($_REQUEST['_uninstall_files']) ? 1 : 0;
    $global->uninstall_tables = isset($_REQUEST['_uninstall_tables']) ? 1 : 0;
	
    //Package
    $global->package_debug = isset($_REQUEST['_package_debug']) ? 1 : 0;

    //WPFront
    $global->wpfront_integrate = isset($_REQUEST['_wpfront_integrate']) ? 1 : 0;
    DUP_PRO_Util::InitSnapshotDirectory();
 
    $old_send_trace_to_error_log = get_option('duplicator_pro_send_trace_to_error_log', false);
    $send_trace_to_error_log = isset($_REQUEST['_send_trace_to_error_log']) ? true : false;
    update_option('duplicator_pro_send_trace_to_error_log', $send_trace_to_error_log);
	
    $action_updated = $global->save();
	$global->adjust_settings_for_system();	
}

$send_trace_to_error_log = get_option('duplicator_pro_send_trace_to_error_log');
$wpfront_ready = apply_filters('wpfront_user_role_editor_duplicator_integration_ready', false);
?>

<style>
   
</style>

<form id="dup-settings-form" action="<?php echo self_admin_url('admin.php?page=' . DUP_PRO_Constants::$SETTINGS_SUBMENU_SLUG); ?>" method="post" data-parsley-validate>

    <?php wp_nonce_field($nonce_action); ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="page"   value="<?php echo DUP_PRO_Constants::$SETTINGS_SUBMENU_SLUG ?>">
    <input type="hidden" name="tab"   value="general">

    <?php if ($action_updated) : ?>
        <div id="message" class="updated below-h2"><p><?php echo $action_response; ?></p></div>
    <?php endif; ?>	


    <!-- ===============================
    PLUG-IN SETTINGS -->
    <h3 class="title"><?php DUP_PRO_U::_e("Plugin") ?> </h3>
    <hr size="1" />
    <table class="form-table">       
        <tr valign="top">
            <th scope="row"><label><?php DUP_PRO_U::_e("Version"); ?></label></th>
            <td><?php echo DUPLICATOR_PRO_VERSION ?></td>
        </tr>	
        <tr valign="top">
            <th scope="row"><label><?php DUP_PRO_U::_e("Uninstall"); ?></label></th>
            <td>
                <input type="checkbox" name="_uninstall_settings" id="_uninstall_settings" <?php DUP_PRO_U::echo_checked($global->uninstall_settings); ?> /> 
                <label for="_uninstall_settings"><?php DUP_PRO_U::_e("Delete Plugin Settings") ?> </label><br/>
                <input type="checkbox" name="_uninstall_files" id="_uninstall_files" <?php echo DUP_PRO_U::echo_checked($global->uninstall_files); ?> /> 
                <label for="_uninstall_files"><?php DUP_PRO_U::_e("Delete Entire Storage Directory") ?></label><br/>
            </td>
        </tr>         

        <tr>
            <th scope="row"><label><?php DUP_PRO_U::_e("Custom Roles"); ?></label></th>
            <td>
                <input type="checkbox" name="_wpfront_integrate" id="_wpfront_integrate" <?php echo DUP_PRO_U::echo_checked($global->wpfront_integrate); ?> <?php echo $wpfront_ready ? '' : 'disabled'; ?> />
                <label for="_wpfront_integrate"><?php DUP_PRO_U::_e("Enable User Role Editor Plugin Integration"); ?></label>
                <p class="description">
                    <?php
                    printf('%s <a href="https://wordpress.org/plugins/wpfront-user-role-editor/" target="_blank">%s</a> %s'
                            . ' <a href="https://wpfront.com/lifeinthegrid" target="_blank">%s</a> %s '
                            . ' <a href="https://wpfront.com/integrations/duplicator-integration/" target="_blank">%s</a>', DUP_PRO_U::__('The User Role Editor Plugin'), DUP_PRO_U::__('Free'), DUP_PRO_U::__('or'), DUP_PRO_U::__('Professional'), DUP_PRO_U::__('must be installed to use'), DUP_PRO_U::__('this feature.')
                    );
                    ?> 
                </p>
            </td>
        </tr>	
    </table><br/>

    <!-- ===============================
   DEBUG SETTINGS -->
    <h3 class="title"><?php DUP_PRO_U::_e('Debug') ?> </h3>
    <hr size="1" />

    <table class="form-table">		
        <tr>
            <th scope="row"><label><?php echo DUP_PRO_U::__("Trace Log"); ?></label></th>
            <td>
                <input type="checkbox" name="_send_trace_to_error_log" id="_send_trace_to_error_log" <?php echo DUP_PRO_U::echo_checked($send_trace_to_error_log); ?> value="1" />
                <label for="_send_trace_to_error_log"><?php DUP_PRO_U::_e("Enabled"); ?></label>
                <p class="description">
                    <?php
                    DUP_PRO_U::_e("Send execution trace to error log in addition to the trace log. <br/>WARNING: This can impact performance.");
                    ?>
                </p>
            </td>
        </tr>	 
        <tr>
            <th scope="row"></th>
            <td>
                <button class="button" <?php DUP_PRO_U::echo_disabled(DUP_PRO_U::trace_file_exists() === false); ?> onclick="DupPro.Pack.DownloadTraceLog();
                        return false">
                    <i class="fa fa-download"></i> <?php echo DUP_PRO_U::__('Download') . ' (' . DUP_PRO_U::get_trace_log_status() . ')'; ?>
                </button>

            </td>
        </tr>
        <tr>
            <th scope="row"><label><?php DUP_PRO_U::_e("Package Debug"); ?></label></th>
            <td>
                <input type="checkbox" name="_package_debug" id="_package_debug" <?php echo DUP_PRO_U::echo_checked($global->package_debug); ?> />
                <label for="_package_debug"><?php DUP_PRO_U::_e("Enabled"); ?></label>
                <p class="description">
                    <?php
                    DUP_PRO_U::_e("Show package debug status on Packages Screen");
                    ?>
                </p>
            </td>
        </tr>	
    </table><br/>

    <p class="submit" style="margin: 20px 0px 0xp 5px;">
        <br/>
        <input type="submit" name="submit" id="submit" class="button-primary" value="<?php DUP_PRO_U::_e('Save General Settings') ?>" style="display: inline-block;" />
    </p>

</form>
<script type="text/javascript">
    jQuery(document).ready(function ($) {
        // which: 0=installer, 1=archive, 2=sql file, 3=log
        DupPro.Pack.DownloadTraceLog = function () {
            var actionLocation = ajaxurl + '?action=duplicator_pro_get_trace_log';
            location.href = actionLocation;
        }
    });
</script>
