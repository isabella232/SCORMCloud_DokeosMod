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
 
$language_file = array ('registration', 'index', 'tracking', 'exercice', 'scorm', 'learnpath');
$language_file[] = 'scorm_cloud';
$cidReset = true;
include ('../inc/global.inc.php');
include_once(api_get_path(LIBRARY_PATH).'course.lib.php');
include_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once ('scorm_cloud.lib.php');

$this_section = "session_my_space";

$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
if($export_csv)
{
	ob_start();
}
$csv_content = array();


$user_id = intval($_GET['student_id']);

if (isset($_GET['course'])) {
	$cidReq = Security::remove_XSS($_GET['course']);
}

$user_infos = UserManager :: get_user_info_by_id($user_id);
$name = $user_infos['firstname'].' '.$user_infos['lastname'];

if(!api_is_platform_admin(true) && !CourseManager :: is_course_teacher($_user['user_id'], $cidReq) && !Tracking :: is_allowed_to_coach_student($_user['user_id'],$_GET['student_id']) && $user_infos['hr_dept_id']!==$_user['user_id']) {
	Display::display_header('');
	api_not_allowed();
	Display::display_footer();
}

$course_exits = CourseManager::course_exists($cidReq);

if (!empty($course_exits)) {
	$_course = CourseManager :: get_course_information($cidReq);
} else {
	api_not_allowed();
}

$_course['dbNameGlu'] = $_configuration['table_prefix'] . $_course['db_name'] . $_configuration['db_glue'];

$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$_course['directory'], 'name' => $_course['title']);
$interbreadcrumb[] = array ("url" => "../tracking/courseLog.php?cidReq=".$cidReq.'&studentlist=true&id_session='.$_SESSION['id_session'], "name" => get_lang("Tracking"));
$interbreadcrumb[] = array("url" => "../mySpace/myStudents.php?student=".Security::remove_XSS($_GET['student_id'])."&course=".$cidReq."&details=true&origin=".Security::remove_XSS($_GET['origin']) , "name" => get_lang("DetailsStudentInCourse"));
$nameTools = get_lang('cloudCourseDetails');

$htmlHeadXtra[] = '
<script type="text/javascript" src="jquery-1.3.2.min.js"></script>
<script type="text/javascript">
    $(document).ready(function(){

    });
    </script>
<style>

#column_headers {position:relative; font-weight:bold; border-bottom:1px solid #4171B5; padding: 3px 0px; margin-top:10px;}
.headertitle {position:relative; width:300px; font-size:110%;}
.headersatisfied {position:absolute; top:3px; left:350px; font-size:110%;}
.headercompleted {position:absolute; top:3px; left:450px; font-size:110%;}
.headerattempts {position:absolute; top:3px; left:550px; font-size:110%;}
.headersuspended {position:absolute; top:3px; left:625px; font-size:110%;}

.activityReportHeader {font-size:150%; position:relative;}
.launchHistoryLink {position:absolute; right:25px; top:0px;}
.launchHistoryLink img {margin-left:10px; vertical-align:top;}

