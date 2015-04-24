
-- SQL TO CREATE A REDCAP DEMO PROJECT --
set @project_title = 'Piping Example Project';
-- Obtain default values --
set @institution = (select value from redcap_config where field_name = 'institution' limit 1);
set @site_org_type = (select value from redcap_config where field_name = 'site_org_type' limit 1);
set @grant_cite = (select value from redcap_config where field_name = 'grant_cite' limit 1);
set @project_contact_name = (select value from redcap_config where field_name = 'project_contact_name' limit 1);
set @project_contact_email = (select value from redcap_config where field_name = 'project_contact_email' limit 1);
set @project_contact_prod_changes_name = (select value from redcap_config where field_name = 'project_contact_prod_changes_name' limit 1);
set @project_contact_prod_changes_email = (select value from redcap_config where field_name = 'project_contact_prod_changes_email' limit 1);
set @headerlogo = (select value from redcap_config where field_name = 'headerlogo' limit 1);
set @auth_meth = (select value from redcap_config where field_name = 'auth_meth_global' limit 1);
-- Create project --
INSERT INTO `redcap_projects` 
(project_name, app_title, status, count_project, auth_meth, creation_time, production_time, institution, site_org_type, grant_cite, project_contact_name, project_contact_email, project_contact_prod_changes_name, project_contact_prod_changes_email, headerlogo, surveys_enabled, auto_inc_set, display_project_logo_institution) VALUES
(concat('redcap_demo_',LEFT(MD5(RAND()),6)), @project_title, 1, 0, @auth_meth, now(), now(), @institution, @site_org_type, @grant_cite, @project_contact_name, @project_contact_email, @project_contact_prod_changes_name, @project_contact_prod_changes_email, @headerlogo, 1, 1, 0);
set @project_id = LAST_INSERT_ID();
-- User rights --
INSERT INTO `redcap_user_rights` (`project_id`, `username`, `expiration`, `group_id`, `lock_record`, `data_export_tool`, `data_import_tool`, 
`data_comparison_tool`, `data_logging`, `file_repository`, `double_data`, `user_rights`, `data_access_groups`, `graphical`, `reports`, `design`, 
`calendar`, `data_entry`, `participants`, `data_quality_execute`) VALUES
(@project_id, 'site_admin', NULL, NULL, 0, 1, 0, 1, 1, 1, 0, 0, 0, 1, 1, 0, 1, '', 0, 1);
-- Create single arm --
INSERT INTO redcap_events_arms (project_id, arm_num, arm_name) VALUES (@project_id, 1, 'Arm 1');
set @arm_id = LAST_INSERT_ID();
-- Create single event --
INSERT INTO redcap_events_metadata (arm_id, day_offset, offset_min, offset_max, descrip) VALUES (@arm_id, 0, 0, 0, 'Event 1');
set @event_id = LAST_INSERT_ID();
-- Insert into redcap_surveys
INSERT INTO redcap_surveys (project_id, form_name, title, instructions, acknowledgement, question_by_section, question_auto_numbering, survey_enabled, save_and_return, logo, hide_title) VALUES
(@project_id, 'survey', 'Example Survey to Demonstrate Piping', 'This survey will demonstrate some basic examples of the Piping feature in REDCap.', '&lt;p style="font-size:14px;"&gt;&lt;strong&gt;[first_name], thank you for taking the survey.&lt;/strong&gt;&lt;/p&gt;<br>&lt;p&gt;Have a nice day!&lt;/p&gt;', 1, 0, 1, 0, NULL, 0);
-- Insert into redcap_metadata --
INSERT INTO `redcap_metadata` (`project_id`, `field_name`, `field_phi`, `form_name`, `form_menu_description`, `field_order`, `field_units`, `element_preceding_header`, `element_type`, `element_label`, `element_enum`, `element_note`, `element_validation_type`, `element_validation_min`, `element_validation_max`, `element_validation_checktype`, `branching_logic`, `field_req`, `edoc_id`, `edoc_display_img`, `custom_alignment`, `stop_actions`, `question_num`, `grid_name`, `misc`) VALUES
(@project_id, 'participant_id', NULL, 'survey', 'Example Survey', 1, NULL, NULL, 'text', 'Participant ID', NULL, NULL, NULL, NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'first_name', NULL, 'survey', NULL, 2, NULL, 'Section 1', 'text', 'Your first name', NULL, NULL, NULL, NULL, NULL, 'soft_typed', NULL, 1, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'last_name', NULL, 'survey', NULL, 3, NULL, NULL, 'text', 'Your last name', NULL, NULL, NULL, NULL, NULL, 'soft_typed', NULL, 1, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'date_today', NULL, 'survey', NULL, 4, NULL, NULL, 'text', '[first_name], please enter today''s date?', NULL, NULL, 'date_mdy', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'ice_cream', NULL, 'survey', NULL, 5, NULL, NULL, 'radio', 'What is your favorite ice cream?', '1, Chocolate \\n 2, Vanilla \\n 3, Strawberry', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'slider', NULL, 'survey', NULL, 6, NULL, 'Section 2', 'slider', 'How much do you like [ice_cream] ice cream?', 'Hate it | Indifferent | I love [ice_cream]!', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'number', NULL, 'survey', NULL, 7, NULL, NULL, 'text', 'Enter your favorite number', NULL, NULL, 'int', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'calc', NULL, 'survey', NULL, 8, NULL, NULL, 'calc', 'Your favorite number above multiplied by 4 is:', '[number]*4', '[number] x 4 = [calc]', NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'confirm_name', NULL, 'survey', NULL, 9, NULL, NULL, 'radio', 'Please confirm your name', '0, [first_name] Harris \\n 1, [first_name] [last_name] \\n 2, [first_name] Taylor \\n 3, [first_name] deGrasse Tyson', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'confirm_name_error', NULL, 'survey', NULL, 10, NULL, NULL, 'descriptive', '<div class="red" style="padding:30px;"><b>ERROR:</b> Please try again!</div>', NULL, NULL, NULL, NULL, NULL, NULL, '[confirm_name] != '''' and [confirm_name] != ''1''', 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'review_answers', NULL, 'survey', NULL, 11, NULL, 'Review answers', 'descriptive', 'Review your answers below:\n\n<div style="font-size:14px;color:red;">Date: [date_today]\nName: [first_name] [last_name]\nFavorite ice cream: [ice_cream]\nFavorite number multiplied by 4: [calc]</div>\n\nIf all your responses look correct and you did not leave any blank, then click the Submit button below.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'survey_complete', NULL, 'survey', NULL, 12, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL);

INSERT INTO `redcap_projects_templates` (`project_id`, `title`, `description`, `enabled`) 
	VALUES (@project_id,  @project_title,  'Contains a single data collection instrument enabled as a survey, which contains questions to demonstrate the Piping feature.',  '1');