<?php

if (!defined('DUPLICATOR_PRO_VERSION'))
    exit; // Exit if accessed directly

require_once ('class.package.archive.zip.php');
require_once ('class.package.archive.shellzip.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/utilities/class.io.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'lib/forceutf8/src/Encoding.php');



/**
 * Defines the scope from which a filter item was created/retreived from
 * @package DupicatorPro\classes
 */
class DUP_PRO_Archive_Filter_Scope_Base
{
	//All internal storage items that we decide to filter
	public $Core = array();
	
	//TODO: Enable with Settings UI
	//Global filter items added from settings
	public $Global = array();
	
	//Items when creating a package or template
	public $Instance = array();
	

}

/**
 * Defines the scope from which a filter item was created/retreived from
 * @package DupicatorPro\classes
 */
class DUP_PRO_Archive_Filter_Scope_Directory extends DUP_PRO_Archive_Filter_Scope_Base
{
	//Items that are not readable
	public $Warning = array();
	
	//Items that are not readable
	public $Unreadable = array();
	
}

/**
 * Defines the scope from which a filter item was created/retreived from
 * @package DupicatorPro\classes
 */
class DUP_PRO_Archive_Filter_Scope_File extends DUP_PRO_Archive_Filter_Scope_Directory
{
	//Items that are too large
	public $Size = array();        	
}

/**
 * Defines the filtered items that are pulled from there various scopes
 * @package DupicatorPro\classes
 */
class DUP_PRO_Archive_Filter_Info
{
	//Contains all folder filter info
	public $Dirs = array();
	
	//Contains all file filter info
	public $Files = array();
	
	//Contains all extensions filter info
	public $Exts = array();
	
	public $UDirCount  = 0;
	public $UFileCount = 0;
	public $UExtCount  = 0;
	
	public function __construct()
    {
		$this->Dirs  = new DUP_PRO_Archive_Filter_Scope_Directory();
		$this->Files = new DUP_PRO_Archive_Filter_Scope_File();
		$this->Exts  = new DUP_PRO_Archive_Filter_Scope_Base();
	}
}

/**
 * Manages all aspects of the archive process
 * @package DupicatorPro\classes
 */
class DUP_PRO_Archive
{
    //PUBLIC
	//Includes only the dirs set on the package
    public $FilterDirs;
    public $FilterExts;
    public $FilterFiles;
	public $FilterDirsAll = array();
	public $FilterExtsAll = array();
    public $FilterFilesAll = array();
    public $FilterOn;
    public $File;
    public $Format;
    public $PackDir;
    public $Size = 0;
    public $Dirs = array();
    public $Files = array();
    
	public $FilterInfo;
    public $file_count = -1;

    //PROTECTED
    protected $Package;

    public function __construct($package)
    {
        $this->Package = $package;
        $this->FilterOn = false;
		
		$this->FilterInfo = new DUP_PRO_Archive_Filter_Info();
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
        /* @var $build_progress DUP_PRO_Build_Progress */
        
        DUP_PRO_U::log("Building archive");
        try
        {
            $this->Package = $package;
            if (!isset($this->PackDir) && !is_dir($this->PackDir))
                throw new Exception("The 'PackDir' property must be a valid directory.");
            if (!isset($this->File))
                throw new Exception("A 'File' property must be set.");

            $completed = false;
            
            switch ($this->Format)
            {
                case 'TAR': break;
                case 'TAR-GZIP': break;
                default:
                    $this->Format = 'ZIP';

                    if($build_progress->current_build_mode == DUP_PRO_Archive_Build_Mode::Shell_Exec)
                    {
                        DUP_PRO_U::log('Doing shell exec zip');
                        $completed = DUP_PRO_ShellZip::Create($this, $build_progress);
                    }
                    else
                    {
                        if (class_exists('ZipArchive'))
                        {                            
                            $completed = DUP_PRO_Zip::Create($this, $build_progress);                            
                        }
                        else
                        {
                            //TODO:PCL Zip
                            DUP_PRO_U::log("Zip archive doesn't exist?");
                        }
                    }
                                        
                    $this->Package->Update();
                    break;
            }

            if($completed)
            {
                if($build_progress->failed)
                {
                    DUP_PRO_U::log_error("Error building archive");
                    $this->Package->set_status(DUP_PRO_PackageStatus::ERROR);
                }
                else
                {
                    $storePath = "{$this->Package->StorePath}/{$this->File}";
                //    $this->Size = @filesize($storePath);
                    DUP_PRO_U::log("filesize of zip = $this->Size");                    
                    $this->Package->set_status(DUP_PRO_PackageStatus::ARCDONE);
                    DUP_PRO_U::log("Done building archive");
                }
            }
            else
            {
                DUP_PRO_U::log("Archive chunk completed");
            }
        }
        catch (Exception $e)
        {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }        
    }

    /**
     *  STATS
     *  Create filters info and generate dirs and files array
     *  @returns array					An array of values for the directory stats
     *  @link http://msdn.microsoft.com/en-us/library/aa365247%28VS.85%29.aspx Windows filename restrictions
     */
    public function Stats()
    {
		$this->createFilterInfo();
        $this->getDirs();
        $this->getFiles();

        return $this;
    }
	
	private function createFilterInfo()
    {
        DUP_PRO_U::log_object('Filter files', $this->FilterFiles);
        
		//FILTER: INSTANCE ITEMS
		//Add the items generated at create time
		if ($this->FilterOn)
        {
			$this->FilterInfo->Dirs->Instance = array_map('DUP_PRO_Util::SafePath', explode(";", $this->FilterDirs, -1));           
			$this->FilterInfo->Exts->Instance = explode(";", $this->FilterExts, -1);
            $this->FilterInfo->Files->Instance = array_map('DUP_PRO_Util::SafePath', explode(";", $this->FilterFiles, -1));
        }
		
		//FILTER: GLOBAL ITMES
		//Filters items set from the settings page
		//TODO: Still need to wire up after UI is complete
		if (true) {
			// $this->FilterInfo->Dirs->Global[] = call_to_store();
			// $this->FilterInfo->Exts->Global[] = call_to_store();
		}
		
		//FILTER: CORE ITMES
		//Filters Duplicator free packages & All pro local directories
		$this->FilterInfo->Dirs->Core[] = LEGACY_DUPLICATOR_SSDIR_PATH;
        $storages = DUP_PRO_Storage_Entity::get_all();
        foreach($storages as $storage)
        {
            if($storage->storage_type == DUP_PRO_Storage_Types::Local  && $storage->local_filter_protection)
            {
				$this->FilterInfo->Dirs->Core[] = DUP_PRO_Util::SafePath($storage->local_storage_folder);
            }
        }
		
		$this->FilterDirsAll = array_merge($this->FilterInfo->Dirs->Instance, 
										   $this->FilterInfo->Dirs->Global,
				                           $this->FilterInfo->Dirs->Core);
		
		$this->FilterExtsAll = array_merge($this->FilterInfo->Exts->Instance,                                 
										   $this->FilterInfo->Exts->Global,
				                           $this->FilterInfo->Exts->Core);
        
        $this->FilterFilesAll = array_merge($this->FilterInfo->Files->Instance, 
										    $this->FilterInfo->Files->Global,
				                            $this->FilterInfo->Files->Core);
	}

    //Get All Directories then filter
    private function getDirs()
    {
        $rootPath = DUP_PRO_Util::SafePath(rtrim(DUPLICATOR_PRO_WPROOTPATH, '//'));
		
        //If the root directory is a filter then we will only need the root files

        if (in_array($this->PackDir, $this->FilterDirsAll))
        {
            $this->Dirs = array();
            $this->Dirs[] = $this->PackDir;
        }
        else
        {
            $this->Dirs = $this->dirsToArray($rootPath);
            $this->Dirs[] = $this->PackDir;
        }
        
        //Filter Directories
        //Invalid test contains checks for: characters over 250, invlaid characters, 
        //empty string and directories ending with period (Windows incompatable)
        foreach ($this->Dirs as $key => $val)
        {            
            //Remove path filter directories
            foreach ($this->FilterDirsAll as $item)
            {
                $trimmed_item = rtrim($item, '/');
                
                if ($val == $trimmed_item || strstr($val, $trimmed_item . '/')) 
				{
                    unset($this->Dirs[$key]);
                    continue 2;
                }
            }

            //Locate invalid directories and warn
            $name = basename($val);
            $invalid_test = strlen($val) > 250 || 
							preg_match('/(\/|\*|\?|\>|\<|\:|\\|\|)/', $name) || 
							trim($name) == '' || 
							(strrpos($name, '.') == strlen($name) - 1 && substr($name, -1) == '.');

            if ($invalid_test || preg_match('/[^\x20-\x7f]/', $name))
            {
				$this->FilterInfo->Dirs->Warning[] = Encoding::toUTF8($val);
            }	
			
			//Dir is not readble remove and flag
			if (! is_readable($this->Dirs[$key])) 
			{
				unset($this->Dirs[$key]);
				$unreadable_dir = Encoding::toUTF8($val);
				$this->FilterInfo->Dirs->Unreadable[] = $unreadable_dir;
				$this->FilterDirsAll[] = $unreadable_dir;
			}
        }
		
		DUP_PRO_U::log_object('filter dirs array', $this->FilterDirsAll);
		DUP_PRO_U::log_object('filter exts array', $this->FilterExtsAll);
        DUP_PRO_U::log_object('filter files array', $this->FilterFilesAll);
    }

    
    //Get all files and filter out error prone subsets
    private function getFiles()
    {
        foreach ($this->Dirs as $key => $val)
        {
            $files = DUP_PRO_IO::get_files($val);

            foreach ($files as $filePath)
            {
                $fileName = basename($filePath);
                if (!is_dir($filePath))
                {
                    if (!in_array(@pathinfo($filePath, PATHINFO_EXTENSION), $this->FilterExtsAll) &&
                        !in_array($filePath, $this->FilterFilesAll))
                    {                        
						if (!is_readable($filePath))
						{
							$this->FilterInfo->Files->Unreadable[]  = $filePath;
							continue;
						}
						
                        $fileSize = @filesize($filePath);
                        $fileSize = empty($fileSize) ? 0 : $fileSize;
						$invalid_test = strlen($filePath) > 250 || 
										preg_match('/(\/|\*|\?|\>|\<|\:|\\|\|)/', $fileName) || 
										trim($fileName) == "";
						
                        if ($invalid_test || preg_match('/[^\x20-\x7f]/', $fileName))
                        {
							$utf8_file_path = Encoding::toUTF8($filePath);
							$this->FilterInfo->Files->Warning[] = $utf8_file_path;
                        } 
						else 
						{
							$this->Size += $fileSize;
                            $this->Files[] = $filePath;
						}
						
                        if ($fileSize > DUPLICATOR_PRO_SCAN_WARNFILESIZE)
                        {
							$this->FilterInfo->Files->Size[] = $filePath . ' [' . DUP_PRO_Util::ByteSize($fileSize) . ']';
                        }
						
                    }
                }
            }
        }
    }

    //Recursive function to get all Directories in a wp install
    //Older PHP logic which is more stable on older version of PHP
	//NOTE RecursiveIteratorIterator is problematic on some systems issues include:
    // - error 'too many files open' for recursion
    // - $file->getExtension() is not reliable as it silently fails at least in php 5.2.17 
    // - issues with when a file has a permission such as 705 and trying to get info (had to fallback to pathinfo)
	// - basic conclusion wait on the SPL libs untill after php 5.4 is a requiremnt
	// - since we are in a tight recursive loop lets remove the utiltiy call DUP_PRO_Util::SafePath("{$path}/{$file}") and 
	//   squeeze out as much performance as we possible can
    private function dirsToArray($path)
    {
        $items = array();
        $handle = @opendir($path);
        if ($handle)
        {
            while (($file = readdir($handle)) !== false)
            {
                if ($file != '.' && $file != '..')
                {
					$fullPath = str_replace("\\", '/', "{$path}/{$file}");
                    if (is_dir($fullPath))
                    {
                        $items = array_merge($items, $this->dirsToArray($fullPath));
                        $items[] = $fullPath;
                    }
                }
            }
            closedir($handle);
        }
        return $items;
    }
}
?>
