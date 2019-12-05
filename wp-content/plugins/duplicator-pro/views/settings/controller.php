<?php

DUP_PRO_Util::CheckPermissions('manage_options');

global $wpdb;

//COMMON HEADER DISPLAY
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/assets/js/javascript.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/views/inc.header.php');
$current_tab = isset($_REQUEST['tab']) ? esc_html($_REQUEST['tab']) : 'general';
?>

<style>
    .narrow-input { width: 80px; }
    .wide-input {width: 400px; } 
	 table.form-table tr td { padding-top: 25px; }
</style>

<div class="wrap">
    <?php duplicator_pro_header(DUP_PRO_U::__("Settings")) ?>

    <h2 class="nav-tab-wrapper">  
        <a href="?page=duplicator-pro-settings&tab=general" class="nav-tab <?php echo ($current_tab == 'general') ? 'nav-tab-active' : '' ?>"> <?php DUP_PRO_U::_e('General'); ?></a> 
		<a href="?page=duplicator-pro-settings&tab=package" class="nav-tab <?php echo ($current_tab == 'package') ? 'nav-tab-active' : '' ?>"> <?php DUP_PRO_U::_e('Packages'); ?></a> 		
		<a href="?page=duplicator-pro-settings&tab=schedule" class="nav-tab <?php echo ($current_tab == 'schedule') ? 'nav-tab-active' : '' ?>"> <?php DUP_PRO_U::_e('Schedules'); ?></a> 	
        <a href="?page=duplicator-pro-settings&tab=storage" class="nav-tab <?php echo ($current_tab == 'storage') ? 'nav-tab-active' : '' ?>"> <?php DUP_PRO_U::_e('Storage'); ?></a> 
        <a href="?page=duplicator-pro-settings&tab=licensing" class="nav-tab <?php echo ($current_tab == 'licensing') ? 'nav-tab-active' : '' ?>"> <?php DUP_PRO_U::_e('Licensing'); ?></a> 
    </h2> 	

    <?php
    switch ($current_tab) {
        case 'general': include('general.php');            
            break;
		case 'package': include('package.php');
            break; 
		case 'schedule': include('schedule.php');
            break; 		
        case 'storage': include('storage.php');
            break;              
        case 'licensing': include('licensing.php');
            break;               
    }
    ?>
</div>
