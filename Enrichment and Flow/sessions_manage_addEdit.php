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
use Gibbon\Module\EnrichmentandFlow\Domain\SessionGateway;


if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/sessions_manage_addEdit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $enfSessionID = $_REQUEST['enfSessionID'] ?? '';

    $page->breadcrumbs
        ->add(__m('Manage Sessions'), 'sessions_manage.php')
        ->add(!empty($enfSessionID) ? __m('Edit Session') : __m('Add Session'));

    if (isset($_GET['editID'])) {
        $page->return->setEditLink(Url::fromModuleRoute('Enrichment and Flow', 'sessions_manage_addEdit')->withQueryParam('enfSessionID', $_GET['editID']));
    }

    $values = $container->get(SessionGateway::class)->getByID($enfSessionID);

    $form = Form::create('sessionAddEdit', Url::fromModuleRoute('Enrichment and Flow', 'sessions_manage_addEditProcess')->directLink());

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('enfSessionID', $enfSessionID);

    $types = ['Consolidation', 'Extension', 'Innovation', 'Enrichment'];
    $form->addSelect('type')
        ->label(__('Type'))
        ->fromArray($types)
        ->placeholder()
        ->required();

    $form->addTextField('focus')
        ->label(__('Focus'))
        ->required()
        ->maxLength(120);

    $form->addNumber('maxStudents')
        ->label(__('Maximum Students'))
        ->required()
        ->maximum(99);

    $form->addRow()->addColumn()->addEditor('description')
        ->label(__('Description'))
        ->minimalMode()
        ->setRows(5);

    $form->addRow('submit')->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
