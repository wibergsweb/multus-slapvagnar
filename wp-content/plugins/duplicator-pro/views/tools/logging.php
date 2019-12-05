<?php
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/assets/js/javascript.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/views/inc.header.php');

$trace_log_filepath = DUP_PRO_U::get_log_filepath();
$trace_filename = basename($trace_log_filepath);

$logs = glob(DUPLICATOR_PRO_SSDIR_PATH . '/*.log');
if ($logs != false && count($logs))
{
    DUP_PRO_U::log_object('logs', $logs);
    // Remove the tracelog from the main list
//    if(($key = array_search($trace_log_filepath, $logs)) !== false) {
//        unset($logs[$key]);
//    }
    
    usort($logs, create_function('$a,$b', 'return filemtime($b) - filemtime($a);'));
    @chmod(DUP_PRO_Util::SafePath($logs[0]), 0644);
}

$logname = (isset($_GET['logname'])) ? trim($_GET['logname']) : "";
$refresh = (isset($_POST['refresh']) && $_POST['refresh'] == 1) ? 1 : 0;
$auto = (isset($_POST['auto']) && $_POST['auto'] == 1) ? 1 : 0;

//Check for invalid file
if (isset($_GET['logname']))
{
    $validFiles = array_map('basename', $logs);
    if (validate_file($logname, $validFiles) > 0)
    {
        unset($logname);
    }
    unset($validFiles);
}

if (!isset($logname) || !$logname)
{
    $logname = (count($logs) > 0) ? basename($logs[0]) : "";
}

//$logurl	 = get_site_url(null, '', is_ssl() ? 'https' : 'http') . '/' . DUPLICATOR_PRO_SSDIR_NAME . '/' . $logname;
$logurl = DUPLICATOR_PRO_SSDIR_URL . '/' . $logname;
$logfound = (strlen($logname) > 0) ? true : false;
?>

