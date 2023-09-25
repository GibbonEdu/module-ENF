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
use Gibbon\FileUploader;
use Gibbon\Services\Format;
use Gibbon\Module\EnrichmentandFlow\Domain\JourneyGateway;
use Gibbon\Domain\System\DiscussionGateway;
use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\NotificationGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$_POST = $container->get(Validator::class)->sanitize($_POST);

$enfJourneyID = $_GET['enfJourneyID'] ?? '';
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$gibbonPersonIDStudent = isset($_GET['gibbonPersonIDStudent'])? $_GET['gibbonPersonIDStudent'] : '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Enrichment and Flow/journey_manage_edit.php&search=$search&status=$status&gibbonPersonIDStudent=$gibbonPersonIDStudent&enfJourneyID=$enfJourneyID";

$highestAction = getHighestGroupedAction($guid, '/modules/Enrichment and Flow/journey_manage_edit.php', $connection2);

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/journey_manage_edit.php') == false || $highestAction == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($enfJourneyID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $journeyGateway = $container->get(JourneyGateway::class);
    $result = $container->get(JourneyGateway::class)->selectJourneyByID($enfJourneyID);

    if ($result->rowCount() != 1) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $values = $result->fetch();

    if ($highestAction != 'Manage Journey_all' && $values['gibbonPersonIDSchoolMentor'] != $session->get('gibbonPersonID')) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit();
    }

    $discussionGateway = $container->get(DiscussionGateway::class);

    $data = [
        'foreignTable'         => 'enfJourney',
        'foreignTableID'       => $enfJourneyID,
        'gibbonModuleID'       => getModuleIDFromName($connection2, 'Enrichment and Flow'),
        'gibbonPersonID'       => $session->get('gibbonPersonID'),
        'gibbonPersonIDTarget' => $values['gibbonPersonIDStudent'],
        'comment'              => $_POST['comment'] ?? '',
        'type'                 => $_POST['status'] ?? 'Comment',
    ];

    //If approved, get last evidence to store in journey later
    if ($data['type'] == 'Complete - Approved') {
        $data['tag'] = 'success';
        $logs = $discussionGateway->selectDiscussionByContext('enfJourney', $enfJourneyID, 'Evidence', 'DESC');
        if ($logs->rowCount() > 0) {
            $log = $logs->fetch();
            $evidenceType = $log['attachmentType'];
            $evidenceLocation = $log['attachmentLocation'];
        }
    } else if ($data['type'] == 'Evidence Not Yet Approved') {
        $data['tag'] = 'warning';
    }

    // Validate the required values are present
    if (empty($data['comment']) || ($values['status'] == 'Complete - Pending' && $data['type'] == 'Comment')) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Insert the record
    $inserted = $discussionGateway->insert($data);

    //Update the journey
    $dataJourney = [
        'status'                        => ($data['type'] == 'Comment') ? $values['status'] : $data['type'],
        'timestampCompleteApproved'     => ($data['type'] == 'Complete - Approved') ? date('Y-m-d H:i:s') : null,
        'gibbonPersonIDApproval'        => ($data['type'] == 'Complete - Approved') ? $session->get('gibbonPersonID'): null,
        'evidenceType'                  => (!empty($evidenceType)) ? $evidenceType : null,
        'evidenceLocation'              => (!empty($evidenceLocation)) ? $evidenceLocation : null
    ];
    $updated = $journeyGateway->update($enfJourneyID, $dataJourney);

    //Notify student
    $notificationGateway = new NotificationGateway($pdo);
    $notificationSender = new NotificationSender($notificationGateway, $session);
    if ($data['type'] == 'Complete - Approved') {
        $notificationString = __m('{mentor} has approved your request for completion of {type} {name}.', ['mentor' => Format::name('', $session->get('preferredName'), $session->get('surname'), 'Student', false, true), 'type' => strtolower($values['type']), 'name' => $values['name']]);
    }
    else if ($data['type'] == 'Evidence Not Yet Approved') {
        $notificationString = __m('{mentor} has responded to your request for completion of {type} {name}, but your evidence has not been approved.', ['mentor' => Format::name('', $session->get('preferredName'), $session->get('surname'), 'Student', false, true), 'type' => strtolower($values['type']), 'name' => $values['name']]);
    }
    else {
        $notificationString = __m('{mentor} has added to the journey log for the {type} {name}.', ['mentor' => Format::name('', $session->get('preferredName'), $session->get('surname'), 'Student', false, true), 'type' => strtolower($values['type']), 'name' => $values['name']]);
    }
    $notificationSender->addNotification($values['gibbonPersonIDStudent'], $notificationString, "Enrichment and Flow", "/index.php?q=/modules/Enrichment and Flow/journey_record_edit.php&enfJourneyID=$enfJourneyID");
    $notificationSender->sendNotifications();


    $URL .= !$inserted && !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
