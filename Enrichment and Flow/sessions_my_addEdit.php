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

use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Form;
use Gibbon\Http\Url;
use Gibbon\Module\EnrichmentandFlow\Domain\BlockFacilityGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\BlockGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionTeacherGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\SessionGateway;
use Gibbon\Support\Facades\Access;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/sessions_my_addEdit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $enfPlannedSessionID = $_REQUEST['enfPlannedSessionID'] ?? '';
    $enfBlockID = $_REQUEST['enfBlockID'] ?? '';
    $mode = $_REQUEST['mode'] ?? '';

    $page->breadcrumbs
        ->add(__m('My Sessions'), 'sessions_my.php')
        ->add(!empty($enfPlannedSessionID) ? __m('Change Session') : __m('Run a Session'));

    if (isset($_GET['editID'])) {
        $page->return->setEditLink(Url::fromModuleRoute('Enrichment and Flow', 'sessions_my_addEdit')->withQueryParam('enfPlannedSessionID', $_GET['editID']));
    }

    $blockGateway = $container->get(BlockGateway::class);

    $block = $blockGateway->getByID($enfBlockID);
    $blocks = $blockGateway->selectBlocks()->fetchAll();

    if (empty($block) || empty($blocks)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $sessionGateway = $container->get(SessionGateway::class);
    $plannedSessionGateway = $container->get(PlannedSessionGateway::class);
    $plannedSessionTeacherGateway = $container->get(PlannedSessionTeacherGateway::class);

    $values = $plannedSessionGateway->getByID($enfPlannedSessionID);
    
    $facilities = $container->get(BlockFacilityGateway::class)->selectAvailableFacilitiesListByBlock($enfBlockID, $enfPlannedSessionID)->fetchKeyPair();
    $facilities['Other'] = __('Other');

    $form = Form::create('sessionAddEdit', Url::fromModuleRoute('Enrichment and Flow', 'sessions_my_addEditProcess')->directLink());
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('enfPlannedSessionID', $enfPlannedSessionID);
    $form->addHiddenValue('mode', $mode);

    $form->addSelect('enfBlockID')
        ->label(__('Block'))
        ->fromArray($blocks, 'enfBlockID', 'name')
        ->required()
        ->selected($block['enfBlockID']);

    $canManage = Access::allows('Enrichment and Flow', 'sessions_view', 'All Sessions_manage');
    if ($mode == 'manage' && $canManage) {
        $teacher = $plannedSessionTeacherGateway->selectTeachersByPlannedSession($enfPlannedSessionID, date('Y-m-d'))->fetch();

        $form->addSelectStaff('gibbonPersonID')
            ->label(__('Person'))
            ->placeholder()
            ->required()
            ->selected($teacher['gibbonPersonID'] ?? '');
    }

    if (!empty($enfPlannedSessionID)) {

        if ($canManage) {
            $sessions = $sessionGateway->selectSessionList()->fetchAll();
            $form->addSearchSelect('enfSessionID')
                ->label(__('Session'))
                ->fromArray($sessions, 'enfSessionID', 'focus', 'type')
                ->placeholder()
                ->required()
                ->selected($values['enfSessionID']);
        } else {
            $sessionDetails = $sessionGateway->getByID($values['enfSessionID']);
            $form->addHiddenValue('enfSessionID', $values['enfSessionID']);
            $form->addTextField('session')
                ->label(__('Session'))
                ->readOnly()
                ->setValue($sessionDetails['focus']);
        }
    } else {
        $sessions = $sessionGateway->selectSessionList()->fetchAll();
        $sessions[] = ['type' => '+', 'enfSessionID' => 'Create', 'focus' => __('Create a Session')];
        $form->addSearchSelect('enfSessionID')
            ->label(__('Session'))
            ->fromArray($sessions, 'enfSessionID', 'focus', 'type')
            ->placeholder()
            ->required();

        $form->toggleVisibilityByClass('newSession')->onSelect('enfSessionID')->when('Create');

        $types = ['Consolidation', 'Extension', 'Innovation', 'Enrichment', 'Other'];
        $row = $form->addRow()->setClass('newSession');
        $row->addSelect('type')
            ->label(__('Type'))
            ->fromArray($types)
            ->placeholder()
            ->required();

        $row = $form->addRow()->setClass('newSession');
        $row->addTextField('focus')
            ->label(__('Focus'))
            ->required()
            ->maxLength(120);

        $row = $form->addRow()->setClass('newSession');
        $row->addNumber('maxStudents')
            ->label(__('Maximum Students'))
            ->required()
            ->maximum(99);
    }
    
    if (!empty($enfPlannedSessionID) && empty($values['enfBlockFacilityID'])) {
        $values['enfBlockFacilityID'] = 'Other';
        $values['gibbonSpaceID'] = str_pad($values['gibbonSpaceID'] ?? '', 10, '0', STR_PAD_LEFT);
    }

    $form->addSearchSelect('enfBlockFacilityID')
        ->label(__('Facility'))
        ->fromArray($facilities)
        ->placeholder()
        ->required();

    $form->toggleVisibilityByClass('otherSpace')->onSelect('enfBlockFacilityID')->when('Other');

    $row = $form->addRow()->setClass('otherSpace');
    $row->addSelectSpace('gibbonSpaceID')
        ->label(__('Other Facility'))
        ->placeholder()
        ->required();

    $row = $form->addRow();
        $row->addLabel('unlisted', __('Unlisted'))->description(__('If checked, this session will not be available for students to sign-up, but teachers can put students in this session.'));
        $row->addYesNo('unlisted')->required();

    $form->addRow()->addColumn()->addEditor('notes')
        ->label(__('Notes').' ('.__('Optional'.')'))
        ->minimalMode()
        ->setRows(3);

    $form->addRow('submit')->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
