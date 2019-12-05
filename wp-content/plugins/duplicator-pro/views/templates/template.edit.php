<?php
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.package.template.entity.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . 'classes/entities/class.global.entity.php');

global $wp_version;
global $wpdb;

$nonce_action = 'duppro-template-edit';

$was_updated = false;
$package_template_id = isset($_REQUEST['package_template_id']) ? esc_html($_REQUEST['package_template_id']) : -1;
$package_templates = DUP_PRO_Package_Template_Entity::get_all();
$package_template_count = count($package_templates);

$view_state = DUP_PRO_UI::GetViewStateArray();
$ui_css_archive = (isset($view_state['dup-template-archive-panel']) && $view_state['dup-template-archive-panel']) ? 'display:block' : 'display:none';
$ui_css_install = (isset($view_state['dup-template-install-panel']) && $view_state['dup-template-install-panel']) ? 'display:block' : 'display:none';

/* @var $package_template DUP_PRO_Package_Template_Entity */
if ($package_template_id == -1)
{
    $package_template = new DUP_PRO_Package_Template_Entity();
    $edit_create_text = DUP_PRO_U::__('Add New');
}
else
{
    $package_template = DUP_PRO_Package_Template_Entity::get_by_id($package_template_id);
    DUP_PRO_U::log_object("getting template $package_template_id", $package_template);
    $edit_create_text = DUP_PRO_U::__('Edit') . ' ' . $package_template->name;
}

if (isset($_REQUEST['action']))
{
    check_admin_referer($nonce_action);
    if ($_REQUEST['action'] == 'save')
    {
        if (isset($_REQUEST['_database_filter_tables']))
        {
            $package_template->database_filter_tables = implode(',', $_REQUEST['_database_filter_tables']);
        }
        else
        {
            $package_template->database_filter_tables = '';
        }

        $package_template->archive_filter_dirs = isset($_REQUEST['_archive_filter_dirs']) ? DUP_PRO_Package::parse_directory_filter($_REQUEST['_archive_filter_dirs']) : '';
        $package_template->archive_filter_exts = isset($_REQUEST['_archive_filter_exts']) ? DUP_PRO_Package::parse_extension_filter($_REQUEST['_archive_filter_exts']) : '';
        $package_template->archive_filter_files = isset($_REQUEST['_archive_filter_files']) ? DUP_PRO_Package::parse_file_filter($_REQUEST['_archive_filter_files']) : '';

        DUP_PRO_U::log_object('request', $_REQUEST);

        // Checkboxes don't set post values when off so have to manually set these
        $package_template->set_post_variables($_REQUEST);
        $package_template->save();
        $was_updated = true;
        $edit_create_text = DUP_PRO_U::__('Edit') . ': ' . $package_template->name;
    }
    else if ($_REQUEST['action'] == 'copy-template')
    {
        $source_template_id = $_REQUEST['duppro-source-template-id'];
         
        if($source_template_id != -1)
        {
            $package_template->copy_from_source_id($source_template_id);
            $package_template->save();
        }
    }
}

$uploads = wp_upload_dir();
$upload_dir = DUP_PRO_Util::SafePath($uploads['basedir']);
?>

<style>
    table.dpro-edit-toolbar select {float:left}
    table.dpro-edit-toolbar input[type=button] {margin-top:-2px}
    div#dpro-notes-area {display:none}
    div#dpro-notes-add {float:right; margin:-4px 2px 4px 0;}
    div.dpro-template-general {margin:8px 0 10px 0}
    div.dpro-template-general label {font-weight: bold}
    div.dpro-template-general input, textarea {width:100%}
    textarea#_archive_filter_dirs {width:100%; height:75px}
    textarea#_archive_filter_files {width:100%; height:75px}
    input#_archive_filter_exts {width:100%}
    b.dpro-hdr {display:block; font-size:16px;  margin:3px 0 3px 0; padding:3px 0 3px 0}
    div.dup-quick-links {font-size:11px; float:right; display:inline-block; margin-bottom:2px; font-style:italic}
</style>