.activity{position:relative; width:100%; border-bottom:1px dotted #4171B5; padding-top:5px; margin-bottom:2px;}
.activityData{width:90%; display:none; border-top:1px dotted #4171B5; padding:5px 20px;}


.title {position:relative; width:300px;height:20px; font-size:115%;}
.satisfaction {position:absolute; top:5px; left:350px; font-size:110%;}
.completion {position:absolute; top:5px; left:450px; font-size:110%;}
.attempts {position:absolute; top:5px; left:550px; font-size:110%;}
.suspended {position:absolute; top:5px; left:625px; font-size:110%;}


.div_detail_arrows {position:absolute; top:3px; right:25px; width:22px; height:22px; overflow:hidden; cursor:pointer;}
.img_detail_arrows {position:absolute; }
.detailsTopLabel{font-size:1.35em; position: relative; background-color:#ffffff; margin-bottom:5px; width:98%; padding-left:3px; }


.table_details {border-spacing:0; width:100%;}
table.table_details td {vertical-align:top;}
.td_objectives {width:235px; padding-right:2px; }
.td_runtimeDetails {width:235px;}
.td_runtimeObjectives {width:235px;}
.tr_space {height:10px;}
td.dotted {border-bottom:1px dotted #4171B5;}
td.intLblWidth {width:110px;}
.actObjectiveData {position: relative; }
.actRuntimeData {position:relative;}

.interactionsTable {display:none;}
.sub_detail_arrows {margin-left:5px; width:16px; height:16px; overflow:hidden; position:absolute; left:90px; top:1px; cursor:pointer;}
.comment_arrow {left:190px;}
.learnerComments {display:none;}
.lmsComments {display:none;}

.actDetailsPropLbl{font-weight:lighter;}
.actDetailsPropVal{}

.margin5 {margin-left:5px;}
.margin20 {margin-left:10px;}
.bold {font-weight:bold;}
.hidden {visibility:hidden;}

.passed {color:green;}
.failed {color:red;}
.completed {color:green;}
.incomplete {color:red;}

</style>';

Display :: display_header($nameTools);

$lp_id = intval($_GET['lp_id']);

$sql = 'SELECT name 
		FROM '.Database::get_course_table(TABLE_LP_MAIN, $_course['db_name']).'
		WHERE id='.Database::escape_string($lp_id);
$rs = api_sql_query($sql, __FILE__, __LINE__);
$lp_title = Database::result($rs, 0, 0);	

echo '<div class ="actions"><div align="left" style="float:left;margin-top:2px;" ><strong>'.$_course['title'].' - '.$lp_title.' - '.$name.'</strong></div>';
echo	'<div class="clear"></div></div>';

$lp_view_id = cloud_getLpViewId($cidReq,$lp_id,$user_id);
$regid = cloud_getRegId($cidReq,$lp_view_id);

echo "<div class='activityReportHeader'>".get_lang('cloudCourseDetails');
echo '<div class="launchHistoryLink"><a href="cloudLaunchHistory.php?regid='.$regid.'&course='.$cidReq.'&lp_id='.$lp_id.'&student_id='.$user_id.'">'.get_lang('launchHistoryReport').'<img src="../img/2rightarrow.gif"/></a></div></div>';


$ScormService = cloud_getScormEngineService();
$regService = $ScormService->getRegistrationService();
//get the full results report
$resultXmlString = $regService->GetRegistrationResult($regid, 2, 0);

//echo $resultXmlString.'<br/>';

$resXml = simplexml_load_string($resultXmlString);

$rootActivities = $resXml->xpath('//registrationreport/activity');


$rootActivity = $rootActivities[0];
//echo $rootActivity.'<br/>';

echo '<div id="column_headers">';
	echo "<div class='headertitle' >Learning Object Name</div>";
	echo "<div class='headersatisfied'>Satisfaction</div>";
	echo "<div class='headercompleted'>Completion</div>";
	echo "<div class='headerattempts'>Attempts</div>";
	//echo "<div class='headersuspended'>Suspended</div>";
echo '</div>';

cloud_displayActivity($rootActivity,0,0);



Display :: display_footer();

function cloud_displayActivity($actNode, $actNum, $leftMargin){
	
	$title = $actNode->title;
	$satisfied = getSatVal($actNode->progressstatus,$actNode->satisfied);
	$completed = getComplVal($actNode->attemptprogressstatus,$actNode->completed);
	$attempts = $actNode->attempts;
	$suspended = $actNode->suspended;
	
	$langMap = array("passed"=>"langLearnpathPassed","failed"=>"langLearnpathFailed","completed"=>"langLearnpathCompstatus","incomplete"=>"langLearnpathIncomplete","unknown"=>"langUnknown");
	
	echo "<div class='activity'>";

	echo "<div class='title' style='margin-left:".$leftMargin."px;'>$title</div>";
	echo "<div class='satisfaction $satisfied'>".get_lang($langMap[$satisfied])."</div>";
	echo "<div class='completion $completed'>".get_lang($langMap[$completed])."</div>";
	echo "<div class='attempts'>$attempts</div>";
	//echo "<div class='suspended'>$suspended</div>";
	//get total time
	echo '<div class="div_detail_arrows" onclick=\'$(this).parent().find("div.activityData").toggle().parent().css("background-color",$(this).parent().find("div.activityData").is(":hidden") ? "#FFFFFF" : "#CEE4F2");'.
		'$("img",this).css("right",$(this).parent().find("div.activityData").is(":hidden") ? "-22px" : "0px");\'>'.
		'<img class="img_detail_arrows" src="img/up_down_arrows.gif" /></div>';
	
	echo "<div class='activityData' >";
	echo "<table class='table_details'><tr><td class='td_objectives'>";
	if ($actNode->objectives){
		cloud_displayObjectives($actNode->objectives);
	}
	echo "</td><td class='td_runtime'>";
	if ($actNode->runtime){
		cloud_displayRuntime($actNode->runtime, $actNum);
	}
	echo "</td></tr></table></div>";
	echo "</div>";

	$newActNum = 0;
	foreach($actNode->children->activity as $childAct){
		$newActNum += 1;
		cloud_displayActivity($childAct,$actNum.$newActNum,$leftMargin + 15);
	}
	
}

function cloud_displayObjectives($objectives){
	
	echo "<div class='actObjectiveData'>";
	
	echo "<div class='detailsTopLabel'>Activity Objectives</div>";
	echo "<table class='table_details'>";
	foreach ($objectives->objective as $obj){
		$id = $obj['id'];
		$measureStat = $obj->measurestatus;
		$normMeasure = $obj->normalizedmeasure;
		$progressstatus = $obj->progressstatus;
		$satisfiedstatus = $obj->satisfiedstatus;
		
		echo "<tr><td><span class='actDetailsPropLbl'>Objective Id: </span></td><td><span class='actDetailsPropVal'>$id</span></td></tr>";
		echo "<tr><td><span class='actDetailsPropLbl margin5'>Measure Status: </span></td><td><span class='actDetailsPropVal'>$measureStat</span></td></tr>";
		echo "<tr><td><span class='actDetailsPropLbl margin5'>Normalized Measure: </span></td><td><span class='actDetailsPropVal'>$normMeasure</span></td></tr>";
		echo "<tr><td><span class='actDetailsPropLbl margin5'>Progress Status: </span></td><td><span class='actDetailsPropVal'>$progressstatus</span></td></tr>";
		echo "<tr><td><span class='actDetailsPropLbl margin5'>Satisfied Status: </span></td><td><span class='actDetailsPropVal'>$satisfiedstatus</span></td></tr>";
		echo "<tr class='tr_space'><td></td><td></td></tr>";
		
	}
	echo "</table>";
	echo '</div>';
	
}
function cloud_displayRuntime($rt,$actNum){
	echo "<div class='actRuntimeData'>";
	
	echo "<table class='table_details'><tr>";
	
	if ($rt->objectives->objective){
		echo "<td class='td_runtimeObjectives'>";
		echo "<div class='detailsTopLabel'>Runtime Objectives</div>";
		echo "<table class='table_details'>";
		foreach ($rt->objectives->objective as $obj){
			
			echo "<tr><td><span class='actDetailsPropLbl'>Objective Id:</span></td><td><span class='actDetailsPropVal'>".$obj['id']."</span></td></tr>";
			echo "<tr><td><span class='actDetailsPropLbl margin5'>Scaled Score:</span></td><td><span class='actDetailsPropVal'>$obj->score_scaled</span></td></tr>";
			echo "<tr><td><span class='actDetailsPropLbl margin5'>Minimum Score:</span></td><td><span class='actDetailsPropVal'>$obj->score_min</span></td></tr>";
			echo "<tr><td><span class='actDetailsPropLbl margin5'>Raw Score:</span></td><td><span class='actDetailsPropVal'>$obj->score_raw</span></td></tr>";
			echo "<tr><td><span class='actDetailsPropLbl margin5'>Maximum Score:</span></td><td><span class='actDetailsPropVal'>$obj->score_max</span></td></tr>";
			echo "<tr><td><span class='actDetailsPropLbl margin5'>Success Status:</span></td><td><span class='actDetailsPropVal'>$obj->success_status</span></td></tr>";
			echo "<tr><td><span class='actDetailsPropLbl margin5'>Completion Status:</span></td><td><span class='actDetailsPropVal'>$obj->completion_status</span></td></tr>";
			echo "<tr><td><span class='actDetailsPropLbl margin5'>Progress Measure:</span></td><td><span class='actDetailsPropVal'>$obj->progress_measure</span></td></tr>";
			echo "<tr><td><span class='actDetailsPropLbl margin5'>Description:</span></td><td><span class='actDetailsPropVal'>$obj->description</span></td></tr>";
			echo "<tr class='tr_space'><td></td><td></td></tr>";
			
		}
		echo "</table><br/>";
		echo "</td>";
		
	}
	
	
	echo "<td class='td_runtimeDetails'>";
	
	echo "<div class='detailsTopLabel'>Activity Runtime Data</div>";
	
	echo "<table class='table_details'>";
	echo "<tr><td><span class='actDetailsPropLbl'>Completion Status: </span></td><td><span class='actDetailsPropVal'>$rt->completion_status</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Credit: </span></td><td><span class='actDetailsPropVal'>$rt->credit</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Entry: </span></td><td><span class='actDetailsPropVal'>$rt->entry</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Exit: </span></td><td><span class='actDetailsPropVal'>$rt->exit</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Learner Preferences: </span></td><td></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl margin5'>Audio Level: </span></td><td><span class='actDetailsPropVal'>$rt->audio_level</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl margin5'>Language: </span></td><td><span class='actDetailsPropVal'>$rt->language</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl margin5'>Delivery Speed: </span></td><td><span class='actDetailsPropVal'>$rt->delivery_speed</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl margin5'>Audio Captioning: </span></td><td><span class='actDetailsPropVal'>$rt->audio_captioning</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Location: </span></td><td><span class='actDetailsPropVal'>$rt->location</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Mode: </span></td><td><span class='actDetailsPropVal'>$rt->mode</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Progress Measure: </span></td><td><span class='actDetailsPropVal'>$rt->progress_measure</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Score Scaled: </span></td><td><span class='actDetailsPropVal'>$rt->score_scaled</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Score Raw: </span></td><td><span class='actDetailsPropVal'>$rt->score_raw</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Score Minimum: </span></td><td><span class='actDetailsPropVal'>$rt->score_min</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Score Maximum: </span></td><td><span class='actDetailsPropVal'>$rt->score_max</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Total Time: </span></td><td><span class='actDetailsPropVal'>$rt->total_time</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Time Tracked: </span></td><td><span class='actDetailsPropVal'>$rt->timetracked</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Success Status: </span></td><td><span class='actDetailsPropVal'>$rt->success_status</span></td></tr>";
	echo "</table>";
	
	echo "</td>";
	
	
	echo "<td>";
	
	echo "<div class='detailsTopLabel'>Suspend Data</div>";
	echo "<div class='actDetailsProp'><span class='actDetailsPropVal'>$rt->suspend_data</span></div>";
	echo "<br/>";
	
	echo "<div class='detailsTopLabel'>Static Runtime Data</div>";
	echo "<table class='table_details'>";
	echo "<tr><td><span class='actDetailsPropLbl'>Completion Threshold:</span></td><td><span class='actDetailsPropVal'>".$rt->static->completion_threshold."</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Launch Data:</span></td><td><span class='actDetailsPropVal'>".$rt->static->launch_data."</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Learner Id:</span></td><td><span class='actDetailsPropVal'>".$rt->static->learner_id."</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Learner Name:</span></td><td><span class='actDetailsPropVal'>".$rt->static->learner_name."</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Maximum Time Allowed:</span></td><td><span class='actDetailsPropVal'>".$rt->static->max_time_allowed."</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Scaled Passing Score:</span></td><td><span class='actDetailsPropVal'>".$rt->static->scaled_passing_score."</span></td></tr>";
	echo "<tr><td><span class='actDetailsPropLbl'>Time Limit Action:</span></td><td><span class='actDetailsPropVal'>".$rt->static->time_limit_action."</span></td></tr>";
	echo "</table><br/>";
	
	if ($rt->interactions->interaction){
		echo '<div class="detailsTopLabel">Interactions<div id="interactionArrowDiv" class="sub_detail_arrows" '.
			'onclick=\'$("#interactionsTable'.$actNum.'").toggle(); $("img",this).css("right",$("#interactionsTable'.$actNum.'").is(":hidden") ? "-16px" : "0px"); \' >'.
			'<img id="interaction_arrows" class="img_detail_arrows" src="img/up_down_arrows_sm.gif" />'.
			'</div></div>';
		echo "<table id='interactionsTable$actNum' class='interactionsTable table_details'>";
		foreach ($rt->interactions->interaction as $int){
			
			
			echo "<tr><td class='intLblWidth'><span class='actDetailsPropLbl'>Interaction Id:</span></td><td><span class='actDetailsPropVal'>".$int['id']."</span></td></tr>";
			echo "<tr><td><span class='actDetailsPropLbl margin5'>Type:</span></td><td><span class='actDetailsPropVal'>$int->type</span></td></tr>";
			echo "<tr><td><span class='actDetailsPropLbl margin5'>Timestamp:</span></td><td><span class='actDetailsPropVal'>$int->timestamp</span></td></tr>";
			echo "<tr><td colspan='2'><span class='actDetailsPropLbl margin5'>Objectives:</span></td><td></td></tr>";
			foreach ($int->objectives->objective as $intObj){
				echo "<tr><td><span class='actDetailsPropLbl margin20'>Objective Id:</span></td><td><span class='actDetailsPropVal'>".$intObj['id']."</span></td></tr>";
			}
			
			echo "<tr><td colspan='2'><span class='actDetailsPropLbl margin5'>Correct Responses:</span></td></tr>";
			foreach ($int->correct_responses->response as $intResp){
				echo "<tr><td><span class='actDetailsPropLbl margin20'>Response Id:</span></td><td><span class='actDetailsPropVal'>".$intResp['id']."</span></td></tr>";
			}
			echo "<tr><td><span class='actDetailsPropLbl margin5'>Weighting:</span></td><td><span class='actDetailsPropVal'>$int->weighting</span></td></tr>";
			echo "<tr><td><span class='actDetailsPropLbl margin5'>Learner Response:</span></td><td><span class='actDetailsPropVal'>$int->learner_response</span></td></tr>";
			echo "<tr><td><span class='actDetailsPropLbl margin5'>Result:</span></td><td><span class='actDetailsPropVal'>$int->result</span></td></tr>";
			echo "<tr><td><span class='actDetailsPropLbl margin5'>Latency:</span></td><td><span class='actDetailsPropVal'>$int->latency</span></td></tr>";
			echo "<tr><td><span class='actDetailsPropLbl margin5'>Description:</span></td><td><span class='actDetailsPropVal'>$int->description</span></td></tr>";
			echo "<tr class='tr_space'><td colspan='2'></td></tr>";
			echo "<tr><td class='dotted' colspan='2'></td></tr>";
			echo "<tr class='tr_space'><td colspan='2'></td></tr>";
		}
		echo "</table><br/>";
	}
	
	
	
	//echo "<br/>";
	if ($rt->comments_from_learner->comment){
		echo '<div class="detailsTopLabel">Comments From Learner<div id="learnerCommentArrowDiv" class="sub_detail_arrows comment_arrow" '.
			'onclick=\'$("#learnerComments'.$actNum.'").toggle(); $("img",this).css("right",$("#learnerComments'.$actNum.'").is(":hidden") ? "-16px" : "0px");\' >'.
		
			'<img id="learnerCommentArrows" class="img_detail_arrows" src="img/up_down_arrows_sm.gif" />'.
			'</div></div>';
		echo "<div id='learnerComments$actNum' class='learnerComments'>";
		foreach ($rt->comments_from_learner->comment as $com){
			
			echo "<div class='commentDetail'><span class='actDetailsPropLbl bold'>Date: </span><span class='actDetailsPropVal'>$com->date_time</span></div>";
			echo "<div class='commentDetail'><span class='actDetailsPropLbl bold'>Location: </span><span class='actDetailsPropVal'>$com->location</span></div>";
			echo "<div class='commentDetail'><span class='actDetailsPropLbl bold'>Comment: </span><span class='actDetailsPropVal'>$com->value</span></div>";
			echo "<br/>";
		}
		echo "</div><br/>";
	}
	
	//echo "<br/>";
	if ($rt->comments_from_lms->comment){
		echo '<div class="detailsTopLabel">Comments From LMS<div id="learnerCommentArrowDiv" class="sub_detail_arrows comment_arrow" '.
			'onclick=\'$("#lmsComments'.$actNum.'").toggle(); $("img",this).css("right",$("#lmsComments'.$actNum.'").is(":hidden") ? "-16px" : "0px");\' >'.
		
			'<img id="lmsCommentArrows" class="img_detail_arrows" src="img/up_down_arrows_sm.gif" />'.
			'</div></div>';
		echo "<div id='lmsComments$actNum' class='lmsComments'>";
		foreach ($rt->comments_from_lms->comment as $com){
			
			echo "<div class='commentDetail'><span class='actDetailsPropLbl bold'>Date: </span><span class='actDetailsPropVal'>$com->date_time</span></div>";
			echo "<div class='commentDetail'><span class='actDetailsPropLbl bold'>Location: </span><span class='actDetailsPropVal'>$com->location</span></div>";
			echo "<div class='commentDetail'><span class='actDetailsPropLbl bold'>Comment: </span><span class='actDetailsPropVal'>$com->value</span></div>";
			echo "<br/>";
		}
		echo "</div><br/>";
	}
	
	echo "</td>";
	echo "</tr></table>";
	
	
	
	
	echo '</div>';
	
	
}


function getSatVal($satStat,$satVal){
	if ($satStat == 'true'){
		if ($satVal == 'true'){
			return "passed";
		} else {
			return "failed";
		}
		
	} else {
		return "unknown";
	}
}

function getComplVal($comStat,$comVal){
	if ($comStat == 'true'){
		if ($comVal == 'true'){
			return "completed";
		} else {
			return "incomplete";
		}
		
	} else {
		return "unknown";
	}	
}


?>
