<?php
$job = new job();
$api = new api();
$data = new data();
$data->displayFilterMessage();
$export = new export();

$hasMET = $_SESSION['et']['job']['MET'];
$hasSL = $_SESSION['et']['job']['SL'];
$hasET = $_SESSION['et']['job']['ET'];
$hasEF = false;

if ($_POST['images']) {
    foreach ($_POST['images'] as $p) {
        $imageIDList .= "$p,";
    }
    $job->imageIDList = substr($imageIDList, 0, -1);
    $export->imageIDList = $job->imageIDList;
    $images = $job->getImageArrayByIDs();
}
else {
    $images = $job->getJobImageArray(true, false);
    $hasEF = $job->getEFImages();
    $collapsed = $job->getJobCollapsedImages();
}

if ($_REQUEST['export']=="exportET") {
    $export->exportDataToExcel();
    exit;
}
if ($_REQUEST['export']=="exportMET") {
    $export->exportMETDataToExcel();
    exit;
}
if ($_REQUEST['export']=="exportIndividualData") {
    $export->exportIndividualDataToExcel();
    exit;
}

echo "<div class=\"iconHeading\">
        <span id=\"collapseLink\"><span class=\"ui-icon  ui-icon-circle-plus\"></span> Collapse Slides</span>&nbsp;&nbsp;
        <span id=\"exportLinkET\" style=\"display:none;\">
            <a href=\"?job={$_REQUEST['job']}&module=analysis&export=exportET\">
            <img src=\"css/xls.png\" style=\"vertical-align:top;\" /> Export ET Tables</a>
        </span>
        <span id=\"exportLinkMET\" style=\"display:none;\">
            <a href=\"?job={$_REQUEST['job']}&module=analysis&export=exportMET\">
            <img src=\"css/xls.png\" style=\"vertical-align:top;\" /> Export MET/EF Tables</a>
        </span>
        <span id=\"exportLinkIndividualData\" style=\"display:none;\">
            <a href=\"?job={$_REQUEST['job']}&module=analysis&export=exportIndividualData\">
            <img src=\"css/xls.png\" style=\"vertical-align:top;\" /> Export Individual Data Tables</a>
        </span>
    </div>
        <p style=\"font-size:12px;\"><strong>Display data as: </strong> 
            <input type=\"radio\" name=\"displayDataRadio\" value=\"data-percent\" checked=\"checked\" /> Percentages&nbsp;&nbsp;
            <input type=\"radio\" name=\"displayDataRadio\" value=\"data-numeric\" /> Counts&nbsp;&nbsp;
        </p>";

echo "<div id=\"groupTabs\">
            <ul style=\"font-size:10px;\">";

    $data->shelves = $job->getJobShelves(true);

    if (count($data->shelves)>1) {
        echo "<li><a href=\"#summary\">Shelf Summary</a></li>";
    }
    if ($images) {
        foreach ($images as $imageID=>$imageName) {
            echo "<li id=\"$imageID\"><a href=\"#image$imageID\">$imageName</a></li>";
        }
        reset($images);
    }
    else {
        echo "Error: no images found. (Note that non-aggregated images do not show up here)";
        echo"<script>document.getElementById('exportLinkIndividualData').style.display = 'none';</script>";
        exit;
    }
    if ($collapsed) {
        foreach ($collapsed as $collapseGroup=>$collapseImages) {
            echo "<li id=\"collapse$collapseGroup\"><a href=\"#collapsegroupcollapse$collapseGroup\">Collapsed (G$collapseGroup): $collapseImages</a></li>";
        }
        reset($collapsed);
    }
    
    echo "</ul>";
    
    
    if (count($data->shelves)>1) {
        echo "<div id=\"summary\">";   
            echo $data->getSummaryTableHeader();
            echo $data->getSummaryTableRows();
            echo $data->getSummaryTableFooter();
        echo "</div>";
    }
    
    foreach ($images as $imageID=>$imageName) {
        echo "<div id=\"image$imageID\" class=\"tabPage\">";
        echo "<p>&nbsp;</p>";
        echo "<div class=\"loadingDiv\" style=\"text-align:center;\">
            <img src=\"css/spinner.gif\" /><br/><br />Please wait as the fixation data for this image is processed. <br /><br />
            This may take up to 30 seconds depending on the number of respondents and the amount of elements.</div>";
        echo "<p>&nbsp;</p>";
        echo "<p>&nbsp;</p>";
        echo "</div>";
    }
    
    if ($collapsed) {
        foreach ($collapsed as $collapseGroup=>$collapseImages) {
            echo "<div id=\"collapsegroupcollapse$collapseGroup\">";
            echo "<p>&nbsp;</p>";
            echo "<div class=\"loadingDiv\" style=\"text-align:center;\">
                <img src=\"css/spinner.gif\" /><br/><br />Please wait as the fixation data for this image is processed. <br /><br />
                This may take up to 30 seconds depending on the number of respondents and the amount of elements.</div>";
            echo "<p>&nbsp;</p>";
            echo "<p>&nbsp;</p>";
            echo "</div>";
        }
    }
    
    echo "</div>";
