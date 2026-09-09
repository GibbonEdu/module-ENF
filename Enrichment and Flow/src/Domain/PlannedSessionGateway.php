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

class PlannedSessionGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'enfPlannedSession';
    private static $primaryKey = 'enfPlannedSessionID';
    private static $searchableColumns = [''];

    public function getSessionDetailsByID(string $enfPlannedSessionID)
    {
        $data = ['enfPlannedSessionID' => $enfPlannedSessionID];
        $sql = "SELECT 
                    enfPlannedSession.enfPlannedSessionID,
                    enfPlannedSession.notes,
                    enfPlannedSession.gibbonSpaceID,
                    enfSession.enfSessionID, 
                    enfSession.type, 
                    enfSession.focus, 
                    enfSession.maxStudents, 
                    enfBlock.enfBlockID, 
                    enfBlock.name as block, 
                    enfBlock.timeStart, 
                    enfBlock.timeEnd
                FROM enfPlannedSession
                JOIN enfSession ON (enfSession.enfSessionID=enfPlannedSession.enfSessionID)
                JOIN enfBlock ON (enfBlock.enfBlockID=enfPlannedSession.enfBlockID)
                WHERE enfPlannedSession.enfPlannedSessionID=:enfPlannedSessionID";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectPlannedSessionListByBlock($enfBlockID)
    {
        $query = $this
            ->newSelect()
            ->cols(['enfSession.type', 'enfPlannedSession.enfPlannedSessionID', 'CONCAT(enfSession.focus, " (", gibbonSpace.name, ")") as focus'])
            ->from('enfPlannedSession')
            ->innerJoin('enfSession', 'enfSession.enfSessionID=enfPlannedSession.enfSessionID')
            ->innerJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID=enfPlannedSession.gibbonSpaceID')
            ->where('enfPlannedSession.enfBlockID=:enfBlockID')
            ->bindValue('enfBlockID', $enfBlockID)
            ->orderBy(['enfSession.type', 'enfSession.focus']);

        return $this->runSelect($query);
    }

    public function selectPlannedSessionsByDate(string $enfBlockID, string $date, bool $includeUnlisted = false)
    {
        $query = $this
            ->newSelect()
            ->cols(['enfBlock.name as groupBy', 'enfBlock.enfBlockID', 'enfBlock.name as block','enfBlock.timeStart','enfBlock.timeEnd', 'gibbonDaysOfWeek.name as weekday', 'enfPlannedSession.enfPlannedSessionID', 'enfSession.enfSessionID', 'enfSession.focus', 'enfSession.type', 'gibbonSpace.gibbonSpaceID', 'gibbonSpace.name as facility', 'enfPlannedSession.unlisted', 'COUNT(DISTINCT enfSessionStudent.gibbonPersonID) as students', 'enfSession.maxStudents'])
            ->from('enfBlock')
            ->innerJoin('enfPlannedSession', 'enfBlock.enfBlockID=enfPlannedSession.enfBlockID')
            ->innerJoin('enfSession', 'enfSession.enfSessionID=enfPlannedSession.enfSessionID')
            ->leftJoin('enfSessionStudent', 'enfSessionStudent.enfPlannedSessionID=enfPlannedSession.enfPlannedSessionID')
            ->leftJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID=enfPlannedSession.gibbonSpaceID')
            ->leftJoin('gibbonDaysOfWeek', 'gibbonDaysOfWeek.gibbonDaysOfWeekID=enfBlock.gibbonDaysOfWeekID')
            ->where('enfPlannedSession.enfBlockID=:enfBlockID')
            ->bindValue('enfBlockID', $enfBlockID)
            ->groupBy(['enfPlannedSession.enfPlannedSessionID'])
            ->orderBy(['gibbonDaysOfWeek.sequenceNumber', 'enfBlock.timeStart', 'enfSession.type', 'enfSession.focus']);

        if (!$includeUnlisted) {
            $query->where('enfPlannedSession.unlisted="N"');
        }

        return $this->runSelect($query);
    }

    public function selectPlannedSessionsByTeacher(string $enfBlockID, string $gibbonPersonID)
    {
        $query = $this
            ->newSelect()
            ->cols(['enfBlock.name as groupBy', 'enfBlock.enfBlockID', 'enfBlock.name','enfBlock.timeStart','enfBlock.timeEnd', 'gibbonDaysOfWeek.name as weekday', 'enfPlannedSessionTeacher.enfPlannedSessionID', 'enfPlannedSessionTeacher.gibbonPersonID', 'enfSession.enfSessionID', 'enfSession.focus', 'enfSession.type', 'gibbonSpace.name as facility', 'COUNT(DISTINCT enfSessionStudent.gibbonPersonID) as students', 'enfSession.maxStudents'])
            ->from('enfBlock')
            ->innerJoin('enfPlannedSession', 'enfBlock.enfBlockID=enfPlannedSession.enfBlockID')
            ->innerJoin('enfSession', 'enfSession.enfSessionID=enfPlannedSession.enfSessionID')
            ->innerJoin('enfPlannedSessionTeacher', 'enfPlannedSessionTeacher.enfPlannedSessionID=enfPlannedSession.enfPlannedSessionID')
            ->leftJoin('enfSessionStudent', 'enfSessionStudent.enfPlannedSessionID=enfPlannedSession.enfPlannedSessionID')
            ->leftJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID=enfPlannedSession.gibbonSpaceID')
            ->leftJoin('gibbonDaysOfWeek', 'gibbonDaysOfWeek.gibbonDaysOfWeekID=enfBlock.gibbonDaysOfWeekID')
            ->where('enfPlannedSession.enfBlockID=:enfBlockID')
            ->bindValue('enfBlockID', $enfBlockID)
            ->where('enfPlannedSessionTeacher.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->groupBy(['enfPlannedSession.enfPlannedSessionID'])
            ->orderBy(['gibbonDaysOfWeek.sequenceNumber', 'enfBlock.timeStart']);

        return $this->runSelect($query);
    }

    public function selectAvailablePlannedSessionsByBlock(string $enfBlockID, string $date)
    {
        $query = $this
            ->newSelect()
            ->cols(['enfBlock.name as groupBy', 'enfBlock.enfBlockID', 'enfBlock.name as block','enfBlock.timeStart','enfBlock.timeEnd', 'gibbonDaysOfWeek.name as weekday', 'enfPlannedSession.enfPlannedSessionID', 'enfSession.enfSessionID', 'enfSession.focus', 'enfSession.type', 'gibbonSpace.name as facility', 'COUNT(DISTINCT enfSessionStudent.gibbonPersonID) as students', 'enfSession.maxStudents'])
            ->from('enfBlock')
            ->innerJoin('enfPlannedSession', 'enfBlock.enfBlockID=enfPlannedSession.enfBlockID')
            ->innerJoin('enfSession', 'enfSession.enfSessionID=enfPlannedSession.enfSessionID')
            ->leftJoin('enfSessionStudent', 'enfSessionStudent.enfPlannedSessionID=enfPlannedSession.enfPlannedSessionID AND enfSessionStudent.date=:date')
            ->leftJoin('gibbonSpace', 'gibbonSpace.gibbonSpaceID=enfPlannedSession.gibbonSpaceID')
            ->leftJoin('gibbonDaysOfWeek', 'gibbonDaysOfWeek.gibbonDaysOfWeekID=enfBlock.gibbonDaysOfWeekID')
            ->where('enfPlannedSession.enfBlockID=:enfBlockID')
            ->where('enfPlannedSession.unlisted="N"')
            ->bindValue('enfBlockID', $enfBlockID)
            ->bindValue('date', $date)
            ->groupBy(['enfPlannedSession.enfPlannedSessionID'])
            ->having('students < maxStudents')
            ->orderBy(['gibbonDaysOfWeek.sequenceNumber', 'enfBlock.timeStart']);

        return $this->runSelect($query);
    }
}
