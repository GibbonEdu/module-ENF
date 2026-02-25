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
use Gibbon\Forms\Form;
use Gibbon\Module\EnrichmentandFlow\Domain\BlockFacilityGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\SessionGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\EnrichmentandFlow\Domain\BlockGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\SessionStudentGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/sessions_my_addEdit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $enfPlannedSessionID = $_REQUEST['enfPlannedSessionID'] ?? '';
    $enfSessionStudentID = $_REQUEST['enfSessionStudentID'] ?? '';
    $gibbonPersonID = $_REQUEST['gibbonPersonID'] ?? '';
    $source = $_REQUEST['source'] ?? '';

    $sessionStudentGateway = $container->get(SessionStudentGateway::class);
    $plannedSessionGateway = $container->get(PlannedSessionGateway::class);
    
    $studentSession = $sessionStudentGateway->getByID($enfSessionStudentID);
    if (empty($studentSession) || $studentSession['gibbonPersonID'] != $gibbonPersonID) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }   
    
    $student = $container->get(UserGateway::class)->getByID($studentSession['gibbonPersonID'], ['surname', 'preferredName']);
    if (empty($student)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }  

    $sessions = $plannedSessionGateway->selectPlannedSessionListByBlock($studentSession['enfBlockID'])->fetchAll();

    $form = Form::create('sessionStudentMove', Url::fromModuleRoute('Enrichment and Flow', 'sessions_my_studentMoveProcess')->directLink());
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('enfBlockID', $studentSession['enfBlockID']);
    $form->addHiddenValue('enfSessionStudentID', $studentSession['enfSessionStudentID']);
    $form->addHiddenValue('gibbonPersonID', $studentSession['gibbonPersonID']);
    $form->addHiddenValue('source', $source);

    $form->addTextField('person')
        ->label(__('Student'))
        ->readonly()
        ->setValue(Format::name('', $student['preferredName'], $student['surname'], 'Student'));

    $form->addSelect('enfPlannedSessionID')
        ->label(__('Current Sessions'))
        ->fromArray($sessions, 'enfPlannedSessionID', 'focus', 'type')
        ->placeholder()
        ->required();

    $form->addRow('submit')->addSubmit();

    echo $form->getOutput();
}
