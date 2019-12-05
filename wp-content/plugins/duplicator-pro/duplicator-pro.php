<?php
/*
  Plugin Name: Duplicator Pro
  Plugin URI: http://snapcreek.com/
  Description: Create a backup of your WordPress files and database. Duplicate and move an entire site from one location to another in a few steps. Create a full snapshot of your site at any point in time.
  Version: 2.0.10
  Author: Snap Creek
  Author URI: http://snapcreek.com
  License: GPLv2 or later
 */

/* ================================================================================ 
  Copyright 2011-2015  Cory Lamle, Robert Riley

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  ================================================================================ */

require_once("define.php");
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/class.package.runner.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/class.constants.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/utilities/class.license.u.php');

define('EDD_DUPPRO_STORE_URL', 'https://snapcreek.com');
define('EDD_DUPPRO_ITEM_NAME', 'Duplicator Pro');

if(!class_exists('EDD_SL_Plugin_Updater'))
{
    include (dirname(__FILE__) . '/lib/edd/EDD_SL_Plugin_Updater.php');
}

$license_key = get_option(DUP_PRO_Constants::LICENSE_KEY_OPTION_NAME, '');


$edd_updater = new EDD_SL_Plugin_Updater(EDD_DUPPRO_STORE_URL, __FILE__,
                                         array('version' => DUPLICATOR_PRO_VERSION, 'license' => $license_key, 'item_name' => EDD_DUPPRO_ITEM_NAME, 'author' => 'Snap Creek Software'));


// Only start the package runner once it's been confirmed that everything has been installed
if(get_option('duplicator_pro_plugin_version') == DUPLICATOR_PRO_VERSION)
{
    DUP_PRO_Package_Runner::init();
}

