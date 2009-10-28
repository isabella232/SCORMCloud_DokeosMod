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



$htmlHeadXtra[] = '

<style>
.manager_table {padding:5px;width:90%;}
.manager_table td {padding:2px; height:25px; border-bottom:1px dotted;}
.tblHeader td {font-weight:bold; font-size:110%; border-bottom:1px solid;}
.link_disable {color:#A8A7A7;}
div.row {clear:both; padding-top:8px;}
.signupFrame {padding:5px;width:90%;border:none;height:500px;}


</style>';

$nameTools = "SCORM Cloud Signup";
Display::display_header($nameTools,"Path");





$cssUrl = api_get_path(WEB_PATH).'main/scorm_cloud/cloudsignup.css';

?>
<div class="row"><div class="form_header">SCORM Cloud Signup</div></div>
<iframe class='signupFrame' src='https://accounts.scorm.com/scorm-cloud-manager/public/signup-embedded?cssurl=<?php echo $cssUrl; ?>'/>



<?php

Display::display_footer();
	

?>