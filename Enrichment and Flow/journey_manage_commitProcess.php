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
use Gibbon\Module\EnrichmentandFlow\Domain\JourneyGateway;

$_POST['address'] = '/modules/Enrichment and Flow/journey_manage_commitProcess.php';

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$gibbonPersonIDStudent = isset($_GET['gibbonPersonIDStudent'])? $_GET['gibbonPersonIDStudent'] : '';

// Proceed!
$enfJourneyID = $_GET['enfJourneyID'] ?? '';
$statusKey = $_GET['statusKey'] ?? '';
$response = $_GET['response'] ?? '';

$URLRedirect = $URL = $session->get('absoluteURL')."/index.php?q=/modules/Enrichment and Flow/journey_manage_edit.php&search=$search&status=$status&gibbonPersonIDStudent=$gibbonPersonIDStudent&enfJourneyID=$enfJourneyID";
if (empty($session->get('username'))) {
    $URLRedirect = $session->get('absoluteURL')."/index.php?q=/modules/Enrichment and Flow/journey_manage_commit_thanks.php";
}

if (empty($enfJourneyID) || empty($statusKey) || ($response != 'Y' && $response !='N')) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

$journeyGateway = $container->get(JourneyGateway::class);
$result = $journeyGateway->selectJourneyByID($enfJourneyID, $statusKey);

if ($result->rowCount() != 1) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit();
}

$values = $result->fetch();

$notificationGateway = new \Gibbon\Domain\System\NotificationGateway($pdo);
$notificationSender = new \Gibbon\Comms\NotificationSender($notificationGateway, $session);

if ($response == 'Y') {
    //Update record
    $data = array(
        'status' => 'Current',
    );
    $updated = $journeyGateway->update($enfJourneyID, $data);

    //Notify student
    $notificationText = __m('Your mentorship request for the Enrichment and Flow {type} {name} has been accepted.', array('type' => strtolower($values['type']), 'name' => $values['name']));
    $notificationSender->addNotification($values['gibbonPersonIDStudent'], $notificationText, 'Enrichment and Flow', "/index.php?q=/modules/Enrichment and Flow/journey_record_edit.php&enfJourneyID=$enfJourneyID");
	$notificationSender->sendNotifications();

    //Return to thanks page
    $URLRedirect .= "&return=success0&enfJourneyID=$enfJourneyID";
    header("Location: {$URLRedirect}");
}
else {
    //Delete record
    $deleted = $journeyGateway->delete($enfJourneyID);

    //Notify student
    $notificationText = __m('Your mentorship request for the Enrichment and Flow {type} {name} has been declined. Your enrolment has been deleted.', array('type' => strtolower($values['type']), 'name' => $values['name']));
    $notificationSender->addNotification($values['gibbonPersonIDStudent'], $notificationText, 'Enrichment and Flow', "/index.php?q=/modules/Enrichment and Flow/journey_record.php");
	$notificationSender->sendNotifications();

    //Return to thanks page
    $URLRedirect .= "&return=success1&enfJourneyID=$enfJourneyID";
    header("Location: {$URLRedirect}");
}
