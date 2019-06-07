<?php
/**
 * External Module: DRW Tweaks
 * @author Luke Stevens, Murdoch Children's Research Institute
 */
namespace MCRI\DRWTweaks;

use ExternalModules\AbstractExternalModule;

class DRWTweaks extends AbstractExternalModule
{
        public function redcap_data_entry_form_top($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance) {
                global $data_resolution_enabled, $user_rights, $lang;

                if ($data_resolution_enabled=='2' && $user_rights['data_quality_resolution']>2) {
                        $this->initializeJavascriptModuleObject();
                        ?>
<style type="text/css">
    #__SUBMITBUTTONS__-tr { vertical-align: top; }
    #MCRI_DRWTweaks_Div { display:none;text-align:center; }
</style>
<div id="MCRI_DRWTweaks_Div">
    <div style="margin-bottom:5px;">
        <img style="vertical-align:middle;" src="<?php echo APP_PATH_IMAGES.'balloons.png';?>">
        <span style="vertical-align:middle;"><?php echo $lang['dataqueries_137'];?></span>
        <img style="vertical-align:middle;" src="<?php echo APP_PATH_IMAGES.'puzzle_small.png';?>">
    </div>
    <div>
        <button class="btn btn-sm btn-defaultrc" id="MCRI_DRWTweaks_Button" onclick="ExternalModules.MCRI.DRWTweaks.verify();return false;"><span style="color:green;"><i class="fas fa-check-circle"></i>&nbsp;Verify all fields</span></button>
    </div>
</div>
<script type="text/javascript">
    (function(){
        ExternalModules.MCRI.DRWTweaks.verify = function() {
            var urlParams = new URLSearchParams(window.location.search);
            var event_id = urlParams.get('event_id');
            var record = urlParams.get('id');
            var instance = urlParams.get('instance');
            if (window.confirm("All visible fields that do not have a query will be marked \"Verified\". OK?")) { 
                $('img[src$="balloon_left_bw2.gif"]').filter(":visible").each(function() {
                    var field = $(this).attr('id').substring(8); // trim off dc-icon-
                    console.log(field);
                    
                    var postdata = { 
                        action: 'save', 
                        field_name: field, 
                        event_id: event_id, 
                        record: record,
                        comment: '',
                        response_requested: 0,
                        upload_doc_id: '', 
                        delete_doc_id: '',
                        assigned_user_id: '',
                        status: 'VERIFIED', 
                        send_back: 0,
                        response: '', 
                        reopen_query: 0,
                        rule_id: '',
                        redcap_csrf_token: redcap_csrf_token
                    };

                    $.ajaxQueue(record, {
                        url: app_path_webroot+"DataQuality/data_resolution_popup.php?pid="+pid+'&instance='+instance,
                        method: "POST",
                        data: postdata, 
                        success: function (data) {
                            if (data=='0') {
                                alert(woops);
                            } else {
                                // Parse JSON
                                var json_data = jQuery.parseJSON(data);
                                // Data Entry page: Change ballon icon for field
                                $('#dc-icon-'+field).attr('src', json_data.icon).attr('onmouseover', '').attr('onmouseout', '');
                            }
                        }
                    });
                });
            }
        };
        $(document).ready(function(){
            var destination = $('#__SUBMITBUTTONS__-tr > td:eq(0)');
            if (destination.length===0) { // if form access is read-only there is no #__SUBMITBUTTONS__-tr
                destination = $('#questiontable').find('tr:last').find('td:first');
            }
            $('#MCRI_DRWTweaks_Div').prependTo(destination).css('display', 'inline-block');
            if ($('img[src$="balloon_left_bw2.gif"]').filter(":visible").length === 0) {
                $('#MCRI_DRWTweaks_Button').prop('disabled', true);
            }
        });
        
        var ajaxQueue = $({});
        $.ajaxQueue = function(rec, ajaxOpts) {
            // hold the original complete function
            var oldComplete = ajaxOpts.complete;

            // queue our ajax request
            ajaxQueue.queue(function(next) {

                // create a complete callback to fire the next event in the queue
                ajaxOpts.complete = function() {
                    // fire the original complete if it was there
                    if (oldComplete) oldComplete.apply(this, arguments);
                    next(); // run the next query in the queue
                };

                // run the query
                $.ajax(ajaxOpts);
            });
        };

    })();
</script>    
                        <?php
                }
        }
}