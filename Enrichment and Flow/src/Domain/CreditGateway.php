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

class CreditGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'enfCredit';
    private static $primaryKey = 'enfCreditID';
    private static $searchableColumns = ['enfCredit.name'];

    /**
     * @param QueryCriteria $criteria
     * @param bool $inactive
     * @return DataSet
     */
    public function queryCredits(QueryCriteria $criteria, $all = true)
    {
        $query = $this
            ->newQuery()
            ->cols(['enfCredit.*', 'enfDomain.name AS domain', 'backgroundColour', 'accentColour'])
            ->from($this->getTableName())
            ->innerJoin('enfDomain', 'enfCredit.enfDomainID=enfDomain.enfDomainID');

        if (!$all) {
            $query->where('enfCredit.active=:active')
                  ->bindValue('active', 'Y');
        }

        $criteria->addFilterRules([
            'enfDomainID' => function ($query, $enfDomainID) {
                return $query
                    ->where('enfCredit.enfDomainID = :enfDomainID')
                    ->bindValue('enfDomainID', $enfDomainID);
            }
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectCreditByID(int $enfCreditID)
    {
        $query = $this
            ->newQuery()
            ->cols(['enfCredit.*', 'enfDomain.name AS domain', 'backgroundColour', 'accentColour'])
            ->from($this->getTableName())
            ->innerJoin('enfDomain', 'enfCredit.enfDomainID=enfDomain.enfDomainID')
            ->where('enfCredit.enfCreditID = :enfCreditID')
            ->bindValue('enfCreditID', $enfCreditID);

        return $this->runSelect($query);
    }
}
