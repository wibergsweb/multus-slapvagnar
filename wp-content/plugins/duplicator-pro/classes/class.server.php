<?php
if ( ! defined( 'DUPLICATOR_PRO_VERSION' ) ) exit; // Exit if accessed directly

require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/entities/class.global.entity.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/utilities/class.u.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/utilities/class.utility.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/utilities/class.io.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/entities/class.storage.entity.php');

/**
 * Class used to get server statis
 * @package Dupicator\classes
 */
class DUP_PRO_Server 
{
	/** 
	* Gets the system requirements which must pass to buld a package
	* @return array   An array of requirements
	*/
	public static function get_requirments() 
	{
		global $wpdb;
        $global = DUP_PRO_Global_Entity::get_instance();
		$dup_tests = array();
		
		//PHP SUPPORT
		$safe_ini = strtolower(ini_get('safe_mode'));
		$dup_tests['PHP']['SAFE_MODE'] = $safe_ini  != 'on' || $safe_ini != 'yes' || $safe_ini != 'true' || ini_get("safe_mode") != 1 ? 'Pass' : 'Fail';
		$dup_tests['PHP']['VERSION'] = version_compare(phpversion(), '5.2.4') >= 0 ? 'Pass' : 'Fail';
                
		if($global->archive_build_mode == DUP_PRO_Archive_Build_Mode::ZipArchive)
		{
			$dup_tests['PHP']['ZIP']	 = class_exists('ZipArchive')				? 'Pass' : 'Fail';
		}
                
		$dup_tests['PHP']['FUNC_1']  = function_exists("file_get_contents")		? 'Pass' : 'Fail';
		$dup_tests['PHP']['FUNC_2']  = function_exists("file_put_contents")		? 'Pass' : 'Fail';
		$dup_tests['PHP']['FUNC_3']  = function_exists("mb_strlen")				? 'Pass' : 'Fail';
		$dup_tests['PHP']['ALL']	 = ! in_array('Fail', $dup_tests['PHP'])	? 'Pass' : 'Fail';		
		
		//PERMISSIONS
		$handle_test = @opendir(DUPLICATOR_PRO_WPROOTPATH);		
        // Removing is_writable on root - 
		//$dup_tests['IO']['WPROOT']	= is_writeable(DUPLICATOR_PRO_WPROOTPATH) && $handle_test ? 'Pass' : 'Fail';
        $dup_tests['IO']['WPROOT']	= $handle_test ? 'Pass' : 'Fail';
		$dup_tests['IO']['SSDIR']	= is_writeable(DUPLICATOR_PRO_SSDIR_PATH)		? 'Pass' : 'Fail';
		$dup_tests['IO']['SSTMP']	= is_writeable(DUPLICATOR_PRO_SSDIR_PATH_TMP)	? 'Pass' : 'Fail';
		$dup_tests['IO']['ALL']		= ! in_array('Fail', $dup_tests['IO'])		? 'Pass' : 'Fail'; 
		@closedir($handle_test);
		
		//SERVER SUPPORT
		$dup_tests['SRV']['MYSQL_VER']	= version_compare($wpdb->db_version(), '5.0', '>=')	? 'Pass' : 'Fail'; 
		$dup_tests['SRV']['ALL']		= ! in_array('Fail', $dup_tests['SRV'])				? 'Pass' : 'Fail'; 
		
		//RESERVED FILES
		$dup_tests['RES']['INSTALL'] = !(self::install_files_found()) ? 'Pass' : 'Fail';
		$dup_tests['Success'] = $dup_tests['PHP']['ALL']  == 'Pass' && $dup_tests['IO']['ALL'] == 'Pass' &&
								$dup_tests['SRV']['ALL']  == 'Pass' && $dup_tests['RES']['INSTALL'] == 'Pass';
		
		return $dup_tests;
	}		
	
