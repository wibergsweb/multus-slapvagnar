<?php
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/utilities/class.u.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/utilities/class.gdrive.u.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/lib/DropPHP/DropboxClient.php');

if(DUP_PRO_U::PHP53())
{
	require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/utilities/class.s3.u.php');	
}

if (!class_exists('DUP_PRO_Web_Service_Execution_Status'))
{
	abstract class DUP_PRO_Web_Service_Execution_Status
	{
		const Pass = 1;
		const Warn = 2;
		const Fail = 3;
		const Incomplete = 4; // Still more to go
		const ScheduleRunning = 5;

	}

}

if (!class_exists('DUP_PRO_Web_Services'))
{
	class DUP_PRO_Web_Services
	{
		public function init()
		{

			$this->add_class_action('wp_ajax_duplicator_pro_package_scan', 'duplicator_pro_package_scan');
			//  $this->add_class_action('wp_ajax_duplicator_pro_package_build', 'duplicator_pro_package_build');
			//  $this->add_class_action('wp_ajax_duplicator_pro_package_process_storage', 'duplicator_pro_package_process_storage');
			$this->add_class_action('wp_ajax_duplicator_pro_package_delete', 'duplicator_pro_package_delete');

			$this->add_class_action('wp_ajax_duplicator_pro_dropbox_get_request_token', 'duplicator_pro_dropbox_get_request_token');
			$this->add_class_action('wp_ajax_duplicator_pro_dropbox_get_access_token', 'duplicator_pro_dropbox_get_access_token');
			$this->add_class_action('wp_ajax_duplicator_pro_dropbox_send_file_test', 'duplicator_pro_dropbox_send_file_test');
			$this->add_class_action('wp_ajax_duplicator_pro_gdrive_send_file_test', 'duplicator_pro_gdrive_send_file_test');
			$this->add_class_action('wp_ajax_duplicator_pro_s3_send_file_test', 'duplicator_pro_s3_send_file_test');

			$this->add_class_action('wp_ajax_duplicator_pro_ftp_send_file_test', 'duplicator_pro_ftp_send_file_test');
			$this->add_class_action('wp_ajax_duplicator_pro_get_storage_details', 'duplicator_pro_get_storage_details');

			$this->add_class_action('wp_ajax_duplicator_pro_get_schedule_infos', 'get_schedule_infos');

			$this->add_class_action('wp_ajax_duplicator_pro_get_package_file', 'get_package_file');
			$this->add_class_action('wp_ajax_duplicator_pro_get_trace_log', 'get_trace_log');
			$this->add_class_action('wp_ajax_duplicator_pro_get_package_statii', 'get_package_statii');


			$this->add_class_action('wp_ajax_duplicator_pro_process_worker', 'process_worker');
			$this->add_class_action('wp_ajax_nopriv_duplicator_pro_process_worker', 'process_worker');

			$this->add_class_action('wp_ajax_nopriv_duplicator_pro_ping', 'ping');

			$this->add_class_action('wp_ajax_duplicator_pro_run_schedule_now', 'run_schedule_now');

			$this->add_class_action('wp_ajax_duplicator_pro_gdrive_get_auth_url', 'get_gdrive_auth_url');

			$this->add_class_action('wp_ajax_duplicator_pro_manual_transfer_storage', 'manual_transfer_storage');

			/* Screen-Specific Web Methods */
			$this->add_class_action('wp_ajax_duplicator_pro_packages_details_transfer_get_package_vm', 'packages_details_transfer_get_package_vm');
			
			/* Granular Web Methods */			
			$this->add_class_action('wp_ajax_duplicator_pro_package_stop_build', 'package_stop_build');
			

			// $this->add_class_action('wp_ajax_duplicator_pro_get_folder_browser_data', 'get_folder_browser_data');
		}

		// todo insert when integrating folder browser
//        function get_folder_browser_data()
//        {  
//            DUP_PRO_U::log("enter");
//            $request = stripslashes_deep($_REQUEST);
//            
//            if((isset($request['dir']) && ($request['dir'] != 'null') && ($request['dir'] != null)))
//            {
//                $dirpath = $request['dir'];
//                DUP_PRO_U::log("using dirpath from request $dirpath");
//            }
//            else
//            {
//                $dirpath = get_home_path();
//                DUP_PRO_U::log("using homepath $dirpath");
//            }
//                         
//            DUP_PRO_U::log_object('request', $_REQUEST);
//            
//            $filenames = scandir($dirpath);
//            
//            natcasesort($filenames);
//            
//            if (count($filenames) > 2)
//            { 
//                /* The 2 accounts for . and .. */
//                echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
//                
//                // All dirs
//                foreach ($filenames as $filename)
//                {
//                    $filepath = $dirpath . $filename;
//                    
//                    if (file_exists($filepath) && ($filename != '.') && ($filename != '..') && is_dir($filepath))
//                    {
//                        echo "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($filepath) . "/\">" . htmlentities($filename) . "</a></li>";
//                    }
//                }
//
//                echo "</ul>";
//            }  
//            
//            die();
//        }


		function process_worker()
		{
			header("HTTP/1.1 200 OK");

			DUP_PRO_U::log("Process worker request");

			DUP_PRO_Package_Runner::process();

			DUP_PRO_U::log("Exiting process worker request");

			echo 'ok';
			exit();
		}

		function ping()
		{
			DUP_PRO_U::log("PING!");
			header("HTTP/1.1 200 OK");
			exit();
		}
		
		function manual_transfer_storage()
		{
			DUP_PRO_U::log("manual transfer storage");
			$request = stripslashes_deep($_REQUEST);

			$package_id = (int) $request['package_id'];
			$storage_id_string = $request['storage_ids'];

			// Do a quick check to ensure
			$storage_ids = explode(',', $storage_id_string);

			DUP_PRO_U::log("package_id $storage_id_string $storage_id_string");

			$report = array();
			$report['succeeded'] = false;
			$report['retval'] = DUP_PRO_U::__('Unknown');
				
			// RSR really should be using a lock here but at worst it would be an annoyance
		//	if(DUP_PRO_Package::get_next_active_package() == null)
			if(DUP_PRO_Package::is_active_package_present() === false)
			{
				$package = DUP_PRO_Package::get_by_id($package_id);

				if ($package != null)
				{
					if (count($storage_ids > 0))
					{
						foreach ($storage_ids as $storage_id)
						{
							if(trim($storage_id) != '')
							{
								DUP_PRO_U::log("Manually transferring package to storage location $storage_id");

								/* @var $upload_info DUP_PRO_Package_Upload_Info */
								DUP_PRO_U::log("No Uploadinfo exists for storage id $storage_id so creating a new one");
								$upload_info = new DUP_PRO_Package_Upload_Info();

								$upload_info->storage_id = $storage_id;

								array_push($package->upload_infos, $upload_info);
							}
							else
							{
								DUP_PRO_U::log("Bogus storage ID sent to manual transfer");
							}
						}

						$package->set_status(DUP_PRO_PackageStatus::STORAGE_PROCESSING);
						$package->timer_start = DUP_PRO_Util::GetMicrotime();

						$report['succeeded'] = true;
						$report['retval'] = null;

						$package->update();
					}
					else
					{
						$message = 'Storage ID count not greater than 0!';
						DUP_PRO_U::log($message);
						$report['retval'] = $message;
					}
				}
				else
				{
					$message = sprintf(DUP_PRO_U::__('Could not find package ID %d!'), $package_id);
					DUP_PRO_U::log($message);
					$report['retval'] = $message;
				}
			}
			else
			{
				DUP_PRO_U::log("Trying to queue a transfer for package $package_id but a package is already active!");
				$report['retval'] = '';	// Indicates not to do the popup
			}

			$json = json_encode($report);

			die($json);
		}

		/**
		 *  DUPLICATOR_PRO_PACKAGE_SCAN
		 *  Returns a json scan report object which contains data about the system
		 *  
		 *  @return json   json report object
		 *  @example	   to test: /wp-admin/admin-ajax.php?action=duplicator_pro_package_scan
		 */
		function duplicator_pro_package_scan()
		{
			header('Content-Type: application/json');
			$global = DUP_PRO_Global_Entity::get_instance();
			DUP_PRO_Util::CheckPermissions('export');
			$json = array();
			$errLevel = error_reporting();

			// Keep the locking file opening and closing just to avoid adding even more complexity
			if ($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock)
			{
				$locking_file = fopen(DUP_PRO_Constants::$LOCKING_FILE_FILENAME, 'c+');
			}
			else
			{
				$locking_file = true;
			}

			if ($locking_file != false)
			{
				if ($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock)
				{
					$acquired_lock = (flock($locking_file, LOCK_EX | LOCK_NB) != false);

					if ($acquired_lock)
					{
						DUP_PRO_U::log("File lock acquired");
					}
					else
					{
						DUP_PRO_U::log("File lock denied");
					}
				}
				else
				{
					$acquired_lock = DUP_PRO_U::get_sql_lock();
				}

				if ($acquired_lock)
				{
					@set_time_limit(0);
					error_reporting(E_ERROR);
					DUP_PRO_Util::InitSnapshotDirectory();

					$package = DUP_PRO_Package::get_temporary_package();
					$package->ID = null;
					$report = $package->scan();

					$report['Status'] = DUP_PRO_Web_Service_Execution_Status::Pass;

					// The package has now been corrupted with directories and scans so cant reuse it after this point
					DUP_PRO_Package::set_temporary_package_member('ScanFile', $package->ScanFile);
					DUP_PRO_Package::tmp_cleanup();
					DUP_PRO_Package::set_temporary_package_member('Status', DUP_PRO_PackageStatus::AFTER_SCAN);

					if ($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock)
					{
						DUP_PRO_U::log("File lock released");
						flock($locking_file, LOCK_UN);
					}
					else
					{
						DUP_PRO_U::release_sql_lock();
					}
				}
				else
				{
					// File is already locked indicating schedule is running
					$report['Status'] = DUP_PRO_Web_Service_Execution_Status::ScheduleRunning;
					DUP_PRO_U::log("Already locked when attempting manual build - schedule running");
					// rsr to put in an error detail in here            
				}
				if ($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock)
				{
					fclose($locking_file);
				}
			}
			else
			{
				// Problem opening the locking file report this is a critical error
				$report['Status'] = DUP_PRO_Web_Service_Execution_Status::Fail;
				DUP_PRO_U::log("Locking file cant be created");
				// rsr to put in an error detail in here
			}

			$json = json_encode($report);
			$json = ($json) ? $json : '{"Status" : 3, "Message" : "Unable to encode to JSON data.  Please validate that no invalid characters exist in your file tree."}';
			error_reporting($errLevel);

			die($json);
		}

		/**
		 *  DUPLICATOR_PRO_PACKAGE_DELETE
		 *  Deletes the files and database record entries
		 *
		 *  @return json   A json message about the action.  
		 * 				   Use console.log to debug from client
		 */
		function duplicator_pro_package_delete()
		{
			DUP_PRO_Util::CheckPermissions('export');

			try
			{
				$json = array();
				$post = stripslashes_deep($_POST);

				$postIDs = isset($post['duplicator_pro_delid']) ? $post['duplicator_pro_delid'] : null;
				$list = explode(",", $postIDs);
				$delCount = 0;

				if ($postIDs != null)
				{
					foreach ($list as $id)
					{
						$package = DUP_PRO_Package::get_by_id($id);
						if ($package->delete())
						{
							$delCount++;
						}
					}
				}
			}
			catch (Exception $e)
			{
				$json['error'] = "{$e}";
				die(json_encode($json));
			}

			$json['ids'] = "{$postIDs}";
			$json['removed'] = $delCount;
			die(json_encode($json));
		}

// DROPBOX METHODS
// <editor-fold>
		function duplicator_pro_dropbox_get_request_token()
		{
			DUP_PRO_Util::CheckPermissions('export');

			try
			{

				$request = stripslashes_deep($_REQUEST);
				$full_access = $request['full_access'] == 'true';

				//this screws things up when returning   DUP_PRO_U::enable_implicit_flush();
				$dropbox = DUP_PRO_Storage_Entity::get_dropbox_client($full_access);
				$json = array();
				$auth_url = $dropbox->BuildAuthorizeUrl();
				$request_token = $dropbox->GetRequestToken();
			}
			catch (Exception $e)
			{
				$json['error'] = "{$e}";
				die(json_encode($json));
			}

			$json['request_token'] = $request_token;
			$json['auth_url'] = $auth_url;

			die(json_encode($json));
		}

		function duplicator_pro_get_storage_details()
		{
			DUP_PRO_Util::CheckPermissions('export');

			try
			{
				$request = stripslashes_deep($_REQUEST);

				$package_id = (int) $request['package_id'];
				$json = array();
				$package = DUP_PRO_Package::get_by_id($package_id);

				if ($package != null)
				{
					$providers = array();
//                    DUP_PRO_U::log_object("upload infos for $package_id are", $providers);

					foreach ($package->upload_infos as $upload_info)
					{
						/* @var $upload_info DUP_PRO_Package_Upload_Info */
						$storage = DUP_PRO_Storage_Entity::get_by_id($upload_info->storage_id);

						/* @var $storage DUP_PRO_Storage_Entity */
						if ($storage != null)
						{
							$storage->storage_location_string = $storage->get_storage_location_string();

							// Dynamic fields
							$storage->failed = $upload_info->failed;
							$storage->cancelled = $upload_info->cancelled;

							// RSR TODO: find a better way of letting them directly go to google drive. This method is much too slow.
//							if($storage->storage_type == DUP_PRO_Storage_Types::GDrive)
//							{
//								$google_client = $storage->get_full_google_client();
//								
//								$google_service_drive = new Google_Service_Drive($google_client);
//								
//								$storage->gdrive_storage_url = DUP_PRO_GDrive_U::get_directory_view_link($google_service_drive, $storage->gdrive_storage_folder);
//								
//								if($storage->gdrive_storage_url == null)
//								{
//									$storage->gdrive_storage_url = '';
//								}
//							}
							// Newest storage upload infos will supercede earlier attempts to the same storage
							$providers[$upload_info->storage_id] = $storage;

							//		array_push($providers, $storage);														
						}
					}



					$json['succeeded'] = true;
					$json['message'] = DUP_PRO_U::__('Retrieved storage information');
					$json['storage_providers'] = $providers;
				}
				else
				{
					$message = sprintf("DUP_PRO_U::__('Unknown package %1$d')", $package_id);

					$json['succeeded'] = false;
					$json['message'] = $message;
					DUP_PRO_U::log_error($message);
					die(json_encode($json));
				}
			}
			catch (Exception $e)
			{
				$json['succeeded'] = false;
				$json['message'] = "{$e}";
				die(json_encode($json));
			}

			die(json_encode($json));
		}

		function duplicator_pro_dropbox_get_access_token()
		{
			DUP_PRO_Util::CheckPermissions('export');

			try
			{
				$request = stripslashes_deep($_REQUEST);

				$request_token = $request['request_token'];
				$full_access = $request['full_access'] == 'true';

				//this screws things up when returning   
				//DUP_PRO_U::enable_implicit_flush();

				$dropbox = DUP_PRO_Storage_Entity::get_dropbox_client($full_access);
				$json = array();

				DUP_PRO_U::log("getting access token");
				$access_token = $dropbox->GetAccessToken($request_token);
				//throw new DropboxException(sprintf('Could not get access token!'));
			}
			catch (Exception $e)
			{
				$json['error'] = "{$e}";
				die(json_encode($json));
			}

			$json['access_token'] = $access_token;
			die(json_encode($json));
		}

		// Returns status: {['success']={message} | ['error'] message}
		function duplicator_pro_ftp_send_file_test()
		{
			DUP_PRO_U::log_object("enter", $_REQUEST);
			DUP_PRO_Util::CheckPermissions('export');

			try
			{
				$source_handle = null;
				$dest_handle = null;

				$request = stripslashes_deep($_REQUEST);

				$storage_folder = $request['storage_folder'];
				$server = $request['server'];
				$port = $request['port'];
				$username = $request['username'];
				$password = $request['password'];
				$ssl = ($request['ssl'] == 1);
				$passive_mode = ($request['passive_mode'] == 1);

				DUP_PRO_U::log("ssl=" . DUP_PRO_U::bool_to_string($ssl));
				$json = array();

				/** -- Store the temp file --* */
				$source_filepath = tempnam(sys_get_temp_dir(), 'DUP');

				DUP_PRO_U::log("Created temp file $source_filepath");
				$source_handle = fopen($source_filepath, 'w');
				$rnd = rand();
				fwrite($source_handle, "$rnd");

				DUP_PRO_U::log("Wrote $rnd to $source_filepath");
				fclose($source_handle);
				$source_handle = null;

				/** -- Send the file -- * */
				$basename = basename($source_filepath);

				/* @var $ftp_client DUP_PRO_FTP_Chunker */
				$ftp_client = new DUP_PRO_FTP_Chunker($server, $port, $username, $password, 15, $ssl, $passive_mode);

				if ($ftp_client->open())
				{
					if (DUP_PRO_U::starts_with($storage_folder, '/') == false)
					{
						$storage_folder = '/' . $storage_folder;
					}

					$ftp_directory_exists = $ftp_client->create_directory($storage_folder);

					if ($ftp_directory_exists)
					{
						if ($ftp_client->upload_file($source_filepath, $storage_folder))
						{
							/** -- Download the file --* */
							$dest_filepath = tempnam(sys_get_temp_dir(), 'DUP');
							$remote_source_filepath = "$storage_folder/$basename";
							DUP_PRO_U::log("About to FTP download $remote_source_filepath to $dest_filepath");

							if ($ftp_client->download_file($remote_source_filepath, $dest_filepath, false))
							{
								$deleted_temp_file = true;

								if ($ftp_client->delete($remote_source_filepath) == false)
								{
									DUP_PRO_U::log_error("Couldn't delete the remote test");
									$deleted_temp_file = false;
								}

								$dest_handle = fopen($dest_filepath, 'r');
								$dest_string = fread($dest_handle, 100);
								fclose($dest_handle);
								$dest_handle = null;

								/* The values better match or there was a problem */
								if ($rnd == (int) $dest_string)
								{
									DUP_PRO_U::log("Files match!");
									if ($deleted_temp_file)
									{
										$json['success'] = DUP_PRO_U::__('Successfully stored and retrieved file');
									}
									else
									{
										$json['error'] = DUP_PRO_U::__("Successfully stored and retrieved file however coudldn't delete the temp file on the server");
									}
								}
								else
								{
									DUP_PRO_U::log_error("mismatch in files $rnd != $dest_string");
									$json['error'] = DUP_PRO_U::__('There was a problem storing or retrieving the temporary file on this account.');
								}
								unlink($source_filepath);
								unlink($dest_filepath);
							}
							else
							{
								$ftp_client->delete($remote_source_filepath);
								$json['error'] = DUP_PRO_U::__('Error downloading file');
							}
						}
						else
						{
							$json['error'] = DUP_PRO_U::__('Error uploading file');
						}
					}
					else
					{
						$json['error'] = DUP_PRO_U::__("Directory doesn't exist");
					}
				}
				else
				{
					$json['error'] = DUP_PRO_U::__('Error opening FTP connection');
				}
			}
			catch (Exception $e)
			{
				if ($source_handle != null)
				{
					fclose($source_handle);
					unlink($source_filepath);
				}

				if ($dest_handle != null)
				{
					fclose($dest_handle);
					unlink($dest_filepath);
				}

				$json['error'] = "{$e}";
				die(json_encode($json));
			}

			die(json_encode($json));
		}

		function duplicator_pro_gdrive_send_file_test()
		{
			DUP_PRO_Util::CheckPermissions('export');

			try
			{
				$source_handle = null;
				$dest_handle = null;

				$request = stripslashes_deep($_REQUEST);

				$storage_id = $request['storage_id'];
				$storage_folder = $request['storage_folder'];

				$json = array();

				/* @var $storage DUP_PRO_Storage_Entity */
				$storage = DUP_PRO_Storage_Entity::get_by_id($storage_id);

				if ($storage != null)
				{
					$source_filepath = tempnam(sys_get_temp_dir(), 'DUP');

					DUP_PRO_U::log("Created temp file $source_filepath");
					$source_handle = fopen($source_filepath, 'w');
					$rnd = rand();
					fwrite($source_handle, "$rnd");
					DUP_PRO_U::log("Wrote $rnd to $source_filepath");
					fclose($source_handle);
					$source_handle = null;

					/** -- Send the file --* */
					$basename = basename($source_filepath);
					$gdrive_filepath = $storage_folder . "/$basename";

					/* @var $google_client Google_Client */
					$google_client = $storage->get_full_google_client();

					DUP_PRO_U::log("About to send $source_filepath to $gdrive_filepath on Google Drive");

					$google_service_drive = new Google_Service_Drive($google_client);

					$directory_id = DUP_PRO_GDrive_U::get_directory_id($google_service_drive, $storage_folder);

					/* @var $google_file Google_Service_Drive_DriveFile */
					$google_file = DUP_PRO_GDrive_U::upload_file($google_client, $source_filepath, $directory_id);

					if ($google_file != null)
					{
						/** -- Download the file --* */
						$dest_filepath = tempnam(sys_get_temp_dir(), 'DUP');

						DUP_PRO_U::log("About to download $gdrive_filepath on Google Drive to $dest_filepath");

						if (DUP_PRO_GDrive_U::download_file($google_client, $google_file, $dest_filepath))
						{
							try
							{
								$google_service_drive = new Google_Service_Drive($google_client);

								$google_service_drive->files->delete($google_file->id);
							}
							catch (Exception $ex)
							{
								DUP_PRO_U::log("Error deleting temporary file generated on Google File test");
							}

							$dest_handle = fopen($dest_filepath, 'r');
							$dest_string = fread($dest_handle, 100);
							fclose($dest_handle);
							$dest_handle = null;

							/* The values better match or there was a problem */
							if ($rnd == (int) $dest_string)
							{
								DUP_PRO_U::log("Files match! $rnd $dest_string");
								$json['success'] = DUP_PRO_U::__('Successfully stored and retrieved file');
							}
							else
							{
								DUP_PRO_U::log_error("mismatch in files $rnd != $dest_string");
								$json['error'] = DUP_PRO_U::__('There was a problem storing or retrieving the temporary file on this account.');
							}
						}
						else
						{
							DUP_PRO_U::log_error("Couldn't download $source_filepath after it had been uploaded");
						}

						unlink($dest_filepath);
					}
					else
					{
						$json['error'] = DUP_PRO_U::__("Couldn't upload file to Google Drive.");
					}

					unlink($source_filepath);
				}
				else
				{
					$json['error'] = "Couldn't find Storage ID $storage_id when performing Google Drive file test";
				}
			}
			catch (Exception $e)
			{
				if ($source_handle != null)
				{
					fclose($source_handle);
					unlink($source_filepath);
				}

				if ($dest_handle != null)
				{
					fclose($dest_handle);
					unlink($dest_filepath);
				}

				$json['error'] = "{$e}";
				die(json_encode($json));
			}

			die(json_encode($json));
		}

		function duplicator_pro_s3_send_file_test()
		{
			DUP_PRO_Util::CheckPermissions('export');

			try
			{
				$source_handle = null;
				$dest_handle = null;

				$request = stripslashes_deep($_REQUEST);

				$storage_folder = $request['storage_folder'];
				$bucket = $request['bucket'];
				$storage_class = $request['storage_class'];
				$region = $request['region'];
				$access_key = $request['access_key'];
				$secret_key = $request['secret_key'];

				$json = array();

				$source_filepath = tempnam(sys_get_temp_dir(), 'DUP');

				DUP_PRO_U::log("Created temp file $source_filepath");
				$source_handle = fopen($source_filepath, 'w');
				$rnd = rand();
				fwrite($source_handle, "$rnd");
				DUP_PRO_U::log("Wrote $rnd to $source_filepath");
				fclose($source_handle);
				$source_handle = null;

				/** -- Send the file --* */
				$filename = basename($source_filepath);

				$s3_client = DUP_PRO_S3_U::get_s3_client($region, $access_key, $secret_key);

				DUP_PRO_U::log("About to send $source_filepath to $storage_folder in bucket $bucket on S3");

				if (DUP_PRO_S3_U::upload_file($s3_client, $bucket, $source_filepath, $storage_folder, $storage_class))
				{
					/** -- Download the file --* */
					$dest_filepath = tempnam(sys_get_temp_dir(), 'DUP');

					DUP_PRO_U::log("About to download $filename on S3 to $dest_filepath");

					//if (DUP_PRO_GDrive_U::download_file($google_client, $google_file, $dest_filepath))
					if(DUP_PRO_S3_U::download_file($s3_client, $bucket, $storage_folder, $filename, $dest_filepath))
					{
						//public static function delete_file($s3_client, $bucket, $remote_filepath)
						$remote_filepath = "$storage_folder/$filename";

						if(DUP_PRO_S3_U::delete_file($s3_client, $bucket, $remote_filepath) == false)
						{	
							DUP_PRO_U::log("Error deleting temporary file generated on S3 File test");
						}

						$dest_handle = fopen($dest_filepath, 'r');
						$dest_string = fread($dest_handle, 100);
						fclose($dest_handle);
						$dest_handle = null;

						/* The values better match or there was a problem */
						if ($rnd == (int) $dest_string)
						{
							DUP_PRO_U::log("Files match! $rnd $dest_string");
							$json['success'] = DUP_PRO_U::__('Successfully stored and retrieved file');
						}
						else
						{
							DUP_PRO_U::log_error("mismatch in files $rnd != $dest_string");
							$json['error'] = DUP_PRO_U::__('There was a problem storing or retrieving the temporary file on this account.');
						}
					}
					else
					{
						DUP_PRO_U::log_error("Couldn't download $source_filepath after it had been uploaded");
					}

					unlink($dest_filepath);
				}
				else
				{
					$json['error'] = DUP_PRO_U::__("Couldn't upload file to S3.");
				}

				unlink($source_filepath);				
			}
			catch (Exception $e)
			{
				if ($source_handle != null)
				{
					fclose($source_handle);
					unlink($source_filepath);
				}

				if ($dest_handle != null)
				{
					fclose($dest_handle);
					unlink($dest_filepath);
				}

				$json['error'] = "{$e}";
				die(json_encode($json));
			}

			die(json_encode($json));
		}
		
		function duplicator_pro_dropbox_send_file_test()
		{
			DUP_PRO_Util::CheckPermissions('export');


			try
			{
				$source_handle = null;
				$dest_handle = null;

				$request = stripslashes_deep($_REQUEST);

				$access_token = $request['access_token'];
				$storage_folder = $request['storage_folder'];

				$full_access = $request['full_access'] == 'true';

				$json = array();

				//this screws things up when returning   
				//DUP_PRO_U::enable_implicit_flush();           

				$source_filepath = tempnam(sys_get_temp_dir(), 'DUP');

				DUP_PRO_U::log("Created temp file $source_filepath");
				$source_handle = fopen($source_filepath, 'w');
				$rnd = rand();
				fwrite($source_handle, "$rnd");
				DUP_PRO_U::log("Wrote $rnd to $source_filepath");
				fclose($source_handle);
				$source_handle = null;



				/** -- Send the file --* */
				$basename = basename($source_filepath);
				$dropbox_filepath = $storage_folder . "/$basename";
				$full_access_string = $full_access ? 'true' : 'false';

				/* @var $$dropbox DropboxClient */
				$dropbox = DUP_PRO_Storage_Entity::get_dropbox_client($full_access);
				DUP_PRO_U::log("About to send $source_filepath to $dropbox_filepath in dropbox");
				$dropbox->SetAccessToken($access_token);
				$dropbox->UploadFile($source_filepath, $dropbox_filepath);

				/** -- Download the file --* */
				$dest_filepath = tempnam(sys_get_temp_dir(), 'DUP');

				DUP_PRO_U::log("About to download $dropbox_filepath in dropbox to $dest_filepath");


				$dropbox_filemeta = $dropbox->DownloadFile($dropbox_filepath, $dest_filepath);
				$dropbox->Delete($dropbox_filemeta);
				$dest_handle = fopen($dest_filepath, 'r');
				$dest_string = fread($dest_handle, 100);
				fclose($dest_handle);
				$dest_handle = null;

				/* The values better match or there was a problem */
				if ($rnd == (int) $dest_string)
				{
					DUP_PRO_U::log("Files match!");
					$json['success'] = DUP_PRO_U::__('Successfully stored and retrieved file');
				}
				else
				{
					DUP_PRO_U::log_error("mismatch in files $rnd != $dest_string");
					$json['error'] = DUP_PRO_U::__('There was a problem storing or retrieving the temporary file on this account.');
				}

				unlink($source_filepath);
				unlink($dest_filepath);
			}
			catch (Exception $e)
			{
				if ($source_handle != null)
				{
					fclose($source_handle);
					unlink($source_filepath);
				}

				if ($dest_handle != null)
				{
					fclose($dest_handle);
					unlink($dest_filepath);
				}

				$json['error'] = "{$e}";
				die(json_encode($json));
			}

			die(json_encode($json));
		}

//        function duplicator_pro_dropbox_file_listing_test()
//        {
//            $json = array();
//            die(json_encode($json));
//        }

		function get_trace_log()
		{
			DUP_PRO_U::log("enter");
			DUP_PRO_Util::CheckPermissions('export');

			$request = stripslashes_deep($_REQUEST);
			$file_path = DUP_PRO_U::get_log_filepath();
			$backup_path = DUP_PRO_U::get_backup_log_filepath();
			$zip_path = DUPLICATOR_PRO_SSDIR_PATH . "/" . DUP_PRO_Constants::ZIPPED_LOG_FILENAME;
			$zipped = DUP_PRO_U::zip_file($file_path, $zip_path);

			if ($zipped && file_exists($backup_path))
			{
				$zipped = DUP_PRO_U::zip_file($backup_path, $zip_path, false);
			}

			if ($zipped)
			{
				$packages = DUP_PRO_Package::get_all();

				foreach ($packages as $package)
				{
					// DUP_PRO_U::log("adding package log to $zip_path");
					/* @var $package DUP_PRO_Package */
					if ($package->add_log_to_zip($zip_path) === false)
					{
						DUP_PRO_U::log("Problems adding package $package->ID log to zip");
					}
				}
			}

			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private", false);
			header("Content-Transfer-Encoding: binary");

			$fp = fopen($zip_path, 'rb');

			if (($fp !== false) && $zipped)
			{
				$zip_filename = basename($zip_path);

				header("Content-Type: application/octet-stream");
				header("Content-Disposition: attachment; filename=\"$zip_filename\";");

				// required or large files wont work
				if (ob_get_length())
				{
					ob_end_clean();
				}

				DUP_PRO_U::log("streaming $zip_path");
				if (fpassthru($fp) === false)
				{
					DUP_PRO_U::log("Error with fpassthru for $zip_path");
				}

				fclose($fp);
				@unlink($zip_path);
			}
			else
			{
				header("Content-Type: text/plain");
				header("Content-Disposition: attachment; filename=\"error.txt\";");
				if ($zipped === false)
				{
					$message = "Couldn't create zip file.";
				}
				else
				{
					$message = "Couldn't open $file_path.";
				}
				DUP_PRO_U::log($message);
				echo $message;
			}

			exit;
		}

		function get_package_file()
		{
			DUP_PRO_U::log("get_package_file()");
			DUP_PRO_Util::CheckPermissions('export');

			$global = DUP_PRO_Global_Entity::get_instance();
			
			$request = stripslashes_deep($_REQUEST);
			$which = (int) $request['which'];
			$package_id = (int) $request['package_id'];

			$package = DUP_PRO_Package::get_by_id($package_id);

			$is_binary = ($which != DUP_PRO_Package_File_Type::Log);
			$file_path = $package->get_local_package_file($which);

			if ($is_binary)
			{
				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Cache-Control: private", false);
				header("Content-Transfer-Encoding: binary");

				if ($file_path != null)
				{
					$fp = fopen($file_path, 'rb');
					if ($fp !== false)
					{
						if ($which == DUP_PRO_Package_File_Type::Installer)
						{
							$file_name = $global->installer_base_name;
						}
						else
						{
							$file_name = basename($file_path);
						}

						header("Content-Type: application/octet-stream");
						header("Content-Disposition: attachment; filename=\"$file_name\";");

						ob_end_clean(); // required or large files wont work
						DUP_PRO_U::log("streaming $file_path");

						if (fpassthru($fp) === false)
						{
							DUP_PRO_U::log("Error with fpassthru for $file_path");
						}
						fclose($fp);
					}
					else
					{
						header("Content-Type: text/plain");
						header("Content-Disposition: attachment; filename=\"error.txt\";");
						$message = "Couldn't open $file_path.";
						DUP_PRO_U::log($message);
						echo $message;
					}
				}
				else
				{
					$message = DUP_PRO_U::__("Couldn't find a local copy of the file requested.");

					header("Content-Type: text/plain");
					header("Content-Disposition: attachment; filename=\"error.txt\";");

					// Report that we couldn't find the file
					DUP_PRO_U::log($message);
					echo $message;
				}
			}
			else
			{
				if ($file_path != null)
				{
					$text = file_get_contents($file_path);
					echo nl2br($text);
				}
				else
				{
					$message = DUP_PRO_U::__("Couldn't find a local copy of the file requested.");
					echo $message;
				}
			}
			exit;
		}
		
		// Stop a package build
		// Input: package_id
		// Output: 
		//			succeeded: true|false
		//			retval: null or error message
		public function package_stop_build()
		{
			$succeeded = false;
			$retval = '';
			$request = stripslashes_deep($_REQUEST);
			$package_id = (int) $request['package_id'];
			
			DUP_PRO_U::log("Web service stop build of $package_id");
			$package = DUP_PRO_Package::get_by_id($package_id);
			
			if ($package != null)
			{
				DUP_PRO_U::log("set $package->ID for cancel");
				$package->set_for_cancel();
				
				$succeeded = true;
			}
			else
			{
				DUP_PRO_U::log("could not find package so attempting hard delete. Old files may end up sticking around although chances are there isnt much if we couldnt nicely cancel it.");
				$result = DUP_PRO_Package::force_delete($package_id);
								
				if($result)
				{
					$message = 'Hard delete success';
					$succeeded = true;
				}
				else
				{
					$message = 'Hard delete failure';
					$succeeded = false;
					$retval = $message;
							
				}
				
				DUP_PRO_U::log($message);
				$succeeded = $result;												
			}			
			
			$json['succeeded'] = $succeeded;
			$json['retval'] = $retval;
			
			die(json_encode($json));
		}

		// Retrieve view model for the Packages/Details/Transfer screen
		// active_package_id: true/false
		// percent_text: Percent through the current transfer
		// text: Text to display
		// transfer_logs: array of transfer request vms (start, stop, status, message)
		function packages_details_transfer_get_package_vm()
		{
			$request = stripslashes_deep($_REQUEST);
			$package_id = (int) $request['package_id'];

			$package = DUP_PRO_Package::get_by_id($package_id);
			
			$json = array();

			$vm = new stdClass();
			
			/*-- First populate the transfer log information --*/
			
			// If this is the package being requested include the transfer details
			$vm->transfer_logs = array();

			$active_upload_info = null;
			
			$storages = DUP_PRO_Storage_Entity::get_all();			

			/* @var $upload_info DUP_PRO_Package_Upload_Info */
			foreach ($package->upload_infos as &$upload_info)
			{
				if ($upload_info->storage_id != DUP_PRO_Virtual_Storage_IDs::Default_Local)
				{
					$status = $upload_info->get_status();
					$status_text = $upload_info->get_status_text();

					$transfer_log = new stdClass();
					
					if($upload_info->get_started_timestamp() == null)
					{						
						$transfer_log->started = DUP_PRO_U::__('N/A');
					}
					else
					{
						$transfer_log->started = DUP_PRO_U::get_local_formatted_time_from_gmt_ticks($upload_info->get_started_timestamp());
					}
					
					if($upload_info->get_stopped_timestamp() == null)
					{
						$transfer_log->stopped = DUP_PRO_U::__('N/A');
					}
					else
					{
						$transfer_log->stopped = DUP_PRO_U::get_local_formatted_time_from_gmt_ticks($upload_info->get_stopped_timestamp());
					}
					
					$transfer_log->status_text = $status_text;
					$transfer_log->message = $upload_info->get_status_message();
					
					$transfer_log->storage_type_text = DUP_PRO_U::__('Unknown');
					/* @var $storage DUP_PRO_Storage_Entity */
					foreach($storages as $storage)
					{
						if($storage->id == $upload_info->storage_id)
						{
							$transfer_log->storage_type_text = $storage->get_type_text();
						}
					}

					array_unshift($vm->transfer_logs, $transfer_log);

					if ($status == DUP_PRO_Upload_Status::Running)
					{
						if ($active_upload_info != null)
						{
							DUP_PRO_U::log("More than one upload info is running at the same time for package {$package->ID}");
						}

						$active_upload_info = &$upload_info;
					}
				}
			}

			/*-- Now populate the activa package information --*/
			
			/* @var $active_package DUP_PRO_Package */
			$active_package = DUP_PRO_Package::get_next_active_package();
			
			if($active_package == null)
			{
				// No active package
				$vm->active_package_id = -1;
				$vm->text = DUP_PRO_U::__('No package is building.');
			}
			else
			{
				$vm->active_package_id = $active_package->ID;
															
				if ($active_package->ID == $package_id)
				{
					//$vm->is_transferring = (($package->Status >= DUP_PRO_PackageStatus::COPIEDPACKAGE) && ($package->Status < DUP_PRO_PackageStatus::COMPLETE));
					if ($active_upload_info != null)
					{
						$vm->percent_text = "{$active_upload_info->progress}%";
						$vm->text = $active_upload_info->get_status_message();
					}
					else
					{
						// We see this condition at the beginning and end of the transfer so throw up a generic message
						$vm->percent_text = "";
						$vm->text = DUP_PRO_U::__("Synchronizing with server...");
					}
				}		
				else
				{
					$vm->text = DUP_PRO_U::__("Another package is presently running.");
				}		
			
				if($active_package->is_cancel_pending())
				{
					// If it's getting cancelled override the normal text
					$vm->text = DUP_PRO_U::__("Cancellation pending...");
				}
			}

			$json['succeeded'] = true;
			$json['retval'] = $vm;
			
			die(json_encode($json));
		}

		static function get_adjusted_package_status($package)
		{
			/* @var $package DUP_PRO_Package */
			if (($package->Status == DUP_PRO_PackageStatus::ARCSTART) && ($package->build_progress->current_build_mode == DUP_PRO_Archive_Build_Mode::Shell_Exec))
			{
				// Amount of time passing before we give them a 1%
				$time_per_percent = 11; // RSR todo gradually increase this 
				$thread_age = time() - $package->build_progress->thread_start_time;
				$total_percentage_delta = DUP_PRO_PackageStatus::ARCDONE - DUP_PRO_PackageStatus::ARCSTART;

				if ($thread_age > ($total_percentage_delta * $time_per_percent))
				{
					// It's maxed out so just give them the done condition for the rest of the time
					return DUP_PRO_PackageStatus::ARCDONE;
				}
				else
				{
					$percentage_delta = (int) ($thread_age / $time_per_percent);

					return DUP_PRO_PackageStatus::ARCSTART + $percentage_delta;
				}
			}
			else
			{
				return $package->Status;
			}
		}

		function get_package_statii()
		{
			DUP_PRO_Util::CheckPermissions('export');
			$request = stripslashes_deep($_REQUEST);
			$packages = DUP_PRO_Package::get_all();
			$package_statii = array();

			foreach ($packages as $package)
			{
				/* @var $package DUP_PRO_Package */
				$package_status = new stdClass();

				$package_status->ID = $package->ID;

				$package_status->status = self::get_adjusted_package_status($package);
				//$package_status->status = $package->Status;
				$package_status->status_progress = $package->get_status_progress();
				$package_status->size = $package->get_display_size();

				$active_storage = $package->get_active_storage();
				if ($active_storage != null)
				{
					$package_status->status_progress_text = $active_storage->get_action_text();
				}
				else
				{
					$package_status->status_progress_text = '';
				}
				array_push($package_statii, $package_status);
			}
			die(json_encode($package_statii));
		}

		// return schedule status'
		// { schedule_id, is_running=true|false, last_ran_string}
		function get_schedule_infos()
		{
			DUP_PRO_Util::CheckPermissions('export');
			$schedules = DUP_PRO_Schedule_Entity::get_all();
			$schedule_infos = array();

			if (count($schedules) > 0)
			{
				$package = DUP_PRO_Package::get_next_active_package();

				foreach ($schedules as $schedule)
				{
					/* @var $schedule DUP_PRO_Schedule_Entity */
					$schedule_info = new stdClass();

					$schedule_info->schedule_id = $schedule->id;
					$schedule_info->last_ran_string = $schedule->get_last_ran_string();

					if ($package != null)
					{
						$schedule_info->is_running = ($package->schedule_id == $schedule->id);
					}
					else
					{
						$schedule_info->is_running = false;
					}

					array_push($schedule_infos, $schedule_info);
				}
			}

			$json_response = json_encode($schedule_infos);
			die($json_response);
		}

		function add_class_action($tag, $method_name)
		{
			return add_action($tag, array($this, $method_name));
		}

		function run_schedule_now()
		{
			DUP_PRO_Util::CheckPermissions('export');
			DUP_PRO_U::log("enter");
			$schedule_id = (int) $_REQUEST['schedule_id'];
			$schedule = DUP_PRO_Schedule_Entity::get_by_id($schedule_id);

			if ($schedule != null)
			{
				DUP_PRO_U::log("Inserting new package for schedule $schedule->name due to manual request");
				// Just inserting it is enough since init() will automatically pick it up and schedule a cron in the near future.
				$schedule->insert_new_package(true);
				DUP_PRO_Package_Runner::kick_off_worker();
				$response['status'] = 0;
			}
			else
			{
				$message = DUP_PRO_U::__("Attempted to queue up a job for non existent schedule $schedule_id");
				DUP_PRO_U::log($message);
				$response['status'] = -1;
			}
			$json_response = json_encode($response);
			die($json_response);
		}

		function get_gdrive_auth_url()
		{
			$response = array();
			$response['status'] = -1;

			if (DUP_PRO_U::PHP53())
			{
				$google_client = DUP_PRO_GDrive_U::get_raw_google_client();

				$response['gdrive_auth_url'] = $google_client->createAuthUrl();
				$response['status'] = 0;
			}
			else
			{
				DUP_PRO_U::log("Attempt to call a google client method when server is not PHP 5.3!");
				$response['status'] = -2;
			}

			$json_response = json_encode($response);

			die($json_response);
		}

	}

}
//todo duplicator_pro_dropbox_file_listing_test
// </editor-fold>
//DO NOT ADD A CARRIAGE RETURN BEYOND THIS POINT (headers issue)!!
?>
