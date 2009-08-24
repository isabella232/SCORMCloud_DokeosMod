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

$ScormService = cloud_getScormEngineService();

if (isset($_REQUEST['action'])){
	
	if ($_REQUEST['action'] == 'delete'  && isset($_REQUEST['ccid'])){
		cloud_deleteCourse($_REQUEST['ccid']);
		
		header('location: cloudManager.php');
	} elseif ($_REQUEST['action'] == 'upload'){
		
		$courseId = uniqid();
		
		require_once('CloudPHPLibrary/CourseData.php');
		$uploadService = $ScormService->getUploadService();
		$courseService = $ScormService->getCourseService();
		// Where the file is going to be placed 
		$target_path = "uploads/";
	
		/* Add the original filename to our target path.  
		Result is "uploads/filename.extension" */
		$target_path = $target_path . basename( $_FILES['user_file']['name']); 
		
		$tempFile = $_FILES["user_file"]["tmp_name"];
	
		move_uploaded_file($_FILES['user_file']['tmp_name'], $target_path);
		
		$absoluteFilePathToZip = $target_path;
	
		//now upload the file and save the resulting location
		$location = $uploadService->UploadFile($absoluteFilePathToZip,null);
		
		$courseService->ImportUploadedCourse($courseId, $location, null);
		//delete local(Dokeos server) copy
		unlink($target_path);
		
		header('location: cloudManager.php');
	}
	
}

$htmlHeadXtra[] = '

<style>
.manager_table {padding:5px;width:90%;}
.manager_table td {padding:2px; height:25px; border-bottom:1px dotted;}
.tblHeader td {font-weight:bold; font-size:110%; border-bottom:1px solid;}
.link_disable {color:#A8A7A7;}
div.row {clear:both; padding-top:8px;}



</style>';

$nameTools = "SCORM Cloud Manager";
Display::display_header($nameTools,"Path");



$courseService = $ScormService->getCourseService();

$allResults = $courseService->GetCourseList();
echo '<div class="row"><div class="form_header">SCORM Cloud Packages</div></div>';
echo '<table class="manager_table" cellspacing="0">';
echo '<tr class="tblHeader"><td>Package Title</td><td>Total Cloud Registrations</td><td>'.get_lang('MyCourses').' (Trainer)</td><td> </td></tr>';
foreach($allResults as $course)
{
	$ccid = $course->getCourseId();
	echo '<td>';
	echo $course->getTitle();
	echo '</td><td>';
	echo $course->getNumberOfRegistrations();
	echo '</td>';
	
	$tbl_scorm_cloud = Database :: get_main_table('scorm_cloud');
	$tbl_course = Database :: get_main_table('course');
	//select distinct c.title from course c inner join scorm_cloud sc on sc.course_code = c.code where sc.cloud_course_id = '4a8d6623c554c'
	$sql_cloud = "select distinct c.title,c.tutor_name from $tbl_course c inner join $tbl_scorm_cloud sc on sc.course_code = c.code ".
		"where sc.cloud_course_id = '$ccid'";
	
	$res = api_sql_query($sql_cloud, __FILE__, __LINE__);
	
	echo '<td>';
	while ($row = Database :: fetch_array($res)) {
		echo $row['title'].' ('.$row['tutor_name'].')<br/>';
	}
	echo '</td><td>';
	if (Database :: num_rows($res) > 0){
		echo '<span class="link_disable">Delete Package</span>';
	} else {
		echo '<a href="cloudManager.php?action=delete&ccid='.$course->getCourseId().'">Delete Package</a>';
	}
	echo '</td></tr>';
	
}
echo '</table>';

echo '
<br/>
<br/>
<form method="post" action="cloudManager.php" enctype="multipart/form-data">
	<div class="row">
		<div class="form_header">New SCORM Cloud Upload</div>
	</div>
	<div class="row">
		<div class="label">SCORM or AICC file to upload</div>
		<div class="formw"><input type="file" name="user_file"/></div>
	</div>
	<input type="hidden" name="action" value="upload"/>
	<div class="row">
		<div class="label"> </div>
		<div class="formw"><button class="upload" type="submit" name="submit">Upload</button></div>
	</div>
	

</form>
';




Display::display_footer();
	

?>