<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//This file describes the module, including database tables

//Basic variables
$name = 'Enrichment and Flow';
$description = 'This module allows schools to implement ICHK\'s Enrichment and Flow (ENF) programme, with functionality to create, track and issue credits. Students undertake learning opportunities to earn credits, producing an evidenced portfolio within Gibbon. ';
$entryURL = 'planner.php';
$type = 'Additional';
$category = 'Learn';
$version = '1.4.02';
$author = "Gibbon Foundation";
$url = "https://gibbonedu.org";

//Module tables
$moduleTables[] = "CREATE TABLE `enfDomain` (
  `enfDomainID` int(3) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `sequenceNumber` int(3) NOT NULL,
  `backgroundColour` varchar(6) NOT NULL DEFAULT '',
  `accentColour` varchar(6) NOT NULL DEFAULT '',
  `logo` varchar(255) NOT NULL DEFAULT '',
  `creditLicensing` text NOT NULL,
  PRIMARY KEY (`enfDomainID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;";

$moduleTables[] = "CREATE TABLE `enfCredit` (
  `enfCreditID` int(4) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `enfDomainID` int(3) unsigned zerofill NOT NULL,
  `name` varchar(50) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `outcomes` text NOT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `logo` varchar(255) NOT NULL DEFAULT '',
  `creditLicensing` text NOT NULL,
  PRIMARY KEY (`enfCreditID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;";

$moduleTables[] = "CREATE TABLE `enfCreditMentor` (
  `enfCreditMentorID` int(6) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `enfCreditID` int(4) unsigned zerofill NOT NULL,
  `gibbonPersonID` int(10) unsigned zerofill NOT NULL,
  PRIMARY KEY (`enfCreditMentorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;";

$moduleTables[] = "CREATE TABLE `enfOpportunity` (
  `enfOpportunityID` int(4) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `outcomes` text NOT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `logo` varchar(255) NOT NULL DEFAULT '',
  `gibbonYearGroupIDList` varchar(255) NOT NULL DEFAULT '',
  `creditLicensing` text NOT NULL,
  PRIMARY KEY (`enfOpportunityID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;";

$moduleTables[] = "CREATE TABLE `enfOpportunityMentor` (
  `enfOpportunityMentorID` int(6) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `enfOpportunityID` int(4) unsigned zerofill NOT NULL,
  `gibbonPersonID` int(10) unsigned zerofill NOT NULL,
  PRIMARY KEY (`enfOpportunityMentorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;";

$moduleTables[] = "CREATE TABLE `enfOpportunityCredit` (
  `enfOpportunityCreditID` int(6) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `enfOpportunityID` int(4) unsigned zerofill NOT NULL,
  `enfCreditID` int(4) unsigned zerofill NOT NULL,
  PRIMARY KEY (`enfOpportunityCreditID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;";

$moduleTables[] = "CREATE TABLE `enfJourney` (
`enfJourneyID` int(12) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `gibbonPersonIDStudent` int(10) unsigned zerofill NULL DEFAULT NULL,
  `gibbonSchoolYearID` INT(3) UNSIGNED ZEROFILL NULL DEFAULT NULL,
  `type` enum('Credit','Opportunity') NOT NULL DEFAULT 'Credit',
  `enfOpportunityID` int(4) unsigned zerofill NULL DEFAULT NULL,
  `enfCreditID` int(4) unsigned zerofill NULL DEFAULT NULL,
  `gibbonPersonIDSchoolMentor` int(10) unsigned zerofill NULL DEFAULT NULL,
  `statusKey` varchar(20) DEFAULT NULL,
  `status` enum('Current','Current - Pending','Complete - Pending','Complete - Approved','Exempt','Evidence Not Yet Approved') NOT NULL DEFAULT 'Current',
  `timestampJoined` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `timestampCompletePending` timestamp NULL DEFAULT NULL,
  `timestampCompleteApproved` timestamp NULL DEFAULT NULL,
  `gibbonPersonIDApproval` int(10) unsigned zerofill NULL DEFAULT NULL,
  `evidenceType` enum('File','Link') NULL DEFAULT NULL,
  `evidenceLocation` text NULL DEFAULT NULL,
  PRIMARY KEY (`enfJourneyID`),
  INDEX(`gibbonPersonIDStudent`),
  INDEX(`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;";

$moduleTables[] = "CREATE TABLE `enfPlannerEntry` ( 
    `enfPlannerEntryID` INT(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT , 
    `gibbonPersonID` INT(10) UNSIGNED ZEROFILL NOT NULL , 
    `date` DATE NOT NULL , `tasks` TEXT NULL , 
    PRIMARY KEY (`enfPlannerEntryID`), 
    UNIQUE KEY `entry` (`gibbonPersonID`, `date`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;";

$moduleTables[] = "CREATE TABLE `enfAnnouncement` ( 
    `enfAnnouncementID` INT(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT , 
    `date` DATE NOT NULL , `content` TEXT NOT NULL , 
    `gibbonPersonIDCreated` INT(10) UNSIGNED ZEROFILL NULL , 
    `gibbonPersonIDModified` INT(10) UNSIGNED ZEROFILL NULL , 
    PRIMARY KEY (`enfAnnouncementID`), UNIQUE KEY `date` (`date`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;";

$moduleTables[] = "CREATE TABLE `enfPlannerTask` ( 
    `enfPlannerTaskID` INT(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT , 
    `enfPlannerEntryID` INT(12) UNSIGNED ZEROFILL NULL , 
    `category` VARCHAR(60) NOT NULL , 
    `minutes` INT(3) NOT NULL , 
    `description` VARCHAR(120) NOT NULL , 
    `sequenceNumber` INT(3) NOT NULL , 
    `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , 
    PRIMARY KEY (`enfPlannerTaskID`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;";


$moduleTables[] = "CREATE TABLE `enfBlock` ( 
    `enfBlockID` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
    `gibbonCourseID` INT UNSIGNED NOT NULL,
    `name` VARCHAR(60) NOT NULL,
    `gibbonDaysOfWeekID` INT UNSIGNED ZEROFILL NOT NULL,
    `timeStart` TIME NOT NULL,
    `timeEnd` TIME NOT NULL,
    `signUpSameDay` ENUM('Y','N') NOT NULL DEFAULT 'Y',
    `signUpStart` TIME NULL,
    PRIMARY KEY (`enfBlockID`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;";

$moduleTables[] = "CREATE TABLE `enfBlockFacility` ( 
    `enfBlockFacilityID` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
    `enfBlockID` INT UNSIGNED NOT NULL,
    `gibbonSpaceID` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`enfBlockFacilityID`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;";

$moduleTables[] = "CREATE TABLE `enfBlockDate` ( 
    `enfBlockDateID` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
    `enfBlockID` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    PRIMARY KEY (`enfBlockDateID`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;";

$moduleTables[] = "CREATE TABLE `enfPlannedSession` ( 
    `enfPlannedSessionID` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
    `enfBlockID` INT UNSIGNED NOT NULL,
    `enfSessionID` INT UNSIGNED NOT NULL,
    `enfBlockFacilityID` INT UNSIGNED NULL,
    `gibbonSpaceID` INT UNSIGNED NULL,
    `notes` TEXT NULL,
    `gibbonPersonIDCreated` INT NOT NULL,
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    PRIMARY KEY (`enfPlannedSessionID`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;";

$moduleTables[] = "CREATE TABLE `enfPlannedSessionTeacher` ( 
    `enfPlannedSessionTeacherID` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
    `enfPlannedSessionID` INT UNSIGNED NOT NULL,
    `gibbonPersonID` INT UNSIGNED NOT NULL,
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    PRIMARY KEY (`enfPlannedSessionTeacherID`),
    UNIQUE KEY (`enfPlannedSessionID`, `gibbonPersonID`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;";

$moduleTables[] = "CREATE TABLE `enfSession` ( 
    `enfSessionID` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
    `type` VARCHAR(120) NOT NULL,
    `focus` VARCHAR(120) NOT NULL,
    `description` TEXT NULL,
    `maxStudents` SMALLINT NULL,
    `gibbonPersonIDCreated` INT NOT NULL,
    `gibbonPersonIDModified` INT NOT NULL,
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , 
    `timestampModified` TIMESTAMP NOT NULL, 
    PRIMARY KEY (`enfSessionID`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;";

$moduleTables[] = "CREATE TABLE `enfSessionStudent` ( 
    `enfSessionStudentID` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
    `enfPlannedSessionID` INT UNSIGNED NOT NULL,
    `enfSessionID` INT UNSIGNED NOT NULL,
    `enfBlockID` INT UNSIGNED NOT NULL,
    `gibbonPersonID` INT UNSIGNED NOT NULL,
    `gibbonSpaceID` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `timeStart` TIME NOT NULL,
    `timeEnd` TIME NOT NULL,
    `block` VARCHAR(60) NOT NULL,
    `type` VARCHAR(120) NOT NULL,
    `focus` VARCHAR(120) NOT NULL,
    `comment` TEXT NULL,
    `status` VARCHAR(60) NOT NULL DEFAULT 'Present',
    `locked` ENUM('Y','N') NOT NULL DEFAULT 'Y',
    `gibbonPersonIDCreated` INT NOT NULL,
    `gibbonPersonIDModified` INT NOT NULL,
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    `timestampModified` TIMESTAMP NOT NULL, 
    PRIMARY KEY (`enfSessionStudentID`),
    UNIQUE KEY (`enfBlockID`, `gibbonPersonID`, `date`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;";


//Settings - none
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES ('Enrichment and Flow', 'indexText', 'Index Text', 'Welcome text for users arriving in the module.', '')";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`scope`, `name`, `nameDisplay`, `description`, `value`) VALUES ('Enrichment and Flow', 'taskCategories', 'Planner Task Categories', 'Available category names and colours used for selecting and displaying planner tasks.', '[{\"category\":\"Homework\",\"0\":\"#fdba74\",\"color\":\"#fdba74\"},{\"category\":\"Studying\",\"0\":\"#5eead4\",\"color\":\"#5eead4\"},{\"category\":\"Sports\",\"0\":\"#7dd3fc\",\"color\":\"#7dd3fc\"},{\"category\":\"Exercise\",\"0\":\"#a5b4fc\",\"color\":\"#a5b4fc\"},{\"category\":\"Games\",\"0\":\"#f9a8d4\",\"color\":\"#f9a8d4\"},{\"category\":\"Music\",\"0\":\"#ffa1b5\",\"color\":\"#ffa1b5\"},{\"category\":\"Reading\",\"0\":\"#c4b5fd\",\"color\":\"#c4b5fd\"},{\"category\":\"Personal Project\",\"0\":\"#d8b4fe\",\"color\":\"#d8b4fe\"},{\"category\":\"Other\",\"0\":\"#d1d5db\",\"color\":\"#d1d5db\"}]');";

//Action rows
$actionRows[] = [
    'name'                      => 'Manage Domains',
    'precedence'                => '0',
    'category'                  => 'Manage',
    'description'               => 'Manage the domains in which credits can be situated.',
    'URLList'                   => 'domains_manage.php,domains_manage_add.php,domains_manage_edit.php,domains_manage_delete.php',
    'entryURL'                  => 'domains_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Manage Credits',
    'precedence'                => '0',
    'category'                  => 'Manage',
    'description'               => 'Manage the credits towards which students come work.',
    'URLList'                   => 'credits_manage.php,credits_manage_add.php,credits_manage_edit.php,credits_manage_delete.php',
    'entryURL'                  => 'credits_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Manage Opportunities',
    'precedence'                => '0',
    'category'                  => 'Manage',
    'description'               => 'Manage the learing opportunities that students can undertake.',
    'URLList'                   => 'opportunities_manage.php,opportunities_manage_add.php,opportunities_manage_edit.php,opportunities_manage_delete.php',
    'entryURL'                  => 'opportunities_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Settings',
    'precedence'                => '0',
    'category'                  => 'Manage',
    'description'               => 'Control settings that adjust the way the module works.',
    'URLList'                   => 'settings.php',
    'entryURL'                  => 'settings.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Browse Credits',
    'precedence'                => '0',
    'category'                  => 'Journey',
    'description'               => 'Allows users to view a grid of available credits.',
    'URLList'                   => 'credits.php,credits_detail.php',
    'entryURL'                  => 'credits.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'Y',
    'defaultPermissionParent'   => 'Y',
    'defaultPermissionSupport'  => 'Y',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'Y',
    'categoryPermissionParent'  => 'Y',
    'categoryPermissionOther'   => 'Y',
];

$actionRows[] = [
    'name'                      => 'Browse Opportunities',
    'precedence'                => '0',
    'category'                  => 'Journey',
    'description'               => 'Allows users to view a grid of available learning opportunties.',
    'URLList'                   => 'opportunities.php,opportunities_detail.php',
    'entryURL'                  => 'opportunities.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'Y',
    'defaultPermissionParent'   => 'Y',
    'defaultPermissionSupport'  => 'Y',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'Y',
    'categoryPermissionParent'  => 'Y',
    'categoryPermissionOther'   => 'Y',
];

$actionRows[] = [
    'name'                      => 'Credits & Licensing',
    'precedence'                => '0',
    'category'                  => 'Other',
    'description'               => 'Allows a user to view image credits for licensed images.',
    'URLList'                   => 'logo_credits.php',
    'entryURL'                  => 'logo_credits.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'Y',
    'defaultPermissionParent'   => 'Y',
    'defaultPermissionSupport'  => 'Y',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'Y',
    'categoryPermissionParent'  => 'Y',
    'categoryPermissionOther'   => 'Y',
];

$actionRows[] = [
    'name'                      => 'Record Journey',
    'precedence'                => '0',
    'category'                  => 'Journey',
    'description'               => 'Allows a student to record steps in their Enrichment and Flow journey.',
    'URLList'                   => 'journey_record.php,journey_record_add.php,journey_record_edit.php,journey_record_delete.php',
    'entryURL'                  => 'journey_record.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'N',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'Y',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'N',
    'categoryPermissionStudent' => 'Y',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Manage Journey_all',
    'precedence'                => '1',
    'category'                  => 'Journey',
    'description'               => 'Allows a member of staff to interact with all student journey records.',
    'URLList'                   => 'journey_manage.php,journey_manage_edit.php,journey_manage_delete.php,journey_manage_commit.php',
    'entryURL'                  => 'journey_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Manage Journey_my',
    'precedence'                => '0',
    'category'                  => 'Journey',
    'description'               => 'Allows a member of staff to interact with journey records of students they mentor.',
    'URLList'                   => 'journey_manage.php,journey_manage_edit.php,journey_manage_delete.php,journey_manage_commit.php',
    'entryURL'                  => 'journey_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'N',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Evidence Pending Approval_all',
    'precedence'                => '1',
    'category'                  => 'Reports',
    'description'               => 'Allows a user to see all evidence awaiting feedback.',
    'URLList'                   => 'report_evidencePendingApproval.php',
    'entryURL'                  => 'report_evidencePendingApproval.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Evidence Pending Approval_my',
    'precedence'                => '0',
    'category'                  => 'Reports',
    'description'               => 'Allows a user to see evidence awaiting their feedback.',
    'URLList'                   => 'report_evidencePendingApproval.php',
    'entryURL'                  => 'report_evidencePendingApproval.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'N',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Planner Overview',
    'precedence'                => '1',
    'category'                  => 'Flow',
    'description'               => 'An ENF teacher dashboard view of daily plans and recent activity.',
    'URLList'                   => 'planner.php,planner_view.php',
    'entryURL'                  => 'planner.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Plan & Log',
    'precedence'                => '0',
    'category'                  => 'Flow',
    'description'               => 'An ENF student dashboard view of daily plans and recent activity.',
    'URLList'                   => 'planner.php',
    'entryURL'                  => 'planner.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'N',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'Y',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'N',
    'categoryPermissionStudent' => 'Y',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Manage Announcements',
    'precedence'                => '0',
    'category'                  => 'Manage',
    'description'               => 'Manage announcements by date.',
    'URLList'                   => 'announcements_manage.php,announcements_manage_add.php,announcements_manage_edit.php,announcements_manage_delete.php',
    'entryURL'                  => 'announcements_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Manage Blocks',
    'precedence'                => '0',
    'category'                  => 'Sessions',
    'description'               => 'Manage blocks for planning sessions',
    'URLList'                   => 'blocks_manage.php,blocks_manage_addEdit.php,blocks_manage_delete.php',
    'entryURL'                  => 'blocks_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Manage Sessions',
    'precedence'                => '0',
    'category'                  => 'Sessions',
    'description'               => 'Manage sessions available for student signup',
    'URLList'                   => 'sessions_manage.php,sessions_manage_addEdit.php,sessions_manage_delete.php',
    'entryURL'                  => 'sessions_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'My Sessions',
    'precedence'                => '0',
    'category'                  => 'Flow',
    'description'               => 'View and plan sessions',
    'URLList'                   => 'sessions_my.php,sessions_my_addEdit.php,sessions_my_join.php,sessions_my_delete.php',
    'entryURL'                  => 'sessions_my.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'All Sessions_view',
    'precedence'                => '0',
    'category'                  => 'Flow',
    'description'               => 'View all planned sessions',
    'URLList'                   => 'sessions_view.php',
    'entryURL'                  => 'sessions_view.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'All Sessions_manage',
    'precedence'                => '1',
    'category'                  => 'Flow',
    'description'               => 'View and manage all planned sessions',
    'URLList'                   => 'sessions_view.php,sessions_view_addEdit.php, sessions_view_addEditStudent.php',
    'entryURL'                  => 'sessions_view.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'My Planner',
    'precedence'                => '0',
    'category'                  => 'Flow',
    'description'               => 'An overview of ENF plans for a given student.',
    'URLList'                   => 'planner_view.php',
    'entryURL'                  => 'planner_view.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'N',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'Y',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'N',
    'categoryPermissionStudent' => 'Y',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];
