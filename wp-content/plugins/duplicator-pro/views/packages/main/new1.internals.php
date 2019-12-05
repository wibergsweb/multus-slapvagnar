<?php
	require_once (DUPLICATOR_PRO_PLUGIN_PATH . 'classes/entities/class.global.entity.php');
	
    /* @var $global DUP_PRO_Global_Entity */
	$global = DUP_PRO_Global_Entity::get_instance();	
?>

<style>
    /* -----------------------------
    PACKAGE OPTS*/
    form#dup-form-opts label {line-height:22px}
    form#dup-form-opts input[type=checkbox] {margin-top:3px}
    form#dup-form-opts textarea, input[type="text"] {width:100%}
    textarea#package_notes {height:37px;}
    div.dpro-name-area label, div.dpro-name-area input {font-size:14px !important}
    div.dpro-notes-add {float:right; margin:-4px 2px 4px 0;}
    div#dpro-notes-area {display:none}

    /*STORAGE: Area*/
    div.storage-filters {display:inline-block; padding: 0 10px 0 10px}
    sup#dpro-storage-title-count {display:inline-block; color: #444; font-weight: normal; margin-top:-3px }
	tr.storage-missing td, tr.storage-missing td a {color: #A62426 !important }

    /*ARCHIVE: Area*/
    form#dup-form-opts div.tabs-panel{max-height:550px; padding:10px; min-height:280px}
    form#dup-form-opts ul li.tabs{font-weight:bold}
    ul.category-tabs li {padding:4px 15px 4px 15px}
    select#archive-format {min-width:100px; margin:1px 0px 4px 0px}
    span#dup-archive-filter-file {color:#A62426; display:none}
    span#dup-archive-filter-db {color:#A62426; display:none}
    div#dup-file-filter-items, div#dup-db-filter-items {padding:5px 0px 0px 0px}
    label.dup-enable-filters {display:inline-block; margin:-5px 0px 5px 0px}
    /* Tab: Files */
    form#dup-form-opts textarea#filter-dirs {height:85px}
    form#dup-form-opts textarea#filter-exts {height:27px}
    form#dup-form-opts textarea#filter-files {height:85px}
    div.dup-quick-links {font-size:11px; float:right; display:inline-block; margin-top:2px; font-style:italic}
    div.dup-tabs-opts-help {font-style:italic; font-size:11px; margin:10px 0px 0px 10px; color:#777}
    /* Tab: Database */
    table#dup-dbtables td {padding:1px 15px 1px 4px}

    /*INSTALLER: Area*/
    div.dup-installer-header-1 {font-weight:bold; padding-bottom:2px; width:100%}
    div.dup-installer-header-2 {font-weight:bold; border-bottom:1px solid #dfdfdf; padding-bottom:2px; width:100%}
    label.chk-labels {display:inline-block; margin-top:1px}
    table.dup-installer-tbl {width:95%; margin-left:20px}
</style>

<form id="dup-form-opts" method="post" action="?page=duplicator-pro&tab=packages&inner_page=new2" data-parsley-validate data-parsley-ui-enabled="true" >
<input type="hidden" id="dup-form-opts-action" name="action" value="">
<input type="hidden" id="dup-form-opts-hash" name="package-hash" value="<?php echo $package_hash; ?>">

<div class="dpro-name-area" id="dpro-package-specific-area">
	<label for="package-name"><b><?php DUP_PRO_U::_e('Name') ?>:</b> </label>
	<div class="dpro-notes-add">
		<button class="button button-small" type="button" onclick="jQuery('#dpro-notes-area').toggle()"><i class="fa fa-pencil-square-o"></i> <?php DUP_PRO_U::_e('Notes') ?></button>
	</div>
	<a href="javascript:void(0)" onclick="DupPro.Pack.ResetName()" title="<?php DUP_PRO_U::_e('Create a new default name') ?>"><i class="fa fa-undo"></i></a> <br/>
	<input id="package-name"  name="package-name" type="text" value="<?php echo $Package->Name ?>" maxlength="40"  data-required="true" data-regexp="^[0-9A-Za-z|_]+$" /> <br/>	
</div>	

<div class="dpro-template-info-area" id="dpro-template-specific-area" style="display:none;">
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><label><?php _e("Package Template"); ?>:</label>                
            </th>
            <td>
                <select data-parsley-ui-enabled="false" onchange="DupPro.Pack.PopulateCurrentTemplate();" style="width: 300px" name="template_id" id="template_id">
                    <?php
                    $templates = DUP_PRO_Package_Template_Entity::get_all();
                    if (count($templates) == 0)
                    {
                        $no_templates = __('No Templates');
                        echo "<option value='-1'>$no_templates</option>";
                    }
                    else
                    {
                        foreach ($templates as $template)
                        {
                            ?>
                            <option value="<?php echo $template->id; ?>"><?php echo $template->name; ?></option>
                            <?php
                        }
                    }
                    ?>
                </select> 

                <div class="dpro-notes-add">
					<button class="button button-small" type="button" onclick="jQuery('#dpro-notes-area').toggle()"><i class="fa fa-pencil-square-o"></i> <?php DUP_PRO_U::_e('Notes') ?></button>
                </div>
            </td>
        </tr>
    </table>
    
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><label><?php DUP_PRO_U::_e("Name"); ?>:</label></th>
            <td><span id="template-name"></span></td>
        </tr>
		 <tr valign="top">
            <th scope="row"><label><?php DUP_PRO_U::_e("Notes"); ?>:</label></th>
            <td><span id="template-notes"><?php echo $Package->Notes ?></span></td>
        </tr>
    </table>
</div>

<div id="dpro-notes-area">
	<label><b><?php DUP_PRO_U::_e('Notes') ?>:</b></label> <br/>
	<textarea id="package-notes" name="package-notes" maxlength="300" /><?php echo $Package->Notes ?></textarea>		
</div>
<br/>

<!-- ===================
META-BOX: STORAGE -->
<div class="dup-box">
	<div class="dup-box-title">
		<i class="fa fa-database"></i> <?php DUP_PRO_U::_e('Storage') ?> <sup id="dpro-storage-title-count"></sup>
		<div class="dup-box-arrow"></div>
	</div>			

	<div class="dup-box-panel" id="dup-pack-storage-panel" style="<?php echo $ui_css_storage ?>">
		<table class="widefat package-tbl">
			<thead>
				<tr>
					<th style='white-space: nowrap; width:10px;'></th>
					<th style='width:275px'><?php DUP_PRO_U::_e('Name') ?></th>
					<th style='width:100px'><?php DUP_PRO_U::_e('Type') ?></th>
					<th style="white-space: nowrap"><?php DUP_PRO_U::_e('Location') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$i = 0;
				$package_storage_ids = array();

				foreach ($Package->upload_infos as $upload_info)
				{
					array_push($package_storage_ids, $upload_info->storage_id);
				}

				foreach ($storage_list as $store) :
					$i++;
					
					$store_type = $store->get_storage_type_string();
					$store_location = $store->get_storage_location_string();
					$is_valid = $store->is_valid();
                                        $is_checked = in_array($store->id, $package_storage_ids) && $is_valid;
					$mincheck   = ($i == 1) ?'data-parsley-mincheck="1" data-parsley-required="true"' : '';
					$row_style  = ($i % 2) ? 'alternate' : '';
					$row_style .= ($is_valid) ? '' : ' storage-missing';
					?>
					<tr class="package-row <?php echo $row_style ?>">
						<td>
							<input class="duppro-storage-input" <?php echo DUP_PRO_U::echo_disabled($is_valid == false); ?> name="_storage_ids[]" onclick="DupPro.Pack.UpdateStorageCount(); return true;" data-parsley-errors-container="#storage_error_container" <?php echo $mincheck; ?> type="checkbox" value="<?php echo $store->id; ?>" <?php DUP_PRO_U::echo_checked($is_checked); ?> />
							<input name="edit_id" type="hidden" value="<?php echo $i ?>" />
						</td>
						<td>
							<a href="?page=duplicator-pro-storage&tab=storage&inner_page=edit&storage_id=<?php echo $store->id ?>" target="_blank">
								<?php 
									echo ($is_valid == false) 
										? '<i class="fa fa-exclamation-triangle"></i>' 
										: (($store_type == 'Local') 
										? '<i class="fa fa-server"></i>' 
										: '<i class="fa fa-cloud"></i>'); 
									echo " {$store->name}";
								?>
							</a>
						</td>
						<td><?php echo $store_type ?></td>
                        <td><?php echo (($store_type == 'Local') || ($store_type == 'Google Drive') || ($store_type == 'Amazon S3'))
									? $store_location
									: "<a href='{$store_location}' target='_blank'>" . urldecode($store_location) . "</a>"; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<table>
			<tr>
				<td style="width: 100%">
					<div id="storage_error_container" class="duplicator-error-container"></div>
				</td>
				<td style="white-space: nowrap">
					<div style="text-align: right; margin:4px 4px -4px 0; padding:0">
						<a href="admin.php?page=duplicator-pro-storage&tab=storage&inner_page=edit" target="_blank">
							[<?php DUP_PRO_U::_e('Create New Storage') ?>]
						</a>
					</div>
				</td>
			</tr>
		</table>
	</div>
</div><br/>
<!-- end meta-box storage  -->

<!-- ===================
 META-BOX: ARCHIVE -->
<div class="dup-box">
	<div class="dup-box-title">
		<i class="fa fa-file-archive-o"></i> <?php DUP_PRO_U::_e('Archive') ?> &nbsp;
		<span style="font-size:13px">
			<span id="dup-archive-filter-file" title="<?php DUP_PRO_U::_e('File filter enabled') ?>"><i class="fa fa-files-o"></i> <i class="fa fa-filter"></i> &nbsp;&nbsp;</span> 
			<span id="dup-archive-filter-db" title="<?php DUP_PRO_U::_e('Database filter enabled') ?>"><i class="fa fa-table"></i> <i class="fa fa-filter"></i></span>	
		</span>

		<div class="dup-box-arrow"></div>
	</div>		
	<div class="dup-box-panel" id="dup-pack-archive-panel" style="<?php echo $ui_css_archive ?>">
		<input type="hidden" name="archive-format" value="ZIP" />
		<!--label for="archive-format"><?php DUP_PRO_U::_e("Format") ?>: </label> &nbsp;

		<select name="archive-format" id="archive-format">
				<option value="ZIP">Zip</option>
				<option value="TAR"></option>
				<option value="TAR-GZIP"></option>
		</select-->

		<!-- ===================
		NESTED TABS -->
		<div class="categorydiv" id="dup-pack-opts-tabs">
			<ul class="category-tabs">
				<li class="tabs"><a href="javascript:void(0)" onclick="DupPro.Pack.ToggleOptTabs(1, this)"><?php DUP_PRO_U::_e('Files') ?></a></li>
				<li><a href="javascript:void(0)" onclick="DupPro.Pack.ToggleOptTabs(2, this)"><?php DUP_PRO_U::_e('Database') ?></a></li>
			</ul>

			<!-- ===================
			TAB2: FILES -->
			<div class="tabs-panel" id="dup-pack-opts-tabs-panel-1">
				<!-- FILTERS -->
				<?php
				$uploads = wp_upload_dir();
				$upload_dir = DUP_PRO_Util::SafePath($uploads['basedir']);
				?>
				<div class="dup-enable-filters">
					<input type="checkbox" id="filter-on" name="filter-on" onclick="DupPro.Pack.ToggleFileFilters()" <?php echo ($Package->Archive->FilterOn) ? "checked='checked'" : ""; ?> />	
					<label for="filter-on"><?php DUP_PRO_U::_e("Enable File Filters") ?></label>
				</div>

				<div id="dup-file-filter-items">
					<label for="filter-dirs" title="<?php DUP_PRO_U::_e("Separate all filters by semicolon"); ?>"><?php DUP_PRO_U::_e("Directories") ?>: </label>
					<div class='dup-quick-links'>
						<a href="javascript:void(0)" onclick="DupPro.Pack.AddExcludePath('<?php echo rtrim(DUPLICATOR_PRO_WPROOTPATH, '/'); ?>')">[<?php DUP_PRO_U::_e("root path") ?>]</a>
						<a href="javascript:void(0)" onclick="DupPro.Pack.AddExcludePath('<?php echo rtrim($upload_dir, '/'); ?>')">[<?php DUP_PRO_U::_e("wp-uploads") ?>]</a>
						<a href="javascript:void(0)" onclick="DupPro.Pack.AddExcludePath('<?php echo DUP_PRO_Util::SafePath(WP_CONTENT_DIR); ?>/cache')">[<?php DUP_PRO_U::_e("cache") ?>]</a>
						<a href="javascript:void(0)" onclick="if(DupPro.Pack.mode == 'manual') { jQuery('#filter-dirs').val('') };"><?php DUP_PRO_U::_e("(clear)") ?></a>
                                     <!--rsr todo later-->           <button type="button" style="display:none; margin-left:10px" onclick="DupPro.Pack.BrowseDirectory();"><?php DUP_PRO_U::_e('Browse'); ?></button>
					</div>
					<textarea name="filter-dirs" id="filter-dirs" placeholder="/full_path/exclude_path1;/full_path/exclude_path2;"><?php echo str_replace(";", ";\n", esc_textarea($Package->Archive->FilterDirs)) ?></textarea><br/>
					<label class="no-select" title="<?php DUP_PRO_U::_e("Separate all filters by semicolon"); ?>"><?php DUP_PRO_U::_e("File Extensions") ?>:</label>
					<div class='dup-quick-links'>
						<a href="javascript:void(0)" onclick="DupPro.Pack.AddExcludeExts('avi;mov;mp4;mpeg;mpg;swf;wmv;aac;m3u;mp3;mpa;wav;wma')">[<?php DUP_PRO_U::_e("media") ?>]</a>
						<a href="javascript:void(0)" onclick="DupPro.Pack.AddExcludeExts('zip;rar;tar;gz;bz2;7z')">[<?php DUP_PRO_U::_e("archive") ?>]</a>
						<a href="javascript:void(0)" onclick="if(DupPro.Pack.mode == 'manual') { jQuery('#filter-exts').val('') };"><?php DUP_PRO_U::_e("(clear)") ?></a>
					</div>
					<textarea name="filter-exts" id="filter-exts" placeholder="ext1;ext2;ext3;"><?php echo esc_textarea($Package->Archive->FilterExts); ?></textarea><br/>

                    <label class="no-select" title="<?php DUP_PRO_U::_e("Separate all filters by semicolon"); ?>"><?php DUP_PRO_U::_e("Files") ?>:</label>
                    <div class='dup-quick-links'>
                        <a href="javascript:void(0)" onclick="DupPro.Pack.AddExcludeFilePath('<?php echo rtrim(DUPLICATOR_PRO_WPROOTPATH, '/'); ?>')"><?php DUP_PRO_U::_e("(file path)") ?></a>
						<a href="javascript:void(0)" onclick="if(DupPro.Pack.mode == 'manual') { jQuery('#filter-files').val('') };"><?php DUP_PRO_U::_e("(clear)") ?></a>
					</div>
                    <textarea name="filter-files" id="filter-files" placeholder="/full_path/exclude_file_1.ext;/full_path/exclude_file2.ext"><?php echo str_replace(";", ";\n", esc_textarea($Package->Archive->FilterFiles)) ?></textarea>
                    
					<div class="dup-tabs-opts-help">
						<?php DUP_PRO_U::_e("The directory paths and extensions above will be be excluded from the archive file if enabled is checked."); ?> <br/>
						<?php DUP_PRO_U::_e("Use the full path for directories and semicolons to separate all items."); ?>
					</div>
				</div>
			</div>

			<!-- ===================
			TAB3: DATABASE -->
			<div class="tabs-panel" id="dup-pack-opts-tabs-panel-2" style="display: none;">
				<div class="dup-enable-filters">						
					<table>
						<tr>
							<td><input type="checkbox" id="dbfilter-on" name="dbfilter-on" onclick="DupPro.Pack.ToggleDBFilters()" <?php echo ($Package->Database->FilterOn) ? "checked='checked'" : ""; ?> /></td>
							<td><label for="dbfilter-on"><?php DUP_PRO_U::_e("Enable Table Filters") ?> &nbsp;</label> </td>
							<td><div class="dup-tabs-opts-help" style="margin:5px 0px 0px 0px"><?php DUP_PRO_U::_e("checked tables are excluded") ?></div></td>
						</tr>
					</table>
				</div>
				<div id="dup-db-filter-items">
					<a href="javascript:void(0)" id="dball" onclick="jQuery('#dup-dbtables .checkbox').prop('checked', true).trigger('click');">[ <?php DUP_PRO_U::_e('Include All'); ?> ]</a> &nbsp; 
					<a href="javascript:void(0)" id="dbnone" onclick="jQuery('#dup-dbtables .checkbox').prop('checked', false).trigger('click');">[ <?php DUP_PRO_U::_e('Exclude All'); ?> ]</a>
					<div style="font-stretch:ultra-condensed; font-family: Calibri; white-space: nowrap">
						<?php
						$tables = $wpdb->get_results("SHOW FULL TABLES FROM `" . DB_NAME . "` WHERE Table_Type = 'BASE TABLE' ", ARRAY_N);
						$num_rows = count($tables);
						echo '<table id="dup-dbtables"><tr><td valign="top">';
						$next_row = round($num_rows / 3, 0);
						$counter = 0;
						$tableList = explode(',', $Package->Database->FilterTables);
						foreach ($tables as $table)
						{
							if (in_array($table[0], $tableList))
							{
								$checked = 'checked="checked"';
								$css = 'text-decoration:line-through';
							}
							else
							{
								$checked = '';
								$css = '';
							}
							echo "<label for='dbtables-{$table[0]}' style='{$css}'><input class='checkbox dbtable' $checked type='checkbox' name='dbtables[]' id='dbtables-{$table[0]}' value='{$table[0]}' onclick='DupPro.Pack.ExcludeTable(this)' />&nbsp;{$table[0]}</label><br />";
							$counter++;
							if ($next_row <= $counter)
							{
								echo '</td><td valign="top">';
								$counter = 0;
							}
						}
						echo '</td></tr></table>';
						?>
					</div>
					<div class="dup-tabs-opts-help">
						<?php 
							DUP_PRO_U::_e("Checked tables will not be added to the database script.");
							DUP_PRO_U::_e("Excluding certain tables can possibly cause your site or plugins to not work correctly after install!");
						?>
					</div>	
				</div>
				<table style="margin-top:10px">
						<tr>
							<td>								
								<input <?php DUP_PRO_U::echo_disabled($global->package_mysqldump == false) ?> type="checkbox" name="old-sql-compatibility" id="old-sql-compatibility"  />
							</td>			
							<td>
								<?php DUP_PRO_U::_e("Legacy SQL") ?> 
								<i class="fa fa-question-circle" data-tooltip-title="<?php DUP_PRO_U::_e("MySQL Note:"); ?>" data-tooltip="<?php DUP_PRO_U::_e('Use if having problems installing packages on an older MySQL server (< 5.5.3). Only affects mysqldump database mode. Setting always unchecked for new package.'); ?>"></i>
							</td>																				
						</tr>
					</table>
			</div>
		</div>	                
	</div>
</div><br/>
<!-- end meta-box options  -->


<!-- ===================
META-BOX: INSTALLER -->
<div class="dup-box">
	<div class="dup-box-title">
		<i class="fa fa-bolt"></i> <?php DUP_PRO_U::_e('Installer') ?>
		<div class="dup-box-arrow"></div>
	</div>			

	<div class="dup-box-panel" id="dup-pack-installer-panel" style="<?php echo $ui_css_installer ?>">
		<div class="dup-installer-header-1"><?php DUP_PRO_U::_e('STEP 1 - INPUTS'); ?></div><br/>
		<table class="dup-installer-tbl">
			<tr>
				<td colspan="2"><div class="dup-installer-header-2"><?php DUP_PRO_U::_e("MySQL Server") ?></div></td>
			</tr>
			<tr>
				<td style="width:130px"><?php DUP_PRO_U::_e("Host") ?></td>
				<td><input type="text" name="dbhost" id="dbhost" value="<?php echo $Package->Installer->OptsDBHost ?>"  maxlength="200" placeholder="localhost"/></td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("Database") ?></td>
				<td><input type="text" name="dbname" id="dbname" value="<?php echo $Package->Installer->OptsDBName ?>" maxlength="100" placeholder="mydatabaseName" /></td>
			</tr>							
			<tr>
				<td><?php DUP_PRO_U::_e("User") ?></td>
				<td><input type="text" name="dbuser" id="dbuser" value="<?php echo $Package->Installer->OptsDBUser ?>"  maxlength="100" placeholder="databaseUserName" /></td>
			</tr>
			<tr>
				<td colspan="2"><div class="dup-installer-header-2"><?php DUP_PRO_U::_e("Advanced Options") ?></div></td>
			</tr>						
			<tr>
				<td colspan="2">
					<table>
						<tr>
							<td style="width:130px"><?php DUP_PRO_U::_e("SSL") ?></td>
							<td style="padding-right: 20px; white-space: nowrap">
								<input type="checkbox" name="ssl-admin" id="ssl-admin" <?php echo ($Package->Installer->OptsSSLAdmin) ? "checked='checked'" : ""; ?>  />
								<label class="chk-labels" for="ssl-admin"><?php DUP_PRO_U::_e("Enforce on Admin") ?></label>
							</td>
							<td>
								<input type="checkbox" name="ssl-login" id="ssl-login" <?php echo ($Package->Installer->OptsSSLLogin) ? "checked='checked'" : ""; ?>  />
								<label class="chk-labels" for="ssl-login"><?php DUP_PRO_U::_e("Enforce on Logins") ?></label>
							</td>
						</tr>
						<tr>
							<td><?php DUP_PRO_U::_e("Cache") ?></td>									
							<td style="padding-right: 20px; white-space: nowrap">
								<input type="checkbox" name="cache-wp" id="cache-wp" <?php echo ($Package->Installer->OptsCacheWP) ? "checked='checked'" : ""; ?>  />
								<label class="chk-labels" for="cache-wp"><?php DUP_PRO_U::_e("Keep Enabled") ?></label>	
							</td>
							<td>
								<input type="checkbox" name="cache-path" id="cache-path" <?php echo ($Package->Installer->OptsCachePath) ? "checked='checked'" : ""; ?>  />
								<label class="chk-labels" for="cache-path"><?php DUP_PRO_U::_e("Keep Home Path") ?></label>			
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table><br />

		<div class="dup-installer-header-1"><?php DUP_PRO_U::_e('STEP 2 - INPUTS'); ?></div>
		<table class="dup-installer-tbl">
			<tr>
				<td style="width:130px"><?php DUP_PRO_U::_e("New URL") ?></td>
				<td><input type="text" name="url-new" id="url-new" value="<?php echo $Package->Installer->OptsURLNew ?>" placeholder="http://mynewsite.com" /></td>
			</tr>
		</table>
		<div class="dup-tabs-opts-help">
			<?php DUP_PRO_U::_e("The installer can have these fields pre-filled at install time."); ?> <b><?php DUP_PRO_U::_e('All values are optional.'); ?></b>
		</div>		
	</div>		
</div><br/>
<!-- end meta-box: installer  -->

<div class="dup-button-footer">
	<input type="button" value="<?php DUP_PRO_U::_e("Reset") ?>" class="button button-large" <?php echo ($dup_tests['Success']) ? '' : 'disabled="disabled"'; ?> onclick="DupPro.Pack.ResetSettings()" />
	<input id="button-next" type="submit" value="<?php DUP_PRO_U::_e("Next") ?> &#9658;" class="button button-primary button-large" <?php echo ($dup_tests['Success']) ? '' : 'disabled="disabled"'; ?> />
</div>
</form>

<div id="duppro-directory-browser">
     
</div>

<script>
    var packageTemplates = [];

	<?php
	$counter = 0;
	$templates = DUP_PRO_Package_Template_Entity::get_all();

	foreach ($templates as $template)
	{
		/* @var $template DUP_PRO_Package_Template_Entity */
		$json = json_encode($template);
		echo "    packageTemplates[$counter] = $json;\n\r\n\r";
		$counter++;
	}
	?>
            
    jQuery(document).ready(function ($) {
        var DUP_PRO_NAMEDEFAULT = '<?php echo $default_name ?>';
        var DUP_PRO_NAMELAST = $('#package-name').val();

     //rsr todo later   $("#duppro-directory-browser").fileTree({root: null});
        
        // Template-specific Functions
        DupPro.Pack.GetTemplateById = function (templateId) {
            for (i = 0; i < packageTemplates.length; i++) {
                var currentTemplate = packageTemplates[i];
                if (currentTemplate.id == templateId) {
                    return currentTemplate;
                }
            }
            return null;
        };
        
//  rsr todo later
//                DupPro.Pack.BrowseDirectory = function() {
//                        
//            $("#duppro-directory-browser").dialog();
//        };

        DupPro.Pack.PopulateCurrentTemplate = function () {

            var selectedId = $('#template_id').val();
            var selectedTemplate = DupPro.Pack.GetTemplateById(selectedId);
            if (selectedTemplate != null) {

                $("#template-name").text(selectedTemplate.name);
                $("#package-notes").text(selectedTemplate.notes);

                $("#filter-on").prop("checked", selectedTemplate.archive_filter_on);
                $("#filter-dirs").val(selectedTemplate.archive_filter_dirs);
               // alert('dirs = ' + selectedTemplate.archive_filter_dirs);
                $("#filter-exts").val(selectedTemplate.archive_filter_exts);
     		$("#filter-files").val(selectedTemplate.archive_filter_files);
                $("#dbfilter-on").prop("checked", selectedTemplate.database_filter_on);
		$("#old-sql-compatibility").prop("checked", selectedTemplate.database_old_sql_compatibility);

                var databaseFilterTables = selectedTemplate.database_filter_tables.split(",");
                $("#dup-dbtables input").prop("checked", false).css('text-decoration', 'none');

                for (filterTableKey in databaseFilterTables)
                {
                    var filterTable = databaseFilterTables[filterTableKey];

                    var selector = "#dbtables-" + filterTable;
                    $(selector).prop("checked", true).css('text-decoration', 'line-through');
                }

                $("#dbhost").val(selectedTemplate.installer_opts_db_host);
                $("#dbname").val(selectedTemplate.installer_opts_db_name);
                $("#dbuser").val(selectedTemplate.installer_opts_db_user);
                $("#url-new").val(selectedTemplate.installer_opts_url_new);

                $("#ssl-admin").prop("checked", selectedTemplate.installer_opts_ssl_admin);
                $("#ssl-login").prop("checked", selectedTemplate.installer_opts_ssl_login);
                $("#cache-wp").prop("checked", selectedTemplate.installer_opts_cache_wp);
                $("#cache-path").prop("checked", selectedTemplate.installer_opts_cache_path);
            } else {

                console.log("Template ID doesn't exist?? " + selectedId);
            }
        }
        
        DupPro.Pack.CheckForInvalidStorage = function() {
                      
            var num_invalid = $('input[is_valid=0]:checked').length;
            
            //alert('num_invalid=' + num_invalid);
            if(num_invalid > 0) {
                
                alert('<?php DUP_PRO_U::_e('Select a template with valid storage locations.'); ?>');
                return false;
            }
                
            return true;
        };        

        /* METHOD: Toggle Archive File/DB tabs */
        DupPro.Pack.ToggleOptTabs = function (tab, label) {
            $('.category-tabs li').removeClass('tabs');
            $(label).parent().addClass('tabs');
            $('#dup-pack-opts-tabs-panel-1, #dup-pack-opts-tabs-panel-2').hide();
            $('#dup-pack-opts-tabs-panel-' + tab).show();
        }

	/* METHOD: Toggle Archive file filter red icon */
        DupPro.Pack.ToggleFileFilters = function () {
            var $filterItems = $('#dup-file-filter-items');
            if ($("#filter-on").is(':checked')) {
                $filterItems.removeAttr('disabled').css({color: '#000'});
                $('#filter-exts, #filter-dirs, #filter-files').removeAttr('readonly').css({color: '#000'});
                $('#dup-archive-filter-file').show();
            } else {
                $filterItems.attr('disabled', 'disabled').css({color: '#999'});
                $('#filter-dirs, #filter-exts, #filter-files').attr('readonly', 'readonly').css({color: '#999'});
                $('#dup-archive-filter-file').hide();
            }
        };

        /* METHOD: Toggle Database table filter red icon */
        DupPro.Pack.ToggleDBFilters = function () {
            var $filterItems = $('#dup-db-filter-items');

            if ($("#dbfilter-on").is(':checked')) {
                $filterItems.removeAttr('disabled').css({color: '#000'});
                $('#dup-dbtables input').removeAttr('readonly').css({color: '#000'});
                $('#dup-archive-filter-db').show();
            } else {
                $filterItems.attr('disabled', 'disabled').css({color: '#999'});
                $('#dup-dbtables input').attr('readonly', 'readonly').css({color: '#999'});
                $('#dup-archive-filter-db').hide();
            }
        };

        /* METHOD: Formats file directory path name on seperate line of textarea */
        DupPro.Pack.AddExcludePath = function (path) {
            if(DupPro.Pack.mode == "manual") {

                var text = $("#filter-dirs").val() + path + ';\n';
                $("#filter-dirs").val(text);
            }
        };

        /*	Appends a path to the extention filter  */
        DupPro.Pack.AddExcludeExts = function (path) {
            if(DupPro.Pack.mode == "manual") {
                var text = $("#filter-exts").val() + path + ';';
                $("#filter-exts").val(text);
            }
        };

	DupPro.Pack.AddExcludeFilePath = function (path) {
            if(DupPro.Pack.mode == "manual") {

                var text = $("#filter-files").val() + path + '/file.ext;\n';
                $("#filter-files").val(text);
            }
        };

        DupPro.Pack.ResetSettings = function () {
            var key = 'duplicator_pro_package_active';
            var result = confirm('<?php DUP_PRO_U::_e("This will reset all of the current package settings.  Would you like to continue?"); ?>');
            if (!result)
                return;

            jQuery('#dup-form-opts-action').val(key);
            jQuery('#dup-form-opts').attr('action', '?page=duplicator-pro&tab=new1')
            jQuery('#dup-form-opts').submit();
        }

        DupPro.Pack.ResetName = function () {
            var current = $('#package-name').val();
            $('#package-name').val((current == DUP_PRO_NAMELAST) ? DUP_PRO_NAMEDEFAULT : DUP_PRO_NAMELAST)
        }

        DupPro.Pack.ExcludeTable = function (check) {
            var $cb = $(check);
            if ($cb.is(":checked")) {
                $cb.closest("label").css('textDecoration', 'line-through');
            } else {
                $cb.closest("label").css('textDecoration', 'none');
            }
        }

        DupPro.Pack.UpdateStorageCount = function () {


            var store_count = $('#dup-pack-storage-panel input[name="_storage_ids[]"]:checked').size();

            $('#dpro-storage-title-count').html('(' + store_count + ')');
            (store_count == 0)
                    ? $('#dpro-storage-title-count').css({'color': 'red', 'font-weight': 'bold'})
                    : $('#dpro-storage-title-count').css({'color': '#444', 'font-weight': 'normal'});
        }

        //Init: Toggle OptionTabs
        DupPro.Pack.ToggleFileFilters();
        DupPro.Pack.ToggleDBFilters();
		DupPro.Pack.UpdateStorageCount();
    });
</script>
