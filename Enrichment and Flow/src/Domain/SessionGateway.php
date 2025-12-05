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

class SessionGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'enfSession';
    private static $primaryKey = 'enfSessionID';
    private static $searchableColumns = [''];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function querySessions(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->cols(['enfSession.enfSessionID', 'enfSession.focus', 'enfSession.type', 'enfSession.maxStudents', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname'])
            ->from($this->getTableName())
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=enfSession.gibbonPersonIDCreated');

        return $this->runQuery($query, $criteria);
    }

    public function selectPlannedSessions()
    {
        $query = $this
            ->newSelect()
            ->cols(['enfBlock.name as groupBy', 'enfBlock.enfBlockID', 'enfBlock.name as block','enfBlock.timeStart','enfBlock.timeEnd', 'gibbonDaysOfWeek.name as weekday', 'enfPlannedSession.enfPlannedSessionID', 'enfSession.enfSessionID', 'enfSession.focus', 'enfSession.type'])
            ->from('enfBlock')
            ->leftJoin('enfPlannedSession', 'enfBlock.enfBlockID=enfPlannedSession.enfBlockID')
            ->leftJoin('enfSession', 'enfSession.enfSessionID=enfPlannedSession.enfSessionID')
            ->leftJoin('enfPlannedSessionTeacher', 'enfPlannedSessionTeacher.enfPlannedSessionID=enfPlannedSession.enfPlannedSessionID')
            ->leftJoin('enfBlockFacility', 'enfBlockFacility.enfBlockFacilityID=enfPlannedSession.enfBlockFacilityID')
            ->leftJoin('gibbonDaysOfWeek', 'gibbonDaysOfWeek.gibbonDaysOfWeekID=enfBlock.gibbonDaysOfWeekID')
            ->orderBy(['gibbonDaysOfWeek.sequenceNumber', 'enfBlock.timeStart']);

        return $this->runSelect($query);
    }

    public function selectPlannedSessionsByTeacher(string $gibbonPersonID)
    {
        $query = $this
            ->newSelect()
            ->cols(['enfBlock.name as groupBy', 'enfBlock.enfBlockID', 'enfBlock.name','enfBlock.timeStart','enfBlock.timeEnd', 'gibbonDaysOfWeek.name as weekday', 'enfPlannedSession.enfPlannedSessionID', 'enfSession.enfSessionID', 'enfSession.focus', 'enfSession.type'])
            ->from('enfBlock')
            ->leftJoin('enfPlannedSession', 'enfBlock.enfBlockID=enfPlannedSession.enfBlockID')
            ->leftJoin('enfSession', 'enfSession.enfSessionID=enfPlannedSession.enfSessionID')
            ->leftJoin('enfPlannedSessionTeacher', 'enfPlannedSessionTeacher.enfPlannedSessionID=enfPlannedSession.enfPlannedSessionID AND enfPlannedSessionTeacher.gibbonPersonID=:gibbonPersonID')
            ->leftJoin('enfBlockFacility', 'enfBlockFacility.enfBlockFacilityID=enfPlannedSession.enfBlockFacilityID')
            ->leftJoin('gibbonDaysOfWeek', 'gibbonDaysOfWeek.gibbonDaysOfWeekID=enfBlock.gibbonDaysOfWeekID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->orderBy(['gibbonDaysOfWeek.sequenceNumber', 'enfBlock.timeStart']);

        return $this->runSelect($query);
    }
}