?>

<div id="collapseModal" class="initialModal" style="width: 700px !important;">
    <form id="collapseform" name="collapseform">
    <div class="heading">Collapse Images</div>
    <p class="centered"><strong>Note: </strong>Images may only be collapsed if the Nets/Elements match.</p>
    <div id="collapseimagelist" style="float:left; width:45%; margin-right:2%;">
        <div class="heading">Images</div>
        <select name="collapseImageSelect" multiple="multiple" style="height:350px; font-size:11px; width:100%;">
            <?php echo $job->getJobImageOptionsForCollapse(); ?>
        </select>
        
    </div>
    <div style="float:left;width:12%; margin-right:2%;">
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p class="centered" style="font-size:8pt;">
            <span class="buttons" id="addCollapseGroupButton">Collapse Selected Images<br /> ---></span>
        </p>
    </div>
    <div id="collapsegrouplist" style="float:left; width:37%;max-height: 600px;overflow: auto;">
        <div class="heading">Collapse Groups</div>
        <?php echo $job->getCollapseGroups(); ?>
    </div>
    <p class="clear">&nbsp;</p>
    <p class="centered" id="buttonholder"><span class="buttons" id="saveCollapseGroupButton">Save Collapse Groups</span></p>
    </form>
</div>

<p>&nbsp;</p>
<script>
    var MET = <?php echo $hasMET ? 1: 0; ?>;
    var EF = <?php echo $hasEF ? 1: 0; ?>;
    var SL = <?php echo $hasSL ? 1: 0; ?>;
    var ET = <?php echo $hasET ? 1: 0; ?>;
    
    $("#collapseLink").click(function() {
        $("#collapseModal").modal();
    });
    
    
    
    $("#addCollapseGroupButton").click(function() {
        var groupcount = parseInt($(".collapsegroupdetail:last").find('.groupnumber').val()) + 1;
        if (isNaN(groupcount)) {
            groupcount = 1;
        }
        var members = '';
        var membertext = '';
        var matchinfo = '';
        var memberinfo = [];
        var error = false;
        $("select[name=collapseImageSelect] option:selected").each(function() {
            matchinfo = $(this).attr('matchinfo');
            if (memberinfo.length==0 || memberinfo.indexOf(matchinfo)!==-1){
                members += $(this).val()+",";
                membertext += $(this).text()+"<br />";
                memberinfo.push(matchinfo);
            } else {
                alert ("Error: images are only collapsable if their nets/elements match");
                error = true;
                return false;
            }
        });
        if (!error){
            $("#collapsegrouplist").append("<div class=\"collapsegroupdetail\"><input type=\"hidden\" name=\"collapse["+groupcount+"][members]\" value=\""+members+"\" /><input class=\"groupnumber\" type=\"hidden\" name=\"collapse["+groupcount+"][number]\" value=\""+groupcount+"\" /><div class=\"collapsegroupheading\">Group "+groupcount+"</div><div class=\"collapsegroupdelete\">&nbsp;</div><div class=\"collapsegroupmembers\">"+membertext+"</div></div>");
        }
    });
    
    $(document).on('click', '#saveCollapseGroupButton', function () {
        if ($(".collapsegroupdetail").length === 0) {
            alert ("Error: no groups defined.");
            return false;
        }
        $(this).parent().html("<img src=\"css/spinner.gif\" style=\"height:50px;\" />");
        $.ajax({
            type: 'post',
            url: 'DBFunctions.php?action=saveCollapseGroup&job=<?php echo $job->job ?>',
            data: $("#collapseform").serialize(),
            success: function (data) {
                if (data === "saved") {
                    alert ("Collapse groups saved successfully");
                    window.location.reload();
                }
                else {
                    alert ("Error: " + data);
                    $("#buttonholder").html("<span class=\"buttons\" id=\"saveCollapseGroupButton\">Save Collapse Groups</span>");
                }
            }
        });
    });
    
    $(document).on('click', '.collapsegroupdelete', function(e) {
        var group = $(this).parent().find('.groupnumber').val();
        var container = $(this).parent();
        if (confirm("Are you sure you want to delete this image group?  There is no way to undo this.")) {
            $.ajax({
                type: "get",
                url: "DBFunctions.php?action=deleteCollapseGroup&job=<?php echo $job->job ?>&group="+group,
                success: function () {
                    container.remove();
                }
            });
        }
    });
    
    $(document).on('click', '.netLink', function() {
        $(this).parent().parent().nextUntil('.tablesorter-parentRow').toggle();
    });

    $(document).on('click', '.expandAllButton', function() {
        $(this).closest('div').parent().find('table tr').css("display", "table-row");
    });

    $(document).on('click', '.collapseAllButton', function() {
        $(this).closest('div').parent().find('table .tablesorter-childRow').css("display", "none");
    });

    $(document).on('mouseover', '.dataElementRow', function() {
        var image = $(".ui-tabs-active").attr('id');
        var element = $(this).attr('element');
        $("#"+image+element).toggle();
    });
    
    $(document).on('mouseout', '.dataElementRow', function() {
        var image = $(".ui-tabs-active").attr('id');
        var element = $(this).attr('element');
        $("#"+image+element).toggle();
    });
    
     $(document).on('mouseover', '.dataElementRowSummary', function() {
        var element = $(this).attr('element');
        $(".summary"+element).toggle();
    });
    
    $(document).on('mouseout', '.dataElementRowSummary', function() {
        var element = $(this).attr('element');
        $(".summary"+element).toggle();
    });
    
    $(document).on('mouseover', '.netRow', function () {
        var image = $(".ui-tabs-active").attr('id');
        var elementrows = $(this).nextUntil('.tablesorter-ParentRow');
        $(elementrows).each(function() {
            $("#"+image+$(this).attr('element')).toggle();
        });
    });
    
    $(document).on('mouseout', '.netRow', function () {
        var image = $(".ui-tabs-active").attr('id');
        var elementrows = $(this).nextUntil('.tablesorter-ParentRow');
        $(elementrows).each(function() {
            $("#"+image+$(this).attr('element')).toggle();
        });
    });
    
    $(document).on('mouseover', '.netRowSummary', function () {
        var elementrows = $(this).nextUntil('.tablesorter-ParentRow');
        $(elementrows).each(function() {
            $(".summary"+$(this).attr('element')).toggle();
        });
    });
    
    $(document).on('mouseout', '.netRowSummary', function () {
        var elementrows = $(this).nextUntil('.tablesorter-ParentRow');
        $(elementrows).each(function() {
            $(".summary"+$(this).attr('element')).toggle();
        });
    });
    
    $("#groupTabs ul li").each(function(i) {
        var count = $("#groupTabs ul li").length;
        if (i > 0 || <?php echo count($data->shelves);?> <= 1) {
            var id = $(this).attr('id');
            var currenttab = '';
            if ((id.indexOf('collapse') === 0)) {
                currenttab = $("#collapsegroup"+id);    
            }
            else {
                currenttab = $("#image"+id);
            }
            queueAjax(id);
            i++;
        }
    });
 
