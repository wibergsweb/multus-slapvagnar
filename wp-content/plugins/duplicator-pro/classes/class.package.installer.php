<?php
if (!defined('DUPLICATOR_PRO_VERSION'))
	exit; // Exit if accessed directly

require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/utilities/class.u.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/utilities/class.utility.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/utilities/class.shell.u.php');

class DUP_PRO_Installer
{
	//PUBLIC
	public $File;
	public $Size = 0;
	public $OptsDBHost;
	public $OptsDBName;
	public $OptsDBUser;
	public $OptsSSLAdmin;
	public $OptsSSLLogin;
	public $OptsCacheWP;
	public $OptsCachePath;
	public $OptsURLNew;
	//PROTECTED
	protected $Package;

	//CONSTRUCTOR
	function __construct($package)
	{
		$this->Package = $package;
	}

	public function get_safe_filepath()
	{
		return DUP_PRO_Util::SafePath(DUPLICATOR_PRO_SSDIR_PATH . "/{$this->File}");
	}

	public function get_url()
	{
		return DUPLICATOR_PRO_SSDIR_URL . "/{$this->File}";
	}

	public function Build($package, $build_progress)
	{
		/* @var $package DUP_PRO_Package */
		DUP_PRO_U::log("building installer");
		$this->Package = $package;

		DUP_PRO_Log::Info("\n********************************************************************************");
		DUP_PRO_Log::Info("MAKE INSTALLER:");
		DUP_PRO_Log::Info("********************************************************************************");
		DUP_PRO_Log::Info("Build Start");

		$template_uniqid = uniqid('') . '_' . time();
		$template_path = DUP_PRO_Util::SafePath(DUPLICATOR_PRO_SSDIR_PATH_TMP . "/installer.template_{$template_uniqid}.php");
		$main_path = DUP_PRO_Util::SafePath(DUPLICATOR_PRO_PLUGIN_PATH . 'installer/build/main.installer.php');
		@chmod($template_path, 0777);
		@chmod($main_path, 0777);

		@touch($template_path);
		$main_data = file_get_contents("{$main_path}");
		$template_result = file_put_contents($template_path, $main_data);

		if ($main_data === false)
		{
			$err_info = "Possible permission issues with file_get_contents. Please validate that PHP has read/write access.\nMain Installer: '{$main_path}";
			DUP_PRO_Log::Error("Install builder failed to generate files.", "{$err_info}");
			$build_progress->failed = true;
			return;
		}
		
		if ($template_result == false)
		{
			$err_info = "Possible permission issues with file_put_contents. Please validate that PHP has read/write access.\nTemplate Installer: '{$template_path}'";
			DUP_PRO_Log::Error("Install builder failed to generate files.", "{$err_info}");
			$build_progress->failed = true;
			return;
		}

		$embeded_files = array(
			"assets/inc.libs.css.php" => "@@INC.LIBS.CSS.PHP@@",
			"assets/inc.css.php" => "@@INC.CSS.PHP@@",
			"assets/inc.libs.js.php" => "@@INC.LIBS.JS.PHP@@",
			"assets/inc.js.php" => "@@INC.JS.PHP@@",
			"classes/class.logging.php" => "@@CLASS.LOGGING.PHP@@",
			"classes/class.utils.php" => "@@CLASS.UTILS.PHP@@",
			"classes/class.config.php" => "@@CLASS.CONFIG.PHP@@",
			"classes/class.serializer.php" => "@@CLASS.SERIALIZER.PHP@@",
			"ajax.step1.php" => "@@AJAX.STEP1.PHP@@",
			"ajax.step2.php" => "@@AJAX.STEP2.PHP@@",
			"view.step1.php" => "@@VIEW.STEP1.PHP@@",
			"view.step2.php" => "@@VIEW.STEP2.PHP@@",
			"view.step3.php" => "@@VIEW.STEP3.PHP@@",
			"view.help.php" => "@@VIEW.HELP.PHP@@",);

		foreach ($embeded_files as $name => $token)
		{
			$file_path = DUPLICATOR_PRO_PLUGIN_PATH . "installer/build/{$name}";
			@chmod($file_path, 0777);

			$search_data = @file_get_contents($template_path);
			$insert_data = @file_get_contents($file_path);
			file_put_contents($template_path, str_replace("${token}", "{$insert_data}", $search_data));

			if ($search_data === false || $insert_data == false)
			{
				DUP_PRO_Log::Error("Installer generation failed at {$token}.");
				$build_progress->failed = true;
				return;
			}

			@chmod($file_path, 0644);
		}

		@chmod($template_path, 0644);
		@chmod($main_path, 0644);

		DUP_PRO_Log::Info("Build Finished");

		if ($this->createFromTemplate($template_path) == false)
		{
			$build_progress->failed = true;
			return;
		}

		$storePath = "{$this->Package->StorePath}/{$this->File}";
		$this->Size = @filesize($storePath);

		if ($this->add_extra_files($package) == false)
		{
			$build_progress->failed = true;
			return;
		}

		$build_progress->installer_built = true;
	}

