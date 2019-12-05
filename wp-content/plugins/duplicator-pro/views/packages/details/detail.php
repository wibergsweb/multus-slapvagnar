<?php
$package = DUP_PRO_Package::get_by_id($package_id);
$global = DUP_PRO_Global_Entity::get_instance();

$view_state = DUP_PRO_UI::GetViewStateArray();
$ui_css_general = (isset($view_state['dup-package-dtl-general-panel']) && $view_state['dup-package-dtl-general-panel']) ? 'display:block' : 'display:none';
$ui_css_storage = (isset($view_state['dup-package-dtl-storage-panel']) && $view_state['dup-package-dtl-storage-panel']) ? 'display:block' : 'display:none';
$ui_css_archive = (isset($view_state['dup-package-dtl-archive-panel']) && $view_state['dup-package-dtl-archive-panel']) ? 'display:block' : 'display:none';
$ui_css_install = (isset($view_state['dup-package-dtl-install-panel']) && $view_state['dup-package-dtl-install-panel']) ? 'display:block' : 'display:none';
?>

<style>
	/*COMMON*/
	div.toggle-box {float:right; margin: 5px 5px 5px 0}
	div.dup-box {margin-top: 15px; font-size:14px; clear: both}
	table.dpro-dtl-data-tbl {width:100%}
	table.dpro-dtl-data-tbl tr {vertical-align: top}
	table.dpro-dtl-data-tbl tr:first-child td {margin:0; padding-top:0 !important;}
	table.dpro-dtl-data-tbl td {padding:0 6px 0 0; padding-top:15px !important;}
	table.dpro-dtl-data-tbl td:first-child {font-weight: bold; width:150px}
	table.dpro-sub-list td:first-child {white-space: nowrap; vertical-align: middle; width: 70px !important;}
	table.dpro-sub-list td {white-space: nowrap; vertical-align:top; padding:0 !important; font-size:12px}
	div.dup-box-panel-hdr {font-size:14px; display:block; border-bottom: 1px dotted #efefef; margin:5px 0 5px 0; font-weight: bold; padding: 0 0 5px 0}
	tr.sub-item td:first-child {padding:0 0 0 40px}
	tr.sub-item td {font-size: 12px}
	tr.sub-item-disabled td {color:gray}
	
	/*GENERAL*/
	div#dpro-name-info {display: none; font-size:11px; line-height:20px; margin:4px 0 0 0}
	div#dpro-downloads-area {padding: 5px 0 5px 0; }
	div#dpro-downloads-msg {margin-bottom:-5px; font-style: italic}
</style>

<?php if ($package_id == 0) :?>
	<div class="error below-h2"><p><?php DUP_PRO_U::_e("Invlaid Package ID request.  Please try again!"); ?></p></div>
<?php endif; ?>
	
<div class="toggle-box">
	<a href="javascript:void(0)" onclick="DupPro.Pack.OpenAll()">[open all]</a> &nbsp; 
	<a href="javascript:void(0)" onclick="DupPro.Pack.CloseAll()">[close all]</a>
</div>
	
<!-- ===============================
GENERAL -->
<div class="dup-box">
<div class="dup-box-title">
	<i class="fa fa-archive"></i> <?php DUP_PRO_U::_e('General') ?>
	<div class="dup-box-arrow"></div>
</div>			
<div class="dup-box-panel" id="dup-package-dtl-general-panel" style="<?php echo $ui_css_general ?>">
	<table class='dpro-dtl-data-tbl'>
		<tr>
			<td><?php DUP_PRO_U::_e("Name") ?>:</td>
			<td>
				<a href="javascript:void(0);" onclick="jQuery('#dpro-name-info').toggle()"><?php echo $package->Name ?></a> 
				<div id="dpro-name-info">
					<b><?php DUP_PRO_U::_e("ID") ?>:</b> <?php echo $package->ID ?><br/>
					<b><?php DUP_PRO_U::_e("Hash") ?>:</b> <?php echo $package->Hash ?><br/>
					<b><?php DUP_PRO_U::_e("Full Name") ?>:</b> <?php echo $package->NameHash ?><br/>
				</div>
			</td>
		</tr>
		<tr>
			<td><?php DUP_PRO_U::_e("Notes") ?>:</td>
			<td><?php echo strlen($package->Notes) ? $package->Notes : DUP_PRO_U::__("- no notes -") ?></td>
		</tr>
		<tr>
			<td><?php DUP_PRO_U::_e("Type") ?>:</td>
			<td><?php echo $package->get_type_string(); ?></td>
		</tr>			
		<tr>
			<td><?php DUP_PRO_U::_e("Version") ?>:</td>
			<td><?php echo $package->Version ?></td>
		</tr>
		<tr>
			<td><?php DUP_PRO_U::_e("Runtime") ?>:</td>
			<td><?php echo strlen($package->Runtime) ? $package->Runtime : DUP_PRO_U::__("error running"); ?></td>
		</tr>
		<tr>
			<td><?php DUP_PRO_U::_e("Files") ?>: </td>
			<td>
				<div id="dpro-downloads-area">
					<?php if ($error_display == 'none') :?>
						<?php if ($package->contains_storage_type(DUP_PRO_Storage_Types::Local)) :?>
							<button class="button" onclick="DupPro.Pack.DownloadPackageFile(0, <?php echo $package->ID ?>);return false;"><i class="fa fa-bolt"></i> Installer</button>						
							<button class="button" onclick="DupPro.Pack.DownloadPackageFile(1, <?php echo $package->ID ?>);return false;"><i class="fa fa-file-archive-o"></i> Archive - <?php echo $package->ZipSize ?></button>
							<button class="button" onclick="DupPro.Pack.DownloadPackageFile(2, <?php echo $package->ID ?>);return false;"><i class="fa fa-table"></i> &nbsp; SQL - <?php echo DUP_PRO_Util::ByteSize($package->Database->Size)  ?></button>
							<button class="button" onclick="DupPro.Pack.DownloadPackageFile(3, <?php echo $package->ID ?>);return false;"><i class="fa fa-list-alt"></i> &nbsp; Log </button>
						<?php else: ?>
							<!-- CLOUD ONLY FILES -->
							<div id="dpro-downloads-msg"><?php DUP_PRO_U::_e("These package files are in remote storage locations.  Please visit the storage provider to download.") ?></div> <br/>
							<button class="button" disabled="true"><i class="fa fa-exclamation-triangle"></i> Installer - <?php echo DUP_PRO_Util::ByteSize($package->Installer->Size) ?></button>						
							<button class="button" disabled="true"><i class="fa fa-exclamation-triangle"></i> Archive - <?php echo $package->ZipSize ?></button>
							<button class="button" disabled="true"><i class="fa fa-exclamation-triangle"></i> &nbsp; SQL - <?php echo DUP_PRO_Util::ByteSize($package->Database->Size)  ?></button>
							<button class="button" onclick="DupPro.Pack.DownloadPackageFile(3, <?php echo $package->ID ?>);return false;"><i class="fa fa-list-alt"></i> &nbsp; Log </button>
						<?php endif; ?>
					<?php else: ?>
							<button class="button" onclick="DupPro.Pack.DownloadPackageFile(3, <?php echo $package->ID ?>);return false;"><i class="fa fa-list-alt"></i> &nbsp; Log </button>
					<?php endif; ?>
				</div>		
				<?php if ($error_display == 'none') :?>
				<table class="dpro-sub-list">
					<tr>
						<td><?php DUP_PRO_U::_e("Archive") ?>: </td>
						<td><?php echo $package->Archive->File ?></td>
					</tr>
					<tr>
						<td><?php DUP_PRO_U::_e("Installer") ?>: </td>
						<td><?php echo $package->Installer->File ?></td>
					</tr>
					<tr>
						<td><?php DUP_PRO_U::_e("Database") ?>: </td>
						<td><?php echo $package->Database->File ?></td>
					</tr>
				</table>
				<?php endif; ?>
			</td>
		</tr>	
	</table>
</div>
</div>

<!-- ===============================
STORAGE -->
<?php 
	$css_file_filter_on = $package->Archive->FilterOn == 1  ? '' : 'sub-item-disabled';
	$css_db_filter_on   = $package->Database->FilterOn == 1 ? '' : 'sub-item-disabled';
?>
<div class="dup-box">
<div class="dup-box-title">
	<i class="fa fa-database"></i> <?php DUP_PRO_U::_e('Storage') ?>
	<div class="dup-box-arrow"></div>
</div>			
<div class="dup-box-panel" id="dup-package-dtl-storage-panel" style="<?php echo $ui_css_storage ?>">
	<table class="widefat package-tbl">
		<thead>
			<tr>
				<th style='width:150px'><?php DUP_PRO_U::_e('Name') ?></th>
				<th style='width:100px'><?php DUP_PRO_U::_e('Type') ?></th>
				<th style="white-space: nowrap"><?php DUP_PRO_U::_e('Location') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
				$i = 0;
				$latest_upload_infos = $package->get_latest_upload_infos();
				
				foreach ($latest_upload_infos as $upload_info) :
					$modifier_text = null;
					if($upload_info->has_completed(true) == false)
					{
						// For now not displaying any cancelled or failed storages
						continue;
					}

					$i++;
					$store = DUP_PRO_Storage_Entity::get_by_id($upload_info->storage_id);
					$store_type = $store->get_storage_type_string();
					$store_location = $store->get_storage_location_string();
					$row_style  = ($i % 2) ? 'alternate' : '';
					?>
					<tr class="package-row <?php echo $row_style ?>">
						<td>
							<a href="?page=duplicator-pro-storage&tab=storage&inner_page=edit&storage_id=<?php echo $store->id ?>" target="_blank">
								<?php 
									switch ($store->storage_type) {
										case DUP_PRO_Storage_Types::FTP :
										case DUP_PRO_Storage_Types::GDrive :
										case DUP_PRO_Storage_Types::Dropbox : echo '<i class="fa fa-cloud"></i>'; break;
										case DUP_PRO_Storage_Types::Local : echo '<i class="fa fa-server"></i>'; break;
									}
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
				<?php if ($i == 0) : ?>
					<tr>
						<td colspan="3" style="text-align: center">
							<?php DUP_PRO_U::_e('- No storage locations associated with this package -'); ?>
						</td>
					</tr>
				<?php endif; ?>
		</tbody>
	</table>
</div>
</div>


<!-- ===============================
ARCHIVE -->
<?php 
	$css_file_filter_on = $package->Archive->FilterOn == 1  ? '' : 'sub-item-disabled';
	$css_db_filter_on   = $package->Database->FilterOn == 1 ? '' : 'sub-item-disabled';
?>
<div class="dup-box">
<div class="dup-box-title">
	<i class="fa fa-file-archive-o"></i> <?php DUP_PRO_U::_e('Archive') ?>
	<div class="dup-box-arrow"></div>
</div>			
<div class="dup-box-panel" id="dup-package-dtl-archive-panel" style="<?php echo $ui_css_archive ?>">

	<!-- FILES -->
	<div class="dup-box-panel-hdr"><i class="fa fa-files-o"></i> <?php DUP_PRO_U::_e('FILES'); ?></div>
	<table class='dpro-dtl-data-tbl'>
		<tr>
			<td><?php DUP_PRO_U::_e("Build Mode") ?>: </td>
			<td>
				<?php 
					if (isset($package->ZipMode)) {
						echo ($package->ZipMode == DUP_PRO_Archive_Build_Mode::ZipArchive) ?  "ZipArchive (slow)" 	: "Shell Exec (fast)";
					} else {
						echo 'unknown';
					}
				?>
			</td>
		</tr>			
		<tr>
			<td><?php DUP_PRO_U::_e("Filters") ?>: </td>
			<td><?php echo $package->Archive->FilterOn == 1 ? 'On' : 'Off'; ?></td>
		</tr>
		<tr class="sub-item <?php echo $css_file_filter_on ?>">
			<td><?php DUP_PRO_U::_e("Directories") ?>: </td>
			<td>
				<?php 
					echo strlen($package->Archive->FilterDirs) 
						? str_replace(';', '<br/>', $package->Archive->FilterDirs)
						: DUP_PRO_U::__('- no filters -');	
				?>
			</td>
		</tr>
		<tr class="sub-item <?php echo $css_file_filter_on ?>">
			<td><?php DUP_PRO_U::_e("Extensions") ?>: </td>
			<td>
				<?php
					echo isset($package->Archive->Extensions) && strlen($package->Archive->Extensions) 
						? $package->Archive->Extensions
						: DUP_PRO_U::__('- no filters -');
				?>
			</td>
		</tr>
		<tr class="sub-item <?php echo $css_file_filter_on ?>">
			<td><?php DUP_PRO_U::_e("Files") ?>: </td>
			<td>
				<?php 
					echo isset($package->Archive->FilterFiles) && strlen($package->Archive->FilterFiles) 
						? str_replace(';', '<br/>', $package->Archive->FilterFiles)
						: DUP_PRO_U::__('- no filters -');	
				?>					
			</td>
		</tr>			
	</table><br/>

	<!-- DATABASE -->
	<div class="dup-box-panel-hdr"><i class="fa fa-table"></i> <?php DUP_PRO_U::_e('DATABASE'); ?></div>
	<table class='dpro-dtl-data-tbl'>
		<tr>
			<td><?php DUP_PRO_U::_e("Type") ?>: </td>
			<td><?php echo $package->Database->Type ?></td>
		</tr>
		<tr>
			<td><?php DUP_PRO_U::_e("Build Mode") ?>: </td>
			<td><?php echo $package->Database->DBMode ?></td>
		</tr>			
		<tr>
			<td><?php DUP_PRO_U::_e("Filters") ?>: </td>
			<td><?php echo $package->Database->FilterOn == 1 ? 'On' : 'Off'; ?></td>
		</tr>
		<tr class="sub-item <?php echo $css_db_filter_on ?>">
			<td><?php DUP_PRO_U::_e("Tables") ?>: </td>
			<td>
				<?php 
					echo isset($package->Archive->FilterTables) && strlen($package->Archive->FilterTables) 
						? str_replace(';', '<br/>', $package->Database->FilterTables)
						: DUP_PRO_U::__('- no filters -');	
				?>
			</td>
		</tr>			
	</table>		
</div>
</div>


<!-- ===============================
INSTALLER -->
<div class="dup-box" style="margin-bottom: 50px">
<div class="dup-box-title">
	<i class="fa fa-bolt"></i> <?php DUP_PRO_U::_e('Installer') ?>
	<div class="dup-box-arrow"></div>
</div>			
<div class="dup-box-panel" id="dup-package-dtl-install-panel" style="<?php echo $ui_css_install ?>">
	<table class='dpro-dtl-data-tbl'>
		<tr>
			<td><?php DUP_PRO_U::_e("Host") ?>:</td>
			<td><?php echo strlen($package->Installer->OptsDBHost) ? $package->Installer->OptsDBHost : DUP_PRO_U::__("- not set -") ?></td>
		</tr>
		<tr>
			<td><?php DUP_PRO_U::_e("Database") ?>:</td>
			<td><?php echo strlen($package->Installer->OptsDBName) ? $package->Installer->OptsDBName : DUP_PRO_U::__("- not set -") ?></td>
		</tr>
		<tr>
			<td><?php DUP_PRO_U::_e("User") ?>:</td>
			<td><?php echo strlen($package->Installer->OptsDBUser) ? $package->Installer->OptsDBUser : DUP_PRO_U::__("- not set -") ?></td>
		</tr>	
		<tr>
			<td><?php DUP_PRO_U::_e("New URL") ?>:</td>
			<td><?php echo strlen($package->Installer->OptsURLNew) ? $package->Installer->OptsURLNew : DUP_PRO_U::__("- not set -") ?></td>
		</tr>
	</table>
</div>
</div>

<?php if ($global->package_debug) : ?>
	<div style="margin:0">
		<a href="javascript:void(0)" onclick="jQuery(this).parent().find('.dup-pack-debug').toggle()">[<?php DUP_PRO_U::_e("View Package Object") ?>]</a><br/>
		<pre class="dup-pack-debug" style="display:none"><?php @print_r($package); ?> </pre>
	</div>
<?php endif; ?>	


<script type="text/javascript">
jQuery(document).ready(function ($) {

	/*	METHOD:  */
	DupPro.Pack.OpenAll = function () {
		$("div.dup-box").each(function() {
			var panel_open = $(this).find('div.dup-box-panel').is(':visible');
			if (! panel_open)
				$( this ).find('div.dup-box-title').trigger("click");
		 });
	};

	/*	METHOD: */
	DupPro.Pack.CloseAll = function () {
			$("div.dup-box").each(function() {
			var panel_open = $(this).find('div.dup-box-panel').is(':visible');
			if (panel_open)
				$( this ).find('div.dup-box-title').trigger("click");
		 });
	};
});
</script>