function statTesting(){
    var shelfCount = $(".summary").find(".head1 th:last").attr("colspan");
    var bigLetter = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
    var smallLetter = 'abcdefghijklmnopqrstuvwxyz'.split('');
    var constant1 = [1.95996398454005,1.64500012974481,1.64500012974481,1.28200128159016];
    var constant2 = [1.64500012974481,1.28200128159016];
    var bigConstant = constant1[$('#statLevel').val()];
    if ($('#statLevel').val()<=1){
        var smallConstant = constant2[$('#statLevel').val()];
    }
    $('.dostats').each(function(){
        var data = [];
        var result = [];
        var base = [];
        for (var set=0;set<shelfCount;set++) {
            data[set] = [];
            result[set] = [];
            for (var alpha=0;alpha<shelfCount;alpha++) {
                result[set][alpha] = '';
            }
        }
        for (m=0;m<=2;m++){ //measure
            for (i=0;i<shelfCount;i++){ //shelves(phases)
                $(this).find('td:eq('+(m*shelfCount+i+1)+')').text($(this).find('td:eq('+(m*shelfCount+i+1)+')').text().replace(/\D/g,""));
                base[i] = parseFloat($(this).parent().parent().find('.respCount:eq('+(i)+')').text());
                data[m][i] = parseFloat($(this).find('td:eq('+(m*shelfCount+i+1)+')').text());
                for (var set=0;set<shelfCount;set++) {
                    result[set][i] = '';
                }
            }
            for (i1=0;i1<shelfCount;i1++){ //parent
                for (i2=i1+1;i2<shelfCount;i2++){ //child
                    if (!(data[m][i1]===data[m][i2])){
                        val = parseFloat(Math.abs((data[m][i1]/100-data[m][i2]/100)/Math.sqrt(Math.abs(data[m][i1]/100*(1-data[m][i1]/100)/base[i1]+data[m][i2]/100*(1-data[m][i2]/100)/base[i2])))*1.0)
                        if (val>bigConstant){
                            (data[m][i1]>data[m][i2]?result[m][i1] += bigLetter[i2]:result[m][i2] += bigLetter[i1]);
                        } 
                        else if (smallConstant && val>smallConstant){
                            (data[m][i1]>data[m][i2]?result[m][i1] += smallLetter[i2]:result[m][i2] += smallLetter[i1]);
                        }
                    }
                }
            }
            for (i=0;i<shelfCount;i++){ //results
                $(this).find('td:eq('+(m*shelfCount+i+1)+')').append('<span style=\"color:red;\">'+result[m][i]+'</span>');
            }
        }
    });
}
    
