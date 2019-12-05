<?php
if (!defined('DUPLICATOR_PRO_VERSION'))
	exit; // Exit if accessed directly

require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/entities/class.global.entity.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/entities/class.storage.entity.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/utilities/class.u.php');

if(DUP_PRO_U::PHP53())
{
	require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/utilities/class.s3.u.php');	
}

if (!class_exists('DUP_PRO_System_Checker'))
{	
	class DUP_PRO_System_Checker
	{
		const SYSTEM_CHECK_PERIOD_IN_SEC = 86400;		// 24 hours
		
		public static function check()
		{
			// Need 5.3 for AWS
			if(DUP_PRO_U::PHP53())
			{
				/* @var $global DUP_PRO_Global_Entity */
				$global = DUP_PRO_Global_Entity::get_instance();
				$time = time();

				if(($time - $global->last_system_check_timestamp) >= self::SYSTEM_CHECK_PERIOD_IN_SEC)
				{
					DUP_PRO_U::log("Doing system checker check because time = $time and last timestamp = {$global->last_system_check_timestamp}" );
					try
					{
						self::purge_old_s3_multipart_uploads();
					}
					catch(Exception $ex)
					{
						DUP_PRO_U::log("Got exception during s3 system check: " . $ex->getMessage());
					}

					$global->last_system_check_timestamp = time();
					$global->save();
				}
			}
		}
		
		private static function purge_old_s3_multipart_uploads()
		{
			$storages = DUP_PRO_Storage_Entity::get_all();
				
			foreach($storages as $storage)
			{
				/* @var $storage DUP_PRO_Storage_Entity */
				if($storage->storage_type == DUP_PRO_Storage_Types::S3)
				{
					$s3_client = $storage->get_full_s3_client();
					
					$active_uploads = DUP_PRO_S3_U::get_active_multipart_uploads($s3_client, $storage->s3_bucket, $storage->s3_storage_folder);				
					
					foreach($active_uploads as $active_upload)
					{
						// Needs to be at least 48 hours old - don't want to much around with timezone so this is safe
						$time_delta = time() - $active_upload->timestamp;
						
						if($time_delta > (48 * 3600))
						{
							DUP_PRO_U::log("Aborting upload because timestamp = {$active_upload->timestamp} while time is " . time());
							DUP_PRO_S3_U::abort_multipart_upload($s3_client, $storage->s3_bucket, $active_upload->key, $active_upload->upload_id);
						}
					}
				}
			}
		}
	}

}
?>