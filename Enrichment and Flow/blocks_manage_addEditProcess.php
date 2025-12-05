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

use Gibbon\Http\Url;
use Gibbon\Data\Validator;
use Gibbon\Module\EnrichmentandFlow\Domain\BlockGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\BlockFacilityGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$enfBlockID = $_REQUEST['enfBlockID'] ?? '';
$URL = Url::fromModuleRoute('Enrichment and Flow', 'blocks_manage_addEdit')->withQueryParams(['enfBlockID' => $enfBlockID]);

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/blocks_manage_addEdit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $blockGateway = $container->get(BlockGateway::class);
    $blockFacilityGateway = $container->get(BlockFacilityGateway::class);

    $data = [
        'name'               => $_POST['name'] ?? '',
        'gibbonDaysOfWeekID' => $_POST['gibbonDaysOfWeekID'] ?? null,
        'timeStart'          => $_POST['timeStart'] ?? '',
        'timeEnd'            => $_POST['timeEnd'] ?? '',
        'signUpSameDay'      => $_POST['signUpSameDay'] ?? 'N',
        'signUpStart'        => $_POST['signUpStart'] ?? $_POST['timeStart'] ?? null,
    ];

    // Validate the required values are present
    if (empty($data['name']) || empty($data['gibbonDaysOfWeekID']) || empty($data['timeStart']) || empty($data['timeEnd'])) {
        header("Location: {$URL}&return=error1");
        exit;
    }

    // Validate that this record is unique
    if (!$blockGateway->unique($data, ['name'], $enfBlockID)) {
        header("Location: {$URL}&return=error7");
        exit;
    }

    if ($blockGateway->exists($enfBlockID)) {
        // Update
        $blockGateway->update($enfBlockID, $data);
    } else {
        // Insert
        $enfBlockID = $blockGateway->insert($data);
        $URL .= "&editID=$enfBlockID";
    }

    // Add or update facilities
    if (!empty($enfBlockID) && !empty($_POST['facilities'])) {

        $facilities = array_map(function ($item) {
            return [
                'gibbonSpaceID' => intval($item['gibbonSpaceID']),
            ];
        }, $_POST['facilities'] ?? []);

        $facilities = array_combine(array_keys($_POST['order'] ?? []), array_values($facilities));
        ksort($facilities);

        $facilityIDs = [];
        foreach ($facilities as $order => $facility) {
            $facility['enfBlockID'] = $enfBlockID;

            if (!empty($facility['enfBlockFacilityID'])) {
                $blockFacilityGateway->update($facility['enfBlockFacilityID'], $facility);
            } else {
                $facility['enfBlockFacilityID'] = $blockFacilityGateway->insert($facility);
            }

            $facilityIDs[] = $facility['enfBlockFacilityID'];
        } 

        $blockFacilityGateway->deleteFacilitiesNotInList($enfBlockID, $facilityIDs);

    }

    $URL .= !empty($enfBlockID)
        ? "&return=success0"
        : "&return=error2";

    header("Location: {$URL}");
}
