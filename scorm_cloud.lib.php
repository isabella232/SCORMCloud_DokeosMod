<?PHP

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

function cloud_getScormEngineService(){
	require_once('CloudPHPLibrary/ScormEngineService.php');
	
	$ServiceUrl = api_get_setting('scormCloudCredsUrl','appUrl');
	$AppId = api_get_setting('scormCloudCredsId','appId');
	$SecretKey = api_get_setting('scormCloudCredsPw','appPw');
	
	return new ScormEngineService($ServiceUrl,$AppId,$SecretKey);
}


function cloud_getRegId($cid,$lp_view_id){
    return $cid.'_'.$lp_view_id;
}

function cloud_isCloudCourse($cid, $lp_id){
	$tbl_lp = Database :: get_course_table_from_code($cid, TABLE_LP_MAIN);
		
	$check_sql = "SELECT default_view_mod FROM $tbl_lp WHERE id = $lp_id";
	
	$res = api_sql_query($check_sql, __FILE__, __LINE__);
	$row = Database :: fetch_array($res);
	return ($row['default_view_mod'] == 'scormcloud');
	
}

function cloud_getCourseScore($cid, $lp_id, $user_id){
    
    $lp_view_id = cloud_getLpViewId($cid,$lp_id,$user_id,true);
    
    $regId = cloud_getRegId($cid,$lp_view_id);
    
    //Get the results from the cloud
    $ScormService = cloud_getScormEngineService();
    $regService = $ScormService->getRegistrationService();
    $resultXmlString = $regService->GetRegistrationResult($regId, 0, 0);
    $resXml = simplexml_load_string($resultXmlString);
    
    $scoreVal = $resXml->registrationreport->score;
    return round((float)$scoreVal*100,2);
    
}

function cloud_getLpViewId($cid,$lp_id, $user_id, $createRegIfNeeded = false){
    
    $lpv_table = Database::get_course_table_from_code($cid,TABLE_LP_VIEW);
    
    //selecting by view_count descending allows to get the highest view_count first
    $sql = "SELECT * FROM $lpv_table WHERE lp_id = $lp_id AND user_id = $user_id ORDER BY view_count DESC";
    $view_res = api_sql_query($sql, __FILE__, __LINE__);
    
    if (Database :: num_rows($view_res) > 0) {
            $row = Database :: fetch_array($view_res);
            $lp_view_id = $row['id'];
            
    } else {
    
            $sql_ins = "INSERT INTO $lpv_table (lp_id,user_id,view_count) VALUES ($lp_id,$user_id,1)";
            $res_ins = api_sql_query($sql_ins, __FILE__, __LINE__);
            $lp_view_id= Database :: get_last_insert_id();
            if ($createRegIfNeeded){
		cloud_createCloudRegistration($cid,$lp_id,$lp_view_id);
            
	    }
            
    }

    return $lp_view_id;
}

function cloud_createCloudRegistration($cid,$lp_id,$lp_view_id){
        
    $userInfo = api_get_user_info(); 
    $userId = $userInfo['user_id'];
        
    $ScormService = cloud_getScormEngineService();
    $regService = $ScormService->getRegistrationService();
    
    $tbl_scorm_cloud = Database :: get_main_table('scorm_cloud');
    $sql_cloud_get_course = "Select cloud_course_id from $tbl_scorm_cloud ".
    "WHERE course_code = '$cid'  AND lp_id = $lp_id ";
    //echo $sql_cloud_get_course.'<br/>';
    $res = api_sql_query($sql_cloud_get_course, __FILE__, __LINE__);
    if (Database :: num_rows($res) > 0) {
        $row = Database :: fetch_array($res);
        $cloud_courseId = $row['cloud_course_id'];
    }
    
    $regService->CreateRegistration(cloud_getRegId($cid,$lp_view_id), $cloud_courseId, $userId, $userInfo['firstName'], $userInfo['lastName']);
    


}