	/** 
	* Gets the system checks which are not required
	* @return array   An array of system checks
	*/
	public static function get_checks($package) 
    {
        $global = DUP_PRO_Global_Entity::get_instance();
		$checks = array();
		
		//WEB SERVER 
		$web_test1 = false;
		foreach ($GLOBALS['DUPLICATOR_PRO_SERVER_LIST'] as $value) {
			if (stristr($_SERVER['SERVER_SOFTWARE'], $value)) {
				$web_test1 = true;
				break;
			}
		}
		$checks['SRV']['WEB']['model'] = $web_test1;
		$checks['SRV']['WEB']['ALL']   = ($web_test1) ? 'Good' : 'Warn';
        
		//PHP SETTINGS
		$php_test1 = ini_get("open_basedir");
		$php_test1 = empty($php_test1) ? true : false;
		$php_test2 = ini_get("max_execution_time");
		$php_test2 = ($php_test2 > DUPLICATOR_PRO_SCAN_TIMEOUT)  || (strcmp($php_test2 , 'Off') == 0 || $php_test2  == 0) ? true : false;                
		$php_test3 = true;
		if($package->contains_storage_type(DUP_PRO_Storage_Types::Dropbox))
		{
			$php_test3 = function_exists('openssl_csr_new');                          
		}
		$php_test4	= function_exists('mysqli_connect');        
        $php_test5 = DUP_PRO_U::is_url_fopen_enabled();
        $php_test6 = DUP_PRO_U::is_curl_available();
        
		$checks['SRV']['PHP']['openbase'] = $php_test1;
		$checks['SRV']['PHP']['maxtime']  = $php_test2;
		$checks['SRV']['PHP']['openssl']  = $php_test3;
		$checks['SRV']['PHP']['mysqli']   = $php_test4;
                
        $checks['SRV']['PHP']['allowurlfopen']   = $php_test5;
        $checks['SRV']['PHP']['curlavailable']   = $php_test6;
        
        if($package->contains_storage_type(DUP_PRO_Storage_Types::Dropbox))
        {
            // TODO: User shouldn't be allowed to start a scan unless transfer is curl or fopen - logic should prevent it so not accounting for it here.
            /* TODO: Technically we should prevent them from even getting here is the option isn't available - move out of php warning into a requirement */
            $dropbox_transfer_test = true;
            if($global->dropbox_transfer_mode == DUP_PRO_Dropbox_Transfer_Mode::cURL)
            {
                $dropbox_transfer_test = $php_test6;
            }
            else if($global->dropbox_transfer_mode == DUP_PRO_Dropbox_Transfer_Mode::FOpen_URL)
            {
                $dropbox_transfer_test = $php_test5;
            }
            
            $checks['SRV']['PHP']['ALL'] = ($php_test1 && $php_test2 && $php_test3 && $php_test4 && $dropbox_transfer_test) ? 'Good' : 'Warn';    
        }   
        else
        {
            $checks['SRV']['PHP']['ALL'] = ($php_test1 && $php_test2 && $php_test4) ? 'Good' : 'Warn';    
        }
                
		//WORDPRESS SETTINGS
		global $wp_version;
		$wp_test1 = version_compare($wp_version,  DUPLICATOR_PRO_SCAN_MIN_WP) >= 0 ? true : false;
		
		//Core Files
		$files = array();
		$files['wp-config.php'] = file_exists(DUP_PRO_Util::SafePath(DUPLICATOR_PRO_WPROOTPATH .  '/wp-config.php'));
		$wp_test2 = $files['wp-config.php'];
		
		//Cache
		$Package = ($package == null) ? DUP_PRO_Package::get_temporary_package() : $package;
		$cache_path = DUP_PRO_Util::SafePath(WP_CONTENT_DIR) .  '/cache';
		$dirEmpty	= DUP_PRO_IO::is_dir_empty($cache_path);
		$dirSize	= DUP_PRO_IO::get_dir_size($cache_path); 
		$cach_filtered = in_array($cache_path, explode(';', $Package->Archive->FilterDirs));
		$wp_test3 = ($cach_filtered || $dirEmpty  || $dirSize < DUPLICATOR_PRO_SCAN_CACHESIZE ) ? true : false;
		
		$checks['SRV']['WP']['version'] = $wp_test1;
		$checks['SRV']['WP']['core']	= $wp_test2;
		$checks['SRV']['WP']['cache']	= $wp_test3;
		$checks['SRV']['WP']['ALL']		= $wp_test1 && $wp_test2 && $wp_test3 ? 'Good' : 'Warn';

		return $checks;
	}
	
	/** 
	* Check to see if duplicator installer files are present
	* @return bool   True if any reserved files are found
	*/
	public static function install_files_found() 
	{
		$global = DUP_PRO_Global_Entity::get_instance();
		
		// Just deleting the installer bak and database.sql since failing shell exec will leave that around and cause problems		
		if($global != null)
		{
			DUP_PRO_IO::delete_file(DUPLICATOR_PRO_WPROOTPATH . $global->get_installer_backup_filename());	
		}
		
		DUP_PRO_IO::delete_file(DUPLICATOR_PRO_WPROOTPATH .  'database.sql');
		$files = self::get_installer_files();

		foreach($files as $file => $path) 
		{
			if (file_exists($path))
				return true;
		}
		return false;
	}
	