$(".summary").ready(function(){
    statTesting();
});

$("#statLevel").change(function(){
    statTesting();
});

$(document).on('click', 'input[name=displayDataRadio]', function () {
    $(".data-numeric").hide();
    $(".data-percent").hide();
    val = $(this).val();
    var $select = $('div[aria-hidden="false"] select');
    if ($select.length > 0){
        $('.'+val+'.'+$select.val()+'sec').show();
    } else {
        $('.'+val).show();
    }
});

$(document).on('change','#durationSelect',function(){
    var $activetab = $('div[aria-hidden="false"]');
    if ($(this).val() !== "Custom"){
        $("#customTime", $activetab).hide();
        $(".durationMetric", $activetab).hide();
        $("." + $(this).val()+"sec:not(.data-percent,.data-numeric)", $activetab).show();
        $("." + $(this).val()+"sec."+$('input[name=displayDataRadio]:checked').val(), $activetab).show();
        $('span#customTime', $activetab).hide();
    } else {
        $('span#customTime', $activetab).show();
    }
    $(".durationSelect option[value='"+$(this).val()+"']", $activetab).prop('selected', 'true');
});

    $(document).on('click', '#saveCustomTime', function () {
        if ($("input[name=customTimeSeconds]").length === 0) {
            alert ("Error: please enter a time in seconds");
            return false;
        }
        $.ajax({
            type: 'post',
            url: 'DBFunctions.php?action=saveCustomTime&job=<?php echo $job->job ?>',
            data: $("#customTimeForm").serialize(),
            success: function (data) {
                if (data === "saved") {
                    window.location.reload();
                }
                else {
                    alert ("Error: " + data);
                }
            }
        });
    }) ; 


    var ajaxLimit = 2; //number of concurrent ajax requests allowed
    var currentReq = 0; //current number of ajax requests
    var attemptLimit = 1; //number of times a upc will be added to the request queue on timeout


    function queueAjax(id, attempts = 0){
        var check = function(){
            if(currentReq < ajaxLimit){
                $.ajax({
                    type: "post",
                    url: "DBFunctions.php?action=getData&job=<?php echo $job->job?>&imageID="+id,
                    data: $('#reportForm').serialize(),
                    success: function (data) {
                        var divID = '#image'+id;
                        if (id.indexOf('collapse') === 0){
                            divID = "#collapsegroup"+id;
                        }
                        $(divID).html(data);

                        $(".buttons").button();
                        if ($(".loadingDiv").length === 0) {
                            if(ET){
                                $("#exportLinkET").show();
                                $("#exportLinkIndividualData").show();
                            }
                            if(MET || SL || EF){
                                $("#exportLinkMET").show();
                                $("#exportLinkIndividualData").show();
                            }
                        }
                    },
                    error: function (data) {
                        var divID = '#image'+id;
                        if (id.indexOf('collapse') === 0){
                            divID = "#collapsegroup"+id;
                        }
                        attempts++;
                        if(attempts < attemptLimit){
                            queueAjax(id, attempts);
                        } else {
                            $(divID).html('An error occured while loading the data for this image.  Please try running the data again or contact IT for assistance.<br/><br/>There is a better chance if you retry after all the other tables are loaded.<br/><br/>\n\
                                <span class="button" id="retry_'+id+'" name="'+id+'">Retry Analysis for this Image</span>');
                            $('#retry_'+id).button().click(function(){
                                $("#exportLinkET").hide();
                                $("#exportLinkMET").hide();
                                $("#exportLinkIndividualData").hide();
                                queueAjax(id);
                                $(divID).html('<div class=\"loadingDiv\" style=\"text-align:center;\"><img src=\"css/spinner.gif\" /><br/><br />Please wait as the fixation data for this image is processed. <br /><br />This may take up to 30 seconds depending on the number of respondents and the amount of elements.</div>');
                            });
                            console.log(id, JSON.stringify(data));
                        }
                        
                    },
                    complete: function(){
                        currentReq -= 1;
                    }
                });
                currentReq += 1;
            }
            else {
                setTimeout(check, 500);
            }
        };

        //run the function
        check();
    }
</script>
