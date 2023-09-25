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

class OpportunityCreditGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'enfOpportunityCredit';
    private static $primaryKey = 'enfOpportunityCreditID';

    /**
     * @param int enfOpportunityID
     * @return DataSet
     */
    public function selectCreditsByOpportunity($enfOpportunityID)
    {
        $query = $this
            ->newQuery()
            ->cols(['enfOpportunityCredit.enfCreditID', 'enfCredit.name', 'backgroundColour', 'accentColour'])
            ->from($this->getTableName())
            ->innerJoin('enfCredit', 'enfOpportunityCredit.enfCreditID=enfCredit.enfCreditID')
            ->innerJoin('enfDomain', 'enfCredit.enfDomainID=enfDomain.enfDomainID')
            ->where('enfOpportunityID=:enfOpportunityID')
            ->bindValue ('enfOpportunityID', $enfOpportunityID)
            ->orderBy(['enfDomain.sequenceNumber', 'enfDomain.name', 'enfCredit.name']);

        return $this->runSelect($query);
    }

    /**
     * @param int enfOpportunityID
     * @return bool
     */
    public function deleteCreditsByOpportunity($enfOpportunityID)
    {
        $data = ['enfOpportunityID' => $enfOpportunityID];
        $sql = "DELETE FROM enfOpportunityCredit
                WHERE enfOpportunityID = :enfOpportunityID";

        return $this->db()->delete($sql, $data);
    }
}
