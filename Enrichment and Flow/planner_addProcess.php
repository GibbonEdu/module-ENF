<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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
use Gibbon\Module\EnrichmentandFlow\Domain\DailyPlannerGateway;
use Gibbon\Domain\System\DiscussionGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['comment' => 'HTML']);

$date = $_POST['date'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Enrichment and Flow/planner.php&date=$date";

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/planner.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $dailyPlannerGatewayGateway = $container->get(DailyPlannerGateway::class);
    $gibbonPersonID = $session->get('gibbonPersonID');

    // Sort and save tasks as a JSON
    if (!empty($_POST['tasks'])) {
        $tasks = $_POST['tasks'] ?? [];
        $tasks = array_combine(array_keys($_POST['order'] ?? []), array_values($tasks));
        ksort($tasks);
    }

    $enfPlannerEntryID = $_POST['enfPlannerEntryID'] ?? '';

    if (empty($enfPlannerEntryID)) {
        // Create a new planner entry
        $data = [
            'gibbonPersonID' => $gibbonPersonID,
            'date'           => $_POST['date'] ?? '',
            'tasks'          => !empty($tasks) ? json_encode($tasks) : null,
        ];

        // Validate the required values are present
        if (empty($data['date']) || empty($data['gibbonPersonID'])) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }

        // Validate that this record is unique
        if (!$dailyPlannerGatewayGateway->unique($data, ['gibbonPersonID', 'date'])) {
            $URL .= '&return=error7';
            header("Location: {$URL}");
            exit;
        }

        $enfPlannerEntryID = $dailyPlannerGatewayGateway->insert($data);
    } else {
        $dailyPlannerGatewayGateway->update($enfPlannerEntryID, [
            'tasks' => !empty($tasks) ? json_encode($tasks) : null,
        ]);
    }

    // Validate the database relationships exist
    if (!$dailyPlannerGatewayGateway->exists($enfPlannerEntryID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Build the discussion entry
    $discussionGateway = $container->get(DiscussionGateway::class);

    // Remove trailing whitespace
    $comment = trim(preg_replace('/^<p>|<\/p>$/i', '', $_POST['comment'] ?? ''));

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

        $URL .= !$inserted
            ? "&return=error2"
            : "&return=success0";

    }

    header("Location: {$URL}");
}