<form id="dpro-template-form" data-parsley-validate data-parsley-ui-enabled="true" action="<?php echo $edit_template_url; ?>" method="post">
    <?php wp_nonce_field($nonce_action); ?>
    <input type="hidden" id="dpro-template-form-action" name="action" value="save">
    <input type="hidden" name="package_template_id" value="<?php echo $package_template->id; ?>">

    <!-- ====================
    SUB-TABS -->
    <?php if ($was_updated) : ?>
        <div class="updated below-h2"><p><?php DUP_PRO_U::_e('Template Updated'); ?></p></div>
    <?php endif; ?>

    <!-- ====================
    TOOL-BAR -->
    <table class="dpro-edit-toolbar">
        <tr>
            <td>
                <?php if ($package_template_count > 0) : ?>
                    <select name="duppro-source-template-id">
                        <option value="-1" selected="selected"><?php _e("Copy From"); ?></option>
                        <?php foreach ($package_templates as $copy_package_template) : 
                            if($copy_package_template->id != $package_template->id)
                            { ?>
                        
                        <option value="<?php echo $copy_package_template->id ?>"><?php echo $copy_package_template->name; ?></option>
                        
                            <?php }
                            endforeach; ?>
                    </select>
                    <input type="button" class="button action" value="Apply" onclick="DupPro.Template.Copy()">
                <?php else : ?>
                    <select disabled="disabled"><option value="-1" selected="selected"><?php _e("Copy From"); ?></option></select>
                    <input type="button" class="button action" value="Apply" onclick="DupPro.Template.Copy()"  disabled="disabled">
                <?php endif; ?>
            </td>
            <td>
                <a href="<?php echo $templates_tab_url; ?>" class="add-new-h2"><i class="fa fa-files-o"></i> <?php DUP_PRO_U::_e('All Templates'); ?></a>
                <span><?php echo $edit_create_text; ?></span>
            </td>
        </tr>
    </table>
    <hr class="dpro-edit-toolbar-divider"/>

    <div class="dpro-template-general">
        <label><?php _e("Package Name"); ?>:</label>
        <div id="dpro-notes-add">
            <button class="button button-small" type="button" onclick="jQuery('#dpro-notes-area').toggle()"><i class="fa fa-pencil-square-o"></i> <?php DUP_PRO_U::_e('Notes') ?></button>
        </div>
        <input type="text" id="template-name" name="name" data-parsley-errors-container="#template_name_error_container" data-parsley-required="true" value="<?php echo $package_template->name; ?>" autocomplete="off">
        <div id="template_name_error_container" class="duplicator-error-container"></div>
        <div id="dpro-notes-area">
            <label><?php _e("Notes"); ?>:</label> <br/>
            <textarea id="template-notes" name="notes" style="height:50px"><?php echo $package_template->notes; ?></textarea>
        </div>
    </div>
    
    <!-- ===============================
    ARCHIVE -->
    <div class="dup-box">
        <div class="dup-box-title">
            <i class="fa fa-file-archive-o"></i> <?php DUP_PRO_U::_e('Archive') ?>
            <div class="dup-box-arrow"></div>
        </div>			
        <div class="dup-box-panel" id="dup-template-archive-panel" style="<?php echo $ui_css_archive ?>">

            <!-- FILES -->
            <b class="dpro-hdr"><i class="fa fa-files-o"></i> <?php DUP_PRO_U::_e('FILES'); ?></b>
            <table class="form-table">              
                <tr valign="top">
                    <th scope="row"><label for="archive_filter_on"><?php _e("File Filters"); ?></label></th>
                    <td>
                        <input id="archive_filter_on" type="checkbox" <?php DUP_PRO_U::echo_checked($package_template->archive_filter_on) ?> name="archive_filter_on" />
                        <label for="archive_filter_on"><?php _e("Enable"); ?></label>
                    </td>
                </tr>	
                <tr valign="top">
                    <th scope="row" style="padding-top:26px;"><label><?php _e("Directories"); ?></label></th>
                    <td>
                        <div class='dup-quick-links'>
                            <a href="javascript:void(0)" onclick="DupPro.Template.AddExcludePath('<?php echo rtrim(DUPLICATOR_PRO_WPROOTPATH, '/'); ?>')">[<?php DUP_PRO_U::_e("root path") ?>]</a>
                            <a href="javascript:void(0)" onclick="DupPro.Template.AddExcludePath('<?php echo rtrim($upload_dir, '/'); ?>')">[<?php DUP_PRO_U::_e("wp-uploads") ?>]</a>
                            <a href="javascript:void(0)" onclick="DupPro.Template.AddExcludePath('<?php echo DUP_PRO_Util::SafePath(WP_CONTENT_DIR); ?>/cache')">[<?php DUP_PRO_U::_e("cache") ?>]</a>
                            <a href="javascript:void(0)" onclick="jQuery('#_archive_filter_dirs').val('')"><?php DUP_PRO_U::_e("(clear)") ?></a>
                        </div>
                        <textarea name="_archive_filter_dirs" id="_archive_filter_dirs" placeholder="/full_path/exclude_path1;/full_path/exclude_path2;">
                            <?php echo str_replace(";", ";\n", esc_textarea($package_template->archive_filter_dirs)) ?>
                        </textarea>
                    </td>
                </tr>	
                <tr valign="top">
                    <th scope="row" style="vertical-align:middle"><label><?php _e("Extensions"); ?></label></th>

                    <td>
                        <div class='dup-quick-links'>
                            <a href="javascript:void(0)" onclick="DupPro.Template.AddExcludeExts('avi;mov;mp4;mpeg;mpg;swf;wmv;aac;m3u;mp3;mpa;wav;wma')">[<?php DUP_PRO_U::_e("media") ?>]</a>
                            <a href="javascript:void(0)" onclick="DupPro.Template.AddExcludeExts('zip;rar;tar;gz;bz2;7z')">[<?php DUP_PRO_U::_e("archive") ?>]</a>
                            <a href="javascript:void(0)" onclick="jQuery('#_archive_filter_exts').val('')"><?php DUP_PRO_U::_e("(clear)") ?></a>
                        </div>
                        <input type="text" name="_archive_filter_exts" id="_archive_filter_exts" value="<?php echo $package_template->archive_filter_exts; ?>" placeholder="ext1;ext2;ext3"></td>
                </tr>	
                <tr valign="top">
                    <th scope="row"><label><?php _e("Files"); ?></label></th>

                    <td>
                        <div class='dup-quick-links'>
                            <a href="javascript:void(0)" onclick="DupPro.Template.AddExcludeFilePath('<?php echo rtrim(DUPLICATOR_PRO_WPROOTPATH, '/'); ?>')">[<?php DUP_PRO_U::_e("file path") ?>]</a>
                            <a href="javascript:void(0)" onclick="jQuery('#_archive_filter_files').val('')"><?php DUP_PRO_U::_e("(clear)") ?></a>
                        </div>
                        <textarea name="_archive_filter_files" id="_archive_filter_files" placeholder="/full_path/exclude_file_1.ext;/full_path/exclude_file2.ext"><?php echo str_replace(";", ";\n", esc_textarea($package_template->archive_filter_files)) ?></textarea>
                    </td>
                </tr>	
            </table>
            <br/>

            <!-- DATABASE -->
            <b class="dpro-hdr"><i class="fa fa-table"></i> <?php DUP_PRO_U::_e('DATABASE'); ?></b>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label><?php _e("Table Filters"); ?></label></th>
                    <td><input type="checkbox" <?php DUP_PRO_U::echo_checked($package_template->database_filter_on) ?> name="_database_filter_on" /><?php _e("Enable"); ?></td>
                </tr>	                
                <tr valign="top">
                    <th scope="row"></th>
                    <td>
                        <div id="dup-db-filter-items">
                            <a href="javascript:void(0)" id="dball" onclick="jQuery('#dup-dbtables .checkbox').prop('checked', true).trigger('click');">[ <?php DUP_PRO_U::_e('Include All'); ?> ]</a> &nbsp; 
                            <a href="javascript:void(0)" id="dbnone" onclick="jQuery('#dup-dbtables .checkbox').prop('checked', false).trigger('click');">[ <?php DUP_PRO_U::_e('Exclude All'); ?> ]</a>
                            <div style="font-family: Calibri; white-space: nowrap">
                                <?php
                                $tables = $wpdb->get_results("SHOW FULL TABLES FROM `" . DB_NAME . "` WHERE Table_Type = 'BASE TABLE' ", ARRAY_N);
                                          
                                $num_rows = count($tables);
                                echo '<table id="dup-dbtables"><tr><td valign="top">';
                                $next_row = round($num_rows / 3, 0);
                                $counter = 0;
                                $tableList = explode(',', $package_template->database_filter_tables);
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
                                    echo "<label for='_database_filter_tables-{$table[0]}' style='{$css}'>" .
                                    "<input class='checkbox dbtable' $checked type='checkbox' name='_database_filter_tables[]' id='_database_filter_tables-{$table[0]}' value='{$table[0]}' onclick='DupPro.Template.ExcludeTable(this)' />&nbsp;{$table[0]}" .
                                    "</label><br />";
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
                                <?php DUP_PRO_U::_e("Checked tables will not be added to the database script.  Excluding certain tables can possibly cause your site or plugins to not work correctly after install!"); ?>
                            </div>
                        </div>

                    </td>
                </tr>	 
				<tr valign="top">
                    <th scope="row">
						<label><?php _e("Legacy SQL"); ?><i style="margin-left:4px" title="<?php DUP_PRO_U::_e('Use if having problems installing packages on an older MySQL server (< 5.5.3). Only affects mysqldump database mode.'); ?>" class="fa fa-question-circle"></i></label>
					</th>
                    <td>
						<input type="checkbox" <?php DUP_PRO_U::echo_checked($package_template->database_old_sql_compatibility) ?> name="_database_old_sql_compatibility">Yes		
					</td>					
                </tr>
            </table>
        </div>
    </div>
    <br />


    <!-- ===============================
    INSTALLER -->
    <div class="dup-box">
        <div class="dup-box-title">
            <i class="fa fa-bolt"></i> <?php DUP_PRO_U::_e('Installer') ?>
            <div class="dup-box-arrow"></div>
        </div>			
        <div class="dup-box-panel" id="dup-template-install-panel" style="<?php echo $ui_css_install ?>">

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label><?php _e("Host"); ?></label></th>
                    <td><input type="text" placeholder="localhost" name="installer_opts_db_host" value="<?php echo $package_template->installer_opts_db_host; ?>"></td>
                </tr>	                
                <tr valign="top">
                    <th scope="row"><label><?php _e("Database Name"); ?></label></th>
                    <td><input type="text" placeholder="mydatabaseName" name="installer_opts_db_name" value="<?php echo $package_template->installer_opts_db_name; ?>"></td>
                </tr>	
                <tr valign="top">
                    <th scope="row"><label><?php _e("Database User"); ?></label></th>
                    <td><input type="text" placeholder="databaseUserName" name="installer_opts_db_user" value="<?php echo $package_template->installer_opts_db_user; ?>"></td>
                </tr>	
                <tr valign="top">
                    <th scope="row"><label><?php _e("New URL"); ?></label></th>
                    <td><input type="text" placeholder="http://mynewsite.com" name="installer_opts_url_new" value="<?php echo $package_template->installer_opts_url_new; ?>"></td>
                </tr>					
                <tr valign="top">
                    <th scope="row"><label><?php _e("Enforce SSL On Admin"); ?></label></th>
                    <td><input type="checkbox" <?php DUP_PRO_U::echo_checked($package_template->installer_opts_ssl_admin) ?> name="_installer_opts_ssl_admin">Yes</td>
                </tr>	
                <tr valign="top">
                    <th scope="row"><label><?php _e("Enforce SSL on Logins"); ?></label></th>
                    <td><input type="checkbox" <?php DUP_PRO_U::echo_checked($package_template->installer_opts_ssl_login) ?> name="_installer_opts_ssl_login">Yes</td>
                </tr>	
                <tr valign="top">
                    <th scope="row"><label><?php _e("Keep Cache Enabled"); ?></label></th>
                    <td><input type="checkbox" <?php DUP_PRO_U::echo_checked($package_template->installer_opts_cache_wp) ?> name="_installer_opts_cache_wp" >Yes</td>
                </tr>	
                <tr valign="top">
                    <th scope="row"><label><?php _e("Keep Home Path"); ?></label></th>
                    <td><input type="checkbox" <?php DUP_PRO_U::echo_checked($package_template->installer_opts_cache_path) ?> name="_installer_opts_cache_path">Yes</td>
                </tr>							
            </table>
        </div>
    </div>
    <br/>
    <button class="button button-primary" type="submit"><?php DUP_PRO_U::_e('Save Template'); ?></button>
