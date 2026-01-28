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
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionTeacherGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\SessionStudentGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$enfPlannedSessionID = $_REQUEST['enfPlannedSessionID'] ?? '';
$enfBlockID = $_REQUEST['enfBlockID'] ?? '';
$date = $_REQUEST['date'] ?? '';

$URL = Url::fromModuleRoute('Enrichment and Flow', 'sessions_view_addEditStudent')->withQueryParams(['enfPlannedSessionID' => $enfPlannedSessionID, 'enfBlockID' => $enfBlockID, 'date' => $date]);

$URLSuccess = Url::fromModuleRoute('Enrichment and Flow', 'sessions_view')->withQueryParams(['enfPlannedSessionID' => $enfPlannedSessionID, 'enfBlockID' => $enfBlockID, 'date' => $date]);

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/sessions_view_addEditStudent.php') == false) {
    header("Location: {$URL}&return=error0");
    exit;
} else {
    // Proceed!
    $sessionStudentGateway = $container->get(SessionStudentGateway::class);
    $plannedSessionGateway = $container->get(PlannedSessionGateway::class);
    $plannedSessionTeacherGateway = $container->get(PlannedSessionTeacherGateway::class);

    $students = $_POST['gibbonPersonIDList'] ?? [];
    if (empty($students)) {
        header("Location: {$URL}&return=error1");
        exit;
    }

    $data = [
        'enfPlannedSessionID' => $enfPlannedSessionID,
        'enfSessionID'        => $_POST['enfSessionID'] ?? null,
        'enfBlockID'          => $enfBlockID,
        'gibbonSpaceID'       => $_POST['gibbonSpaceID'] ?? null,
        'date'                => $date,
        'timeStart'           => $_POST['timeStart'] ?? null,
        'timeEnd'             => $_POST['timeEnd'] ?? null,
        'block'               => $_POST['block'] ?? null,
        'type'                => $_POST['type'] ?? null,
        'focus'               => $_POST['focus'] ?? null,
        'comment'             => $_POST['comment'] ?? null,
        'locked'              => $_POST['locked'] ?? 'N',
    ];

    // Validate the required values are present
    if (empty($data['enfBlockID']) || empty($data['enfPlannedSessionID']) || empty($data['date']) ) {
        header("Location: {$URL}&return=error1");
        exit;
    }

    // Validate database records
    if (!$plannedSessionGateway->exists($enfPlannedSessionID)) {
        header("Location: {$URL}&return=error2");
        return;
    }

    $partialFail = false;
    // Update or insert students
    foreach ($students as $gibbonPersonIDStudent) {

        $enfSessionStudentID = $sessionStudentGateway->insertAndUpdate($data + [
            'gibbonPersonID'         => $gibbonPersonIDStudent,
            'gibbonPersonIDCreated'  => $session->get('gibbonPersonID'),
            'timestampCreated'       => date('Y-m-d H:i:s'),
            'gibbonPersonIDModified' => $session->get('gibbonPersonID'),
            'timestampModified'      => date('Y-m-d H:i:s'),
        ], $data + [
            'gibbonPersonIDModified' => $session->get('gibbonPersonID'),
            'timestampModified'      => date('Y-m-d H:i:s'),
        ]);

        $partialFail &= empty($enfSessionStudentID);
    }

    if ($partialFail) {
        header("Location: {$URL}&return=error2");
        exit;
    }

    header("Location: {$URLSuccess}");
}
