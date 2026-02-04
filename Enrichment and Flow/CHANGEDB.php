<?php
//USE ;end TO SEPERATE SQL STATEMENTS. DON'T USE ;end IN ANY OTHER PLACES!

$sql = array();
$count = 0;

//v1.0.00
$sql[$count][0] = '1.0.00';
$sql[$count][1] = '-- First version, nothing to update. Based on Gibbon\'s Mastery Transcipt module, v1.4.07';

//v1.1.00
++$count;
$sql[$count][0] = '1.1.00';
$sql[$count][1] = "
UPDATE `gibbonModule` SET entryURL='planner.php' WHERE name='Enrichment and Flow';end
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Enrichment and Flow'), 'Planner Overview', 1, 'Flow', 'An ENF teacher dashboard view of daily plans and recent activity.', 'planner.php,planner_view.php','planner.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Enrichment and Flow' AND gibbonAction.name='Planner Overview'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '2', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Enrichment and Flow' AND gibbonAction.name='Planner Overview'));end
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Enrichment and Flow'), 'Plan & Log', 0, 'Flow', 'An ENF student dashboard view of daily plans and recent activity.', 'planner.php,planner_view.php','planner.php', 'Y', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '3', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Enrichment and Flow' AND gibbonAction.name='Plan & Log'));end
CREATE TABLE `enfPlannerEntry` ( `enfPlannerEntryID` INT(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT , `gibbonPersonID` INT(10) UNSIGNED ZEROFILL NOT NULL , `date` DATE NOT NULL , `tasks` TEXT NULL , PRIMARY KEY (`enfPlannerEntryID`), UNIQUE KEY `entry` (`gibbonPersonID`, `date`)) ENGINE = InnoDB;end
CREATE TABLE `enfAnnouncement` ( `enfAnnouncementID` INT(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT , `date` DATE NOT NULL , `content` TEXT NOT NULL , `gibbonPersonIDCreated` INT(10) UNSIGNED ZEROFILL NULL , `gibbonPersonIDModified` INT(10) UNSIGNED ZEROFILL NULL , PRIMARY KEY (`enfAnnouncementID`), UNIQUE KEY `date` (`date`)) ENGINE = InnoDB;end 
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Enrichment and Flow'), 'Manage Announcements', 0, 'Manage', 'Manage announcements by date.', 'announcements_manage.php,announcements_manage_add.php,announcements_manage_edit.php,announcements_manage_delete.php','announcements_manage.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Enrichment and Flow' AND gibbonAction.name='Manage Announcements'));end
INSERT INTO `gibbonSetting` (`scope`, `name`, `nameDisplay`, `description`, `value`) VALUES ('Enrichment and Flow', 'taskCategories', 'Planner Task Categories', 'Available category names and colours used for selecting and displaying planner tasks.', '[{\"category\":\"Homework\",\"0\":\"#fdba74\",\"color\":\"#fdba74\"},{\"category\":\"Studying\",\"0\":\"#5eead4\",\"color\":\"#5eead4\"},{\"category\":\"Sports\",\"0\":\"#7dd3fc\",\"color\":\"#7dd3fc\"},{\"category\":\"Exercise\",\"0\":\"#a5b4fc\",\"color\":\"#a5b4fc\"},{\"category\":\"Games\",\"0\":\"#f9a8d4\",\"color\":\"#f9a8d4\"},{\"category\":\"Music\",\"0\":\"#ffa1b5\",\"color\":\"#ffa1b5\"},{\"category\":\"Reading\",\"0\":\"#c4b5fd\",\"color\":\"#c4b5fd\"},{\"category\":\"Personal Project\",\"0\":\"#d8b4fe\",\"color\":\"#d8b4fe\"},{\"category\":\"Other\",\"0\":\"#d1d5db\",\"color\":\"#d1d5db\"}]');end
CREATE TABLE `enfPlannerTask` ( `enfPlannerTaskID` INT(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT , `enfPlannerEntryID` INT(12) UNSIGNED ZEROFILL NULL , `category` VARCHAR(60) NOT NULL , `minutes` INT(3) NOT NULL , `description` VARCHAR(120) NOT NULL , `sequenceNumber` INT(3) NOT NULL , `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , PRIMARY KEY (`enfPlannerTaskID`)) ENGINE = InnoDB;end
";

//v1.1.01
++$count;
$sql[$count][0] = '1.1.01';
$sql[$count][1] = "
";

//v1.2.00
++$count;
$sql[$count][0] = '1.2.00';
$sql[$count][1] = "
UPDATE gibbonModule SET author='Gibbon Foundation', url='https://gibbonedu.org' WHERE name='Enrichment and Flow';end
";

//v1.2.01
++$count;
$sql[$count][0] = '1.2.01';
$sql[$count][1] = "";

//v1.2.02
++$count;
$sql[$count][0] = '1.2.02';
$sql[$count][1] = "";

//v1.3.00
++$count;
$sql[$count][0] = '1.3.00';
$sql[$count][1] = "";

//v1.4.00
++$count;
$sql[$count][0] = '1.4.00';
$sql[$count][1] = "
CREATE TABLE `enfBlock` ( 
    `enfBlockID` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
    `name` VARCHAR(60) NOT NULL,
    `gibbonDaysOfWeekID` INT UNSIGNED NOT NULL,
    `timeStart` TIME NOT NULL,
    `timeEnd` TIME NOT NULL,
    `signUpSameDay` ENUM('Y','N') NOT NULL DEFAULT 'Y',
    `signUpStart` TIME NULL,
    PRIMARY KEY (`enfBlockID`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;end
CREATE TABLE `enfBlockFacility` ( 
    `enfBlockFacilityID` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
    `gibbonSpaceID` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`enfBlockFacilityID`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;end
CREATE TABLE `enfBlockDate` ( 
    `enfBlockDateID` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
    `enfBlockID` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    PRIMARY KEY (`enfBlockDateID`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;end
CREATE TABLE `enfPlannedSession` ( 
    `enfPlannedSessionID` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
    `enfBlockID` INT UNSIGNED NOT NULL,
    `enfSessionID` INT UNSIGNED NOT NULL,
    `enfBlockFacilityID` INT UNSIGNED NOT NULL,
    `gibbonPersonIDCreated` INT NOT NULL,
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    PRIMARY KEY (`enfPlannedSessionID`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;end
CREATE TABLE `enfPlannedSessionTeacher` ( 
    `enfPlannedSessionTeacherID` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
    `enfPlannedSessionID` INT UNSIGNED NOT NULL,
    `gibbonPersonID` INT UNSIGNED NOT NULL,
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    PRIMARY KEY (`enfPlannedSessionTeacherID`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;end
CREATE TABLE `enfSession` ( 
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
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;end
CREATE TABLE `enfSessionStudent` ( 
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
    `session` VARCHAR(120) NOT NULL,
    `comment` TEXT NULL,
    `gibbonPersonIDCreated` INT NOT NULL,
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    PRIMARY KEY (`enfSessionStudentID`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;end
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Enrichment and Flow'), 'Manage Blocks', 0, 'Sessions', 'Manage blocks for planning sessions', 'blocks_manage.php,blocks_manage_addEdit.php,blocks_manage_delete.php','blocks_manage.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , 1, (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Enrichment and Flow' AND gibbonAction.name='Manage Blocks'));end
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Enrichment and Flow'), 'Manage Sessions', 0, 'Sessions', 'Manage sessions available for student signup', 'sessions_manage.php,sessions_manage_addEdit.php,sessions_manage_delete.php','sessions_manage.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , 1, (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Enrichment and Flow' AND gibbonAction.name='Manage Sessions'));end
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Enrichment and Flow'), 'My Sessions', 0, 'Flow', 'View and plan sessions', 'sessions_my.php,sessions_my_addEdit.php,sessions_my_join.php','sessions_my.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , 1, (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Enrichment and Flow' AND gibbonAction.name='My Sessions'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , 2, (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Enrichment and Flow' AND gibbonAction.name='My Sessions'));end
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Enrichment and Flow'), 'All Sessions', 0, 'Flow', 'View all planned sessions', 'sessions_view.php','sessions_view.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , 1, (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Enrichment and Flow' AND gibbonAction.name='All Sessions'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , 2, (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Enrichment and Flow' AND gibbonAction.name='All Sessions'));end
";

//v1.4.01
++$count;
$sql[$count][0] = '1.4.01';
$sql[$count][1] = "
DROP TABLE IF EXISTS `enfBlock`,`enfBlockFacility`,`enfPlannedSession`,`enfPlannedSessionTeacher`;end
CREATE TABLE `enfBlock` ( 
    `enfBlockID` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
    `gibbonSchoolYearID` INT UNSIGNED NOT NULL,
    `gibbonCourseID` INT UNSIGNED NOT NULL,
    `name` VARCHAR(60) NOT NULL,
    `gibbonDaysOfWeekID` INT UNSIGNED ZEROFILL NOT NULL,
    `timeStart` TIME NOT NULL,
    `timeEnd` TIME NOT NULL,
    `signUpSameDay` ENUM('Y','N') NOT NULL DEFAULT 'Y',
    `signUpStart` TIME NULL,
    PRIMARY KEY (`enfBlockID`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;end
 CREATE TABLE `enfBlockFacility` ( 
    `enfBlockFacilityID` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
    `enfBlockID` INT UNSIGNED NOT NULL,
    `gibbonSpaceID` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`enfBlockFacilityID`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;end
 CREATE TABLE `enfPlannedSession` ( 
    `enfPlannedSessionID` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
    `enfBlockID` INT UNSIGNED NOT NULL,
    `enfSessionID` INT UNSIGNED NOT NULL,
    `enfBlockFacilityID` INT UNSIGNED NULL,
    `gibbonSpaceID` INT UNSIGNED NULL,
    `notes` TEXT NULL,
    `gibbonPersonIDCreated` INT NOT NULL,
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    PRIMARY KEY (`enfPlannedSessionID`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;end
 CREATE TABLE `enfPlannedSessionTeacher` ( 
    `enfPlannedSessionTeacherID` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
    `enfPlannedSessionID` INT UNSIGNED NOT NULL,
    `gibbonPersonID` INT UNSIGNED NOT NULL,
    `timestampCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    PRIMARY KEY (`enfPlannedSessionTeacherID`),
    UNIQUE KEY (`enfPlannedSessionID`, `gibbonPersonID`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3;end
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Enrichment and Flow'), 'My Planner', 0, 'Flow', 'An overview of ENF plans for a given student.', 'planner_view.php','planner_view.php', 'Y', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N');end
UPDATE `gibbonAction` SET name='All Sessions_view' WHERE name='All Sessions' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Enrichment and Flow');end
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Enrichment and Flow'), 'All Sessions_manage', 1, 'Flow', 'View and manage all planned sessions', 'sessions_view.php,sessions_view_addEdit.php, sessions_view_addEditStudent.php','sessions_view.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , 1, (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Enrichment and Flow' AND gibbonAction.name='All Sessions_manage'));end
ALTER TABLE `enfSessionStudent` ADD `locked` ENUM('Y','N') NOT NULL DEFAULT 'N' AFTER `comment`;end
ALTER TABLE `enfSessionStudent` CHANGE `session` `focus` VARCHAR(120) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL;end
ALTER TABLE `enfSessionStudent` ADD UNIQUE(`enfBlockID`, `gibbonPersonID`, `date`);end
ALTER TABLE `enfSessionStudent` ADD `gibbonPersonIDModified` INT NOT NULL AFTER `gibbonPersonIDCreated`, ADD `timestampModified` TIMESTAMP NOT NULL AFTER `timestampCreated`;end
UPDATE `gibbonAction` SET URLList='planner_view.php' WHERE name='My Planner' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Enrichment and Flow');end
ALTER TABLE `enfSessionStudent` ADD `status` VARCHAR(60) NOT NULL DEFAULT 'Present' AFTER `comment`;end
UPDATE `gibbonAction` SET URLList='sessions_my.php,sessions_my_addEdit.php,sessions_my_join.php,sessions_my_delete.php' WHERE name='My Sessions' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Enrichment and Flow');end
";

//v1.4.02
++$count;
$sql[$count][0] = '1.4.02';
$sql[$count][1] = "
";
