<?php
global $wp_version;
global $wpdb;


require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/utilities/class.license.u.php');

//$force_refresh = false;
$force_refresh = true;
$nonce_action = 'duppro-settings-licensing-edit';

$action_updated = null;


/* @var $global DUP_PRO_Global_Entity */
$global = DUP_PRO_Global_Entity::get_instance();

//SAVE RESULTS
if (isset($_POST['action']))
{    
   // check_admin_referer($nonce_action);
    $action = $_POST['action'];
    
    switch($action)
    {
        case 'activate':            
            $submitted_license_key = trim($_REQUEST['_license_key']);
            update_option(DUP_PRO_Constants::LICENSE_KEY_OPTION_NAME, $submitted_license_key);

            $action_updated = DUP_PRO_License_Utility::change_license_activation(true);
            if($action_updated)
            {
                $action_response = DUP_PRO_U::__("License Activated");    
            }
            else
            {
                $action_response = DUP_PRO_U::__("Error Activating License");    
            }
            
            break;
        
        case 'deactivate':
            $action_updated = DUP_PRO_License_Utility::change_license_activation(false);
            if($action_updated)
            {
                $action_response = DUP_PRO_U::__("License Deactivated");    
            }
            else
            {
                $action_response = DUP_PRO_U::__("Error Deactivating License");    
            }
            break;
     }
     
     $force_refresh = true;
}

$license_status = DUP_PRO_License_Utility::get_license_status($force_refresh);
$license_text_disabled = false;
$activate_button_text = DUP_PRO_U::__('Activate');     

if($license_status == DUP_PRO_License_Status::Valid)
{
    $license_status_style = 'color:#509B18';
    $license_status_text = DUP_PRO_U::__('Status: Active');
    $activate_button_text = DUP_PRO_U::__('Deactivate');
    $license_text_disabled = true;
}
else if(($license_status == DUP_PRO_License_Status::Inactive) || ($license_status == DUP_PRO_License_Status::Site_Inactive))
{
    $license_status_style = 'color:#dd3d36;';
    $license_status_text = DUP_PRO_U::__('Status: Inactive');
}
else
{
    $license_status_string = DUP_PRO_License_Utility::get_license_status_string($license_status);
    $license_status_style = 'color:#dd3d36;';    
    $license_status_text = sprintf(DUP_PRO_U::__('Status: %1$s. Please %2$sgo to snapcreek.com%3$s for assistance, to purchase a new license or to renew an existing license.'), $license_status_string, '<a target="_blank" href="https://snapcreek.com/">', '</a>');
}

$license_key = get_option(DUP_PRO_Constants::LICENSE_KEY_OPTION_NAME, '');


?>

<style>    

</style>

<form id="dup-settings-form" action="<?php echo self_admin_url('admin.php?page=' . DUP_PRO_Constants::$SETTINGS_SUBMENU_SLUG ); ?>" method="post" data-parsley-validate>

    <?php wp_nonce_field($nonce_action); ?>
    <input type="hidden" name="action" value="save" id="action">
    <input type="hidden" name="page"   value="<?php echo DUP_PRO_Constants::$SETTINGS_SUBMENU_SLUG ?>">
    <input type="hidden" name="tab"   value="licensing">

    <?php if ($action_updated === true) { ?>
        <div id="message" class="updated below-h2"><p><?php echo $action_response; ?></p></div>
    <?php } else if ($action_updated === false) { ?>	
        <div id="message" class="error below-h2"><p><?php echo $action_response; ?></p></div>
    <?php } ?>


    <!-- ===============================
    PLUG-IN SETTINGS -->
    <h3 class="title"><?php DUP_PRO_U::_e("Plugin") ?> </h3>
    <hr size="1" />
    <table class="form-table">
        <tr valign="top">           
            <th scope="row"><label><?php DUP_PRO_U::_e("License Key"); ?></label></th>
            <td>
                <input type="text" class="wide-input" name="_license_key" id="_license_key" <?php DUP_PRO_U::echo_disabled($license_text_disabled); ?> value="<?php echo $license_key; ?>" />
                <p class="description">
                    <?php echo "<span style='$license_status_style'>$license_status_text</span>"; ?>
                </p>
            </td>
        </tr>	        
        <tr valign="top">           
            <th scope="row"><label><?php DUP_PRO_U::_e("Activation"); ?></label></th>
            <td>
                <button onclick="DupPro.Licensing.ChangeActivationStatus(<?php echo (($license_status != DUP_PRO_License_Status::Valid) ? 'true' : 'false'); ?>);return false;"><?php echo $activate_button_text; ?></button>
            </td>
        </tr>
    </table>   

</form>
<script type="text/javascript">
    jQuery(document).ready(function($) {
             
        DupPro.Licensing = new Object();
        
        // which: 0=installer, 1=archive, 2=sql file, 3=log
        DupPro.Licensing.ChangeActivationStatus = function (activate) {    
            
            if(activate){
          
                $('#action').val('activate');
            } 
            else  {
                $('#action').val('deactivate');
            }

            $('#dup-settings-form').submit();
        }
    });
</script>
