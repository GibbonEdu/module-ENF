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

class OpportunityMentorGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'enfOpportunityMentor';
    private static $primaryKey = 'enfOpportunityMentorID';

    /**
     * @param int enfOpportunityID
     * @return DataSet
     */
    public function selectMentorsByOpportunity($enfOpportunityID)
    {
        $query = $this
            ->newQuery()
            ->cols(['enfOpportunityMentor.gibbonPersonID', 'title', 'surname', 'preferredName'])
            ->from($this->getTableName())
            ->innerJoin('gibbonPerson', 'enfOpportunityMentor.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->where('enfOpportunityID=:enfOpportunityID')
            ->bindValue('enfOpportunityID', $enfOpportunityID)
            ->where("gibbonPerson.status='Full'")
            ->orderBy(['surname', 'preferredName']);

        return $this->runSelect($query);
    }

    /**
     * @param int enfOpportunityID
     * @return bool
     */
    public function deleteMentorsByOpportunity($enfOpportunityID)
    {
        $data = ['enfOpportunityID' => $enfOpportunityID];
        $sql = "DELETE FROM enfOpportunityMentor
                WHERE enfOpportunityID = :enfOpportunityID";

        return $this->db()->delete($sql, $data);
    }
}
