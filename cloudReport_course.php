<?php
 
 /*
==============================================================================
	
	Copyright (c) 2009 Rustici Software
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

==============================================================================
*/
 
$language_file = array ('tracking', 'exercice', 'scorm', 'learnpath');
$language_file[] = 'scorm_cloud';
$cidReset = true;
include ('../inc/global.inc.php');
$is_allowedToTrack = $is_courseAdmin || $is_platformAdmin || $is_courseCoach || $is_sessionAdmin;

if (!$is_allowedToTrack) {
	Display :: display_header(null);
	api_not_allowed();
	Display :: display_footer();
}
//includes for SCORM and LP
require_once '../newscorm/learnpath.class.php';
require_once '../newscorm/learnpathItem.class.php';
require_once '../newscorm/learnpathList.class.php';
require_once '../newscorm/scorm.class.php';
require_once '../newscorm/scormItem.class.php';
require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once('../scorm_cloud/scorm_cloud.lib.php');


if (isset($_GET['cidReq'])) {
	$cidReq = Security::remove_XSS($_GET['cidReq']);
}



$lp_id = intval($_GET['lp_id']);

$lp_table = Database :: get_course_table_from_code($cidReq,'lp');
$sql = "SELECT name FROM $lp_table WHERE id = $lp_id";
$name_res = api_sql_query($sql, __FILE__, __LINE__);
$row = Database :: fetch_array($name_res);
$title = $row['name'];


$tbl_scorm_cloud = Database :: get_main_table('scorm_cloud');
$sql_cloud_get_course = "Select cloud_course_id from $tbl_scorm_cloud ".
"WHERE course_code = '$cidReq'  AND lp_id = $lp_id ";

$res = api_sql_query($sql_cloud_get_course, __FILE__, __LINE__);
if (Database :: num_rows($res) > 0) {
    $row = Database :: fetch_array($res);
    $cloud_courseId = $row['cloud_course_id'];
}



$nameTools = $title;

$ScormService = cloud_getScormEngineService();
$rptService = $ScormService->getReportingService();
$reportageAuth = $rptService->GetReportageAuth('NONAV', false);

$dataServer = str_replace('EngineWebServices','',api_get_setting('scormCloudCredsUrl','appUrl'));
//$dataServer = 'http://localhost/';
$htmlHeadXtra[] = '

<link rel="stylesheet" type="text/css" href="'.$dataServer.'/Reportage/css/reportage.css"/>
<script type="text/javascript" src="'.$dataServer.'/Reportage/scripts/reportage.combined.js"></script>


<script type="text/javascript">
    $(document).ready(function(){
       
    });
</script>

<style>

iframe {border:0; height:500px;width:1090px;}
.reportage div.details_widget {width:100%; font-size:1.5em;}
.reportage table {border-collapse:collapse;}
.detailsWrapper {float:left; width:540px;}
.detailsWrapper.first {margin-right:10px;clear:both;}
.detailsDiv {margin-top:5px;}
.instance_info_reg_fields_title, .score_fields_title {font-size:90%;}
.info_label {font-size:90%;}

</style>';

Display :: display_header($nameTools);

echo '<div class="row">
		<div class="form_header">Course Summary Report</div>
</div>';
$tagSettings = new TagSettings();
$tagSettings->addTag("course",$cidReq);

$sumWidgetSettings = new WidgetSettings(null,$tagSettings,null);
$sumWidgetSettings->setCourseId($cloud_courseId);
$sumWidgetSettings->setShowTitle(true);
$sumWidgetSettings->setScriptBased(false);
$sumWidgetSettings->setEmbedded(true);
$sumWidgetSettings->setIframe(true);

echo "<iframe id=\"UserSummaryFrame\" src=\"".$rptService->GetWidgetUrl($reportageAuth,'courseSummary',$sumWidgetSettings)."\"  scrolling=\"no\" frameborder='0'></iframe>";

echo '<div class="detailsWrapper first">';

echo '<div class="row"><div class="form_header">Learners</div></div>';
echo '<div id="courseLearners" class="detailsDiv">Loading...</div>';
$widgetSettings = new WidgetSettings(null,$tagSettings,null);
$widgetSettings->setCourseId($cloud_courseId);
$widgetSettings->setShowTitle(false);
$widgetSettings->setScriptBased(true);
$widgetSettings->setEmbedded(true);
$widgetSettings->setDivname('courseLearners');
echo '<script type="text/javascript">
        loadScript("'.$rptService->GetWidgetUrl($reportageAuth,'learnerRegistration',$widgetSettings).'");
    </script>';

echo '<div class="row"><div class="form_header">Learner Comments</div></div>';
echo '<div id="courseComments" class="detailsDiv">Loading...</div>';
$widgetSettings->setDivname('courseComments');
echo '<script type="text/javascript">
        loadScript("'.$rptService->GetWidgetUrl($reportageAuth,'courseComments',$widgetSettings).'");
    </script>';

echo '</div>';

echo '<div class="detailsWrapper">';
echo '<div class="row"><div class="form_header">Course Activities</div></div>';
echo '<div id="courseActivities" class="detailsDiv">Loading...</div>';
$widgetSettings->setDivname('courseActivities');
echo '<script type="text/javascript">
        loadScript("'.$rptService->GetWidgetUrl($reportageAuth,'courseActivities',$widgetSettings).'");
    </script>';

echo '<div class="row"><div class="form_header">Interactions</div></div>';
echo '<div id="courseInteractionsShort" class="detailsDiv">Loading...</div>';
$widgetSettings->setDivname('courseInteractionsShort');
echo '<script type="text/javascript">
        loadScript("'.$rptService->GetWidgetUrl($reportageAuth,'courseInteractionsShort',$widgetSettings).'");
    </script>';

echo '</div>';







Display :: display_footer();









?>