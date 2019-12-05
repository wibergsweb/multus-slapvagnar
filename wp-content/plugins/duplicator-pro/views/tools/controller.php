<?php
DUP_PRO_Util::CheckPermissions('manage_options');

global $wpdb;

//COMMON HEADER DISPLAY
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/assets/js/javascript.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/views/inc.header.php');
$current_tab = isset($_REQUEST['tab']) ? esc_html($_REQUEST['tab']) : 'logging';
?>
<div class="wrap">
    <?php duplicator_pro_header(DUP_PRO_U::__("Tools")) ?>

    <h2 class="nav-tab-wrapper">  
        <a href="?page=duplicator-pro-tools" class="nav-tab <?php echo ($current_tab == 'logging') ? 'nav-tab-active' : '' ?>"> <?php DUP_PRO_U::_e('Logging'); ?></a>  
        <a href="?page=duplicator-pro-tools&tab=diagnostics" class="nav-tab <?php echo ($current_tab == 'diagnostics') ? 'nav-tab-active' : '' ?>"> <?php DUP_PRO_U::_e('Diagnostics'); ?></a> 
        <a href="?page=duplicator-pro-tools&tab=support" class="nav-tab <?php echo ($current_tab == 'support') ? 'nav-tab-active' : '' ?>"> <?php DUP_PRO_U::_e('Support'); ?></a> 		
    </h2> 	

    <?php
    switch ($current_tab)
    {
        case 'logging': include('logging.php');
            break;
        case 'diagnostics': include('diagnostics.php');
            break;
        case 'support': include('support.php');
            break;
    }
    ?>
</div>
