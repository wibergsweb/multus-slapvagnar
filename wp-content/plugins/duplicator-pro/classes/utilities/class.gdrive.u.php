<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/utilities/class.u.php');

if (DUP_PRO_U::PHP53())
{
	require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'lib/google/apiclient/src/Google/autoload.php');
	require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'lib/google/class.enhanced.google.media.file.upload.php');

	if (!class_exists('DUP_PRO_GDriveClient_UploadInfo'))
	{
		class DUP_PRO_GDriveClient_UploadInfo
		{
			public $resume_uri = '';
			public $next_offset = 0;
			public $error_details = null;
			public $is_complete = false;
		}
	}

	if (!class_exists('DUP_PRO_GDrive_U'))
	{
		class DUP_PRO_GDrive_U
		{
			const RedirectUri = 'urn:ietf:wg:oauth:2.0:oob'; // Special URI indicating that user should copy and paste
			const UploadChunkSizeBytes = 2097152;

			static $Scopes;

			public static function init()
			{
				// The drive.file scope limits access to just those files created by the plugin
				self::$Scopes = array('https://www.googleapis.com/auth/drive.file', 'profile', 'email');
			}

			public static function get_directory_view_link($google_service_drive, $directory)
			{
				$directory_id = DUP_PRO_GDrive_U::get_directory_id($google_service_drive, $directory);

				if($directory_id != null)
				{
					$directory_metadata = DUP_PRO_GDrive_U::get_file_metadata_by_id($google_service_drive, $directory_id);

					if ($directory_metadata != null)
					{
						DUP_PRO_U::log("Directory link = " . $directory_metadata->alternateLink);

						return $directory_metadata->alternateLink;
					}
					else
					{
						DUP_PRO_U::log("Directory link for $directory not found");

						return null;
					}
				}
				else
				{
					DUP_PRO_U::log("Directory id for $directory not found");
					return null;
				}
			}

			public static function get_file_metadata_by_id($google_service_drive, $file_id)
			{
				$file = null;

				try
				{
					$file_metadata = $google_service_drive->files->get($file_id);
				}
				catch (Exception $ex)
				{
					DUP_PRO_U::log("Problems retrieving metadata for file $file_id");
				}

				return $file_metadata;
			}

			public static function delete_file($google_service_drive, $file_id)
			{
				$success = false;

				/* @var $google_service_drive Google_Service_Drive */
				try
				{
					$google_service_drive->files->delete($file_id);
					$success = true;
					DUP_PRO_U::log("Delete of Google Drive file $file_id succeeded");
				}
				catch (Exception $ex)
				{
					DUP_PRO_U::log("Exception when trying to delete Google Drive file id $file_id");
				}

				return $success;
			}

			// Retrieve files in a given directory orderd by creation date
			public static function get_files_in_directory($google_service_drive, $directory_id)
			{
				$file_items = null;
				$parameters = array('orderBy' => 'createdDate', 'q' => "'$directory_id' in parents and trashed=false");

				try
				{
					$file_list = $google_service_drive->files->listFiles($parameters);
					$file_items = $file_list->getItems();
				}
				catch (Exception $ex)
				{
					DUP_PRO_U::log("Error retrieving file list for directory ID $directory_id " . $ex->getMessage());
				}

				return $file_items;
			}
			
			public static function get_file($google_service_drive, $filename, $directory_id)
			{
				DUP_PRO_U::log("get_file for $filename $directory_id");
				$file_id = null;

				$file_items = self::get_files_in_directory($google_service_drive, $directory_id);

				if($file_items != null)
				{
					foreach ($file_items as $drive_file)
					{
						/* @var $drive_file Google_Service_Drive_DriveFile */

						$google_filename = $drive_file->getTitle();

						if($google_filename == $filename)
						{
					//		DUP_PRO_U::log_object("File match for $filename", $drive_file);
							$file_id = $drive_file->getId();
							break;
						}
						else
						{
							//DUP_PRO_U::log("file compare $google_filename != $filename");
						}
					}
				}
				else
				{
					DUP_PRO_U::log("files in directory $directory_id are null");
				}
						
				return $file_id;
			}

			public static function get_directory_id($google_service_drive, $path, $autocreate = true)
			{
				$path = str_replace('\\', '/', $path);

				$path = rtrim($path, '/');

				$directory_parts = explode('/', $path);

				$parent_id = 'root';

				try
				{
					foreach ($directory_parts as $subdirectory)
					{
						$parameters = array();

						$parameters['q'] = "'$parent_id' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed=false";

						$file_list = $google_service_drive->files->listFiles($parameters);

						$folder_id = '';

						$items = $file_list->getItems();

						foreach ($items as $drive_file)
						{
							/* @var $drive_file Google_Service_Drive_DriveFile */
							if ($drive_file->title == $subdirectory)
							{
								$folder_id = $drive_file->id;
								break;
							}
							else
							{
								DUP_PRO_U::log("{$drive_file->title} doesnt equal $subdirectory");
							}
						}

						if ($folder_id == '')
						{
							if($autocreate)
							{
								DUP_PRO_U::log("Creating new folder " . $subdirectory);

								// Folder wasn't present so we have to create one
								$folder_file = new Google_Service_Drive_DriveFile();
								$folder_file->setTitle($subdirectory);
								$folder_file->setMimeType('application/vnd.google-apps.folder');
								$folder_file_parent = new Google_Service_Drive_ParentReference();
								$folder_file_parent->setId($parent_id);
								$folder_file->setParents(array($folder_file_parent));

								$created_file = $google_service_drive->files->insert($folder_file, array('mimeType' => 'application/vnd.google-apps.folder'));

								$folder_id = $created_file->id;
							}
							else
							{
								// Doesn't exist
								$parent_id = null;
							}
						}

						$parent_id = $folder_id;
					}
				}
				catch (Exception $ex)
				{
					DUP_PRO_U::log("Got error when trying to get directory id for $path: " . $ex->getMessage());
					$parent_id = null;
				}

				return $parent_id;
			}

			// Upload a file all in one shot
			// returns null if error, Google_Service_Drive_DriveFile if success
			public static function upload_file($google_client, $src_file_path, $parent_file_id)
			{
				/* @var $google_Client Google_Client */

				$drive_file = null;

				/* @var $google_service_drive Google_Service_Drive */
				try
				{
					$mime_type = 'application/octet-stream';

					$google_service_drive = new Google_Service_Drive($google_client);

					$upload_file = new Google_Service_Drive_DriveFile();
					$upload_file->setTitle(basename($src_file_path));

					$upload_file->setMimeType($mime_type);
					$upload_file_parent = new Google_Service_Drive_ParentReference();
					$upload_file_parent->setId($parent_file_id);
					$upload_file->setParents(array($upload_file_parent));

					try
					{
						$data = file_get_contents($src_file_path);

						if ($data !== false)
						{
							//	DUP_PRO_U::log_object("file to upload", $upload_file)
							/* @var $drive_file Google_Service_Drive_DriveFile */
							$drive_file = $google_service_drive->files->insert($upload_file, array('data' => $data, 'uploadType' => 'media'));
						}
						else
						{
							DUP_PRO_U::log("Couldn't read file contents from $src_file_path when attempting Google Drive Upload");
						}
					}
					catch (Exception $ex)
					{
						DUP_PRO_U::log("Exception from Google drive insert of $src_file_path " . $ex->getMessage());
					}

					if (isset($drive_file) == false)
					{
						DUP_PRO_U::log("File returned from Google drive insert of $src_file_path is null.");
					}
				}
				catch (Exception $ex)
				{
					DUP_PRO_U::log("Error uploading $src_file_path to Google Drive");
				}

				return $drive_file;
			}
			
			// Will either upload it successfully or populate $upload_info->error_details
			public static function upload_file_chunk($google_client, $src_file_path, $parent_file_id, $upload_chunk_size = self::UploadChunkSizeBytes, $max_upload_time_in_sec = 15, $next_offset = 0, $resume_uri = null, $server_load_delay = 0)
			{
				/* @var $google_client Google_Client */
				$upload_info = new DUP_PRO_GDriveClient_UploadInfo();
						
				try
				{
					if (file_exists($src_file_path) == false)
					{
						$upload_info->error_details = "$src_file_path doesn't exist!";
					}

					$google_service_drive = new Google_Service_Drive($google_client);

					$google_client->setDefer(true);

					$upload_file = new Google_Service_Drive_DriveFile();
					$upload_file->title = basename($src_file_path);
					$upload_file->setMimeType('application/octet-stream');
					$upload_file_parent = new Google_Service_Drive_ParentReference();
					$upload_file_parent->setId($parent_file_id);
					$upload_file->setParents(array($upload_file_parent));

					$request = $google_service_drive->files->insert($upload_file);

					if ($resume_uri == null)
					{
						$resume_uri = false;
					}

					$media_file_upload = new DUP_Pro_EnhancedGoogleMediaFileUpload($google_client, $request, 'binary/octet-stream', null, true, $upload_chunk_size, false, $next_offset, $resume_uri);

					$media_file_upload->setFileSize(filesize($src_file_path));

					// Upload the various chunks. $status will be false until the process is complete.
					$status = false;
					$handle = fopen($src_file_path, "rb");

					if ($handle != false)
					{
						fseek($handle, $next_offset);

						$start_time = time();
						$time_passed = 0;

						while (!$upload_info->is_complete && !feof($handle) && ($time_passed < $max_upload_time_in_sec))
						{
							usleep($server_load_delay);

							$chunk = self::read_file_chunk($handle, $upload_chunk_size);

							$upload_info->is_complete = ($media_file_upload->nextChunk($chunk) !== false);
							$upload_info->resume_uri = $media_file_upload->resumeUri;
							$upload_info->next_offset = $media_file_upload->getNextOffset();

							fseek($handle, $upload_info->next_offset);

							$time_passed = time() - $start_time;
						}

						if ($upload_info->is_complete)
						{
							DUP_PRO_U::log("Upload info is complete!");
						}

						fclose($handle);
					}
					else
					{
						$upload_info->error_details = "Error opening $src_file_path";
					}
				}
				catch (Exception $ex)
				{
					$upload_info->error_details = "Error uploading to Google Drive: " . $ex->getMessage();
				}

				$google_client->setDefer(false);

				return $upload_info;
			}

			static function download_file($google_client, $google_file, $local_filepath, $overwrite_local = true)
			{
				/* @var $google_client Google_Client */
				/* @var $google_file Google_Service_Drive_DriveFile */
				$success = false;

				if ($overwrite_local || (file_exists($local_filepath) === false))
				{
					$downloadUrl = $google_file->getDownloadUrl();

					if ($downloadUrl)
					{
						$request = new Google_Http_Request($downloadUrl, 'GET', null, null);

						$httpRequest = $google_client->getAuth()->authenticatedRequest($request);

						$response_code = $httpRequest->getResponseHttpCode();

						if ($response_code == 200)
						{
							$file_contents = $httpRequest->getResponseBody();

							if (@file_put_contents($local_filepath, $file_contents) === FALSE)
							{
								DUP_PRO_U::log("Problem writing downloaded file from $remote_filepath to $local_filepath!");
							}
							else
							{
								$success = true;
							}
						}
						else
						{
							DUP_PRO_U::log("When attempting to download $remote_filepath got error code $response_code!");
						}
					}
					else
					{
						DUP_PRO_U::log("Attempted to download $remote_filepath but file doesn't have any content stored on Google Drive!");
					}
				}
				else
				{
					DUP_PRO_U::log("Attempted to download a file to $local_filepath but that file already exists!");
				}

				return $success;
			}

			static function read_file_chunk($handle, $chunk_size)
			{
				$byte_count = 0;
				$giant_chunk = "";

				while (!feof($handle))
				{
					// fread will never return more than 8192 bytes if the stream is read buffered and it does not represent a plain file
					$chunk = fread($handle, 8192);

					$byte_count += strlen($chunk);
					$giant_chunk .= $chunk;

					if ($byte_count >= $chunk_size)
					{
						return $giant_chunk;
					}
				}

				return $giant_chunk;
			}

			// Array(email, familyName, givenName,...)
			public static function get_user_info($google_client)
			{
				$userInfoService = new Google_Service_Oauth2($google_client);
				$userInfo = null;

				try
				{
					$userInfo = $userInfoService->userinfo->get();

					return $userInfo;
				}
				catch (Google_Exception $e)
				{
					DUP_PRO_U::log("Error retrieving user information");
				}

				if ($userInfo != null && $userInfo->getId() == null)
				{
					$userInfo = null;
				}

				return $userInfo;
			}

			public static function get_binary_self_value()
			{
				return 'jfds2!x4';
			}

			public static function get_binary_extraction_value()
			{
				return 'kkd23p';
			}

			public static function get_raw_google_client()
			{
				$client = new Google_Client();

				$sv = self::get_binary_self_value();
				$ev = self::get_binary_extraction_value();

				$ci = DUP_PRO_U::decrypt('EQNJ53++6/40fuF5ke+IaQ==', $sv);
				$cs = DUP_PRO_U::decrypt('ui25chqoBexPt6QDi9qmGg==', $ev);

				$ci = trim($ci);
				$cs = trim($cs);

				if (($ci != $cs) || ($ci != "x93fdf8"))
				{
					$ci = self::get_cj1() . self::get_cj2();
					$cs = self::get_ct1() . self::get_ct2();
				}

				$client->setClientId($ci);
				$client->setAccessType('offline');
				$client->setClientSecret($cs);
				$client->setScopes(self::$Scopes);
				$client->setRedirectUri(self::RedirectUri);

				return $client;
			}

			private static function get_cj1()
			{
				return base64_decode('MTMwOTA5MDkxOTkzLTZlMzFpNHN2cW9uaG9iMmRz');
			}

			private static function get_cj2()
			{
				return base64_decode('a2Zkc2R2cThvbWxnN3RlLmFwcHMuZ29vZ2xldXNlcmNvbnRlbnQuY29t');
			}

			private static function get_ct1()
			{
				return base64_decode('SVltaThQVnlzblFNbGo3');
			}

			private static function get_ct2()
			{
				return base64_decode('dHhuakgzN09t');
			}

		}

		DUP_PRO_GDrive_U::init();
	}
}