<style>
    span#dup-refresh-count {display:inline;}
    table#dup-log-panels {width:100%; }
    td#dup-log-panel-left {width:75%;}
    td#dup-log-panel-left div.name {float:left; margin: 0px 0px 5px 5px; font-weight: bold}
    td#dup-log-panel-left div.opts {float:right;}
    td#dup-log-panel-right {vertical-align: top; padding-left:15px; max-width: 375px}
    div.dup-log-file-list a, span{display: inline-block; white-space: nowrap; text-overflow: ellipsis; max-width: 375px; line-height:20px; overflow:hidden}
    div.dup-log-file-list span {font-weight: bold}
    div.dup-opts-items {border:1px solid silver; background: #efefef; padding: 5px; border-radius: 4px; margin:2px 0px 10px -2px;}
    label#dup-auto-refresh-lbl {display: inline-block;}
    iframe#dup-log-content {padding:5px; background: #fff; min-height:500px; width:99%; border:1px solid silver}
</style>

<script type="text/javascript">
    jQuery(document).ready(function ($) {

        DupPro.Tools.FullLog = function () {
            var $panelL = $('#dup-log-panel-left');
            var $panelR = $('#dup-log-panel-right');

            if ($panelR.is(":visible")) {
                $panelR.hide(400);
                $panelL.css({width: '100%'});
            } else {
                $panelR.show(200);
                $panelL.css({width: '75%'});
            }
        }

        DupPro.Tools.Refresh = function () {
            $('#refresh').val(1);
            $('#dup-form-logs').submit();
        }

        DupPro.Tools.RefreshAuto = function () {
            if ($("#dup-auto-refresh").is(":checked")) {
                $('#auto').val(1);
                startTimer();
            } else {
                $('#auto').val(0);
            }
        }

        DupPro.Tools.GetLog = function (log) {
            window.location = log;
        }

        DupPro.Tools.WinResize = function () {
            var height = $(window).height() - 170;
            $("#dup-log-content").css({height: height + 'px'});
        }


        var duration = 9;
        var count = duration;
        var timerInterval;
        function timer() {
            count = count - 1;
            $("#dup-refresh-count").html(count.toString());
            if (!$("#dup-auto-refresh").is(":checked")) {
                clearInterval(timerInterval);
                $("#dup-refresh-count").text(count.toString().trim());
                return;
            }

            if (count <= 0) {
                count = duration + 1;
                DupPro.Tools.Refresh();
            }
        }

        function startTimer() {
            timerInterval = setInterval(timer, 1000);
        }

        //INIT Events
        $(window).resize(DupPro.Tools.WinResize);
        $('#dup-options').click(DupPro.Tools.FullLog);
        $("#dup-refresh").click(DupPro.Tools.Refresh);
        $("#dup-auto-refresh").click(DupPro.Tools.RefreshAuto);
        $("#dup-refresh-count").html(duration.toString());

        //INIT
        DupPro.Tools.WinResize();
<?php if ($refresh) : ?>
            //Scroll to Bottom
            $("#dup-log-content").load(function () {
                var $contents = $('#dup-log-content').contents();
                $contents.scrollTop($contents.height());
            });
    <?php if ($auto) : ?>
                $("#dup-auto-refresh").prop('checked', true);
                DupPro.Tools.RefreshAuto();
    <?php endif; ?>
<?php endif; ?>

    });

</script>

<form id="dup-form-logs" method="post" action="">
    <input type="hidden" id="refresh" name="refresh" value="<?php echo ($refresh) ? 1 : 0 ?>" />
    <input type="hidden" id="auto" name="auto" value="<?php echo ($auto) ? 1 : 0 ?>" />
<?php if (!$logfound) : ?>
        <div style="padding:20px">
            <h2><?php DUP_PRO_U::_e("Log file not found or unreadable") ?>.</h2>

              <?php DUP_PRO_U::_e("Try to create a package, since no log files were found in the snapshots directory with the extension *.log") ?>.<br/><br/>

			  <?php DUP_PRO_U::_e("Reasons for log file not showing") ?>: <br/>
            - <?php DUP_PRO_U::_e("The web server does not support returning .log file extensions") ?>. <br/>
            - <?php DUP_PRO_U::_e("The snapshots directory does not have the correct permissions to write files.  Try setting the permissions to 755") ?>. <br/>
            - <?php DUP_PRO_U::_e("The process that PHP runs under does not have enough permissions to create files.  Please contact your hosting provider for more details") ?>. <br/>
        </div>

<?php else: ?>	

        <table id="dup-log-panels">
            <tr>
                <td id="dup-log-panel-left">
                    <div class="name"><i class='fa fa-list-alt'></i> <?php echo basename($logurl); ?></div>
                    <div class="opts"><a href="javascript:void(0)" id="dup-options"><?php DUP_PRO_U::_e("Options") ?> <i class="fa fa-angle-double-right"></i></a> &nbsp;</div>
                    <br style="clear:both" />
                    <iframe id="dup-log-content" src="<?php echo $logurl ?>" ></iframe>							
                </td>
                <td id="dup-log-panel-right">


                    <input type="button" class="button" id="dup-refresh" value="<?php DUP_PRO_U::_e("Refresh Now") ?>" /> &nbsp; 
                    <div style="display:inline-block;margin-top:1px;">
                        <input type='checkbox' id="dup-auto-refresh" style="margin-top:3px" /> 
                        <label id="dup-auto-refresh-lbl" for="dup-auto-refresh">
						<?php DUP_PRO_U::_e("Auto Refresh") ?>	[<span id="dup-refresh-count"></span>]
                        </label> 
                    </div>
                    <hr />

                    <b><?php DUP_PRO_U::_e('Trace Log') ?> </b><br/>

                    <div class="dup-log-file-list">
                        <?php
                        $trace_log_filepath = DUP_PRO_U::get_log_filepath();

                        $time = date('h:i:s m/d/y', filemtime($trace_log_filepath));
                        //$name = DUP_PRO_U::__('View'); //esc_html(basename($trace_log_filepath));
                        //$trace_log_url = DUP_PRO_U::get_log_url();

                        $active_filename = basename($logurl);                        

                        $trace_log_url = '?page=duplicator-pro-tools&logname=' . $trace_filename;

                        $is_trace_active = ($active_filename == $trace_filename);

                        DUP_PRO_U::log("trace filename $trace_filename active filename $active_filename $is_trace_active");
                        if ($is_trace_active)
                        {
                            ?>

                            <span><?php echo $time ?></span>
                            <?php
                        }
                        else
                        {
                            ?>
                            <a href='javascript:void(0)'  onclick='DupPro.Tools.GetLog("<?php echo $trace_log_url ?>")'><?php echo $time ?></a>
                            <?php
                        }
                        ?>     
                    </div>

                    <br/>
                    <b><?php DUP_PRO_U::_e('Last 20 Logs') ?> </b><br/>

                    <div class="dup-log-file-list">
                        <?php
                        $count = 0;
                        $active = basename($logurl);
                        foreach ($logs as $log)
                        {
                            $time = date('h:i:s m/d/y', filemtime($log));
                            $name = esc_html(basename($log));
                            $url = '?page=duplicator-pro-tools&logname=' . $name;
                            
                            DUP_PRO_U::log("name $name trace filename $trace_filename");
                            $should_display = ($name !== $trace_filename);
                            if($should_display)
                            {
                                echo ($active == $name) ? "<span title='{$name}'>{$time} - {$name}</span><br/>" : "<a href='javascript:void(0)'  title='{$name}' onclick='DupPro.Tools.GetLog(\"{$url}\")'>{$time} - {$name}</a>";                            
                                if ($count > 20)
                                break;
                            }
                            
                        }
                        ?>
                    </div>
                </td>
            </tr>
        </table> 

    <?php endif; ?>	
</form>
