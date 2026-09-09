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

use Gibbon\Data\Validator;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionTeacherGateway;
use Gibbon\Http\Url;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$enfPlannedSessionID = $_POST['enfPlannedSessionID'] ?? '';
$mode = $_REQUEST['mode'] ?? '';

$URL = $mode == 'manage'
    ? Url::fromModuleRoute('Enrichment and Flow', 'sessions_view')
    : Url::fromModuleRoute('Enrichment and Flow', 'sessions_my');

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/sessions_my_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($enfPlannedSessionID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $plannedSessionGateway = $container->get(PlannedSessionGateway::class);
    $plannedSessionTeacherGateway = $container->get(PlannedSessionTeacherGateway::class);
    
    $values = $plannedSessionGateway->getByID($enfPlannedSessionID);
    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $teachers = $plannedSessionTeacherGateway->selectBy(['enfPlannedSessionID' => $enfPlannedSessionID])->fetchAll();

    if (count($teachers) > 1 && $mode != 'manage') {
        // Remove the teacher from the session
        $deleted = $plannedSessionTeacherGateway->deleteWhere(['enfPlannedSessionID' => $enfPlannedSessionID, 'gibbonPersonID' => $session->get('gibbonPersonID')]);
    } else {
        // Cancel the session and remove all teachers
        $deleted = $plannedSessionGateway->delete($enfPlannedSessionID);
        $plannedSessionTeacherGateway->deleteWhere(['enfPlannedSessionID' => $enfPlannedSessionID]);
    }
    

    $URL .= !$deleted
        ? '&return=error2'
        : '&return=success0';

    header("Location: {$URL}");
}
