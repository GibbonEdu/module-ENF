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

class PlannedSessionTeacherGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'enfPlannedSessionTeacher';
    private static $primaryKey = 'enfPlannedSessionTeacherID';
    private static $searchableColumns = [''];

    public function selectTeachersByPlannedSession($enfPlannedSessionID, $date)
    {
        $query = $this
            ->newSelect()
            ->cols(['enfPlannedSessionTeacher.enfPlannedSessionTeacherID', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.image_240', 'gibbonStaffAbsenceDate.allDay as absenceAllDay', 'gibbonStaffAbsenceDate.timeStart as absenceStart', 'gibbonStaffAbsenceDate.timeEnd as absenceEnd', 'enfPlannedSessionTeacher.enfPlannedSessionID', 'classTeacher.role'])
            ->from('enfPlannedSessionTeacher')
            ->innerJoin('enfPlannedSession', 'enfPlannedSession.enfPlannedSessionID=enfPlannedSessionTeacher.enfPlannedSessionID')
            ->innerJoin('enfBlock', 'enfBlock.enfBlockID=enfPlannedSession.enfBlockID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=enfPlannedSessionTeacher.gibbonPersonID')
            ->leftJoin('gibbonStaffAbsence', 'gibbonStaffAbsence.gibbonPersonID=enfPlannedSessionTeacher.gibbonPersonID AND gibbonStaffAbsence.status="Approved"')
            ->leftJoin('gibbonStaffAbsenceType', 'gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID=gibbonStaffAbsence.gibbonStaffAbsenceTypeID')
            ->leftJoin('gibbonStaffAbsenceDate', 'gibbonStaffAbsence.gibbonStaffAbsenceID=gibbonStaffAbsenceDate.gibbonStaffAbsenceID AND gibbonStaffAbsenceDate.date=:date')
            ->joinSubSelect(
                'LEFT',
                $this->newSelect()
                    ->cols(['gibbonCourseClassPerson.gibbonPersonID', 'gibbonCourseClass.gibbonCourseID', 'gibbonCourseClassPerson.role'])
                    ->from('gibbonCourseClass')
                    ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourseClassPerson.role="Teacher"'),
                'classTeacher', 'classTeacher.gibbonPersonID=enfPlannedSessionTeacher.gibbonPersonID AND classTeacher.gibbonCourseID=enfBlock.gibbonCourseID'
            )
            ->where('enfPlannedSessionTeacher.enfPlannedSessionID=:enfPlannedSessionID')
            ->bindValue('enfPlannedSessionID', $enfPlannedSessionID)
            ->bindValue('date', $date)
            ->groupBy(['enfPlannedSessionTeacher.gibbonPersonID'])
            ->orderBy(['enfPlannedSessionTeacher.timestampCreated', 'gibbonPerson.surname', 'gibbonPerson.preferredName']);

        return $this->runSelect($query);
    }

    public function selectAvailableTeachersByBlock($enfBlockID, $date)
    {
        $data =['enfBlockID' => $enfBlockID, 'date' => $date];
        $sql = "SELECT gibbonCourseClassPerson.role, gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.title, gibbonPerson.image_240, staffAbsence.allDay as absenceAllDay, staffAbsence.timeStart as absenceStart, staffAbsence.timeEnd as absenceEnd, staffAbsence.gibbonStaffAbsenceID
            FROM enfBlock
                INNER JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=enfBlock.gibbonCourseID)
                INNER JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                INNER JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                INNER JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                LEFT JOIN enfPlannedSession ON (enfPlannedSession.enfBlockID=enfBlock.enfBlockID)
                LEFT JOIN enfPlannedSessionTeacher ON (enfPlannedSessionTeacher.gibbonPersonID=gibbonPerson.gibbonPersonID AND enfPlannedSessionTeacher.enfPlannedSessionID=enfPlannedSession.enfPlannedSessionID)
                LEFT JOIN (SELECT gibbonStaffAbsence.gibbonPersonID, gibbonStaffAbsence.gibbonStaffAbsenceID, gibbonStaffAbsenceDate.date, gibbonStaffAbsenceDate.allDay, gibbonStaffAbsenceDate.timeStart, gibbonStaffAbsenceDate.timeEnd
                    FROM gibbonStaffAbsence
                    JOIN gibbonStaffAbsenceDate ON (gibbonStaffAbsence.gibbonStaffAbsenceID=gibbonStaffAbsenceDate.gibbonStaffAbsenceID AND gibbonStaffAbsenceDate.date=:date)
                    WHERE gibbonStaffAbsence.status='Approved') 
                    AS staffAbsence ON (gibbonPerson.gibbonPersonID=staffAbsence.gibbonPersonID AND staffAbsence.date=:date)
            WHERE enfBlock.enfBlockID=:enfBlockID
                AND gibbonPerson.status='Full'
                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date)
                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date)
                AND (gibbonCourseClassPerson.role='Teacher' AND gibbonCourseClassPerson.reportable='Y')
            GROUP BY gibbonPerson.gibbonPersonID
            HAVING COUNT(enfPlannedSessionTeacher.enfPlannedSessionTeacherID) = 0
            ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectCoverTeachersByBlock($enfBlockID, $date)
    {
        $data =['enfBlockID' => $enfBlockID, 'date' => $date];
        $sql = "SELECT 'Cover' as role, gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.title, gibbonPerson.image_240, gibbonStaffCoverageDate.date, gibbonStaffCoverageDate.allDay as absenceAllDay, gibbonStaffCoverageDate.timeStart as absenceStart, gibbonStaffCoverageDate.timeEnd as absenceEnd, enfPlannedSessionTeacher.enfPlannedSessionTeacherID
            FROM enfBlock
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=enfBlock.gibbonCourseID)
                JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                JOIN gibbonTTDayRowClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID)
                JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID AND gibbonTTColumnRow.timeStart=enfBlock.timeStart AND gibbonTTColumnRow.timeEnd=enfBlock.timeEnd)
                JOIN gibbonStaffCoverageDate ON (gibbonStaffCoverageDate.foreignTable='gibbonTTDayRowClass' AND gibbonStaffCoverageDate.foreignTableID=gibbonTTDayRowClass.gibbonTTDayRowClassID)
                JOIN gibbonStaffCoverage ON (gibbonStaffCoverage.gibbonStaffCoverageID=gibbonStaffCoverageDate.gibbonStaffCoverageID AND gibbonStaffCoverage.status='Accepted')
                JOIN gibbonPerson ON (gibbonStaffCoverage.gibbonPersonIDCoverage=gibbonPerson.gibbonPersonID)
                LEFT JOIN enfPlannedSessionTeacher ON (enfPlannedSessionTeacher.gibbonPersonID=gibbonPerson.gibbonPersonID)
                LEFT JOIN enfPlannedSession ON (enfPlannedSession.enfPlannedSessionID=enfPlannedSessionTeacher.enfPlannedSessionID AND enfPlannedSession.enfBlockID=enfBlock.enfBlockID)
            WHERE enfBlock.enfBlockID=:enfBlockID
                AND gibbonPerson.status='Full'
                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date)
                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date)
                AND gibbonStaffCoverageDate.date=:date
            GROUP BY gibbonPerson.gibbonPersonID
            HAVING COUNT(enfPlannedSessionTeacher.enfPlannedSessionTeacherID) = 0
            ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }



    public function getSessionTeacherByBlock($gibbonPersonID, $enfBlockID)
    {
        $data =['gibbonPersonID' => $gibbonPersonID, 'enfBlockID' => $enfBlockID];
        $sql = "SELECT enfPlannedSessionTeacher.enfPlannedSessionTeacherID 
            FROM enfPlannedSessionTeacher 
            JOIN enfPlannedSession ON (enfPlannedSession.enfPlannedSessionID=enfPlannedSessionTeacher.enfPlannedSessionID)
            WHERE enfPlannedSession.enfBlockID=:enfBlockID
            AND enfPlannedSessionTeacher.gibbonPersonID=:gibbonPersonID";

        return $this->db()->selectOne($sql, $data);
    }

    public function deleteTeachersByBlockID($enfBlockID)
    {
        $data =['enfBlockID' => $enfBlockID];
        $sql = "DELETE enfPlannedSessionTeacher 
            FROM enfPlannedSessionTeacher
            JOIN enfPlannedSession ON (enfPlannedSession.enfPlannedSessionID=enfPlannedSessionTeacher.enfPlannedSessionID)
            JOIN enfBlock ON (enfBlock.enfBlockID=enfPlannedSession.enfBlockID)
            WHERE enfBlock.enfBlockID=:enfBlockID";

        return $this->db()->delete($sql, $data);
    }

}
