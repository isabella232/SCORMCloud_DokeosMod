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

if (isset($_REQUEST['cidReq'])){
	$cid = $_REQUEST['cidReq'];
}

if(!isset($_REQUEST['action'])){

	/**
	 * Just display the form needed to upload a SCORM and give its settings
	 */
	$nameTools = get_lang("cloudUpload");
	$interbreadcrumb[]= array ("url"=>"../newscorm/lp_controller.php?action=list", "name"=> get_lang("Learnpath"));
	Display::display_header($nameTools,"Path");
	
	
	
	echo '<div class="actions">';
	echo '<a href="../newscorm/lp_controller.php?cidReq='.$_course['sysCode'].'">'.Display::return_icon('scorm.gif',get_lang('ReturnToLearningPaths')).' '.get_lang('ReturnToLearningPaths').'</a>';
	echo '</div>';
	
	require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
	require_once('../newscorm/content_makers.inc.php');
	
	if (api_get_setting('enableCloudCourseSharing', 'existingCloudCourseUse') == 'true') {
		$formImport = new FormValidator('','POST','upload.php','','id="upload_form" style="background-image: url(\'img/icon_cloud.jpg\'); background-repeat: no-repeat; background-position: 600px 35px;"');
		$formImport->addElement('header', '', get_lang("cloudImportExisting"));
		$formImport->addElement('hidden', 'action', 'import');
		$formImport->addElement('hidden', 'cidReq', $cid);
		$select_cloud_course = &$formImport->addElement('select','courseId',get_lang('cloudSelectCourse'));
	
		require_once('CloudPHPLibrary/CourseData.php');
		$ScormService = cloud_getScormEngineService();
		
		$courseService = $ScormService->getCourseService();
		$allResults = $courseService->GetCourseList();
		foreach($allResults as $course){
			$select_cloud_course->addOption($course->getTitle(),$course->getCourseId());
		}
		
		$select_content_marker_import = &$formImport->addElement('select','content_maker',get_lang('ContentMaker'));

		foreach($content_origins as $index => $origin){
			$select_content_marker_import->addOption($origin,$origin);
		}
		
		$formImport->addElement('style_submit_button','submit', get_lang('cloudImport'),'class="upload"');
		
		$formImport->addElement('html', '<br /><br /><br /><br /><br />');
		
		$formImport->display();
	}


	$form = new FormValidator('','POST','upload.php','','id="upload_form" enctype="multipart/form-data" style="background-image: url(\'img/icon_cloud.jpg\'); background-repeat: no-repeat; background-position: 600px 35px;"');
	$form->addElement('header', '', get_lang("cloudNewFileUpload"));
	$form->addElement('hidden', 'action', 'upload');
	$form->addElement('hidden', 'cidReq', $cid);
	$form->addElement('file','user_file',get_lang('FileToUpload'));
	
	$select_content_marker = &$form->addElement('select','content_maker',get_lang('ContentMaker'));

	foreach($content_origins as $index => $origin){
		$select_content_marker->addOption($origin,$origin);
	}
	
	$form->addElement('style_submit_button','submit', get_lang('Upload'),'class="upload"');
	
	$form->addElement('html', '<br /><br /><br /><br /><br />');
	
	$form->display();
	
	// footer
	Display::display_footer();
	
} elseif(isset($_REQUEST['action'])){
	
	
	
	$courseId = uniqid();
	//echo $courseId.'<br/>';
	if ($_REQUEST['action'] == 'upload'){
		
		
		require_once('CloudPHPLibrary/CourseData.php');
		$ScormService = cloud_getScormEngineService();
		$uploadService = $ScormService->getUploadService();
		$courseService = $ScormService->getCourseService();
		// Where the file is going to be placed 
		$target_path = "tmp/uploads/";
	
		/* Add the original filename to our target path.  
		Result is "uploads/filename.extension" */
		$target_path = $target_path . basename( $_FILES['user_file']['name']); 
		
		$tempFile = $_FILES["user_file"]["tmp_name"];
	
		move_uploaded_file($_FILES['user_file']['tmp_name'], $target_path);
		
		$absoluteFilePathToZip = $target_path;
	
		//now upload the file and save the resulting location
		$location = $uploadService->UploadFile($absoluteFilePathToZip,null);
		
		$courseService->ImportUploadedCourse($courseId, $location, null);
		
		unlink($target_path);
		
		$msg = urlencode(get_lang('cloudUploadSuccess'));
		//echo $msg.'<br/>';
		
	} elseif ($_REQUEST['action'] == 'import'){
		if (isset($_REQUEST['courseId'])){
			$courseId = $_REQUEST['courseId'];
			
			$msg = urlencode(get_lang('cloudImportSuccess'));
		}
	}

	$content_maker = $_REQUEST['content_maker'];
	
	//course is uploaded to the cloud, now add it to dokeos
	require_once('../newscorm/learnpath.class.php');
	
	if (!isset($ScormService)){
		$ScormService = cloud_getScormEngineService();
	}
	$courseService = $ScormService->getCourseService();
	$allResults = $courseService->GetCourseList($courseId);
	
	$title = $allResults[0]->getTitle();
	
	$tbl_lp = Database :: get_course_table('lp');
		
	//check lp_name doesn't exist, otherwise append something
	$i = 0;
	$title = learnpath :: escape_string($title); 
	$newtitle = $title;
	$check_name = "SELECT * FROM $tbl_lp WHERE name = '$title'";
	//if($this->debug>2){error_log('New LP - Checking the name for new LP: '.$check_name,0);}
	$res_name = api_sql_query($check_name, __FILE__, __LINE__);
	while (Database :: num_rows($res_name)) {
		//there is already one such name, update the current one a bit
		$i++;
		$newtitle = $title . ' - ' . $i;
		$check_name = "SELECT * FROM $tbl_lp WHERE name = '$newtitle'";
		//if($this->debug>2){error_log('New LP - Checking the name for new LP: '.$check_name,0);}
		$res_name = api_sql_query($check_name, __FILE__, __LINE__);
	}
	$title = $newtitle;
	//echo $title;	
		
	$type = 2;
		
	$get_max = "SELECT MAX(display_order) FROM $tbl_lp";
	$res_max = api_sql_query($get_max, __FILE__, __LINE__);
	if (Database :: num_rows($res_max) < 1) {
		$dsp = 1;
	} else {
		$row = Database :: fetch_array($res_max);
		$dsp = $row[0] + 1;
	}
	$sql_insert = "INSERT INTO $tbl_lp " .
	"(lp_type,name,description,path,default_view_mod," .
	"default_encoding,display_order,content_maker," .
	"content_local) " .
	"VALUES ($type,'$title','','','scormcloud'," .
	"'UTF-8','$dsp','$content_maker'," .
	"'remote')";
	//echo $sql_insert;
	$res_insert = api_sql_query($sql_insert, __FILE__, __LINE__);
	$id = Database :: get_last_insert_id();
	
	$tbl_scorm_cloud = Database :: get_main_table('scorm_cloud');
	$sql_cloud_insert = "INSERT INTO $tbl_scorm_cloud ".
	"(course_code,lp_id,cloud_course_id)" .
	"VALUES ('$cid',$id,'$courseId')";

	$res_insert2 = api_sql_query($sql_cloud_insert, __FILE__, __LINE__);
	
	$dialogtype = 'confirmation';
	header('location: ../newscorm/lp_controller.php?action=list&dialog_box='.$msg.'&dialogtype='.$dialogtype);
	exit;
}
?>