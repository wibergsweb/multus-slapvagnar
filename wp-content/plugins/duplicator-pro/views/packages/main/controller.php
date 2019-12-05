<?php
$packages_url = DUP_PRO_U::menu_page_url(DUP_PRO_Constants::$PACKAGES_SUBMENU_SLUG, false);
$packages_tab_url = DUP_PRO_U::append_query_value($packages_url, 'tab', 'packages');
$edit_package_url = DUP_PRO_U::append_query_value($packages_tab_url, 'inner_page', 'new1');
$inner_page = isset($_REQUEST['inner_page']) ? esc_html($_REQUEST['inner_page']) : 'list';
?>

<style>
    /*WIZARD TABS */
    div#dup-wiz {padding:0px; margin:0;  }
    div#dup-wiz-steps {margin:10px 0px 0px 10px; padding:0px;  clear:both; font-size:12px; min-width:350px;}
    div#dup-wiz-title {padding:2px 0px 0px 0px; font-size:18px;}
	div#dup-wiz-steps a span {font-size:10px !important}
    /* wiz-steps numbers */
    #dup-wiz span {display:block;float:left; text-align:center; width:14px; margin:4px 5px 0px 0px; line-height:13px; color:#ccc; border:1px solid #CCCCCC; border-radius:5px; }
    /* wiz-steps default*/
    #dup-wiz a { position:relative; display:block; width:auto; min-width:55px; height:25px; margin-right:8px; padding:0px 10px 0px 10px; float:left; line-height:24px; color:#000; background:#E4E4E4; border-radius:5px }
	/* wiz-steps active*/
    #dup-wiz .active-step a {color:#fff; background:#BBBBBB;}
    #dup-wiz .active-step span {color:#fff; border:1px solid #fff;}
	/* wiz-steps completed */
    #dup-wiz .completed-step a {color:#E1E1E1; background:#BBBBBB; }
    #dup-wiz .completed-step span {color:#E1E1E1;}
    /*Footer */
    div.dup-button-footer input {min-width: 105px}
    div.dup-button-footer {padding: 1px 10px 0px 0px; text-align: right}
</style>

<?php

switch ($inner_page)
{
    case 'list': 
		duplicator_pro_header(DUP_PRO_U::__("Packages &raquo; All"));
		include('main.php');
        break;
    case 'new1': 
		duplicator_pro_header(DUP_PRO_U::__("Packages &raquo; New"));
		include('new1.base.php');
        break;
    case 'new2': 
		duplicator_pro_header(DUP_PRO_U::__("Packages &raquo; New"));
		include('new2.base.php');
        break;
}
?>

