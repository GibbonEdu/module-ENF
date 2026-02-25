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
use Gibbon\Module\EnrichmentandFlow\Domain\SessionStudentGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionTeacherGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$enfSessionStudentID = $_REQUEST['enfSessionStudentID'] ?? '';
$gibbonPersonID = $_REQUEST['gibbonPersonID'] ?? '';
$status = $_REQUEST['status'] ?? '';
$source = $_REQUEST['source'] ?? '';

$URL = $source == 'report' 
    ? Url::fromModuleRoute('Enrichment and Flow', 'report_sessions_view')
    : Url::fromModuleRoute('Enrichment and Flow', 'sessions_my');

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/sessions_my_addEdit.php') == false) {
    header("Location: {$URL}&return=error0");
    exit;
} else {
    // Proceed!
    $sessionStudentGateway = $container->get(SessionStudentGateway::class);

    $data = [
        'status' => $_REQUEST['status'] ?? '',
    ];

    // Validate the required values are present
    if (empty($enfSessionStudentID) || empty($data['status'])) {
        header("Location: {$URL}&return=error1");
        exit;
    }

    // Check for valid status
    if ($data['status'] != 'Missing' && $data['status'] != 'Present') {
        header("Location: {$URL}&return=error1");
        exit;
    }

    // Validate database records
    if (!$sessionStudentGateway->exists($enfSessionStudentID)) {
        header("Location: {$URL}&return=error2");
        return;
    }

    $sessionStudentGateway->update($enfSessionStudentID, $data);

    header("Location: {$URL}");
}
