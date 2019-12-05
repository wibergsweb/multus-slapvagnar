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
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.schedule.entity.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/class.package.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/class.server.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/class.system.checker.php');

if (!class_exists('DUP_PRO_Package_Runner'))
{
    class DUP_PRO_Package_Runner
    {        
        public static $delayed_exit_and_kickoff = false;
	
        public static function init()
        {			
            $kick_off_worker = false;
            /* @var $global DUP_PRO_Global_Entity */
            $global = DUP_PRO_Global_Entity::get_instance();
           				
            if($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock)
            {
                $locking_file = fopen(DUP_PRO_Constants::$LOCKING_FILE_FILENAME, 'c+');
            }
            else
            {
                $locking_file = true;
            }

            if ($locking_file != false)            
            {                
                if($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock)
                {
                    $acquired_lock = (flock($locking_file, LOCK_EX | LOCK_NB) != false);
                    
                    if($acquired_lock)
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
                
                if($acquired_lock)
                {	
					DUP_PRO_System_Checker::check();					
					
					$pending_cancellations = DUP_PRO_Package::get_pending_cancellations();
									
					// Try to minimize the impact of checking for long running
					if(time() % 3 == 0)
					{
						self::cancel_long_running($pending_cancellations);
					}
                    
                    if (count($pending_cancellations) > 0)
                    {
                        foreach ($pending_cancellations as $package_id_to_cancel)
                        {
                            DUP_PRO_U::log("looking to cancel $package_id_to_cancel");
                            $package_to_cancel = DUP_PRO_Package::get_by_id((int) $package_id_to_cancel);

                            if ($package_to_cancel != null)
                            {
                                if ($package_to_cancel->Status == DUP_PRO_PackageStatus::STORAGE_PROCESSING)
                                {
                                    $package_to_cancel->Status = DUP_PRO_PackageStatus::STORAGE_CANCELLED;
									
									$package_to_cancel->cancel_all_uploads();
                                }
                                else
                                {
                                    $package_to_cancel->Status = DUP_PRO_PackageStatus::BUILD_CANCELLED;
                                }

                                $package_to_cancel->update();
                            }
                        }

                        // rsr technically COULD have a weird race condition here - to do it properly the pending insert should require the lock too...
                        DUP_PRO_Package::clear_pending_cancellations();
                    }

                    if ((!isset($_REQUEST['action'])) || ((isset($_REQUEST['action']) && ($_REQUEST['action'] != 'duplicator_pro_process_worker'))))
                    {						
                        self::process_schedules();						
						
						$kick_off_worker = DUP_PRO_Package::is_active_package_present();
                    }

                    if($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock)
                    {             
                        DUP_PRO_U::log("File lock released");
                        flock($locking_file, LOCK_UN);                        
                        fclose($locking_file);
                    }
                    else
                    {
                        DUP_PRO_U::release_sql_lock();
                    }                                                                   
                }
                else
                {
                  //  DUP_PRO_U::log_error("Init not doing anything because the lock is set");
                }
            }
            
            if ($kick_off_worker || self::$delayed_exit_and_kickoff)
            {
                self::kick_off_worker();
            }
            else if(is_admin() && (isset($_REQUEST['page']) && (strpos($_REQUEST['page'], DUP_PRO_Constants::PLUGIN_SLUG) !== false)))
            {
                DUP_PRO_U::log("************kicking off slug worker");
                // If it's one of our pages force it to kick off the client
                self::kick_off_worker(true);
            }  

            if (self::$delayed_exit_and_kickoff)
            {
                self::$delayed_exit_and_kickoff = false;

                exit();
            }
        }
        
		public static function add_kickoff_worker_javascript()
		{
			$global = DUP_PRO_Global_Entity::get_instance();
			
			$custom_url = strtolower($global->custom_ajax_url);
			
			$CLIENT_CALL_PERIOD_IN_MS = 20000;  // How often client calls into the service
							
			if($global->ajax_protocol == 'custom')
			{
				if(DUP_PRO_U::starts_with($custom_url, 'http'))
				{
					$ajax_url = $custom_url;
				}
				else
				{
					// Revert to http standard if they don't have the url correct
					$ajax_url = admin_url('admin-ajax.php', 'http');

					DUP_PRO_U::log("Even though custom ajax url configured, incorrect url set so reverting to $ajax_url"); 
				}
			}
			else
			{
				$ajax_url = admin_url('admin-ajax.php', $global->ajax_protocol);
			}   
				
			$gateway = array('ajaxurl' => $ajax_url, 'client_call_frequency' => $CLIENT_CALL_PERIOD_IN_MS);

			wp_register_script('dup-pro-kick', DUPLICATOR_PRO_PLUGIN_URL . 'assets/js/dp-kick.js', array('jquery'), DUPLICATOR_PRO_VERSION);

			wp_localize_script('dup-pro-kick', 'dp_gateway', $gateway);

			DUP_PRO_U::log('KICKOFF: Client Side');

			wp_enqueue_script('dup-pro-kick');
		}
		
        public static function cancel_long_running(&$pending_cancellations)
        {
            /* @var $global DUP_PRO_Global_Entity */
            $global = DUP_PRO_Global_Entity::get_instance();
                
            /* @var $active_package DUP_PRO_Package */
            
            if(DUP_PRO_Package::is_active_package_present())
			{
				$active_package = DUP_PRO_Package::get_next_active_package();	
			
				if(($global->max_package_runtime_in_min > 0) && ($active_package != null) && ($active_package->timer_start > 0))
				{                                
					$current_time = DUP_PRO_Util::GetMicrotime();
					$elapsed_sec = $current_time - $active_package->timer_start;

					$elapsed_minutes = $elapsed_sec / 60;

			   //     DUP_PRO_U::log("current microseconds=$current_time active package start=$active_package->timer_start");
					if($elapsed_minutes > $global->max_package_runtime_in_min)
					{
						DUP_PRO_U::log("Package $active_package->ID has been going for $elapsed_minutes minutes so cancelling. ($elapsed_sec)");

						array_push($pending_cancellations, $active_package->ID);
					}
					else
					{
					 //   DUP_PRO_U::log("Package $active_package->ID has been going for $elapsed_minutes minutes so NOT cancelling. ($elapsed_sec)");
					}
				}
				
				/* RSR Tacking on this logic here since getting the active package is so expensive */
				if($active_package != null)
				{														
					if($active_package->Status == DUP_PRO_PackageStatus::AFTER_SCAN)
					{
						if($active_package->timer_start == -1)
						{
							$active_package->timer_start = DUP_PRO_Util::GetMicrotime();

							$active_package->save();
						}
					
						$current_time = DUP_PRO_Util::GetMicrotime();
						$elapsed_sec = $current_time - $active_package->timer_start;

						if($elapsed_sec > 75)
						{
							DUP_PRO_Log::Open($active_package->NameHash);
														
							$message = DUP_PRO_U::__("Communication to AJAX is blocked. RECOMMENDATION: Go to https://snapcreek.com/duplicator/docs/faqs-tech/ and read \"Why is the package build stuck at 5%?\"");
							
							DUP_PRO_U::log($message);
							
							$active_package->Status = DUP_PRO_PackageStatus::ERROR;
							$active_package->save();
							
							DUP_PRO_Log::Error($message, $message, false);
						}
					}
				}
			}
        }

        public static function kick_off_worker($run_only_if_client = false)
        {
            /* RSR TODO: Once this logic has been proven across various hosts move these two values into settings */
            /* @var $global DUP_PRO_Global_Entity */    
			$global = DUP_PRO_Global_Entity::get_instance();
			
            if(!$run_only_if_client || $global->clientside_kickoff)
            {
//                $CLIENT_CALL_PERIOD_IN_MS = 20000;  // How often client calls into the service
               
                $calling_function_name = DUP_PRO_U::get_calling_function_name();

                DUP_PRO_U::log("Kicking off worker process as requested by $calling_function_name");

                $custom_url = strtolower($global->custom_ajax_url);

                if($global->ajax_protocol == 'custom')
                {
                    if(DUP_PRO_U::starts_with($custom_url, 'http'))
                    {
                        $ajax_url = $custom_url;
                    }
                    else
                    {
                        // Revert to http standard if they don't have the url correct
                        $ajax_url = admin_url('admin-ajax.php', 'http');

                        DUP_PRO_U::log("Even though custom ajax url configured, incorrect url set so reverting to $ajax_url"); 
                    }
                }
                else
                {
                    $ajax_url = admin_url('admin-ajax.php', $global->ajax_protocol);
                }   

                DUP_PRO_U::log("Attempting to use ajax url $ajax_url");            

                if($global->clientside_kickoff)
                {
					add_action('wp_enqueue_scripts', 'DUP_PRO_Package_Runner::add_kickoff_worker_javascript');
					add_action('admin_enqueue_scripts', 'DUP_PRO_Package_Runner::add_kickoff_worker_javascript');
					
//                    // Client-side kickoff
//                    $gateway = array('ajaxurl' => $ajax_url, 'client_call_frequency' => $CLIENT_CALL_PERIOD_IN_MS);
//
//                    wp_register_script('dup-pro-kick', DUPLICATOR_PRO_PLUGIN_URL . 'assets/js/dp-kick.js', array('jquery'), DUPLICATOR_PRO_VERSION);
//
//                    wp_localize_script('dup-pro-kick', 'dp_gateway', $gateway);
//
//                    DUP_PRO_U::log('KICKOFF: Client Side');
//
//                    wp_enqueue_script('dup-pro-kick');
                }
                else
                {           
                    // Server-side kickoff

                    $ajax_url = DUP_PRO_U::append_query_value($ajax_url, 'action', 'duplicator_pro_process_worker');

                    /* @var $global DUP_PRO_Global_Entity */

                    DUP_PRO_U::log('KICKOFF: Server Side');

                    if($global->basic_auth_enabled)
                    {
                        wp_remote_get($ajax_url, array('blocking' => false, 'headers' => array('Authorization' => 'Basic ' . base64_encode( $global->basic_auth_user. ':' . $global->basic_auth_password))));
                    }
                    else
                    {
                        wp_remote_get($ajax_url, array('blocking' => false));
                    }
                }

                DUP_PRO_U::log("after sent kickoff request");
            }
        }

        // Executed by cron
        public static function process()
        {
			if ( ! defined( 'WP_MAX_MEMORY_LIMIT' ) ) {
				define( 'WP_MAX_MEMORY_LIMIT', '256M' );
			}
			
			@ini_set('memory_limit', WP_MAX_MEMORY_LIMIT);
       
			@set_time_limit(7200);
			
			@ignore_user_abort(true);
		
			@ini_set('default_socket_timeout', 7200);	// 2 Hours
			
			/* @var $global DUP_PRO_Global_Entity */
            $global = DUP_PRO_Global_Entity::get_instance();
			
			if($global->clientside_kickoff)
			{
				DUP_PRO_U::log("PROCESS: From client");
				session_write_close();
			}
			else
			{
				DUP_PRO_U::log("PROCESS: From server");
			}
			
            ///* @var $global DUP_PRO_Global_Entity */
            $global = DUP_PRO_Global_Entity::get_instance();
            
            // Only attempt to process schedules if manual isn't running
            if($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock)
            {
                $locking_file = fopen(DUP_PRO_Constants::$LOCKING_FILE_FILENAME, 'c+');
            }
            else
            {
                $locking_file = true;
            }

            if ($locking_file != false)
            {
                if($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock)
                {
                    $acquired_lock = (flock($locking_file, LOCK_EX | LOCK_NB) != false);
                    
                    if($acquired_lock)
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
                    
                if($acquired_lock)
                {
                    self::process_schedules();

                    $package = DUP_PRO_Package::get_next_active_package();

                    /* @var $package DUP_PRO_Package */
                    if ($package != null)
                    {
                        $now = time();

                        DUP_PRO_Util::InitSnapshotDirectory();

                        $dup_tests = self::get_requirements_tests();

                        if ($dup_tests['Success'] == true)
                        {
                            $start_time = time();

                            DUP_PRO_U::log("PACKAGE $package->ID:PROCESSING");

                            ignore_user_abort(true);

                            // Split build time and scan time into two steps to make more reliable

                            if ($package->Status < DUP_PRO_PackageStatus::AFTER_SCAN)
                            {
                                DUP_PRO_U::log("PACKAGE $package->ID:SCANNING");

                                $package->scan();

                                $dupe_package = DUP_PRO_Package::get_by_id($package->ID);

                                $dupe_package->set_status(DUP_PRO_PackageStatus::AFTER_SCAN);

                                $end_time = time();

                                $scan_time = $end_time - $start_time;

                                DUP_PRO_U::log("SCAN TIME=$scan_time seconds");
                            }
                            else if ($package->Status < DUP_PRO_PackageStatus::COPIEDPACKAGE)
                            {
                                DUP_PRO_U::log("PACKAGE $package->ID:BUILDING");

                                $package->build();

                                $end_time = time();

                                $build_time = $end_time - $start_time;

                                DUP_PRO_U::log("BUILD TIME=$build_time seconds");
                            }
                            else if ($package->Status < DUP_PRO_PackageStatus::COMPLETE)
                            {
                                DUP_PRO_U::log("PACKAGE $package->ID:STORAGE PROCESSING");

                                $package->set_status(DUP_PRO_PackageStatus::STORAGE_PROCESSING);
								
                                $package->process_storages();

							//	DUP_PRO_Transfer_Request_Entity::update_active();
								
                                $end_time = time();

                                $build_time = $end_time - $start_time;

                                DUP_PRO_U::log("STORAGE CHUNK PROCESSING TIME=$build_time seconds");

                                if ($package->Status == DUP_PRO_PackageStatus::COMPLETE)
                                {
                                    DUP_PRO_U::log("PACKAGE $package->ID COMPLETE");
                                }
                                else if ($package->Status == DUP_PRO_PackageStatus::ERROR)
                                {
                                    DUP_PRO_U::log("PACKAGE $package->ID IN ERROR STATE");
                                }
                            }

                            ignore_user_abort(false);
                        }
                        else
                        {
                            DUP_PRO_Log::Open($package->NameHash);

                            DUP_PRO_Log::Error(DUP_PRO_U::__('Requirements Failed'), print_r($dup_tests, true), false);

                            DUP_PRO_U::log_error('Requirements didn\'t pass so can\'t perform backup!');
                            $package->Status = DUP_PRO_PackageStatus::REQUIREMENTS_FAILED;
                            $package->update();
                        }
                    }                   

                    //$kick_off_worker = (DUP_PRO_Package::get_next_active_package() != null);
					$kick_off_worker = DUP_PRO_Package::is_active_package_present();

                    if($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock)
                    {          
                        DUP_PRO_U::log("File lock released");
                        flock($locking_file, LOCK_UN);
                        fclose($locking_file);    
                    }
                    else
                    {
                        DUP_PRO_U::release_sql_lock();                        
                    }
                                       
                    if ($kick_off_worker)
                    {
                        self::kick_off_worker();
                    }					
                }
                else
                {
                    // File locked so another cron already running so just skip
                    DUP_PRO_U::log("File locked so skipping");
                }                         
            }
			
			
        }

        public static function get_requirements_tests()
        {
            $dup_tests = DUP_PRO_Server::get_requirments();

            if ($dup_tests['Success'] != true)
            {
                DUP_PRO_U::log_object('requirements', $dup_tests);
            }

            return $dup_tests;
        }

        public static function calculate_earliest_schedule_run_time()
        {
            $next_run_time = PHP_INT_MAX;

            $schedules = DUP_PRO_Schedule_Entity::get_active();

            foreach ($schedules as $schedule)
            {
                /* @var $schedule DUP_PRO_Schedule_Entity */
                if ($schedule->next_run_time == -1)
                {
                    $schedule->next_run_time = $schedule->get_next_run_time();
                    $schedule->save();
                }

                if ($schedule->next_run_time < $next_run_time)
                {
                    $next_run_time = $schedule->next_run_time;
                }
            }

            if ($next_run_time == PHP_INT_MAX)
            {
            //    DUP_PRO_U::log("No active schedules therefore no next run time");
                $next_run_time = -1;
            }
            else
            {
                if ($next_run_time != -1)
                {
              //      DUP_PRO_U::log("Next run time = " . DUP_PRO_U::get_local_formatted_time_from_gmt_ticks($next_run_time));
                }
                else
                {
              //      DUP_PRO_U::log("Earliest schedule next run time is -1??");
                }
            }

            return $next_run_time;
        }

        public static function process_schedules()
        {
            $next_run_time = self::calculate_earliest_schedule_run_time();

            if ($next_run_time != -1 && ($next_run_time <= time()))
            {
                $schedules = DUP_PRO_Schedule_Entity::get_active();

                foreach ($schedules as $schedule)
                {
                    /* @var $schedule DUP_PRO_Schedule_Entity */
                    $schedule->process();
                }
            }
        }

    }

}
?>
