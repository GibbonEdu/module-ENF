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
            ->cols(['enfPlannedSessionTeacher.enfPlannedSessionTeacherID', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonStaffAbsenceDate.allDay as absenceAllDay', 'gibbonStaffAbsenceDate.timeStart as absenceStart', 'gibbonStaffAbsenceDate.timeEnd as absenceEnd'])
            ->from('enfPlannedSessionTeacher')
            ->innerJoin('enfPlannedSession', 'enfPlannedSession.enfPlannedSessionID=enfPlannedSessionTeacher.enfPlannedSessionID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=enfPlannedSessionTeacher.gibbonPersonID')
            ->leftJoin('gibbonStaffAbsence', 'gibbonStaffAbsence.gibbonPersonID=enfPlannedSessionTeacher.gibbonPersonID AND gibbonStaffAbsence.status="Approved"')
            ->leftJoin('gibbonStaffAbsenceType', 'gibbonStaffAbsenceType.gibbonStaffAbsenceTypeID=gibbonStaffAbsence.gibbonStaffAbsenceTypeID')
            ->leftJoin('gibbonStaffAbsenceDate', 'gibbonStaffAbsence.gibbonStaffAbsenceID=gibbonStaffAbsenceDate.gibbonStaffAbsenceID AND gibbonStaffAbsenceDate.date=:date')
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
        $sql = "SELECT gibbonCourseClassPerson.role, gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName
            FROM enfBlock
                INNER JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=enfBlock.gibbonCourseID)
                INNER JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                INNER JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                INNER JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
            WHERE enfBlock.enfBlockID=:enfBlockID
                AND gibbonPerson.status='Full'
                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date)
                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date)
                AND (gibbonCourseClassPerson.role='Teacher' OR gibbonCourseClassPerson.role='Assistant')
            GROUP BY gibbonPerson.gibbonPersonID
            ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

}
