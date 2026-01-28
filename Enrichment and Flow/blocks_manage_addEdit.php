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
use Gibbon\Module\EnrichmentandFlow\Domain\BlockGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\EnrichmentandFlow\Domain\BlockFacilityGateway;


if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/blocks_manage_addEdit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $enfBlockID = $_REQUEST['enfBlockID'] ?? '';

    $page->breadcrumbs
        ->add(__m('Manage Blocks'), 'blocks_manage.php')
        ->add(!empty($enfBlockID) ? __m('Edit Block') : __m('Add Block'));

    if (isset($_GET['editID'])) {
        $page->return->setEditLink(Url::fromModuleRoute('Enrichment and Flow', 'blocks_manage_addEdit')->withQueryParam('enfBlockID', $_GET['editID']));
    }

    $values = $container->get(BlockGateway::class)->getByID($enfBlockID);
    $values['gibbonCourseID'] = str_pad(intval($values['gibbonCourseID'] ?? ''), 8, '0', STR_PAD_LEFT);

    $form = Form::create('blockAddEdit', Url::fromModuleRoute('Enrichment and Flow', 'blocks_manage_addEditProcess')->directLink());
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('enfBlockID', $enfBlockID);
    $form->addHiddenValue('gibbonSchoolYearID', $values['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID'));

    $form->addSection('Basic Details', __('Basic Details'));

    $form->addSelectCourseByYearGroup('gibbonCourseID', $session->get('gibbonSchoolYearID'), '004,005')
        ->label(__('Course'))
        ->required();

    $form->addTextField('name')
        ->label(__('Name'), __('Must be unique'))
        ->maxLength(60)
        ->required();

    $sqlWeekdays = "SELECT gibbonDaysOfWeekID as value, name FROM gibbonDaysOfWeek WHERE schoolDay='Y' ORDER BY sequenceNumber";
    $form->addSelect('gibbonDaysOfWeekID')
        ->label(__('Weekday'), __m('Sessions occur weekly'))
        ->fromQuery($pdo, $sqlWeekdays)
        ->placeholder()
        ->required();

    $form->addTime('timeStart')
        ->label(__('Time'))
        ->required()
        ->attach()
        ->addTime('timeEnd')
        ->required()
        ->chainedTo('timeStart');

    $form->addSection('Sign Up', __('Sign Up'));

    $form->addYesNo('signUpSameDay')
        ->label(__m('Same-day Sign up'), __('Students can only sign up on the same weekday after the specified time'));

    $form->toggleVisibilityByClass('signup')->onRadio('signUpSameDay')->when('Y');
    $row = $form->addRow()->setClass('signup');
    $row->addTime('signUpStart')
        ->label(__m('Sign up Start'));

    $form->addSection('Facilities', __('Facilities'));

    // CATEGORIES
    $addBlockButton = $form->getFactory()->createButton(__m('Add Facility'))->addClass('addBlock');

    $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
    $row = $blockTemplate->addRow();
        $row->addSelectSpace('gibbonSpaceID')->setClass('w-full mr-2')->required()->placeholder(__m('Facility'));

    // Custom Blocks
    $column = $form->addRow();
    $customBlocks = $column->addCustomBlocks('facilities', $session)
        ->fromTemplate($blockTemplate, true)
        ->settings(array('inputNameStrategy' => 'object', 'addOnEvent' => 'click', 'sortable' => true))
        ->placeholder(__m('Add facilities...'))
        ->addToolInput($addBlockButton);

    // Add existing facilities, or create some blank ones
    $facilities = $container->get(BlockFacilityGateway::class)->selectFacilitiesByBlock($enfBlockID)->fetchAll();
    if (!empty($facilities)) {
        foreach ($facilities ?? [] as $index => $facility) {
            $facility['primaryInput'] = $facility['space'];
            $facility['gibbonSpaceID'] = str_pad($facility['gibbonSpaceID'], 10, '0', STR_PAD_LEFT);
            $customBlocks->addBlock($index, $facility);
        }
    }

    $form->addSection('submit')->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