function cloud_getLatestLoginDate($cid,$lp_id, $user_id){
	
	$lp_view_id = cloud_getLpViewId($cid,$lp_id,$user_id,true);
	
	$regid = cloud_getRegId($cid,$lp_view_id);
	
	$ScormService = cloud_getScormEngineService();
	$regService = $ScormService->getRegistrationService();
	
	$allResults = $regService->GetLaunchHistory($regid);
	
	$date = 0;
	
	foreach($allResults as $result)
	{
		$intTime = cloud_convertTimeToInt($result->getLaunchTime());
		
		if ($intTime > $date){
			$date = $intTime;
		}
		
	}
	
	return $date;
}

function cloud_getCourseLaunchTime($regid){
	
	$ScormService = cloud_getScormEngineService();
	$regService = $ScormService->getRegistrationService();
	
	$resultArray = $regService->GetLaunchHistory($regid);
	
	if (count($resultArray) > 0){
		return cloud_convertTimeToInt($resultArray[0]->getLaunchTime());
	}
}

function cloud_getTotalCourseTime($cid,$lp_id, $user_id){
	
	$lp_view_id = cloud_getLpViewId($cid,$lp_id,$user_id,true);
    
	$regId = cloud_getRegId($cid,$lp_view_id);
	
	$ScormService = cloud_getScormEngineService();
	$regService = $ScormService->getRegistrationService();
	$resultXmlString = $regService->GetRegistrationResult($regId, 0, 0);
	$resXml = simplexml_load_string($resultXmlString);
	//echo $resultXmlString;
	$timeVal = $resXml->registrationreport->totaltime;
	return $timeVal;
	
}


//input format 2009-08-11T19:01:50.081+0000 
function cloud_convertTimeToInt($str){
	//echo 'hour: '.substr($str,11,2).'<br/>';
	//echo 'minute: '.substr($str,14,2).'<br/>';
	return mktime(substr($str,11,2),substr($str,14,2),substr($str,17,2),substr($str,5,2),substr($str,8,2), substr($str,0,4));
}

function cloud_deleteRegistrations($cid, $lp_id){
	
	$ScormService = cloud_getScormEngineService();
	$regService = $ScormService->getRegistrationService();
	
	$lpv_table = Database::get_course_table_from_code($cid,TABLE_LP_VIEW);
    
	//selecting by view_count descending allows to get the highest view_count first
	$sql = "SELECT * FROM $lpv_table WHERE lp_id = $lp_id";
	$view_res = api_sql_query($sql, __FILE__, __LINE__);
	
	while ($row = Database :: fetch_array($view_res)) {
		$lp_view_id = $row['id'];
		
		$regid = cloud_getRegId($cid,$lp_view_id);
		//echo $regid.' ';
		$regService->DeleteRegistration($regid, 'false');
		
	}
	
	$tbl_scorm_cloud = Database :: get_main_table('scorm_cloud');
	$sql_cloud_delete = "Delete From $tbl_scorm_cloud ".
	"Where course_code = '$cid' AND lp_id = $lp_id";

	$res_insert2 = api_sql_query($sql_cloud_delete, __FILE__, __LINE__);
	
}

function cloud_deleteCourse($cloud_courseId){
	
	$ScormService = cloud_getScormEngineService();
	$courseService = $ScormService->getCourseService();
	$courseService->DeleteCourse($cloud_courseId, 'false');
	
	$tbl_scorm_cloud = Database :: get_main_table('scorm_cloud');
	$sql_cloud_delete = "Delete From $tbl_scorm_cloud ".
	"WHere cloud_course_id = '$cloud_courseId'";

	$res_insert2 = api_sql_query($sql_cloud_delete, __FILE__, __LINE__);
}


