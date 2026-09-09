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
use Gibbon\Services\Format;
use Gibbon\Domain\System\DiscussionGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\DailyPlannerGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannerTaskGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\SessionStudentGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$date = $_POST['date'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Enrichment and Flow/planner.php&date=$date";

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/planner.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $dailyPlannerGateway = $container->get(DailyPlannerGateway::class);
    $sessionStudentGateway = $container->get(SessionStudentGateway::class);
    $plannerTasksGateway = $container->get(PlannerTaskGateway::class);
    $plannedSessionGateway = $container->get(PlannedSessionGateway::class);
    $gibbonPersonID = $session->get('gibbonPersonID');

    $enfPlannerEntryID = $_POST['enfPlannerEntryID'] ?? '';
    $enfPlannedSessionIDs = $_POST['enfPlannedSessionID'] ?? '';
    $date = $_POST['date'] ?? '';

    // Remove trailing whitespace
    $comment = trim(preg_replace('/^<p>|<\/p>$/i', '', $_POST['comment'] ?? ''));

    $plannerEntry = $dailyPlannerGateway->getByID($enfPlannerEntryID);
    $studentSessions = $sessionStudentGateway->selectSessionsByStudentsAndDate($gibbonPersonID, $date)->fetchGroupedUnique();
    $canModify = true;

    if (!empty($plannerEntry)) {
        $timeRemaining = time() - Format::timestamp($plannerEntry['timestampCreated']);
        $canModify = $timeRemaining < 300;
    }

    if (!$canModify) {
        $enfPlannedSessionIDs = [];
    }

    if (empty($date)) {
        header("Location: {$URL}&return=error1");
        exit;
    }

    if (empty($plannerEntry)) {
        // Create a new planner entry
        $data = [
            'gibbonPersonID' => $gibbonPersonID,
            'date'           => $_POST['date'] ?? '',
        ];

        // Validate the required values are present
        if (empty($data['date']) || empty($data['gibbonPersonID'])) {
            header("Location: {$URL}&return=error1'");
            exit;
        }

        // Validate that this record is unique
        if (!$dailyPlannerGateway->unique($data, ['gibbonPersonID', 'date'])) {
            header("Location: {$URL}&return=error7");
            exit;
        }

        $enfPlannerEntryID = $dailyPlannerGateway->insert($data);
    } else {
        $dailyPlannerGateway->update($enfPlannerEntryID, [
            'tasks' => !empty($tasks) ? json_encode($tasks) : null,
        ]);
    }

    // Validate the database relationships exist
    if (!$dailyPlannerGateway->exists($enfPlannerEntryID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Create session entries
    $partialFail = false;
    foreach ($enfPlannedSessionIDs as $enfBlockID => $enfPlannedSessionID) {
        $sessionDetails = $plannedSessionGateway->getSessionDetailsByID($enfPlannedSessionID);

        $data = [
            'enfPlannedSessionID' => $enfPlannedSessionID,
            'enfSessionID'        => $sessionDetails['enfSessionID'] ?? null,
            'enfBlockID'          => $enfBlockID ?? null,
            'gibbonSpaceID'       => $sessionDetails['gibbonSpaceID'] ?? null,
            'date'                => $date,
            'timeStart'           => $sessionDetails['timeStart'] ?? null,
            'timeEnd'             => $sessionDetails['timeEnd'] ?? null,
            'block'               => $sessionDetails['block'] ?? null,
            'type'                => $sessionDetails['type'] ?? null,
            'focus'               => $sessionDetails['focus'] ?? null,
            'comment'             => $comment,
            'locked'              => 'N',
        ];

        $enfSessionStudentID = $sessionStudentGateway->insertAndUpdate($data + [
            'gibbonPersonID'         => $session->get('gibbonPersonID'),
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

    // Build the discussion entry
    $discussionGateway = $container->get(DiscussionGateway::class);

    if (!empty($comment)) {
        $data = [
            'foreignTable'         => 'enfPlannerEntry',
            'foreignTableID'       => $enfPlannerEntryID,
            'gibbonModuleID'       => getModuleIDFromName($connection2, 'Enrichment and Flow'),
            'gibbonPersonID'       => $session->get('gibbonPersonID'),
            'gibbonPersonIDTarget' => $session->get('gibbonPersonID'),
            'comment'              => $comment,
            'type'                 => 'Planner Entry',
            'attachmentType'       => null,
            'attachmentLocation'   => null,
        ];

        // Validate the required values are present
        if (empty($data['type']) || empty($data['comment']) || (!is_null($data['attachmentType']) && empty($data['attachmentLocation']))) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }

        // Insert the record
        $inserted = $discussionGateway->insert($data);
        $partialFail &= !$inserted;
    }

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}");
}