if (is_admin() == true)
{
    // RSR: Put in because customer was hitting memory limit
    ini_set('memory_limit', '256M');
    
    require_once(dirname(__FILE__) . '/classes/utilities/class.u.php');
    require_once(dirname(__FILE__) . '/classes/entities/class.global.entity.php');
    require_once(dirname(__FILE__) . '/classes/entities/class.package.template.entity.php');
    require_once 'classes/class.logging.php';
    require_once 'classes/utilities/class.utility.php';
    require_once 'classes/class.ui.php';
    require_once 'classes/class.server.php';
    require_once 'classes/class.package.php';
    require_once 'views/class.web.services.php';

    require_once 'classes/entities/class.json.entity.base.php';

    /* ACTIVATION 
      Only called when plugin is activated */
    function duplicator_pro_activate()
    {
        global $wpdb;
        
        //Only update database on version update        
        if (DUPLICATOR_PRO_VERSION != get_option("duplicator_pro_plugin_version"))
        {
            $table_name = $wpdb->prefix . "duplicator_pro_packages";

            //PRIMARY KEY must have 2 spaces before for dbDelta to work
            $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
			   `id`			BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT  PRIMARY KEY,
			   `name`		VARCHAR(250)	NOT NULL,
			   `hash`		VARCHAR(50)		NOT NULL,
			   `status`		INT(11)			NOT NULL,
			   `created`	DATETIME		NOT NULL DEFAULT '0000-00-00 00:00:00',
			   `owner`		VARCHAR(60)		NOT NULL,
			   `package`	MEDIUMBLOB		NOT NULL,
			    KEY `hash` (`hash`))";

            require_once(DUPLICATOR_PRO_WPROOTPATH . 'wp-admin/includes/upgrade.php');
            @dbDelta($sql);

            DUP_PRO_JSON_Entity_Base::init_table();

            DUP_PRO_Global_Entity::initialize_plugin_data();

            DUP_PRO_Package_Template_Entity::create_default();
        }

        //WordPress Options Hooks
        update_option('duplicator_pro_plugin_version', DUPLICATOR_PRO_VERSION);

        //Setup All Directories
        DUP_PRO_Util::InitSnapshotDirectory();
    }

    
    function duplicator_pro_plugins_loaded()
    {
        duplicator_pro_update();
        duplicator_pro_patched_data_initialization();
    }
    
    // Necessary to handle data that needs to be initialized because of fixes etc
    function duplicator_pro_patched_data_initialization()
    {
        /* @var $global DUP_PRO_Global_Entity */
        $global = DUP_PRO_Global_Entity::get_instance();
        
        $global->configure_dropbox_transfer_mode();               
    }
    
    
    /* UPDATE 
      register_activation_hook is not called when a plugin is updated
      so we need to use the following function */
    function duplicator_pro_update()
    {
        if (DUPLICATOR_PRO_VERSION != get_option("duplicator_pro_plugin_version"))
        {
            duplicator_pro_activate();
        }
        load_plugin_textdomain(DUP_PRO_Constants::PLUGIN_SLUG, FALSE, dirname(plugin_basename(__FILE__)) . '/lang/');
    }

    /* DEACTIVATION / UNINSTALL 
     * Only called when plugin is deactivated.
     * For uninstall see uninstall.php */
    function duplicator_pro_deactivate()
    {
        //No actions needed yet
    }

    /* META LINK ADDONS
      Adds links to the plugins manager page */
    function duplicator_pro_meta_links($links, $file)
    {
        $plugin = plugin_basename(__FILE__);
        // create link
        if ($file == $plugin)
        {
            $help_url = DUP_PRO_U::menu_page_url(DUP_PRO_Constants::$TOOLS_SUBMENU_SLUG, false);
            $links[] = sprintf('<a href="%1$s" title="%2$s">%3$s</a>', $help_url, DUP_PRO_U::__('Get Help'), DUP_PRO_U::__('Help'));

            return $links;
        }
        return $links;
    }

    //HOOKS 
    register_activation_hook(__FILE__, 'duplicator_pro_activate');
    register_deactivation_hook(__FILE__, 'duplicator_pro_deactivate');
    

    //ACTIONS
    $web_services = new DUP_PRO_Web_Services();

    $web_services->init();

    add_action('plugins_loaded', 'duplicator_pro_plugins_loaded');
    add_action('admin_init', 'duplicator_pro_init');
	
	//if(defined('MULTISITE') && MULTISITE && defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE)
	if(defined('MULTISITE') && MULTISITE)
	{
		add_action('network_admin_menu', 'duplicator_pro_menu');	
	}
	else
	{
		add_action('admin_menu', 'duplicator_pro_menu');	
	}
	
    

    add_action('wp_ajax_DUP_PRO_UI_SaveViewStateByPost', array('DUP_PRO_UI', 'SaveViewStateByPost'));
	
    if(DUP_PRO_U::is_multisite())
	{
		add_action('network_admin_notices', array('DUP_PRO_UI', 'ShowReservedFilesNotice'));
		add_action('network_admin_notices', 'duplicator_pro_license_alert_check');
	}
	else
	{
		add_action('admin_notices', array('DUP_PRO_UI', 'ShowReservedFilesNotice'));
		add_action('admin_notices', 'duplicator_pro_license_alert_check');
	}
	
    add_action('plugins_loaded', 'duplicator_pro_wpfront_integrate');

    //FILTERS
	if(defined('MULTISITE') && MULTISITE && defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE)
	{
		add_filter('network_admin_plugin_action_links', 'duplicator_pro_manage_link', 10, 2);
		add_filter('network_admin_plugin_row_meta', 'duplicator_pro_meta_links', 10, 2);
	}
	else
	{
		add_filter('plugin_action_links', 'duplicator_pro_manage_link', 10, 2);
		add_filter('plugin_row_meta', 'duplicator_pro_meta_links', 10, 2);
	}
    
    function duplicator_pro_license_alert_check() 
    {
        $on_licensing_tab = (isset($_REQUEST['tab']) && ($_REQUEST['tab'] === 'licensing'));
                
        if(($on_licensing_tab === false) && (DUP_PRO_License_Utility::get_license_status(false) !== DUP_PRO_License_Status::Valid))
        {
            $activate_url = self_admin_url() . "admin.php?page=" . DUP_PRO_Constants::$SETTINGS_SUBMENU_SLUG . '&tab=licensing';

            $mailto_url = 'mailto:support@snapcreek.com';
            
            echo sprintf('<div class="error"><p><b>Warning!</b> Your Duplicator Pro license is invalid or disabled so you won\'t get important updates! <a href="%1$s">Activate your license</a> or <a target="_blank" href="%2$s">contact us to get one</a>.</p></div>', $activate_url, $mailto_url);
        }
    }
        
    function duplicator_pro_wpfront_integrate()
    {
        $global = DUP_PRO_Global_Entity::get_instance();
        
       // if (DUP_PRO_Settings::Get('wpfront_integrate'))
        if($global->wpfront_integrate)
        {
            do_action('wpfront_user_role_editor_duplicator_pro_init', array('export', 'manage_options', 'read'));
        }
    }

    /**
     *  DUPLICATOR_PRO_INIT
     *  Init routines  */
    function duplicator_pro_init()
    {
        /* CSS */
        wp_register_style('dup-pro-jquery-ui', DUPLICATOR_PRO_PLUGIN_URL . 'assets/css/jquery-ui.css', null, "1.11.2");
        wp_register_style('dup-pro-font-awesome', DUPLICATOR_PRO_PLUGIN_URL . 'assets/css/font-awesome.min.css', null, '4.3.0');
        wp_register_style('dup-pro-plugin-style', DUPLICATOR_PRO_PLUGIN_URL . 'assets/css/style.css', null, DUPLICATOR_PRO_VERSION);
        wp_register_style('dup-pro-parsley',DUPLICATOR_PRO_PLUGIN_URL . 'assets/css/parsley.css', null, '2.0.6');
		wp_register_style('dup-pro-parsley',DUPLICATOR_PRO_PLUGIN_URL . 'assets/css/parsley.css', null, '2.0.6');
		wp_register_style('dup-pro-jquery-qtip',DUPLICATOR_PRO_PLUGIN_URL . 'assets/js/jquery.qtip/jquery.qtip.min.css', null, '2.2.1');
        /* JS */
        wp_register_script('parsley', DUPLICATOR_PRO_PLUGIN_URL . 'assets/js/parsley.min.js', array('jquery'), '2.0.6');
		wp_register_script('dup-pro-jquery-qtip', DUPLICATOR_PRO_PLUGIN_URL . 'assets/js/jquery.qtip/jquery.qtip.min.js', array('jquery'), '2.2.1');
    }

    //PAGE VIEWS
    function duplicator_pro_get_menu()
    {
        $current_page = isset($_REQUEST['page']) ? esc_html($_REQUEST['page']) : DUP_PRO_Constants::$PACKAGES_SUBMENU_SLUG;

        switch ($current_page)
        {
            case DUP_PRO_Constants::$PACKAGES_SUBMENU_SLUG: include('views/packages/controller.php');
                break;
            case DUP_PRO_Constants::$SCHEDULES_SUBMENU_SLUG: include('views/schedules/controller.php');
                break;            
            case DUP_PRO_Constants::$STORAGE_SUBMENU_SLUG: include('views/storage/controller.php');
                break;
            case DUP_PRO_Constants::$TEMPLATES_SUBMENU_SLUG: include('views/templates/controller.php');
                break;
            case DUP_PRO_Constants::$TOOLS_SUBMENU_SLUG: include('views/tools/controller.php');
                break;
            case DUP_PRO_Constants::$SETTINGS_SUBMENU_SLUG: include('views/settings/controller.php');
                break;

            default:
                DUP_PRO_U::log_object("Error current page doesnt show up", $_REQUEST);
        }
    }

    /**
     *  DUPLICATOR_PRO_MENU
     *  Loads the menu item into the WP tools section and queues the actions for only this plugin */
    function duplicator_pro_menu()
    {
        $wpfront_caps_translator = 'wpfront_user_role_editor_duplicator_pro_translate_capability';
		$icon_svg = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4iICJodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQiPjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iQXJ0d29yayIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiIHdpZHRoPSIyMy4yNXB4IiBoZWlnaHQ9IjIyLjM3NXB4IiB2aWV3Qm94PSIwIDAgMjMuMjUgMjIuMzc1IiBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCAyMy4yNSAyMi4zNzUiIHhtbDpzcGFjZT0icHJlc2VydmUiPjxwYXRoIGZpbGw9IiM5Q0ExQTYiIGQ9Ik0xOC4wMTEsMS4xODhjLTEuOTk1LDAtMy42MTUsMS42MTgtMy42MTUsMy42MTRjMCwwLjA4NSwwLjAwOCwwLjE2NywwLjAxNiwwLjI1TDcuNzMzLDguMTg0QzcuMDg0LDcuNTY1LDYuMjA4LDcuMTgyLDUuMjQsNy4xODJjLTEuOTk2LDAtMy42MTUsMS42MTktMy42MTUsMy42MTRjMCwxLjk5NiwxLjYxOSwzLjYxMywzLjYxNSwzLjYxM2MwLjYyOSwwLDEuMjIyLTAuMTYyLDEuNzM3LTAuNDQ1bDIuODksMi40MzhjLTAuMTI2LDAuMzY4LTAuMTk4LDAuNzYzLTAuMTk4LDEuMTczYzAsMS45OTUsMS42MTgsMy42MTMsMy42MTQsMy42MTNjMS45OTUsMCwzLjYxNS0xLjYxOCwzLjYxNS0zLjYxM2MwLTEuOTk3LTEuNjItMy42MTQtMy42MTUtMy42MTRjLTAuNjMsMC0xLjIyMiwwLjE2Mi0xLjczNywwLjQ0M2wtMi44OS0yLjQzNWMwLjEyNi0wLjM2OCwwLjE5OC0wLjc2MywwLjE5OC0xLjE3M2MwLTAuMDg0LTAuMDA4LTAuMTY2LTAuMDEzLTAuMjVsNi42NzYtMy4xMzNjMC42NDgsMC42MTksMS41MjUsMS4wMDIsMi40OTUsMS4wMDJjMS45OTQsMCwzLjYxMy0xLjYxNywzLjYxMy0zLjYxM0MyMS42MjUsMi44MDYsMjAuMDA2LDEuMTg4LDE4LjAxMSwxLjE4OHoiLz48L3N2Zz4=';

        //Main Menu
        $perms = 'export';
        $perms = apply_filters($wpfront_caps_translator, $perms);
		$main_menu = add_menu_page('Duplicator Plugin', 'Duplicator Pro', $perms, DUP_PRO_Constants::PLUGIN_SLUG, 'duplicator_pro_get_menu', $icon_svg);
		//$main_menu = add_menu_page('Duplicator Plugin', 'Duplicator Pro', $perms, DUP_PRO_Constants::PLUGIN_SLUG, 'duplicator_pro_get_menu', plugins_url('duplicator-pro/assets/img/logo-xs.png'));

        $perms = 'export';
        $perms = apply_filters($wpfront_caps_translator, $perms);
        $page_packages = add_submenu_page(DUP_PRO_Constants::PLUGIN_SLUG, DUP_PRO_U::__('Packages'), DUP_PRO_U::__('Packages'), $perms, DUP_PRO_Constants::$PACKAGES_SUBMENU_SLUG, 'duplicator_pro_get_menu');

        $perms = 'manage_options';
        $perms = apply_filters($wpfront_caps_translator, $perms);
        $page_schedules = add_submenu_page(DUP_PRO_Constants::PLUGIN_SLUG, DUP_PRO_U::__('Schedules'), DUP_PRO_U::__('Schedules'), $perms, DUP_PRO_Constants::$SCHEDULES_SUBMENU_SLUG, 'duplicator_pro_get_menu');

        $perms = 'manage_options';
        $perms = apply_filters($wpfront_caps_translator, $perms);
        $page_storage = add_submenu_page(DUP_PRO_Constants::PLUGIN_SLUG, DUP_PRO_U::__('Storage'), DUP_PRO_U::__('Storage'), $perms, DUP_PRO_Constants::$STORAGE_SUBMENU_SLUG, 'duplicator_pro_get_menu');

        $perms = 'manage_options';
        $perms = apply_filters($wpfront_caps_translator, $perms);
        $page_templates = add_submenu_page(DUP_PRO_Constants::PLUGIN_SLUG, DUP_PRO_U::__('Templates'), DUP_PRO_U::__('Templates'), $perms, DUP_PRO_Constants::$TEMPLATES_SUBMENU_SLUG, 'duplicator_pro_get_menu');
        
        $perms = 'manage_options';
        $perms = apply_filters($wpfront_caps_translator, $perms);
        $page_tools = add_submenu_page(DUP_PRO_Constants::PLUGIN_SLUG, DUP_PRO_U::__('Tools'), DUP_PRO_U::__('Tools'), $perms, DUP_PRO_Constants::$TOOLS_SUBMENU_SLUG, 'duplicator_pro_get_menu');

        $perms = 'manage_options';
        $perms = apply_filters($wpfront_caps_translator, $perms);
        $page_settings = add_submenu_page(DUP_PRO_Constants::PLUGIN_SLUG, DUP_PRO_U::__('Settings'), DUP_PRO_U::__('Settings'), $perms, DUP_PRO_Constants::$SETTINGS_SUBMENU_SLUG, 'duplicator_pro_get_menu');

        //Apply Scripts
        add_action('admin_print_scripts-' . $page_packages, 'duplicator_pro_scripts');
        add_action('admin_print_scripts-' . $page_schedules, 'duplicator_pro_scripts');
        add_action('admin_print_scripts-' . $page_storage, 'duplicator_pro_scripts');
        add_action('admin_print_scripts-' . $page_settings, 'duplicator_pro_scripts');
        add_action('admin_print_scripts-' . $page_templates, 'duplicator_pro_scripts');
        add_action('admin_print_scripts-' . $page_tools, 'duplicator_pro_scripts');

        //Apply Styles
        add_action('admin_print_styles-' . $page_packages, 'duplicator_pro_styles');
        add_action('admin_print_styles-' . $page_schedules, 'duplicator_pro_styles');
        add_action('admin_print_styles-' . $page_storage, 'duplicator_pro_styles');
        add_action('admin_print_styles-' . $page_settings, 'duplicator_pro_styles');
        add_action('admin_print_styles-' . $page_templates, 'duplicator_pro_styles');
        add_action('admin_print_styles-' . $page_tools, 'duplicator_pro_styles');
    }

    /**
     *  DUPLICATOR_PRO_SCRIPTS
     *  Loads the required javascript libs only for this plugin  */
    function duplicator_pro_scripts()
    {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-progressbar');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('parsley');
		wp_enqueue_script('dup-pro-jquery-qtip');
    }

    /**
     *  DUPLICATOR_PRO_STYLES
     *  Loads the required css links only for this plugin  */
    function duplicator_pro_styles()
    {
        wp_enqueue_style('dup-pro-jquery-ui');
        wp_enqueue_style('dup-pro-font-awesome');
        wp_enqueue_style('dup-pro-parsley');
		wp_enqueue_style('dup-pro-plugin-style');
		wp_enqueue_style('dup-pro-jquery-qtip');
    }

    /**
     *  DUPLICATOR_PRO_MANAGE_LINK
     *  Adds the manage link in the plugins list */
    function duplicator_pro_manage_link($links, $file)
    {
        static $this_plugin;

        if (!$this_plugin)
        {
            $this_plugin = plugin_basename(__FILE__);
        }

        if ($file == $this_plugin)
        {
            $url = DUP_PRO_U::menu_page_url(DUP_PRO_Constants::PLUGIN_SLUG, false);

            $settings_link = "<a href='$url'>" . DUP_PRO_U::__('Manage') . '</a>';
            array_unshift($links, $settings_link);
        }
        return $links;
    }

}
?>
