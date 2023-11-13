<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Module\EnrichmentandFlow\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class DailyPlannerGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'enfPlannerEntry';
    private static $primaryKey = 'enfPlannerEntryID';
    private static $searchableColumns = ['date'];

    public function getPlannerEntryByDate($gibbonPersonIDStudent, $date)
    {
        $data = ['gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'date' => $date];
        $sql = "SELECT enfPlannerEntry.enfPlannerEntryID, enfPlannerEntry.date, enfPlannerEntry.tasks, gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.image_240, MAX(gibbonDiscussion.timestamp) as timestamp, GROUP_CONCAT(gibbonDiscussion.comment ORDER BY gibbonDiscussion.timestamp SEPARATOR '<br><hr class=\'my-3 border-dashed\'>') as comment
                FROM enfPlannerEntry
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=enfPlannerEntry.gibbonPersonID)
                LEFT JOIN gibbonDiscussion ON (gibbonDiscussion.gibbonPersonID=enfPlannerEntry.gibbonPersonID AND gibbonDiscussion.foreignTable='enfPlannerEntry' AND gibbonDiscussion.foreignTableID=enfPlannerEntry.enfPlannerEntryID)
                WHERE enfPlannerEntry.gibbonPersonID=:gibbonPersonIDStudent AND enfPlannerEntry.date=:date
                GROUP BY enfPlannerEntry.enfPlannerEntryID";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectPlannerEntryDiscussionByDate($enfPlannerEntryID)
    {
        $data = ['enfPlannerEntryID' => $enfPlannerEntryID];
        $sql = "SELECT gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.image_240, gibbonDiscussion.*
                FROM gibbonDiscussion
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonDiscussion.gibbonPersonID)
                WHERE gibbonDiscussion.foreignTable='enfPlannerEntry'
                AND gibbonDiscussion.foreignTableID=:enfPlannerEntryID
                GROUP BY gibbonDiscussion.gibbonDiscussionID";

        return $this->db()->select($sql, $data);
    }

    public function selectPlannerEntriesByStudent($gibbonPersonIDStudent)
    {
        $data = ['gibbonPersonIDStudent' => $gibbonPersonIDStudent];
        $sql = "SELECT enfPlannerEntry.date as groupBy, enfPlannerEntry.enfPlannerEntryID, enfPlannerEntry.date, enfPlannerEntry.tasks, gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.image_240, gibbonDiscussion.*
                FROM enfPlannerEntry
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=enfPlannerEntry.gibbonPersonID)
                LEFT JOIN gibbonDiscussion ON (gibbonDiscussion.gibbonPersonID=enfPlannerEntry.gibbonPersonID AND gibbonDiscussion.foreignTable='enfPlannerEntry' AND gibbonDiscussion.foreignTableID=enfPlannerEntry.enfPlannerEntryID)
                WHERE enfPlannerEntry.gibbonPersonID=:gibbonPersonIDStudent
                ORDER BY enfPlannerEntry.date DESC";

        return $this->db()->select($sql, $data);
    }

    public function selectENFStudentsByTeacher($gibbonSchoolYearID, $gibbonPersonIDTeacher)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonIDTeacher' => $gibbonPersonIDTeacher, 'today' => date('Y-m-d')];
        $sql = "SELECT DISTINCT student.gibbonPersonID, student.surname, student.preferredName, student.email, student.image_240
                FROM gibbonCourseClassPerson AS teacherClass
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=teacherClass.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                JOIN gibbonPerson AS teacher ON (teacherClass.gibbonPersonID=teacher.gibbonPersonID)
                JOIN gibbonCourseClassPerson AS studentClass ON (studentClass.gibbonCourseClassID=teacherClass.gibbonCourseClassID)
                JOIN gibbonPerson AS student ON (studentClass.gibbonPersonID=student.gibbonPersonID)
                WHERE gibbonCourse.nameShort LIKE 'ENF%'
                AND teacher.status='Full'
                AND teacherClass.role='Teacher'
                AND teacherClass.gibbonPersonID=:gibbonPersonIDTeacher
                AND studentClass.role='Student'
                AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID 
                AND student.status = 'Full'
                AND (student.dateStart IS NULL OR student.dateStart <= :today)
                AND (student.dateEnd IS NULL OR student.dateEnd >= :today)
                GROUP BY student.gibbonPersonID
                ORDER BY student.surname, student.preferredName, student.email";

        return $this->db()->select($sql, $data);
    }

    public function selectENFStudentsByClass($gibbonSchoolYearID, $gibbonCourseClassID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseClassID' => $gibbonCourseClassID, 'today' => date('Y-m-d')];
        $sql = "SELECT DISTINCT student.gibbonPersonID, student.surname, student.preferredName, student.email, student.image_240
                FROM gibbonCourseClass
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                JOIN gibbonPerson AS student ON (gibbonCourseClassPerson.gibbonPersonID=student.gibbonPersonID)
                WHERE gibbonCourse.nameShort LIKE 'ENF%'
                AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID 
                AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID
                AND gibbonCourseClassPerson.role='Student'
                AND student.status = 'Full'
                AND (student.dateStart IS NULL OR student.dateStart <= :today)
                AND (student.dateEnd IS NULL OR student.dateEnd >= :today)
                GROUP BY student.gibbonPersonID
                ORDER BY student.surname, student.preferredName, student.email";

        return $this->db()->select($sql, $data);
    }

    public function selectENFTeachersByStudent($gibbonSchoolYearID, $gibbonPersonIDStudent)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent];
        $sql = "SELECT DISTINCT teacher.gibbonPersonID, teacher.title, teacher.surname, teacher.preferredName, teacher.email
                FROM gibbonCourseClassPerson AS teacherClass
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=teacherClass.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                JOIN gibbonPerson AS teacher ON (teacherClass.gibbonPersonID=teacher.gibbonPersonID)
                JOIN gibbonCourseClassPerson AS studentClass ON (studentClass.gibbonCourseClassID=teacherClass.gibbonCourseClassID)
                JOIN gibbonPerson AS student ON (studentClass.gibbonPersonID=student.gibbonPersonID)
                WHERE gibbonCourse.nameShort LIKE 'ENF%'
                AND teacher.status='Full'
                AND teacherClass.role='Teacher'
                AND studentClass.gibbonPersonID=:gibbonPersonIDStudent
                AND studentClass.role='Student'
                AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID 
                GROUP BY teacher.gibbonPersonID
                ORDER BY teacher.surname, teacher.preferredName, teacher.email";

        return $this->db()->select($sql, $data);
    }

    public function selectAllENFClasses($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name
                FROM gibbonCourseClass 
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                WHERE gibbonCourse.nameShort LIKE 'ENF%'
                AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID 
                ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort";

        return $this->db()->select($sql, $data);
    }

    public function getENFClassByPerson($gibbonSchoolYearID, $gibbonPersonIDStudent)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'today' => date('Y-m-d')];
        $sql = "SELECT DISTINCT gibbonCourseClassPerson.*, gibbonCourseClass.name as 'className', gibbonCourseClass.nameShort as 'classNameShort', gibbonCourse.nameShort as 'courseNameShort', gibbonCourse.name as 'courseName', gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.email, gibbonPerson.image_240
                FROM gibbonCourseClassPerson
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE gibbonCourse.nameShort LIKE 'ENF%'
                AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonIDStudent
                AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID 
                AND gibbonCourseClassPerson.role NOT LIKE '%Left'
                AND gibbonPerson.status = 'Full'
                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)
                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)
                GROUP BY gibbonPerson.gibbonPersonID
                ORDER BY gibbonCourse.nameShort";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectPlannerTasksByEntry($enfPlannerEntryID)
    {
        $data = ['enfPlannerEntryID' => $enfPlannerEntryID];
        $sql = "SELECT enfPlannerTaskID, category, minutes, description, sequenceNumber, timestamp
                FROM enfPlannerTask
                WHERE enfPlannerTask.enfPlannerEntryID=:enfPlannerEntryID
                ORDER BY enfPlannerTask.sequenceNumber";

        return $this->db()->select($sql, $data);
    }
}
