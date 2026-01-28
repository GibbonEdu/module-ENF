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
use Gibbon\Support\Facades\Access;
use Gibbon\Services\Format;
use Gibbon\Module\EnrichmentandFlow\Domain\SessionStudentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/sessions_view_addEditStudent.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonPersonIDStudent = $_REQUEST['gibbonPersonIDStudent'] ?? '';
    $enfPlannedSessionID = $_REQUEST['enfPlannedSessionID'] ?? '';
    $enfBlockID = $_REQUEST['enfBlockID'] ?? '';
    $date = $_REQUEST['date'] ?? '';

    $page->breadcrumbs
        ->add(__m('All Sessions'), 'sessions_view.php')
        ->add(__m('Add/Edit Student Session'));

    if (isset($_GET['editID'])) {
        $page->return->setEditLink(Url::fromModuleRoute('Enrichment and Flow', 'sessions_view_addEditStudent')->withQueryParam('enfPlannedSessionID', $_GET['editID']));
    }

    if (empty($date) || empty($enfBlockID) || empty($enfPlannedSessionID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }
    
    $sessionGateway = $container->get(SessionGateway::class);
    $sessionStudentGateway = $container->get(SessionStudentGateway::class);
    $plannedSessionGateway = $container->get(PlannedSessionGateway::class);

    $block = $container->get(BlockGateway::class)->getByID($enfBlockID);
    $plannedSession = $plannedSessionGateway->getByID($enfPlannedSessionID);
    $sessionDetails = $sessionGateway->getByID($plannedSession['enfSessionID'] ?? '');
    if (empty($block) || empty($plannedSession) || empty($sessionDetails)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    
    $form = Form::create('sessionAddEdit', Url::fromModuleRoute('Enrichment and Flow', 'sessions_view_addEditStudentProcess')->directLink());
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('date', $date);
    $form->addHiddenValue('timeStart', $block['timeStart']);
    $form->addHiddenValue('timeEnd', $block['timeEnd']);
    $form->addHiddenValue('block', $block['name']);
    $form->addTextField('dateLabel')
        ->label(__('Date'))
        ->readOnly()
        ->setValue(Format::date($date));

    $form->addHiddenValue('enfBlockID', $enfBlockID);
    $form->addTextField('block')
        ->label(__('Block'))
        ->readOnly()
        ->setValue($block['name']);
    
    $form->addHiddenValue('enfPlannedSessionID', $enfPlannedSessionID);
    $form->addHiddenValue('enfSessionID', $plannedSession['enfSessionID']);
    $form->addHiddenValue('gibbonSpaceID', $plannedSession['gibbonSpaceID']);
    $form->addHiddenValue('type', $sessionDetails['type']);
    $form->addHiddenValue('focus', $sessionDetails['focus']);
    $form->addTextField('session')
        ->label(__('Session'))
        ->readOnly()
        ->setValue($sessionDetails['focus']);

    $students = $sessionStudentGateway->selectStudentsByBlock($enfBlockID, $date)->fetchAll();
    $students = array_reduce($students, function ($group, $student) {
        $group['students'][$student['gibbonPersonID']] = Format::name('', $student['preferredName'], $student['surname'], 'Student', true) . ' - ' . $student['formGroup'];
        $group['form'][$student['gibbonPersonID']] = $student['formGroup'];
        return $group;
    }, []);

    $col = $form->addRow()->addColumn();
    $multiSelect = $col->addMultiSelect('gibbonPersonIDList')
        ->addSortableAttribute('Form', $students['form'])
        ->required();

    $multiSelect->destination()->fromArray(!empty($gibbonPersonIDStudent)? [$gibbonPersonIDStudent] : []);
    $multiSelect->source()->fromArray($students['students']);

    $form->addYesNo('locked')
        ->required()
        ->label(__('Locked'))
        ->selected('Y');

    $form->addRow()->addColumn()->addTextArea('comment')
        ->label(__('Comment'))
        ->setRows(3);

    $form->addRow('submit')->addSubmit();

    echo $form->getOutput();
}
