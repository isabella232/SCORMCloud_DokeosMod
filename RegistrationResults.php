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

$regid = $_GET["regid"];
$lp_id = $_GET["lp_id"];

$course_id = substr($regid,0,strpos($regid,'_'));
$lp_view_id = substr($regid,strpos($regid,'_')+1);

if($regid != null){
	
	cloud_updateLMSRegistrationResults($regid,$lp_id);
	
	$exitUrl = api_get_path(WEB_PATH).'main/newscorm/lp_controller.php?cidReq='.$cid;
        header('location: '.$exitUrl);		
}





?>