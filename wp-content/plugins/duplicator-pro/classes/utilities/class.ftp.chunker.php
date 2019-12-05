<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


if (!class_exists('DUP_PRO_FTP_UploadInfo'))
{
    class DUP_PRO_FTP_UploadInfo
    {
        public $next_offset;
        public $error_details = null;
        public $success = false;

    }

}
/**
 * Description of cls-ftp-chunker
 *
 */
class DUP_PRO_FTP_Chunker
{
    public $server;
    public $port = 21;
    public $username;
    public $password;
    public $timeout_in_sec = 90;
    public $ssl = false;
    public $passive_mode = false;
    public $echo = false;   
    public $ftp_connection_id = false;
    public $ftp_login_result = false;

    public function __construct($server, $port = 21, $username = 'anonymous', $password = 'anonymous@gmail.com', $timeout_in_sec = 15, $ssl = false, $passive_mode = false)
    {
        $this->server = $server;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->timeout_in_sec = $timeout_in_sec;
        $this->ssl = $ssl;
        $this->passive_mode = $passive_mode;
    }
    
    public function open()
    {
        $opened = false;
        
        $this->close();
        
        if($this->ssl)
        {
            if(function_exists('ftp_ssl_connect'))
            {
                DUP_PRO_U::log("Attempting to open ssl connection");
                $this->ftp_connection_id = ftp_ssl_connect($this->server, $this->port, $this->timeout_in_sec);                
            }
            else
            {
                DUP_PRO_U::log_error("Attempted to open FTP SSL connection when OpenSSL hasn't been statically built into this PHP install.");
                return false;
            }
        }
        else
        {
            $this->ftp_connection_id = ftp_connect($this->server, $this->port, $this->timeout_in_sec);
        }
        
        
        if ($this->ftp_connection_id !== false)
        {
            $message = sprintf(DUP_PRO_U::__('Successfully FTP connected to %1$s:%2$d'), $this->server, $this->port);
            DUP_PRO_U::log($message);
            
            $this->ftp_login_result = ftp_login($this->ftp_connection_id, $this->username, $this->password);

            if($this->ftp_login_result)
            {
                $message = sprintf(DUP_PRO_U::__('Logged user %1$s into %2$s'), $this->username, $this->server);
                DUP_PRO_U::log($message);
                
                if($this->passive_mode)
                {
                    if(ftp_pasv($this->ftp_connection_id, true))
                    {
                        DUP_PRO_U::log('Set connection to passive');
                        $opened = true;
                    }   
                    else
                    {
                        DUP_PRO_U::log_error("Couldn't set the connection into passive mode." . $this->get_info());
                    }                    
                }
                else
                {
                    $opened = true;                
                }
            }
            
            if ($this->ftp_login_result == false)
            {
                $message = sprintf(DUP_PRO_U::__('Error logging in user %1$s into %2$s'), $this->username, $this->server);
                DUP_PRO_U::log($message);
                //rsr todo set an error so we dont get stuck in a loop
     //           $ftp_upload_info->error_details = message;
            }            
        }
        else
        {
            $message = sprintf(DUP_PRO_U::__('Error connecting to FTP server %1$s:%2$d'), $this->server, $this->port);
            DUP_PRO_U::log($message);
            //rsr todo set an error so we dont get stuck in a loop
        //    $ftp_upload_info->error_details = message;
        }     
        
        return $opened;
    }
    
    public function create_directory($directory)
    {
        // function//function ftp_mksubdirs($ftpcon,$ftpbasedir,$ftpath){
       // @ftp_chdir($ftpcon, $ftpbasedir); // /var/www/uploads
        $parts = explode('/',$directory); // 2013/06/11/username
       
        foreach($parts as $part)
        {
            if(trim($part) != '')
            {
                if(!@ftp_chdir($this->ftp_connection_id, $part))
                {                
                    @ftp_mkdir($this->ftp_connection_id, $part);
                    @ftp_chdir($this->ftp_connection_id, $part);
                    //ftp_chmod($ftpcon, 0777, $part);
                }
            }
        }
        
        return @ftp_chdir($this->ftp_connection_id, $directory);
    }
    
    public function directory_exists($directory)
    {
        return @ftp_chdir($this->ftp_connection_id, $directory);
    }
    
