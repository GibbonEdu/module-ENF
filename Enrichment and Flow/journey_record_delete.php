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

use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Module\EnrichmentandFlow\Domain\JourneyGateway;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/journey_record_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $enfJourneyID = $_GET['enfJourneyID'] ?? '';
    $search = $_GET['search'] ?? '';

    if (empty($enfJourneyID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $container->get(JourneyGateway::class)->getByID($enfJourneyID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    if ($values['status'] != 'Current - Pending') {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $form = DeleteForm::createForm($session->get('absoluteURL')."/modules/Enrichment and Flow/journey_record_deleteProcess.php?search=$search");
    echo $form->getOutput();
}
