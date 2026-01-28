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
use Gibbon\Module\EnrichmentandFlow\Domain\SessionGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionTeacherGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\BlockGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\BlockFacilityGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['notes' => 'HTML']);

$enfPlannedSessionID = $_REQUEST['enfPlannedSessionID'] ?? '';
$enfBlockID = $_REQUEST['enfBlockID'] ?? '';

$URL = Url::fromModuleRoute('Enrichment and Flow', 'sessions_my_addEdit')->withQueryParams(['enfPlannedSessionID' => $enfPlannedSessionID, 'enfBlockID' => $enfBlockID]);

$URLSuccess = Url::fromModuleRoute('Enrichment and Flow', 'sessions_my')->withQueryParams(['enfPlannedSessionID' => $enfPlannedSessionID, 'enfBlockID' => $enfBlockID]);

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/sessions_my_addEdit.php') == false) {
    header("Location: {$URL}&return=error0");
    exit;
} else {
    // Proceed!
    $plannedSessionGateway = $container->get(PlannedSessionGateway::class);
    $plannedSessionTeacherGateway = $container->get(PlannedSessionTeacherGateway::class);

    $data = [
        'enfBlockID'            => $enfBlockID,
        'enfSessionID'          => $_POST['enfSessionID'] ?? null,
        'enfBlockFacilityID'    => $_POST['enfBlockFacilityID'] ?? null,
        'gibbonSpaceID'         => $_POST['gibbonSpaceID'] ?? null,
        'notes'                 => $_POST['notes'] ?? null,
    ];

    // Validate the required values are present
    if (empty($data['enfBlockID']) || empty($data['enfSessionID'])) {
        header("Location: {$URL}&return=error1");
        exit;
    }

    // Validate database records
    if (!$container->get(BlockGateway::class)->exists($enfBlockID)) {
        header("Location: {$URL}&return=error2");
        return;
    }

    // Ensure a gibbonSpaceID is set when choosing an existing facility
    if (empty($data['gibbonSpaceID'])) {
        $facility = $container->get(BlockFacilityGateway::class)->getByID($data['enfBlockFacilityID']);
        $data['gibbonSpaceID'] = $facility['gibbonSpaceID'] ?? '';
    }

    if ($data['enfSessionID'] == 'Create') {
        $sessionData = [
            'type'                  => $_POST['type'] ?? '',
            'focus'                 => $_POST['focus'] ?? '',
            'maxStudents'           => $_POST['maxStudents'] ?? null,
            'gibbonPersonIDCreated' => $session->get('gibbonPersonID'),
            'timestampCreated'      => date('Y-m-d H:i:s'),
        ];
        $data['enfSessionID'] = $container->get(SessionGateway::class)->insert($sessionData);
    }
    
    if ($plannedSessionGateway->exists($enfPlannedSessionID)) {
        // Update
        $plannedSessionGateway->update($enfPlannedSessionID, $data);

        $plannedSessionTeacherGateway->insertAndUpdate([
            'enfPlannedSessionID' => $enfPlannedSessionID,
            'gibbonPersonID'      => $_POST['gibbonPersonID'] ?? $session->get('gibbonPersonID'),
            'timestampCreated'    => date('Y-m-d H:i:s'),
        ], [
            'timestampCreated'    => date('Y-m-d H:i:s')
        ]);

    } else {
        // Insert
        $enfPlannedSessionID = $plannedSessionGateway->insert($data + [
            'gibbonPersonIDCreated' => $session->get('gibbonPersonID'),
            'timestampCreated'      => date('Y-m-d H:i:s'),
        ]);

        if ($enfPlannedSessionID) {
            $plannedSessionTeacherGateway->insert([
                'enfPlannedSessionID' => $enfPlannedSessionID,
                'gibbonPersonID'      => $_POST['gibbonPersonID'] ?? $session->get('gibbonPersonID'),
                'timestampCreated'    => date('Y-m-d H:i:s'),
            ]);
        }

        $URL .= "&editID=$enfPlannedSessionID";
    }

    if (empty($enfPlannedSessionID)) {
        header("Location: {$URL}&return=error2");
        exit;
    }

    header("Location: {$URLSuccess}");
}
