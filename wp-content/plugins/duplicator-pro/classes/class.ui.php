<?php
if ( ! defined('DUPLICATOR_PRO_VERSION') ) exit; // Exit if accessed directly

/**
 * Helper Class for UI internactions
 * @package Dupicator\classes
 */
class DUP_PRO_UI {
	
	/**
	 * The key used in the wp_options table
	 * @var string 
	 */
	private static $OptionsTableKey = 'duplicator_pro_ui_view_state';
	
	/** 
     * Save the view state of UI elements
	 * @param string $key A unique key to define the ui element
	 * @param string $value A generic value to use for the view state
     */
	static public function SaveViewState($key, $value) {
	   
		$view_state = array();
		$view_state = get_option(self::$OptionsTableKey);
		$view_state[$key] =  $value;
		$success = update_option(self::$OptionsTableKey, $view_state);
		
		return $success;
    }
	
	
    /** 
     * Saves the state of a UI element via post params
	 * @return json result string
	 * <code>
	 * //JavaScript Ajax Request
	 * DupPro.UI.SaveViewStateByPost('dup-pack-archive-panel', 1);
	 * 
	 * //Call PHP Code
	 * $view_state       = DUP_PRO_UI::GetViewStateValue('dup-pack-archive-panel');
	 * $ui_css_archive   = ($view_state == 1)   ? 'display:block' : 'display:none';
	 * </code>
     */
    static public function SaveViewStateByPost() {
		
		DUP_PRO_Util::CheckPermissions('read');
		
		$post  = stripslashes_deep($_POST);
		$key   = esc_html($post['key']);
		$value = esc_html($post['value']);
		$success = self::SaveViewState($key, $value);
		
		//Show Results as JSON
		$json = array();
		$json['key']    = $key;
		$json['value']  = $value;
		$json['update-success'] = $success;
		die(json_encode($json));
    }
	
	
	/** 
     *	Gets all the values from the settings array
	 *  @return array Returns and array of all the values stored in the settings array
     */
    static public function GetViewStateArray() {
		return get_option(self::$OptionsTableKey);
	}
	
	 /** 
	  * Return the value of the of view state item
	  * @param type $searchKey The key to search on
	  * @return string Returns the value of the key searched or null if key is not found
	  */
    static public function GetViewStateValue($searchKey) {
		$view_state = get_option(self::$OptionsTableKey);
		if (is_array($view_state)) {
			foreach ($view_state as $key => $value) {
				if ($key == $searchKey) {
					return $value;	
				}
			}
		} 
		return null;
	}
	
	/**
	 * Shows a display message in the wp-admin if any researved files are found
	 * @return type void
	 */
	static public function ShowReservedFilesNotice() 
	{

		$dpro_active = is_plugin_active('duplicator-pro/duplicator-pro.php');
		$dup_perm    = current_user_can( 'manage_options' );
		if (! $dpro_active || ! $dup_perm) 
			return;
		
		//Hide free error message if Pro is active
		if (is_plugin_active('duplicator/duplicator.php')) {
			echo "<style>div#dup-global-error-reserved-files {display:none}</style>";
		}

		$screen = get_current_screen();
		if (! isset($screen))
			return;
		
		//Hide on save permalinks to prevent user distraction
		if ($screen->id == 'options-permalink')
			return;
		
		if (DUP_PRO_Server::install_files_found()) 
		{
			$txt_messgate = DUP_PRO_U::__('Reserved Duplicator Pro install files have been detected in the root directory.  Please delete these reserved files to avoid security issues. <br/>'
					. 'Go to: Tools > Diagnostics > Stored Data > and click the "Delete Reserved Files" button');
			$on_active_tab =  isset($_GET['tab']) && $_GET['tab'] == 'diagnostics' ? true : false;
			echo '<div class="error" id="dpro-global-error-reserved-files"><p>';
			if ($screen->id == 'duplicator-pro_page_duplicator-pro-tools' && $on_active_tab) 
			{
				echo $txt_messgate;
			}
			else 
			{
				$duplicator_pro_nonce = wp_create_nonce('duplicator_pro_cleanup_page');
				echo $txt_messgate;
				$diagnostics_url = self_admin_url('admin.php?page=duplicator-pro-tools&tab=diagnostics&_wpnonce=' . $duplicator_pro_nonce);
				
				@printf("<br/><a href='$diagnostics_url'>%s</a>", DUP_PRO_U::__('Take me there now!'));
			}			
			echo "</p></div>";
		}
	}
	
}
?>