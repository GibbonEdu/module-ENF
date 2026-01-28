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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Module\EnrichmentandFlow\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class SessionStudentGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'enfSessionStudent';
    private static $primaryKey = 'enfSessionStudentID';
    private static $searchableColumns = [''];

    public function selectStudentsByPlannedSessionAndDate($enfPlannedSessionID, $date)
    {
        $query = $this
            ->newSelect()
            ->cols(['enfSessionStudent.enfSessionStudentID', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.image_240', 'gibbonFormGroup.name as formGroup', 'enfSessionStudent.locked', 'enfSessionStudent.comment', 'enfSessionStudent.timestampModified'])
            ->from('enfPlannedSession')
            ->innerJoin('enfBlock', 'enfBlock.enfBlockID=enfPlannedSession.enfBlockID')
            ->innerJoin('enfSessionStudent', 'enfSessionStudent.enfPlannedSessionID=enfPlannedSession.enfPlannedSessionID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=enfSessionStudent.gibbonPersonID')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=enfBlock.gibbonSchoolYearID')
            ->leftJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            ->where('enfSessionStudent.enfPlannedSessionID=:enfPlannedSessionID')
            ->bindValue('enfPlannedSessionID', $enfPlannedSessionID)
            ->where('enfSessionStudent.date=:date')
            ->bindValue('date', $date)
            ->orderBy(['gibbonPerson.surname', 'gibbonPerson.preferredName']);

        return $this->runSelect($query);
    }

    public function selectSessionsByStudentsAndDate($gibbonPersonID, $date)
    {
        $query = $this
            ->newSelect()
            ->cols(['enfBlock.enfBlockID as groupBy', 'enfBlock.enfBlockID', 'enfBlock.name as block', 'enfPlannedSession.enfPlannedSessionID', 'enfSessionStudent.enfSessionStudentID', 'enfSession.focus', 'enfSession.type', 'gibbonSpace.name as facility', 'enfSessionStudent.locked', 'enfSessionStudent.comment', 'enfSessionStudent.timestampCreated', 'enfSessionStudent.timestampModified', 'GROUP_CONCAT(DISTINCT CONCAT(teacher.title, " ", teacher.surname) SEPARATOR ", ") as teachers'])
            ->from('enfSessionStudent')
            ->innerJoin('enfPlannedSession', 'enfSessionStudent.enfPlannedSessionID=enfPlannedSession.enfPlannedSessionID')
            ->innerJoin('enfSession', 'enfSession.enfSessionID=enfPlannedSession.enfSessionID')
            ->innerJoin('enfBlock', 'enfBlock.enfBlockID=enfPlannedSession.enfBlockID')
            ->leftJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID=enfPlannedSession.gibbonSpaceID')
            ->leftJoin('enfPlannedSessionTeacher', 'enfPlannedSessionTeacher.enfPlannedSessionID=enfPlannedSession.enfPlannedSessionID')
            ->leftJoin('gibbonPerson as teacher', 'teacher.gibbonPersonID=enfPlannedSessionTeacher.gibbonPersonID')
            ->where('enfSessionStudent.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where('enfSessionStudent.date=:date')
            ->bindValue('date', $date)
            ->groupBy(['enfSessionStudent.enfSessionStudentID'])
            ->orderBy(['enfBlock.timeStart']);

        return $this->runSelect($query);
    }

    public function selectStudentsByBlock($enfBlockID, $date)
    {
        $data =['enfBlockID' => $enfBlockID, 'date' => $date];
        $sql = "SELECT gibbonCourseClassPerson.role, gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonFormGroup.name as formGroup
            FROM enfBlock
                INNER JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=enfBlock.gibbonCourseID)
                INNER JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                INNER JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                INNER JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                INNER JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=enfBlock.gibbonSchoolYearID)
                INNER JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
            WHERE enfBlock.enfBlockID=:enfBlockID
                AND gibbonPerson.status='Full'
                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date)
                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date)
                AND gibbonCourseClassPerson.role='Student'
            GROUP BY gibbonPerson.gibbonPersonID
            ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectENFStudentsNotSignedUp($gibbonSchoolYearID, string $date)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'date' => $date];
        $sql = "SELECT DISTINCT student.gibbonPersonID, student.surname, student.preferredName, student.email, student.image_240, gibbonFormGroup.name as formGroup, gibbonCourse.nameShort as course, gibbonCourseClass.nameShort as class
                FROM enfBlock
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=enfBlock.gibbonCourseID)
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                JOIN gibbonPerson AS student ON (gibbonCourseClassPerson.gibbonPersonID=student.gibbonPersonID)
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonCourse.gibbonSchoolYearID)
                JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
                LEFT JOIN enfSessionStudent ON (enfSessionStudent.gibbonPersonID=student.gibbonPersonID AND enfSessionStudent.enfBlockID=enfBlock.enfBlockID AND enfSessionStudent.date=:date)
                WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID 
                AND gibbonCourseClassPerson.role='Student'
                AND gibbonCourseClassPerson.reportable = 'Y'
                AND student.status = 'Full'
                AND (student.dateStart IS NULL OR student.dateStart <= :date)
                AND (student.dateEnd IS NULL OR student.dateEnd >= :date)
                AND enfSessionStudent.enfSessionStudentID IS NULL
                GROUP BY student.gibbonPersonID
                ORDER BY gibbonFormGroup.name, student.surname, student.preferredName, student.email";

        return $this->db()->select($sql, $data);
    }
}
