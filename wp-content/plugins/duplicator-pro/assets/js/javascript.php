<script type="text/javascript">
    /* DESCRIPTION: Methods and Objects in this file are global and common in 
     * nature use this file to place all shared methods and varibles */

//UNIQUE NAMESPACE
    DupPro = new Object();
    DupPro.UI = new Object();
    DupPro.Pack = new Object();
    DupPro.Tools = new Object();
    DupPro.Settings = new Object();

    DupPro.Storage = new Object();
    DupPro.Storage.Dropbox = new Object();
    DupPro.Storage.FTP = new Object();
    DupPro.Storage.GDrive = new Object();
	DupPro.Storage.S3 = new Object();

    DupPro.Schedule = new Object();
    DupPro.Template = new Object();
    
    DupPro.Support = new Object();

//GLOBAL CONSTANTS
    DupPro.DEBUG_AJAX_RESPONSE = false;
    DupPro.AJAX_TIMER = null;


    /* ============================================================================
     *  BASE NAMESPACE: All methods at the top of the Duplicator Namespace  
     *  ============================================================================	*/

    /*	----------------------------------------
     *	METHOD: Starts a timer for Ajax calls */
    DupPro.StartAjaxTimer = function () {
        DupPro.AJAX_TIMER = new Date();
    };

    /*	----------------------------------------
     *	METHOD: Ends a timer for Ajax calls */
    DupPro.EndAjaxTimer = function () {
        var endTime = new Date();
        DupPro.AJAX_TIMER = (endTime.getTime() - DupPro.AJAX_TIMER) / 1000;
    };

    /*	----------------------------------------
     *	METHOD: Reloads the current window
     *	@param data		An xhr object  */
    DupPro.ReloadWindow = function (data) {
        if (DupPro.DEBUG_AJAX_RESPONSE) {
            DupPro.Pack.ShowError('debug on', data);
        } else {
            //window.location.reload(true);
            window.location = window.location.href;
        }
    };

//Basic Util Methods here:
    DupPro.OpenLogWindow = function (log) {
        var logFile = log || null;
        if (logFile == null) {
            window.open('?page=duplicator-pro-tools', 'Log Window');
        } else {
            window.open('<?php echo DUPLICATOR_PRO_SSDIR_URL; ?>' + '/' + log)
        }
    };


    /* ============================================================================
     *  UI NAMESPACE: All methods at the top of the Duplicator Namespace  
     *  ============================================================================	*/

    /*  ----------------------------------------
     *  METHOD:   */
    DupPro.UI.SaveViewStateByPost = function (key, value) {
        if (key != undefined && value != undefined) {
            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                dataType: "json",
                data: {action: 'DUP_PRO_UI_SaveViewStateByPost', key: key, value: value},
                success: function (data) {
                },
                error: function (data) {
                }
            });
        }
    }

    /*  ----------------------------------------
     *  METHOD:   */
    DupPro.UI.AnimateProgressBar = function (id) {
        //Create Progress Bar
        var $mainbar = jQuery("#" + id);
        $mainbar.progressbar({value: 100});
        $mainbar.height(25);
		$mainbar.width(20);
        runAnimation($mainbar);

        function runAnimation($pb) {
            $pb.css({"padding-left": "0%", "padding-right": "90%"});
            $pb.progressbar("option", "value", 100);
			$pb.animate({paddingLeft: "90%", paddingRight: "0%"}, 2500, "linear", function () {
							       runAnimation($pb);
            });
        }
    }


    /*	----------------------------------------
     * METHOD: Toggle MetaBoxes */
    DupPro.UI.ToggleMetaBox = function () {
        var $title = jQuery(this);
        var $panel = $title.parent().find('.dup-box-panel');
        var $arrow = $title.parent().find('.dup-box-arrow i');
        var key = $panel.attr('id');
        var value = $panel.is(":visible") ? 0 : 1;
        $panel.toggle();
        DupPro.UI.SaveViewStateByPost(key, value);
        (value)
                ? $arrow.removeClass().addClass('fa fa-caret-up')
                : $arrow.removeClass().addClass('fa fa-caret-down');

    }

    DupPro.UI.TogglePasswordDisplay = function (display, inputID) {

        if (display) {
            document.getElementById(inputID).type = "text";
        } else {
            document.getElementById(inputID).type = "password";
        }
    }


    jQuery(document).ready(function ($) {
        //Init: Toggle MetaBoxes
        $('div.dup-box div.dup-box-title').each(function () {
            var $title = $(this);
            var $panel = $title.parent().find('.dup-box-panel');
            var $arrow = $title.find('.dup-box-arrow');
            $title.click(DupPro.UI.ToggleMetaBox);
            ($panel.is(":visible"))
                    ? $arrow.html('<i class="fa fa-caret-up"></i>')
                    : $arrow.html('<i class="fa fa-caret-down"></i>');
        });
		
		//Look for tooltip data
		$('i[data-tooltip!=""]').qtip({ 
			content: {
				attr: 'data-tooltip',
				title: {
					text: function() { return  $(this).attr('data-tooltip-title'); }
				}
			},
			style: {
				classes: 'qtip-light qtip-rounded qtip-shadow',
				width: 500
			},
			 position: {
				my: 'top left', 
				at: 'bottom center'
			}
		});
    });

</script>