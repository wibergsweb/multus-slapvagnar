<?php
if (!defined('DUPLICATOR_PRO_VERSION'))
    exit; // Exit if accessed directly

require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/class.package.archive.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/entities/class.global.entity.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/entities/class.storage.entity.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/utilities/class.shell.u.php');

if (!class_exists('DUP_PRO_ShellZip'))
{
    /**
     *  DUP_PRO_ZIP
     *  Creates a zip file using the built in PHP ZipArchive class
     */
    class DUP_PRO_ShellZip extends DUP_PRO_Archive
    {
        /**
         *  CREATE
         *  Creates the zip file and adds the SQL file to the archive
         */
        static public function Create(DUP_PRO_Archive $archive, $build_progress)
        {
            $timed_out = false;

            try
            {
                $archive->Package->Status = DUP_PRO_PackageStatus::ARCSTART;
                $archive->Package->update();
                
                $archive->Package->safe_tmp_cleanup(true);

                /* @var $global DUP_PRO_Global_Entity */
                $global = DUP_PRO_Global_Entity::get_instance();

                /* @var $build_progress DUP_PRO_Build_Progress */
                $timerAllStart = DUP_PRO_Util::GetMicrotime();

                $compressDir = rtrim(DUP_PRO_Util::SafePath($archive->PackDir), '/');
                $zipPath = DUP_PRO_Util::SafePath("{$archive->Package->StorePath}/{$archive->File}");                
                $sql_filepath = DUP_PRO_Util::SafePath("{$archive->Package->StorePath}/{$archive->Package->Database->File}");                

                $filterDirs = empty($archive->FilterDirs) ? 'not set' : $archive->FilterDirs;
                $filterExts = empty($archive->FilterExts) ? 'not set' : $archive->FilterExts;
                $filterFiles = empty($archive->FilterFiles) ? 'not set' : $archive->FilterFiles;
                $filterOn = ($archive->FilterOn) ? 'ON' : 'OFF';

                //LOAD SCAN REPORT
                $json = file_get_contents(DUPLICATOR_PRO_SSDIR_PATH_TMP . "/{$archive->Package->NameHash}_scan.json");
                $scanReport = json_decode($json);

                DUP_PRO_Log::Info("\n********************************************************************************");
                DUP_PRO_Log::Info("ARCHIVE  Type=ZIP Mode=Shell");
                DUP_PRO_Log::Info("********************************************************************************");
                DUP_PRO_Log::Info("ARCHIVE DIR:  " . $compressDir);
                DUP_PRO_Log::Info("ARCHIVE FILE: " . basename($zipPath));
                DUP_PRO_Log::Info("FILTERS: *{$filterOn}*");
                DUP_PRO_Log::Info("DIRS:  {$filterDirs}");
                DUP_PRO_Log::Info("EXTS:  {$filterExts}");
                DUP_PRO_Log::Info("FILES:  {$filterFiles}");

                DUP_PRO_Log::Info("----------------------------------------");
                DUP_PRO_Log::Info("COMPRESSING");
                DUP_PRO_Log::Info("SIZE:\t" . $scanReport->ARC->Size);
                DUP_PRO_Log::Info("STATS:\tDirs " . $scanReport->ARC->DirCount . " | Files " . $scanReport->ARC->FileCount . " | Total " . $scanReport->ARC->FullCount);
                               
        
                $contains_root = false;
                $exclude_string = '';

                if ($archive->FilterOn)
                {
                    $filterDirs = explode(';', $archive->FilterDirs);
                    $filterExts = explode(';', $archive->FilterExts);
                    $filterFiles = explode(';', $archive->FilterFiles);

                    foreach ($filterDirs as $filterDir)
                    {
                        if(trim($filterDir) != '')
                        {
                            $relative_filter_dir = DUP_PRO_U::get_relative_path($compressDir, $filterDir);

                            DUP_PRO_U::log("Adding relative filter dir $relative_filter_dir for $filterDir relative to $compressDir");
                           
                            if(trim($relative_filter_dir) == '')
                            {
                                $contains_root = true;
                                break;
                            }
                            else
                            {                                                            
                                $exclude_string .= "$relative_filter_dir**\* ";
                                $exclude_string .= "$relative_filter_dir ";
                            }
                        }
                    }

                    foreach ($filterExts as $filterExt)
                    {
                        $exclude_string .= "\*.$filterExt ";
                    }
                    
                    foreach ($filterFiles as $filterFile)
                    {
						if(trim($filterFile) != '')
						{
							$relative_filter_file = DUP_PRO_U::get_relative_path($compressDir, trim($filterFile));

							DUP_PRO_U::log("Full file=$filterFile relative=$relative_filter_file compressDir=$compressDir");

							$exclude_string .= "\"$relative_filter_file\" ";
						}
                    }
                }
				
				
								
                if($contains_root == false)
                {					
                    // Only attempt to zip things up if root isn't in there since stderr indicates when it cant do anything 
					
					$storages = DUP_PRO_Storage_Entity::get_all();
					/* @var $storage DUP_PRO_Storage_Entity */
					foreach($storages as $storage)
					{
						if(($storage->storage_type == DUP_PRO_Storage_Types::Local)  && $storage->local_filter_protection && ($storage->id != DUP_PRO_Virtual_Storage_IDs::Default_Local))
						{
							//$this->FilterInfo->Dirs->Core[] = DUP_PRO_Util::SafePath($storage->local_storage_folder);
							$storage_path = DUP_PRO_Util::SafePath($storage->local_storage_folder);
							$storage_path = DUP_PRO_U::get_relative_path($compressDir, $storage_path);
							$exclude_string .= "$storage_path**\* ";							
						}
					}
				
                    $relative_backup_dir = DUP_PRO_U::get_relative_path($compressDir, DUPLICATOR_PRO_SSDIR_PATH);

                    $exclude_string .= "$relative_backup_dir**\* ";
                    
                    $relative_duplicator_free_backup_dir = DUP_PRO_U::get_relative_path($compressDir, LEGACY_DUPLICATOR_SSDIR_PATH);
                    $exclude_string .= "$relative_duplicator_free_backup_dir**\* ";

                    $command = 'cd ' . escapeshellarg($compressDir);
                    $command .= ' && ' . escapeshellcmd(DUP_PRO_Util::get_zip_filepath()) . ' -rq ';
                    $command .= escapeshellarg($zipPath) . ' ./';
                    $command .= " -x $exclude_string 2>&1";

                    DUP_PRO_U::log("Executing shellzip command $command");

                    $stderr = shell_exec($command);

                    DUP_PRO_U::log("After shellzip command");
                    
                    if ($stderr != NULL)
                    {
                        DUP_PRO_Log::Error("Error executing shell exec zip: $stderr", '', false);
                        $build_progress->failed = true;
                        return true;
                    }
                    else
                    {
                        DUP_PRO_U::log("Stderr is null");
                    }															
                
					if(DUP_PRO_Util::get_exe_filepath('zipinfo') != NULL)
					{
						DUP_PRO_U::log("zipinfo exists");
						$file_count_string = "zipinfo -t '$zipPath'";

						$file_count = DUP_PRO_SHELL_U::execute_and_get_value($file_count_string, 1);

						if(is_numeric($file_count))
						{
							// Accounting for the sql and installer back files
							$archive->file_count = (int)$file_count +2;    
						}
						else
						{                  
							DUP_PRO_U::log("executed file count string of $file_count_string");
							DUP_PRO_U::log("Error retrieving file count in shell zip " . $file_count);
							$archive->file_count = -2;
						}     
					}
					else
					{
						DUP_PRO_U::log("zipinfo doesnt exist");
						// The -1 and -2 should be constants since they signify different things
						$archive->file_count = -1;						
					}
                }
                else
                {
                    $archive->file_count = 2;	// Installer bak and database.sql
                }
                
                DUP_PRO_U::log("archive file count from shellzip is $archive->file_count");
                
                $build_progress->archive_built = true;
				//$build_progress->installer_built = true; //rsr moving back out to a separate step
                $build_progress->retries = 0;
                
                $archive->Package->update();
                    
                $timerAllEnd = DUP_PRO_Util::GetMicrotime();
                $timerAllSum = DUP_PRO_Util::ElapsedTime($timerAllEnd, $timerAllStart);

                $zipFileSize = @filesize($zipPath);

                DUP_PRO_Log::Info("COMPRESSED SIZE: " . DUP_PRO_Util::ByteSize($zipFileSize));
                DUP_PRO_Log::Info("ARCHIVE RUNTIME: {$timerAllSum}");
                DUP_PRO_Log::Info("MEMORY STACK: " . DUP_PRO_Server::get_php_memory());
            }
            catch (Exception $e)
            {   
                DUP_PRO_Log::Error("Runtime error in shell exec zip compression.", "Exception: {$e}");
            }

            return true;
        }         		

		//TODO: Remove??
//		// Returns true if correctly added installer backup to root false if not
//		private static function add_installer_backup_file($package)
//		{
//			$global = DUP_PRO_Global_Entity::get_instance();
//			$installer_path = DUP_PRO_Util::SafePath(DUPLICATOR_PRO_SSDIR_PATH_TMP) . "/{$package->NameHash}_{$global->installer_base_name}";
//			        
//            $home_path = get_home_path();
//            
//            // Add installer to root directory
//            $archive_installerbak_filepath = $home_path . DUPLICATOR_PRO_INSTALL_BAK;            
//			
//			return DUP_PRO_U::copy_with_verify($installer_path, $archive_installerbak_filepath);
//        }     					
//		
//		// Returns false if correctly added installer backup to root false if not
//        private static function add_sql_file($source_sql_filepath)
//        {                       
//            $home_path = get_home_path();
//            
//            $archive_sql_filepath = $home_path . 'database.sql';           
//			
//			return DUP_PRO_U::copy_with_verify($source_sql_filepath, $archive_sql_filepath);
//        }           
    }
}
?>
