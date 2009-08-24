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


require_once('../newscorm/back_compat.inc.php');
require_once('../newscorm/learnpath.class.php');
require_once('scorm_cloud.lib.php');

//get the courseid and userid

	
        //Dokeos Course ID (a db, training, etc. - can contain many courses (or none))
        $cid = $_GET['cidReq'];
        //learningPath Id - learningpath is a course
        $lp_id = $_GET['lp_id'];
        
        $userInfo = api_get_user_info(); 
        $userId = $userInfo['user_id'];
        
        $need_to_create_reg = false;
        

        $lp_view_id = cloud_getLpViewId($cid,$lp_id,$userId,true);
        //echo $lp_view_id;
        $regId = cloud_getRegId($cid,$lp_view_id);
        
        
        $ScormService = cloud_getScormEngineService();
	$regService = $ScormService->getRegistrationService();
	
        $exitUrl = api_get_path(WEB_PATH).'main/scorm_cloud/RegistrationResults.php?regid='.$regId.'&lp_id='.$lp_id;
        $launchUrl = $regService->GetLaunchUrl($regId, $exitUrl);
        //echo $launchUrl;
        header('location: '.$launchUrl);
        
?>