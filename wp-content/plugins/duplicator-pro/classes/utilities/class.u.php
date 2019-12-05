<?php

/*
  Duplicator Pro Plugin
  Copyright (C) 2015, Snap Creek LLC
  website: snapcreek.com

  Duplicator Pro Plugin is distributed under the GNU General Public License, Version 3,
  June 2007. Copyright (C) 2007 Free Software Foundation, Inc., 51 Franklin
  St, Fifth Floor, Boston, MA 02110, USA

  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
  ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
  WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
  DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
  ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
  (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
  LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
  ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
  SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

require_once(dirname(__FILE__) . '/../../define.php');
require_once(dirname(__FILE__) . '/class.utility.php');
require_once(dirname(__FILE__) . '/class.io.php');
require_once(dirname(__FILE__) . '/../class.constants.php');
require_once(dirname(__FILE__) . '/../../lib/pcrypt/class.pcrypt.php');
require_once(dirname(__FILE__) . '/../entities/class.global.entity.php');


if (!class_exists('DUP_PRO_U'))
{

    /**
     * @copyright 2015 Snap Creek LLC
     */
    class DUP_PRO_U
    {
        // Pseudo-constants
        public static $PLUGIN_URL;
        public static $PLUGIN_DIRECTORY;
        private static $type_format_array;

        // private static $package_dumpfile_handle = null;

        public static function init()
        {
            $__dir__ = dirname(__FILE__);

            self::$PLUGIN_URL = plugins_url() . "/" . DUP_PRO_Constants::PLUGIN_SLUG;

            self::$PLUGIN_DIRECTORY = (WP_CONTENT_DIR . "/plugins/" . DUP_PRO_Constants::PLUGIN_SLUG);

            self::$type_format_array = array('boolean' => '%s', 'integer' => '%d', 'double' => '%g', 'string' => '%s');
        }
				
		private function network_menu_page_url($menu_slug, $echo = true) 
		{			
			global $_parent_pages;

			if ( isset( $_parent_pages[$menu_slug] ) ) {
				$parent_slug = $_parent_pages[$menu_slug];
				if ( $parent_slug && ! isset( $_parent_pages[$parent_slug] ) ) {
					$url = network_admin_url( add_query_arg( 'page', $menu_slug, $parent_slug ) );
				} else {
					$url = network_admin_url( 'admin.php?page=' . $menu_slug );
				}
			} else {
				$url = '';
			}

			$url = esc_url($url);

			if ( $echo ) echo $url;

			// --<
			return $url;
		}
		
		public static function menu_page_url($menu_slug, $echo = true)
		{
			if(defined('MULTISITE') && MULTISITE)
			{
				return self::network_menu_page_url($menu_slug, $echo);
			}
			else
			{
				return menu_page_url($menu_slug, $echo);
			}					
		}
		
		public static function is_multisite()
		{
			return self::get_mu_mode() > 0;
		}
		
		// 0 = single site; 1 = multisite subdomain; 2 = multisite subdirectory
		public static function get_mu_mode()
		{
			if(defined('MULTISITE') && MULTISITE)
			{
				if(defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL)
				{
					return 1;
				}
				else
				{
					return 2;
				}
			}
			else
			{
				return 0;
			}
		}
        
        public static function percentage($val1, $val2, $precision = 0)
        {
            $division = $val1 / (float) $val2;

            $res = $division * 100;

            $res = round($res, $precision);

            return $res;
        }
		
		static public function PHP53()
		{
			return version_compare(PHP_VERSION, '5.3.2', '>=');
		}
		
		static public function PHP55()
		{
			return version_compare(PHP_VERSION, '5.5.0', '>=');
		}
        
        public static function get_sql_lock()
        {
            global $wpdb;

          //  $table_name = $wpdb->prefix . $table_name;

            $query_string = "select GET_LOCK('duplicator_pro_lock', 0)";          

            $ret_val = $wpdb->get_var($query_string);
            
            if($ret_val == 0)
            {
                DUP_PRO_U::log("Couldnt get mysql lock");
                return false;                
            } else if ($ret_val == null)
            {
                DUP_PRO_U::log("Error retrieving mysql lock");
                return false;
            }
            else
            {
                DUP_PRO_U::log("Mysql lock obtained");
                return true;                
            }
        }            
        
        public static function release_sql_lock()
        {
             global $wpdb;
             
             $query_string = "select RELEASE_LOCK('duplicator_pro_lock')";
             
             $ret_val = $wpdb->get_var($query_string);
            
            if($ret_val == 0)                
            {
                DUP_PRO_U::log("Failed releasing sql lock duplicator_pro_lock because it wasn't established by this thread");
            }
            else if($ret_val == null)
            {
                DUP_PRO_U::log("Tried to release sql lock duplicator_pro_lock but it didn't exist");
            }
            else
            {
                // Lock was released
                DUP_PRO_U::log("SQL lock released");
            }
        }
        
        public static function is_curl_available()
        {
            return function_exists('curl_init');
        }
                
        public static function is_url_fopen_enabled()
        {
            $val = ini_get('allow_url_fopen');
            
            return ($val == true);
        }

        public static function safe_path($path)
        {
            // rsr pull out logic from dpputil and swithc everything over to this method
            return DUP_PRO_Util::SafePath($path);
        }
		
		public static function copy_with_verify($source_filepath, $dest_filepath)
		{			
			DUP_PRO_U::log("Copy with verify $source_filepath to $dest_filepath");
			
			$ret_val = false;
						
			if(copy($source_filepath, $dest_filepath))
			{
				if(function_exists('sha1_file'))
				{
					$source_sha1 = sha1_file($source_filepath);
					$dest_sha1 = sha1_file($dest_filepath);
					
					if($source_sha1 === $dest_sha1 && ($source_sha1 !== false))
					{
						DUP_PRO_U::log("Sha1 of $source_filepath and $dest_filepath match");
						$ret_val = true;
					}
					else
					{
						DUP_PRO_U::log("Sha1 hash of $dest_filepath doesn't match $source_filepath!");
					}
				}
				else
				{
					DUP_PRO_U::log("sha1_file not present so doing existence check");
					
					$ret_val = file_exists($dest_filepath);
					
					if($ret_val != true)
					{
						DUP_PRO_U::log("$dest_filepath doesn't exist after copy!");
					}
				}
			}
			else
			{
				DUP_PRO_U::log("Problem copying $source_filepath to $dest_filepath");
			}
			
			return $ret_val;
		}
        
        // Copy source to destination while preserving the backup if the destination already exists
        // Note: Intended to be used during package building only since fatal log errors are utilized
        public static function copy_with_backup($source_filepath, $dest_filepath, $backup_filepath)
        {
            DUP_PRO_U::log("Copy with backup $source_filepath $dest_filepath $backup_filepath");
            if(is_dir($dest_filepath) || 
               (file_exists($dest_filepath) && (is_file($dest_filepath) == false)))
            {
                DUP_PRO_Log::Error("Trying to copy to a directory ($dest_filepath) not a file which isn't allowed.");               
            }
            
            // In the event there is a file with that same name present we have to save it off into $backup_filepath
            
            if(file_exists($backup_filepath))
            {
                DUP_PRO_U::log("Deleting $backup_filepath");
                if(@unlink($backup_filepath))
                {
                    DUP_PRO_U::log("Deleted $backup_filepath");
                }
                else
                {        
                    DUP_PRO_Log::Error("ERROR: Couldn't delete backup file $backup_filepath");    
                }
            }
            
            if(file_exists($dest_filepath))
            {
                DUP_PRO_U::log("Renaming $dest_filepath to $backup_filepath");
                if(@rename($dest_filepath, $backup_filepath))
                {
                    DUP_PRO_U::log("Renamed $dest_filepath to $backup_filepath");
                }
                else
                {
                    DUP_PRO_Log::Error("ERROR: Couldn't rename $dest_filepath $backup_filepath");
                }
            }                                               
            
            DUP_PRO_U::log("Copying $source_filepath to $dest_filepath");
            if (copy($source_filepath, $dest_filepath))
            {
                DUP_PRO_U::log("Copied $source_filepath to $dest_filepath");
            }
            else
            {                              
                @rename($backup_filepath, $dest_filepath);

                DUP_PRO_Log::Error("ERROR: Couldn't copy the $source_filepath to $dest_filepath");
            }
        }
        
        public static function restore_backup($filepath, $backup_filepath)
        {            
            if(is_dir($filepath) || 
               (file_exists($filepath) && (is_file($filepath) == false)))
            {
                DUP_PRO_U::log("Trying to restore backup to a directory ($filepath) rather than file which isn't allowed.");
            }
                        
            if(file_exists($filepath))
            {
                DUP_PRO_U::log("Deleting $filepath");
                if(@unlink($filepath))
                {
                    DUP_PRO_U::log("Deleted $filepath");
                }
                else
                {
                    $message = "Couldn't delete $filepath";
                    DUP_PRO_Log::Error($message, false);
                    DUP_PRO_U::log($message);
                }
            }
                                   
            if(file_exists($backup_filepath))
            {
                DUP_PRO_U::log("Renaming $backup_filepath to $filepath");
                
                if(@rename($backup_filepath, $filepath))
                {
                    DUP_PRO_U::log("Renamed $backup_filepath to $filepath");                        
                }
                else
                {
                    $message = "Couldn't rename $backup_filepath to $filepath";
                    DUP_PRO_Log::Error($message, false);    
                    DUP_PRO_U::log($message);
                }
            }
        }

        public static function get_log_filepath()
        {
            $default_key = self::get_default_key();

            $log_filename = "dup_pro_$default_key.log";

            $file_path = DUPLICATOR_PRO_SSDIR_PATH . "/" . $log_filename;

            return $file_path;
        }

        public static function get_log_url()
        {
            $default_key = self::get_default_key();

            $log_filename = "dup_pro_$default_key.log";

            $url = DUPLICATOR_PRO_SSDIR_URL . "/" . $log_filename;

            return $url;
        }

        public static function get_backup_log_filepath()
        {
            $default_key = self::get_default_key();

            $backup_log_filename = "dup_pro_$default_key.log1";

            $backup_path = DUPLICATOR_PRO_SSDIR_PATH . "/" . $backup_log_filename;

            return $backup_path;
        }

        public static function get_url_from_local_path($local_path)
        {
            get_site_url(null, '', is_ssl() ? 'https' : 'http') . $local_path;
        }

        private static function get_default_key()
        {
            $auth_key = defined('AUTH_KEY') ? AUTH_KEY : 'atk';
            $auth_key .= defined('DB_HOST') ? DB_HOST : 'dbh';
            $auth_key .= defined('DB_NAME') ? DB_NAME : 'dbn';
            $auth_key .= defined('DB_USER') ? DB_USER : 'dbu';

            return hash('md5', $auth_key);
        }

        public static function encrypt($string, $key = null)
        {
            if ($key == null)
            {
                $key = self::get_default_key();
            }

            $crypt = new pcrypt(MODE_ECB, "BLOWFISH", $key);

            // to encrypt
            $encrypted_value = $crypt->encrypt($string);

            $encrypted_value = base64_encode($encrypted_value);

            return $encrypted_value;
        }

        public static function decrypt($encrypted_string, $key = null)
        {
            if ($key == null)
            {
                $key = self::get_default_key();
            }

            $crypt = new pcrypt(MODE_ECB, "BLOWFISH", $key);

            $encrypted_string = base64_decode($encrypted_string);

            $decrypted_value = $crypt->decrypt($encrypted_string);

            return $decrypted_value;
        }

        public static function bool_to_string($b)
        {
            return ($b ? self::__('True') : self::__('False'));
        }

        public static function _e($text)
        {
            _e($text, DUP_PRO_Constants::PLUGIN_SLUG);
        }

        public static function __($text)
        {
            return __($text, DUP_PRO_Constants::PLUGIN_SLUG);
        }

        public static function add_file_to_zip($zip_archive, $file_path, $compress_dir)
        {
			/* @var $global DUP_PRO_Global_Entity */
			$global = DUP_PRO_Global_Entity::get_instance();
			
            // USe addfromstring when possible/lower memory requirements - doesn't cause file descriptor issues
            $local_name = ltrim(str_replace($compress_dir, '', $file_path), '/');

            //rsr todo verify size algorithm
            if ((filesize($file_path) < DUP_PRO_Constants::ZIP_STRING_LIMIT) || ($global->ziparchive_mode == DUP_PRO_ZipArchive_Mode::Enhanced))
            {
                return $zip_archive->addFromString($local_name, file_get_contents($file_path));
            }
            else
            {
                return $zip_archive->addFile($file_path, $local_name);
            }
        }

        public static function _he($text)
        {

            echo htmlspecialchars($text);
        }

        public static function get_full_table_name($base_table_name)
        {
            global $wpdb;

            return $wpdb->prefix . $base_table_name;
        }

        public static function url_from_slug($slug)
        {

            return "?page=" . $slug;
        }

        public static function get_simplified_local_time_from_formatted_gmt($timestamp)
        {
            $local_ticks = DUP_PRO_U::get_local_ticks_from_gmt_formatted_time($timestamp);

            $date_portion = date('M j,', $local_ticks);
            $time_portion = date('g:i:s a', $local_ticks);

            return "$date_portion $time_portion";
        }

        public static function get_local_time_in_format($format)
        {
            $ticks = time();

            $ticks += ((int) get_option('gmt_offset') * 3600);

            return date($format, $ticks);
        }

        public static function ticks_to_standard_formatted($ticks)
        {
            return date('Y-m-d H:i:s', $ticks);
        }

        public static function starts_with($haystack, $needle)
        {
            $length = strlen($needle);

            return (substr($haystack, 0, $length) === $needle);
        }
        
        public static function create_relative_path($base_path, $full_target_path)
        {
            $base_index = strlen($base_path);
            $relative_target_path = $full_target_path;
            
            if(self::starts_with($full_target_path, $base_path))
            {
                $relative_target_path = substr($relative_target_path, $base_index);
                $relative_target_path = ltrim($relative_filter_dir, '/\\');
            }
            
            return $relative_target_path;
        }
        
        public static function get_relative_path($from, $to)
        {
            // some compatibility fixes for Windows paths
            $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
            $to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
            $from = str_replace('\\', '/', $from);
            $to   = str_replace('\\', '/', $to);

            $from     = explode('/', $from);
            $to       = explode('/', $to);
            $relPath  = $to;

            foreach($from as $depth => $dir) {
                // find first non-matching dir
                if($dir === $to[$depth]) {
                    // ignore this directory
                    array_shift($relPath);
                } else {
                    // get number of remaining dirs to $from
                    $remaining = count($from) - $depth;
                    if($remaining > 1) {
                        // add traversals up to first matching dir
                        $padLength = (count($relPath) + $remaining - 1) * -1;
                        $relPath = array_pad($relPath, $padLength, '..');
                        break;
                    } else {
                        //$relPath[0] = './' . $relPath[0];
                    }
                }
            }
            return implode('/', $relPath);
        }
        
        public static function get_local_ticks_from_gmt_formatted_time($timestamp)
        {
            $ticks = strtotime($timestamp);

            $ticks += ((int) get_option('gmt_offset') * 3600);

            return $ticks;
        }

        public static function get_local_formatted_time_from_gmt_ticks($ticks)
        {
            $ticks += ((int) get_option('gmt_offset') * 3600);

            return self::get_standard_formatted_time($ticks);
        }

        public static function get_formatted_local_time_from_gmt($timestamp, $format)
        {
            $ticks = self::get_local_ticks_from_gmt_formatted_time($timestamp);

            return date($format, $ticks);
        }

        public static function get_standard_formatted_time($ticks)
        {
            //return date('D, d M Y H:i:s', $ticks);
            return date('D, d M H:i:s', $ticks);
        }

        public static function get_wp_formatted_from_gmt_formatted_time($timestamp, $include_date = true, $include_time = true)
        {
            $ticks = self::get_local_ticks_from_gmt_formatted_time($timestamp);

            $date_format = get_option('date_format');
            $time_format = get_option('time_format');

            //return date("Y-m-d H:i:s", $ticks);
            if ($include_date)
            {
                $date_portion = date($date_format, $ticks);
            }
            else
            {
                $date_portion = '';
            }

            if ($include_time)
            {
                $time_portion = date($time_format, $ticks);
            }
            else
            {
                $time_portion = '';
            }

            if ($include_date && $include_time)
            {
                $seperator = ' ';
            }
            else
            {
                $seperator = '';
            }

            return "$date_portion$seperator$time_portion";
        }

        public static function ends_with($haystack, $needle)
        {
            $length = strlen($needle);
            if ($length == 0)
            {
                return true;
            }

            return (substr($haystack, -$length) === $needle);
        }

        public static function get_page_name_from_url($url)
        {
            $post_id = url_to_postid($url);

            if ($post_id != 0)
            {
                $page_name = get_the_title($post_id);
            }
            else
            {
                $page_name = $url;
            }

            return $page_name;
        }

        public static function append_query_value($url, $key, $value)
        {
            $separator = (parse_url($url, PHP_URL_QUERY) == NULL) ? '?' : '&';

            $modified_url = $url . "$separator$key=$value";

            return $modified_url;
        }

        public static function get_db_type_format($variable)
        {

            $type_string = gettype($variable);

            if ($type_string == "NULL")
            {

                self::log("get_db_type_format: Error. Variable is not initialized.");
                return "";
            }

            return self::$type_format_array[$type_string];
        }

        public static function echo_checked($val)
        {
            echo $val ? 'checked' : '';
        }

        public static function echo_disabled($val)
        {
            echo $val ? 'disabled' : '';
        }

        public static function echo_selected($val)
        {
            echo $val ? 'selected' : '';
        }

        public static function get_checked($val)
        {
            return ($val ? 'checked' : '');
        }

        public static function get_selected($val)
        {
            return ($val ? 'selected' : '');
        }

        public static function get_public_properties($object)
        {

            $publics = get_object_vars($object);
            unset($publics['id']);

            // Disregard anything that starts with '_'
            foreach ($publics as $key => $value)
            {
                if (self::starts_with($key, '_'))
                {
                    unset($publics[$key]);
                }
            }

            // rsr only in json types unset($publics['type']);

            return $publics;
        }

        public static function get_public_class_properties($class_name)
        {

            $publics = get_class_vars($class_name);
            unset($publics['id']);

            return $publics;
        }

        public static function get_guid()
        {

            if (function_exists('com_create_guid') === true)
            {
                return trim(com_create_guid(), '{}');
            }

            return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
        }

        public static function get_request_val($key, $default)
        {
            if (isset($_REQUEST[$key]))
            {
                return $_REQUEST[$key];
            }
            else
            {
                return $default;
            }
        }

        // Copies simple values from one object to another
        public static function simple_object_copy($source_object, $dest_object)
        {
            foreach ($source_object as $member_name => $member_value)
            {
                if (!is_object($member_value))
                {
                    // Skipping all object members
                    $dest_object->$member_name = $member_value;
                }
            }
        }

        public static function simple_object_array_copy(&$source_array, &$dest_array, $class_name)
        {
            foreach ($source_array as $source_object)
            {
                $dest_object = new $class_name();

                self::simple_object_copy($source_object, $dest_object);

                array_push($dest_array, $dest_object);
            }
        }

        public static function get_calling_function_name()
        {
            $callers = debug_backtrace();

            $function_name = $callers[2]['function'];
            $class_name = isset($callers[2]['class']) ? $callers[2]['class'] : '';

            return "$class_name::$function_name";
        }

        public static function log_error($message)
        {
            self::log($message, false);
        }

        public static function write_to_plugin_log($formatted_logging_message)
        {
            $log_filepath = DUP_PRO_U::get_log_filepath();

            if (@filesize($log_filepath) > DUP_PRO_Constants::MAX_LOG_SIZE)
            {
                $backup_log_filepath = DUP_PRO_U::get_backup_log_filepath();

                if (file_exists($backup_log_filepath))
                {
                    if (@unlink($backup_log_filepath) === false)
                    {
                        error_log("Couldn't delete backup log $backup_log_filepath");
                    }
                }

                if (@rename($log_filepath, $backup_log_filepath) === false)
                {
                    error_log("Couldn't rename log $log_filepath to $backup_log_filepath");
                }
            }

            if (@file_put_contents($log_filepath, $formatted_logging_message, FILE_APPEND) === false)
            {
                error_log("Error writing $formatted_logging_message to system log.");
            }
        }

        public static function purge_plugin_logs()
        {
            $log_filepath = DUP_PRO_U::get_log_filepath();

			DUP_PRO_IO::delete_file($log_filepath);
        }

        public static function trace_file_exists()
        {
            $file_path = DUP_PRO_U::get_log_filepath();

            return file_exists($file_path);
        }

        public static function get_trace_log_status()
        {
            $file_path = DUP_PRO_U::get_log_filepath();
            $backup_path = DUP_PRO_U::get_backup_log_filepath();

            if (file_exists($file_path))
            {
                $filesize = filesize($file_path);

                if (file_exists($backup_path))
                {
                    $filesize += filesize($backup_path);
                }

                $message = sprintf(DUP_PRO_U::__('%1$s'), DUP_PRO_Util::ByteSize($filesize));
            }
            else
            {
                $message = DUP_PRO_U::__('No Log');
            }


            return $message;
        }

        public static function zip_file($source_filepath, $zip_filepath, $delete_old = true, $new_name = null)
        {
            if ($delete_old && file_exists($zip_filepath))
            {
                DUP_PRO_IO::delete_file($zip_filepath);
            }

            if (file_exists($source_filepath))
            {
                $zip_archive = new ZipArchive();

                $is_zip_open = ($zip_archive->open($zip_filepath, ZIPARCHIVE::CREATE) === TRUE);

                if ($is_zip_open === false)
                {
                    DUP_PRO_Log::Error("Cannot create zip archive $zip_filepath");
                }
                else
                {
                    //ADD SQL 
                    if ($new_name == null)
                    {
                        $source_filename = basename($source_filepath);
                        DUP_PRO_U::log("adding $source_filename");
                    }
                    else
                    {
                        $source_filename = $new_name;
                        DUP_PRO_U::log("new name added $new_name");
                    }

					$global = DUP_PRO_Global_Entity::get_instance();
					
					if ($global->ziparchive_mode == DUP_PRO_ZipArchive_Mode::Enhanced)
					{
						$in_zip = $zip_archive->addFromString($source_filename, file_get_contents($source_filepath));
					}
					else
					{
						$in_zip = $zip_archive->addFile($source_filepath, $source_filename);
					}

                    if ($in_zip === false)
                    {
                        DUP_PRO_Log::Error("Unable to add $source_filepath to $zip_filepath");
                    }

                    $zip_archive->close();

                    return true;
                }
            }
            else
            {
                DUP_PRO_Log::Error("Trying to add $source_filepath to a zip but it doesn't exist!");
            }

            return false;
        }

        public static function log($message, $audit = true)
        {
            $send_trace_to_error_log = (bool) get_option('duplicator_pro_send_trace_to_error_log', false);

            $unique_id = sprintf("%08x", abs(crc32($_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME'] . $_SERVER['REMOTE_PORT'])));

            $calling_function = self::get_calling_function_name() . '()';

            if (is_array($message) || is_object($message))
            {
                $message = print_r($message, true);
            }

            $logging_message = 'DUP_PRO | ' . $unique_id . " | $calling_function | " . $message;

            $ticks = time() + ((int) get_option('gmt_offset') * 3600);

            $formatted_time = date('d M H:i:s', $ticks);

            $formatted_logging_message = "[$formatted_time] $logging_message \r\n";


            // Write to error log if warranted - if either it's a non audit(error) or tracing has been piped to the error log
            if (($audit == false) || ($send_trace_to_error_log) && WP_DEBUG && WP_DEBUG_LOG)
            {
                error_log($logging_message);
            }

            // Everything goes to the plugin log, whether it's part of package generation or not.
            self::write_to_plugin_log($formatted_logging_message);
        }

        public static function log_object($message, $object)
        {
            self::log($message . '<br\>');
            self::log($object);
        }

        public static function ddebug($message)
        {
            self::log($message, true);
        }

    }

    DUP_PRO_U::init();
}
?>