    public function close()
    {
        $closed = false;
        
        if ($this->ftp_connection_id !== false)
        {
            DUP_PRO_U::log_object("closing ftp connection", $this->ftp_connection_id);
            $closed = ftp_close($this->ftp_connection_id);
        }
        else
        {
            $closed = true;
        }
        
        return $closed;
    }
    
    public function is_opened()
    {
        return (($this->ftp_connection_id != false) && ($this->ftp_login_result == true));
    }

    public function upload_file($source_filepath, $storage_folder)
    {
        $uploaded = false;
        
        if($this->is_opened())
        {
			
            $offset = 0;

            $timeout = 15;

            $start_time = time();

            while (!$uploaded)
            {
                DUP_PRO_U::log("file calling upload chunk offset=$offset");

                /* @var $ftp_upload_info DUP_PRO_FTP_UploadInfo */
                $ftp_upload_info = $this->upload_chunk($source_filepath, $storage_folder, $timeout, $offset);

                DUP_PRO_U::log("after upload chunk file");

                $offset = $ftp_upload_info->next_offset;

                if ($ftp_upload_info->success)
                {
                    DUP_PRO_U::log("1");
                    $uploaded = true;
                }
                else if ($ftp_upload_info->error_details != null)
                {
                    DUP_PRO_U::log_error("Error uploading $source_filepath to $storage_folder: $ftp_upload_info->error_details");
                    break;
                }
                else if (time() - $start_time >= $timeout)
                {
                    DUP_PRO_U::log_error("File transfer timed out");
                }
                else
                {
                    DUP_PRO_U::log("2");
                 //   $offset = $ftp_upload_info->next_offset;
                }
            }
        }
        else
        {
            DUP_PRO_U::log_error("Tried to upload file when connection wasn't opened. Info:" . $this->get_info());
        }

        return $uploaded;
    }

    public function upload_chunk($source_filepath, $storage_folder, $max_upload_time_in_sec = 15, $offset = 0, $server_load_delay = 0)
    {
        /* @var $ftp_upload_info DUP_PRO_FTP_UploadInfo */
        $ftp_upload_info = new DUP_PRO_FTP_UploadInfo();
            
        if($this->is_opened())
        {            
            $start_time = time();

            $should_close_ftp = true;

            DUP_PRO_U::log("call upload chunk");

            $local_file_handle = fopen($source_filepath, 'rb'); // rsr was just 'r'

            if ($local_file_handle !== false)
            {
                if(fseek($local_file_handle, $offset) != 0)
                {
                    $error_message = sprintf(DUP_PRO_U::__('Couldnt seek to $offset in %1$s'), $source_filepath);

                    DUP_PRO_U::log($error_message);
                    $ftp_upload_info->error_details = $error_message;
                    $ftp_upload_info->next_offset = $offset;    // RSR new 6/29/15 Have it start this chunk over next time
                    
                    DUP_PRO_U::log("closing local file handle");
                    fclose($local_file_handle);
                    DUP_PRO_U::log("local file handle closed");
                    
                    return $ftp_upload_info;
                }

                $filename = basename($source_filepath);
                $dest_filepath = "$storage_folder/$filename";
				
				if($offset == 0)
				{
					DUP_PRO_U::log("Deleting $dest_filepath before attempting to upload it");
					// Delete any file that may be there already
					$this->delete($dest_filepath);
				}

                $time_passed = time() - $start_time;               

                $ret = ftp_nb_fput($this->ftp_connection_id, $dest_filepath, $local_file_handle, FTP_BINARY, $offset);

                $next_offset = $offset;
                
                while (($ret == FTP_MOREDATA) && ($time_passed < $max_upload_time_in_sec))
                {
                    usleep($server_load_delay);
                    if ($ret != FTP_FAILED)
                    {
                        $next_offset = ftell($local_file_handle);
                    }
                                                           
                    $now = time();
                    $time_passed = time() - $start_time;

                    $ret = ftp_nb_continue($this->ftp_connection_id);
                     //rsr uncomment to slow down ftp DUP_PRO_U::log("next_offset=$next_offset time passed=$time_passed");
                }

                if ($ret == FTP_FAILED)
                {
                    $error_message = sprintf(DUP_PRO_U::__('FTP failed during transfer of %1$s'), $source_filepath);

                    DUP_PRO_U::log($error_message);
                    $ftp_upload_info->error_details = $error_message;
                    $ftp_upload_info->next_offset = $offset;    // RSR new 6/29/15 Have it start this chunk over next time
                }
                else if ($ret == FTP_FINISHED)
                {
                    DUP_PRO_U::log("ftp finished with offset $next_offset");
                    $ftp_upload_info->next_offset = $next_offset;
                        
                    $ftp_size = ftp_size($this->ftp_connection_id, $dest_filepath);
                    $local_size = filesize($source_filepath);
                    
                    if(($ftp_size != -1) && ($ftp_size != $local_size))
                    {
                        $error_message = sprintf(DUP_PRO_U::__('FTP size mismatch for %1$s. Local file=%2$d bytes while server\'s file is %3$d bytes.'), $source_filepath, $local_size, $ftp_size);

                        DUP_PRO_U::log($error_message);
                        $ftp_upload_info->error_details = $error_message;        
                        
                    }
                    else
                    {   
                        DUP_PRO_U::log(sprintf(DUP_PRO_U::__('FTP sizes match for %1$s. Local file=%2$d bytes while server\'s file is %3$d bytes.'), $source_filepath, $local_size, $ftp_size));
                        
                        $ftp_upload_info->success = true;   
                    }
                }
                else
                {
                    DUP_PRO_U::log("timed out so saving off offset");
                    // $ret = FTP_MOREDATA
                    $ftp_upload_info->next_offset = $next_offset;
                    //$should_close_ftp = false;
                }

                DUP_PRO_U::log("closing local file handle");
                fclose($local_file_handle);
                DUP_PRO_U::log("local file handle closed");
            }
            else
            {
                $message = sprintf(DUP_PRO_U::__('Error opening %1$ for FTP'), $source_filepath);
                DUP_PRO_U::log($message);
                $ftp_upload_info->error_details = message;
            }        
        }
        else
        {
            $message = "Tried to upload file when connection wasn't opened. Info:" . $this->get_info();
            
            $ftp_upload_info->error_details = $message;
            DUP_PRO_U::log($message);
        }
        
        // $this->eo('ftp_upload_info', $ftp_upload_info);
        return $ftp_upload_info;
    }
    
