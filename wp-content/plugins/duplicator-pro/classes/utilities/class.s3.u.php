<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/utilities/class.u.php');

// S3 Notes
// Object key is a unique name within the bucket up to 1024 characters ong - you would put full path in here
// Client specifies region and bucket is in a region - unknown ramifications of making these different
// Need to do the following from user
//	* Create bucket if not exists (checkbox)
//	* Path within bucket [first part of object key]
//  * Region for client
//  * Access keys (recommend they create new ones with limited functionality - in the future we could use master user  to create sub users so we don't have access to their entire account)
//  * Storage class - Standard, Stnd1ard/Infrequent Access, Reduced Redundancy
//  * Important metadata: Date (creation date)
//  * Note ALL keys should not be prefixed with / but look like a relative path
//  Test information
//		test.snapcreek.com
//		user:bob 
//		Access Key ID: AKIAJ2AVLUPKYIFP5V3Q
//		Secret Access Key: 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

if (DUP_PRO_U::PHP53())
{		
	require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'aws/aws-autoloader.php');

	if (!class_exists('DUP_PRO_S3_Client_UploadInfo'))
	{
		class DUP_PRO_S3_Client_UploadInfo
		{
			const UploadPartSizeBytes = 2097152;
						
			public $next_offset = 0;
			public $error_details = null;
			public $is_complete = false;
			public $upload_id = '';
			public $parts = array();
			public $part_number = 1;
			public $src_filepath;
			public $bucket;
			public $dest_directory;
			public $upload_part_size = self::UploadPartSizeBytes;
			public $storage_class;
			
			public function get_key()
			{
				$trimmed_dir = trim($this->dest_directory, '/');
				$basename = basename($this->src_filepath);
				
				return "$trimmed_dir/$basename";
			}
		}
	}

	if (!class_exists('DUP_PRO_S3_U'))
	{
		class DUP_PRO_S3_U
		{			
			public static function delete_file($s3_client, $bucket, $remote_filepath)
			{
				/* @var $s3_client Aws\S3\S3Client */
				// Object key = $remote_filepath

				$success = false;

				try
				{
					/* @var $result Guzzle\Service\Resource\Model */
					$result = $s3_client->deleteObject(array('Bucket' => $bucket, 'Key' => $remote_filepath));
					
					$delete_marker = ((bool)$result->get('DeleteMarker') ? 'true' : 'false');
											
					// RSR TODO
					DUP_PRO_U::log("Delete of S3 file $remote_filepath succeeded delete marker = $delete_marker");
				}
				catch (Exception $ex)
				{
					DUP_PRO_U::log("Exception when trying to delete S3 file $remote_filepath in bucket $bucket");
				}

				return $success;
			}

			// Retrieve files in a given directory orderd by creation date
			public static function get_files_in_directory($s3_client, $remote_parent_directory)
			{
				$remote_file_paths = null;
				
				// RSR TODO

				return $remote_file_paths;
			}		
			
			public static function get_active_multipart_uploads($s3_client, $bucket, $storage_folder)
			{
				/* @var $s3_client Aws\S3\S3Client */
				
				$results = false;
												
				try
				{
					$dirname = trim($storage_folder, '/') . '/';
					
					$return_val = $s3_client->listMultipartUploads(array(
						'Bucket' => $bucket,
						'Delimiter' => '/',
						'Prefix' => $dirname
					));
										
					$results = array();
					foreach($return_val['Uploads'] as $upload)
					{
						$result = new stdClass();
						$result->upload_id = $upload['UploadId'];
						$result->key = $upload['Key'];
						$result->timestamp = strtotime($upload['Initiated']);
						
						$results[] = $result;
					}
				} 
				catch (Exception $ex) 
				{
					DUP_PRO_U::log("Exception when retrieving multipart uploads in bucket $bucket:" . $ex->getMessage());
				}
			
				return $results;
			}
			
			public static function abort_multipart_upload($s3_client, $bucket, $key, $upload_id)
			{
				try
				{
					DUP_PRO_U::log("Aborting multipart upload $upload_id");
					$s3_client->abortMultipartUpload(array(
						'Bucket' => $bucket,
						'Key' => $key,
						'UploadId' => $upload_id
					));
				} 
				catch (Exception $ex) 
				{
					DUP_PRO_U::log("Exception when aborting multipart upload $upload_id in bucket $bucket:" . $ex->getMessage());
				}
			}

			// Upload a file all in one shot
			// returns true/false for success/failure
			public static function upload_file($s3_client, $bucket, $src_filepath, $remote_directory, $storage_class)
			{
				/* @var $s3_client Aws\S3\S3Client */

				// storage classes: s3 standard, s3 infrequent access, reduced redundency

				$success = false;
				
				try
				{
					$filename = basename($src_filepath);
					$key = trim($remote_directory, '/');
					$key = "$key/$filename";
					
					DUP_PRO_U::log("Bucket: $bucket, Key:$key SouceFile:$src_filepath StorageClass:$storage_class");
					$result = $s3_client->putObject(array(
						'Bucket' => $bucket,
						'Key' => $key,
						'SourceFile' => $src_filepath,
						'ACL' => 'bucket-owner-full-control',
						'StorageClass' => $storage_class,						
					));
					
					DUP_PRO_U::log_object('result', $result);
					
					$success = true;
				}
				catch (Exception $ex)
				{
					DUP_PRO_U::log("Error uploading $src_file_path to S3. Exception:" . $ex);
				}

				return $success;
			}
			
			// Will either upload it successfully or populate $upload_info->error_details
			public static function upload_file_chunk($s3_client, &$s3_client_uploadinfo,  $max_upload_time_in_sec = 15, $server_load_delay = 0)
			{
				/* @var $s3_client Aws\S3\S3Client */
				/* @var $s3_client_uploadinfo DUP_PRO_S3_Client_UploadInfo */
													
				try
				{
					if (file_exists($s3_client_uploadinfo->src_filepath) == false)
					{
						$message = "{$s3_client_uploadinfo->src_filepath} doesn't exist!";
						
						DUP_PRO_U::log($message);
						
						$s3_client_uploadinfo->error_details = $message;
						
						return $s3_client_uploadinfo;
					}
					
					if($s3_client_uploadinfo->upload_id == '')
					{
						try
						{
							$response = $s3_client->createMultipartUpload(array(
								'Bucket' => $s3_client_uploadinfo->bucket,
								'Key' => $s3_client_uploadinfo->get_key(),
								'StorageClass' => $s3_client_uploadinfo->storage_class
							));
							
							$s3_client_uploadinfo->upload_id = $response['UploadId'];							
							
							return $s3_client_uploadinfo;
						} 
						catch (Exception $ex) 
						{
							$message = DUP_PRO_U::__("Problem starting multipart upload from {$s3_client_uploadinfo->src_filepath} to {$s3_client_uploadinfo->dest_directory} in bucket {$s3_client_uploadinfo->bucket}") . $ex->getMessage();

							DUP_PRO_U::log($message);
							$s3_client_uploadinfo->error_details = $message;
							
							return $s3_client_uploadinfo;
						}
					}

					// Upload the various parts. 
					$handle = fopen($s3_client_uploadinfo->src_filepath, "rb");
					$filesize = filesize($s3_client_uploadinfo->src_filepath);
										
					if ($handle != false)
					{
						fseek($handle, $s3_client_uploadinfo->next_offset);
						
						$start_time = time();
						$time_passed = 0;
													
						while (!$s3_client_uploadinfo->is_complete && !feof($handle) && ($time_passed < $max_upload_time_in_sec))
						{
							usleep($server_load_delay);
																					
							$amount_left = $filesize - $s3_client_uploadinfo->next_offset;
							
							if($amount_left > $s3_client_uploadinfo->upload_part_size)
							{
								$read_amount = $s3_client_uploadinfo->upload_part_size;
							}
							else
							{
								$read_amount = $amount_left;
							}
							
							DUP_PRO_U::log_object("About to upload part {$s3_client_uploadinfo->part_number} with read amount $read_amount at offset {$s3_client_uploadinfo->next_offset}");
							
							// RSR TODO Upload a part
							$response = $s3_client->uploadPart(array(
								
								'Bucket' => $s3_client_uploadinfo->bucket,
								'Key' => $s3_client_uploadinfo->get_key(),
								'UploadId' => $s3_client_uploadinfo->upload_id,
								'PartNumber' => $s3_client_uploadinfo->part_number,
								'Body' => fread($handle, $read_amount),								
							));

							$s3_client_uploadinfo->parts[] = array(
								'PartNumber' => $s3_client_uploadinfo->part_number++,
								'ETag' => trim($response['ETag'],'"')
							);
																							
							$s3_client_uploadinfo->next_offset += $s3_client_uploadinfo->upload_part_size;
							
							if($s3_client_uploadinfo->next_offset < $filesize)
							{								
								fseek($handle, $s3_client_uploadinfo->next_offset);
							}
							else 
							{
								$s3_client_uploadinfo->is_complete = true;								
							}
							
							$time_passed = time() - $start_time;
						}

						if ($s3_client_uploadinfo->is_complete)
						{
							DUP_PRO_U::log("S3 transfer is complete!");
							
							// Correct the parts array since the etags have problems being stored with quotes
							
							$fixed_parts = array();
							
							foreach($s3_client_uploadinfo->parts as $part)
							{
								if(is_array($part))
								{
									$fixed_part['PartNumber'] = $part['PartNumber'];
									$fixed_part['ETag'] = '"' . $part['ETag'] . '"';
								}
								else
								{
									$fixed_part['PartNumber'] = $part->PartNumber;
									$fixed_part['ETag'] = '"' . $part->ETag . '"';
								}
								
								$fixed_parts[] = $fixed_part;
							}
							
							try
							{
								DUP_PRO_U::log_object("complete multipart $s3_client_uploadinfo->bucket {$s3_client_uploadinfo->get_key} $s3_client_uploadinfo->upload_id", $fixed_parts);
								$result = $s3_client->completeMultipartUpload(array(
									'Bucket'   => $s3_client_uploadinfo->bucket,
									'Key'      => $s3_client_uploadinfo->get_key(),
									'UploadId' => $s3_client_uploadinfo->upload_id,
									'Parts'    => $fixed_parts
									));
								
								DUP_PRO_U::log_object('Completed multipart upload', $result);
							} 
							catch (Exception $ex) 
							{
								$message = DUP_PRO_U::__("Problem uploading multipart upload from {$s3_client_uploadinfo->src_filepath} to {$s3_client_uploadinfo->dest_directory} in bucket {$s3_client_uploadinfo->bucket}") . $ex->getMessage();

								DUP_PRO_U::log($message);
								$s3_client_uploadinfo->error_details = $message;
							}
						}

						fclose($handle);
					}
					else
					{
						$s3_client_uploadinfo->error_details = "Error opening $s3_client_uploadinfo->src_filepath";
					}
				}
				catch (Exception $ex)
				{
					$s3_client_uploadinfo->error_details = "Error uploading to S3: " . $ex->getMessage();
				}				

				return $s3_client_uploadinfo;
			}
					
			static function download_file($s3_client, $bucket, $remote_directory, $remote_filename, $local_filepath, $overwrite_local = true)
			{
				/* @var $s3_client S3Client */
				$success = false;
				
				DUP_PRO_U::log("1");
				if ($overwrite_local || (file_exists($local_filepath) === false))				
				{				
				DUP_PRO_U::log("2");	
					$trimmed_dir = trim($remote_directory, '/');
					$key = "$trimmed_dir/$remote_filename";				
	
					DUP_PRO_U::log("bucket: $bucket key:$key saveas:$local_filepath");
					try
					{
						$result = $s3_client->getObject(array(
							'Bucket' => $bucket,
							'Key'    => $key,
							'SaveAs' => $local_filepath
						));	
						
						DUP_PRO_U::log_object('result', $result);
						
						$success = true;
					} 
					catch (Exception $ex) 
					{
						$message = DUP_PRO_U::__("Problem downloading $key in bucket $bucket and saving to $local_filepath") . $ex->getMessage();
						
						DUP_PRO_U::log($message);
					}
				}
				else
				{
					DUP_PRO_U::log("3");
					DUP_PRO_U::log("Attempted to download a file to $local_filepath but that file already exists!");
				}

				DUP_PRO_U::log("4");
				return $success;
			}

			public static function get_s3_client($region, $access_key, $secret_key)
			{				
				$client = Aws\S3\S3Client::factory(array(
					
					'version'     => 'latest',
					'region'      => $region,
					'credentials' => array('key' => $access_key, 'secret' => $secret_key),
				));
				
				return $client;
			}			
		}
	}
}