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

$language_file[] = "document";
$language_file[] = "scorm";
$language_file[] = "scormdocument";
$language_file[] = "learnpath";
$language_file[] = "scorm_cloud";

// global settings initialisation
// also provides access to main api (inc/lib/main_api.lib.php)
include("../inc/global.inc.php");

$is_allowed_to_edit = api_is_allowed_to_edit();
if(!$is_allowed_to_edit){
	api_not_allowed(true);
}



/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/

//many useful functions in main_api.lib.php, by default included

//require_once(api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php');
require_once(api_get_path(LIBRARY_PATH) . 'events.lib.inc.php');
require_once(api_get_path(LIBRARY_PATH) . 'document.lib.php');
require_once ('scorm_cloud.lib.php');

$cid = $_REQUEST['cidReq'];
$lp_id = $_REQUEST['lp_id'];



	/**
	 * Just display the form needed to upload a SCORM and give its settings
	 */
	$nameTools = get_lang("cloudUpload");
	$interbreadcrumb[]= array ("url"=>"../newscorm/lp_controller.php?action=list", "name"=> get_lang("Learnpath"));
	Display::display_header($nameTools,"Path");
	
	
	
	echo '<div class="actions">';
	echo '<a href="../newscorm/lp_controller.php?cidReq='.$_course['sysCode'].'">'.Display::return_icon('scorm.gif',get_lang('ReturnToLearningPaths')).' '.get_lang('ReturnToLearningPaths').'</a>';
	echo '</div>';
	
	
	$tbl_scorm_cloud = Database :: get_main_table('scorm_cloud');
	$sql_cloud_get_course = "Select cloud_course_id from $tbl_scorm_cloud ".
	"WHERE course_code = $cid  AND lp_id = $lp_id ";
	
	$res = api_sql_query($sql_cloud_get_course, __FILE__, __LINE__);
	if (Database :: num_rows($res) > 0) {
	    $row = Database :: fetch_array($res);
	    $cloud_courseId = $row['cloud_course_id'];
	}
	
	$ScormService = cloud_getScormEngineService();
	$courseService = $ScormService->getCourseService();
	
	$cssUrl = api_get_path(WEB_PATH).'main/scorm_cloud/packageproperties.css';
	
	$url = $courseService->GetPropertyEditorUrl($cloud_courseId,$cssUrl);
	//echo $url;
	
	$lp_table = Database :: get_course_table('lp');
	$sql = "SELECT name FROM $lp_table WHERE id = $lp_id";
	$name_res = api_sql_query($sql, __FILE__, __LINE__);
	$row = Database :: fetch_array($name_res);
        $title = $row['name'];
    
	
	echo "<img src=\"../scorm_cloud/img/cloud_icon_sm.gif\" border=\"0\" title=\"".get_lang('cloudViewProps')."\">".
		'<span style="font-size:125%;position:relative;top:-5px;margin-left:5px;">'.$title.'</span>';
	
	echo "<div style=''>";
	echo "<iframe src='$url' width='100%' height='450px' frameborder='0' framepadding='0' ></iframe>";
	echo "</div>";
	
	
	// footer
	Display::display_footer();
	

?>