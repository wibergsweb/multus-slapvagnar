<?php
//Prevent directly browsing to the file
if (function_exists('plugin_dir_url'))
{
    define('DUPLICATOR_PRO_VERSION', '2.0.10');
    define('DUPLICATOR_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
    define('DUPLICATOR_PRO_SITE_URL', get_site_url());
	define('DUPLICATOR_PRO_IMG_URL', DUPLICATOR_PRO_PLUGIN_URL . '/assets/img');

    /* Paths should ALWAYS read "/"
      uni: /home/path/file.txt
      win:  D:/home/path/file.txt
      SSDIR = SnapShot Directory */
    if (!defined('ABSPATH'))
    {
        define('ABSPATH', dirname(__FILE__));
    }

    //PATH CONSTANTS
    define("LEGACY_DUPLICATOR_SSDIR_NAME",     'wp-snapshots');
    define('LEGACY_DUPLICATOR_PLUGIN_PATH',    str_replace("\\", "/", plugin_dir_path(__FILE__)));
    define('LEGACY_DUPLICATOR_WPROOTPATH',     str_replace("\\", "/", ABSPATH));
    define("LEGACY_DUPLICATOR_SSDIR_PATH",     str_replace("\\", "/", LEGACY_DUPLICATOR_WPROOTPATH . LEGACY_DUPLICATOR_SSDIR_NAME));
    
    define("DUPLICATOR_PRO_SSDIR_NAME", 'backups-dup-pro');
    define('DUPLICATOR_PRO_PLUGIN_PATH', str_replace("\\", "/", plugin_dir_path(__FILE__)));
    define('DUPLICATOR_PRO_WPROOTPATH', str_replace("\\", "/", ABSPATH));
    define("DUPLICATOR_PRO_SSDIR_PATH", str_replace("\\", "/", WP_CONTENT_DIR . '/' . DUPLICATOR_PRO_SSDIR_NAME));
    define("DUPLICATOR_PRO_SSDIR_PATH_TMP", DUPLICATOR_PRO_SSDIR_PATH . '/tmp');
    define("DUPLICATOR_PRO_SSDIR_URL", content_url() . "/" . DUPLICATOR_PRO_SSDIR_NAME);
    define("DUPLICATOR_PRO_INSTALL_PHP", 'installer.php');
    //define("DUPLICATOR_PRO_INSTALL_BAK", 'installer-backup.php');
    define("DUPLICATOR_PRO_INSTALL_SQL", 'installer-data.sql');
    define("DUPLICATOR_PRO_INSTALL_LOG", 'installer-log.txt');
    define("DUPLICATOR_PRO_DUMP_PATH", DUPLICATOR_PRO_SSDIR_PATH . '/dump');
    
    //RESTRAINT CONSTANTS
    define("DUPLICATOR_PRO_PHP_MAX_MEMORY", '5000M');
    define("DUPLICATOR_PRO_DB_MAX_TIME", 5000);
    define("DUPLICATOR_PRO_DB_EOF_MARKER", 'DUPLICATOR_PRO_MYSQLDUMP_EOF');
    define("DUPLICATOR_PRO_SCAN_SITE", 500000000); //500MB
    define("DUPLICATOR_PRO_SCAN_WARNFILESIZE", 6291456); //6MB
    define("DUPLICATOR_PRO_SCAN_CACHESIZE", 524288); //512K
    define("DUPLICATOR_PRO_SCAN_DBSIZE", 104857600); //100MB
    define("DUPLICATOR_PRO_SCAN_DBROWS", 250000);
    define("DUPLICATOR_PRO_SCAN_TIMEOUT", 25);   //Seconds
    define("DUPLICATOR_PRO_SCAN_MIN_WP", "3.7.0");
    $GLOBALS['DUPLICATOR_PRO_SERVER_LIST'] = array('Apache', 'LiteSpeed', 'Nginx', 'Lighttpd', 'IIS', 'WebServerX', 'uWSGI');
    $GLOBALS['DUPLICATOR_PRO_OPTS_DELETE'] = array('duplicator_pro_ui_view_state', 'duplicator_pro_package_active', 'duplicator_pro_settings');    
}
else
{
    error_reporting(0);
    $port = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off") ? "https://" : "http://";
    $url = $port . $_SERVER["HTTP_HOST"];
    header("HTTP/1.1 404 Not Found", true, 404);
    header("Status: 404 Not Found");
    exit();
}
?>