	/** 
	* Returns an array with stats about the orphaned files
	* @return array   The full path of the orphaned file
	*/
	public static function get_orphaned_package_files() 
	{
		$global = DUP_PRO_Global_Entity::get_instance();

		$packages = DUP_PRO_Package::get_all();
		$installer_base_name = $global->installer_base_name;
		$filepaths = glob(DUPLICATOR_PRO_SSDIR_PATH . "/" . "*_{archive,database,scan,$installer_base_name}*", GLOB_BRACE);
		
		$orphans = array();
		
		if($filepaths !== false)
		{
			$log_filepaths = glob(DUPLICATOR_PRO_SSDIR_PATH . "/" . '*.log');
			$filepaths = array_merge($filepaths, $log_filepaths);
			
			foreach($filepaths as $filepath) 
			{
				$is_orphan = true;
				$filename = basename($filepath);
				// If it doesn't start with any of the hashes it's an orphan
				foreach($packages as $package)
				{				
					if(DUP_PRO_U::starts_with($filename, $package->NameHash) || DUP_PRO_U::starts_with($filename, 'dup_pro'))
					{
						$is_orphan = false;
						break;
					}
				}

				if(count($packages) == 0)
				{
					$is_orphan = false;
				}

				if($is_orphan)
				{
					DUP_PRO_U::log("$filename is an orphan");
					// This is a bogus file
					array_push($orphans, $filepath);
				}	
			}
		}
		return $orphans;
	}
	
	/** 
	* Returns an array with stats about the orphaned files
	* @return array   The total count and file size of orphaned files
	*/
	public static function get_orphaned_package_info() 
	{
		$files = self::get_orphaned_package_files();
		$info = array();
		$info['size'] = 0;
		$info['count'] = 0;
		if (count($files)) {
			foreach($files as $path) 
			{
				$get_size = @filesize($path);
				if ($get_size > 0) {
					$info['size'] += $get_size;
					$info['count']++;
				}
			}
		}
		return $info;
	}
	
	/** 
	* Gets a list of all the installer files by name and full path
	* @return array [file_name, file_path]
	*/
	public static function get_installer_files() 
	{
		$global = DUP_PRO_Global_Entity::get_instance();
		
		if($global == null)
		{
			$installer_backup_filename = 'installer-backup.php';
		}
		else
		{
			$installer_backup_filename = $global->get_installer_backup_filename();
		}
		/* Files:
		 * installer.php, installer-backup.php, installer-data.sql, installer-log.txt, database.sql */
		return array(
			DUPLICATOR_PRO_INSTALL_PHP => DUPLICATOR_PRO_WPROOTPATH . $global->installer_base_name,  
			$installer_backup_filename => DUPLICATOR_PRO_WPROOTPATH . $installer_backup_filename, 
			DUPLICATOR_PRO_INSTALL_SQL => DUPLICATOR_PRO_WPROOTPATH . DUPLICATOR_PRO_INSTALL_SQL,
			DUPLICATOR_PRO_INSTALL_LOG => DUPLICATOR_PRO_WPROOTPATH . DUPLICATOR_PRO_INSTALL_LOG,
			'database.sql'			   => DUPLICATOR_PRO_WPROOTPATH .  'database.sql'
		);
	}
	
	/** 
	* Get the IP of a client machine
	* @return string   IP of the client machine
	*/
	public static function get_clientip() 
	{
		if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)){
            return  $_SERVER["HTTP_X_FORWARDED_FOR"];  
        }else if (array_key_exists('REMOTE_ADDR', $_SERVER)) { 
            return $_SERVER["REMOTE_ADDR"]; 
        }else if (array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
            return $_SERVER["HTTP_CLIENT_IP"]; 
        } 
        return '';
	}
	
	/** 
	* Get PHP memory useage 
	* @return string   Returns human readable memory useage.
	*/
	public static function get_php_memory($peak = false) 
	{
		if ($peak) {
			$result = 'Unable to read PHP peak memory usage';
			if (function_exists('memory_get_peak_usage')) {
				$result = DUP_PRO_Util::ByteSize(memory_get_peak_usage(true));
			} 
		} else {
			$result = 'Unable to read PHP memory usage';
			if (function_exists('memory_get_usage')) {
				$result = DUP_PRO_Util::ByteSize(memory_get_usage(true));
			} 
		}
        return $result;
	}
}
?>