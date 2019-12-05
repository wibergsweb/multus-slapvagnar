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
require_once(dirname(__FILE__) . '/class.json.entity.base.php');
require_once(dirname(__FILE__) . '/../utilities/class.u.php');

if (!class_exists('DUP_PRO_Dropbox_Transfer_Mode'))
{

    /**
     * @copyright 2015 Snap Creek LLC
     */
    abstract class DUP_PRO_Dropbox_Transfer_Mode
    {        
        const Unconfigured = -1;
        const Disabled = 0;
        const cURL = 1;
        const FOpen_URL = 2;
    }
}

if (!class_exists('DUP_PRO_Thread_Lock_Mode'))
{

    /**
     * @copyright 2015 Snap Creek LLC
     */
    abstract class DUP_PRO_Thread_Lock_Mode
    {
        const Flock = 0;
        const SQL_Lock = 1;
    }
}

if (!class_exists('DUP_PRO_Email_Build_Mode'))
{

    /**
     * @copyright 2015 Snap Creek LLC
     */
    abstract class DUP_PRO_Email_Build_Mode
    {
        const No_Emails = 0;
        const Email_On_Failure = 1;
        const Email_On_All_Builds = 2;

    }

}

if (!class_exists('DUP_PRO_JSON_Mode'))
{

    /**
     * @copyright 2015 Snap Creek LLC
     */
    abstract class DUP_PRO_JSON_Mode
    {
        const PHP = 0;
        const Custom = 1;
    }
}

if (!class_exists('DUP_PRO_Archive_Build_Mode'))
{

    /**
     * @copyright 2015 Snap Creek LLC
     */
    abstract class DUP_PRO_Archive_Build_Mode
    {
		const Unconfigured = -1;
        const Auto = 0;	// should no longer be used
        const Shell_Exec = 1;
        const ZipArchive = 2;
    }
}

if (!class_exists('DUP_PRO_Server_Load_Reduction'))
{

    /**
     * @copyright 2015 Snap Creek LLC
     */
    class DUP_PRO_Server_Load_Reduction
    {
        const None = 0;
        const A_Bit = 1;
        const More = 2;
        const A_Lot = 3;

        public static function microseconds_from_reduction($reduction)
        {
            switch ($reduction)
            {
                case self::A_Bit:
                    return 9000;

                case self::More:
                    return 29000;

                case self::A_Lot:
                    return 92000;

                default:
                    return 0;
            }
        }

    }

}

if (!class_exists('DUP_PRO_License_Status'))
{
    abstract class DUP_PRO_License_Status
    {
        const Uncached = -2;
        const Unknown = -1;
        const Valid = 0;
        const Invalid = 1;
        const Inactive = 2;
        const Disabled = 3;
        const Site_Inactive = 4;
        const Expired = 5;
    }
}

if (!class_exists('DUP_PRO_ZipArchive_Mode'))
{
    abstract class DUP_PRO_ZipArchive_Mode
    {
        const Legacy = 0;
        const Enhanced = 1;
    }
}

