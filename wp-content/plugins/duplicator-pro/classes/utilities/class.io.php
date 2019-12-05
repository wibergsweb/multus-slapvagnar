<?php

require_once(DUPLICATOR_PRO_PLUGIN_PATH . 'define.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . 'classes/class.constants.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . 'classes/utilities/class.u.php');

if (! class_exists('DUP_PRO_IO'))
{
    /**
     * @copyright 2015 Snap Creek LLC
	 * Class for all IO operations
     */
    class DUP_PRO_IO
    {
		/**  
		 * Attempts to change the mode of the specified file.
		 * 
		 * @param string	$file	Path to the file.
		 * @param octal		$mode		The mode parameter consists of three octal number components specifying access restrictions for the owner
		 * 
		 * @return TRUE on success or FALSE on failure.
		 */
		public static function change_mode($file , $mode)
		{
			if (! file_exists($file)) 
				return false;
				
			if (chmod($file , $mode) === false)
			{
				DUP_PRO_U::log_error("Error chaning the mode on: {$file}.");
				return false;
			}
			return true;
		}
		
		
		/** 
		 * Safely deletes a file
		 * 
		 * @param string $file	The full filepath to the file
		 * 
		 * @return TRUE on success or if file does not exist. FALSE on failure
		 */
		public static function delete_file($file)
		{
			if (file_exists($file))
			{
				if (@unlink($file) === false)
				{
					DUP_PRO_U::log_error("Could not delete file: {$file}");
					return false;
				}
			}
			return true;
		}
		
	
		/**  
		 * Safely copies a file to a directory 
		 * 
		 * @param string $source_file	The full filepath to the file to copy
		 * @param string $dest_dir			The full path to the destination directory were the file will be copied
		 * @param string $delete_first		Delete file before copying the new one
		 * 
		 *  @return TRUE on success or if file does not exist. FALSE on failure
		 */
		public static function copy_file($source_file, $dest_dir, $delete_first = false)
        {
			//Create directory 
            if (file_exists($dest_dir) == false)
            {
                if (self::create_dir($dest_dir, 0755, true) === false)
                {
                    DUP_PRO_U::log_error("Error creating $dest_dir.");
                    return false;
                }
            }
			
			//Remove file with same name before copy
            $filename = basename($source_file);
            $dest_filepath = $dest_dir . "/$filename";
			if($delete_first)
			{
				self::delete_file($dest_filepath);
			}
			
            return copy($source_file, $dest_filepath);
        }

		
		/**
	     * Get all of the files of a path
	     * 
	     * @path string $path A system directory path
	     * 
	     * @return array of all files in that path
	     */
		public static function get_files($dir = '.')
		{
			$files = array();
			foreach (new DirectoryIterator($dir) as $file)
			{
				$files[] = str_replace("\\", '/', $file->getPathname());
			}       
			return $files;
		}
		
		
		/**  
		 * Safely creates a directory
		 * 
		 * @param string $dir		The full path to the directory to be created
		 * @param octal  $mode			The mode is 0755 by default
		 * @param bool	 $recursive		Allows the creation of nested directories specified in the pathname.
		 * 
		 * @return TRUE on success and if directory already exists. FALSE on failure
		 */
		public static function create_dir($dir, $mode = 0755, $recursive = false)
		{
			if (file_exists($dir) && @is_dir($dir))
				return true;
			
			if (@mkdir($dir, $mode, $recursive) === false)
			{
				DUP_PRO_U::log_error("Error creating directory: {$dir}.");
				return false;
			}
			return true;
		}
		
		
		/**
	     * List all of the directories of a path
	     * 
	     * @param string $dir to a system directory
	     *
	     * @return array of all directories in that path
	     */
		public static function get_dirs($dir = '.')
		{
			$dirs = array();
			foreach (new DirectoryIterator($dir) as $file)
			{
				if ($file->isDir() && !$file->isDot())
				{
					$dirs[] = DUP_PRO_Util::SafePath($file->getPathname());
				}
			}
			return $dirs;
		}
		
		
		/**
	     * Does the directory have content
	     * 
	     * @param string $dir	A system directory
	     *
	     * @return array of all directories in that path
	     */
		public static function is_dir_empty($dir)
		{
			if (!is_readable($dir))
				return NULL;
			return (count(scandir($dir)) == 2);
		}
		
		
		/**
	     * Size of the directory recuresivly in bytes
	     * 
	     * @param string $dir	A system directory
	     *
	     * @return int Returns the size of all data in the directory in bytes
	     */
		public static function get_dir_size($dir)
		{
			if (!file_exists($dir))
				return 0;
			if (is_file($dir))
				return filesize($dir);

			$size = 0;
			$list = glob($dir . "/*");
			if (!empty($list))
			{
				foreach ($list as $file)
					$size += self::get_dir_size($file);
			}
			return $size;
		}
		
    }
}
?>