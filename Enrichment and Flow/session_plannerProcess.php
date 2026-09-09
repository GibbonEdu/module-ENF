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
use Gibbon\Http\Url;
use Gibbon\Module\EnrichmentandFlow\Domain\BlockFacilityGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\BlockGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionTeacherGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\SessionGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\SessionStudentGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$date = $_POST['date'] ?? date('Y-m-d');

$URL = Url::fromModuleRoute('Enrichment and Flow', 'session_planner')->withQueryParams(['date' => $date]);

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/session_planner.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $sessionGateway = $container->get(SessionGateway::class);
    $blockGateway = $container->get(BlockGateway::class);
    $blockFacilityGateway = $container->get(BlockFacilityGateway::class);
    $plannedSessionGateway = $container->get(PlannedSessionGateway::class);
    $plannedSessionTeacherGateway = $container->get(PlannedSessionTeacherGateway::class);

    $partialFail = false;

    // echo '<pre>';
    // print_r($_POST);
    // echo '</pre>';

    // die();

    // Update session facilities
    $facilities = $_POST['facility'] ?? [];
    foreach ($facilities as $enfPlannedSessionID => $gibbonSpaceID) {
        $plannedSessionGateway->update($enfPlannedSessionID, ['gibbonSpaceID' => $gibbonSpaceID]);
    }

    // Update teachers per session
    $teachersByBlock = $_POST['teacher'] ?? [];
    
    foreach ($teachersByBlock as $enfBlockID => $teachers) {

        foreach ($teachers as $gibbonPersonID => $enfPlannedSessionID) {

            $enfPlannedSessionTeacherID = $plannedSessionTeacherGateway->getSessionTeacherByBlock($gibbonPersonID, $enfBlockID);

            if (!empty($enfPlannedSessionTeacherID) && empty($enfPlannedSessionID)) {
                $plannedSessionTeacherGateway->delete($enfPlannedSessionTeacherID);
            } elseif (!empty($enfPlannedSessionTeacherID)) {
                $plannedSessionTeacherGateway->update($enfPlannedSessionTeacherID, [
                    'enfPlannedSessionID' => $enfPlannedSessionID,
                ]);
            } elseif (!empty($enfPlannedSessionID)) {
                $plannedSessionTeacherGateway->insert([
                    'enfPlannedSessionID' => $enfPlannedSessionID,
                    'gibbonPersonID' => $gibbonPersonID,
                ]);
            }
        }
    }

    $URL .= !$partialFail
        ? "&return=success0"
        : "&return=warning1";

    header("Location: {$URL}");
}