if (!class_exists('DUP_PRO_Global_Entity'))
{

    /**
     * @copyright 2015 Snap Creek LLC
     */
    class DUP_PRO_Global_Entity extends DUP_PRO_JSON_Entity_Base
    {
        public $dropbox_upload_chunksize_in_kb = 2000;
        public $php_max_worker_time_in_sec = 15;
        public $max_storage_retries = 10;
        public $uninstall_settings = true;
        public $uninstall_files = true;
        public $uninstall_tables = true;
        public $package_debug = false;
        public $package_mysqldump = true;
        public $package_phpdump_qrylimit = 100;
        public $package_mysqldump_path = '';
        public $storage_htaccess_off = false;
        public $wpfront_integrate = false;
        public $send_trace_to_error_log = true;
        public $send_email_on_build_mode = DUP_PRO_Email_Build_Mode::Email_On_Failure;
        public $max_package_runtime_in_min = 90;
        public $ajax_protocol = "http";
        public $custom_ajax_url = "";
        public $server_load_reduction = DUP_PRO_Server_Load_Reduction::None;
        public $basic_auth_enabled = false;
        public $basic_auth_user = '';
        public $basic_auth_password = '';
        public $archive_build_mode = DUP_PRO_Archive_Build_Mode::Unconfigured; 
        public $lock_mode = DUP_PRO_Thread_Lock_Mode::Flock;
        public $license_status = DUP_PRO_License_Status::Unknown;
        public $license_expiration_time = 0;
		public $clientside_kickoff = false;

        public $dropbox_transfer_mode = DUP_PRO_Dropbox_Transfer_Mode::Unconfigured;
		public $gdrive_upload_chunksize_in_kb = 2000;		// Not exposed through the UI (yet)
		public $s3_upload_part_size_in_kb = 6000;			// Not exposed through the UI (yet)
		public $ziparchive_chunk_size_in_mb = 6;
		public $ziparchive_mode = DUP_PRO_ZipArchive_Mode::Legacy;
		public $notification_email_address = '';
		
		public $json_mode = DUP_PRO_JSON_Mode::PHP;
		public $installer_base_name = 'installer.php';
		
		public $max_default_store_files = 0;
		
		public $last_system_check_timestamp = 0;
		
		const GLOBAL_NAME = 'dup_pro_global';
        
        public static function initialize_plugin_data()
        {
            $globals = parent::get_by_type(get_class());
            /* @var $globals DUP_PRO_Global_Entity */

            if (count($globals) == 0)
            {
                $global = new DUP_PRO_Global_Entity();

                $max_execution_time = ini_get("max_execution_time");

                if (empty($max_execution_time))
                {
                    $max_execution_time = 30;
                }

				// Default is just a bit under the .7 max
                $global->php_max_worker_time_in_sec = (int) (0.6 * (float) $max_execution_time);
                
                if($global->php_max_worker_time_in_sec > 18)
                {
                    // Cap it at 18 as a starting point since there have been some oddities experienced on a couple servers
                    $global->php_max_worker_time_in_sec = 18;
                }
                                
                $global->set_build_mode();
                $global->license_expiration_time = time() - 10;  // Ensure it expires right away

                $global->custom_ajax_url = admin_url('admin-ajax.php', 'http');

                $global->save();
            }
        }
		
		public function set_build_mode()
		{
			$is_shellexec_zip_available = (DUP_PRO_Util::get_zip_filepath() != null);
                
			// If unconfigured go with auto logic
			// If configured for shell exec verify that mode exists otherwise slam it back
			
			if(($this->archive_build_mode == DUP_PRO_Archive_Build_Mode::Unconfigured) || ($this->archive_build_mode == DUP_PRO_Archive_Build_Mode::Auto)) 
			{
				if($is_shellexec_zip_available)
				{
					$this->archive_build_mode = DUP_PRO_Archive_Build_Mode::Shell_Exec;
				}
				else
				{
					$this->archive_build_mode = DUP_PRO_Archive_Build_Mode::ZipArchive;
				}
			}
			else if ($this->archive_build_mode == DUP_PRO_Archive_Build_Mode::Shell_Exec)
			{
				if(!$is_shellexec_zip_available)
				{
					$this->archive_build_mode = DUP_PRO_Archive_Build_Mode::ZipArchive;
				}				
			}
		}
		
        public function save()
        {
			$result = false;
            $this->encrypt();
            $result = parent::save();
            $this->decrypt();   // Whenever its in memory its unencrypted
			return $result;
        }
        
		// Change settings that may need to be changed because we have restored to a different system
		public function adjust_settings_for_system()				
		{		
			$save_required = false;
			
			$max_execution_time = ini_get("max_execution_time");

			if(empty($max_execution_time))
			{
				$max_execution_time = 30;
				DUP_PRO_U::log("xxxx 1");
			}

			$max_worker_time = (int)(0.7 * (float)$max_execution_time);
						
			if($this->php_max_worker_time_in_sec > $max_worker_time)
			{				
				DUP_PRO_U::log("Max worker time is set to {$this->php_max_worker_time_in_sec} so overriding to $max_worker_time");
		
				$this->php_max_worker_time_in_sec = $max_worker_time;
				
				$save_required = true;
			}
			
			if($save_required)
			{
				$this->save();
			}
		}
				
        private function encrypt()
        {
            /* @var $storage DUP_PRO_Storage_Entity */
            if (!empty($this->basic_auth_password))
            {
                $this->basic_auth_password = DUP_PRO_U::encrypt($this->basic_auth_password);
            }
        }

        private function decrypt()
        {
            /* @var $storage DUP_PRO_Storage_Entity */
            if (!empty($this->basic_auth_password))
            {
                $this->basic_auth_password = DUP_PRO_U::decrypt($this->basic_auth_password);
            }
        }

        /*
         * @return DUP_PRO_Global_Entity
         */

        public static function &get_instance()
        {
			// RSR TODO: Uncomment when we put in the efficiency fixes
			if(isset($GLOBALS[self::GLOBAL_NAME]) == false)
			{			
				/* @var $global DUP_PRO_Global_Entity */
				$global = null;
			
				$globals = DUP_PRO_JSON_Entity_Base::get_by_type(get_class());

				if (count($globals) > 0)
				{
					$global = $globals[0];                

					$global->decrypt();
				}
				else
				{
					DUP_PRO_U::log_error("Global entity is null!");
				}
				
				$GLOBALS[self::GLOBAL_NAME] = $global;
			}

            return $GLOBALS[self::GLOBAL_NAME];
        }

        public function configure_dropbox_transfer_mode()
        {
            if($this->dropbox_transfer_mode == DUP_PRO_Dropbox_Transfer_Mode::Unconfigured)
            {
                $is_curl_available = DUP_PRO_U::is_curl_available();
                $is_fopen_url_enabled = DUP_PRO_U::is_url_fopen_enabled();

                if($is_curl_available)
                {
                    $this->dropbox_transfer_mode = DUP_PRO_Dropbox_Transfer_Mode::cURL;
                }
                else
                {
                    if($is_fopen_url_enabled)
                    {
                        $this->dropbox_transfer_mode = DUP_PRO_Dropbox_Transfer_Mode::FOpen_URL;
                    }
                    else
                    {
                        $this->dropbox_transfer_mode = DUP_PRO_Dropbox_Transfer_Mode::Disabled;
                    }
                }

                $this->save();
            }
        }
		
		public function get_installer_backup_filename()
		{
			$installer_extension = pathinfo($this->installer_base_name, PATHINFO_EXTENSION);
			if(trim($installer_extension) == '')
			{		
				return 'installer-backup';
			}
			else
			{
				return "installer-backup.$installer_extension";
			}
		}
    }

}
?>
