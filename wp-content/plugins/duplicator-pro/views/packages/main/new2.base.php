<?php

require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/utilities/class.io.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/class.package.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/entities/class.global.entity.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/entities/class.storage.entity.php');

if(empty($_POST))
{
    // Refresh 'fix'
    $redirect = self_admin_url('admin.php?page=duplicator-pro&tab=packages&inner_page=new1');
    
    DUP_PRO_U::log("redirect to $redirect");
    
    ?>
    <script type="text/javascript">
        window.location.href="<?php echo $redirect; ?>";
    </script>
        
    <?php
    die();
}

global $wp_version;
/* @var $global DUP_PRO_Global_Entity */
$global = DUP_PRO_Global_Entity::get_instance();

$Package = null;

if (isset($_REQUEST['action']))
{
    switch ($_REQUEST['action'])
    {
        case 'template-create' :
            $template_id = (int)$_REQUEST['template_id'];                        
            
            $Package = new DUP_PRO_Package();
            $storage_ids = isset($_REQUEST['_storage_ids']) ? $_REQUEST['_storage_ids'] : array();
                        
            $Package->set_temporary_package_from_template_and_storages($template_id, $storage_ids);
            break;
    }
}

if($Package == null)
{
    $Package = new DUP_PRO_Package();
    $Package->set_temporary_package_from_post($_REQUEST);   
}

$Package = DUP_PRO_Package::get_temporary_package(); 

$mysqlDumpPath = DUP_PRO_Database::GetMySqlDumpPath();

$legacy_sql_string = '';

if($Package->Database->old_sql_compatibility)
{	
	$legacy_sql_string = DUP_PRO_U::__('Legacy');
}

$dbbuild_mode = ($mysqlDumpPath && $global->package_mysqldump) ? "MysqlDump $legacy_sql_string (fast)" : 'PHP (slow)';

$archive_build_mode = ($global->archive_build_mode == DUP_PRO_Archive_Build_Mode::ZipArchive) ?  " ZipArchive (slow)" : "Shell Exec (fast)";

$package_list_url = 'admin.php?page=' . DUP_PRO_Constants::$PACKAGES_SUBMENU_SLUG;
?>