</form>


<script>
    jQuery(document).ready(function ($) {

        $('#_archive_filter_dirs').val($('#_archive_filter_dirs').val().trim());

        DupPro.Template.ExcludeTable = function (check) {
            var $cb = $(check);
            if ($cb.is(":checked")) {
                $cb.closest("label").css('textDecoration', 'line-through');
            } else {
                $cb.closest("label").css('textDecoration', 'none');
            }
        }

        /* METHOD: Formats file directory path name on seperate line of textarea */
        DupPro.Template.AddExcludePath = function (path) {
            var text = $("#_archive_filter_dirs").val() + path + ';\n';
            $("#_archive_filter_dirs").val(text);
        };

        /*	Appends a path to the extention filter  */
        DupPro.Template.AddExcludeExts = function (path) {
            var text = $("#_archive_filter_exts").val() + path + ';';
            $("#_archive_filter_exts").val(text);
        };
        
        /* METHOD: Formats file path name on seperate line of textarea */
        DupPro.Template.AddExcludeFilePath = function (path) {
            var text = $("#_archive_filter_files").val() + path + '/file.ext;\n';
            $("#_archive_filter_files").val(text);
        };

        DupPro.Template.Copy = function() {
            
            $("#dpro-template-form-action").val('copy-template');
            $("#dpro-template-form").parsley().destroy();
            $("#dpro-template-form").submit();
        };
    });
</script>