	/**
	 *  createZipBackup
	 *  Puts an installer zip file in the archive for backup purposes.
	 */
	private function add_extra_files($package)
	{
		$global = DUP_PRO_Global_Entity::get_instance();
		
		$success = false;

		$installer_filepath = DUP_PRO_Util::SafePath(DUPLICATOR_PRO_SSDIR_PATH_TMP) . "/{$this->Package->NameHash}_{$global->installer_base_name}";
		$sql_filepath = DUP_PRO_Util::SafePath("{$this->Package->StorePath}/{$this->Package->Database->File}");
		$zip_filepath = DUP_PRO_Util::SafePath("{$this->Package->StorePath}/{$this->Package->Archive->File}");
		
		if(file_exists($installer_filepath) == false)
		{
			DUP_PRO_Log::Error("Installer $installer_filepath not present", '', false);
		}
		
		if(file_exists($sql_filepath) == false)
		{
			DUP_PRO_Log::Error("Database SQL file $sql_filepath not present", '', false);
		}
			
		if($package->Archive->file_count != 2)
		{
			DUP_PRO_U::log("Doing archive file check");
			// Only way it's 2 is if the root was part of the filter in which case the archive won't be there
			if(file_exists($zip_filepath) == false)
			{
				DUP_PRO_Log::Error("Zip archive $zip_filepath not present. RECOMMENDATION: In package settings, change Archive Engine to 'ZipArchive' and retry build.", '', false);
			}
		}
			
		// Only add this if not in shell exec mode since shell exec adds everything in one shot
		if ($package->build_progress->current_build_mode == DUP_PRO_Archive_Build_Mode::ZipArchive)
		{					
			DUP_PRO_U::log("add backup current build mode = " . $package->build_progress->current_build_mode);

			$zipArchive = new ZipArchive();
			if ($zipArchive->open($zip_filepath, ZIPARCHIVE::CREATE) === TRUE)
			{
				$installer_backup_filename = $global->get_installer_backup_filename();
				
				if($global->ziparchive_mode == DUP_PRO_ZipArchive_Mode::Enhanced)
				{
					$added_to_archive = $zipArchive->addFromString($installer_backup_filename, file_get_contents($installer_filepath));
				}
				else
				{
					$added_to_archive = $zipArchive->addFile($installer_filepath, $installer_backup_filename);
				}
				
				if ($added_to_archive)
				{
					DUP_PRO_Log::Info("Added to archive");
					DUP_PRO_U::log("Added to archive");
					
					$success = true;
				}
				else
				{
					DUP_PRO_Log::Info("Unable to add $installer_backup_filename to archive.", "Installer File Path [{$installer_filepath}]");
					DUP_PRO_U::log("Unable to add $installer_backup_filename to archive.");
				}

				$zipArchive->close();
				
				DUP_PRO_U::log("After ziparchive close when adding installer");
			}
		}
		else if ($package->build_progress->current_build_mode == DUP_PRO_Archive_Build_Mode::Shell_Exec)
		{
			$home_path = get_home_path();

			if (self::add_sql_file($sql_filepath))
			{
				if (self::add_installer_backup_file($this->Package))
				{
					/* @var $global DUP_PRO_Global_Entity */
					$global = DUP_PRO_Global_Entity::get_instance();
					$installer_backup_filename = $global->get_installer_backup_filename();
					$home_path = get_home_path();

					// RSR TODO: Add to zip archive
					$command = 'cd ' . escapeshellarg($home_path);
					$command .= ' && ' . escapeshellcmd(DUP_PRO_Util::get_zip_filepath()) . ' -rq ';
					$command .= escapeshellarg($zip_filepath) . ' ./';
					$command .= " -i database.sql " . $installer_backup_filename;

					DUP_PRO_U::log("Executing shellexeczip to add installer and database.sql: $command");

					$stderr = shell_exec($command);
										
					if ($stderr == '')
					{
						if(DUP_PRO_Util::get_exe_filepath('zipinfo') != NULL)
						{
							
							// Verify they got in there		
							$extra_count_string = "zipinfo -1 '$zip_filepath' database.sql $installer_backup_filename | wc -l";

							$extra_count = DUP_PRO_SHELL_U::execute_and_get_value($extra_count_string, 1);

							if(is_numeric($extra_count))
							{	
								// Accounting for the sql and installer back files
								if($extra_count == 2)
								{
									DUP_PRO_U::log("Database.sql and $installer_backup_filename confirmed to be in the archive");	
									$success = true;
								}
								else
								{
									DUP_PRO_Log::Error("Tried to verify database.sql and $installer_backup_filename and one or both were missing. Count = $extra_count", '', false);
								}
							}
							else
							{                  
								DUP_PRO_U::log("Executed extra count string of $extra_count_string");
								DUP_PRO_Log::Error("Error retrieving extra count in shell zip " . $extra_count, '', false);
							}   
						}
						else
						{
							DUP_PRO_U::log("Zipinfo doesn't exist so not doing the extra file check");
							$success = true;
						}
					}
					else
					{
						DUP_PRO_Log::Error("Unable to add installer backup and database.sql to archive $stderr.", '', false);
					}
				}
				else
				{
					DUP_PRO_Log::Error("Problem copying installer backup file when creating package", '', false);
				}
			}
			else
			{
				DUP_PRO_Log::Error("Problem copying $sql_filepath file when creating package", '', false);
			}
		}
		
		$package->Archive->Size = @filesize($zip_filepath);

		return $success;
	}