function cloud_updateLMSRegistrationResults($regid,$lp_id){
	
	$ScormService = cloud_getScormEngineService();
	$regService = $ScormService->getRegistrationService();
	$resultXmlString = $regService->GetRegistrationResult($regid, 2, 0);
	$resXml = simplexml_load_string($resultXmlString);
	
	//echo 'xml: '.$resultXmlString.':endXml<br/>';
	
	$course_id = substr($regid,0,strpos($regid,'_'));
	$lp_view_id = substr($regid,strpos($regid,'_')+1);
	
	$activity = $resXml->registrationreport->activity[0];
	$report = $root_activities[0];
	
	$act_ref = $activity["id"];
	$act_title = $activity->title;
	
	//required type in order to report score
	$act_type = 'sco';
	
	
	
	$act_start_time = cloud_getCourseLaunchTime($regid);
	
	$lp_view_table = Database::get_course_table_from_code($course_id,TABLE_LP_VIEW);
    
	$sql = "SELECT user_id FROM $lp_view_table WHERE id = $lp_view_id";
	$view_res = api_sql_query($sql, __FILE__, __LINE__);
	$userRow = Database::fetch_array($view_res);
	$user_id = $userRow['user_id'];
	
	$act_total_time = cloud_getTotalCourseTime($course_id,$lp_id,$user_id);
	//echo $act_total_time.'<br/>';
	$act_score =  cloud_getCourseScore($course_id,$lp_id,$user_id);
	
	//get the status
	$satisfied = $activity->success;
	$completed = $activity->complete;
	if ($satisfied == 'true'){
		$act_status = 'passed';
	}elseif($completed == 'true' && $satisfied == 'false'){
		$act_status = 'failed';
	}else{
		$act_status = 'incomplete';
	}
	
	//First add an item if necessary, otherwise get the id
	$table_lp_item = Database::get_course_table_from_code($course_id,TABLE_LP_ITEM);
	$sql_check_lp_item = "Select count(*) AS num_items,id FROM $table_lp_item WHERE lp_id = $lp_id";
	$result = api_sql_query($sql_check_lp_item, __FILE__, __LINE__);
	$num_row = Database::fetch_array($result);
	//echo $num_row['num_items'];
	
	if ($num_row['num_items'] == 0){
		$sql_item = "INSERT INTO $table_lp_item (lp_id,item_type,ref,title,path,parent_item_id) ".
			"VALUES($lp_id,'$act_type','$act_ref','$act_title','',0)";
		//echo $sql_item.'<br>'; 
		$res_ins = api_sql_query($sql_item, __FILE__, __LINE__);
	//	$lp_item_id = Database :: get_last_insert_id();
	} else {
		$lp_item_id = $num_row['id'];
		
	}
	
	$table_lp_item_view = Database::get_course_table_from_code($course_id,TABLE_LP_ITEM_VIEW);
	$sql_check_lp_item_view = "Select count(*)AS num_items FROM $table_lp_item_view WHERE lp_view_id = $lp_view_id";
	$result_view = api_sql_query($sql_check_lp_item_view, __FILE__, __LINE__);
	$num_row_view = Database::fetch_array($result_view);
	
	if ($num_row_view['num_items'] == 0){
		$sql_item_view = "INSERT INTO $table_lp_item_view (lp_item_id,lp_view_id,start_time,total_time,score,status) ".
				"VALUES($lp_item_id,$lp_view_id,$act_start_time,$act_total_time,$act_score,'$act_status')";
		
	} else {
		$sql_item_view = "Update $table_lp_item_view " .
			"SET total_time = $act_total_time, " .
			"start_time = $act_start_time, ".
			" score = $act_score,".
			" status = '$act_status'".
			" WHERE lp_item_id = $lp_item_id ".
			"AND lp_view_id = $lp_view_id";
			
	}
	//echo $sql_item_view.'<br>';
	$res_ins = api_sql_query($sql_item_view, __FILE__, __LINE__);
	
	$scos = $resXml->xpath('//activity[not(fn:exists(children/activity))]');
	//echo $scos[0]->title;
	
	$totScos = count($scos);
	$totCompl = 0;
	foreach($scos as $sco){
		if ($sco->completed == 'true') {$totCompl++;}
	}
	$progress = round(($totCompl/$totScos) * 100,2);
	//echo $progress;	
	$table_lp_view = Database::get_course_table_from_code($course_id,TABLE_LP_VIEW);
	$sql_update_lp_view = "UPDATE $table_lp_view SET progress=$progress WHERE id=$lp_view_id";
	
	$res_ins = api_sql_query($sql_update_lp_view, __FILE__, __LINE__);

}



?>