<style>
    /* -----------------------------
    PROGRESS ARES-CHECKS */
    div#dup-progress-area {text-align:center; max-width:650px; min-height:200px; margin:0px auto 0px auto; padding:0px;}
    div#dup-msg-success {color:#18592A; padding:5px; text-align: left}	
    div#dup-msg-success-subtitle {font-style: italic; margin:7px 0px}	
    div#dup-msg-error {color:#A62426; padding:5px; max-width: 790px;}
    div#dup-msg-error-response-text { max-height:350px; overflow-y:scroll; border:1px solid silver; border-radius: 3px; padding:8px;background:#fff}

    div.dup-panel {margin-bottom: 25px}
    div.dup-scan-filter-status {display:inline; float: right; font-size:11px; margin-right:10px; color:#AF0000; font-style: italic}
    /* 	SERVER-CHECKS */
    div.dup-scan-title {display:inline-block;  padding:1px; font-weight: bold;}
    div.dup-scan-title a {display:inline-block; min-width:200px; padding:3px; }
	div.dup-scan-title a:focus {outline: 1px solid #fff; box-shadow: none}
    div.dup-scan-title div {display:inline-block;  }
    div.dup-scan-info {display:none;}
    div.dup-scan-good {display:inline-block; color:green;font-weight: bold;}
    div.dup-scan-warn {display:inline-block; color:#AF0000;font-weight: bold;}
    span.dup-toggle {float:left; margin:0 2px 2px 0; }

    /*DATABASE*/
    table.dup-scan-db-details {line-height: 14px; margin:5px 0px 0px 20px;  width:98%}
    table.dup-scan-db-details td {padding:2px;}
    table.dup-scan-db-details td:first-child {font-weight: bold;  white-space: nowrap; width:105px}
    div#dup-scan-db-info {margin:0px 0px 0px 10px}
    div#data-db-tablelist {max-height: 300px; overflow-y: scroll}
    div#data-db-tablelist div{padding:0px 0px 0px 15px;}
    div#data-db-tablelist span{display:inline-block; min-width: 75px}
    div#data-db-size1 {display: inline-block; float:right; font-size:11px; margin-right: 15px; font-style: italic}
    /*FILES */
    div#data-arc-size1 {display: inline-block; float:right; font-size:11px; margin-right: 15px; font-style: italic}
    div#data-arc-names-data, div#data-arc-big-data
    {word-wrap: break-word;font-size:10px; border:1px dashed silver; padding:5px; display: none}

    /*Footer*/
    div.dup-button-footer {text-align:center; margin:0}
    button.button {font-size:15px !important; height:30px !important; font-weight:bold; padding:3px 5px 5px 5px !important;}
	
	/*WARNING AFTER FOOTER*/
	#dpro-warning-present { display:none; text-align:center; font-weight:bold; margin-bottom:20px; }
</style>


<!-- ====================
TOOL-BAR -->
<table class="dpro-edit-toolbar">
    <tr>
        <td>
            <div id="dup-wiz">
                <div id="dup-wiz-steps">
                    <div class="completed-step"><a><span>1</span> <?php DUP_PRO_U::_e('Setup'); ?></a></div>
                    <div class="active-step"><a><span>2</span> <?php DUP_PRO_U::_e('Scan'); ?> </a></div>
                    <div><a><span>3</span> <?php DUP_PRO_U::_e('Build'); ?> </a></div>
                </div>
                <div id="dup-wiz-title" style="white-space: nowrap">
                    <?php DUP_PRO_U::_e('Step 2: System Scan'); ?>
                </div> 
            </div>	
        </td>
        <td>
            <a href="<?php echo $packages_tab_url; ?>" class="add-new-h2"><i class="fa fa-archive"></i> <?php DUP_PRO_U::_e('All Packages'); ?></a>
            <span> <?php _e("Create New"); ?></span>
        </td>
    </tr>
</table>
<hr class="dpro-edit-toolbar-divider"/>


<form id="form-duplicator" method="post" action="<?php echo $package_list_url ?>">
<input type="hidden" name="create_from_temp" value="1" />

<div id="dup-progress-area">
	<!--  PROGRESS BAR -->
	<div id="dup-progress-bar-area">
		<div style="font-size:1.7em; margin-bottom:20px"><i class="fa fa-spinner fa-spin"></i> <?php DUP_PRO_U::_e('Scanning Site'); ?></div>
		<div id="dup-progress-bar"></div>
		<b><?php DUP_PRO_U::_e('Please Wait...'); ?></b>
	</div>

	<!--  SUCCESS MESSAGE -->
	<div id="dup-msg-success" style="display:none">
		<div style="text-align:center">
			<div class="dup-hdr-success"><i class="fa fa-check-square-o"></i> <?php DUP_PRO_U::_e('Scan Complete'); ?></div>
			<div id="dup-msg-success-subtitle">
				<?php DUP_PRO_U::_e("Process Time:"); ?> <span id="data-rpt-scantime"></span>
			</div>
		</div>

		<!-- ================================================================
		META-BOX: SERVER
		================================================================ -->
		<div class="dup-panel">
			<div class="dup-panel-title">
				<i class="fa fa-hdd-o"></i> <?php DUP_PRO_U::_e("Server"); ?>
				<div style="float:right; margin:-1px 10px 0px 0px">
					<small><a href="?page=duplicator-pro-tools&tab=diagnostics" target="_blank"><?php DUP_PRO_U::_e('Diagnostics'); ?></a></small>
				</div>
			</div>
			<div class="dup-panel-panel">
				<!-- ==========================
				WEB SERVER -->
				<div>
				<div class='dup-scan-title'>
					<a><?php DUP_PRO_U::_e('Web Server'); ?></a> <div id="data-srv-web-all"></div>
				</div>
				<div class='dup-scan-info dup-info-box'>
					<?php
					$web_servers = implode(', ', $GLOBALS['DUPLICATOR_PRO_SERVER_LIST']);
					echo '<span id="data-srv-web-model"></span>&nbsp;<b>' . DUP_PRO_U::__('Web Server') . ":</b>&nbsp; '{$_SERVER['SERVER_SOFTWARE']}'";	
					echo '<small>';
					DUP_PRO_U::_e("Supported web servers:");
					echo "{$web_servers}";
					echo '</small>';
					?>
				</div>
				</div>

				<!-- ==========================
				PHP SETTINGS -->
				<div>
				<div class='dup-scan-title'>
					<a><?php DUP_PRO_U::_e('PHP Setup'); ?></a> <div id="data-srv-php-all"></div>
				</div>
				<div class='dup-scan-info dup-info-box'>
					<?php
					//OPEN_BASEDIR
					$test = ini_get("open_basedir");
					echo '<span id="data-srv-php-openbase"></span>&nbsp;<b>' . DUP_PRO_U::__('Open Base Dir') . ":</b>&nbsp; '{$test}' <br/>";
					echo '<small>';
					DUP_PRO_U::_e('Issues might occur when [open_basedir] is enabled. Work with your server admin to disable this value in the php.ini file if you’re having issues building a package.');
					echo "&nbsp;<i><a href='http://www.php.net/manual/en/ini.core.php#ini.open-basedir' target='_blank'>[" . DUP_PRO_U::__('details') . "]</a></i><br/>";
					echo '</small>';

					//MAX_EXECUTION_TIME
					$test = (set_time_limit(0)) ? 0 : ini_get("max_execution_time");
					echo '<hr size="1" /><span id="data-srv-php-maxtime"></span>&nbsp;<b>' . DUP_PRO_U::__('Max Execution Time') . ":</b>&nbsp; '{$test}' <br/>";
					echo '<small>';
					printf(DUP_PRO_U::__('Issues might occur for larger packages when the [max_execution_time] value in the php.ini is too low.  The minimum recommended timeout is "%1$s" seconds or higher. An attempt is made to override this value if the server allows it.  A value of 0 (recommended) indicates that PHP has no time limits.'), DUPLICATOR_PRO_SCAN_TIMEOUT);
					echo "&nbsp;<i><a href='http://www.php.net/manual/en/info.configuration.php#ini.max-execution-time' target='_blank'>[" . DUP_PRO_U::__('details') . "]</a></i>";
					echo '</small>';
					
					//MYSQLI
					echo '<hr size="1" /><span id="data-srv-php-mysqli"></span>&nbsp;<b>' . DUP_PRO_U::__('MySQLi') . "</b> <br/>";
					echo '<small>';
					DUP_PRO_U::_e('Creating the package does not require the mysqli module.  However the installer file requires that the PHP module mysqli be installed on the server it is deployed on.');
					echo "&nbsp;<i><a href='http://php.net/manual/en/mysqli.installation.php' target='_blank'>[" . DUP_PRO_U::__('details') . "]</a></i>";
					echo '</small>';

					
					if($Package->contains_storage_type(DUP_PRO_Storage_Types::Dropbox))
					{
                        //OPENSSL
                        
						echo '<hr size="1" /><span id="data-srv-php-openssl"></span>&nbsp;<b>' . DUP_PRO_U::__('Open SSL') . '</b> ';
						echo '<br/><small>';
						DUP_PRO_U::_e('Dropbox storage requires an HTTPS connection. On windows systems enable "extension=php_openssl.dll" in the php.ini configuration file.  ');
						DUP_PRO_U::_e('On Linux based systems check for the --with-openssl[=DIR] flag.');
						echo "&nbsp;<i><a href='http://php.net/manual/en/openssl.installation.php' target='_blank'>[" . DUP_PRO_U::__('details') . "]</a></i>";
						echo '</small>';
                        
                        /* TODO: if dropbox_transfer_mode isn't one of these two values we should be prevented from even starting a dropbox package build */
                        /* TODO: Technically we should prevent them from even getting here is the option isn't available - move out of php warning into a requirement */
                        if($global->dropbox_transfer_mode == DUP_PRO_Dropbox_Transfer_Mode::FOpen_URL)
                        {
                            //FOpen                        
                            $test = DUP_PRO_U::is_url_fopen_enabled();
                            echo '<hr size="1" /><span id="data-srv-php-allowurlfopen"></span>&nbsp;<b>' . DUP_PRO_U::__('Allow URL Fopen') . ":</b>&nbsp; '{$test}' <br/>";
                            echo '<small>';
                            DUP_PRO_U::_e('Dropbox communications requires that [allow_url_fopen] be set to 1 in the php.ini file.');
                            echo "&nbsp;<i><a href='http://php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen' target='_blank'>[" . DUP_PRO_U::__('details') . "]</a></i><br/>";
                            echo '</small>';
                        }
                        else if ($global->dropbox_transfer_mode == DUP_PRO_Dropbox_Transfer_Mode::cURL)
                        {
                            //FOpen                        
                            $test = DUP_PRO_U::is_curl_available() ? DUP_PRO_U::__('True') : DUP_PRO_U::__('False');
                            echo '<hr size="1" /><span id="data-srv-php-curlavailable"></span>&nbsp;<b>' . DUP_PRO_U::__('cURL Available') . ":</b>&nbsp; '{$test}' <br/>";
                            echo '<small>';
                            DUP_PRO_U::_e('Dropbox communications requires that extension=php_curl.dll be present in the php.ini file.');
                            echo "&nbsp;<i><a href='http://php.net/manual/en/curl.installation.php' target='_blank'>[" . DUP_PRO_U::__('details') . "]</a></i><br/>";
                            echo '</small>';
                        }                        
					}                    
                    
					?>
				</div>
				</div>

				<!-- ======================
				WP SETTINGS -->
				<div>
				<div class='dup-scan-title'>
					<a><?php DUP_PRO_U::_e('WordPress'); ?></a> <div id="data-srv-wp-all"></div>
				</div>
				<div class='dup-scan-info dup-info-box'>
					<?php
					//VERSION CHECK
					echo '<span id="data-srv-wp-version"></span>&nbsp;<b>' . DUP_PRO_U::__('WordPress Version') . ":</b>&nbsp; '{$wp_version}' <br/>";
					echo '<small>';
					printf(DUP_PRO_U::__('It is recommended to have a version of WordPress that is greater than %1$s'), DUPLICATOR_PRO_SCAN_MIN_WP);
					echo '</small>';
				
					//CORE FILES
					echo '<hr size="1" /><span id="data-srv-wp-core"></span>&nbsp;<b>' . DUP_PRO_U::__('Core Files') . "</b> <br/>";
					echo '<small>';
					DUP_PRO_U::_e("If the scanner is unable to locate the wp-config.php file in the root directory, then you will need to manually copy it to its new location.");
					echo '</small>';

					//CACHE DIR
					$cache_path = $cache_path = DUP_PRO_Util::SafePath(WP_CONTENT_DIR) . '/cache';
					$cache_size = DUP_PRO_Util::ByteSize(DUP_PRO_IO::get_dir_size($cache_path));
					echo '<hr size="1" /><span id="data-srv-wp-cache"></span>&nbsp;<b>' . DUP_PRO_U::__('Cache Path') . ":</b>&nbsp; '{$cache_path}' ({$cache_size}) <br/>";
					echo '<small>';
					DUP_PRO_U::_e("Cached data will lead to issues at install time and increases your archive size. It is recommended to empty your cache directory at build time. Use caution when removing data from the cache directory. If you have a cache plugin review the documentation for how to empty it; simply removing files might cause errors on your site. The cache size minimum threshold is currently set at ");
					echo DUP_PRO_Util::ByteSize(DUPLICATOR_PRO_SCAN_CACHESIZE) . '.';
					echo '</small>';
					?>
				</div>
				</div>

			</div><!-- end .dup-panel -->
		</div><!-- end .dup-panel-panel -->

		<div style="font-size:18px; font-weight:bold; margin:-15px 0 0 10px">
			<i class="fa fa-file-archive-o"></i>&nbsp;<?php DUP_PRO_U::_e('Archive'); ?> 
		</div>


		<div class="dup-panel">
			
			<!-- ================================================================
			FILES
			================================================================ -->
			<div class="dup-panel-title">
				<i class="fa fa-files-o"></i>
				<?php DUP_PRO_U::_e("Files"); ?> 
				<div id="data-arc-size1"></div>
				<div class="dup-scan-filter-status">
				<?php
				if ($Package->Archive->FilterOn)
				{
					echo '<i class="fa fa-filter"></i> ';
					DUP_PRO_U::_e('Enabled');
				}
				?> 
				</div>
			</div>
			<div class="dup-panel-panel">

				<!-- ======================
				TOTAL SIZE -->
				<div>
					<div class='dup-scan-title'>
						<a><?php DUP_PRO_U::_e('Total Size'); ?></a> <div id="data-arc-status-size"></div>
					</div>
					<div class='dup-scan-info  dup-info-box'>
						<b><?php DUP_PRO_U::_e('Size'); ?>:</b> <span id="data-arc-size2"></span>  &nbsp; | &nbsp;
						<b><?php DUP_PRO_U::_e('Files'); ?>:</b> <span id="data-arc-files"></span>  &nbsp; | &nbsp;
						<b><?php DUP_PRO_U::_e('Directories '); ?>:</b> <span id="data-arc-dirs"></span>   &nbsp; | &nbsp;
						<b><?php DUP_PRO_U::_e('Total'); ?>:</b> <span id="data-arc-fullcount"></span>  
						
						<small>
						<?php
							printf(DUP_PRO_U::__('Total size represents all files minus any filters that have been setup.  The current thresholds that trigger warnings are %1$s for the entire site and %2$s for large files.'), 
									DUP_PRO_Util::ByteSize(DUPLICATOR_PRO_SCAN_SITE), 
									DUP_PRO_Util::ByteSize(DUPLICATOR_PRO_SCAN_WARNFILESIZE));
						?>
						</small>
					</div>
				</div>		

				<!-- ======================
				NAME CHECKS -->
				<div>
					<div class='dup-scan-title' >
						<a><?php DUP_PRO_U::_e('Name Checks'); ?></a> <div id="data-arc-status-names"></div>
					</div>
					<div class='dup-scan-info dup-info-box'>
						<small>
						<?php
						DUP_PRO_U::_e('File or directory names may cause issues when working across different environments and servers.  Names that are over 250 characters, contain special characters (such as * ? > < : / \ |) or are unicode might cause issues in a remote enviroment.  It is recommended to remove or filter these files before building the archive if you have issues at install time.');
						?>
						</small><br/>
						<a href="javascript:void(0)" onclick="jQuery('#data-arc-names-data').toggle()">[<?php DUP_PRO_U::_e('Show Paths'); ?>]</a>
						<div id="data-arc-names-data"></div>
					</div>
				</div>		

				<!-- ======================
				LARGE FILES -->
				<div>
					<div class='dup-scan-title'>
						<a><?php DUP_PRO_U::_e('Large Files'); ?></a> <div id="data-arc-status-big"></div>
					</div>
					<div class='dup-scan-info  dup-info-box'>
						<small>
						<?php
						printf(DUP_PRO_U::__('Large files such as movies or other content can cause issues with timeouts.  The current check for large files is %1$s per file.  If your having issues creating a package consider excluding these files with the files filter and manually moving them to your new location.'), DUP_PRO_Util::ByteSize(DUPLICATOR_PRO_SCAN_WARNFILESIZE));
						?>
						</small><br/>
						<a href="javascript:void(0)" onclick="jQuery('#data-arc-big-data').toggle()">[<?php DUP_PRO_U::_e('Show Paths'); ?>]</a>
						<div id="data-arc-big-data"></div>
					</div>
				</div>	

				<!-- ======================
				VIEW FILTERS -->
				<?php if ($Package->Archive->FilterOn) : ?>
					<div>
						<div class='dup-scan-title'>
							<a><?php DUP_PRO_U::_e('View Filters'); ?></a> 
						</div>
						<div class='dup-scan-info  dup-info-box'>
							<b>[<?php DUP_PRO_U::_e('Directories'); ?>]</b><br/>
							<?php
							if (strlen($Package->Archive->FilterDirs))
							{
								echo str_replace(";", "<br/>", $Package->Archive->FilterDirs);
							}
							else
							{
								DUP_PRO_U::_e('No directory filters have been set.');
                                echo '<br/>';
							}
							?>
							<br/>

							<b>[<?php DUP_PRO_U::_e('File Extensions'); ?>]</b><br/>
							<?php
							if (strlen($Package->Archive->FilterExts))
							{
								echo $Package->Archive->FilterExts;
							}
							else
							{
								DUP_PRO_U::_e('No file extension filters have been set.');
                                echo '<br/>';
							}
							?>		
                            <br/>
                            
                            <b>[<?php DUP_PRO_U::_e('Files'); ?>]</b><br/>
							<?php
							if (strlen($Package->Archive->FilterFiles))
							{
                                echo str_replace(";", "<br/>", $Package->Archive->FilterFiles);
							}
							else
							{
								DUP_PRO_U::_e('No file filters have been set.');
                                echo '<br/>';
							}
							?>	
							
							<small>
								<?php DUP_PRO_U::_e('The lists above are the directories, files and file extensions that will be excluded from the archive.'); ?>
							</small><br/>
						</div>
					</div>	
				<?php endif; ?>	


			</div><!-- end .dup-panel -->

			<!-- ================================================================
			DATABASE
			================================================================ -->
			<div class="dup-panel-title">
				<i class="fa fa-table"></i>
				<?php DUP_PRO_U::_e("Database"); ?>
				<div id="data-db-size1"></div>
				<div class="dup-scan-filter-status">
				<?php
				if ($Package->Database->FilterOn)
				{
					echo '<i class="fa fa-filter"></i> ';
					DUP_PRO_U::_e('Enabled');
				}
				?> 
				</div>
			</div>
			<div class="dup-panel-panel" id="dup-scan-db">

				<!-- ======================
				TOTAL SIZE -->
				<div>
					<div class='dup-scan-title'>
						<a><?php DUP_PRO_U::_e('Total Size'); ?></a>
						<div id="data-db-status-size1"></div>
					</div>
					<div class='dup-scan-info  dup-info-box'>
						<b><?php DUP_PRO_U::_e('Tables'); ?>:</b> <span id="data-db-tablecount"></span> &nbsp; | &nbsp;
						<b><?php DUP_PRO_U::_e('Records'); ?>:</b> <span id="data-db-rows"></span> &nbsp; | &nbsp;
						<b><?php DUP_PRO_U::_e('Size'); ?>:</b> <span id="data-db-size2"></span> 
						<small>
						<?php
						$lnk = '<a href="maint/repair.php" target="_blank">' . DUP_PRO_U::__('repair and optimization') . '</a>';
						printf(DUP_PRO_U::__('Total size and row count for all database tables are approximate values.  The thresholds that trigger warnings are %1$s and %2$s records.  Large databases take time to process and can cause issues with server timeout and memory settings.  Running a %3$s on your database can also help improve the overall size and performance.  If your server supports shell_exec and mysqldump you can try to enable this option from the settings menu.'), DUP_PRO_Util::ByteSize(DUPLICATOR_PRO_SCAN_DBSIZE), number_format(DUPLICATOR_PRO_SCAN_DBROWS), $lnk);
						?>
						</small>
					</div>
				</div>

				<!-- ======================
				TABLE DETAILS -->
				<div>
					<div class='dup-scan-title'>
						<a><?php DUP_PRO_U::_e('Detailed Info'); ?></a>
						<div id="data-db-status-size2"></div>
					</div>
					<div class='dup-scan-info dup-info-box'>
						
						<b><?php DUP_PRO_U::_e('Name:'); ?></b> <?php echo DB_NAME; ?> <br/>
						<b><?php DUP_PRO_U::_e('Host:'); ?></b> <?php echo DB_HOST; ?>  <br/>
						<div style="margin: 4px 0 -1px 0">
							<b><?php DUP_PRO_U::_e('Tables'); ?></b> <hr size="1" />
						</div>
						
						<div id="dup-scan-db-info">
							<div id="data-db-tablelist"></div>
						</div>
					</div>
				</div>
			</div><!-- end .dup-panel -->
			
			<!-- ================================================================
			BUILD MODES
			================================================================ -->
			<div class="dup-panel-title">
				<i class="fa fa-gear"></i>
				<?php DUP_PRO_U::_e("Build Mode"); ?>
			</div>
			<div class="dup-panel-panel">
				<table class="dup-scan-db-details">
					<tr><td><b><?php DUP_PRO_U::_e('Archive'); ?></b></td><td><a href="?page=duplicator-pro-settings" target="_blank"><?php echo $archive_build_mode; ?></a> </td></tr>
					<tr><td><b><?php DUP_PRO_U::_e('Database'); ?></b></td><td><a href="?page=duplicator-pro-settings" target="_blank"><?php echo $dbbuild_mode; ?></a> </td></tr>
				</table>	
				<br/>
			</div>

		</div><!-- end .dup-panel-panel -->
	</div>

	<!--  ERROR MESSAGE -->
	<div id="dup-msg-error" style="display:none">
		<div class="dup-hdr-error"><i class="fa fa-exclamation-circle"></i> <?php DUP_PRO_U::_e('Scan Error'); ?></div>
		<i><?php DUP_PRO_U::_e('Please try again!'); ?></i><br/>
		<div style="text-align:left">
			<b><?php DUP_PRO_U::_e("Server Status:"); ?></b> &nbsp;
			<div id="dup-msg-error-response-status" style="display:inline-block"></div><br/>

			<b><?php DUP_PRO_U::_e("Error Message:"); ?></b>
			<div id="dup-msg-error-response-text"></div>
		</div>
	</div>			
	<div id="dpro-warning-present" ><?php DUP_PRO_U::_e('Warnings help diagnose build failures if one occurs. Warnings DON\'T indicate that a build will fail.'); ?></div>
</div> <!-- end #dup-progress-area -->

<div class="dup-button-footer" style="display:none">
	<input type="button" value="&#9668; <?php DUP_PRO_U::_e("Back") ?>" onclick="window.location.assign('?page=duplicator-pro&tab=packages&inner_page=new1')" class="button button-large" />
	<input type="button" value="<?php DUP_PRO_U::_e("Rescan") ?>" onclick="DupPro.Pack.Rescan()" class="button button-large" />
	<input type="submit" onclick="jQuery('#form-duplicator').submit();jQuery('#dup-build-button').prop('disabled', true);return false;" class="button button-primary button-large" id="dup-build-button" value='<?php DUP_PRO_U::_e("Build")?> &#9658'/>
	<!-- Used for iMacros testing do not remove -->
	<div id="dup-automation-imacros"></div>
</div>

</form>

<script type="text/javascript">
    jQuery(document).ready(function ($) {

		DupPro.Pack.WarningPresent = false;
		
        DupPro.Pack.WebServiceStatus = {
            Pass: 1,
            Warn: 2,
            Error: 3,
            Incomplete: 4,
            ScheduleRunning: 5
        }
        /*	----------------------------------------
         *	METHOD: Performs Ajax post to create check system  */
        DupPro.Pack.Scan = function () {
            var input = {action: 'duplicator_pro_package_scan'}

            $.ajax({
                type: "POST",
                url: ajaxurl,
                dataType: "json",
                timeout: 10000000,
                data: input,
                complete: function () {
                    
                },
                success: function (data) {

					var data    = data || new Object();
					var status  = data.Status  || 3;
					var message = data.Message || "Unable to read JSON from service. <br/> See: /wp-admin/admin-ajax.php?action=duplicator_pro_package_scan";
					console.log(data);

                    if (status == DupPro.Pack.WebServiceStatus.Pass)
                    {
                        DupPro.Pack.LoadScanData(data);
						
						if(DupPro.Pack.WarningPresent) { 
							$('#dpro-warning-present').show();	
						} else {
							$('#dpro-warning-present').hide();	
						}
							
						
						 $('.dup-button-footer').show();
                    }
                    else if (status == DupPro.Pack.WebServiceStatus.ScheduleRunning) {
                        // as long as its just saying that someone blocked us keep trying
                        console.log('retrying scan in 300 ms...');
                        setTimeout(DupPro.Pack.Scan, 300);
                    }
                    else
                    {
                        $('#dup-progress-bar-area, #dup-build-button').hide();
						$('#dup-msg-error-response-status').html(status);
						$('#dup-msg-error-response-text').html(message);
						$('#dup-msg-error').show();
						$('.dup-button-footer').show();
                    }
                },
                error: function (data) {
                    var status = data.status + ' -' + data.statusText;
                    $('#dup-progress-bar-area, #dup-build-button').hide();
                    $('#dup-msg-error-response-status').html(status)
                    $('#dup-msg-error-response-text').html(data.responseText);
                    $('#dup-msg-error, .dup-button-footer').show();
                    console.log(data);
                }
            });
        }

        DupPro.Pack.Rescan = function () {
            $('#dup-msg-success,#dup-msg-error,.dup-button-footer').hide();
			$('#dpro-warning-present').hide();	
            $('#dup-progress-bar-area').show();
            DupPro.Pack.Scan();
        }

        DupPro.Pack.LoadScanStatus = function (status) {
            var result;
	
            switch (status) {
				case false :    result = '<div class="dup-scan-warn"><i class="fa fa-exclamation-triangle"></i></div>';      break;
                case 'Warn' :   result = '<div class="dup-scan-warn"><i class="fa fa-exclamation-triangle"></i> Warn</div>'; DupPro.Pack.WarningPresent = true; break;
				case true :     result = '<div class="dup-scan-good"><i class="fa fa-check"></i></div>';	                 break;
                case 'Good' :   result = '<div class="dup-scan-good"><i class="fa fa-check"></i> Good</div>';                break;
                default :
                    result = 'unable to read';
            }
            return result;
        }

        /*	----------------------------------------
         *	METHOD:    */
        DupPro.Pack.LoadScanData = function (data) {
			try {
				var errMsg = "unable to read";
				$('#dup-progress-bar-area').hide();

				//****************
				//REPORT
				var base = $('#data-rpt-scanfile').attr('href');
				$('#data-rpt-scanfile').attr('href', base + '&scanfile=' + data.RPT.ScanFile);
				$('#data-rpt-scantime').text(data.RPT.ScanTime || 0);

				//****************
				//SERVER
				$('#data-srv-web-model').html(DupPro.Pack.LoadScanStatus(data.SRV.WEB.model));
				$('#data-srv-web-all').html(DupPro.Pack.LoadScanStatus(data.SRV.WEB.ALL));

				$('#data-srv-php-openbase').html(DupPro.Pack.LoadScanStatus(data.SRV.PHP.openbase));
				$('#data-srv-php-maxtime').html(DupPro.Pack.LoadScanStatus(data.SRV.PHP.maxtime));
				$('#data-srv-php-mysqli').html(DupPro.Pack.LoadScanStatus(data.SRV.PHP.mysqli));
				$('#data-srv-php-openssl').html(DupPro.Pack.LoadScanStatus(data.SRV.PHP.openssl));
				$('#data-srv-php-allowurlfopen').html(DupPro.Pack.LoadScanStatus(data.SRV.PHP.allowurlfopen));            
                $('#data-srv-php-curlavailable').html(DupPro.Pack.LoadScanStatus(data.SRV.PHP.curlavailable));            
				$('#data-srv-php-all').html(DupPro.Pack.LoadScanStatus(data.SRV.PHP.ALL));

				$('#data-srv-wp-version').html(DupPro.Pack.LoadScanStatus(data.SRV.WP.version));
				$('#data-srv-wp-core').html(DupPro.Pack.LoadScanStatus(data.SRV.WP.core));
				$('#data-srv-wp-cache').html(DupPro.Pack.LoadScanStatus(data.SRV.WP.cache));
				$('#data-srv-wp-all').html(DupPro.Pack.LoadScanStatus(data.SRV.WP.ALL));


				//****************
				//DATABASE
				var html = "";
				if (data.DB.Status.Success) {
					$('#data-db-status-size1').html(DupPro.Pack.LoadScanStatus(data.DB.Status.Size));
					$('#data-db-status-size2').html(DupPro.Pack.LoadScanStatus(data.DB.Status.Size));
					$('#data-db-size1').text(data.DB.Size || errMsg);
					$('#data-db-size2').text(data.DB.Size || errMsg);
					$('#data-db-rows').text(data.DB.Rows || errMsg);
					$('#data-db-tablecount').text(data.DB.TableCount || errMsg);
					//Table Details
					if (data.DB.TableList == undefined || data.DB.TableList.length == 0) {
						html = '<?php DUP_PRO_U::_e("Unable to report on any tables") ?>';
					} else {
						$.each(data.DB.TableList, function (i) {
							html += '<b>' + i + '</b><br/>';
							$.each(data.DB.TableList[i], function (key, val) {
								html += '<div><span>' + key + ':</span>' + val + '</div>';
							})
						});
					}
					$('#data-db-tablelist').append(html);
				} else {
					html = '<?php DUP_PRO_U::_e("Unable to report on database stats") ?>';
					$('#dup-scan-db').html(html);
				}

				//****************
				//ARCHIVE
				$('#data-arc-status-size').html(DupPro.Pack.LoadScanStatus(data.ARC.Status.Size));
				$('#data-arc-status-names').html(DupPro.Pack.LoadScanStatus(data.ARC.Status.Names));
				$('#data-arc-status-big').html(DupPro.Pack.LoadScanStatus(data.ARC.Status.Big));
				$('#data-arc-size1').text(data.ARC.Size || errMsg);
				$('#data-arc-size2').text(data.ARC.Size || errMsg);
				$('#data-arc-files').text(data.ARC.FileCount || errMsg);
				$('#data-arc-dirs').text(data.ARC.DirCount || errMsg);
				$('#data-arc-fullcount').text(data.ARC.FullCount || errMsg);

				//Name Checks
				html = '';
				//Dirs
				if (data.ARC.FilterInfo.Dirs.Warning !== undefined && data.ARC.FilterInfo.Dirs.Warning.length > 0) {
					$.each(data.ARC.FilterInfo.Dirs.Warning, function (key, val) {
						html += '<?php DUP_PRO_U::_e("DIR") ?> ' + key + ':<br/>[' + val + ']<br/>';
					});
				}
				//Files
				if (data.ARC.FilterInfo.Files.Warning !== undefined && data.ARC.FilterInfo.Files.Warning.length > 0) {
					$.each(data.ARC.FilterInfo.Files.Warning, function (key, val) {
						html += '<?php DUP_PRO_U::_e("FILE") ?> ' + key + ':<br/>[' + val + ']<br/>';
					});
				}
				html = (html.length == 0) ? '<?php DUP_PRO_U::_e("No name warning issues found.") ?>' : html;
				
				
				$('#data-arc-names-data').html(html);

				//Large Files
				html = '<?php DUP_PRO_U::_e("No large files found.") ?>';
				if (data.ARC.FilterInfo.Files.Size !== undefined && data.ARC.FilterInfo.Files.Size.length > 0) {
					html = '';
					$.each(data.ARC.FilterInfo.Files.Size, function (key, val) {
						html += '<?php DUP_PRO_U::_e("FILE") ?> ' + key + ':<br/>' + val + '<br/>';
					});
				}
				$('#data-arc-big-data').html(html);
				$('#dup-msg-success').show();
			
			}
			catch(err) {
				err += '<br/> Please try again!'
				$('#dup-msg-error-response-status').html("n/a")
				$('#dup-msg-error-response-text').html(err);
				$('#dup-msg-error, .dup-button-footer').show();
				$('#dup-build-button').hide();
			}
			
        }

        //Page Init:
        DupPro.UI.AnimateProgressBar('dup-progress-bar');
        DupPro.Pack.Scan();
        DupPro.Pack.ToggleSystemDetails = function (anchor) {
            $(anchor).parent().siblings('.dup-scan-info').toggle();
        }

        //Init: Toogle for system requirment detial links
        $('.dup-scan-title a').each(function () {
            $(this).attr('href', 'javascript:void(0)');
            $(this).click(function () {
                DupPro.Pack.ToggleSystemDetails(this);
            });
            $(this).prepend("<span class='ui-icon ui-icon-triangle-1-e dup-toggle' />");
        });
        
    });
</script>