    public function get_info()
    {
        $ssl_string = DUP_PRO_U::bool_to_string($this->ssl);
        $passive_string = DUP_PRO_U::bool_to_string($this->passive_mode);
                        
        return sprintf(DUP_PRO_U::__('Server:%1$s Port:%2$d User:%3$s SSL:%4$s Passive:%5$s'), $this->server, $this->port, $this->username, $ssl_string, $passive_string);
    }

    public function get_filelist($directory = '.')
    {
        $items = array();

        if($this->is_opened())
        {
            $parameters = "$directory";
            
            $items =  ftp_nlist($this->ftp_connection_id, $parameters);                   
        }
        else
        {
            $items = false;
            $message = "Tried to upload file when connection wasn't opened. Info:" . $this->get_info();

            DUP_PRO_U::log($message);
        }

        return $items;
    }
    
    public function delete($file_path)
    {
        $ret_val = false;
        
        if($this->is_opened())
        {
            $ret_val = ftp_delete($this->ftp_connection_id, $file_path);
            
            if($ret_val)
            {
                DUP_PRO_U::log("Successfully deleted $file_path from " . $this->server);
            }
        }
        else
        {                 
            DUP_PRO_U::log("Tried to upload file when connection wasn't opened. Info:" . $this->get_info());
        }
        
        return $ret_val;
    }   
    
    // Using straight up downloading right now - not using chunking
    public function download_file($remote_source_filepath, $local, $is_local_directory = true)
    {
        $ret_val = false;

        if($is_local_directory)
        {
            $filename = basename($remote_source_filepath);

            $local_dest_filepath = "$local_storage_folder/$filename";
        }
        else
        {
            $local_dest_filepath = $local;
        }

        $ret_val = ftp_get($this->ftp_connection_id, $local_dest_filepath, $remote_source_filepath, FTP_BINARY);

        if($ret_val == false)
        {
            DUP_PRO_U::log(sprintf("Error downloading $remote_source_filepath into $local_dest_filepath. FTP Info: " . $this->get_info()));
        }
        
        return $ret_val;
    }
}?>