

--
-- Dumping data for table settings_current
--
/*!40000 ALTER TABLE settings_current DISABLE KEYS */;
LOCK TABLES settings_current WRITE;
INSERT INTO settings_current 
(variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable)
VALUES
('enableScormCloud','enableScormCloud','checkbox','cloud','false','cloudEnableTitle',NULL,NULL,'cloudEnableComment', 1),
('enableCloudCourseSharing','existingCloudCourseUse','checkbox','cloud','false','cloudCourseSharingTitle','allowExistingExplanation',NULL,'allowExistingCloudCourseUseComment', 1),
('scormCloudCredsId','appId','textfield','cloud','','cloudAppIdTitle','cloudAppIdComment',NULL,NULL, 1),
('scormCloudCredsPw','appPw','textfield','cloud','','','cloudAppPwComment',NULL,NULL, 1),
('scormCloudCredsUrl','appUrl','textfield','cloud','','','cloudAppUrlComment',NULL,NULL, 1);
UNLOCK TABLES;
/*!40000 ALTER TABLE settings_current ENABLE KEYS */;


/**/

--DROP TABLE IF EXISTS scorm_cloud;
CREATE TABLE scorm_cloud (
  course_code varchar(40) NOT NULL,
  lp_id int NOT NULL,
  cloud_course_id varchar(40) NOT NULL);
  
  