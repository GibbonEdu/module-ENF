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
