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

class BlockGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'enfBlock';
    private static $primaryKey = 'enfBlockID';
    private static $searchableColumns = [''];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryBlocks(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->cols(['enfBlock.enfBlockID', 'enfBlock.name','enfBlock.timeStart','enfBlock.timeEnd','enfBlock.signUpSameDay','enfBlock.signUpStart', 'gibbonDaysOfWeek.name as weekday', 'gibbonCourse.nameShort as courseNameShort'])
            ->from($this->getTableName())
            ->leftJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=enfBlock.gibbonCourseID')
            ->leftJoin('gibbonDaysOfWeek', 'gibbonDaysOfWeek.gibbonDaysOfWeekID=enfBlock.gibbonDaysOfWeekID');

        return $this->runQuery($query, $criteria);
    }

    public function selectBlocks()
    {
        $data = [];
        $sql = "SELECT enfBlock.enfBlockID, enfBlock.name, enfBlock.timeStart, enfBlock.timeEnd, enfBlock.signUpSameDay, enfBlock.signUpStart, gibbonDaysOfWeek.name as weekday
                FROM enfBlock
                JOIN gibbonDaysOfWeek ON (gibbonDaysOfWeek.gibbonDaysOfWeekID=enfBlock.gibbonDaysOfWeekID)
                WHERE enfBlock.active='Y'
                ORDER BY gibbonDaysOfWeek.sequenceNumber, enfBlock.timeStart";

        return $this->db()->select($sql, $data);
    }

    public function selectBlocksByWeekday(string $weekday)
    {
        $data = ['weekday' => $weekday];
        $sql = "SELECT enfBlock.enfBlockID, enfBlock.name, enfBlock.timeStart, enfBlock.timeEnd, enfBlock.signUpSameDay, enfBlock.signUpStart, gibbonDaysOfWeek.name as weekday
                FROM enfBlock
                JOIN gibbonDaysOfWeek ON (gibbonDaysOfWeek.gibbonDaysOfWeekID=enfBlock.gibbonDaysOfWeekID)
                WHERE gibbonDaysOfWeek.name=:weekday
                AND enfBlock.active='Y'
                ORDER BY gibbonDaysOfWeek.sequenceNumber, enfBlock.timeStart";

        return $this->db()->select($sql, $data);
    }
}