	// Returns true if correctly added installer backup to root false if not
	private static function add_installer_backup_file($package)
	{
		$global = DUP_PRO_Global_Entity::get_instance();
		$installer_path = DUP_PRO_Util::SafePath(DUPLICATOR_PRO_SSDIR_PATH_TMP) . "/{$package->NameHash}_{$global->installer_base_name}";

		$home_path = get_home_path();

		// Add installer to root directory
		$archive_installerbak_filepath = $home_path . $global->get_installer_backup_filename();

		return DUP_PRO_U::copy_with_verify($installer_path, $archive_installerbak_filepath);
	}

	// Returns false if correctly added installer backup to root false if not
	private static function add_sql_file($source_sql_filepath)
	{
		$home_path = get_home_path();

		$archive_sql_filepath = $home_path . 'database.sql';

		return DUP_PRO_U::copy_with_verify($source_sql_filepath, $archive_sql_filepath);
	}

	/**
	 *  createFromTemplate
	 *  Generates the final installer file from the template file
	 */
	private function createFromTemplate($template)
	{
		global $wpdb;
		$global = DUP_PRO_Global_Entity::get_instance();
		
		DUP_PRO_Log::Info("Prepping for use");
		$installer = DUP_PRO_Util::SafePath(DUPLICATOR_PRO_SSDIR_PATH_TMP) . "/{$this->Package->NameHash}_{$global->installer_base_name}";

		//Option values to delete at install time
		$deleteOpts = $GLOBALS['DUPLICATOR_PRO_OPTS_DELETE'];

		$replace_items = Array(
			"fwrite_url_old" => get_option('siteurl'),
			"fwrite_installer_base_name" => $global->installer_base_name,
			"fwrite_package_name" => "{$this->Package->NameHash}_archive.zip",
			"fwrite_package_notes" => $this->Package->Notes,
			"fwrite_secure_name" => $this->Package->NameHash,
			"fwrite_url_new" => $this->Package->Installer->OptsURLNew,
			"fwrite_dbhost" => $this->Package->Installer->OptsDBHost,
			"fwrite_dbname" => $this->Package->Installer->OptsDBName,
			"fwrite_dbuser" => $this->Package->Installer->OptsDBUser,
			"fwrite_dbpass" => '',
			"fwrite_ssl_admin" => $this->Package->Installer->OptsSSLAdmin,
			"fwrite_ssl_login" => $this->Package->Installer->OptsSSLLogin,
			"fwrite_cache_wp" => $this->Package->Installer->OptsCacheWP,
			"fwrite_cache_path" => $this->Package->Installer->OptsCachePath,
			"fwrite_wp_tableprefix" => $wpdb->prefix,
			"fwrite_opts_delete" => json_encode($deleteOpts),
			"fwrite_blogname" => esc_html(get_option('blogname')),
			"fwrite_wproot" => DUPLICATOR_PRO_WPROOTPATH,
			'mu_mode' => DUP_PRO_U::get_mu_mode(),
			"fwrite_duplicator_pro_version" => DUPLICATOR_PRO_VERSION);

		if (file_exists($template) && is_readable($template))
		{
			$err_msg = "ERROR: Unable to read/write installer. \nERROR INFO: Check permission/owner on file and parent folder.\nInstaller File = <{$installer}>";
			$install_str = $this->parseTemplate($template, $replace_items);
			(empty($install_str)) ? DUP_PRO_Log::Error("{$err_msg}", "DUP_PRO_Installer::createFromTemplate => file-empty-read") : DUP_PRO_Log::Info("Template parsed with new data");

			//INSTALLER FILE
			$fp = (!file_exists($installer)) ? fopen($installer, 'x+') : fopen($installer, 'w');
			if (!$fp || !fwrite($fp, $install_str, strlen($install_str)))
			{
				DUP_PRO_Log::Error("{$err_msg}", "DUP_PRO_Installer::createFromTemplate => file-write-error");
				return false;
			}

			@fclose($fp);
		}
		else
		{
			DUP_PRO_Log::Error("Installer Template missing or unreadable.", "Template [{$template}]");
			return false;
		}
		@unlink($template);
		DUP_PRO_Log::Info("Complete [{$installer}]");
		return true;
	}

	/**
	 *  parseTemplate
	 *  Tokenize a file based on an array key 
	 *
	 *  @param string $filename		The filename to tokenize
	 *  @param array  $data			The array of key value items to tokenize
	 */
	private function parseTemplate($filename, $data)
	{
		$q = file_get_contents($filename);
		foreach ($data as $key => $value)
		{
			//NOTE: Use var_export as it's probably best and most "thorough" way to
			//make sure the values are set correctly in the template.  But in the template,
			//need to make things properly formatted so that when real syntax errors
			//exist they are easy to spot.  So the values will be surrounded by quotes

			$find = array("'%{$key}%'", "\"%{$key}%\"");
			$q = str_replace($find, var_export($value, true), $q);
			//now, account for places that do not surround with quotes...  these
			//places do NOT need to use var_export as they are not inside strings
			$q = str_replace('%' . $key . '%', $value, $q);
		}
		return $q;
	}

}

?>
