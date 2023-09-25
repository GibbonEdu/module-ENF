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

class AnnouncementGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'enfAnnouncement';
    private static $primaryKey = 'enfAnnouncementID';
    private static $searchableColumns = ['date'];

    /**
     * @param QueryCriteria $criteria
     * @param bool $inactive
     * @return DataSet
     */
    public function queryAnnouncements(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->cols(['enfAnnouncement.*', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname'])
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=enfAnnouncement.gibbonPersonIDCreated')
            ->from($this->getTableName());

        return $this->runQuery($query, $criteria);
    }

    public function getAnnouncementByDate(string $date)
    {
        $query = $this
            ->newQuery()
            ->cols(['enfAnnouncement.*'])
            ->from($this->getTableName())
            ->where('enfAnnouncement.date = :date')
            ->bindValue('date', $date);

        return $this->runSelect($query)->fetch();
    }
}
