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

class BlockFacilityGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'enfBlockFacility';
    private static $primaryKey = 'enfBlockFacilityID';
    private static $searchableColumns = [''];

    public function selectFacilitiesByBlock($enfBlockID)
    {
        $data = ['enfBlockID' => $enfBlockID];
        $sql = "SELECT enfBlockFacility.enfBlockFacilityID, enfBlockFacility.gibbonSpaceID, gibbonSpace.name, gibbonSpace.name as space
                FROM enfBlockFacility 
                JOIN gibbonSpace ON (gibbonSpace.gibbonSpaceID=enfBlockFacility.gibbonSpaceID)
                WHERE enfBlockFacility.enfBlockID=:enfBlockID
                ORDER BY enfBlockFacility.sequenceNumber, gibbonSpace.name";

        return $this->db()->select($sql, $data);
    }

    public function selectUsedAndAvailableFacilitiesByBlock($enfBlockID)
    {
        $data = ['enfBlockID' => $enfBlockID];
        $sql = "(
                    SELECT enfBlockFacility.enfBlockFacilityID, enfBlockFacility.gibbonSpaceID, gibbonSpace.name, gibbonSpace.name as space, enfBlockFacility.sequenceNumber
                    FROM enfBlockFacility 
                    JOIN gibbonSpace ON (gibbonSpace.gibbonSpaceID=enfBlockFacility.gibbonSpaceID)
                    WHERE enfBlockFacility.enfBlockID=:enfBlockID
                ) UNION ALL (
                    SELECT NULL as enfBlockFacilityID, gibbonSpace.gibbonSpaceID, gibbonSpace.name, gibbonSpace.name as space, 1000 as sequenceNumber
                    FROM gibbonSpace
                    JOIN enfPlannedSession ON (enfPlannedSession.gibbonSpaceID=gibbonSpace.gibbonSpaceID)
                    JOIN enfSession ON (enfSession.enfSessionID=enfPlannedSession.enfSessionID)
                    LEFT JOIN enfBlockFacility ON (enfBlockFacility.gibbonSpaceID=gibbonSpace.gibbonSpaceID AND enfBlockFacility.enfBlockID=:enfBlockID)
                    WHERE enfPlannedSession.enfBlockID=:enfBlockID
                    AND enfBlockFacility.enfBlockFacilityID IS NULL
                ) ORDER BY sequenceNumber, name";

        return $this->db()->select($sql, $data);
    }

    public function selectAvailableFacilitiesListByBlock($enfBlockID, $enfPlannedSessionID)
    {
        $data = ['enfBlockID' => $enfBlockID, 'enfPlannedSessionID' => $enfPlannedSessionID];
        $sql = "SELECT enfBlockFacility.enfBlockFacilityID as value, gibbonSpace.name
                FROM enfBlockFacility 
                JOIN enfBlock ON (enfBlock.enfBlockID=enfBlockFacility.enfBlockID)
                JOIN gibbonSpace ON (gibbonSpace.gibbonSpaceID=enfBlockFacility.gibbonSpaceID)
                LEFT JOIN enfPlannedSession ON (enfPlannedSession.enfBlockID=enfBlockFacility.enfBlockID AND enfPlannedSession.enfBlockFacilityID=enfBlockFacility.enfBlockFacilityID AND NOT enfPlannedSession.enfPlannedSessionID=:enfPlannedSessionID)
                WHERE enfBlock.enfBlockID=:enfBlockID
                AND enfPlannedSession.enfPlannedSessionID IS NULL";

        return $this->db()->select($sql, $data);
    }

    public function deleteFacilitiesNotInList($enfBlockID, $enfBlockFacilityList)
    {
        $enfBlockFacilityList = is_array($enfBlockFacilityList) ? implode(',', $enfBlockFacilityList) : $enfBlockFacilityList;

        $data = ['enfBlockID' => $enfBlockID, 'enfBlockFacilityList' => $enfBlockFacilityList];
        $sql = "DELETE FROM enfBlockFacility WHERE enfBlockID=:enfBlockID AND NOT FIND_IN_SET(enfBlockFacilityID, :enfBlockFacilityList)";

        return $this->db()->delete($sql, $data);
    }
}
