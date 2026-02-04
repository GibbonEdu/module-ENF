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
use Gibbon\Module\EnrichmentandFlow\Domain\SessionStudentGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$enfPlannedSessionID = $_REQUEST['enfPlannedSessionID'] ?? '';
$enfSessionStudentID = $_REQUEST['enfSessionStudentID'] ?? '';

$URL = Url::fromModuleRoute('Enrichment and Flow', 'sessions_my');

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/sessions_my_addEdit.php') == false) {
    header("Location: {$URL}&return=error0");
    exit;
} else {
    // Proceed!
    $sessionStudentGateway = $container->get(SessionStudentGateway::class);
    $plannedSessionGateway = $container->get(PlannedSessionGateway::class);
    $sessionGateway = $container->get(SessionGateway::class);

    // Validate database records
    if (!$sessionStudentGateway->exists($enfSessionStudentID)) {
        header("Location: {$URL}&return=error2");
        return;
    }

    // Validate database records
    $plannedSession = $plannedSessionGateway->getByID($enfPlannedSessionID);
    $sessionDetails = $sessionGateway->getByID($plannedSession['enfSessionID'] ?? '');
    if (empty($plannedSession) || empty($sessionDetails) ) {
        header("Location: {$URL}&return=error2");
        return;
    }
    
    $data = [
        'enfPlannedSessionID'    => $enfPlannedSessionID,
        'enfSessionID'           => $plannedSession['enfSessionID'],
        'gibbonSpaceID'          => $plannedSession['gibbonSpaceID'],
        'type'                   => $sessionDetails['type'],
        'focus'                  => $sessionDetails['focus'],
        'status'                 => 'Joining Session',
        'gibbonPersonIDModified' => $session->get('gibbonPersonID'),
        'timestampModified'      => date('Y-m-d H:i:s'),

    ];

    // Validate the required values are present
    if (empty($data['enfPlannedSessionID']) || empty($data['enfSessionID'])) {
        header("Location: {$URL}&return=error1");
        exit;
    }

    $updated = $sessionStudentGateway->update($enfSessionStudentID, $data);

    if (!$updated) {
        header("Location: {$URL}&return=error2");
        exit;
    }

    header("Location: {$URL}");
}
